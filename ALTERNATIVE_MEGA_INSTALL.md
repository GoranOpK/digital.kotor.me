# Alternativni način instalacije MEGA paketa

## Problem

Plesk "Update" dugme samo ažurira postojeće pakete, ne instalira nove. Takođe, `tuyenlaptrinh/php-mega-nz` možda ne postoji ili nije dostupan.

## Rešenje 1: Koristi SSH terminal u Plesk-u

### Korak 1: Otvori SSH Terminal u Plesk-u

1. Idi na **Websites & Domains**
2. Pronađi opciju **"SSH Terminal"** ili **"Web SSH"**
3. Ako nema SSH pristupa, omogući ga u Plesk Settings

### Korak 2: Navigiraj do projekta

```bash
cd /var/www/vhosts/digital.kotor.me/httpdocs
# ILI putanja gde je tvoj Laravel projekat
```

### Korak 3: Pokreni composer require

```bash
composer require tuyenlaptrinh/php-mega-nz
```

Ako paket ne postoji, probaj alternativne:

```bash
composer require php-extended/php-api-nz-mega-object
```

Ili:

```bash
composer require cybercog/laravel-mega-nz
```

## Rešenje 2: Proveri da li paket postoji

Proveri na https://packagist.org/ da li paket stvarno postoji:
- Pretraži: `php mega` ili `mega.nz`
- Kopiraj tačan naziv paketa
- Dodaj ga u `composer.json`

## Rešenje 3: Rucna instalacija (ako nijedan paket ne radi)

Ako nijedan paket ne radi, možete implementirati `MegaStorageService` direktno sa HTTP zahtevima ka Mega.nz API-ju. Ovo je kompleksnije ali radi bez Composer paketa.

## Provera instalacije

Nakon `composer require`, proveri:

```bash
ls vendor/tuyenlaptrinh/
# ili
ls vendor/php-extended/
```

Ako folder postoji, paket je instaliran uspešno.

## Napomena o Plesk Composer interfejsu

Plesk PHP Composer interfejs možda nema opciju za direktno dodavanje novih paketa - samo za ažuriranje postojećih. U tom slučaju, koristite SSH terminal ili Composer CLI direktno.
