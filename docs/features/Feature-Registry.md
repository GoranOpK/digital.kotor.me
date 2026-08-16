# Digital Kotor
# Feature Registry

**Status dokumenta:** AKTIVAN
**Verzija:** 0.1

---

# Feature Registry

## Svrha

Feature Registry predstavlja centralni registar svih funkcionalnosti sistema Digital Kotor.

Svaka funkcionalnost dobija jedinstveni identifikator (Feature ID) koji ostaje nepromijenjen tokom cijelog životnog ciklusa projekta.

Feature ID omogućava povezivanje poslovne dokumentacije, funkcionalnih specifikacija, tehničkih specifikacija, Change Request-ova, implementacije i testiranja.

---

# Pravila

Svaka funkcionalnost dobija jedinstveni identifikator u formatu:

* FT-001
* FT-002
* FT-003
* ...

Feature ID se dodjeljuje samo jednom i nikada se ne koristi ponovo.

Feature Registry predstavlja polaznu tačku za sljedivost kroz cijeli projekat.

---

# Pregled funkcionalnosti

| Feature ID | Naziv            | Status  | Napomena                                      |
| ---------- | ---------------- | ------- | --------------------------------------------- |
| FT-001     | Kalendar kulture | Active  | **V1 COMPLETE** (Faze 0–8 + Završna stabilizacija CLOSED). B3 `cultural_events` physical DROP = DEFERRED. |
| FT-002     | Plaćanja         | Planned | Dokumentacija razvijena (BM-002/FS-002 usvojeni BP-01–BP-09; TS-002 djelimično usvojen, dokument u izradi) |
| FT-003     | Evidencija aktivnosti (Kalendar kulture) | Usvojen (TS-012 v1.0.8) — **FAZA 8 CLOSED: IMPLEMENTATION COMPLETE / PRODUCTION ACTIVE / PRODUCTION ACCEPTED** | FS §5.16 + PATCH-FS-074; BM-14; V1 katalog uključuje Manifestacije; van opsega: napredni pregled/filteri, retention, izvoz (BR-188); **nema durable audit replay**; `repeatable()` uniqueness = known V1 limitation |
| FT-004     | Obavještenja     | Active  | V1 infrastruktura verifikovana testovima; javni panel + `competition_decision_html`; E2E emitovanje iz konkursa i dalje OFD-OB-006 |

Dozvoljeni statusi:

* Planned
* Active
* Deprecated
* Removed

---

# Veza sa ostalom dokumentacijom

Svaka funkcionalnost može biti povezana sa:

* Business Model dokumentacijom
* Functional Specification
* jednim ili više Technical Specification dokumenata
* Change Request Register
* Test Case dokumentacijom
* Implementacijom

Primjer:

FT-001
→ BM-001
→ FS-001
→ CR-001
→ TS-001
→ Test Cases
→ Produkcija

---

# Pravila sljedivosti

Svaki novi dokument koji opisuje određenu funkcionalnost treba da sadrži referencu na odgovarajući Feature ID.

Svaki Change Request mora sadržati referencu na Feature ID kojem pripada.

Technical Specification mora biti vezana za odgovarajući Feature ID.

Jedan Feature može biti povezan sa jednim ili više TS dokumenata.

TS dokumenti koriste jedinstvenu globalnu numeraciju (TS-001, TS-002, TS-003...), nezavisno od Feature ID-a.

Na ovaj način svaka funkcionalnost može biti praćena od poslovne ideje do produkcije.

---

# Prvi zapis

## FT-001

Naziv:

Kalendar kulture

Status:

Active

Napomena:

FT-001 je aktivan modul. **Kalendar kulture V1 = COMPLETE** (Faze 0–8 + Završna stabilizacija **CLOSED**; IR-001 v1.0.22). **Faza 6A — Javni portal Događaja** — **CLOSED** (canonical-only SSOT; B1+B2 PRODUCTION VERIFIED / CLOSED; categories **14/14 PASS**; dual-read/write = 0). **6A residual Package A** (`cultural-calendar.day`): **CLOSED** / PRODUCTION VERIFIED — EMPTY-DATE. **Phase B1+B2:** **PRODUCTION VERIFIED / CLOSED**. **B3** (table DROP) = **DEFERRED / NON-RUNTIME / NON-BLOCKING**. **kk_admin UX / navigation consolidation:** `Kontrolna tabla` + unified `Zahtjevi` — **IMPLEMENTED / PRODUCTION ACTIVE**. **PO-ORG/MOD rejected request editor cleanup** i **MOD-UX-01** — **IMPLEMENTED / PRODUCTION ACTIVE** (historical changelog redovi „NOT DEPLOYED“ ostaju tačni za datum unosa). TS-010 → **v1.0.11**. **Faza 6B — Manifestacije:** **FORMALLY CLOSED / PRODUCTION ACCEPTED** (WITH LIMITED CONTENT-SMOKE COVERAGE).

**Newsletter (u okviru FT-001):** **FAZA 7 / TS-011 = FORMALLY CLOSED.** Kanonska pretplata na `User` (nije automatska pri registraciji); jedna pretplata po korisniku; režimi „Svi događaji“ i „Odabrani organizatori“ + „Bez organizatora“; aktivacija bez dodatnog e-mail confirmationa; odjava/reaktivacija nad istim zapisom; preference samo ubuduće; aktivna pretplata ≠ dozvoljena isporuka; nema praznog Newslettera. Regular `cultural-calendar:send-newsletter` (6h) + priority `cultural-calendar:send-newsletter-priority` (5 min). Manifestacija nije kriterijum pretplate u V1. Legacy weekly runtime **disabled**; fizički legacy artefakti KEEP. **Bez** migracije testnih pretplatnika. Settings URI = `/newsletter`. TS-012 emit = **Faza 8 CLOSED**.

**Usvojene poslovne odluke (Događaj — otkazivanje / terminalnost Otkazan):** Dok je Organizator aktivan, Moderator može otkazati objavljeni događaj u aktivnom kontekstu; deaktivacijom Organizatora moderatorski kontekst prestaje i Moderator više ne izvršava poslovne radnje nad njegovim događajima — otkazivanje tada isključivo Urednik. Urednik može otkazati bilo koji objavljeni događaj (BM PATCH-035/036: BM-ORG-12, BM-DG-05, BM-ST-07, BM-MOD-16, BM-UR-11; FS PATCH-FS-037/038: BR-007, BR-049, BR-050, BR-063). **PO-DG-07 / BM PATCH-053 / FS PATCH-FS-053** (superseduje isključivo dio PATCH-035 / N-DG-01 koji je dozvoljavao Otkazan → Objavljen): status **Otkazan** je terminalan za povratak u **Objavljen**; prelaz Otkazan → Objavljen nije dozvoljen; isti kulturni program kasnije = **novi** događaj (novi zapis); **Odgođen** na održavanju = jedini mehanizam promjene termina postojećeg (neotkazanog) događaja (BM-TR-12, BR-131); Otkazan = istorijski zapis / forma **read-only**; jedina dozvoljena izmjena = razlog otkazivanja (napomena urednika) — Urednik (BM-DG-09, BM-DG-10, BM-ST-07, BM-ST-09, BM-UR-11; BR-064). Jedini usvojeni statusni izlaz iz Otkazan ostaje Otkazan → Arhiviran (Sistem; BR-065). **PO-AUTO-01 / BM PATCH-055 / FS PATCH-FS-055:** pri Objavljen → Otkazan, Planirana i Odgođena Održavanja automatski postaju Otkazana u istoj poslovnoj operaciji (BM-DG-11); Završen/Otkazan Održavanja nepromijenjena. **PATCH-063 / PO-U:** razlog otkazivanja Događaja je **opcion** i ako je unesen **može se javno prikazati** (BM-PK-36 / BR-295); **ne** mijenja terminalnost Otkazan, zabranu republish-a, zabranu delete nakon objave niti historical/read-only sadržaj. Relevantno za TS-003 v0.1.10, TS-004 v0.1.9, TS-009 v1.0.9, TS-010 v1.0.7 (**dokumentaciono usvojeno i implementirano**).

**Usvojene poslovne odluke (Događaj — direktna objava / arhiviranje):** **PO-DG-05** (N-DG-05 zatvoren; BM PATCH-037 BM-ST-04; FS PATCH-FS-039 BR-018, BR-028; **precizirano PATCH-063 / PO-U**): Direktna objava Urednika dozvoljena je isključivo za Događaj **bez veze sa registrovanim Organizatorom** (`organizer_id` null / nije `CulturalOrganizer`). „Bez Organizatora“ **ne** znači „bez ikakvog naziva Organizatora“ — Urednik može opciono imati ručno upisan naziv neregistrovanog Organizatora (`organizer_manual_name`); taj naziv **ne** utiče na approval i **ne** blokira direct publish kada je publish gate ispunjen. Događaj **sa registrovanim Organizatorom** ide isključivo Nacrt → Na odobrenju → Objavljen (Moderator; bez direct publish). Otkazan događaj automatski prelazi u Arhiviran nakon završetka svih održavanja, isto kao Objavljen (PO-DG-06 / N-DG-06 zatvoren; BM-DG-04, BM-ST-08; BR-065). **PO-AUTO-02:** Sistemski Planiran → Završen nakon `vrijeme_do` ako postoji, inače nakon kraja kalendarskog dana `datum` (aplikaciona vremenska zona); Odgođen/Otkazan se ne završavaju automatski.

**Usvojene poslovne odluke (Događaj — naknadno povezivanje sa Organizatorom):** **PO-DG-08 / PO-DG-09** (BM PATCH-056 / FS PATCH-FS-056): BR-052 važi isključivo za Objavljen + bez Organizatora; samo Urednik; samo Aktivan Organizator; jednosmjerno bez Organizatora → Aktivan Org; bez uklanjanja/zamjene Organizatora u V1; status ostaje Objavljen. Relevantno za TS-003 v0.1.6 i TS-010 v1.0.3.

**Usvojene poslovne odluke (Događaj — V1 prvi Event review):** **PO-DG-10** (BM PATCH-057 / FS PATCH-FS-057): Na odobrenju = sadržajno zaključan do Odobri / Vrati na doradu; bez Moderator povlačenja Eventa; bez „Počni pregled“; bez direktnog uređivanja Urednika na pending Eventu. Ne mijenja tok Prijedloga izmjene Objavljenog. Relevantno za TS-003 v0.1.7 i TS-010 v1.0.4. TM-WF-03/04/06 i TM-CRUD-08 supersedovani za Event (VAN V1). **PATCH-063 ne zamjenjuje ovaj Moderatorski approval tok.**

**Usvojene poslovne odluke (Događaj / Održavanje / javni portal — PATCH-063 / PO-U-01…19):** **BM PATCH-063 / FS PATCH-FS-063** usvajaju urednički tok kreiranja, pripreme i neposrednog upravljanja Događajem. **Status:** dokumentacija **usvojena** (BM/FS/TS); **implementacija PATCH-063 završena** (produkcija; HEAD `9825fec`). Ne uvodi novi FT ID — proširuje FT-001 / postojeće Događaj–Održavanje–portal feature-e.

| ID (paket) | Suština | BM | FS | TS |
|------------|---------|----|----|-----|
| PO-U / BM-UR-12 | Urednik create bez izbora registrovanog Org | BM-UR-12, BM-DG-08, BM-ORG-04 | BR-287, BR-021, BR-045 | TS-003 v0.1.10; TS-010 v1.0.7 |
| PO-U / BM-UR-13 | Opcion ručni naziv neregistrovanog Org (samo naziv) | BM-UR-13, BM-DG-12 | BR-288 | TS-003 §6.2 (`organizer_manual_name`); TS-009 §7.3.6 |
| PO-U / BM-UR-14 | Sačuvaj i nastavi → U pripremi (tehnički `draft`; nije novi status; Mod = Nacrt) | BM-UR-14, BM-ST-03, BM-ST-11, BM-EP-11 | BR-013, BR-015, BR-016, BR-289 | TS-003 §4.2–§4.3; TS-010 §10.7.0 / DU-03 |
| PO-U / BM-UR-15 | Trajno brisanje samo nikad objavljenog (U pripremi / `draft`) | BM-UR-15 | BR-290 | TS-003 §4.12; TS-010 §10.13 |
| PO-U / PO-DG-05 preciz. | Direct publish bez approval; publish gate; ručni Org ne blokira | BM-ST-04, BM-UR-06 | BR-018, BR-028, BR-291 | TS-003 §4.6; TS-010 |
| PO-U / BM-UR-16 | Direktan published content edit (Urednik, bez registrovanog Org); Mod proposal ostaje | BM-UR-16, BM-ST-11 | BR-025 (supersede partial), BR-292 | TS-003 §4.7/§4.13; TS-010 §10.5 |
| PO-U / BM-TR-19 | Odgođen bez obaveznog novog termina odmah; Prvobitni termin | BM-TR-19, BM-PK-31 | BR-130, BR-282, BR-293 | TS-004 §4.7; TS-009 §7.3.4 |
| PO-U / razlozi | Opcion `postponement_reason`; OCC `cancellation_reason`; Entry `cancellation_reason` opcion + javna napomena | BM-TR-20, BM-DG-10, BM-PK-36 | BR-063/064/069/272/284/294/295 | TS-003; TS-004 §4.5/§4.7/§6.6; TS-009 §7.2.4/§7.3.5 |
| PO-U / PO-DG-07 KEEP | Otkazan terminalan; nema republish; nema delete nakon objave; historical; novi program = novi DG | BM-DG-09/10, BM-ST-07/09 | BR-064 | TS-003 §4.9; TS-009 archive (PATCH-062) |

**Tehnička napomena (PATCH-063 — implementirano):** `cultural_event_entries.organizer_manual_name`; `cultural_occurrences.postponement_reason` + `cancellation_reason`; Entry `cancellation_reason` opcion.

**Moderator regression (PATCH-063 KEEP):** Nacrt → Pošalji na odobrenje → zaključavanje → Odobri/Vrati; published izmjene preko Prijedloga; **nema** direct publish Moderatora (PO-DG-10 / PO-DG-05).

**Implementacioni closeout (TS-010 V1):** Urednički portal V1 je **implementaciono završen** za prethodni obuhvat (TS-010 v1.0.6). Zatvoreni implementacioni markeri: **T10-WF-01** (PO-DG-10), **T10-GEN-01**, **GAP-RESUME-01** / **R-02** / **TM-OCC-17**. Verifikacija tada: `php artisan test --filter=Cultural` — 420 passed / 1740 assertions. **PATCH-063** (TS-010 v1.0.7 i povezani TS) je **dokumentaciono usvojen i implementiran** (produkcija). **PATCH-064** portalna delta je takođe **implementirana, testirana i deployovana** (vidi donji blok). **Ne** uključuje: TS-005 Manifestacije; TS-012 emit/storage (Roadmap Faza 8).

**Usvojene poslovne odluke (Javni portal — PATCH-064 / PO-064 — informativna naslovna vidljivost Odgođenog):** **BM PATCH-064 / FS PATCH-FS-064 / TS-009 v1.0.10.** Ne uvodi novi FT ID — proširuje FT-001 / postojeće naslovne / kartične / Odgođeno portal feature-e (PO-TS9-06D, PO-TS9-08B/08D). **Status:** dokumentacija **usvojena** (BM/FS/TS); **implementacija PATCH-064 završena, testirana i produkcijski potvrđena**.

| ID (paket) | Suština | BM | FS | TS |
|------------|---------|----|----|-----|
| PO-064 / planned mode | Standardna kartica = prvo naredno relevantno **Planirano** OCC; ranking = taj datum | BM-PK-29, BM-PK-37 | BR-280, BR-297 | TS-009 §5.5 / §7.3.2 (`mode=planned`) |
| PO-064 / postponed_info | Bez narednog Planiranog + Odgođeno bez novog termina + prvobitni ≥ today → informativna naslovna kartica („Odgođeno“ / „Prvobitni termin"); prvobitni ≠ važeći termin; Odgođeno ≠ Planirano/upcoming | BM-PK-37, BM-GL-26, BM-PK-31, BM-TR-19 | BR-296, BR-282, BR-295 | TS-009 §5.5 (`mode=postponed_info`); prvobitni = `OCC.datum` |
| PO-064 / multi postponed | Najbliže neisteklo Odgođeno; prelaz nakon isteka; nakon posljednjeg nema naslovne po tom osnovu | BM-PK-37 | BR-296 | TS-009 §5.5.2 |
| PO-064 / shared pool | Planirani + informativno Odgođeni u **jednom** hronološkom bazenu; sort po ranking datumu; **nema** tip-prioriteta za slotove; max **3** | BM-PK-23, BM-PK-37 | BR-264, BR-297 | TS-009 §5.4 / §5.5.5 / §11.3 |
| PO-064 / one Entry one slot | Ako postoji naredno Planirano → samo planned mode; Entry ne ulazi dvaput | BM-PK-29/37 | BR-297 | TS-009 §5.5.2 |
| PO-064 / tie-breaker | Stabilan tehnički `entry.id ASC`; mode **nije** poslovni tie-breaker | (FS ostavio TS-u) | BR-297 | TS-009 §5.5.5 |
| PO-064 / + još N | U `postponed_info` **ne** prikazivati standardni „+ još N" (broji samo Planirana card-relevant OCC) | — | BR-280 (planned) | TS-009 §5.5.6 / §7.3.2 |
| PO-064 / KEEP | Pretraga sort, detalj PATCH-063, calendar counts, selected-day, Otkazano, arhiva, newsletter, lifecycle, Urednik/Moderator — **NO CHANGE** | BM-PK-37 granice | BR-281 / BR-296 KEEP | TS-009 §5.5.1 / §5.5.9 |

**Coverage napomena (status sync):** TS-009 TM-JP-23…38 scenariji su behavioralno pokriveni kroz implementaciju i regresije PATCH-064 / CLOSE-04; nema otvorenog 6A implementacionog paketa za ovu stavku.

**Usvojene poslovne odluke (Održavanje — V1 generator):** **PO-N-TR-02-04** (BM PATCH-058 / FS PATCH-FS-058): generator samo na Nacrtu; dnevno/sedmično/mjesečno algoritmi; broj XOR krajnji datum; max 100; šablon vremena/lokacije; Planiran; potpuni duplikati odbijaju cijelu operaciju; atomičnost; bez preview; bez Proposal/Objavljen generatora. **T10-GEN-01 = ZATVOREN** (implementiran i testiran: `OccurrenceGenerator`, Urednik/Moderator HTTP/UI, `CulturalOccurrenceGeneratorTest`). Relevantno za TS-004 v0.1.8 i TS-010 v1.0.6. PATCH-063 ne mijenja generator.

**Usvojene poslovne odluke (Lokacije):**
- **PO-LOC-01 (korekcija):** Lokacija iz kataloga je samostalan poslovni entitet, a centralni katalog predstavlja opcioni katalog za ponovno korišćenje Lokacija (nije obavezan i nije jedini izvor svih Lokacija). Moderator može odabrati katalošku Lokaciju ili ručno unijeti naziv Lokacije.
- **PO-LOC-02:** identične Lokacije nijesu dozvoljene; sistem prijavljuje moguće duplikate; konačnu odluku donosi Urednik.
- **PO-LOC-03:** Organizator nije operativna uloga; Moderator predlaže u ime Organizatora; Urednik odobrava/odbija/vraća na doradu, uređuje katalog, rješava duplikate, deaktivira i ponovo aktivira; Administrator platforme nema redovnu poslovnu ulogu.
- **PO-LOC-04:** lifecycle Lokacije = Aktivna/Deaktivirana; samo Aktivne za nove zapise; istorija ostaje; bez redovnog fizičkog brisanja.
- **PO-LOC-05 (korekcija):** kataloška referenca je opciona; kada postoji, čuva se stabilnim identifikatorom i podliježe referencijalnom integritetu; merge važi za kataloške Lokacije i atomski preusmjerava postojeće kataloške reference; ručno uneseni tekst Lokacije ne mijenja se automatski merge operacijom.
- **PO-LOC-06:** audit za kreiranje/izmjene/odobrenje/odbijanje/vraćanje/deaktivaciju/aktivaciju/merge sa starom/novom vrijednošću; audit nepromjenjiv i nije rollback.
- **PO-LOC-07:** V1 podržava isključivo fizičke Lokacije; online/hibridne van V1.

**Usvojene poslovne odluke (Kategorije i oznake):**
- **TS7-PO-01:** Kategorije predstavljaju poslovni katalog (ne tehničku ENUM listu); katalog je proširiv.
- **TS7-PO-02:** Oznake ulaze u V1; dodatna klasifikacija; više oznaka po događaju; nisu zamjena za primarnu kategoriju.
- **TS7-PO-03:** Lifecycle Aktivna/Neaktivna; nova = Aktivna; reaktivacija dozvoljena; bez redovnog fizičkog brisanja; deaktivacija ne mijenja istoriju; postojeći događaji zadržavaju reference.
- **TS7-PO-04:** Bez migracije test podataka; bez kompatibilnosti/tranzicije sa starim modelom; novi poslovni katalog; test kategorije nisu referentni poslovni podaci.
- **TS7-PO-05:** Kategorija „Nešto drugo“ ne postoji; Urednik proširuje katalog; oznake nisu zamjena za kategoriju.
- **TS7-PO-06:** Katalogom kategorija i oznaka upravlja isključivo Urednik; Moderator samo koristi pri uređivanju događaja; bez workflow-a predlaganja, dodatnih statusa i ovlašćenja.
- **TS7-PO-07:** Konačni početni V1 katalog kategorija Događaja (14 naziva, usvojeni redoslijed); značenja; kategorija ≠ Manifestacija / ≠ tip Organizatora; odbačene legacy vrijednosti; semantičko mapiranje; tehnički cutover = TS-009. BM PATCH-059 / FS PATCH-FS-059 / TS-007 v0.1.1. **Nije** nova CRUD funkcionalnost (katalog CRUD već implementiran); **nije** implementacija cutover-a.

**Usvojene poslovne odluke (Organizator / Moderator — PO-ORG):**
- **PO-ORG-01:** Katalog polja Organizatora V1 — naziv (obavezno); opis, kontakt e-mail, telefon, web (opciono); status Aktivan/Deaktiviran; sistemski datumi. Van V1: PIB, MB, adresa, GPS, društvene mreže, logo, ostali pravni podaci.
- **PO-ORG-02:** *(istorijski / djelimično superseded)* Moderator grant vezan na postojeći aktivan nalog (`user_id`). **Selection pretpostavka** „biraj samo iz users kataloga / zabrana imena+e-maila pri submit-u“ **superseded** odlukom **PO-ORG-06**. Grant i dalje nastaje tek nakon Editor approval na resolve-ovani `user_id`.
- **PO-ORG-03:** Organizator se kreira tek nakon odobrenja Urednika (atomično sa početnim Moderatorom); podnošenje zahtjeva ne kreira entitet.
- **PO-ORG-04:** Moderator pristupa uredničkom portalu iz aktivnog ovlašćenja; nije nova platformska uloga; `kk_admin` = jedina platformska uloga Urednika.
- **PO-ORG-05:** Napomena Urednika na zahtjevu za kreiranje Organizatora — pri odobrenju opciona; pri odbijanju obavezna (ne-prazna); fail-closed bez napomene; napomena se trajno čuva. BM PATCH-067 / FS PATCH-FS-068 (BR-307) / TS-001 v0.3.1. **KEEP.** PO-ORG-06 proširuje obaveznu reject napomenu i na subsequent ADD (BR-317).
- **PO-ORG-06:** Privacy-safe Moderator invitation (first + subsequent ADD) — ime+e-mail; stanje „Čeka registraciju Moderatora“; auto → Podnesen kad eligible; neutral flash; invitation/outcome/REMOVE-approved emails; supersede PO-ORG-02 selection model. BM PATCH-068 / FS PATCH-FS-069 (BR-308–BR-320) / TS-001 v0.4.1. **Status: ADOPTED / DOCUMENTED / IMPLEMENTED / PRODUCTION VERIFIED.** Packages 1–5 + produkciona schema migracija + produkcioni smoke (PO-confirmed). Discoverable ordinary-user CTA „Zahtjev za Organizatora“ uključen (`814ff96`). Optional durable mail retry / `invitation_sent_at` = OUT OF SCOPE (non-blocking).

**Usvojene implementacione odluke (Događaj / Održavanje — PO-EV):**
- **PO-EV-01:** Postojeći podaci Kalendara kulture (`cultural_events`) su isključivo testni/prototipski; nisu referentni produkcijski sadržaj. Bez migracije/backfill-a/dual-write-a/adaptera radi legacy sadržaja. Novi domen Događaj + Održavanje (TS-003/TS-004) implementira se direktno prema BM/FS/TS. Legacy flat model privremen do cutover-a. **Potvrđeno za Fazu 6A** javnog portala (TS-009 v1.0.6 / IR-001 v1.0.5).

**Usvojene product odluke (Javni portal — Faza 6A / TS-009 v1.0.8):**
- **PO-TS9-08A:** Očuvanje postojećeg izgleda javnog portala; bez redizajna; UI izmjene samo kad neophodne zbog kanonskog modela / usvojenih pravila.
- **PO-TS9-08B:** Kartica = prvo naredno relevantno **Planirano** Održavanje; „+ još N termina“ (Planirana); detalj = sva javno relevantna Održavanja. **PATCH-064:** standardni režim ostaje; informativni `postponed_info` je zaseban naslovni mode (bez „+ još N“).
- **PO-TS9-08C:** Pretraga sortira rastuće po prvom narednom relevantnom Održavanju (sistemsko; bez korisničkog sortiranja). **PATCH-064 KEEP** — naslovni ranking ≠ Pretraga.
- **PO-TS9-08D:** Odgođeno na detalju sa oznakom / Prvobitni termin (PATCH-063); kartica u standardnom režimu prikazuje naredno **Planirano**. **PATCH-064:** kada nema Planiranog, naslovna može koristiti informativni Odgođeno režim (BM-PK-37 / BR-296).
- **PO-TS9-08E:** CAT-CUTOVER — isključivo `CulturalCategory` (14); bez legacy alias mape; preduslov 14 u bazi.
- **PO-TS9-08F:** Faza 6A (Događaji) / 6B (Manifestacije); TS-005 ne blokira 6A.
- **PO-TS9-08G:** V1 — BR-272 standardno obavještenje. **PATCH-063 / BM-PK-36 / BR-295 superseduje** raniju zabranu javnog `cancellation_reason`: ako je opcion razlog unesen, **može** se javno prikazati kao napomena.
- **PO-TS9-08H:** Legacy URL smije 404; bez redirect mape; bez slug-a u 6A.
- **PO-TS9-08I:** Privremeni feature flag `legacy` XOR `canonical`; zatim uklanjanje flag-a i legacy public/CRUD.
  - **PHASE 6A-CLOSE-02 (historical):** legacy admin CRUD surface bio HTTP-disabled (403) uz zadržan flag; supersedovano Phase B1+B2.
  - **6A residual Package A (`cultural-calendar.day`):** **CLOSED** — PRODUCTION VERIFIED — EMPTY-DATE (`f35cb2e`).
  - **Phase B1+B2 (CURRENT):** **IMPLEMENTED / TESTED / PRODUCTION VERIFIED / CLOSED** — flag removed; public portal canonical-only; legacy CRUD routes/controller/views removed; image helpers → `CulturalCalendarDefaultImages`; `CulturalEvent` model class KEEP (non-runtime / B3 shell; table retained). **B3 table DROP = DEFERRED / NON-BLOCKING.** **FAZA 6A = CLOSED.**
- **PO-TS9-08J:** Minimalni public query SSOT — aktivni `base()` odvojen od archive-only query-ja (PO-6A09-01).
- **PO-6A11-01:** Kanonski javni status Događaja (multi-OCC): Otkazan prioritet; agregat U toku → Predstoji → Završen; postponed-only / 0 OCC → bez badge-a; BM-PK-34 / BR-285 / TS-009 §7.1.6.
- **PO-6A09-01:** Aktivni public skup ostaje `published`|`cancelled`; Javna Arhiva = poseban archive-only query; `archived` ne ulazi u `base()`.
- **PO-6A09-02:** Pri arhiviranju sačuvati izvorni javni status (`published`|`cancelled`); radni naziv `archived_from_status`.
- **PO-6A09-03:** Direct URL 200 za archive-public Entry; draft/pending → 404.
- **PO-6A09-04:** Nema badge-a „Arhiviran"; iz cancelled → Otkazan; iz published → Završen.
- **PO-6A09-05:** Arhiva kartica = posljednje istorijsko Održavanje (ne `nextRelevantOccurrence`).
- **PO-6A09-06:** Arhiva sort = posljednje istorijsko Održavanje DESC (ne scheduler/`archived_at`).
- **PO-CR4B-09 (revidiran):** Historijski CR-004B nije implementirao `cancelled → archived`. Lifecycle prelaz **jeste** na snazi (BR-065). Očuvanje Otkazan kroz arhiviranje je **obavezni** ugovor kanonske Javne Arhive (PO-6A09-02/04), ne buduća opciona zavisnost.

**Usvojene poslovne odluke (Mediji):**
**Status:** **ZASTARJELO / SUPERSEDED** (MED-01–MED-28 / 2026-08-15). Nisu aktivni V1 cilj.

- **TS8-01:** Medij je samostalan poslovni entitet i zajednički platformski resurs bez poslovnog vlasnika.
- **TS8-02:** Zatvoreni katalog namjena: Naslovna fotografija događaja; Naslovna fotografija manifestacije; Podrazumijevana fotografija kategorije.
- **TS8-03:** Kardinalnosti 0..1 po entitetu; medij → 1..N entiteta iste namjene; hijerarhija prikaza događaja (direktna → kategorija → tehnički placeholder); fallback nije veza.
- **TS8-04:** Tip Fotografija; JPEG/PNG/WebP; max 5 MB; obavezna serverska validacija sadržaja/MIME/ekstenzije; bez SVG/GIF/BMP/TIFF/HEIC.
- **TS8-05:** Status Aktivan/Neaktivan; reaktivacija; bez soft delete; trajno brisanje samo bez poslovnih veza.
- **TS8-06.1–TS8-06.5:** Creator = audit; upload samo tokom uređivanja DG/MF/kategorije; vidljivost ≠ vlasništvo; Moderator samo veze; Urednik upravlja zapisom i lifecycle-om.
- **TS8-07:** Pretraga (Moderator: naziv/opis u kontekstu; Urednik: katalog + filteri); prikaz kartice; load more / infinite scroll.
- **TS8-08:** Poslovni i tehnički metapodaci; tagovi u modelu, bez V1 UI.
- **TS8-09.1–TS8-09.6:** Prikaz neaktivnog na postojećim vezama; uklanjanje cover-a dozvoljeno uz fallback; dupli upload sa upozorenjem; ponovna provjera ovlašćenja/uslova; bez poslovnog scenarija dva Urednika.

**Usvojene poslovne odluke (Naslovna fotografija — MED; 2026-08-15):**

**Status:** **ADOPTED / DOCS CANONICALIZED / IMPLEMENTATION COMPLETE / VERIFIED.**

- **MED-01–MED-28:** kanonski paket naslovne fotografije Događaja i Manifestacije. SSOT: BM PATCH-075 / PATCH-076, FS PATCH-FS-075 / PATCH-FS-076, TS-003/005/007/009/010; TS-012 KEEP `mf.cover.change`. TS-008 i TS8-01–09 SUPERSEDED.
- **MED-I4B:** finalni vizuelni resursi = **DEFERRED / NON-BLOCKING PROJECT ASSET WORK** (nije funkcionalni blocker). Fallback resolver COMPLETE.

**Usvojene product / IA odluke (Javni portal — TS-009 faza 1):**
- **IA-01:** Evolutivni razvoj javnog portala; bez redizajna; zadržavanje postojeće strukture i tokova uz minimalne neophodne izmjene.
- **PO-TS9-03A:** Stranica „Pregled događaja“ → „Pretraga i pregled“; centralno mjesto za pretragu i pregled.
- **PO-TS9-04A:** Filteri (datum, kategorija, lokacija, manifestacija) sastavni dio „Pretrage i pregleda“; uvijek vidljivi; kombinovanje; „Poništi filtere“; stanje u URL-u.

**Usvojene product odluke (CR-003 — filteri Pretrage i pregleda):**
- **PO-CR3-01…08:** URL `q`/`category`/`location`; pretraga naslov/opis/lokacija; dropdown kategorije (`CATEGORIES`) i lokacije (distinct objavljenih); AND sa jednim datumskim mehanizmom; aktivni filteri (×); „Poništi sve filtere“; GET filter zona; state persistence. Referenca: TS-009 §3.3.

**Usvojene product odluke (CR-004A — javni status badge):**
- **PO-CR4A-01:** Javna stanja Predstoji / U toku / Završen / Otkazan; interni statusi se ne prikazuju; `cancelled` → Otkazan (prioritet); Odgođen nije status Događaja.
- **PO-CR4A-02:** Badge na Početnoj, Pretrazi i pregledu, Arhivi i Detaljima; isti tekst/badge/logika.
- **PO-CR4A-03:** Pravila određivanja (Otkazan; zatim vremenska stanja po `datum_od`/`datum_do`/`vrijeme`/`vrijeme_do`); izračunata stanja, ne statusi baze; vremenska zona aplikacije.
- **PO-CR4A-04:** Kartice — gornji desni ugao fotografije; Detalji — ispod naslova; jedinstven vizuelni izgled. Referenca: TS-009 §7.1.
- **PO-CR4A-05:** Ako se javni status ne može pouzdano odrediti zbog nekonzistentnih podataka, badge se ne prikazuje (bez exceptiona / „Unknown“). Dokumentuje već usvojeno i implementirano ponašanje (`0f73240`).

**Usvojene product odluke (CR-004B — javni prikaz otkazanih događaja):**
- **PO-CR4B-01:** Otkazani događaj ostaje javno dostupan.
- **PO-CR4B-02:** Do planiranog termina prikazuje se na početnoj, kalendaru, događajima dana, narednim događajima, Pretrazi i pregledu, Detaljima i direktnom URL-u.
- **PO-CR4B-03:** Ne prikazuje se među Istaknutim; flag „Istaknut“ se ne mijenja — samo isključenje iz javnog prikaza.
- **PO-CR4B-04:** Nakon isteka planiranog termina otkazani događaj zadržava interni status `cancelled`, prikazuje se u portalnoj Arhivi na osnovu datuma i u javnom prikazu ostaje označen statusom „Otkazan“. Portalna Arhiva ≠ interni `archived`.
- **PO-CR4B-05:** Na Detaljima fiksno sistemsko obavještenje: „Ovaj događaj je otkazan i neće biti održan u planiranom terminu.“ Tekst nije uređiv i nije dio opisa; badge ostaje.
- **PO-CR4B-06:** Bez novih filtera, URL parametara ili search moda; otkazani učestvuju u postojećoj pretrazi.
- **PO-CR4B-07:** Odgođen nije dio CR-004B.
- **PO-CR4B-08:** Prava otkazivanja = postojeća BR-063 / BM-DG-05 (Moderator Organizatora + Urednik); bez novih pravila prava.
- **PO-CR4B-09:** Historijski: CR-004B nije mijenjao BR-065/BM-DG-04 niti otvarao interni `archived` javnosti; budući `cancelled → archived` zahtijevao je očuvanje informacije o otkazivanju. **Revidirano (PO-6A09):** lifecycle `cancelled → archived` je na snazi; očuvanje Otkazan kroz arhiviranje je obavezni ugovor Javne Arhive (vidi PO-6A09-02/04).
- **PO-CR4B-10:** Regresija CR-001…CR-004A (badge, filteri, mjesečni filter, UI baseline). Referenca: TS-009 §7.2.

**Usvojene product / IA odluke (nastavak):**
- **PO-TS9-05A:** Zadržavaju se postojeći prikazi; ne uvode se novi ekrani.
- **PO-TS9-05B:** „Pretraga i pregled“ = samo lista; mjesečni kalendar samo na početnoj.
- **TD-TS9-01:** Ruta `cultural-calendar.day` nije dio referentne IA javnog portala; interna tehnička podrška admin toku. **Package A status:** canonical cutover **PRODUCTION VERIFIED — EMPTY-DATE SCENARIO CONFIRMED** (`f35cb2e`); Phase B1+B2 **CLOSED**; **FAZA 6A = CLOSED**.

**Usvojene product odluke (Javni portal — TS-009 faza 2 — početna stranica):**
- **PO-TS9-06A:** Hero — sastavni dio; postojeći vizuelni identitet; statički; nije uređiv iz admina; bez baze, CTA, promo, rotacije, videa; isključivo identitet modula.
- **PO-TS9-06B:** Istaknuti — postojeće mjesto/raspored; max 3; samo objavljeni i aktuelni; bira Urednik (ne sistem); kartice: foto, datum, vrijeme, lokacija, naslov, kratak opis, link na detalj; neutralno prazno stanje bez admin poruka.
- **PO-TS9-06C:** Statistike — 3 klikabilne kartice (Danas, Ove sedmice, Izabrani mjesec = naziv izabranog mjeseca); klik → Pretraga i pregled sa datumskim filterom; 0 ostaje klikabilno; brojači i `date`/`week`/`month` pregledi uključuju javno dostupne događaje (`published` | `cancelled`) u odgovarajućem vremenskom skupu (CR-004B); bez novih filtera/URL parametara.
- **PO-TS9-06D:** Lista ispod kalendara — bez datuma: „Naredni događaji“ max 3; sa datumom: svi za dan (aktivni OCC filter); dugme „Prikaži sve događaje“ → Pretraga i pregled (sa/bez datumskog filtera); postojeće prazno stanje. **PATCH-064 / PO-064:** „Naredni“ = zajednički hronološki bazen Planiranih + informativno Odgođenih (BR-264/297; TS-009 §5.4–§5.5); max 3 KEEP; selected-day / calendar counts KEEP (postponed nije aktivni dan).

**Usvojene product odluke (Javni portal — TS-009 faza 3 — Manifestacije):**
- **PO-TS9-07A:** Manifestacije = zasebna cjelina portala; stavka navigacije „Manifestacije“; lista + Detalji manifestacije + program; ne kroz kategorije događaja; bez redizajna.
- **PO-TS9-07B:** Lista — javno dostupne MF koje nijesu Arhivirane (Objavljene + Otkazane do isteka perioda per PO-6B-08); sort datum početka → naziv; 12/stranica; kartica (foto, naziv, period, opis, broj objavljenih događaja, „Detalji manifestacije“; Otkazana → oznaka); V1 bez pretrage/filtera; neutralno prazno.
- **PO-TS9-07C:** Detalji manifestacije — foto, naziv, period, organizator, web, opis, program ispod; Manifestacija nema sopstvenu ni agregiranu lokaciju (lokacija samo po programskoj stavci); V1 bez galerija/video/dijeljenja/rezervacija/komentara.
- **PO-TS9-07D:** Program — grupisan po datumima; sort datum → vrijeme → naziv; po Održavanju; završeni ostaju; otkazani uz statusnu oznaku „Otkazano“; „Vrijeme nije definisano“; poruka ako nema programa.
- **PO-TS9-07E:** 1 MF → N događaja; događaj ≤1 MF; događaj može bez MF; dvosmjerna navigacija; događaji ostaju u Pretrazi i pregledu/kalendaru/statistikama/Arhivi događaja; uklanjanje/arhiva MF ne briše događaje.
- **PO-6B-01:** „Pretraga i pregled“ dobija filter **Tip sadržaja**: Sve (default), Događaji (`tip=dogadjaji`), Manifestacije (`tip=manifestacije`); nevalidan `tip` se ignoriše (fail-safe).
- **PO-6B-02:** Manifestacija u V1 nema sopstvenu/agregiranu lokaciju; lokacija se prikazuje isključivo uz konkretnu programsku stavku (Događaj/Održavanje) kada postoji.
- **PO-6B-03:** Arhivirana Manifestacija ne ulazi u aktivnu listu Manifestacija, ali ostaje javno dostupna preko direktnog canonical URL detalja; posebna javna lista/ruta „Arhiva Manifestacija“ nije V1 scope.
- **PO-6B-04:** Semantika filtera po tipu sadržaja: `Sve` i `Manifestacije` imaju samo `q`; event-specifični filteri (`category`, `location`, `date`, `week_start`, `week_end`, `month`) dostupni su samo za `tip=dogadjaji`; non-applicable parametri su fail-safe i ne utiču na rezultat.
- **PO-6B-05:** MF `q` searchable fields: pretraga Manifestacija koristi samo sopstvena polja `naziv` i `opis` (partial, case-insensitive); bez pretrage Organizatora, povezanih Događaja/Održavanja, lokacija, kategorija, Oznaka i drugih izvedenih/agregiranih podataka programa.
- **PO-6B-08:** Otkazana Manifestacija ostaje javno vidljiva do isteka izvedenog perioda (aktivna lista + oznaka „Otkazana" + detalj + program); otkazivanje MF ne mijenja Event/OCC statuse; nakon isteka → Arhivirana (postojeći lifecycle); Arhivirana van aktivne liste, direct detail ostaje. **ADOPTED / DOCUMENTED.**
- **PO-6B-09:** Na javnom detalju Događaja veza ka MF prikazuje se samo ako je MF javno dostupna (Objavljena / Otkazana / Arhivirana); anti-leak za Nacrt / Na odobrenju / Vraćena; bez statusa MF na Event detail; semantika: oznaka + naziv + link. **ADOPTED / DOCUMENTED.**
- **PO-6B-10:** Kada je Tip sadržaja = Sve, Događaji i Manifestacije sortiraju se **zajedno** po vremenskom ključu (Event: prvo naredno relevantno Održavanje; MF: početak izvedenog perioda); NULL last; tie Naziv → tip (tehnički) → ID; bez grupisanja po tipu. Tip=Događaji zadržava 6A ordering; Tip=Manifestacije zadržava MF list ordering. **ADOPTED / DOCUMENTED / IMPLEMENTED / TESTED / COMMITTED / PUSHED / DEPLOYED / PRODUCTION ACCEPTED.**

**PHASE 6B — FORMALLY CLOSED** (production closeout 2026-08-12). **ADOPTED / DOCUMENTED / IMPLEMENTED / TESTED / DEPLOYED / PRODUCTION ACCEPTED** WITH LIMITED CONTENT-SMOKE COVERAGE. Acceptance coverage: automated Feature tests (nema zasebnog TM-MF dokumenta) + PO-accepted production smoke (editorial lifecycle, moderator osnovni lifecycle, kk_admin nav split; 6B migracije RAN).

| Paket | Status | Commit |
|-------|--------|--------|
| **6B-01** Core Domain | IMPLEMENTED / TESTED / COMMITTED / PUSHED / DEPLOYED | `26217f6` — `feat(calendar): add manifestation core domain` |
| **6B-02** Editorial Flow | IMPLEMENTED / TESTED / COMMITTED / PUSHED / DEPLOYED | `0e8f7c3` — `feat(calendar): add manifestation editorial flow` |
| **6B-03** Public Portal | IMPLEMENTED / TESTED / COMMITTED / PUSHED / DEPLOYED | `7875e99` — `feat(calendar): add public manifestation portal` |
| **6B-03A** List performance | IMPLEMENTED / TESTED / COMMITTED / PUSHED / DEPLOYED | (isti `7875e99`) |
| **6B-04** Search + Tip sadržaja | IMPLEMENTED / TESTED / COMMITTED / PUSHED / DEPLOYED | `0c99241` — `feat(calendar): add content type search` (+ status sync `8a30754`) |
| **PO-MF-WF** Editor/Moderator lifecycle | IMPLEMENTED / TESTED / COMMITTED / PUSHED / DEPLOYED / PRODUCTION VERIFIED | `d3c7a96` — `fix(calendar): separate editor and moderator manifestation workflows` |

**Final regression gate (functional):** 244 passed / 992 assertions. **Closeout regression (2026-08-12):** 88 passed / 639 assertions / 0 failed / 0 errors.

**PRODUCTION DEPLOYMENT: DONE.** 6B migracije RAN (`2026_08_11_121000`, `2026_08_11_121100`). `cultural_manifestations` postoji; trenutno **0 redova**. Cleanup testnih MF = **N/A**. **PHASE 6B FORMALLY CLOSED.**

**Limited content-smoke (NON-BLOCKING PRODUCTION SMOKE DEBT — ne defect, ne blocker, ne incomplete implementation):** public MF detail / program / Event→MF link / Pretraga Tip=Manifestacije ili Sve sa stvarnim MF rezultatom; Moderator resubmit nakon return; dodatna produkcijska potvrda Organizer scope. Nije izvršeno jer PO ne zahtijeva vještačke produkcijske Manifestacije samo radi closeout-a. **Ne** tvrdi se da su ti scenariji produkcijski smoke-testirani.

**V1 boundaries (ne gapovi):** Homepage MF **NOT REQUIRED**; posebna „Arhiva Manifestacija“ **OUT OF V1**; Delete MF / manual archive MF **OUT OF V1**.

**Known follow-ups (NON-BLOCKING):** BM-MF-19 invariant drift ako Event lifecycle van MF writer-a ukloni posljednji published Event (IMPORTANT / future corrective); Tip=Sve full lightweight projection load (V1 NON-BLOCKER / future optimization).

PO-6B-01…05, **PO-6B-08/09** i **PO-6B-10** usvojene (BM/FS/TS-009/Feature Registry). Core domain i editorial flow ostaju dio istog 6B functional closeout-a.

**Napomena (TS-009 v1.0.0 Stable):** Detalji događaja i Arhiva događaja nemaju zasebne PO-TS9-* odluke; pokriveni su BM-PK-05 / BM-PK-13 i BR-106 / BR-114 (baseline u TS-009 §7–§8). **CR-004A / PO-CR4A-01…05** dopunjavaju javni status badge (TS-009 §7.1). **CR-004B / PO-CR4B-01…10** dopunjavaju javni prikaz otkazanih (TS-009 §7.2; BR-270–BR-274).

Povezana dokumentacija (Organizator):

* Technical Specification — `docs/technical-specifications/Technical-Specification_Organizator.md` (TS-001; funkcionalna cjelina Organizator / Moderator / Zahtjev za kreiranje Organizatora u okviru FT-001)

Povezana dokumentacija (Događaj):

* Business Model — BM-04 (BM-DG-01–BM-DG-13), BM-10 (BM-ST-01–BM-ST-11), BM-UR-12–BM-UR-16, BM-MOD-19, BM-TR-12/19/20, BM-PK-36; PATCH-035/036 (otkazivanje), PATCH-037 (direktna objava / arhiva), **PATCH-053 / PO-DG-07** (terminalnost Otkazan), **PATCH-055 / PO-AUTO-01 / PO-AUTO-02**, **PATCH-056 / PO-DG-08 / PO-DG-09**, **PATCH-057 / PO-DG-10**, **PATCH-058 / PO-N-TR-02-04**, **PATCH-063 / PO-U-01…19** (urednički tok; ručni Org; U pripremi; delete; published edit; razlozi)
* Functional Specification — §5.4–§5.5, §5.7.1–§5.7.2 (BR-006–BR-044, BR-062–BR-066, BR-131, **BR-287–BR-295**), §5.6 BR-052, §5.16 katalog Događaji; PATCH-FS-037/038/039; **PATCH-FS-053**; **PATCH-FS-056**; **PATCH-FS-057**; **PATCH-FS-058**; **PATCH-FS-063**
* Technical Specification — `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (TS-003 — Događaj; verzija **0.1.13**; Usvojen; PATCH-063 + PO-EV-WF-01 + MED cover COMPLETE)

Povezana dokumentacija (Održavanje):

* Technical Specification — `docs/technical-specifications/Technical-Specification_Odrzavanje.md` (TS-004; verzija **0.1.9**; Usvojen; N-TR-01 / N-TR-02 / N-TR-04 zatvoreni; PO-N-TR-02-04; PATCH-063 postpone/OCC razlozi)

Povezana dokumentacija (Manifestacija):

* Business Model — BM-05 (BM-MF-01–BM-MF-23), PATCH-038–PATCH-039, PATCH-070, **PATCH-071** (6B production closeout status only); PO-MF-01–PO-MF-12, PO-MF-WF-01–04
* Functional Specification — §5.12 (BR-092–BR-101, BR-189–BR-205), §5.16 katalog Manifestacije, PATCH-FS-040–PATCH-FS-041
* Technical Specification — `docs/technical-specifications/Technical-Specification_Manifestacija.md` (TS-005; verzija **0.1.7**; Usvojen)

Povezana dokumentacija (Lokacije):

* Business Model — BM-07 (BM-LK-01–BM-LK-12), BM-GL-13, PATCH-040, PATCH-041; PO-LOC-01–PO-LOC-07
* Functional Specification — §5.9 (BR-074–BR-080, BR-206–BR-223), PATCH-FS-042, PATCH-FS-043
* Technical Specification — `docs/technical-specifications/Technical-Specification_Lokacije.md` (TS-006; verzija 0.1.1; Usvojen)

Povezana dokumentacija (Kategorije i oznake):

* Business Model — BM-08 (BM-KO-01–BM-KO-11), BM-GL-14, BM-GL-23, PATCH-043, **PATCH-059 / TS7-PO-07**
* Functional Specification — §5.10 (BR-081–BR-085, BR-224–BR-236, **BR-277–BR-279**), PATCH-FS-045, **PATCH-FS-059**
* Technical Specification — `docs/technical-specifications/Technical-Specification_Kategorije_i_oznake.md` (TS-007; verzija **0.1.4**; Usvojen)

Povezana dokumentacija (Naslovna fotografija / MED):

* **MED-01–MED-28** = **ADOPTED / DOCS CANONICALIZED / IMPLEMENTATION COMPLETE / VERIFIED** (closeout 2026-08-16). **MED-I4B** vizueli = DEFERRED / NON-BLOCKING.
* Business Model — BM-09 (BM-MD-18–BM-MD-36 aktivno; BM-MD-01–17 SUPERSEDED), BM-GL-15, BM-PK-12, **PATCH-075 / PATCH-076**
* Functional Specification — §5.11 (BR-351–BR-370; BR-086–091 / BR-237–254 SUPERSEDED), §5.4.4, BR-113, **PATCH-FS-075 / PATCH-FS-076**
* Technical Specification — TS-003 v0.1.13; TS-005 v0.1.7; TS-007 v0.1.4; TS-009 v1.0.22; TS-010 v1.0.13; TS-012 v1.0.9 (katalog KEEP)
* TS-008 v0.1.2 = **SUPERSEDED / HISTORICAL** (`Technical-Specification_Mediji.md`)
* TS8-01–TS8-09 = **ZASTARJELO / SUPERSEDED**

Povezana dokumentacija (Mediji — istorijski TS8):

* Business Model — BM-09 (BM-MD-01–BM-MD-17), BM-GL-15, BM-PK-12, PATCH-044; TS8-01–TS8-09 — **SUPERSEDED PATCH-075**
* Functional Specification — §5.11 (BR-086–BR-091, BR-237–BR-254), §5.4.4, BR-113, PATCH-FS-046 — **SUPERSEDED PATCH-FS-075**
* Technical Specification — `docs/technical-specifications/Technical-Specification_Mediji.md` (TS-008; verzija **0.1.2**; **SUPERSEDED / HISTORICAL**)

Povezana dokumentacija (Javni portal):

* Business Model — BM-11 (BM-PK-01–BM-PK-40, BM-GL-26), BM-AR-02, PATCH-045–PATCH-048, PATCH-051 (CR-004B), PATCH-060–**PATCH-066**; IA-01, PO-TS9-03A–05B, PO-TS9-06A–06D, PO-TS9-07A–07E, PO-TS9-08A–08J, PO-6A11-01, PO-6A09-01…06, **PO-U / PATCH-063**, **PO-064 / PATCH-064**, **PO-6B-08/09 / PATCH-065**, **PO-6B-10 / PATCH-066**
* Functional Specification — §5.1–§5.4, §5.13 (BR-102–BR-117, BR-255–BR-274, BR-280–BR-286, **BR-287–BR-306**), PATCH-FS-047–PATCH-FS-049, PATCH-FS-051, PATCH-FS-060–**PATCH-FS-067**
* Technical Specification — `docs/technical-specifications/Technical-Specification_Javni_portal.md` (TS-009; verzija **1.0.22**; Stable; TD-TS9-01; CR-002 §3.2; CR-003 §3.3; CR-004A §7.1; CR-004B §7.2; Faza 6A; PO-6A11-01; PO-6A09; **PATCH-063** §7.2.4 / §7.3.4–§7.3.6; **PATCH-064** §5.4–§5.5 / §11.3 / TM-JP-23…38; **PO-6B-01…05**; **PO-6B-08/09** §6.7–§6.8; **PO-6B-10** §3.4.1; **PHASE 6B FORMALLY CLOSED** / limited content-smoke; Package A CLOSED; **Phase B1+B2 PRODUCTION VERIFIED / CLOSED**; **FAZA 6A CLOSED**; B3 DEFERRED)
* Implementation Strategy — `docs/implementation-strategies/Implementation-Strategy_Javni_portal.md` (IS-001; verzija **1.0.10**; Stable)
* Change Request — CR-001 (Implemented, IS-001 Faza 1); CR-002 (Implemented, IS-001 Faza 2 — `month=YYYY-MM`; commit `c5d396f`); CR-003 (Implemented, IS-001 Faza 2 — `q`/`category`/`location`; dokumentacija `fc35132`; implementacija `595045a`; TS-009 v1.0.2; IS-001 v1.0.2); CR-004A (Implemented, IS-001 Faza 3 — javni status badge; PO-CR4A-01…05; dokumentacija `614706c`; implementacija `0f73240`; TS-009 v1.0.4; IS-001 v1.0.5); CR-004B (Planned, IS-001 Faza 3 — javni prikaz otkazanih; PO-CR4B-01…10; TS-009 v1.0.5 §7.2; IS-001 v1.0.6 §9.3.2)

Povezana dokumentacija (Newsletter):

* Business Model — BM-13 (BM-NL-01–BM-NL-47), PATCH-031–PATCH-033, **PATCH-073 / PO-NL-01…22**, **PATCH-074**; usklađenost sa PATCH-053 / PO-DG-07 (G-NL-08 zatvoren)
* Functional Specification — §5.15 (BR-138–BR-169, BR-328–BR-348), PATCH-FS-032–PATCH-FS-034, **PATCH-FS-072**, **PATCH-FS-073**; §5.16 katalog Newsletter (BR-184–BR-186)
* Technical Specification — `docs/technical-specifications/Technical-Specification_Newsletter.md` (TS-011; verzija **1.0.4**; USVOJEN)
* **FAZA 7** = NL-01…NL-06 **IMPLEMENTED / FORMALLY CLOSED** (eligibility + regular/priority delivery + legacy weekly disabled). Settings = `/newsletter`. **TS-012 emit/storage = Faza 8 CLOSED.** Legacy physical files KEEP. Production evidence = **PO-CONFIRMED**; live Git HEAD = **UNOBSERVED** iz Cursora.

Povezana dokumentacija (Urednički portal):

* Business Model — BM-12 (BM-EP-01–BM-EP-11), BM-01–BM-03 (uloge), BM-MOD-04, BM-UR-09, BM-UR-12–BM-UR-16, BM-GL-09/24/25; BM-DG-09/BM-DG-10 / **PATCH-053**; **PATCH-063 / PO-U**
* Functional Specification — Platformsko pravilo; §5.14 (BR-118–BR-128); BR-007; BR-048; BR-051; BR-063–BR-065; BR-131; **BR-287–BR-295**; **PATCH-FS-053**; **PATCH-FS-063**
* Usvojene product odluke (Dashboard — TS-010.6):
  * **PO-DASH-01:** Radna tabla prijavljenog korisnika; sadržaj po ulozi/ovlašćenjima/aktivnom kontekstu; svrha = brz nastavak rada; statistika pomoćna.
  * **PO-DASH-02:** Informacije za nastavak rada; nije izvještavanje ni poslovna analitika.
  * **PO-DASH-03:** Samo stavke koje zahtijevaju akciju ili predstavljaju aktivni rad; bez informacija bez operativnog značaja.
  * **PO-DASH-04:** Sažete kategorije sa brojačem; klik → lista sa filterom; nema liste događaja na Dashboardu; ne duplira CRUD.
  * **PO-DASH-05:** Jedinstven raspored po ulozi; bez personalizacije, drag&drop, skrivanja kartica, korisničkih layout-a.
* Usvojene product odluke (Evidencija aktivnosti — TS-010.7):
  * **PO-AL-01:** TS-010.7 = obaveze portala prema centralnoj Evidenciji; ne UI centralne; ne zamjenjuje FT-003/TS-012; Mod/Urednik bez direktnog pristupa; pristup samo Administrator.
  * **PO-AL-02:** Lokalni audit ≠ centralna Evidencija (FT-003).
  * **PO-AL-03:** Samo obaveza evidentiranja; bez API/SQL/strukture/prikaza (FT-003/TS-012).
  * **PO-AL-04:** Bez novih aktivnosti; samo BM/FS katalog.
* Usvojena QA odluka (Business Test Matrix — TS-010.8):
  * **QA-TS0108-01:** TS-010.8 = Business Test Matrix; nije QA Plan / Test Strategy / Test Implementation / CI / coverage.
* Technical Specification — `docs/technical-specifications/Technical-Specification_Urednicki_portal.md` (TS-010; verzija **1.0.11**; USVOJEN — V1 implementaciono završen za prethodni obuhvat; **PATCH-063 dokumentaciono usvojen i implementiran**; **MOD-UX-01** = UI/navigation status sync (+ Alpine-free CURRENT STATE); **PATCH-064** = portalna delta u TS-009 (nije TS-010 scope); usklađen sa TS-003 v0.1.10 / TS-004 v0.1.9)
  * TS-010.1 Osnove uredničkog portala — Usvojeno / implementirano
  * TS-010.2 Organizatori — Usvojeno / implementirano
  * TS-010.3 Moderator Organizatora — Usvojeno / implementirano
  * TS-010.4 Workflow događaja — Usvojeno / implementirano (terminalnost Otkazan; bez Otkazan → Objavljen; PO-DG-10); **PATCH-063 usvojeno i implementirano**
  * TS-010.5 CRUD događaja i validacije — Usvojeno / implementirano (prethodni obuhvat); **PATCH-063** (ručni Org, U pripremi, delete draft, published direct edit) — **dokumentaciono usvojeno i implementirano**
  * TS-010.6 Dashboard uredničkog portala — Usvojeno / implementirano; DU-03 → „Događaji u pripremi“ (PATCH-063 implementirano)
  * TS-010.7 Evidencija aktivnosti uredničkog portala — Usvojeno (obaveza); emit/storage = **TS-012 / Faza 8**
  * TS-010.8 Business Test Matrix — Usvojeno (poslovna matrica; §13.x PATCH-063 matrica za buduću implementaciju)

**Planirani Technical Specification dokumenti (modul Kalendar kulture):**

Plan koristi globalnu numeraciju (M-TS-002). Oznaka TS-002 pripada modulu Plaćanja (FT-002) i nije dio ovog plana.

| TS | Naziv | Feature | Modul | Status |
| -- | ----- | ------- | ----- | ------ |
| TS-001 | Organizator, Moderator i zahtjev za kreiranje Organizatora | FT-001 | Kalendar kulture | Usvojen (v0.4.1) — PO-ORG-06 IMPLEMENTED / PRODUCTION VERIFIED |
| TS-003 | Događaj | FT-001 | Kalendar kulture | Usvojen (v0.1.13); MED cover **IMPLEMENTATION COMPLETE / VERIFIED** |
| TS-004 | Održavanje događaja | FT-001 | Kalendar kulture | Usvojen (v0.1.9); N-TR-01 / N-TR-02 / N-TR-04 zatvoreni; PATCH-063 docs |
| TS-005 | Manifestacija | FT-001 | Kalendar kulture | Usvojen (v0.1.7); MED cover **COMPLETE / VERIFIED**; MED-19 MF destroy nije uveden |
| TS-006 | Lokacije | FT-001 | Kalendar kulture | Usvojen (v0.1.1) |
| TS-007 | Kategorije i oznake | FT-001 | Kalendar kulture | Usvojen (v0.1.4); fallback resolver COMPLETE; MED-I4B dedicated vizueli DEFERRED |
| TS-008 | Mediji (istorijski) | FT-001 | Kalendar kulture | **SUPERSEDED / HISTORICAL** (v0.1.2); TS8-01–09 zastarjeli; kanon = MED-01–28 |
| TS-009 | Javni portal | FT-001 | Kalendar kulture | Stable (v1.0.22); MED fallback resolver COMPLETE; **MED-I4B** vizueli DEFERRED / NON-BLOCKING |
| TS-010 | Urednički portal | FT-001 | Kalendar kulture | Usvojen (v1.0.13) — V1 KEEP; MED-26 UX **IMPLEMENTATION COMPLETE / VERIFIED**; Media CRUD REMOVED |
| TS-011 | Newsletter | FT-001 | Kalendar kulture | Usvojen (v1.0.4) — **FAZA 7 FORMALLY CLOSED** (NL-01…NL-06); emit ka TS-012 = **Faza 8 CLOSED**; izmjena fotografije **nije** NL okidač (KEEP) |
| TS-012 | Evidencija aktivnosti | FT-003 | Kalendar kulture | Usvojen (v1.0.9) — katalog KEEP; MED-28: `TS12-MF-11` KEEP; no new `media.*` |

---

## FT-002

Naziv:

Plaćanja

Status:

Planned

Napomena:

Modul za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor. Poslovna i funkcionalna dokumentacija su usklađene i usvojene za BP-01 do BP-09 (BM-002/FS-002), dok je TS-002 u statusu dokumenta u izradi sa djelimično usvojenim tehničkim poglavljima. Implementacija nije započeta.

Povezana dokumentacija:

* Pravni okvir: `docs/pravni-okvir/Pravni_okvir_Placanja.md`
* Katalog finansijskih obaveza: `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md`
* Business Model: `docs/business-model/Business_Model_Placanja.md`
* Functional Specification: `docs/functional-specifications/Functional-Specification_Placanja.md`
* Technical Specification: `docs/technical-specifications/Technical-Specification_Placanja.md`

Sljedivost:

FT-002
→ BM-002
→ FS-002
→ TS-002

Usvojene projektne odluke:

* P-01 do P-08 — Projektna načela modula Plaćanja
* F-01 — Obavezni obuhvat V1
* UR-01 — Uplatni računi (referentni / konfiguracioni podaci)
* BP-01 — Pronalaženje vrste uplate
* BP-02 — Način popunjavanja podataka za plaćanje
* BP-03 — Pregled i potvrda prije plaćanja
* BP-04 — Jedinstvena integracija sa sistemom elektronskog plaćanja
* BP-05 — Obrada ishoda elektronskog plaćanja
* BP-06 — Potvrda o izvršenom elektronskom plaćanju
* BP-07 — Izvor obaveznih podataka za elektronsko plaćanje
* BP-08 — Životni ciklus transakcije
* BP-09 — Istorija transakcija i pregled plaćanja

Sljedivost poslovnih odluka:

| Oznaka | Naziv | BM | FS | TS |
|--------|-------|----|----|----|
| BP-01 | Pronalaženje vrste uplate | BM-002 / 9.1 | FS-002 / 7.1 | TS-002 / 2.5 |
| BP-02 | Način popunjavanja podataka za plaćanje | BM-002 / 9.2 | FS-002 / 7.2 | TS-002 / 2.5 |
| BP-03 | Pregled i potvrda prije plaćanja | BM-002 / 9.3 | FS-002 / 7.3 | TS-002 / 2.5 |
| BP-04 | Jedinstvena integracija sa sistemom elektronskog plaćanja | BM-002 / 9.4 | FS-002 / 7.4 | TS-002 / 2.6 |
| BP-05 | Obrada ishoda elektronskog plaćanja | BM-002 / 9.5 | FS-002 / 7.5 | TS-002 / 2.7 |
| BP-06 | Potvrda o izvršenom elektronskom plaćanju | BM-002 / 9.6 | FS-002 / 7.6 | TS-002 / 2.8 |
| BP-07 | Izvor obaveznih podataka za elektronsko plaćanje | BM-002 / 9.7 | FS-002 / 7.7 | TS-002 / 2.9 |
| BP-08 | Životni ciklus transakcije | BM-002 / 9.8 | FS-002 / 7.8 | TS-002 / 2.10 |
| BP-09 | Istorija transakcija i pregled plaćanja | BM-002 / 9.9 | FS-002 / 7.9 | TS-002 / 2.11 |

Veze BP-04: P-03, P-08, UR-01.

Veze BP-05: BP-03, BP-04.

Veze BP-06: BP-05, BP-09.

Veze BP-07: BP-02, BP-03, UR-01, BP-04.

Veze BP-08: BP-03, BP-04, BP-05, BP-06, P-08.

Veze BP-09: BP-06, BP-08, UR-01.

---

## FT-003

Naziv:

Evidencija aktivnosti (Kalendar kulture)

Status:

Usvojen (TS-012 v1.0.8) — **FAZA 8 CLOSED: IMPLEMENTATION COMPLETE / PRODUCTION ACTIVE / PRODUCTION ACCEPTED.** F8-01 freeze complete. F8-02 store + F8-03 emitters + F8-04 V1 Admin UI = production active / accepted (read-only chronological list + pagination; no filters/search/export/show). V1 audit = best-effort / failure-isolated / no durable replay. `repeatable()` uniqueness = known V1 limitation. Historical audit rows immutable.

Napomena:

Centralna Evidencija aktivnosti modula Kalendar kulture — dokumentovanje poslovno značajnih radnji radi odgovornosti, kontrole i revizije. Direktan pristup: Administrator platforme. Razlikuje se od lokalnih audit tragova na entitetima.

V1 katalog (FS / BM-AL-07 / BM-MF-20 / TS-012): Moderator ovlašćenja; Organizatori; Manifestacije; događaji (uključujući aktivnosti nad Održavanjem); Newsletter. Van opsega (BR-188): autentikacija/platformski nalozi i uloge, detaljni Admin pregled/filteri, retention, izvoz.

Povezana dokumentacija:

* Business Model — BM-14 (BM-AL-01–BM-AL-08), BM-EP-09, BM-GL-09, BM-GL-20, BM-MF-20
* Functional Specification — §5.16 (BR-170–BR-188, BR-349–BR-350), PATCH-FS-035 / PATCH-FS-041 / **PATCH-FS-074**
* Technical Specification — `docs/technical-specifications/Technical-Specification_Evidencija_aktivnosti.md` (TS-012; verzija **1.0.8**; USVOJEN; **FAZA 8 CLOSED / PRODUCTION ACCEPTED**; V1 best-effort)

Matrica sljedivosti (sažetak):

| BM | FS | FT | TS |
|----|----|----|-----|
| BM-AL-01–BM-AL-08 | BR-170–BR-188, BR-349–BR-350 / §5.16 | FT-003 | TS-012 (usvojen v1.0.8; FAZA 8 CLOSED / PRODUCTION ACCEPTED; V1 best-effort) |
| BM-EP-09 | §5.16 | FT-003 | TS-012 (usvojen v1.0.8; FAZA 8 CLOSED / PRODUCTION ACCEPTED) |
| BM-GL-09, BM-GL-20 | BR-170, BR-174 | FT-003 | TS-012 (usvojen v1.0.8; FAZA 8 CLOSED / PRODUCTION ACCEPTED) |
| BM-MF-20 | §5.16 katalog Manifestacije | FT-003 | TS-012 (usvojen v1.0.8; FAZA 8 CLOSED / PRODUCTION ACCEPTED) |

---

## FT-004

Naziv:

Obavještenja

Status:

Active

Napomena:

Unakrsna (cross-platform) funkcionalnost Digital Kotor za javno predstavljanje zvaničnog sadržaja nastalog u drugim funkcionalnostima platforme. Nije zaseban aplikativni modul, nije korisnički inbox, nije sistem privatnih poruka, nije konvencionalni centar notifikacija sa stanjem pročitano/nepročitano i nije urednički CMS za vijesti.

V1 infrastruktura je implementirana i verifikovana (`ObavjestenjaFeatureTest` + smoke): `notices`, `NoticePublicationService`, događaj/listener (jednom registrovan), panel na `/`, javna ruta `notices.public-content`, `competition_decision_html`. End-to-end automatizam iz izvornog konkursa i dalje blokiran OFD-OB-006.

Povezana dokumentacija:

* Business Model: `docs/business-model/Business_Model_Obavjestenja.md`
* Use Case Specification: `docs/use-cases/Use_Cases_Obavjestenja.md`
* Functional Specification: `docs/functional-specifications/Functional_Specification_Obavjestenja.md`
* Technical Specification: `docs/technical-specifications/Technical_Specification_Obavjestenja.md` (TS-013)

Sljedivost:

FT-004
→ Business Model Obavještenja (v0.1 + PATCH-001, U IZRADI)
→ Use Cases Obavještenja (v0.1, U IZRADI)
→ Functional Specification Obavještenja (v0.1 + PATCH-FS-OB-001, U IZRADI)
→ Technical Specification Obavještenja (TS-013 v0.1, U IZRADI)

Usvojene Product Owner odluke (evidentirane u Business Modelu):

* PO-OB-01 — Svrha
* PO-OB-02 — Mjesto na platformi
* PO-OB-03 — Javna dostupnost
* PO-OB-04 — Struktura Obavještenja
* PO-OB-05 — Vrste referenciranog sadržaja
* PO-OB-06 — Automatsko nastajanje
* PO-OB-07 — Odgovornost izvorne funkcionalnosti
* PO-OB-08 — Početni izvori
* PO-OB-09 — Trajanje vidljivosti
* PO-OB-10 — Bez praćenja čitanja u usvojenom obuhvatu
* PO-OB-11 — Bez ručnog uredničkog workflow-a u usvojenom scenariju
* PO-OB-12 — Stabilna javna dostupnost
* PO-OB-13 — Značenje zamjene

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-26 | Kreiran Feature Registry. Registrovana funkcionalnost FT-001 – Kalendar kulture. |
| 2026-07-27 | Registrovana funkcionalnost FT-002 – Plaćanja. Status: Planned. |
| 2026-07-27 | FT-002 – Dodate usvojene odluke BP-01, BP-02, BP-03 i matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-04 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-05 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-06 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-07 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-08 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – PATCH-008A: redakcijsko usklađivanje BP-05/BP-06/BP-08 (bez nove poslovne odluke). |
| 2026-07-27 | FT-002 – PATCH-008B: evidencija bilježi trenutni status transakcije (bez nove poslovne odluke). |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-09 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – PATCH-009A: redakcijsko usklađivanje BP-06↔BP-09 i terminologija identifikatora (bez nove poslovne odluke). |
| 2026-07-27 | FT-001 – Newsletter: usklađeno sa BM PATCH-031 / FS PATCH-FS-032 (novoobjavljeni događaji; bez fiksnog sedmičnog modela). |
| 2026-07-27 | Registrovana funkcionalnost FT-003 – Evidencija aktivnosti (Kalendar kulture). Status: Planned. Povezano sa BM-14 i FS §5.16 (PATCH-FS-035). |
| 2026-07-28 | Usklađivanje pravila sljedivosti: Feature može imati jedan ili više TS dokumenata; TS dokumenti koriste globalnu numeraciju. Za FT-001 evidentiran postojeći TS-001 za funkcionalnu cjelinu Organizator / Moderator / Zahtjev za kreiranje Organizatora. |
| 2026-07-28 | Evidentiran planski raspored TS dokumenata za modul Kalendar kulture (TS-001, TS-003–TS-012); TS-002 ostaje rezervisan za Plaćanja (FT-002). TS-012 rezervisan za FT-003. Status planiranih: nacrt nije započet. |
| 2026-07-28 | FT-001 — Evidentirana usvojena odluka o ovlašćenjima za otkazivanje i ponovnu objavu događaja (BM PATCH-035 / FS PATCH-FS-037); relevantno za TS-003. |
| 2026-07-28 | FT-001 — Korekcija odluke: nakon deaktivacije Organizatora Moderator nema pravo otkazivanja (BM PATCH-036 / FS PATCH-FS-038); nalaz B4 zatvoren. |
| 2026-07-29 | FT-001 — PO-DG-05 i PO-DG-06: direktna objava samo bez Organizatora; Otkazan → Arhiviran nakon isteka održavanja (BM PATCH-037 / FS PATCH-FS-039); N-DG-05 i N-DG-06 zatvoreni. |
| 2026-07-29 | FT-001 — TS-004 Održavanje događaja usvojen (v0.1.1); Termin nije poslovni/konceptualni entitet V1. |
| 2026-07-29 | FT-001 — TS-003 Događaj usvojen (v0.1.1); putanja `docs/technical-specifications/Technical-Specification_Dogadjaj.md`. |
| 2026-07-29 | FT-001 — PO-MF-01–PO-MF-08 i TS-005 Manifestacija Draft v0.1; BM PATCH-038 / FS PATCH-FS-040; putanja `docs/technical-specifications/Technical-Specification_Manifestacija.md`. |
| 2026-07-29 | FT-001 — TS-005 Manifestacija usvojen (v0.1.1); PO-MF-09–PO-MF-12; N-MF-01–N-MF-04 zatvoreni; N-MF-05 napomena (evidencija). |
| 2026-07-30 | FT-001 — Lokacije: ugrađene usvojene odluke PO-LOC-01–PO-LOC-07 u BM PATCH-040 i FS PATCH-FS-042; kreiran i usvojen TS-006 (v0.1.0), putanja `docs/technical-specifications/Technical-Specification_Lokacije.md`. |
| 2026-07-30 | FT-001 — Lokacije: korekcija PO-LOC-01 i PO-LOC-05 (razrješenje KON-LOC-01 i KON-LOC-02): katalog opcioni, ručni unos dozvoljen, kataloška referenca opciona, merge samo za kataloške reference. Usklađeni BM PATCH-041, FS PATCH-FS-043 i TS-006 v0.1.1. |
| 2026-07-30 | FT-001 — TS-004 Održavanje događaja v0.1.2: terminološko usklađivanje sa TS-006 (kataloška Lokacija / ručno uneseni naziv); usklađene reference verzije u Feature Registry. |
| 2026-07-30 | Documentation Consistency Patch (CR-002): usklađen statusni opis FT-002 sa stvarnim stanjem dokumentacije (BM-002/FS-002 usvojeni BP-01–BP-09; TS-002 djelimično usvojen i u izradi). Bez izmjene poslovnih pravila. |
| 2026-07-30 | FT-001 — Kategorije i oznake: ugrađene usvojene odluke TS7-PO-01–TS7-PO-06 u BM PATCH-043 i FS PATCH-FS-045; kreiran i usvojen TS-007 (v0.1.0), putanja `docs/technical-specifications/Technical-Specification_Kategorije_i_oznake.md`. |
| 2026-07-31 | FT-001 — Mediji: ugrađene usvojene odluke TS8-01–TS8-09 u BM PATCH-044 i FS PATCH-FS-046; kreiran i usvojen TS-008 (v0.1.0), putanja `docs/technical-specifications/Technical-Specification_Mediji.md`. |
| 2026-07-31 | Registrovana funkcionalnost FT-004 – Obavještenja. Status: Planned. Povezan početni Business Model `docs/business-model/Business_Model_Obavjestenja.md` (v0.1, U IZRADI). |
| 2026-07-31 | FT-004 — Evidentirana početna Functional Specification v0.1 (`docs/functional-specifications/Functional_Specification_Obavjestenja.md`); povezani Use Cases; dopunjene PO-OB-12 i PO-OB-13 u registru. |
| 2026-07-31 | FT-004 — Evidentirana početna Technical Specification TS-013 v0.1 (`docs/technical-specifications/Technical_Specification_Obavjestenja.md`). |
| 2026-07-31 | PATCH-DOC-STRUCTURE-001 — Normalizacija direktorijuma: kanonski `docs/functional-specifications/` i `docs/technical-specifications/`; uklonjeni singular folderi. |
| 2026-07-31 | FT-004 — Implementirana V1 infrastruktura Obavještenja (TS-013): `notices`, servis objave, događaj/listener, panel na `/`, javna ruta `notices.public-content`, `competition_decision_html`. Status ostaje Planned do verifikacije testova; E2E okidač iz konkursa OFD-OB-006. |
| 2026-07-31 | FT-004 — Stabilizacija: uklonjena dvostruka registracija listenera (discovery XOR explicit); status korigovan sa Active na Planned (testovi nisu verifikovani; OFD-OB-006). |
| 2026-07-31 | FT-004 — Verifikacija na MySQL test bazi: `ObavjestenjaFeatureTest` 21/21 PASSED; smoke PASS; status Active. E2E okidač iz konkursa i dalje OFD-OB-006. |
| 2026-07-31 | FT-001 — TS-009 faza 2 (PO-TS9-06A–06D): usklađeni BM PATCH-046, FS PATCH-FS-048 i TS-009 v0.2.0 (Hero, istaknuti max 3, klikabilne statistike, lista ispod kalendara). Bez izmjene implementacije. |
| 2026-07-31 | FT-001 — TS-009 faza 3 (PO-TS9-07A–07E): usklađeni BM PATCH-047, FS PATCH-FS-049 i TS-009 v0.3.0 (Manifestacije na javnom portalu). TS-005 v0.1.2 usklađen za javni program (Otkazani). Bez izmjene implementacije. |
| 2026-07-31 | FT-001 — TS-009 Final Review v0.5.0: završna dokumentaciona revizija (sljedivost, terminologija, granice TS-003/004/005, baseline §7 Detalji događaja / §8 Arhiva događaja bez novih PO). BM PATCH-048 (Oznake ≠ Tagovi u BM-DG-06). Nije v1.0.0. Bez izmjene implementacije. |
| 2026-07-31 | FT-001 — TS-009 Stable v1.0.0: objavljena stabilna verzija specifikacije javnog portala. Bez izmjene poslovnih/funkcionalnih/tehničkih pravila. Bez izmjene implementacije. |
| 2026-08-01 | FT-001 — CR-001 Implemented (IS-001 Faza 1 UI). CR-002 Planned (IS-001 Faza 2: `month=YYYY-MM`, klik treće statistike). TS-009 v1.0.1 + IS-001 v1.0.1 usklađeni dokumentaciono. Bez izmjene implementacije. |
| 2026-08-01 | FT-001 — CR-002 Implemented (IS-001 Faza 2 mjesečni filter). Commit `c5d396f`. Referenca: TS-009 v1.0.1, IS-001 v1.0.1. Bez izmjene FT identifikatora / funkcionalnog obuhvata. |
| 2026-08-01 | FT-001 — CR-003 Planned (IS-001 Faza 2: `q`/`category`/`location`; PO-CR3-01…08). TS-009 v1.0.2 + IS-001 v1.0.2 usklađeni dokumentaciono. Bez izmjene implementacije. |
| 2026-08-01 | FT-001 — CR-003 Implemented (IS-001 Faza 2 filteri). Dokumentacija `fc35132`; implementacija `595045a`. Referenca: TS-009 v1.0.2, IS-001 v1.0.2. Bez izmjene FT identifikatora / funkcionalnog obuhvata. |
| 2026-08-01 | FT-001 — CR-004A Planned (IS-001 Faza 3: javni status badge Predstoji / U toku / Završen / Otkazan; PO-CR4A-01…04). TS-009 v1.0.3 + IS-001 v1.0.4 usklađeni dokumentaciono. Bez izmjene implementacije. |
| 2026-08-01 | FT-001 — CR-004A Implemented (IS-001 Faza 3 status badge). Dokumentacija `614706c`; implementacija `0f73240`; testovi 65/266. Referenca: TS-009 v1.0.4, IS-001 v1.0.5. Bez izmjene FT identifikatora / funkcionalnog obuhvata. |
| 2026-08-06 | FT-001 — CR-004B Planned (IS-001 Faza 3: korektivni prolaz dokumentacije; portalna Arhiva ≠ archived; cancelled ostaje; statistike/PO-TS9-06C; inventar TS-009 Stable v1.0.5). PO-CR4B-01…10; BR-270–BR-274. Bez izmjene BR-065 / BM-DG-04. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010 Urednički portal započet (v0.1.0, U IZRADI). TS-010.1 Osnove dokumentaciono pripremljene; TS-010.2–TS-010.8 Planned. Putanja `docs/technical-specifications/Technical-Specification_Urednicki_portal.md`. Bez izmjene FT-001 statusa. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.2 Organizatori dokumentaciono pripremljen (v0.2.0, U IZRADI). Pravila veze Organizator↔Moderator, invariant najmanje jednog aktivnog Moderatora, statusi Na odobrenju/Aktivan/Deaktiviran. TS-010.3–TS-010.8 Planned. Bez izmjene FT-001 statusa. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.2 QA korektivni prolaz (v0.2.1): precizirana Pravila 3 i 5; uklonjen trailing whitespace. Bez izmjene FT-001 statusa. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.3 Moderator Organizatora dokumentaciono pripremljen (v0.3.0, U IZRADI). Nastanak/uklanjanje ovlašćenja; kontekst; invarianti; ovlašćenja/zabrane; G-11/G-12/G-13/G-16/G-17; G-14 van obuhvata. TS-010.4–TS-010.8 Planned. Bez izmjene FT-001 statusa. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.3 QA korektivni prolaz (v0.3.1): G-11 zahtjev za sopstveno uklanjanje; G-12 terminologija; G-14 granica podataka Organizatora; G-17 sloj platformske role; povlačenje; G-13; sljedivost. Bez izmjene FT-001 statusa. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.4 Workflow događaja dokumentaciono pripremljen (v0.4.0, U IZRADI). Akcije/guard/matrica prelaza; otkazivanje (neoperativan aktivni prijedlog); arhiviranje ref. TS-004; CR-004B ref. TS-009; §8.4. TS-010.5–TS-010.8 Planned. Bez izmjene FT-001 statusa. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010 v0.4.1: zatvoren N-DG-02 (V1 katalog sadržajnih polja događaja u TS-010 §9). TS-010.5–TS-010.8 Planned. Bez izmjene FT-001 statusa. Bez izmjene BM/FS. Bez izmjene TS-003. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-004 v0.1.3: zatvoren N-TR-01 (model jednog održavanja; jedan kalendarski datum; vrijeme početka/završetka; cjelodnevno; bez raspona datuma). N-TR-02 / N-TR-04 ostaju otvoreni. FT-001 ostaje Active. Bez izmjene BM/FS. Bez izmjene TS-010. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-004 v0.1.4: zatvoren N-TR-04 (fizičko uklanjanje održavanja samo iz Nacrta prije prvog uredničkog postupka; nakon prvog slanja — izmjena/statusi). N-TR-02 ostaje otvoren. FT-001 ostaje Active. Bez izmjene BM/FS. Bez izmjene TS-010. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-004 v0.1.5 + BM PATCH-052 + FS PATCH-FS-052: zatvoren N-TR-02 (PO-N-TR-02-01–03; generator; max 100; serija nije entitet). FT-001 ostaje Active. Bez izmjene TS-010. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.5 CRUD događaja i validacije dokumentaciono pripremljen (v0.5.0, U IZRADI). Create/Read/Update; prijedlog izmjene (N-DG-04 implementacioni izbor); nested Održavanja (TS-004); gate-ovi; Delete događaja nije V1; §8.5. TS-010.6–TS-010.8 Planned. FT-001 ostaje Active. Bez izmjene BM/FS. Bez izmjene TS-003/TS-004/TS-008/TS-009. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.6 Dashboard uredničkog portala dokumentaciono pripremljen (v0.6.0, U IZRADI). PO-DASH-01–05; radne kategorije + brojač + filter ka CRUD; bez BI/Activity Feed/FT-003. TS-010.7–TS-010.8 Planned. FT-001 ostaje Active. Bez izmjene BM/FS. Bez izmjene TS-001/TS-003/TS-004/TS-008/TS-009. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.7 Evidencija aktivnosti uredničkog portala dokumentaciono pripremljen (v0.7.0, U IZRADI). PO-AL-01–04; obaveza evidentiranja prema FT-003; lokalni ≠ centralni; bez UI centralne evidencije; ne zamjenjuje TS-012. TS-010.8 Planned. FT-001 ostaje Active; FT-003 ostaje Planned. Bez izmjene BM/FS. Bez izmjene TS-001/TS-003/TS-004/TS-008/TS-009. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010.8 Business Test Matrix dokumentaciono pripremljen (v0.8.0, U IZRADI). QA-TS0108-01; poslovni test scenariji sa sljedivošću BM→FS→TS→matrica; bez QA plana / implementacije testova / CI. TS-010.1–TS-010.8 Dokumentaciono pripremljeno. FT-001 ostaje Active. Bez izmjene BM/FS. Bez izmjene TS-001/TS-003/TS-004/TS-008/TS-009. Bez izmjene implementacije. |
| 2026-08-06 | FT-001 — TS-010 formalno usvojen (v1.0.0, USVOJEN). Kompletne podcjeline TS-010.1–TS-010.8. Bez funkcionalnih izmjena. FT-001 ostaje Active. Bez izmjene BM/FS. Bez izmjene TS-001/TS-003/TS-004/TS-008/TS-009. Bez izmjene implementacije. |
| 2026-08-07 | FT-001 — Evidentirana usvojena odluka PO-DG-07 / BM PATCH-053 / FS PATCH-FS-053: Otkazan terminalan (nema Otkazan → Objavljen); novi program = novi događaj; Odgođen = jedini mehanizam promjene termina; Otkazan = istorijski zapis / read-only (izuzetak: razlog otkazivanja). Usklađeni TS-003 v0.1.2 i TS-010 v1.0.1. Superseduje isključivo republish dio PATCH-035 / N-DG-01. FT-001 ostaje Active. Bez izmjene implementacije. |
| 2026-08-07 | FT-001 — TS-011 Newsletter formalno usvojen (v1.0.1). Putanja `docs/technical-specifications/Technical-Specification_Newsletter.md`. Administrativno usklađen status u registru (više nije „Planiran — nacrt nije započet”). Bez izmjene poslovnog sadržaja Feature Registry-ja. Bez izmjene BM/FS. Bez izmjene TS-003/TS-004/TS-009/TS-010. Bez izmjene implementacije. |
| 2026-08-07 | FT-003 — TS-012 Evidencija aktivnosti formalno usvojen (v1.0.1). Putanja `docs/technical-specifications/Technical-Specification_Evidencija_aktivnosti.md`. Status: Usvojen (TS-012 v1.0.1). U sažetku V1 kataloga dodate Manifestacije (usklađenje sa BM-AL-07, BM-MF-20, FS §5.16). Bez izmjene sadržaja TS-012. Bez izmjene BM/FS. Bez izmjene ostalih TS. Bez izmjene implementacije. |
| 2026-08-07 | FT-001 — PO-ORG-01–PO-ORG-04 usvojene i ugrađene: BM PATCH-054, FS PATCH-FS-054 (BR-275/BR-276), TS-001 v0.3.0. Zatvorena TS-001 otvorena pitanja 1, 2, 3, 15. Priprema Faze 2 / Korak 1. Bez izmjene implementacije. |
| 2026-08-07 | FT-001 — **PO-EV-01** usvojena (implementaciona): legacy `cultural_events` = testni/prototipski podaci; bez migracije/backfill/dual-write. Usklađeni Implementation Roadmap v1.0.3, TS-003 v0.1.3, TS-004 v0.1.6. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 2026-08-08 | FT-001 — **PO-AUTO-01 / PO-AUTO-02** usvojene (BM PATCH-055 / FS PATCH-FS-055): cascade otkazivanja otvorenih Održavanja pri otkazivanju Događaja; preciziran trenutak Planiran → Završen. Usklađeni TS-003 v0.1.5, TS-004 v0.1.7, TS-010 v1.0.2, RG-001 v1.1.3. Bez izmjene implementacije. |
| 2026-08-08 | FT-001 — **PO-DG-08 / PO-DG-09** usvojene (BM PATCH-056 / FS PATCH-FS-056): BR-052 samo Objavljen + bez Org; jednosmjerno bez Org → Aktivan Org; bez uklanjanja/zamjene. Usklađeni TS-003 v0.1.6 i TS-010 v1.0.3. Bez izmjene implementacije. |
| 2026-08-08 | FT-001 — **PO-DG-10** usvojena (BM PATCH-057 / FS PATCH-FS-057): V1 prvi Event review — Na odobrenju zaključan; Odobri / Vrati; bez Mod povlačenja, bez Počni pregled, bez Urednik edit na pending Eventu. Proposal tok neizmijenjen. Usklađeni TS-003 v0.1.7 i TS-010 v1.0.4 (TM-WF-03/04/06, TM-CRUD-08 VAN V1 za Event). Bez izmjene implementacije. |
| 2026-08-08 | FT-001 — **PO-N-TR-02-04** usvojena (BM PATCH-058 / FS PATCH-FS-058): V1 generator Održavanja preciziran (samo Nacrt; algoritmi; XOR; max 100; duplikati; atomičnost). Usklađeni TS-004 v0.1.8 i TS-010 v1.0.5. T10-GEN-01 = spreman za implementaciju (nije zatvoren). Bez izmjene implementacije. |
| 2026-08-08 | FT-001 — **TS-010 V1 implementacioni closeout** (TS-010 v1.0.6; IR-001 v1.0.4): Urednički portal V1 funkcionalno/implementaciono završen. **T10-GEN-01 = ZATVOREN**; **T10-WF-01** potvrđen; **GAP-RESUME-01** / **R-02** / **TM-OCC-17** zatvoreni (Urednik resume). Cultural: 420 passed / 1740 assertions. Dependency ostaju: TS-005 (Manifestacije), TS-009 (javni cutover / Faza 6), TS-012 (audit emit / Faza 8). Bez izmjene BM/FS/TS-003/TS-004. Bez novih poslovnih pravila. |
| 2026-08-08 | FT-001 — **TS7-PO-07** usvojena (BM PATCH-059 / FS PATCH-FS-059 / TS-007 v0.1.1 / RG-001 v1.1.4): konačni početni V1 katalog kategorija Događaja (14 naziva, redoslijed); značenja; odbačene legacy vrijednosti; semantičko mapiranje; cutover = TS-009. Katalog CRUD neizmijenjen. Bez izmjene implementacije. |
| 2026-08-09 | FT-001 — **Faza 6A dokumentacioni PATCH** (BM PATCH-060 / FS PATCH-FS-060 / TS-009 v1.0.6 / IR-001 v1.0.5): PO-TS9-08A–08J; cutover kanonskog javnog portala Događaja; Faza 6A/6B; PO-EV-01 potvrđen; TM-JP matrica. Bez izmjene implementacije. |
| 2026-08-09 | FT-001 — **PO-6A11-01** (BM PATCH-061 / FS PATCH-FS-061 / TS-009 v1.0.7): kanonski multi-OCC javni status Događaja (BM-PK-34 / BR-285 / §7.1.6). |
| 2026-08-09 | FT-001 — **PO-6A09-01…06** (BM PATCH-062 / FS PATCH-FS-062 / TS-009 v1.0.8): Javna Arhiva vs interni Arhiviran (BM-PK-35 / BR-286); PO-CR4B-09 revidiran; bez izmjene implementacije. |
| 2026-08-10 | FT-001 — **PATCH-063 / PO-U-01…19** (BM PATCH-063 / FS PATCH-FS-063 / TS-003 v0.1.10 / TS-004 v0.1.9 / TS-009 v1.0.9 / TS-010 v1.0.7): preciziran PO-DG-05 (bez registrovanog Org ≠ bez ručnog naziva); U pripremi / Sačuvaj i nastavi; delete pre-publish; published direct edit; postpone/OCC/Entry razlozi; Prvobitni termin; PO-DG-07 terminalnost KEEP; Moderator approval KEEP. **Docs usvojene; implementacija PATCH-063 završena** (produkcija `9825fec`). Bez novog FT ID. |
| 2026-08-10 | FT-001 — **PATCH-064 / PO-064** (BM PATCH-064 / FS PATCH-FS-064 / TS-009 v1.0.10): informativna naslovna vidljivost Odgođenog; zajednički hronološki bazen max 3; `planned` / `postponed_info`; one Entry one slot; Odgođeno ≠ upcoming; Pretraga/detalj/kalendar/arhiva/newsletter KEEP. Proširuje PO-TS9-06D / 08B / 08D. **Docs usvojene; implementacija završena** (produkcija `f5d5965`), a završni planned `+ još N termina` closeout završen kroz **PHASE 6A-CLOSE-04** (produkcija `e88479d`). Bez novog FT ID. |
| 2026-08-11 | FT-001 — **PHASE 6A FINAL CLOSEOUT (status sync):** potvrđeno produkcijsko stanje za CLOSE-02 (legacy admin CRUD 403), CLOSE-03/CLOSE-03A (Oznake public detail + UX cleanup), CLOSE-04 (`+ još N termina` planned homepage kartica; `postponed_info` bez `+N`). Finalni targeted regression za CLOSE-04: 69 passed / 226 assertions; produkcijski smoke PASS. Faza 6A (Javni portal Događaja) funkcionalno zatvorena; Manifestacije ostaju Faza 6B. |
| 2026-08-11 | FT-001 — **6B-DOC-01A / PO-6B-01…04** (dok. korekcija): potvrđen `tip` URL ugovor (`Sve` default / `dogadjaji` / `manifestacije`, fail-safe), i korigovana semantika filtera po tipu (PO-6B-04): event-specifični filteri dostupni samo za `tip=dogadjaji`; `Sve` i `Manifestacije` koriste samo `q`; non-applicable parametri ne utiču na rezultat. PO-6B-02/03 ostaju važeći (bez MF agregirane lokacije; Arhivirana MF direct detail dostupan, bez posebne MF Arhive u V1). Usklađeni FS (PATCH-FS-065), TS-005 v0.1.3 i TS-009 v1.0.12. **Implementation not started**. |
| 2026-08-11 | FT-001 — **6B-DOC-01B / PO-6B-05** (dok. korekcija): zatvoren preostali PO gap za MF `q` searchable fields — `tip=manifestacije` koristi samo `naziv` i `opis` (partial, case-insensitive), bez derived pretrage kroz povezane Događaje/Održavanja; potvrđeno ponašanje za `Tip=Sve + q` (isti `q` nad oba podskupa po njihovim pravilima) i fail-safe za prazan/whitespace `q` i non-applicable event parametre. Usklađeni FS (BR-303 / PATCH-FS-065) i TS-009 v1.0.12. **6B documentation ready; implementation not started**. |
| 2026-08-11 | FT-001 — **6B-03 PRE-IMPL DOCS / PO-6B-08 / PO-6B-09:** Otkazana MF javno vidljiva do isteka izvedenog perioda; Event→MF anti-leak; bez statusa MF na Event detail. BM PATCH-065 (BM-PK-38/39), FS PATCH-FS-066 (BR-304–305), TS-009 v1.0.13, TS-005 v0.1.4, RG-001 v1.1.6. **ADOPTED / DOCUMENTED**. **6B-03 implementacija nije započeta**. |
| 2026-08-11 | FT-001 — **6B-03 + 6B-03A IMPLEMENTATION CLOSEOUT (status sync):** javni portal Manifestacija (lista/detalj/program + list performance) **IMPLEMENTED / TESTED / COMMITTED / PUSHED** — `7875e99` (`feat(calendar): add public manifestation portal`). Stale „6B-03 implementacija nije započeta“ supersedovan. |
| 2026-08-11 | FT-001 — **6B-04 PRE-COMMIT DOCS / PO-6B-10:** globalno Tip=Sve sortiranje Pretrage (BM PATCH-066 / BM-PK-40; FS PATCH-FS-067 / BR-306; TS-009 v1.0.14 §3.4.1; RG-001 v1.1.7 family example sync). **PO-6B-10 ADOPTED / DOCUMENTED.** **6B-04** lokalna implementacija PO-validirana; **NOT COMMITTED / NOT PUSHED / NOT DEPLOYED**. |
| 2026-08-11 | FT-001 — **6B-04 IMPLEMENTATION CLOSEOUT (status sync):** Tip sadržaja + combined Pretraga (PO-6B-01/04/05/10) **IMPLEMENTED / TESTED / COMMITTED / PUSHED** — `0c99241` (`feat(calendar): add content type search`); final gate 244 passed / 992 assertions. **PO-6B-10 ADOPTED / DOCUMENTED / IMPLEMENTED / TESTED / COMMITTED / PUSHED.** **NOT DEPLOYED.** Stale „NOT COMMITTED / NOT PUSHED“ supersedovan. |
| 2026-08-11 | FT-001 — **6B-05A FUNCTIONAL DOCUMENTATION CLOSEOUT:** **PHASE 6B FUNCTIONAL IMPLEMENTATION COMPLETE** (6B-01…6B-04; commits `26217f6` / `0e8f7c3` / `7875e99` / `0c99241`). Gate 244/992. **PRODUCTION DEPLOYMENT / SMOKE: NOT DONE** — **PHASE 6B PRODUCTION CLOSEOUT PENDING**. Usklađeni Feature Registry, IR-001 v1.0.6, TS-009 v1.0.15 (status only). Homepage MF / Arhiva MF list / Delete MF = OUT OF V1. Bez novih PO/BR. Bez izmjene implementacije. |
| 2026-08-11 | FT-001 — **PO-ORG-05** usvojena: napomena Urednika na zahtjevu za kreiranje Organizatora — approve opciono / reject obavezno; fail-closed; `decision_note` reuse. BM PATCH-067 (BM-ORG-14), FS PATCH-FS-068 (BR-307), TS-001 v0.3.1. Moderator request note rule = unchanged (PO decision required separately). |
| 2026-08-11 | FT-001 — **PO-ORG-06** usvojena / dokumentovana: privacy-safe Moderator invitation (first + subsequent ADD); waiting status; resolver; emails; REMOVE-approved notify; supersede PO-ORG-02 selection. BM PATCH-068 (BM-ORG-15–19, BM-MOD-20–26), FS PATCH-FS-069 (BR-308–BR-320), TS-001 v0.4.0. **ADOPTED / DOCUMENTED / IMPLEMENTATION NOT STARTED.** CURRENT = users dropdown. RG-001 KEEP. IR/IS KEEP. |
| 2026-08-12 | FT-001 — **PO-ORG-06 PRODUCTION CLOSEOUT (status sync):** core Packages 1–5 IMPLEMENTED / COMMITTED / PUSHED / PRODUCTION DEPLOYED; Package 1 produkciona migracija RAN; produkcioni smoke PO-confirmed PASS; ordinary-user CTA discoverability corrective `814ff96` (`fix(calendar): expose organizer request action`) PRODUCTION VERIFIED. TS-001 → v0.4.1 (status only). BM PATCH-069 (status napomena). FS KEEP (PATCH-FS-069 historical). **ADOPTED / DOCUMENTED / IMPLEMENTED / PRODUCTION VERIFIED.** Optional durable mail retry / `invitation_sent_at` ostaje non-blocking OUT OF SCOPE. Bez novih poslovnih pravila. RG-001 KEEP. |
| 2026-08-12 | FT-001 — **PO-MF-WF-01–04 / PO-EV-WF-01** (BM PATCH-070 / FS PATCH-FS-070 / TS-005 v0.1.5): Manifestation editor vs moderator lifecycle corrective by creator origin (`created_by` / `kk_admin`); editor direct publish; backend self-submit/self-return guards; Event no-org submit hardening. **ADOPTED / DOCUMENTED / IMPLEMENTED / TESTED (local).** **NOT COMMITTED / NOT PUSHED / NOT DEPLOYED / NOT PRODUCTION VERIFIED.** Bez migracije/backfill. RG-001 KEEP (PO-MF familija). |
| 2026-08-12 | FT-001 — **PHASE 6B PRODUCTION CLOSEOUT (status sync):** PO usvojio **FUNCTIONALLY COMPLETE / PRODUCTION ACCEPTED WITH LIMITED CONTENT-SMOKE COVERAGE**. 6B migracije RAN; `cultural_manifestations` = 0 redova; cleanup N/A; editorial + moderator osnovni lifecycle + kk_admin nav **PRODUCTION VERIFIED**; PO-MF-WF / PO-EV-WF (`d3c7a96`) **COMMITTED / PUSHED / DEPLOYED / PRODUCTION VERIFIED**. **PHASE 6B FORMALLY CLOSED.** NON-BLOCKING PRODUCTION SMOKE DEBT: public detail/program/Event→MF/search-with-hit; moderator resubmit; organizer-scope extra smoke. Homepage MF / Arhiva MF / Delete MF ostaju OUT OF V1. BM PATCH-071 (status only). IR-001 → v1.0.7. TS-009 → v1.0.16. TS-005 / FS / RG-001 KEEP. |
| 2026-08-12 | FT-001 — **6A residual Package A (`cultural-calendar.day` canonical cutover):** kada je public read = canonical, `day()` koristi `CulturalPublicEventQuery::filterByDate` + `occurrenceOnDate` (reuse `indexCanonical` selected-day SSOT); legacy branch KEEP; kk_admin redirect KEEP; bez badge/detail-link/redesign. **IMPLEMENTED / TESTED (local).** **NOT COMMITTED / NOT PUSHED / NOT PRODUCTION VERIFIED.** Phase B hard-remove / flag cleanup ostaje otvoren. IR-001 → v1.0.8. TS-009 → v1.0.17 (status only). BM/FS/RG-001 KEEP. |
| 2026-08-12 | FT-001 — **6A residual Package A PRODUCTION CLOSEOUT (status sync):** commit `f35cb2e` (`fix(calendar): cut over day view to canonical events`) **COMMITTED / PUSHED / DEPLOYED**. Production smoke `/kalendar-kulture/dan/2026-08-12` — empty-date PASS (naslov + „Nema događaja…“; bez 404/500; bez badge/detail-link; layout KEEP). **PRODUCTION VERIFIED — EMPTY-DATE SCENARIO CONFIRMED.** Content-bearing day scenario not separately production-smoked (local suite covers; not a blocker). **Package A CLOSED.** Phase B hard-remove / flag cleanup **OPEN**. IR-001 → v1.0.9. TS-009 → v1.0.18 (status only). BM/FS/RG-001 KEEP. |
| 2026-08-13 | FT-001 — **Phase B1+B2 CANONICAL-ONLY RUNTIME CLEANUP (status sync):** B1 flag/config removed; B2 dual-read public controller + legacy CRUD routes/controller/views/middleware removed; Blade canonical-only; image helpers → `CulturalCalendarDefaultImages`; `cultural_events` table KEEP; `CulturalEvent` model KEEP as non-runtime B3 shell. **IMPLEMENTED / TESTED (local).** **NOT COMMITTED / NOT PUSHED / NOT PRODUCTION VERIFIED.** **B3 OPEN / DEFERRED.** IR-001 → v1.0.10. TS-009 → v1.0.19 (status only). TS-011/BM/FS/RG-001 KEEP. |
| 2026-08-13 | FT-001 — **kk_admin UX / navigation consolidation:** editorial label `Kontrolna tabla` (was `Urednički rad`, first item); unified nav `Zahtjevi` → `cultural-editorial-requests.index` (Org/Mod sections; decision services KEEP); `kk_admin` login fallback → `cultural-calendar.index` with safe intended. **IMPLEMENTED / TESTED (local).** **NOT COMMITTED / NOT PUSHED / NOT PRODUCTION VERIFIED.** TS-010 → v1.0.8 (status only). BM/FS/RG-001 KEEP. |
| 2026-08-13 | FT-001 — **PO-ORG/MOD rejected request editor cleanup:** `Ukloni` = workspace dismiss (soft-hide) for rejected Org + Mod ADD/REMOVE; columns `editor_dismissed_at` / `editor_dismissed_by_user_id`; **no hard delete**; unified `Zahtjevi` filters dismissed. **ADOPTED / DOCUMENTED / IMPLEMENTED / TESTED (LOCAL).** **NOT DEPLOYED / NOT PRODUCTION VERIFIED.** BM PATCH-072 (BM-ORG-20, BM-MOD-27); FS PATCH-FS-071 (BR-326–BR-327); TS-001 → v0.4.2; TS-010 → v1.0.9. RG-001 KEEP. |
| 2026-08-13 | FT-001 — **MOD-UX-01 — Moderator UX / navigation corrective:** UI entrypoint **Kontrolna tabla**; **Moderiranje** (Događaji / Manifestacije); `Organizator: <naziv>`; **Promijeni organizatora**; **Izbor organizatora**; uklonjeni korisnički termini Radna tabla / Mod rad / Workspace (u ovom značenju); context switch + approval email CTA → Kontrolna tabla; auth/lifecycle/`CulturalOrganizerContext` KEEP; bez migracije. **ADOPTED / DOCUMENTED / IMPLEMENTED / TESTED (LOCAL).** **NOT DEPLOYED / NOT PRODUCTION VERIFIED.** TS-010 → v1.0.10 (§11.14); RG-001 → v1.1.8 (UX / MOD / ORG). BM/FS/TS-001/TS-005/IR-001 KEEP. |
| 2026-08-13 | FT-001 — **DOC-SYNC-TS010 (Alpine-free Moderator nav CURRENT STATE):** TS-010 → **v1.0.11** — **Moderiranje** = hub `<a>` (ne Alpine dropdown); `128×38`; hamburger inline vanilla JS; runtime bez Alpine dependency. Feature Registry status/version sync only. RG-001 / BM / FS / IR-001 / TS-001 / TS-005 KEEP. Bez izmjene implementacije. |
| 2026-08-13 | FT-001 — **FAZA 6A FINAL DOCUMENTATION CLOSURE:** FAZA 6A = **CLOSED**; B1+B2 = **PRODUCTION VERIFIED / CLOSED**; production categories **14/14 PASS**; public SSOT canonical-only; dual-read/write = 0; P0/P1 = 0; implementation remaining **NONE**. IS-001 → v1.0.8; IR-001 → v1.0.11. TS-009 / BM / FS / TS-003 / TS-007 / RG-001 KEEP. B3 DROP = DEFERRED / non-blocking. 6B OUT OF SCOPE for this closure. Bez izmjene implementacije. |
| 2026-08-14 | FT-001 — **PO-NL-01…PO-NL-22 Newsletter decision sync:** dobrovoljna pretplata; jedna pretplata po `User`; režimi opsega; „Bez organizatora“; bez confirmation e-maila; testna legacy implementacija bez migracije pretplatnika. BM PATCH-073 (BM-NL-26–BM-NL-44); FS PATCH-FS-072 (BR-328–BR-344); TS-011 → **v1.0.2**; IR-001 → v1.0.12. RG-001 KEEP (PO-NL nisu nove RG skraćenice). **ADOPTED / DOCUMENTED / IMPLEMENTATION NOT STARTED.** Bez izmjene koda. |
| 2026-08-14 | FT-001 — **NL-03 temporal eligibility + ledger boundary:** prva pretplata i reaktivacija nijesu retroaktivne; candidate ≠ dostava; first_include ledger = samo uspješna isporuka; NL-03 = eligibility/candidate foundation (bez ledger write / bez e-maila). BM PATCH-074 (BM-NL-45–BM-NL-47); FS PATCH-FS-073 (BR-345–BR-348); TS-011 → **v1.0.3**; IR-001 → v1.0.13. RG-001 KEEP. IS-001 KEEP. **ADOPTED / DOCUMENTED / NL-03 IMPLEMENTATION NOT STARTED.** Bez izmjene koda. |
| 2026-08-14 | FT-001 — **FAZA 7 FORMAL CLOSEOUT + STABILIZATION (status only):** NL-01…NL-06 **IMPLEMENTED / TESTED / COMMITTED / PUSHED**; TS-011 v1.0.3 **KEEP** (nema rewrite ugovora). **FAZA 7 = FORMALLY CLOSED.** Settings `/newsletter`; regular 6h + priority 5 min; legacy weekly runtime disabled. Production = **PO-CONFIRMED** (migracije Ran; scheduler; `/newsletter` UI). Live production Git HEAD = **UNOBSERVED**. KEEP V1: Organizer listing URL; crash-after-SMTP; no queue/outbox; physical legacy. IR-001 → **v1.0.14**. TS-012 / BM / FS / TS-011 / IS-001 / RG-001 KEEP. **Naredna numerisana faza = Faza 8 / TS-012.** Bez izmjene implementacije. |
| 2026-08-14 | FT-003 — **F8-01 TS-012 canonical freeze:** katalog V1 usklađen (FS PATCH-FS-074 / BR-349–BR-350); TS-012 → **v1.0.2** USVOJEN (bez DRAFT/Nacrt; FR-GAP uklonjen). **Faza 8 STARTED — canonical freeze. Implementation pending.** IR-001 → **v1.0.15**. BM / IS-001 / RG-001 KEEP. Bez store/emitter/UI koda. |
| 2026-08-14 | FT-003 — **F8-02 central audit foundation:** `cultural_activity_records` store/idempotency/immutability/safe facade **IMPLEMENTED (local; awaiting PO accept/commit)**. Katalog KEEP. Emiteri/Admin UI **NOT STARTED**. TS-012 → **v1.0.3** (status). IR-001 → **v1.0.16**. BM / FS / IS-001 / RG-001 KEEP. |
| 2026-08-14 | FT-003 — **F8-03 canonical emitters:** TS12-* povezani na poslovne tokove preko `CulturalActivityEmitter` **IMPLEMENTED (local; awaiting PO accept/commit)**. Katalog §7 KEEP. Admin UI **NOT STARTED**. TS-012 → **v1.0.4** (status). IR-001 → **v1.0.17**. BM / FS / IS-001 / RG-001 KEEP. |
| 2026-08-14 | FT-003 — **V1 retry semantics clarification:** best-effort / failure-isolated; idempotent ingest; **nema** durable replay garancije. Known V1 limitation registrovan. TS-012 → **v1.0.5**. IR-001 → **v1.0.18**. BM / FS / IS-001 / RG-001 KEEP. **Nije commit/push.** |
| 2026-08-14 | FT-003 — **F8-03 PO ACCEPT:** canonical emitters prihvaćeni. TS-012 → **v1.0.6** (`repeatable()` uniqueness limitation; durable replay i dalje nije V1). IR-001 → **v1.0.19**. BM / FS / IS-001 / RG-001 KEEP. Admin UI NOT STARTED. |
| 2026-08-15 | FT-003 — **F8-04 PO ACCEPT (status):** minimalni V1 Admin UI **implementation complete in repo / production UI pending deploy**. TS-012 → **v1.0.7**. IR-001 → **v1.0.20**. BM / FS / IS-001 / RG-001 KEEP. Faza 8 **nije** production closed. |
| 2026-08-15 | FT-003 — **FAZA 8 PRODUCTION CLOSEOUT (status):** F8-01…F8-04 **IMPLEMENTATION COMPLETE / PRODUCTION ACTIVE / PRODUCTION ACCEPTED / CLOSED**. TS-012 → **v1.0.8**. IR-001 → **v1.0.21**. BM / FS / IS-001 / RG-001 KEEP. Historical audit rows immutable. |
| 2026-08-15 | FT-001 / FT-003 — **V1 COMPLETE / ZAVRŠNA STABILIZACIJA CLOSED (status):** IR-001 → **v1.0.22**. Corrective 01 `1f9d959`. Runtime canonical-only. B3 DROP **DEFERRED**. TS-011 → **v1.0.4**; TS-009 → **v1.0.20** (status hygiene). BM / FS / IS-001 / RG-001 KEEP. Nema Faze 9. |
| 2026-08-15 | FT-001 — **MED-01–MED-28 dokumentaciona kanonizacija:** naslovna fotografija DG/MF; TS-008 / TS8-01–09 / BM-MD-01–17 SUPERSEDED. **ADOPTED / DOCS CANONICALIZED / IMPLEMENTATION PENDING.** Nije code COMPLETE. Nije Faza 9. BM PATCH-075; FS PATCH-FS-075; TS-003 v0.1.12; TS-005 v0.1.6; TS-007 v0.1.3; TS-008 v0.1.1 SUPERSEDED; TS-009 v1.0.21; TS-010 v1.0.12; TS-011 KEEP; TS-012 v1.0.9 (`TS12-MF-11` KEEP; no `media.*`); IR-001 v1.0.23; IS-001 v1.0.9; RG-001 v1.1.10. Bez izmjene koda. |
| 2026-08-16 | FT-001 — **MED documentation closeout:** **ADOPTED / DOCS CANONICALIZED / IMPLEMENTATION COMPLETE / VERIFIED.** MED-I1 `6060bee`; MED-I2 `e7c6a07`; MED-I3 `b416c0b`; MED-I4A `3ef974b`; MED-I5 `6a4d50e`. **MED-I4B** = DEFERRED / NON-BLOCKING PROJECT ASSET WORK. Nije Faza 9. BM PATCH-076; FS PATCH-FS-076; TS-003 v0.1.13; TS-005 v0.1.7; TS-007 v0.1.4; TS-008 v0.1.2 SUPERSEDED; TS-009 v1.0.22; TS-010 v1.0.13; TS-011 KEEP; TS-012 v1.0.9 KEEP (`TS12-MF-11`; no `media.*`); IR-001 v1.0.24; IS-001 v1.0.10; RG-001 v1.1.11 (status hygiene postojeće MED oznake; MED-I* nisu RG skraćenice). Bez izmjene koda/testova/migracija/aseta. |
| 2026-08-13 | FT-001 — **FR cross-reference cleanup:** CURRENT STATE pin IS-001 **1.0.7 → 1.0.8** (usklađeno sa `Implementation-Strategy_Javni_portal.md`). FAZA 6A/6B CLOSED KEEP; TS-009 v1.0.19 KEEP; IR-001 v1.0.11 KEEP. Bez izmjene implementacije. |
| 2026-08-10 | FT-001 — **PHASE 6A-CLOSE-02:** legacy admin CRUD `cultural-events.*` disabled (middleware `legacy_cultural_events_disabled` → 403 all methods). Legacy code/table/views retained; `CULTURAL_PUBLIC_READ_SOURCE` + public legacy read rollback retained; canonical routes unchanged. **Nije** hard remove / flag cleanup (Phase B). |
