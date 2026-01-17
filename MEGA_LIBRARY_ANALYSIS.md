# Analiza: tuyenlaptrinh/php-mega-nz biblioteka

## 📋 Rezultat istrage

### ✅ Šta biblioteka **PODRŽAVA**:

1. **Download iz public linkova:**
   ```php
   $mega = new \PhpExtended\Mega\Mega('https://mega.nz/file/ABCDE#key123');
   $root = $mega->getRootNodeInfo();
   $content = $mega->downloadFile($root);
   ```

2. **Listanje foldera (public linkovi):**
   ```php
   $children = $mega->getChildren($nodeId);
   ```

3. **Dobijanje informacija o fajlu:**
   ```php
   $size = $node->getNodeSize();
   ```

### ❌ Šta biblioteka **NE PODRŽAVA**:

1. **❌ Login sa email/password** - Nema metode za autentifikaciju
2. **❌ Upload fajlova** - Nema metode za upload
3. **❌ Delete fajlova** - Nema metode za brisanje
4. **❌ Rad sa privatnim nalogom** - Radi samo sa public linkovima

### 📚 Struktura biblioteke:

Biblioteka radi ovako:
- Prima **public MEGA link** (format: `https://mega.nz/file/NODE_ID#KEY`)
- Ekstraktuje `NODE_ID` i `KEY` iz linka
- Koristi te podatke za download/listanje
- **NE radi** sa email/password login-om

### 🔍 Zašto ne odgovara našim potrebama?

Naš sistem zahteva:
- ✅ Upload fajlova u **privatni MEGA nalog** (tvoj email/password)
- ✅ Upload u specifični folder: `digital.kotor/documents/user_7/`
- ✅ Download fajlova iz privatnog naloga
- ✅ Delete fajlova iz privatnog naloga

`tuyenlaptrinh/php-mega-nz` **NEMA** ove funkcionalnosti.

---

## 🎯 Preporuke za implementaciju

### Opcija 1: Implementiraj direktan MEGA API (Najbolje rešenje)

**Prednosti:**
- ✅ Potpuna kontrola
- ✅ Radi tačno kako želimo
- ✅ Nema dodatnih zavisnosti

**Nedostaci:**
- ⚠️ Kompleksna implementacija (enkripcija, chunking)

**Šta treba implementirati:**

#### 1. Login (`a: "us"`)
```php
POST https://g.api.mega.co.nz/cs?id=SEQUENCE
Content-Type: application/json

[{
    "a": "us",
    "user": "email@example.com",
    "uh": "<PBKDF2 hash password-a>"
}]
```

**Zahteva:**
- PBKDF2 hash password-a (100.000 iteracija, SHA-512)
- AES enkripciju email-a
- Dobijanje session ID-a iz odgovora

#### 2. Upload (`a: "up"`)
```php
POST https://g.api.mega.co.nz/cs?id=SEQUENCE
[{
    "a": "up",
    "s": FILE_SIZE,
    "t": PARENT_NODE_ID,
    "name": ENCRYPTED_FILENAME,
    "k": FILE_KEY,
    "iv": IV,
    "meta": ATTRIBUTES
}]
```

**Zahteva:**
- Generisanje AES-128 key-a za fajl
- Enkripciju fajla u chunks (8MB)
- Enkripciju filename-a i metadata
- Chunk upload na storage server
- Kreiranje node-a u MEGA strukturi

#### 3. Download (`a: "g"`)
```php
[{
    "a": "g",
    "g": 1,
    "n": NODE_HANDLE
}]
```

**Zahteva:**
- Session ID
- Node handle
- Dekripciju chunk-ova
- Spajanje u kompletan fajl

#### 4. Delete (`a: "d"`)
```php
[{
    "a": "d",
    "n": NODE_HANDLE
}]
```

**Zahteva:**
- Session ID
- Node handle

---

### Opcija 2: Koristi megacmd CLI alat (Najjednostavnije)

**Prednosti:**
- ✅ Veoma jednostavno
- ✅ Zvaničan MEGA alat
- ✅ Sve funkcionalnosti već postoje

**Nedostaci:**
- ⚠️ Zahteva instalaciju `megacmd` na serveru
- ⚠️ Zahteva CLI pristup (exec iz PHP-a)

**Instalacija:**
```bash
# Linux
wget https://mega.nz/linux/MEGAsync/Debian_12.0/amd64/megacmd_1.5.1-1.1_amd64.deb
sudo dpkg -i megacmd_*.deb

# Ili preko package manager-a
```

**Korišćenje iz PHP-a:**
```php
// Login (jednom)
exec('megacmd --login="email@example.com" --password="password"');

// Upload
exec('megacmd --upload "/path/to/file.pdf" "/Root/digital.kotor/documents/user_7/"');

// Download
exec('megacmd --download "/Root/digital.kotor/documents/user_7/file.pdf" "/local/path/"');

// Delete
exec('megacmd --delete "/Root/digital.kotor/documents/user_7/file.pdf"');
```

**Implementacija u `MegaStorageService`:**
```php
private function uploadViaCmd(string $filePath, string $remotePath): array
{
    $cmd = sprintf(
        'megacmd --upload "%s" "/Root/%s/"',
        escapeshellarg($filePath),
        escapeshellarg($remotePath)
    );
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0) {
        // Uspešno - dobij node handle iz output-a ili iz MEGA strukture
        return ['success' => true, 'cloud_path' => $this->getNodeHandle($remotePath)];
    }
    
    return ['success' => false, 'error' => 'Upload failed'];
}
```

---

### Opcija 3: Koristi alternativnu PHP biblioteku

**Istraživanje:**
- `php-extended/php-api-nz-mega-object` - takođe samo public linkovi
- `cybercog/laravel-mega-nz` - možda ima više funkcionalnosti (treba proveriti)

**Problem:** Većina PHP biblioteka za MEGA su ograničene na public linkove.

---

## 🎯 **MOJA PREPORUKA:**

### **Korak 1: Probaj megacmd (najbrže)**

Ako tvoj Plesk hosting dozvoljava instalaciju CLI alata ili već ima `megacmd`, ovo je najbrže rešenje. Mogu da implementiram `MegaStorageService` da koristi `megacmd` komande.

### **Korak 2: Ako megacmd nije moguć, implementiraj MEGA API direktno**

Ako CLI pristup nije moguć, implementiraćemo direktan MEGA API pristup sa:
- PBKDF2 hash-om (koristi `hash_pbkdf2()` iz PHP-a)
- AES enkripcijom (koristi `openssl_encrypt()`)
- HTTP pozivima (koristi Laravel `Http::` facade)

---

## ❓ Šta želiš da uradimo?

1. **Implementiram megacmd pristup** (ako je moguć na tvom serveru)?
2. **Implementiram direktan MEGA API** (kompleksnije, ali radi bez CLI)?
3. **Istražim druge PHP biblioteke** koje možda podržavaju upload?

Koja opcija ti najviše odgovara?
