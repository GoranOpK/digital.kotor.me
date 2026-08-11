# Digital Kotor
# Technical Specification
## Javni portal

**Feature ID:** FT-001  
**Oznaka dokumenta:** TS-009  
**Funkcionalna cjelina:** Javni portal Kalendara kulture  
**Modul:** Kalendar kulture  
**Status dokumenta:** Stable
**Verzija:** 1.0.12
**Datum:** 2026-08-11

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
| 1.0.6 | 2026-08-09 | **Faza 6A dokumentacioni PATCH:** cutover `CulturalEvent` → `CulturalEventEntry`+`CulturalOccurrence`; PO-EV-01; očuvanje UI; kartica + sortiranje + Odgođen; CAT-CUTOVER; Faza 6A/6B; V1 bez javnog `cancellation_reason`; legacy URL 404; privremeni feature flag; public query SSOT; TM-JP test matrica. Usklađeno sa BM PATCH-060 / FS PATCH-FS-060. Bez izmjene implementacije. |
| 1.0.7 | 2026-08-09 | **PO-6A11-01:** kanonski javni status Događaja (multi-OCC) — §7.1.6; razdvajanje legacy flat (§7.1.3) i canonical agregata; usklađeno sa BM-PK-34 / BR-285. Bez izmjene lifecycle / arhive. |
| 1.0.8 | 2026-08-09 | **PO-6A09-01…06:** Javna Arhiva vs interni Arhiviran — §8 / §11 / §12 / §7.2 usklađeni; archive-only query; očuvanje izvornog statusa; istorijski badge Otkazan/Završen; TM-JP-11/04. Usklađeno sa BM PATCH-062 / FS PATCH-FS-062 / BM-PK-35 / BR-286. Bez izmjene implementacije. |
| 1.0.9 | 2026-08-10 | **BM PATCH-063 / FS PATCH-FS-063 (PO-U):** ručni Organizator; Odgođeno + Prvobitni termin + razlozi; OCC cancel prikaz; Entry cancel opcion razlog javno; supersede V1 zabrane javnog `cancellation_reason`. Bez izmjene implementacije. |
| 1.0.10 | 2026-08-10 | **BM PATCH-064 / FS PATCH-FS-064:** Informativna naslovna vidljivost Odgođenog; zajednički hronološki bazen „Naredni događaji“ max 3; ranking datum; mode `planned` / `postponed_info`; tehnički tie-breaker `entry.id ASC`; bez mijenjanja calendar counts / selected-day / Pretrage / detalja / lifecycle. Bez izmjene implementacije. |
| 1.0.11 | 2026-08-11 | **PHASE 6A closeout status sync (dokumentacioni):** potvrđeno implementirano/testirano/deployovano stanje za PATCH-063, PATCH-064 i CLOSE-02/03/03A/04; planned kartica uključuje „+ još N termina“ za dodatna relevantna Planirana Održavanja; `postponed_info` bez `+N`; legacy admin CRUD surface ostaje HTTP-disabled (403) uz zadržan rollback feature-flag mehanizam. Bez izmjene poslovnih pravila. |
| 1.0.12 | 2026-08-11 | **6B-DOC-01 / PO-6B-01…05:** formalizovan V1 ugovor za `tip` filter na „Pretrazi i pregledu“ (`sve`/`dogadjaji`/`manifestacije`) i korekcija semantike filtera po tipu sadržaja (PO-6B-04), uz preciziranu MF `q` semantiku (PO-6B-05: Naziv+Opis, partial/case-insensitive, bez derived pretrage kroz program), bez agregirane lokacije Manifestacije, te razdvajanje aktivne MF liste od public detalja Arhivirane MF (bez zasebne MF Arhive u V1). Bez izmjene implementacije. |

---

# Svrha dokumenta

Ovaj dokument opisuje tehničku specifikaciju funkcionalne cjeline **Javni portal** u okviru FT-001 – Kalendar kulture.

TS-009:

* ne uvodi nova poslovna pravila van usvojenih BM/FS;
* ne predstavlja implementaciju;
* ne definiše SQL, migracije, Laravel kod ni konkretne API ugovore;
* dokumentuje usvojene product i informaciono-arhitektonske odluke kao referentni okvir za naredne tehničke i implementacione faze.

Izvori istine:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-11 BM-PK-01–BM-PK-34, BM-05, BM-AR-02; PATCH-045–PATCH-048, PATCH-051, PATCH-060, PATCH-061)
* `docs/functional-specifications/Functional-Specification.md` (§5.1–§5.4, §5.13 BR-102–BR-117, BR-255–BR-274, BR-280–BR-286, BR-296–BR-302; PATCH-FS-047–PATCH-FS-049, PATCH-FS-051, PATCH-FS-060, PATCH-FS-061, PATCH-FS-065)
* usvojene odluke faze 1: IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B, TD-TS9-01
* usvojene odluke faze 2: PO-TS9-06A, PO-TS9-06B, PO-TS9-06C, PO-TS9-06D
* usvojene odluke faze 3: PO-TS9-07A, PO-TS9-07B, PO-TS9-07C, PO-TS9-07D, PO-TS9-07E
* usvojene odluke CR-003: PO-CR3-01 … PO-CR3-08 (filteri Pretrage i pregleda: `q`, `category`, `location`)
* usvojene odluke CR-004A: PO-CR4A-01 … PO-CR4A-05 (javni statusi / status badge)
* usvojene odluke CR-004B: PO-CR4B-01 … PO-CR4B-10 (javni prikaz otkazanih događaja)
* usvojene odluke Faze 6A: PO-EV-01; PO-TS9-08A … PO-TS9-08J (cutover kanonskog modela; UI očuvanje; kartica/sortiranje/Odgođen; CAT-CUTOVER; 6A/6B; cancellation_reason V1; legacy URL; feature flag; public query SSOT)
* usvojena odluka **PO-6A11-01** (kanonski javni status Događaja / multi-OCC badge)
* usvojene odluke **PO-6B-01…05** (Tip sadržaja na Pretrazi + semantika filtera po tipu; MF `q` = Naziv+Opis; MF bez agregirane lokacije; Arhivirana MF = direct detail dostupan, bez posebne MF Arhive u V1)
* granice entiteta: TS-005 (Manifestacija), TS-003 (Događaj), TS-004 (Održavanje) — TS-009 ne duplicira njihova poslovna pravila
* `docs/features/Feature-Registry.md`
* `docs/METHODOLOGY.md`
* `docs/implementation-strategies/Implementation-Roadmap_Kalendar_kulture.md` (IR-001 — Faza 6A / 6B)

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
| 1.7 Faza 6A / 6B — granica obuhvata | Usvojeno |
| 2. Informaciona arhitektura i prikazi | Usvojeno |
| 3. Pretraga i pregled — filteri | Usvojeno |
| 3.4 Sortiranje Pretrage (Faza 6A) | Usvojeno |
| 4. Tehnička napomena: ruta `cultural-calendar.day` | Usvojeno |
| 5. Početna stranica — Hero, istaknuti, statistike, lista | Usvojeno |
| 6. Manifestacije (javni portal) | Usvojeno (implementacija = Faza 6B) |
| 7. Detalji događaja (baseline) | Usvojeno |
| 7.1 Javni statusi događaja — badge (CR-004A) | Usvojeno |
| 7.1.6 Kanonski multi-OCC javni status (PO-6A11-01) | Usvojeno |
| 7.2 Javni prikaz otkazanih (CR-004B) | Usvojeno |
| 7.3 Više Održavanja i Odgođen (Faza 6A) | Usvojeno |
| 8. Arhiva događaja (baseline) | Usvojeno |
| 9. Cutover kanonskog modela (Faza 6A) | Usvojeno |
| 10. Legacy URL i feature flag | Usvojeno |
| 11. Public query SSOT | Usvojeno |
| 12. Javna vidljivost statusa (kanonski) | Usvojeno |
| 13. Nefunkcionalni zahtjevi | Planirano — naredne faze |
| 14. Granice V1 (Out of Scope) | Usvojeno (Faza 6A) |
| 15. Otvorena pitanja | Usvojeno (nema blocker-a za 6A dokumentaciju) |
| 16. Matrica sljedivosti | Usvojeno |
| 17. Napomene za implementaciju | Usvojeno |
| 18. Test matrica Faze 6A (TM-JP) | Usvojeno (dokumentaciono; bez test koda) |

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

* dodatni URL ugovori filtera izvan usvojenih §3.2 / §3.3 (npr. napredni facet filteri);
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

## 1.7 Faza 6A / 6B — granica obuhvata (IR-001)

| Odluka | PO-TS9-08F |
|--------|------------|
| IR | IR-001 Faza 6A / 6B |
| BM/FS | BM-PK-16; BR-255; BM-PK-24–28 (6B) |

### Faza 6A — Javni portal Događaja

Obuhvata prelazak javnog portala Događaja sa legacy `CulturalEvent` na kanonski `CulturalEventEntry` + `CulturalOccurrence`, uz kanonske kataloge (`CulturalCategory`, lokacije, medija) potrebne za javni prikaz Događaja.

Faza 6A realizuje se uz **maksimalno očuvanje postojećeg izgleda** javnog portala (IA-01 / PO-TS9-08A).

**TS-005 / Manifestacije ne blokiraju Fazu 6A.**

### Faza 6B — Manifestacije

Manifestacije na javnom portalu (TS-009 §6 / PO-TS9-07A–07E; filter Manifestacije na Pretrazi) realizuju se **naknadno** kada TS-005 bude spreman za implementaciju.

Ne implementirati Manifestacije u okviru Faze 6A.

### Van Faze 6A

* implementacija Manifestacija (lista / detalj / program / navigacija / filter MF);
* slug / nova SEO URL arhitektura;
* široki frontend refactor / vizuelni redizajn;
* trajni dual-read / dual-write / sinhronizacija legacy↔kanonski;
* migracija legacy `CulturalEvent` sadržaja;
* javni prikaz `cancellation_reason`.

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
| Filteri | datum; kategorija; lokacija; tip sadržaja |
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

Dokumentuje implementacioni ugovor za ne-datumske filtere na stranici „Pretraga i pregled“: tekstualnu pretragu, kategoriju, lokaciju i tip sadržaja.

### 3.3.1 Query parametri (PO-CR3-01)

Ruta ostaje `cultural-calendar.events` (`GET /kalendar-kulture/pregled-dogadjaja`).

| Parametar | Format | Obavezan | Semantika |
|-----------|--------|----------|-----------|
| `q` | string (tekst) | ne | Tekstualna pretraga |
| `category` | tačna vrijednost iz kataloga kategorija: do cutover-a `CulturalEvent::CATEGORIES` (legacy read); nakon Faze 6A **kanonski naziv** aktivnog `CulturalCategory` (§3.3.3) | ne | Filter po kategoriji (Događaji) |
| `location` | tačna nenull/neprazna lokacija iz objavljenih događaja | ne | Filter po lokaciji (Događaji) |
| `tip` | `dogadjaji` \| `manifestacije` | ne | Tip sadržaja; bez parametra = `Sve` |

Postojeći datumski parametri (`date`, `week_start`, `week_end`, `month`) ostaju kako u §3.2.

### 3.3.2 Tekstualna pretraga `q` (PO-CR3-02)

Pretražuje (case-insensitive, djelimično poklapanje u granicama implementacije):

* `naslov`
* `opis`
* `lokacija`

**U mode-u Događaji** ne pretražuje: `kategorija`, `status`, datume, vrijeme, `featured`, Oznake, medijske tagove, interne identifikatore.

Prazan ili nedostajući `q` ne primjenjuje tekstualni filter.

### 3.3.3 Kategorija `category` (PO-CR3-03; Faza 6A / PO-TS9-08E)

* UI: **dropdown** (nema slobodnog unosa).
* **Do cutover-a (legacy read):** izvor opcija `CulturalEvent::CATEGORIES` (postojeći CR-003 ugovor).
* **Nakon cutover-a (kanonski read):** izvor opcija = aktivni zapisi `CulturalCategory`; URL vrijednost = **kanonski naziv** kategorije; `CulturalEvent::CATEGORIES` **nije** izvor (BM-PK-32 / BR-283).
* Nevalidna vrijednost se **ignoriše** (bez HTTP greške; bez aktivnog filtera kategorije).
* **Ne** uvodi se legacy alias mapa ni kompatibilnost sa starim nazivima kategorija.

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

### 3.3.10 Tip sadržaja `tip` (PO-6B-01)

`tip` je ne-datumski filter za „Pretragu i pregled“:

| URL | Semantika |
|-----|-----------|
| bez `tip` parametra | `Sve` (Događaji + Manifestacije) |
| `tip=dogadjaji` | samo Događaji |
| `tip=manifestacije` | samo Manifestacije |

Nevalidan `tip` se **ignoriše** (fail-safe, bez HTTP greške) i tretira kao podrazumijevano `Sve`.

Manifestacija se ne tretira kao vrsta Događaja; to su odvojeni poslovni entiteti (BM-05 / BM-04).

### 3.3.11 PO-6B-04 — semantika filtera po tipu sadržaja

Matrica dostupnih filtera na stranici „Pretraga i pregled“:

| Tip sadržaja | `q` | `category` | `location` | `date` | `week` | `month` |
|-------------|-----|------------|------------|--------|--------|---------|
| Sve | DA | NE | NE | NE | NE | NE |
| Događaji | DA | DA | DA | DA | DA | DA |
| Manifestacije | DA | NE | NE | NE | NE | NE |

Dodatna pravila:

* `tip=dogadjaji`: zadržava se postojeća 6A semantika (`q`, `category`, `location`, `date`, `week_start/week_end`, `month`, sortiranje i vidljivost).
* `tip` bez parametra (Sve): prikazuju se javno relevantni Događaji i Manifestacije; event-specifični filter controls nijesu dostupni; `q` se primjenjuje na oba podskupa prema njihovim pravilima (`Događaji` = §3.3.2; `Manifestacije` = §3.3.12).
* `tip=manifestacije`: prikazuju se Manifestacije; event-specifični filter controls nijesu dostupni; `q` semantika = §3.3.12.
* MF u V1 nema izvedeno filtriranje preko kategorija/lokacija/datumskih skupova povezanih Događaja/Održavanja.

### 3.3.12 PO-6B-05 — MF `q` searchable fields

Za `tip=manifestacije`, `q` pretražuje isključivo sopstvena tekstualna polja Manifestacije:

* `naziv`
* `opis`

Semantika:

* djelimično poklapanje;
* case-insensitive.

Isključenja (nije dio MF `q` pretrage):

* Organizator;
* povezani Događaji i njihovi nazivi;
* Održavanja;
* lokacije Događaja/Održavanja;
* kategorije Događaja;
* Oznake Događaja;
* izvedeni period Manifestacije;
* drugi izvedeni/agregirani podaci iz programa Manifestacije.

Ako je Opis NULL/prazan, Manifestacija i dalje učestvuje kroz pretragu po Nazivu; NULL/prazan Opis ne izaziva grešku.

Prazan/nedostajući/whitespace-only `q` ne aktivira tekstualni filter (reuse postojećeg fail-safe search patterna).

### 3.3.13 Non-applicable URL parametri (fail-safe)

Ako URL sadrži validan `tip`, ali i parametre koji nijesu primjenjivi za taj tip (`category`, `location`, `date`, `week_start`, `week_end`, `month` van `tip=dogadjaji`):

* parametri ne utiču na rezultat;
* ne izazivaju HTTP grešku;
* ne uvode implicitno derived MF filtriranje.

Pri promjeni tipa sadržaja event-specifični controls se skrivaju van `tip=dogadjaji`; eventualni preostali URL parametri tretiraju se kao non-applicable (fail-safe).

### 3.3.14 Van obuhvata CR-003

* dodatni MF-specifični filteri izvan `tip` ugovora (npr. lifecycle, period, organizator);
* Oznake;
* nove rute, migracije, izmjene modela / ENUM-a (osim cutover ugovora Faze 6A u §9);
* AJAX / live search;
* slobodni unos kategorije ili lokacije.

---

## 3.4 Sortiranje Pretrage (Faza 6A)

| Odluka | PO-TS9-08C |
|--------|------------|
| BM | BM-PK-30 |
| FS | BR-281 |

Događaji na „Pretrazi i pregledu“ sortiraju se **rastuće** prema datumu (i vremenu, kada postoji) **prvog narednog relevantnog Održavanja** (§7.3.1).

Za Događaj sa više Održavanja: dok postoji naredno relevantno Održavanje, ono određuje poziciju; kada jedno prođe, sljedeće naredno relevantno postaje ključ sortiranja.

**Ne** uvodi se korisnički izbor sortiranja (BM-PK-07 / BR-108).

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

| Odluka | PO-TS9-06D (+ PATCH-064) |
|--------|------------|
| BM | BM-PK-23, BM-PK-37 |
| FS | BR-264, BR-296, BR-297 |

| Režim | Naslov | Sadržaj |
|-------|--------|---------|
| Datum nije izabran | „Naredni događaji" | Najviše **3** Događaja iz **zajedničkog hronološkog bazena** (§5.5) |
| Datum izabran | „Događaji za izabrani datum“ | Svi događaji za taj datum po **aktivnom** OCC filteru (PATCH-063); Odgođeno OCC **ne** ulazi |

| Stavka | Vrijednost |
|--------|------------|
| Kartice | Postojeći izgled; `postponed_info` mode prikazuje „Odgođeno“ / „Prvobitni termin" (§5.5 / §7.3.4) |
| Dugme | „Prikaži sve događaje" na kraju liste |
| Dugme bez datuma | → „Pretraga i pregled“ **bez** datumskog filtera |
| Dugme sa datumom | → „Pretraga i pregled" **sa** istim datumskim filterom |
| Prazno | Postojeća poruka o praznom stanju |
| Limit | Max **3** nepromijenjen |

---

## 5.5 PATCH-064 — Informativna naslovna vidljivost Odgođenog (tehnički)

| Referenca | Sadržaj |
|-----------|---------|
| BM | BM-PK-37, BM-GL-26, BM-PK-23, BM-PK-29, BM-PK-31 |
| FS | BR-296, BR-297, BR-264, BR-280, BR-282 |
| Kod (trenutno) | `CulturalPublicEventQuery::homepageUpcomingCards` (shared pool `planned` + `postponed_info`); `CulturalPublicCardOccurrenceCriteria` ostaje planned-only za card-relevant semantiku; `CulturalCalendarController` koristi taj SSOT za naslovnu listu; `OccurrenceLifecycle::postpone` (ne mijenja `datum`) |

### 5.5.1 Semantička granica

* Odgođeno **≠** Planirano.
* Odgođeno **≠** upcoming / card-relevant (`CulturalPublicCardOccurrenceCriteria`).
* Informativna naslovna vidljivost je **poseban** homepage selection/display mehanizam.
* **Ne** proširivati `nextRelevantOccurrence()` / `CulturalPublicCardOccurrenceCriteria` da uključuju postponed.
* **Ne** mijenjati Pretragu (`orderedByNextRelevantOccurrence`), calendar counts, selected-day aktivni filter, detalj, featured, lifecycle, arhivu, newsletter.

### 5.5.2 Tipovi kandidata (jedan Entry → jedan kandidat)

**A. `mode = planned` (standardni Planirani kandidat)**

* Entry `status = published` (ili postojeći javno vidljivi skup koji već ulazi u „Naredni“ — kanonski: published sa next planned; cancelled u narednima ostaje po postojećim CR-004B pravilima ako već važi).
* Postoji najmanje jedno kartično relevantno **Planirano** OCC (`CulturalPublicCardOccurrenceCriteria`).
* `selected_occurrence` = prvo naredno relevantno Planirano OCC (`nextRelevantOccurrence`).
* `ranking_date` = `selected_occurrence.datum` (Y-m-d).
* Display = standardna kartica (§7.3.2).

**B. `mode = postponed_info` (informativno Odgođeni kandidat)**

* Entry `status = published`.
* **Nema** naredno kartično relevantno Planirano OCC.
* Postoji OCC `status = postponed` čiji je **prvobitni datum** (`cultural_occurrences.datum`) `>=` lokalni današnji datum (`config('app.timezone')`).
* `selected_occurrence` = najbliže takvo postponed OCC po `datum ASC`, zatim stabilni OCC tie-breaker `id ASC`.
* `ranking_date` = `selected_occurrence.datum` (Y-m-d) — **samo ranking/display ključ**, ne važeći termin.
* Display = informativna kartica (§5.5.6).

**Invariant unutar Entry-ja:** ako postoji planned kandidat, Entry **ne** formira `postponed_info` kandidata.

### 5.5.3 Izvor prvobitnog datuma

| Stanje | Izvor | Napomena |
|--------|-------|----------|
| Odgođeno bez novog termina | `cultural_occurrences.datum` | `OccurrenceLifecycle::postpone()` mijenja samo `status` (+ opcion `postponement_reason`); **ne** mijenja `datum` |
| Odgođeno → Planiran (`resumeWithNewTermin`) | Novi `datum` | Prestaje `postponed_info` za taj OCC; ulazi u planned selection |

**Ne** uvoditi novu kolonu za original date u PATCH-064.

### 5.5.4 Granica dana / timezone

* Lokalni poslovni datum = `Carbon::now(config('app.timezone'))->toDateString()` (postojeći pattern; default `Europe/Belgrade`).
* Podobnost postponed OCC: `DATE(datum) >= today_local` (uključujući danas).
* Sutra (`today + 1 day`) taj OCC više nije kandidat za `postponed_info`.

### 5.5.5 Zajednički hronološki bazen / sort / limit

Algoritam (obavezan redoslijed):

1. Formirati planned kandidate (query A).
2. Formirati postponed_info kandidate (query B) — samo Entry-ji **bez** planned kandidata.
3. Merge u jednu listu kandidata (`entry`, `mode`, `ranking_date`, `selected_occurrence`).
4. Sort:
   1. `ranking_date ASC`
   2. tehnički tie-breaker: `entry.id ASC` (**bez** poslovnog prioriteta `mode`)
5. `take(3)`.

**Zabranjeno:** odvojeno `take(3)` planned + `take(3)` postponed pa merge.

**Tie-breaker:** nema poslovnog značenja; samo determinističnost. Ne koristiti `mode` kao prioritet.

### 5.5.6 Display — `postponed_info` kartica

Meta zona kartice mora jasno prikazati:

* oznaku **„Odgođeno"**;
* **„Prvobitni termin: [d.m.Y]"** iz `selected_occurrence.datum`.

**Ne** prikazivati taj datum kroz isti UI kao važeći „next" termin (bez vremena lokacije kao „predstojećeg" termina osim ako UI već prikazuje lokaciju OCC-a informativno — preferirati fokus na Odgođeno + Prvobitni termin).

**„+ još N termina":** standardni indikator broji samo kartično relevantna **Planirana** OCC (`additionalRelevantOccurrencesCount`). BM/FS **ne** definišu brojanje Odgođenih kao „+ još N" u informativnom režimu. U `postponed_info` mode-u indikator **ne prikazivati** (izostaviti). To nije nova poslovna semantika broja, već izbjegavanje zbunjujućeg / nula prikaza.

### 5.5.7 Query strategija (preporuka implementacije)

**Preferirano: B — dva prefiltrirana query-ja + Collection merge/sort.**

* **Query A:** postojeći planned path (`withCardRelevantOccurrence` + sort po next planned) — bez `take(3)` dok se ne merge-uje, ili dovoljno velik candidate window.
* **Query B:** `published` Entry **without** card-relevant planned OCC; **with** postponed OCC gdje `datum >= today`; eager `occurrences` (+ category/cover/location po postojećem `$cardEager`).
* PHP merge/sort/take(3) — čitljivo, deterministično, bez miješanja sa Pretragom.

**Performance:** kandidatski set je ograničen published Entry-jima sa open planned/postponed OCC; homepage prikazuje 3. Prefilter po `whereHas`/`whereDoesntHave` je obavezan — **ne** učitavati sve published Entry-je bez OCC filtera.

Alternativa A (jedan SQL sa computed ranking) dozvoljena ako ostane čitljiva i ne dira search/calendar; nije obavezna.

### 5.5.8 View model

Controller/helper mora view-u proslijediti jednoznačan rezultat po kartici, npr. semantički:

* `mode`: `planned` | `postponed_info`
* `selected_occurrence`
* `ranking_date`

Zabranjen hack: „ako `nextRelevantOccurrence()` null, nađi bilo koji postponed".

Nova metoda SSOT (npr. `homepageNextEventsForPublicIndex(): Collection` kandidata ili Entry + attach mode) u `CulturalPublicEventQuery` (ili mali value object) — **odvojeno** od `upcomingForPublicIndex` ako taj ostaje planned-only helper; ili zamijeniti homepage call site da koristi novi ugovor. Featured i Pretraga **ne** koriste `postponed_info`.

### 5.5.9 Kalendar / selected day / Pretraga / detalj

| Površina | PATCH-064 |
|----------|-----------|
| Calendar day counts | **NO CHANGE** — postponed i dalje isključeni |
| Selected-day lista | **NO CHANGE** — `constrainCalendarActiveOccurrence` isključuje postponed |
| Pretraga sort (BR-281) | **NO CHANGE** |
| Javni detalj PATCH-063 | **NO CHANGE** (Odgođeno / Prvobitni / Napomena) |
| Featured | **NO CHANGE** (i dalje planned/aktuelni) |

---# 6. Manifestacije (javni portal) — dokumentaciona faza 3 / implementacija Faza 6B

> **Granica:** Poslovna pravila entiteta Manifestacija (BM-05 / TS-005), Događaja (TS-003) i Održavanja (TS-004) se ovdje ne dupliciraju. Ova sekcija definiše isključivo javni prikaz i navigaciju (PO-TS9-07A–07E).
>
> **Implementacija:** §6 pripada **Fazi 6B**. Faza 6A **ne** implementira Manifestacije. TS-005 **ne blokira** Fazu 6A (PO-TS9-08F).

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
| Vidljivost | Aktivna lista prikazuje samo javno dostupne Manifestacije koje nijesu Arhivirane |
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
| Lokacija | Manifestacija nema sopstvenu ni agregiranu lokaciju; na headeru/kartici se ne prikazuje MF lokacija. Lokacija se prikazuje samo po pojedinačnoj programskoj stavci kada postoji |
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

## 6.6 PO-6B-03 — Arhivirana Manifestacija (V1)

| Odluka | PO-6B-03 |
|--------|----------|
| BM | BM-MF-06, BM-MF-15 |
| FS | BR-300, BR-301, BR-302 |

| Površina | V1 ugovor |
|----------|-----------|
| Aktivna javna lista Manifestacija | Arhivirane Manifestacije se ne prikazuju |
| Direktni canonical URL detalja Manifestacije | Arhivirana Manifestacija ostaje javno dostupna (istorijski programski zapis) |
| Posebna javna lista/ruta „Arhiva Manifestacija“ | Ne uvodi se u V1 |
| Povezani Događaji | Zadržavaju sopstvenu javnu vidljivost i lifecycle semantiku; arhiva MF ih ne mijenja |

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

**Zajednički prioritet (oba izvora):**

1. Otkazan (apsolutni — interni `cancelled`)
2. zatim vremenska stanja prema aktivnom izvoru čitanja (legacy flat **ili** canonical multi-OCC)

**Korak 1 — Otkazan**

Ako je interni status događaja `cancelled`, javni status je uvijek **Otkazan**, bez obzira na datum, vrijeme i Održavanja.

**Korak 2 — vremenska stanja** (samo ako događaj nije otkazan)

Proračun koristi vremensku zonu aplikacije Digital Kotor.

#### Legacy flat (`CulturalEvent` — CR-004A baseline)

Polja: `datum_od`, `datum_do`, `vrijeme`, `vrijeme_do`.

##### A) Višednevni događaj (`datum_do` postoji)

* prije početka → **Predstoji**
* od početka do kraja perioda → **U toku**
* nakon završetka → **Završen**

Ako postoje `vrijeme` i `vrijeme_do`, ona preciziraju početak prvog i završetak posljednjeg dana.

Za višednevni događaj **bez** vremena (cjelodnevni):

* prije `datum_od` → **Predstoji**
* `datum_od`–`datum_do` uključivo → **U toku**
* nakon `datum_do` → **Završen**

##### B) Jednodnevni događaj sa vremenom završetka (`vrijeme_do` postoji)

* prije vremena početka → **Predstoji**
* od početka do završetka → **U toku**
* nakon završetka → **Završen**

##### C) Jednodnevni događaj bez vremena završetka

* prije početka → **Predstoji**
* od početka do kraja kalendarskog dana → **U toku**
* od narednog dana → **Završen**

##### D) Događaj bez definisanog vremena

Datum se tretira kao cjelodnevni događaj (vidi A / C prema tome da li postoji `datum_do`).

**PO-CR4A-05 (implementirano ponašanje):** Ako se javni status ne može pouzdano odrediti zbog nekonzistentnih podataka, badge se ne prikazuje (bez exceptiona / „Unknown“). Ne mijenja pravila A–D iznad.

#### Canonical multi-OCC (`CulturalEventEntry` + `CulturalOccurrence`)

Vidi **§7.1.6** (PO-6A11-01 / BM-PK-34 / BR-285). Legacy flat polja se **ne** koriste za kanonski Entry.

### 7.1.4 Vizuelni prikaz (PO-CR4A-04)

| Prikaz | Pozicija badge-a |
|--------|------------------|
| Kartice događaja | Gornji desni ugao fotografije (ili fallback slike) |
| Detalji događaja | Neposredno ispod naslova događaja, prije osnovnih informacija o datumu, vremenu i lokaciji |

Na svim javnim prikazima koristi se **jedinstven** vizuelni izgled badge-a (isti položaj u okviru odgovarajućeg tipa prikaza, isti tekst i ista logika).

### 7.1.6 Kanonski multi-OCC javni status (PO-6A11-01)

| Stavka | Vrijednost |
|--------|------------|
| Odluka | PO-6A11-01 |
| BM | BM-PK-34 |
| FS | BR-285 |
| Odnos | Proširuje CR-004A / §7.1 za `CulturalEventEntry` + `CulturalOccurrence`; ne mijenja legacy flat §7.1.3 A–D |

Javni badge Događaja **nije** kopija statusa pojedinačnog Održavanja. Oznake Održavanja na Detalju (Odgođeno / Otkazano / Završeno — §7.3) ostaju na nivou Održavanja.

**Otkazan:** `Entry.status = cancelled` → uvijek **Otkazan** (apsolutni prioritet).

**Objavljen Entry — agregatni prioritet:**

1. **U toku** — najmanje jedno **Planirano** Održavanje je u važećem intervalu `[početak, expiresAt]` (početak = `datum+vrijeme_od` ili `startOfDay(datum)` kada nema `vrijeme_od` / cjelodnevno; završetak = postojeći domenski `expiresAt`, uključujući kraj dana bez `vrijeme_do`).
2. **Predstoji** — nijedno Planirano nije u toku i postoji najmanje jedno buduće Planirano (`now` strogo prije početka).
3. **Završen** — Entry ima ≥1 Održavanje i nema Planiranog koje je u toku ili buduće. Uključuje vremenski istekla Planirana (čak i ako tehnički status još nije `finished`), te istorijska Završena/Otkazana Održavanja. Pojedinačno Otkazano Održavanje **ne** daje Entry badge Otkazan.
4. **Bez badge-a (`null`)** — 0 Održavanja; ili postponed-only (samo Odgođena, bez Planiranog koje omogućava pouzdano vremensko određivanje).

Odgođeno Održavanje ne ulazi u računanje vremenskog statusa Događaja. Kartična relevantnost (§7.3.1 / 6A-03) ostaje zaseban kriterijum (Planiran + nije istekao) i **ne** mijenja se ovim pravilom.

Granica „U toku“ kompatibilna je sa `isExpiredAt` (istek strogo nakon `expiresAt`).

### 7.1.5 Van obuhvata CR-004A

* novi statusi baze / migracije / izmjene lifecycle Događaja ili Održavanja;
* uvođenje **Odgođen** kao statusa Događaja ili javnog badge-a događaja;
* domen Održavanja / Oznaka / Manifestacije (Faza 4+) — **izuzev** kanonskog javnog statusa Događaja u §7.1.6 (Faza 6A / PO-6A11-01);
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
| BM | BM-PK-13 / BM-PK-35 (prikaz); BM-PK-15 (Istaknuti); BM-DG-05 (prava — bez izmjene); BM-DG-04 (lifecycle arhiviranje — na snazi) |
| FS | BR-270–BR-274; BR-286; BR-001, BR-002, BR-004, BR-114, BR-116 (usklađeno); BR-063 / BR-065 (lifecycle na snazi) |
| Status | Dokumentaciono usvojeno; kanonska Javna Arhiva = PO-6A09 / BR-286 (implementacija Planned u 6A-09) |

### 7.2.0 Planirani termin (flat model)

CR-004B ne mijenja način izračunavanja završetka događaja. Koristi postojeća polja flat modela: `datum_od`, `datum_do`, `vrijeme`, `vrijeme_do`.

Za **portalnu Arhivu** (dnevni kriterijum, kao u postojećem kodu):

* ako postoji `datum_do`, završna granica je taj datum;
* ako ne postoji `datum_do`, koristi se `datum_od`;
* događaj je „prošao planirani termin“ kada je završni datum **strogo prije** današnjeg kalendarskog dana (vremenska zona aplikacije).

CR-004B ne uvodi minutnu preciznost za ulazak u portalnu Arhivu i ne uvodi model Održavanja.

### 7.2.1 Javni skupovi dostupnosti

**Historijski (CR-004B):** portalna Arhiva nije sinonim za interni `archived`.

#### A) Aktivne javne površine

Za početnu (kalendar, događaji dana, naredni), Pretragu i pregled, aktivni Detalji i direktni URL aktivnog skupa — uz postojeće vremenske uslove konkretne površine — javno dostupni događaji su:

* `published`, **ili**
* `cancelled` (dok planirani termin **nije** prošao / dok događaj ulazi u aktivni vremenski skup te površine).

Istaknuti isključuju `cancelled` (PO-CR4B-03 / §7.2.3).

Aktivne površine **ne** čitaju interni `archived` (PO-6A09-01).

#### B) Portalna / Javna Arhiva (CR-004B + PO-6A09)

Dok je Događaj još u `published` | `cancelled` i prošao je planirani termin, Arhiva ga čita kao:

* `published` + prošao termin, **ili**
* `cancelled` + prošao termin.

**Aktivni kanonski ugovor (PO-6A09-01…06 / BM-PK-35 / BR-286 / §8):** Javna Arhiva je **poseban** archive-only query, odvojen od aktivnog `base()`. Može uključiti i `archived` zapise koji su ranije bili javni, uz **sačuvan izvorni status** (`published` ili `cancelled`). Nacrt / Na odobrenju nikada. Samo `status=archived` nije dokaz da je zapis ikada bio javni.

Za otkazani događaj: dok je interni status `cancelled`, ostaje `cancelled` i prije i nakon ulaska u Arhivu. Lifecycle **jeste** `cancelled → archived` (BR-065 / BM-DG-04). Nakon arhiviranja javni badge **Otkazan** ostaje obavezan putem sačuvanog izvornog statusa (PO-6A09-02 / PO-6A09-04).

Javni badge za `cancelled` (i arhiviran-iz-cancelled) je **Otkazan** (CR-004A / §7.1 / §8).

Bez novih filtera / URL parametara u CR-004B.

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

### 7.2.4 Sistemsko obavještenje na Detaljima (PO-CR4B-05 / PO-TS9-08G)

Na Detaljima otkazanog događaja prikazuje se fiksni tekst:

> Ovaj događaj je otkazan i neće biti održan u planiranom terminu.

Tekst nije uređiv i nije dio opisa. Status badge (§7.1) ostaje.

**PATCH-063 / BR-295 / BM-PK-36 (supersede V1 zabrane):** fiksni sistemski tekst ostaje. Ako Entry `cancellation_reason` postoji (opcion), **može se javno prikazati** kao napomena uz sistemsko obavještenje. Ako razlog nedostaje — samo sistemski tekst. Fail-closed archive pravila (PATCH-062) neizmijenjena.

### 7.2.5 Pretraga (PO-CR4B-06)

Otkazani učestvuju u postojećoj Pretrazi i pregledu. Bez novih filtera, URL parametara ili search moda.

### 7.2.6 Portalna Arhiva (PO-CR4B-04)

Nakon isteka planiranog termina otkazani događaj:

* dok je još `cancelled`, **zadržava** interni status `cancelled`;
* prestaje da se prikazuje među narednim događajima;
* prikazuje se u **portalnoj / Javnoj Arhivi** na osnovu datuma (§7.2.0 / §7.2.1 B / §8);
* u javnom prikazu ostaje označen statusom **Otkazan**.

**Historijski (CR-004B):** portalna Arhiva nije podrazumijevala promjenu u `archived`; CR-004B nije implementirao `cancelled → archived`.

**Aktivni ugovor:** BR-065 radi `cancelled → archived`. Očuvanje javnog ishoda **Otkazan** kroz arhiviranje je obavezno (PO-CR4B-09 revidiran / PO-6A09-02 / PO-6A09-04 / BR-286). Detalj archive-only query-ja: §8 / §11 / §12.

### 7.2.7 Van obuhvata CR-004B

* Odgođen;
* Faza 4 / Faza 5;
* izmjena prava otkazivanja (BR-063 / BM-DG-05);
* izmjena flaga Istaknut;
* novi filteri / URL parametri / search modovi;
* ~~javni prikaz `cancellation_reason` zabranjen~~ — **superseded** PATCH-063 / §7.2.4 / BR-295 (opcion prikaz ako postoji).

**Napomena:** globalno otvaranje svih `archived` zapisa na aktivnim površinama **nije** dio CR-004B ni PO-6A09. Kanonska Javna Arhiva (poseban query + očuvanje izvornog statusa) uređuje **§8 / PO-6A09**.

---

## 7.3 Više Održavanja i Odgođen (Faza 6A)

| Odluke | PO-TS9-08B, PO-TS9-08D |
|--------|------------------------|
| BM | BM-PK-09, BM-PK-29, BM-PK-31 |
| FS | BR-110, BR-280, BR-282 |
| Granica | TS-004 (lifecycle Održavanja — bez dupliciranja) |

### 7.3.1 Definicija „relevantnog“ Održavanja (javni portal)

Za kartični glavni termin i sortiranje Pretrage (**naredno relevantno važeće**):

* Održavanje u statusu **Planiran** čiji termin još nije prošao (prema vremenskoj zoni aplikacije i pravilima završetka iz TS-004 / CR-004A vremenske logike na nivou Održavanja);
* **Odgođeno** Održavanje **nije** glavni termin kartice niti ključ sortiranja dok postoji Planirano naredno;
* **Otkazano** Održavanje nije „važeće“ za glavni termin kartice aktivnog (neotkazanog) Događaja.

Za **Detalj Događaja** — **javno relevantna** Održavanja uključuju Planirana, Odgođena, Otkazana i Završena Održavanja javno vidljivog Događaja (BM-PK-09), uz odgovarajuće oznake statusa Održavanja gdje je usvojeno (npr. „Odgođeno“, „Otkazano“ u kontekstu MF programa / detalja).

### 7.3.2 Kartica Događaja (PO-TS9-08B / PATCH-064)

**Standardni režim (`mode = planned`):**

* Prikazuje se **prvo naredno relevantno Planirano** Održavanje (datum/vrijeme/lokacija tog Održavanja).
* Ako postoje dodatna kartično relevantna **Planirana** Održavanja: oznaka **„+ još N termina"**.
* Kartica **ne** prikazuje kompletnu listu Održavanja.
* Minimalne vizuelne izmjene; bez redizajna kartice (IA-01 / PO-TS9-08A).

**Informativni režim (`mode = postponed_info`) — samo naslovna „Naredni događaji" (§5.5):**

* Prikaz: **„Odgođeno"** + **„Prvobitni termin: [datum]"**.
* Prvobitni datum **nije** važeći termin održavanja.
* **Ne** prikazivati standardni „+ još N termina" indikator.
* Ne redefinisati `CulturalPublicCardOccurrenceCriteria` / `nextRelevantOccurrence()`.

---
### 7.3.3 Detalj Događaja

Prikazuju se **sva** javno relevantna Održavanja (BM-PK-09 / BR-110).

### 7.3.4 Odgođeno Održavanje (PO-TS9-08D / PATCH-063)

* **Detalj — Odgođeno bez novog termina:** prikazati status **„Odgođeno“**; label **„Prvobitni termin“**; postojeći originalni datum; opcion `postponement_reason` ako postoji.
* **Ne** prikazivati Odgođeno kao aktivno predstojeće Održavanje (§7.3.1).
* **Kada novi datum unesen i status Planiran:** novi datum = aktivni termin; razlog/history ostaje po TS-004; glavni prikaz **ne** tvrdi da je trenutno Odgođeno.
* * **Kartica — standardni režim:** stari odgođeni termin **nije** važeći glavni termin; kartica prikazuje prvo naredno relevantno **Planirano** Održavanje (+ „+ još N" po §7.3.2 planned).
* **Kartica — `postponed_info` (naslovna, PATCH-064):** kada nema narednog Planiranog, kartica **može** prikazati Odgođeno informativno (§5.5 / BR-296); prvobitni datum nije upcoming termin.

### 7.3.5 Otkazano Održavanje (PATCH-063 / BR-294)

* Prikaz: **„Otkazano“**; prvobitni datum; opcion OCC `cancellation_reason` ako postoji.
* Ostala aktivna Održavanja i dalje normalno prikazana.
* Entry **ne** dobija status Otkazan zbog jednog OCC cancel.
* Otkazani OCC **ne** ulazi u predstojeće aktivne termine (§7.3.1).

### 7.3.6 Prikaz Organizatora (PATCH-063 / BR-288)

* Ako postoji registrovani Organizator (`organizer_id`): postojeći prikaz.
* Inače ako postoji `organizer_manual_name`: prikazati taj naziv kao Organizator.
* Ako oba nedostaju: bez Organizatora / ne prikazivati praznu sekciju (postojeće UI pravilo).
* Fail-closed: ne smiju oba tipa biti istovremeno aktivna za isti poslovni tok (XOR — TS-003 §6.2).

---

# 8. Arhiva događaja (baseline + kanonski ugovor PO-6A09)

> Stranica „Arhiva događaja“ pokrivena je usvojenim BM/FS. Pravila **kada** Događaj prelazi u interni status Arhiviran ostaju u BM-04 / TS-003 (BM-DG-04); TS-009 definiše portalni prikaz. **Javni status badge:** §7.1. **Otkazani u portalnoj Arhivi:** §7.2 / CR-004B (javni status ostaje **Otkazan**).

| Referenca | Sadržaj |
|-----------|---------|
| BM | BM-PK-13; **BM-PK-35** (PO-6A09-01…06); BM-DG-04; BM-ST-08 |
| FS | BR-114; BR-274; **BR-286**; BR-065 / BR-066 |
| PO (portal) | PO-CR4A-01…05; PO-CR4B-01…10 (historijski); **PO-6A09-01…06** |

Portalni obuhvat (referenca):

* **Princip (PO-6A09):** Arhiviran = interni lifecycle; Javna Arhiva = istorijski pogled (BM-PK-35 / BR-286) — nije lista svih `archived`.
* Aktivne površine: samo `published`|`cancelled` — `archived` se **ne** dodaje u `base()` / `PUBLICLY_VISIBLE_STATUSES`.
* Javna Arhiva: **poseban** archive-only query; može uključiti `published`|`cancelled` (prošli) i `archived` sa sačuvanim izvornim statusom ∈ {published, cancelled}.
* Ulazak u Javnu Arhivu zahtijeva **i** dokazano prethodno javno stanje **i** istorijski kriterijum Održavanja; samo `archived_from_status` nije dovoljan.
* Očuvanje izvornog statusa pri arhiviranju (radni naziv: `archived_from_status`); zabranjen SSOT: `cancellation_reason`, OCC status, audit parsing.
* Javni badge: nema „Arhiviran"; iz cancelled → **Otkazan**; iz published → **Završen**.
* Direct URL: 200 za archive-public Entry; draft/pending/nejavni → 404; bez globalnog `archived` u `base()`.
* Kartica: posljednje istorijsko Održavanje (ne `nextRelevantOccurrence`); sort DESC po tom Održavanju (ne `archived_at` / scheduler).
* Nacrt / Na odobrenju nikada ne ulaze.

---
# 9. Cutover kanonskog modela (Faza 6A)

| Odluke | PO-EV-01; PO-TS9-08A; PO-TS9-08E |
|--------|----------------------------------|
| BM | BM-PK-16, BM-PK-32; BM-KO-11 |
| FS | BR-255, BR-283, BR-279 |
| IR | IR-001 Faza 6A |

## 9.1 SSOT nakon cutover-a

Javni portal Događaja prelazi sa legacy `CulturalEvent` na:

* `CulturalEventEntry` (Događaj)
* `CulturalOccurrence` (Održavanje)

uz `CulturalCategory` kao kanonski katalog kategorija.

Nakon završenog prelaska kanonski model je **jedini izvor istine** za javni portal Događaja.

## 9.2 Legacy sadržaj (PO-EV-01)

Postojeći `CulturalEvent` zapisi su isključivo **testni**. Ne postoji produkcijski relevantan legacy sadržaj za očuvanje.

Zato Faza 6A:

* **NE** migrira legacy Događaje;
* **NE** uvodi content migration;
* **NE** uvodi dual-write;
* **NE** uvodi dual-read;
* **NE** spaja legacy i kanonske rezultate;
* **NE** pravi trajnu sinhronizaciju između modela.

## 9.3 Očuvanje UI-ja (PO-TS9-08A)

Prelazak **nije** redizajn. Sačuvati: hero, vizuelni identitet, raspored stranica, izgled kartica, strukturu portala, dobre UI obrasce.

UI izmjene dozvoljene samo kada su neophodne radi: kanonskog modela; više Održavanja; statusa; kanonskih kategorija/lokacija/medija; eksplicitno usvojenih BM/FS pravila.

## 9.4 CAT-CUTOVER (PO-TS9-08E)

* Javni portal koristi 14 usvojenih kanonskih kategorija (`CulturalCategory`).
* Filter se puni dinamički iz aktivnog kataloga.
* Preduslov: svih 14 mora postojati u `cultural_categories` prije public cutover-a.
* Način punjenja kataloga (seed/ručno/…) **nije** predmet ovog PATCH-a; tehnički preduslov jeste.
* Bez legacy alias mape; bez migracije legacy kategorija.

---

# 10. Legacy URL i feature flag

## 10.1 Legacy URL (PO-TS9-08H)

Legacy `CulturalEvent` i kanonski `CulturalEventEntry` imaju **različite ID prostore**.

Ne uvodi se: redirect tabela; mapiranje legacy ID → kanonski ID; kompatibilnost sa starim testnim URL-ovima; migracija radi očuvanja URL-a.

Nakon prelaska ruta `/kalendar-kulture/dogadjaj/{event}` radi sa kanonskim Događajem.

Stari URL koji pokazuje na testni legacy `CulturalEvent` **smije vratiti 404**.

U Fazi 6A: **NE** slug; **NE** nova SEO URL arhitektura.

## 10.2 Privremeni feature flag (PO-TS9-08I)

Tokom implementacije, verifikacije i kratkog perioda stabilizacije Faze 6A dozvoljen je privremeni tehnički feature flag za **jedan** izvor javnog čitanja:

* `legacy` **ILI**
* `canonical`

**Nikada oba istovremeno.**

Flag služi isključivo kao rollback zaštita — **nije** trajna arhitektura.

**Zabranjeno:** dual-read; merge rezultata; dual-write; sinhronizacija legacy↔kanonski.

### Redoslijed gašenja (nakon stabilizacije)

1. kanonski read ostaje jedini javni read;
2. feature flag se uklanja;
3. legacy public read se uklanja;
4. legacy `CulturalEvent` CRUD se uklanja.

**Intermediate status (PHASE 6A-CLOSE-02):** prije koraka 4, legacy admin CRUD surface (`cultural-events.*`) je **HTTP-disabled** (403; dedicated middleware). Kod, model, tabela i views ostaju; javni rollback read preko feature flag-a ostaje. Hard removal / flag cleanup = kasniji Phase B (koraci 1–4 iznad).

Ovaj dokument **ne** implementira navedeno — samo propisuje redoslijed.

---

# 11. Public query SSOT (PO-TS9-08J)

Minimalni kanonski public query ugovor (bez velikog Repository refactora; preferirati Eloquent / postojeće obrasce):

### 11.1 Aktivni public query (`base()`)

Površine koje **moraju** dijeliti ista pravila **aktivne** javne vidljivosti:

* `index` (početna: liste, kalendar, statistike);
* Pretraga i pregled;
* featured (Istaknuti);
* aktivni dio detalja (`show` za aktivni skup).

Ne smiju međusobno različito tumačiti aktivni skup `published|cancelled`, isključenje draft/pending, niti pravila Istaknutih.

**`archived` se ne dodaje u aktivni `base()`** (PO-6A09-01).

### 11.2 Archive-only query (PO-6A09-01)

**Javna Arhiva** koristi **poseban** ugovor (npr. `CulturalPublicEventQuery::archive()`), potpuno odvojen od `base()`.

`show` za istorijski dozvoljen Entry koristi archive-public skup (ili uniju aktivni ∪ archive-public), **bez** proširenja globalnog `base()`.

Tehnički oblik (scope / query object / shared builder) bira se u implementaciji uz poštovanje ovog ugovora.

---


### 11.3 Homepage „Naredni događaji" — PATCH-064 selection SSOT

`CulturalPublicEventQuery` (ili ekvivalent) mora izložiti **poseban** homepage ugovor za zajednički bazen (§5.5), odvojen od:

* `orderedByNextRelevantOccurrence` / Pretraga;
* `withCardRelevantOccurrence` kao **jedini** filter za homepage (taj filter ostaje planned-only i **ne** smije samostalno biti cijeli homepage SSOT nakon PATCH-064);
* calendar `distinctPublicEntryCountsByOccurrenceDate` / `constrainCalendarActiveOccurrence`.

Implementacioni redoslijed: candidate selection → merge → sort (`ranking_date ASC`, `entry.id ASC`) → `take(3)`.

Eager load za kartice: postojeći pattern (`category`, `coverMedia`, `occurrences.location`) — bez N+1; ručni Organizator bez relation query-ja.

---
# 12. Javna vidljivost statusa (kanonski)

Usklađeno sa BR-270–BR-274 / BR-286 / BM-PK-13 / BM-PK-35 / CR-004A–B / PO-6A09; operacionalizacija na kanonskom modelu:

| Status Događaja | Aktivne površine (`base()`) | Javna Arhiva (archive-only) | Detalj URL |
|-----------------|-----------------------------|-----------------------------|------------|
| Objavljen (`published`) | Da — prema vremenskim pravilima | Da — ako je istorijski/prošao | 200 ako u aktivnom ili archive-public skupu |
| Otkazan (`cancelled`) | Da — po BR-270–BR-274; badge Otkazan; bez `cancellation_reason` u V1 | Da — ako je prošao; badge Otkazan | 200 ako u dozvoljenom skupu |
| Arhiviran (`archived`) | **Ne** | Da — **samo** ako ima sačuvan izvorni status (`published`\|`cancelled`) **i** istorijski OCC kriterijum; badge Otkazan ili Završen | 200 samo ako archive-public; inače 404 |
| Nacrt | **Ne** | **Ne** | 404 |
| Na odobrenju | **Ne** | **Ne** | 404 |

Kanonski aktivni public query **ne smije** izložiti Nacrt, Na odobrenju ni Arhiviran.

Samo `status=archived` **nije** dovoljan dokaz da je zapis ikada bio javni — archive-only mora zahtijevati sačuvan izvorni status (ili ekvivalentni pouzdani SSOT). Samo `archived_from_status` takođe **nije** dovoljan bez istorijskog Održavanja.

**Istaknuti:** samo Objavljen + aktuelan; isključuju Otkazan (BR-271); ne čitaju `archived`.

Odnos prema Održavanjima: aktivna kartica/sort §7.3; Arhiva kartica/sort §8; detalj sva javno relevantna Održavanja.

---

# 13. Nefunkcionalni zahtjevi

Planirano za naredne faze (nije blocker dokumentacije Faze 6A).

---

# 14. Granice V1 — Faza 6A Out of Scope

* Manifestacije (Faza 6B);
* slug / SEO URL arhitektura;
* dual-read / dual-write / merge / sync;
* migracija legacy sadržaja;
* legacy alias mapa kategorija;
* nove semantike javnog prikaza `cancellation_reason` van već usvojenog PATCH-063 ugovora (§7.2.4);
* korisnički izbor sortiranja;
* široki UI redizajn;
* gašenje legacy CRUD-a prije završetka stabilizacije (§10.2 korak 4).

---

# 15. Otvorena pitanja

Nema otvorenih pitanja koja blokiraju dokumentacioni ugovor Faze 6A. Implementacioni preduslov: 14 kategorija u `cultural_categories` (način punjenja van ovog PATCH-a).

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
| PO-6A11-01 (kanonski multi-OCC status) | BM-PK-34 | BR-285 | §7.1.6 |
| CR-004B (javni prikaz otkazanih) | BM-PK-13, BM-PK-15 | BR-270–BR-274; BR-001, BR-002, BR-004, BR-114, BR-116 | §7.2, §8 |
| PO-CR4B-01 … PO-CR4B-10 | BM-PK-13, BM-PK-15 | BR-270–BR-274 | §7.2 |
| PO-TS9-06C (CR-004B usklađenje skupa) | BM-PK-22 | BR-263 | §3.2, §5.3 |
| PO-TS9-06D | BM-PK-23 | BR-264, §5.3 | §5.4 |
| PATCH-064 / BM-PK-37 | BM-PK-37 / BM-GL-26 | BR-296 | §5.5, §7.3.2, §7.3.4 |
| PATCH-064 / zajednički bazen | BM-PK-23 | BR-264 / BR-297 | §5.4, §5.5, §11.3 |
| PO-TS9-07A | BM-PK-24 | BR-265 | §6.1 |
| PO-TS9-07B | BM-PK-25 | BR-266 | §6.2 |
| PO-TS9-07C | BM-PK-26 | BR-267 | §6.3 |
| PO-TS9-07D | BM-PK-27, BM-MF-13 | BR-268, BR-192 | §6.4 |
| PO-TS9-07E | BM-PK-28 | BR-269, §5.4.2 | §6.5 |
| Detalji događaja (baseline) | BM-PK-05, BM-PK-09–14, BM-PK-28 | §5.4, BR-106, BR-110–115, BR-269 | §7 |
| Arhiva događaja (baseline) | BM-PK-13 | BR-114 | §8 |
| Pretraga / filteri (opšte) | BM-PK-06, BM-PK-07 | BR-107, BR-108 | §2–§3 |
| Načini prikaza | BM-PK-08 | BR-109 | §2.4, §6 |
| PO-EV-01 / cutover SSOT | BM-KO-11; PO-EV-01 | BR-279 | §9 |
| PO-TS9-08A UI očuvanje | BM-PK-16, BM-PK-19 | BR-255, BR-258 | §9.3 |
| PO-TS9-08B kartica + N termina | BM-PK-29 | BR-280 | §7.3.2 |
| PO-TS9-08C sortiranje | BM-PK-30 | BR-281 | §3.4 |
| PO-TS9-08D Odgođen | BM-PK-31 | BR-282 | §7.3.4 |
| PO-TS9-08E CAT-CUTOVER | BM-PK-32, BM-KO-09–11 | BR-277–BR-279, BR-283 | §3.3.3, §9.4 |
| PO-TS9-08F Faza 6A/6B | — | — | §1.7; IR-001 |
| PO-TS9-08G cancellation_reason V1 | BM-PK-33, BM-DG-10 | BR-272, BR-284, BR-064 | §7.2.4 |
| PO-TS9-08H legacy URL | — | — | §10.1 |
| PO-TS9-08I feature flag | — | — | §10.2 |
| PO-TS9-08J public query SSOT | — | — | §11–§12 |

Granice (bez dupliciranja u TS-009): lifecycle Događaja → TS-003; Održavanje/Termin → TS-004; Manifestacija entitet → TS-005 (implementacija portala = Faza 6B).

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
* CR-004B (IS-001 Faza 3; Implemented): javni prikaz otkazanih — aktivne površine i portalna Arhiva (vremenska; ≠ interni `archived`); status ostaje `cancelled`; Istaknuti isključuju cancelled; show dozvoljava cancelled; sistemsko obavještenje; statistike/datumski skupovi uključuju cancelled; javni status uvijek Otkazan (PO-CR4B-01…10 / §7.2). Bez migracija; bez javne dostupnosti `archived`; bez izmjene BR-065 / BM-DG-04. PATCH-063 dopušta opcion javni `cancellation_reason` kada postoji (§7.2.4 / BR-295).
* **Faza 6A:** kanonski cutover (§9–§12); kartica/sortiranje/Odgođen (§7.3 / §3.4); CAT-CUTOVER; privremeni flag (§10.2); public query SSOT (§11); test matrica §18. **Bez** Manifestacija (Faza 6B / §6).
* Detalji događaja / Arhiva događaja: uskladiti prikaz sa BM-PK-05/13 i BR-106/114/270–274; status badge prema §7.1; dostupnost otkazanih prema §7.2; ne uvoditi paralelna lifecycle pravila.
* Ne duplicirati TS-003 / TS-004 / TS-005 u portalskom sloju.

---


* **PATCH-064 homepage (implemented):** §5.5 / §11.3 isporučeno bez diranja Pretrage, calendar counts, selected-day, featured, lifecycle. `CulturalPublicCardOccurrenceCriteria` ostaje planned-only. Tie-breaker ostaje `entry.id ASC`.
# 18. Test matrica Faze 6A (TM-JP) — dokumentaciono

Konvencija: `TM-JP-*` (Javni Portal), u skladu sa `TM-*` iz RG-001 / TS-010.8. **Bez test koda** u ovom PATCH-u.

| ID | Tema | Preduslov | Očekivano | Tip | Sljedivost |
|----|------|-----------|-----------|-----|------------|
| TM-JP-01 | Vidljivost Objavljen | Objavljen + Planirano Održavanje | U listama / detalj 200 | Pozitivan | §12; BR-270 |
| TM-JP-02 | Leakage Nacrt | Nacrt | Nije u listama; detalj 404 | Negativan | §12 |
| TM-JP-03 | Leakage Na odobrenju | Na odobrenju | Nije u listama; detalj 404 | Negativan | §12 |
| TM-JP-04 | Leakage Arhiviran na aktivne | `archived` Entry | Nije na naslovnoj / Pretrazi / featured / upcoming / aktivnim counts | Negativan | §11.1; §12; PO-6A09-01 |
| TM-JP-05 | Kartica 1 Održavanje | 1 Planirano | Termin kartice = to Održavanje; bez „+ još N“ | Pozitivan | §7.3.2; BR-280 |
| TM-JP-06 | Kartica više Održavanja | ≥2 Planirana naredna | Glavni = prvo naredno; „+ još N“ | Pozitivan | §7.3.2; BR-280 |
| TM-JP-07 | Sortiranje Pretrage | Više Događaja | Rastuće po narednom relevantnom Održavanju | Pozitivan | §3.4; BR-281 |
| TM-JP-08 | Odgođen detalj | Odgođeno + novo Planirano | Detalj: „Odgođeno“ + Planirano | Pozitivan | §7.3.4; BR-282 |
| TM-JP-09 | Odgođen kartica | Isto | Kartica ≠ stari odgođeni kao glavni | Pozitivan | §7.3.4; BR-282 |
| TM-JP-10 | Otkazan | Objavljen→Otkazan | Badge Otkazan; BR-272 tekst; opcion `cancellation_reason` ako postoji | Pozitivan | §7.2; BR-272; BR-295 |
| TM-JP-11 | Javna Arhiva | Prošao termin published/cancelled; archived-from-published/cancelled | U Arhivi; badge Otkazan ili Završen; kartica = posljednje istorijsko OCC; sort DESC | Pozitivan | §8; BR-274; BR-286 |
| TM-JP-11a | Archive detail | Archive-public Entry | Detalj 200 | Pozitivan | §8; PO-6A09-03 |
| TM-JP-11b | Non-public archived | `archived` bez izvornog javnog statusa / nikad nije bio javni | Nije u Arhivi; detalj 404 | Negativan | §8; §12 |
| TM-JP-12 | Featured | Featured + cancelled | Cancelled nije u Istaknutim | Pozitivan | §5.2; BR-271 |
| TM-JP-13 | Kategorije | 14 u `cultural_categories` | Filter iz `CulturalCategory`; URL kanonski naziv | Pozitivan | §9.4; BR-283 |
| TM-JP-14 | Detalj sva Održavanja | Više Održavanja | Sva javno relevantna na detalju | Pozitivan | §7.3.3; BR-110 |
| TM-JP-15 | Legacy URL | Stari CulturalEvent id | Smije 404 nakon cutover-a | Pozitivan | §10.1 |
| TM-JP-16 | Feature flag | Flag legacy | Samo legacy read; bez merge | Pozitivan | §10.2 |
| TM-JP-17 | Feature flag | Flag canonical | Samo canonical read; bez merge | Pozitivan | §10.2 |
| TM-JP-18 | CAT preduslov | <14 kategorija | Cutover se ne smatra spremnim | Negativan | §9.4 |
| TM-JP-19 | Ručni Org | Entry sa `organizer_manual_name` | Prikaz naziva; bez registrovanog Org | Pozitivan | §7.3.6; BR-288 |
| TM-JP-20 | Bez Org | Oba null | Nema prazne Org sekcije | Pozitivan | §7.3.6 |
| TM-JP-21 | Odgođeno Prvobitni | Odgođen bez novog termina | „Odgođeno“ + „Prvobitni termin“ + datum + opcion razlog | Pozitivan | §7.3.4; BR-293 |
| TM-JP-22 | OCC cancel | Jedan OCC Otkazan | „Otkazano“ + datum + opcion razlog; Entry ostaje Objavljen ako uslovi dozvole | Pozitivan | §7.3.5; BR-294 |
| TM-JP-23 | Homepage planned only | 1+ planned | Standard card; mode planned | Pozitivan | §5.5; BR-280 |
| TM-JP-24 | Planned + postponed same Entry | Oba OCC | Jedan kandidat; mode planned; ranking = planned datum | Pozitivan | §5.5; BR-297 |
| TM-JP-25 | Info postponed today | published; no planned; postponed datum=today | Info card; Odgođeno + Prvobitni | Pozitivan | §5.5; BR-296 |
| TM-JP-26 | Info postponed tomorrow | postponed datum=tomorrow | Info card vidljiva | Pozitivan | §5.5 |
| TM-JP-27 | Info postponed yesterday | postponed datum=yesterday | Nije homepage kandidat po tom OCC | Negativan | §5.5 |
| TM-JP-28 | Multi postponed | 2+ postponed bez planned | Najbliže neisteklo; prelaz nakon isteka | Pozitivan | §5.5; BR-296 |
| TM-JP-29 | All postponed expired | svi datum < today | Nema postponed_info kandidata | Negativan | §5.5 |
| TM-JP-30 | Shared pool rank | earlier postponed_info + later planned | Sort po ranking_date; max 3; mode nije priority | Pozitivan | §5.5; BR-297 |
| TM-JP-31 | Max 3 | ≥4 kandidata | Prikaz prva 3 nakon sort | Pozitivan | §5.4; BR-264 |
| TM-JP-32 | Tie same ranking_date | 2 Entry isti datum | Stabilan `entry.id ASC`; mode nije tie | Pozitivan | §5.5.5 |
| TM-JP-33 | Resume new termin | postponed→planned | Standard mode; novi datum ranking | Pozitivan | §5.5; BR-293 |
| TM-JP-34 | Calendar count regression | postponed OCC na danu | Ne ulazi u day count | Negativan | §5.5.9; PATCH-063 |
| TM-JP-35 | Selected-day regression | postponed na izabranom danu | Ne u aktivnoj day listi | Negativan | §5.5.9 |
| TM-JP-36 | Search sort unchanged | Pretraga | BR-281 / orderedByNextRelevant neizmijenjen | Pozitivan | §3.4; §5.5.9 |
| TM-JP-37 | Detail unchanged | postponed Entry | PATCH-063 detalj neizmijenjen | Pozitivan | §7.3.4 |
| TM-JP-38 | Cancel/archive/newsletter | — | Lifecycle/arhiva/newsletter neizmijenjeni | Pozitivan | §5.5.1 |
