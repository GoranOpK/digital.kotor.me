<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Servis za rad sa Mega.nz cloud storage
 * 
 * Implementacija direktnog MEGA API-ja za upload/download/delete operacije
 */
class MegaStorageService
{
    private string $email;
    private string $password;
    private ?object $megaClient = null;
    private string $baseFolder;
    private ?string $sessionId = null;
    private ?string $apiUrl = null;
    private int $sequence = 0;
    private ?string $masterKey = null;

    public function __construct()
    {
        $this->email = config('services.mega.email', '');
        $this->password = config('services.mega.password', '');
        $this->baseFolder = config('services.mega.base_folder', 'digital.kotor');
        $this->apiUrl = 'https://g.api.mega.co.nz/cs';
        
        if (empty($this->email) || empty($this->password)) {
            Log::warning('MEGA credentials not configured');
        }
    }

    /**
     * Inicijalizuje API URL i sequence
     */
    private function initApi(): void
    {
        if (!$this->apiUrl) {
            $this->apiUrl = 'https://g.api.mega.co.nz/cs';
        }
        $this->sequence = time() % 1000000;
    }

    /**
     * Pravi API poziv ka MEGA serveru
     */
    private function apiRequest(array $request): array
    {
        $this->initApi();
        
        $url = $this->apiUrl . '?id=' . $this->sequence++;
        $payload = is_array($request[0] ?? null) ? $request : [$request];
        
        try {
            Log::debug('MEGA API request', [
                'url' => $url,
                'action' => $request['a'] ?? 'unknown',
                'payload_keys' => array_keys($request),
                'payload' => $payload
            ]);
            
            // MEGA API zahteva JSON format - koristimo cURL direktno za bolju kontrolu
            $jsonPayload = json_encode($payload);
            
            Log::debug('MEGA API sending request', [
                'url' => $url,
                'payload_length' => strlen($jsonPayload),
                'payload_preview' => substr($jsonPayload, 0, 200)
            ]);
            
            // Prvo pošalji zahtev da dobijemo Hashcash challenge
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonPayload)
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_VERBOSE => false,
                CURLOPT_HEADER => true // Include headers in response
            ]);
            
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?? 0;
            
            // Parse headers and body BEFORE closing
            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
            
            curl_close($ch);
            
            Log::debug('MEGA API cURL response details', [
                'status_code' => $statusCode,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno,
                'content_type' => $contentType,
                'total_time' => $totalTime,
                'body_length' => strlen($body ?? ''),
                'headers_preview' => substr($headers, 0, 500)
            ]);
            
            if ($curlError) {
                Log::error('MEGA API cURL error', [
                    'error' => $curlError,
                    'errno' => $curlErrno
                ]);
                return [];
            }
            
            // Proveri da li MEGA zahteva Hashcash proof-of-work
            if ($statusCode === 402 && preg_match('/X-Hashcash:\s*([^\r\n]+)/i', $headers, $matches)) {
                $hashcashChallenge = trim($matches[1]);
                Log::info('MEGA API requires Hashcash proof-of-work', [
                    'challenge' => $hashcashChallenge
                ]);
                
                // Reši Hashcash proof-of-work
                $hashcashSolution = $this->solveHashcash($hashcashChallenge);
                
                if (!$hashcashSolution) {
                    Log::error('Failed to solve Hashcash challenge');
                    return [];
                }
                
                Log::info('Hashcash solution found', [
                    'solution' => $hashcashSolution
                ]);
                
                // Pošalji zahtev ponovo sa Hashcash rešenjem
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => $jsonPayload,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($jsonPayload),
                        'X-Hashcash: ' . $hashcashSolution
                    ],
                    CURLOPT_TIMEOUT => 60, // Više vremena za drugi pokušaj
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_HEADER => true
                ]);
                
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?? 0;
                $body = substr($response, $headerSize);
                curl_close($ch);
                
                Log::debug('MEGA API retry with Hashcash', [
                    'status_code' => $statusCode,
                    'body_length' => strlen($body ?? '')
                ]);
            }
            
            if ($statusCode !== 200) {
                Log::error('MEGA API HTTP error', [
                    'status' => $statusCode,
                    'body' => $body,
                    'body_length' => strlen($body ?? ''),
                    'body_preview' => substr($body ?? '', 0, 500),
                    'headers' => $headers
                ]);
                
                // Pokušaj da pročitaš JSON iako status nije 200
                if (!empty($body)) {
                    $data = json_decode($body, true);
                    if ($data !== null) {
                        Log::error('MEGA API error response (non-200 but valid JSON)', [
                            'status' => $statusCode,
                            'data' => $data
                        ]);
                        return $data;
                    }
                }
                
                return [];
            }
            
            $data = json_decode($body, true);
            
            if ($data === null) {
                Log::error('MEGA API invalid JSON response', [
                    'body' => $body,
                    'json_error' => json_last_error_msg()
                ]);
                return [];
            }
            
            // MEGA API vraća array sa jednim elementom ili direktno objekat
            $result = is_array($data) && isset($data[0]) ? $data[0] : $data;
            
            // Proveri da li ima grešku u odgovoru
            if (isset($result['e'])) {
                Log::error('MEGA API error in response', [
                    'error_code' => $result['e'],
                    'action' => $request['a'] ?? 'unknown',
                    'full_response' => $result
                ]);
            } else {
                Log::debug('MEGA API request successful', [
                    'action' => $request['a'] ?? 'unknown',
                    'response_keys' => is_array($result) ? array_keys($result) : 'non-array'
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('MEGA API request exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Inicijalizuje MEGA klijent
     * 
     * @return object|null
     */
    private function getMegaClient()
    {
        // Pokušaj da koristiš tuyenlaptrinh/php-mega-nz biblioteku
        // Biblioteka može biti limitirana za privatne naloge
        try {
            if (!class_exists('\PhpExtended\Mega\Mega')) {
                Log::error('MEGA library class not found. Make sure tuyenlaptrinh/php-mega-nz is installed.');
                return null;
            }

            // tuyenlaptrinh/php-mega-nz radi sa public linkovima koji sadrže node ID i key
            // Za upload/delete u privatnom nalogu možda treba drugačiji pristup
            // TODO: Implementirati prema dokumentaciji biblioteke
            
            return null; // Privremeno
            
        } catch (Exception $e) {
            Log::error('Failed to initialize MEGA client', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Uploaduje fajl na Mega.nz
     * 
     * @param string $filePath Lokalna putanja do fajla
     * @param string $remotePath Putanja na Mega.nz (folder struktura)
     * @return array ['success' => bool, 'cloud_path' => string|null, 'error' => string|null]
     */
    public function upload(string $filePath, string $remotePath = ''): array
    {
        try {
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'Fajl ne postoji: ' . $filePath
                ];
            }

            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'error' => 'MEGA credentials not configured'
                ];
            }

            // Pokušaj sa MEGA API-jem direktno
            // tuyenlaptrinh/php-mega-nz biblioteka možda nema upload za privatne naloge
            // Koristimo direktan HTTP pristup MEGA API-ju
            
            $result = $this->uploadViaApi($filePath, $remotePath);
            
            if ($result['success']) {
                Log::info('File uploaded to MEGA successfully', [
                    'file_path' => $filePath,
                    'remote_path' => $remotePath,
                    'cloud_path' => $result['cloud_path']
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('MEGA upload failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Greška pri upload-u na MEGA: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Uploaduje fajl preko MEGA API-ja direktno
     * 
     * @param string $filePath
     * @param string $remotePath
     * @return array
     */
    private function uploadViaApi(string $filePath, string $remotePath): array
    {
        // MEGA API zahteva kompleksnu enkripciju i autentifikaciju
        // Ovo je pojednostavljena implementacija koja treba da se proširi
        
        try {
            // 1. Login na MEGA
            $sessionId = $this->megaLogin();
            if (!$sessionId) {
                return [
                    'success' => false,
                    'error' => 'MEGA login failed'
                ];
            }

            // 2. Pronađi ili kreiraj folder strukturu
            $parentNodeId = $this->ensureFolderStructure($sessionId, $remotePath);
            if (!$parentNodeId) {
                return [
                    'success' => false,
                    'error' => 'Failed to create folder structure'
                ];
            }

            // 3. Upload fajla
            $fileHandle = $this->megaUploadFile($sessionId, $filePath, $parentNodeId);
            if (!$fileHandle) {
                return [
                    'success' => false,
                    'error' => 'File upload failed'
                ];
            }

            return [
                'success' => true,
                'cloud_path' => $fileHandle // Node handle koji identifikuje fajl na MEGA
            ];

        } catch (Exception $e) {
            Log::error('MEGA API upload failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'MEGA API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Konvertuje string u 32-bit word array (big-endian)
     */
    private function strToA32(string $s): array
    {
        $out = [];
        $len = strlen($s);
        $padded = str_pad($s, ceil($len / 4) * 4, "\0");
        $words = unpack('N*', $padded);
        return array_values($words ?: []);
    }

    /**
     * Konvertuje 32-bit word array u base64 (MEGA format - URL safe, bez padding)
     */
    private function a32ToBase64(array $a): string
    {
        $bin = '';
        foreach ($a as $w) {
            $bin .= pack('N', $w);
        }
        $b64 = base64_encode($bin);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    /**
     * AES-CBC enkripcija za 4-word (128-bit) blok
     */
    private function aesCbcEncryptA32(array $plain4, array $key4): array
    {
        $plain = pack('N*', ...$plain4);
        $key = pack('N*', ...$key4);
        $iv = str_repeat("\0", 16); // Zero IV
        
        $cipher = openssl_encrypt(
            $plain,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );
        
        if ($cipher === false) {
            return $plain4;
        }
        
        $words = unpack('N*', str_pad($cipher, 16, "\0"));
        return array_values($words ?: []);
    }

    /**
     * MEGA prepare_key funkcija - generiše password AES key
     */
    private function prepareKey(array $a): array
    {
        $pkey = [0x93C467E3, 0x7DB0C7A4, 0xD1BE3F81, 0x0152CB56];
        
        for ($r = 0; $r < 0x10000; $r++) {
            for ($j = 0; $j < count($a); $j += 4) {
                $key4 = [0, 0, 0, 0];
                for ($i = 0; $i < 4; $i++) {
                    if (($i + $j) < count($a)) {
                        $key4[$i] = $a[$i + $j];
                    }
                }
                $pkey = $this->aesCbcEncryptA32($pkey, $key4);
            }
        }
        
        return $pkey;
    }

    /**
     * MEGA stringhash funkcija - generiše user hash
     */
    private function stringHash(string $s, array $aeskey): string
    {
        $s32 = $this->strToA32($s);
        $h32 = [0, 0, 0, 0];
        $cnt = count($s32);
        
        for ($i = 0; $i < $cnt; $i++) {
            $h32[$i % 4] ^= $s32[$i];
        }
        
        for ($i = 0; $i < 0x4000; $i++) {
            $h32 = $this->aesCbcEncryptA32($h32, $aeskey);
        }
        
        return $this->a32ToBase64([$h32[0], $h32[2]]);
    }

    /**
     * Generiše MEGA user hash (uh) za login
     */
    private function megaUserHash(string $email, string $password): string
    {
        $emailLow = strtolower($email);
        $passwordA32 = $this->strToA32($password);
        $passwordAes = $this->prepareKey($passwordA32);
        
        return $this->stringHash($emailLow, $passwordAes);
    }

    /**
     * Rešava Hashcash proof-of-work challenge
     * 
     * Hashcash format: version:bits:timestamp:resource:ext:rand:counter
     * Treba naći counter tako da SHA1(hashcash_string) počinje sa bits nula
     */
    private function solveHashcash(string $challenge): ?string
    {
        try {
            // Parsuj challenge
            $parts = explode(':', $challenge);
            if (count($parts) < 6) {
                Log::error('Invalid Hashcash challenge format', ['challenge' => $challenge]);
                return null;
            }
            
            $version = $parts[0];
            $bits = (int)$parts[1];
            $timestamp = $parts[2];
            $resource = $parts[3];
            $ext = $parts[4] ?? '';
            $rand = $parts[5] ?? '';
            
            Log::debug('Solving Hashcash challenge', [
                'bits' => $bits,
                'timestamp' => $timestamp
            ]);
            
            // Konvertuj bits u broj nula (bits je broj bitova, ne bajtova)
            // Svaki hex karakter = 4 bita, dakle bits/4 karaktera mora biti 0
            $requiredZeros = (int)($bits / 4);
            
            // Hashcash 1.0 format koristi partial hash collision
            // Moramo da nađemo counter gde SHA1 hash počinje sa bits nula (u bitovima)
            // Za bits=192, to je 48 hex karaktera = 192 bita
            
            $targetPrefix = str_repeat('0', min($requiredZeros, 40)); // SHA1 je 40 hex karaktera max
            
            $startTime = microtime(true);
            $counter = 0;
            $maxAttempts = 10000000; // Limit pokušaja
            
            while ($counter < $maxAttempts) {
                // Kreiraj full hashcash string sa counter-om
                $hashcashString = sprintf(
                    '%s:%d:%s:%s:%s:%s:%d',
                    $version,
                    $bits,
                    $timestamp,
                    $resource,
                    $ext,
                    $rand,
                    $counter
                );
                
                // Izračunaj SHA1 hash
                $hash = sha1($hashcashString);
                
                // Proveri da li hash počinje sa potrebnim brojem nula
                if (substr($hash, 0, $requiredZeros) === $targetPrefix) {
                    $elapsed = microtime(true) - $startTime;
                    Log::info('Hashcash solution found', [
                        'counter' => $counter,
                        'hash' => $hash,
                        'time' => round($elapsed, 2) . 's',
                        'attempts' => $counter + 1
                    ]);
                    
                    return $hashcashString;
                }
                
                $counter++;
                
                // Loguj progress svakih 1M pokušaja
                if ($counter % 1000000 === 0) {
                    Log::debug('Hashcash solving progress', [
                        'attempts' => $counter,
                        'elapsed' => round(microtime(true) - $startTime, 2) . 's'
                    ]);
                }
            }
            
            Log::error('Hashcash solving failed - max attempts reached', [
                'max_attempts' => $maxAttempts,
                'bits' => $bits
            ]);
            
            return null;
            
        } catch (Exception $e) {
            Log::error('Hashcash solving exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Login na MEGA API
     * 
     * @return string|null Session ID
     */
    private function megaLogin(): ?string
    {
        // Ako već imamo session ID, vrati ga
        if ($this->sessionId) {
            return $this->sessionId;
        }
        
        try {
            Log::info('MEGA login attempt', [
                'email' => $this->email
            ]);
            
            $uh = $this->megaUserHash($this->email, $this->password);
            
            Log::debug('MEGA user hash generated', [
                'uh_length' => strlen($uh),
                'uh_full' => $uh, // Logujemo puni hash za debug
                'email' => $this->email,
                'password_length' => strlen($this->password)
            ]);
            
            $response = $this->apiRequest([
                'a' => 'us',
                'user' => $this->email,
                'uh' => $uh
            ]);
            
            Log::debug('MEGA login response', [
                'response_keys' => is_array($response) ? array_keys($response) : 'non-array',
                'has_csid' => isset($response['csid']),
                'has_k' => isset($response['k']),
                'has_e' => isset($response['e'])
            ]);
            
            if (empty($response)) {
                Log::error('MEGA login returned empty response');
                return null;
            }
            
            // MEGA vraća 'csid' za session ID ili 's' za sekvencu
            if (isset($response['csid'])) {
                // Session ID je u csid parametru
                $this->sessionId = $response['csid'];
                $this->apiUrl = 'https://g.api.mega.co.nz/cs?id=' . $this->sessionId;
                
                Log::info('MEGA login successful with csid', [
                    'session_id_preview' => substr($this->sessionId, 0, 20) . '...'
                ]);
                
                // Dekriptuj master key ako postoji
                if (isset($response['k'])) {
                    // TODO: Dekriptuj master key koristeći password
                    $this->masterKey = $response['k'];
                }
                
                return $this->sessionId;
            }
            
            // Alternativni format - možda koristi 's' za sekvencu
            if (isset($response['s'])) {
                Log::info('MEGA login successful with sequence', [
                    'sequence' => $response['s']
                ]);
                // Možda treba da čuvamo sekvencu kao session
                return 'sequence_' . $response['s'];
            }
            
            // Proveri grešku
            if (isset($response['e'])) {
                $errorCode = $response['e'];
                $errorMessages = [
                    -1 => 'Internal error',
                    -2 => 'Bad arguments',
                    -3 => 'Request failed, retry',
                    -4 => 'Rate limit exceeded',
                    -6 => 'Too many requests',
                    -8 => 'Upload failed',
                    -9 => 'Item not found',
                    -11 => 'Access denied',
                    -13 => 'Over quota',
                    -14 => 'Temporarily unavailable',
                    -15 => 'Blocked',
                    -16 => 'Expired',
                    -17 => 'Not found',
                    -18 => 'Circular reference',
                    -19 => 'Access denied'
                ];
                
                $errorMsg = $errorMessages[$errorCode] ?? 'Unknown error';
                
                Log::error('MEGA login failed', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMsg,
                    'full_response' => $response
                ]);
            } else {
                Log::error('MEGA login failed - unexpected response format', [
                    'response' => $response
                ]);
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error('MEGA login exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Dobija root node (files ili inbox)
     */
    private function getRootNode(): ?string
    {
        try {
            $response = $this->apiRequest(['a' => 'f', 'c' => 1]);
            
            if (isset($response['f'])) {
                // f je array svih node-ova
                // Root node je obično node sa p=null ili p=""
                foreach ($response['f'] as $node) {
                    if (($node['t'] ?? 0) == 1 && (!isset($node['p']) || $node['p'] == '')) {
                        // t=1 je folder, p je parent (prazno = root)
                        return $node['h'] ?? null;
                    }
                }
                // Ako nema eksplicitnog root-a, uzmi prvi folder
                foreach ($response['f'] as $node) {
                    if (($node['t'] ?? 0) == 1) {
                        return $node['h'] ?? null;
                    }
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error('Failed to get root node', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Kreira folder strukturu na MEGA
     * 
     * @param string $sessionId
     * @param string $remotePath Putanja poput "digital.kotor/documents/user_7/"
     * @return string|null Parent node ID gde treba uploadovati fajl
     */
    private function ensureFolderStructure(string $sessionId, string $remotePath): ?string
    {
        try {
            // Parsuj putanju: "digital.kotor/documents/user_7/" -> ["digital.kotor", "documents", "user_7"]
            $folders = array_filter(explode('/', trim($remotePath, '/')));
            if (empty($folders)) {
                // Ako nema foldera, koristi root
                return $this->getRootNode();
            }
            
            // Dobij root node
            $currentParent = $this->getRootNode();
            if (!$currentParent) {
                Log::error('Could not get root node for folder structure');
                return null;
            }
            
            // Iteriraj kroz foldere i kreiraj ih po potrebi
            foreach ($folders as $folderName) {
                $folderName = trim($folderName);
                if (empty($folderName)) {
                    continue;
                }
                
                // Pronađi folder u trenutnom parent-u
                $existingFolder = $this->findFolder($currentParent, $folderName);
                
                if ($existingFolder) {
                    $currentParent = $existingFolder;
                } else {
                    // Kreiraj novi folder
                    $newFolder = $this->createFolder($currentParent, $folderName);
                    if (!$newFolder) {
                        Log::error('Failed to create folder', [
                            'folder' => $folderName,
                            'parent' => $currentParent
                        ]);
                        return null;
                    }
                    $currentParent = $newFolder;
                }
            }
            
            return $currentParent;
            
        } catch (Exception $e) {
            Log::error('Failed to ensure folder structure', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Pronalazi folder u parent-u
     */
    private function findFolder(string $parentId, string $folderName): ?string
    {
        try {
            $response = $this->apiRequest(['a' => 'f', 'c' => 1]);
            
            if (isset($response['f'])) {
                foreach ($response['f'] as $node) {
                    // t=1 je folder
                    if (($node['t'] ?? 0) == 1 && ($node['p'] ?? '') == $parentId) {
                        // Dekriptuj ime foldera ako je enkriptovano
                        // Za sada proveravamo po handle-u
                        // TODO: Dekriptovati 'a' (attributes) da dobijemo stvarno ime
                        $nodeName = $this->decryptNodeAttributes($node['a'] ?? '', $node['k'] ?? '');
                        if ($nodeName === $folderName) {
                            return $node['h'] ?? null;
                        }
                    }
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error('Failed to find folder', [
                'parent' => $parentId,
                'folder' => $folderName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Kreira novi folder
     */
    private function createFolder(string $parentId, string $folderName): ?string
    {
        try {
            // Enkriptuj ime foldera
            $key = random_bytes(16);
            $iv = random_bytes(16);
            $attributes = json_encode(['n' => $folderName]);
            $encryptedAttributes = openssl_encrypt(
                $attributes,
                'AES-128-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            $keyB64 = base64_encode($iv . $encryptedAttributes);
            
            $response = $this->apiRequest([
                'a' => 'p',
                't' => $parentId,
                'n' => [['t' => 1, 'a' => $keyB64, 'k' => base64_encode($key)]]
            ]);
            
            if (isset($response['f'])) {
                foreach ($response['f'] as $node) {
                    if (($node['t'] ?? 0) == 1) {
                        return $node['h'] ?? null;
                    }
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error('Failed to create folder', [
                'parent' => $parentId,
                'folder' => $folderName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Dekriptuje node attributes
     */
    private function decryptNodeAttributes(string $encryptedAttributes, string $key): string
    {
        try {
            // Attributes su base64 enkriptovani (IV + data)
            $data = base64_decode($encryptedAttributes);
            if (strlen($data) < 16) {
                return '';
            }
            
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $keyBytes = base64_decode($key);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                'AES-128-CBC',
                $keyBytes,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                return '';
            }
            
            $attrs = json_decode($decrypted, true);
            return $attrs['n'] ?? '';
            
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Uploaduje fajl na MEGA
     * 
     * @param string $sessionId
     * @param string $filePath
     * @param string $parentNodeId
     * @return string|null File handle
     */
    private function megaUploadFile(string $sessionId, string $filePath, string $parentNodeId): ?string
    {
        try {
            $fileSize = filesize($filePath);
            $fileName = basename($filePath);
            
            if ($fileSize === false || $fileSize === 0) {
                Log::error('Invalid file size for upload', ['file' => $filePath]);
                return null;
            }
            
            // 1. Generiši file key i IV za enkripciju
            $fileKey = random_bytes(16);
            $iv = random_bytes(16);
            
            // 2. Enkriptuj filename i kreiraj attributes
            $attributes = json_encode(['n' => $fileName]);
            $encryptedAttributes = openssl_encrypt(
                $attributes,
                'AES-128-CBC',
                $fileKey,
                OPENSSL_RAW_DATA,
                $iv
            );
            $attrB64 = base64_encode($iv . $encryptedAttributes);
            
            // 3. Generiši upload URL
            $uploadResponse = $this->apiRequest([
                'a' => 'u',
                's' => $fileSize
            ]);
            
            if (!isset($uploadResponse['p'])) {
                Log::error('Failed to get upload URL', ['response' => $uploadResponse]);
                return null;
            }
            
            $uploadUrl = $uploadResponse['p'];
            
            // 4. Chunking i upload
            $chunkSize = 8 * 1024 * 1024; // 8MB chunks
            $chunks = [];
            $handle = fopen($filePath, 'rb');
            
            if (!$handle) {
                Log::error('Failed to open file for upload', ['file' => $filePath]);
                return null;
            }
            
            $offset = 0;
            $chunkIndex = 0;
            
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false || strlen($chunk) === 0) {
                    break;
                }
                
                // Enkriptuj chunk
                $chunkIv = random_bytes(16);
                $encryptedChunk = openssl_encrypt(
                    $chunk,
                    'AES-128-CBC',
                    $fileKey,
                    OPENSSL_RAW_DATA,
                    $chunkIv
                );
                
                // Upload chunk - MEGA zahteva raw POST sa enkriptovanim chunkom
                $encryptedChunkWithIv = $chunkIv . $encryptedChunk;
                
                $chunkResponse = Http::timeout(60)
                    ->withBody($encryptedChunkWithIv, 'application/octet-stream')
                    ->post($uploadUrl . '?' . http_build_query([
                        'offset' => $offset,
                        'length' => strlen($encryptedChunkWithIv)
                    ]));
                
                if (!$chunkResponse->successful()) {
                    fclose($handle);
                    Log::error('Chunk upload failed', [
                        'chunk' => $chunkIndex,
                        'offset' => $offset,
                        'status' => $chunkResponse->status()
                    ]);
                    return null;
                }
                
                // MEGA vraća completion marker, ne individual chunk handles
                // Sledeći chunk ide na sledeći offset
                $offset += strlen($chunk);
                $chunkIndex++;
            }
            
            fclose($handle);
            
            // 5. Kreiraj node u MEGA strukturi
            // h je upload handle iz uploadResponse
            $uploadHandle = $uploadResponse['h'] ?? null;
            if (!$uploadHandle) {
                Log::error('No upload handle returned', ['response' => $uploadResponse]);
                return null;
            }
            
            $nodeResponse = $this->apiRequest([
                'a' => 'p',
                't' => $parentNodeId,
                'n' => [[
                    'h' => $uploadHandle,
                    't' => 0, // 0 = file, 1 = folder
                    'a' => $attrB64,
                    'k' => base64_encode($fileKey),
                    's' => $fileSize
                ]]
            ]);
            
            if (isset($nodeResponse['f'])) {
                foreach ($nodeResponse['f'] as $node) {
                    if (($node['t'] ?? 0) == 0) { // 0 = file
                        Log::info('File uploaded successfully', [
                            'file_handle' => $node['h'],
                            'file_name' => $fileName
                        ]);
                        return $node['h'];
                    }
                }
            }
            
            Log::error('Failed to create file node', ['response' => $nodeResponse]);
            return null;
            
        } catch (Exception $e) {
            Log::error('MEGA file upload exception', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Downloaduje fajl sa Mega.nz
     * 
     * @param string $cloudPath Mega.nz file handle (node ID)
     * @return array ['success' => bool, 'content' => string|null, 'error' => string|null]
     */
    public function download(string $cloudPath): array
    {
        try {
            if (empty($cloudPath)) {
                return [
                    'success' => false,
                    'error' => 'Cloud path nije naveden'
                ];
            }

            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'error' => 'MEGA credentials not configured'
                ];
            }

            // Pokušaj sa tuyenlaptrinh/php-mega-nz bibliotekom
            // Ako cloud_path sadrži full MEGA URL (public link), koristimo biblioteku
            // Ako je samo node handle, koristimo direktni API poziv
            
            if (strpos($cloudPath, 'mega.nz') !== false) {
                // Public link format
                return $this->downloadFromPublicLink($cloudPath);
            } else {
                // Node handle - koristi direktan API
                return $this->downloadViaApi($cloudPath);
            }

        } catch (Exception $e) {
            Log::error('MEGA download failed', [
                'cloud_path' => $cloudPath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Greška pri download-u sa MEGA: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Downloaduje iz public linka
     */
    private function downloadFromPublicLink(string $url): array
    {
        try {
            if (class_exists('\PhpExtended\Mega\Mega')) {
                $mega = new \PhpExtended\Mega\Mega($url);
                $root = $mega->getRootNodeInfo();
                $content = $mega->downloadFile($root);
                
                return [
                    'success' => true,
                    'content' => $content
                ];
            }
        } catch (Exception $e) {
            Log::error('MEGA public link download failed', [
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => false,
            'error' => 'Download from public link not supported with current configuration'
        ];
    }

    /**
     * Downloaduje preko API-ja
     */
    private function downloadViaApi(string $nodeHandle): array
    {
        try {
            // 1. Login ako nije već ulogovan
            $sessionId = $this->megaLogin();
            if (!$sessionId) {
                return [
                    'success' => false,
                    'error' => 'MEGA login failed'
                ];
            }
            
            // 2. Dobij informacije o fajlu
            $response = $this->apiRequest([
                'a' => 'g',
                'g' => 1,
                'n' => $nodeHandle
            ]);
            
            if (isset($response['e'])) {
                return [
                    'success' => false,
                    'error' => 'MEGA API error: ' . ($response['e'] ?? 'Unknown error')
                ];
            }
            
            if (!isset($response['g']) || !isset($response['s'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid MEGA API response'
                ];
            }
            
            $downloadUrl = $response['g'];
            $fileSize = $response['s'];
            
            // 3. Dobij file key iz node informacija
            // Prvo moramo da dobijemo node info da bi dobili key
            $fileResponse = $this->apiRequest(['a' => 'f', 'c' => 1]);
            
            $fileKey = null;
            if (isset($fileResponse['f'])) {
                foreach ($fileResponse['f'] as $node) {
                    if (($node['h'] ?? '') === $nodeHandle) {
                        // Dekriptuj file key ako je potrebno
                        // Za sada uzimamo direktno ako postoji
                        if (isset($node['k'])) {
                            $fileKey = base64_decode($node['k']);
                            break;
                        }
                    }
                }
            }
            
            if (!$fileKey || strlen($fileKey) < 16) {
                return [
                    'success' => false,
                    'error' => 'File key not found or invalid'
                ];
            }
            
            // 4. Download fajla
            $encryptedContent = Http::timeout(300)->get($downloadUrl)->body();
            
            if (empty($encryptedContent)) {
                return [
                    'success' => false,
                    'error' => 'Failed to download file from MEGA'
                ];
            }
            
            // 5. Dekriptuj fajl
            // MEGA fajlovi su podeljeni u chunks sa IV-ovima
            // Za sada, jednostavno dekriptujemo ceo sadržaj
            // TODO: Implementirati chunk dekripciju ako je potrebno
            
            $iv = substr($encryptedContent, 0, 16);
            $encrypted = substr($encryptedContent, 16);
            
            $decryptedContent = openssl_decrypt(
                $encrypted,
                'AES-128-CBC',
                $fileKey,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decryptedContent === false) {
                // Možda je fajl podeljen u chunks, probaj bez chunking-a
                // Za sada vratimo grešku
                return [
                    'success' => false,
                    'error' => 'Failed to decrypt file content'
                ];
            }
            
            return [
                'success' => true,
                'content' => $decryptedContent
            ];
            
        } catch (Exception $e) {
            Log::error('MEGA API download exception', [
                'node_handle' => $nodeHandle,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Download exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Briše fajl sa Mega.nz
     * 
     * @param string $cloudPath Mega.nz file handle ili putanja
     * @return bool True ako je brisanje uspešno
     */
    public function delete(string $cloudPath): bool
    {
        try {
            if (empty($cloudPath)) {
                Log::warning('MEGA delete called with empty cloud_path');
                return false;
            }

            if (!$this->isConfigured()) {
                Log::error('MEGA credentials not configured for delete');
                return false;
            }

            // 1. Login ako nije već ulogovan
            $sessionId = $this->megaLogin();
            if (!$sessionId) {
                Log::error('MEGA login failed for delete operation');
                return false;
            }
            
            // 2. cloud_path može biti node handle ili full path
            // Ako je full path, ekstraktuj node handle
            $nodeHandle = $cloudPath;
            if (strpos($cloudPath, 'mega.nz') !== false) {
                // Ekstraktuj node handle iz URL-a
                // Format: https://mega.nz/file/NODEHANDLE#KEY
                preg_match('/mega\.nz\/file\/([^#]+)#?/', $cloudPath, $matches);
                if (isset($matches[1])) {
                    $nodeHandle = $matches[1];
                }
            }
            
            // 3. Obriši node
            $response = $this->apiRequest([
                'a' => 'd',
                'n' => $nodeHandle
            ]);
            
            if (isset($response['e'])) {
                Log::error('MEGA delete failed', [
                    'node_handle' => $nodeHandle,
                    'error_code' => $response['e']
                ]);
                return false;
            }
            
            Log::info('File deleted from MEGA successfully', [
                'node_handle' => $nodeHandle
            ]);
            
            return true;

        } catch (Exception $e) {
            Log::error('MEGA delete exception', [
                'cloud_path' => $cloudPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Proverava da li je servis konfigurisan
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }
}
