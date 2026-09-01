# Digital Kotor
# Feature Registry — Digital Kotor platforma

**Oznaka dokumenta:** DK-FR-001
**Naziv:** Feature Registry — Digital Kotor platforma
**Vlasništvo:** platformski sloj Digital Kotora
**Status dokumenta:** AKTIVAN
**Verzija:** 1.1.3
**Datum:** 2026-09-02

---

# 1. Svrha

Ovaj dokument je **tanak** Feature Registry platformske dokumentacije Digital Kotora.

Nije Feature Registry Kalendara kulture (`KK-FR-001`) i nije katalog e-Plaćanja.

Registruje samo funkcionalnosti čiji je ownership **PLATFORM**. Auth, MEGA, Plesk i ostala operativna dokumentacija nisu stavke ovog registra dok Product Owner ne otvori poseban paket.

---

# 2. Pregled

| Feature ID | Naziv | Ownership | Status | BM | UC | FS | TS |
| ---------- | ----- | --------- | ------ | -- | -- | -- | -- |
| FT-004 | Obavještenja | PLATFORM | Infrastruktura implementirana / aktivna; signed-copy first publication + direct predecessor revoke + metadata correction + source-specific unpublish + republish iste povučene kopije IMPLEMENTED LOCALLY / PO ACCEPTED; uniqueness / leftover HTML / permanent-delete / finalni javni prikaz poslovnog datuma DOCUMENTED / NOT YET IMPLEMENTED; LOCAL PO MANUAL ACCEPTANCE signed-copy toka 2026-09-01; NOT PRODUCTION DEPLOYED; NOT PRODUCTION ACCEPTED; E2E za ostale izvore otvoren (OFD-OB-006); dokumenti U IZRADI | DK-BM-001 | DK-UC-001 | DK-FS-001 | DK-TS-001 |

FT-004 **nije** formalno zatvoren kao cijeli feature.

FT-004 nije KK Newsletter i nije `/notifications` stub.

---

# 3. FT-004 — Obavještenja

**Feature ID:** FT-004 (nije document ID; ne migrira u `DK-FT-*`).

**Ownership:** PLATFORM.

**Kanonski dokumenti:**

* DK-BM-001 — `docs/business-model/Business_Model_Obavjestenja.md`
* DK-UC-001 — `docs/use-cases/Use_Cases_Obavjestenja.md`
* DK-FS-001 — `docs/functional-specifications/Functional_Specification_Obavjestenja.md`
* DK-TS-001 — `docs/technical-specifications/Technical_Specification_Obavjestenja.md` (istorijski document ID: `TS-013`)
* DK-RG-001 — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Digital-Kotor.md`

**Status (razdvojeno):**

* Infrastruktura: implementirana / aktivna (`Notice`, tabela `notices`, servis objave, događaj/listener, javni panel na `/`, ruta `notices.public-content`).
* Source-specific E2E za **zvaničnu Odluku Konkursa** — **IMPLEMENTED:** upload → first publication → `competition_decision_signed_copy` → korekcija sa revoke-om **neposrednog** signed-copy predecessor-a. LOCAL PO MANUAL ACCEPTANCE 2026-09-01 na `http://127.0.0.1:8000`. **NOT PRODUCTION DEPLOYED.** Plesk/production nijesu bili predmet tog testa. Detalj: `DK-TS-001` §14.10.
* Source-specific KN lifecycle — **IMPLEMENTED LOCALLY / PO ACCEPTED** (CURRENT RUNTIME; **IMPLEMENTED ≠ PRODUCTION DEPLOYED**): poslovni naziv od KN, poslovni datum prikaza (čuvanje, ne finalni javni rendering), metadata correction, source-specific unpublish, republish iste povučene kopije. Binding: `KN-BM-003` v1.0.6 §15.4 / `KN-FS-003` v0.1.21 §16.8–§16.13; DK-UC-001 v0.1.1; DK-FS-001 PATCH-FS-OB-003; DK-TS-001 v0.1.5. **Nije** PRODUCTION ACCEPTED.
* Source-specific KN lifecycle — **DOCUMENTED / NOT YET IMPLEMENTED:** puna one-current-publication semantika / leftover HTML cleanup, permanent-delete channel revoke, nova kopija nakon permanent delete, finalni javni prikaz poslovnog datuma (`KN-FS-003` §16.14–§16.16 i related uniqueness). **Nije** production accepted.
* E2E integracija za **ostale** izvore: otvorena / blokirana — **OFD-OB-006** ostaje generički otvoren.
* Dokumentacija: **U IZRADI**. OFD-OB-001 do OFD-OB-010 ostaju **generički** otvoreni. Nijedna OFD nije RESOLVED / CLOSED / IMPLEMENTED generički.
* **Source-specific target binding:** `KN-BM-003` §15.4 i `KN-FS-003` v0.1.21 (§15.6, §15.7.1, §15.7.5, §16.6, §16.8–§16.16, §18.7.4, §18.9) određuju ponašanje javnog kanala za **zvaničnu Odluku Konkursa**. To **nije** zatvaranje OFD-OB-007 za sve izvore.
* **CURRENT IMPLEMENTED** javni objekat nove zvanične Odluke Konkursa je `competition_decision_signed_copy`. `competition_decision_html` ostaje LEGACY path i **nije** target nove objave.

Cijeli feature **nije** CLOSED / COMPLETE / PRODUCTION ACCEPTED.

**Terminološka granica:**

* FT-004 Obavještenja = javni platformski panel zvaničnog sadržaja.
* `/notifications` / `NotificationController` stub ≠ FT-004 Obavještenja.
* Newsletter „prioritetna obavještenja“ Kalendara kulture ≠ FT-004.

`KK-FR-001` ne sadrži detaljni kanonski opis FT-004; tamo je samo pointer na ovaj dokument.

---

# 4. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-17 | Uspostavljen DK-FR-001. Registrovan FT-004 kao PLATFORM. Pointeri na DK-BM-001 / DK-UC-001 / DK-FS-001 / DK-TS-001. Status razdvojen: infrastruktura implementirana; E2E otvoren; dokumenti U IZRADI. Bez izmjene aplikacionog koda. |
| 1.1.0 | 2026-08-29 | FT-004: evidentiran source-specific target binding `KN-FS-003` v0.1.16 za zvaničnu Odluku Konkursa. OFD-OB-006 i OFD-OB-007 ostaju generički otvoreni. CURRENT RUNTIME i dalje koristi `competition_decision_html`; E2E okidač iz Konkursa nije implementiran. Feature nije zatvoren. Bez izmjene aplikacionog koda. |
| 1.1.1 | 2026-09-01 | FT-004 status-only: source-specific KN tok zvanične Odluke IMPLEMENTED; LOCAL PO MANUAL ACCEPTANCE 2026-09-01; NOT PRODUCTION DEPLOYED. Stari CURRENT RUNTIME gap (`competition_decision_html` kao jedini javni objekat) uklonjen. OFD-OB-006 i OFD-OB-007 ostaju generički otvoreni. Feature nije zatvoren. Bez izmjene aplikacionog koda. |
| 1.1.2 | 2026-09-01 | FT-004: razdvojeno IMPLEMENTED (signed-copy + direct predecessor revoke) od DOCUMENTED / NOT YET IMPLEMENTED (KN lifecycle `KN-BM-003` §15.4 / `KN-FS-003` v0.1.21). Binding ažuriran na DK-UC-001 v0.1.1, DK-FS-001 PATCH-FS-OB-003, DK-TS-001 v0.1.3. OFD-OB-006 i OFD-OB-007 ostaju generički otvoreni. Feature nije zatvoren. Bez izmjene aplikacionog koda. |
| 1.1.3 | 2026-09-02 | FT-004 status-only: metadata correction, source-specific unpublish i republish iste povučene kopije IMPLEMENTED LOCALLY / PO ACCEPTED. Binding ažuriran na DK-TS-001 v0.1.5. Uniqueness / leftover HTML / permanent-delete / finalni javni prikaz poslovnog datuma ostaju DOCUMENTED / NOT YET IMPLEMENTED. NOT PRODUCTION DEPLOYED. NOT PRODUCTION ACCEPTED. Feature nije zatvoren. Bez izmjene aplikacionog koda. |

---

**Kraj dokumenta DK-FR-001 v1.1.3**
