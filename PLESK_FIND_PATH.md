# Kako pronaći putanju do projekta u Plesk-u

## Metoda 1: Kroz Artisan komandu (najbrže)

U **Laravel Toolkit** → **Artisan** tab, pokrenite:

```
tinker
```

Zatim u tinker promptu:
```php
base_path()
```

Ili direktno u Artisan komandi:
```
tinker --execute="echo base_path();"
```

## Metoda 2: Kroz File Manager

1. Idite u **Websites & Domains** → **digital.kotor.me** → **File Manager**
2. Pronađite `artisan` fajl
3. Desni klik na `artisan` → **Properties** ili **Info**
4. Kopirajte **Full Path** ili **Absolute Path**

## Metoda 3: Standardna Plesk putanja za poddomene

Pošto je `digital.kotor.me` poddomen od `kotor.me`, putanja je verovatno:

```
/var/www/vhosts/kotor.me/subdomains/digital.kotor.me
```

Ili:

```
/var/www/vhosts/kotor.me/digital.kotor.me
```

Ili:

```
/var/www/vhosts/kotor.me/httpdocs/digital.kotor.me
```

## Metoda 4: Kroz Artisan komandu - provera putanje

U **Laravel Toolkit** → **Artisan**, pokrenite:

```
config:show app.name
```

Ili:

```
route:list
```

Ako ove komande rade, znači da ste u tačnom direktorijumu. Putanja je ono što vidite u URL-u ili možete proveriti kroz File Manager.

## Metoda 5: Kroz Scheduled Tasks - test komanda

U **Plesk Scheduled Tasks**, dodajte test task:

**Command:**
```bash
ls -la /var/www/vhosts/kotor.me/subdomains/digital.kotor.me/artisan
```

**Run:** `Once`

Ako vidi `artisan` fajl, putanja je tačna. Ako ne, probajte:

```bash
ls -la /var/www/vhosts/kotor.me/digital.kotor.me/artisan
```

Ili:

```bash
ls -la /var/www/vhosts/kotor.me/httpdocs/digital.kotor.me/artisan
```

## Najbolje rešenje

1. Otvorite **File Manager** u Plesk-u
2. Idite do `artisan` fajla
3. Desni klik → **Properties** → kopirajte **Full Path**
4. Koristite tu putanju u Scheduled Tasks

