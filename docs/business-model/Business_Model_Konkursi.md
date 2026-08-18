# Digital Kotor
# Poslovni model Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-BM-001
**Naziv:** Poslovni model Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** NACRT
**Verzija:** 0.1.0
**Datum:** 2026-08-18

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Kreiran početni kanonski poslovni model cjeline Konkursi. Otvorena struktura dokumenta. Ciljni poslovni model, ne opis postojeće implementacije. Poslovni sadržaj čeka analizu pravnog izvora (KN-PRO-001) i eksplicitne PO odluke. Bez izmišljenih uslova, procedura, statusa ili limita. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (`KN-PATCH-BM-001`, …),
- datum,
- kratak naziv,
- kratak opis izmjene.

Naziv PATCH-a predstavlja zvanični naziv izmjene i koristi se u istoriji verzija. PATCH model: KN-RG-001 / DK-DS-001 §8.

---

## Svrha dokumenta

Dokument predstavlja **ciljni** referentni poslovni model cjeline Konkursi za planiranje, razvoj, testiranje i održavanje.

U verziji 0.1.0 dokument uspostavlja strukturu i evidentira samo PO-usvojene činjenice otvaranja paketa. **Ne** opisuje postojeći aplikacioni kod. **Ne** prenosi poslovna pravila iz postojeće implementacije ženskog preduzetništva.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | NACRT — identitet cjeline; bez poslovnih pravila konkursa |
| 2. Svrha | NACRT — ciljni model; documentation-first |
| 3. Ciljevi | PENDING BUSINESS ANALYSIS |
| 4. Opseg | PENDING BUSINESS ANALYSIS |
| 5. Usvojena dokumentaciona načela ovog paketa | NACRT — samo PO odluke otvaranja paketa |
| 6. Obavezni obuhvat V1 | PENDING BUSINESS ANALYSIS |
| 7. Poslovni entiteti | PENDING BUSINESS ANALYSIS |
| 8. Korisničke uloge | PENDING BUSINESS ANALYSIS |
| 9. Poslovni procesi | PENDING BUSINESS ANALYSIS |
| 10. Veza sa dokumentacijom | NACRT — hijerarhija dokumenata |
| 11. Rječnik poslovnih pojmova | PENDING BUSINESS ANALYSIS |
| 12. Registar usvojenih poslovnih odluka | PENDING BUSINESS ANALYSIS |

---

# Pravila upravljanja Business Modelom

1. Poslovni model predstavlja ciljnu zvaničnu poslovnu specifikaciju cjeline Konkursi (KN-BM-001).

2. Posljednja usvojena verzija Business Modela predstavlja jedini izvor istine (Single Source of Truth) za poslovna pravila cjeline. Verzija 0.1.0 **nije** usvojena kao sadržajni izvor poslovnih pravila konkursa.

3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu ili projektnu odluku.

4. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno unositi, pretpostavljati ili dopunjavati poslovna pravila.

5. Poslovna pravila se izvode iz KN-PRO-001 (nakon analize zvaničnog teksta Odluke) i eksplicitnih PO odluka. Ne izmišljaju se. Ne preuzimaju se nekritički iz postojeće implementacije.

6. Ako postoji razlika između implementacije sistema i usvojenog Business Modela, implementacija se usklađuje sa Business Modelom, osim ako se odlukom ne izmijeni sam Business Model. Ovo pravilo **ne** stupa na snagu za sadržaj koji je još PENDING BUSINESS ANALYSIS.

---

# Upravljanje promjenama

Svaka izmjena poslovnog sadržaja Business Modela mora biti rezultat usvojene poslovne ili projektne odluke i evidentirana kroz odgovarajući PATCH (`KN-PATCH-BM-*`).

---

## Sadržaj

1. Uvod
2. Svrha
3. Ciljevi
4. Opseg
5. Usvojena dokumentaciona načela ovog paketa
6. Obavezni obuhvat V1
7. Poslovni entiteti
8. Korisničke uloge
9. Poslovni procesi
10. Veza sa dokumentacijom
11. Rječnik poslovnih pojmova
12. Registar usvojenih poslovnih odluka

---

# 1. Uvod

Cjelina: **Konkursi** — podrška preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.

Dokumentacioni namespace: **`KN`** (DK-DS-001 §1; KN-RG-001).

Ovo je **ciljni** poslovni model. Nije Technical Overview postojeće implementacije i nije opis trenutnog koda.

Identifikovani pravni izvor (naslov; bez analize teksta):

**Odluka o podršci preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.**

Postojeći tok ženskog preduzetništva predstavlja raniju implementaciju cjeline Konkursi i biće predmet posebnog naknadnog dokumentacionog usklađivanja. Do završetka tog procesa ne predstavlja automatski kanonski izvor poslovnih pravila za KN-BM/FS/TS.

---

# 2. Svrha

Dokumentacija-first: poslovna pravila se usvajaju u KN-BM-001 prije funkcionalne i tehničke razrade.

Svrha budućeg usvojenog sadržaja: definisati šta cjelina Konkursi poslovno radi, za koga, pod kojim uslovima i u kojim granicama.

Konkretna poslovna svrha konkursa (pravo učešća, vrste podrške, efekti odluke) ostaje **PENDING BUSINESS ANALYSIS**.

---

# 3. Ciljevi

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se ciljevi, ishodi, indikatori ni očekivani efekti subvencija dok se ne analizira zvanični tekst Odluke.

---

# 4. Opseg

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne definišu se: ko ima pravo učešća; uslovi; procedure; vrste subvencija; bodovanje; statusi prijava; komisijski procesi; ugovori; izvještavanje; žalbe; limiti; workflow.

Dokumentacioni opseg ovog nacrta: samo otvaranje kanonske strukture KN-BM-001. To **nije** poslovni obuhvat V1 konkursa.

---

# 5. Usvojena dokumentaciona načela ovog paketa

Ova načela **nisu** poslovna pravila konkursa. Evidentiraju PO odluke kojima je otvoren kanonski dokumentacioni paket.

| ID | Načelo | Izvor |
|----|--------|--------|
| KN-DOC-01 | Otvara se rad na kanonskoj dokumentaciji cjeline `KN`. | PO odluka otvaranja paketa |
| KN-DOC-02 | Namespace `KN` je već usvojen (DK-DS-001) i ne predlaže se niti mijenja. | DK-DS-001 §1; KN-RG-001 |
| KN-DOC-03 | Dokumentacija prati DK-DS-001, METHODOLOGY i DK-RG-001. | PO odluka otvaranja paketa |
| KN-DOC-04 | Pravni i poslovni izvor novog modula je identifikovana Odluka (naslov). | KN-PRO-001 |
| KN-DOC-05 | Postojeća dokumentacija i implementacija ženskog preduzetništva se ovim paketom ne mijenjaju. | PO odluka otvaranja paketa |
| KN-DOC-06 | Legacy dokumentacija ženskog preduzetništva nije automatski source of truth za KN-BM/FS/TS. Biće predmet posebnog naknadnog usklađivanja. | PO odluka otvaranja paketa |
| KN-DOC-07 | Poslovna pravila novog modula izvode se iz zvanične Odluke i eksplicitnih PO odluka, ne nekritički iz postojeće implementacije. | PO odluka otvaranja paketa |

Oznake `KN-DOC-*` su dokumentaciona načela ovog paketa. **Nisu** BR identifikatori i **nisu** poslovna pravila iz Odluke.

---

# 6. Obavezni obuhvat V1

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

---

# 7. Poslovni entiteti

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se entiteti, atributi ni relacije iz postojećeg koda niti iz neanalizirane Odluke.

---

# 8. Korisničke uloge

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se poslovne uloge, ovlašćenja ni mapiranje na postojeće runtime role.

---

# 9. Poslovni procesi

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se BP identifikatori, tokovi, statusi, komisijski procesi, ugovori, izvještavanje ni žalbe.

---

# 10. Veza sa dokumentacijom

Dokumentaciona hijerarhija (nije prenos pravnih pravila):

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

Minimalni kanonski lanac sadržaja (DK-DS-001 §11): `KN-BM-001` → `KN-FS-001` → `KN-TS-001`.

| Dokument | Putanja | Status |
|----------|---------|--------|
| KN-RG-001 | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` | USVOJENO |
| KN-PRO-001 | `docs/pravni-okvir/Pravni_okvir_Konkursi.md` | NACRT |
| KN-FS-001 | `docs/functional-specifications/Functional-Specification_Konkursi.md` | NACRT |
| KN-TS-001 | `docs/technical-specifications/Technical-Specification_Konkursi.md` | NACRT |

`KN-FR-*`, `KN-CR-REG-*`, `KN-IS-*`, `KN-IR-*` i UC **nisu** kreirani (DK-DS-001 §3 — ONLY WHEN NEEDED / OPTIONAL).

---

# 11. Rječnik poslovnih pojmova

**Status:** PENDING BUSINESS ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Dokumentacioni termini namespace / Document ID / PRO / PATCH definisani su u KN-RG-001 i DK-DS-001; ne ponavljaju se ovdje kao poslovni pojmovnik konkursa.

---

# 12. Registar usvojenih poslovnih odluka

**Status:** PENDING BUSINESS ANALYSIS

Nijedna poslovna odluka konkursa (pravo učešća, uslovi, procedure, vrste subvencija, bodovanje, statusi, komisija, ugovori, izvještavanje, žalbe, limiti, workflow) **nije** usvojena u ovom dokumentu.

Usvojena je samo PO odluka da se **otvara** kanonski dokumentacioni paket; vidi poglavlje 5 (`KN-DOC-01` … `KN-DOC-07`).

---

**Kraj dokumenta KN-BM-001 v0.1.0**
