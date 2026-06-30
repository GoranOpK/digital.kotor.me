# Pronalaženje putanje iz Log Browser-a

## Iz Log Browser-a vidim:

Breadcrumb: `Websites & Domains > kotor.me > Files >`
Naslov: `Log Browser file kotor.me / digital.kotor.me / artisan`

To znači da je putanja verovatno:
```
/var/www/vhosts/kotor.me/digital.kotor.me/artisan
```

Ili:
```
/var/www/vhosts/kotor.me/subdomains/digital.kotor.me/artisan
```

## Kako pronaći tačnu putanju:

### Metoda 1: Kroz File Manager Properties

1. U Plesk-u, idite na **Websites & Domains** → **kotor.me** → **Files** (File Manager)
2. Pronađite folder `digital.kotor.me`
3. Unutar tog foldera, pronađite `artisan` fajl
4. **Desni klik** na `artisan` fajl → **Properties** ili **Info**
5. Kopirajte **Full Path** ili **Absolute Path**

### Metoda 2: Test komanda u Scheduled Tasks

Dodajte test task u Scheduled Tasks:

**Command:**
```bash
test -f /var/www/vhosts/kotor.me/digital.kotor.me/artisan && echo "Putanja je tačna: /var/www/vhosts/kotor.me/digital.kotor.me" || echo "Putanja nije tačna"
```

**Run:** `Once`

Ako vidi "Putanja je tačna", koristite:
```bash
php /var/www/vhosts/kotor.me/digital.kotor.me/artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

Ako ne, probajte:
```bash
test -f /var/www/vhosts/kotor.me/subdomains/digital.kotor.me/artisan && echo "Putanja je tačna: /var/www/vhosts/kotor.me/subdomains/digital.kotor.me" || echo "Putanja nije tačna"
```

### Metoda 3: Direktno iz File Manager-a

1. U File Manager-u, idite do `artisan` fajla
2. Kliknite na `artisan` fajl (ne desni klik, već običan klik)
3. U URL-u pregledača će biti putanja
4. Ili u Properties/Info prozorčiću će biti Full Path

## Finalna komanda za Scheduled Tasks

Kada pronađete tačnu putanju, koristite:

**Command:**
```bash
php /tačna/putanja/artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

**Primer** (ako je putanja `/var/www/vhosts/kotor.me/digital.kotor.me`):
```bash
php /var/www/vhosts/kotor.me/digital.kotor.me/artisan queue:work --tries=3 --timeout=300 --stop-when-empty
```

**Run:** `Every minute`

---

**Najbrže:** U File Manager-u, desni klik na `artisan` → Properties → kopirajte Full Path!

