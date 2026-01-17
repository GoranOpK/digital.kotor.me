# Git Commit i Push Instrukcije

## ✅ Sve je spremno za GitHub!

## 🚀 Koraci za commit i push:

### 1. Proveri status:
```bash
git status
```

**VAŽNO:** Proveri da `.env` NIJE u staging area. Ako vidiš `.env`, pokreni:
```bash
git reset HEAD .env
```

### 2. Dodaj sve promene:
```bash
git add .
```

### 3. Proveri šta će biti commit-ovano:
```bash
git status
```

Trebalo bi da vidiš:
- `app/Services/MegaStorageService.php` (novi)
- `database/migrations/2025_01_16_000001_add_cloud_path_to_user_documents_table.php` (novi)
- `app/Models/UserDocument.php` (modifikovan)
- `app/Services/DocumentProcessor.php` (modifikovan)
- `app/Http/Controllers/DocumentController.php` (modifikovan)
- `config/services.php` (modifikovan)
- `composer.json` (modifikovan)
- `.md` dokumentacija fajlovi (novi)

**NE bi trebalo da vidiš:**
- `.env` ❌
- `vendor/` ❌

### 4. Commit sa opisom:
```bash
git commit -m "Add Mega.nz cloud storage integration

- Add MegaStorageService for upload/download/delete operations
- Add cloud_path column to user_documents table
- Modify DocumentProcessor to upload to Mega.nz after processing
- Modify DocumentController to download/delete from Mega.nz
- Update storage management to exclude cloud files from local quota
- Add MEGA configuration to config/services.php
- Update composer.json with tuyenlaptrinh/php-mega-nz package
- Add integration and setup documentation"
```

### 5. Push na GitHub:
```bash
git push origin main
```

**ILI** ako tvoja glavna grana se zove `master`:
```bash
git push origin master
```

## 📋 Provera preko terminala:

Nakon push-a, možeš proveriti na GitHub-u da li su svi fajlovi upload-ovani.

## ⚠️ Provera .env.example (opciono):

Ako `.env.example` fajl nema MEGA varijable, dodaj ih ručno:
```env
# MEGA.nz Configuration
MEGA_EMAIL=
MEGA_PASSWORD=
MEGA_BASE_FOLDER=digital.kotor
```

Zatim commit-uj i taj fajl:
```bash
git add .env.example
git commit -m "Add MEGA configuration to .env.example"
git push origin main
```

## ✅ Gotovo!

Nakon uspešnog push-a, svi kodovi i dokumentacija će biti na GitHub-u. Credentials ostaju samo u `.env` fajlu na serveru.
