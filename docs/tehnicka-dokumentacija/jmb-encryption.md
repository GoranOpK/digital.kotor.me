# JMB/JMBG enkripcija — implementacija i produkcijska evidencija

**Posljednje ažuriranje:** 2026-09-11
**Sloj:** platforma (DK REFERENCE / OPERATIONS). **Nije** BM/FS/TS paket. **Nema** novi Document ID.
**Izvor u kodu:** `config/jmb.php`, `App\Security\JmbEncryptionService`, `App\Security\JmbDualWrite`, `App\Security\JmbEncryptedReadService`, `App\Security\JmbLookupService`, `App\Models\SynchronizesJmbEncryption`, `jmb:backfill-encrypted`, `jmb:backfill-lookup`, `jmb:precheck-plaintext-retirement`, `jmb:retire-plaintext`
**Env ugovor:** [environment-variables.md](environment-variables.md#jmbjmbg-enkripcija)
**Komanda:** [deployment-and-cron.md](deployment-and-cron.md)

Ovaj dokument je kanonski zapis **šta je implementirano**, **šta je izvršeno na produkciji** i **šta još nije**. Služi rekonstrukciji bez chat istorije.

Dokument **ne** sadrži: stvarne JMB/JMBG vrijednosti, ciphertext, `JMB_ENCRYPTION_KEY`, `JMB_ENCRYPTION_PREVIOUS_KEYS`, `JMB_LOOKUP_KEY`, `APP_KEY`, niti identitet test-korisnika.

Kanonski redoslijed faza: **A → B1 → B2 → C → D**, zatim lookup dual-write, uniqueness cutover i kontrolisani plaintext retirement. Faza C2 **ne postoji**. Ranija dokumentacija je dual-write fazu privremeno označavala kao „C1“; kanonski naziv je **Faza C**. Implementacija (`JmbDualWrite`, `SynchronizesJmbEncryption`) i git istorija se **ne** preimenuje. Commit `3d71cbf77a62ae3ed5d673b4009c49b7580b983a` ostaje neizmijenjen.

**Fizički DROP plaintext kolona nije dio ovog closeout-a.** Security cilj je ostvaren NULL-ovanjem plaintext vrijednosti, encrypted-only runtime-om i zabranom budućeg plaintext persist-a dok je retirement mode uključen. Fizičko uklanjanje kolona/indeksa, ako ikad, je odvojena buduća PO odluka.

---

## Trenutno kanonsko stanje (2026-09-11)

| Dimenzija (DK-DS-001 §12) | Stanje |
|---------------------------|--------|
| Faza A — paralelne kolone | **PRODUCTION ACCEPTED** |
| Faza B1 — encryption service | **PRODUCTION ACCEPTED** |
| Faza B2 — backfill | **PRODUCTION ACCEPTED** — apply + verifikacija 2026-09-10 |
| Faza C — Eloquent dual-write | **IMPLEMENTATION COMPLETE**; **DEPLOYED TO PRODUCTION**; **PRODUCTION VERIFIED** za kontrolisanu `users.jmb` aplikacionu putanju |
| Faza D — encrypted-first VALUE read | **IMPLEMENTATION COMPLETE**; **DEPLOYED TO PRODUCTION**; **PRODUCTION VERIFIED**; **PRODUCTION ACCEPTED** |
| Originalni plan 1/6 — lookup dual-write | **CLOSED** |
| Originalni plan 2/6 — uniqueness/equality cutover | **CLOSED** |
| Originalni plan 3/6 — produkcijska verifikacija cutover-a | **CLOSED** |
| Originalni plan 4/6 — plaintext runtime VALUE-read cutover | **CLOSED** |
| Originalni plan 5/6 — plaintext retirement readiness / write-contract | **CLOSED** |
| Originalni plan 6/6 — kontrolisani produkcijski plaintext retirement | **CLOSED** / **PRODUCTION ACCEPTED** |
| Originalni plan ukupno | **6/6 CLOSED** / **PRODUCTION ACCEPTED** |
| Plaintext JMB/JMBG vrijednosti | **retired na NULL** (72 reda, 7 kolona, `post_plaintext_non_null=0`) |
| Encrypted vrijednosti | **autoritativne** za aplikacioni VALUE read |
| Lookup | `users.jmb_lookup` / `physical_person_identities.jmb_lookup` za equality/uniqueness |
| `users.jmb` UNIQUE | **ostaje** kao schema constraint |
| `JMB_PLAINTEXT_RETIREMENT_ENABLED` | **`true`** — obavezno produkcijsko operativno stanje |
| Fizički DROP plaintext kolona | **izvan ovog closeout-a** — kolone nijesu dropovane |

Kanonski kod: `HEAD` = `origin/main` = `027067c42dd1d5678d687310076931cd8d3bfe1f` (`feat(security): add controlled JMB plaintext retirement`).

Implementacioni commit Faze C: `3d71cbf77a62ae3ed5d673b4009c49b7580b983a` (`feat(security): add JMB dual-write synchronization`). SHA i poruka su imutabilni.

Implementacioni commit Faze D: `077a01cd5c5af85f327ccdd4701b9bb089051523` (`feat(security): add encrypted-first JMB reads`). SHA i poruka su imutabilni.

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

Boot aplikacije **ne** zahtijeva JMB ključ. Poziv `encrypt()` / `decrypt()` nad ne-praznom vrijednošću bez ispravnog ključa pada eksplicitno. Faza C dual-write zahtijeva ključ kada se persistuje ne-prazan JMB/JMBG.

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

Encrypted kolone **nisu** unique i **nisu** indeks uniqueness-a.

**Tokom Faze D (2026-09-10):** SQL equality i uniqueness (`users.jmb` UNIQUE, `CanonicalIdentifierUniqueness::jmbTaken()`, `Rule::unique` na `users.jmb`) ostajali su na plaintextu. Aplikacioni VALUE read je već bio encrypted-first (§9).

**Važeće (2026-09-11):** runtime equality/uniqueness za registraciju, profil i dopunu subjekta ide preko `jmb_lookup` (§12–§13). Aplikacioni VALUE read ide encrypted-first, bez plaintext fallback-a u retirement mode-u (§14–§17). `users.jmb` UNIQUE ostaje. Plaintext kolone **postoje** u šemi, ali aktivne plaintext vrijednosti su NULL.

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

## 8. Faza C — application dual-write

**Kod na main:** `3d71cbf77a62ae3ed5d673b4009c49b7580b983a` — `feat(security): add JMB dual-write synchronization` (imutabilno; poruka commita se ne mijenja).

PO status: **usvojeno**. Git: commitovano i gurnuto na `origin/main`.

**Produkcijski status:** Faza C je **deployovana na produkciju**. Kontrolisana verifikacija `users.jmb` aplikacione putanje = **PASS** (2026-09-10, §8.1). To **nije** dokaz da je svaka Faza C write putanja ručno testirana na produkciji; ostale putanje pokriva automatska regresija.

Usvojeno ponašanje koda (neizmijenjeno; ranije dokumentovano pod privremenom oznakom „C1“):

- dual-write na Eloquent `saving` preko `SynchronizesJmbEncryption` → `JmbDualWrite::syncModel()`;
- svih šest vlasničkih modela: `User`, `PhysicalPersonIdentity`, `LegalEntityAuthorizedPerson`, `ForeignBranchRepresentative`, `Application`, `BusinessPlan`;
- promijenjen JMB/JMBG → nova encrypted kopija (`JmbEncryptionService::encrypt`);
- nepromijenjen JMB/JMBG (dirty samo druga polja) → ciphertext se **ne** regeneriše;
- namjerno brisanje / prazan string → encrypted kolona `NULL`;
- neuspjeh enkripcije **sprečava** persist novog/izmijenjenog plaintext JMB-a (isti `save()`, ista transakcija);
- Faza C **ne** mijenja read (encrypted-first VALUE read je Faza D);
- uniqueness/equality ostaje plaintext;
- ručni / direct SQL (phpMyAdmin, raw query) **zaobilazi** Fazu C i operativno je **zabranjen** za izmjene JMB/JMBG kolona.

Encrypted-first read je Faza D, **nije** Faza C. Faza C dual-write ostaje aktivan i neizmijenjen. Faza C **ne** uklanja plaintext.

### 8.1 Produkcijska verifikacija Faze C — 2026-09-10 (`users.jmb`)

Ovo je **stvarni produkcijski zapis**, ne plan. Identitet test-korisnika, JMB vrijednost, ciphertext i ključevi **nisu** dokumentovani.

1. Deploy završen preko Plesk Laravel Toolkit.
2. `php artisan about` na produkciji: Environment=`production`, Laravel 12.29.0, PHP 8.3.33, Debug OFF; aplikacija operativna.
3. Kontrolisani test: JMB postojećeg korisnika izmijenjen kroz regularni aplikacioni tok profila; snimanje uspjelo.
4. Read-only provjera (bez apply-a):

```text
php artisan jmb:backfill-encrypted --dry-run --scope=users --chunk=100
```

Rezultat (`users`):

| scanned | would_encrypt | already_valid | skipped_no_plaintext | errors |
|--------:|--------------:|--------------:|---------------------:|-------:|
| 51 | 0 | 22 | 29 | 0 |

Zaključak:

- Faza C `users.jmb` dual-write produkcijska verifikacija = **PASS**;
- remediation / backfill apply **nije** potreban;
- `would_encrypt=0` i `already_valid=22` znače da encrypted kopija prati plaintext na toj putanji, bez novih rupa u `users` scope-u;
- **ne** tvrdi se da je svaka Faza C write putanja ručno produkcijski testirana.

---

## 9. Faza D — encrypted-first VALUE read

**Kod na main:** `077a01cd5c5af85f327ccdd4701b9bb089051523` — `feat(security): add encrypted-first JMB reads` (imutabilno).

**Namjena:** aplikacioni VALUE read spremljenog JMB/JMBG ide encrypted-first. Faza D **ne** uklanja plaintext, **ne** mijenja šemu, **ne** mijenja Fazu C dual-write i **ne** mijenja plaintext SQL equality/uniqueness.

**Produkcijski status:** **IMPLEMENTATION COMPLETE**; **DEPLOYED TO PRODUCTION**; **PRODUCTION VERIFIED**; **PRODUCTION ACCEPTED** (2026-09-10, §9.2).

### 9.1 Ugovor

Granica implementacije: `App\Security\JmbEncryptedReadService` (par `encrypted` / `plaintext` + sanitizovani kontekst). **Nema** Eloquent `getJmbAttribute()` / attribute override.

Za svaki aplikacioni VALUE read spremljenog JMB/JMBG:

1. encrypted postoji i dekripcija uspije → **dekriptovana encrypted vrijednost je autoritativna** (i kada se plaintext razlikuje);
2. encrypted je `NULL`/prazan i plaintext postoji → **tokom Faze D (retirement OFF):** privremeni plaintext fallback + sanitizovani signal `reason=plaintext_fallback`; **važeće (retirement ON):** **nema** plaintext fallback, vraća se `null`;
3. encrypted postoji i dekripcija padne → **fail closed**; **nema** plaintext fallback.

Live kanonski identitet: `CanonicalIdentityReader` (FL / ovlašćeno lice / predstavnik). Legacy `users.jmb` VALUE putanje koje su integrisane (`CurrentIdentityResolver::fromLegacyUser`, `ExistingSubjectIdentityPrefill`) takođe idu encrypted-first.

Snapshoti ostaju **istorijski snapshoti tog reda**. Dekriptuje se encrypted kopija **tog** reda; ne zamjenjuje se tekućim profilnim JMB-om:

| Snapshot | Encrypted |
|----------|-----------|
| `applications.physical_person_jmbg` | `physical_person_jmbg_encrypted` |
| `applications.applicant_jmbg` | `applicant_jmbg_encrypted` |
| `business_plans.applicant_jmbg` | `applicant_jmbg_encrypted` |

Ako je snapshot prema postojećem poslovnom ponašanju **genuinely odsutan**, postojeći live-identity fallback može ostati; taj live identitet i sam ide encrypted-first.

Observability fallback-a smije sadržati samo `table`, `id`, logical pair i `reason=plaintext_fallback`. Faza D read **ne** loguje JMB/JMBG, ciphertext niti ključ. Dekripcijski fail **ne** pada na plaintext.

### 9.2 Produkcijska verifikacija Faze D — 2026-09-10

Ovo je **stvarni produkcijski zapis**, ne plan. Identitet test-korisnika, JMB/JMBG vrijednost, ciphertext i ključevi **nisu** dokumentovani. Produkcijski podaci **nisu** namjerno mijenjani tokom ovih read verifikacija.

Kontrolisano kroz normalni aplikacioni UI:

| Putanja | Rezultat |
|---------|----------|
| Izmjena korisničkog profila — postojeći JMB se prikazuje ispravno | **PASS** |
| Obrazac 1A — postojeći application JMBG snapshot se prikazuje/prepunjava ispravno | **PASS** |
| Obrazac 2 / Biznis plan — postojeći business-plan JMBG snapshot se prikazuje/prepunjava ispravno | **PASS** |

---

## 10. Security / rollback status — Faza D (istorijski, 2026-09-10)

**Rešenje je bilo ovako (zastarelo / pre plaintext retirement-a):**

- encrypted kopije postoje za istorijske ne-prazne vrijednosti koje je obradio B2 (71) i za naknadne Eloquent upise koje pokriva Faza C;
- Faza C dual-write ostaje aktivan;
- aplikacioni VALUE read ide encrypted-first (Faza D);
- uniqueness i equality i dalje rade nad plaintextom;
- plaintext kolone **i dalje postoje** u bazi; administrator baze i dalje može vidjeti plaintext;
- Faza D štiti aplikacione read-ove, ali **nije** konačno uklanjanje plaintext-at-rest;
- rollback tada počiva na **sačuvanom plaintextu** plus **čuvanju JMB encryption ključeva**.

Važeće stanje nakon retirement-a: §17.

---

## 11. Šta Faza D closeout (2026-09-10) nije radio

Istorijske granice dokumentacionog closeout-a Faze D (ne mijenjati kao činjenicu tog dana):

- nije uklanjao plaintext;
- nije mijenjao produkcijski `.env`;
- nije nalagao ponovni B2 apply;
- nije preimenovao PHP klase, testove ni git istoriju;
- nije usvajao narednu fazu (nema automatskog „sljedećeg slova“ nakon D).

Ponovni produkcijski `jmb:backfill-encrypted` apply nije bio potreban za already-valid ciphertext. Nakon retirement mode-a (2026-09-11) B2 apply je **odbijen** runtime-om; v. §17.

---

## 12. Lookup digest — kolone, backfill, dual-write (plan 1/6)

**Kod na main:**

| Commit | Poruka |
|--------|--------|
| `d9cb132b0f3ef0f2970a5520d1e96f0082b85622` | `feat(security): add parallel JMB lookup columns` |
| `e660f31c579f2d0eb30a4cb8e31aa6d6d7ee2515` | `feat(security): add JMB lookup digest service` |
| `9d90f6d1311fe203dc8df620bbb517dde2ad08ee` | `feat(security): add controlled JMB lookup backfill` |
| `8b3ac4bf2a864d2a3c38cde01d2465c78fa88843` | `feat(security): add JMB lookup dual-write synchronization` |

HMAC lookup digest je odvojen od `JMB_ENCRYPTION_KEY` i od `APP_KEY`. Env: `JMB_LOOKUP_KEY`, `JMB_LOOKUP_KEY_ID`. Ključevi se **ne** dokumentuju.

Lookup se piše samo za `users.jmb` i `physical_person_identities.jmb`. Application / business-plan snapshoti i ovlašćeno lice / predstavnik **ne** pišu lookup.

Komanda `jmb:backfill-lookup` kopira postojeći plaintext u `jmb_lookup`. **Nije cron.** U retirement mode-u komanda **odbija** rad.

Plan 1/6 lookup dual-write = **CLOSED**.

---

## 13. Uniqueness / equality cutover (plan 2/6 i 3/6)

**Kod na main:** `0128272533a550ee16bde348f357f754caa518c7` — `feat(security): cut over JMB uniqueness to lookup digests`

Runtime uniqueness/equality za registraciju, profil i dopunu postojećeg subjekta: `CanonicalIdentifierUniqueness::jmbTaken()` poredi digest na `users.jmb_lookup` i `physical_person_identities.jmb_lookup`. Nema SQL equality na plaintext `jmb` za tu runtime provjeru.

`users.jmb` UNIQUE **ostaje** kao schema constraint. Encrypted kolone nisu unique.

Plan 2/6 uniqueness/equality cutover = **CLOSED**.
Plan 3/6 produkcijska verifikacija cutover-a = **CLOSED**.

---

## 14. Plaintext runtime VALUE-read cutover (plan 4/6)

Faza D (`077a01c`, 2026-09-10) uvela je encrypted-first VALUE read sa privremenim plaintext fallback-om dok ciphertext nije prisutan.

**Kod na main:** `cb8fbea9f06820d1530837125adaccb4ece2e9c9` — `feat(security): cut over JMB value reads to encrypted-first`

Taj commit siječe preostale aplikacione VALUE read-ove na encrypted-first putanju. U retirement mode-u (`JMB_PLAINTEXT_RETIREMENT_ENABLED=true`) `JmbEncryptedReadService` **ne** pada na plaintext: ciphertext odsutan → `null`. Decrypt fail ostaje fail-closed.

Plan 4/6 = **CLOSED**.

---

## 15. Plaintext retirement write-contract (plan 5/6)

**Kod na main:** `0dd9107d14be4a16fd386e8af1fe9b7506c3092f` — `feat(security): prepare JMB plaintext retirement writes`

Logički JMB se persistuje preko `JmbDualWrite::assignLogical()`.

| Flag | Persist |
|------|---------|
| `false` | plaintext = logička vrijednost; encrypted/lookup se sinhronizuju |
| `true` | encrypted/lookup se sinhronizuju; plaintext se sprema kao `NULL`; `NULL` plaintext **nije** logički clear |

Namjerni clear ostaje `assignLogical(..., null)`. Ažuriranje nepovezanih polja **ne** regeneriše ciphertext/lookup ako se logički JMB nije promijenio. Neuspjeh enkripcije sprečava persist.

Ovaj commit **ne** null-uje postojeće redove. To radi `jmb:retire-plaintext` (§16–§17).

Plan 5/6 = **CLOSED**.

---

## 16. Precheck i retirement komanda (implementacija)

**Kod na main:**

| Commit | Poruka |
|--------|--------|
| `9d3139e5c2361150f7dddd18b675c2fcab3e306c` | `feat(security): add JMB plaintext retirement precheck` |
| `027067c42dd1d5678d687310076931cd8d3bfe1f` | `feat(security): add controlled JMB plaintext retirement` |

```text
php artisan jmb:precheck-plaintext-retirement
php artisan jmb:retire-plaintext --dry-run
php artisan jmb:retire-plaintext
```

**Nisu cron.** Apply zahtijeva retirement mode ON i prolazan precheck. Komanda null-uje **samo** sedam plaintext kolona; encrypted i lookup fingerprinti moraju ostati neizmijenjeni. Jedna transakcija.

Dok je retirement mode ON:

- `jmb:precheck-plaintext-retirement` odbija rad;
- `jmb:backfill-encrypted` odbija rad;
- `jmb:backfill-lookup` odbija rad.

Izlaz su samo agregati (`nulled`, `post_plaintext_non_null`, status). Nikad JMB/JMBG, ciphertext ili ključ.

---

## 17. Produkcijski plaintext retirement closeout — 2026-09-11

Ovo je **stvarni produkcijski zapis**, ne plan. Identitet korisnika, JMB/JMBG vrijednosti, ciphertext i ključevi **nisu** dokumentovani.

Kanonski kod na produkciji: `027067c42dd1d5678d687310076931cd8d3bfe1f`.

### 17.1 Pre-retirement verifier

```text
php artisan jmb:precheck-plaintext-retirement
```

Rezultat: **PASS — all 7 plaintext columns are production-data ready for controlled retirement**

### 17.2 Dry-run

```text
php artisan jmb:retire-plaintext --dry-run
```

Rezultat: `precheck=PASS`, `total_would_null=72`, **READY — plaintext retirement can proceed after retirement mode is enabled**

### 17.3 Runtime switch

Produkcija prebačena na:

```text
JMB_PLAINTEXT_RETIREMENT_ENABLED=true
```

Ovo ostaje **obavezno produkcijsko operativno stanje** nakon završenog retirement-a. Nije jednokratni apply-only flag.

Ponovni `jmb:precheck-plaintext-retirement` ispravno odbijen:

```text
JMB plaintext retirement precheck is refused while plaintext retirement is enabled.
```

### 17.4 Apply

```text
php artisan jmb:retire-plaintext
```

`precheck=PASS`

| Scope | nulled | post_plaintext_non_null |
|-------|-------:|------------------------:|
| `users` | 22 | 0 |
| `physical-identities` | 20 | 0 |
| `authorized-persons` | 0 | 0 |
| `foreign-branch-representatives` | 0 | 0 |
| `applications-physical-person` | 6 | 0 |
| `applications-applicant` | 11 | 0 |
| `business-plans` | 13 | 0 |
| **Ukupno** | **72** | **0** |

**PASS — JMB plaintext retirement completed successfully**

Encrypted/lookup preservation je enforcement retirement komande: ciphertext i lookup digesti nijesu mijenjani ovim apply-om.

### 17.5 Produkcijski smoke nakon retirement-a

Kontrolisano kroz normalni aplikacioni UI. Vrijednosti se **ne** zapisuju ovdje.

| Putanja | Rezultat |
|---------|----------|
| Postojeći Application / Obrazac 1a snapshot | **PASS** |
| Postojeći Business Plan JMBG snapshot | **PASS** |

Prikaz ide iz `*_encrypted` preko `JmbEncryptedReadService` / model `*ForRead()` accessora. Retired plaintext kolona nije izvor prikaza.

### 17.6 Finalno produkcijsko stanje

- sve 7 plaintext JMB/JMBG kolona nemaju aktivne plaintext vrijednosti;
- 72 postojeće plaintext vrijednosti retired na `NULL`;
- encrypted vrijednosti su autoritativne;
- `users` i `physical_person_identities` koriste `jmb_lookup` za equality/uniqueness;
- JMB encrypted read radi bez plaintext-a;
- plaintext fallback je isključen u retirement mode-u;
- novi logički upisi **ne** repopuliraju plaintext;
- `JMB_PLAINTEXT_RETIREMENT_ENABLED=true` ostaje produkcijski runtime;
- backfill komande odbijaju rad u retirement mode-u;
- `users.jmb` UNIQUE ostaje;
- plaintext kolone **nijesu** dropovane;
- encrypted kolone **nijesu** mijenjane ovim retirement-om;
- lookup kolone/indeksi **nijesu** uklonjeni.

### 17.7 Originalni plan — zatvaranje

| Stavka | Status |
|--------|--------|
| 1/6 lookup dual-write | **CLOSED** |
| 2/6 uniqueness/equality cutover | **CLOSED** |
| 3/6 production verification of cutover | **CLOSED** |
| 4/6 plaintext runtime value-read cutover | **CLOSED** |
| 5/6 plaintext retirement readiness/write-contract | **CLOSED** |
| 6/6 controlled production plaintext retirement | **CLOSED** |
| **Ukupno** | **6/6 CLOSED** / **PRODUCTION ACCEPTED** |

---

## 18. Fizičko uklanjanje kolona — izvan ovog closeout-a

Ovaj closeout **ne** dokumentuje fizički `DROP` plaintext kolona kao nedovršen posao potreban za zatvaranje plana.

Security cilj je ostvaren:

- plaintext vrijednosti retired na `NULL`;
- runtime encrypted-only;
- nema budućeg plaintext persist-a dok je retirement mode uključen.

Fizičko uklanjanje kolona ili indeksa, ako ikad bude željeno, je **odvojena buduća PO odluka** i **nije** dio ovog closeout-a.