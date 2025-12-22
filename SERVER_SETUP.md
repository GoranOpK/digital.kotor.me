# Komande za pokretanje na serveru

## 1. Pokretanje migracije

Prvo, pokrenite migraciju da dodate nova polja u tabelu `user_documents`:

```bash
cd /path/to/your/project
php artisan migrate
```

Ako dobijete grešku sa enum kolonom, možete prvo da proverite trenutnu strukturu:

```bash
php artisan migrate:status
```

## 2. Pokretanje Queue Workera

Queue worker mora biti pokrenut da bi se dokumenti obrađivali u pozadini. Imate nekoliko opcija:

### Opcija A: Pokretanje u pozadini (za testiranje)

```bash
php artisan queue:work --tries=3 --timeout=300
```

Ova komanda će raditi dok ne zatvorite terminal. Za produkciju koristite opciju B ili C.

### Opcija B: Pokretanje sa nohup (jednostavnije rešenje)

```bash
nohup php artisan queue:work --tries=3 --timeout=300 > storage/logs/queue.log 2>&1 &
```

Ova komanda će raditi u pozadini čak i kada zatvorite terminal.

### Opcija C: Konfigurisanje Supervisor (preporučeno za produkciju)

Kreirajte fajl `/etc/supervisor/conf.d/digital-kotor-queue.conf`:

```ini
[program:digital-kotor-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --timeout=300 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue.log
stopwaitsecs=3600
```

Zatim pokrenite:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start digital-kotor-queue:*
```

## 3. Provera da li Queue radi

Proverite da li queue worker radi:

```bash
# Provera procesa
ps aux | grep "queue:work"

# Provera logova
tail -f storage/logs/queue.log

# Provera failed jobs
php artisan queue:failed
```

## 4. Provera konfiguracije Queue-a

Proverite da li je queue driver podešen u `.env` fajlu:

```bash
grep QUEUE_CONNECTION .env
```

Trebalo bi da bude:
```
QUEUE_CONNECTION=database
```

Ako nije, ažurirajte `.env` fajl i pokrenite:

```bash
php artisan config:clear
php artisan queue:table  # Ako tabela ne postoji
php artisan migrate
```

## 5. Testiranje

Nakon što je sve pokrenuto, možete testirati:

1. Upload-ujte dokument kroz aplikaciju
2. Proverite da li se kreira zapis u bazi sa statusom `pending`
3. Proverite logove da vidite da li se job pokreće:

```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/queue.log
```

4. Proverite da li se status menja na `processing`, a zatim na `processed`

## 6. Rukovanje failed jobs

Ako neki job ne uspe, možete ga ponovo pokrenuti:

```bash
# Lista failed jobs
php artisan queue:failed

# Ponovno pokretanje određenog job-a
php artisan queue:retry {job-id}

# Ponovno pokretanje svih failed jobs
php artisan queue:retry all

# Brisanje failed job-a
php artisan queue:forget {job-id}
```

## 7. Zaustavljanje Queue Workera

Ako koristite nohup:

```bash
# Pronađite proces ID
ps aux | grep "queue:work"

# Zaustavite proces
kill {process-id}
```

Ako koristite Supervisor:

```bash
sudo supervisorctl stop digital-kotor-queue:*
```

## Napomene

- **Queue tabela**: Ako koristite `database` driver, tabela `jobs` mora postojati (kreira se sa `php artisan queue:table && php artisan migrate`)
- **Logovi**: Svi logovi se čuvaju u `storage/logs/` direktorijumu
- **Timeout**: Job ima timeout od 5 minuta (300 sekundi) - dovoljno za obradu većine dokumenata
- **Retry**: Job će se pokušati 3 puta pre nego što se označi kao failed

## Troubleshooting

### Queue worker se ne pokreće
```bash
# Proverite da li postoji tabela jobs
php artisan tinker
>>> \DB::table('jobs')->count();
```

### Job se ne izvršava
```bash
# Proverite logove
tail -f storage/logs/laravel.log
tail -f storage/logs/queue.log

# Proverite da li postoji failed job
php artisan queue:failed
```

### Permission greške
```bash
# Osigurajte da storage direktorijum ima prava za pisanje
chmod -R 775 storage
chown -R www-data:www-data storage
```

