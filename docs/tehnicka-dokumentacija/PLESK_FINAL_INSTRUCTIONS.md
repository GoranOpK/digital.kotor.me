# Finalne instrukcije za Plesk - Laravel 12

## Važno: Laravel 12 i PHP 8.3.27

✅ **Laravel 12** koristi `bootstrap/app.php` za konfiguraciju (ne `Kernel.php` koji više ne postoji)  
✅ **PHP 8.3.27** je potpuno kompatibilan sa Laravel 12

## Korak 1: Pokretanje migracije

U **Laravel Toolkit** → **Artisan Commands**, pokrenite:

```
php artisan migrate
```

## Korak 2: Provera queue konfiguracije

U **Laravel Toolkit** → **Artisan Commands**:

```
grep QUEUE_CONNECTION .env
```

Ako ne postoji ili nije `database`, dodajte u `.env` fajl (kroz **File Manager**):
```
QUEUE_CONNECTION=database
```

Zatim:
```
php artisan config:clear
php artisan cache:clear
```

## Korak 3: Konfigurisanje Queue Workera u Plesk Scheduled Tasks

**Ovo je jedini način za Plesk** - direktno kroz Scheduled Tasks (nema potrebe za Kernel.php ili scheduler konfiguracijom).

### Instrukcije:

1. Idite u **Tools & Settings** → **Scheduled Tasks**
2. Kliknite **Add Task**
3. Popunite:

   **Task Name:** `Laravel Queue Worker`
   
   **Run:** `Custom script`
   
   **Script path:**
   ```
   cd /putanja/do/projekta && php artisan queue:work --tries=3 --timeout=300 --max-time=3600 --stop-when-empty
   ```
   
   **Kako pronaći putanju:**
   - U **Laravel Toolkit** → **Application Root Path**
   - Ili u **File Manager**, idite do `artisan` fajla i kopirajte putanju
   - Obično: `/var/www/vhosts/yourdomain.com/httpdocs`
   
   **Run:** `Every minute`
   
   **Run as:** `root` (ili korisnik koji ima pristup)
   
4. Kliknite **OK**
5. Proverite da li je task **aktiviran** (checkbox)

## Korak 4: Provera da li radi

U **Laravel Toolkit** → **Artisan Commands**, pokrenite:

```
php artisan queue:work --once
```

Ako se komanda izvršava bez greške, sve je u redu.

## Korak 5: Testiranje

1. Upload-ujte dokument kroz aplikaciju
2. Proverite status dokumenta - trebalo bi da se menja:
   - `pending` → `processing` → `processed`
3. Proverite logove u **File Manager** → `storage/logs/laravel.log`

## Provera logova

U **File Manager**, otvorite:
- `storage/logs/laravel.log` - glavni log
- `storage/logs/queue.log` - queue log (ako postoji)

## Provera failed jobs

U **Laravel Toolkit** → **Artisan Commands**:

```
php artisan queue:failed
```

## Troubleshooting

### Queue worker se ne pokreće
- ✅ Proverite da li je task **aktiviran** u Scheduled Tasks
- ✅ Proverite putanju do projekta (mora biti tačna)
- ✅ Proverite logove: `storage/logs/laravel.log`
- ✅ Proverite da li postoji `jobs` tabela: `php artisan tinker` → `\DB::table('jobs')->count();`

### Jobs se ne obrađuju
- ✅ Proverite da li je `QUEUE_CONNECTION=database` u `.env`
- ✅ Proverite logove: `storage/logs/laravel.log`
- ✅ Proverite failed jobs: `php artisan queue:failed`
- ✅ Proverite da li je Scheduled Task pokrenut

### Permission greške
- ✅ U **File Manager**, proverite permissions za `storage` folder (trebalo bi da bude 755 ili 775)
- ✅ Proverite da li `storage/logs` direktorijum postoji i ima prava za pisanje

## Važne napomene

- ✅ **Laravel 12** - koristi `bootstrap/app.php`, ne `Kernel.php`
- ✅ **Queue worker mora biti pokrenut** - bez njega dokumenti neće biti obrađivani
- ✅ **Scheduled Tasks** - jedini način za Plesk (nema SSH pristup)
- ✅ **Logovi** - sve greške se čuvaju u `storage/logs/laravel.log`

## Komande koje možete pokrenuti u Laravel Toolkit

```
# Migracija
php artisan migrate

# Clear cache
php artisan config:clear
php artisan cache:clear

# Provera queue
php artisan queue:work --once
php artisan queue:failed

# Provera jobs tabele
php artisan tinker
>>> \DB::table('jobs')->count();
```

---

**Sve je spremno za pokretanje u Plesk-u!** 🚀

