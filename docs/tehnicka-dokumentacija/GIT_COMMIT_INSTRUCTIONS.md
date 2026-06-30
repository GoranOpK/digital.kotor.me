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
- `database/migrations/2025_01_16_000001_add_cloud_path_to_user_documents_table.php` (novi)
- `app/Models/UserDocument.php` (modifikovan - dodato `cloud_path`)
- `app/Services/DocumentProcessor.php` (modifikovan)
- `app/Http/Controllers/DocumentController.php` (modifikovan - dodati `getMegaSession` i `storeMegaMetadata`)
- `config/services.php` (modifikovan - dodata MEGA sekcija)
- `resources/js/mega-upload.js` (novi - browser-side MEGA upload)
- `package.json` (modifikovan - dodat `megajs`)
- `routes/web.php` (modifikovan - dodate rute za megajs)
- `.md` dokumentacija fajlovi (browser-side MEGA)

**NE bi trebalo da vidiš:**
- `.env` ❌
- `vendor/` ❌

### 4. Commit sa opisom:
```bash
git commit -m "Add browser-side MEGA.nz upload integration using megajs

- Add cloud_path column to user_documents table
- Add browser-side MEGA upload using megajs library
- Add getMegaSession and storeMegaMetadata endpoints
- Modify DocumentController download to redirect to MEGA links
- Add mega-upload.js for client-side upload handling
- Update package.json with megajs dependency
- Add MEGA configuration to config/services.php
- Add browser-side upload documentation"
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
