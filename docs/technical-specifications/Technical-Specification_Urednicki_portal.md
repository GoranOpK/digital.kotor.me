# Digital Kotor
# Technical Specification
## Urednički portal

**Feature ID:** FT-001  
**Oznaka dokumenta:** TS-010  
**Funkcionalna cjelina:** Urednički portal Kalendara kulture  
**Modul:** Kalendar kulture  
**Status dokumenta:** U IZRADI  
**Verzija:** 0.1.1  
**Datum:** 2026-08-06

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-08-06 | TS-010.1 — Osnove uredničkog portala: korisnici, aktivni kontekst Organizatora, četvoroslojni autorizacioni model, arhitektura Platforma / Urednički portal / Javni portal. Bez razrade Organizatora, Moderatora, workflow-a, CRUD-a, dashboarda, evidencije i test matrice. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 0.1.1 | 2026-08-06 | QA korektivni prolaz TS-010.1: eksplicitna norma o tehničkoj operacionalizaciji BM/FS; poglavlje Terminologija; eksplicitna norma da javni portal nikada ne mijenja poslovne podatke. Bez proširenja obuhvata. Bez izmjene BM/FS. Bez izmjene implementacije. |

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

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-01, BM-02, BM-03, BM-12 BM-EP-01–BM-EP-10, BM-GL-06–BM-GL-09, BM-MOD-04, BM-UR-09)
* `docs/functional-specifications/Functional-Specification.md` (Platformsko pravilo; BR-007; BR-048; BR-051; §5.14 BR-118–BR-128)
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

---

# Van obuhvata

TS-010.1 **ne razrađuje**:

* Organizatore (detalj entiteta i tokova);
* Moderatore (detalj ovlašćenja i tokova);
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

Te cjeline ostaju u planiranim podcjelinama TS-010.2–TS-010.8, odnosno u postojećim TS dokumentima entiteta (TS-001, TS-003–TS-008), bez dupliciranja njihovih pravila.

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
| TS-010.2 Organizatori | Planned |
| TS-010.3 Moderator Organizatora | Planned |
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
5. Detaljna razrada cjelina van TS-010.1 ostaje u planiranim podcjelinama.
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
* deaktivacija Organizatora ili moderatorskog ovlašćenja automatski ukida aktivni kontekst.

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

Pristup Uredničkom portalu ostvaruju Moderatori i Urednici. Organizator nema pristup. Sistemska uloga Administratora platforme ne predstavlja uredničku ulogu.

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

# 5. Planirane cjeline TS-010

Sljedeće podcjeline su planirane i **nisu razrađene** u ovoj verziji:

| Cjelina | Naziv | Status |
|---------|-------|--------|
| TS-010.2 | Organizatori | Planned |
| TS-010.3 | Moderator Organizatora | Planned |
| TS-010.4 | Workflow događaja | Planned |
| TS-010.5 | CRUD događaja | Planned |
| TS-010.6 | Dashboard | Planned |
| TS-010.7 | Evidencija aktivnosti | Planned |
| TS-010.8 | Test matrica | Planned |

---

# 6. Matrica sljedivosti

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

Napomena: matrica ne uvodi nove BM ili BR identifikatore.

---

# 7. Napomene za naredne podcjeline

* TS-010.2–TS-010.3 oslanjaju se na TS-001 bez ponovnog definisanja entiteta Organizator/Moderator.
* TS-010.4–TS-010.5 oslanjaju se na TS-003 i FS §5.5 / §5.7 bez ponovnog definisanja lifecycle pravila događaja.
* TS-010.7 ostaje usklađen sa BM-14 / FS §5.16 / planiranim TS-012; ne zamjenjuje centralnu Evidenciju aktivnosti.
* TS-009 ostaje referentni dokument javnog portala; CR-004B ostaje Planned van obuhvata TS-010.1.

---

**Kraj dokumenta TS-010 v0.1.1 (TS-010.1)**
