# Ručno brisanje starih fajlova

Pošto komanda možda nije još upload-ovana na server, evo kako ručno obrisati stare fajlove:

## Opcija 1: Preko File Manager-a u Plesk-u

1. Idi na **File Manager** u Plesk-u
2. Navigiraj do: `storage/app/private/documents/user_7/`
3. **Obriši sve fajlove sa `_original` u imenu** - to su originalni fajlovi koji treba da se brišu nakon obrade
4. **Obriši PDF fajlove** koji ne postoje u bazi (proveri preko SQL)

## Opcija 2: Preko SQL upita (provera)

Preko **phpMyAdmin** ili **Database** sekcije u Plesk-u:

```sql
-- Proveri koje fajlove imaš u bazi za korisnika 7
SELECT id, name, file_path, original_file_path, status 
FROM user_documents 
WHERE user_id = 7;
```

**Obriši fajlove koji NISU u ovoj listi.**

## Opcija 3: Proveri da li je komanda upload-ovana

Preko **SSH terminala**:

```bash
# Proveri da li fajl postoji
ls -la app/Console/Commands/CleanupOrphanedFiles.php

# Ako ne postoji, možda treba da se upload-uje fajl na server
# Ako postoji, osveži autoloader:
composer dump-autoload

# Očisti cache
php artisan config:clear
php artisan optimize:clear

# Probaj komandu
php artisan documents:cleanup-orphaned --dry-run --user=7
```

## Šta da obrišeš:

### Originalni fajlovi:
- Sve fajlove sa `_original` u imenu
- Primer: `7-20260117-9114a415_original.png`

### Orphaned PDF fajlovi:
- PDF fajlove koji ne postoje u `user_documents` tabeli
- Proveri preko SQL upita gore

## Za buduće upload-e:

✅ Originalni fajlovi će se automatski brisati nakon obrade  
✅ Obrađeni PDF će se brisati kada se briše dokument iz aplikacije  
✅ Nema više "siročad" fajlova
