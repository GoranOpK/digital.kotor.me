# Fix za registraciju komande

## Problem:

Komanda `documents:cleanup-orphaned` se ne registruje automatski u Laravel 12.

## Rešenje:

### 1. Osveži Composer autoloader:

Preko SSH terminala u Plesk-u:

```bash
cd /path/to/your/project  # putanja do projekta
composer dump-autoload
```

### 2. Očisti Laravel cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

### 3. Proveri da li se komanda pojavila:

```bash
php artisan list | grep documents
```

Ili:

```bash
php artisan documents:cleanup-orphaned --help
```

### 4. Ako i dalje ne radi:

Proveri da li fajl postoji na serveru:
- Putanja: `app/Console/Commands/CleanupOrphanedFiles.php`
- Proveri da li se fajl upload-ovao na server

### 5. Alternativno rešenje - ručno brisanje:

Ako komanda ne radi, možeš ručno obrisati fajlove preko File Manager-a ili SQL upita:

**Preko SQL (provera):**
```sql
-- Pronađi sve dokumente za korisnika 7
SELECT id, name, file_path, original_file_path FROM user_documents WHERE user_id = 7;
```

**Preko File Manager-a:**
- Idi u `storage/app/private/documents/user_7/`
- Obriši fajlove koji imaju `_original` u imenu (to su originalni fajlovi koji treba da se brišu)
- Obriši PDF fajlove koji više nisu u bazi
