<?php

namespace App\Services;

use Exception;

/**
 * ĐỒ ÁN MÔN BẢO MẬT THÔNG TIN
 * ITSoloLevelingEncryption - Custom Two-Way Encryption Service
 * 
 * Service mã hóa dữ liệu nhạy cảm (two-way encryption) sử dụng:
 * - Base layer: SHA-256 hash để tạo key stream
 * - XOR encryption với team signature (reversible)
 * - Fibonacci sequence để thêm entropy
 * - AES-256-GCM wrapper cho authenticated encryption
 * 
 * Kế thừa từ ITSoloLevelingSecurity (one-way hash) nhưng mở rộng
 * thành two-way encryption để có thể decrypt lại dữ liệu.
 * 
 * Thuật toán:
 * 1. Generate key stream từ team signature + app key
 * 2. XOR data với key stream (reversible)
 * 3. Fibonacci sequence để scramble positions
 * 4. AES-256-GCM wrapper để bảo vệ integrity
 * 
 * @author IT Solo Leveling Team
 */
class ITSoloLevelingEncryption
{
    /**
     * Chuỗi cá nhân hóa của team (Team Signature)
     */
    private const TEAM_SIGNATURE = "It solo leveling";
    
    /**
     * AES encryption method
     */
    private const CIPHER_METHOD = 'aes-256-gcm';
    
    /**
     * Encryption key (256-bit = 32 bytes)
     * Được derive từ team signature + app key
     */
    private string $encryptionKey;
    
    /**
     * Constructor
     * Khởi tạo encryption key từ team signature + Laravel app key
     */
    public function __construct()
    {
        // Combine team signature với Laravel app key
        $appKey = config('app.key');
        
        // Remove "base64:" prefix if exists
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        
        // Derive encryption key sử dụng HKDF (HMAC-based Key Derivation Function)
        // Input: Laravel app key + team signature
        // Output: 32-byte encryption key
        $this->encryptionKey = hash_hkdf(
            'sha256',
            $appKey . self::TEAM_SIGNATURE,
            32, // 256 bits
            'it-solo-leveling-encryption', // Context info
            '' // Salt (optional)
        );
    }
    
    /**
     * Mã hóa dữ liệu sử dụng Custom Algorithm + AES-256-GCM
     * 
     * Thuật toán:
     * 1. Apply custom XOR + Fibonacci layer (IT Solo Leveling signature)
     * 2. Wrap with AES-256-GCM for integrity protection
     * 
     * @param mixed $data Dữ liệu cần mã hóa (string, array, object)
     * @return string Encrypted data (base64 encoded)
     * @throws Exception
     */
    public function encrypt($data): string
    {
        // Null hoặc empty string không cần encrypt
        if ($data === null || $data === '') {
            return '';
        }
        
        // Convert data sang JSON nếu không phải string
        if (!is_string($data)) {
            $data = json_encode($data);
        }
        
        // STEP 1: Apply custom XOR + Fibonacci layer
        $customEncrypted = $this->customXorEncrypt($data);
        
        // STEP 2: Wrap with AES-256-GCM
        // Generate random IV (Initialization Vector)
        $iv = random_bytes(12);
        
        // Encrypt with AES-256-GCM
        $tag = '';
        
        $aesEncrypted = openssl_encrypt(
            $customEncrypted,
            self::CIPHER_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::TEAM_SIGNATURE, // Additional authenticated data (AAD)
            16 // Tag length (128 bits)
        );
        
        if ($aesEncrypted === false) {
            throw new Exception('AES encryption failed: ' . openssl_error_string());
        }
        
        // Combine: IV + Tag + Encrypted data
        $combined = $iv . $tag . $aesEncrypted;
        
        // Encode to base64 for storage
        return base64_encode($combined);
    }
    
    /**
     * Custom XOR + Fibonacci encryption (IT Solo Leveling algorithm)
     * Reversible encryption layer
     * 
     * @param string $data Plain data
     * @return string Encrypted data (binary)
     */
    private function customXorEncrypt(string $data): string
    {
        $dataBytes = unpack('C*', $data);
        $dataLen = count($dataBytes);
        
        // Team signature bytes
        $team = self::TEAM_SIGNATURE;
        $teamBytes = unpack('C*', $team);
        $teamLen = strlen($team);
        
        // Generate Fibonacci sequence
        $fibo = [0, 1];
        $maxFibo = max(256, $dataLen * 2); // Ensure enough fibonacci numbers
        for ($i = 2; $i < $maxFibo; $i++) {
            $fibo[$i] = $fibo[$i-1] + $fibo[$i-2];
        }
        
        // XOR each byte with team signature + fibonacci
        $result = '';
        for ($i = 1; $i <= $dataLen; $i++) {
            $byte = $dataBytes[$i];
            
            // Team byte (circular)
            $tByte = $teamBytes[($i - 1) % $teamLen + 1];
            
            // Fibonacci byte (mod 256)
            $fByte = $fibo[$i] % 256;
            
            // Triple XOR
            $encryptedByte = $byte ^ $tByte ^ $fByte;
            
            $result .= chr($encryptedByte);
        }
        
        return $result;
    }
    
    /**
     * Custom XOR + Fibonacci decryption
     * Reverse của customXorEncrypt()
     * 
     * @param string $encryptedData Encrypted data (binary)
     * @return string Plain data
     */
    private function customXorDecrypt(string $encryptedData): string
    {
        // XOR is symmetric - same operation for encrypt/decrypt
        return $this->customXorEncrypt($encryptedData);
    }
    
    /**
     * Giải mã dữ liệu Custom Algorithm + AES-256-GCM
     * 
     * Reverse thuật toán encrypt:
     * 1. Decrypt AES-256-GCM wrapper
     * 2. Remove custom XOR + Fibonacci layer
     * 
     * @param string $encryptedData Encrypted data (base64 encoded)
     * @return mixed Decrypted data (original format)
     * @throws Exception
     */
    public function decrypt(string $encryptedData)
    {
        // Empty string return empty
        if ($encryptedData === '') {
            return '';
        }
        
        // Decode from base64
        $combined = base64_decode($encryptedData, true);
        
        if ($combined === false) {
            throw new Exception('Invalid encrypted data: not valid base64');
        }
        
        // Extract IV (12 bytes) + Tag (16 bytes) + Ciphertext
        if (strlen($combined) < 28) {
            throw new Exception('Invalid encrypted data: too short');
        }
        
        $iv = substr($combined, 0, 12);
        $tag = substr($combined, 12, 16);
        $ciphertext = substr($combined, 28);
        
        // STEP 1: Decrypt AES-256-GCM
        $customEncrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::TEAM_SIGNATURE
        );
        
        if ($customEncrypted === false) {
            throw new Exception('AES decryption failed: ' . openssl_error_string());
        }
        
        // STEP 2: Try remove custom XOR + Fibonacci layer (for new algorithm)
        try {
            $xorDecrypted = $this->customXorDecrypt($customEncrypted);
            
            // Validate if result is valid
            $jsonTest = json_decode($xorDecrypted, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $jsonTest;
            }
            
            // Check if it's valid UTF-8
            if (mb_check_encoding($xorDecrypted, 'UTF-8') && !ctype_cntrl($xorDecrypted)) {
                return $xorDecrypted;
            }
        } catch (\Exception $e) {
            // XOR failed, use legacy path
        }
        
        // LEGACY PATH: No custom XOR layer (old AES-only encryption)
        $jsonDecoded = json_decode($customEncrypted, true);
        return $jsonDecoded !== null ? $jsonDecoded : $customEncrypted;
    }
    
    /**
     * Encrypt array of fields in a model
     * 
     * @param array $data Data array
     * @param array $fields Fields to encrypt
     * @return array Data with encrypted fields
     */
    public function encryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Decrypt array of fields in a model
     * 
     * @param array $data Data array
     * @param array $fields Fields to decrypt
     * @return array Data with decrypted fields
     */
    public function decryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                try {
                    $data[$field] = $this->decrypt($data[$field]);
                } catch (Exception $e) {
                    // Log error but don't throw - keep encrypted value
                    \Log::error("Decryption failed for field {$field}: " . $e->getMessage());
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Generate hash for encrypted data verification
     * Useful for checking if data has been tampered with
     * 
     * @param string $data Original data
     * @return string SHA-256 hash
     */
    public function generateHash(string $data): string
    {
        return hash('sha256', $data . self::TEAM_SIGNATURE);
    }
    
    /**
     * Verify data integrity
     * 
     * @param string $data Original data
     * @param string $hash Expected hash
     * @return bool True if hash matches
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($this->generateHash($data), $hash);
    }
    
    /**
     * Demo function to test encryption/decryption
     * 
     * @return array Test results
     */
    public function demo(): array
    {
        $tests = [];
        
        // Test 1: String encryption
        $originalString = "Nguyễn Văn Minh";
        $encrypted = $this->encrypt($originalString);
        $decrypted = $this->decrypt($encrypted);
        
        $tests['string'] = [
            'original' => $originalString,
            'encrypted' => $encrypted,
            'decrypted' => $decrypted,
            'match' => $originalString === $decrypted
        ];
        
        // Test 2: Array encryption
        $originalArray = [
            'name' => 'Nguyễn Văn Minh',
            'email' => 'ungvien2@test.com',
            'phone' => '0912345678'
        ];
        $encrypted = $this->encrypt($originalArray);
        $decrypted = $this->decrypt($encrypted);
        
        $tests['array'] = [
            'original' => $originalArray,
            'encrypted' => $encrypted,
            'decrypted' => $decrypted,
            'match' => $originalArray === $decrypted
        ];
        
        // Test 3: CV Data encryption
        $cvData = [
            'education' => [
                ['school' => 'ĐH Bách Khoa', 'degree' => 'thac_si', 'year' => 2020]
            ],
            'work_experiences' => [
                ['company' => 'FPT', 'position' => 'Senior Dev', 'years' => 3]
            ]
        ];
        $encrypted = $this->encrypt($cvData);
        $decrypted = $this->decrypt($encrypted);
        
        $tests['cv_data'] = [
            'original' => $cvData,
            'encrypted' => substr($encrypted, 0, 50) . '...', // Show first 50 chars
            'decrypted' => $decrypted,
            'match' => $cvData === $decrypted
        ];
        
        return $tests;
    }
}
