# MEGA Debug - Sledeći koraci

## Promene u kodu:

1. ✅ **Zamenjen Laravel Http sa direktnim cURL-om** - bolja kontrola zahtjeva
2. ✅ **Dodato detaljnije logovanje** - videćeš tačno šta se dešava

## Šta uraditi:

### 1. Podigni novu verziju koda na server:

```bash
git pull origin main
# ili kroz Plesk - pull iz GitHub-a
```

### 2. Proveri da li su promene primenjene:

Proveri da li `MegaStorageService.php` ima `curl_init` umesto `Http::` facade.

### 3. Testiraj ponovo upload:

Upload-uj mali test fajl i proveri logove.

### 4. Proveri logove za:

Traži sledeće poruke u `storage/logs/laravel.log`:

- **`MEGA login attempt`** - da li se poziva login?
- **`MEGA user hash generated`** - da li se hash generiše?
- **`MEGA API sending request`** - tačan URL i payload koji se šalje
- **`MEGA API HTTP error`** - ako ima HTTP grešku, videćeš status i body
- **`MEGA login response`** - šta MEGA vraća
- **`MEGA API error in response`** - greška u JSON odgovoru

## Mogući problemi:

### Problem 1: Stara verzija koda
**Simptom:** Ne vidiš nove debug logove  
**Rešenje:** Proveri da li je nova verzija podignuta na server

### Problem 2: MEGA login format
**Simptom:** HTTP 402 ili prazan odgovor  
**Rešenje:** Proveri logove za tačan format koji se šalje

### Problem 3: MEGA blokira zahtev
**Simptom:** HTTP 402 (Payment Required)  
**Rešenje:** Možda MEGA blokira zahtev zbog rate limiting-a ili IP-a

### Problem 4: Enkripcija nije tačna
**Simptom:** MEGA vraća grešku -9 (bad login)  
**Rešenje:** Proveri `prepare_key` i `stringhash` funkcije

## Ako i dalje ne radi:

1. **Pošalji logove** sa sledećim porukama:
   - `MEGA API sending request` - videćemo šta se šalje
   - `MEGA API HTTP error` - videćemo šta MEGA vraća
   - `MEGA login response` - videćemo odgovor

2. **Proveri .env** - da li su MEGA_EMAIL i MEGA_PASSWORD tačni

3. **Testiraj direktno** - probaj da pozoveš MEGA API direktno (preko Postman ili curl) da vidiš šta vraća

## Debug komanda:

Ako imaš SSH pristup, možeš probati direktno:

```bash
curl -X POST "https://g.api.mega.co.nz/cs?id=123" \
  -H "Content-Type: application/json" \
  -d '[{"a":"us","user":"your_email@example.com","uh":"test_hash"}]'
```

Ovo će vratiti tačan odgovor MEGA API-ja.
