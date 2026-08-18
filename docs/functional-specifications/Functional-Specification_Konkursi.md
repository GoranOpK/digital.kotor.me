# Digital Kotor
# Funkcionalna specifikacija Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-FS-001
**Naziv:** Funkcionalna specifikacija Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** NACRT
**Verzija:** 0.1.0
**Datum:** 2026-08-18

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Kreirana početna struktura KN-FS-001. Sljedivost uspostavljena prema KN-BM-001. Funkcionalni zahtjevi i BR identifikatori nijesu uneseni; čekaju usvajanje poslovnih pravila iz KN-BM-001 nakon analize Odluke. Bez preuzimanja zahtjeva iz postojeće implementacije ženskog preduzetništva. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (`KN-PATCH-FS-001`, …),
- datum,
- kratak naziv,
- kratak opis izmjene.

PATCH model: KN-RG-001 / DK-DS-001 §8.

---

## Svrha dokumenta

Dokument predstavlja referentnu funkcionalnu specifikaciju cjeline Konkursi.

U verziji 0.1.0 uspostavlja strukturu za buduće funkcionalne zahtjeve, BR identifikatore, aktere, tokove, validacije, statuse, autorizaciju sa poslovnog stanovišta, ivice slučajeva i sljedivost prema KN-BM-001.

**Ne** uvodi poslovna pravila mimo KN-BM-001. **Ne** preuzima zahtjeve iz postojeće implementacije ženskog preduzetništva.

---

# Status razvoja Functional Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Svrha modula | NACRT — identitet; bez funkcionalnih pravila |
| 2. Obuhvat V1 | PENDING BUSINESS ANALYSIS |
| 3. Granice funkcionalnosti | PENDING BUSINESS ANALYSIS |
| 4. Regulatorna i dokumentaciona pravila | NACRT — sljedivost i zabrana izmišljanja |
| 5. Akteri | PENDING BUSINESS ANALYSIS |
| 6. Funkcionalni zahtjevi i tokovi | PENDING BUSINESS ANALYSIS |
| 7. Poslovna pravila (BR) | PENDING BUSINESS ANALYSIS |
| 8. Validacije | PENDING BUSINESS ANALYSIS |
| 9. Statusi | PENDING BUSINESS ANALYSIS |
| 10. Autorizacija sa poslovnog stanovišta | PENDING BUSINESS ANALYSIS |
| 11. Ivice slučajeva (edge cases) | PENDING BUSINESS ANALYSIS |
| 12. Sljedivost prema Business Modelu | NACRT — hijerarhija; bez rule-level mapa |
| 13. Prihvatni kriterijumi V1 | PENDING BUSINESS ANALYSIS |

---

# Pravila upravljanja Functional Specification

1. Funkcionalna specifikacija pripada cjelini Konkursi (KN-FS-001).

2. Posljednja usvojena verzija Functional Specification predstavlja jedini izvor istine za funkcionalne zahtjeve. Verzija 0.1.0 **nije** usvojena kao sadržajni izvor zahtjeva.

3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH.

4. Cursor ne smije samostalno unositi, pretpostavljati ili dopunjavati funkcionalne zahtjeve niti BR identifikatore.

5. KN-FS-001 ostaje sljediv prema KN-BM-001. Ne dokumentuje privremena tehnička ograničenja trenutne implementacije. Ne uvodi pravila kojih nema u KN-BM-001.

6. BR identifikatori se dodjeljuju tek kada postoji usvojeno poslovno pravilo u KN-BM-001 koje se funkcionalno razrađuje.

---

# Upravljanje promjenama

Svaka izmjena funkcionalnog sadržaja mora biti rezultat usvojene odluke i evidentirana kroz `KN-PATCH-FS-*`.

---

## Sadržaj

1. Svrha modula
2. Obuhvat V1
3. Granice funkcionalnosti
4. Regulatorna i dokumentaciona pravila
5. Akteri
6. Funkcionalni zahtjevi i tokovi
7. Poslovna pravila (BR)
8. Validacije
9. Statusi
10. Autorizacija sa poslovnog stanovišta
11. Ivice slučajeva (edge cases)
12. Sljedivost prema Business Modelu
13. Prihvatni kriterijumi V1

---

# 1. Svrha modula

Cjelina **Konkursi** (`KN`) dokumentuje podršku preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.

Ovaj dokument razrađuje **kako** usvojena poslovna pravila iz KN-BM-001 treba da se ponašaju u sistemu. Dok KN-BM-001 nema usvojena poslovna pravila konkursa, FS ne projektuje ponašanje.

Postojeći tok ženskog preduzetništva predstavlja raniju implementaciju cjeline Konkursi i biće predmet posebnog naknadnog dokumentacionog usklađivanja. Do završetka tog procesa ne predstavlja automatski kanonski izvor poslovnih pravila za KN-BM/FS/TS.

---

# 2. Obuhvat V1

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se funkcionalni obuhvat, vrste prijava, koraci postupka ni obavezne funkcije V1.

---

# 3. Granice funkcionalnosti

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Nisu usvojena isključenja van V1. Otvorena pitanja ne ulaze ovdje kao granice.

---

# 4. Regulatorna i dokumentaciona pravila

Funkcionalni sadržaj prati:

* DK-DS-001 i `docs/METHODOLOGY.md`;
* KN-RG-001;
* KN-PRO-001 (pravni izvor, kada bude analiziran);
* KN-BM-001 (jedini izvor poslovnih pravila za ovaj FS).

Identifikovani pravni izvor (naslov; bez analize):

**Odluka o podršci preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.**

Zabranjeno u ovom nacrtu:

* izmišljanje BR pravila;
* preuzimanje zahtjeva iz koda ili TO dokumentacije ženskog preduzetništva;
* uvođenje runtime uloga, ruta ili statusa kao funkcionalnih pravila.

Dokumentaciona načela paketa (`KN-DOC-01` … `KN-DOC-07`) žive u KN-BM-001 §5. **Nisu** BR.

---

# 5. Akteri

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se akteri, poslovne uloge ni mapiranje na postojeće runtime role.

Struktura za kasniju razradu (prazna):

| Akter | Poslovna uloga | Ovlašćenja | Izvor (KN-BM-001) |
|-------|----------------|------------|-------------------|
| — | PENDING BUSINESS ANALYSIS | PENDING BUSINESS ANALYSIS | — |

---

# 6. Funkcionalni zahtjevi i tokovi

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Struktura za kasniju razradu:

| FR / tok | Opis | Preduslov u KN-BM-001 | Status |
|----------|------|------------------------|--------|
| — | PENDING BUSINESS ANALYSIS | nema usvojenog poslovnog pravila | — |

Ne unose se tokovi prijave, evaluacije, odlučivanja, ugovaranja, izvještavanja ni žalbe.

---

# 7. Poslovna pravila (BR)

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Registar BR identifikatora je prazan. Format budućih identifikatora usaglasiće se sa KN-RG-001 kada prvo pravilo bude usvojeno u KN-BM-001 i razrađeno ovdje. **Ne** dodjeljuje se `BR-001` unaprijed.

| BR ID | Pravilo | Izvor KN-BM-001 | Status |
|-------|---------|-----------------|--------|
| — | Nema unesenih pravila | — | PENDING BUSINESS ANALYSIS |

---

# 8. Validacije

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se polja, obavezna dokumentacija, kriterijumi ni pravila odbijanja.

---

# 9. Statusi

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se statusi prijava, prelazi stanja ni lifecycle. Postojeći runtime statusi **nisu** kanonski FS statusi.

---

# 10. Autorizacija sa poslovnog stanovišta

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se ko smije vidjeti, mijenjati ili odlučivati o prijavi. Ne navode se Laravel middleware, gate-ovi ni postojeće role.

---

# 11. Ivice slučajeva (edge cases)

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

---

# 12. Sljedivost prema Business Modelu

Dokumentaciona hijerarhija (nije prenos pravila):

```text
DK-DS-001 / METHODOLOGY
        ↓
KN-RG-001
        ↓
KN-PRO-001
        ↓
KN-BM-001
        ↓
KN-FS-001
        ↓
KN-TS-001
```

Rule-level mapa `poslovno pravilo → BR` **nije** uspostavljena: KN-BM-001 nema usvojena poslovna pravila konkursa.

| KN-BM-001 | KN-FS-001 | Napomena |
|-----------|-----------|----------|
| §5 KN-DOC-01 … KN-DOC-07 | §4 | Dokumentaciona načela; nisu BR |
| §3–§9, §11–§12 | §2–§3, §5–§11, §13 | PENDING BUSINESS ANALYSIS u oba dokumenta |

---

# 13. Prihvatni kriterijumi V1

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

---

**Kraj dokumenta KN-FS-001 v0.1.0**
