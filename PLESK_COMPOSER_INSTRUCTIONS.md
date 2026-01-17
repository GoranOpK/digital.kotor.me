# Instrukcije za dodavanje paketa preko Plesk PHP Composer

## Kako dodati `php-mega-nz` paket

### Korak 1: Otvori composer.json
1. U Plesk interfejsu, klikni na dugme **"Edit composer.json"**
2. Otvoriće se editor sa sadržajem `composer.json` fajla

### Korak 2: Dodaj paket u "require" sekciju
Pronađi sekciju `"require"` (oko linije 8) i dodaj novi paket:

```json
"require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "laravel/tinker": "^2.10.1",
    "tuyenlaptrinh/php-mega-nz": "^1.0"
},
```

**VAŽNO:** 
- Ne zaboravi zarez (`,`) posle poslednjeg reda pre dodavanja novog
- Ne zaboravi zarez posle novog reda ako nije poslednji
- Sazdržavaj se unutar `{}` zagrada

### Korak 3: Sačuvaj izmene
1. Klikni **"Save"** ili **"OK"** da sačuvaš izmene
2. Plesk će validirati JSON format

### Korak 4: Instaliraj paket
1. Vrati se na glavnu stranu PHP Composer-a
2. Klikni na dugme **"Install"** ili **"Update"**
3. Plesk će automatski pokrenuti `composer install` ili `composer update`
4. Sačekaj da se instalacija završi

## Alternativni paketi (ako `tuyenlaptrinh/php-mega-nz` ne radi)

Ako paket `tuyenlaptrinh/php-mega-nz` nije dostupan ili ne radi kako treba, možete probati:

### Opcija 1: php-extended/php-api-nz-mega-object
```json
"php-extended/php-api-nz-mega-object": "^1.0"
```

### Opcija 2: Rucna implementacija
Ako nijedan paket ne radi, možete implementirati `MegaStorageService` direktno sa HTTP zahtevima.

## Provera instalacije

Nakon instalacije, proveri:
1. Da li je paket instaliran - trebalo bi da se pojavi u listi "Package Dependencies"
2. Proveri logove: `storage/logs/laravel.log`
3. Testiraj upload dokumenta

## Napomena o verzijama

- `^1.0` znači da će Composer instalirati najnoviju verziju 1.x.x
- Ako paket nije kompatibilan sa PHP 8.2/8.3, možda će biti potrebna druga verzija
- Ako ima problema, probaj bez verzije ili sa `*` za najnoviju

## Struktura composer.json nakon izmene

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10.1",
        "tuyenlaptrinh/php-mega-nz": "^1.0"
    }
}
```

## Troubleshooting

### Problem: "Package not found"
**Rešenje:** Proveri tačan naziv paketa na packagist.org. Možda paket nije dostupan ili ima drugačiji naziv.

### Problem: "Compatibility issue"
**Rešenje:** Proveri da li paket podržava PHP 8.2/8.3 i Laravel 12. Možda treba koristiti drugačiju verziju ili alternativni paket.

### Problem: "JSON syntax error"
**Rešenje:** Proveri zareze i zagrade. JSON mora biti validan format.
