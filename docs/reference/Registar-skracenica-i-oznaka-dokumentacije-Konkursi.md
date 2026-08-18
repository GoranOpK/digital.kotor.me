# Digital Kotor
# Registar skraćenica i oznaka dokumentacije Konkursa

**Oznaka dokumenta:** KN-RG-001
**Naziv:** Registar skraćenica i oznaka dokumentacije Konkursa
**Modul:** Konkursi
**Vlasništvo:** cjelina Konkursi (`KN`)
**Status dokumenta:** USVOJENO
**Verzija:** 1.0.0
**Datum:** 2026-08-18

---

# 1. Identitet i svrha

KN-RG-001 je referentni i živi dokument. Predstavlja centralni registar skraćenica i dokumentacionih oznaka cjeline **Konkursi**.

Nije poslovni pojmovnik. Ne definiše poslovna pravila ni tehnička rješenja. Ne zamjenjuje KN-PRO-001, KN-BM-001, KN-FS-001 ili KN-TS-001.

Nije registar Kalendara kulture (`KK-RG-001`), e-Plaćanja (`EP-RG-001`) ni platformskog sloja (`DK-RG-001`). Nije globalni katalog svih poslovnih oznaka svih modula.

Normativni dokumentacioni standard platforme je **DK-DS-001** (`docs/reference/Digital-Kotor-Documentation-Standard.md`). Proces i TS struktura: `docs/METHODOLOGY.md`. Ovaj registar **referencira** ta pravila; ne uvodi paralelni platformski standard.

---

# 2. Namespace

Dokumentacioni namespace cjeline Konkursi je **`KN`**.

Značenje: poslovna cjelina **Konkursi** na platformi Digital Kotor, uključujući podršku preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.

Namespace `KN` je usvojen u DK-DS-001 §1. Ovaj paket **ne** predlaže niti mijenja prefiks.

Žensko preduzetništvo i omladinski konkurs **nijesu** zasebni dokumentacioni moduli. Oni su vrste/tokovi unutar cjeline Konkursi. Poseban `OM-*` namespace **ne postoji** (DK-DS-001 §1).

Tenderi **nemaju** namespace `KN`. `KK-*`, `EP-*` i `DK-*` **ne** pripadaju ovom registru.

---

# 3. Document ID

Kanonski format (DK-DS-001 §2):

`{NS}-{TYPE}-{NNN}`

Primjeri: `KN-RG-001`, `KN-PRO-001`, `KN-BM-001`, `KN-FS-001`, `KN-TS-001`.

Numeracija je **lokalna unutar namespace-a `KN` i tipa dokumenta**. Ne postoji jedan globalni BM/FS/TS niz. Rupe u numeraciji su dozvoljene. Dokument se ne kreira samo da bi se popunio broj (DK-DS-001 §1).

Document ID živi u zaglavlju dokumenta. **Filename nije document ID i nije SSOT.** SSOT je Document ID (DK-DS-001 §2, §14).

---

# 4. Tipovi dokumenata

Tipovi i obaveznost preuzeti su iz DK-DS-001 §3. Ovaj registar ih ne proširuje.

| Type | Namjena | Status u KN paketu (2026-08-18) |
|------|---------|----------------------------------|
| **RG** | Registar skraćenica i oznaka | Kreiran: KN-RG-001 (ovaj dokument) |
| **PRO** | Pravni okvir (novi moduli) | Kreiran: KN-PRO-001 — NACRT |
| **BM** | Poslovni model | Kreiran: KN-BM-001 — NACRT |
| **FS** | Funkcionalna specifikacija | Kreiran: KN-FS-001 — NACRT |
| **TS** | Tehnička specifikacija | Kreiran: KN-TS-001 — NACRT |
| **UC** | Use Cases | CONDITIONAL / ONLY WHEN NEEDED — **NOT YET CREATED** |
| **FR** | Feature Registry | CONDITIONAL / ONLY WHEN NEEDED — **NOT YET CREATED** |
| **CR-REG** | Change Request Register | CONDITIONAL / ONLY WHEN NEEDED — **NOT YET CREATED** |
| **IS** | Implementation Strategy | OPTIONAL / ONLY WHEN NEEDED — **NOT YET CREATED** |
| **IR** | Implementation Roadmap | OPTIONAL / ONLY WHEN NEEDED — **NOT YET CREATED** |
| **KF** | Katalog | CONDITIONAL — **NOT YET CREATED** |
| **DS** | Dokumentacioni standard | Nije modulni tip; samo `DK-DS-001` |

Modul može imati jedan ili više TS dokumenata. Svaki ima lokalnu numeraciju. `KN-TS-001` je prvi TS; dodatni `KN-TS-00n` se ne kreiraju unaprijed.

**Ne** kreirati UC, FR, CR-REG, IS, IR ili KF radi forme (DK-DS-001 §3).

Pravni okvir novog modula koristi tip **PRO**, ne `PO`. `PO-*` u drugim modulima može označavati Product Owner odluku; to **nije** document type Pravnog okvira (DK-DS-001 §4). `EP-PO-001` ostaje KEEP za e-Plaćanje i **nije** KN dokument.

---

# 5. Kanonski dokumenti (aktuelno)

| Oznaka | Dokument | Putanja | Status dokumenta |
|--------|----------|---------|------------------|
| **KN-RG-001** | Registar skraćenica i oznaka dokumentacije Konkursa | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` | USVOJENO |
| **KN-PRO-001** | Pravni okvir Konkursa | `docs/pravni-okvir/Pravni_okvir_Konkursi.md` | NACRT |
| **KN-BM-001** | Poslovni model Konkursa | `docs/business-model/Business_Model_Konkursi.md` | NACRT |
| **KN-FS-001** | Funkcionalna specifikacija Konkursa | `docs/functional-specifications/Functional-Specification_Konkursi.md` | NACRT |
| **KN-TS-001** | Tehnička specifikacija Konkursa | `docs/technical-specifications/Technical-Specification_Konkursi.md` | NACRT |

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

Minimalni kanonski lanac sadržaja prema DK-DS-001 §11: `KN-BM-*` → `KN-FS-*` → `KN-TS-*`. Pravni izvor, kada bude analiziran, ulazi preko KN-PRO-001 u BM, ne zaobilazeći taj lanac.

---

# 6. PATCH, Feature ID, Change Request

Pravila su DK-DS-001 §6–§8. KN ih primjenjuje, ne mijenja.

| Kategorija | KN model | Status |
|------------|----------|--------|
| PATCH | `{NS}-PATCH-{TYPE}-{NNN}` npr. `KN-PATCH-BM-001` | RESERVED; nijedan PATCH još nije izdat |
| Feature ID | Globalni `FT-*` **nije obavezan**. Ako se kasnije uvede FR: `{NS}-FR-001`; model feature ID-a definiše se tada u ovom RG-u i **ne** nastavlja automatski istorijski `FT-*` niz. | **NOT YET CREATED** |
| CR | Ako zatreba: `KN-CR-{NNN}`; registar `KN-CR-REG-001` | **NOT YET CREATED** |
| KN-DOC | Dokumentaciona načela otvaranja paketa (`KN-DOC-01` … `KN-DOC-07`) | Žive u KN-BM-001 §5. **Nisu** poslovna pravila konkursa i **nisu** BR. |

KK `PATCH-*` / `CR-*` i EP `EP-PATCH-*` **ne** koriste se u KN dokumentima.

Kategorije oznaka se ne smiju mehanički poistovjećivati (DK-DS-001 §5): Document ID ≠ poslovno pravilo ≠ BR ≠ Feature ID ≠ CR ≠ PATCH ≠ runtime ključ.

---

# 7. Runtime / stable key

Promjena document ID-a **nikada** automatski ne znači promjenu runtime/stable ključa (DK-DS-001 §9).

U ovom otvaranju paketa **nije dodijeljen** nijedan KN runtime ključ (`source_module` ili drugi).

Postojeći runtime identiteti implementacije ženskog preduzetništva (klase, tabele, rute, statusi u kodu) ostaju KEEP dok poseban application/data migration audit i PO paket ne odrede drugačije. Ovaj dokumentacioni paket ih **ne** mijenja implicitno.

---

# 8. Status, verzija, changelog

Četiri dimenzije statusa nijesu sinonimi (DK-DS-001 §12).

Za **nove** KN dokumente status dokumenta: `NACRT` · `U IZRADI` · `USVOJENO` · `SUPERSEDED` · `ARHIVIRANO`.

PO odluka da se **otvara** kanonski KN paket je USVOJENA. Status pojedinačnih sadržajnih dokumenata (PRO/BM/FS/TS) u ovom koraku je **NACRT**, jer pravni i poslovni sadržaj još nije analiziran.

Minimalni metadata: Document ID · Naziv · Namespace / modul · Verzija · Status dokumenta · Datum posljednje izmjene (DK-DS-001 §13).

Changelog: novi red; stari redovi se ne prepisuju. Razlikovati poslovnu, funkcionalnu, tehničku, administrativnu i status/closeout izmjenu.

Closeout feature-a ili faze **nije** isto što i status dokumenta (DK-DS-001 §18).

---

# 9. Folderi

KEEP CURRENT TYPE-BASED STRUCTURE (DK-DS-001 §14). KN dokumenti stoje u postojećim tip-folderima. **Ne** postoji `docs/KN/`.

---

# 10. Cross-module granice

* `KN-*` ne registruje Kalendar kulture, e-Plaćanje ni platformski FT-004 kao KN sadržaj.
* `KK-*` i `EP-*` nijesu izvor poslovnih pravila za KN-BM/FS/TS.
* `DK-*` je platformski sloj; `DK-RG-001` nije katalog svih KN oznaka.
* Homofoni se ne spajaju: `PO-*` (Product Owner) ≠ pravni okvir `KN-PRO-*` ≠ `EP-PO-001`.

---

# 11. Legacy i žensko preduzetništvo

Postojeći tok ženskog preduzetništva predstavlja raniju implementaciju cjeline Konkursi i biće predmet posebnog naknadnog dokumentacionog usklađivanja. Do završetka tog procesa ne predstavlja automatski kanonski izvor poslovnih pravila za KN-BM/FS/TS.

U ovom paketu se **ne** radi retroaktivna migracija, **ne** označavaju postojeći TO dokumenti kao SUPERSEDED i **ne** mijenja runtime.

Zabranjene / deprecated oznake u KN kanonskim dokumentima:

| Oznaka | Pravilo |
|--------|---------|
| **OM-*** | Ne postoji. Ne uvoditi. |
| **KN-PO-*** kao document type pravnog okvira | Zabranjeno; pravni okvir = `KN-PRO-*`. |
| **FT-xxx** dodijeljen samo zato što modul postoji | Zabranjeno (DK-DS-001 §6). |
| Preuzimanje KK/EP document ID-eva kao KN ID-eva | Zabranjeno. |

---

# 12. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-18 | Uspostavljen KN-RG-001. Otvoren kanonski dokumentacioni paket cjeline Konkursi. Registrovani namespace `KN`, tipovi, kanonski dokumenti KN-PRO/BM/FS/TS-001 (NACRT), PATCH/FR/CR model po DK-DS-001, dokumentaciona načela `KN-DOC-*`, runtime dual-key KEEP, granice prema KK/EP/DK i legacy ženskom preduzetništvu. Bez poslovnih pravila iz Odluke. Bez izmjene aplikacionog koda. |

---

**Kraj dokumenta KN-RG-001 v1.0.0**
