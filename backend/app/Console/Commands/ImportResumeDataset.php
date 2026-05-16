<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MLTrainingData;
use App\Services\ML\MLFeatureExtractor;
use App\Services\ML\MLScoringPipeline;
use Illuminate\Support\Facades\DB;

class ImportResumeDataset extends Command
{
    protected $signature = 'ml:import-resume-dataset 
                            {--path= : Path to CSV file}
                            {--limit=500 : Number of records to import}
                            {--train : Train model after import}';

    protected $description = 'Import Resume Dataset from Kaggle and train ML model';

    /**
     * IT-related job categories and their base scores
     * Higher score = more relevant to IT
     */
    private const CATEGORY_SCORES = [
        // Excellent IT roles (85-100)
        'Data Science' => 95,
        'Web Designing' => 90,
        'Python Developer' => 92,
        'Java Developer' => 92,
        'Blockchain' => 88,
        'Database' => 85,
        'DevOps Engineer' => 90,
        'DotNet Developer' => 88,
        'ETL Developer' => 82,
        'Network Security Engineer' => 88,
        'Automation Testing' => 80,
        'Testing' => 75,
        'Hadoop' => 85,
        
        // Good IT-adjacent roles (65-84)
        'Business Analyst' => 70,
        'SAP Developer' => 75,
        'Operations Manager' => 65,
        'PMO' => 68,
        'Mechanical Engineer' => 55,
        'Electrical Engineering' => 58,
        'Civil Engineer' => 50,
        
        // Less relevant (30-64)
        'HR' => 45,
        'Sales' => 40,
        'Health and fitness' => 35,
        'Advocate' => 30,
        'Arts' => 35,
        'Teacher' => 40,
        'Chef' => 25,
        'Aviation' => 45,
        'Accountant' => 45,
        'Public-Relations' => 40,
        'Apparel' => 30,
        'BPO' => 50,
        'Consultant' => 55,
        'Digital-Media' => 60,
        'Designing' => 55,
        'Agriculture' => 25,
        'Automobile' => 45,
        'Banking' => 50,
        'Finance' => 48,
        'Construction' => 35,
        'Food and Beverages' => 30,
    ];

    /**
     * IT Keywords to extract from resume text
     */
    private const IT_KEYWORDS = [
        // Languages
        'php', 'python', 'java', 'javascript', 'typescript', 'c++', 'c#', 'ruby', 'go', 'golang',
        'swift', 'kotlin', 'scala', 'rust', 'perl', 'r programming',
        
        // Frameworks
        'laravel', 'symfony', 'django', 'flask', 'spring', 'react', 'angular', 'vue', 'node.js',
        'express', '.net', 'asp.net', 'rails', 'jquery', 'bootstrap',
        
        // Databases
        'mysql', 'postgresql', 'mongodb', 'redis', 'elasticsearch', 'oracle', 'sql server',
        'sqlite', 'cassandra', 'dynamodb', 'firebase',
        
        // DevOps/Cloud
        'docker', 'kubernetes', 'aws', 'azure', 'gcp', 'jenkins', 'git', 'github', 'gitlab',
        'ci/cd', 'terraform', 'ansible', 'linux', 'nginx', 'apache',
        
        // Data Science/ML
        'machine learning', 'deep learning', 'tensorflow', 'pytorch', 'scikit-learn', 'pandas',
        'numpy', 'data science', 'neural network', 'nlp', 'computer vision',
        
        // Other
        'api', 'rest', 'graphql', 'microservices', 'agile', 'scrum', 'jira', 'testing',
        'unit test', 'selenium', 'blockchain', 'ethereum', 'smart contract',
    ];

    private const EDUCATION_KEYWORDS = [
        'phd' => 10,
        'doctorate' => 10,
        'master' => 8,
        'mba' => 7,
        'bachelor' => 6,
        'b.tech' => 6,
        'b.e.' => 6,
        'b.sc' => 5,
        'diploma' => 4,
        'certification' => 3,
        'certified' => 3,
    ];

    private const EXPERIENCE_PATTERNS = [
        '/(\d+)\+?\s*years?\s*(?:of\s*)?experience/i',
        '/experience\s*(?:of\s*)?(\d+)\+?\s*years?/i',
        '/(\d+)\s*yrs?\s*(?:of\s*)?(?:exp|experience)/i',
    ];

    public function handle()
    {
        $path = $this->option('path') ?: storage_path('app/ml_training/UpdatedResumeDataSet.csv');
        $limit = (int) $this->option('limit');
        $shouldTrain = $this->option('train');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $this->info("📁 Importing Resume Dataset from: {$path}");
        $this->info("📊 Limit: {$limit} records");
        $this->newLine();

        // Parse CSV
        $records = $this->parseCSV($path, $limit);
        $this->info("✓ Parsed " . count($records) . " records from CSV");

        // Process and extract features
        $this->info("🔄 Processing resumes and extracting features...");
        $bar = $this->output->createProgressBar(count($records));
        $bar->start();

        $trainingData = [];
        $featureExtractor = new MLFeatureExtractor();

        foreach ($records as $record) {
            $features = $this->extractFeaturesFromResume($record);
            $targetScore = $this->calculateTargetScore($record, $features);
            
            $trainingData[] = [
                'category' => $record['category'],
                'features' => $features,
                'target_score' => $targetScore,
                'resume_preview' => mb_substr($record['resume'], 0, 200),
            ];
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Save to database
        $this->info("💾 Saving to database...");
        $this->saveToDatabase($trainingData);
        $this->info("✓ Saved " . count($trainingData) . " training samples");

        // Show statistics
        $this->showStatistics($trainingData);

        // Train if requested
        if ($shouldTrain) {
            $this->newLine();
            $this->info("🎓 Training ML model...");
            $this->trainModel($trainingData);
        }

        $this->newLine();
        $this->info("✅ Import completed successfully!");
        
        return 0;
    }

    /**
     * Parse CSV file
     */
    private function parseCSV(string $path, int $limit): array
    {
        $records = [];
        $handle = fopen($path, 'r');
        
        if (!$handle) {
            throw new \Exception("Cannot open file: {$path}");
        }

        // Skip header
        $header = fgetcsv($handle);
        $categoryIdx = array_search('Category', $header);
        $resumeIdx = array_search('Resume', $header);

        if ($categoryIdx === false || $resumeIdx === false) {
            // Try lowercase
            $categoryIdx = array_search('category', array_map('strtolower', $header));
            $resumeIdx = array_search('resume', array_map('strtolower', $header));
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false && $count < $limit) {
            if (count($row) >= 2) {
                $records[] = [
                    'category' => trim($row[$categoryIdx] ?? $row[0] ?? ''),
                    'resume' => trim($row[$resumeIdx] ?? $row[1] ?? ''),
                ];
                $count++;
            }
        }

        fclose($handle);
        return $records;
    }

    /**
     * Extract features from resume text
     */
    private function extractFeaturesFromResume(array $record): array
    {
        $resumeText = strtolower($record['resume']);
        $category = $record['category'];

        // Count IT skills
        $mainSkillsCount = 0;
        $subSkillsCount = 0;
        $foundSkills = [];
        
        foreach (self::IT_KEYWORDS as $keyword) {
            if (str_contains($resumeText, strtolower($keyword))) {
                $foundSkills[] = $keyword;
                if (count($foundSkills) <= 6) {
                    $mainSkillsCount++;
                } else {
                    $subSkillsCount++;
                }
            }
        }
        $mainSkillsCount = min(6, $mainSkillsCount);
        $subSkillsCount = min(4, $subSkillsCount);

        // Extract experience years
        $experienceYears = 0;
        foreach (self::EXPERIENCE_PATTERNS as $pattern) {
            if (preg_match($pattern, $record['resume'], $matches)) {
                $experienceYears = max($experienceYears, (int) $matches[1]);
            }
        }
        $experienceYears = min(15, $experienceYears);

        // Education score
        $educationScore = 3; // Default
        foreach (self::EDUCATION_KEYWORDS as $keyword => $score) {
            if (str_contains($resumeText, $keyword)) {
                $educationScore = max($educationScore, $score);
            }
        }

        // Count projects (approximate)
        $projectsCount = 0;
        $projectPatterns = ['/project[s]?\s*[:\-]?\s*\d*/i', '/worked on/i', '/developed/i', '/built/i', '/created/i'];
        foreach ($projectPatterns as $pattern) {
            $projectsCount += preg_match_all($pattern, $record['resume']);
        }
        $projectsCount = min(10, max(0, (int) ($projectsCount / 2)));

        // Tech match (based on category being IT-related)
        $techMatchCount = isset(self::CATEGORY_SCORES[$category]) && self::CATEGORY_SCORES[$category] >= 75 
            ? min(10, $mainSkillsCount + 2) 
            : min(6, $mainSkillsCount);

        // Certifications count
        $certPatterns = ['/certif/i', '/certified/i', '/certificate/i', '/aws\s*certified/i', '/google\s*certified/i'];
        $certificationsCount = 0;
        foreach ($certPatterns as $pattern) {
            $certificationsCount += preg_match_all($pattern, $record['resume']);
        }
        $certificationsCount = min(5, $certificationsCount);

        // CV quality (based on length and structure)
        $wordCount = str_word_count($record['resume']);
        $cvQualityScore = 2; // Default
        if ($wordCount > 500) $cvQualityScore = 5;
        if ($wordCount > 800) $cvQualityScore = 7;
        if ($wordCount > 1200) $cvQualityScore = 10;

        // Soft skills
        $softSkillKeywords = ['leadership', 'communication', 'team', 'management', 'problem solving', 
            'analytical', 'critical thinking', 'collaboration', 'adaptable', 'creative'];
        $softSkillsCount = 0;
        foreach ($softSkillKeywords as $keyword) {
            if (str_contains($resumeText, $keyword)) {
                $softSkillsCount++;
            }
        }
        $softSkillsCount = min(6, $softSkillsCount);

        // Portfolio score (check for links)
        $portfolioScore = 0;
        if (preg_match('/github\.com/i', $record['resume'])) $portfolioScore += 4;
        if (preg_match('/linkedin\.com/i', $record['resume'])) $portfolioScore += 2;
        if (preg_match('/portfolio|website|blog/i', $record['resume'])) $portfolioScore += 2;
        $portfolioScore = min(8, $portfolioScore);

        return [
            'experience_years' => $experienceYears,
            'projects_count' => $projectsCount,
            'tech_match_count' => $techMatchCount,
            'main_skills_count' => $mainSkillsCount,
            'sub_skills_count' => $subSkillsCount,
            'certifications_count' => $certificationsCount,
            'education_score' => $educationScore,
            'cv_quality_score' => $cvQualityScore,
            'soft_skills_count' => $softSkillsCount,
            'portfolio_score' => $portfolioScore,
        ];
    }

    /**
     * Calculate target score based on category and features
     */
    private function calculateTargetScore(array $record, array $features): float
    {
        $category = $record['category'];
        
        // Base score from category
        $categoryScore = self::CATEGORY_SCORES[$category] ?? 50;
        
        // Adjust based on features (to add variance)
        $featureBonus = 0;
        $featureBonus += $features['experience_years'] * 1.5;
        $featureBonus += $features['main_skills_count'] * 2;
        $featureBonus += $features['projects_count'] * 1;
        $featureBonus += $features['education_score'] * 0.5;
        $featureBonus += $features['certifications_count'] * 1;
        $featureBonus += $features['cv_quality_score'] * 0.3;
        
        // Combine with some randomness for realistic variance
        $noise = (mt_rand(-500, 500) / 100); // -5 to +5
        $finalScore = ($categoryScore * 0.6) + ($featureBonus * 0.4) + $noise;
        
        // Clamp to 0-100
        return max(0, min(100, round($finalScore, 2)));
    }

    /**
     * Save training data to database
     */
    private function saveToDatabase(array $trainingData): void
    {
        // Clear old data
        MLTrainingData::truncate();
        
        $chunks = array_chunk($trainingData, 100);
        
        foreach ($chunks as $chunk) {
            $inserts = [];
            foreach ($chunk as $data) {
                $f = $data['features'];
                $inserts[] = [
                    // Features
                    'experience_years' => $f['experience_years'],
                    'projects_count' => $f['projects_count'],
                    'tech_match_count' => $f['tech_match_count'],
                    'main_skills_count' => $f['main_skills_count'],
                    'sub_skills_count' => $f['sub_skills_count'],
                    'certifications_count' => $f['certifications_count'],
                    'education_score' => $f['education_score'],
                    'cv_quality_score' => $f['cv_quality_score'],
                    'soft_skills_count' => $f['soft_skills_count'],
                    'portfolio_score' => $f['portfolio_score'],
                    
                    // Target/Label
                    'actual_score' => $data['target_score'],
                    'classification' => $this->scoreToClassification($data['target_score']),
                    
                    // Metadata
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('ml_training_data')->insert($inserts);
        }
    }

    /**
     * Convert score to classification label
     */
    private function scoreToClassification(float $score): string
    {
        if ($score >= 90) return 'Xuất sắc';
        if ($score >= 75) return 'Tốt';
        if ($score >= 60) return 'Khá';
        return 'Không phù hợp';
    }

    /**
     * Convert score to outcome label
     */
    private function scoreToOutcome(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'fair';
        return 'poor';
    }

    /**
     * Show statistics about imported data
     */
    private function showStatistics(array $trainingData): void
    {
        $this->newLine();
        $this->info("📈 IMPORT STATISTICS");
        $this->line(str_repeat('-', 50));

        // Category distribution
        $categories = array_count_values(array_column($trainingData, 'category'));
        arsort($categories);
        
        $this->info("Top Categories:");
        $i = 0;
        foreach ($categories as $cat => $count) {
            if ($i++ >= 10) break;
            $this->line("  • {$cat}: {$count}");
        }

        // Score distribution
        $scores = array_column($trainingData, 'target_score');
        $this->newLine();
        $this->info("Score Statistics:");
        $this->line("  • Min: " . min($scores));
        $this->line("  • Max: " . max($scores));
        $this->line("  • Mean: " . round(array_sum($scores) / count($scores), 2));

        // Outcome distribution
        $excellent = count(array_filter($scores, fn($s) => $s >= 90));
        $good = count(array_filter($scores, fn($s) => $s >= 75 && $s < 90));
        $fair = count(array_filter($scores, fn($s) => $s >= 60 && $s < 75));
        $poor = count(array_filter($scores, fn($s) => $s < 60));

        $this->newLine();
        $this->info("Classification Distribution:");
        $this->line("  • Excellent (90+): {$excellent}");
        $this->line("  • Good (75-89): {$good}");
        $this->line("  • Fair (60-74): {$fair}");
        $this->line("  • Poor (<60): {$poor}");
    }

    /**
     * Train ML model with imported data
     */
    private function trainModel(array $trainingData): void
    {
        $pipeline = new MLScoringPipeline();
        
        // Prepare training arrays
        $X = [];
        $y = [];
        
        foreach ($trainingData as $data) {
            $X[] = array_values($data['features']);
            $y[] = $data['target_score'];
        }

        // Train the model
        $this->info("Training with " . count($X) . " samples...");
        
        try {
            $result = $pipeline->trainFromFeatures($X, $y);
            
            if ($result['success']) {
                $this->info("✓ Training completed!");
                $this->line("  • Random Forest R²: " . round($result['rf_metrics']['r2_score'] ?? 0, 4));
                $this->line("  • Random Forest MAE: " . round($result['rf_metrics']['mae'] ?? 0, 2));
                $this->line("  • Weight R²: " . round($result['weight_metrics']['r2_score'] ?? 0, 4));
                
                $this->newLine();
                $this->info("Feature Importance:");
                $importance = $result['feature_importance'] ?? [];
                arsort($importance);
                foreach (array_slice($importance, 0, 5) as $feature => $imp) {
                    $bar = str_repeat('█', (int)($imp * 20));
                    $this->line("  • {$feature}: " . round($imp * 100, 1) . "% {$bar}");
                }
            } else {
                $this->error("Training failed: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("Training error: " . $e->getMessage());
        }
    }
}
