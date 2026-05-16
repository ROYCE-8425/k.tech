<?php

namespace App\Services;

/**
 * ĐỒ ÁN MÔN BẢO MẬT THÔNG TIN
 * ITSoloLevelingSecurity
 * 
 * Service mã hóa mật khẩu tùy chỉnh sử dụng:
 * - SHA-256 hashing
 * - Fibonacci sequence
 * - XOR encryption với chuỗi cá nhân hóa
 * 
 * @author IT Solo Leveling Team
 */
class ITSoloLevelingSecurity
{
    /**
     * Chuỗi cá nhân hóa của team (Team Signature)
     */
    private const TEAM_SIGNATURE = "It solo leveling";

    /**
     * Thuật toán mã hóa mật khẩu nâng cao với cá nhân hóa
     * 
     * Các bước thực hiện:
     * 1. Hash dữ liệu bằng SHA-256 (32 bytes binary)
     * 2. Lấy chuỗi cá nhân hóa team
     * 3. Sinh dãy Fibonacci (32 phần tử)
     * 4. XOR 3 lớp: (SHA-256 byte) ^ (Team byte) ^ (Fibonacci byte)
     * 5. Convert kết quả sang hexadecimal
     * 
     * @param string $data Dữ liệu cần hash (thường là password)
     * @return string Hash hex 64 ký tự
     */
    public function advancedPersonalizedHash(string $data): string
    {
        // Bước 1: Hash SHA-256 chuẩn
        // hash() với tham số true trả về dạng binary (32 bytes)
        $hash = hash('sha256', $data, true);
        
        // Chuyển binary sang mảng số nguyên (giá trị 0-255)
        // unpack('C*') chuyển mỗi byte thành số nguyên
        $hashBytes = unpack('C*', $hash); // Array index từ 1-32
        
        // Bước 2: Chuỗi cá nhân hóa
        $team = self::TEAM_SIGNATURE;
        $teamBytes = unpack('C*', $team);
        $teamLen = strlen($team);
        
        // Bước 3: Khởi tạo dãy Fibonacci
        // F(0) = 0, F(1) = 1, F(n) = F(n-1) + F(n-2)
        $fibo = [0, 1];
        for ($i = 2; $i <= 32; $i++) {
            $fibo[$i] = $fibo[$i-1] + $fibo[$i-2];
        }
        
        // Bước 4: XOR 3 lớp và chuyển sang hex
        $result = "";
        $i = 1;
        foreach ($hashBytes as $byte) {
            // Lấy byte của team (xoay vòng nếu team ngắn hơn 32 bytes)
            // Sử dụng modulo để lặp lại chuỗi team
            $tByte = $teamBytes[($i - 1) % $teamLen + 1];
            
            // Lấy số Fibonacci tương ứng (mod 256 để giữ trong phạm vi byte)
            $fByte = $fibo[$i] % 256;
            
            // Thực hiện XOR 3 lớp:
            // - $byte: byte từ SHA-256 hash
            // - $tByte: byte từ team signature
            // - $fByte: byte từ dãy Fibonacci
            $finalByte = $byte ^ $tByte ^ $fByte;
            
            // Chuyển sang hex (2 ký tự, pad 0 nếu cần)
            $result .= str_pad(dechex($finalByte), 2, "0", STR_PAD_LEFT);
            $i++;
        }
        
        return $result; // Kết quả: chuỗi hex 64 ký tự
    }

    /**
     * Hash password sử dụng thuật toán tùy chỉnh
     * Alias cho advancedPersonalizedHash() với tên rõ nghĩa
     * 
     * @param string $password Password cần hash
     * @return string Hashed password (64 ký tự hex)
     */
    public function protect(string $password): string
    {
        return $this->advancedPersonalizedHash($password);
    }

    /**
     * Verify password với hash đã lưu
     * 
     * @param string $input Password cần kiểm tra
     * @param string $hash Hash đã lưu trong database
     * @return bool True nếu password đúng
     */
    public function authenticate(string $input, string $hash): bool
    {
        $computedHash = $this->advancedPersonalizedHash($input);
        
        // So sánh hash an toàn (timing-attack resistant)
        return hash_equals($hash, $computedHash);
    }

    /**
     * Generate random salt (nếu cần thêm salt)
     * 
     * @param int $length Độ dài salt (bytes)
     * @return string Salt hex
     */
    public function generateSalt(int $length = 16): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash với salt (tùy chọn nâng cao)
     * 
     * @param string $data Dữ liệu cần hash
     * @param string $salt Salt
     * @return string Hash kết quả
     */
    public function hashWithSalt(string $data, string $salt): string
    {
        return $this->advancedPersonalizedHash($data . $salt);
    }

    /**
     * Demo thuật toán với các test cases
     * 
     * @return array Kết quả demo
     */
    public function demo(): array
    {
        $testCases = [
            "user_password_123",
            "admin@2024",
            "SecureP@ssw0rd!",
            "12345678",
            "It solo leveling"
        ];

        $results = [];
        foreach ($testCases as $password) {
            $hash = $this->protect($password);
            $verify = $this->authenticate($password, $hash);
            $wrongVerify = $this->authenticate($password . "wrong", $hash);

            $results[] = [
                'password' => $password,
                'hash' => $hash,
                'hash_length' => strlen($hash),
                'verify_correct' => $verify,
                'verify_wrong' => $wrongVerify
            ];
        }

        return $results;
    }

    /**
     * Phân tích chi tiết thuật toán (cho báo cáo)
     * 
     * @param string $data Dữ liệu test
     * @return array Phân tích từng bước
     */
    public function analyzeAlgorithm(string $data): array
    {
        // Bước 1: SHA-256
        $sha256Hash = hash('sha256', $data, true);
        $sha256Hex = hash('sha256', $data, false);
        $hashBytes = unpack('C*', $sha256Hash);

        // Bước 2: Team signature
        $team = self::TEAM_SIGNATURE;
        $teamBytes = unpack('C*', $team);
        $teamLen = strlen($team);

        // Bước 3: Fibonacci
        $fibo = [0, 1];
        for ($i = 2; $i <= 32; $i++) {
            $fibo[$i] = $fibo[$i-1] + $fibo[$i-2];
        }

        // Bước 4: XOR chi tiết
        $xorDetails = [];
        $i = 1;
        foreach ($hashBytes as $byte) {
            $tByte = $teamBytes[($i - 1) % $teamLen + 1];
            $fByte = $fibo[$i] % 256;
            $finalByte = $byte ^ $tByte ^ $fByte;

            $xorDetails[] = [
                'position' => $i,
                'sha256_byte' => $byte,
                'team_byte' => $tByte,
                'team_char' => chr($tByte),
                'fibo_value' => $fibo[$i],
                'fibo_byte' => $fByte,
                'xor_result' => $finalByte,
                'hex' => str_pad(dechex($finalByte), 2, "0", STR_PAD_LEFT)
            ];
            $i++;
        }

        return [
            'input' => $data,
            'sha256_hex' => $sha256Hex,
            'team_signature' => $team,
            'team_length' => $teamLen,
            'fibonacci_sequence' => array_slice($fibo, 0, 10), // First 10 only
            'xor_details' => array_slice($xorDetails, 0, 5), // First 5 bytes
            'final_hash' => $this->advancedPersonalizedHash($data)
        ];
    }

    /**
     * ========================================
     * THUẬT TOÁN NÂNG CAO: RUBIK CRYPTO HASH
     * ========================================
     * 
     * Thuật toán mã hóa Rubik Cube-inspired với các lớp bảo mật:
     * 1. SHA-256 Base Hash
     * 2. Rubik Rotation (Matrix rotation với số nguyên tố)
     * 3. XOR Layer (Team signature + Fibonacci)
     * 
     * Độ phức tạp: O(n + 32 × (4 + 8)) = O(n) với n = input length
     * Security level: High (multi-layer, personalized)
     */

    /**
     * 32 số nguyên tố đầu tiên (dùng cho Rubik Rotation)
     */
    private const PRIMES = [
        2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53,
        59, 61, 67, 71, 73, 79, 83, 89, 97, 101, 103, 107, 109, 113, 127, 131
    ];

    /**
     * Thuật toán mã hóa Rubik Crypto Hash
     * 
     * Kết hợp SHA-256, Matrix Rotation (Rubik Cube), và XOR multi-layer
     * 
     * @param string $data Dữ liệu cần hash
     * @return string Hash hex 64 ký tự
     */
    public function rubikCryptoHash(string $data): string
    {
        // ===== BƯỚC 1: SHA-256 BASE HASH =====
        $hash = hash('sha256', $data, true);
        $hashBytes = unpack('C*', $hash); // 32 bytes, index từ 1-32
        
        // ===== BƯỚC 2: RUBIK ROTATION =====
        // Sắp xếp 32 bytes vào ma trận 4 hàng × 8 cột
        $matrix = [];
        $idx = 1;
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $matrix[$row][$col] = $hashBytes[$idx];
                $idx++;
            }
        }
        
        // Thực hiện 32 bước rotation (Rubik-inspired)
        for ($step = 0; $step < 32; $step++) {
            $prime = self::PRIMES[$step];
            
            if ($step % 2 === 0) {
                // Bước chẵn: Xoay vòng HÀNG sang phải
                $rowIndex = $step % 4;
                $rotations = $prime % 8;
                $matrix = $this->rotateRowRight($matrix, $rowIndex, $rotations);
            } else {
                // Bước lẻ: Xoay vòng CỘT xuống dưới
                $colIndex = $step % 8;
                $rotations = $prime % 4;
                $matrix = $this->rotateColumnDown($matrix, $colIndex, $rotations);
            }
        }
        
        // Flatten ma trận về 1 chiều
        $rotatedBytes = [];
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $rotatedBytes[] = $matrix[$row][$col];
            }
        }
        
        // ===== BƯỚC 3: XOR LAYER =====
        $team = self::TEAM_SIGNATURE;
        $teamBytes = unpack('C*', $team);
        $teamLen = strlen($team);
        
        // Sinh dãy Fibonacci 32 số
        $fibo = [0, 1];
        for ($i = 2; $i < 32; $i++) {
            $fibo[$i] = $fibo[$i-1] + $fibo[$i-2];
        }
        
        // XOR 3 lớp: Rotated ^ Team ^ Fibonacci
        $result = "";
        for ($i = 0; $i < 32; $i++) {
            $byte = $rotatedBytes[$i];
            $tByte = $teamBytes[($i % $teamLen) + 1];
            $fByte = $fibo[$i] % 256;
            
            $finalByte = $byte ^ $tByte ^ $fByte;
            $result .= str_pad(dechex($finalByte), 2, "0", STR_PAD_LEFT);
        }
        
        return $result;
    }

    /**
     * Xoay vòng hàng sang phải
     * 
     * @param array $matrix Ma trận 4×8
     * @param int $rowIndex Chỉ số hàng (0-3)
     * @param int $rotations Số lần xoay (0-7)
     * @return array Ma trận sau khi xoay
     */
    private function rotateRowRight(array $matrix, int $rowIndex, int $rotations): array
    {
        $row = $matrix[$rowIndex];
        $rotations = $rotations % 8; // Normalize
        
        // Rotation algorithm: lấy phần cuối và ghép vào đầu
        for ($r = 0; $r < $rotations; $r++) {
            $last = array_pop($row);
            array_unshift($row, $last);
        }
        
        $matrix[$rowIndex] = $row;
        return $matrix;
    }

    /**
     * Xoay vòng cột xuống dưới
     * 
     * @param array $matrix Ma trận 4×8
     * @param int $colIndex Chỉ số cột (0-7)
     * @param int $rotations Số lần xoay (0-3)
     * @return array Ma trận sau khi xoay
     */
    private function rotateColumnDown(array $matrix, int $colIndex, int $rotations): array
    {
        // Extract column
        $col = [];
        for ($row = 0; $row < 4; $row++) {
            $col[] = $matrix[$row][$colIndex];
        }
        
        $rotations = $rotations % 4; // Normalize
        
        // Rotation: lấy phần cuối và ghép vào đầu
        for ($r = 0; $r < $rotations; $r++) {
            $last = array_pop($col);
            array_unshift($col, $last);
        }
        
        // Put column back
        for ($row = 0; $row < 4; $row++) {
            $matrix[$row][$colIndex] = $col[$row];
        }
        
        return $matrix;
    }

    /**
     * Phân tích chi tiết thuật toán Rubik Crypto
     * 
     * @param string $data Dữ liệu test
     * @return array Chi tiết từng bước
     */
    public function analyzeRubikAlgorithm(string $data): array
    {
        // Step 1: SHA-256
        $hash = hash('sha256', $data, true);
        $sha256Hex = hash('sha256', $data, false);
        $hashBytes = unpack('C*', $hash);
        
        // Step 2: Build matrix
        $matrix = [];
        $idx = 1;
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $matrix[$row][$col] = $hashBytes[$idx];
                $idx++;
            }
        }
        
        $matrixBefore = $matrix;
        
        // Simulate rotations (first 5 steps only for demo)
        $rotationSteps = [];
        for ($step = 0; $step < 5; $step++) {
            $prime = self::PRIMES[$step];
            
            if ($step % 2 === 0) {
                $rowIndex = $step % 4;
                $rotations = $prime % 8;
                $matrix = $this->rotateRowRight($matrix, $rowIndex, $rotations);
                $rotationSteps[] = [
                    'step' => $step,
                    'type' => 'row_right',
                    'index' => $rowIndex,
                    'prime' => $prime,
                    'rotations' => $rotations
                ];
            } else {
                $colIndex = $step % 8;
                $rotations = $prime % 4;
                $matrix = $this->rotateColumnDown($matrix, $colIndex, $rotations);
                $rotationSteps[] = [
                    'step' => $step,
                    'type' => 'column_down',
                    'index' => $colIndex,
                    'prime' => $prime,
                    'rotations' => $rotations
                ];
            }
        }
        
        return [
            'input' => $data,
            'sha256_hex' => $sha256Hex,
            'matrix_before' => $matrixBefore,
            'rotation_steps' => $rotationSteps,
            'matrix_after_5_steps' => $matrix,
            'primes_used' => array_slice(self::PRIMES, 0, 10),
            'final_hash' => $this->rubikCryptoHash($data)
        ];
    }

    /**
     * Demo và so sánh 2 thuật toán
     * 
     * @return array Kết quả so sánh
     */
    public function compareAlgorithms(): array
    {
        $testPasswords = [
            "password123",
            "admin@2024",
            "SecureP@ssw0rd!",
            "It solo leveling"
        ];

        $results = [];
        foreach ($testPasswords as $password) {
            $hash1 = $this->advancedPersonalizedHash($password);
            $hash2 = $this->rubikCryptoHash($password);
            
            $results[] = [
                'password' => $password,
                'simple_hash' => $hash1,
                'rubik_hash' => $hash2,
                'different' => ($hash1 !== $hash2),
                'both_length_64' => (strlen($hash1) === 64 && strlen($hash2) === 64)
            ];
        }

        return $results;
    }
}
