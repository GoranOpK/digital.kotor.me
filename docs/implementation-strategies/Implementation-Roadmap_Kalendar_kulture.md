# Digital Kotor
# Implementation Roadmap
## Kalendar kulture V1

**Oznaka dokumenta:** IR-001  
**Naziv:** Implementation Roadmap — Kalendar kulture V1  
**Feature ID:** FT-001 (+ FT-003 / TS-012)  
**Modul:** Kalendar kulture  
**Status dokumenta:** Active  
**Verzija:** 1.0.2  
**Datum:** 2026-08-07

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|-------|------|
| 1.0.0 | 2026-08-07 | Početni implementacioni roadmap na osnovu usvojene dokumentacije (BM, FS, Feature Registry, IS-001, TS-001 / TS-003–TS-012) i stanja produkcije. |
| 1.0.1 | 2026-08-07 | **PATCH-001 (FINAL):** TS-012 isključivo kao završna integraciona faza (bez audit skeleta u Fazi 1); obavezne stabilizacione faze; princip jedne velike migracije domena po deploymentu; implementaciona disciplina (jedna logička cjelina po zadatku); usklađen konačni redoslijed Faza 0–8. |
| 1.0.2 | 2026-08-07 | Dokumentaciona napomena: Faza 1 (TS-006/007/008) završena u kodu; Faza 2 (TS-001) spremna za Korak 1 nakon PO-ORG-01–04. Bez izmjene redoslijeda faza. |

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

Primjeri velikih migracija:

* Održavanja 1..N (TS-003 / TS-004)
* Manifestacije (TS-005)
* Newsletter model (TS-011)

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
| **TS-010** | Urednički portal | Koristi domen | Da | Da | TS-001, 003–008 | Nakon domena | **Vrlo visoka** |
| **TS-011** | Newsletter | Da | Da + job | Da | TS-001, 003, 004, 009, 010 | Nakon stabilnog lifecycle-a | **Visoka** |
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
| Javni portal | Postoji; CR-001–004A usklađeni | Faza 0: CR-004B; Faza 6: domen |
| `CulturalEvent` flat | Postoji | Refaktor u Fazi 3 (TS-003/004) |
| Admin `kk_admin` | Postoji | Refaktor u Fazi 5 (TS-010) |
| Organizator / Moderator | Nema | Faza 2 (TS-001) |
| Održavanja 1..N | Nema | Faza 3 |
| Manifestacije | Nema | Faza 4 |
| Katalozi lokacija / kategorija / medija | Nema / ENUM | Faza 1 |
| Newsletter (sedmični cron) | Postoji | Refaktor u Fazi 7 (TS-011) |
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
| **Cilj** | Uskladiti model sa TS-003 / TS-004; migrirati flat zapise |
| **Moduli** | TS-003, TS-004 |
| **Migracija** | **Velika** — Održavanja 1..N; **jedina** velika migracija domena u tom deploymentu |
| **Rizici** | Najveći tehnički rizik projekta — migracija datuma; lom javnog portala / badge-a |
| **Rezultat** | 1..N održavanja; lifecycle konzistentan |
| **Zatim** | Stabilizacija |

### FAZA 4 — Manifestacija

| Stavka | Opis |
|--------|------|
| **Cilj** | Entitet Manifestacija + veze |
| **Moduli** | TS-005 |
| **Migracija** | Velika (ako uvodi nove tabele / podatke) — zaseban deployment |
| **Rizici** | Kardinalnost; arhiva MF ne briše događaje |
| **Rezultat** | Domen spreman za portale |
| **Zatim** | Stabilizacija |

### FAZA 5 — Urednički portal

| Stavka | Opis |
|--------|------|
| **Cilj** | TS-010 umjesto direktnog `kk_admin` CRUD-a |
| **Moduli** | TS-010 (cjeline 010.1–010.7 inkrementalno; 010.8 kao test matrica) |
| **Rizici** | Prijedlozi izmjena; zaključavanje; regresija admin tokova |
| **Rezultat** | Moderator / Urednik operativni tokovi V1 |
| **Zatim** | Stabilizacija |

### FAZA 6 — Javni portal (preostale domenske funkcionalnosti)

| Stavka | Opis |
|--------|------|
| **Cilj** | Ostatak TS-009 / IS-001 Faze 5–6 na punom domenu |
| **Moduli** | TS-009 (MF lista/detalj/program, više održavanja, oznake, filter MF) |
| **Rizici** | Široka UI regresija |
| **Rezultat** | Javni portal usklađen sa TS-009 na domenu |
| **Zatim** | Stabilizacija |

### FAZA 7 — Newsletter

| Stavka | Opis |
|--------|------|
| **Cilj** | Zamjena sedmičnog digest-a modelom TS-011 |
| **Moduli** | TS-011 |
| **Migracija** | Velika (pretplata / ledger / pending) — zaseban deployment |
| **Rizici** | Dupla slanja; kontradiktorne poruke |
| **Rezultat** | Event-driven newsletter |
| **Zatim** | Stabilizacija |

### FAZA 8 — Evidencija aktivnosti (TS-012)

| Stavka | Opis |
|--------|------|
| **Cilj** | Centralni prijem, trajno skladište, Admin pristup; pun V1 katalog emitera |
| **Moduli** | TS-012 — **integracija** sa već stabilnim emiterima (TS-001, 003, 004, 005, 010, 011) |
| **Preduslov** | Stabilizacija Faze 7 (i svih prethodnih) |
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
| 6 | MF portal; više termina; oznake | Proširenje CulturalCalendar* | CR-001…004B |
| 7 | Okidači; objedinjavanje; odjava | Newsletter Feature | Mail / cron |
| 8 | Katalog aktivnosti; nepromjenjivost; Admin | Audit Feature | Emiteri ne smiju mijenjati prava |

---

# 10. Produkcija — uvođenje

1. Feature branch po logičkoj cjelini; staging prije produkcije.
2. UI-only / additive faze (0, dijelovi 1–2, 5–6 bez velike migracije): deploy bez maintenance window gdje je moguće.
3. Velike migracije (Faze 3, 4, 7 — i eventualno Faza 8 skladište): **jedna po deploymentu**; backup; rollback plan; staging dry-run; produkcioni smoke.
4. Feature flag za MF navigaciju, novi urednički portal i novi newsletter dok se ne potvrdi stabilnost.
5. Stari `kk_admin` tok gasiti tek nakon što TS-010 pokrije potrebne tokove.

---

# 11. Najveći tehnički rizik

Migracija flat `CulturalEvent` datuma/lokacije → **Održavanja 1..N** (Faza 3 / TS-004), uz očuvanje javnog portala, badge-a i budućeg lifecycle-a otkazanih događaja.

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
