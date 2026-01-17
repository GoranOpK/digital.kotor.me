# Objašnjenje: MEGA upload je placeholder

## Šta znači "placeholder"?

**Placeholder** = "rezervisano mesto" - struktura koda postoji, ali funkcionalnost ne radi. Kod je spreman za implementaciju, ali ne izvršava stvarnu operaciju.

## Trenutno stanje:

### ✅ Šta radi:
- Kod struktura je kompletna
- Metode `upload()`, `download()`, `delete()` postoje
- Integracija sa ostatkom sistema (DocumentProcessor, DocumentController)
- Fallback logika - ako upload ne uspe, fajl ostaje lokalno

### ❌ Šta NE radi:
- **MEGA upload** - metoda `upload()` ne uploaduje stvarno na Mega.nz
- **MEGA download** - metoda `download()` ne preuzima sa Mega.nz
- **MEGA delete** - metoda `delete()` ne briše sa Mega.nz

## Zašto ne radi?

Trenutna implementacija u `MegaStorageService.php`:

```php
private function megaLogin(): ?string
{
    // TODO: Implementirati kompletnu login logiku sa MEGA API-jem
    Log::warning('MEGA login not fully implemented - using placeholder');
    return null; // ← Vraća null umesto stvarnog session ID-a
}

private function megaUploadFile(...): ?string
{
    // TODO: Implementirati kompletnu upload logiku
    return null; // ← Vraća null umesto stvarnog file handle-a
}
```

**Problemi:**
1. `megaLogin()` vraća `null` umesto stvarnog session ID-a
2. `megaUploadFile()` vraća `null` umesto stvarnog file handle-a
3. Nema stvarnih HTTP poziva ka MEGA API-ju
4. Nema implementacije enkripcije/dekripcije koje MEGA zahteva

## Šta treba da se implementira?

### 1. MEGA API autentifikacija:

MEGA API zahteva:
- PBKDF2 hash password-a (100.000 iteracija)
- AES enkripciju
- API pozive ka `https://g.api.mega.co.nz/cs`
- Session ID koji se koristi za sve operacije

**Primer (pseudokod):**
```php
private function megaLogin(): ?string
{
    // 1. Hash password koristeći PBKDF2
    $passwordHash = hash_pbkdf2('sha512', $this->password, $salt, 100000);
    
    // 2. AES enkripcija
    $encryptedEmail = encrypt($this->email, $passwordHash);
    
    // 3. HTTP poziv ka MEGA API
    $response = Http::post('https://g.api.mega.co.nz/cs', [
        'a' => 'us',
        'user' => $encryptedEmail,
        'uh' => $passwordHash
    ]);
    
    // 4. Vrati session ID
    return $response['sessionId'];
}
```

### 2. MEGA upload:

Zahteva:
- Chunk upload (fajlovi se dele na delove)
- AES enkripciju svakog chunk-a
- Upload na MEGA storage servere
- Kreiranje node-a u MEGA strukturi

**Primer (pseudokod):**
```php
private function megaUploadFile(...): ?string
{
    // 1. Podeli fajl u chunks (8MB po chunk-u)
    $chunks = splitFileIntoChunks($filePath, 8 * 1024 * 1024);
    
    // 2. Uploaduj svaki chunk sa enkripcijom
    foreach ($chunks as $chunk) {
        $encryptedChunk = encrypt($chunk, $key);
        uploadChunkToMega($encryptedChunk);
    }
    
    // 3. Kreiraj node u MEGA strukturi
    $nodeHandle = createNode($sessionId, $parentNodeId, $fileName, $fileKey);
    
    return $nodeHandle;
}
```

### 3. MEGA download:

Zahteva:
- Dekripciju fajla
- Spajanje chunk-ova
- Vraćanje kompletnog sadržaja

## Zašto je to komplikovano?

1. **Enkripcija:** MEGA koristi end-to-end enkripciju - sve je enkriptovano
2. **Kompleksnost:** MEGA API nije jednostavan REST API - zahteva specifične formate
3. **Dokumentacija:** Slaba dokumentacija za MEGA API (nema zvaničnu PHP biblioteku)

## Opcije za implementaciju:

### Opcija 1: Koristi postojeću PHP biblioteku (preporučeno)

Već imaš instaliran `tuyenlaptrinh/php-mega-nz` - proveri dokumentaciju te biblioteke.

**Problem:** Biblioteka možda radi samo sa public linkovima, ne sa privatnim upload-om.

### Opcija 2: Implementiraj direktno sa MEGA API-jem

Zahteva:
- Implementaciju PBKDF2 hash-a
- Implementaciju AES enkripcije/dekripcije
- HTTP pozive ka MEGA API-ju
- Parsovanje MEGA odgovora

**Kompleksno, ali radi.**

### Opcija 3: Koristi megacmd CLI alat

Instaliraj `megacmd` na serveru i pozivaj ga iz PHP-a:

```bash
megacmd --upload /path/to/file.pdf /Root/digital.kotor/documents/user_7/
```

**Jednostavnije, ali zahteva CLI pristup i instalaciju megacmd.**

### Opcija 4: Koristi mega.js SDK (preko Node.js)

MEGA ima zvanični Node.js SDK - možeš pozvati Node.js skriptu iz PHP-a.

## Trenutno ponašanje:

Kada sistem pokušava da upload-uje:
1. ✅ Fajl se obrađuje (PDF)
2. ✅ Fajl se čuva lokalno
3. ⚠️ Pokušaj MEGA upload-a (vraća `success => false`)
4. ✅ Fajl ostaje lokalno (fallback)
5. ✅ `cloud_path` ostaje `NULL`

**Zaključak:** Sistem radi, ali fajlovi se čuvaju lokalno umesto na cloud-u.

## Kada implementirati?

MEGA upload može da sačeka - sistem i dalje radi (fajlovi lokalno). Implementiraj kada:
- Treba da se smanji prostor na serveru
- Imaš vreme za implementaciju MEGA API-ja
- Ili nađeš jednostavniju biblioteku/alat
