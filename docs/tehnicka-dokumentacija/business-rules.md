# Poslovna pravila

**Poslednje ažuriranje:** 2026-08-20 (kanonski V1 korisnički model: 8 kategorija; Preduzetnik = fizičko lice)
**Izvor u kodu:** `KotorAddress`, `HomeController`, `ProfileUpdateRequest`, `ApplicationController`, `Application` model, `App\Support\UserType`

---

## Adresa — Opština Kotor

**Klasa:** `app\Support\KotorAddress`  
**Pravilo:** `app\Rules\KotorMunicipalityAddress`

### Važeće

- **Ulica i broj** u posebnom polju; **grad** u posebnom polju (`city` na `users` i u profilu/prijavi).
- Ulica mora imati min. 2 znaka; ne smije biti samo naziv naselja.
- Dozvoljeno `bb` / `b.b.` / „bez broja“.
- Grad ili ulica+grad moraju ukazivati na naselje Opštine Kotor ili poštanski broj **85310** / **85330**.

### Naselja (iz koda)

Kotor, Dobrota, Prčanj, Škaljari, Risan, Perast, Muo, Orahovac, Stoliv, Ljuta, Mirač, Kostanjica, Lastva, Mrčajevići, Puce, Grbalj (normalizacija bez dijakritika u validaciji).

### Gdje se provjerava

| Kontekst | Metoda / mjesto |
|----------|-----------------|
| Registracija | `HomeController::register` — rezidenti i pravna lica (ne preduzetnik bez adrese) |
| Profil | `ProfileUpdateRequest` |
| Prijava — submit | `ApplicationController::kotorAddressErrorForApplication` |
| Obrazac 1a/1b (finalno) | Adresa iz profila (`profileAddressErrorForUser`) |

---

## JMB, PIB, pasoš

| Identifikator | Pravilo | Ko |
|---------------|---------|-----|
| **JMB** | 13 cifara + kontrolna cifra (`HomeController::validateJMB`) | Rezidenti (Fizičko lice i Preduzetnik) |
| **PIB** | Tačno **8** cifara (`App\Support\Pib`), unique u `users` | Pravna lica. Preduzetnik: **nije** automatski obavezan |
| **Pasoš** | Alternativa za nerezidente | `residential_status=non-resident` (samo Fizičko lice / Preduzetnik) |

Kanonski `users.residential_status` za Fizičko lice i Preduzetnika: samo `resident` / `non-resident`. Pravno lice: `NULL` na novom zapisu. `ex-non-resident` je legacy vrijednost uklonjena iz aktivnog modela (nije treći status).

Kolone u bazi: `users.pib` VARCHAR(8); identifikaciono polje je `users.jmb` (nije `jmbg`).

---

## Registracija — tipovi korisnika

`users.user_type` je osnovni identitet/oblik korisnika (8 kanonskih vrijednosti). `business_type` je **polje forme** pri registraciji, nije kolona u `users`.

Preduzetnik je fizičko lice sa registrovanom djelatnošću, nije pravno lice.

Svojstva potrebna za konkretan konkurs (npr. poljoprivrednik, MSP veličina, individualni sportista) nisu `user_type`; ostaju u konkursnom/eligibility sloju.

Detalji: [authentication-and-registration.md](authentication-and-registration.md).

---

## Prijava — obavezni dokumenti

Logika: `Application::getRequiredDocuments()` i `getRequiredDocumentsForType()` — zavisi od:

- `applicant_type` (preduzetnica, doo, ostalo, fizicko_lice)
- `business_stage` (započinjanje / razvoj)
- `is_registered` (da li ima registrovanu djelatnost)

Primjer ključeva tipova: `licna_karta`, `crps_resenje`, `pib_resenje`, `predracuni_nabavka`, `potvrda_zavod_nezaposleni`, …

**Potvrda Zavoda za nezaposlene:** opciona u upload validaciji; može biti u listi obaveznih po tipu prijave.

**Važna napomena iz koda:** za DOO/ostalo u fazi razvoj/započinjanje **ne traži se** „Dokaz o broju poslovnog žiro računa društva“ (uklonjeno iz liste).

---

## Bodovanje i dodatni bodovi

| Bod | Uslov | Vrijednost |
|-----|-------|------------|
| Info dan | `bonus_info_day` | +1 |
| Novi biznis | `bonus_new_business` | +2 |
| Zavod nezaposleni | `bonus_zavod_nezaposleni` | +2 |
| Zeleno / inovativno | `bonus_green_innovative` | +3 |

**Minimum za prolaz:** 30 bodova (`meetsMinimumScore()`).

Odbijanje zbog nedostatka dokumenata: prikaz ocjene **0** (`getDisplayScore()`).

---

## Konkurs

### Zvaničan izvor pravila

**Odluka o konkursu** i pravilnici — zvaničan izvor je **katalog propisa** ili **Službeni list** Opštine Kotor (ne repozitorijum). U aplikaciji i korisničkom uputstvu sve je rađeno po toj Odluci; **nema posebnih pravila mimo Odluke** osim tehničkih ograničenja forme (validacija adrese, upload, rokovi u sistemu).

**Korisničko uputstvo (PDF):** `public/pdf/uputstvo-zensko-preduzetnistvo.pdf` — potvrđena putanja na produkciji; ruta `competitions.guide.pdf`.

### Tipovi i prioritet (važeće — odluka tima)

Planirano je **nekoliko vrsta konkursa** na istom modulu (`Competition.type`). Redoslijed razvoja cjelina:

| Redoslijed | Cjelina | Status |
|------------|---------|--------|
| — | Kalendar kulture | **Završeno** (produkcija) |
| — | Konkurs — žensko preduzetništvo (`zensko`) | **Završeno** (produkcija) |
| 1 | e-Plaćanje | **Trenutni razvojni prioritet** |
| — | Konkurs — mladi u preduzetništvu (`omladinsko`) | **Odložen za sada** — baza/UI pripremljeni djelimično; razvoj se ne pokreće |
| — | Tenderi | Stub / future module |

V. [project-operations.md](project-operations.md#prioritet-budućih-cjelina), [stubs-and-future-modules.md](stubs-and-future-modules.md).

### Pravila u sistemu

- Tip u produkciji (javna lista): `zensko` — filter na `competitions.index`
- Statusi: `draft`, `published`, `closed`, `completed`
- Rok prijave: `deadline_days` (default 20) od `start_date` ili `published_at`
- Jedna prijava po korisniku po konkursu

---

## Biblioteka dokumenata

- Kvota: **20 MB** po korisniku (`DocumentProcessor::MAX_STORAGE_PER_USER` / `DOCUMENT_LIBRARY_USER_QUOTA_BYTES`)
- Formati: PDF, JPG, PNG
- Opcioni `expires_at` — mora biti u budućnosti pri uploadu
- U **jednom** upload zahtjevu: zabranjeni binarni duplikati (SHA-256) i, za slike, pixel-identični duplikati nakon Imagick normalizacije — v. [document-library-and-mega.md](document-library-and-mega.md#zaštita-od-duplikata-u-istom-uploadu)

---

## Član komisije

Ne može podnijeti prijavu na konkurs za koji je aktivan član komisije (`CommissionMember::activeForCommission`).

---

## Povezani dokumenti

- [application-lifecycle.md](application-lifecycle.md)
- [UPUTSTVO_ZENSKO_PREDUZETNISTVO.md](../UPUTSTVO_ZENSKO_PREDUZETNISTVO.md)
