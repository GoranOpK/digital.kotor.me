# Digital Kotor
# Implementation Roadmap
## Kalendar kulture V1

**Oznaka dokumenta:** KK-IR-001  
**Naziv:** Implementation Roadmap — Kalendar kulture V1  
**Feature ID:** FT-001 (+ FT-003 / KK-TS-012)  
**Modul:** Kalendar kulture  
**Status dokumenta:** Active — **Kalendar kulture V1 COMPLETE**; **V1 TECHNICAL VERIFICATION = PASS**; **V1 FULL-SYSTEM CROSS-VALIDATION = PASS**; **FINAL FULL REGRESSION = GREEN**; **BLOCKS V1 CLOSEOUT = NO**. MED = FORMALLY CLOSED. B3 = DEFERRED / NON-BLOCKING / POST-V1. MED-I4B = DEFERRED / NON-BLOCKING PROJECT ASSET WORK. Repository HEAD `4595a14` = COMMITTED / PUSHED; production deploy ovog HEAD-a = **NOT CONFIRMED**.
**Verzija:** 1.0.25
**Datum:** 2026-08-16

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
| 1.0.15 | 2026-08-14 | **F8-01 TS-012 canonical freeze (status):** **FAZA 8 STARTED — canonical freeze.** TS-012 v1.0.2 USVOJEN; FS PATCH-FS-074. Store/emiteri/Admin UI = **NOT STARTED**. Nije IMPLEMENTED/CLOSED. Bez izmjene BM. Bez izmjene implementacije. |
| 1.0.16 | 2026-08-14 | **F8-02 central audit foundation (status):** store/idempotency/immutability/safe facade **IMPLEMENTED (local; awaiting PO accept/commit)**. Emiteri i Admin UI = **NOT STARTED**. TS-012 → v1.0.3 (status). Katalog KEEP. |
| 1.0.17 | 2026-08-14 | **F8-03 canonical emitters (status):** TS12-* emiteri **IMPLEMENTED (local; awaiting PO accept/commit)**. Katalog KEEP. Admin UI = **NOT STARTED**. TS-012 → v1.0.4 (status). |
| 1.0.18 | 2026-08-14 | **F8-03 V1 retry semantics (docs):** best-effort / failure-isolated; **nema** durable replay garancije. TS-012 → v1.0.5. F8-03 emiteri i dalje local. Admin UI = **NOT STARTED**. BM/FS/IS/RG-001 KEEP. |
| 1.0.19 | 2026-08-14 | **F8-03 PO ACCEPT:** canonical emitters **PO ACCEPTED** (local, awaiting commit/push). TS-012 → v1.0.6 (`repeatable()` uniqueness limitation). Admin UI = **NOT STARTED**. BM/FS/IS/RG-001 KEEP. |
| 1.0.20 | 2026-08-15 | **F8-04 Admin UI (status):** F8-01 canonical freeze = complete; F8-02 foundation = complete / production active; F8-03 emitters = complete / production active; F8-04 Admin UI = **implementation complete**, production acceptance pending. TS-012 → v1.0.7. Faza 8 **nije** production closed. BM/FS/IS/RG-001 KEEP. |
| 1.0.21 | 2026-08-15 | **FAZA 8 PRODUCTION CLOSEOUT (status only):** F8-01…F8-04 **IMPLEMENTATION COMPLETE / PRODUCTION ACTIVE / PRODUCTION ACCEPTED / CLOSED**. TS-012 → v1.0.8. Admin UI production smoke PASS. Historical audit rows immutable. Naredno po §6/§8 = **Završna stabilizacija** (nije započeta). BM/FS/IS/RG-001 KEEP. |
| 1.0.22 | 2026-08-15 | **ZAVRŠNA STABILIZACIJA CLOSED / V1 COMPLETE (status only):** Faze 0–8 **CLOSED**. Corrective 01 (`1f9d959`) OCC fixture + audit/invitation privacy. Regression 1045 passed / 0 failed. Runtime = canonical-only. **B3** `cultural_events` physical DROP = **DEFERRED** (nije V1 blocker). Nema Faze 9. TS-011 → v1.0.4; TS-009 → v1.0.20 (status hygiene). BM/FS/IS/RG-001 KEEP. |
| 1.0.23 | 2026-08-15 | **MED-01–MED-28 dokumentaciona kanonizacija (nije implementacija):** istorijski zapis Faze 1 da je TS-008 „završen u kodu“ ostaje kao istorija tadašnjeg TS8 modela; taj poslovni model je **SUPERSEDED**. Kanonski SSOT = MED paket. **Nije** Faza 9. **Nije** MED implementation COMPLETE. |
| 1.0.24 | 2026-08-16 | **MED documentation closeout:** MED-01–MED-28 = **PO ADOPTED / DOCS CANONICALIZED / IMPLEMENTATION COMPLETE / VERIFIED**. Paketi: MED-I1 `6060bee`; MED-I2 `e7c6a07`; MED-I3 `b416c0b`; MED-I4A `3ef974b`; MED-I5 `6a4d50e`. **MED-I4B** = DEFERRED / NON-BLOCKING PROJECT ASSET WORK. **Nije** Faza 9. Istorijski V1 Faza 0–8 closeout KEEP. Obsolete `cultural_media` schema cleanup = DEFERRED / NON-BLOCKING. |
| 1.0.25 | 2026-08-16 | **FINAL V1 DOCUMENTATION CLOSEOUT (status only):** Javni / urednički / Administracija final auditi = **PASS / ACCEPTED FOR V1 CLOSEOUT**. Full-system cross-validation = **PASS** (active conflicts = 0). Final full regression = **GREEN** (PHPUnit 11.5.39; PHP 8.3.21; GD=yes; WebP=yes; 1286 tests / 6224 assertions / 0 failed / 0 errors / 12 skipped Imagick-environment; 0 warnings / 0 deprecations / 0 risky; exit 0). **BLOCKS V1 CLOSEOUT = NO**. MED KEEP CLOSED. B3 audit = **NO ACTION REQUIRED BEFORE V1** (physical DROP/storage = POST-V1; preduslov = production read-only recheck). ADM-C1 `4595a14` = Users superadmin protection **CLOSED** u repou (COMMITTED / PUSHED; production deploy ovog HEAD-a **NOT CONFIRMED**). ADMIN-AUDIT-02 = TEST GAP / NON-BLOCKING; ADMIN-AUDIT-04 = LOW / NON-BLOCKING. Nema Faze 9. BM/FS pravila KEEP. RG-001 → **v1.1.12** (granica: Plaćanje nije aktivni KK registar). |
| — | 2026-08-17 | Administrativna migracija dokumentacionog ID-a na `KK-*` namespace. Poslovni i tehnički sadržaj, status i closeout ostaju nepromijenjeni. |

---

# 1. Identitet i granice

| Stavka | Vrijednost |
|--------|------------|
| Tip | Operativni planski dokument |
| Svrha | Redoslijed, faze, disciplina i rizici implementacije Kalendara kulture V1 |
| Ne zamjenjuje | BM, FS, Feature Registry, TS, KK-IS-001 |

Ovaj dokument:

* ne definiše nova poslovna pravila;
* ne mijenja BM, FS, Feature Registry, Technical Specification ni KK-IS-001;
* ne sadrži SQL, Laravel kod ni PATCH predloge za te dokumente;
* mora ostati sljediv prema usvojenim TS i KK-IS-001.

---

# 2. Referentni dokumenti

| Dokument | Uloga |
|----------|--------|
| Business Model — Kalendar kulture (Stable) | Poslovna pravila |
| Functional Specification — Kalendar kulture (Stable) | Funkcionalni zahtjevi |
| Feature Registry | FT-001 / FT-003; plan TS |
| KK-IS-001 | Implementaciona strategija javnog portala (KK-TS-009) |
| Change Request Register | CR-001…CR-004B |
| KK-TS-001, KK-TS-003–KK-TS-012 | Usvojene tehničke specifikacije |

---

# 3. Principi (PATCH-001)

## 3.1 KK-TS-012 — samo završna integraciona faza

KK-TS-012 (Evidencija aktivnosti) je **centralni prijemnik** audit događaja.

**Ne uvoditi** KK-TS-012 parcijalno (npr. „audit skelet“ u Fazi 1).

Puna vrijednost KK-TS-012 postoji tek kada postoje kanonski emiteri iz:

* KK-TS-001
* KK-TS-003
* KK-TS-004
* KK-TS-005
* KK-TS-010
* KK-TS-011

**Pravilo:** KK-TS-012 implementirati kao **FAZU 8** — završnu integracionu fazu — nakon stabilizacije svih emitera.

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

* Uvođenje novog modela Događaj + Održavanja 1..N (KK-TS-003 / KK-TS-004) — **bez** migracije/backfill-a legacy `cultural_events` sadržaja (**PO-EV-01**)
* Manifestacije (KK-TS-005)
* Newsletter model (KK-TS-011) — **bez** migracije/backfill-a testnih pretplatnika (**PO-NL-22**)

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
| **KK-TS-001** | Organizator / Moderator / zahtjevi | Da | Da | Da | Platformski User/Role; emisija → KK-TS-012 (tek Faza 8) | Djelimično sa katalozima nakon Faze 1 | **Visoka** |
| **KK-TS-003** | Događaj | Da | Da | Da (preko KK-TS-010) | KK-TS-001, KK-TS-004, KK-TS-006–008 | Ne — jezgro sa KK-TS-004 | **Vrlo visoka** |
| **KK-TS-004** | Održavanje | Da | Da | Da (KK-TS-010) | KK-TS-003, KK-TS-006 | Samo u paru sa KK-TS-003 | **Vrlo visoka** |
| **KK-TS-005** | Manifestacija | Da | Da | Da | KK-TS-001, KK-TS-003, KK-TS-004 | Nakon jezgra | **Visoka** |
| **KK-TS-006** | Lokacije | Da | Da | Da | Potrošači KK-TS-003/004; audit emit tek Faza 8 | Da — u Fazi 1 | **Srednja–visoka** |
| **KK-TS-007** | Kategorije i oznake | Da | Da | Da | KK-TS-003; bez migracije test ENUM-a | Da — u Fazi 1 | **Srednja** |
| **KK-TS-008** | Mediji (istorijski) | Da + storage | Da | Da | SUPERSEDED MED-01–MED-28; vidi IR v1.0.24 | Istorijski Faza 1 | **Srednja–visoka** (model zastario) |
| **KK-TS-009** | Javni portal | Po fazi | Proširenje | Da | CR-004B (Faza 0); domen za Fazu 6 | CR-004B rano; domen kasnije | **Srednja** (preostalo) |
| **KK-TS-010** | Urednički portal | Koristi domen | Da | Da | KK-TS-001, 003–008; emit → KK-TS-012 (Faza 8) | Nakon domena; **Faza 5 V1 završena** | **Vrlo visoka** |
| **KK-TS-011** | Newsletter | Da | Da + job | Da | KK-TS-001, 003, 004, 009, 010 | **Faza 7 V1 završena / FORMALLY CLOSED** | **Visoka** (zatvorena) |
| **KK-TS-012** | Evidencija aktivnosti | Da | Da | Min. Admin | Svi emiteri stabilni | **FAZA 8 CLOSED — PRODUCTION ACCEPTED** | **Srednja** |

### Stanje KK-IS-001 / CR (javni portal, postojeći model)

| CR | Status | Obuhvat |
|----|--------|---------|
| CR-001…CR-004A | Implemented | UI, filteri, badge |
| **CR-004B** | **Planned** | Javni prikaz `cancelled` — Faza 0 |

---

# 5. Postojeća produkcija (sažetak)

**CURRENT (2026-08-16):** Kalendar kulture V1 = **COMPLETE**. **V1 FUNCTIONAL IMPLEMENTATION = COMPLETE.** **V1 TECHNICAL VERIFICATION = PASS.** **V1 FULL-SYSTEM CROSS-VALIDATION = PASS.** **FINAL FULL REGRESSION = GREEN.** **BLOCKS V1 CLOSEOUT = NO.** Faze 0–8 + Završna stabilizacija = **CLOSED**. Public/editorial runtime = **canonical-only** (`CulturalEventEntry` / `CulturalOccurrence`). Legacy `cultural_events` = **fizički KEEP / runtime disabled / B3 DROP DEFERRED / POST-V1 / NON-BLOCKING**. MED = **FORMALLY CLOSED**. MED-I4B = **DEFERRED / NON-BLOCKING PROJECT ASSET WORK**. Repository `origin/main` HEAD = `4595a14` (**COMMITTED / PUSHED**). **Production deploy ovog HEAD-a = NOT CONFIRMED** (istorijski PRODUCTION ACCEPTED za Faze 6A/6B/7/8 ostaje VALID HISTORICAL za te ranije closeout-e). Tabela ispod je **istorijski baseline** iz IR v1.0.0 (stanje prije Faza 0–8) — **nije** current runtime mapa.

| Područje | Stanje | Akcija |
|----------|--------|--------|
| Javni portal | Postoji; CR-001–004A usklađeni | Faza 0: CR-004B; **Faza 6A:** kanonski cutover Događaja (**završena**); **Faza 6B:** Manifestacije (**FORMALLY CLOSED** / **PRODUCTION ACCEPTED** WITH LIMITED CONTENT-SMOKE COVERAGE) |
| `CulturalEvent` flat | Postoji (testni/prototipski podaci — **PO-EV-01**) | Faza 3: novi domen KK-TS-003/004; zamjena flat modela **bez** migracije/backfill legacy sadržaja |
| Admin `kk_admin` | Postoji | Refaktor u Fazi 5 (KK-TS-010) |
| Organizator / Moderator | Nema | Faza 2 (KK-TS-001) |
| Održavanja 1..N | Nema | Faza 3 |
| Manifestacije | **PRODUCTION ACCEPTED** (6B-01…6B-04 + PO-MF-WF; deployed) | **FAZA 4 / 6B FORMALLY CLOSED**; migracije RAN; `cultural_manifestations` = 0 redova; cleanup N/A |
| Katalozi lokacija / kategorija / medija | Nema / ENUM | Faza 1 |
| Newsletter (Kalendar kulture) | **FAZA 7 FORMALLY CLOSED** — kanonski `User` pretplata; regular 6h + priority 5 min | Legacy weekly runtime **disabled**; tabela `newsletter_subscribers` fizički KEEP; **bez** backfill-a (**PO-NL-22**) |
| Centralni audit (FT-003) | **FAZA 8 CLOSED — IMPLEMENTATION COMPLETE / PRODUCTION ACTIVE / PRODUCTION ACCEPTED** | Historical audit rows immutable; V1 KEEP (best-effort / no durable replay / no filters-export) |

---

# 6. Konačni redoslijed implementacije

```text
FAZA 0
  CR-004B
    ↓
  Stabilizacija
    ↓
FAZA 1
  KK-TS-006
  KK-TS-007
  KK-TS-008
    ↓
  Stabilizacija
    ↓
FAZA 2
  KK-TS-001
    ↓
  Stabilizacija
    ↓
FAZA 3
  KK-TS-003
  KK-TS-004
    ↓
  Stabilizacija
    ↓
FAZA 4
  KK-TS-005
    ↓
  Stabilizacija
    ↓
FAZA 5
  KK-TS-010
    ↓
  Stabilizacija
    ↓
FAZA 6
  KK-TS-009
  (preostale domenske funkcionalnosti)
    ↓
  Stabilizacija
    ↓
FAZA 7
  KK-TS-011
    ↓
  Stabilizacija
    ↓
FAZA 8
  KK-TS-012
    ↓
  Završna stabilizacija
```

**CURRENT:** Faze 0–8 i Završna stabilizacija = **CLOSED**. Kalendar kulture V1 = **COMPLETE**. Nema Faze 9 u ovom KK-IR-001.

**MED corrective (post-closeout paket, nije Faza 9):** PO paket MED-01–MED-28 je **usvojen, dokumentaciono kanonizovan, implementiran i verifikovan**.

| Paket | Status | Implementacioni dokaz |
|-------|--------|------------------------|
| MED-I1 ingest/validation/storage | COMPLETE / VERIFIED / PUSHED | `6060bee` |
| MED-I2 Event cover workflow | COMPLETE / VERIFIED / PUSHED | `e7c6a07` |
| MED-I3 Manifestation cover workflow | COMPLETE / VERIFIED / PUSHED | `b416c0b` |
| MED-I4A public fallback resolver | COMPLETE / VERIFIED / PUSHED | `3ef974b` |
| MED-I4B final visual assets | **DEFERRED / NON-BLOCKING PROJECT ASSET WORK** | nema implementacionog commita (nije funkcionalni blocker) |
| MED-I5 Media CRUD removal + cleanup | COMPLETE / VERIFIED / PUSHED | `6a4d50e` |

**MED-I4B inventory (vizueli, ne funkcija):** MISSING — Dječiji programi, Konferencije, Sajmovi, zaseban MF placeholder. AMBIGUOUS (bez automatske PO odluke o legacy JPG) — Književni programi, Publikacije, Prezentacije i predavanja, Paneli i tribine. Bezbjedni pad = globalni Event placeholder.

**Schema debt:** obsolete `cultural_media` kolone = DEFERRED / NON-BLOCKING; **nema** migracije u MED closeout-u.

**B3 / legacy (post-V1):** B3 / LEGACY CLEANUP AUDIT = **NO ACTION REQUIRED BEFORE V1**; **BLOCKS V1 CLOSEOUT = NO**. MUST BEFORE V1 = **NONE**. Physical DROP `cultural_events` / `CulturalEvent` shell / `CulturalEvent.slika` / `cultural-events/` storage = **DEFERRED / NON-BLOCKING / POST-V1**. Ako se kasnije radi: **production read-only recheck** je preduslov. Nije V1 blocker.

**Administracija (repo corrective, nije Faza 9):** ADMIN-AUDIT-01 = **CLOSED** (`4595a14` — Users UI ne dodjeljuje/skida/aktivira/deaktivira `superadmin`; provisioning ostaje env-driven). ADMIN-AUDIT-02 = **TEST GAP / NON-BLOCKING**. ADMIN-AUDIT-04 = **LOW / NON-BLOCKING** (prazan leftover `admin/media` dir nakon MED-I5). Ove oznake su radni audit ID-evi, **nisu** KK-RG-001 skraćenice.

Ovo **nije** nova numerisana faza.

Za svaku logičku cjelinu unutar faze:

```text
analiza → implementacija → test → review → merge → deploy
```

---

# 7. Opis faza

### FAZA 0 — CR-004B

| Stavka | Opis |
|--------|------|
| **Cilj** | Završiti KK-IS-001 Fazu 3 na postojećem modelu (javni prikaz otkazanih) |
| **Moduli** | KK-TS-009 (query / UI); bez migracije |
| **Rizici** | Pogrešan skup `published\|cancelled`; regresija Istaknutih / statistika |
| **Rezultat** | Otkazani javno dostupni po PO-CR4B |
| **Zatim** | Stabilizacija |

### FAZA 1 — Temeljni katalozi

| Stavka | Opis |
|--------|------|
| **Cilj** | Stabilni dijeljeni resursi prije lifecycle-a događaja |
| **Moduli** | KK-TS-006 Lokacije; KK-TS-007 Kategorije i oznake; KK-TS-008 Mediji (istorijski TS8 model — kasnije SUPERSEDED MED paketom; vidi IR v1.0.23) |
| **Van obuhvata** | **KK-TS-012** (nema audit skeleta u ovoj fazi) |
| **Rizici** | Merge lokacija; ENUM → katalog; storage / MIME |
| **Rezultat** | Katalozi upravljivi |
| **Zatim** | Stabilizacija |

### FAZA 2 — Organizator i ovlašćenja

| Stavka | Opis |
|--------|------|
| **Cilj** | Poslovni entitet Organizator + Moderator + zahtjevi |
| **Moduli** | KK-TS-001 |
| **Rizici** | `kk_admin` vs Urednik; invariant ≥1 aktivnog Moderatora |
| **Rezultat** | Org / Mod u bazi; priprema za KK-TS-010 |
| **Zatim** | Stabilizacija |

### FAZA 3 — Događaj + Održavanje

| Stavka | Opis |
|--------|------|
| **Cilj** | Uskladiti model sa KK-TS-003 / KK-TS-004; uvesti Održavanja 1..N |
| **Moduli** | KK-TS-003, KK-TS-004 |
| **Migracija** | **Velika** (schema / novi domen) — Održavanja 1..N; **jedina** velika migracija domena u tom deploymentu. **PO-EV-01:** bez migracije/backfill/dual-write postojećih `cultural_events` zapisa |
| **Rizici** | Implementacija novog domena; cutover javnog portala; zamjena flat modela; regresija badge-a / filtera / CR-001…004B |
| **Rezultat** | Kanonski Događaj + 1..N održavanja; lifecycle konzistentan; legacy flat model zamijenjen |
| **Zatim** | Stabilizacija |

### FAZA 4 — Manifestacija

| Stavka | Opis |
|--------|------|
| **Status** | **FORMALLY CLOSED / PRODUCTION ACCEPTED** — domen **6B-01** (`26217f6`); editorial **6B-02** (`0e8f7c3`); PO-MF-WF (`d3c7a96`) |
| **Cilj** | Entitet Manifestacija + veze |
| **Moduli** | KK-TS-005 |
| **Migracija** | Velika (nove tabele / FK) — **produkcijski RAN** (`2026_08_11_121000`, `2026_08_11_121100`) |
| **Rizici** | Kardinalnost; arhiva MF ne briše događaje |
| **Rezultat** | Domen + editorial lifecycle **DEPLOYED / PRODUCTION VERIFIED** |
| **Zatim** | **FAZA 7 FORMALLY CLOSED**; naredno = **Faza 8 / KK-TS-012**; Phase B1+B2 = **PRODUCTION VERIFIED / CLOSED**; B3 table DROP ostaje **DEFERRED / non-blocking** |

### FAZA 5 — Urednički portal

| Stavka | Opis |
|--------|------|
| **Status** | **V1 funkcionalno / implementaciono završen** (KK-TS-010 v1.0.6; Cultural: 420 passed / 1740 assertions) |
| **Cilj** | KK-TS-010 umjesto direktnog `kk_admin` CRUD-a |
| **Moduli** | KK-TS-010 (cjeline 010.1–010.7; 010.8 = Business Test Matrix) |
| **Rizici** | Prijedlozi izmjena; zaključavanje; regresija admin tokova |
| **Rezultat** | Moderator / Urednik operativni tokovi V1 — **ostvareno** |
| **Van obuhvata Faze 5** | KK-TS-005 (Manifestacije); KK-TS-009 javni cutover; KK-TS-012 emit/storage (Faza 8); KK-TS-010.7 ostaje obaveza / dependency ka KK-TS-012 |
| **Zatim** | Stabilizacija → **Faza 6A (KK-TS-009 javni portal Događaja)** |

### FAZA 6A — Javni portal Događaja (kanonski cutover)

| Stavka | Opis |
|--------|------|
| **Status** | **CLOSED** — implementation complete; production verification **PASS** (PO-confirmed 2026-08-13) |
| **Cilj** | Prelazak javnog portala Događaja sa `CulturalEvent` na `CulturalEventEntry` + `CulturalOccurrence`; CAT-CUTOVER; očuvanje postojećeg UI-ja |
| **Moduli** | KK-TS-009 (§1.7, §3.4, §7.3, §9–§12, §18); kanonski katalozi KK-TS-006/007/008 po potrebi |
| **PO** | PO-EV-01; PO-TS9-08A–PO-TS9-08J |
| **CURRENT SSOT** | **CANONICAL ONLY**; active public legacy dependency **0**; dual-read **NO**; dual-write **NO** |
| **Rezultat** | Kanonski javni read za Događaje; flag **uklonjen**; bez Manifestacija u 6A scope-u |
| **Ne blokira** | KK-TS-005 / Manifestacije |
| **Van obuhvata (historical plan note)** | Manifestacije (6B); slug/SEO; migracija legacy sadržaja; dual-read/write |
| **cancellation_reason** | PATCH-063: opcioni javni note **dozvoljen** (superseduje PATCH-060 apsolutnu zabranu) |
| **Categories** | Production canonical catalog **14/14 PASS** |
| **Residual Package A** | `cultural-calendar.day` — **CLOSED**; PRODUCTION VERIFIED — EMPTY-DATE (`f35cb2e`) |
| **Phase B1** | Flag/config removal — **IMPLEMENTED / TESTED / PRODUCTION VERIFIED / CLOSED** |
| **Phase B2** | Canonical-only public + legacy CRUD runtime removal — **IMPLEMENTED / TESTED / PRODUCTION VERIFIED / CLOSED** |
| **Phase B3** | `cultural_events` table DROP — **DEFERRED** — non-runtime / **non-blocking for 6A** |
| **Implementation remaining** | **NONE** |
| **Zatim** | **FAZA 7 FORMALLY CLOSED** → **Faza 8 / KK-TS-012** |

### FAZA 6B — Manifestacije (javni portal)

| Stavka | Opis |
|--------|------|
| **Status** | **FORMALLY CLOSED / PRODUCTION ACCEPTED** WITH LIMITED CONTENT-SMOKE COVERAGE — javni portal + Tip sadržaja/Pretraga: **6B-03 / 6B-03A / 6B-04**; editorial MF: **6B-02**; domen: **6B-01**; lifecycle corrective: PO-MF-WF (`d3c7a96`) |
| **Cilj** | Javni portal Manifestacija (KK-TS-009 §6 / PO-TS9-07A–07E) + Tip sadržaja / MF `q` / PO-6B-10 na Pretrazi |
| **Moduli** | KK-TS-005; KK-TS-009 §3.3–§3.4.1 / §6 |
| **Dokaz** | `7875e99` (portal); `0c99241` (search/tip); editorial `0e8f7c3`; domain `26217f6`; lifecycle `d3c7a96` |
| **Test gate** | Functional 244/992; closeout 88 passed / 639 assertions / 0 failed / 0 errors |
| **Rezultat** | Lista / Detalji / program / navigacija / Tip sadržaja — **DEPLOYED**; editorial + moderator osnovni lifecycle + kk_admin nav **PRODUCTION VERIFIED** |
| **Production** | **DEPLOYED** — migracije RAN; tabela postoji; 0 MF redova; cleanup N/A; **PHASE 6B FORMALLY CLOSED** |
| **Limited content-smoke** | **NON-BLOCKING PRODUCTION SMOKE DEBT** (nije defect): public detail/program/Event→MF/search-with-hit; moderator resubmit; organizer-scope extra smoke. PO ne zahtijeva vještačke produkcijske MF. |
| **Zatim** | **FAZA 7 FORMALLY CLOSED** → **Faza 8 / KK-TS-012** |

### FAZA 6 — (istorijski naziv)

> Raniji jedinstveni naziv „Faza 6 — Javni portal“ **supersedovan** je podjelom na **6A** i **6B** (v1.0.5). Referenca u starijim zapisima na „Fazu 6“ tumači se kao 6A+6B osim ako kontekst kaže drugačije.

### FAZA 7 — Newsletter

| Stavka | Opis |
|--------|------|
| **Status** | **FORMALLY CLOSED** — NL-01…NL-06 **IMPLEMENTED / TESTED / COMMITTED / PUSHED**; repo-level stabilization **PASS** |
| **Cilj (ispunjen)** | Zamjena testnog sedmičnog digest-a kanonskim modelom KK-TS-011 v1.0.3 |
| **Moduli** | KK-TS-011 |
| **Paketi** | NL-01 pretplata; NL-02 `/newsletter` settings; NL-03 eligibility/`first_published_at`; NL-04 regular delivery; NL-05 priority; NL-06 legacy weekly disabled + ops/routing docs |
| **Migracija** | Canonical schema deployed (**PO-CONFIRMED** Ran: `120000`, `140000`, `160000`, `180000`); **PO-NL-22:** **bez** backfill-a testnih pretplatnika |
| **Scheduler (kanon / PO-CONFIRMED)** | `cultural-calendar:send-newsletter` `0 */6 * * *`; `cultural-calendar:send-newsletter-priority` `*/5 * * * *`; legacy weekly **nije** production invoker; **ne** `schedule:run` ako Plesk koristi direktne Artisan invokere |
| **Production** | **PO-CONFIRMED:** Environment production; Debug OFF; Timezone `Europe/Belgrade`; Mail smtp; `/newsletter` settings UI. Live Git HEAD = **UNOBSERVED** iz Cursora. Ručni real mail = NO |
| **KEEP V1** | Organizer listing URL u mailu; crash-after-SMTP window; nema queue/outbox; fizički legacy subscriber/weekly artefakti |
| **KK-TS-012** | **Ne** implementirano u Fazi 7 (emit/storage = Faza 8) |
| **Zatim** | **Faza 8 / KK-TS-012** (nakon ovog closeout-a) |

### FAZA 8 — Evidencija aktivnosti (KK-TS-012)

| Stavka | Opis |
|--------|------|
| **Status** | **CLOSED — IMPLEMENTATION COMPLETE / PRODUCTION ACTIVE / PRODUCTION ACCEPTED.** F8-01 canonical freeze complete. F8-02 store complete / production active / accepted. F8-03 emitters complete / production active / accepted. F8-04 Admin UI complete / production active / accepted (Super Administrator smoke PASS na `/admin/evidencija-aktivnosti`). V1 audit = **best-effort / failure-isolated / no durable replay**. `repeatable()` uniqueness = known V1 limitation. V1 Admin UI = hronološka read-only lista + paginacija; bez filtera/search/export/show. Historical audit redovi **immutable**. |
| **Cilj** | Centralni prijem, trajno skladište, Admin pristup; pun V1 katalog emitera |
| **Moduli** | KK-TS-012 — **integracija** sa već stabilnim emiterima (KK-TS-001, 003, 004, 005, 010, 011) |
| **Preduslov** | **FAZA 7 FORMALLY CLOSED** — **ispunjen** |
| **Katalog** | KK-TS-012 §7 / FS PATCH-FS-074 — **FROZEN** |
| **Rizici** | Lom nepromjenjivosti; propušten kanonski emiter |
| **KEEP V1** | Audit write = best-effort; nema queue/outbox; nema durable replay; `repeatable()` uniqueness limitation; Admin UI bez filtera/search/export/show; historical rows immutable |
| **Rezultat (cilj faze)** | FT-003 V1 zatvoren (bez retention / izvoza van BR-188) — **ostvaren** |
| **Zatim** | **Završna stabilizacija** (KK-IR-001 §6 / §8) — **CLOSED** (vidi dolje) |

### ZAVRŠNA STABILIZACIJA — Kalendar kulture V1

| Stavka | Opis |
|--------|------|
| **Status** | **CLOSED / COMPLETE.** Istorijski: Feature + regresija na `1f9d959` (1045 passed). **Finalna V1 verifikacija (2026-08-16):** full-system cross-validation **PASS**; final full regression **GREEN** — 1286 tests / 6224 assertions / 0 failed / 0 errors / 12 skipped (Imagick/environment; MED-critical skipped = 0); PHPUnit 11.5.39; PHP 8.3.21; GD=yes; WebP=yes; warnings/deprecations/risky = 0; exit 0. Javni / urednički / Administracija final auditi = **PASS / ACCEPTED FOR V1 CLOSEOUT**. Production smoke Faza 6A/6B/7/8 = **PO-CONFIRMED** (istorijski). Production deploy HEAD `4595a14` = **NOT CONFIRMED**. |
| **Cilj** | Kontrolni punkt nakon Faze 8 (§8): testovi, review, smoke, posmatranje. |
| **Rezultat** | **Kalendar kulture V1 = COMPLETE.** **V1 TECHNICAL VERIFICATION = PASS.** **BLOCKS V1 CLOSEOUT = NO.** Runtime closed. Physical B3 DROP = **DEFERRED / POST-V1**. MED = **FORMALLY CLOSED**. MED-I4B = **DEFERRED / NON-BLOCKING**. |
| **KEEP V1** | **Audit:** failure isolation; no durable replay; no outbox/queue; `repeatable()` uniqueness limitation; no filter/search/export/show; historical rows immutable. **Newsletter:** crash-after-SMTP duplicate window; no queue/outbox; organizer listing URL omitted; legacy physical artifacts remain. **Manifestation:** delete OOS. **Public / legacy:** B3 `cultural_events` physical DROP deferred; day view without badge/detail-link (TD-TS9-01). **Administracija:** ADMIN-AUDIT-02 test gap; ADMIN-AUDIT-04 dead Media remnants. |
| **Zatim** | Nema numerisane **Faze 9** u ovom KK-IR-001. Post-V1: B3 physical cleanup (uz production read-only recheck); MED-I4B vizueli; obsolete `cultural_media` schema. |

---

# 8. Stabilizacija (obavezni kontrolni punkt)

Primjenjuje se nakon Faza 0–7 i kao **Završna stabilizacija** nakon Faze 8.

**CURRENT:** Završna stabilizacija nakon Faze 8 = **CLOSED / COMPLETE** (KK-IR-001 v1.0.22). Finalni V1 documentation closeout = **KK-IR-001 v1.0.25**. Nema naredne numerisane velike faze u ovom dokumentu.

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
| 1 | CRUD kataloga; deaktivacija; merge; MIME | Feature KK-TS-006/007/008 | Javni filter kategorije/lokacije |
| 2 | Zahtjevi Org/Mod; kontekst | Feature KK-TS-001 | Role middleware |
| 3 | Migracija dry-run; ≥1 održavanje; Odgođen; Otkazan terminalan | Domain Feature + migracioni testovi | `publicStatus`, liste, admin |
| 4 | Veze MF↔DG; program | Feature KK-TS-005 | Događaji bez MF |
| 5 | Matrica KK-TS-010.8; gate-ovi | Feature po ulogama | Stari admin put |
| 6A | Kanonski cutover Događaja; kartica multi-OCC; sort; CAT; flag | Public query + CulturalCalendar* | CR-001…004B |
| 6B | MF portal + Tip sadržaja | **FORMALLY CLOSED / PRODUCTION ACCEPTED** (limited content-smoke) | 6A stabilan |
| 7 | Okidači; objedinjavanje; odjava | **FORMALLY CLOSED** — NL-01…NL-06 Feature | Mail / cron; legacy weekly disabled |
| 8 | Katalog aktivnosti; nepromjenjivost; Admin | **FORMALLY CLOSED / PRODUCTION ACCEPTED** — Foundation + Emitter + Admin UI Feature | Emiteri ne smiju mijenjati prava |

---

# 10. Produkcija — uvođenje

1. Feature branch po logičkoj cjelini; staging prije produkcije.
2. UI-only / additive faze (0, dijelovi 1–2, 5–6 bez velike migracije): deploy bez maintenance window gdje je moguće.
3. Velike migracije (Faze 3, 4, 7 — i eventualno Faza 8 skladište): **jedna po deploymentu**; backup; rollback plan; staging dry-run; produkcioni smoke.
4. Feature flag: privremeno za Fazu 6A (`legacy` XOR `canonical`); za MF navigaciju (6B), novi urednički portal i novi newsletter dok se ne potvrdi stabilnost.
5. Stari `kk_admin` tok gasiti tek nakon što KK-TS-010 pokrije potrebne tokove.

---

# 11. Najveći tehnički rizik

Faza 6A (KK-TS-009): javni cutover na `CulturalEventEntry` + `CulturalOccurrence` — **bez** migracije/backfill-a postojećih testnih zapisa (**PO-EV-01**); bez dual-read/dual-write. Regresioni rizik: badge, filteri, CR-001…004B, lifecycle otkazanih, kartica multi-Održavanje. KK-TS-005 **ne blokira** 6A.

---

# 12. Prvi implementacioni korak

**CR-004B** (Faza 0), zatim Stabilizacija, zatim Faza 1 (KK-TS-006 / KK-TS-007 / KK-TS-008).

---

# 13. PATCH-001 — izmjene u odnosu na v1.0.0

| # | Izmjena |
|---|---------|
| 1 | Uklonjena preporuka „Audit skelet u Fazi 1“; KK-TS-012 isključivo Faza 8 |
| 2 | Dodata obavezna Stabilizacija nakon svake velike faze + Završna stabilizacija |
| 3 | Dodat princip: jedna velika migracija domena po deploymentu (+ backup / rollback / dry-run / smoke) |
| 4 | Dodata implementaciona disciplina: jedna logička cjelina iz TS po zadatku; lanac analiza→…→deploy |
| 5 | Konačni redoslijed usklađen sa usvojenim PATCH-001 redoslijedom (Faza 0–8) |

---

# Kraj dokumenta
