# Digital Kotor
# Technical Specification
## Urednički portal

**Feature ID:** FT-001
**Oznaka dokumenta:** TS-010
**Funkcionalna cjelina:** Urednički portal Kalendara kulture
**Modul:** Kalendar kulture
**Status dokumenta:** U IZRADI
**Verzija:** 0.7.0
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
| 0.4.0 | 2026-08-06 | TS-010.4 — Workflow događaja: akcije, guard redoslijed, matrica prelaza, otkazivanje (aktivni prijedlog izmjene neoperativan), arhiviranje (ref. TS-004), CR-004B (ref. TS-009); sljedivost §8.4. Bez novih statusa/BM/BR. Bez izmjene implementacije. |
| 0.4.1 | 2026-08-06 | Zatvoren N-DG-02: V1 katalog sadržajnih polja događaja (priprema za TS-010.5). Bez novih BM polja; bez izmjene BM/FS/TS-003. Bez izmjene implementacije. |
| 0.5.0 | 2026-08-06 | TS-010.5 — CRUD događaja i validacije: Create/Read/Update; prijedlog izmjene (N-DG-04 = implementacioni izbor); nested Održavanja (TS-004); editabilnost; gate-ovi; Delete događaja nije podržan; sljedivost §8.5. Bez novih BM/BR. Bez izmjene implementacije. |
| 0.6.0 | 2026-08-06 | TS-010.6 — Dashboard uredničkog portala: radna tabla (PO-DASH-01–05); radne kategorije sa brojačem i filterom ka CRUD; bez BI/listi/Activity Feed; sljedivost §8.6. Bez novih BM/BR. Bez izmjene implementacije. |
| 0.7.0 | 2026-08-06 | TS-010.7 — Evidencija aktivnosti uredničkog portala: obaveza evidentiranja prema FT-003 (PO-AL-01–04); lokalni audit ≠ centralna evidencija; bez UI centralne evidencije; sljedivost §8.7. Bez novih BM/BR. Bez izmjene implementacije. |

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
| 0.4.0 | 2026-08-06 | Dokumentaciono pripremljen TS-010.4 (Workflow događaja). TS-010.5–TS-010.8 ostaju Planned. |
| 0.4.1 | 2026-08-06 | Zatvoren N-DG-02 (katalog sadržajnih polja događaja). TS-010.5–TS-010.8 ostaju Planned. |
| 0.5.0 | 2026-08-06 | Dokumentaciono pripremljen TS-010.5 (CRUD događaja i validacije). TS-010.6–TS-010.8 ostaju Planned. |
| 0.6.0 | 2026-08-06 | Dokumentaciono pripremljen TS-010.6 (Dashboard uredničkog portala; PO-DASH-01–05). TS-010.7–TS-010.8 ostaju Planned. |
| 0.7.0 | 2026-08-06 | Dokumentaciono pripremljen TS-010.7 (Evidencija aktivnosti uredničkog portala; PO-AL-01–04). TS-010.8 ostaje Planned. |

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

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-01, BM-02, BM-03, BM-10 BM-ST, BM-04 BM-DG, BM-12 BM-EP-01–BM-EP-10, BM-GL-06–BM-GL-09, BM-MOD-01–BM-MOD-16, BM-UR-09)
* `docs/functional-specifications/Functional-Specification.md` (Platformsko pravilo; BR-006–BR-044; BR-062–BR-066; BR-007; BR-047–BR-055; BR-070–BR-073; BR-048; BR-051; §5.14 BR-118–BR-128; BR-270–BR-274)
* `docs/technical-specifications/Technical-Specification_Organizator.md` (TS-001)
* `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (TS-003)
* `docs/technical-specifications/Technical-Specification_Odrzavanje.md` (TS-004)
* `docs/technical-specifications/Technical-Specification_Mediji.md` (TS-008)
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

TS-010.4 — Workflow događaja obuhvata isključivo:

1. akcije workflow-a u uredničkom portalu;
2. dozvoljene prelaze statusa događaja;
3. guard uslove i redoslijed autorizacionih / statusnih provjera;
4. UX ponašanje akcija (ko vidi, kada, rezultat);
5. vezu sa TS-003 / TS-004 / TS-009 bez redefinisanja domena.

TS-010.5 — CRUD događaja i validacije obuhvata isključivo:

1. Create / Read / Update događaja u uredničkom portalu;
2. prijedlog izmjene Objavljenog (poslovno ponašanje; N-DG-04 = implementacioni izbor skladištenja);
3. nested CRUD Održavanja prema TS-004;
4. validacije po akcijama i editabilnost po statusu/fazi;
5. naslovnu fotografiju (veza TS-008) i veze Organizator / Manifestacija;
6. Delete događaja kao nepodržanu operaciju V1.

TS-010.6 — Dashboard uredničkog portala obuhvata isključivo:

1. svrhu i namjenu Dashboarda (PO-DASH-01–PO-DASH-05);
2. radne kategorije sa brojačem po ulozi;
3. navigaciju klikom na CRUD/listu sa unaprijed primijenjenim filterom;
4. poštovanje aktivnog konteksta, ovlašćenja i Read pravila (TS-010.1 / TS-010.3 / TS-010.5);
5. granice: što Dashboard nije (BI, Activity Feed, FT-003 / TS-012, javni portal).

TS-010.7 — Evidencija aktivnosti uredničkog portala obuhvata isključivo:

1. razgraničenje lokalnog audita i centralne Evidencije aktivnosti (PO-AL-02);
2. obavezu uredničkog portala da evidentira poslovno značajne radnje prema BM-14 / FS §5.16 (PO-AL-01, PO-AL-03);
3. referencu na V1 katalog aktivnosti bez proširenja (PO-AL-04);
4. granice pristupa (Moderator / Urednik / Administrator) i odnos prema FT-003 / TS-012.

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

TS-010.4 **ne razrađuje**: vidi §7.11 Van obuhvata TS-010.4.

TS-010.5 **ne razrađuje**: vidi §10.16 Van obuhvata TS-010.5.

TS-010.6 **ne razrađuje**: vidi §11.10 Van obuhvata TS-010.6.

TS-010.7 **ne razrađuje**: vidi §12.8 Van obuhvata TS-010.7.

Te cjeline ostaju u planiranim podcjelinama TS-010.8, odnosno u postojećim TS dokumentima entiteta (TS-001, TS-003–TS-008) i FT-003 / TS-012, bez dupliciranja njihovih pravila.

---

# Referentni dokumenti

| Dokument | Uloga za TS-010 |
|----------|-----------------|
| Business Model — Kalendar kulture (MASTER) | Izvor poslovnih pravila |
| Functional Specification — Kalendar kulture | Izvor funkcionalnih zahtjeva |
| TS-001 — Organizator, Moderator i zahtjev | Granica entiteta i ovlašćenja Organizator/Moderator |
| TS-003 — Događaj | Domenski lifecycle i prelazi statusa događaja |
| TS-004 — Održavanje događaja | Predikat automatskog arhiviranja; model održavanja (N-TR-01/02/04); održavanje ≠ status događaja |
| TS-008 — Mediji | Naslovna fotografija; MIME/veličina/ALT; lifecycle Medija |
| TS-009 — Javni portal | Javni potrošač podataka; CR-004B vidljivost; van uredničkog upravljanja |
| BM-14 / FS §5.16 / FT-003 / TS-012 | Centralna Evidencija aktivnosti (granica za TS-010.7) |
| Feature Registry (FT-001) | Sljedivost Feature ↔ TS |

---

# Status razvoja Technical Specification

| Poglavlje / cjelina | Status |
|---------------------|--------|
| TS-010.1 Osnove uredničkog portala | Dokumentaciono pripremljeno |
| TS-010.2 Organizatori | Dokumentaciono pripremljeno |
| TS-010.3 Moderator Organizatora | Dokumentaciono pripremljeno |
| TS-010.4 Workflow događaja | Dokumentaciono pripremljeno |
| TS-010.5 CRUD događaja i validacije | Dokumentaciono pripremljeno |
| TS-010.6 Dashboard uredničkog portala | Dokumentaciono pripremljeno |
| TS-010.7 Evidencija aktivnosti uredničkog portala | Dokumentaciono pripremljeno |
| TS-010.8 Test matrica | Planned |

---

# Pravila upravljanja ovim dokumentom

1. TS-010 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz TS-010.
4. TS-010 opisuje **urednički portal**; ne zamjenjuje TS-009 (javni portal) niti TS-001 / TS-003 / TS-004 / TS-008 (entiteti); ne zamjenjuje FT-003 / TS-012 (centralna Evidencija aktivnosti).
5. Detaljna razrada cjelina van TS-010.1–TS-010.7 ostaje u planiranim podcjelinama.
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

# 7. TS-010.4 — Workflow događaja

Izvori: BM-ST-01–BM-ST-09, BM-DG-01, BM-DG-04, BM-DG-05, BM-DG-09, BM-ORG-04–BM-ORG-05, BM-ORG-12, BM-MOD-04–BM-MOD-06, BM-MOD-16, BM-UR-02–BM-UR-03, BM-UR-11; BR-006–BR-044, BR-062–BR-066, BR-007, BR-013–BR-034, BR-063–BR-064, BR-270–BR-274; TS-001; TS-003; TS-004; TS-009; TS-010.1–TS-010.3.

TS-010.4 ne uvodi nova poslovna pravila, nove statuse niti nove workflow grane. Tehnički operacionalizuje usvojene BM/FS u uredničkom portalu i veže se na TS-003 (domen prelaza), TS-004 (predikat arhive) i TS-009 (javni efekti CR-004B) bez redefinisanja tih dokumenata.

## 7.1 Obuhvat i granice

**Obuhvat:** akcije workflow-a događaja u uredničkom portalu; dozvoljeni prelazi; guard uslovi; autorizacija; UX ponašanje (ko vidi akciju, kada, rezultat).

**Van obuhvata:** CRUD forme i UI detalj (TS-010.5); Dashboard (TS-010.6); centralna Evidencija aktivnosti (FT-003 / TS-012; obaveze portala — TS-010.7); API; SQL; migracije; newsletter; UI dizajn. Katalog sadržajnih polja događaja: vidi §9 (N-DG-02 zatvoren).

## 7.2 Kanonski lifecycle

Isključivo statusi (BM-ST-02, BR-062; TS-003 §3.1):

```text
Nacrt
    ↓
Na odobrenju
    ↓
Objavljen
    ↓
Otkazan
    ↓
Arhiviran
```

Dodatni dozvoljeni prelazi iz BM/FS (nisu novi statusi):

* Nacrt → Objavljen — direktna objava Urednika **isključivo bez Organizatora** (BM-ST-04, BR-018);
* Na odobrenju → Nacrt — vraćanje na doradu ili povlačenje prije pregleda;
* Objavljen → Arhiviran i Otkazan → Arhiviran — automatski Sistem (BM-DG-04, BR-065);
* Otkazan → Objavljen — ponovna objava Urednika (BM-DG-09, BR-064).

**Nisu statusi događaja** (BM-ST-05 / PATCH-010; TS-003 §4):

* „Vraćen na doradu“ — poslovna radnja (Na odobrenju → Nacrt);
* „Pregled Urednika“ — faza toka;
* „Prijedlog izmjene“ / „Nacrt prijedloga izmjene“ — faza toka nad događajem u statusu Objavljen (javna verzija ostaje odobrena do nove odluke).

## 7.3 Redoslijed guard provjera

Svaka workflow akcija u uredničkom portalu prolazi redom (usklađeno sa TS-010.1 §3 i TS-010.3):

```text
Autentikacija
        ↓
Platformski pristup
        ↓
Aktivni kontekst
        ↓
Poslovno ovlašćenje
        ↓
Status događaja
        ↓
Workflow prelaz
        ↓
Validacije
        ↓
Izvršenje
```

| Korak | Značenje |
|-------|----------|
| Autentikacija | Registrovan i aktivan nalog (Platformsko pravilo; TS-010.1 §3.1). |
| Platformski pristup | Pristup uredničkom prostoru; za Moderatora bez posebne platformske role „Moderator“ (TS-010.1 §3.2; G-17). |
| Aktivni kontekst | Za Moderatora: radnje nad događajem Organizatora isključivo u aktivnom kontekstu (BM-MOD-04; BR-051). Za Urednika: kontekst se ne primjenjuje. |
| Poslovno ovlašćenje | Važeće ovlašćenje / urednička nadležnost za konkretnu akciju (TS-010.3 §6.8–§6.9; TS-003 §5). |
| Status događaja | Akcija je dozvoljena samo iz dozvoljenog ulaznog statusa (BM-ST-09). |
| Workflow prelaz | Ciljni status / ishod u skladu sa BM-ST / TS-003 §4. |
| Validacije | Obavezna polja / ≥1 održavanje / kategorija gdje FS zahtijeva (BM-DG-01; TS-003 §7); sadržajni katalog = §9; detalj forme = TS-010.5. |
| Izvršenje | Promjena statusa ili faze; serverska ponovna provjera (TS-010.1 §3.5). |

## 7.4 Workflow akcije

Detalj CRUD polja ostaje u TS-010.5. Ovdje: vidljivost, guard, rezultat, naredno stanje.

### 7.4.1 Kreiraj

| | |
|--|--|
| **Ko vidi** | Moderator (aktivni Org + kontekst); Urednik (događaj bez Org, ili po postojećim pravilima TS-003 / TS-001). |
| **Guard** | Autentikacija; pristup; za Moderatora — Aktivan Org + aktivni kontekst; Organizator nije Deaktiviran. |
| **Rezultat** | Novi zapis događaja. |
| **Naredno stanje** | **Nacrt** (BM-ST-03). |

### 7.4.2 Sačuvaj nacrt

| | |
|--|--|
| **Ko vidi** | Moderator (svoj Org u kontekstu); Urednik (gdje uređuje nacrt po pravilima). |
| **Guard** | Status **Nacrt**; ovlašćenje; kontekst gdje je primjenjivo; događaj nije zaključan pregledom. |
| **Rezultat** | Ažurirani nacrt; nije javno. |
| **Naredno stanje** | Ostaje **Nacrt**. |

### 7.4.3 Pošalji na odobrenje

| | |
|--|--|
| **Ko vidi** | Aktivni Moderator Organizatora za događaj svog Org (BM-MOD-06; BR-028; TS-010.3). |
| **Guard** | Status **Nacrt** (ili faza prijedloga izmjene nad Objavljenim — BR-025); validacije FS/TS-003; aktivni kontekst; Org Aktivan. |
| **Rezultat** | Prijedlog na uredničkom pregledu; zaključavanje po BR-022–BR-024 kada pregled počne. |
| **Naredno stanje** | **Na odobrenju** (BM-ST-04). |

### 7.4.4 Povuci prijedlog

| | |
|--|--|
| **Ko vidi** | Moderator koji je podnio prijedlog (BR-033). |
| **Guard** | Status **Na odobrenju**; **prije** početka uredničkog pregleda; nakon početka pregleda — zabranjeno (BR-034). |
| **Rezultat** | Prijedlog povučen; Moderator može ponovo uređivati. |
| **Naredno stanje** | **Nacrt**. |

### 7.4.5 Vrati na doradu

| | |
|--|--|
| **Ko vidi** | Urednik. |
| **Guard** | Status **Na odobrenju**; obavezan razlog (BR-040–BR-041). |
| **Rezultat** | Otključavanje za Moderatora; javna verzija nepromijenjena ako je događaj već bio Objavljen (BR-042). |
| **Naredno stanje** | **Nacrt**. Nije status „Vraćen na doradu“ niti „Odbijeno“ (BR-044). |

### 7.4.6 Objavi

Obuhvata dvije usvojene putanje (nije nova grana):

**A — Odobri (standardno)**

| | |
|--|--|
| **Ko vidi** | Urednik. |
| **Guard** | Status **Na odobrenju**; urednička nadležnost (BM-UR-02, BM-ST-06, BR-039). |
| **Rezultat** | Javna verzija postaje odobrena; vidljivost po TS-009. |
| **Naredno stanje** | **Objavljen**. |

**B — Direktna objava**

| | |
|--|--|
| **Ko vidi** | Urednik. |
| **Guard** | Status **Nacrt**; događaj **bez** registrovanog Organizatora (BM-ST-04, BR-018); validacije objave. |
| **Rezultat** | Objava bez toka odobravanja. |
| **Naredno stanje** | **Objavljen**. |

Moderator **nikada** ne vidi / ne izvršava Objavi (BM-ORG-05, BM-MOD-05; BR-007; TS-010.3 §6.9).

### 7.4.7 Otkazivanje

| | |
|--|--|
| **Ko vidi** | Moderator (uslovno); Urednik (svaki objavljeni). |
| **Guard** | Ulaz isključivo **Objavljen** (BM-ST-07, BR-063). Moderator: Org **Aktivan** + aktivni kontekst + ovlašćenje. Nakon deaktivacije Org — samo Urednik (BM-ORG-12, BM-MOD-16). |
| **Rezultat** | Status **Otkazan**; zapis ostaje; javni efekti po TS-009 / CR-004B (vidi §7.9). |
| **Naredno stanje** | **Otkazan**. |

**Aktivni prijedlog izmjene pri otkazivanju (usvojena tehnička operacionalizacija):**

> Ako u trenutku otkazivanja postoji aktivni prijedlog izmjene, sistem ga automatski čini neoperativnim. Prijedlog ostaje evidentiran u auditu, ne može biti odobren niti vraćen na doradu i ne dobija novi poslovni status. Nakon eventualne ponovne objave događaja, nove izmjene pokreću se kroz novi prijedlog izmjene.

Ne uvodi se status „Odbijen“, automatsko odbijanje ni automatsko odobrenje prijedloga.

### 7.4.8 Ponovna objava

| | |
|--|--|
| **Ko vidi** | Urednik. |
| **Guard** | Status **Otkazan** (ne Arhiviran); urednička nadležnost (BM-DG-09, BR-064, BM-UR-11). Nije automatska. |
| **Rezultat** | Događaj ponovo **Objavljen**; Urednik može prethodno ažurirati podatke / održavanja po postojećim ovlašćenjima. |
| **Naredno stanje** | **Objavljen**. |

Moderator **ne može** ponovnu objavu (BM-MOD-16; TS-010.3).

## 7.5 Matrica prelaza

| Akcija | Iz statusa | U status | Ko | Guard |
|--------|------------|----------|----|-------|
| Kreiraj | — | Nacrt | Mod / Urednik | Kontekst / bez Org po pravilima |
| Sačuvaj nacrt | Nacrt | Nacrt | Mod / Urednik | Ovlašćenje; nije zaključan |
| Pošalji na odobrenje | Nacrt | Na odobrenju | Moderator | Validacije; Org Aktivan; kontekst |
| Pošalji na odobrenje (prijedlog izmjene) | Objavljen (faza prijedloga) | Na odobrenju | Moderator | Jedan aktivni prijedlog (BR-012); validacije |
| Povuci prijedlog | Na odobrenju | Nacrt | Moderator | Prije početka pregleda (BR-033) |
| Vrati na doradu | Na odobrenju | Nacrt | Urednik | Obavezan razlog (BR-040) |
| Objavi (odobri) | Na odobrenju | Objavljen | Urednik | Urednička odluka (BR-039) |
| Objavi (direktno) | Nacrt | Objavljen | Urednik | Bez Organizatora (BR-018) |
| Otkazivanje | Objavljen | Otkazan | Mod / Urednik | BR-063; Mod: Org Aktivan + kontekst |
| Ponovna objava | Otkazan | Objavljen | Urednik | BR-064; nije Arhiviran |
| Automatsko arhiviranje | Objavljen | Arhiviran | Sistem | TS-004 predikat; BR-065 |
| Automatsko arhiviranje | Otkazan | Arhiviran | Sistem | TS-004 predikat; BR-065 |

Zabranjeni prelazi (sažetak; BM-ST-09 / TS-003): Nacrt → Objavljen sa Org; Moderator → Objavljen / ponovna objava; otkaz iz Nacrta / Na odobrenju; ručna arhiva; izlaz iz Arhiviran u V1.

## 7.6 Prijedlog izmjene nakon objave

Nije novi status (TS-003 §4.7; BR-006–BR-012):

* javni prikaz ostaje posljednja odobrena verzija do odobrenja;
* najviše jedan aktivni prijedlog;
* odobrenje / vraćanje — iste akcije Urednika (§7.4.5–§7.4.6A);
* detalj uređivanja polja — TS-010.5.

## 7.7 Arhiviranje

Referenca: **TS-004 §4.9** i **TS-003 §4.10**; BM-DG-04; BR-065.

* Izvršava **Sistem**, **automatski**.
* **Ručno arhiviranje nije dio V1.**
* Predikat se **ne redefiniše** u TS-010.4: događaj je podoban kada nijedno održavanje nije u Planiran ili Odgođen (Završen i Otkazan održavanja ne blokiraju).
* Ulaz: **Objavljen** ili **Otkazan**.
* Održavanje status ≠ status događaja (BR-134; TS-004).

## 7.8 UX ponašanje (urednički portal)

* Akcija se prikazuje samo ako guard prolazi; inače sakrivena ili onemogućena uz jasan razlog (implementacioni izbor prikaza).
* Serverska provjera je obavezna (TS-010.1 §3.5); klijent ne odlučuje o statusu.
* Tokovi za Moderatora uvijek u aktivnom kontekstu Organizatora.
* Poruke o vraćanju na doradu prikazuju razlog Moderatoru (BR-041).
* Vizuelni dizajn UI-a nije dio TS-010.4.

## 7.9 CR-004B i javni portal

Referenca: **TS-009 §7.2**; BR-270–BR-274; BM-PK-13.

* Urednički **workflow prelazi ostaju isti** (otkaz → Otkazan; arhiva → Arhiviran po BR-065).
* TS-010.4 **ne ponavlja** javni portal: vidljivost `cancelled`, portalna Arhiva ≠ interni `archived`, badge, Istaknuti — isključivo TS-009.
* CR-004B ne mijenja BR-063 ni BR-065; implementacija CR-004B ostaje Planned u Feature Registry.

## 7.10 Veza sa ulogama (TS-010.1–TS-010.3)

| Akcija | Moderator | Urednik | Sistem |
|--------|-----------|---------|--------|
| Kreiraj / Sačuvaj / Pošalji / Povuci | Da (granice §7.4) | Ograničeno (bez Org / pregled) | Ne |
| Vrati / Objavi / Ponovna objava | Ne | Da | Ne |
| Otkazivanje | Da (uslovno) | Da | Ne |
| Arhiviranje | Ne | Ne | Da |

Organizator nije korisnik. Administrator platforme nije učesnik uredničkog workflow-a događaja.

## 7.11 Van obuhvata TS-010.4

* CRUD forme, mediji/kategorije UI (TS-010.5); sadržajni katalog polja = §9 (N-DG-02 zatvoren);
* Dashboard (TS-010.6);
* centralna Evidencija / TS-012 (FT-003; obaveze portala — TS-010.7);
* mehanizam zaključavanja pregleda (implementacioni izbor — TS-003 §7.3);
* kanal obavještenja Uredniku (N-DG-03);
* model skladištenja verzija (N-DG-04);
* API, SQL, migracije, kod, testovi;
* newsletter;
* Manifestacija workflow;
* javni portal UI (TS-009).

## 7.12 Napomena o G-W02

Pravilo o neoperativnom aktivnom prijedlogu pri otkazivanju (§7.4.7) je tehnička operacionalizacija integriteta toka izmjena (BR-006–BR-012) uz otkaz (BR-063). Ne uvodi novi BM/BR identifikator niti novi status prijedloga.

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

## 8.4 TS-010.4

| TS-010.4 tema | BM | FS | TS-003 | TS-004 | TS-009 | TS-010.1–.3 |
|---------------|----|----|--------|--------|--------|-------------|
| Kanonski lifecycle / nisu-statusi | BM-ST-01–02, BM-ST-05 | BR-062; BR-044 | §3.1, §4 | — | — | — |
| Guard redoslijed | BM-MOD-04; BM-EP | Platformsko pravilo; BR-051 | §5, §7 | — | — | §3; §6.8–§6.9 |
| Kreiraj / Sačuvaj nacrt | BM-ST-03; BM-ORG-04 | BR-013–BR-020 | §4.3 | — | — | §6.8 |
| Pošalji / Povuci | BM-ST-04; BM-MOD-06 | BR-028–BR-034 | §4.4 | — | — | §6.8 |
| Vrati na doradu | BM-ST-05; BM-UR-03 | BR-040–BR-044 | §4.5 | — | — | — |
| Objavi (odobri / direktno) | BM-ST-04, BM-ST-06; BM-UR-02 | BR-018, BR-039; BR-007 | §4.5–§4.6 | — | granica | §6.9 |
| Otkazivanje + neoperativan prijedlog | BM-DG-05, BM-ST-07; BM-MOD-16 | BR-063; BR-006–BR-012 | §4.8 | — | §7.2 ref. | §6.8–§6.9 |
| Ponovna objava | BM-DG-09; BM-UR-11 | BR-064 | §4.9 | — | — | §6.9 |
| Arhiviranje | BM-DG-04; BM-ST-08 | BR-065 | §4.10 | §4.9 | portal ≠ archived | — |
| CR-004B (workflow ne mijenja) | BM-PK-13 | BR-270–BR-274 | — | — | §7.2 | — |
| Matrica prelaza / UX | BM-ST-09 | §5.5.6a | §4, §5 | — | — | §3.5 |

Lanac sljedivosti:

```text
BM → FS → TS-001 → TS-003 → TS-004 → TS-009 → TS-010.1 → TS-010.2 → TS-010.3 → TS-010.4
```

Napomena: matrica ne uvodi nove BM ili BR identifikatore.

## 8.5 TS-010.5

| TS-010.5 tema | BM | FS | TS-003 | TS-004 | TS-008 | TS-009 | TS-010 |
|---------------|----|----|--------|--------|--------|--------|--------|
| V1 katalog sadržajnih polja | BM-DG | §5.4; BR-015–BR-017 | §6.2 | — | BM-MD | — | §9; N-DG-02 |
| Create (Moderator / Urednik) | BM-ORG-04; BM-ST-03; BM-MOD-05 | BR-013–BR-020 | §4.3 | — | — | — | §6.8; §7 |
| Read opseg | BM-MOD-05–06; BM-UR; BM-ORG-12 | BR-013+; BR-122–125 | — | — | — | — | §1; §6 |
| Update po statusu/fazi | BM-ST; BM-DG-05–09 | BR-006–044; BR-062–066 | §4–§5 | N-TR-04 | — | — | §7; §10.5 |
| Prijedlog izmjene / N-DG-04 | BM-DG; BM-ST-05 | BR-006–012; G-W02 | §4.7 | — | — | javna verzija | §7; §10.6 |
| Validacije / gate-ovi | BM-DG-01, BM-DG-07–08 | BR-015–020; BR-028–039; BR-064 | §6 | §3–§4 | — | naslov prikaz | §9.5; §10.7 |
| Nested Održavanja | BM-TR | BR-056–061; BR-082–091 | — | v0.1.5; N-TR-01/02/04 | — | — | §10.9 |
| Organizator / Manifestacija | BM-DG; BM-MF; BM-ORG | BR-129–134 | — | — | — | — | §10.10–§10.11 |
| Naslovna fotografija | BM-MD-06 | BR-117 | — | — | TS-008 | fallback | §10.12 |
| Delete događaja nije V1 | BM-DG-04; BM-ST-08 | BR-065 | §4.10 | — | — | — | §10.13 |
| Konflikt verzije | — | — | §5 (pregled) | — | — | — | §10.14 |
| Guard CRUD | BM-EP; BM-MOD-04 | Platformsko; BR-051 | — | — | — | — | §7.3; §10.15 |
| Lokalni audit CRUD | — | — | — | — | — | — | §10.17 (≠ FT-003) |

Lanac sljedivosti:

```text
BM → FS → TS-001 → TS-003 → TS-004 → TS-008 → TS-009 → TS-010.1 → … → TS-010.5
```

Napomena: matrica ne uvodi nove BM ili BR identifikatore. N-DG-04 ostaje implementaciona granica skladištenja.

## 8.6 TS-010.6

| TS-010.6 tema | BM | FS | TS-001 | TS-003 | TS-010 | PO |
|---------------|----|----|--------|--------|--------|-----|
| Svrha / radna tabla | BM-EP-01; BM-EP-03 | BR-118; BR-126 | — | — | §1; §11.3 | PO-DASH-01–02 |
| Uloge / vidljivost | BM-EP-02; BM-EP-06; BM-MOD-04; BM-UR | BR-122–BR-125; BR-051 | §3–§5 | — | §1; §2; §3; §6; §10.4 | PO-DASH-01 |
| Operativne kategorije (ne BI) | BM-ST; BM-DG | BR-013–BR-044; BR-062–BR-066 | — | §3–§5 | §7; §10.5 | PO-DASH-03–04 |
| Brojač + klik → filter lista | BM-EP-03 (pregled statusa) | BR-126 | — | — | §10; §11.6 | PO-DASH-04 |
| Jedinstven raspored po ulozi | — | — | — | — | §11.5 | PO-DASH-05 |
| Nije FT-003 / TS-012 / Activity Feed | BM-EP-09; BM-14; BM-AL | §5.16; BR-128 ≠ feed | — | — | §10.17; §11.9 | — |
| Nije javni portal / TS-009 statistike | BM-PK-22 | BR-263; §5.2 | — | — | granica §4; §11.9 | — |
| Nije Newsletter | BM-NL | BR-128; §5.15 | — | — | §11.9 | — |
| Zahtjevi Org./Moderator (Urednik) | BM-ORG; BM-MOD | BR-047–BR-055; BR-070–073 | §4 | — | §6 | — |

Lanac sljedivosti:

```text
BM → FS → TS-001 → TS-003 → TS-010.1 → … → TS-010.5 → TS-010.6
```

Napomena: matrica ne uvodi nove BM ili BR identifikatore. PO-DASH-01–PO-DASH-05 su usvojene product odluke za Dashboard; ne zamjenjuju BM/FS.

## 8.7 TS-010.7

| TS-010.7 tema | BM | FS | TS-001 / TS-003 | FT-003 / TS-012 | TS-010 | PO |
|---------------|----|----|-----------------|-----------------|--------|-----|
| Obaveza evidentiranja (bez UI centralne) | BM-EP-09; BM-AL-01–05, BM-AL-07–08 | BR-170–173; §5.16 tok | §8 (emisija) | Prima zapise | §12 | PO-AL-01, PO-AL-03 |
| Pristup: samo Administrator | BM-AL-06; BM-GL-09 | BR-174–175 | — | Direktan pristup | §1.4; §12.5 | PO-AL-01 |
| Lokalni audit ≠ centralna | BM-AL-02 | BR-171; razgraničenje §5.16 | §8 lokalni | ≠ lokalni | §6.11; §10.17; §12.3 | PO-AL-02 |
| V1 katalog (bez proširenja) | BM-AL-07 | BR-177–185; katalog §5.16 | — | Katalog | §12.6 | PO-AL-04 |
| Van: API/SQL/struktura/prikaz | — | Granice V1 FT-003 | — | TS-012 | §12.8 | PO-AL-03 |
| Dashboard ≠ Activity Feed / FT-003 | — | — | — | — | §11.9 | PO-DASH |

Lanac sljedivosti:

```text
BM-14 / BM-EP-09 → FS §5.16 → FT-003 / TS-012
                ↘ TS-001 / TS-003 (§8) → TS-010.7 (obaveza portala)
```

Napomena: matrica ne uvodi nove BM ili BR identifikatore. PO-AL-01–PO-AL-04 su usvojene product odluke za TS-010.7; ne zamjenjuju BM/FS niti FT-003.

---

# 9. Katalog sadržajnih polja događaja (N-DG-02)

**Odluka:** V1 uvodi **zatvoreni** katalog sadržajnih polja događaja. Ne uvode se nova poslovna polja niti se proširuje scope Business Modela. N-DG-02 više **nije** otvoreno pitanje u okviru TS-010.

TS-010.5 (CRUD događaja i validacije) **operacionalizuje** ovaj katalog; ne redefiniše lifecycle (TS-010.4 / TS-003) niti model održavanja (TS-004).

## 9.1 V1 katalog sadržajnih polja

### Osnovni podaci

* Naslov događaja
* Opis događaja

### Organizacija

* Organizator (0..1)
* Manifestacija (0..1)

### Klasifikacija

* Primarna kategorija
* Oznake (0..N)

### Mediji

* Naslovna fotografija (0..1)

### Održavanja

* Održavanja (0..N u nacrtu; najmanje jedno za slanje na odobrenje i objavu) — preduslov / relacija; detalj entiteta = **TS-004**

## 9.2 Nisu sadržajna polja događaja

Sljedeće **nijesu** sadržajna polja događaja (sistemska ili urednička svojstva):

* status događaja
* lifecycle
* istaknutost (Feature)
* aktivni prijedlog izmjene
* audit podaci
* kreator
* vrijeme kreiranja
* posljednja izmjena
* korisnik posljednje izmjene

## 9.3 Granica događaj ↔ održavanje

Lokacija, datum, vrijeme, status održavanja i ostali podaci termina pripadaju entitetu **Održavanje**, a ne entitetu **Događaj** (BM-DG-03; TS-004).

TS-010.5 ne redefiniše TS-004. Formalni oblik vremenskih polja održavanja (N-TR-01 i srodna) ostaje u domenu TS-004.

## 9.4 Van obuhvata V1 (sadržajna polja)

Nijesu dio sadržajnih polja događaja (FS §5.4.9; potvrđeni obim V1, ne privremeno ograničenje):

* kontakt
* web linkovi
* društvene mreže
* GPS
* galerija fotografija
* dokumenti
* cijena
* rezervacije
* SEO podaci

## 9.5 Obaveznost (postojeći gate-ovi)

Usklađeno sa BM/FS / TS-003 (bez novih pravila). Detalj forme i poruka validacije = TS-010.5.

| Stavka | Nacrt | Slanje / objava |
|--------|-------|-----------------|
| Primarna kategorija | Opciono | Obavezno (BM-DG-07) |
| Organizator | 0..1 (izuzetak bez Org — Urednik) | Isto (BM-DG-08) |
| Manifestacija | Opciono | Opciono |
| Oznake | Opciono | Opciono |
| Naslovna fotografija | Opciono | Opciono (fallback prikaza; BM-MD-06) |
| Održavanja | 0..N | ≥1 (BM-DG-01) |

Naslov i opis su sadržajna polja kataloga (§9.1). **Naslov** može nedostajati u nepotpunom Nacrtu (BR-015); obavezan je za slanje na odobrenje i objavu kao operacionalizacija BR-017 i zahtjeva javnog prikaza (FS §5.4.2 / TS-009), ne kao novo polje. Detalj validacija po akcijama = §10.7.

## 9.6 Veza sa TS-003

TS-003 §6.2 / §6.4 dokumentuje potvrđeni konceptualni skup i ranije otvoreno N-DG-02. Ova sekcija **zatvara N-DG-02** za V1 sadržajni katalog u okviru uredničkog portala, bez izmjene fajla TS-003 i bez proširenja BM.

---

# 10. TS-010.5 — CRUD događaja i validacije

Izvori: BM-ST, BM-DG, BM-TR, BM-MD, BM-MF, BM-MOD, BM-UR; BR-006–BR-044, BR-052, BR-056–BR-069, BR-082–BR-091, BR-117, BR-129–BR-134; TS-003; TS-004 v0.1.5; TS-008; TS-009; TS-010.1–TS-010.4; §9 (N-DG-02); N-DG-04; N-TR-01; N-TR-02; N-TR-04; G-W02.

TS-010.5 ne uvodi nova poslovna pravila, nove BM/BR identifikatore, nova sadržajna polja, nove statuse, nove workflow grane, novu poslovnu ulogu niti entitet Serija. Tehnički operacionalizuje usvojene BM/FS i zatvoreni katalog §9.

## 10.1 Obuhvat i granice

**Obuhvat:** Create / Read / Update događaja; prijedlog izmjene Objavljenog; nested CRUD Održavanja; validacije po akcijama; editabilnost po statusu i fazi; naslovna fotografija; veze Organizator / Manifestacija; statusna i sadržajna granica; Delete događaja kao nepodržana operacija; tehnička granica N-DG-04; osnovni zahtjev zaštite od konflikta izmjena; lokalni audit za poslovni integritet.

**Van obuhvata:** vidi §10.16.

## 10.2 Kanonski V1 katalog (referenca §9)

Sadržajna polja (bez proširenja):

| Grupa | Polja |
|-------|-------|
| Osnovni podaci | naslov; opis |
| Organizacija | Organizator `0..1`; Manifestacija `0..1` |
| Klasifikacija | primarna kategorija; oznake `0..N` |
| Mediji | naslovna fotografija `0..1` |
| Održavanja | lista prema TS-004 |

**Nijesu sadržajna polja** (FS §5.4.9 / §9.3): status; lifecycle; istaknutost (Feature); aktivni prijedlog izmjene; audit; kreator; timestamps; posljednji korisnik koji je mijenjao.

**Van V1 sadržaja:** kontakt; web linkovi; društvene mreže; GPS; galerija; dokumenti; cijena; rezervacije; SEO.

## 10.3 Create događaja

### 10.3.1 Moderator Organizatora

Može kreirati događaj:

* samo za Organizatora za kojeg ima aktivno ovlašćenje;
* samo u aktivnom kontekstu;
* samo ako je Organizator **Aktivan**.

Početni status: **Nacrt**.

Moderator **ne** kreira događaj bez Organizatora.

### 10.3.2 Urednik

Može kreirati događaj **bez Organizatora** prema postojećem izuzetku (BM-ST-04 / BR-018 i srodna).

### 10.3.3 Početni Nacrt

Može biti nepotpun (BR-015).

Može imati `0..N` Održavanja.

Fizičko uklanjanje Održavanja dozvoljeno je prema **N-TR-04** samo dok je riječ o **početnom Nacrtu** koji nikada nije bio poslat, pregledan niti objavljen. Nakon prvog slanja / pregleda / objave — nema fizičkog Delete Održavanja.

## 10.4 Read prava

| Uloga | Opseg pregleda |
|-------|----------------|
| Moderator Organizatora | Događaji njegovog **aktivnog** Organizatora; svi relevantni interni statusi u tom kontekstu. Nakon deaktivacije Organizatora — **nema** operativnih prava (BM-MOD-16). |
| Urednik | Svi događaji; svi Organizatori; svi statusi; prijedlozi izmjena; Arhivirani događaji za pregled. |
| Administrator platforme | Nema redovnu poslovnu CRUD ulogu u Uredničkom portalu. |

Deaktivacija Organizatora **ne briše** događaje niti istoriju.

## 10.5 Update po statusu i fazi

| Status / faza | Moderator | Urednik | Način izmjene |
|---------------|-----------|---------|---------------|
| Početni Nacrt (nikad poslat/pregledan/objavljen) | Uređuje u svom kontekstu | Prema svojim pravilima | Direktno; fizičko uklanjanje Održavanja po N-TR-04 |
| Nacrt nakon vraćanja na doradu | Može uređivati | Može uređivati | Direktno; **nema** fizičkog Delete Održavanja; istorija se čuva |
| Na odobrenju — prije početka pregleda | Može uređivati ili povući (postojeća pravila) | Prema pravilima | Direktno; bez projektovanja konkretnog lock mehanizma |
| Na odobrenju — tokom pregleda | **Ne** uređuje | Može uređivati tokom pregleda | Direktno; serverska autorizacija obavezna |
| Objavljen | Uređuje **prijedlog**, ne javnu verziju | Pregleda / uređuje / odobrava / vraća prijedlog | Prijedlog izmjene; javna verzija = posljednja odobrena |
| Otkazan | **Ne** može ponovo objaviti; ne uređuje za republiku | Može urediti sadržaj prije ponovne objave | Direktno (Urednik); puni gate-ovi prije ponovne objave |
| Arhiviran | Read-only | Read-only | **Nema** uređivanja; **nema** izlaznog workflow prelaza; **nema** ručnog vraćanja u drugi status (V1) |

## 10.6 Prijedlog izmjene i N-DG-04

Poslovno / funkcionalno ponašanje (bez modela baze):

* najviše **jedan** aktivni prijedlog izmjene;
* Moderator uređuje **prijedlog**, ne direktno Objavljeni događaj;
* javnost vidi posljednju **odobrenu** verziju;
* Urednik pregleda, uređuje, odobrava ili vraća prijedlog;
* odobrenjem prijedlog postaje nova javna verzija;
* vraćanjem na doradu prijedlog ostaje odvojen od javne verzije;
* otkazivanje događaja čini aktivni prijedlog **neoperativnim** prema **G-W02** (TS-010.4);
* nakon ponovne objave eventualne izmjene počinju **novim** prijedlogom.

**N-DG-04:** Način fizičkog skladištenja verzija i prijedloga predstavlja **implementacioni izbor**. TS-010.5 **ne** propisuje: posebnu tabelu; snapshot; JSON model; verzijski broj; tehnički status prijedloga; naziv kolone; event sourcing.

TS-010.5 propisuje UX ponašanje, validacije i integritet bez projektovanja baze.

## 10.7 Validacije po akcijama

Sve ključne provjere izvršavaju se **serverski**. Vidljivost UI kontrole nije dovoljna autorizacija.

### 10.7.1 Sačuvaj Nacrt

| Provjera | Obavezno |
|----------|----------|
| Tehnički integritet unesenih vrijednosti i veza | Da |
| Naslov (postoji / nije prazan) | **Ne** (nepotpun Nacrt dozvoljen) |
| Pun sadržajni gate slanja/objave | Ne |

### 10.7.2 Pošalji na odobrenje

| Provjera | Obavezno |
|----------|----------|
| Naslov postoji i nije prazan nakon normalizacije | Da |
| Primarna kategorija postoji | Da |
| Organizator postoji (događaj Organizatora) | Da |
| Organizator je Aktivan | Da |
| Moderator: aktivno ovlašćenje + kontekst | Da |
| ≥1 validno Održavanje; sva po TS-004 | Da |
| Ostale obavezne veze i poslovni gate-ovi | Da |

Naslov kao gate slanja/objave = operacionalizacija **BR-017** i zahtjeva javnog prikaza; nije novo polje.

### 10.7.3 Direktna objava događaja bez Organizatora (Urednik)

Isti sadržajni gate-ovi koji važe za objavu (uključujući naslov, kategoriju, ≥1 Održavanje, itd.).

### 10.7.4 Sačuvaj prijedlog izmjene

Može biti privremeno nepotpun u fazi izrade; unesene vrijednosti moraju biti tehnički validne.

### 10.7.5 Pošalji prijedlog na pregled

Puni sadržajni i statusni gate-ovi (kao za slanje/objavu sadržaja).

### 10.7.6 Odobri prijedlog

Ponovo: autorizacija; status; sadržajni gate-ovi; Održavanja; validnost veza; odsustvo neoperativnog ishoda **G-W02**.

### 10.7.7 Ponovna objava Otkazanog

Puni objavni gate: naslov; kategorija; Organizator ako postoji; ≥1 validno Održavanje; sve relevantne veze i validacije.

## 10.8 Naslov, opis i tehnička ograničenja

* Naslov je sadržajno polje; može nedostajati u nepotpunom Nacrtu; **obavezan** za slanje i objavu.
* Opis je opcion.

Konkretne maksimalne dužine, tip skladištenja i tehničke poruke validacije ostaju implementaciji.

**Zahtjev:** Ograničenja u bazi, serverskoj validaciji i korisničkom interfejsu moraju biti međusobno usklađena.

Ne uvode se proizvoljni brojčani limiti u ovom dokumentu. Ne uvodi se rich-text / HTML podrška ako nije definisana drugim usvojenim izvorom.

## 10.9 Održavanja — nested CRUD

Koristi se **TS-004 v0.1.5** bez redefinisanja. Model: **jedno Održavanje = jedan kalendarski datum** (N-TR-01).

### 10.9.1 Create

* ručno dodavanje;
* generator dnevno / sedmično / mjesečno (N-TR-02);
* kraj: broj **ili** krajnji datum;
* najviše **100** po jednom generisanju;
* sva generisana Održavanja postaju **nezavisna**;
* entitet **Serija ne postoji**; nema regenerate / edit-all / brisanje cijele grupe.

### 10.9.2 Read

Prikaz liste Održavanja unutar forme Događaja.

### 10.9.3 Update

Datum; opciono vrijeme početka/završetka; cjelodnevnost; lokacija; druga usvojena polja TS-004.

Na **Objavljenom** događaju izmjena podataka Održavanja ide kroz **prijedlog izmjene**.

Statusne promjene Održavanja — prema TS-004 i TS-010.4.

### 10.9.4 Delete (Održavanje)

Fizičko uklanjanje dozvoljeno **samo**:

* u početnom Nacrtu;
* prije prvog slanja;
* prije uredničkog pregleda;
* prije objave.

Nakon toga **nema** Delete Održavanja. Otkazivanje ≠ Delete (N-TR-04).

**Nije** Delete Događaja (§10.13).

## 10.10 Organizator

* Veza `0..1`.
* Događaj bez Organizatora kreira samo Urednik (postojeći izuzetak).
* Moderator **ne može** mijenjati Organizatora događaja.
* Urednik može naknadno povezati događaj sa Organizatorom (postojeće pravilo).
* Promjena veze ne smije zaobići autorizaciona i kontekstna pravila.
* Deaktiviran Organizator prekida operativna prava Moderatora.
* Podaci Organizatora se **ne kopiraju** u događaj kao zasebna sadržajna polja.

## 10.11 Manifestacija

* Veza `0..1`.
* Događaj ima **nezavisan** lifecycle.
* Događaj **ne nasljeđuje** sadržaj Manifestacije.
* Link / unlink = sadržajna izmjena Događaja.
* Na Objavljenom — promjena veze kroz prijedlog izmjene.
* Manifestacija **ne određuje** CRUD polja događaja.

## 10.12 Naslovna fotografija (TS-008)

* Opciona veza `0..1` prema Mediju.
* Moderator može izabrati ili dodati Medij prema dozvoljenim tokovima.
* Urednik — prava prema TS-008.
* Uklanjanje veze naslovne **nije** brisanje Medija.
* Fallback prikaza — postojeća pravila (BM-MD-06).
* MIME, veličina, naziv, ALT, lifecycle Medija — **TS-008**.
* Na Objavljenom — promjena naslovne kroz **prijedlog izmjene**.
* **Feature (istaknutost)** nije sadržajno polje i **ne ide** kroz sadržajni prijedlog.

Kompletan CRUD Medija nije dio TS-010.5.

## 10.13 Delete događaja

```text
Delete Događaja nije podržan u V1.
```

Nema: fizičkog brisanja; soft delete; recycle bin; brisanja početnog Nacrta; administratorskog Delete toka.

Događaj koristi postojeći lifecycle i automatsko arhiviranje (BM-DG-04 / BR-065 / TS-004).

Ovo pravilo **nije** isto što i fizičko uklanjanje Održavanja iz početnog Nacrta (N-TR-04 / §10.9.4).

## 10.14 Konflikti istovremenog uređivanja

**Minimalni zahtjev:** Sistem ne smije tiho prepisati noviju sačuvanu izmjenu zastarjelim podacima drugog korisnika ili druge sesije.

Tehnologija zaključavanja / optimistic concurrency **nije** propisana ovim dokumentom. Dozvoljeni su različiti implementacioni izbori; nijedan se ne navodi kao jedino normativno rješenje.

Početak uredničkog pregleda i postojeća pravila zaključavanja ostaju prema TS-003 i TS-010.4.

Konkurentno uređivanje dva Moderatora ili više tabova **ne uvodi** novu poslovnu odluku.

## 10.15 Guard redoslijed (CRUD)

Nasljeđuje TS-010.4 §7.3, prošireno za CRUD:

```text
autentikacija
→ platformski pristup
→ aktivni kontekst
→ poslovno ovlašćenje
→ status/faza
→ dozvoljena CRUD operacija
→ funkcionalne validacije
→ provjera konflikta verzije
→ izvršenje
```

Moderator **nema** posebnu platformsku rolu (TS-010.1 / G-17).

## 10.16 Van obuhvata TS-010.5

* Dashboard (TS-010.6);
* kompletan workflow osim referenci na TS-010.4;
* centralna Evidencija aktivnosti (FT-003 / TS-012; obaveze portala — TS-010.7);
* Newsletter; Obavještenja;
* javni portal (TS-009);
* kompletan CRUD Medija;
* API; rute; modeli baze; migracije;
* detaljan UI dizajn;
* implementaciona tehnologija zaključavanja / skladištenja verzija (N-DG-04).

## 10.17 Audit granica

Podaci potrebni za poslovni integritet CRUD-a:

* kreator;
* vrijeme kreiranja;
* posljednji korisnik koji je mijenjao;
* vrijeme posljednje izmjene;
* istorija workflow odluka prema postojećim pravilima.

**Ne** projektuje se centralna Evidencija aktivnosti FT-003 / TS-012. **Ne** uvodi se novi audit entitet.

## 10.18 Napomena — FS §5.4.3

U TS-010.5 koristi se usvojeni TS-004 model: **jedno Održavanje = jedan kalendarski datum** (N-TR-01).

FS se **ne mijenja** u ovoj verziji. Doslovno terminološko usklađenje FS §5.4.3 sa N-TR-01 ostaje **zaseban dokumentacioni cleanup** prije finalnog zatvaranja TS-010. To **nije** blokator TS-010.5.

---

# 11. TS-010.6 — Dashboard uredničkog portala

Izvori: BM-EP-01–BM-EP-06, BM-EP-09; BM-ST; BM-DG; BM-MOD; BM-UR; BM-ORG; BR-118–BR-128; BR-013–BR-044; BR-047–BR-055; BR-062–BR-066; BR-070–BR-073; BR-051; TS-001; TS-003; TS-010.1–TS-010.5; **PO-DASH-01–PO-DASH-05**.

TS-010.6 ne uvodi nova poslovna pravila, nove statuse, nove entitete, nove KPI niti BI. Tehnički operacionalizuje usvojene BM/FS i product odluke PO-DASH-01–05 nad već usvojenim lifecycle-om i Read/CRUD pravilima.

## 11.1 Obuhvat i granice

**Obuhvat:** radna tabla uredničkog portala; radne kategorije sa brojačem; navigacija na CRUD/listu sa filterom; uloge Moderator i Urednik; poštovanje konteksta i ovlašćenja.

**Van obuhvata:** vidi §11.10.

## 11.2 Usvojene product odluke

| Odluka | Sažetak |
|--------|---------|
| **PO-DASH-01** | Dashboard je radna tabla prijavljenog korisnika; sadržaj po ulozi, ovlašćenjima i aktivnom kontekstu; osnovna svrha = brz nastavak rada; statistika je pomoćna. |
| **PO-DASH-02** | Prikazuje informacije za nastavak rada; analitika/statistika pomoćna; **nije** izvještavanje niti poslovna analitika. |
| **PO-DASH-03** | Samo stavke koje zahtijevaju akciju korisnika ili predstavljaju aktivni rad; svaka kategorija ima poslovno opravdanje; bez informacija bez operativnog značaja. |
| **PO-DASH-04** | Sažete radne kategorije sa brojačem; klik → lista sa unaprijed primijenjenim filterom; **nema** liste događaja na Dashboardu; **ne** duplira CRUD pregled. |
| **PO-DASH-05** | Jedinstven raspored za sve korisnike iste uloge; bez personalizacije, drag&drop, skrivanja kartica i korisničkih layout-a. |

## 11.3 Svrha

Dashboard omogućava **brz nastavak operativnog rada** u Uredničkom portalu (PO-DASH-01, PO-DASH-02).

Brojači na kategorijama služe orijentaciji u radu; **nijesu** KPI, trendovi ni izvještaji.

## 11.4 Namjena po ulogama

### 11.4.1 Moderator Organizatora

Vidi Dashboard isključivo u okviru:

* aktivnog ovlašćenja;
* **aktivnog konteksta** Organizatora;
* Read opsega iz TS-010.5 §10.4.

Nakon deaktivacije Organizatora — **nema** operativnog Dashboarda za taj Organizator (BM-MOD-16).

**Ne vidi** podatke drugih Organizatora niti globalni urednički skup.

### 11.4.2 Urednik

Vidi Dashboard sa **globalnim** operativnim kategorijama u skladu sa uredničkim ovlašćenjima (TS-010.1 §1.2; TS-010.5 §10.4).

**Nema** aktivni kontekst Organizatora.

### 11.4.3 Administrator platforme

Dashboard uredničkog portala **nije** namijenjen Administratoru platforme kao redovnoj poslovnoj CRUD ulozi (TS-010.1 §1.4). Centralna Evidencija aktivnosti ostaje **FT-003 / TS-012**. Obaveze uredničkog portala prema toj evidenciji: **TS-010.7** (PO-AL-01).

## 11.5 Autorizacija i kontekst

Svaki prikaz i svaki klik-navigacija poštuju:

```text
autentikacija
→ platformski pristup
→ aktivni kontekst (Moderator)
→ poslovno ovlašćenje
→ Read opseg
→ filter kategorije
→ otvaranje CRUD/liste
```

Vidljivost kategorije **nije** dovoljna autorizacija za radnje na listi (TS-010.1 §3; TS-010.5 §10.15).

## 11.6 Model prikaza

Za svaku radnu kategoriju:

1. **Naziv** kategorije (poslovno opravdan);
2. **Brojač** (broj entiteta koji zadovoljavaju filter u opsegu korisnika);
3. **Klik** otvara postojeću CRUD/listu sa **unaprijed primijenjenim** filterom (PO-DASH-04).

Dashboard **ne** sadrži:

* tabele događaja;
* „posljednjih X“ događaja;
* ugrađeni CRUD pregled.

Brojač `0` ostaje dozvoljen; kategorija ostaje vidljiva u jedinstvenom rasporedu uloge (PO-DASH-05), osim ako korisnik uopšte nema pravo na tu kategoriju.

## 11.7 Radne kategorije — Moderator

Opseg: događaji **aktivnog** Organizatora. Ne uvode se novi statusi; „vraćen na doradu“ ostaje status **Nacrt** (TS-010.4).

| ID | Kategorija | Filter (postojeći status / faza) | Poslovno opravdanje | Ciljna lista |
|----|------------|----------------------------------|---------------------|--------------|
| DM-01 | Nacrti | Status **Nacrt** | Aktivni rad / nastavak uređivanja / slanje | Lista događaja + filter Nacrt |
| DM-02 | Na odobrenju | Status **Na odobrenju** | Aktivan tok; uređivanje/povlačenje prije pregleda ili praćenje do odluke | Lista događaja + filter Na odobrenju |
| DM-03 | Aktivni prijedlozi izmjene | Status **Objavljen** + **operativan** aktivni prijedlog izmjene | Nastavak rada na prijedlogu (uređivanje / slanje na pregled) | Lista događaja + filter aktivni prijedlog |

**Nijesu** kategorije Moderatora (nema operativne akcije / van PO-DASH-03):

* Objavljen **bez** aktivnog prijedloga;
* Otkazan (Moderator ne ponovo objavljuje);
* Arhiviran (read-only V1);
* događaji drugih Organizatora;
* neoperativni prijedlog nakon otkazivanja (**G-W02**).

## 11.8 Radne kategorije — Urednik

Opseg: globalni, prema uredničkim ovlašćenjima.

| ID | Kategorija | Filter (postojeći status / faza / zahtjev) | Poslovno opravdanje | Ciljna lista |
|----|------------|-------------------------------------------|---------------------|--------------|
| DU-01 | Čeka pregled (događaji) | Status **Na odobrenju** | Urednička odluka / pregled | Lista događaja + filter Na odobrenju |
| DU-02 | Prijedlozi izmjene na pregledu | Status **Objavljen** + prijedlog u fazi pregleda / čeka odluku Urednika | Odobrenje / vraćanje / uređivanje prijedloga | Lista događaja + filter prijedlog na pregledu |
| DU-03 | Nacrti bez Organizatora | Status **Nacrt** i događaj **bez** Organizatora | Aktivni rad Urednika po postojećem izuzetku | Lista događaja + filter Nacrt bez Org. |
| DU-04 | Zahtjevi za Organizatora | Otvoreni zahtjevi za kreiranje Organizatora (TS-001) | Odobrenje / odbijanje zahtjeva | Lista zahtjeva + filter otvoreno |
| DU-05 | Zahtjevi za Moderatore | Otvoreni zahtjevi za dodjelu / uklanjanje Moderatora (TS-001 / TS-010.3) | Odluka Urednika | Lista zahtjeva Moderatora + filter otvoreno |

**Nijesu** kategorije Urednika na Dashboardu (bez inventisanja inventara / BI):

* svi Objavljeni;
* svi Arhivirani;
* kompletna evidencija Otkazanih kao statistika (ponovna objava ostaje dostupna kroz CRUD/workflow, ne kao Dashboard inventar);
* FT-003 Activity Feed;
* javne statistike TS-009.

## 11.9 Šta Dashboard nije

Dashboard **nije**:

* BI / poslovna analitika / izvještaj (PO-DASH-02);
* Activity Feed;
* centralna Evidencija aktivnosti (**FT-003** / **TS-012**);
* lokalni audit kao feed;
* **javni portal** niti statistike **TS-009** (Danas / Ove sedmice / Izabrani mjesec);
* Newsletter;
* zamjena za CRUD listu događaja;
* kanal isporuke BR-032 (**N-DG-03** ostaje otvoren; Dashboard ne zatvara taj kanal).

BR-128 (pregled poslovnih obavještenja i sistemskih informacija) ostaje sposobnost portala; **nije** isto što i Dashboard radne kategorije ovog poglavlja. Detalj UI obavještenja nije dio TS-010.6 ako nije vezan za radne kategorije §11.7–§11.8.

## 11.10 Van obuhvata TS-010.6

* grafici; trendovi; KPI; Top liste;
* Dashboard statistike van operativnih kategorija §11.7–§11.8;
* prečice (Quick actions) koje nijesu usvojene;
* personalizacija; widget konfiguracija; drag&drop; korisnički layout; skrivanje kartica (PO-DASH-05);
* implementacija CRUD listi i filter-UI (već TS-010.5 / TS-001);
* API; SQL; migracije; detaljan UI dizajn;
* FT-003 / TS-012 (centralna Evidencija; vidi TS-010.7 za obaveze portala);
* Newsletter; javni portal;
* zatvaranje N-DG-03.

## 11.11 Raspored

Jedinstven raspored kategorija za sve korisnike **iste uloge** (PO-DASH-05).

Redoslijed kategorija unutar uloge je tehnički/UX izbor implementacije, uz uslov da sadržaj ostane isti za sve korisnike te uloge.

## 11.12 Brojači

* Brojač = broj entiteta u opsegu korisnika koji zadovoljavaju filter kategorije.
* Računa se uz poštovanje Read pravila i aktivnog konteksta.
* Nije trend, prosjek, udio ni vremenski niz.
* Konkretna tehnologija agregacije ostaje implementaciji.

## 11.13 Veza sa CRUD i workflow

Dashboard **navigira** ka postojećim listama/formama; ne redefiniše:

* statuse (TS-010.4 / TS-003);
* editabilnost (TS-010.5 §10.5);
* guard redoslijed;
* prijedlog izmjene / N-DG-04;
* Delete pravila.

---

# 12. TS-010.7 — Evidencija aktivnosti uredničkog portala

Izvori: BM-EP-09; BM-14 (BM-AL-01–BM-AL-08); BM-GL-09; BM-GL-20; FS §5.16 (BR-170–BR-188); TS-001 §8; TS-003 §8; TS-010.1–TS-010.6; Feature Registry FT-003 / TS-012; **PO-AL-01–PO-AL-04**.

TS-010.7 ne uvodi nova poslovna pravila, nove aktivnosti, novi audit entitet niti UI centralne Evidencije. Tehnički operacionalizuje obaveze Uredničkog portala prema usvojenom BM-14 / FS §5.16.

## 12.1 Obuhvat i granice

**Obuhvat:** razgraničenje lokalnog audita i centralne Evidencije; obaveza evidentiranja radnji nastalih u Uredničkom portalu; pristupna pravila; referenca na V1 katalog bez proširenja; odnos prema FT-003 / TS-012.

**Van obuhvata:** vidi §12.8.

## 12.2 Usvojene product odluke

| Odluka | Sažetak |
|--------|---------|
| **PO-AL-01** | TS-010.7 = obaveze portala prema centralnoj Evidenciji; **ne** uvodi zaseban pregled centralne evidencije; **ne** zamjenjuje FT-003 ni TS-012; Moderator i Urednik **nemaju** direktan pristup; pristup ima isključivo Administrator platforme. |
| **PO-AL-02** | Lokalni audit i centralna Evidencija su **dva različita** koncepta. |
| **PO-AL-03** | TS-010.7 definiše samo obavezu evidentiranja; **ne** opisuje API, queue, event bus, SQL, tabelu, strukturu zapisa, skladištenje ni prikaz centralne evidencije (to je FT-003 / TS-012). |
| **PO-AL-04** | Ne uvodi nove poslovne aktivnosti; koristi isključivo BM/FS katalog; ne dodaje otvaranje forme, klik, filter, pretragu, promjenu konteksta, lock/unlock, pregled bez izmjene ni druge nove aktivnosti. |

## 12.3 Dva koncepta (PO-AL-02)

### 12.3.1 Lokalni audit

* postoji na entitetu / zahtjevu;
* prikazuje istoriju **tog** entiteta ovlašćenim ulogama u okviru rada nad entitetom;
* **nije** centralni audit;
* **nije** FT-003;
* primjeri i norme: TS-001 §8; TS-003 §8; TS-010.3 §6.11; TS-010.5 §10.17; BR-014, BR-026, BR-031, BR-043, BR-055, BR-073 i srodna.

Prikaz lokalnih audit informacija **ne smatra se** direktnim pristupom centralnoj Evidenciji (BM-AL-06; BR-175).

### 12.3.2 Centralna Evidencija aktivnosti

* poslovni audit log radnji modula Kalendar kulture;
* opisana BM-14 / FS §5.16;
* Feature / TS: **FT-003** / **TS-012**;
* nije sredstvo komunikacije niti poslovno obavještenje (BM-AL-08; BR-170);
* ne zamjenjuje tehničke sistemske logove (BM-AL-02; BR-172).

## 12.4 Obaveza uredničkog portala (PO-AL-01, PO-AL-03)

Kada se kroz Urednički portal izvrši radnja koja pripada V1 katalogu poslovno značajnih aktivnosti (FS §5.16), sistem **mora** omogućiti nastanak odgovarajućeg zapisa centralne Evidencije aktivnosti, u skladu sa BM-EP-09 i BM-AL-03.

TS-010.7:

* **ne** projektuje mehanizam isporuke (API, queue, event bus);
* **ne** projektuje skladištenje ni strukturu zapisa;
* **ne** projektuje Admin UI pregled.

Ti elementi ostaju **FT-003 / TS-012**.

Emisija / lokalni tragovi na nivou domena ostaju usklađeni sa TS-001 §8 i TS-003 §8; TS-010.7 ih ne redefiniše.

## 12.5 Pristup

| Uloga | Centralna Evidencija (direktno) | Lokalni audit na entitetu |
|-------|----------------------------------|---------------------------|
| Moderator Organizatora | **Ne** | Da, u okviru ovlašćenja i aktivnog konteksta |
| Urednik | **Ne** | Da, u okviru uredničkih ovlašćenja |
| Administrator platforme | **Da** (BM-AL-06; BR-174) | Nije redovna urednička CRUD uloga (TS-010.1 §1.4) |

Organizator kao poslovni entitet **ne pristupa** Uredničkom portalu niti centralnoj Evidenciji.

## 12.6 Katalog aktivnosti (PO-AL-04)

TS-010.7 **ne proširuje** katalog. Kanonski V1 katalog ostaje u **FS §5.16** (Moderator; Organizatori; Manifestacije; Događaji uključujući relevantne aktivnosti nad Održavanjem; Newsletter), uz BM-AL-07.

**Ne ulaze** u centralnu Evidenciju (već usvojeno u FS §5.16 / PO-AL-04), između ostalog:

* uređivanje nacrta i sitne korekcije;
* zaključavanje / otključavanje prijedloga;
* pregled bez izmjena;
* promjena aktivnog konteksta Organizatora (bilježi se samo kao atribut drugih zapisa kada je primjenjivo);
* otvaranje forme, klik, filter, pretraga kao zasebne aktivnosti;
* tehnički logovi i aktivnosti van poslovnog značaja.

## 12.7 Odnos prema Dashboardu i ostalim cjelinama

* Dashboard (**TS-010.6**) **ne** prikazuje Activity Feed niti centralnu Evidenciju.
* Newsletter i BR-128 **nijesu** kanali Evidencije aktivnosti.
* Javni portal (**TS-009**) ne upravlja Evidencijom aktivnosti.

## 12.8 Van obuhvata TS-010.7

* zaseban UI / pregled centralne Evidencije u Uredničkom portalu;
* filteri, pretraga, sortiranje, izvoz, retention politika, struktura polja zapisa (FT-003 / TS-012; van V1 opsega FT-003 do posebnog PATCH-a gdje je tako evidentirano);
* API; queue; event bus; SQL; tabele; migracije;
* nove poslovne aktivnosti van FS §5.16;
* redefinisanje lokalnih audit pravila TS-001 / TS-003;
* zamjena FT-003 ili TS-012.

## 12.9 Veza sa FT-003 / TS-012

```text
Urednički portal (FT-001 / TS-010)
        → izvršava radnje
        → obaveza evidentiranja (TS-010.7)
        → centralna Evidencija (FT-003 / TS-012)
```

TS-010.7 je cjelina **FT-001**. Centralna Evidencija aktivnosti je cjelina **FT-003**.

---

# 13. Planirane cjeline TS-010

Sljedeće podcjeline su planirane i **nisu razrađene** u ovoj verziji:

| Cjelina | Naziv | Status |
|---------|-------|--------|
| TS-010.8 | Test matrica | Planned |

---

# 14. Napomene za naredne podcjeline

* TS-010.4 operacionalizuje workflow akcije; ne zamjenjuje TS-003 / TS-004 / TS-009.
* TS-010.5 operacionalizuje CRUD i validacije uz katalog §9 i TS-004 v0.1.5; N-DG-04 ostaje implementacioni izbor.
* TS-010.6 operacionalizuje Dashboard (PO-DASH-01–05); ne uvodi BI ni Activity Feed.
* TS-010.7 (PO-AL-01–04) definiše obaveze portala prema centralnoj Evidenciji; ne zamjenjuje FT-003 / TS-012 i ne uvodi UI centralne evidencije.
* TS-010.8 ostaje planirana test matrica.
* TS-009 ostaje referentni dokument javnog portala; CR-004B ostaje Planned za implementaciju.
* Detaljna lista / katalog polja Organizatora ostaje van TS-010.3; usvojena granica ovlašćenja G-14 je u §6.8.1.
* Terminološki cleanup FS §5.4.3 ↔ N-TR-01 ostaje zaseban, van ove verzije.
* N-DG-03 (kanal obavještavanja Urednika) ostaje otvoren.

---

**Kraj dokumenta TS-010 v0.7.0 (TS-010.1–TS-010.7; TS-010.8 Planned)**
