# Checklist pre GitHub commit-a

## ✅ BEZBEDNOST - Proveri da nemaš:

### ❌ NE COMMIT-UJ:
- [ ] `.env` fajl (već je u `.gitignore`)
- [ ] `vendor/` folder (već je u `.gitignore`)
- [ ] Hardcoded passwords ili API keys u kodu
- [ ] `composer.lock` - OVO JE POZIV - Laravel projekti obično **JESU** commit-uju `composer.lock`
- [ ] Database fajlove (`.sqlite`, `.sql` fajlovi sa podacima)

### ✅ SIGURNO ZA COMMIT:

- [x] Svi PHP fajlovi iz `app/` direktorijuma
- [x] Konfiguracija iz `config/` (koristi `env()` funkcije - dobro!)
- [x] Migracije iz `database/migrations/`
- [x] Routes fajlovi
- [x] Views (Blade templates)
- [x] `composer.json` (zahteva nove pakete)
- [x] Dokumentacija (`.md` fajlovi)

## 📋 Provera:

### 1. Proveri da `.env` nije u staging area:

```bash
git status
```

Ako vidiš `.env`, ne commit-uj ga. Ako je slučajno dodat:

```bash
git reset HEAD .env
```

### 2. Proveri da li postoji `.env.example`:

Trebalo bi da imaš `.env.example` fajl sa primerima (bez stvarnih credentials). Ako nemaš, kreiraj ga sa:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# ... ostale konfiguracije ...

# MEGA.nz Configuration
MEGA_EMAIL=your_email@example.com
MEGA_PASSWORD=your_password
MEGA_BASE_FOLDER=digital.kotor
```

### 3. Proveri da li si dodao credentials direktno u kod:

Proveri da li u bilo kom fajlu imaš:
- Email adrese sa lozinkama
- API keys hardcoded
- Database passwords

**REZULTAT:** ✅ Tvoj kod koristi `env()` funkcije - dobro je!

## 📝 Šta commit-ovati:

### NOVI FAJLOVI (kreirani za MEGA integraciju):
- ✅ `app/Services/MegaStorageService.php`
- ✅ `database/migrations/2025_01_16_000001_add_cloud_path_to_user_documents_table.php`
- ✅ `config/services.php` (samo dodata MEGA sekcija)

### MODIFIKOVANI FAJLOVI:
- ✅ `app/Models/UserDocument.php` (dodato `cloud_path`)
- ✅ `app/Services/DocumentProcessor.php` (modifikovan za MEGA upload)
- ✅ `app/Http/Controllers/DocumentController.php` (modifikovan download/destroy)
- ✅ `composer.json` (dodat `tuyenlaptrinh/php-mega-nz`)

### DOKUMENTACIJA (opciono, ali preporučeno):
- ✅ `MEGA_INTEGRATION_INSTRUCTIONS.md`
- ✅ `PLESK_COMPOSER_INSTRUCTIONS.md`
- ✅ `PLESK_UPDATE_INSTRUCTIONS.md`
- ✅ `ALTERNATIVE_MEGA_INSTALL.md`

## 🚀 Git komande za commit:

```bash
# Proveri status
git status

# Dodaj sve nove/modifikovane fajlove (osim onih u .gitignore)
git add .

# Proveri šta će biti commit-ovano
git status

# Commit sa opisom
git commit -m "Add Mega.nz cloud storage integration

- Add MegaStorageService for upload/download/delete operations
- Add cloud_path column to user_documents table
- Modify DocumentProcessor to upload to Mega.nz after processing
- Modify DocumentController to download/delete from Mega.nz
- Update storage management to exclude cloud files from local quota
- Add MEGA configuration to config/services.php
- Update composer.json with tuyenlaptrinh/php-mega-nz package
- Add integration documentation"

# Push na GitHub
git push origin main
# ILI
git push origin master
```

## ⚠️ VAŽNO:

1. **NIKADA ne commit-uj `.env` fajl** - već je u `.gitignore`, ali proveri
2. **`composer.lock` MOŽE da bude commit-ovan** - Laravel projekti obično commit-uju lock fajl
3. **Proveri da li su credentials samo u `.env`** - ne u kodu direktno
4. **Ako koristiš private repo**, bolje je - ali i dalje ne commit-uj credentials

## ✅ Finalna provera pre push-a:

```bash
# Proveri šta će biti push-ovano
git log --oneline -5

# Proveri da nemaš local promene koje nisu commit-ovane
git status

# Sada možeš da push-uješ
git push
```
