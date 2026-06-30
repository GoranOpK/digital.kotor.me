# Popravka zaglavljenog dokumenta ID 34

## Problem:

Dokument ID 34 je zaglavljen u "processing" statusu jer je update pao pre nego što sam dodao fallback proveru za `cloud_path` kolonu.

## Rešenje:

### Opcija 1: Popravi preko SQL (najbrže):

Preko phpMyAdmin ili MySQL CLI:

```sql
-- Proveri trenutni status
SELECT id, name, status, file_path, cloud_path FROM user_documents WHERE id = 34;

-- Postavi status na 'failed' (možeš kasnije obrisati ili pokušati ponovo)
UPDATE user_documents SET status = 'failed' WHERE id = 34;

-- ILI postavi na 'processed' ako fajl postoji
UPDATE user_documents 
SET status = 'processed', processed_at = NOW() 
WHERE id = 34 
AND file_path IS NOT NULL;
```

### Opcija 2: Popravi preko Laravel Tinker:

```bash
php artisan tinker
```

Zatim:

```php
$doc = \App\Models\UserDocument::find(34);
$doc->status = 'failed'; // ili 'processed' ako fajl postoji
$doc->save();
exit
```

### Opcija 3: Obriši dokument (ako nije važan):

```sql
DELETE FROM user_documents WHERE id = 34;
```

**NAPOMENA:** Ako obrišeš dokument, obriši i fajlove sa servera ako postoje.

## Za MEGA upload:

MEGA upload trenutno **ne radi** jer je placeholder implementacija. Fajlovi se čuvaju **lokalno** umesto na MEGA.

**Status:**
- ✅ Fajl se obrađuje
- ✅ Fajl se čuva lokalno
- ⚠️ MEGA upload ne radi (placeholder)
- ✅ `cloud_path` ostaje NULL (očekivano)

Dok se ne implementira stvarni MEGA API pristup, fajlovi će ostati lokalno.
