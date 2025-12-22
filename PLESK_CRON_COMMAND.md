# Komanda za Plesk Scheduled Tasks (Cron)

## Komanda za cron task

U **Plesk Scheduled Tasks**, dodajte sledeću komandu:

### Opcija 1: Obrada svih job-ova (preporučeno)

**Command:**
```bash
cd /putanja/do/projekta && php artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

**Run:** `Every minute` (ili `Every 5 minutes` ako želite manje opterećenje)

**Napomena:** 
- `--stop-when-empty` znači da će komanda završiti kada nema više job-ova za obradu
- Ovo je idealno za cron jer ne ostavlja proces koji radi u pozadini
- Pokreće se periodično i obrađuje sve job-ove koji čekaju

### Opcija 2: Obrada jednog job-a po pokretanju

**Command:**
```bash
cd /putanja/do/projekta && php artisan queue:work --once --tries=3 --timeout=300
```

**Run:** `Every minute` (ili češće, npr. `Every 30 seconds`)

**Napomena:**
- `--once` znači da će obraditi samo jedan job i završiti
- Korisno ako imate puno job-ova i želite da ih obrađujete postepeno
- Možete pokrenuti češće (svakih 30 sekundi)

## Kako pronaći putanju do projekta

1. U **Laravel Toolkit** → **Application Root Path**
2. Ili u **File Manager**, idite do `artisan` fajla i kopirajte putanju
3. Obično: `/var/www/vhosts/yourdomain.com/httpdocs`

## Primer konfiguracije u Plesk Scheduled Tasks

**Task Name:** `Laravel Queue Worker`

**Run:** `Custom script`

**Script path:**
```
cd /var/www/vhosts/yourdomain.com/httpdocs && php artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

**Run:** `Every minute`

**Run as:** `root` (ili korisnik koji ima pristup)

## Pre pokretanja

Pre nego što pokrenete cron task, uradite:

1. **Migracija:**
   ```
   php artisan migrate
   ```

2. **Provera queue konfiguracije:**
   ```
   grep QUEUE_CONNECTION .env
   ```
   Ako ne postoji, dodajte u `.env`:
   ```
   QUEUE_CONNECTION=database
   ```

3. **Clear cache:**
   ```
   php artisan config:clear
   php artisan cache:clear
   ```

## Provera da li radi

Nakon što pokrenete cron task:

1. Upload-ujte test dokument
2. Sačekajte 1-2 minuta
3. Proverite status dokumenta - trebalo bi da se promeni sa `pending` → `processing` → `processed`
4. Proverite logove: `storage/logs/laravel.log`

## Troubleshooting

### Jobs se ne obrađuju
- Proverite da li je cron task **aktiviran**
- Proverite logove: `storage/logs/laravel.log`
- Proverite failed jobs: `php artisan queue:failed`
- Proverite da li postoji `jobs` tabela

### Provera cron logova
U Plesk-u, možete proveriti izvršavanje cron task-a u **Scheduled Tasks** → **Execution History**

---

**Preporučena opcija:** **Opcija 1** sa `--stop-when-empty` i pokretanjem svakog minuta.

