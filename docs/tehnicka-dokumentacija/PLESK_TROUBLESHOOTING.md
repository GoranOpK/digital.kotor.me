# Plesk Scheduled Tasks - Troubleshooting

## Problem: "No such file or directory"

Ako dobijate grešku da direktorijum ne postoji, to znači da putanja nije tačna.

## Kako pronaći tačnu putanju

### Metoda 1: Kroz Laravel Toolkit

1. Otvorite **Laravel Toolkit** u Plesk-u
2. Proverite **Application Root Path** - ovo je tačna putanja do vašeg projekta
3. Kopirajte tu putanju

### Metoda 2: Kroz File Manager

1. Otvorite **File Manager** u Plesk-u
2. Idite do `artisan` fajla (trebalo bi da bude u root direktorijumu projekta)
3. Desni klik na `artisan` → **Properties** ili **Info**
4. Kopirajte **Full Path** - ovo je tačna putanja

### Metoda 3: Kroz SSH (ako imate pristup)

```bash
pwd
# Ili
realpath artisan
```

## Ispravna komanda za Plesk Scheduled Tasks

Plesk Scheduled Tasks možda ne podržava `&&` operator. Koristite jednu od sledećih opcija:

### Opcija 1: Sa cd u komandi (ako radi)

**Command:**
```bash
cd /tačna/putanja/do/projekta && php artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

### Opcija 2: Bez cd (preporučeno)

**Command:**
```bash
php /tačna/putanja/do/projekta/artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

**Napomena:** Zamenite `/tačna/putanja/do/projekta` sa stvarnom putanjom (bez `artisan` na kraju u Opciji 1, sa `artisan` u Opciji 2).

### Opcija 3: Kroz bash skriptu

Ako ni jedna od gornjih opcija ne radi, kreirajte skriptu:

1. U **File Manager**, kreirajte fajl `queue-worker.sh` u root direktorijumu projekta:
```bash
#!/bin/bash
cd /tačna/putanja/do/projekta
php artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

2. Dajte execute prava:
```bash
chmod +x queue-worker.sh
```

3. U Scheduled Tasks, koristite:
```bash
/bin/bash /tačna/putanja/do/projekta/queue-worker.sh
```

## Provera putanje

Nakon što pronađete putanju, proverite da li postoji:

U **Laravel Toolkit** → **Artisan Commands**, pokrenite:
```
ls -la /putanja/koju/ste/pronašli
```

Ili proverite da li postoji `artisan` fajl:
```
test -f /putanja/do/projekta/artisan && echo "Putanja je tačna" || echo "Putanja nije tačna"
```

## Česti problemi

### Problem 1: Putanja ima razmake
Ako putanja ima razmake, koristite navodnike:
```bash
cd "/putanja/sa razmacima/digital.kotor.me" && php artisan queue:work --stop-when-empty
```

### Problem 2: Plesk ne podržava &&
Pokušajte bez `cd`:
```bash
php /putanja/do/projekta/artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

### Problem 3: PHP nije u PATH-u
Koristite punu putanju do PHP-a:
```bash
/usr/bin/php /putanja/do/projekta/artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

## Provera da li komanda radi

Nakon podešavanja, testirajte komandu:

U **Laravel Toolkit** → **Artisan Commands**, pokrenite:
```
php artisan queue:work --once
```

Ako ova komanda radi, znači da je putanja tačna i da možete koristiti istu putanju u Scheduled Tasks.

## Alternativno: Koristite relativnu putanju

Ako ništa ne radi, možete pokušati sa relativnom putanjom od home direktorijuma:

1. Proverite home direktorijum u Plesk-u
2. Koristite relativnu putanju:
```bash
cd ~/digital.kotor.me && php artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

---

**Najbolje rešenje:** Pronađite tačnu putanju kroz Laravel Toolkit → Application Root Path i koristite **Opciju 2** (bez cd).

