# Digital Kotor
# Technical Specification
## Organizator, Moderator i zahtjev za kreiranje Organizatora

**Feature ID:** FT-001  
**Oznaka dokumenta:** TS-001  
**Funkcionalna cjelina:** Organizator, Moderator i zahtjev za kreiranje Organizatora  
**Modul:** Kalendar kulture  
**Status dokumenta:** Usvojen  
**Verzija:** 0.2.1  
**Datum:** 2026-07-30

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1 | 2026-07-28 | Prva verzija Technical Specification za funkcionalnu cjelinu Organizator / Moderator / Zahtjev za kreiranje Organizatora. Dokument usklađen sa usvojenim BM-01, BM-02, BM-03 (relevantni dijelovi), FS §5.6, §5.8 i Platformskim pravilom. Bez implementacionog dizajna baze, API-ja i koda. |
| 0.2 | 2026-07-28 | Redakcijsko usklađivanje strukture dokumenta sa M-TS-005 (standardna struktura TS). Bez izmjene poslovnih i funkcionalnih pravila. |
| 0.2.1 | 2026-07-30 | Documentation Consistency Patch (CR-001): usklađene statusne oznake dokumenta i status razvoja poglavlja sa stvarnim stanjem finalizovanog TS sadržaja. Bez izmjene poslovnih i funkcionalnih pravila. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.  
Kod svake naredne verzije dodaje se novi red u tabeli.  
Ne mijenjaju se postojeći redovi.

---

# Svrha dokumenta

Ovaj dokument opisuje kako će se usvojeni Business Model i Functional Specification za funkcionalnu cjelinu **Organizator**, **Moderator** i **Zahtjev za kreiranje Organizatora** tehnički realizovati u okviru FT-001 – Kalendar kulture.

TS-001 obrađuje jednu logički zaokruženu funkcionalnu cjelinu unutar FT-001 i ne predstavlja kompletnu tehničku specifikaciju svih cjelina Feature-a FT-001.

Dokument:

* ne uvodi nova poslovna pravila;
* ne zamjenjuje Business Model niti Functional Specification;
* nije Technical Overview trenutne implementacije;
* nije Change Request;
* ne definiše SQL, migracije, Laravel kod niti konkretne API ugovore.

Izvori istine za poslovna pravila:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-01, BM-02, BM-03 i povezana pravila)
* `docs/functional-specification/Functional-Specification.md` (Platformsko pravilo; §5.6; §5.8; relevantna pravila o događajima i auditu)
* `docs/features/Feature-Registry.md` (FT-001)
* `docs/METHODOLOGY.md`

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | Usvojeno |
| 2. Arhitektonski principi | Usvojeno |
| 3. Tehnički model | Usvojeno |
| 4. Tokovi | Usvojeno |
| 5. Autorizacija i ovlašćenja | Usvojeno |
| 6. Model podataka | Usvojeno |
| 7. Validacije | Usvojeno |
| 8. Evidencija aktivnosti (Audit) | Usvojeno |
| 9. Integracije | Usvojeno |
| 10. Nefunkcionalni zahtjevi | Usvojeno |
| 11. Granice V1 (Out of Scope) | Usvojeno |
| 12. Otvorena pitanja | Usvojeno |
| 13. Matrica sljedivosti | Usvojeno |
| 14. Napomene za implementaciju | Usvojeno |

---

# Pravila upravljanja ovim dokumentom

1. TS-001 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz Technical Specification.
4. Sve što nije definisano u BM ili FS evidentira se kao **Otvoreno pitanje**.
5. Product Owner donosi poslovne odluke; ovaj dokument ih ne pretpostavlja.
6. Izmjene usvojenog sadržaja u narednim verzijama evidentiraju se novim redom u istoriji verzija.

---

# 1. Pregled funkcionalne cjeline

Izvori

Business Model:
- BM-ORG-01–BM-ORG-12
- BM-MOD-01–BM-MOD-15
- BM-UR-01, BM-UR-05, BM-UR-08, BM-UR-09, BM-UR-10
- BM-EP-02, BM-EP-03, BM-EP-06

Functional Specification:
- Platformsko pravilo
- §5.6 (BR-045–BR-055, BR-135–BR-137)
- §5.8 (BR-070–BR-073)
- §5.16 (BR-178–BR-181)

## 1.1 Svrha funkcionalne cjeline

Funkcionalna cjelina omogućava da Kalendar kulture vodi **Organizatora** kao poslovni entitet i nosioca sadržaja, a da operativne radnje u njegovo ime obavljaju **Moderatori** — registrovani korisnici sa ovlašćenjem za konkretnog Organizatora.

Organizator:

* nije korisnik sistema;
* nije korisnička uloga;
* nema korisnički nalog na osnovu statusa Organizatora;
* ne prijavljuje se i ne pristupa portalu kao Organizator;
* ne izvršava neposredno radnje u sistemu.

Kreiranje Organizatora pokreće se **zahtjevom za kreiranje Organizatora**, koji podnosi registrovani korisnik. Ovlašćenja Moderatora nastaju isključivo nakon odobrenja Urednika.

## 1.2 Obuhvat dokumenta

Obuhvat TS-001:

* tehnički model Organizatora kao poslovnog entiteta;
* tehnički model ovlašćenja Moderatora u odnosu na Organizatora;
* tehnički model zahtjeva za kreiranje Organizatora;
* tehnički tokovi odobravanja / odbijanja, dodjele i uklanjanja Moderatora, te deaktivacije Organizatora;
* konceptualni model podataka (bez SQL / migracija);
* logički model autorizacije (bez middleware / Laravel koda);
* lokalni audit tragovi vezani za ovu cjelinu;
* integracione tačke prema korisnicima platforme, Uredničkom portalu, događajima i Evidenciji aktivnosti.

Van obuhvata ovog dokumenta:

* implementacija;
* migracije i fizički model baze;
* Laravel kod, rute, kontroleri, middleware;
* detaljni dizajn FT-003 (centralna Evidencija aktivnosti);
* workflow događaja u punoj širini (obrađen u FS §5.5 / §5.7; ovdje samo veze pripadnosti);
* Newsletter, lokacije, kategorije, mediji, javni portal — osim gdje utiču na Organizatora / Moderatora.

## 1.3 Zavisnosti

| Zavisnost | Uloga u odnosu na TS-001 |
|-----------|---------------------------|
| Platforma Digital Kotor – korisnički nalozi | Identitet podnosioca, predloženog Moderatora, aktivnog Moderatora i Urednika |
| Platformske uloge (Urednik / Administrator platforme) | Dodjela van Kalendara kulture; Kalendar koristi već dodijeljenu ulogu Urednika |
| Urednički portal | Operativni prostor Moderatora i Urednika |
| Događaji / Manifestacije | Pripadaju Organizatoru; Moderatori rade u aktivnom kontekstu Organizatora |
| Evidencija aktivnosti (FT-003) | Prima poslovno značajne događaje iz kataloga FS §5.16; TS-001 ne projektuje FT-003 |
| Technical Overview (`cultural-calendar.md`) | Opisuje trenutno stanje; nije izvor istine za ciljni model |

## 1.4 Veze sa BM, FS i FT-001

```
FT-001 Kalendar kulture
  → BM-01 Organizator
  → BM-02 Moderator organizatora
  → BM-03 Urednik (relevantni dijelovi)
  → FS Platformsko pravilo
  → FS §5.6 Upravljanje organizatorima (BR-045–BR-055, BR-135–BR-137)
  → FS §5.8 Upravljanje moderatorima (BR-070–BR-073)
  → FS §5.16 Evidencija aktivnosti (katalog Organizatori / Moderator; bez projektovanja FT-003)
  → TS-001 (ovaj dokument)
```

Trenutna implementacija još ne sadrži ovu cjelinu; odstupanja se vode u Technical Overview, ne u TS-001.

---

# 2. Arhitektonski principi

Izvori

Business Model:
- BM-ORG-01, BM-ORG-04, BM-ORG-06
- BM-ORG-12
- BM-MOD-02, BM-MOD-04, BM-MOD-11
- BM-UR-09, BM-UR-10
- BM-AL-07

Functional Specification:
- Platformsko pravilo
- BR-047, BR-049, BR-051, BR-055
- BR-073
- BR-178–BR-181

## 2.1 Razdvajanje platformskih uloga od poslovnih entiteta

Platformske uloge (npr. Urednik Kalendara kulture, Administrator platforme, običan registrovani korisnik) ostaju u nadležnosti platforme Digital Kotor.

Organizator je **poslovni entitet modula**, ne platformska uloga i ne korisnički nalog.

Moderator je **poslovno ovlašćenje** registrovanog korisnika za konkretnog Organizatora, koje se dodjeljuje i ukida unutar Kalendara kulture.

Tehnički model ne smije tretirati Organizatora kao user role niti izjednačavati Moderatora sa Urednikom.

## 2.2 Modularnost

Cjeline Organizator, Moderator i Zahtjev predstavljaju zasebne logičke tehničke odgovornosti unutar FT-001.

Promjene u ovoj cjelini ne smiju zahtijevati izmjenu poslovnog značenja platformskih uloga drugih modula.

## 2.3 Proširivost

Model mora podržati:

* više Moderatora po Organizatoru;
* više Organizatora za koje isti korisnik može biti Moderator;
* buduće dopune atributa Organizatora bez promjene osnovnog odnosa nosilac sadržaja ↔ ovlašćeni Moderator;
* buduće vrste zahtjeva vezanih za ovlašćenja, bez miješanja sa platformskim ulogama.

Konkretan katalog tipova Organizatora nije usvojen u BM/FS — vidi Otvorena pitanja.

## 2.4 Auditabilnost

Svaki zahtjev i svaka urednička odluka o Organizatoru / Moderatoru mora ostaviti trajni, neizmjenjivi lokalni trag u skladu sa BR-055 i BR-073.

Poslovno značajne radnje iz V1 kataloga FS §5.16 moraju biti dostupne za upis u centralnu Evidenciju aktivnosti, bez projektovanja FT-003 u ovom dokumentu.

## 2.5 Sljedivost

Za svaku poslovno značajnu radnju sistem mora moći odgovoriti:

* ko je pokrenuo radnju;
* u ime kojeg Organizatora (aktivni kontekst, kada je primjenjivo);
* kada je radnja izvršena;
* koja je odluka donijeta (gdje postoji odobravanje).

## 2.6 Minimalan uticaj na postojeći sistem

Uvođenje cjeline mora:

* zadržati postojeći model platformskih korisnika i uloga;
* ne mijenjati značenje postojeće uloge Urednika (`kk_admin` u trenutnoj implementaciji = poslovni Urednik);
* omogućiti postepeno uvođenje bez redefinisanja drugih modula platforme;
* sačuvati postojeće događaje i omogućiti kasnije usklađivanje sa pripadnošću Organizatoru u skladu sa BR-045 / BR-052.

---

# 3. Tehnički model

Izvori

Business Model:
- BM-ORG-01–BM-ORG-12
- BM-MOD-01–BM-MOD-15
- BM-UR-01, BM-UR-05, BM-UR-08, BM-UR-09, BM-UR-10
- BM-GL-06, BM-GL-07, BM-GL-08

Functional Specification:
- BR-045–BR-055
- BR-070–BR-073
- BR-135–BR-137

Tehnički model je logički. Ne definiše tabele, ORM klase ni fizičko skladištenje.

## 3.1 Organizator

**Odgovornost**

Poslovni entitet i nosilac sadržaja u Kalendaru kulture. Predstavlja subjekt u čije ime se vode događaji i povezani sadržaj.

**Životni ciklus (ciljni model usklađen sa FS)**

```
[Zahtjev u obradi] → Aktivan → Deaktiviran
```

* **Aktivan** — Organizator je odobren; Moderatori mogu raditi u njegovo ime u skladu sa pravilima.
* **Deaktiviran** — Moderatori ne mogu kreirati nove događaje niti slati nove prijedloge/izmjene u ime tog Organizatora; postojeći objavljeni događaji ostaju dostupni po pravilima otkazivanja i arhiviranja (BM-ORG-12, BM-UR-10, BR-049, BR-050).

Brisanje Organizatora nije dozvoljeno ako postoje povezani događaji (BM-ORG-12, BM-UR-10, BR-049).  
Istorijski podaci i veze sa događajima moraju ostati sačuvani pri deaktivaciji (BM-ORG-12, BM-UR-10, BR-049, BR-050).

**Odnosi**

* 1 Organizator : N Moderator ovlašćenja (najmanje jedno aktivno dok je Organizator aktivan — BR-047, BM-MOD-07).
* 1 Organizator : N Događaja (BR-046), uz izuzetak događaja bez registrovanog Organizatora (BR-045).
* Nastaje kao ishod odobrenog zahtjeva za kreiranje Organizatora.

**Ograničenja**

* nije korisnik ni uloga;
* ne pristupa Uredničkom portalu;
* ne dodjeljuje ovlašćenja Moderatorima;
* ne objavljuje sadržaj.

**Otvoreno u odnosu na nastanak entiteta**

BM/FS kažu da se nakon odobrenja „kreira, odnosno odobrava“ entitet. Da li tehnički zapis Organizatora nastaje tek pri odobrenju ili ranije u stanju koje nije aktivno — **Otvoreno pitanje** (vidi §12).

## 3.2 Moderator (ovlašćenje)

**Odgovornost**

Poslovno ovlašćenje registrovanog korisnika da u aktivnom kontekstu konkretnog Organizatora obavlja operativne radnje (kreiranje/uređivanje sadržaja, predlaganje Moderatora, pokretanje uklanjanja, upravljanje podacima Organizatora u skladu sa BM-GL-07), osim samostalne objave.

**Životni ciklus**

```
Predložen (zahtjev) → Na odobrenju → Aktivan → Uklonjen
```

* Ovlašćenja nastaju tek nakon odobrenja Urednika.
* Uklanjanje važi tek nakon odobrenja Urednika (BR-071, BM-MOD-09).
* Nije dozvoljeno ukloniti posljednjeg aktivnog Moderatora (BR-072, BM-MOD-10).

**Odnosi**

* N : 1 prema Korisniku (isti korisnik može biti Moderator više Organizatora — BM-MOD-02, BR-051).
* N : 1 prema Organizatoru.
* Početni Moderator nastaje iz odobrenog zahtjeva za kreiranje Organizatora.
* Naredni Moderatori nastaju iz zahtjeva koje podnosi postojeći aktivni Moderator istog Organizatora.

**Ograničenja**

* nije Urednik;
* nije Organizator;
* ne dodjeljuje ovlašćenja — samo predlaže;
* ne može samostalno objaviti sadržaj;
* pri radnji postupa isključivo u aktivnom kontekstu jednog Organizatora.

## 3.3 Zahtjev za kreiranje Organizatora

**Odgovornost**

Poslovni postupak kojim registrovani korisnik predlaže novi entitet Organizatora i predloženog početnog Moderatora. Podnošenje zahtjeva samo po sebi ne stvara aktivnog Organizatora niti Moderatora.

**Životni ciklus**

```
Podnesen → Odobren
         ↘ Odbijen
```

* **Podnesen** — zahtjev je iniciran i čeka odluku Urednika.
* **Odobren** — nastaje/aktivira se Organizator; predloženi korisnik dobija početno moderatorsko ovlašćenje.
* **Odbijen** — Organizator se ne odobrava kao aktivan; predloženi korisnik ne dobija ovlašćenja; podnosilac ne dobija novu ulogu. Odbijanje ne sprečava novi zahtjev (BR-137, BM-ORG-11).

**Sadržaj zahtjeva (BR-135 / BM-ORG-07)**

* podaci o predloženom Organizatoru kao poslovnom entitetu;
* podaci potrebni za identifikovanje predloženog početnog Moderatora;
* podatak da li je predloženi Moderator sam podnosilac ili drugi registrovani korisnik.

**Odnosi**

* 1 zahtjev : 1 podnosilac (registrovani korisnik);
* 1 zahtjev : 1 predloženi početni Moderator (korisnik);
* 1 zahtjev : 0..1 aktivni Organizator (nastaje pri odobrenju);
* 1 korisnik : N zahtjeva (BR-136).

**Ograničenja**

* podnošenje ne mijenja platformske uloge;
* Urednik odobrava ili odbija (BM-UR-01);
* trajni audit obavezan (BR-055, BM-ORG-09).

## 3.4 Zahtjev za dodjelu / uklanjanje Moderatora

Iako naziv dokumenta ističe zahtjev za kreiranje Organizatora, BM/FS zahtijevaju i zahtjeve za Moderatore. Tehnički model ih tretira kao zasebne postupke vezane za isto ovlašćenje.

**Zahtjev za dodjelu ovlašćenja Moderatora**

* pokreće postojeći aktivni Moderator Organizatora (BR-053);
* odobrava / odbija isključivo Urednik (BR-054, BM-UR-08);
* tek nakon odobrenja novo ovlašćenje postaje aktivno.

**Zahtjev za uklanjanje Moderatora**

* pokreće Moderator za drugog Moderatora istog Organizatora (BR-070);
* odobrava / odbija Urednik (BR-071);
* uklanjanje važi tek nakon odobrenja;
* zabranjeno ako bi ostao bez aktivnog Moderatora (BR-072);
* trajna evidencija (BR-073).

## 3.5 Aktivni kontekst Organizatora

Kada korisnik ima moderatorska ovlašćenja za više Organizatora, svaka radnja izvršava se u kontekstu tačno jednog Organizatora (BM-MOD-04, BR-051).

Aktivni kontekst:

* nije izbor platformske uloge;
* nije isto što i uloga Urednika;
* mora biti dovoljno određen da sistem primijeni pripadnost sadržaja, ovlašćenja i audit.

Tehnički mehanizam izbora / čuvanja konteksta nije propisan u FS — **Otvoreno pitanje** (§12).

## 3.6 Urednik (u odnosu na ovu cjelinu)

Urednik je isključiva administrativna uloga Uredničkog portala (BM-UR-09).

U okviru TS-001 Urednik:

* odobrava / odbija zahtjeve za kreiranje Organizatora;
* odobrava / odbija dodjelu i uklanjanje Moderatora;
* dodjeljuje pristup novom Moderatoru;
* ne postaje Moderator niti Organizator kroz ove tokove;
* ne mijenja aktivnu poslovnu ulogu.

---

# 4. Tokovi

Izvori

Business Model:
- BM-ORG-02, BM-ORG-03, BM-ORG-08, BM-ORG-11
- BM-ORG-12
- BM-MOD-08, BM-MOD-09, BM-MOD-10, BM-MOD-13, BM-MOD-14
- BM-UR-01, BM-UR-05, BM-UR-08, BM-UR-10

Functional Specification:
- BR-047, BR-049, BR-050
- BR-053, BR-054, BR-055
- BR-070–BR-073
- BR-135–BR-137

## 4.1 Podnošenje zahtjeva za kreiranje Organizatora

```mermaid
sequenceDiagram
  participant K as Registrovani korisnik
  participant S as Sistem (Kalendar kulture)
  participant U as Urednik

  K->>S: Podnosi zahtjev (podaci Organizatora + predloženi Moderator)
  S->>S: Validacija preduslova i sadržaja zahtjeva
  S->>S: Evidentira podnosioca, predloženog Moderatora, vrijeme
  S-->>U: Zahtjev dostupan za pregled
  Note over K,S: Korisnik nije Organizator ni Moderator
```

**Tehnički tok**

1. Sistem potvrđuje da je podnosilac registrovan i aktivan na platformi.
2. Sistem prima sadržaj zahtjeva u skladu sa BR-135.
3. Sistem ne dodjeljuje platformske uloge niti moderatorska ovlašćenja.
4. Sistem trajno bilježi podnosioca, predloženog Moderatora, datum/vrijeme podnošenja.
5. Zahtjev ulazi u stanje čekanja uredničke odluke.

## 4.2 Odobravanje zahtjeva, kreiranje Organizatora i dodjela početnog Moderatora

```mermaid
sequenceDiagram
  participant U as Urednik
  participant S as Sistem
  participant O as Organizator
  participant M as Predloženi korisnik

  U->>S: Odobrava zahtjev
  S->>O: Kreira / odobrava aktivni entitet Organizatora
  S->>M: Dodjeljuje početno moderatorsko ovlašćenje
  S->>S: Uspostavlja vezu Moderator ↔ Organizator
  S->>S: Trajni audit odluke + lokalni tragovi
  Note over S: Pri odobrenju: dva zapisa za centralnu evidenciju\n(odobrenje/kreiranje + dodjela početnog Moderatora)
```

**Tehnički tok**

1. Urednik pregleda zahtjev.
2. Pri odobrenju sistem:
   * uspostavlja Organizatora kao aktivan poslovni entitet;
   * dodjeljuje predloženom korisniku početno moderatorsko ovlašćenje;
   * uspostavlja poslovnu vezu Moderator ↔ Organizator;
   * bilježi Urednika i vrijeme odluke.
3. Podnosilac ne dobija posebnu ulogu „Organizator“.
4. Moderatorska ovlašćenja nastaju tek u ovom koraku.

## 4.3 Odbijanje zahtjeva

```mermaid
flowchart TD
  A[Zahtjev podnesen] --> B{Odluka Urednika}
  B -->|Odobri| C[Aktivan Organizator + početni Moderator]
  B -->|Odbij| D[Organizator nije aktivan]
  D --> E[Predloženi korisnik bez ovlašćenja]
  D --> F[Podnosilac bez nove uloge]
  D --> G[Dozvoljen novi zahtjev]
```

**Tehnički tok**

1. Urednik odbija zahtjev.
2. Sistem ne aktivira Organizatora.
3. Sistem ne dodjeljuje moderatorska ovlašćenja.
4. Sistem bilježi odluku i vrijeme.
5. Podnosilac može kasnije podnijeti novi zahtjev (BR-137).

## 4.4 Dodjela dodatnog Moderatora

```mermaid
sequenceDiagram
  participant M1 as Aktivni Moderator
  participant S as Sistem
  participant U as Urednik
  participant M2 as Predloženi korisnik

  M1->>S: Predlaže novog Moderatora (u kontekstu Organizatora)
  Note over M1,S: M1 ne dodjeljuje ovlašćenja
  S-->>U: Zahtjev za dodjelu
  U->>S: Odobrava ili odbija
  alt Odobreno
    S->>M2: Dodjeljuje aktivno ovlašćenje za Organizatora
  else Odbijeno
    S->>S: Bez novog ovlašćenja
  end
  S->>S: Trajni audit
```

**Tehnički tok**

1. Samo aktivni Moderator datog Organizatora može predložiti narednog.
2. Predlaganje se vrši u aktivnom kontekstu tog Organizatora.
3. Urednik odlučuje i, ako odobri, isključivo on dodjeljuje pristup.
4. Novo ovlašćenje postaje aktivno tek nakon odobrenja.

## 4.5 Uklanjanje Moderatora

```mermaid
flowchart TD
  A[Moderator pokreće uklanjanje drugog Moderatora] --> B{Da li bi ostao bez aktivnog Moderatora?}
  B -->|Da| C[Zahtjev / uklanjanje nije dozvoljeno]
  B -->|Ne| D[Zahtjev šalje se Uredniku]
  D --> E{Odluka Urednika}
  E -->|Odobri| F[Moderator = Uklonjen]
  E -->|Odbij| G[Moderator ostaje Aktivan]
```

**Tehnički tok**

1. Moderator pokreće zahtjev za uklanjanje drugog Moderatora istog Organizatora.
2. Sistem provjerava zabranu uklanjanja posljednjeg aktivnog Moderatora.
3. Urednik odobrava ili odbija.
4. Status uklonjen nastaje tek nakon odobrenja.
5. Sistem vodi evidenciju podnošenja, obrade i odluke (BR-073).

## 4.6 Deaktivacija Organizatora

```mermaid
flowchart TD
  A[Organizator Aktivan] --> B[Deaktivacija]
  B --> C[Status: Deaktiviran]
  C --> D[Zabrana novih događaja u ime Organizatora]
  C --> E[Zabrana novih prijedloga / izmjena]
  C --> F[Istorija i veze sa događajima sačuvani]
  C --> G[Objavljeni događaji ostaju po pravilima portala]
```

**Tehnički tok**

1. Organizator prelazi u status Deaktiviran.
2. Moderatori gube mogućnost kreiranja novih događaja i slanja novih prijedloga/izmjena u ime tog Organizatora (BM-ORG-12, BM-UR-10, BR-049, BR-050).
3. Brisanje nije alternativa deaktivaciji kada postoje povezani događaji (BM-ORG-12, BM-UR-10, BR-049).
4. Deaktivacija je poslovno značajna aktivnost za lokalni trag i za katalog Evidencije aktivnosti.

Deaktivaciju Organizatora pokreće Urednik bez prethodnog zahtjeva Organizatora ili Moderatora (BM-ORG-12, BM-UR-10, BR-049, BR-050).

---

# 5. Autorizacija i ovlašćenja

Izvori

Business Model:
- BM-ORG-02, BM-ORG-04, BM-ORG-05, BM-ORG-12
- BM-MOD-08, BM-MOD-11, BM-MOD-13
- BM-UR-01, BM-UR-02, BM-UR-05, BM-UR-08, BM-UR-09, BM-UR-10
- BM-EP-03

Functional Specification:
- Platformsko pravilo
- BR-048, BR-049, BR-051, BR-053, BR-054
- BR-070, BR-071
- BR-135, BR-137

Logički model ovlašćenja (bez middleware / framework detalja).

| Radnja | Ko smije | Napomena / izvor |
|--------|----------|------------------|
| Podnijeti zahtjev za kreiranje Organizatora | Registrovani aktivni korisnik platforme | BM-ORG-02, BR-135, Platformsko pravilo |
| Odobriti zahtjev za kreiranje Organizatora | Urednik | BM-UR-01 |
| Odbiti zahtjev za kreiranje Organizatora | Urednik | BM-UR-01, BR-137 |
| Deaktivirati Organizatora | Urednik | BM-ORG-12, BM-UR-10, BR-049, BR-050 |
| Predložiti dodatnog Moderatora | Aktivni Moderator istog Organizatora | BR-053, BM-MOD-13 |
| Odobriti / odbiti dodjelu Moderatora | Urednik | BR-054, BM-UR-08 |
| Pokrenuti uklanjanje Moderatora | Aktivni Moderator (za drugog Moderatora istog Organizatora) | BR-070, BM-MOD-08 |
| Odobriti / odbiti uklanjanje Moderatora | Urednik | BR-071, BM-UR-05 |
| Upravljati podacima Organizatora | Moderator u aktivnom kontekstu; Urednik kroz Urednički portal | BM-GL-07, BM-EP-03; **obim izmjena — Otvoreno pitanje** |
| Kreirati / uređivati događaj u ime Organizatora | Aktivni Moderator tog Organizatora; Urednik po pravilima događaja | BM-ORG-04, BM-MOD-05 |
| Poslati događaj na odobrenje | Aktivni Moderator Organizatora | FS §5.5.5 |
| Objaviti događaj | Isključivo Urednik | BM-ORG-05, BM-UR-02 |

Dodatna pravila autorizacije:

* Urednik ne kombinuje ulogu sa Moderatorom / običnim korisnikom u poslovnom modelu Kalendara kulture.
* Moderator bez aktivnog konteksta Organizatora ne smije izvršavati radnje koje zahtijevaju pripadnost Organizatoru.
* Deaktiviran Organizator onemogućava moderatorske radnje kreiranja i slanja novih prijedloga/izmjena (BM-ORG-12, BM-UR-10, BR-049, BR-050).
* Organizator kao entitet nema ovlašćenja za prijavu ni radnje.

---

# 6. Model podataka

Izvori

Business Model:
- BM-ORG-01, BM-ORG-07, BM-ORG-09
- BM-MOD-02, BM-MOD-07, BM-MOD-10, BM-MOD-15
- BM-GL-06, BM-GL-07

Functional Specification:
- BR-046, BR-047, BR-049, BR-050
- BR-051, BR-053, BR-054, BR-055
- BR-070–BR-073
- BR-135–BR-137

Konceptualni model. Bez SQL, bez migracija, bez fizičkih tipova.

## 6.1 Dijagram odnosa

```mermaid
erDiagram
  KORISNIK ||--o{ ZAHTJEV_KREIRANJE_ORG : podnosi
  KORISNIK ||--o{ ZAHTJEV_KREIRANJE_ORG : "predlozen kao Moderator"
  ZAHTJEV_KREIRANJE_ORG ||--o| ORGANIZATOR : "odobrenjem nastaje"
  ORGANIZATOR ||--o{ MODERATOR_OVLAŠCENJE : ima
  KORISNIK ||--o{ MODERATOR_OVLAŠCENJE : posjeduje
  ORGANIZATOR ||--o{ DOGADJAJ : "nosilac (uz izuzetak)"
  ORGANIZATOR ||--o{ ZAHTJEV_MODERATOR : odnosi_se_na
  KORISNIK ||--o{ ZAHTJEV_MODERATOR : podnosi
  KORISNIK ||--o{ ZAHTJEV_MODERATOR : "ciljni Moderator"
```

## 6.2 Entitet: Organizator

**Svrha:** nosilac sadržaja.

**Ključni atributi (konceptualno, izvedeni iz BM/FS)**

| Atribut / svojstvo | Obrazloženje |
|--------------------|--------------|
| Identitet entiteta | Jedinstvena identifikacija Organizatora u modulu |
| Poslovni podaci entiteta | „Podaci o predloženom Organizatoru“ — tačan katalog polja nije usvojen (**Otvoreno pitanje**) |
| Status | Aktivan / Deaktiviran (BM-ORG-12, BM-UR-10, BR-049, BR-050) |
| Vrijeme nastanka aktivnog entiteta | Potrebno za sljedivost |
| Veza na odobravajući zahtjev | Sljedivost prema zahtjevu |

**Veze / kardinalnosti**

* 0..1 zahtjev za kreiranje ↔ 1 Organizator (nakon odobrenja);
* 1 Organizator : 1..N aktivnih Moderatora dok je aktivan (poslovno pravilo minimuma);
* 1 Organizator : 0..N događaja.

**Poslovna ograničenja**

* nije korisnički nalog;
* ne briše se ako ima povezane događaje;
* deaktivacija čuva istoriju (BM-ORG-12, BM-UR-10, BR-049, BR-050).

## 6.3 Entitet: Moderator ovlašćenje

**Svrha:** veza korisnik ↔ Organizator sa statusom ovlašćenja.

**Ključni atributi**

| Atribut / svojstvo | Obrazloženje |
|--------------------|--------------|
| Referenca na korisnika | Registrovani korisnik platforme |
| Referenca na Organizatora | Konkretni Organizator |
| Status | Aktivan / Uklonjen (i prelazna stanja kroz zahtjev) |
| Tip nastanka | Početni (iz zahtjeva za kreiranje) / Naredni (iz zahtjeva za dodjelu) |
| Vrijeme aktivacije | Nakon odobrenja Urednika |
| Vrijeme uklanjanja | Nakon odobrenja uklanjanja |

**Veze / kardinalnosti**

* N ovlašćenja : 1 korisnik;
* N ovlašćenja : 1 Organizator;
* u jednom trenutku korisnik ima najviše jedno aktivno ovlašćenje po Organizatoru (**tehničko ograničenje radi integriteta; nije eksplicitno BM pravilo — potvrditi u §12 ako treba poslovna potvrda**).

**Poslovna ograničenja**

* aktivacija samo nakon odobrenja;
* uklanjanje samo nakon odobrenja;
* zabranjeno uklanjanje posljednjeg aktivnog.

## 6.4 Entitet: Zahtjev za kreiranje Organizatora

**Svrha:** postupak predlaganja novog Organizatora i početnog Moderatora.

**Ključni atributi**

| Atribut / svojstvo | Obrazloženje |
|--------------------|--------------|
| Podnosilac | Registrovani korisnik |
| Predloženi Moderator | Registrovani korisnik |
| Indikator „predloženi = podnosilac“ | BR-135 |
| Predloženi podaci Organizatora | Sadržaj zahtjeva |
| Status | Podnesen / Odobren / Odbijen |
| Urednik odluke | Popunjava se pri odluci |
| Datum/vrijeme podnošenja | BM-ORG-09, BR-055 |
| Datum/vrijeme odluke | BM-ORG-09, BR-055 |

**Veze / kardinalnosti**

* N zahtjeva : 1 podnosilac;
* 1 zahtjev : 1 predloženi Moderator;
* 1 odobren zahtjev : 1 Organizator.

## 6.5 Entitet: Zahtjev za Moderatora (dodjela / uklanjanje)

**Svrha:** postupci predlaganja dodjele ili uklanjanja ovlašćenja.

**Ključni atributi**

| Atribut / svojstvo | Obrazloženje |
|--------------------|--------------|
| Vrsta | Dodjela / Uklanjanje |
| Organizator | Kontekst zahtjeva |
| Podnosilac | Aktivni Moderator |
| Ciljni korisnik / Moderator | Predloženi ili Moderator koji se uklanja |
| Status | Podnesen / Odobren / Odbijen |
| Urednik odluke | Pri odluci |
| Datum/vrijeme podnošenja i odluke | BM-MOD-15, BR-055, BR-073 |

**Veze / kardinalnosti**

* N zahtjeva : 1 Organizator;
* 1 zahtjev : 1 podnosilac;
* 1 zahtjev : 1 ciljni korisnik.

## 6.6 Koncept: Aktivni kontekst

Nije samostalni poslovni entitet u BM/FS, ali je obavezan tehnički koncept.

**Svrha:** određivanje Organizatora u čije ime Moderator izvršava radnju.

**Obavezna svojstva pri radnji**

* identifikator Organizatora u kontekstu;
* korisnik izvršilac;
* potvrda da postoji aktivno moderatorsko ovlašćenje za taj par.

Mehanizam perzistencije konteksta — **Otvoreno pitanje**.

---

# 7. Validacije

Izvori

Business Model:
- BM-ORG-02, BM-ORG-08, BM-ORG-10, BM-ORG-11
- BM-ORG-12
- BM-MOD-10, BM-MOD-13, BM-MOD-14
- BM-UR-01, BM-UR-10

Functional Specification:
- BR-049, BR-050
- BR-053, BR-054, BR-055
- BR-070–BR-073
- BR-135–BR-137

## 7.1 Obavezna polja / sadržaj

Za **zahtjev za kreiranje Organizatora** (BR-135):

* podaci o predloženom Organizatoru;
* identifikacija predloženog početnog Moderatora;
* podatak da li je predloženi Moderator podnosilac ili drugi registrovani korisnik.

Tačan spisak polja podataka Organizatora (npr. naziv, opis, kontakt, pravni oblik) **nije usvojen u BM/FS** — Otvoreno pitanje.

Za **zahtjev za dodjelu Moderatora**:

* Organizator (kontekst);
* podnosilac (aktivni Moderator);
* predloženi registrovani korisnik.

Za **zahtjev za uklanjanje Moderatora**:

* Organizator;
* podnosilac;
* ciljni aktivni Moderator;
* provjera da ciljni nije posljednji aktivni Moderator.

## 7.2 Poslovne validacije

* Podnosilac zahtjeva za kreiranje mora biti registrovan i aktivan (Platformsko pravilo, BM-ORG-02).
* Predloženi Moderator mora biti registrovan korisnik platforme (BM-ORG-07, BR-135).
* Podnošenje zahtjeva ne smije automatski aktivirati Organizatora ni Moderatora (BM-ORG-02, BM-ORG-08, BR-137).
* Odobrenje mora atomično (logički nedjeljivo) uspostaviti aktivnog Organizatora i početno ovlašćenje, ili u potpunosti odbiti ishod (BM-ORG-03, BM-ORG-08, BR-047, BR-137).
* Dodatnog Moderatora smije predložiti samo aktivni Moderator istog Organizatora (BM-MOD-13, BR-053).
* Deaktivaciju Organizatora pokreće Urednik i za nju nije potreban prethodni zahtjev Organizatora niti Moderatora (BM-ORG-12, BM-UR-10, BR-049, BR-050).
* Uklanjanje posljednjeg aktivnog Moderatora mora biti odbijeno (BM-MOD-10, BR-072).
* Radnje Moderatora nad sadržajem dozvoljene su samo za Organizatora iz aktivnog konteksta (BM-MOD-04, BR-051).
* Za deaktiviranog Organizatora zabranjeno je kreiranje novih događaja i slanje novih prijedloga/izmjena (BM-ORG-12, BM-UR-10, BR-049, BR-050).
* Brisanje Organizatora sa povezanim događajima nije dozvoljeno (BM-ORG-12, BM-UR-10, BR-049).

## 7.3 Tehničke validacije

* Referencirani korisnici moraju postojati u platformskom registru korisnika.
* Statusni prelazi zahtjeva i ovlašćenja smiju slijediti samo definisane tokove iz §4.
* Audit polja odluke ne smiju biti ručno izmjenjiva nakon upisa (BR-055).
* Aktivni kontekst, kada je potreban, mora biti postavljen prije autorizovane poslovne radnje.
* Isti korisnik ne smije dobiti duplo aktivno ovlašćenje za istog Organizatora (integritet veze).

## 7.4 Ograničenja

* Broj zahtjeva za kreiranje Organizatora po korisniku: neograničen (BR-136).
* Broj Moderatora po Organizatoru: jedan ili više; minimum jedan aktivan dok je Organizator aktivan.
* Broj Organizatora po Moderatoru: jedan ili više.
* Organizator nema pristup Uredničkom portalu.
* Moderator ne može samostalno objaviti sadržaj.

---

# 8. Evidencija aktivnosti (Audit)

Izvori

Business Model:
- BM-ORG-09
- BM-ORG-12
- BM-MOD-15
- BM-AL-01–BM-AL-08
- BM-EP-09
- BM-UR-10

Functional Specification:
- BR-049, BR-055
- BR-073
- BR-178–BR-181

Ovo poglavlje definiše **logičke događaje** koje ova cjelina mora evidentirati. Ne projektuje FT-003.

## 8.1 Lokalni audit tragovi (obavezni po BM/FS)

| Događaj | Ko pokreće | Kada nastaje | Šta se bilježi |
|---------|------------|--------------|----------------|
| Podnošenje zahtjeva za kreiranje Organizatora | Registrovani korisnik | Pri uspješnom podnošenju | Podnosilac; predloženi Moderator; datum/vrijeme podnošenja |
| Odluka o zahtjevu za kreiranje Organizatora | Urednik | Pri odobrenju ili odbijanju | Urednik; datum/vrijeme odluke; ishod |
| Podnošenje zahtjeva za dodjelu Moderatora | Aktivni Moderator | Pri podnošenju | Podnosilac; predloženi Moderator; Organizator; vrijeme |
| Odluka o dodjeli Moderatora | Urednik | Pri odobrenju/odbijanju | Urednik; vrijeme; ishod |
| Podnošenje zahtjeva za uklanjanje Moderatora | Aktivni Moderator | Pri podnošenju | Podnosilac; ciljni Moderator; Organizator; vrijeme |
| Odluka o uklanjanju Moderatora | Urednik | Pri odobrenju/odbijanju | Urednik; vrijeme; ishod |

Lokalni tragovi nisu ručno izmjenjivi (BR-055).

## 8.2 Poslovno značajni događaji relevantni za Evidenciju aktivnosti

U skladu sa FS §5.16 katalogom (bez projektovanja FT-003), ova cjelina mora biti u stanju emitovati najmanje:

| Događaj | Izvršilac | Napomena |
|---------|-----------|----------|
| Podnošenje zahtjeva za kreiranje Organizatora | Korisnik | |
| Odobravanje zahtjeva za kreiranje Organizatora | Urednik | Uz kreiranje/aktivaciju entiteta |
| Odbijanje zahtjeva za kreiranje Organizatora | Urednik | |
| Dodjela početnog ovlašćenja Moderatora | Urednik / sistemski ishod odobrenja | Drugi zapis pri odobrenju kreiranja (BR-179) |
| Podnošenje / odobravanje / odbijanje dodjele Moderatora | Moderator / Urednik | Zasebni zapisi |
| Podnošenje / odobravanje / odbijanje uklanjanja Moderatora | Moderator / Urednik | Zasebni zapisi |
| Deaktivacija Organizatora | Urednik | BM-ORG-12, BM-UR-10, BR-049, BR-050, BR-178 |
| Izmjene poslovno značajnih podataka Organizatora | Moderator / Urednik | Kriterijum „poslovno značajno“ iz FS |
| Naknadno povezivanje događaja sa Organizatorom | Urednik | BR-052; ne smije mijenjati postojeći audit događaja |

Ne ulazi u centralnu evidenciju kao zaseban događaj:

* sama promjena aktivnog konteksta Organizatora (bilježi se kao atribut drugih radnji kada je primjenjivo).

---

# 9. Integracije

Izvori

Business Model:
- BM-ORG-04
- BM-UR-06, BM-UR-07, BM-UR-10
- BM-EP-02, BM-EP-03, BM-EP-06
- BM-GL-09

Functional Specification:
- Platformsko pravilo
- BR-045, BR-048, BR-049, BR-050, BR-051, BR-052
- BR-178

## 9.1 Korisnici Digital Kotora

* Svi učesnici tokova moraju imati registrovan i aktivan nalog.
* Identitet podnosioca, Moderatora i Urednika dolazi iz platformskog korisničkog registra.
* Dodjela platformske uloge Urednik ostaje van Kalendara kulture.
* Kalendar ne smije tretirati Organizatora kao platformsog korisnika.

## 9.2 Urednički portal

* Operativni prostor Moderatora i Urednika (BM-EP-02).
* Omogućava upravljanje podacima Organizatora, pregled statusa i uredničke odluke (BM-EP-03).
* Organizator ne pristupa portalu.
* Pristup podacima ograničen ovlašćenjima i aktivnim kontekstom (BM-EP-06, BR-051).

## 9.3 Događaji

* Događaj pripada tačno jednom Organizatoru, uz izuzetak događaja bez registrovanog Organizatora (BR-045).
* Moderator kreira/uređuje događaje samo za Organizatora iz aktivnog konteksta.
* Deaktivacija Organizatora ograničava nove događaje i nove prijedloge/izmjene (BM-ORG-12, BM-UR-10, BR-049, BR-050).
* Naknadno povezivanje događaja sa Organizatorom ne smije mijenjati audit, istoriju ni javne verzije (BR-052).

## 9.4 Evidencija aktivnosti

* TS-001 obezbjeđuje izvore događaja iz §8.2.
* FT-003 definiše centralnu evidenciju, pristup Administratora platforme i detalje skladištenja — van obuhvata ovog dokumenta.
* Lokalni audit tragovi ostaju na entitetima/zahtjevima i ne zamjenjuju centralnu evidenciju.

## 9.5 Ostali moduli platforme

* Moderatorska i urednička ovlašćenja ograničena su na Kalendar kulture i ne daju prava u drugim modulima (Platformsko pravilo).
* Administrator platforme nije učesnik uredničkog procesa ove cjeline.
* Drugi moduli ne smiju direktno mijenjati status Organizatora / Moderatora mimo definisanih tokova.

---

# 10. Nefunkcionalni zahtjevi

Izvori

Business Model:
- BM-GL-18, BM-GL-20
- BM-AL-04, BM-AL-05

Functional Specification:
- BR-049, BR-050, BR-051
- BR-055, BR-072, BR-073
- BR-178–BR-181

## 10.1 Sigurnost

* Autorizacija mora biti zasnovana na poslovnom modelu (entitet + ovlašćenje + kontekst), ne na tretmanu Organizatora kao uloge.
* Urednik i Moderator moraju biti strogo razdvojeni.
* Audit zapisi odluka ne smiju biti izmjenjivi kroz redovno korišćenje.
* Pristup tuđim Organizatorima preko pogrešnog konteksta mora biti spriječen.

## 10.2 Performanse

* Provjera aktivnog ovlašćenja i aktivnog konteksta mora biti dovoljno brza za redovne radnje u Uredničkom portalu.
* Broj zahtjeva i Moderatora po Organizatoru nije poslovno ograničen; tehničko rješenje mora podnijeti rast bez promjene poslovnog modela.
* Konkretni pragovi performansi nisu usvojeni u BM/FS — **Otvoreno pitanje** ako su potrebni za prihvatanje.

## 10.3 Integritet podataka

* Odobrenje zahtjeva za kreiranje mora rezultirati konzistentnim stanjem: aktivan Organizator + aktivno početno ovlašćenje, ili nikakav djelimični ishod.
* Minimum jednog aktivnog Moderatora mora biti očuvan dok je Organizator aktivan.
* Veze događaj ↔ Organizator moraju ostati sačuvane pri deaktivaciji (BM-ORG-12, BM-UR-10, BR-049, BR-050).
* Naknadno povezivanje događaja ne smije falsifikovati istoriju.

## 10.4 Konkurentnost izmjena

* Dva Urednika ne smiju proizvesti kontradiktorne konačne odluke nad istim zahtjevom bez kontrolisanog ishoda (jedna važeća odluka).
* Paralelni zahtjevi za uklanjanje ne smiju zaobići zabranu uklanjanja posljednjeg aktivnog Moderatora.
* Promjena aktivnog konteksta ne smije uzrokovati upis radnje pod pogrešnim Organizatorom.

Detaljan mehanizam zaključavanja nije propisan u BM/FS — tehnički izbor ostaje za kasniju implementacionu razradu, uz poštovanje gornjih ishoda.

## 10.5 Proširivost

* Model ovlašćenja Moderatora mora dozvoliti buduće dodatne vrste zahtjeva bez pretvaranja Organizatora u korisničku ulogu.
* Atributi Organizatora moraju moći biti prošireni nakon usvajanja kataloga polja.
* Više portala / budući kanali pristupa ne smiju narušiti pravilo da Organizator nema sopstvenu prijavu.

## 10.6 Održavanje

* TS-001 ostaje usklađen sa BM/FS; odstupanja implementacije vode se u Technical Overview.
* Izmjene poslovnih pravila ulaze isključivo preko BM/FS, zatim usklađivanja TS.
* FT-003 i detalji događajskog workflow-a razvijaju se kao zasebne specifikacije / poglavlja, uz stabilne integracione tačke iz §9.

---

# 11. Granice V1 (Out of Scope)

Izvori

Business Model:
- BM-ORG-01–BM-ORG-12
- BM-MOD-01–BM-MOD-15

Functional Specification:
- §5.6, §5.8
- §5.16 (posebno BR-176 i BR-188)

Ovo poglavlje navodi usvojene granice obuhvata TS-001 za V1.

1. Ovaj dokument ne projektuje implementaciju (kod, Laravel komponente, SQL, migracije, API ugovore i rute).
2. Detaljni dizajn centralne Evidencije aktivnosti (FT-003) nije dio obuhvata TS-001.
3. Tehnički model workflow-a događaja u punoj širini nije dio obuhvata TS-001; u ovom dokumentu navode se samo veze koje su nužne za cjelinu Organizator / Moderator.
4. Funkcionalnosti Newsletter-a, lokacija, kategorija, medija i javnog portala nisu dio obuhvata TS-001, osim tačaka koje direktno utiču na poslovna pravila Organizatora i Moderatora.
5. Van opsega ovog TS-a ostaju i aktivnosti koje FS §5.16 eksplicitno isključuje iz V1 kataloga centralne Evidencije aktivnosti (npr. autentikacija/platformske aktivnosti), jer nisu predmet ove funkcionalne cjeline.
6. Nema dodatnih usvojenih isključenja van V1 osim onih navedenih u BM i FS izvorima.

---

# 12. Otvorena pitanja

Pitanja za odluku Product Ownera. Bez predloženih odgovora.

1. Koji tačan katalog poslovnih podataka čini „podatke o predloženom Organizatoru“ u zahtjevu i na entitetu Organizatora (obavezna i opciona polja)?
2. Na koji način se identifikuje predloženi Moderator (npr. izbor postojećeg korisnika, identifikator naloga, drugi način)?
3. Da li zapis entiteta Organizatora nastaje tek u trenutku odobrenja, ili ranije u neaktivnom / predloženom stanju uz zahtjev?
4. Da li V1 uključuje ponovnu aktivaciju deaktiviranog Organizatora?
5. Da li V1 uključuje arhivski status Organizatora odvojen od deaktivacije?
6. Ko smije mijenjati koje podatke Organizatora nakon odobrenja (obim Moderatora vs Urednika; šta je „poslovno značajna izmjena“)?
7. Da li Urednik može kreirati Organizatora bez zahtjeva registrovanog korisnika?
8. Da li podnosilac može povući zahtjev za kreiranje Organizatora prije odluke Urednika?
9. Da li Moderator može pokrenuti uklanjanje samog sebe?
10. Šta se dešava sa otvorenim zahtjevima za Moderatore i sa aktivnim ovlašćenjima kada se Organizator deaktivira?
11. Kako korisnik bira i mijenja aktivni kontekst Organizatora (poslovno/UX pravilo), s obzirom da FS ne propisuje tehnički mehanizam?
12. Da li postoje tipovi / vrste Organizatora u V1, ili je jedan jedinstveni tip entiteta?
13. Da li je dozvoljeno da isti korisnik bude predloženi početni Moderator na više istovremenih neodobrenih zahtjeva?
14. Da li odbijeni zahtjev ostaje trajno vidljiv podnosiocu / Uredniku i u kom obimu, ili je dovoljna audit evidencija?
15. Da li Moderatoru treba posebna platformska uloga za pristup Uredničkom portalu, ili je dovoljno samo poslovno ovlašćenje unutar Kalendara kulture?
16. Da li treba potvrditi kao poslovno pravilo zabranu više istovremenih aktivnih ovlašćenja istog korisnika za istog Organizatora?
17. Koji nefunkcionalni pragovi (odgovori sistema, kapacitet, retention lokalnih tragova) važe za prihvatanje V1 ove cjeline?

---

# 13. Matrica sljedivosti

| Oblast | BM | FS / BR | TS |
|--------|----|---------|-----|
| Organizator kao poslovni entitet | BM-ORG-01–BM-ORG-12, BM-GL-06 | Platformsko pravilo; §5.6; BR-045–BR-052; BR-135–BR-137 | §1, §3, §6 |
| Moderator ovlašćenje | BM-MOD-01–BM-MOD-15, BM-GL-07 | Platformsko pravilo; BR-047; BR-051; BR-053–BR-055; §5.8 BR-070–BR-073 | §3, §5, §6 |
| Zahtjev za kreiranje Organizatora | BM-ORG-02, BM-ORG-07–BM-ORG-11 | BR-135–BR-137; §5.6 tok | §3, §4, §6, §7 |
| Uredničke odluke | BM-UR-01, BM-UR-05, BM-UR-08, BM-UR-09, BM-UR-10 | BR-049, BR-054, BR-071, BR-137 | §4, §5 |
| Deaktivacija | BM-ORG-12, BM-UR-10 | BR-049, BR-050 | §3, §4, §5, §7, §8 |
| Aktivni kontekst | BM-MOD-04 | BR-051 | §3, §5, §6 |
| Audit zahtjeva | BM-ORG-09, BM-MOD-15, BM-AL-07 | BR-055, BR-073; §5.16 katalog | §8, §9 |
| Urednički portal | BM-EP-02, BM-EP-03, BM-EP-06 | BR-048; FS §5.14 (povezano) | §9 |

---

# 14. Napomene za implementaciju

Funkcionalnost zahtjeva za kreiranje Organizatora i upravljanja Moderatorima usvojena je u BM/FS, ali trenutno nije implementirana.  
TS-001 opisuje ciljni tehnički model.  
Trenutna implementacija i odstupanja ostaju u `docs/tehnicka-dokumentacija/cultural-calendar.md` (Technical Overview).
