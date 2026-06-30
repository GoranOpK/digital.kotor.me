# Objašnjenje `cloud_path` kolone

## Svrha kolone:

`cloud_path` kolona u `user_documents` tabeli čuva **identifikator fajla na Mega.nz** koji omogućava download i brisanje fajla sa cloud storage-a.

## Format vrednosti:

Vrednost u `cloud_path` zavisi od implementacije MEGA API-ja, ali može biti:

### Opcija 1: Node Handle (preporučeno)
Jedinstveni identifikator koji Mega.nz dodeljuje fajlu. Primer:
```
a1b2c3d4e5f6g7h8
```
Ovo je najčešće - jedinstveni node ID koji se koristi za pristup fajlu.

### Opcija 2: Node Handle + Dekripcijski ključ
Ako je potreban i ključ za dekripciju. Primer:
```
a1b2c3d4e5f6g7h8#decryption_key_here
```

### Opcija 3: Public link (alternativa)
Ako se koristi public link format. Primer:
```
https://mega.nz/file/ABCDE#key123
```

## Trenutna implementacija:

Trenutno je **placeholder implementacija** - komentar u kodu kaže:
```php
'cloud_path' => $fileHandle // Node handle koji identifikuje fajl na MEGA
```

Ali `$fileHandle` trenutno vraća `null` jer MEGA upload nije implementiran.

## Kada se postavlja:

- ✅ Postavlja se nakon uspešnog upload-a na Mega.nz
- ✅ Ako upload ne uspe, ostaje `NULL` i fajl ostaje lokalno
- ✅ Ako je `NULL`, znači da fajl nije na cloud-u (lokalno je)

## Kako se koristi:

1. **Download:** `download()` metoda proverava da li postoji `cloud_path` - ako postoji, preuzima sa Mega.nz
2. **Delete:** `destroy()` metoda proverava da li postoji `cloud_path` - ako postoji, briše sa Mega.nz
3. **Storage calculation:** Fajlovi sa `cloud_path` se ne računaju u lokalni storage (20 MB limit)

## Format za buduću implementaciju:

Kada implementiraš stvarni MEGA upload, `cloud_path` bi trebalo da sadrži:
- **Node handle** (najčešće) - jedinstveni ID fajla na Mega.nz
- **Ili kombinaciju** node handle + key ako je potrebno za dekripciju

**Preporuka:** Koristi samo node handle jer je najjednostavniji i jedinstven.

## Primer implementacije (kada se MEGA API implementira):

```php
// Nakon upload-a na Mega.nz
$nodeHandle = $megaClient->uploadFile($filePath, $folder);
$cloudPath = $nodeHandle; // npr. "a1b2c3d4e5f6g7h8"

// Za download:
$fileContent = $megaClient->downloadFile($cloudPath);

// Za delete:
$megaClient->deleteFile($cloudPath);
```

## Provera u bazi:

Možeš proveriti trenutne vrednosti:

```sql
SELECT id, name, file_path, cloud_path, status 
FROM user_documents 
WHERE cloud_path IS NOT NULL;
```

Trenutno bi sve vrednosti trebalo da budu `NULL` jer MEGA upload još ne radi.
