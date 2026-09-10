# JMB/JMBG enkripcija — implementacija i produkcijska evidencija

**Posljednje ažuriranje:** 2026-09-10
**Sloj:** platforma (DK REFERENCE / OPERATIONS). **Nije** BM/FS/TS paket. **Nema** novi Document ID.
**Izvor u kodu:** `config/jmb.php`, `App\Security\JmbEncryptionService`, `App\Security\JmbDualWrite`, `App\Models\SynchronizesJmbEncryption`, `app/Console/Commands/JmbEncryptedBackfillCommand.php`, migracija `2026_09_09_190000_add_jmb_encrypted_parallel_columns.php`
**Env ugovor:** [environment-variables.md](environment-variables.md#jmbjmbg-enkripcija-faza-b1--b2--c1)
**Komanda:** [deployment-and-cron.md](deployment-and-cron.md)

Ovaj dokument je kanonski zapis **šta je implementirano**, **šta je izvršeno na produkciji** i **šta još nije**. Služi rekonstrukciji bez chat istorije.

Dokument **ne** sadrži: stvarne JMB/JMBG vrijednosti, ciphertext, `JMB_ENCRYPTION_KEY`, `JMB_ENCRYPTION_PREVIOUS_KEYS` ni `APP_KEY`.

---

## Trenutno kanonsko stanje (2026-09-10)

| Dimenzija (DK-DS-001 §12) | Stanje |
|---------------------------|--------|
| Faza A — paralelne kolone | **PRODUCTION ACCEPTED** — migracija izvršena na produkciji |
| Faza B1 — encryption service | **PRODUCTION ACCEPTED** — kod deployovan |
| Faza B2 — backfill | **PRODUCTION ACCEPTED** — apply + verifikacija 2026-09-10 |
| Faza C1 — Eloquent dual-write | **IMPLEMENTATION COMPLETE** na `origin/main`; **NOT DEPLOYED** na produkciju |
| Faza D — encrypted-first read | **NOT STARTED** |
| Plaintext JMB/JMBG | **postoji** i ostaje autoritativan za read i uniqueness |
| Uklanjanje plaintexta | **zabranjeno** dok PO posebno ne odluči |

`origin/main` HEAD u trenutku ovog closeout-a: `3d71cbf77a62ae3ed5d673b4009c49b7580b983a` (`feat(security): add JMB dual-write synchronization`).

---

## 1. Arhitektura

Namjena: reverzibilna enkripcija JMB/JMBG vrijednosti **odvojena** od Laravel `APP_KEY`.

| Stavka | Kanonska činjenica |
|--------|-------------------|
| Biblioteka | `Illuminate\Encryption\Encrypter` |
| Cipher | AES-256-GCM |
| Ključ | posvećeni JMB ključ; **nije** `APP_KEY` / `APP_PREVIOUS_KEYS` |
| Envelope (jedna TEXT kolona, bez dodatnih DB polja) | `jmb:<key_id>:<laravel_payload>` |

`jmb` je fiksni scheme marker. `key_id` identifikuje koji JMB ključ je enkriptovao payload. `laravel_payload` je izlaz `Encrypter::encryptString()` (AES-256-GCM).

Env ugovor (`config/jmb.php`):

| Varijabla | Uloga |
|-----------|--------|
| `JMB_ENCRYPTION_KEY` | aktivni 32-bajtni ključ, format `base64:...`; jedini ključ za `encrypt()` |
| `JMB_ENCRYPTION_KEY_ID` | aktivni `key_id` u envelope-u (default `v1`) |
| `JMB_ENCRYPTION_PREVIOUS_KEYS` | decrypt-only keyring, JSON `{"<key_id>":"base64:..."}`; prazno dok nema rotacije |

Rotacija:

- nove enkripcije koriste **aktivni** ključ / `key_id`;
- prethodni ključevi su **samo decrypt**;
- `decrypt()` bira tačno `key_id` iz envelope-a (aktivni ili previous);
- **nema** fallback na drugi ključ ako envelope `key_id` ne odgovara ili dekripcija padne;
- B2 **ne** re-enkriptuje već validan ciphertext pri rotaciji.

`null` i prazan string se **ne** enkriptuju; encrypted kolona tada ostaje SQL `NULL`.

Boot aplikacije **ne** zahtijeva JMB ključ. Poziv `encrypt()` / `decrypt()` nad ne-praznom vrijednošću bez ispravnog ključa pada eksplicitno. C1 dual-write zahtijeva ključ kada se persistuje ne-prazan JMB/JMBG (**nakon** C1 deploya).

---

## 2. Paralelni kolonski model

Sedam mappinga. Plaintext kolona ostaje; encrypted je nullable `TEXT`, dodata Faza A.

| Plaintext | Encrypted |
|-----------|-----------|
| `users.jmb` | `users.jmb_encrypted` |
| `physical_person_identities.jmb` | `physical_person_identities.jmb_encrypted` |
| `legal_entity_authorized_persons.jmb` | `legal_entity_authorized_persons.jmb_encrypted` |
| `foreign_branch_representatives.jmb` | `foreign_branch_representatives.jmb_encrypted` |
| `applications.physical_person_jmbg` | `applications.physical_person_jmbg_encrypted` |
| `applications.applicant_jmbg` | `applications.applicant_jmbg_encrypted` |
| `business_plans.applicant_jmbg` | `business_plans.applicant_jmbg_encrypted` |

Encrypted kolone **nisu** unique i **nisu** indeks uniqueness-a. Read i equality (`users.jmb` UNIQUE, `CanonicalIdentifierUniqueness::jmbTaken()` i ostali plaintext query) ostaju na plaintextu.

---

## 3. Faza A — paralelne kolone

**Kod na main:** `66ffa35a205da55410201c8afcb4205aa80e10a9` — `feat(security): add parallel encrypted JMB columns`

| Stavka | Činjenica |
|--------|-----------|
| Šema | sedam nullable paralelnih `TEXT` kolona (tabela iznad) |
| Plaintext | **nedirnut** |
| Runtime | **nema** promjene read/write ponašanja u A |
| Produkcija | migracija izvršena uspješno |
| Rollback u A | uklanjanje **samo** novih encrypted kolona (`down()` migracije); plaintext ostaje |

---

## 4. Faza B1 — encryption service

**Kod na main (zajedno sa B2):** `06cf72982251955837e3d727df33681107c10d05` — `feat(security): add JMB encryption and controlled backfill`

| Stavka | Činjenica |
|--------|-----------|
| Servis | `App\Security\JmbEncryptionService` |
| Keyring | aktivni ključ + decrypt-only previous keys; rotacija kao u §1 |
| Integracija u B1 | **nema** DB backfill-a, **nema** read/write dual-write, **nema** encrypted-first read |
| Status | PO-usvojeno i deployovano na produkciju |

---

## 5. Faza B2 — `jmb:backfill-encrypted`

Komanda kopira postojeći plaintext u paralelne `*_encrypted` kolone. **Ne** mijenja plaintext. **Ne** uvodi dual-write ni encrypted-first read.

**Nije cron.** Produkcijski run je odvojena, kontrolisana, PO-odobrena akcija — nije dio običnog deploya.

```text
php artisan jmb:backfill-encrypted --dry-run
php artisan jmb:backfill-encrypted --dry-run --scope=users --chunk=100
php artisan jmb:backfill-encrypted --scope=applications.physical_person_jmbg
```

| Opcija | Ponašanje |
|--------|-----------|
| (bez opcija) | svih 7 mappinga, upis u encrypted kolone |
| `--dry-run` | čitanje + encrypt/decrypt validacija u memoriji; **nula** DB upisa |
| `--scope=` | jedan mapping; nepoznat scope = greška |
| `--chunk=` | redova po batch-u; default `100`, min `1`, max `500` |

Dozvoljeni `--scope`: `users`, `physical_person_identities`, `legal_entity_authorized_persons`, `foreign_branch_representatives`, `applications.physical_person_jmbg`, `applications.applicant_jmbg`, `business_plans`.

Algoritam po redu:

- `null` / prazan plaintext → `skipped_no_plaintext` (bez upisa);
- plaintext + NULL target → encrypt + in-memory round-trip verify → upis samo ako je tačan (`encrypted` / u dry-run `would_encrypt`);
- postojeći target koji se dekriptuje na isti plaintext → `already_valid` (bez rewrite);
- mismatch ili nečitljiv target → **fail closed**: stop na prvoj grešci, target se **ne** prepisuje, exit ≠ 0.

Ostalo:

- idempotentno: drugi apply ne mijenja already-valid ciphertext;
- nema jedne velike transakcije preko 7 tabela; svaki uspješan encrypted upis se commit-uje zasebno;
- izlaz su **samo agregati** (`scope`, `table`, `scanned`, `encrypted` / `would_encrypt`, `already_valid`, `skipped_no_plaintext`, `errors`); na grešci `table` + `id` + `reason`;
- izlaz **nikad** ne smije sadržati identifikator, ciphertext ili ključ.

---

## 6. Produkcijsko izvršenje B2 — 2026-09-10

Ovo je **stvarni produkcijski zapis**, ne plan.

Dry-run je završen uspješno za svih sedam mappinga sa **0** grešaka, prije apply-a.

### Apply (upis ciphertext-a)

| Mapping | scanned | encrypted | skipped_no_plaintext | errors |
|---------|--------:|----------:|---------------------:|-------:|
| `users` | 51 | 22 | 29 | 0 |
| `physical_person_identities` | 20 | 20 | 0 | 0 |
| `legal_entity_authorized_persons` | 0 | 0 | — | 0 |
| `foreign_branch_representatives` | 0 | 0 | — | 0 |
| `applications.physical_person_jmbg` | 15 | 5 | 10 | 0 |
| `applications.applicant_jmbg` | 15 | 11 | 4 | 0 |
| `business_plans` | 13 | 13 | 0 | 0 |
| **Ukupno novih encrypted vrijednosti** | | **71** | | **0** |

Plaintext kolone **nisu** mijenjane ovim runom.

---

## 7. Produkcijska verifikacija nakon backfill-a — 2026-09-10

Drugi (idempotentni) apply pokrenut je za svaki mapping koji je sadržao encrypted podatke.

| Mapping | encrypted (novo) | already_valid | errors |
|---------|-----------------:|--------------:|-------:|
| `users` | 0 | 22 | 0 |
| `physical_person_identities` | 0 | 20 | 0 |
| `applications.physical_person_jmbg` | 0 | 5 | 0 |
| `applications.applicant_jmbg` | 0 | 11 | 0 |
| `business_plans` | 0 | 13 | 0 |
| **Ukupno** | **0** | **71** | **0** |

`legal_entity_authorized_persons` i `foreign_branch_representatives` nijesu ušli u ovaj drugi run jer nijesu imali encrypted podatke (scanned 0 u apply-u).

Ovo potvrđuje produkcijski round-trip i B2 idempotentnost za svih **71** encrypted vrijednosti: 0 mismatch / decrypt grešaka, 0 novih upisa.

---

## 8. Faza C1 — application dual-write

**Kod na main:** `3d71cbf77a62ae3ed5d673b4009c49b7580b983a` — `feat(security): add JMB dual-write synchronization`

PO status: **usvojeno**. Git status: **commitovano i gurnuto** na `origin/main`.

**Produkcijski status u trenutku ovog closeout-a: C1 NIJE deployovan na produkciju.**

Ne tretirati C1 kao `PRODUCTION ACTIVE`. Produkcijski runtime i dalje **nema** application dual-write dok se ovaj commit ne deployuje.

Usvojeno ponašanje koda (važi na `main`; na produkciji tek nakon deploya):

- dual-write na Eloquent `saving` preko `SynchronizesJmbEncryption` → `JmbDualWrite::syncModel()`;
- svih šest vlasničkih modela: `User`, `PhysicalPersonIdentity`, `LegalEntityAuthorizedPerson`, `ForeignBranchRepresentative`, `Application`, `BusinessPlan`;
- promijenjen JMB/JMBG → nova encrypted kopija (`JmbEncryptionService::encrypt`);
- nepromijenjen JMB/JMBG (dirty samo druga polja) → ciphertext se **ne** regeneriše;
- namjerno brisanje / prazan string → encrypted kolona `NULL`;
- neuspjeh enkripcije **sprečava** persist novog/izmijenjenog plaintext JMB-a (isti `save()`, ista transakcija);
- read ostaje plaintext;
- uniqueness/equality ostaje plaintext;
- ručni / direct SQL (phpMyAdmin, raw query) **zaobilazi** C1 i operativno je **zabranjen** za izmjene JMB/JMBG kolona.

Encrypted-first read je Faza D, **nije** C1. C1 **ne** uklanja plaintext.

---

## 9. Trenutni security / rollback status

Važeće na produkciji **sada** (C1 još nije deployovan):

- encrypted kopije postoje za **71** istorijsku ne-praznu vrijednost koju je obradio B2;
- plaintext kolone i dalje postoje;
- aplikacioni read i dalje zavisi od plaintexta;
- uniqueness i equality i dalje rade nad plaintextom;
- zato plaintext **ne smije** biti uklonjen;
- trenutni rollback i dalje počiva na **sačuvanom plaintextu** plus **čuvanju JMB encryption ključeva** (gubitak ključa čini 71 ciphertext nečitljivim, ali read i dalje ide preko plaintexta);
- Faza D / encrypted-first read **nije** pokrenuta;
- povlačenje plaintext kolona zahtijeva **posebnu kasniju PO odluku**, nije implikacija B2 ni C1.

Nakon eventualnog C1 deploya, rollback i dalje **ne** smije uklanjati plaintext dok Faza D i PO odluka to eksplicitno ne zatvore.

---

## 10. Šta ovaj closeout ne radi

- ne pokreće Fazu D;
- ne uklanja plaintext;
- ne mijenja produkcijski `.env`;
- ne nalaže ponovni B2 run;
- ne nalaže C1 deploy (to je odvojena operacija).

Ponovni produkcijski `jmb:backfill-encrypted` apply nije potreban za već validnih 71 vrijednost. Novi run samo uz PO kontrolu (npr. nakon C1 deploya, za redove koje dual-write nije pokrio jer su upisani van Eloquent-a).
