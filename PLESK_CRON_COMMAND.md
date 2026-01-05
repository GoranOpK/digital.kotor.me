# Komanda za Plesk Scheduled Tasks (Cron)

## Komanda za cron task

U **Plesk Scheduled Tasks**, dodajte sledeću komandu:

### Opcija 1: Obrada svih job-ova (preporučeno)

**VAŽNO:** Plesk Scheduled Tasks možda ne podržava `&&` operator. Koristite jednu od sledećih varijanti:

**Varijanta A (sa cd):**
```bash
cd /putanja/do/projekta && php artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

**Varijanta B (bez cd - preporučeno ako Varijanta A ne radi):**
```bash
php /putanja/do/projekta/artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

**Run:** `Every minute` (ili `Every 5 minutes` ako želite manje opterećenje)

**Napomena:** 
- `--stop-when-empty` znači da će komanda završiti kada nema više job-ova za obradu
- Ovo je idealno za cron jer ne ostavlja proces koji radi u pozadini
- Pokreće se periodično i obrađuje sve job-ove koji čekaju
- **Ako dobijate "No such file or directory" grešku, koristite Varijantu B ili proverite putanju**

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

### Metoda 1: Kroz Artisan komandu (NAJPOUZDANIJE!)

U **Laravel Toolkit** → **Artisan** tab, pokrenite:

```
path:show
```

**Ovo će vam pokazati tačnu putanju do projekta!**

**Napomena:** Ova komanda je kreirana posebno za ovu svrhu. Ako ne radi, koristite Metodu 2.

### Metoda 2: Kroz File Manager - Home Directory

Pošto vidite "Home directory" u File Manager-u, putanja je verovatno:

```
/home/opstinakotor/digital.kotor.me
```

Ili:

```
/home/opstin/digital.kotor.me
```

### Metoda 3: Test komanda u Scheduled Tasks

Dodajte test task u Scheduled Tasks:

**Command:**
```bash
test -f /home/opstinakotor/digital.kotor.me/artisan && echo "TAČNA: /home/opstinakotor/digital.kotor.me" || test -f /home/opstin/digital.kotor.me/artisan && echo "TAČNA: /home/opstin/digital.kotor.me" || find /home -name "artisan" -path "*/digital.kotor.me/artisan" 2>/dev/null | head -1
```

**Run:** `Once`

Ova komanda će pokazati tačnu putanju.

**VAŽNO:** 
- Ako dobijate "No such file or directory" grešku, koristite **Varijantu B** (bez cd)
- Ili proverite putanju kroz File Manager (Metoda 1)

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

