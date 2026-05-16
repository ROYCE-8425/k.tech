# AI Match Evaluation Suite

Lightweight evaluation framework for assessing CV-JD matching quality.

## Structure

```
evals/
├── README.md           ← this file
├── dataset_v1.json     ← evaluation dataset (test cases)
└── runner.py           ← evaluation runner with ablation modes
```

## Dataset Format

Each test case in `dataset_v1.json`:

```json
{
  "id": "case-001",
  "description": "Backend senior matching senior backend role",
  "candidate": { ... CandidatePayload fields ... },
  "job": { ... JobPayload fields ... },
  "expected": {
    "score_band": "high",          // "high" (≥80), "medium" (60-79), "low" (<60)
    "rank_label": "high_fit",      // high_fit | medium_fit | low_fit
    "must_match_skills": ["Python"],
    "must_miss_skills": ["Go"],
    "min_evidence_count": 1
  }
}
```

## Running

```bash
# Full evaluation (all features enabled)
python evals/runner.py

# Ablation modes
python evals/runner.py --mode exact_only       # exact/synonym baseline
python evals/runner.py --mode one_hop          # + one-hop graph
python evals/runner.py --mode two_hop          # + two-hop graph (default)
python evals/runner.py --mode feedback_on      # + feedback reranking
python evals/runner.py --mode feedback_off     # two-hop, no reranking

# Compare all modes
python evals/runner.py --compare
```

## Metrics Reported

- **Score band accuracy**: % of cases where actual score band matches expected
- **Rank label agreement**: % of cases where rank_label matches expected
- **Skill matching precision**: % of must_match_skills actually matched
- **Skill gap detection**: % of must_miss_skills correctly identified as missing
- **Evidence presence**: % of cases with ≥ expected evidence count
- **Retrieval method distribution**: count per retrieval mode
- **Provider usage**: which LLM provider was used

## Adding Test Cases

Add new objects to `dataset_v1.json`. Each case should test a specific
matching scenario (exact match, skill gap, seniority mismatch, etc.).

## Baseline Comparison

The `--compare` mode runs all 4 ablation variants and outputs a comparison
table showing how each feature contributes to matching accuracy.
