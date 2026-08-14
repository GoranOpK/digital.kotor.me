# Digital Kotor
# Implementation Roadmap
## Kalendar kulture V1

**Oznaka dokumenta:** IR-001  
**Naziv:** Implementation Roadmap — Kalendar kulture V1  
**Feature ID:** FT-001 (+ FT-003 / TS-012)  
**Modul:** Kalendar kulture  
**Status dokumenta:** Active  
**Verzija:** 1.0.14
**Datum:** 2026-08-14

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|-------|------|
| 1.0.0 | 2026-08-07 | Početni implementacioni roadmap na osnovu usvojene dokumentacije (BM, FS, Feature Registry, IS-001, TS-001 / TS-003–TS-012) i stanja produkcije. |
| 1.0.1 | 2026-08-07 | **PATCH-001 (FINAL):** TS-012 isključivo kao završna integraciona faza (bez audit skeleta u Fazi 1); obavezne stabilizacione faze; princip jedne velike migracije domena po deploymentu; implementaciona disciplina (jedna logička cjelina po zadatku); usklađen konačni redoslijed Faza 0–8. |
| 1.0.2 | 2026-08-07 | Dokumentaciona napomena: Faza 1 (TS-006/007/008) završena u kodu; Faza 2 (TS-001) spremna za Korak 1 nakon PO-ORG-01–04. Bez izmjene redoslijeda faza. |
| 1.0.3 | 2026-08-07 | **PO-EV-01:** Postojeći `cultural_events` su testni/prototipski podaci (ne referentni produkcijski sadržaj). Faza 3 bez migracije/backfill/dual-write legacy zapisa; rizik = novi domen + cutover portala + zamjena flat modela. Bez izmjene BM/FS. |
| 1.0.4 | 2026-08-08 | **TS-010 V1 closeout:** Faza 5 (TS-010 Urednički portal) V1 funkcionalno / implementaciono završena. Naredna velika faza ostaje **Faza 6 → TS-009** (javni portal / domen cutover). **Faza 8 → TS-012** ostaje buduća audit integracija. Bez izmjene redoslijeda faza. Bez izmjene BM/FS. |
| 1.0.5 | 2026-08-09 | **Faza 6A / 6B:** Faza 6 podijeljena — **6A** javni portal Događaja (kanonski cutover; TS-009); **6B** Manifestacije (TS-005 + TS-009 §6). TS-005 **ne blokira** 6A. Usklađeno sa BM PATCH-060 / FS PATCH-FS-060 / TS-009 v1.0.6. Bez izmjene implementacije. |
| 1.0.6 | 2026-08-11 | **6B-05A status sync:** FAZA 4 realizovana kroz **6B-01**; FAZA 6B (portal + Pretraga tip) kroz **6B-03/03A/04**; editorial MF kroz **6B-02**. **PHASE 6B FUNCTIONAL IMPLEMENTATION COMPLETE** (TESTED / COMMITTED / PUSHED). **PRODUCTION DEPLOY / SMOKE: NOT DONE.** Bez izmjene redoslijeda Faza 7–8. Bez izmjene BM/FS. |
| 1.0.7 | 2026-08-12 | **PHASE 6B PRODUCTION CLOSEOUT (status only):** 6B deployed; dvije 6B migracije RAN; editorial lifecycle + kk_admin nav **PRODUCTION VERIFIED**; PO **PRODUCTION ACCEPTED** WITH LIMITED CONTENT-SMOKE COVERAGE; **Phase 6B formally closed**. NON-BLOCKING PRODUCTION SMOKE DEBT evidentiran (nije defect). Bez izmjene redoslijeda Faza 7–8. Bez izmjene BM/FS pravila. |
| 1.0.8 | 2026-08-12 | **6A residual Package A status sync:** `cultural-calendar.day` canonical cutover **IMPLEMENTED / TESTED (local)** (`CulturalPublicEventQuery::filterByDate` + `occurrenceOnDate`; legacy rollback KEEP). **NOT PRODUCTION VERIFIED.** Phase B hard-remove / flag cleanup ostaje. Bez izmjene redoslijeda Faza 7–8. Bez izmjene BM/FS. |
| 1.0.9 | 2026-08-12 | **6A residual Package A PRODUCTION CLOSEOUT (status only):** `day()` canonical cutover **DEPLOYED** (`f35cb2e`); production smoke empty-date **PASS** — **PRODUCTION VERIFIED — EMPTY-DATE SCENARIO CONFIRMED**; content-bearing day not separately production-smoked (local coverage; not a blocker). **Package A CLOSED.** Phase B hard-remove / flag cleanup ostaje **OPEN**. Bez izmjene redoslijeda Faza 7–8. Bez izmjene BM/FS. |
| 1.0.10 | 2026-08-13 | **Phase B1+B2 status sync:** flag + dual-read + legacy CRUD runtime **REMOVED** (canonical-only public read). **B3** `cultural_events` DROP = **OPEN / DEFERRED**. **IMPLEMENTED / TESTED (local); NOT PRODUCTION VERIFIED.** FAZA 7 (TS-011) ostaje naredna velika faza. Bez izmjene BM/FS. |
| 1.0.11 | 2026-08-13 | **FAZA 6A FINAL DOCUMENTATION CLOSURE:** FAZA 6A = **CLOSED**; B1+B2 = **PRODUCTION VERIFIED / CLOSED**; categories **14/14 PASS**; public SSOT canonical-only; dual-read/write = 0; B3 DROP = **DEFERRED / non-blocking**; FAZA 7 ostaje naredna. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 1.0.12 | 2026-08-14 | **PO-NL-01…22 / Newsletter decision sync:** FAZA 7 cilj = kanonski TS-011 v1.0.2; postojeći Newsletter = testna implementacija; **bez** migracije testnih pretplatnika / e-mail-only backfill-a; CANONICAL MODEL WINS. Usklađeno sa BM PATCH-073 / FS PATCH-FS-072. Bez izmjene implementacije. |
| 1.0.13 | 2026-08-14 | **NL-03 temporal eligibility + ledger boundary:** FAZA 7 cilj = kanonski TS-011 v1.0.3. NL-03 = FIRST_INCLUDE ELIGIBILITY / CANDIDATE FOUNDATION (bez ledger write, bez e-maila, bez queue/scheduler). Usklađeno sa BM PATCH-074 / FS PATCH-FS-073. Bez izmjene implementacije. |
| 1.0.14 | 2026-08-14 | **FAZA 7 FORMAL CLOSEOUT + STABILIZATION (status only):** NL-01…NL-06 **IMPLEMENTED / TESTED / COMMITTED / PUSHED**; kanonski model TS-011 v1.0.3. **FAZA 7 = FORMALLY CLOSED.** Repo HEAD `da5220d` (docs routing) / NL-06 `f9b8216`. Production evidence = **PO-CONFIRMED** (migracije Ran; regular 6h; priority 5 min; legacy weekly invoker = 0; `/newsletter` settings). Live production Git HEAD = **UNOBSERVED** iz Cursora. KEEP V1 limitations (Organizer listing URL; crash-after-SMTP; no queue/outbox; physical legacy files). Naredna numerisana faza = **Faza 8 / TS-012**. Bez izmjene BM/FS/TS-011 ugovora. Bez izmjene implementacije. |

---

# 1. Identitet i granice

| Stavka | Vrijednost |
|--------|------------|
| Tip | Operativni planski dokument |
| Svrha | Redoslijed, faze, disciplina i rizici implementacije Kalendara kulture V1 |
| Ne zamjenjuje | BM, FS, Feature Registry, TS, IS-001 |

Ovaj dokument:

* ne definiše nova poslovna pravila;
* ne mijenja BM, FS, Feature Registry, Technical Specification ni IS-001;
* ne sadrži SQL, Laravel kod ni PATCH predloge za te dokumente;
* mora ostati sljediv prema usvojenim TS i IS-001.

---

# 2. Referentni dokumenti

| Dokument | Uloga |
|----------|--------|
| Business Model — Kalendar kulture (Stable) | Poslovna pravila |
| Functional Specification — Kalendar kulture (Stable) | Funkcionalni zahtjevi |
| Feature Registry | FT-001 / FT-003; plan TS |
| IS-001 | Implementaciona strategija javnog portala (TS-009) |
| Change Request Register | CR-001…CR-004B |
| TS-001, TS-003–TS-012 | Usvojene tehničke specifikacije |

---

# 3. Principi (PATCH-001)

## 3.1 TS-012 — samo završna integraciona faza

TS-012 (Evidencija aktivnosti) je **centralni prijemnik** audit događaja.

**Ne uvoditi** TS-012 parcijalno (npr. „audit skelet“ u Fazi 1).

Puna vrijednost TS-012 postoji tek kada postoje kanonski emiteri iz:

* TS-001
* TS-003
* TS-004
* TS-005
* TS-010
* TS-011

**Pravilo:** TS-012 implementirati kao **FAZU 8** — završnu integracionu fazu — nakon stabilizacije svih emitera.

## 3.2 Stabilizaciona faza

Nakon **svake** velike implementacione faze slijedi obavezna **Stabilizacija**.

Minimalni obuhvat stabilizacije:

* Feature testovi
* Regresioni testovi
* Code review
* Staging validacija
* Smoke test produkcije
* Posmatranje stabilnosti prije naredne velike faze

Trajanje (broj dana) **nije** propisano ovim dokumentom — ostaje operativna odluka.

## 3.3 Migracije — jedna velika migracija domena po deploymentu

**Princip:** Jedan deployment **ne smije** sadržati više od jedne velike migracije domena.

Primjeri velikih migracija (schema / novi domen):

* Uvođenje novog modela Događaj + Održavanja 1..N (TS-003 / TS-004) — **bez** migracije/backfill-a legacy `cultural_events` sadržaja (**PO-EV-01**)
* Manifestacije (TS-005)
* Newsletter model (TS-011) — **bez** migracije/backfill-a testnih pretplatnika (**PO-NL-22**)

**PO-EV-01:** Postojeći zapisi u `cultural_events` smatraju se isključivo testnim/prototipskim podacima, ne referentnim produkcijskim sadržajem. Ne radi se migracija tih zapisa, backfill, dual-write ni adapteri radi očuvanja legacy sadržaja. Novi domen implementira se direktno prema BM/FS/TS; legacy flat model ostaje privremeno do cutover-a.

Svaka velika migracija mora imati:

* backup
* rollback plan
* staging dry-run
* produkcioni smoke test

## 3.4 Implementaciona disciplina

**Pravilo:** Jedan implementacioni zadatak obuhvata **jednu logičku cjelinu** iz odgovarajuće TS dokumentacije.

Ne implementirati kompletan TS u jednom koraku.

Svaka cjelina prolazi:

```text
analiza
  ↓
implementacija
  ↓
test
  ↓
review
  ↓
merge
  ↓
deploy
```

---

# 4. Matrica po TS

Napomena: „API“ = nove/izmijenjene HTTP rute i kontroleri (Blade monolit).

| TS | Naziv | Baza | Novi API / rute | UI | Zavisnosti | Paralelno? | Složenost |
|----|-------|:----:|:---------------:|:--:|------------|------------|-----------|
| **TS-001** | Organizator / Moderator / zahtjevi | Da | Da | Da | Platformski User/Role; emisija → TS-012 (tek Faza 8) | Djelimično sa katalozima nakon Faze 1 | **Visoka** |
| **TS-003** | Događaj | Da | Da | Da (preko TS-010) | TS-001, TS-004, TS-006–008 | Ne — jezgro sa TS-004 | **Vrlo visoka** |
| **TS-004** | Održavanje | Da | Da | Da (TS-010) | TS-003, TS-006 | Samo u paru sa TS-003 | **Vrlo visoka** |
| **TS-005** | Manifestacija | Da | Da | Da | TS-001, TS-003, TS-004 | Nakon jezgra | **Visoka** |
| **TS-006** | Lokacije | Da | Da | Da | Potrošači TS-003/004; audit emit tek Faza 8 | Da — u Fazi 1 | **Srednja–visoka** |
| **TS-007** | Kategorije i oznake | Da | Da | Da | TS-003; bez migracije test ENUM-a | Da — u Fazi 1 | **Srednja** |
| **TS-008** | Mediji | Da + storage | Da | Da | TS-003/005/007 | Da — u Fazi 1 | **Srednja–visoka** |
| **TS-009** | Javni portal | Po fazi | Proširenje | Da | CR-004B (Faza 0); domen za Fazu 6 | CR-004B rano; domen kasnije | **Srednja** (preostalo) |
| **TS-010** | Urednički portal | Koristi domen | Da | Da | TS-001, 003–008; emit → TS-012 (Faza 8) | Nakon domena; **Faza 5 V1 završena** | **Vrlo visoka** |
| **TS-011** | Newsletter | Da | Da + job | Da | TS-001, 003, 004, 009, 010 | **Faza 7 V1 završena / FORMALLY CLOSED** | **Visoka** (zatvorena) |
| **TS-012** | Evidencija aktivnosti | Da | Da | Min. Admin | Svi emiteri stabilni | **Ne** — samo Faza 8 | **Srednja** |

### Stanje IS-001 / CR (javni portal, postojeći model)

| CR | Status | Obuhvat |
|----|--------|---------|
| CR-001…CR-004A | Implemented | UI, filteri, badge |
| **CR-004B** | **Planned** | Javni prikaz `cancelled` — Faza 0 |

---

# 5. Postojeća produkcija (sažetak)

| Područje | Stanje | Akcija |
|----------|--------|--------|
| Javni portal | Postoji; CR-001–004A usklađeni | Faza 0: CR-004B; **Faza 6A:** kanonski cutover Događaja (**završena**); **Faza 6B:** Manifestacije (**FORMALLY CLOSED** / **PRODUCTION ACCEPTED** WITH LIMITED CONTENT-SMOKE COVERAGE) |
| `CulturalEvent` flat | Postoji (testni/prototipski podaci — **PO-EV-01**) | Faza 3: novi domen TS-003/004; zamjena flat modela **bez** migracije/backfill legacy sadržaja |
| Admin `kk_admin` | Postoji | Refaktor u Fazi 5 (TS-010) |
| Organizator / Moderator | Nema | Faza 2 (TS-001) |
| Održavanja 1..N | Nema | Faza 3 |
| Manifestacije | **PRODUCTION ACCEPTED** (6B-01…6B-04 + PO-MF-WF; deployed) | **FAZA 4 / 6B FORMALLY CLOSED**; migracije RAN; `cultural_manifestations` = 0 redova; cleanup N/A |
| Katalozi lokacija / kategorija / medija | Nema / ENUM | Faza 1 |
| Newsletter (Kalendar kulture) | **FAZA 7 FORMALLY CLOSED** — kanonski `User` pretplata; regular 6h + priority 5 min | Legacy weekly runtime **disabled**; tabela `newsletter_subscribers` fizički KEEP; **bez** backfill-a (**PO-NL-22**). Naredno: **Faza 8 / TS-012** |
| Centralni audit (FT-003) | Nema | **Faza 8** (TS-012) — ne ranije |

---

# 6. Konačni redoslijed implementacije

```text
FAZA 0
  CR-004B
    ↓
  Stabilizacija
    ↓
FAZA 1
  TS-006
  TS-007
  TS-008
    ↓
  Stabilizacija
    ↓
FAZA 2
  TS-001
    ↓
  Stabilizacija
    ↓
FAZA 3
  TS-003
  TS-004
    ↓
  Stabilizacija
    ↓
FAZA 4
  TS-005
    ↓
  Stabilizacija
    ↓
FAZA 5
  TS-010
    ↓
  Stabilizacija
    ↓
FAZA 6
  TS-009
  (preostale domenske funkcionalnosti)
    ↓
  Stabilizacija
    ↓
FAZA 7
  TS-011
    ↓
  Stabilizacija
    ↓
FAZA 8
  TS-012
    ↓
  Završna stabilizacija
```

Za svaku logičku cjelinu unutar faze:

```text
analiza → implementacija → test → review → merge → deploy
```

---

# 7. Opis faza

### FAZA 0 — CR-004B

| Stavka | Opis |
|--------|------|
| **Cilj** | Završiti IS-001 Fazu 3 na postojećem modelu (javni prikaz otkazanih) |
| **Moduli** | TS-009 (query / UI); bez migracije |
| **Rizici** | Pogrešan skup `published\|cancelled`; regresija Istaknutih / statistika |
| **Rezultat** | Otkazani javno dostupni po PO-CR4B |
| **Zatim** | Stabilizacija |

### FAZA 1 — Temeljni katalozi

| Stavka | Opis |
|--------|------|
| **Cilj** | Stabilni dijeljeni resursi prije lifecycle-a događaja |
| **Moduli** | TS-006 Lokacije; TS-007 Kategorije i oznake; TS-008 Mediji |
| **Van obuhvata** | **TS-012** (nema audit skeleta u ovoj fazi) |
| **Rizici** | Merge lokacija; ENUM → katalog; storage / MIME |
| **Rezultat** | Katalozi upravljivi |
| **Zatim** | Stabilizacija |

### FAZA 2 — Organizator i ovlašćenja

| Stavka | Opis |
|--------|------|
| **Cilj** | Poslovni entitet Organizator + Moderator + zahtjevi |
| **Moduli** | TS-001 |
| **Rizici** | `kk_admin` vs Urednik; invariant ≥1 aktivnog Moderatora |
| **Rezultat** | Org / Mod u bazi; priprema za TS-010 |
| **Zatim** | Stabilizacija |

### FAZA 3 — Događaj + Održavanje

| Stavka | Opis |
|--------|------|
| **Cilj** | Uskladiti model sa TS-003 / TS-004; uvesti Održavanja 1..N |
| **Moduli** | TS-003, TS-004 |
| **Migracija** | **Velika** (schema / novi domen) — Održavanja 1..N; **jedina** velika migracija domena u tom deploymentu. **PO-EV-01:** bez migracije/backfill/dual-write postojećih `cultural_events` zapisa |
| **Rizici** | Implementacija novog domena; cutover javnog portala; zamjena flat modela; regresija badge-a / filtera / CR-001…004B |
| **Rezultat** | Kanonski Događaj + 1..N održavanja; lifecycle konzistentan; legacy flat model zamijenjen |
| **Zatim** | Stabilizacija |

### FAZA 4 — Manifestacija

| Stavka | Opis |
|--------|------|
| **Status** | **FORMALLY CLOSED / PRODUCTION ACCEPTED** — domen **6B-01** (`26217f6`); editorial **6B-02** (`0e8f7c3`); PO-MF-WF (`d3c7a96`) |
| **Cilj** | Entitet Manifestacija + veze |
| **Moduli** | TS-005 |
| **Migracija** | Velika (nove tabele / FK) — **produkcijski RAN** (`2026_08_11_121000`, `2026_08_11_121100`) |
| **Rizici** | Kardinalnost; arhiva MF ne briše događaje |
| **Rezultat** | Domen + editorial lifecycle **DEPLOYED / PRODUCTION VERIFIED** |
| **Zatim** | **FAZA 7 FORMALLY CLOSED**; naredno = **Faza 8 / TS-012**; Phase B1+B2 = **PRODUCTION VERIFIED / CLOSED**; B3 table DROP ostaje **DEFERRED / non-blocking** |

### FAZA 5 — Urednički portal

| Stavka | Opis |
|--------|------|
| **Status** | **V1 funkcionalno / implementaciono završen** (TS-010 v1.0.6; Cultural: 420 passed / 1740 assertions) |
| **Cilj** | TS-010 umjesto direktnog `kk_admin` CRUD-a |
| **Moduli** | TS-010 (cjeline 010.1–010.7; 010.8 = Business Test Matrix) |
| **Rizici** | Prijedlozi izmjena; zaključavanje; regresija admin tokova |
| **Rezultat** | Moderator / Urednik operativni tokovi V1 — **ostvareno** |
| **Van obuhvata Faze 5** | TS-005 (Manifestacije); TS-009 javni cutover; TS-012 emit/storage (Faza 8); TS-010.7 ostaje obaveza / dependency ka TS-012 |
| **Zatim** | Stabilizacija → **Faza 6A (TS-009 javni portal Događaja)** |

### FAZA 6A — Javni portal Događaja (kanonski cutover)

| Stavka | Opis |
|--------|------|
| **Status** | **CLOSED** — implementation complete; production verification **PASS** (PO-confirmed 2026-08-13) |
| **Cilj** | Prelazak javnog portala Događaja sa `CulturalEvent` na `CulturalEventEntry` + `CulturalOccurrence`; CAT-CUTOVER; očuvanje postojećeg UI-ja |
| **Moduli** | TS-009 (§1.7, §3.4, §7.3, §9–§12, §18); kanonski katalozi TS-006/007/008 po potrebi |
| **PO** | PO-EV-01; PO-TS9-08A–PO-TS9-08J |
| **CURRENT SSOT** | **CANONICAL ONLY**; active public legacy dependency **0**; dual-read **NO**; dual-write **NO** |
| **Rezultat** | Kanonski javni read za Događaje; flag **uklonjen**; bez Manifestacija u 6A scope-u |
| **Ne blokira** | TS-005 / Manifestacije |
| **Van obuhvata (historical plan note)** | Manifestacije (6B); slug/SEO; migracija legacy sadržaja; dual-read/write |
| **cancellation_reason** | PATCH-063: opcioni javni note **dozvoljen** (superseduje PATCH-060 apsolutnu zabranu) |
| **Categories** | Production canonical catalog **14/14 PASS** |
| **Residual Package A** | `cultural-calendar.day` — **CLOSED**; PRODUCTION VERIFIED — EMPTY-DATE (`f35cb2e`) |
| **Phase B1** | Flag/config removal — **IMPLEMENTED / TESTED / PRODUCTION VERIFIED / CLOSED** |
| **Phase B2** | Canonical-only public + legacy CRUD runtime removal — **IMPLEMENTED / TESTED / PRODUCTION VERIFIED / CLOSED** |
| **Phase B3** | `cultural_events` table DROP — **DEFERRED** — non-runtime / **non-blocking for 6A** |
| **Implementation remaining** | **NONE** |
| **Zatim** | **FAZA 7 FORMALLY CLOSED** → **Faza 8 / TS-012** |

### FAZA 6B — Manifestacije (javni portal)

| Stavka | Opis |
|--------|------|
| **Status** | **FORMALLY CLOSED / PRODUCTION ACCEPTED** WITH LIMITED CONTENT-SMOKE COVERAGE — javni portal + Tip sadržaja/Pretraga: **6B-03 / 6B-03A / 6B-04**; editorial MF: **6B-02**; domen: **6B-01**; lifecycle corrective: PO-MF-WF (`d3c7a96`) |
| **Cilj** | Javni portal Manifestacija (TS-009 §6 / PO-TS9-07A–07E) + Tip sadržaja / MF `q` / PO-6B-10 na Pretrazi |
| **Moduli** | TS-005; TS-009 §3.3–§3.4.1 / §6 |
| **Dokaz** | `7875e99` (portal); `0c99241` (search/tip); editorial `0e8f7c3`; domain `26217f6`; lifecycle `d3c7a96` |
| **Test gate** | Functional 244/992; closeout 88 passed / 639 assertions / 0 failed / 0 errors |
| **Rezultat** | Lista / Detalji / program / navigacija / Tip sadržaja — **DEPLOYED**; editorial + moderator osnovni lifecycle + kk_admin nav **PRODUCTION VERIFIED** |
| **Production** | **DEPLOYED** — migracije RAN; tabela postoji; 0 MF redova; cleanup N/A; **PHASE 6B FORMALLY CLOSED** |
| **Limited content-smoke** | **NON-BLOCKING PRODUCTION SMOKE DEBT** (nije defect): public detail/program/Event→MF/search-with-hit; moderator resubmit; organizer-scope extra smoke. PO ne zahtijeva vještačke produkcijske MF. |
| **Zatim** | **FAZA 7 FORMALLY CLOSED** → **Faza 8 / TS-012** |

### FAZA 6 — (istorijski naziv)

> Raniji jedinstveni naziv „Faza 6 — Javni portal“ **supersedovan** je podjelom na **6A** i **6B** (v1.0.5). Referenca u starijim zapisima na „Fazu 6“ tumači se kao 6A+6B osim ako kontekst kaže drugačije.

### FAZA 7 — Newsletter

| Stavka | Opis |
|--------|------|
| **Status** | **FORMALLY CLOSED** — NL-01…NL-06 **IMPLEMENTED / TESTED / COMMITTED / PUSHED**; repo-level stabilization **PASS** |
| **Cilj (ispunjen)** | Zamjena testnog sedmičnog digest-a kanonskim modelom TS-011 v1.0.3 |
| **Moduli** | TS-011 |
| **Paketi** | NL-01 pretplata; NL-02 `/newsletter` settings; NL-03 eligibility/`first_published_at`; NL-04 regular delivery; NL-05 priority; NL-06 legacy weekly disabled + ops/routing docs |
| **Migracija** | Canonical schema deployed (**PO-CONFIRMED** Ran: `120000`, `140000`, `160000`, `180000`); **PO-NL-22:** **bez** backfill-a testnih pretplatnika |
| **Scheduler (kanon / PO-CONFIRMED)** | `cultural-calendar:send-newsletter` `0 */6 * * *`; `cultural-calendar:send-newsletter-priority` `*/5 * * * *`; legacy weekly **nije** production invoker; **ne** `schedule:run` ako Plesk koristi direktne Artisan invokere |
| **Production** | **PO-CONFIRMED:** Environment production; Debug OFF; Timezone `Europe/Belgrade`; Mail smtp; `/newsletter` settings UI. Live Git HEAD = **UNOBSERVED** iz Cursora. Ručni real mail = NO |
| **KEEP V1** | Organizer listing URL u mailu; crash-after-SMTP window; nema queue/outbox; fizički legacy subscriber/weekly artefakti |
| **TS-012** | **Ne** implementirano u Fazi 7 (emit/storage = Faza 8) |
| **Zatim** | **Faza 8 / TS-012** (nakon ovog closeout-a) |

### FAZA 8 — Evidencija aktivnosti (TS-012)

| Stavka | Opis |
|--------|------|
| **Cilj** | Centralni prijem, trajno skladište, Admin pristup; pun V1 katalog emitera |
| **Moduli** | TS-012 — **integracija** sa već stabilnim emiterima (TS-001, 003, 004, 005, 010, 011) |
| **Preduslov** | **FAZA 7 FORMALLY CLOSED** + repo stabilization (NL-01…NL-06); kanonski emiteri TS-001, 003, 004, 005, 010, 011 postoje (emit u TS-012 još nije) |
| **Rizici** | Rupe u katalogu; lom nepromjenjivosti |
| **Rezultat** | FT-003 V1 zatvoren (bez retention / izvoza van BR-188) |
| **Zatim** | **Završna stabilizacija** |

---

# 8. Stabilizacija (obavezni kontrolni punkt)

Primjenjuje se nakon Faza 0–7 i kao **Završna stabilizacija** nakon Faze 8.

| Stavka | Obavezno |
|--------|----------|
| Feature testovi | Da |
| Regresioni testovi | Da |
| Code review | Da |
| Staging validacija | Da |
| Smoke test produkcije | Da |
| Posmatranje stabilnosti prije naredne velike faze | Da |
| Broj dana | Operativna odluka (nije fiksan) |

Naredna velika faza **ne počinje** dok stabilizacija nije potvrđena.

---

# 9. Testiranje po fazama (sažetak)

| Faza | Obavezno | Očekivani testovi | Tipične regresije |
|------|----------|-------------------|-------------------|
| 0 | Dostupnost `cancelled`; Istaknuti; Arhiva; Detalji | CR-004B Feature; regresija CR-001…004A | Badge, filteri, statistike |
| 1 | CRUD kataloga; deaktivacija; merge; MIME | Feature TS-006/007/008 | Javni filter kategorije/lokacije |
| 2 | Zahtjevi Org/Mod; kontekst | Feature TS-001 | Role middleware |
| 3 | Migracija dry-run; ≥1 održavanje; Odgođen; Otkazan terminalan | Domain Feature + migracioni testovi | `publicStatus`, liste, admin |
| 4 | Veze MF↔DG; program | Feature TS-005 | Događaji bez MF |
| 5 | Matrica TS-010.8; gate-ovi | Feature po ulogama | Stari admin put |
| 6A | Kanonski cutover Događaja; kartica multi-OCC; sort; CAT; flag | Public query + CulturalCalendar* | CR-001…004B |
| 6B | MF portal + Tip sadržaja | **FORMALLY CLOSED / PRODUCTION ACCEPTED** (limited content-smoke) | 6A stabilan |
| 7 | Okidači; objedinjavanje; odjava | **FORMALLY CLOSED** — NL-01…NL-06 Feature | Mail / cron; legacy weekly disabled |
| 8 | Katalog aktivnosti; nepromjenjivost; Admin | Audit Feature | Emiteri ne smiju mijenjati prava |

---

# 10. Produkcija — uvođenje

1. Feature branch po logičkoj cjelini; staging prije produkcije.
2. UI-only / additive faze (0, dijelovi 1–2, 5–6 bez velike migracije): deploy bez maintenance window gdje je moguće.
3. Velike migracije (Faze 3, 4, 7 — i eventualno Faza 8 skladište): **jedna po deploymentu**; backup; rollback plan; staging dry-run; produkcioni smoke.
4. Feature flag: privremeno za Fazu 6A (`legacy` XOR `canonical`); za MF navigaciju (6B), novi urednički portal i novi newsletter dok se ne potvrdi stabilnost.
5. Stari `kk_admin` tok gasiti tek nakon što TS-010 pokrije potrebne tokove.

---

# 11. Najveći tehnički rizik

Faza 6A (TS-009): javni cutover na `CulturalEventEntry` + `CulturalOccurrence` — **bez** migracije/backfill-a postojećih testnih zapisa (**PO-EV-01**); bez dual-read/dual-write. Regresioni rizik: badge, filteri, CR-001…004B, lifecycle otkazanih, kartica multi-Održavanje. TS-005 **ne blokira** 6A.

---

# 12. Prvi implementacioni korak

**CR-004B** (Faza 0), zatim Stabilizacija, zatim Faza 1 (TS-006 / TS-007 / TS-008).

---

# 13. PATCH-001 — izmjene u odnosu na v1.0.0

| # | Izmjena |
|---|---------|
| 1 | Uklonjena preporuka „Audit skelet u Fazi 1“; TS-012 isključivo Faza 8 |
| 2 | Dodata obavezna Stabilizacija nakon svake velike faze + Završna stabilizacija |
| 3 | Dodat princip: jedna velika migracija domena po deploymentu (+ backup / rollback / dry-run / smoke) |
| 4 | Dodata implementaciona disciplina: jedna logička cjelina iz TS po zadatku; lanac analiza→…→deploy |
| 5 | Konačni redoslijed usklađen sa usvojenim PATCH-001 redoslijedom (Faza 0–8) |

---

# Kraj dokumenta
