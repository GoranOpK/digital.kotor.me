# Digital Kotor
# Pravni okvir Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-PRO-001
**Naziv:** Pravni okvir Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** NACRT
**Verzija:** 0.1.0
**Datum:** 2026-08-18

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Kreirana početna struktura KN-PRO-001. Identifikovan pravni izvor po naslovu. Pravni sadržaj čeka analizu zvaničnog teksta Odluke. Bez izmišljenih članova, kriterijuma, iznosa ili rokova. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument definiše pravni okvir cjeline Konkursi na platformi Digital Kotor.

Predstavlja osnov za usklađenost poslovnog modela, funkcionalnosti i tehničke realizacije sa važećim propisima Crne Gore i Opštine Kotor.

U verziji 0.1.0 dokument **samo** uspostavlja strukturu i evidentira identifikovani pravni izvor. **Ne** sadrži pravnu analizu, tumačenje članova niti izvedena poslovna pravila.

Tip dokumenta je **PRO** (DK-DS-001 §4). Ovo **nije** Product Owner odluka (`PO-*`) i **nije** `EP-PO-001`.

---

# Status razvoja

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | NACRT — identifikovan izvor; bez pravne analize |
| 2. Identifikovani pravni izvor | NACRT — naveden naslov Odluke |
| 3. Propis kao izvor istine | NACRT — metodološko pravilo; bez sadržaja Odluke |
| 4. Registar pravnih odredbi | PENDING LEGAL SOURCE ANALYSIS |
| 5. Izvedena pravila prema poslovnom modelu | PENDING LEGAL SOURCE ANALYSIS |
| 6. Veza sa ostalom dokumentacijom | NACRT — hijerarhija dokumenata |
| 7. Završne odredbe | PENDING LEGAL SOURCE ANALYSIS |

---

# Pravila upravljanja dokumentom

1. Pravni okvir pripada cjelini Konkursi (KN-PRO-001).

2. Cursor ne smije samostalno unositi, pretpostavljati ili dopunjavati pravne podatke.

3. Ne potvrđeni pravni podaci ne smiju se unositi. Dok zvanični tekst Odluke nije analiziran, sadržajne sekcije ostaju **PENDING LEGAL SOURCE ANALYSIS**.

4. Izmjene se evidentiraju kroz novi red u istoriji verzija / PATCH (`KN-PATCH-PRO-*` kada bude izdat; vidi KN-RG-001).

5. Postojeći tok ženskog preduzetništva nije automatski izvor pravnih pravila ovog dokumenta.

---

## Sadržaj

1. Uvod
2. Identifikovani pravni izvor
3. Propis kao izvor istine
4. Registar pravnih odredbi
5. Izvedena pravila prema poslovnom modelu
6. Veza sa ostalom dokumentacijom
7. Završne odredbe

---

# 1. Uvod

Cjelina **Konkursi** na platformi Digital Kotor dokumentuje se u namespace-u `KN`.

Ovaj dokument je pravni sloj tog paketa. Poslovna pravila se ne izmišljaju ovdje; izvode se kasnije iz zvaničnog teksta identifikovanog izvora i eksplicitnih PO odluka, pa se prenose u KN-BM-001.

---

# 2. Identifikovani pravni izvor

**Status:** identifikovan naslov; tekst nije analiziran u ovom koraku.

Identifikovani pravni osnov:

**Odluka o podršci preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.**

U repositoryju **nije** uložen zvanični tekst Odluke u trenutku kreiranja ovog nacrta.

Ovaj naslov **nije** tumačenje sadržaja. Nisu uneseni članovi, stavovi, aneksi, datumi stupanja na snagu, ni drugi pravni elementi.

---

# 3. Propis kao izvor istine

**Status:** metodološko pravilo paketa; nije pravna analiza.

Zvaničan izvor poslovnih pravila konkursa, kada bude analiziran, je identifikovana Odluka (i, prema `docs/tehnicka-dokumentacija/project-operations.md`, katalog propisa / Službeni list). Pravila se ne izmišljaju i ne preuzimaju nekritički iz postojeće implementacije.

Dok analiza nije završena, nijedna odredba se ne smatra unesenom u KN kanonski paket.

---

# 4. Registar pravnih odredbi

**Status:** PENDING LEGAL SOURCE ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

Ne unose se: članovi Odluke, kriterijumi, iznosi, rokovi, kategorije korisnika, procenti, bodovanje, obavezna dokumentacija, sastav komisije, rokovi žalbe, način odlučivanja, niti bilo koje drugo pravno pravilo.

---

# 5. Izvedena pravila prema poslovnom modelu

**Status:** PENDING LEGAL SOURCE ANALYSIS

Lanac koji će se uspostaviti nakon analize Odluke:

`pravna odredba → poslovno pravilo (KN-BM-001) → BR (KN-FS-001) → TS → implementacija → test`

Ovaj nacrt **ne** prenosi pravna pravila u BM.

---

# 6. Veza sa ostalom dokumentacijom

Dokumentaciona hijerarhija (nije prenos sadržaja Odluke):

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

| Dokument | Putanja |
|----------|---------|
| KN-RG-001 | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` |
| KN-BM-001 | `docs/business-model/Business_Model_Konkursi.md` |
| KN-FS-001 | `docs/functional-specifications/Functional-Specification_Konkursi.md` |
| KN-TS-001 | `docs/technical-specifications/Technical-Specification_Konkursi.md` |

Postojeći tok ženskog preduzetništva predstavlja raniju implementaciju cjeline Konkursi i biće predmet posebnog naknadnog dokumentacionog usklađivanja. Do završetka tog procesa ne predstavlja automatski kanonski izvor poslovnih pravila za KN-BM/FS/TS.

---

# 7. Završne odredbe

**Status:** PENDING LEGAL SOURCE ANALYSIS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivog sadržaja.

---

**Kraj dokumenta KN-PRO-001 v0.1.0**
