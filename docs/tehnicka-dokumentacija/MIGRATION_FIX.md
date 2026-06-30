# Fix za migraciju i cloud_path problem

## Problem:

1. **Migracija nije pokrenuta** - Kolona `cloud_path` ne postoji u bazi
2. **Dokument ostaje u "processing"** - Update pada jer pokušava da postavi `cloud_path` koji ne postoji
3. **MEGA upload ne radi** - Placeholder implementacija (očekivano)

## Rešenje:

### 1. Pokreni migraciju ponovo na serveru:

Preko SSH terminala ili Plesk komandne linije:

```bash
php artisan migrate
```

Ili ako hoćeš da pokreneš samo ovu migraciju:

```bash
php artisan migrate --path=database/migrations/2025_01_16_000001_add_cloud_path_to_user_documents_table.php
```

### 2. Proveri da li je migracija uspešna:

```bash
php artisan migrate:status
```

Trebalo bi da vidiš migraciju `2025_01_16_000001_add_cloud_path_to_user_documents_table` sa statusom "Ran".

### 3. Proveri da li kolona postoji:

Možeš direktno u bazi (preko phpMyAdmin ili CLI):

```sql
DESCRIBE user_documents;
```

Ili kroz Laravel:

```bash
php artisan tinker
```

Zatim:
```php
Schema::hasColumn('user_documents', 'cloud_path');
// Treba da vrati true
```

### 4. Popravi dokument koji je zaglavljen u "processing":

Ako imaš dokument ID 34 koji je u "processing" statusu, možeš ga popraviti:

```bash
php artisan tinker
```

```php
$doc = \App\Models\UserDocument::find(34);
$doc->status = 'failed'; // ili 'pending' ako hoćeš da ga pokušaš ponovo
$doc->save();
```

ILI direktno u bazi:

```sql
UPDATE user_documents SET status = 'failed' WHERE id = 34;
```

## Promene u kodu:

Dodata je provera da li kolona `cloud_path` postoji pre nego što pokuša update. Kod sada:
- Radi i bez `cloud_path` kolone (backward compatibility)
- Ne pada ako migracija nije pokrenuta
- Automatski koristi `cloud_path` ako kolona postoji

## Za MEGA upload:

MEGA upload trenutno ne radi jer je placeholder implementacija. Fajlovi će se zadržati lokalno dok se ne implementira stvarni MEGA API pristup.

**Trenutno ponašanje:**
- Fajl se obrađuje ✅
- Fajl se čuva lokalno ✅
- MEGA upload ne radi (fallback na lokalno) ⚠️
- Fajl ostaje lokalno umesto da se obriše ✅ (zaštita od gubitka)

## Testiranje nakon migracije:

1. Pokreni migraciju
2. Testiraj upload novog dokumenta
3. Proveri da dokument dobije status "processed" (ne "processing")
4. Proveri da se fajl čuva lokalno
5. Proveri logove za MEGA upload poruke (trenutno će biti "failed", što je ok)
