# Digital Kotor
# Digital Kotor Documentation Standard v1

**Oznaka dokumenta:** DK-DS-001
**Naziv:** Digital Kotor Documentation Standard v1
**Vlasništvo:** platformski sloj Digital Kotora
**Status dokumenta:** USVOJENO
**Verzija:** 1.0.3
**Datum:** 2026-09-03

Ovo je **normativni** dokumentacioni standard cijele platforme Digital Kotor.

Nije Business Model, Functional Specification ni Technical Specification. Nije dokument FT-004.

Proces i primjena: `docs/METHODOLOGY.md`.
Registar platformske dokumentacije: `DK-RG-001`.

---

# 1. Namespace

Svaki poslovni modul dobija sopstveni dokumentacioni namespace. Numeracija je **lokalna unutar namespace-a i tipa dokumenta**. Ne postoji jedan globalni BM/FS/TS niz.

| Prefiks | Modul / sloj | Status |
|---------|--------------|--------|
| **KK** | Kalendar kulture | AKTIVAN |
| **EP** | e-Plaćanje | AKTIVAN |
| **DK** | Zajednička/platformska dokumentacija | AKTIVAN |
| **KN** | Konkursi | USVOJEN za budući corrective; dokumenti **nisu** kreirani ovim standardom |

Tenderi **nemaju** usvojeni namespace dok Product Owner formalno ne otvori modul.

Žensko preduzetništvo i omladinski konkurs **nijesu** zasebni dokumentacioni moduli. Oni su vrste/tokovi unutar modula **Konkursi** i postojećeg `Competition` framework-a. Poseban `OM-*` namespace **ne postoji**.

Rupe u numeraciji su dozvoljene. Dokument se ne kreira samo da bi se popunio broj.

Lista namespace prefiksa **nije** zatvorena: novi poslovni modul dobija prefiks samo Product Owner odlukom.

---

# 2. Document ID

Kanonski format:

`{NS}-{TYPE}-{NNN}`

Primjeri: `KK-BM-001`, `KK-TS-003`, `EP-TS-001`, `DK-TS-001`, budući `KN-BM-001`, budući `KN-PRO-001`.

Document ID živi u zaglavlju dokumenta. **Filename nije document ID.**

---

# 3. Tipovi dokumenata

| Type | Namjena | Obaveznost za novi poslovni modul |
|------|---------|-----------------------------------|
| **BM** | Poslovni model | MANDATORY |
| **FS** | Funkcionalna specifikacija | MANDATORY |
| **TS** | Tehnička specifikacija | MANDATORY |
| **RG** | Registar skraćenica i oznaka dokumentacije | MANDATORY |
| **UC** | Use Cases | CONDITIONAL / ONLY WHEN NEEDED |
| **FR** | Feature Registry dokument | CONDITIONAL / ONLY WHEN NEEDED |
| **CR-REG** | Change Request Register dokument | CONDITIONAL / ONLY WHEN NEEDED |
| **IS** | Implementation Strategy | OPTIONAL / ONLY WHEN NEEDED |
| **IR** | Implementation Roadmap | OPTIONAL / ONLY WHEN NEEDED |
| **KF** | Katalog (kada modul ima stvarni poslovni katalog) | CONDITIONAL |
| **PRO** | Pravni okvir (samo novi moduli) | CONDITIONAL |
| **DS** | Dokumentacioni standard (platforma) | Nije modulni tip; samo `DK-DS-001` |

Modul može imati jedan ili više TS dokumenata. Svaki ima jasan ownership i lokalnu numeraciju.

**Ne** zahtijevati UC, FR, CR-REG, IS, IR, KF ili PRO radi forme.

---

# 4. Pravni okvir i kolizija `PO`

Za **nove module**:

> Pravni okvir = `{NS}-PRO-{NNN}`

Primjer: `KN-PRO-001`.

`PO-*` u postojećoj dokumentaciji može označavati **Product Owner odluku** (npr. `PO-OB-01`, `PO-DG-07`). To **nije** document type Pravnog okvira.

`EP-PO-001` je već usvojeni document ID Pravnog okvira e-Plaćanja. **KEEP.** Ne migrirati u `EP-PRO-001` ovim standardom.

---

# 5. Kategorije oznaka — ne miješati

Ove kategorije se **ne** smiju mehanički poistovjećivati niti migrirati zajedno.

| Kategorija | Primjer | Pravilo |
|------------|---------|---------|
| Document ID | `KK-TS-003` | `{NS}-{TYPE}-{NNN}` |
| Business rule / business identifier | `BM-DG-07`, `BM-OB-06` | KEEP MODULE-INTERNAL |
| Functional requirement | `FR-OB-*`, `BR-*`, `BR-P-*` | KEEP MODULE-INTERNAL |
| Feature ID | `FT-004` | vidi §6 |
| Change Request instance | `CR-004B` | vidi §7 |
| PATCH ID | `PATCH-FS-060` | vidi §8 |
| Acceptance criterion | `AC-*` | KEEP MODULE-INTERNAL |
| Gap / open finding | `OFD-OB-*` | KEEP MODULE-INTERNAL |
| Runtime / stable key | `source_module = TS-003` | vidi §9 |

---

# 6. Feature ID

Postojeći feature ID-evi ostaju stabilni: `FT-001`, istorijski `FT-002`, `FT-003`, `FT-004`. **Ne** migrirati retroaktivno. **Ne** uvoditi `DK-FT-*`.

Za buduće module globalni `FT-*` broj **nije obavezan**. Modul ne dobija novi `FT-xxx` samo zato što postoji.

Feature Registry je **ONLY WHEN NEEDED**. Ako treba: `{NS}-FR-001`. Model feature ID-a definiše se u RG-u tog modula i **ne** nastavlja automatski istorijski globalni `FT-*` niz.

Postoje: `KK-FR-001`, `DK-FR-001`. To ne obavezuje svaki novi modul.

---

# 7. Change Request

Postojeći KK ID-evi ostaju KEEP: `CR-001`, `CR-002`, `CR-003`, `CR-004A`, `CR-004B`. **Ne** migrirati retroaktivno.

`CR-004B` status se ovim standardom **ne mijenja**. Follow-up: `CR-004B STATUS = NEEDS SEPARATE READ-ONLY REVIEW`.

Za **nove module**: `{NS}-CR-{NNN}` (npr. `KN-CR-001`). Formalni registar, ako treba: `{NS}-CR-REG-001`. CR registar nije obavezan za mali modul.

---

# 8. PATCH

Postojeći KK `PATCH-*` i `PATCH-FS-*` ostaju KEEP. **Nema** retroaktivnog `KK-PATCH-*`.

Postojeći EP namespaced model (`EP-PATCH-BM-*`, `EP-PATCH-FS-*`) ostaje.

Za **nove module**: `{NS}-PATCH-{TYPE}-{NNN}` (npr. `KN-PATCH-BM-001`). Ako modul usvoji jednostavniji model, mora biti eksplicitan u njegovom RG-u.

### Kolizija PATCH oznake prije prve objave

Kanonsko objavljeno stanje dokumentacione PATCH oznake je `origin/main`.

**A. Objavljena oznaka.** Oznaka koja je objavljena na `origin/main` predstavlja kanonski identifikator. Ne smije se naknadno preimenovati niti dodijeliti drugom značenju. Objavljena istorija na `origin/main` se ne prepisuje.

**B. Neobjavljena oznaka.** Oznaka koja postoji samo u lokalnom radu i još nije objavljena na `origin/main` može se prije prve objave korigovati ako je u međuvremenu došlo do kolizije sa oznakom koja je već kanonski objavljena.

**C. Prioritet.** U slučaju kolizije: objavljena oznaka zadržava identitet i značenje; neobjavljena oznaka dobija novu slobodnu oznaku; poslovni i dokumentacioni sadržaj corrective-a se time ne mijenja; objavljena istorija se ne prepisuje.

**D. Registar.** Nova slobodna oznaka mora se provjeriti prema CURRENT registru odgovarajućeg namespace-a prije prve objave.

---

# 9. Runtime / stable key

> Promjena document ID-a **nikada** automatski ne znači promjenu runtime/stable ključa.

Primjer: dokument `KK-TS-003`; runtime `source_module = TS-003`.

Runtime ključ može živjeti u PHP konstantama, bazi, audit zapisima, API payloadima, eventima, testovima i integracijama.

Prije izmjene runtime ključa potreban je zaseban application/data migration audit. Dokumentacioni corrective ga **ne** smije mijenjati implicitno.

---

# 10. RG

Svaki novi poslovni modul mora imati `{NS}-RG-001` (**MANDATORY**).

RG najmanje definiše: namespace; document type prefikse; kanonske document ID-eve; poslovne/interne oznake; feature ID model ako postoji; CR model ako postoji; PATCH model ako postoji; runtime dual-key ako postoji; legacy oznake; cross-module granice; zabranjene / deprecated oznake.

`DK-RG-001` je registar **platformskog sloja**, ne globalni katalog svih poslovnih oznaka svih modula.

---

# 11. Traceability

Minimalni kanonski lanac novog poslovnog modula:

`{NS}-BM-*` → `{NS}-FS-*` → `{NS}-TS-*`

Gdje je primjenjivo: BM → FS → TS → implementation → tests → closeout.

---

# 12. Status — četiri dimenzije

Ove dimenzije **nijesu** sinonimi.

### A. Status dokumenta

Kanonski skup za **nove** dokumente: `NACRT` · `U IZRADI` · `USVOJENO` · `SUPERSEDED` · `ARHIVIRANO`.

Postojeći `STABLE` / `AKTIVAN` / `Usvojen` ostaju legacy/ekvivalent. **Ne** normalizovati retroaktivno ovim paketom.

### B. Status funkcionalnosti / feature-a

Primjeri: `PLANNED` · `IN PROGRESS` · `COMPLETE` · `CLOSED`.

### C. Status implementacije

Primjeri: `NOT STARTED` · `IN PROGRESS` · `IMPLEMENTATION COMPLETE`.

### D. Produkcijski status

Primjeri: `NOT DEPLOYED` · `PRODUCTION ACTIVE` · `PRODUCTION ACCEPTED`.

---

# 13. Verzionisanje i changelog

Minimalni metadata za kanonske dokumente: Document ID · Naziv · Namespace / modul · Verzija · Status dokumenta · Datum posljednje izmjene.

Aktivno razvijani kanonski dokument ima changelog. Istorijski redovi se **ne** prepisuju. Stari ID-evi u istoriji mogu ostati.

Changelog razlikuje: poslovnu izmjenu; funkcionalnu izmjenu; tehničku izmjenu; administrativnu / document-ID izmjenu; status / closeout izmjenu.

Standard važi odmah za **nove** dokumente; za postojeće kroz kontrolisane corrective pakete.

---

# 14. Folderi i imenovanje fajlova

**KEEP CURRENT TYPE-BASED STRUCTURE.** Repo se **ne** reorganizuje po modulima.

Kanonski tip-folderi ostaju: `docs/business-model/`, `docs/functional-specifications/`, `docs/technical-specifications/`, `docs/use-cases/`, `docs/features/`, `docs/change-requests/`, `docs/implementation-strategies/`, `docs/reference/`, `docs/katalog/`, `docs/pravni-okvir/`, `docs/tehnicka-dokumentacija/`.

Postojeći filename može ostati legacy ako su document ID i ownership nedvosmisleni. **Ne** raditi masovni rename.

Za **nove** dokumente: čitljiv filename sa tipom i predmetom/modulom, latinica bez dijakritika, npr. `Business_Model_Konkursi.md`. Document ID u dokumentu je SSOT.

---

# 15. Canonical vs reference / operations

**Canonical module documentation:** BM / FS / TS / RG i uslovni UC / FR / CR / IS / IR / PRO / katalozi.

**Reference / operations:** architecture overview, modules and routes, database entities, deployment, Plesk, environment, handoff, project operations, project status, stubs/future modules.

Technical Overview / Operations **nije** zamjena za BM/FS/TS. **Ne** dodjeljivati joj automatski modulne BM/FS/TS ID-eve.

Registracija i korisnički identitet imaju otvoren platformski dokumentacioni paket. Kanonski poslovni model je **DK-BM-002** (status **USVOJENO**). Taj predmet više **nije** isključivo DK REFERENCE / OPERATIONS.

Uloge i biblioteka dokumenata ostaju **DK REFERENCE / OPERATIONS** dok PO ne otvori zaseban paket. **Nisu** automatski obuhvaćeni `DK-BM-002`.

`DK-UC-002`, `DK-FS-002` i `DK-TS-002` **nisu** uvedeni. Ne kreirati ih usput. Njihovo eventualno uvođenje ide kroz kontrolisani dokumentacioni korak prema ovom standardu i PO procesu.

---

# 16. Razvojni prioritet (kanonski)

* **e-Plaćanje** = trenutni razvojni prioritet.
* **Omladinski konkurs** = ODLOŽEN ZA SADA. Ne pokretati razvoj. Ne uvoditi `OM-*`.
* **Tenderi** = STUB / FUTURE MODULE. Namespace se ne dodjeljuje dok PO ne otvori modul.
* **Konkursi (`KN-*`)** = usvojeni namespace; dokumentaciona migracija **nije** ovaj paket.

---

# 17. Controlled corrective

Izmjena dokumentacione arhitekture ide kroz kontrolisani paket: read-only audit → PO odluka → documentation-only izmjena → validacija da aplikacioni kod nije diran.

Ne radi se mehanički rename, ne radi se implicitna izmjena runtime ključa, ne usvaja se novi namespace bez PO odluke.

---

# 18. Closeout i legacy

Closeout feature-a / faze **nije** isto što i status dokumenta.

Stabilni legacy ID-evi (KK `PATCH-*`, KK `CR-*`, `FT-*`, `EP-PO-001`, runtime `TS-003`) ostaju KEEP dok poseban paket eksplicitno ne kaže drugačije.

---

# 19. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-17 | Usvojen Digital Kotor Documentation Standard v1. Formalizovani namespace, document ID, tipovi, PRO vs EP-PO-001, CR/PATCH/FT/runtime pravila, statusne dimenzije, folderi. KN rezervisan. Tenderi bez namespace-a. |
| 1.0.1 | 2026-09-03 | Evidencija PO-usvojenog pravila u §8 PATCH: neobjavljena dokumentaciona oznaka može se korigovati prije prve objave pri koliziji sa već objavljenom oznakom; objavljena oznaka zadržava identitet; objavljena istorija na `origin/main` se ne prepisuje. |
| 1.0.2 | 2026-09-03 | §15: usklađenje sa PO-otvorenim paketom registracije i korisničkog identiteta. `DK-BM-002` je kanonski poslovni model (U IZRADI). Uloge i biblioteka dokumenata ostaju DK REFERENCE / OPERATIONS. `DK-UC-002` / `DK-FS-002` / `DK-TS-002` nisu uvedeni. |
| 1.0.3 | 2026-09-03 | §15: status `DK-BM-002` usklađen sa PO usvajanjem dokumenta kao cjeline (USVOJENO). `DK-UC-002` / `DK-FS-002` / `DK-TS-002` nisu uvedeni. |

---

**Kraj dokumenta DK-DS-001 v1.0.3**
