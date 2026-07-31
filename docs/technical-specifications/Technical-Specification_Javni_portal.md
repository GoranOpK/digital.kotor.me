# Digital Kotor
# Technical Specification
## Javni portal

**Feature ID:** FT-001  
**Oznaka dokumenta:** TS-009  
**Funkcionalna cjelina:** Javni portal Kalendara kulture  
**Modul:** Kalendar kulture  
**Status dokumenta:** U izradi (faza 1–3 — usvojene product / IA odluke)  
**Verzija:** 0.3.0  
**Datum:** 2026-07-31

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-31 | Faza 1: dokumentovane usvojene odluke IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B i TD-TS9-01. Usklađeno sa BM PATCH-045 i FS PATCH-FS-047. Bez SQL, API ugovora, Laravel koda i migracija. Bez izmjene implementacije. |
| 0.2.0 | 2026-07-31 | Faza 2: dokumentovane usvojene odluke PO-TS9-06A–PO-TS9-06D (Hero, istaknuti, statistike, lista ispod kalendara). Usklađeno sa BM PATCH-046 i FS PATCH-FS-048. Faza 1 odluke neizmijenjene. Bez izmjene implementacije. |
| 0.3.0 | 2026-07-31 | Faza 3: dokumentovane usvojene odluke PO-TS9-07A–PO-TS9-07E (Manifestacije na javnom portalu). Usklađeno sa BM PATCH-047 i FS PATCH-FS-049. Faze 1–2 neizmijenjene. Bez izmjene implementacije. |

---

# Svrha dokumenta

Ovaj dokument opisuje tehničku specifikaciju funkcionalne cjeline **Javni portal** u okviru FT-001 – Kalendar kulture.

TS-009:

* ne uvodi nova poslovna pravila van usvojenih BM/FS;
* ne predstavlja implementaciju;
* ne definiše SQL, migracije, Laravel kod ni konkretne API ugovore;
* dokumentuje usvojene product i informaciono-arhitektonske odluke kao referentni okvir za naredne tehničke i implementacione faze.

Izvori istine:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-11 BM-PK-01–BM-PK-28, BM-05, BM-AR-02; PATCH-045–PATCH-047)
* `docs/functional-specifications/Functional-Specification.md` (§5.1–§5.4, §5.13 BR-102–BR-117 i BR-255–BR-269; PATCH-FS-047–PATCH-FS-049)
* usvojene odluke faze 1: IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B, TD-TS9-01
* usvojene odluke faze 2: PO-TS9-06A, PO-TS9-06B, PO-TS9-06C, PO-TS9-06D
* usvojene odluke faze 3: PO-TS9-07A, PO-TS9-07B, PO-TS9-07C, PO-TS9-07D, PO-TS9-07E
* granice entiteta: TS-005 (Manifestacija), TS-003 (Događaj), TS-004 (Održavanje) — TS-009 ne duplicira njihova poslovna pravila
* `docs/features/Feature-Registry.md`
* `docs/METHODOLOGY.md`

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | Usvojeno (faza 1–3) |
| 2. Informaciona arhitektura i prikazi | Usvojeno (faza 1; dopuna faza 3 — Manifestacije u IA) |
| 3. Pretraga i pregled — filteri | Usvojeno (faza 1) |
| 4. Tehnička napomena: ruta `cultural-calendar.day` | Usvojeno (faza 1) |
| 5. Početna stranica — Hero, istaknuti, statistike, lista | Usvojeno (faza 2) |
| 6. Manifestacije (javni portal) | Usvojeno (faza 3) |
| 7. Arhitektonski principi (šire) | Planirano — naredne faze |
| 8. Tokovi i URL ugovor (detalj) | Planirano — naredne faze |
| 9. Integracije sa TS-003…TS-008 (detalj) | Planirano — naredne faze |
| 10. Model podataka / upiti | Planirano — naredne faze |
| 11. Nefunkcionalni zahtjevi | Planirano — naredne faze |
| 12. Granice V1 (Out of Scope) | Planirano — naredne faze |
| 13. Otvorena pitanja | Planirano — naredne faze |
| 14. Matrica sljedivosti | Usvojeno (faza 1–3) |
| 15. Napomene za implementaciju | Usvojeno (faza 1–3 — ograničeno) |

---

# Pravila upravljanja ovim dokumentom

1. TS-009 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz TS-009.
4. Princip **IA-01**: evolucija postojećeg javnog portala; bez redizajna i bez nove strukture stranica van usvojenih odluka.
5. Izmjene usvojenog sadržaja evidentiraju se novom verzijom dokumenta i odgovarajućim PATCH-om BM/FS, gdje je primjenjivo.
6. Odluke faza 1 i 2 ostaju važeće i ne mijenjaju se fazom 3.
7. TS-009 opisuje **isključivo javni portal**; poslovni model entiteta Manifestacija ostaje u BM-05 / TS-005.

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
* Detalj Manifestacije (PO-TS9-07C);
* Program Manifestacije (PO-TS9-07D);
* Veza Manifestacija ↔ Događaji na portalu (PO-TS9-07E).

Poslovni model entiteta Manifestacija (lifecycle, kardinalnost, uslovi objave) ostaje u BM-05 / TS-005. TS-009 definiše **isključivo javni prikaz i navigaciju**.

## 1.5 Van obuhvata faze 1–3 (dokumentacioni)

* detaljan URL ugovor filtera (imena parametara, format datuma) — naredna faza;
* implementacija filtera, rename navigacije, klikabilnih statistika, dugmeta „Prikaži sve događaje“, praznog stanja istaknutih;
* implementacija cjelina Manifestacije na portalu (lista, detalj, program, navigacija);
* newsletter UI (TS-011);
* urednički portal (TS-010);
* potpuni model upita prema novom domenu (Održavanje, Manifestacija, Lokacija katalog, …).

## 1.6 Zavisnosti

| Zavisnost | Uloga u odnosu na TS-009 |
|-----------|---------------------------|
| TS-003 Događaj | Izvor javne verzije događaja; isticanje; detalj događaja + blok veze ka MF |
| TS-004 Održavanje | Termini i lokacije u prikazu; unosi u programu MF |
| TS-005 Manifestacija | Entitet MF; javni prikaz liste/detalja/programa (bez dupliciranja lifecycle pravila) |
| TS-006 Lokacije | Filter i prikaz lokacija |
| TS-007 Kategorije i oznake | Filter kategorije; prikaz |
| TS-008 Mediji | Prikaz fotografija (naslovna / fallback) |
| TS-010 Urednički portal | Nije dio javnog portala; Urednik označava istaknute |
| TS-011 Newsletter | Povezano; van faze 1–3 TS-009 |

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
| Manifestacije (nova cjelina, PO-TS9-07A) | Lista + detalj + program |
| Arhiva (`cultural-calendar.archive`) | Prošli / arhivski prikaz u skladu sa BM/FS |
| Detalj događaja (`cultural-calendar.show`) | Puni prikaz jednog događaja; blok veze ka Manifestaciji (PO-TS9-07E) |

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

Detaljan ugovor imena URL parametara i ponašanja praznih/nevažećih vrijednosti definiše se u narednoj fazi TS-009, u skladu sa postojećim obrascem URL stanja na početnoj (§5.3.4 FS). Klikovi sa statistika i dugmeta „Prikaži sve događaje“ (faza 2) koriste isti datumski filter mehanizam.

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
| Danas | Klik → „Pretraga i pregled“ sa datumskim filterom za danas |
| Ove sedmice | Klik → „Pretraga i pregled“ sa datumskim filterom za tekuću sedmicu |
| Izabrani mjesec | Label = **naziv** trenutno izabranog mjeseca u kalendaru (ne „Ovog mjeseca“); klik → „Pretraga i pregled“ sa datumskim filterom za taj mjesec |

Dodatno:

* vrijednost 0 ne ukida klikabilnost;
* isključivo javno objavljeni događaji;
* postojeće mjesto na početnoj.

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
| Obuhvat | Lista + detalj + program |
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

## 6.3 PO-TS9-07C — Detalj Manifestacije

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
| Unos | Svako održavanje zasebno: vrijeme; naziv; lokacija (ako postoji); link „Detalji događaja“ |
| Završeni | Ostaju prikazani |
| Otkazani | Ostaju prikazani uz oznaku „Otkazano“ |
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
| Kardinalnost | 1 MF → N događaja; događaj ≤ 1 MF; događaj može biti bez MF |
| Uloga | MF = programski okvir; događaj = osnovni poslovni entitet |
| Detalj događaja | Blok „Ovaj događaj je dio manifestacije“ + naziv + period + „Detalji manifestacije“ |
| Detalj MF | Program → „Detalji događaja“ |
| Navigacija | Dvosmjerna |
| Ostala mjesta | Događaji ostaju u Pretrazi i pregledu, kalendaru, statistikama, arhivi |
| Arhiva / uklanjanje MF | Ne briše događaje |

---

# 7–13. Planirano (naredne faze)

Sljedeća poglavlja ostaju za naredne faze TS-009 nakon dodatnih usvojenih odluka:

* širi arhitektonski principi (autorizacija pristupa, performans liste, paginacija);
* detaljni tokovi i URL ugovor (uključujući parametre filtera za statistike i „Prikaži sve“; rute Manifestacija);
* detaljne integracije sa TS-003–TS-008;
* model podataka / upiti;
* NFR;
* Out of Scope V1 (dopuna);
* otvorena pitanja.

---

# 14. Matrica sljedivosti (faza 1–3)

| Odluka | BM | FS | TS-009 |
|--------|----|----|--------|
| IA-01 | BM-PK-16, BM-AR-02 | BR-255 | §2.1 |
| PO-TS9-03A | BM-PK-17 | BR-256 | §2.3 |
| PO-TS9-04A | BM-PK-18 | BR-257 | §3 |
| PO-TS9-05A | BM-PK-19 | BR-258 | §2.2 |
| PO-TS9-05B | BM-PK-20 | BR-259 | §2.4 |
| TD-TS9-01 | — (tehnička) | BR-260 | §4 |
| PO-TS9-06A | BM-PK-21 | BR-261, §5.1 | §5.1 |
| PO-TS9-06B | BM-PK-15 | BR-117, BR-262 | §5.2 |
| PO-TS9-06C | BM-PK-22 | BR-263, §5.2 | §5.3 |
| PO-TS9-06D | BM-PK-23 | BR-264, §5.3 | §5.4 |
| PO-TS9-07A | BM-PK-24 | BR-265 | §6.1 |
| PO-TS9-07B | BM-PK-25 | BR-266 | §6.2 |
| PO-TS9-07C | BM-PK-26 | BR-267 | §6.3 |
| PO-TS9-07D | BM-PK-27, BM-MF-13 | BR-268, BR-192 | §6.4 |
| PO-TS9-07E | BM-PK-28 | BR-269, §5.4.2 | §6.5 |
| Pretraga / filteri (opšte) | BM-PK-06, BM-PK-07 | BR-107, BR-108 | §2–§3 |
| Načini prikaza | BM-PK-08 | BR-109 | §2.4, §6 |

---

# 15. Napomene za implementaciju (faza 1–3)

* Faze 1–3 su **dokumentacione**; ne mijenja se kod u okviru ovih PATCH-eva.
* Pri budućoj implementaciji: poštovati IA-01 (minimalne izmjene postojećeg portala).
* Rename navigacionog labela „Pregled događaja“ → „Pretraga i pregled“ pripada budućoj implementacionoj fazi (PO-TS9-03A).
* Filteri (PO-TS9-04A) pripadaju stranici `cultural-calendar.events` (budući UI naziv „Pretraga i pregled“).
* Rutu `cultural-calendar.day` ne tretirati kao javni ekran u IA dijagramima ni u korisničkoj dokumentaciji portala (TD-TS9-01).
* Faza 2 — budući CR/impl paket: klikabilne statistike; label treće kartice = naziv mjeseca; naredni događaji max 3; dugme „Prikaži sve događaje“; neutralno prazno stanje istaknutih; usklađenje max istaknutih sa BM-PK-15 / BR-117 (3).
* Faza 3 — budući CR/impl paket: navigacija „Manifestacije“; lista (12/strana, sortiranje, kartice); detalj; program (grupisanje, Otkazano, Vrijeme nije definisano); blok veze na detalju događaja; bez pretrage/filtera liste MF u V1.
* Ne duplicirati lifecycle / kardinalnost iz TS-005 / TS-003 / TS-004 u portalskom kodu kao paralelna pravila — koristiti usvojene BM/FS reference.
