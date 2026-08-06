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
| FT-001     | Kalendar kulture | Active  | Prva funkcionalnost u razvoju                 |
| FT-002     | Plaćanja         | Planned | Dokumentacija razvijena (BM-002/FS-002 usvojeni BP-01–BP-09; TS-002 djelimično usvojen, dokument u izradi) |
| FT-003     | Evidencija aktivnosti (Kalendar kulture) | Planned | FS §5.16; BM-14; van opsega: TS, pregled/filteri, retention, izvoz |
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

Funkcionalnost je u fazi detaljne funkcionalne specifikacije i predstavlja referentni model za razvoj metodologije Digital Kotor.

**Newsletter (u okviru FT-001):** model zasnovan na novoobjavljenim događajima i poslovno značajnim promjenama — javno objavljivanje je okidač za prvo uključivanje; otkazivanje, odlaganje i promjena datuma/vremena/lokacije su prioritetni okidači (samo pretplatnicima kojima je događaj već poslat). Višestruke promjene prije slanja daju jedinstveno obavještenje sa posljednjim važećim stanjem. Bez fiksnog sedmičnog rasporeda.

**Usvojene poslovne odluke (Događaj — otkazivanje / ponovna objava):** Dok je Organizator aktivan, Moderator može otkazati objavljeni događaj u aktivnom kontekstu; deaktivacijom Organizatora moderatorski kontekst prestaje i Moderator više ne izvršava poslovne radnje nad njegovim događajima — otkazivanje tada isključivo Urednik. Urednik može otkazati bilo koji objavljeni događaj; isključivo Urednik može ponovo objaviti otkazani događaj dok je status Otkazan (BM PATCH-035/036: BM-ORG-12, BM-DG-05, BM-DG-09, BM-ST-07, BM-MOD-16, BM-UR-11; FS PATCH-FS-037/038: BR-007, BR-049, BR-050, BR-063, BR-064). Relevantno za TS-003.

**Usvojene poslovne odluke (Događaj — direktna objava / arhiviranje):** Direktna objava Urednika dozvoljena je isključivo za događaj bez Organizatora; događaj sa Organizatorom ide isključivo Nacrt → Na odobrenju → Objavljen (PO-DG-05 / N-DG-05 zatvoren; BM PATCH-037 BM-ST-04; FS PATCH-FS-039 BR-018, BR-028). Otkazan događaj automatski prelazi u Arhiviran nakon završetka svih održavanja, isto kao Objavljen (PO-DG-06 / N-DG-06 zatvoren; BM-DG-04, BM-ST-08; BR-065).

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

**Usvojene poslovne odluke (Mediji):**
- **TS8-01:** Medij je samostalan poslovni entitet i zajednički platformski resurs bez poslovnog vlasnika.
- **TS8-02:** Zatvoreni katalog namjena: Naslovna fotografija događaja; Naslovna fotografija manifestacije; Podrazumijevana fotografija kategorije.
- **TS8-03:** Kardinalnosti 0..1 po entitetu; medij → 1..N entiteta iste namjene; hijerarhija prikaza događaja (direktna → kategorija → tehnički placeholder); fallback nije veza.
- **TS8-04:** Tip Fotografija; JPEG/PNG/WebP; max 5 MB; obavezna serverska validacija sadržaja/MIME/ekstenzije; bez SVG/GIF/BMP/TIFF/HEIC.
- **TS8-05:** Status Aktivan/Neaktivan; reaktivacija; bez soft delete; trajno brisanje samo bez poslovnih veza.
- **TS8-06.1–TS8-06.5:** Creator = audit; upload samo tokom uređivanja DG/MF/kategorije; vidljivost ≠ vlasništvo; Moderator samo veze; Urednik upravlja zapisom i lifecycle-om.
- **TS8-07:** Pretraga (Moderator: naziv/opis u kontekstu; Urednik: katalog + filteri); prikaz kartice; load more / infinite scroll.
- **TS8-08:** Poslovni i tehnički metapodaci; tagovi u modelu, bez V1 UI.
- **TS8-09.1–TS8-09.6:** Prikaz neaktivnog na postojećim vezama; uklanjanje cover-a dozvoljeno uz fallback; dupli upload sa upozorenjem; ponovna provjera ovlašćenja/uslova; bez poslovnog scenarija dva Urednika.

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
- **PO-CR4B-09:** CR-004B ne mijenja BR-065 ni BM-DG-04 i ne uvodi javnu dostupnost internog statusa `archived`. Buduća implementacija lifecycle prelaza `cancelled → archived` zahtijeva zasebno rješenje za trajno očuvanje informacije o otkazivanju.
- **PO-CR4B-10:** Regresija CR-001…CR-004A (badge, filteri, mjesečni filter, UI baseline). Referenca: TS-009 §7.2.

**Usvojene product / IA odluke (nastavak):**
- **PO-TS9-05A:** Zadržavaju se postojeći prikazi; ne uvode se novi ekrani.
- **PO-TS9-05B:** „Pretraga i pregled“ = samo lista; mjesečni kalendar samo na početnoj.
- **TD-TS9-01:** Ruta `cultural-calendar.day` nije dio referentne IA javnog portala; interna tehnička podrška admin toku.

**Usvojene product odluke (Javni portal — TS-009 faza 2 — početna stranica):**
- **PO-TS9-06A:** Hero — sastavni dio; postojeći vizuelni identitet; statički; nije uređiv iz admina; bez baze, CTA, promo, rotacije, videa; isključivo identitet modula.
- **PO-TS9-06B:** Istaknuti — postojeće mjesto/raspored; max 3; samo objavljeni i aktuelni; bira Urednik (ne sistem); kartice: foto, datum, vrijeme, lokacija, naslov, kratak opis, link na detalj; neutralno prazno stanje bez admin poruka.
- **PO-TS9-06C:** Statistike — 3 klikabilne kartice (Danas, Ove sedmice, Izabrani mjesec = naziv izabranog mjeseca); klik → Pretraga i pregled sa datumskim filterom; 0 ostaje klikabilno; brojači i `date`/`week`/`month` pregledi uključuju javno dostupne događaje (`published` | `cancelled`) u odgovarajućem vremenskom skupu (CR-004B); bez novih filtera/URL parametara.
- **PO-TS9-06D:** Lista ispod kalendara — bez datuma: „Naredni događaji“ max 3; sa datumom: svi za dan; dugme „Prikaži sve događaje“ → Pretraga i pregled (sa/bez datumskog filtera); postojeće prazno stanje.

**Usvojene product odluke (Javni portal — TS-009 faza 3 — Manifestacije):**
- **PO-TS9-07A:** Manifestacije = zasebna cjelina portala; stavka navigacije „Manifestacije“; lista + Detalji manifestacije + program; ne kroz kategorije događaja; bez redizajna.
- **PO-TS9-07B:** Lista — samo javno objavljene; sort datum početka → naziv; 12/stranica; kartica (foto, naziv, period, opis, broj objavljenih događaja, „Detalji manifestacije“); V1 bez pretrage/filtera; neutralno prazno.
- **PO-TS9-07C:** Detalji manifestacije — foto, naziv, period, organizator, lokacija (ako dostupna), web, opis, program ispod; V1 bez galerija/video/dijeljenja/rezervacija/komentara.
- **PO-TS9-07D:** Program — grupisan po datumima; sort datum → vrijeme → naziv; po Održavanju; završeni ostaju; otkazani uz statusnu oznaku „Otkazano“; „Vrijeme nije definisano“; poruka ako nema programa.
- **PO-TS9-07E:** 1 MF → N događaja; događaj ≤1 MF; događaj može bez MF; dvosmjerna navigacija; događaji ostaju u Pretrazi i pregledu/kalendaru/statistikama/Arhivi događaja; uklanjanje/arhiva MF ne briše događaje.

**Napomena (TS-009 v1.0.0 Stable):** Detalji događaja i Arhiva događaja nemaju zasebne PO-TS9-* odluke; pokriveni su BM-PK-05 / BM-PK-13 i BR-106 / BR-114 (baseline u TS-009 §7–§8). **CR-004A / PO-CR4A-01…05** dopunjavaju javni status badge (TS-009 §7.1). **CR-004B / PO-CR4B-01…10** dopunjavaju javni prikaz otkazanih (TS-009 §7.2; BR-270–BR-274).

Povezana dokumentacija (Organizator):

* Technical Specification — `docs/technical-specifications/Technical-Specification_Organizator.md` (TS-001; funkcionalna cjelina Organizator / Moderator / Zahtjev za kreiranje Organizatora u okviru FT-001)

Povezana dokumentacija (Događaj):

* Technical Specification — `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (TS-003 — Događaj; verzija 0.1.1; Usvojen)

Povezana dokumentacija (Održavanje):

* Technical Specification — `docs/technical-specifications/Technical-Specification_Odrzavanje.md` (TS-004; verzija 0.1.2; Usvojen)

Povezana dokumentacija (Manifestacija):

* Business Model — BM-05 (BM-MF-01–BM-MF-20), PATCH-038–PATCH-039; PO-MF-01–PO-MF-12
* Functional Specification — §5.12 (BR-092–BR-101, BR-189–BR-205), §5.16 katalog Manifestacije, PATCH-FS-040–PATCH-FS-041
* Technical Specification — `docs/technical-specifications/Technical-Specification_Manifestacija.md` (TS-005; verzija 0.1.2; Usvojen)

Povezana dokumentacija (Lokacije):

* Business Model — BM-07 (BM-LK-01–BM-LK-12), BM-GL-13, PATCH-040, PATCH-041; PO-LOC-01–PO-LOC-07
* Functional Specification — §5.9 (BR-074–BR-080, BR-206–BR-223), PATCH-FS-042, PATCH-FS-043
* Technical Specification — `docs/technical-specifications/Technical-Specification_Lokacije.md` (TS-006; verzija 0.1.1; Usvojen)

Povezana dokumentacija (Kategorije i oznake):

* Business Model — BM-08 (BM-KO-01–BM-KO-08), BM-GL-14, BM-GL-23, PATCH-043; TS7-PO-01–TS7-PO-06
* Functional Specification — §5.10 (BR-081–BR-085, BR-224–BR-236), PATCH-FS-045
* Technical Specification — `docs/technical-specifications/Technical-Specification_Kategorije_i_oznake.md` (TS-007; verzija 0.1.0; Usvojen)

Povezana dokumentacija (Mediji):

* Business Model — BM-09 (BM-MD-01–BM-MD-17), BM-GL-15, BM-PK-12, PATCH-044; TS8-01–TS8-09
* Functional Specification — §5.11 (BR-086–BR-091, BR-237–BR-254), §5.4.4, BR-113, PATCH-FS-046
* Technical Specification — `docs/technical-specifications/Technical-Specification_Mediji.md` (TS-008; verzija 0.1.0; Usvojen)

Povezana dokumentacija (Javni portal):

* Business Model — BM-11 (BM-PK-01–BM-PK-28), BM-AR-02, PATCH-045–PATCH-048, PATCH-051 (CR-004B); IA-01, PO-TS9-03A–05B, PO-TS9-06A–06D, PO-TS9-07A–07E
* Functional Specification — §5.1–§5.4, §5.13 (BR-102–BR-117, BR-255–BR-274), PATCH-FS-047–PATCH-FS-049, PATCH-FS-051
* Technical Specification — `docs/technical-specifications/Technical-Specification_Javni_portal.md` (TS-009; verzija 1.0.5; Stable; TD-TS9-01; CR-002 §3.2; CR-003 §3.3; CR-004A §7.1; CR-004B §7.2)
* Implementation Strategy — `docs/implementation-strategies/Implementation-Strategy_Javni_portal.md` (IS-001; verzija 1.0.6; Stable)
* Change Request — CR-001 (Implemented, IS-001 Faza 1); CR-002 (Implemented, IS-001 Faza 2 — `month=YYYY-MM`; commit `c5d396f`); CR-003 (Implemented, IS-001 Faza 2 — `q`/`category`/`location`; dokumentacija `fc35132`; implementacija `595045a`; TS-009 v1.0.2; IS-001 v1.0.2); CR-004A (Implemented, IS-001 Faza 3 — javni status badge; PO-CR4A-01…05; dokumentacija `614706c`; implementacija `0f73240`; TS-009 v1.0.4; IS-001 v1.0.5); CR-004B (Planned, IS-001 Faza 3 — javni prikaz otkazanih; PO-CR4B-01…10; TS-009 v1.0.5 §7.2; IS-001 v1.0.6 §9.3.2)

Povezana dokumentacija (Newsletter):

* Business Model — BM-13 (BM-NL-01–BM-NL-25), PATCH-031–PATCH-033
* Functional Specification — §5.15 (BR-138–BR-169), PATCH-FS-032–PATCH-FS-034

Povezana dokumentacija (Urednički portal):

* Business Model — BM-12 (BM-EP-01–BM-EP-10), BM-01–BM-03 (uloge), BM-MOD-04, BM-UR-09, BM-GL-09
* Functional Specification — Platformsko pravilo; §5.14 (BR-118–BR-128); BR-007; BR-048; BR-051
* Technical Specification — `docs/technical-specifications/Technical-Specification_Urednicki_portal.md` (TS-010; verzija 0.3.1; U IZRADI)
  * TS-010.1 Osnove uredničkog portala — Dokumentaciono pripremljeno
  * TS-010.2 Organizatori — Dokumentaciono pripremljeno
  * TS-010.3 Moderator Organizatora — Dokumentaciono pripremljeno
  * TS-010.4 Workflow događaja — Planned
  * TS-010.5 CRUD događaja — Planned
  * TS-010.6 Dashboard — Planned
  * TS-010.7 Evidencija aktivnosti — Planned
  * TS-010.8 Test matrica — Planned

**Planirani Technical Specification dokumenti (modul Kalendar kulture):**

Plan koristi globalnu numeraciju (M-TS-002). Oznaka TS-002 pripada modulu Plaćanja (FT-002) i nije dio ovog plana.

| TS | Naziv | Feature | Modul | Status |
| -- | ----- | ------- | ----- | ------ |
| TS-001 | Organizator, Moderator i zahtjev za kreiranje Organizatora | FT-001 | Kalendar kulture | Usvojen (v0.2.1) |
| TS-003 | Događaj | FT-001 | Kalendar kulture | Usvojen (v0.1.1) |
| TS-004 | Održavanje događaja | FT-001 | Kalendar kulture | Usvojen (v0.1.2) |
| TS-005 | Manifestacija | FT-001 | Kalendar kulture | Usvojen (v0.1.2) |
| TS-006 | Lokacije | FT-001 | Kalendar kulture | Usvojen (v0.1.1) |
| TS-007 | Kategorije i oznake | FT-001 | Kalendar kulture | Usvojen (v0.1.0) |
| TS-008 | Mediji | FT-001 | Kalendar kulture | Usvojen (v0.1.0) |
| TS-009 | Javni portal | FT-001 | Kalendar kulture | Stable (v1.0.5) |
| TS-010 | Urednički portal | FT-001 | Kalendar kulture | U IZRADI (v0.3.1) — TS-010.1–TS-010.3 Dokumentaciono pripremljeno; TS-010.4–TS-010.8 Planned |
| TS-011 | Newsletter | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-012 | Evidencija aktivnosti | FT-003 | Kalendar kulture | Planiran — nacrt nije započet |

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

Planned

Napomena:

Centralna Evidencija aktivnosti modula Kalendar kulture — dokumentovanje poslovno značajnih radnji radi odgovornosti, kontrole i revizije. Direktan pristup: Administrator platforme. Razlikuje se od lokalnih audit tragova na entitetima.

V1 katalog (FS): Moderator ovlašćenja; Organizatori; događaji; Newsletter. Van opsega ovog feature zapisa do posebnog PATCH-a: autentikacija/platformski nalozi i uloge, detaljni Admin pregled/filteri, struktura polja zapisa, retention, izvoz, Technical Specification.

Povezana dokumentacija:

* Business Model — BM-14 (BM-AL-01–BM-AL-08), BM-EP-09, BM-GL-09, BM-GL-20
* Functional Specification — §5.16 (BR-170–BR-188), PATCH-FS-035
* Technical Specification — TS-012 Evidencija aktivnosti (planiran — nacrt nije započet; modul Kalendar kulture)

Matrica sljedivosti (sažetak):

| BM | FS | FT | TS |
|----|----|----|-----|
| BM-AL-01–BM-AL-08 | BR-170–BR-188 / §5.16 | FT-003 | TS-012 (planiran) |
| BM-EP-09 | §5.16 | FT-003 | TS-012 (planiran) |
| BM-GL-09, BM-GL-20 | BR-170, BR-174 | FT-003 | TS-012 (planiran) |

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
