# Digital Kotor
# Registar skraćenica i oznaka platformske dokumentacije Digital Kotora

**Oznaka dokumenta:** DK-RG-001
**Naziv:** Registar skraćenica i oznaka platformske dokumentacije Digital Kotora
**Vlasništvo:** platformski sloj Digital Kotora
**Status dokumenta:** AKTIVAN
**Verzija:** 1.0.0
**Datum:** 2026-08-17

---

# 1. Identitet

DK-RG-001 je referentni i živi dokument. Predstavlja centralni registar skraćenica i dokumentacionih oznaka **zajedničke/platformske dokumentacije Digital Kotora**.

Nije poslovni pojmovnik. Ne definiše poslovna pravila ni tehnička rješenja. Ne zamjenjuje DK-BM-001, DK-UC-001, DK-FS-001, DK-TS-001 ili DK-FR-001.

Nije registar Kalendara kulture (`KK-RG-001`) i nije registar e-Plaćanja (`EP-RG-001`).

Auth, korisnici, uloge, MEGA, Plesk, deployment, project operations, handoff, architecture overview, shared landing i module access ostaju **DK REFERENCE / OPERATIONS** dok Product Owner ne otvori poseban dokumentacioni paket. Ovaj registar im **ne** dodjeljuje `DK-BM` / `DK-FS` / `DK-TS` ID-eve.

`docs/METHODOLOGY.md` ostaje globalna projektna metodologija i **nema** `DK-*` document ID.

---

# 2. Namespace pravilo

`DK-*` koristi se za zajedničku/platformsku dokumentaciju Digital Kotora.

`KK-*` pripada Kalendaru kulture.

`EP-*` pripada e-Plaćanju.

Numeracija je lokalna po namespace-u. Lista namespace prefiksa **nije** zatvorena: novi poslovni moduli mogu uvesti sopstveni prefiks.

U ovom paketu **nisu** uvedeni: `DK-IS-*`, `DK-IR-*`, `DK-CR-*`, `DK-FT-*`, `DK-PATCH-*`.

---

# 3. Dokumentacioni prefiksi

| Prefiks | Puni naziv | Značenje |
|---------|------------|----------|
| **DK-BM** | Poslovni model platformske dokumentacije | Dokumentacioni prefiks BM dokumenata `DK-*`. |
| **DK-UC** | Use Case specifikacija platformske dokumentacije | Dokumentacioni prefiks UC dokumenata `DK-*`. |
| **DK-FS** | Funkcionalna specifikacija platformske dokumentacije | Dokumentacioni prefiks FS dokumenata `DK-*`. |
| **DK-TS** | Tehnička specifikacija platformske dokumentacije | Dokumentacioni prefiks TS dokumenata `DK-*`. |
| **DK-RG** | Registar skraćenica i oznaka platformske dokumentacije | Ovaj dokument. |
| **DK-FR** | Feature Registry platformske dokumentacije | Dokumentacioni prefiks Feature Registry dokumenata `DK-*`. |

---

# 4. Kanonski dokumenti (aktuelno)

| Oznaka | Dokument | Path | Status |
|--------|----------|------|--------|
| **DK-BM-001** | Poslovni model Obavještenja (platformska funkcionalnost Digital Kotora) | `docs/business-model/Business_Model_Obavjestenja.md` | U IZRADI |
| **DK-UC-001** | Use Cases Obavještenja | `docs/use-cases/Use_Cases_Obavjestenja.md` | U IZRADI |
| **DK-FS-001** | Funkcionalna specifikacija Obavještenja | `docs/functional-specifications/Functional_Specification_Obavjestenja.md` | U IZRADI |
| **DK-TS-001** | Tehnička specifikacija Obavještenja | `docs/technical-specifications/Technical_Specification_Obavjestenja.md` | U IZRADI |
| **DK-RG-001** | Registar skraćenica i oznaka platformske dokumentacije Digital Kotora | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Digital-Kotor.md` | AKTIVAN |
| **DK-FR-001** | Feature Registry — Digital Kotor platforma | `docs/features/Feature-Registry_Digital-Kotor.md` | AKTIVAN |

Fajlovi Obavještenja **nisu** preimenovani. Document ID je u zaglavlju, ne u imenu fajla.

---

# 5. Feature ID

| Oznaka | Značenje | Status |
|--------|----------|--------|
| **FT-004** | Feature ID platformske funkcionalnosti Obavještenja; **nije** document ID i **ne** migrira u `DK-FT-*`. | KEEP |

Kanonski opis FT-004 je u `DK-FR-001`. `KK-FR-001` ne sadrži detaljni kanonski opis FT-004.

---

# 6. KEEP poslovne i funkcionalne oznake

Ove oznake **ostaju** poslovne/funkcionalne instance ID-evi. **Ne** migriraju u `DK-*` document ID.

| Prefiks | Značenje | Gdje živi |
|---------|----------|-----------|
| **BM-OB-*** | Poslovna pravila Obavještenja | DK-BM-001 |
| **PO-OB-*** | Product Owner odluke Obavještenja | DK-BM-001 |
| **FR-OB-*** | Funkcionalni zahtjevi Obavještenja | DK-FS-001 |
| **UC-OB-*** | Usvojeni use case-ovi Obavještenja | DK-UC-001 |
| **C-UC-OB-*** | Kandidat use case-ovi Obavještenja (nisu usvojeni FR izvor) | DK-UC-001 |
| **OFD-OB-*** | Open Finding-i Obavještenja | DK-FS-001 / DK-TS-001 |
| **PATCH-FS-OB-*** | PATCH oznake Functional Specification Obavještenja | DK-FS-001 |
| **FS-OB-FLOW-*** | Funkcionalni tokovi Obavještenja | DK-FS-001 |

OFD-OB-001 do OFD-OB-010 ostaju otvoreni prema postojećem stanju. Posebno: **OFD-OB-006** (E2E okidač iz konkursa) **nije** zatvoren.

---

# 7. Legacy document ID

| Oznaka | Status | Značenje |
|--------|--------|----------|
| **TS-013** | ISTORIJSKO | Istorijski document ID tehničke specifikacije Obavještenja. Zamijenjen sa **DK-TS-001**. Ne koristiti `TS-013` kao aktivni kanonski document ID. |

`TS-013` **nije** runtime `source_module` i **nije** testni ugovor. Istorijski changelog redovi koji legitimno navode `TS-013` ostaju kao istorija.

`KK-TS-013` **ne postoji**.

---

# 8. Terminološka granica

FT-004 Obavještenja = javni platformski panel zvaničnog sadržaja.

`/notifications` / `NotificationController` stub ≠ FT-004 Obavještenja.

Newsletter „prioritetna obavještenja“ Kalendara kulture ≠ FT-004.

Runtime entitet FT-004 je `Notice` (tabela `notices`), javni panel na `/`, javna ruta `notices.public-content`. Infrastruktura je implementirana; E2E integracija iz izvornog procesa ostaje otvorena (OFD-OB-006). Dokumenti Obavještenja ostaju **U IZRADI**.

---

# 9. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-17 | Uspostavljen DK-RG-001. Registrovani `DK-BM` / `DK-UC` / `DK-FS` / `DK-TS` / `DK-RG` / `DK-FR` i kanonski dokumenti `*-001`. FT-004 KEEP kao feature ID. `TS-013` → istorijski document ID; aktivni ID `DK-TS-001`. Poslovne oznake BM-OB / PO-OB / FR-OB / UC-OB / C-UC-OB / OFD-OB / PATCH-FS-OB / FS-OB-FLOW KEEP. Bez izmjene aplikacionog koda. |

---

**Kraj dokumenta DK-RG-001 v1.0.0**
