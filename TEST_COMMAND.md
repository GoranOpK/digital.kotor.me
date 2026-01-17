# Testiranje komande

## Provera da li komanda postoji:

Preko SSH terminala u Plesk-u:

```bash
# Lista svih komandi (bez grep-a)
php artisan list

# Ili direktno probaj komandu
php artisan documents:cleanup-orphaned --help

# Ili samo proveri da li se izvršava
php artisan documents:cleanup-orphaned --dry-run --user=7
```

## Ako komanda još ne postoji:

### 1. Osveži autoloader:
```bash
composer dump-autoload
```

### 2. Očisti cache:
```bash
php artisan config:clear
php artisan optimize:clear
```

### 3. Proveri da li fajl postoji:
```bash
ls -la app/Console/Commands/CleanupOrphanedFiles.php
```

## Alternativno - ručno brisanje:

Ako komanda ne radi, možeš ručno obrisati fajlove preko File Manager-a:
- Idi u `storage/app/private/documents/user_7/`
- Obriši sve fajlove sa `_original` u imenu
- Obriši PDF fajlove koji više nisu u bazi
