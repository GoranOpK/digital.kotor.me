# Digital Kotor
# Technical Specification
## Javni portal

**Feature ID:** FT-001  
**Oznaka dokumenta:** TS-009  
**Funkcionalna cjelina:** Javni portal Kalendara kulture  
**Modul:** Kalendar kulture  
**Status dokumenta:** U izradi (faza 1 — usvojene product / IA odluke)  
**Verzija:** 0.1.0  
**Datum:** 2026-07-31

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-31 | Faza 1: dokumentovane usvojene odluke IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B i TD-TS9-01. Usklađeno sa BM PATCH-045 i FS PATCH-FS-047. Bez SQL, API ugovora, Laravel koda i migracija. Bez izmjene implementacije. |

---

# Svrha dokumenta

Ovaj dokument opisuje tehničku specifikaciju funkcionalne cjeline **Javni portal** u okviru FT-001 – Kalendar kulture.

TS-009:

* ne uvodi nova poslovna pravila van usvojenih BM/FS;
* ne predstavlja implementaciju;
* ne definiše SQL, migracije, Laravel kod ni konkretne API ugovore;
* u fazi 1 dokumentuje usvojene product i informaciono-arhitektonske odluke kao referentni okvir za naredne tehničke faze.

Izvori istine:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-11 BM-PK-01–BM-PK-20, BM-AR-02; PATCH-045)
* `docs/functional-specification/Functional-Specification.md` (§5.1–§5.4, §5.13 BR-102–BR-117 i BR-255–BR-260; PATCH-FS-047)
* usvojene odluke: IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B, TD-TS9-01
* `docs/features/Feature-Registry.md`
* `docs/METHODOLOGY.md`

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | Usvojeno (faza 1) |
| 2. Informaciona arhitektura i prikazi | Usvojeno (faza 1) |
| 3. Pretraga i pregled — filteri | Usvojeno (faza 1) |
| 4. Tehnička napomena: ruta `cultural-calendar.day` | Usvojeno (faza 1) |
| 5. Arhitektonski principi (šire) | Planirano — naredne faze |
| 6. Tokovi i URL ugovor (detalj) | Planirano — naredne faze |
| 7. Integracije sa TS-003…TS-008 | Planirano — naredne faze |
| 8. Model podataka / upiti | Planirano — naredne faze |
| 9. Nefunkcionalni zahtjevi | Planirano — naredne faze |
| 10. Granice V1 (Out of Scope) | Planirano — naredne faze |
| 11. Otvorena pitanja | Planirano — naredne faze |
| 12. Matrica sljedivosti | Usvojeno (faza 1 — djelimično) |
| 13. Napomene za implementaciju | Usvojeno (faza 1 — ograničeno) |

---

# Pravila upravljanja ovim dokumentom

1. TS-009 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz TS-009.
4. Princip **IA-01**: evolucija postojećeg javnog portala; bez redizajna i bez nove strukture stranica van usvojenih odluka.
5. Izmjene usvojenog sadržaja evidentiraju se novom verzijom dokumenta i odgovarajućim PATCH-om BM/FS, gdje je primjenjivo.

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

## 1.3 Van obuhvata faze 1

* detaljan URL ugovor filtera (imena parametara, format datuma);
* implementacija filtera, rename navigacije i UI;
* newsletter UI (TS-011);
* urednički portal (TS-010);
* potpuni model upita prema novom domenu (Održavanje, Manifestacija, Lokacija katalog, …).

## 1.4 Zavisnosti

| Zavisnost | Uloga u odnosu na TS-009 |
|-----------|---------------------------|
| TS-003 Događaj | Izvor javne verzije događaja |
| TS-004 Održavanje | Termini i lokacije u prikazu |
| TS-005 Manifestacija | Filter i prikaz povezanih MF |
| TS-006 Lokacije | Filter i prikaz lokacija |
| TS-007 Kategorije i oznake | Filter kategorije; prikaz |
| TS-008 Mediji | Prikaz fotografija |
| TS-010 Urednički portal | Nije dio javnog portala |
| TS-011 Newsletter | Povezano; van faze 1 TS-009 |

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
| Arhiva (`cultural-calendar.archive`) | Prošli / arhivski prikaz u skladu sa BM/FS |
| Detalj događaja (`cultural-calendar.show`) | Puni prikaz jednog događaja |

## 2.3 PO-TS9-03A — Pretraga i pregled

| Odluka | PO-TS9-03A |
|--------|------------|
| BM | BM-PK-17 |
| FS | BR-256 |

Stranica koja je u navigaciji/implementaciji nosila naziv **„Pregled događaja“** preimenuje se u **„Pretraga i pregled“**.

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

Detaljan ugovor imena URL parametara i ponašanja praznih/nevažećih vrijednosti definiše se u narednoj fazi TS-009, u skladu sa postojećim obrascem URL stanja na početnoj (§5.3.4 FS).

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

Ova napomena ne nalaže izmjenu koda u okviru faze 1 dokumentovanja.

---

# 5–11. Planirano (naredne faze)

Sljedeća poglavlja ostaju za naredne faze TS-009 nakon dodatnih usvojenih odluka:

* širi arhitektonski principi (autorizacija pristupa, performans liste, paginacija);
* detaljni tokovi i URL ugovor;
* integracije sa TS-003–TS-008;
* model podataka / upiti;
* NFR;
* Out of Scope V1;
* otvorena pitanja.

---

# 12. Matrica sljedivosti (faza 1)

| Odluka | BM | FS | TS-009 |
|--------|----|----|--------|
| IA-01 | BM-PK-16, BM-AR-02 | BR-255 | §2.1 |
| PO-TS9-03A | BM-PK-17 | BR-256 | §2.3 |
| PO-TS9-04A | BM-PK-18 | BR-257 | §3 |
| PO-TS9-05A | BM-PK-19 | BR-258 | §2.2 |
| PO-TS9-05B | BM-PK-20 | BR-259 | §2.4 |
| TD-TS9-01 | — (tehnička) | BR-260 | §4 |
| Pretraga / filteri (opšte) | BM-PK-06, BM-PK-07 | BR-107, BR-108 | §2–§3 |
| Načini prikaza | BM-PK-08 | BR-109 | §2.4 |

---

# 13. Napomene za implementaciju (faza 1)

* Faza 1 je **dokumentaciona**; ne mijenja se kod u okviru ovog PATCH-a.
* Pri budućoj implementaciji: poštovati IA-01 (minimalne izmjene postojećeg portala).
* Rename navigacionog labela „Pregled događaja“ → „Pretraga i pregled“ pripada budućoj implementacionoj fazi, u skladu sa PO-TS9-03A.
* Filteri (PO-TS9-04A) pripadaju stranici `cultural-calendar.events` (budući UI naziv „Pretraga i pregled“).
* Rutu `cultural-calendar.day` ne tretirati kao javni ekran u IA dijagramima ni u korisničkoj dokumentaciji portala (TD-TS9-01).
