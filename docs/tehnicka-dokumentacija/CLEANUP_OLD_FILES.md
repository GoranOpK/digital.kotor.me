# Cleanup starih fajlova na serveru

## Problem:

Na serveru ostaju stari PDF fajlovi i originalni fajlovi koji treba da se brišu:
- Originalni fajlovi treba da se brišu nakon obrade
- Obrađeni PDF treba da se briše kada se briše dokument iz aplikacije

## Rešenje u kodu:

✅ **Dodato brisanje originalnih fajlova nakon obrade:**
- `ProcessDocumentJob` - sada briše originalni fajl nakon uspešne obrade
- `DocumentController::store()` - direktna obrada već briše originalni fajl
- `DocumentController::processMergeDirectly()` - briše originalne fajlove nakon spajanja

✅ **Brisanje obrađenog PDF-a:**
- `DocumentController::destroy()` - briše obrađeni PDF (`file_path`) i original (`original_file_path`) kada se briše dokument

## Cleanup starih fajlova na serveru:

### Opcija 1: Ručno brisanje (preko File Manager-a)

Preko Plesk File Manager-a, idi u:
```
storage/app/private/documents/user_7/
```

Obriši:
- Sve fajlove sa `_original` u imenu (originalni fajlovi)
- PDF fajlove koji ne postoje u bazi

### Opcija 2: SQL upit za proveru (preko phpMyAdmin)

```sql
-- Pronađi sve dokumente koji još postoje u bazi
SELECT id, name, file_path, original_file_path, status 
FROM user_documents 
WHERE user_id = 7;
```

### Opcija 3: Kreiraj Artisan komandu za cleanup

Hajde da kreiram helper komandu koja:
1. Prolazi kroz sve fajlove u `documents/user_X/` folderima
2. Proverava da li fajl postoji u bazi
3. Briše fajlove koji ne postoje u bazi

## Napomena:

Nova upload-ovanja će automatski brisati originalne fajlove nakon obrade, i obrađeni PDF će se brisati kada se briše dokument iz aplikacije.
