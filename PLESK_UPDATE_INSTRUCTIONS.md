# Rešavanje problema sa Composer lock file-om

## Problem
Greška kaže da `composer.lock` fajl nije ažuran sa izmenama u `composer.json`.

## Rešenje: Koristi "Update" umesto "Install"

### U Plesk PHP Composer interfejsu:

1. **Klikni na dugme "Update"** (umesto "Install")
   - "Update" će ažurirati `composer.lock` i instalirati paket
   - "Install" samo instalira pakete koji su već u `composer.lock`

2. **Sačekaj da se završi update proces**
   - Može potrajati nekoliko minuta
   - Plesk će pokrenuti `composer update tuyenlaptrinh/php-mega-nz`

3. **Proveri rezultat**
   - Paket će se pojaviti u listi "Package Dependencies"
   - Status će biti "Installed"

## Zašto se ovo dešava?

- `composer.json` = deklaracija paketa (šta treba)
- `composer.lock` = tačne verzije instaliranih paketa (šta je instalirano)

Kada ručno edituješ `composer.json`, `composer.lock` ne zna za nove izmene dok ne pokreneš `composer update`.

## Alternativno rešenje (ako "Update" ne radi)

Ako "Update" dugme ne radi ili ima problema:

### Preko SSH terminala u Plesk-u:

1. Idi na **Websites & Domains > SSH Terminal** (ili Web SSH)
2. Pokreni komandu:
```bash
cd /var/www/vhosts/digital.kotor.me/httpdocs  # ili putanja do tvog projekta
composer update tuyenlaptrinh/php-mega-nz
```

Ili da ažuriraš sve:
```bash
composer update
```

## Provera uspešne instalacije

Nakon update-a, proveri:

1. U Plesk interfejsu - paket bi trebalo da se pojavi u listi
2. Proveri da li fajl postoji: `vendor/tuyenlaptrinh/php-mega-nz/`
3. Testiraj import u PHP kodu

## Ako i dalje ima problema

Mogući uzroci:
- Paket ne postoji pod tim nazivom
- Kompatibilnost sa PHP verzijom
- Problemi sa internet konekcijom Composer-a

U tom slučaju, probaj alternativni paket ili rucnu implementaciju.
