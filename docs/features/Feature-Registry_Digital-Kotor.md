# Digital Kotor
# Feature Registry — Digital Kotor platforma

**Oznaka dokumenta:** DK-FR-001
**Naziv:** Feature Registry — Digital Kotor platforma
**Vlasništvo:** platformski sloj Digital Kotora
**Status dokumenta:** AKTIVAN
**Verzija:** 1.0.0
**Datum:** 2026-08-17

---

# 1. Svrha

Ovaj dokument je **tanak** Feature Registry platformske dokumentacije Digital Kotora.

Nije Feature Registry Kalendara kulture (`KK-FR-001`) i nije katalog e-Plaćanja.

Registruje samo funkcionalnosti čiji je ownership **PLATFORM**. Auth, MEGA, Plesk i ostala operativna dokumentacija nisu stavke ovog registra dok Product Owner ne otvori poseban paket.

---

# 2. Pregled

| Feature ID | Naziv | Ownership | Status | BM | UC | FS | TS |
| ---------- | ----- | --------- | ------ | -- | -- | -- | -- |
| FT-004 | Obavještenja | PLATFORM | Infrastruktura implementirana / aktivna; E2E integracija iz izvornog modula otvorena/blokirana (OFD-OB-006); dokumenti U IZRADI | DK-BM-001 | DK-UC-001 | DK-FS-001 | DK-TS-001 |

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
* E2E integracija iz izvornog procesa (konkurs): otvorena / blokirana — **OFD-OB-006**.
* Dokumentacija: **U IZRADI**. OFD-OB-001 do OFD-OB-010 ostaju otvoreni.

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

---

**Kraj dokumenta DK-FR-001 v1.0.0**
