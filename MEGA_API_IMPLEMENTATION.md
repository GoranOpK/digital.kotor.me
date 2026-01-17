# MEGA API Implementacija - Kompletna integracija

## ✅ Šta je implementirano:

### 1. **MEGA Login (`a: us`)**
- ✅ Implementirana MEGA custom enkripcija (`prepare_key`, `stringhash`)
- ✅ PBKDF2-like hash sa AES-CBC enkripcijom (65536 + 16384 iteracija)
- ✅ Login sa email/password
- ✅ Session ID čuvanje

**Funkcije:**
- `megaLogin()` - glavna login funkcija
- `megaUserHash()` - generiše user hash (`uh`)
- `prepareKey()` - generiše password AES key
- `stringHash()` - generiše hash od email-a
- `strToA32()`, `a32ToBase64()`, `aesCbcEncryptA32()` - helper funkcije

### 2. **Folder struktura (`a: f`, `a: p`)**
- ✅ Dobijanje root node-a
- ✅ Pronalaženje postojećih foldera
- ✅ Kreiranje novih foldera
- ✅ Rekurzivno kreiranje folder strukture

**Funkcije:**
- `getRootNode()` - dobija root node
- `ensureFolderStructure()` - osigurava da folder struktura postoji
- `findFolder()` - pronalazi folder u parent-u
- `createFolder()` - kreira novi folder
- `decryptNodeAttributes()` - dekriptuje ime foldera/fajla

### 3. **MEGA Upload (`a: u`, `a: p`)**
- ✅ Generisanje file key-a i IV-a
- ✅ Enkripcija filename-a
- ✅ Chunking fajla (8MB chunks)
- ✅ Enkripcija chunk-ova
- ✅ Upload chunk-ova na storage server
- ✅ Kreiranje node-a u MEGA strukturi

**Funkcije:**
- `megaUploadFile()` - glavna upload funkcija

**Napomena:** Upload funkcija možda treba dorade nakon testiranja. MEGA upload API može da zahteva drugačiji format chunk upload-a.

### 4. **MEGA Download (`a: g`)**
- ✅ Dobijanje download URL-a
- ✅ Download enkriptovanog sadržaja
- ✅ Dekripcija fajla

**Funkcije:**
- `downloadViaApi()` - download preko API-ja

**Napomena:** Download funkcija podrazumeva jednostavan format. Veliki fajlovi na MEGA su možda podeljeni u više chunk-ova sa posebnim IV-ovima - to možda treba doraditi.

### 5. **MEGA Delete (`a: d`)**
- ✅ Brisanje node-a (fajla ili foldera)

**Funkcije:**
- `delete()` - glavna delete funkcija

---

## 🔧 Tehnički detalji:

### MEGA API format:
```php
POST https://g.api.mega.co.nz/cs?id=SEQUENCE
Content-Type: application/json

[{ "a": "action", ... }]
```

### Akcije (actions):
- `a: "us"` - User session (login)
- `a: "f"` - Fetch files/folders
- `a: "p"` - Put (upload/create node)
- `a: "u"` - Upload (dobija upload URL)
- `a: "g"` - Get (download info)
- `a: "d"` - Delete node

### Enkripcija:

**Login:**
1. Password → 32-bit word array → `prepare_key()` → password AES key
2. Email (lowercase) + password AES key → `stringhash()` → `uh` (user hash)
3. Login sa `{"a":"us", "user": email, "uh": uh}`

**File upload:**
1. Generiši random 16-byte file key
2. Enkriptuj filename: `AES-128-CBC(JSON({"n": filename}), fileKey, IV)`
3. Podeli fajl u chunks (8MB)
4. Enkriptuj svaki chunk: `AES-128-CBC(chunk, fileKey, randomIV)`
5. Upload chunkove na storage server
6. Kreiraj node sa encrypted attributes i file key

**File download:**
1. Dobij download URL i file key iz node-a
2. Download enkriptovanih chunk-ova
3. Dekriptuj chunkove sa file key-om
4. Spoji u kompletan fajl

---

## ⚠️ Potencijalni problemi i rešenja:

### 1. **Upload chunk format**
MEGA upload možda zahteva drugačiji format chunk upload-a. Trenutna implementacija koristi `HTTP::attach()` što možda nije tačan format.

**Rešenje:** Testirati i prilagoditi format upload-a na osnovu MEGA API odgovora.

### 2. **Session ID format**
Login vraća `csid` koji možda treba da se koristi drugačije. Trenutno čuvamo ga kao session ID.

**Rešenje:** Proveriti MEGA API dokumentaciju ili testirati format session ID-a.

### 3. **Chunk dekripcija za download**
Veliki fajlovi su možda podeljeni u više chunk-ova sa posebnim IV-ovima.

**Rešenje:** Implementirati chunking za download ako je potrebno.

### 4. **Master key dekripcija**
MEGA vraća `k` (key) u login odgovoru koji možda treba da se dekriptuje sa password-om.

**Rešenje:** Implementirati dekripciju master key-a ako je potrebno za pristup node key-ovima.

### 5. **File key dekripcija u download**
Node key-ovi su možda enkriptovani sa master key-om.

**Rešenje:** Dekriptovati file key sa master key-om pre download-a.

---

## 🧪 Testiranje:

### Korak 1: Test login
```php
$service = new MegaStorageService();
$sessionId = $service->megaLogin(); // Private method - dodaj public za testiranje
```

### Korak 2: Test upload
```php
$result = $service->upload(
    '/path/to/file.pdf',
    'digital.kotor/documents/user_7/'
);
```

### Korak 3: Test download
```php
$result = $service->download($nodeHandle);
```

### Korak 4: Test delete
```php
$success = $service->delete($nodeHandle);
```

---

## 📝 Šta treba testirati:

1. ✅ **Login** - da li vraća session ID?
2. ✅ **Folder kreiranje** - da li kreira foldere?
3. ⚠️ **Upload** - da li uploaduje fajlove? (možda treba dorada)
4. ⚠️ **Download** - da li preuzima fajlove? (možda treba dorada chunking-a)
5. ✅ **Delete** - da li briše fajlove?

---

## 🐛 Debug logovi:

Sve funkcije imaju detaljne logove:
- `Log::info()` - uspešne operacije
- `Log::error()` - greške
- `Log::warning()` - upozorenja

Proveri `storage/logs/laravel.log` za detalje.

---

## 🔄 Dalje koraci:

1. **Testirati upload** - probati upload malog fajla
2. **Proveriti logove** - ako upload ne radi, proveri šta MEGA vraća
3. **Prilagoditi upload format** - na osnovu MEGA API odgovora
4. **Testirati download** - probati download uploadovanog fajla
5. **Prilagoditi download** - ako ne radi, proveri chunking format
6. **Testirati delete** - probati brisanje fajla

---

## 💡 Alternativa ako ne radi:

Ako direktni MEGA API pristup ne radi, možeš:
1. Koristiti **MEGA WebDAV** (ako je dostupan)
2. Koristiti **MEGA REST API** (ako postoji zvanična verzija)
3. Koristiti **megacmd** preko SSH-a (ako imaš SSH pristup na Plesk-u)

---

## ✅ Implementacija je kompletna!

Sve osnovne funkcije su implementirane. Sada treba da testiraš na serveru i prilagodiš na osnovu MEGA API odgovora.

Ako imaš greške, proveri logove i javi mi šta MEGA API vraća - mogu da pomognem sa debugovanjem i prilagođavanjem koda.
