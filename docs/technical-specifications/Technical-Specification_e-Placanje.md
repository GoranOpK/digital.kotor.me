# Digital Kotor
# Tehnička specifikacija e-Plaćanja
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-TS-001
**Modul:** e-Plaćanje
**Status dokumenta:** U IZRADI
**Verzija:** 1.0.10

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Technical Specification. Unesena obavezujuća ograničenja iz P-01–P-08 i F-01. Tehnička rješenja nisu projektovana. |
| 0.2 | 2026-07-27 | UR-01 – Uplatni računi kao konfiguracioni podaci; implementaciona zabrana hardkodiranja; Katalog ≠ šifrarnik; buduća arhitektura izvoda šifrarnika. |
| 0.3 | 2026-07-27 | BP-01/BP-02/BP-03 – Neutralne napomene: jedinstveni izvor vrsta uplata; podrška različitim izvorima podataka; nepromjenjivost potvrđene transakcije. |
| 0.4 | 2026-07-27 | BP-04 – Jedinstvena apstraktna integracija prema payment gateway sloju; konfiguracija odvojena od koda; bez pretpostavki o konkretnoj implementaciji. |
| 0.5 | 2026-07-27 | BP-05 – Prihvatanje i obrada statusa ishoda transakcije iz payment gateway sloja; bez vezivanja za konkretnu implementaciju. |
| 0.6 | 2026-07-27 | BP-06 – Generisanje potvrde nezavisno od konkretnog payment gateway-a; bez formata/dostave/PDF pretpostavki. |
| 0.7 | 2026-07-27 | BP-07 – Izvor obaveznih podataka (iznos, primalac, račun, poziv na broj, svrha) kao konfiguracija vrste uplate; bez implementacionog dizajna. |
| 0.8 | 2026-07-27 | BP-08 – Životni ciklus transakcije (statusi, state machine, audit, potvrda izvornom sistemu); bez implementacionog dizajna. |
| 0.9 | 2026-07-27 | EP-PATCH-BM-008A – Redakcijsko usklađivanje BP-05/BP-06/BP-08: korisnička poruka ≠ status; početni status Kreirana; potvrda ≠ knjiženje. |
| 0.9.1 | 2026-07-27 | EP-PATCH-BM-008B – Redakcijsko usklađivanje: evidencija bilježi trenutni status transakcije. |
| 1.0 | 2026-07-27 | BP-09 – Istorija transakcija i pregled plaćanja (pristup, lista, filteri, detalji, retention); bez implementacionog dizajna. |
| 1.0.1 | 2026-07-27 | EP-PATCH-BM-009A – Redakcijsko: BP-06↔BP-09 (istorija); terminologija identifikatora. |
| 1.0.2 | 2026-08-17 | Dokumentacioni corrective: oznaka EP-TS-001; namespace EP-*; naziv modula e-Plaćanje. Statusi NIJE USVOJENO zadržani. Bez novih tehničkih odluka. |
| 1.0.3 | 2026-08-20 | EP-PATCH-TS-001 — Nasljeđivanje zatvorenog Koraka 6 / EP-BM-001 v1.0.0. SUPERSEDE stale business contract (Kreirana/U toku; preuzimanje iz izvornog sistema; zapis prije gateway-a; 17 kategorija + 41 vrsta uplate). Bez izbora gateway mehanizma. Bez finalne DB šeme. `APPLICATION DEVELOPMENT = LOCAL ONLY`. `PRODUCTION APPLICATION DEPLOY = NOT APPROVED`. |
| 1.0.4 | 2026-08-20 | EP-PATCH-TS-002 — Usklađivanje platform user-model zavisnosti sa kanonskih 8 tipova. Application corrective COMPLETE; production data cleanup OPEN. Mapping 17/41 ostaje OPEN. Bez application implementation-a. |
| 1.0.5 | 2026-08-20 | EP-PATCH-TS-003 — Faza 1 domain/schema foundation IMPLEMENTED (local/test). Katalog/availability/gateway/UI ostaju NOT IMPLEMENTED. |
| 1.0.6 | 2026-08-20 | EP-PATCH-TS-004 — Faza 2 catalog admin IMPLEMENTED locally (CRUD vrsta/računa, activate/deactivate, immutable account_number). Availability, 17/41 podaci, user flow i gateway nijesu uključeni. Nije production complete. |
| 1.0.7 | 2026-08-20 | EP-PATCH-TS-005 — Faza 3 availability engine IMPLEMENTED locally (type+account rules, fail-closed evaluation, canonical 8 user types). Real 17/41 mapping, user flow i gateway nijesu uključeni. Nije production complete. |
| 1.0.8 | 2026-08-21 | EP-PATCH-TS-006 — Faza 4 user payment flow IMPLEMENTED locally (browse / account / amount / preview, declare-on-use UI, module enable/disable). Purpose/model/code/reference nijesu izmišljeni. Gateway i transakcija nijesu pokrenuti. Nije production complete. |
| 1.0.9 | 2026-08-21 | EP-PATCH-TS-007 — Faza 5 local initiation + Fake Gateway lifecycle IMPLEMENTED locally. Explicit start kreira PaymentInitiation + PaymentTransaction(processing). Bankart nije implementiran. Nije production complete. |
| 1.0.10 | 2026-08-21 | EP-PATCH-TS-008 — Faza 6A provider-neutral gateway hardening IMPLEMENTED locally. Bankart adapter ostaje BLOCKED BY EXTERNAL DEPENDENCY. Nije production complete. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument je tehnička specifikacija **U IZRADI**. Nasljeđuje zatvoreni poslovni model (EP-BM-001) i funkcionalne zahtjeve (EP-FS-001).

U verziji 1.0.10 dokument:

* usklađuje obavezujuća projektna ograničenja sa Korakom 6 (2026-08-20);
* više **ne** propagira superseded poslovni ugovor;
* evidentira tehničke otvorene tačke kao **OPEN PRE-PRODUCTION DEPENDENCY**;
* **ne** bira gateway mehanizam (webhook / callback / API status check / drugi ugovoreni mehanizam);
* evidentira **IMPLEMENTED FOUNDATION** Faze 1 (tabele/modeli/statusi), bez usvajanja cjelokupnog modela podataka ni poslovnog toka;
* evidentira **IMPLEMENTED locally** Faze 2 catalog admin (CRUD vrsta/računa), bez 17/41 podataka, user flowa i gateway-a;
* evidentira **IMPLEMENTED locally** Faze 3 availability engine (type+account rules, fail-closed), bez stvarne 17/41 matrice;
* evidentira **IMPLEMENTED locally** Faze 4 user payment flow do preview-a (declare-on-use UI, module flag). Purpose/model/code/reference nijesu izmišljeni. Gateway nije pokrenut. Nije production complete.
* evidentira **IMPLEMENTED locally** Faze 5: explicit start boundary, PaymentInitiation + PaymentTransaction(processing), Fake Gateway, verified result, terminal statuses. Bankart contract ostaje OPEN. Nije production complete.
* evidentira **IMPLEMENTED locally** Faze 6A: provider-neutral gateway port, verified result boundary, inquiry/reconciliation foundation. `BANKART ADAPTER = BLOCKED BY EXTERNAL DEPENDENCY`.

Poslovni SSOT ostaje EP-BM-001. Terminologija: EP-RG-001.

---

# Razvojna granica

| Pravilo | Status |
|---------|--------|
| `APPLICATION DEVELOPMENT = LOCAL ONLY` | VAŽI |
| `PRODUCTION APPLICATION DEPLOY = NOT APPROVED` | VAŽI |
| Application implementation | **PHASE 1–6A IMPLEMENTED** (local/test): foundation through Fake lifecycle + provider-neutral gateway hardening. Bankart **NOT IMPLEMENTED**. Nije production-ready. |
| Dokumentacioni commit/push | dozvoljen po projektnom toku |

Ova granica **nije** usvojena tehnička arhitektura. Poglavlja 3–9 ostaju **NIJE USVOJENO**, osim evidentiranog Faza 1 foundation zapisa ispod.

---

# Faza 1 — domain/schema foundation (IMPLEMENTED)

**Status:** IMPLEMENTED FOUNDATION (local/test only). **NOT** a completed EP module. **NOT** an adopted full data model for poglavlje 4.

Ovo **nije** poslovni tok, katalog, availability, gateway niti UI.

## PaymentStatus

PHP backed enum `App\Enums\PaymentStatus`. Storage = VARCHAR tehnički ključ. Label = Korak 6.

| Status | Storage value | Label |
|--------|---------------|-------|
| Processing | `processing` | U obradi |
| Successful | `successful` | Uspješna |
| Failed | `failed` | Neuspješna |
| Cancelled | `cancelled` | Otkazana |

`PAYMENT STATUSES = 4`. Nema Kreirana / U toku / pending / Greška kao EP poslovne statuse.

## Tabele (prazan katalog)

| Tabela | Svrha |
|--------|--------|
| `payment_types` | Vrsta plaćanja (code, name, description, is_active) |
| `payment_accounts` | Račun jedne vrste (`account_number` string, is_active). Promjena broja = deaktivacija + novi red. |
| `payment_initiations` | Korisnička potvrda / buduća idempotency granica. **Nije** poslovna transakcija. Nema petog statusa. |
| `payment_transactions` | Poslovna EP transakcija. Live FK + JSON `snapshot`. `currency` = EUR. Amount `decimal(12,2)`. |
| `payment_transaction_events` | Append-only evidencija. Nema processing-a. |

Stub tabela `payments` **koegzistira** i **nije** mijenjana. Cutover je kasnija faza.

Nema: 17/41 seed; availability tabela; `ep_catalog_audits`; fiscal polja; gateway tabele.

## Modeli

`PaymentType`, `PaymentAccount`, `PaymentInitiation`, `PaymentTransaction`, `PaymentTransactionEvent`.

Relacije: Type hasMany Account; Initiation belongsTo User/Type/Account, hasOne Transaction; Transaction belongsTo User/Initiation/Type/Account, hasMany Events.

## Još nije implementirano

Fake/Bankart/callback/HMAC/queue/status inquiry; PDF/email; EP audit subsystem; 17/41 seed; stvarna availability matrica; purpose/model/code/reference konfiguracija; PaymentTransaction / gateway start.

`GATEWAY PROVIDER CONTRACT = OPEN`

`FINAL 17/41 USER CATEGORY MAPPING = OPEN`

`EP CATALOG AUDIT = OPEN ARCHITECTURE DECISION`

---

# Faza 2 — catalog admin (IMPLEMENTED locally)

**Status:** IMPLEMENTED locally. **NOT** production complete. **NOT** PO local acceptance.

Administrator platforme (`admin` / `superadmin`) upravlja vrstama i računima na `/admin/e-placanje/payment-types`.

- Create/edit vrste (kod nepromjenjiv nakon create)
- Create/edit računa (broj računa nepromjenjiv nakon create)
- Activate/deactivate umjesto hard delete
- Aktivacija vrste: naziv + kod + najmanje jedan aktivan račun (fail-closed)
- Deaktivacija posljednjeg aktivnog računa skida aktivnost vrste
- `is_active` je intern admin flag; **nije** user-facing katalog; availability nije u obuhvatu
- Nema 17/41 podataka, nema mappinga na 8 user types, nema novog audit subsystema
- Stub `/payments` neizmijenjen

---

# Faza 3 — availability engine (IMPLEMENTED locally)

**Status:** IMPLEMENTED locally. **NOT** production complete. **NOT** PO local acceptance.

Generički engine (`App\Services\Payments\PaymentAvailabilityService`) evaluira dostupnost kataloga prema platformskom identitetu. Ne redefiniše `users.user_type`.

- Tabele: `payment_type_availabilities`, `payment_account_availabilities`
- Input: kanonskih 8 `UserType` storage vrijednosti; `resident` / `non-resident` samo za Fizičko lice i Preduzetnika
- Fail-closed: nema poklapanja = NOT AVAILABLE
- Intersection: aktivna vrsta + type rule + aktivan račun + account rule
- Outcome: `available` / `not_available` / `residential_declaration_required`
- Declare-on-use UI = NOT IMPLEMENTED; engine koristi postojeći `ResidentialStatusDeclaration`
- Legacy user_type i `ex-non-resident` = fail closed; LEGACY AUTO-MAPPING = NONE
- Admin CRUD pravila na `/admin/e-placanje/payment-types/{type}/availabilities` i `.../accounts/{account}/availabilities`
- Nema hard delete (activate/deactivate)
- `is_active` kataloga ostaje administrativni flag (Faza 2 KEEP); availability je zaseban gate
- Nema stvarnih 17/41 pravila; `FINAL 17/41 USER CATEGORY MAPPING = OPEN`
- Stub `/payments` neizmijenjen

---

# Faza 4 — user payment flow (IMPLEMENTED locally)

**Status:** IMPLEMENTED locally. **NOT** production complete. PO LOCAL ACCEPTANCE = PASS (local).

`GET /payments` zamjenjuje dummy stub. Tok završava na preview-u.

- Browse usable vrsta preko `PaymentAvailabilityService` (fail-closed)
- 0 računa = vrsta nije usable; 1 = auto-select; 2+ = izbor
- Iznos: korisnik unosi; EUR; > 0; max 2 decimale
- Transient session draft; **NO TRANSACTION**; `payment_initiations` se ne koriste
- Preview: uplatilac, kategorija, vrsta, račun, iznos, 0 EUR provizija. Svrha/poziv/model/šifra **nisu prikazani** jer nijesu konfigurisani; preview nije potpuna uplatnica
- Declare-on-use UI za FL/Preduzetnika sa NULL residential
- Module flag `new_payments_enabled` u `ep_settings`
- Legacy `POST /payments/pay` ostaje ruta ali ne kreira transakciju
- Gateway nije pokrenut u ovoj fazi (uveden u Fazi 5)

---

# Faza 5 — initiation + Fake Gateway (IMPLEMENTED locally)

**Status:** IMPLEMENTED locally. **NOT** production complete. PO LOCAL ACCEPTANCE = PASS (local).

Lokalni lifecycle: preview → izričito **Pokreni plaćanje** → `PaymentInitiation` + `PaymentTransaction(status=processing)` → Fake Gateway → verified result → terminalni status → result page.

## Transaction boundary

Browse / account / amount / preview / back / odustajanje prije start-a = **NO TRANSACTION**.

Tek explicit start kreira zapise. Kreiranje initiation + transaction je atomsko (DB transaction). Draft `merchant_transaction_id` (`EPLOCAL-` + ULID) je server-generated idempotency ključ; unique constraint na `payment_transactions.merchant_transaction_id`. Consumed session draft se briše. Double POST / browser Back+repost ne kreira drugu transakciju.

Nova migracija **nije** dodata. Faza 1 šema je dovoljna.

## Gateway abstraction

- Contract: `PaymentGateway`, `PaymentGatewayStartResult`, `PaymentGatewayVerifiedResult`
- Resolver: `PaymentGatewayResolver` (config-driven; bez rasutog `app()->environment()` u business kodu)
- `config/payments.php`: local/testing default `fake`; production default unset = fail closed
- Fake nije dostupan kada je `app.env=production` ili `payments.fake.enabled=false`
- Bankart adapter **nije** implementiran. `GATEWAY PROVIDER CONTRACT = OPEN`. `BANKART CONTRACT = OPEN`.

## Fake Gateway

Lokalni/test adapter. Ekran: **LOKALNI SIMULATOR PLAĆANJA** / „Ovo nije stvarni payment gateway.“

Akcije: uspješno / neuspješno / otkazivanje.

Rute su `signed` + auth + vlasništvo transakcije. Query `?status=successful` nije autoritativan. Core prima samo verified normalized result.

Synthetic provider reference: prefix `SYN-GW-` (nije Bankart format).

## Status i callback

Kanonska 4 statusa. Terminalni: successful / failed / cancelled. Conflicting callback se ignoriše. Replay je idempotentan.

Amount i currency iz callback-a moraju odgovarati snapshot/transaction vrijednostima (EUR). Mismatch = fail closed, original amount se ne mijenja.

Module OFF blokira **nova** plaćanja; postojeća `processing` transakcija smije završiti. Availability promjena nakon start-a ne blokira callback; snapshot je istorijska istina.

## Gateway start failure

Fake `start()` je lokalno izdavanje signed URL-a. Durable `processing` se upisuje **prije** redirect-a.

Ako Fake/provider `start()` baci exception nakon commit-a: status ostaje **U obradi** (ne Failed, ne Cancelled). BM: Failed = gateway pouzdano potvrdio da plaćanje nije izvršeno; tehnički start exception to nije. Retry/status-check ostaju OPEN za pravi provider.

## Events

Append-only `payment_transaction_events`. Minimalni set: `transaction.started`, `gateway.redirected`, `payment.successful`, `payment.failed`, `payment.cancelled`. Canonical transition event se ne duplira na replay.

## Merchant transaction identity

Provider-neutral internal id: `EPLOCAL-{ULID}`. Unique, server-generated, not user-controlled. Final Bankart format constraints ostaju OPEN.

## Još nije implementirano

Bankart adapter/credentials/endpoints/HMAC; PDF/email; EP catalog audit subsystem; 17/41 seed; purpose/model/code/reference konfiguracija.

---

# Faza 6A — provider-neutral gateway hardening (IMPLEMENTED locally)

**Status:** IMPLEMENTED locally. **NOT** production complete. **NOT** PO local acceptance.

`PROVIDER-NEUTRAL FOUNDATION = IMPLEMENTED`

`BANKART ADAPTER = BLOCKED BY EXTERNAL DEPENDENCY`

External dependency: signed bank agreement + official technical documentation. To nije PO blocker za F6A.

## Portovi

- `PaymentGateway`: `name()`, `capabilities()`, `start(PaymentGatewayStartRequest)`
- `PaymentGatewayResultVerifier`: adapter proizvodi `PaymentGatewayVerifiedResult`; core processor prima samo taj typed DTO
- `PaymentGatewayStatusInquiry`: opcioni port; Fake ga **ne** implementira
- `PaymentGatewayCapabilities`: start, resultVerification, statusInquiry, hostedRedirect

Start request sadrži samo provider-neutral business data: merchant transaction id, transaction uuid, amount, currency. Return/callback URL-ovi nijesu u core DTO-u.

Start result je tehnički: `redirect_ready` / `technical_failure` / `unsupported`. Nije PaymentStatus.

## Inquiry / reconciliation

`PaymentStatusInquiryService::checkStatus()` normalizuje inquiry outcome i, ako je terminalan, primjenjuje isti `PaymentResultProcessor`.

Inquiry outcomes nijesu novi PaymentStatus-i: successful / failed / cancelled / processing / unknown / not_found / unsupported / technical_error.

Vrijeme samo **ne** mijenja `processing`. Nema production scheduler-a. Nema admin dugmeta (Faza 9).

`AUTOMATIC PAYMENT START RETRY = NOT ASSUMED`

## Failure / config

Unknown/missing/disabled provider: fail closed, user-safe poruka, bez interne detalje.

Ako je transakcija već durable i start tehnički pukne: ostaje `processing` + `gateway.start_failed`.

Amount/currency mismatch: nema transition, `gateway.verification_failed`, status ostaje. Nije `failed`.

Contradictory terminal result: original ostaje, `gateway.contradictory_result`.

## Fake fidelity

Fake implementira isti `PaymentGateway` port. Nema `if fake then skip core logic`. Signed local result i dalje ide kroz `PaymentResultProcessor`. Production + fake ostaje zabranjen.

Bankart keys/URLs/HMAC **nisu** u config-u.

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| EP-TS-001 / 1. Uvod i veza sa dokumentacijom | U IZRADI |
| EP-TS-001 / 2. Obavezujuća projektna ograničenja | USVOJENO (nasljeđuje Korak 6) |
| EP-TS-001 / 2.4 Uplatni računi – konfiguracioni podaci (UR-01) | USVOJENO |
| EP-TS-001 / 2.5 Ograničenja iz BP-01, BP-02 i BP-03 | USVOJENO uz SUPERSEDE BP-02 |
| EP-TS-001 / 2.6 BP-04 – Jedinstvena integracija sa payment gateway slojem | USVOJENO |
| EP-TS-001 / 2.7 BP-05 – Obrada ishoda transakcije | USVOJENO uz UPDATE |
| EP-TS-001 / 2.8 BP-06 – Potvrda o izvršenom plaćanju | USVOJENO uz UPDATE |
| EP-TS-001 / 2.9 BP-07 – Izvor obaveznih podataka | USVOJENO uz SUPERSEDE 07.1/07.5 |
| EP-TS-001 / 2.10 BP-08 – Životni ciklus transakcije | USVOJENO uz SUPERSEDE |
| EP-TS-001 / 2.11 BP-09 – Istorija transakcija | USVOJENO uz UPDATE |
| EP-TS-001 / 3. Arhitektura rješenja | NIJE USVOJENO |
| EP-TS-001 / 4. Model podataka | NIJE USVOJENO |
| EP-TS-001 / 5. Integracije | NIJE USVOJENO |
| EP-TS-001 / 6. Sigurnost | NIJE USVOJENO |
| EP-TS-001 / 7. Interfejsi i API | NIJE USVOJENO |
| EP-TS-001 / 8. Ne-funkcionalni zahtjevi | NIJE USVOJENO |
| EP-TS-001 / 9. Plan implementacije | NIJE USVOJENO |
| EP-TS-001 / Faza 1 domain/schema foundation | IMPLEMENTED (local/test; nije cjelokupni model podataka) |
| EP-TS-001 / Faza 2 catalog admin | IMPLEMENTED locally (nije production complete) |
| EP-TS-001 / Faza 3 availability engine | IMPLEMENTED locally (nije production complete; 17/41 mapping OPEN) |
| EP-TS-001 / Faza 4 user payment flow | IMPLEMENTED locally (do preview-a; PO local PASS; nije production complete) |
| EP-TS-001 / Faza 5 initiation + Fake Gateway | IMPLEMENTED locally (PO local PASS; Bankart OPEN; nije production complete) |
| EP-TS-001 / Faza 6A provider-neutral gateway hardening | IMPLEMENTED locally (Bankart adapter BLOCKED BY EXTERNAL DEPENDENCY) |

---

# Pravila upravljanja Technical Specification

1. Tehnička specifikacija pripada modulu e-Plaćanja (EP-TS-001).
2. Tehnička rješenja unose se isključivo nakon usvojene tehničke ili projektne odluke i evidentiraju kroz PATCH.
3. Cursor ne smije samostalno projektovati bazu podataka, API-je, integracije, arhitekturu ni druga tehnička rješenja.
4. Tehnička specifikacija mora ostati usklađena sa poslovnim modelom (EP-BM-001) i funkcionalnom specifikacijom (EP-FS-001). **KORAK 6 WINS.**
5. P-01 do P-08, F-01, UR-01 i BP-01 do BP-09 ostaju identifikatori. Aktivno značenje je ono iz EP-BM-001 (KEEP / UPDATE / SUPERSEDE). Stari TS tekst koji je u konfliktu je **SUPERSEDE**.
6. Ako bankovna/gateway dokumentacija još nije ugovorena, mehanizam se **ne** bira.

---

## Sadržaj

1. Uvod i veza sa dokumentacijom
2. Obavezujuća projektna ograničenja
3. Arhitektura rješenja
4. Model podataka
5. Integracije
6. Sigurnost
7. Interfejsi i API
8. Ne-funkcionalni zahtjevi
9. Plan implementacije

---

# EP-TS-001 / 1. Uvod i veza sa dokumentacijom

Modul e-Plaćanje je V1 servis za korisnički iniciranu elektronsku uplatu prema kontrolisanom katalogu vrsta plaćanja i računa Opštine Kotor (P-01 / Korak 6).

Tehnička specifikacija razvija se u okviru dokumentacije propisane odlukom P-06:

| # | Dokument | Putanja |
|---|----------|---------|
| 1 | Pravni okvir e-Plaćanja (EP-PO-001) | `docs/pravni-okvir/Pravni_okvir_e-Placanje.md` |
| 2 | Katalog finansijskih obaveza (EP-KF-001) | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` |
| 3 | Poslovni model e-Plaćanja (EP-BM-001) | `docs/business-model/Business_Model_e-Placanje.md` |
| 4 | Funkcionalna specifikacija e-Plaćanja (EP-FS-001) | `docs/functional-specifications/Functional-Specification_e-Placanje.md` |
| 5 | Tehnička specifikacija e-Plaćanja (EP-TS-001) | `docs/technical-specifications/Technical-Specification_e-Placanje.md` |
| 6 | Registar skraćenica e-Plaćanja (EP-RG-001) | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-e-Placanja.md` |

Sljedivost: EP-BM-001 → EP-FS-001 → EP-TS-001

---

# EP-TS-001 / 2. Obavezujuća projektna ograničenja

**Status:** USVOJENO (nasljeđuje Korak 6)

Ova ograničenja važe za sva buduća tehnička rješenja. Ne predstavljaju tehnički dizajn.

## 2.1 Poslovne i funkcionalne granice

| Oznaka | Ograničenje |
|--------|-------------|
| P-01 | Modul služi korisnički iniciranoj elektronskoj uplati prema katalogu vrsta plaćanja i računa Opštine Kotor. |
| P-02 | V1 je elektronski kanal za plaćanje obaveza koje se mogu platiti na blagajni; korisnik plaća u svoje ime. |
| P-03 | Modul ne obračunava obaveze, ne donosi upravna rješenja, ne kreira zaduženja i ne vodi izvorne evidencije. V1 ne pronalazi/preuzima rješenja niti zaduženja. |
| P-04 | Modul ne uvodi nove obaveze niti mijenja postojeće poslovne procese Opštine. |
| P-05 | Svaka funkcionalnost mora imati pravni osnov. |
| P-07 | Pravni osnov se evidentira po propisanim poljima; bez pretpostavljenih podataka. |
| P-08 | Izvorni sistem / nadležni organ ostaje mjerodavan za stvarnu obavezu. V1 **ne** preuzima te podatke i **ne** potvrđuje izmirenje. |

## 2.2 Obuhvat kataloga (F-01) — SUPERSEDE ontologije

**Kanonski (Korak 6):** `17 vrsta plaćanja` → `41 račun`.

**SUPERSEDE:** „jedinica podrške = pojedinačna vrsta uplate (ne kategorija)“ i „17 kategorija + 41 vrsta uplate“.

| Ograničenje | Opis |
|-------------|------|
| Vrsta plaćanja | Šta korisnik plaća (17). |
| Račun | Gdje se sredstva uplaćuju (41). Jedna vrsta može imati 1..N računa. |
| Filter | `korisnik → dozvoljena vrsta → dozvoljeni račun(i)`. Račun ne proširuje pravo sa nivoa vrste. |
| Izvor liste | Isključivo EP-KF-001; bez samostalnog dopunjavanja iz propisa. |
| Mapping | Konačno mapiranje 17/41 na 8 kanonskih user types = OPEN PRE-PRODUCTION. Ne izmišljati u TS. |

## 2.3 Dokumentacioni preduslov za tehnički dizajn

Prije usvajanja tehničkih rješenja (poglavlja 3–9) potrebno je:

1. zatvoreni BM (ispunjeno: EP-BM-001 v1.0.1);
2. FR usklađeni sa Korakom 6 i kanonskim user modelom (EP-FS-001 v1.1.1, status U IZRADI zbog gateway AC);
3. gateway/bankovna dokumentacija za mehanizam statusa;
4. usvojene posebne tehničke odluke za arhitekturu, podatke i integracije.

Katalog ontologija je usklađena; konačni availability mapping ostaje OPEN.

## 2.4 Uplatni računi – konfiguracioni podaci (UR-01)

**Status:** USVOJENO

### Projektno pravilo

> Uplatni računi predstavljaju konfiguracione podatke (šifrarnik) i ne smiju biti hardkodirani u aplikacionom kodu.

Korisnik **ne može** ručno unijeti račun. Račun uvijek dolazi iz kontrolisanog kataloga.

1 dozvoljeni račun: sistem ga može automatski odabrati. 2+ dozvoljenih: korisnik bira. Bez aktivnog/validnog/dozvoljenog računa vrsta se ne prikazuje.

Korišćeni račun se ne briše; deaktivira se. Promjena broja računa = stari deactivate + novi catalog record.

### Referentni podaci u dokumentaciji

Brojevi uplatnih računa u **EP-KF-001** predstavljaju referentne podatke preuzete iz važeće Naredbe o načinu uplate javnih prihoda.

Njihovo navođenje u dokumentaciji **nije** hardkodiranje niti projektovanje implementacije.

### Implementaciono pravilo

Tokom razvoja **nije dozvoljeno** hardkodirati brojeve računa u kontrolerima, servisima, modelima ili konfiguracionim klasama koje predstavljaju poslovnu logiku.

### Buduća arhitektura (ogrančenje, ne dizajn)

Katalog je **poslovni referentni dokument**. Iz njega će kasnije biti izveden šifrarnik. **Katalog nije šifrarnik i nije implementacioni artefakt.**

Način tehničke realizacije šifrarnika (tabela, seed, admin UI) **nije** predmet ove odluke.

## 2.5 Ograničenja iz BP-01, BP-02 i BP-03

**Status:** USVOJENO uz SUPERSEDE BP-02

### BP-01 – Jedinstveni izvor vrsta plaćanja — UPDATE

Buduća implementacija mora omogućiti pregled i pretragu nad **istim** skupom vrsta plaćanja izvedenim iz Kataloga / šifrarnika.

Ne smije postojati paralelna lista. Filter eligibility-ja je obavezan kada mapping bude usvojen.

### BP-02 – Popunjavanje podataka — SUPERSEDE

**SUPERSEDE:** automatsko preuzimanje iz izvornog informacionog sistema; dual-mode „integracija vs ručni unos“.

Kanonski V1: podaci uplatioca iz **current profile**; iznos unosi korisnik; račun iz kataloga; svrha sistemski prema vrsti; poziv per vrsta/račun.

V1 **ne** pronalazi rješenja i **ne** preuzima zaduženja.

### BP-03 – Pregled, potvrda, immutability — UPDATE

Tok: `formiranje` → `pregled` → `izričita potvrda` → `gateway`.

Do gateway-a: nazad; izmjena vlastitih unosa; odustajanje = **NO TRANSACTION**.

Potvrda korisnika **nije** dovoljna za nastanak transakcije i **nije** potvrda uspjeha.

Nakon gateway start-a: transakcija je immutable.

## 2.6 BP-04 – Jedinstvena integracija sa payment gateway slojem

**Status:** USVOJENO — KEEP

Aplikacija koristi **jednu apstraktnu integraciju** prema payment gateway sloju.

Broj računa javnih prihoda ne smije uslovljavati posebnu gateway integraciju po računu.

Konfiguracija gateway-a, računa, merchant profila, terminala i parametara mora biti **izdvojena** iz poslovne logike (UR-01).

Payment gateway je infrastrukturna komponenta, ne poslovna logika. Zamjena gateway-a ne smije mijenjati poslovni tok.

### Ograničenje

Ova odluka **ne** pretpostavlja: jednog ili više merchant-a, marketplace / master-sub model, terminal ID, MID, određenu banku, određeni payment gateway, određeni API, webhook, callback niti tehnologiju.

## 2.7 BP-05 – Obrada ishoda transakcije

**Status:** USVOJENO uz UPDATE

Modul mora prihvatiti i obraditi **server-confirmed gateway result**, bez vezivanja za konkretan mehanizam.

Digital Kotor **ne nagađa**. Browser return = **NOT AUTHORITATIVE**.

Mogući mehanizmi ostaju OPEN: webhook; callback; API status check; drugi ugovoreni mehanizam. **Ne birati** dok ne postoji bankovna/gateway dokumentacija.

### Četiri poslovna statusa (Korak 6)

1. **U obradi** — gateway proces pokrenut, rezultat nepoznat.
2. **Uspješna** — gateway pouzdano potvrdio uspješno plaćanje.
3. **Neuspješna** — gateway proces pokrenut i potvrđeno da plaćanje nije izvršeno.
4. **Otkazana** — gateway proces pokrenut i gateway potvrdio otkazivanje.

**SUPERSEDE** kao V1 poslovni statusi: Kreirana; U toku; pending; Greška.

Korisnička poruka „status trenutno nije moguće potvrditi“ **nije** peti status. Transakcija ostaje **U obradi**.

Vrijeme samo **ne** mijenja status. Admin **Provjeri status** ako gateway podržava; admin **ne** bira rezultat. Ako status-check nije podržan: ostaje U obradi dok se rezultat ne utvrdi ugovorenim mehanizmom.

Kontradiktorni gateway rezultati: ne last-response-wins. Pravilo nije tehnički dizajnirano.

### Evidencija (ogrančenje, ne šema)

Buduća implementacija mora omogućiti evidenciju najmanje: Digital Kotor transaction ID; vrsta plaćanja; račun; iznos; snapshot uplatioca; vrijeme gateway start-a; trenutni status; gateway reference kada postoji.

Odnos internog ID-a i korisničkog prikaza **nije** propisan.

Struktura baze **ne** projektuje se ovom odlukom.

### Idempotentnost (zahtjev, ne dizajn ključeva)

Jedna korisnička potvrda: max jedna transakcija; max jedan gateway attempt.

Dupli request istog zahtjeva: no new transaction.

Ponovljeni isti gateway callback: no duplicate effect.

Novi pokušaj nakon Neuspješna/Otkazana: nova transakcija + novi snapshot.

Ključevi/tokeni se **ne** definišu u ovoj verziji.

## 2.8 BP-06 – Potvrda o izvršenom plaćanju

**Status:** USVOJENO uz UPDATE

Sistem mora generisati potvrdu nezavisno od konkretnog gateway-a, samo za status **Uspješna**.

Potvrda:

* jeste dokaz uspješno izvršene konkretne EP transakcije;
* **nije** fiskalni račun;
* **nije** rješenje;
* **nije** potvrda da je konkretna obaveza izmirena.

PDF: **YES** (poslovni zahtjev). Tehnički generator, layout i storage **nisu** usvojeni.

Minimalni sadržaj: snapshot uplatioca; vrsta; račun; iznos; svrha; poziv/model/šifra ako se koriste; datum/vrijeme; DK transaction ID; gateway reference ako postoji; status Uspješna; disclaimer.

Email: automatski samo Uspješna. Fail **ne** mijenja finansijski status. Resend na current valid account email, bez izmjene snapshot-a.

Potvrda **ne** smije sadržati osjetljive kartične podatke.

## 2.9 BP-07 – Izvor obaveznih podataka za elektronsko plaćanje

**Status:** USVOJENO uz SUPERSEDE

Podaci nisu hardkodirani. Konfiguracija vrste/računa + kanonski profil.

| Podatak | Kanonski V1 izvor |
|---------|-------------------|
| Iznos | Korisnik unosi. EUR. > 0. Max 2 decimale. Nema univerzalnog min/max dok gateway/konkretno plaćanje ne zahtijeva. |
| Provizija | Snosi Opština Kotor. Korisnik: no additional fee. Terećenje = potvrđeni iznos. |
| Primalac / uplatilac prikaz | Snapshot iz current profile. Korisnik ne unosi drugi identitet. |
| Račun | Katalog. Nije ručni unos. |
| Poziv na broj | Per vrsta/račun: system generated; user input; optional; N/A. |
| Model i šifra plaćanja | Sistemski kontrolisani. Nije proizvoljni unos. |
| Svrha | Sistem formira osnovni tekst prema vrsti plaćanja. |

**SUPERSEDE:** fiksni iznos; iznos iz izvornog sistema; predloženi iznos; svrha iz izvornog sistema kao V1 default; primalac iz izvornog sistema.

## 2.10 BP-08 – Životni ciklus transakcije

**Status:** USVOJENO uz SUPERSEDE

Implementacija mora podržati state machine sa tačno četiri statusa, bez ručne izmjene statusa od administratora ili korisnika.

### BP-08.1 – Granica nastanka — SUPERSEDE

**SUPERSEDE:** zapis prije preusmjeravanja; početni status Kreirana.

Transakcija počinje kao **U obradi** tek kada Digital Kotor pouzdano utvrdi da je konkretni pokušaj **accepted/started by payment gateway**.

Ako gateway proces nije pokrenut: **NO TRANSACTION**. To **nije** Neuspješna ni Otkazana.

Tehnički način utvrđivanja „accepted/started“ ostaje OPEN (gateway contract).

### BP-08.2 – Statusi — SUPERSEDE

Tačno: U obradi; Uspješna; Neuspješna; Otkazana.

Jedan status u svakom trenutku. Konačni: Uspješna, Neuspješna, Otkazana.

### BP-08.3 – Promjena statusa — UPDATE

Status mijenja isključivo sistem na osnovu verifikovanog gateway događaja. Ručna izmjena zabranjena. Promjena se evidentiše (vrijeme, izvor). Format audit zapisa nije usvojen.

### BP-08.4 – Prelazi — SUPERSEDE

Kanonski: U obradi → Uspješna | Neuspješna | Otkazana.

**SUPERSEDE:** Kreirana → U toku.

Nakon konačnog stanja nema dalje promjene **istog** zapisa. Novi pokušaj = nova transakcija.

### BP-08.5 – Potvrda izvornom sistemu — SUPERSEDE za V1

V1 **ne** dostavlja potvrdu izvornom informacionom sistemu kao dio kanonskog toka.

P-08 ostaje: izvorni sistem ostaje mjerodavan. To **nije** outbound integracija u V1.

## 2.11 BP-09 – Istorija transakcija i pregled plaćanja

**Status:** USVOJENO uz UPDATE

Bez izmjene ili brisanja kroz regularne funkcionalnosti.

### BP-09.1 – Pravo pristupa

Korisnik: ONLY OWN. Administrator platforme: sve, uz audit pregleda.

### BP-09.2 – Lista

Minimum: datum/vrijeme; vrsta plaćanja; iznos; status; Digital Kotor transaction ID.

Osjetljivi card podaci se ne prikazuju.

### BP-09.3 – Filteri

Kriterijumi (period, status, vrsta, ID, iznos) ostaju funkcionalni zahtjev. UI/paginacija nisu usvojeni.

### BP-09.4 – Detalj — UPDATE

Detalj je informativan. Snapshot. Gateway reference kada postoji.

**SUPERSEDE:** prikaz statusa dostave potvrde izvornom sistemu kao V1 polje.

### BP-09.5 – Retention — UPDATE

**SUPERSEDE** kao zatvoreno pravilo: „transakcije se trajno čuvaju“.

User account lifecycle **ne briše** automatski finansijsku istoriju.

Rok retention / deletion / anonymization: **PRE-PRODUCTION LEGAL / REGULATORY REVIEW REQUIRED**. TS ne izmišlja rok.

---

# EP-TS-001 / 3. Arhitektura rješenja

**Status:** NIJE USVOJENO

Poglavlje je rezervisano. Arhitektura se ne projektuje u ovoj fazi.

Naslijeđena ograničenja (nisu dizajn):

* UR-01 — računi iz konfiguracionog izvora, ne hardkod, ne ručni unos;
* BP-04 — jedna apstraktna gateway integracija; konfiguracija odvojena od koda;
* BP-05 — server-confirmed result; mehanizam OPEN;
* BP-08 — četiri statusa; granica gateway start;
* kartični podaci se ne čuvaju u Digital Kotor.

---

# EP-TS-001 / 4. Model podataka

**Status:** NIJE USVOJENO

Finalna šema, tabele i migracije se **ne** izmišljaju.

Kada model bude usvajan: vrsta plaćanja 1..N računa; availability na vrsti i eventualno na računu; snapshot uplatioca; četiri statusa; immutability nakon gateway start-a; korišćeni račun se ne briše.

Postojeći stub `payments.status` default `pending` **nije** kanonski V1 status. Application stub se u ovom paketu **ne** mijenja.

---

# EP-TS-001 / 5. Integracije

**Status:** NIJE USVOJENO

Konkretan gateway, banka, merchant model, API, webhook, callback, status-check i protokoli **ne** biraju se ovom specifikacijom.

V1 **ne** projektuje integraciju sa izvornim sistemima Opštine za preuzimanje obaveze niti outbound potvrdu izvornom sistemu.

`PLATFORM USER MODEL CORRECTIVE = COMPLETE` (application-level). TS ne implementira user model.

`PRODUCTION LEGACY DATA CLEANUP = OPEN PRE-PRODUCTION` (production COUNT-ovi; legal-entity `resident`; NULL FL/Preduzetnik residential; `ex-non-resident`).

Declare-on-use ostaje poslovni ugovor (samo FL/Preduzetnik). UI aktivacija i EP application nisu predmet ovog dokumenta.

---

# EP-TS-001 / 6. Sigurnost

**Status:** NIJE USVOJENO

Poglavlje je rezervisano.

Naslijeđeno ograničenje: Digital Kotor ne prikuplja, ne obrađuje i ne čuva osjetljive kartične podatke. Tehničko-pravni review gateway-a ostaje OPEN (EP-PO-001).

---

# EP-TS-001 / 7. Interfejsi i API

**Status:** NIJE USVOJENO

API-ji, rute i interfejsi se ne projektuju. Application stub (`PaymentsController`, payment routes, Blade) ostaje neizmijenjen u ovom paketu.

---

# EP-TS-001 / 8. Ne-funkcionalni zahtjevi

**Status:** NIJE USVOJENO

Poglavlje je rezervisano. Idempotentnost je poslovni zahtjev (2.7); tehnički ključevi nisu usvojeni.

---

# EP-TS-001 / 9. Plan implementacije

**Status:** NIJE USVOJENO

Poglavlje je rezervisano.

`APPLICATION DEVELOPMENT = LOCAL ONLY`

`PRODUCTION APPLICATION DEPLOY = NOT APPROVED`

Dokumentacione izmjene mogu se commitovati i pushovati. To **nije** application deploy.

---

# Tehničke otvorene tačke (nije Korak 6 reopen)

Status svih: **OPEN PRE-PRODUCTION DEPENDENCY**

1. gateway protocol;
2. callback / webhook / status contract;
3. gateway data contract;
4. gateway data retention set;
5. min/max limits ako postoje;
6. long-running U obradi resolution;
7. reversal / refund / chargeback contract;
8. legal / privacy retention;
9–12. production platform user-data cleanup (EP-BM-001 / 11);
13. final mapping 17/41 na kanonske user types (`FINAL 17/41 USER CATEGORY MAPPING = OPEN`).

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana početna verzija 0.1. Unesena ograničenja P-01–P-08 i F-01. Tehnička poglavlja ostavljena kao NIJE USVOJENO. |
| 2026-07-27 | Verzija 0.2 — UR-01: uplatni računi = konfiguracioni podaci; zabrana hardkodiranja; Katalog ≠ šifrarnik. |
| 2026-07-27 | Verzija 0.3 — BP-01/BP-02/BP-03: jedinstveni izvor vrsta uplata; različiti izvori podataka; nepromjenjivost potvrđene transakcije. |
| 2026-07-27 | Verzija 0.4 — BP-04: jedna apstraktna gateway integracija; konfiguracija odvojena od koda; bez pretpostavki o konkretnoj implementaciji. |
| 2026-07-27 | Verzija 0.5 — BP-05: prihvatanje i obrada statusa ishoda transakcije; bez API/webhook/DB pretpostavki. |
| 2026-07-27 | Verzija 0.6 — BP-06: generisanje potvrde nezavisno od gateway-a; bez PDF/e-mail/formata. |
| 2026-07-27 | Verzija 0.7 — BP-07: izvor obaveznih podataka (BP-07.1 do BP-07.5) kao konfiguracija vrste uplate. |
| 2026-07-27 | Verzija 0.8 — BP-08: životni ciklus transakcije (BP-08.1 do BP-08.5); state machine; audit; potvrda izvornom sistemu. |
| 2026-07-27 | Verzija 0.9 — EP-PATCH-BM-008A: redakcijsko usklađivanje BP-05/BP-06/BP-08. |
| 2026-07-27 | Verzija 0.9.1 — EP-PATCH-BM-008B: evidencija bilježi trenutni status transakcije. |
| 2026-07-27 | Verzija 1.0 — BP-09: istorija transakcija i pregled plaćanja (BP-09.1 do BP-09.5). |
| 2026-07-27 | Verzija 1.0.1 — EP-PATCH-BM-009A: BP-06↔BP-09 (istorija); terminologija identifikatora. |
| 2026-08-17 | Verzija 1.0.2 — Dokumentacioni corrective: oznaka EP-TS-001; namespace EP-*; naziv modula e-Plaćanje. Statusi NIJE USVOJENO zadržani. Bez novih tehničkih odluka. |
| 2026-08-20 | Verzija 1.0.3 / EP-PATCH-TS-001 — Nasljeđivanje zatvorenog Koraka 6. SUPERSEDE stale business contract. Poglavlja 3–9 ostaju NIJE USVOJENO. Bez izbora gateway mehanizma i bez finalne DB šeme. |
| 2026-08-20 | Verzija 1.0.4 / EP-PATCH-TS-002 — Platform user-model zavisnost usklađena sa kanonskih 8 tipova. Application corrective COMPLETE; production data cleanup OPEN. Mapping ostaje OPEN. |
| 2026-08-20 | Verzija 1.0.5 / EP-PATCH-TS-003 — Faza 1 domain/schema foundation evidentirana kao IMPLEMENTED (local/test). Katalog/availability/gateway/UI ostaju NOT IMPLEMENTED. |
| 2026-08-20 | Verzija 1.0.6 / EP-PATCH-TS-004 — Faza 2 catalog admin evidentirana kao IMPLEMENTED locally. Availability, 17/41 podaci, user flow i gateway ostaju NOT IMPLEMENTED. |
| 2026-08-20 | Verzija 1.0.7 / EP-PATCH-TS-005 — Faza 3 availability engine evidentirana kao IMPLEMENTED locally. Stvarna 17/41 matrica, user flow i gateway ostaju NOT IMPLEMENTED. |
| 2026-08-21 | Verzija 1.0.8 / EP-PATCH-TS-006 — Faza 4 user payment flow evidentirana kao IMPLEMENTED locally do preview-a. Purpose/model/code/reference nijesu izmišljeni. Gateway ostaje NOT IMPLEMENTED. |
| 2026-08-21 | Verzija 1.0.9 / EP-PATCH-TS-007 — Faza 5 local initiation + Fake Gateway lifecycle evidentirana kao IMPLEMENTED locally. Bankart nije implementiran. Provider contract ostaje OPEN. |
| 2026-08-21 | Verzija 1.0.10 / EP-PATCH-TS-008 — Faza 6A provider-neutral gateway hardening IMPLEMENTED locally. Bankart adapter BLOCKED BY EXTERNAL DEPENDENCY. |
