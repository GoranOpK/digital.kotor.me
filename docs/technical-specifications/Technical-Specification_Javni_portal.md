# Digital Kotor
# Technical Specification
## Javni portal

**Feature ID:** FT-001  
**Oznaka dokumenta:** TS-009  
**Funkcionalna cjelina:** Javni portal Kalendara kulture  
**Modul:** Kalendar kulture  
**Status dokumenta:** Stable
**Verzija:** 1.0.5
**Datum:** 2026-08-06

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-31 | Faza 1: dokumentovane usvojene odluke IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B i TD-TS9-01. Usklađeno sa BM PATCH-045 i FS PATCH-FS-047. Bez SQL, API ugovora, Laravel koda i migracija. Bez izmjene implementacije. |
| 0.2.0 | 2026-07-31 | Faza 2: dokumentovane usvojene odluke PO-TS9-06A–PO-TS9-06D (Hero, istaknuti, statistike, lista ispod kalendara). Usklađeno sa BM PATCH-046 i FS PATCH-FS-048. Faza 1 odluke neizmijenjene. Bez izmjene implementacije. |
| 0.3.0 | 2026-07-31 | Faza 3: dokumentovane usvojene odluke PO-TS9-07A–PO-TS9-07E (Manifestacije na javnom portalu). Usklađeno sa BM PATCH-047 i FS PATCH-FS-049. Faze 1–2 neizmijenjene. Bez izmjene implementacije. |
| 0.5.0 | 2026-07-31 | Final Review: završna dokumentaciona revizija (sljedivost, terminologija, granice TS-003/004/005, baseline sekcije Detalji događaja i Arhiva događaja bez novih PO). Faze 1–3 neizmijenjene. Nije v1.0.0. Bez izmjene implementacije. |
| 1.0.0 | 2026-07-31 | Stable release. Objavljena stabilna verzija TS-009. Bez izmjene poslovnih, funkcionalnih ili tehničkih pravila. Bez izmjene implementacije. |
| 1.0.1 | 2026-08-01 | CR-002 / IS-001 Faza 2: URL ugovor `month=YYYY-MM`; klik treće statističke kartice; prioritet filtera date → week → month; isti skup podataka kartica/lista; nevalidan `month` se ignoriše. Bez izmjene implementacije. |
| 1.0.2 | 2026-08-01 | CR-003 / IS-001 Faza 2: URL ugovor `q`, `category`, `location` (PO-CR3-01…08); filter zona; AND sa datumskim mehanizmom; aktivni filteri i reset. Bez izmjene implementacije. |
| 1.0.3 | 2026-08-01 | CR-004A / IS-001 Faza 3: javni statusi događaja (Predstoji / U toku / Završen / Otkazan); izračunata stanja; badge na karticama i Detaljima; PO-CR4A-01…04. Bez novih statusa baze. Bez izmjene implementacije. |
| 1.0.4 | 2026-08-01 | Statusno usklađenje CR-004A nakon implementacije; bez izmjene tehničkih i poslovnih pravila. |
| 1.0.5 | 2026-08-06 | CR-004B / IS-001 Faza 3: javni prikaz otkazanih; portalna Arhiva ≠ interni `archived`; status ostaje `cancelled`; javni skupovi `published`\|`cancelled`; §7.2; §3.2/§5.3 statistike; PO-CR4B-01…10. Bez migracija. Bez izmjene BR-065 / BM-DG-04. Bez javne dostupnosti `archived`. Bez izmjene implementacije. |

---

# Svrha dokumenta

Ovaj dokument opisuje tehničku specifikaciju funkcionalne cjeline **Javni portal** u okviru FT-001 – Kalendar kulture.

TS-009:

* ne uvodi nova poslovna pravila van usvojenih BM/FS;
* ne predstavlja implementaciju;
* ne definiše SQL, migracije, Laravel kod ni konkretne API ugovore;
* dokumentuje usvojene product i informaciono-arhitektonske odluke kao referentni okvir za naredne tehničke i implementacione faze.

Izvori istine:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-11 BM-PK-01–BM-PK-28, BM-05, BM-AR-02; PATCH-045–PATCH-048)
* `docs/functional-specifications/Functional-Specification.md` (§5.1–§5.4, §5.13 BR-102–BR-117 i BR-255–BR-269; PATCH-FS-047–PATCH-FS-049)
* usvojene odluke faze 1: IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B, TD-TS9-01
* usvojene odluke faze 2: PO-TS9-06A, PO-TS9-06B, PO-TS9-06C, PO-TS9-06D
* usvojene odluke faze 3: PO-TS9-07A, PO-TS9-07B, PO-TS9-07C, PO-TS9-07D, PO-TS9-07E
* usvojene odluke CR-003: PO-CR3-01 … PO-CR3-08 (filteri Pretrage i pregleda: `q`, `category`, `location`)
* usvojene odluke CR-004A: PO-CR4A-01 … PO-CR4A-05 (javni statusi / status badge)
* usvojene odluke CR-004B: PO-CR4B-01 … PO-CR4B-10 (javni prikaz otkazanih događaja)
* granice entiteta: TS-005 (Manifestacija), TS-003 (Događaj), TS-004 (Održavanje) — TS-009 ne duplicira njihova poslovna pravila
* `docs/features/Feature-Registry.md`
* `docs/METHODOLOGY.md`

### Terminologija (kanonski nazivi u TS-009)

| Pojam | Značenje u dokumentaciji |
|-------|--------------------------|
| Kalendar kulture | Modul / portal |
| Početna | Početna stranica portala |
| Pretraga i pregled | Centralna lista + filteri (raniji UI naziv: „Pregled događaja“) |
| Detalji događaja | Stranica jednog događaja |
| Manifestacije | Zasebna cjelina (lista) |
| Detalji manifestacije | Stranica jedne Manifestacije (+ program) |
| Arhiva događaja | Stranica arhive |
| Održavanje | Poslovni entitet (TS-004); u dokumentaciji se ne zamjenjuje pojmom „Termin“ |
| Termin | Isključivo vremenski atributi Održavanja (datum/vrijeme); nije entitet |
| Kategorija | Primarna klasifikacija događaja |
| Oznake | Dodatna klasifikacija događaja (BM-08 / TS-007) |
| Tagovi | Metapodaci medija (BM-09); nisu sinonim za Oznake; nisu V1 UI |
| Statusne oznake / status badge | Javni prikaz **izračunatog** javnog stanja događaja: Predstoji, U toku, Završen, Otkazan (CR-004A / §7.1). Nisu statusi baze. **Odgođen** nije javni status Događaja. |

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | Usvojeno |
| 2. Informaciona arhitektura i prikazi | Usvojeno |
| 3. Pretraga i pregled — filteri | Usvojeno |
| 4. Tehnička napomena: ruta `cultural-calendar.day` | Usvojeno |
| 5. Početna stranica — Hero, istaknuti, statistike, lista | Usvojeno |
| 6. Manifestacije (javni portal) | Usvojeno |
| 7. Detalji događaja (baseline) | Usvojeno |
| 7.1 Javni statusi događaja — badge (CR-004A) | Usvojeno |
| 8. Arhiva događaja (baseline) | Usvojeno |
| 9. Arhitektonski principi (šire) | Planirano — naredne faze |
| 10. Tokovi i URL ugovor (detalj) | Planirano — naredne faze |
| 11. Integracije sa TS-003…TS-008 (detalj) | Planirano — naredne faze |
| 12. Model podataka / upiti | Planirano — naredne faze |
| 13. Nefunkcionalni zahtjevi | Planirano — naredne faze |
| 14. Granice V1 (Out of Scope) | Planirano — naredne faze |
| 15. Otvorena pitanja | Planirano — naredne faze |
| 16. Matrica sljedivosti | Usvojeno |
| 17. Napomene za implementaciju | Usvojeno (ograničeno) |

---

# Pravila upravljanja ovim dokumentom

1. TS-009 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz TS-009.
4. Princip **IA-01**: evolucija postojećeg javnog portala; bez redizajna i bez nove strukture stranica van usvojenih odluka.
5. Izmjene usvojenog sadržaja evidentiraju se novom verzijom dokumenta i odgovarajućim PATCH-om BM/FS, gdje je primjenjivo.
6. Odluke faza 1–3 ostaju važeće.
7. TS-009 opisuje **isključivo javni portal**; poslovni model entiteta Manifestacija ostaje u BM-05 / TS-005; Događaj u TS-003; Održavanje u TS-004.
8. Detalji događaja i Arhiva događaja pokriveni su postojećim BM/FS pravilima (§7–§8); bez zasebnih PO-TS9-* za te stranice.

---

# 1. Pregled funkcionalne cjeline

## 1.1 Svrha

Javni portal Kalendara kulture je javni dio modula namijenjen pregledu, pretrazi i korišćenju javno objavljenog kulturnog sadržaja (BM-PK-01, BR-102).

## 1.2 Obuhvat faze 1

Faza 1 obuhvata isključivo:

* načelo evolutivnog razvoja (IA-01);
* preimenovanje i ulogu stranice „Pretraga i pregled“ (PO-TS9-03A);
* filtere na toj stranici (PO-TS9-04A);
* zadržavanje postojećih prikaza bez novih ekrana (PO-TS9-05A);
* listu kao jedini način prikaza na „Pretrazi i pregledu“ i kalendar samo na početnoj (PO-TS9-05B);
* tehničku klasifikaciju rute `cultural-calendar.day` (TD-TS9-01).

## 1.3 Obuhvat faze 2

Faza 2 obuhvata isključivo usvojene odluke za **početnu stranicu**:

* Hero / uvodna sekcija (PO-TS9-06A);
* Istaknuti događaji (PO-TS9-06B);
* Statistike (PO-TS9-06C);
* Lista ispod kalendara (PO-TS9-06D).

Ne obuhvata: pretragu/filtere (već faza 1), detalj događaja, arhivu, urednički portal. Manifestacije na portalu su faza 3.

## 1.4 Obuhvat faze 3

Faza 3 obuhvata isključivo usvojene odluke za **Manifestacije na javnom portalu**:

* Manifestacije kao zasebna cjelina i navigacija (PO-TS9-07A);
* Lista Manifestacija (PO-TS9-07B);
* Detalji manifestacije (PO-TS9-07C);
* Program Manifestacije (PO-TS9-07D);
* Veza Manifestacija ↔ Događaji na portalu (PO-TS9-07E).

Poslovni model entiteta Manifestacija (lifecycle, kardinalnost, uslovi objave) ostaje u BM-05 / TS-005. TS-009 definiše **isključivo javni prikaz i navigaciju**.

## 1.5 Van obuhvata faze 1–3 (dokumentacioni)

* detaljan URL ugovor filtera (imena parametara, format datuma) — naredna faza;
* implementacija filtera, rename navigacije, klikabilnih statistika, dugmeta „Prikaži sve događaje“, praznog stanja istaknutih;
* implementacija cjelina Manifestacije na portalu (lista, Detalji manifestacije, program, navigacija);
* newsletter UI (TS-011);
* urednički portal (TS-010);
* potpuni model upita prema novom domenu (Održavanje, Manifestacija, Lokacija katalog, …).

## 1.6 Zavisnosti

| Zavisnost | Uloga u odnosu na TS-009 |
|-----------|---------------------------|
| TS-003 Događaj | Izvor javne verzije događaja; isticanje; Detalji događaja + blok veze ka MF |
| TS-004 Održavanje | Održavanja (termini kao vremenski atributi) i lokacije u prikazu; unosi u programu MF |
| TS-005 Manifestacija | Entitet MF; javni prikaz liste / Detalja manifestacije / programa (bez dupliciranja lifecycle pravila) |
| TS-006 Lokacije | Filter i prikaz lokacija |
| TS-007 Kategorije i oznake | Filter kategorije; prikaz kategorije i oznaka |
| TS-008 Mediji | Prikaz fotografija (naslovna / fallback); tagovi medija nisu V1 UI |
| TS-010 Urednički portal | Nije dio javnog portala; Urednik označava istaknute |
| TS-011 Newsletter | Povezano; van usvojenog obuhvata TS-009 |

---

# 2. Informaciona arhitektura i prikazi

## 2.1 IA-01 — Evolutivni razvoj

| Odluka | IA-01 |
|--------|--------|
| BM | BM-PK-16, BM-AR-02 |
| FS | BR-255 |

**Pravilo:** cilj nije redizajn javnog portala, već evolucija postojećeg rješenja kroz minimalne i strogo neophodne izmjene. Zadržavaju se postojeća struktura i korisnički tokovi.

## 2.2 PO-TS9-05A — Zadržavanje postojećih prikaza

| Odluka | PO-TS9-05A |
|--------|------------|
| BM | BM-PK-19 |
| FS | BR-258 |

Zadržavaju se postojeći prikazi javnog portala. Ne uvode se novi ekrani radi proširenja IA van usvojenih odluka.

Referentni javni prikazi (postojeća struktura):

| Prikaz | Napomena |
|--------|----------|
| Početna (`cultural-calendar.index`) | Hero, statistike, mjesečni kalendar, lista ispod kalendara, istaknuti, newsletter UI, kontakt |
| Pretraga i pregled (ranije „Pregled događaja“, `cultural-calendar.events`) | Centralna lista + filteri (PO-TS9-03A / 04A) |
| Manifestacije (nova cjelina, PO-TS9-07A) | Lista + Detalji manifestacije + program |
| Arhiva događaja | Prošli / arhivski prikaz u skladu sa BM-PK-13 / BR-114 (§8) |
| Detalji događaja | Puni prikaz jednog događaja; blok veze ka Manifestaciji (PO-TS9-07E / §7) |

## 2.3 PO-TS9-03A — Pretraga i pregled

| Odluka | PO-TS9-03A |
|--------|------------|
| BM | BM-PK-17 |
| FS | BR-256 |

Stranica koja je u navigaciji/implementaciji nosila naziv **„Pregled događaja"** preimenuje se u **„Pretraga i pregled"**.

Predstavlja **centralno mjesto** za pretragu i pregled događaja.

## 2.4 PO-TS9-05B — Lista vs kalendar

| Odluka | PO-TS9-05B |
|--------|------------|
| BM | BM-PK-20 |
| FS | BR-259 |

* „Pretraga i pregled“ koristi **isključivo prikaz liste**.
* Ne uvodi se dodatni kalendarski prikaz na toj stranici.
* Mjesečni kalendar ostaje **isključivo na početnoj stranici**.

---

# 3. Pretraga i pregled — filteri

## 3.1 PO-TS9-04A

| Odluka | PO-TS9-04A |
|--------|------------|
| BM | BM-PK-18 |
| FS | BR-257 |

| Zahtjev | Vrijednost |
|---------|------------|
| Položaj | Sastavni dio stranice „Pretraga i pregled“ |
| Vidljivost | Uvijek vidljivi |
| Filteri | datum; kategorija; lokacija; manifestacija |
| Kombinovanje | Dozvoljeno |
| Reset | Opcija „Poništi filtere“ |
| Stanje | Aktivni filteri u URL parametrima |

## 3.2 URL ugovor — datumski filteri sa statistika (CR-002)

Ulazi sa početne (statistike, „Prikaži sve događaje“) koriste postojeću rutu `cultural-calendar.events` (`GET /kalendar-kulture/pregled-dogadjaja`) sa query parametrima:

| Parametar | Format | Izvor | Semantika skupa |
|-----------|--------|-------|-----------------|
| `date` | `YYYY-MM-DD` | Kartica Danas; „Prikaži sve“ sa izabranim danom | Javno dostupni događaji (`published` \| `cancelled`) koji se preklapaju sa tim danom |
| `week_start` + `week_end` | `YYYY-MM-DD` (oba) | Kartica Ove sedmice | Javno dostupni događaji (`published` \| `cancelled`) koji se preklapaju sa periodom |
| `month` | `YYYY-MM` | Kartica Izabrani mjesec | Javno dostupni događaji (`published` \| `cancelled`) koji se preklapaju sa cijelim mjesecom |

Primjer mjesečnog filtera:

`/kalendar-kulture/pregled-dogadjaja?month=2026-08`

### Prioritet (filteri se ne kombinuju)

Ako je prisutno više od jednog datumskog mehanizma, primjenjuje se **isključivo** prvi po prioritetu:

1. `date`
2. `week_start` + `week_end` (oba moraju biti validna)
3. `month`

### Nevalidan `month`

* Prihvata se svaki validan `YYYY-MM`.
* Nevalidna vrijednost **ne** izaziva HTTP grešku; parametar se **ignoriše**; prikazuje se standardna stranica „Pretraga i pregled“ (bez mjesečnog filtera).

### Usklađenost broja kartice i liste (CR-002)

Za filter `month`:

* broj na trećoj statističkoj kartici i lista na „Pretrazi i pregledu“ predstavljaju **isti skup**: sve **javno dostupne** događaje (`published` \| `cancelled`) koji se **preklapaju** sa izabranim mjesecom;
* **ne** primjenjuje se ograničenje „samo od danas“ / samo budući događaji na tom ulazu.

Brojači **Danas** / **Ove sedmice** / **Izabrani mjesec** i pregledi `date` / `week` / `month` koriste isti skup javno dostupnih događaja (`published` \| `cancelled`) za odgovarajući vremenski opseg. Nema novih filtera ni URL parametara — status **Otkazan** razlikuje se badge-om (CR-004B).

### Naslov stranice

Glavni naslov ostaje **„Pretraga i pregled“**.

Mjesečni kontekst prikazuje se kao aktivni filter ili podnaslov, npr.:

**Izabrani mjesec: Avgust 2026**

(lokalizovani naziv mjeseca + godina).

---

## 3.3 URL ugovor i filter zona — ne-datumski filteri (CR-003)

| Odluke | PO-CR3-01 … PO-CR3-08 |
|--------|------------------------|
| BM | BM-PK-06, BM-PK-07, BM-PK-18 |
| FS | BR-107, BR-108, BR-257 |
| IS | IS-001 Faza 2 / CR-003 |

Dokumentuje implementacioni ugovor za tekstualnu pretragu, kategoriju i lokaciju na stranici „Pretraga i pregled“, u granicama **postojećeg** modela događaja. **Bez** filtera Manifestacije (Faza 5) i **bez** Oznaka (Faza 4+).

### 3.3.1 Query parametri (PO-CR3-01)

Ruta ostaje `cultural-calendar.events` (`GET /kalendar-kulture/pregled-dogadjaja`).

| Parametar | Format | Obavezan | Semantika |
|-----------|--------|----------|-----------|
| `q` | string (tekst) | ne | Tekstualna pretraga |
| `category` | tačna vrijednost iz `CulturalEvent::CATEGORIES` | ne | Filter po kategoriji |
| `location` | tačna nenull/neprazna lokacija iz objavljenih događaja | ne | Filter po lokaciji |

Postojeći datumski parametri (`date`, `week_start`, `week_end`, `month`) ostaju kako u §3.2.

### 3.3.2 Tekstualna pretraga `q` (PO-CR3-02)

Pretražuje (case-insensitive, djelimično poklapanje u granicama implementacije):

* `naslov`
* `opis`
* `lokacija`

**Ne** pretražuje: `kategorija`, `status`, datume, vrijeme, `featured`, Manifestacije, Oznake, medijske tagove, interne identifikatore.

Prazan ili nedostajući `q` ne primjenjuje tekstualni filter.

### 3.3.3 Kategorija `category` (PO-CR3-03)

* UI: **dropdown** (nema slobodnog unosa).
* Izvor opcija: `CulturalEvent::CATEGORIES`.
* Vrijednost u URL-u mora odgovarati jednoj od dozvoljenih kategorija; nevalidna vrijednost se **ignoriše** (bez HTTP greške; bez aktivnog filtera kategorije).

### 3.3.4 Lokacija `location` (PO-CR3-04)

* UI: **dropdown** (nema slobodnog unosa).
* Izvor opcija: **jedinstvene** vrijednosti `lokacija` među **objavljenim** događajima.
* Sortiranje opcija: A–Z.
* Isključuju se `NULL` i prazne vrijednosti.
* Nevalidna / nepostojeća vrijednost se **ignoriše** (bez HTTP greške).

### 3.3.5 Filter logika i kombinovanje (PO-CR3-05)

**Datumski mehanizmi** (isti prioritet kao §3.2; istovremeno aktivan **samo jedan**):

1. `date`
2. `week_start` + `week_end`
3. `month`
4. default (standardni prikaz bez aktivnog datumskog filtera)

**Ne-datumski filteri** `q`, `category`, `location`:

* kombinuju se međusobno **AND** logikom;
* kombinuju se **AND** sa aktivnim datumskim mehanizmom (ili default skupom).

Datumski mehanizmi se **međusobno ne kombinuju** (§3.2).

### 3.3.6 Filter zona UI (PO-CR3-07)

Na stranici „Pretraga i pregled“ filter zona sadrži:

* polje teksta (`q`);
* dropdown kategorije (`category`);
* dropdown lokacije (`location`);
* dugme **Pretraži**.

Pravila:

* **GET** forma;
* bez AJAX-a;
* bez automatskog submit-a pri promjeni polja;
* Enter u tekstualnom polju = Pretraži;
* **datumski filteri ne ulaze** u filter zonu (ostaju ulazi sa početne / URL, §3.2).

Filter zona je uvijek vidljiva (PO-TS9-04A / BM-PK-18).

### 3.3.7 Aktivni filteri i reset (PO-CR3-06)

* Prikazuju se aktivni filteri (uključujući ne-datumske; datumski kontekst ostaje kako je već usvojeno — npr. podnaslov mjeseca / naslov za dan/sedmicu).
* Svaki aktivni filter ima kontrolu **×** koja uklanja **samo taj** filter (ostali query parametri ostaju).
* Postoji akcija **„Poništi sve filtere“** koja vodi na:

`/kalendar-kulture/pregled-dogadjaja`

(bez query parametara).

### 3.3.8 State persistence (PO-CR3-08)

* Forma **uvijek** prikazuje trenutno stanje URL-a (popunjena polja = aktivni query).
* Filteri ostaju popunjeni nakon: pretrage, paginacije (`withQueryString`), povratka (`back`) sa Detalja događaja, i ponovnog otvaranja liste sa sačuvanim URI-jem.

### 3.3.9 Responsive i pristupačnost

* Filter zona mora biti upotrebljiva na mobilnom i desktop prikazu unutar postojećeg `kk-shell` layouta.
* Kontrole moraju imati odgovarajuće labele; tipkovnički tok: fokus polja → Enter / Pretraži; × i „Poništi sve filtere“ dostupni bez miša.
* Bez uvođenja novog ekrana ili redizajna portala (IA-01).

### 3.3.10 Van obuhvata CR-003

* Filter Manifestacije;
* Oznake;
* nove rute, migracije, izmjene modela / ENUM-a;
* AJAX / live search;
* slobodni unos kategorije ili lokacije.

---

# 4. Tehnička napomena: ruta `cultural-calendar.day` (TD-TS9-01)

| Odluka | TD-TS9-01 |
|--------|-----------|
| FS | BR-260 |
| BM | (tehnička klasifikacija; nije poslovna funkcionalnost BM-11) |

## 4.1 Klasifikacija

Ruta `cultural-calendar.day` (`GET /kalendar-kulture/dan/{date}`):

* **nije** dio referentne informacione arhitekture javnog portala;
* predstavlja **internu tehničku podršku** administratorskom toku (`kk_admin` / Urednik: klik na dan u kalendaru → redirect na kreiranje događaja);
* **ne** tretira se kao poslovna funkcionalnost niti kao dio javnog korisničkog toka.

## 4.2 Činjenice iz postojeće implementacije (referenca)

| Stavka | Vrijednost |
|--------|------------|
| Definicija | `routes/web.php` — `cultural-calendar.day` |
| Handler | `CulturalCalendarController@day` |
| View | `resources/views/cultural-calendar/day.blade.php` |
| Javni tok | Građanin sa kalendara ide na `cultural-calendar.index?date=…`, ne na `.day` |
| Admin tok | Link sa kalendara na `.day` → redirect `cultural-events.create` |

Ova napomena ne nalaže izmjenu koda u okviru faze 1–2 dokumentovanja.

---

# 5. Početna stranica — faza 2 (PO-TS9-06A–06D)

Referentni view: `resources/views/cultural-calendar/index.blade.php`  
Referentni handler: `CulturalCalendarController@index`  
Raspored sekcija (postojeći, zadržava se): Hero → statistike → (kalendar + lista ispod | istaknuti) → newsletter/kontakt.

## 5.1 PO-TS9-06A — Hero sekcija

| Odluka | PO-TS9-06A |
|--------|------------|
| BM | BM-PK-21 |
| FS | BR-261, §5.1 FR-001–FR-005 |

| Zahtjev | Vrijednost |
|---------|------------|
| Položaj | Sastavni dio početne; postojeći vizuelni identitet |
| Tip | Statički |
| Administracija | Nije uređiv iz administracije |
| Podaci | Ne koristi bazu |
| CTA / promo / rotacija / video | Nema |
| Namjena | Isključivo identitet modula Kalendara kulture |

## 5.2 PO-TS9-06B — Istaknuti događaji

| Odluka | PO-TS9-06B |
|--------|------------|
| BM | BM-PK-15 |
| FS | BR-117, BR-262 |

| Zahtjev | Vrijednost |
|---------|------------|
| Položaj | Postojeće mjesto (desni stub uz kalendar); postojeći raspored |
| Maksimum | 3 istaknuta u jednom trenutku |
| Uslov | Javno objavljeni **i** aktuelni |
| Izbor | Urednik; bez automatske selekcije sistema |
| Kartica | Postojeći izgled: naslovna fotografija, datum, vrijeme, lokacija (ako postoji), naslov, kratak opis, link na detalj |
| Prazno stanje | Neutralno; **bez** administrativnih poruka na javnom portalu |

## 5.3 PO-TS9-06C — Statistike

| Odluka | PO-TS9-06C |
|--------|------------|
| BM | BM-PK-22 |
| FS | BR-263, §5.2 |

| Kartica | Ponašanje |
|---------|-----------|
| Danas | Klik → „Pretraga i pregled“ sa `date` = današnji datum |
| Ove sedmice | Klik → „Pretraga i pregled“ sa `week_start` / `week_end` za tekuću sedmicu |
| Izabrani mjesec | Label = **naziv** trenutno izabranog mjeseca u kalendaru (ne „Ovog mjeseca“); klik → „Pretraga i pregled“ sa `month=YYYY-MM` za taj mjesec (CR-002; vidi §3.2) |

Dodatno:

* vrijednost 0 ne ukida klikabilnost;
* brojači uključuju **javno dostupne** događaje (`published` \| `cancelled`) u odgovarajućem vremenskom skupu (CR-004B / BR-270);
* postojeće mjesto na početnoj;
* za `month`: broj na kartici i rezultati liste moraju biti isti skup (preklapanje sa mjesecom; bez ograničenja „samo od danas“) — CR-002 / §3.2;
* nema novih filtera ni URL parametara; status **Otkazan** razlikuje se postojećim badge-om.

## 5.4 PO-TS9-06D — Lista ispod kalendara

| Odluka | PO-TS9-06D |
|--------|------------|
| BM | BM-PK-23 |
| FS | BR-264, §5.3 |

| Režim | Naslov | Sadržaj |
|-------|--------|---------|
| Datum nije izabran | „Naredni događaji“ | Najviše **3** naredna događaja |
| Datum izabran | „Događaji za izabrani datum“ | Svi događaji za taj datum |

| Stavka | Vrijednost |
|--------|------------|
| Kartice | Postojeći izgled |
| Dugme | „Prikaži sve događaje“ na kraju liste |
| Dugme bez datuma | → „Pretraga i pregled“ **bez** datumskog filtera |
| Dugme sa datumom | → „Pretraga i pregled“ **sa** istim datumskim filterom |
| Prazno | Postojeća poruka o praznom stanju |

---

# 6. Manifestacije (javni portal) — faza 3

> **Granica:** Poslovna pravila entiteta Manifestacija (BM-05 / TS-005), Događaja (TS-003) i Održavanja (TS-004) se ovdje ne dupliciraju. Ova sekcija definiše isključivo javni prikaz i navigaciju (PO-TS9-07A–07E).

## 6.1 PO-TS9-07A — Zasebna cjelina

| Odluka | PO-TS9-07A |
|--------|------------|
| BM | BM-PK-24 (takođe BM-PK-04, BM-PK-08) |
| FS | BR-265 (takođe BR-105, BR-109) |

| Stavka | Vrijednost |
|--------|------------|
| Položaj | Zasebna sadržajna cjelina Portala |
| Kategorije | Ne predstavljaju se kroz kategorije događaja |
| Navigacija | Stavka „Manifestacije“ u glavnoj navigaciji |
| Obuhvat | Lista + Detalji manifestacije + program |
| Redizajn | Ne; samo nova funkcionalna cjelina za usvojeni entitet (BM-05) |

## 6.2 PO-TS9-07B — Lista Manifestacija

| Odluka | PO-TS9-07B |
|--------|------------|
| BM | BM-PK-25 |
| FS | BR-266 |

| Stavka | Vrijednost |
|--------|------------|
| Vidljivost | Samo javno objavljene i javno dostupne Manifestacije |
| Sortiranje | (1) datum početka, (2) naziv |
| Paginacija | 12 po stranici, standardna |
| Kartica | Naslovna fotografija; naziv; period; kratak opis; broj objavljenih događaja u programu; link „Detalji manifestacije“ |
| V1 | Bez pretrage; bez filtera |
| Prazno | Neutralna poruka |

## 6.3 PO-TS9-07C — Detalji manifestacije

| Odluka | PO-TS9-07C |
|--------|------------|
| BM | BM-PK-26 |
| FS | BR-267 |

| Polje / stavka | Vrijednost |
|----------------|------------|
| Osnovno | Naslovna fotografija; naziv; period; Organizator (ako postoji); web stranica (ako postoji); opis |
| Lokacija | Prikaži ako je dostupna kao javna informacija; MF nema sopstvenu lokaciju (BM-MF-16 / TS-005); lokacije događaja u programu |
| Program | Ispod osnovnih informacija; ako nije javno dostupan — odgovarajuća poruka |
| V1 Out of Scope | Galerije; video; dijeljenje; rezervacije; komentari; dodatne multimedije |

## 6.4 PO-TS9-07D — Program Manifestacije

| Odluka | PO-TS9-07D |
|--------|------------|
| BM | BM-PK-27, BM-MF-13 |
| FS | BR-268, BR-192 |

| Stavka | Vrijednost |
|--------|------------|
| Grupisanje | Po datumima |
| Sortiranje | (1) datum, (2) vrijeme, (3) naziv |
| Unos | Svako Održavanje zasebno: vrijeme; naziv; lokacija (ako postoji); link „Detalji događaja“ |
| Završeni | Ostaju prikazani |
| Otkazani | Ostaju prikazani uz statusnu oznaku „Otkazano“ |
| Bez vremena | Oznaka „Vrijeme nije definisano“ |
| Bez programa | Odgovarajuća poruka |
| Nacrti / na odobrenju / vraćeni | Nisu javno vidljivi (BR-192) |

## 6.5 PO-TS9-07E — Veza Manifestacija ↔ Događaji

| Odluka | PO-TS9-07E |
|--------|------------|
| BM | BM-PK-28 (kardinalnost: BM-MF-03 / BM-DG-02; brisanje: BM-MF-14, BM-MF-15) |
| FS | BR-269 (takođe BR-093, BR-094, §5.4.2) |

| Stavka | Vrijednost |
|--------|------------|
| Kardinalnost | 1 MF → N događaja; događaj ≤ 1 MF; događaj može biti bez MF — **pravila entiteta:** BM-05 / TS-005, BM-04 / TS-003 (ovdje samo portalna navigacija) |
| Uloga | MF = programski okvir; događaj = osnovni poslovni entitet |
| Detalji događaja | Blok „Ovaj događaj je dio manifestacije“ + naziv + period + „Detalji manifestacije“ |
| Detalji manifestacije | Program → „Detalji događaja“ |
| Navigacija | Dvosmjerna |
| Ostala mjesta | Događaji ostaju u Pretrazi i pregledu, kalendaru, statistikama, Arhivi događaja |
| Uklanjanje / arhiviranje MF | Ne briše događaje (BM-MF-14 / BM-MF-15) |

---

# 7. Detalji događaja (baseline)

> Stranica „Detalji događaja“ pokrivena je usvojenim BM/FS pravilima. TS-009 ne duplicira lifecycle Događaja (TS-003), Održavanja (TS-004) ni Manifestacije (TS-005). **Javni status badge:** §7.1 (CR-004A / PO-CR4A-01…05). **Javni prikaz otkazanih:** §7.2 (CR-004B / PO-CR4B-01…10).

| Referenca | Sadržaj |
|-----------|---------|
| BM | BM-PK-05, BM-PK-09–BM-PK-14, BM-PK-28 |
| FS | §5.4, BR-106, BR-110–BR-115, BR-269, BR-270–BR-274 |
| PO (portal) | PO-TS9-07E (blok veze ka Manifestaciji); PO-CR4A-01…05 (badge); PO-CR4B-01…10 (otkazani) |

Portalni obuhvat (referenca, ne nova pravila):

* naslov i osnovni identitet događaja;
* fotografija / fallback (BM-PK-12 / BR-113 / TS-008);
* Održavanja sa terminima i lokacijama (BM-PK-09–10 / BR-110–111 / TS-004);
* Kategorija i Oznake (BM-PK-11 / BR-112 / TS-007) — **Oznake ≠ Tagovi**;
* statusne oznake / status badge prema §7.1 (BM-PK-13 / BR-114; CR-004A);
* opis i javno objavljeni podaci (BM-PK-05 / BR-106);
* informativni blok Manifestacije kada postoji veza (BM-PK-28 / BR-269).

---

## 7.1 Javni statusi događaja — status badge (CR-004A)

| Stavka | Vrijednost |
|--------|------------|
| CR | CR-004A (IS-001 Faza 3) |
| Odluke | PO-CR4A-01 … PO-CR4A-05 |
| BM | BM-PK-13 (prikaz; bez novih BM pravila) |
| FS | BR-114 (prikaz; bez novih BR) |
| Status | Dokumentaciono usvojeno; implementacija Implemented (0f73240) |

### 7.1.1 Javna stanja (PO-CR4A-01)

Na javnom portalu građanima se prikazuju **isključivo** javna stanja događaja:

| Javno stanje | Tip |
|--------------|-----|
| Predstoji | Izračunato |
| U toku | Izračunato |
| Završen | Izračunato |
| Otkazan | Mapirano sa internog `cancelled` |

Interni statusi sistema **ne prikazuju se** građanima kao labela:

* Draft / Nacrt
* Na odobrenju
* Published / Objavljen
* Archived / Arhiviran

**Otkazan** prikazuje se kada je interni status događaja `cancelled` i ima **apsolutni prioritet** nad svim ostalim javnim stanjima.

**Odgođen** nije status Događaja. Ostaje isključivo status Održavanja (BM-TR / FS §5.7.3 / TS-004) i **ne prikazuje se** kao javni status Događaja.

Predstoji, U toku i Završen **nisu** statusi baze podataka — isključivo izračunata javna stanja. CR-004A ne uvodi nove statuse baze niti mijenja životni ciklus Događaja ili Održavanja.

### 7.1.2 Mjesto prikaza (PO-CR4A-02)

Javni status događaja prikazuje se na **svim** javnim prikazima događaja:

* Početna stranica (kartice: istaknuti, lista ispod kalendara, i drugi prikazi kartica događaja);
* Pretraga i pregled;
* Arhiva događaja;
* Detalji događaja.

Na svim prikazima koristi se **isti tekst**, **isti badge** i **ista logika** određivanja statusa.

### 7.1.3 Pravila određivanja (PO-CR4A-03)

Prioritet određivanja:

1. Otkazan
2. Predstoji
3. U toku
4. Završen

**Korak 1 — Otkazan**

Ako je interni status događaja `cancelled`, javni status je uvijek **Otkazan**, bez obzira na datum i vrijeme.

**Korak 2 — vremenska stanja** (samo ako događaj nije otkazan)

Proračun koristi vremensku zonu aplikacije Digital Kotor. Polja postojećeg modela: `datum_od`, `datum_do`, `vrijeme`, `vrijeme_do`.

#### A) Višednevni događaj (`datum_do` postoji)

* prije početka → **Predstoji**
* od početka do kraja perioda → **U toku**
* nakon završetka → **Završen**

Ako postoje `vrijeme` i `vrijeme_do`, ona preciziraju početak prvog i završetak posljednjeg dana.

Za višednevni događaj **bez** vremena (cjelodnevni):

* prije `datum_od` → **Predstoji**
* `datum_od`–`datum_do` uključivo → **U toku**
* nakon `datum_do` → **Završen**

#### B) Jednodnevni događaj sa vremenom završetka (`vrijeme_do` postoji)

* prije vremena početka → **Predstoji**
* od početka do završetka → **U toku**
* nakon završetka → **Završen**

#### C) Jednodnevni događaj bez vremena završetka

* prije početka → **Predstoji**
* od početka do kraja kalendarskog dana → **U toku**
* od narednog dana → **Završen**

#### D) Događaj bez definisanog vremena

Datum se tretira kao cjelodnevni događaj (vidi A / C prema tome da li postoji `datum_do`).

**PO-CR4A-05 (implementirano ponašanje):** Ako se javni status ne može pouzdano odrediti zbog nekonzistentnih podataka, badge se ne prikazuje (bez exceptiona / „Unknown“). Ne mijenja pravila A–D iznad.

### 7.1.4 Vizuelni prikaz (PO-CR4A-04)

| Prikaz | Pozicija badge-a |
|--------|------------------|
| Kartice događaja | Gornji desni ugao fotografije (ili fallback slike) |
| Detalji događaja | Neposredno ispod naslova događaja, prije osnovnih informacija o datumu, vremenu i lokaciji |

Na svim javnim prikazima koristi se **jedinstven** vizuelni izgled badge-a (isti položaj u okviru odgovarajućeg tipa prikaza, isti tekst i ista logika).

### 7.1.5 Van obuhvata CR-004A

* novi statusi baze / migracije / izmjene lifecycle Događaja ili Održavanja;
* uvođenje **Odgođen** kao statusa Događaja ili javnog badge-a događaja;
* domen Održavanja / Oznaka / Manifestacije (Faza 4+);
* izmjena kriterijuma ulaska u Arhivu (BM-DG-04 → Faza 6 ili zaseban CR);
* redizajn portala van badge pozicija iz PO-CR4A-04;
* proširenje javne dostupnosti statusa `cancelled` — to je **CR-004B** (§7.2).
* Proširenje javne dostupnosti internog statusa `archived` nije dio CR-004B i ostaje van njegovog obuhvata.

---

## 7.2 Javni prikaz otkazanih događaja (CR-004B)

| Stavka | Vrijednost |
|--------|------------|
| CR | CR-004B (IS-001 Faza 3) |
| Odluke | PO-CR4B-01 … PO-CR4B-10 |
| BM | BM-PK-13 (prikaz); BM-PK-15 (Istaknuti); BM-DG-05 (prava — bez izmjene); BM-DG-04 (lifecycle — bez izmjene) |
| FS | BR-270–BR-274; BR-001, BR-002, BR-004, BR-114, BR-116 (usklađeno); BR-063 / BR-065 (bez izmjene) |
| Status | Dokumentaciono usvojeno; implementacija Planned |

### 7.2.0 Planirani termin (flat model)

CR-004B ne mijenja način izračunavanja završetka događaja. Koristi postojeća polja flat modela: `datum_od`, `datum_do`, `vrijeme`, `vrijeme_do`.

Za **portalnu Arhivu** (dnevni kriterijum, kao u postojećem kodu):

* ako postoji `datum_do`, završna granica je taj datum;
* ako ne postoji `datum_do`, koristi se `datum_od`;
* događaj je „prošao planirani termin“ kada je završni datum **strogo prije** današnjeg kalendarskog dana (vremenska zona aplikacije).

CR-004B ne uvodi minutnu preciznost za ulazak u portalnu Arhivu i ne uvodi model Održavanja.

### 7.2.1 Javni skupovi dostupnosti

**Portalna Arhiva ≠ interni status `archived`.**

#### A) Aktivne javne površine

Za početnu (kalendar, događaji dana, naredni), Pretragu i pregled, Detalje i direktni URL — uz postojeće vremenske uslove konkretne površine — javno dostupni događaji su:

* `published`, **ili**
* `cancelled` (dok planirani termin **nije** prošao / dok događaj ulazi u aktivni vremenski skup te površine).

Istaknuti isključuju `cancelled` (PO-CR4B-03 / §7.2.3).

#### B) Portalna Arhiva

Portalna Arhiva uključuje:

* `published` + prošao planirani termin, **ili**
* `cancelled` + prošao planirani termin.

Interni status `archived` se **ne otvara** javnosti kroz CR-004B.

Za otkazani događaj (`cancelled`): interni status **ostaje** `cancelled` i prije i nakon ulaska u portalnu Arhivu. CR-004B **ne** dokumentuje ni implementira prelaz `cancelled → archived`. BR-065 / BM-DG-04 ostaju neizmijenjeni (buduća zavisnost).

Javni badge za `cancelled` je uvijek **Otkazan** (CR-004A / §7.1).

Bez migracija, novih modela i novih tabela.

### 7.2.2 Površine prikaza do planiranog termina (PO-CR4B-02)

Otkazani događaj do planiranog termina prikazuje se na:

* početnoj stranici (uključujući kalendar, događaje dana, naredne događaje);
* Pretrazi i pregledu;
* Detaljima događaja;
* direktnom URL-u (`show` dozvoljava `cancelled`).

Nakon isteka planiranog termina otkazani se **ne** prikazuje među narednim događajima; prelazi na portalnu Arhivu (§7.2.6).

### 7.2.3 Istaknuti (PO-CR4B-03)

Sekcija Istaknutih **isključuje** `cancelled` iz javnog prikaza.

Flag „Istaknut“ se **ne mijenja** otkazivanjem — samo isključenje iz query/prikaza Istaknutih.

### 7.2.4 Sistemsko obavještenje na Detaljima (PO-CR4B-05)

Na Detaljima otkazanog događaja prikazuje se fiksni tekst:

> Ovaj događaj je otkazan i neće biti održan u planiranom terminu.

Tekst nije uređiv i nije dio opisa. Status badge (§7.1) ostaje.

### 7.2.5 Pretraga (PO-CR4B-06)

Otkazani učestvuju u postojećoj Pretrazi i pregledu. Bez novih filtera, URL parametara ili search moda.

### 7.2.6 Portalna Arhiva (PO-CR4B-04)

Nakon isteka planiranog termina otkazani događaj:

* **zadržava** interni status `cancelled`;
* prestaje da se prikazuje među narednim događajima;
* prikazuje se u **portalnoj** Arhivi na osnovu datuma (§7.2.0 / §7.2.1 B);
* u javnom prikazu ostaje označen statusom **Otkazan**.

Portalna Arhiva je javna vremenska površina i **ne** podrazumijeva promjenu internog statusa u `archived`. CR-004B ne implementira `cancelled → archived`. BR-065 / BM-DG-04 se ne mijenjaju.

### 7.2.7 Van obuhvata CR-004B

* Odgođen;
* Faza 4 / Faza 5;
* novi modeli / migracije / tabele;
* izmjena BR-065 / BM-DG-04;
* javna dostupnost internog statusa `archived`;
* prelaz `cancelled → archived`;
* izmjena prava otkazivanja (BR-063 / BM-DG-05);
* izmjena flaga Istaknut;
* novi filteri / URL parametri / search modovi.

---

# 8. Arhiva događaja (baseline)

> Stranica „Arhiva događaja“ pokrivena je usvojenim BM/FS. Pravila **kada** Događaj prelazi u interni status Arhiviran ostaju u BM-04 / TS-003 (BM-DG-04); TS-009 definiše portalni prikaz. **Javni status badge:** §7.1. **Otkazani u portalnoj Arhivi:** §7.2 / CR-004B (javni status ostaje **Otkazan**).

| Referenca | Sadržaj |
|-----------|---------|
| BM | BM-PK-13 (takođe BM-DG-04 za interno arhiviranje entiteta — bez izmjene u CR-004B) |
| FS | BR-114, BR-274 (takođe BR-065 za sistemsko arhiviranje — bez izmjene) |
| PO (portal) | PO-CR4A-01…05 (badge); PO-CR4B-01…10 (javni prikaz otkazanih) |

Portalni obuhvat (referenca):

* prikaz otkazanih i arhiviranih događaja u skladu sa BM-PK-13 / BR-114 / BR-270–BR-274;
* status mora biti jasno prikazan korisniku putem javnog status badge-a (§7.1): **Otkazan** za otkazane događaje (javni prikaz), bez obzira na interni lifecycle; za ostale javno dostupne događaje izračunata stanja Predstoji / U toku / Završen;
* nakon isteka planiranog termina otkazani ulaze u portalnu Arhivu (CR-004B / §7.2.6);
* navigacija ka Detaljima događaja u skladu sa BM-PK-05 / BR-106; `show` dozvoljava `cancelled`.

---
# 9–15. Planirano (naredne faze)

Sljedeća poglavlja ostaju za naredne faze TS-009 (tehnička dubina, ne nova poslovna pravila):

* širi arhitektonski principi;
* detaljni tokovi i URL ugovor;
* detaljne integracije sa TS-003–TS-008;
* model podataka / upiti;
* NFR;
* Out of Scope V1 (dopuna);
* otvorena pitanja.

---

# 16. Matrica sljedivosti

| Odluka / tema | BM | FS | TS-009 |
|---------------|----|----|--------|
| IA-01 | BM-PK-16, BM-AR-02 | BR-255 | §2.1 |
| PO-TS9-03A | BM-PK-17 | BR-256 | §2.3 |
| PO-TS9-04A | BM-PK-18 | BR-257 | §3, §3.2, §3.3 |
| PO-TS9-05A | BM-PK-19 | BR-258 | §2.2 |
| PO-TS9-05B | BM-PK-20 | BR-259 | §2.4 |
| TD-TS9-01 | — (tehnička) | BR-260 | §4 |
| PO-TS9-06A | BM-PK-21 | BR-261, §5.1 FR-001–FR-005 | §5.1 |
| PO-TS9-06B | BM-PK-15 | BR-117, BR-262 | §5.2 |
| PO-TS9-06C | BM-PK-22 | BR-263, §5.2 | §5.3, §3.2 |
| CR-002 (`month`) | BM-PK-22 | BR-263 | §3.2, §5.3 |
| CR-003 (`q` / `category` / `location`) | BM-PK-06, BM-PK-07, BM-PK-18 | BR-107, BR-108, BR-257 | §3.3 |
| PO-CR3-01 … PO-CR3-08 | BM-PK-18 (granice postojećeg modela) | BR-257 | §3.3 |
| CR-004A (javni status badge) | BM-PK-13 | BR-114 | §7.1 |
| PO-CR4A-01 … PO-CR4A-05 | BM-PK-13 | BR-114 | §7.1 |
| CR-004B (javni prikaz otkazanih) | BM-PK-13, BM-PK-15 | BR-270–BR-274; BR-001, BR-002, BR-004, BR-114, BR-116 | §7.2, §8 |
| PO-CR4B-01 … PO-CR4B-10 | BM-PK-13, BM-PK-15 | BR-270–BR-274 | §7.2 |
| PO-TS9-06C (CR-004B usklađenje skupa) | BM-PK-22 | BR-263 | §3.2, §5.3 |
| PO-TS9-06D | BM-PK-23 | BR-264, §5.3 | §5.4 |
| PO-TS9-07A | BM-PK-24 | BR-265 | §6.1 |
| PO-TS9-07B | BM-PK-25 | BR-266 | §6.2 |
| PO-TS9-07C | BM-PK-26 | BR-267 | §6.3 |
| PO-TS9-07D | BM-PK-27, BM-MF-13 | BR-268, BR-192 | §6.4 |
| PO-TS9-07E | BM-PK-28 | BR-269, §5.4.2 | §6.5 |
| Detalji događaja (baseline) | BM-PK-05, BM-PK-09–14, BM-PK-28 | §5.4, BR-106, BR-110–115, BR-269 | §7 |
| Arhiva događaja (baseline) | BM-PK-13 | BR-114 | §8 |
| Pretraga / filteri (opšte) | BM-PK-06, BM-PK-07 | BR-107, BR-108 | §2–§3 |
| Načini prikaza | BM-PK-08 | BR-109 | §2.4, §6 |

Granice (bez dupliciranja u TS-009): lifecycle Događaja → TS-003; Održavanje/Termin → TS-004; Manifestacija entitet → TS-005.

---

# 17. Napomene za implementaciju

* Verzije do v1.0.0 (uključujući) su **dokumentacione**; ne mijenja se kod u okviru tih verzija. Od v1.0.1 CR-002 dokumentuje ugovor za implementaciju (IS-001 Faza 2); kod se ne mijenja u dokumentacionom koraku.
* Pri budućoj implementaciji: poštovati IA-01 (minimalne izmjene postojećeg portala).
* Rename navigacionog labela „Pregled događaja“ → „Pretraga i pregled“ (PO-TS9-03A) — isporučeno u CR-001.
* Filteri (PO-TS9-04A) pripadaju stranici Pretraga i pregled; datumski ulazi sa statistika: §3.2 / CR-002 (`month=YYYY-MM`); ne-datumski filteri: §3.3 / CR-003 (`q`, `category`, `location`).
* Internu podršku dan-view toka ne tretirati kao javni ekran u IA (TD-TS9-01).
* CR-001 (IS-001 Faza 1): terminologija; Hero; istaknuti max 3 + neutralno prazno; Danas/Ove sedmice klikabilne; label mjeseca; naredni max 3; „Prikaži sve događaje“.
* CR-002 (IS-001 Faza 2): treća kartica klikabilna sa `month`; isti skup kartica/lista; prioritet filtera; podnaslov „Izabrani mjesec: …“.
* CR-003 (IS-001 Faza 2): filter zona `q` / `category` / `location`; AND sa datumskim mehanizmom; aktivni filteri (×); „Poništi sve filtere“; GET forma; state persistence (PO-CR3-01…08).
* CR-004A (IS-001 Faza 3): javni status badge Predstoji / U toku / Završen / Otkazan na Početnoj, Pretrazi i pregledu, Arhivi i Detaljima; izračunata stanja; `cancelled` → Otkazan (prioritet); Odgođen nije status Događaja (PO-CR4A-01…05 / §7.1). Dokumentacija `614706c`; implementacija `0f73240`.
* CR-004B (IS-001 Faza 3; Planned): javni prikaz otkazanih — aktivne površine i portalna Arhiva (vremenska; ≠ interni `archived`); status ostaje `cancelled`; Istaknuti isključuju cancelled; show dozvoljava cancelled; sistemsko obavještenje; statistike/datumski skupovi uključuju cancelled; javni status uvijek Otkazan (PO-CR4B-01…10 / §7.2). Bez migracija; bez javne dostupnosti `archived`; bez izmjene BR-065 / BM-DG-04.
* Faza 3 CR/impl (šire): navigacija Manifestacije; lista; Detalji manifestacije; program; blok veze na Detaljima događaja — **van** CR-004A / CR-004B (Faza 5 / domen).
* Detalji događaja / Arhiva događaja: uskladiti prikaz sa BM-PK-05/13 i BR-106/114/270–274; status badge prema §7.1; dostupnost otkazanih prema §7.2; ne uvoditi paralelna lifecycle pravila.
* Ne duplicirati TS-003 / TS-004 / TS-005 u portalskom sloju.
