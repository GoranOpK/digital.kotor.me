# Alternativni načini pronalaženja putanje

## Metoda 1: Kroz Artisan komandu (base_path)

U **Laravel Toolkit** → **Artisan** tab, pokrenite:

```
tinker
```

Zatim u tinker promptu unesite:
```php
base_path()
```

Ili direktno:
```
tinker --execute="echo base_path();"
```

Ovo će vam pokazati tačnu putanju do projekta.

## Metoda 2: Kroz Artisan komandu (config path)

U **Laravel Toolkit** → **Artisan**, pokrenite:

```
config:cache
```

Ili:

```
route:list
```

Ako ove komande rade, znači da ste u tačnom direktorijumu. Putanja je ono što vidite u URL-u ili možete proveriti kroz File Manager.

## Metoda 3: Kroz File Manager - Properties

U File Manager-u:
1. Kliknite na `artisan` fajl (ne desni klik, već običan klik da ga selektujete)
2. Gledajte u URL pregledača - možda će biti vidljiva putanja
3. Ili kliknite na "Properties" ili "Info" dugme (ako postoji u toolbar-u)

## Metoda 4: Kroz Log Browser

U File Manager-u, desni klik na `artisan` → **Open in Log Browser**

U Log Browser-u, možda će biti vidljiva putanja u naslovu ili URL-u.

## Metoda 5: Test različitih standardnih Plesk putanja

Dodajte test task u Scheduled Tasks i probajte svaku:

### Test 1:
```bash
test -f /home/opstinakotor/digital.kotor.me/artisan && echo "TAČNA: /home/opstinakotor/digital.kotor.me" || echo "Nije ova"
```

### Test 2:
```bash
test -f /home/opstin/digital.kotor.me/artisan && echo "TAČNA: /home/opstin/digital.kotor.me" || echo "Nije ova"
```

### Test 3:
```bash
test -f ~/digital.kotor.me/artisan && echo "TAČNA: ~/digital.kotor.me" || echo "Nije ova"
```

### Test 4:
```bash
find /home -name "artisan" -path "*/digital.kotor.me/artisan" 2>/dev/null | head -1
```

## Metoda 6: Kroz Artisan komandu - provera trenutnog direktorijuma

U **Laravel Toolkit** → **Artisan**, pokrenite:

```
about
```

Ili:

```
list
```

Ako ove komande rade, znači da Laravel zna gde se nalazi. Možete proveriti kroz `base_path()` u tinker-u.

## Metoda 7: Kroz Scheduled Tasks - koji php

Dodajte test task:

```bash
which php && php -v
```

Ovo će pokazati gde se nalazi PHP i možete koristiti tu putanju.

## Najbolje rešenje

**Probajte Metodu 1** - `tinker --execute="echo base_path();"` u Artisan tab-u. To će vam pokazati tačnu putanju!

