# Opcije za MEGA implementaciju - Detaljno poređenje

## 📊 Poređenje opcija

| Opcija | Kompleksnost | Brzina implementacije | Zavisnosti | Funkcionalnost |
|--------|--------------|----------------------|------------|----------------|
| **megacmd CLI** | ⭐ Niska | ⚡ Brzo (1-2h) | Zahteva megacmd instalaciju | ✅ 100% |
| **Direktan MEGA API** | ⭐⭐⭐ Visoka | 🐌 Sporo (8-16h) | Samo PHP standard | ✅ 100% |
| **tuyenlaptrinh/php-mega-nz** | ⭐ Niska | ⚡ Već instalirano | ❌ Ne radi za naše potrebe | ❌ 0% (samo public linkovi) |

---

## 🔧 Opcija 1: megacmd CLI (PREPORUČENO)

### Prednosti:
- ✅ Veoma jednostavno - samo `exec()` pozivi
- ✅ Zvaničan MEGA alat - pouzdan
- ✅ Sve funkcionalnosti već postoje
- ✅ Brzo za implementirati (~2 sata)

### Nedostaci:
- ⚠️ Zahteva instalaciju `megacmd` na serveru
- ⚠️ Zahteva CLI pristup (Plesk hosting može imati ograničenja)
- ⚠️ Potrebno da se login jednom (session se čuva)

### Instalacija na serveru:
```bash
# Proveri da li već postoji
which megacmd

# Ako ne postoji, instaliraj:
# Debian/Ubuntu:
wget https://mega.nz/linux/MEGAsync/Debian_12.0/amd64/megacmd_1.5.1-1.1_amd64.deb
sudo dpkg -i megacmd_*.deb

# Ili preko package manager-a:
sudo apt-get install megacmd
```

### Primer implementacije:

```php
// app/Services/MegaStorageService.php

private function uploadViaCmd(string $filePath, string $remotePath): array
{
    // Escape putanje za shell
    $escapedFilePath = escapeshellarg($filePath);
    $escapedRemotePath = escapeshellarg("/Root/{$remotePath}");
    
    // Upload komanda
    $cmd = "megacmd --upload {$escapedFilePath} {$escapedRemotePath} 2>&1";
    
    exec($cmd, $output, $returnCode);
    $outputStr = implode("\n", $output);
    
    if ($returnCode === 0) {
        // Dobij filename iz putanje
        $filename = basename($filePath);
        $remoteFullPath = "/Root/{$remotePath}/{$filename}";
        
        // Dobij node handle (treba da se pročita iz MEGA strukture)
        $nodeHandle = $this->getNodeHandleFromPath($remoteFullPath);
        
        return [
            'success' => true,
            'cloud_path' => $nodeHandle ?: $remoteFullPath
        ];
    }
    
    return [
        'success' => false,
        'error' => "megacmd upload failed: {$outputStr}"
    ];
}

private function getNodeHandleFromPath(string $remotePath): ?string
{
    // megacmd ne vraća direktno node handle
    // Možeš da koristiš:
    // 1. Čuvanje putanje (ne node handle-a)
    // 2. Listing foldera da dobiješ node ID
    // 3. Eksport MEGA strukture
    
    $cmd = "megacmd --export \"{$remotePath}\" 2>&1";
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0) {
        // Parse output za node handle
        // Format izvoza: https://mega.nz/file/NODE_ID#KEY
        preg_match('/mega\.nz\/file\/([^#]+)#/', $outputStr, $matches);
        return $matches[1] ?? null;
    }
    
    return null;
}
```

**Prednosti ovog pristupa:**
- Brzo implementirati
- Pouzdano
- Sve funkcionalnosti radi

**Moguće probleme:**
- Plesk može imati ograničenja za `exec()`
- Potrebna instalacija `megacmd`

---

## 🔧 Opcija 2: Direktan MEGA API (Kompleksno, ali radi)

### Prednosti:
- ✅ Ne zahteva CLI pristup
- ✅ Potpuna kontrola
- ✅ Nema dodatnih zavisnosti osim PHP-a

### Nedostaci:
- ⚠️ Veoma kompleksna implementacija
- ⚠️ Sporo za implementirati (1-2 dana)
- ⚠️ Zahteva detaljno razumevanje MEGA protokola

### Šta treba implementirati:

#### 1. PBKDF2 Hash Password-a
```php
private function hashPassword(string $password, string $email): string
{
    // MEGA koristi email kao salt
    // PBKDF2-SHA512, 100.000 iteracija
    return hash_pbkdf2('sha512', $password, $email, 100000, 64, true);
}
```

#### 2. AES Enkripcija
```php
private function aesEncrypt(string $data, string $key, string $iv): string
{
    // AES-128-CBC
    return openssl_encrypt($data, 'AES-128-CBC', $key, 0, $iv);
}

private function generateIv(): string
{
    return random_bytes(16);
}

private function generateKey(): string
{
    return random_bytes(16);
}
```

#### 3. Login
```php
private function megaLogin(): ?string
{
    $sequence = $this->getSequence();
    $url = "https://g.api.mega.co.nz/cs?id={$sequence}";
    
    $uh = base64_encode($this->hashPassword($this->password, $this->email));
    $encryptedEmail = $this->aesEncrypt($this->email, $uh, $this->generateIv());
    
    $payload = [[
        'a' => 'us',
        'user' => base64_encode($encryptedEmail),
        'uh' => $uh
    ]];
    
    $response = Http::post($url, $payload);
    // Parse response za session ID...
}
```

#### 4. Upload (najkompleksnije)
```php
private function megaUploadFile(...): ?string
{
    // 1. Generiši file key i IV
    $fileKey = $this->generateKey();
    $fileIv = $this->generateIv();
    
    // 2. Enkriptuj filename
    $encryptedFilename = $this->aesEncrypt($filename, $fileKey, $fileIv);
    
    // 3. Podeli fajl u chunks (8MB)
    $chunks = $this->splitIntoChunks($filePath, 8 * 1024 * 1024);
    
    // 4. Uploaduj svaki chunk
    foreach ($chunks as $chunk) {
        $encryptedChunk = $this->aesEncrypt($chunk, $fileKey, $fileIv);
        // Upload na storage server...
    }
    
    // 5. Kreiraj node
    $nodeHandle = $this->createNode($sessionId, $parentNodeId, $encryptedFilename, $fileKey);
    
    return $nodeHandle;
}
```

**Problem:** Ovo zahteva dosta vremena i testiranja.

---

## 🎯 **FINALNA PREPORUKA:**

### **Probaj prvo megacmd** (ako je moguć):
1. Proveri da li Plesk dozvoljava `exec()`
2. Proveri da li možeš instalirati `megacmd`
3. Ako da - implementiraj `MegaStorageService` sa `megacmd` pozivima

### **Ako megacmd nije moguć, implementiraj direktan MEGA API:**
1. Implementiraj login (`a: "us"`)
2. Implementiraj folder kreiranje (`a: "p"`)
3. Implementiraj upload (`a: "up"`)
4. Implementiraj download (`a: "g"`)
5. Implementiraj delete (`a: "d"`)

---

## ❓ Šta želiš da uradim?

1. **Implementiram megacmd verziju** (ako možeš instalirati megacmd)?
2. **Implementiram direktan MEGA API** (kompleksnije, ali radi bez CLI)?
3. **Pomognem ti da proveriš da li megacmd može da se instalira** na tvom Plesk serveru?

Koja opcija ti najviše odgovara?
