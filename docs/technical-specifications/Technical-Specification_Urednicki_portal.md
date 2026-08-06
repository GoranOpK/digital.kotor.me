# Digital Kotor
# Technical Specification
## Urednički portal

**Feature ID:** FT-001
**Oznaka dokumenta:** TS-010
**Funkcionalna cjelina:** Urednički portal Kalendara kulture
**Modul:** Kalendar kulture
**Status dokumenta:** U IZRADI
**Verzija:** 0.3.1
**Datum:** 2026-08-06

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-08-06 | TS-010.1 — Osnove uredničkog portala: korisnici, aktivni kontekst Organizatora, četvoroslojni autorizacioni model, arhitektura Platforma / Urednički portal / Javni portal. Bez razrade Organizatora, Moderatora, workflow-a, CRUD-a, dashboarda, evidencije i test matrice. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 0.1.1 | 2026-08-06 | QA korektivni prolaz TS-010.1: eksplicitna norma o tehničkoj operacionalizaciji BM/FS; poglavlje Terminologija; eksplicitna norma da javni portal nikada ne mijenja poslovne podatke. Bez proširenja obuhvata. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 0.2.0 | 2026-08-06 | TS-010.2 — Organizatori: poslovno-tehnički model entiteta, veza sa Moderatorima (pravila 1–6), statusi Na odobrenju / Aktivan / Deaktiviran, invariant najmanje jednog aktivnog Moderatora. Bez razrade registracije, zahtjeva, CRUD-a, UI, autorizacije i workflow-a. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 0.2.1 | 2026-08-06 | QA korektivni prolaz TS-010.2: uklonjen trailing whitespace; precizirana Pravila 3 i 5 (invariant i zabrana prolaznog stanja bez aktivnog Moderatora). Bez proširenja obuhvata. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 0.3.0 | 2026-08-06 | TS-010.3 — Moderator Organizatora: nastanak/uklanjanje ovlašćenja, kontekst, kardinalnosti, invarianti, ovlašćenja/zabrane, prestanak pri deaktivaciji Organizatora, lokalni audit; operacionalizacija G-11/G-12/G-13/G-16/G-17; G-14 van obuhvata. Bez novih BM/BR. Bez izmjene implementacije. |
| 0.3.1 | 2026-08-06 | QA korektivni prolaz TS-010.3: G-11 (zahtjev za sopstveno uklanjanje); G-12 terminologija u TS-010.1–TS-010.2; G-14 granica podataka Organizatora; G-17 sloj platformske role; povlačenje; G-13 bez novog statusa; sljedivost. Bez novih BM/BR. Bez izmjene implementacije. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.
Kod svake naredne verzije dodaje se novi red u tabeli.
Ne mijenjaju se postojeći redovi.

---

# Change Log

| Verzija | Datum | Izmjena |
|---------|--------|---------|
| 0.1.0 | 2026-08-06 | Kreiran TS-010. Dokumentaciono pripremljen TS-010.1 (Osnove). Planirane cjeline TS-010.2–TS-010.8 evidentirane bez razrade. |
| 0.1.1 | 2026-08-06 | QA korektivni prolaz TS-010.1 (terminologija; normativne rečenice BM/FS i javnog portala). Bez proširenja obuhvata. |
| 0.2.0 | 2026-08-06 | Dokumentaciono pripremljen TS-010.2 (Organizatori). TS-010.3–TS-010.8 ostaju Planned. |
| 0.2.1 | 2026-08-06 | QA korektivni prolaz TS-010.2 (Pravila 3 i 5; trailing whitespace). Bez proširenja obuhvata. |
| 0.3.0 | 2026-08-06 | Dokumentaciono pripremljen TS-010.3 (Moderator Organizatora). TS-010.4–TS-010.8 ostaju Planned. |
| 0.3.1 | 2026-08-06 | QA korektivni prolaz TS-010.3 (G-11, G-12, G-14, G-17; matrica; sljedivost). Bez proširenja obuhvata. |

---

# Svrha dokumenta

Ovaj dokument opisuje tehničku specifikaciju funkcionalne cjeline **Urednički portal** u okviru FT-001 – Kalendar kulture.

TS-010 ne uvodi nova poslovna pravila; tehnički operacionalizuje Business Model i Functional Specification.

TS-010:

* ne uvodi nova poslovna pravila van usvojenih BM/FS;
* ne zamjenjuje Business Model niti Functional Specification;
* ne predstavlja implementaciju;
* ne definiše SQL, migracije, Laravel kod ni konkretne API ugovore;
* dokumentuje usvojene arhitektonske i tehničke osnove kao referentni okvir za naredne podcjeline TS-010.2–TS-010.8.

Izvori istine:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-01, BM-02, BM-03, BM-12 BM-EP-01–BM-EP-10, BM-GL-06–BM-GL-09, BM-MOD-01–BM-MOD-16, BM-UR-09)
* `docs/functional-specifications/Functional-Specification.md` (Platformsko pravilo; BR-007; BR-047–BR-055; BR-070–BR-073; BR-048; BR-051; §5.14 BR-118–BR-128)
* `docs/technical-specifications/Technical-Specification_Organizator.md` (TS-001)
* `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (TS-003)
* `docs/technical-specifications/Technical-Specification_Javni_portal.md` (TS-009)
* `docs/features/Feature-Registry.md` (FT-001)
* `docs/METHODOLOGY.md`

---

# Obuhvat

TS-010.1 — Osnove uredničkog portala obuhvata isključivo:

1. korisnike uredničkog portala (Moderator Organizatora, Urednik, Organizator kao poslovni entitet, Administrator platforme);
2. aktivni kontekst Organizatora;
3. četvoroslojni autorizacioni model;
4. arhitekturu sistema (Platforma / Urednički portal / Javni portal) i arhitektonski tok.

TS-010.2 — Organizatori obuhvata isključivo:

1. Organizatora kao poslovni entitet;
2. vezu Organizatora sa Moderatorima Organizatora (pravila 1–6);
3. statuse Organizatora: Na odobrenju, Aktivan, Deaktiviran.

TS-010.3 — Moderator Organizatora obuhvata isključivo:

1. definiciju Moderatora kao poslovnog ovlašćenja;
2. nastanak i uklanjanje moderatorskog ovlašćenja;
3. aktivni kontekst, kardinalnosti i invariante (u odnosu na TS-010.1 / TS-010.2);
4. ovlašćenja i zabrane Moderatora;
5. prestanak ovlašćenja pri deaktivaciji Organizatora;
6. lokalni audit zahtjeva za Moderatore.

---

# Van obuhvata

TS-010.1 **ne razrađuje**:

* Organizatore (detalj entiteta i tokova — vidi TS-010.2);
* Moderatore (detalj ovlašćenja i tokova — vidi TS-010.3);
* workflow događaja;
* CRUD događaja;
* dashboard detalje;
* manifestacije;
* održavanja;
* medije;
* lokacije;
* kategorije;
* oznake;
* audit / evidenciju aktivnosti;
* newsletter;
* endpointe;
* validacije;
* test matricu.

TS-010.2 **ne razrađuje**:

* proces registracije;
* zahtjeve;
* odobravanja;
* CRUD;
* dashboard;
* korisnički interfejs;
* autorizaciju;
* aktivni kontekst;
* workflow događaja;
* detalj Moderatora Organizatora (TS-010.3).

TS-010.3 **ne razrađuje**: vidi §6.12 Van obuhvata TS-010.3.

Te cjeline ostaju u planiranim podcjelinama TS-010.4–TS-010.8, odnosno u postojećim TS dokumentima entiteta (TS-001, TS-003–TS-008), bez dupliciranja njihovih pravila.

---

# Referentni dokumenti

| Dokument | Uloga za TS-010 |
|----------|-----------------|
| Business Model — Kalendar kulture (MASTER) | Izvor poslovnih pravila |
| Functional Specification — Kalendar kulture | Izvor funkcionalnih zahtjeva |
| TS-001 — Organizator, Moderator i zahtjev | Granica entiteta i ovlašćenja Organizator/Moderator |
| TS-003 — Događaj | Granica poslovnog modela događaja |
| TS-009 — Javni portal | Javni potrošač podataka; van uredničkog upravljanja |
| Feature Registry (FT-001) | Sljedivost Feature ↔ TS |

---

# Status razvoja Technical Specification

| Poglavlje / cjelina | Status |
|---------------------|--------|
| TS-010.1 Osnove uredničkog portala | Dokumentaciono pripremljeno |
| TS-010.2 Organizatori | Dokumentaciono pripremljeno |
| TS-010.3 Moderator Organizatora | Dokumentaciono pripremljeno |
| TS-010.4 Workflow događaja | Planned |
| TS-010.5 CRUD događaja | Planned |
| TS-010.6 Dashboard | Planned |
| TS-010.7 Evidencija aktivnosti | Planned |
| TS-010.8 Test matrica | Planned |

---

# Pravila upravljanja ovim dokumentom

1. TS-010 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz TS-010.
4. TS-010 opisuje **urednički portal**; ne zamjenjuje TS-009 (javni portal) niti TS-001 / TS-003 (entiteti).
5. Detaljna razrada cjelina van TS-010.1–TS-010.3 ostaje u planiranim podcjelinama.
6. Izmjene usvojenog sadržaja evidentiraju se novom verzijom dokumenta.

---

# Terminologija

| Pojam | Značenje u TS-010 |
|-------|-------------------|
| Poslovna uloga | Poslovna uloga u modelu Kalendara kulture (npr. Moderator Organizatora, Urednik); nije isto što i platformska rola. |
| Platformska rola | Uloga na nivou platforme Digital Kotor koja određuje pristup sistemu; ne zamjenjuje poslovna ovlašćenja nad resursima. |
| Aktivni kontekst Organizatora | Kontekst u kojem Moderator izvršava radnje u ime tačno jednog Organizatora; nije izbor platformske ili poslovne uloge. |
| Poslovno ovlašćenje | Dozvola za konkretnu poslovnu radnju nad konkretnim resursom, u skladu sa BM/FS. |

---

# 1. Korisnici uredničkog portala

Izvori: BM-ORG-01, BM-MOD-01–BM-MOD-05, BM-MOD-11, BM-UR-09, BM-EP-02, BM-GL-09; Platformsko pravilo; BR-007, BR-048, BR-122–BR-125.

## 1.1 Moderator Organizatora

Moderator Organizatora:

* je registrovani korisnik platforme Digital Kotor;
* radi isključivo u ime Organizatora;
* može imati ovlašćenja nad više Organizatora;
* u jednom trenutku radi u okviru jednog aktivnog Organizatora;
* nema pravo objave događaja.

Moderator nije Urednik i nije Organizator. Moderatorska ovlašćenja ne prenose urednička prava.

## 1.2 Urednik

Urednik:

* je platformska urednička uloga;
* radi nad cijelim Kalendarom kulture;
* nema aktivni kontekst Organizatora;
* ima globalni pregled uredničkog sistema.

Urednik je isključiva uloga Uredničkog portala. Urednik nije Organizator, nije Moderator Organizatora i ne kombinuje ulogu Urednika sa statusom običnog registrovanog korisnika u poslovnom modelu Kalendara kulture. Urednik uvijek postupa kao Urednik.

## 1.3 Organizator

Organizator **nije** korisnička uloga.

Organizator je poslovni entitet i nosilac sadržaja.

Organizator:

* nije korisnik sistema;
* nema pristup Uredničkom portalu;
* ne izvršava operativne radnje direktno.

Sve operativne radnje u ime Organizatora vrši ovlašćeni Moderator Organizatora.

## 1.4 Administrator platforme

Administrator platforme:

* upravlja platformom;
* nije redovni učesnik uredničkog workflow-a;
* njegova sistemska prava ne predstavljaju urednička prava.

Administrator platforme nije Moderator niti Urednik u smislu poslovnog modela Kalendara kulture. Sistemska administracija ne zamjenjuje urednička ovlašćenja.

---

# 2. Aktivni kontekst Organizatora

Izvori: BM-MOD-04, BM-ORG-12; BR-051, BR-049, BR-050.

Moderator:

* može biti vezan za više Organizatora;
* bira aktivni Organizator prije rada;
* sve akcije izvršava isključivo u aktivnom kontekstu;
* promjena aktivnog Organizatora ne mijenja njegova ovlašćenja;
* deaktivacija Organizatora ili uklanjanje moderatorskog ovlašćenja automatski ukida aktivni kontekst.

Aktivni kontekst Organizatora nije isto što i izbor platformske ili poslovne uloge.

Urednik **nema** aktivni kontekst Organizatora.

---

# 3. Autorizacioni model

Usvojeni četvoroslojni autorizacioni model.

Svaka poslovna akcija u Uredničkom portalu prolazi redom:

```text
1. Autentikacija
        │
        ▼
2. Platformska rola
        │
        ▼
3. Aktivni kontekst
        │
        ▼
4. Poslovna dozvola nad konkretnim resursom
```

### 3.1 Autentikacija

Korisnik mora biti autentikovan na platformi Digital Kotor (registrovan i aktivan nalog), u skladu sa Platformskim pravilom.

### 3.2 Platformska rola

Pristup Uredničkom portalu ostvaruju Moderatori Organizatora i Urednici. Organizator nema pristup. Sistemska uloga Administratora platforme ne predstavlja uredničku ulogu.

Značenje ovog sloja nije isto za sve učesnike (**G-17**):

* **Za Urednika i Administratora platforme** — sloj provjerava njihovu konkretnu platformsku / sistemsku rolu.
* **Za Moderatora Organizatora** — ne postoji posebna platformska rola „Moderator Organizatora“. Drugi sloj potvrđuje da je korisnik registrovan, aktivan i podoban za pristup modulskom uredničkom prostoru kao korisnik platforme. Konkretna moderatorska prava nastaju tek kroz važeće poslovno ovlašćenje nad Organizatorom, aktivni kontekst i dozvolu nad konkretnim resursom.

```text
Moderator Organizatora nije platformska rola.
Moderatorska prava ne nastaju iz globalne role, već iz važećeg poslovnog ovlašćenja i aktivnog konteksta Organizatora.
```

Ne uvodi se nova platformska rola. Centralni platformski model korisnika i uloga se ne mijenja.

### 3.3 Aktivni kontekst

Za Moderatora: poslovne radnje koje zahtijevaju pripadnost Organizatoru izvršavaju se isključivo u aktivnom kontekstu Organizatora.

Za Urednika: aktivni kontekst Organizatora se ne primjenjuje.

### 3.4 Poslovna dozvola nad konkretnim resursom

Nakon autentikacije, platformske role i (gdje je primjenjivo) aktivnog konteksta, sistem provjerava poslovnu dozvolu za konkretnu radnju nad konkretnim resursom (npr. događaj, zahtjev, ovlašćenje), u skladu sa BM/FS.

### 3.5 Serverska provjera

Server nikada ne vjeruje:

* URL parametrima;
* ID vrijednostima iz browsera;
* klijentskoj logici.

Svaka akcija mora biti ponovo provjerena na serveru.

---

# 4. Arhitektura uredničkog portala

## 4.1 Tri nivoa sistema

### Platforma

* autentikacija;
* korisnici;
* role;
* sistemska administracija.

### Urednički portal

* Moderator Organizatora;
* Urednik;
* poslovni workflow;
* uređivanje sadržaja.

### Javni portal

* pregled;
* pretraga;
* kalendar;
* arhiva;
* detalji.

Javni portal je isključivo potrošač javno dostupnih podataka.

## 4.2 Arhitektonski tok

```text
Platforma
        │
        ▼
Urednički portal
        │
        ▼
Domenski model
        │
        ▼
Javni portal
```

Naglasci:

* Javni portal nikada ne mijenja poslovne podatke.
* urednički portal jedini upravlja poslovnim sadržajem;
* oba koriste isti domenski model;
* nema dupliranja poslovnih pravila.

Usklađenost sa BM-EP-08 / BR-120 / BR-121: Urednički portal primjenjuje ista poslovna pravila modula; ne definiše paralelna pravila.

---

# 5. TS-010.2 — Organizatori

Izvori: BM-ORG-01, BM-ORG-06, BM-ORG-12, BM-MOD-02, BM-MOD-07, BM-MOD-10, BM-MOD-11; BR-047, BR-048, BR-049, BR-050, BR-072; TS-001 (granica entiteta).

## 5.1 Poslovno-tehnički model

Organizator je poslovni entitet.

Organizator:

* nije korisnički nalog;
* nije Moderator Organizatora;
* ne predstavlja korisnika platforme.

## 5.2 Veza sa Moderatorima Organizatora

### Pravilo 1

Jedan Organizator može imati jednog ili više Moderatora Organizatora.

### Pravilo 2

Jedan Moderator Organizatora može biti ovlašćen za jednog ili više Organizatora.

### Pravilo 3

Aktivan Organizator mora u svakom trenutku imati najmanje jednog aktivnog Moderatora Organizatora.

Ovo predstavlja trajni poslovni invariant.

Sistem ne smije dozvoliti stanje koje narušava ovo pravilo.

### Pravilo 4

Sistem mora spriječiti svaku radnju koja bi dovela do toga da Organizator ostane bez ijednog aktivnog Moderatora Organizatora.

Primjeri:

* uklanjanje posljednjeg Moderatora Organizatora;
* ukidanje posljednjeg aktivnog moderatorskog ovlašćenja.

Implementacija se ne razrađuje u ovoj cjelini.

### Pravilo 5

Posljednji Moderator Organizatora može biti uklonjen isključivo:

* nakon prethodne dodjele drugog Moderatora,

ili

* istovremeno sa deaktivacijom Organizatora.

Ne smije postojati prolazno stanje:

```text
Aktivan Organizator
        │
        ▼
0 aktivnih Moderatora
```

### Pravilo 6

Moderator nije vlasnik Organizatora.

Promjena Moderatora ne utiče na:

* identitet Organizatora;
* događaje;
* manifestacije;
* istoriju;
* ostale poslovne podatke.

## 5.3 Statusi Organizatora

Statusi Organizatora u V1:

* Na odobrenju
* Aktivan
* Deaktiviran

Novi statusi se ne uvode kroz TS-010.2.

## 5.4 Van obuhvata TS-010.2

U ovoj cjelini se ne dokumentuju:

* proces registracije;
* zahtjevi;
* odobravanja;
* CRUD;
* dashboard;
* korisnički interfejs;
* autorizacija;
* aktivni kontekst;
* workflow događaja.

---

# 6. TS-010.3 — Moderator Organizatora

Izvori: BM-MOD-01–BM-MOD-16, BM-ORG-04–BM-ORG-08, BM-ORG-12, BM-UR-05, BM-UR-08, BM-GL-07; Platformsko pravilo; BR-007, BR-013–BR-034, BR-047–BR-055, BR-063–BR-064, BR-070–BR-073, BR-122–BR-125, BR-132, BR-135–BR-137; TS-001; TS-010.1; TS-010.2; GAP G-11, G-12, G-13, G-14, G-16, G-17.

TS-010.3 ne uvodi nova poslovna pravila; tehnički operacionalizuje usvojene BM/FS i usklađuje se sa TS-001 / TS-010.1 / TS-010.2.

## 6.1 Definicija Moderatora Organizatora

Moderator Organizatora je **poslovno ovlašćenje** registrovanog korisnika platforme Digital Kotor za konkretnog Organizatora (BM-MOD-01, BM-MOD-11, BM-GL-07; Platformsko pravilo; TS-001 §2.1; **G-17**).

Nije platformska rola tipa Urednik. Nije Organizator. Ne prenosi urednička ovlašćenja.

```text
Moderator Organizatora nije platformska rola.
Moderatorska prava ne nastaju iz globalne role, već iz važećeg poslovnog ovlašćenja i aktivnog konteksta Organizatora.
```

Kako Moderator prolazi sloj „platformska rola“ u četvoroslojnom modelu bez posebne moderatorske platformske role: **TS-010.1 §3.2**.

Osnovni opis korisnika i granica uloga: **TS-010.1 §1.1** (bez ponavljanja).

## 6.2 Nastanak moderatorskog ovlašćenja

### 6.2.1 Početni Moderator

* Predlaže se u zahtjevu za kreiranje Organizatora (BM-MOD-03, BM-ORG-07/08; BR-135–BR-137, BR-047).
* Podnosilac može, ali ne mora, biti predloženi početni Moderator.
* Ovlašćenje **nastaje tek nakon odobrenja Urednika** (BM-MOD-12, BM-ORG-08; BR-047, BR-054).
* Detalj toka zahtjeva za kreiranje Organizatora ostaje u TS-001 / budućim cjelinama; TS-010.3 ne redefiniše workflow.

### 6.2.2 Naredni Moderatori

* Pokreće: postojeći **aktivni** Moderator istog Organizatora — samo predlaganje (BM-MOD-13; BR-053).
* Odobrava / dodjeljuje: isključivo **Urednik** (BM-MOD-14, BM-UR-08; BR-054).
* Ovlašćenje postaje aktivno **tek nakon odobrenja**.
* Moderator ne dodjeljuje ovlašćenja.

## 6.3 Aktivni kontekst

Nasljeđuje **TS-010.1 §2** i **§3.3** (bez ponavljanja sadržaja).

Za Moderatora u TS-010.3 važi:

* u jednom trenutku radi u okviru **jednog** aktivnog Organizatora;
* poslovne radnje koje zahtijevaju pripadnost Organizatoru izvršava **isključivo** u aktivnom kontekstu (BM-MOD-04; BR-051);
* promjena aktivnog Organizatora ne mijenja skup njegovih ovlašćenja; mijenja samo kontekst primjene;
* Urednik **nema** aktivni kontekst Organizatora (TS-010.1).

## 6.4 Kardinalnosti

Nasljeđuje **TS-010.2 Pravila 1–2**:

* Organizator → 1..N Moderatora Organizatora (BM-ORG-06; BR-047);
* Moderator → 1..N Organizatora (BM-MOD-02; BR-051).

## 6.5 Invarianti

Nasljeđuje **TS-010.2 Pravila 3–5** i BM/FS:

* aktivan Organizator mora u svakom trenutku imati najmanje jednog aktivnog Moderatora (BM-MOD-07; BR-047; TS-010.2 Pravilo 3);
* nije dozvoljeno ukloniti posljednjeg aktivnog Moderatora bez zadovoljenja uslova iz §6.6.3 (BM-MOD-10; BR-072);
* nije dozvoljeno ostaviti aktivnog Organizatora bez aktivnog Moderatora, uključujući prolazno stanje (TS-010.2 Pravila 4–5).

## 6.6 Uklanjanje Moderatora Organizatora

Kanonski pojam: **uklanjanje Moderatora Organizatora** (**G-12**). Ne postoji poslovni lifecycle status „deaktiviran Moderator Organizatora“. Deaktivacija Organizatora (BM-ORG-12) nije isto što i uklanjanje moderatorskog ovlašćenja.

### 6.6.1 Pokretanje i odobrenje

* Aktivni Moderator može pokrenuti uklanjanje **drugog** Moderatora istog Organizatora (BM-MOD-08; BR-070).
* Aktivni Moderator može podnijeti **zahtjev** Uredniku za **uklanjanje sopstvenog** ovlašćenja (**G-11**; vidi §6.6.2).
* Odobrava / odbija: Urednik (BM-MOD-09, BM-UR-05; BR-071).
* Moderator se smatra uklonjenim **tek nakon odobrenja**.

### 6.6.2 Operacionalizacija G-11

```text
neposredno samouklanjanje — zabranjeno
zahtjev za uklanjanje sopstvenog ovlašćenja — dozvoljen
konačna odluka — Urednik
```

1. Moderator Organizatora **ne može neposredno i samostalno** ukloniti sopstveno moderatorsko ovlašćenje.
2. Moderator **može podnijeti zahtjev** Uredniku za uklanjanje sopstvenog ovlašćenja.
3. Podnošenje zahtjeva **ne ukida** niti **ne suspenduje** postojeće ovlašćenje.
4. Ovlašćenje ostaje **aktivno do odluke Urednika**.
5. Urednik može odobriti zahtjev samo ako:
   * Organizator nakon uklanjanja i dalje ima najmanje jednog aktivnog Moderatora Organizatora; ili
   * se istovremeno deaktivira Organizator.
6. Sistem ne smije dozvoliti ni prolazno stanje aktivnog Organizatora bez aktivnog Moderatora.

Ne uvodi se novi poslovni status zahtjeva.

### 6.6.3 Uklanjanje posljednjeg Moderatora

Posljednji aktivni Moderator Organizatora može biti uklonjen samo:

* nakon prethodne dodjele drugog aktivnog Moderatora; ili
* istovremeno sa deaktivacijom Organizatora.

Sistem ne smije dozvoliti prolazno stanje:

```text
Aktivan Organizator
→ 0 aktivnih Moderatora
```

Ovo je prenos invarianta iz TS-010.2 Pravila 4–5; nije novo poslovno pravilo.

## 6.7 Prestanak ovlašćenja (deaktivacija Organizatora)

Pri deaktivaciji Organizatora (BM-ORG-12; BR-049–BR-050; BM-MOD-16):

* prestaje moderatorski kontekst za tog Organizatora;
* Moderatori nemaju pravo poslovnih radnji nad događajima / sadržajem tog Organizatora;
* aktivni kontekst za tog Organizatora se ukida (TS-010.1 §2).

### 6.7.1 Operacionalizacija G-13

Bez novog BM/FS pravila i bez novog poslovnog statusa zahtjeva:

* otvoreni zahtjevi za **dodjelu** ili **uklanjanje** Moderatora vezani za deaktiviranog Organizatora **ne mogu** proizvesti aktivno operativno ovlašćenje niti obnoviti kontekst rada nad deaktiviranim Organizatorom (posljedica BR-049–BR-050);
* Sistem mora spriječiti dalju poslovnu obradu otvorenog zahtjeva nakon deaktivacije Organizatora, uz očuvanje postojećeg zapisa i istorije. Konkretan tehnički status zapisa određuje se u implementacionom modelu bez uvođenja novog poslovnog ishoda.
* zapisi zahtjeva se ne brišu ako se propisuje auditabilnost (BR-055 / BR-073).

## 6.8 Ovlašćenja Moderatora

Matrica je sažetak usvojenih BM/FS/TS-001 pravila. Nova ovlašćenja se ne uvode. Detalj workflow/CRUD događaja ostaje u TS-010.4 / TS-010.5 / TS-003.

### 6.8.1 Granica upravljanja podacima Organizatora (G-14)

Usvojena granica ovlašćenja (bez kataloga polja i bez Kataloga Organizatora):

**Moderator Organizatora** može upravljati poslovnim podacima Organizatora koji služe javnom predstavljanju i operativnom radu Organizatora. To može uključivati naziv Organizatora, opis, kontakt podatke, web stranicu, logo i druge javne prezentacione podatke. Naziv Organizatora nije tretiran kao poseban pravni ili kataloški naziv.

**Urednik** zadržava isključivu nadležnost nad odobrenjem Organizatora, statusom Organizatora, deaktivacijom Organizatora, dodjelom i uklanjanjem moderatorskih ovlašćenja, te drugim administrativnim i statusnim odlukama.

```text
Moderator upravlja sadržajem i javnim predstavljanjem Organizatora.
Urednik donosi administrativne, statusne i uredničke odluke.
```

Detaljna lista polja i UI forma ostaju van obuhvata TS-010.3. Ne uvode se Katalog Organizatora, pravni naziv, prikazni naziv kao zaseban atribut, novi poslovni entitet ni katalog polja Organizatora.

### 6.8.2 Matrica ovlašćenja

| Akcija | Dozvoljeno? | Izvor |
|--------|-------------|-------|
| Pregled sadržaja u aktivnom kontekstu | Da | BM-EP-06; BR-016, BR-124; TS-001 §5 |
| Kreiranje događaja za Organizatora u kontekstu | Da | BM-ORG-04, BM-MOD-05; BR-013 |
| Uređivanje nacrta / prijedloga (u granicama FS) | Da | BM-MOD-05; BR-007, BR-021–BR-025 |
| Slanje na odobrenje | Da | BM-MOD-06; BR-028 |
| Povlačenje prijedloga / zahtjeva | Da, isključivo prema postojećim pravilima FS | BR-015–BR-034 |
| Otkazivanje objavljenog događaja | Da, uz uslove | BM-MOD-16; BR-007, BR-063 |
| Pregled / rad nad statusima održavanja (sa Org.) | Da, uz BR-132 | BR-132; TS-004 granica |
| Predlaganje narednog Moderatora | Da | BM-MOD-13; BR-053 |
| Pokretanje uklanjanja drugog Moderatora | Da | BM-MOD-08; BR-070 |
| Zahtjev za uklanjanje sopstvenog ovlašćenja | Da (**G-11**; odluka Urednika) | §6.6.2; BM-MOD-09; BR-071 |
| Upravljanje podacima Organizatora (javno / operativno) | Da (**G-14**; §6.8.1) | BM-GL-07; TS-001 §5 |
| Objava događaja | Ne | BM-ORG-05, BM-MOD-05; BR-007 |
| Ponovna objava otkazanog | Ne | BM-MOD-16, BM-DG-09; BR-064 |
| Dodjela ovlašćenja Moderatoru | Ne (samo Urednik) | BM-MOD-14; BR-054 |

## 6.9 Zabrane

| Zabrana | Izvor |
|---------|-------|
| Nema samostalne objave | BM-ORG-05, BM-MOD-05; BR-007 |
| Nema ponovne objave otkazanog događaja | BM-MOD-16; BR-064 |
| Nema uredničkih odluka (odobrenje/objava/dodjela ovlašćenja) | BM-MOD-11, BM-MOD-14; BR-054, BR-122 |
| Nema rada van aktivnog konteksta | BM-MOD-04; BR-051; TS-010.1 |
| Nema rada nad deaktiviranim Organizatorom | BM-ORG-12, BM-MOD-16; BR-049–BR-050 |
| Nema neposrednog samouklanjanja; zahtjev za sopstveno uklanjanje dozvoljen, odluka Urednika (**G-11**) | §6.6.2; BM-MOD-08–09; BR-070–071 |
| Nema dva aktivna ovlašćenja istog korisnika za isti Organizator (**G-16**) | TS-001 §6.3 / §7 (tehnički integritet); bez novog BM identifikatora |

## 6.10 Integritet modela

Tehnički invarianti (usklađeno sa TS-001; bez izmjene BM):

* jedan korisnik može imati aktivna ovlašćenja za više Organizatora (BM-MOD-02);
* jedan Organizator može imati više aktivnih Moderatora (BM-ORG-06; BM-MOD-07);
* jedan korisnik **ne može** imati **dva aktivna** ovlašćenja nad **istim** Organizatorom (**G-16**);
* aktivni kontekst je najviše jedan Organizator po trenutku rada (TS-010.1).

## 6.11 Audit

Lokalni audit (BM-MOD-15; BR-055, BR-073; TS-001 §8):

* zahtjevi za **dodjelu** ovlašćenja Moderatora;
* zahtjevi za **uklanjanje** Moderatora (uključujući zahtjev za sopstveno uklanjanje);
* podnosilac, predloženi/ciljni Moderator gdje je primjenjivo, vrijeme podnošenja, Urednik koji je odlučio, vrijeme odluke;
* zapisi nisu ručno izmjenjivi nakon upisa (BR-055).

**Centralna Evidencija aktivnosti (FT-003 / TS-012) nije dio TS-010.3.** TS-010.3 ne projektuje FT-003.

## 6.12 Van obuhvata TS-010.3

TS-010.3 ne obrađuje:

* workflow događaja (TS-010.4);
* CRUD događaja (TS-010.5);
* Dashboard (TS-010.6);
* testove (TS-010.8);
* FT-003 / TS-012;
* Newsletter;
* Obavještenja;
* katalog Organizatora;
* detaljnu listu / katalog polja Organizatora i UI formu (granica ovlašćenja G-14 je u §6.8.1);
* UI izgled;
* API;
* modele baze / SQL / migracije.

## 6.13 GAP operacionalizacija (sažetak)

| GAP | Operacionalizacija u TS-010.3 |
|-----|-------------------------------|
| G-11 | §6.6.2 — nema neposrednog samouklanjanja; zahtjev za sopstveno uklanjanje dozvoljen; odluka Urednika; invariant ≥1 |
| G-12 | §6.6 — termin uklanjanje; bez lifecycle „deaktiviran Moderator“; usklađeno i u TS-010.1 §2 / TS-010.2 Pravila 4–5 |
| G-13 | §6.7.1 — otvoreni zahtjevi bez operativnog efekta nakon deaktivacije Org.; bez novog poslovnog statusa |
| G-14 | §6.8.1 — usvojena granica Moderator–Urednik; detaljni katalog polja van obuhvata |
| G-16 | §6.9 / §6.10 — jedno aktivno ovlašćenje po paru korisnik–Organizator |
| G-17 | §6.1 + TS-010.1 §3.2 — poslovno ovlašćenje; nije platformska rola |

---

# 7. Planirane cjeline TS-010

Sljedeće podcjeline su planirane i **nisu razrađene** u ovoj verziji:

| Cjelina | Naziv | Status |
|---------|-------|--------|
| TS-010.4 | Workflow događaja | Planned |
| TS-010.5 | CRUD događaja | Planned |
| TS-010.6 | Dashboard | Planned |
| TS-010.7 | Evidencija aktivnosti | Planned |
| TS-010.8 | Test matrica | Planned |

---

# 8. Matrica sljedivosti

## 8.1 TS-010.1

| TS-010.1 tema | Business Model | Functional Specification | TS-001 | TS-003 | TS-009 | Feature Registry |
|---------------|----------------|--------------------------|--------|--------|--------|------------------|
| Moderator Organizatora | BM-MOD-01–BM-MOD-05, BM-MOD-11, BM-ORG-04–BM-ORG-05 | Platformsko pravilo; BR-007; BR-122–BR-123 | §1, §3, §5 | — | — | FT-001 |
| Urednik | BM-UR-09, BM-EP-02 | Platformsko pravilo; BR-122–BR-125 | §3.6, §5 | — | — | FT-001 |
| Organizator (nije uloga) | BM-ORG-01, BM-GL-06 | Platformsko pravilo; BR-048; BR-122 | §1, §3 | — | — | FT-001 |
| Administrator platforme | BM-GL-09 | Platformsko pravilo | — | — | — | FT-001 |
| Aktivni kontekst | BM-MOD-04, BM-ORG-12 | BR-051; BR-049–BR-050 | §3.5, §5, §6 | — | — | FT-001 |
| Četvoroslojni autorizacioni model | BM-EP-05–BM-EP-06; BM-MOD-04; BM-UR-09 | Platformsko pravilo; BR-048; BR-051; BR-123–BR-124 | §5 | — | — | FT-001 |
| Arhitektura Platforma / EP / JP | BM-EP-01, BM-EP-08, BM-EP-10 | BR-118–BR-121; BR-127 | granica | granica | TS-009 cjelina | FT-001 |
| Javni portal kao potrošač | BM-PK / BM-11 | BR-102+; §5.13 | — | — | §1–§8 | FT-001 |
| Jedinstvena poslovna pravila | BM-EP-04, BM-EP-08 | BR-120, BR-121, BR-127 | bez dupliranja | bez dupliranja | bez dupliranja | FT-001 |

## 8.2 TS-010.2

| TS-010.2 tema | Business Model | Functional Specification | TS-001 | Feature Registry |
|---------------|----------------|--------------------------|--------|------------------|
| Organizator kao poslovni entitet | BM-ORG-01, BM-GL-06 | Platformsko pravilo; BR-048 | §1, §3 | FT-001 |
| Kardinalnost Organizator ↔ Moderator | BM-ORG-06, BM-MOD-02 | BR-047 | §3, §5 | FT-001 |
| Invariant najmanje jednog aktivnog Moderatora | BM-MOD-07, BM-MOD-10 | BR-072 | §5 | FT-001 |
| Sprječavanje ostanka bez aktivnog Moderatora | BM-MOD-07, BM-MOD-10 | BR-072 | §5 | FT-001 |
| Uklanjanje posljednjeg Moderatora (uslov) | BM-MOD-07, BM-MOD-10, BM-ORG-12 | BR-049, BR-050, BR-072 | §4, §5 | FT-001 |
| Moderator nije vlasnik Organizatora | BM-ORG-01, BM-MOD-11 | BR-045, BR-048 | §1, §3 | FT-001 |
| Statusi Organizatora | BM-ORG-12 | BR-049, BR-050 | §6 | FT-001 |

## 8.3 TS-010.3

| TS-010.3 tema | BM | FS | TS-001 | TS-010.1 | TS-010.2 | GAP |
|---------------|----|----|--------|----------|----------|-----|
| Definicija / ovlašćenje (nije platformska rola) | BM-MOD-01, BM-MOD-11, BM-GL-07 | Platformsko pravilo | §2.1, §3.2 | §1.1, §3.2, Terminologija | — | G-17 |
| Nastanak (početni / naredni) | BM-MOD-03, BM-MOD-12–14, BM-ORG-08 | BR-047, BR-053–054, BR-135–137 | §4 | — | — | — |
| Aktivni kontekst | BM-MOD-04 | BR-051 | §3.5 | §2, §3.3 | — | — |
| Kardinalnosti | BM-MOD-02, BM-ORG-06 | BR-047 | §3 | — | Pravila 1–2 | — |
| Invarianti ≥1 | BM-MOD-07, BM-MOD-10 | BR-047, BR-072 | §5 | — | Pravila 3–5 | — |
| Uklanjanje (drugi / sopstveni zahtjev) | BM-MOD-08–10 | BR-070–073 | §4, §5 | — | Pravila 4–5 | G-11, G-12 |
| Prestanak (deaktivacija Org.) | BM-ORG-12, BM-MOD-16 | BR-049–050 | §4 | §2 | Statusi | G-13 |
| Kreiranje / uređivanje / povlačenje / slanje na odobrenje | BM-MOD-05–06, BM-ORG-04 | BR-013–BR-034; BR-122–BR-125 | §5 | §3 | — | — |
| Ovlašćenja / zabrane / podaci Organizatora | BM-MOD-05–06, BM-MOD-16, BM-ORG-05, BM-GL-07 | BR-007, BR-013–BR-034, BR-063–064, BR-122–BR-125, BR-132 | §5 | §3.2 | — | G-14 |
| Integritet (jedno aktivno ovlašćenje / par) | — | — | §6.3, §7 | — | — | G-16 |
| Lokalni audit | BM-MOD-15 | BR-055, BR-073 | §8 | — | — | — |

Lanac sljedivosti:

```text
BM → FS → TS-001 → TS-010.1 → TS-010.2 → TS-010.3
```

Napomena: matrica ne uvodi nove BM ili BR identifikatore.

---

# 9. Napomene za naredne podcjeline

* TS-010.3 razrađuje Moderatora Organizatora bez ponovnog definisanja modela Organizatora iz TS-010.2 i bez ponavljanja osnova iz TS-010.1.
* TS-010.4–TS-010.5 oslanjaju se na TS-003 i FS §5.5 / §5.7 bez ponovnog definisanja lifecycle pravila događaja; koriste matricu ovlašćenja iz TS-010.3.
* TS-010.7 ostaje usklađen sa BM-14 / FS §5.16 / planiranim TS-012; ne zamjenjuje centralnu Evidenciju aktivnosti.
* TS-009 ostaje referentni dokument javnog portala; CR-004B ostaje Planned van obuhvata TS-010.1–TS-010.3.
* Detaljna lista / katalog polja Organizatora ostaje van TS-010.3; usvojena granica ovlašćenja G-14 je u §6.8.1.

---

**Kraj dokumenta TS-010 v0.3.1 (TS-010.1 + TS-010.2 + TS-010.3)**
