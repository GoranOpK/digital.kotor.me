# Digital Kotor
# Registar skraćenica i oznaka platformske dokumentacije Digital Kotora

**Oznaka dokumenta:** DK-RG-001
**Naziv:** Registar skraćenica i oznaka platformske dokumentacije Digital Kotora
**Vlasništvo:** platformski sloj Digital Kotora
**Status dokumenta:** AKTIVAN
**Verzija:** 1.3.0
**Datum:** 2026-09-04

---

# 1. Identitet

DK-RG-001 je referentni i živi dokument. Predstavlja centralni registar skraćenica i dokumentacionih oznaka **zajedničke/platformske dokumentacije Digital Kotora**.

Nije poslovni pojmovnik. Ne definiše poslovna pravila ni tehnička rješenja. Ne zamjenjuje DK-BM-001, DK-BM-002, DK-UC-001, DK-FS-001, DK-FS-002, DK-TS-001, DK-FR-001 ili DK-DS-001.

Nije registar Kalendara kulture (`KK-RG-001`), nije registar e-Plaćanja (`EP-RG-001`) i nije registar Konkursa (`KN-RG-001`). Nije globalni katalog svih poslovnih oznaka svih modula.

Product Owner je otvorio dokumentacioni paket **registracije i korisničkog identiteta**. Ovaj registar mu dodjeljuje **DK-BM-002** (USVOJENO) i **DK-FS-002** (USVOJENO). Dokumenti `DK-UC-002` i `DK-TS-002` **nisu** uvedeni.

Uloge, biblioteka dokumenata, MEGA, Plesk, deployment, project operations, handoff, architecture overview, shared landing i module access ostaju **DK REFERENCE / OPERATIONS** dok Product Owner ne otvori poseban dokumentacioni paket. Ovaj registar im **ne** dodjeljuje `DK-BM` / `DK-FS` / `DK-TS` ID-eve.

`docs/METHODOLOGY.md` ostaje globalna projektna metodologija i **nema** `DK-*` document ID. Normativni dokumentacioni standard platforme je **DK-DS-001**.

---

# 2. Namespace pravilo

`DK-*` koristi se za zajedničku/platformsku dokumentaciju Digital Kotora.

Usvojeni dokumentacioni namespace-i Digital Kotora:

| Prefiks | Modul / sloj | Status u ovom registru |
|---------|--------------|------------------------|
| **KK** | Kalendar kulture | AKTIVAN (nije sadržaj ovog registra) |
| **EP** | e-Plaćanje | AKTIVAN (nije sadržaj ovog registra) |
| **DK** | Platformski sloj | AKTIVAN (ovaj registar) |
| **KN** | Konkursi | USVOJEN. Kanonski paket: `KN-RG-001` (USVOJENO). `KN-BM-001` / `KN-FS-001` = USVOJENO (kanonski Odluka SSOT); `KN-PRO-001` / `KN-TS-001` = NACRT. Sačuvani profili/framework (`KN-BM-002`, `KN-BM-003`, `KN-BM-004`, `KN-FS-002` planiran, `KN-FS-003`) nijesu trenutni Odluka SSOT — vodi `KN-RG-001`. Nije sadržaj ovog registra. |

Tenderi **nemaju** usvojeni namespace.

Žensko preduzetništvo i omladinski konkurs nijesu zasebni namespace-i; pripadaju cjelini Konkursi (`KN-*`). Poseban `OM-*` **ne postoji**. Kanonske KN oznake vodi `KN-RG-001`.

Numeracija je lokalna po namespace-u i tipu dokumenta. Lista namespace prefiksa **nije** zatvorena: novi poslovni moduli dobijaju prefiks samo Product Owner odlukom.

U platformskom `DK-*` sloju **nisu** uvedeni: `DK-IS-*`, `DK-IR-*`, `DK-CR-*`, `DK-FT-*`, `DK-PATCH-*`, `DK-UC-002`, `DK-TS-002`.

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
| **DK-DS** | Digital Kotor Documentation Standard | Dokumentacioni prefiks platformskog dokumentacionog standarda. |

---

# 4. Kanonski dokumenti (aktuelno)

| Oznaka | Dokument | Path | Status |
|--------|----------|------|--------|
| **DK-BM-001** | Poslovni model Obavještenja (platformska funkcionalnost Digital Kotora) | `docs/business-model/Business_Model_Obavjestenja.md` | U IZRADI |
| **DK-BM-002** | Poslovni model registracije i korisničkog identiteta Platforme Digital Kotor | `docs/business-model/Business_Model_Registracija_korisnickog_identiteta.md` | USVOJENO |
| **DK-UC-001** | Use Cases Obavještenja | `docs/use-cases/Use_Cases_Obavjestenja.md` | U IZRADI |
| **DK-FS-001** | Funkcionalna specifikacija Obavještenja | `docs/functional-specifications/Functional_Specification_Obavjestenja.md` | U IZRADI |
| **DK-FS-002** | Funkcionalna specifikacija registracije i korisničkog identiteta Platforme Digital Kotor | `docs/functional-specifications/Functional_Specification_Registracija_korisnickog_identiteta.md` | USVOJENO |
| **DK-TS-001** | Tehnička specifikacija Obavještenja | `docs/technical-specifications/Technical_Specification_Obavjestenja.md` | U IZRADI |
| **DK-RG-001** | Registar skraćenica i oznaka platformske dokumentacije Digital Kotora | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Digital-Kotor.md` | AKTIVAN |
| **DK-FR-001** | Feature Registry — Digital Kotor platforma | `docs/features/Feature-Registry_Digital-Kotor.md` | AKTIVAN |
| **DK-DS-001** | Digital Kotor Documentation Standard v1 | `docs/reference/Digital-Kotor-Documentation-Standard.md` | USVOJENO |

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
| 1.1.0 | 2026-08-17 | Registrovani `DK-DS` / `DK-DS-001`. Usvojeni namespace-i evidentiirani: KK, EP, DK, KN (Konkursi — rezervisan). Tenderi bez namespace-a. Bez kreiranja KN dokumenata. Bez izmjene FT-004 BM/UC/FS/TS sadržaja. |
| 1.2.0 | 2026-08-18 | Namespace `KN`: kanonski paket otvoren (`KN-RG-001`). DK-RG i dalje nije katalog KN oznaka. Bez izmjene FT-004 BM/UC/FS/TS sadržaja. Bez izmjene aplikacionog koda. |
| 1.2.1 | 2026-09-03 | Administrativno usklađivanje §2 summary: `KN-BM-001` = USVOJENO; `KN-PRO-001` / `KN-FS-001` / `KN-TS-001` ostaju NACRT. DK-RG i dalje nije katalog KN oznaka. Bez izmjene FT-004 BM/UC/FS/TS sadržaja. |
| 1.2.2 | 2026-09-03 | Administrativno usklađivanje §2 summary: `KN-FS-001` = USVOJENO; `KN-PRO-001` / `KN-TS-001` ostaju NACRT. DK-RG i dalje nije katalog KN oznaka. Bez izmjene FT-004 BM/UC/FS/TS sadržaja. |
| 1.3.0 | 2026-09-04 | Kontrolisana remote integracija (Phase 3C): sačuvani remote Document ID-evi `DK-BM-002` i `DK-FS-002` (USVOJENO — registracija/korisnički identitet). §2 summary usklađen sa `KN-RG-001` v1.0.10: kanonski Odluka SSOT `KN-BM-001` / `KN-FS-001`; `KN-PRO-001` / `KN-TS-001` = NACRT; sačuvani profili/framework `KN-BM-002` / `KN-BM-003` / `KN-BM-004` / `KN-FS-002` (planiran) / `KN-FS-003` nijesu trenutni Odluka SSOT. Lokalna istorija `1.0.0`–`1.2.2` zadržana. `DK-UC-002` / `DK-TS-002` nisu uvedeni. DK-RG i dalje nije katalog KN oznaka. Bez izmjene FT-004 BM/UC/FS/TS sadržaja. Bez `DK-PATCH`. |

---

**Kraj dokumenta DK-RG-001 v1.3.0**
