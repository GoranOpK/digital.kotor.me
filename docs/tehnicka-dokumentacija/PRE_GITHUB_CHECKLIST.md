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
- ✅ `database/migrations/2025_01_16_000001_add_cloud_path_to_user_documents_table.php`
- ✅ `resources/js/mega-upload.js` (browser-side MEGA upload)

### MODIFIKOVANI FAJLOVI:
- ✅ `app/Models/UserDocument.php` (dodato `cloud_path`)
- ✅ `app/Services/DocumentProcessor.php` (uklonjen server-side MEGA upload)
- ✅ `app/Http/Controllers/DocumentController.php` (dodati `getMegaSession` i `storeMegaMetadata`, modifikovan download)
- ✅ `package.json` (dodat `megajs`)
- ✅ `routes/web.php` (dodate rute za megajs)
- ✅ `resources/views/documents/index.blade.php` (modifikovan upload form)

### DOKUMENTACIJA (opciono, ali preporučeno):
- ✅ `MEGA_BROWSER_UPLOAD_PLAN.md`
- ✅ `MEGA_BROWSER_UPLOAD_SETUP.md`
- ✅ `MEGAJS_SETUP_COMPLETE.md`
- ✅ `CLEANUP_SUMMARY.md`

## 🚀 Git komande za commit:

```bash
# Proveri status
git status

# Dodaj sve nove/modifikovane fajlove (osim onih u .gitignore)
git add .

# Proveri šta će biti commit-ovano
git status

# Commit sa opisom
git commit -m "Add browser-side MEGA.nz upload integration using megajs

- Add cloud_path column to user_documents table
- Add browser-side MEGA upload using megajs library
- Add getMegaSession and storeMegaMetadata endpoints
- Modify DocumentController download to redirect to MEGA links
- Add mega-upload.js for client-side upload handling
- Update package.json with megajs dependency
- Add MEGA configuration to config/services.php
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
