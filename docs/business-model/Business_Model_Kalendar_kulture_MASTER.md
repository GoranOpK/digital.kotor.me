# Digital Kotor
# Business Model
## Modul: Kalendar kulture

**Status dokumenta:** U IZRADI
**Verzija:** 0.1

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-25 | Usvojena poglavlja BM-01 Organizator, BM-02 Moderator organizatora i BM-03 Urednik. Uspostavljena metodologija rada Business Modela modula Kalendar kulture. |
| PATCH-001 | 2026-07-25 | Revizija zaglavlja dokumenta (verzija 0.1, status U IZRADI; Status razvoja). |
| PATCH-002 | 2026-07-25 | Istorija verzija i Pravila upravljanja Business Modelom. |
| PATCH-003 | 2026-07-25 | Finalizacija metodologije rada (Upravljanje promjenama). |
| PATCH-004 | 2026-07-25 | BM-04 Događaj — USVOJENO. |
| PATCH-005 | 2026-07-25 | Finalizacija poglavlja BM-04 Događaj. |
| PATCH-006 | 2026-07-25 | BM-05 Manifestacija — USVOJENO. |
| PATCH-007 | 2026-07-25 | BM-06 Termin — USVOJENO. |
| PATCH-008 | 2026-07-25 | BM-07 Lokacija i BM-08 Kategorije i oznake — USVOJENO. |
| PATCH-009 | 2026-07-25 | BM-09 Mediji — USVOJENO. |
| PATCH-010 | 2026-07-25 | BM-10 Statusi i životni ciklus događaja — USVOJENO. Usvojeno: „Vraćen na doradu“ nije status događaja, već poslovna radnja kojom se događaj vraća iz statusa „Na odobrenju“ u status „Nacrt“. |
| PATCH-011 | 2026-07-25 | Korekcija numeracije poglavlja Korisnički portal i usklađivanje internih referenci nakon uvođenja BM-10 Statusi i životni ciklus događaja. |
| PATCH-012 | 2026-07-25 | Preimenovanje poglavlja BM-11 u „Portal Kalendara kulture“ i uklanjanje poslovnih pravila koja pripadaju platformi Digital Kotor. |
| PATCH-013 | 2026-07-25 | BM-11 Portal Kalendara kulture — USVOJENO (BM-PK-01–BM-PK-14). |
| PATCH-014 | 2026-07-25 | BM-14 Evidencija aktivnosti (Audit log) — USVOJENO (BM-AL-01–BM-AL-08). |
| PATCH-015 | 2026-07-25 | BM-13 Newsletter — USVOJENO (BM-NL-01–BM-NL-09); uklonjene ranije rezervacije BM-13 Poslovna obavještenja i BM-13.1 Newsletter. |
| PATCH-016 | 2026-07-26 | BM-15 Opšta poslovna pravila — USVOJENO (BM-GR-01–BM-GR-07). |
| PATCH-017 | 2026-07-26 | BM-16 Rječnik poslovnih pojmova — USVOJENO (BM-GL-01–BM-GL-21). |
| PATCH-018 | 2026-07-26 | BM-17 Arhitektura poslovnih cjelina — USVOJENO (BM-AR-01–BM-AR-08). |
| PATCH-019 | 2026-07-26 | BM-12 Urednički portal — USVOJENO (BM-EP-01–BM-EP-10). |
| PATCH-020 | 2026-07-26 | Dopuna BM-01/BM-02: tok „Postani organizator“ (podaci Organizatora i prvog Moderatora, odobrenje Urednika, verifikacija Moderatora); napomena da funkcionalnost još nije implementirana; pojašnjenje da Moderator nije Urednik; BM-MD-06 — podrazumijevana naslovna fotografija kategorije. |
| PATCH-021 | 2026-07-26 | Usklađivanje sa izuzetkom za događaje bez registrovanog Organizatora (javni interes): dopuna BM-01/BM-03/BM-04; usklađeni BM-ORG-04 napomena, BM-UR-06, BM-UR-07 i BM-DG-08. |
| PATCH-022 | 2026-07-26 | Konačni model upravljanja Moderatorima: podnosilac zahtjeva postaje prvi Moderator; naredne Moderatore predlažu postojeći Moderatori; ovlašćenja dodjeljuje isključivo Urednik; trajni audit zahtjeva. |
| PATCH-023 | 2026-07-26 | Terminološka migracija: postojeći poslovni koncept „Termin“, koji je predstavljao pojedinačno održavanje događaja, preimenovan je u „Održavanje događaja“. Pojam „Termin“ sužen je na datum i vrijeme održavanja. Poslovna logika nije promijenjena. Zahvaćeni: BM-04, BM-05, BM-06, BM-07, BM-10, BM-11, BM-12, BM-16 (uključujući BM-GL-22). Oznake BM-TR-* zadržane kao istorijske oznake pravila. |
| PATCH-024 | 2026-07-26 | Usvojeno: Datum održavanja je obavezan, a vrijeme može biti definisano. Za cjelodnevni događaj definiše se samo datum održavanja. Usklađeni BM-TR-01, BM-TR-03, BM-TR-05, BM-GL-12, BM-GL-22, BM-PK-09, BM-16, dijagrami i formulacije termina u BM-04/BM-06. |
| PATCH-025 | 2026-07-26 | BM-11 Portal: obavezna registracija za korišćenje portala uz zadržavanje domena identiteta na platformi (BM-PK-02); uklonjeno sortiranje iz BM-PK-07; dodato BM-PK-15 Istaknuti događaj. BM-AR-02 zadržan bez izmjene. |
| PATCH-026 | 2026-07-26 | BM-06: definisan kompletan poslovni workflow statusa „Odgođen“ za održavanje događaja (BM-TR-10 usklađen; dodati BM-TR-12–BM-TR-15). |
| PATCH-027 | 2026-07-27 | Definisana poslovna ovlašćenja za upravljanje statusima održavanja (Planiran, Odgođen, Otkazan) u skladu sa modelom uloga. Dodati BM-TR-16–BM-TR-18; usklađen BM-TR-08. |
| PATCH-028 | 2026-07-27 | Terminološko usklađivanje: u BM-EP-03 i BM-AL-07 usvojen termin „entitet“ umjesto ranijeg neusklađenog naziva. Poslovna logika nije mijenjana. |
| PATCH-029 | 2026-07-27 | Model korisnika: Organizator = poslovni entitet (nije uloga/korisnik); zahtjev za kreiranje Organizatora sa predloženim početnim Moderatorom; Urednik isključiva uloga; BM-MOD-03/04 usklađeni (aktivni kontekst Organizatora). |
| PATCH-030 | 2026-07-27 | Ulaznice i cijena van poslovnog opsega V1: BM-TR-11 — upravljanje informacijama o ulaznicama i cijeni nije dio opsega V1; uklonjene reference u BM-06 konceptu i BM-16 terminološkim pravilima. |
| PATCH-031 | 2026-07-27 | BM-13 Newsletter: model zasnovan na novoobjavljenim događajima (objavljivanje = okidač; periodična provjera; objedinjavanje; bez fiksnog sedmičnog perioda). Usklađeni BM-NL-01, BM-NL-06, BM-NL-07, BM-NL-09; dodati BM-NL-10–BM-NL-16. |
| PATCH-032 | 2026-07-27 | BM-13 Newsletter: poslovno značajne promjene kao dodatni okidači (otkazivanje, odlaganje, promjena datuma/vremena/lokacije); prioritetna obavještenja; publika = pretplatnici kojima je događaj već poslat. Usklađeni BM-NL-01, BM-NL-06, BM-NL-07, BM-NL-14, BM-NL-16; dodati BM-NL-17–BM-NL-21. |
| PATCH-033 | 2026-07-27 | BM-13 Newsletter: višestruke poslovno značajne promjene → jedinstveno obavještenje sa posljednjim važećim stanjem; objedinjavanje prioritetnih obavještenja uz blagovremenost; zabrana kontradiktornih poruka u istom ciklusu. Usklađeni BM-NL-06, BM-NL-20; dodati BM-NL-22–BM-NL-25. |
| PATCH-034 | 2026-07-28 | Nova poslovna odluka za deaktivaciju Organizatora: Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. Usklađeni BM-ORG-12 i BM-UR-10. |
| PATCH-035 | 2026-07-28 | Ovlašćenja za otkazivanje i ponovnu objavu događaja (N-DG-01): Moderator može otkazati objavljeni događaj svog Organizatora; Urednik može otkazati bilo koji objavljeni događaj; isključivo Urednik može ponovo objaviti otkazani događaj (nije automatski; može ažurirati podatke prije objave). Usklađeni BM-DG-05, BM-DG-09, BM-ST-07, BM-MOD-16, BM-UR-11. |
| PATCH-036 | 2026-07-28 | Korekcija otkazivanja nakon deaktivacije Organizatora: deaktivacijom prestaje moderatorski kontekst; Moderator više ne izvršava poslovne radnje nad događajima tog Organizatora; otkazivanje događaja deaktiviranog Organizatora isključivo Urednik. Usklađeni BM-ORG-12, BM-DG-05, BM-ST-07, BM-MOD-16. |
| PATCH-037 | 2026-07-29 | PO-DG-05: direktna objava Urednika isključivo za događaj bez Organizatora (usklađen BM-ST-04). PO-DG-06: otkazani događaj automatski prelazi u Arhiviran nakon završetka svih održavanja (usklađeni BM-DG-04, BM-ST-08). Zatvoreni N-DG-05 i N-DG-06. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (PATCH-001, PATCH-002...),
- datum,
- kratak naziv,
- kratak opis izmjene.

Naziv PATCH-a predstavlja zvanični naziv izmjene i koristi se u istoriji verzija.

---

## Svrha dokumenta

Dokument predstavlja referentni poslovni model za planiranje, razvoj, testiranje i održavanje sistema.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| BM-01 Organizator | USVOJENO |
| BM-02 Moderator organizatora | USVOJENO |
| BM-03 Urednik | USVOJENO |
| BM-04 Događaj | USVOJENO |
| BM-05 Manifestacija | USVOJENO |
| BM-06 Održavanje događaja | USVOJENO |
| BM-07 Lokacija | USVOJENO |
| BM-08 Kategorije i oznake | USVOJENO |
| BM-09 Mediji | USVOJENO |
| BM-10 Statusi i životni ciklus događaja | USVOJENO |
| BM-11 Portal Kalendara kulture | USVOJENO |
| BM-12 Urednički portal | USVOJENO |
| BM-13 Newsletter | USVOJENO |
| BM-14 Evidencija aktivnosti (Audit log) | USVOJENO |
| BM-15 Opšta poslovna pravila | USVOJENO |
| BM-16 Rječnik poslovnih pojmova | USVOJENO |
| BM-17 Arhitektura poslovnih cjelina | USVOJENO |

---

# Pravila upravljanja Business Modelom

1. Business Model predstavlja zvaničnu poslovnu specifikaciju modula Kalendar kulture.

2. Posljednja usvojena verzija Business Modela predstavlja jedini izvor istine (Single Source of Truth).

3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu odluku ili usvojenu izmjenu dokumenta.

4. Kompletan Business Model generiše se isključivo na izričit zahtjev.

5. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno prepisivati, preformulisati ili reorganizovati usvojeni sadržaj.

6. Ako postoji razlika između implementacije sistema i Business Modela, implementacija se usklađuje sa Business Modelom, osim ako se poslovnom odlukom ne izmijeni sam Business Model.

---

# Upravljanje promjenama

Svaka izmjena Business Modela mora biti rezultat usvojene poslovne odluke i evidentirana kroz odgovarajući PATCH.

---

## Sadržaj

1. Uvod
2. Svrha
3. Ciljevi
4. Opseg
5. Poslovni principi
6. Poslovni model
   - BM-01 Organizator
   - BM-02 Moderator organizatora
   - BM-03 Urednik
   - BM-04 Događaj
   - BM-05 Manifestacija
   - BM-06 Održavanje događaja
   - BM-07 Lokacija
   - BM-08 Kategorije i oznake
   - BM-09 Mediji
   - BM-10 Statusi i životni ciklus događaja
   - BM-11 Portal Kalendara kulture
   - BM-12 Urednički portal
   - BM-13 Newsletter
   - BM-14 Evidencija aktivnosti (Audit log)
   - BM-15 Opšta poslovna pravila
   - BM-16 Rječnik poslovnih pojmova
   - BM-17 Arhitektura poslovnih cjelina
7. Završne odredbe

---

# 1. Uvod

Business Model definiše poslovna pravila, poslovne entitete, korisničke uloge i način funkcionisanja modula Kalendar kulture. Dokument predstavlja osnov za izradu funkcionalne i tehničke specifikacije.

---

# 2. Svrha

Dokument predstavlja referentni poslovni model za planiranje, razvoj, testiranje i održavanje sistema.

---

# 3. Ciljevi

Definisati poslovna pravila

Definisati korisničke uloge

Definisati poslovne entitete

Obezbijediti osnov za funkcionalnu i tehničku specifikaciju.

---

# 4. Opseg

Dokument opisuje poslovna pravila i poslovne procese, bez definisanja tehničke implementacije.

---

# 5. Poslovni principi

Poglavlje još nije definisano.

---

# 6. Poslovni model

# BM-01 Organizator

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Organizator definiše poslovni entitet koji je nosilac sadržaja u modulu Kalendar kulture, način njegovog kreiranja kroz zahtjev registrovanog korisnika, odnos prema Moderatorima i Uredniku, te pravila da Organizator nije korisnik sistema niti korisnička uloga.

## 2. Poslovni opis

Organizator je poslovni entitet u okviru Kalendara kulture i nosilac sadržaja.

Organizator nije korisnik sistema i nije korisnička uloga. Organizator nema korisnički nalog na osnovu statusa Organizatora, ne prijavljuje se u sistem, ne pristupa portalu kao Organizator, ne izvršava neposredno radnje u sistemu i nema sopstvenu korisničku sesiju.

Organizator može predstavljati, između ostalog: ustanovu, preduzeće, udruženje, nevladinu organizaciju, neformalnu grupu, fizičko lice koje organizuje događaje ili drugi subjekt koji se pojavljuje kao organizator događaja.

Radnje u ime Organizatora izvršava jedan ili više registrovanih korisnika koji imaju ovlašćenje Moderatora za tog Organizatora.

## 3. Poslovni koncept

Registrovani korisnik Digital Kotor može u modulu Kalendar kulture podnijeti zahtjev za kreiranje Organizatora.

Podnošenjem zahtjeva korisnik ne postaje Organizator, ne dobija automatski novu korisničku ulogu, ne postaje automatski Moderator i ne postaje vlasnik Organizatora. Korisnik samo inicira postupak kreiranja novog entiteta Organizatora.

Tok procesa:

1. Registrovani korisnik pokreće zahtjev za kreiranje Organizatora (iniciranje zahtjeva).
2. Zahtjev sadrži podatke o predloženom Organizatoru kao poslovnom entitetu, podatke potrebne za identifikovanje predloženog početnog Moderatora i podatak da li je predloženi Moderator sam podnosilac ili drugi registrovani korisnik.
3. Zahtjev se šalje Uredniku.
4. Urednik pregleda i odobrava ili odbija zahtjev.
5. Ako Urednik odobri zahtjev:

   * kreira se, odnosno odobrava se novi entitet Organizatora;
   * predloženi korisnik dobija ovlašćenje Moderatora za tog konkretnog Organizatora;
   * uspostavlja se poslovna veza između Moderatora i Organizatora.
6. Ako Urednik odbije zahtjev:

   * Organizator se ne odobrava kao aktivan poslovni entitet;
   * predloženi korisnik ne dobija moderatorska ovlašćenja;
   * podnosilac zahtjeva ne dobija novu ulogu niti druga posebna prava.

Podnosilac zahtjeva i predloženi Moderator mogu biti ista osoba ili dvije različite osobe. Podnosilac može sebe predložiti za Moderatora, ali to nije obavezno. Samo podnošenje zahtjeva ne daje moderatorska ovlašćenja ni podnosiocu ni predloženom korisniku.

Jedan registrovani korisnik može podnijeti zahtjev za kreiranje neograničenog broja Organizatora. Svaki zahtjev predstavlja poseban postupak i Urednik ga razmatra nezavisno.

Operativno upravljanje sadržajem u ime Organizatora obavljaju Moderatori. Organizator ne pristupa uredničkom portalu. Moderatori ne mogu samostalno objaviti sadržaj.

Svaki naredni Moderator može biti predložen isključivo od strane postojećeg aktivnog Moderatora povezanog sa tim Organizatorom. Moderator ne dodjeljuje ovlašćenja; samo podnosi zahtjev. Pristup i ovlašćenja novom Moderatoru dodjeljuje isključivo Urednik nakon odobrenja.

Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. Deaktivacijom Organizatora prestaje moderatorski kontekst za tog Organizatora; Moderatori više ne izvršavaju poslovne radnje nad njegovim događajima.

**Napomena o implementaciji:** Zahtjev za kreiranje Organizatora i upravljanje Moderatorima usvojeni su kao dio poslovnog modela, ali trenutno još nisu implementirani u aplikaciji.

**Napomena o nazivu:** Raniji naziv funkcionalnosti „Postani organizator“ zamijenjen je poslovno preciznijim nazivom „zahtjev za kreiranje Organizatora“.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-ORG-01 | Organizator je poslovni entitet i nosilac sadržaja u Kalendaru kulture. Organizator nije korisnik sistema i nije korisnička uloga. |
| BM-ORG-02 | Registrovani korisnik podnosi zahtjev za kreiranje Organizatora. Podnošenjem zahtjeva korisnik ne postaje Organizator, ne postaje Moderator i ne dobija novu korisničku ulogu. |
| BM-ORG-03 | Nakon odobrenja Urednika kreira se, odnosno odobrava se novi entitet Organizatora. Odobrenje ne dodjeljuje podnosiocu status korisničke uloge Organizatora. |
| BM-ORG-04 | Organizator je nosilac sadržaja. Operativno kreiranje, uređivanje i čuvanje nacrta sadržaja, kao i slanje sadržaja Uredniku na odobravanje, obavljaju Moderatori u ime Organizatora. Ovo pravilo ne isključuje izuzetak da Urednik može kreirati i objaviti događaj bez registrovanog Organizatora radi javnog interesa i pravovremenog informisanja građana, u skladu sa BM-UR-06 i BM-DG-08. |
| BM-ORG-05 | Moderator ne može samostalno objaviti sadržaj. |
| BM-ORG-06 | Organizator ima jednog ili više Moderatora koji upravljaju sadržajem u njegovo ime. Organizator ne dodjeljuje ovlašćenja Moderatorima. |
| BM-ORG-07 | Zahtjev za kreiranje Organizatora sadrži podatke o predloženom Organizatoru, podatke potrebne za identifikovanje predloženog početnog Moderatora i podatak da li je predloženi Moderator sam podnosilac ili drugi registrovani korisnik. Podnosilac i predloženi Moderator mogu biti ista ili različite osobe. |
| BM-ORG-08 | Nakon odobrenja zahtjeva za kreiranje Organizatora, predloženi korisnik dobija ovlašćenje početnog Moderatora za tog Organizatora. Moderatorska ovlašćenja nastaju tek nakon odobrenja Urednika. |
| BM-ORG-09 | Sistem trajno evidentira za zahtjev za kreiranje Organizatora: podnosioca zahtjeva, predloženog Moderatora, datum i vrijeme podnošenja, Urednika koji je odlučio i datum i vrijeme odluke. |
| BM-ORG-10 | Jedan registrovani korisnik može podnijeti zahtjev za kreiranje neograničenog broja Organizatora. Svaki zahtjev predstavlja poseban postupak. |
| BM-ORG-11 | Ako Urednik odbije zahtjev, Organizator se ne odobrava kao aktivan poslovni entitet, predloženi korisnik ne dobija moderatorska ovlašćenja, a podnosilac ne dobija novu ulogu. Odbijanje ne sprečava podnošenje novog zahtjeva. |
| BM-ORG-12 | Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. Deaktivacijom Organizatora prestaje moderatorski kontekst za tog Organizatora. Nakon deaktivacije Moderatori više nemaju pravo izvršavanja poslovnih radnji nad događajima tog Organizatora. Ako je potrebno otkazati događaj deaktiviranog Organizatora, tu radnju izvršava isključivo Urednik. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Moderator organizatora** — Moderatori upravljaju sadržajem u ime Organizatora. Obaveza da Organizator ima najmanje jednog aktivnog Moderatora definisana je u BM-02.
- **Urednik** — odobrava ili odbija zahtjev za kreiranje Organizatora i dodjeljuje ovlašćenja Moderatorima; odobrava i objavljuje sadržaj.
- **Događaj** — događaj se vodi u ime Organizatora. Izuzetno, Urednik može kreirati i objaviti događaj bez registrovanog Organizatora radi javnog interesa; naknadno povezivanje sa registrovanim Organizatorom uređeno je u BM-03 i BM-04.
- **Manifestacija** — Organizator može biti povezan sa Manifestacijama u skladu sa poslovnim pravilima Manifestacije.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-02 Moderator organizatora

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Moderator organizatora definiše korisničku ulogu, odnosno poslovno ovlašćenje registrovanog korisnika da postupa u ime konkretnog Organizatora, obim njegovih ovlašćenja, pravila o broju Moderatora, postupak predlaganja i odobravanja novih Moderatora te postupak njihovog uklanjanja.

## 2. Poslovni opis

Moderator organizatora je poslovna uloga registrovanog korisnika koja upravlja sadržajem u modulu Kalendar kulture u ime konkretnog Organizatora.

Moderator organizatora **nije** Urednik i **nije** nosilac sadržaja. Moderator nije Organizator. Moderator je operativni korisnik ovlašćen da izvršava radnje u ime Organizatora.

## 3. Poslovni koncept

Moderator organizatora ne postaje nosilac sadržaja. Sadržaj koji Moderator organizatora kreira ili uređuje vodi se u ime Organizatora.

Moderator organizatora može obavljati operativne radnje nad sadržajem u ime Organizatora, osim samostalne objave sadržaja i osim ponovne objave otkazanog događaja. To obuhvata kreiranje događaja, uređivanje događaja, otkazivanje objavljenog događaja dok je Organizator aktivan i dok postoji aktivni moderatorski kontekst, upravljanje manifestacijama, čuvanje nacrta i slanje sadržaja Uredniku na odobravanje. Deaktivacijom Organizatora moderatorski kontekst prestaje. Sadržaj koji Moderator organizatora kreira ili uređuje mora biti poslat Uredniku na odobravanje prije objave.

Jedan korisnik može biti Moderator organizatora za jednog ili više Organizatora. Pri svakoj radnji Moderator postupa u kontekstu konkretnog Organizatora (aktivni kontekst Organizatora).

Organizator može imati jednog ili više Moderatora organizatora i mora imati najmanje jednog aktivnog Moderatora organizatora.

**Početni Moderator:** Predloženi korisnik iz odobrenog zahtjeva za kreiranje Organizatora, nakon odobrenja Urednika, dobija ovlašćenje početnog Moderatora tog Organizatora. Predloženi Moderator može biti podnosilac zahtjeva ili drugi registrovani korisnik.

**Naredni Moderatori:** Svaki naredni Moderator može biti predložen isključivo od strane postojećeg aktivnog Moderatora povezanog sa tim Organizatorom (iniciranje zahtjeva). Moderator ne dodjeljuje ovlašćenja; samo podnosi zahtjev. Pristup i ovlašćenja novom Moderatoru dodjeljuje isključivo Urednik nakon pregleda i odobrenja (odobravanje zahtjeva i dodjela ovlašćenja). Tek nakon odobrenja Urednika novi Moderator postaje aktivan.

Moderator organizatora može pokrenuti postupak uklanjanja drugog Moderatora organizatora istog Organizatora, a uklanjanje odobrava Urednik.

Za zahtjeve vezane za Moderatore sistem trajno evidentira: podnosioca zahtjeva, datum i vrijeme podnošenja, Urednika koji je odobrio i datum i vrijeme odobrenja.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-MOD-01 | Moderator organizatora upravlja sadržajem u ime Organizatora. |
| BM-MOD-02 | Jedan korisnik može biti Moderator organizatora za jednog ili više Organizatora. |
| BM-MOD-03 | Podnosilac zahtjeva za kreiranje Organizatora može, ali ne mora, biti predložen kao početni Moderator. Podnosilac i predloženi Moderator mogu biti ista ili različite osobe. |
| BM-MOD-04 | Kada je Moderator povezan sa više Organizatora, pri svakoj radnji postupa u kontekstu konkretnog Organizatora. Sistem primjenjuje ovlašćenja i pripadnost sadržaja u skladu sa tim aktivnim kontekstom Organizatora. |
| BM-MOD-05 | Moderator organizatora može obavljati operativne radnje nad sadržajem u ime Organizatora, osim samostalne objave sadržaja. |
| BM-MOD-06 | Sadržaj koji kreira ili uređuje Moderator organizatora mora biti poslat Uredniku na odobravanje prije objave. |
| BM-MOD-07 | Organizator mora imati najmanje jednog aktivnog Moderatora organizatora. |
| BM-MOD-08 | Moderator organizatora može pokrenuti postupak uklanjanja drugog Moderatora organizatora istog Organizatora. |
| BM-MOD-09 | Moderator organizatora smatra se uklonjenim tek nakon odobrenja Urednika. |
| BM-MOD-10 | Sistem neće dozvoliti uklanjanje posljednjeg aktivnog Moderatora organizatora. |
| BM-MOD-11 | Moderator organizatora nije Urednik; urednička ovlašćenja se ne prenose ulozi Moderatora. Moderator nije Organizator. |
| BM-MOD-12 | Početni Moderator je predloženi korisnik iz odobrenog zahtjeva za kreiranje Organizatora. Ovlašćenja dobija tek nakon odobrenja Urednika. |
| BM-MOD-13 | Svaki naredni Moderator može biti predložen isključivo od strane postojećeg aktivnog Moderatora povezanog sa tim Organizatorom. Moderator ne dodjeljuje ovlašćenja; samo podnosi zahtjev. |
| BM-MOD-14 | Pristup i ovlašćenja novom Moderatoru dodjeljuje isključivo Urednik nakon pregleda i odobrenja zahtjeva. Tek nakon odobrenja Moderator postaje aktivan. |
| BM-MOD-15 | Sistem trajno evidentira za zahtjeve vezane za Moderatore: podnosioca zahtjeva, datum i vrijeme podnošenja, Urednika koji je odobrio i datum i vrijeme odobrenja. |
| BM-MOD-16 | Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje. Deaktivacijom Organizatora moderatorski kontekst prestaje i Moderator više nema pravo otkazivanja događaja tog Organizatora. Moderator ne može ponovo objaviti otkazani događaj. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Organizator** — ostaje nosilac sadržaja kao poslovni entitet; Moderatori upravljaju sadržajem u njegovo ime.
- **Urednik** — pregleda i odobrava sadržaj koji Moderator pošalje na odobravanje; odobrava zahtjeve za dodjelu i uklanjanje Moderatora te dodjeljuje ovlašćenja.
- **Događaj** — Moderator organizatora kreira, uređuje i može otkazati objavljeni događaj u ime aktivnog Organizatora u aktivnom kontekstu; nakon deaktivacije Organizatora nema pravo poslovnih radnji nad njegovim događajima; ne može samostalno objaviti niti ponovo objaviti otkazani događaj.
- **Lokacija** — Moderator organizatora može predlagati nove lokacije u skladu sa poslovnim pravilima Lokacije.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-03 Urednik

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Urednik definiše isključivu administrativnu ulogu Uredničkog portala Kalendara kulture: odobravanje ili odbijanje zahtjeva za kreiranje Organizatora, dodjelu ovlašćenja Moderatorima, urednički pregled, odobravanje i objavu sadržaja, otkazivanje bilo kojeg objavljenog događaja, ponovnu objavu otkazanog događaja, te odobravanje uklanjanja Moderatora organizatora.

## 2. Poslovni opis

Urednik je administrator Uredničkog portala Kalendara kulture. Urednik nije običan registrovani korisnik javnog portala i ne koristi funkcionalnosti namijenjene običnim registrovanim korisnicima.

Urednik nije Organizator i nije Moderator Organizatora. Uloga Urednika je isključiva unutar poslovnog modela Kalendara kulture: Urednik nema kombinaciju uloge Urednika sa ulogom Moderatora niti sa statusom običnog registrovanog korisnika, ne mijenja aktivnu poslovnu ulogu i uvijek postupa u svojstvu Urednika.

Urednik odobrava ili odbija zahtjeve za kreiranje Organizatora, odobrava zahtjeve za dodjelu ovlašćenja novim Moderatorima, pregleda, uređuje, odobrava i objavljuje događaje, vraća ih na doradu kada su potrebne suštinske izmjene, može otkazati bilo koji objavljeni događaj, isključivo on može ponovo objaviti otkazani događaj i odobrava uklanjanje Moderatora organizatora.

Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora.

## 3. Poslovni koncept

Urednik obezbjeđuje kvalitet i dosljednost javno objavljenog sadržaja kroz pregled, uređivanje, vraćanje na doradu, odobravanje i objavljivanje događaja. Sadržaj koji pošalju Moderatori Urednik pregleda i odobrava prije objave. Objavu sadržaja vrši isključivo Urednik.

Urednik koristi isključivo Urednički portal u okviru svojih poslovnih ovlašćenja i ne podnosi zahtjeve kao običan registrovani korisnik.

Urednik je isključivo ovlašćen da dodijeli pristup i ovlašćenja novom Moderatoru nakon pregleda i odobrenja zahtjeva.

Urednik može kreirati događaj bez registrovanog Organizatora kada je to potrebno radi pravovremenog informisanja građana i ostvarivanja javnog interesa. Po registraciji Organizatora događaj se može naknadno povezati sa Organizatorom. Naknadno povezivanje predstavlja administrativnu dopunu podataka i ne smije mijenjati audit, istoriju događaja niti javno objavljene verzije.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-UR-01 | Urednik odobrava ili odbija zahtjev za kreiranje Organizatora. |
| BM-UR-02 | Urednik pregleda, uređuje, odobrava i objavljuje događaje. |
| BM-UR-03 | Urednik vraća događaje na doradu kada su potrebne suštinske izmjene. |
| BM-UR-04 | Urednik pregleda i odobrava sadržaj koji šalju Moderatori. |
| BM-UR-05 | Urednik odobrava uklanjanje Moderatora organizatora. |
| BM-UR-06 | Urednik može kreirati događaj bez registrovanog Organizatora kada je to potrebno radi pravovremenog informisanja građana i ostvarivanja javnog interesa. |
| BM-UR-07 | Po registraciji Organizatora, događaj kreiran bez registrovanog Organizatora može se naknadno povezati sa tim Organizatorom. Naknadno povezivanje ne smije mijenjati audit, istoriju događaja niti javno objavljene verzije i predstavlja administrativnu dopunu podataka. |
| BM-UR-08 | Urednik odobrava zahtjeve za dodjelu ovlašćenja novim Moderatorima i isključivo on dodjeljuje pristup novom Moderatoru. |
| BM-UR-09 | Urednik je isključiva uloga Uredničkog portala. Urednik nije Organizator, nije Moderator Organizatora, ne kombinuje ulogu Urednika sa statusom običnog registrovanog korisnika u poslovnom modelu Kalendara kulture, ne mijenja aktivnu poslovnu ulogu i uvijek postupa kao Urednik. |
| BM-UR-10 | Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. |
| BM-UR-11 | Urednik može otkazati bilo koji objavljeni događaj. Isključivo Urednik može ponovo objaviti otkazani događaj. Prije ponovne objave Urednik provjerava i, po potrebi, ažurira podatke događaja i povezanih održavanja koristeći postojeća ovlašćenja. Ponovna objava nije automatska. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Organizator** — Urednik odobrava ili odbija zahtjev za kreiranje Organizatora, može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora i može naknadno povezati događaj kreiran bez registrovanog Organizatora sa tim Organizatorom, u skladu sa BM-UR-07.
- **Moderator organizatora** — Urednik pregleda i odobrava sadržaj koji Moderator pošalje na odobravanje; odobrava zahtjeve za dodjelu i uklanjanje Moderatora te dodjeljuje ovlašćenja.
- **Događaj** — Urednik pregleda, uređuje, odobrava, objavljuje i vraća na doradu događaje, može otkazati bilo koji objavljeni događaj, isključivo on ponovo objavljuje otkazani događaj, a u propisanim slučajevima može i kreirati događaj.
- **Lokacija** — Urednik odobrava ili odbija nove lokacije predložene za zajednički katalog lokacija.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-04 Događaj

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Događaj definiše osnovnu programsku cjelinu Kalendara kulture, njena osnovna svojstva, odnos prema održavanjima događaja, manifestaciji, organizatoru, kategoriji i lokaciji, te pravila arhiviranja i otkazivanja.

## 2. Poslovni opis

Događaj predstavlja osnovnu programsku cjelinu Kalendara kulture koja opisuje kulturni sadržaj. Događaj može imati jedno ili više održavanja.

## 3. Poslovni koncept

```text
Događaj
    │
    └── ima jedno ili više održavanja
            ├── ima termin (Datum održavanja je obavezan, a vrijeme može biti definisano.)
            ├── može imati lokaciju
            └── može imati status i druga svojstva
```

Događaj može biti kreiran bez definisanog održavanja isključivo dok se nalazi u statusu Nacrt. Za slanje događaja na odobrenje mora biti definisano najmanje jedno održavanje. Objavljeni događaj uvijek mora imati najmanje jedno održavanje.

Događaj može biti samostalan ili biti dio jedne manifestacije. Pripadnost manifestaciji nije obavezna.

Lokacija nije svojstvo događaja već svojstvo održavanja događaja. Svako održavanje može imati svoju lokaciju.

Događaj pripada jednoj primarnoj kategoriji. Dodatna klasifikacija događaja može se vršiti korišćenjem oznaka (tagova). Događaj može biti sačuvan kao nacrt bez izabrane primarne kategorije. Za slanje događaja na odobrenje mora biti izabrana jedna primarna kategorija. Svaki objavljeni događaj mora imati jednu primarnu kategoriju.

Svaki događaj mora biti povezan sa tačno jednim Organizatorom. Izuzetno, ako Organizator nije registrovan u sistemu, Urednik može kreirati i objaviti događaj bez registrovanog Organizatora radi ostvarivanja javnog interesa i pravovremenog informisanja građana, u skladu sa BM-03 Urednik. Po registraciji Organizatora događaj se može naknadno povezati sa Organizatorom kao administrativna dopuna podataka, bez izmjene audita, istorije događaja i javno objavljenih verzija.

Nakon završetka svih održavanja sistem automatski arhivira događaj, bez obzira da li je događaj u statusu Objavljen ili Otkazan. Arhiviranje se ne izvršava ručno.

Događaj može biti otkazan. Otkazani događaj ostaje evidentiran u sistemu i dobija status „Otkazan“.

Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje. Deaktivacijom Organizatora moderatorski kontekst prestaje; Moderator tada više nema pravo otkazivanja događaja tog Organizatora. Urednik može otkazati bilo koji objavljeni događaj, uključujući događaje deaktiviranog Organizatora.

Ponovno objavljivanje otkazanog događaja predstavlja uredničku radnju. Isključivo Urednik može ponovo objaviti otkazani događaj. Prije ponovne objave Urednik može provjeriti i ažurirati podatke događaja i povezanih održavanja. Ponovna objava nije automatska.

Pojedinačno održavanje događaja može biti otkazano bez uticaja na ostala održavanja istog događaja.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-DG-01 | Događaj može biti kreiran bez definisanog održavanja isključivo dok se nalazi u statusu Nacrt. Za slanje događaja na odobrenje mora biti definisano najmanje jedno održavanje. Objavljeni događaj uvijek mora imati najmanje jedno održavanje. |
| BM-DG-02 | Događaj može biti samostalan ili biti dio jedne manifestacije. Pripadnost manifestaciji nije obavezna. Detaljna pravila definišu se u BM-05 Manifestacija. |
| BM-DG-03 | Lokacija nije svojstvo događaja već svojstvo održavanja događaja. Svako održavanje može imati svoju lokaciju. Detaljna pravila definišu se u BM-07 Lokacija. |
| BM-DG-04 | Nakon završetka svih održavanja sistem automatski arhivira događaj. Automatsko arhiviranje primjenjuje se na događaj u statusu Objavljen i na događaj u statusu Otkazan. Arhiviranje se ne izvršava ručno. Detaljna pravila prikaza arhive definišu se u BM-11 Portal Kalendara kulture. |
| BM-DG-05 | Događaj može biti otkazan. Otkazani događaj ostaje evidentiran u sistemu i dobija status „Otkazan“. Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje. Deaktivacijom Organizatora moderatorski kontekst prestaje i Moderator više nema pravo otkazivanja događaja tog Organizatora; otkazivanje događaja deaktiviranog Organizatora izvršava isključivo Urednik. Urednik može otkazati bilo koji objavljeni događaj. Pojedinačno održavanje događaja može biti otkazano bez uticaja na ostala održavanja istog događaja. Detaljna pravila za održavanja definišu se u BM-06 Održavanje događaja. |
| BM-DG-06 | Događaj pripada jednoj primarnoj kategoriji. Dodatna klasifikacija događaja može se vršiti korišćenjem oznaka (tagova). Detaljna pravila o kategorijama i oznakama (tagovima) definišu se u BM-08 Kategorija. |
| BM-DG-07 | Događaj može biti sačuvan kao nacrt bez izabrane primarne kategorije. Za slanje događaja na odobrenje mora biti izabrana jedna primarna kategorija. Svaki objavljeni događaj mora imati jednu primarnu kategoriju. |
| BM-DG-08 | Svaki događaj mora biti povezan sa tačno jednim Organizatorom. Izuzetno, ako Organizator nije registrovan u sistemu, Urednik može kreirati i objaviti događaj bez registrovanog Organizatora radi javnog interesa i pravovremenog informisanja građana, u skladu sa BM-UR-06. Po registraciji Organizatora događaj se može naknadno povezati sa Organizatorom u skladu sa BM-UR-07, bez izmjene audita, istorije događaja i javno objavljenih verzija. |
| BM-DG-09 | Ponovno objavljivanje otkazanog događaja predstavlja uredničku radnju. Isključivo Urednik može ponovo objaviti otkazani događaj, dok je događaj još u statusu Otkazan. Prije ponovne objave Urednik provjerava i, po potrebi, ažurira podatke događaja i povezanih održavanja koristeći postojeća ovlašćenja. Ponovna objava nije automatska. Moderator ne može ponovo objaviti otkazani događaj. |

## 5. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-05 Manifestacija

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Manifestacija definiše njena osnovna svojstva, odnos prema Događajima i njihovim održavanjima, način određivanja trajanja, te pravila uređivanja, odobravanja, otkazivanja i arhiviranja.

## 2. Poslovni opis

Manifestacija predstavlja zasebnu programsku cjelinu Kalendara kulture koja pod zajedničkim nazivom, identitetom i programskim okvirom objedinjuje jedan ili više povezanih Događaja.

## 3. Poslovni koncept

Manifestacija može biti kreirana bez Događaja isključivo dok se nalazi u statusu Nacrt. Za slanje Manifestacije na odobrenje mora sadržati najmanje jedan Događaj. Objavljena Manifestacija mora sadržati najmanje jedan Događaj.

Manifestacija može sadržati jedan ili više Događaja. Jedan Događaj može pripadati najviše jednoj Manifestaciji, a pripadnost Događaja Manifestaciji nije obavezna.

Manifestacija nema sopstvena održavanja. Održavanja imaju isključivo Događaji koji pripadaju Manifestaciji.

Početak Manifestacije određuje se najranijim terminom svih održavanja Događaja koji joj pripadaju, a završetak posljednjim terminom svih održavanja Događaja koji joj pripadaju. Trajanje Manifestacije sistem određuje automatski na osnovu termina održavanja Događaja.

Manifestacija se automatski arhivira nakon završetka posljednjeg održavanja posljednjeg Događaja koji joj pripada. Arhiviranje se ne izvršava ručno.

Manifestacija može biti otkazana. Otkazana Manifestacija ostaje evidentirana u sistemu i dobija status „Otkazana“. Otkazivanje Manifestacije ne briše njene Događaje. Pravila za status Događaja uređuju se u skladu sa poslovnim pravilima definisanim u BM-04 Događaj.

Manifestacija predstavlja samostalnu programsku cjelinu i ima sopstvene podatke, uključujući naziv, opis, naslovnu fotografiju i ostale pripadajuće informacije. Manifestacija ne nasljeđuje ove podatke od Događaja koji joj pripadaju.

Manifestaciju može kreirati Moderator organizatora u ime svog Organizatora. Urednik može kreirati Manifestaciju u ime bilo kojeg Organizatora ili bez registrovanog Organizatora, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik.

Manifestacija može biti sačuvana u statusu Nacrt. Dok se nalazi u statusu Nacrt, može se slobodno uređivati. Za slanje na odobrenje mora ispunjavati poslovna pravila definisana u BM-MF-02 i ostala pravila propisana ovim poglavljem.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-MF-01 | Manifestacija predstavlja zasebnu programsku cjelinu Kalendara kulture koja pod zajedničkim nazivom, identitetom i programskim okvirom objedinjuje jedan ili više povezanih Događaja. |
| BM-MF-02 | Manifestacija može biti kreirana bez Događaja isključivo dok se nalazi u statusu Nacrt. Za slanje Manifestacije na odobrenje mora sadržati najmanje jedan Događaj. Objavljena Manifestacija mora sadržati najmanje jedan Događaj. |
| BM-MF-03 | Manifestacija može sadržati jedan ili više Događaja. Jedan Događaj može pripadati najviše jednoj Manifestaciji. Pripadnost Događaja Manifestaciji nije obavezna. Detaljna pravila za Događaje definišu se u BM-04 Događaj. |
| BM-MF-04 | Manifestacija nema sopstvena održavanja. Održavanja imaju isključivo Događaji koji pripadaju Manifestaciji. Detaljna pravila za održavanja definišu se u BM-06 Održavanje događaja. |
| BM-MF-05 | Početak Manifestacije određuje se najranijim terminom svih održavanja Događaja koji joj pripadaju. Završetak Manifestacije određuje se posljednjim terminom svih održavanja Događaja koji joj pripadaju. Trajanje Manifestacije sistem određuje automatski na osnovu termina održavanja Događaja. |
| BM-MF-06 | Manifestacija se automatski arhivira nakon završetka posljednjeg održavanja posljednjeg Događaja koji joj pripada. Arhiviranje se ne izvršava ručno. Detaljna pravila prikaza arhive definišu se u BM-11 Portal Kalendara kulture. |
| BM-MF-07 | Manifestacija može biti otkazana. Otkazana Manifestacija ostaje evidentirana u sistemu i dobija status „Otkazana“. Otkazivanje Manifestacije ne briše njene Događaje. Pravila za status Događaja uređuju se u skladu sa poslovnim pravilima definisanim u BM-04 Događaj. |
| BM-MF-08 | Manifestacija predstavlja samostalnu programsku cjelinu i ima sopstvene podatke, uključujući naziv, opis, naslovnu fotografiju i ostale pripadajuće informacije. Manifestacija ne nasljeđuje ove podatke od Događaja koji joj pripadaju. Detaljna pravila za Medije definišu se u BM-09 Mediji. |
| BM-MF-09 | Manifestaciju može kreirati Moderator organizatora u ime svog Organizatora. Urednik može kreirati Manifestaciju u ime bilo kojeg Organizatora ili bez registrovanog Organizatora, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik. |
| BM-MF-10 | Manifestacija može biti sačuvana u statusu Nacrt. Dok se nalazi u statusu Nacrt, može se slobodno uređivati. Za slanje na odobrenje mora ispunjavati poslovna pravila definisana u BM-MF-02 i ostala pravila propisana ovim poglavljem. |

## 5. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-06 Održavanje događaja

**Status poglavlja:** USVOJENO

Napomena o oznakama pravila: identifikatori `BM-TR-*` zadržani su kao istorijske tehničke oznake radi stabilnosti referenci. Tekst pravila više ne definiše Termin kao poslovni entitet; opisuju **održavanje događaja**. Pojam **Termin** u ovom poglavlju označava: Datum održavanja je obavezan, a vrijeme može biti definisano.

## 1. Svrha

Svrha ovog poglavlja je definisanje poslovnog koncepta održavanja događaja kao jednog konkretnog održavanja jednog događaja, uključujući njegov termin (Datum održavanja je obavezan, a vrijeme može biti definisano.), lokaciju, ponavljanje, status i druga osnovna poslovna svojstva.

## 2. Poslovni opis

Održavanje događaja predstavlja jedno konkretno održavanje jednog događaja. Jedan događaj može imati jedno ili više održavanja.

Održavanje se ne posmatra kao samostalan programski sadržaj, već uvijek pripada jednom događaju.

Svako održavanje ima termin. Datum održavanja je obavezan, a vrijeme može biti definisano. Termin nije samostalan poslovni entitet.

## 3. Poslovni koncept

```text
Događaj 1 ───── 1..N Održavanja događaja
                      ├── Termin (Datum održavanja je obavezan, a vrijeme može biti definisano.)
                      └── Lokacija (opciono)
```

Održavanje omogućava da se za jedan događaj evidentira jedno ili više konkretnih održavanja, uključujući cjelodnevna, ponavljajuća, izmijenjena, odgođena ili otkazana održavanja.

Svako održavanje ima sopstveni termin i status, dok lokacija može biti opciona.

## 4. Poslovna pravila

### BM-TR-01 — Definicija održavanja događaja

> Održavanje događaja predstavlja jedno konkretno održavanje jednog događaja, sa sopstvenim terminom (Datum održavanja je obavezan, a vrijeme može biti definisano.) i, po potrebi, lokacijom. Jedan događaj može imati jedno ili više održavanja.

### BM-TR-02 — Veza održavanja i događaja

> Održavanje uvijek pripada jednom događaju. Održavanje ne može postojati samostalno niti može biti povezano sa više događaja.

### BM-TR-03 — Termin održavanja

> Datum održavanja je obavezan, a vrijeme može biti definisano. Termin nije samostalan poslovni entitet. Ostali podaci održavanja uređuju se posebnim poslovnim pravilima i mogu biti opcioni.

### BM-TR-04 — Lokacija održavanja

> Održavanje može biti definisano bez lokacije. Kada je lokacija definisana, ona predstavlja svojstvo održavanja i uređuje se u skladu sa poslovnim pravilima definisanim u BM-07 Lokacija.

### BM-TR-05 — Cjelodnevno održavanje

> Održavanje može biti označeno kao cjelodnevno. Za cjelodnevni događaj definiše se samo datum održavanja.

### BM-TR-06 — Ponavljanje i više održavanja

> Održavanja događaja mogu biti kreirana kao pojedinačna ili kroz pravilo ponavljanja. Pravilo ponavljanja definiše ili generiše više održavanja jednog događaja. Svako održavanje dobija svoj termin. Sistem podržava dnevno, sedmično i mjesečno ponavljanje, kao i ručno dodavanje pojedinačnih održavanja.

### BM-TR-07 — Izuzeci u ponavljajućoj seriji

> Pojedinačno održavanje u okviru ponavljajuće serije može biti izmijenjeno ili otkazano bez uticaja na ostala održavanja iste serije. Izmjene i otkazivanja primjenjuju se isključivo na odabrano održavanje. Pomjeranje znači promjenu termina (datuma i/ili vremena) jednog održavanja.

### BM-TR-08 — Izmjena objavljenog održavanja

> Održavanje objavljenog događaja može se izmijeniti (uključujući promjenu termina, lokacije ili drugih podataka održavanja). Izmjene podataka održavanja, osim postavljanja statusa **Planiran**, **Odgođen** i **Otkazan** uređenih pravilima BM-TR-16 i BM-TR-17, podliježu istim pravilima uređivanja i odobravanja koja važe za događaj, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik.

### BM-TR-09 — Status održavanja

> Svako održavanje ima vlastiti status, nezavisno od ostalih održavanja istog događaja. Status održavanja određuje njegovo trenutno stanje i može biti različit od statusa drugih održavanja događaja. Status održavanja nije status događaja.

### BM-TR-10 — Dozvoljeni statusi održavanja

> Održavanje može imati jedan od sljedećih statusa:
>
> * **Planiran** — održavanje je aktivno i biće održano prema objavljenim podacima.
> * **Odgođen** — održavanje neće biti održano u planiranom terminu i očekuje se određivanje novog termina.
> * **Otkazan** — održavanje neće biti održano.
> * **Završen** — održavanje je održano ili je prošao njegov termin.
>
> Status **Završen** sistem dodjeljuje automatski nakon što prođe termin održavanja.

### BM-TR-11 — Ulaznice i cijena

> Upravljanje informacijama o ulaznicama i cijeni nije dio poslovnog opsega verzije V1.

### BM-TR-12 — Odgođen pripada održavanju

> Status **Odgođen** odnosi se isključivo na održavanje događaja. Status **Odgođen** nije status događaja.

### BM-TR-13 — Tranzicije iz statusa Planiran

> Iz statusa **Planiran** održavanje može preći u status:
>
> * **Odgođen**;
> * **Otkazan**;
> * **Završen**.

### BM-TR-14 — Tranzicije iz statusa Odgođen

> Iz statusa **Odgođen** održavanje može preći u status:
>
> * **Planiran**, nakon određivanja novog termina;
> * **Otkazan**.
>
> Druge tranzicije iz statusa **Odgođen** nisu dozvoljene.

### BM-TR-15 — Povratak iz statusa Odgođen u Planiran

> Prilikom prelaska iz statusa **Odgođen** u status **Planiran** radi se o istom održavanju događaja. Novo održavanje se ne kreira. Istorija održavanja ostaje sačuvana.

### BM-TR-16 — Ovlašćenja za status održavanja sa registrovanim Organizatorom

> Kada održavanje pripada događaju sa registrovanim Organizatorom:
>
> * Moderator može u ime Organizatora zatražiti odgađanje ili promjenu termina.
> * Organizator ne mijenja direktno status objavljenog održavanja (Organizator nije korisnik i ne izvršava radnje u sistemu).
> * Moderator postavlja status **Odgođen**, **Planiran** (nakon određivanja novog termina) i **Otkazan**, u skladu sa poslovnim pravilima tranzicija statusa održavanja.

### BM-TR-17 — Ovlašćenja za status održavanja bez registrovanog Organizatora

> Kada održavanje pripada događaju bez registrovanog Organizatora, ista ovlašćenja za postavljanje statusa **Odgođen**, **Planiran** (nakon određivanja novog termina) i **Otkazan** ima Urednik.

### BM-TR-18 — Obuhvat ovlašćenja za status održavanja

> Pravila BM-TR-16 i BM-TR-17 odnose se isključivo na status održavanja. Ne mijenjaju status događaja niti postojeći urednički workflow događaja.

## 5. Otvorena pitanja

Za poglavlje BM-06 trenutno nema otvorenih poslovnih pitanja.

Teme koje nijesu obuhvaćene ovim poglavljem ne treba dodavati bez nove, izričito usvojene poslovne odluke i narednog numerisanog PATCH-a.

---

# BM-07 Lokacija

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnog koncepta lokacije kao mjesta održavanja događaja i pravila njenog korišćenja u sistemu.

## 2. Poslovni opis

Lokacija predstavlja mjesto na kojem se događaj konkretno održava.

Lokacija pripada održavanju događaja, a ne terminu, u skladu sa usvojenim poslovnim pravilima.

## 3. Poslovni koncept

Lokacije predstavljaju zajednički poslovni resurs koji može koristiti više događaja kroz njihova održavanja.

## 4. Poslovna pravila

### BM-LK-01 — Definicija lokacije

> Lokacija je mjesto održavanja događaja. Jedna lokacija može biti mjesto jednog ili više održavanja. Lokacija može biti unaprijed definisana ili određena naknadno, u skladu sa poslovnim pravilima sistema.

### BM-LK-02 — Ponovna upotreba lokacije

> Jedna lokacija može biti povezana sa jednim ili više održavanja različitih događaja. Lokacija se koristi kao zajednički poslovni entitet i ne kreira se ponovo za svako održavanje.

### BM-LK-03 — Naziv lokacije

> Lokacija mora imati naziv. Ostali podaci o lokaciji uređuju se posebnim poslovnim pravilima i mogu biti opcioni.

### BM-LK-04 — Naknadno određivanje lokacije

> Lokacija može biti definisana ili određena naknadno, u skladu sa potrebama organizacije događaja.

### BM-LK-05 — Aktivnost lokacije

> Lokacija može biti aktivna ili neaktivna. Neaktivna lokacija ne može se koristiti za nova održavanja događaja, ali ostaje povezana sa postojećim održavanjima radi očuvanja istorijskih podataka.

## 5. Otvorena pitanja

Za poglavlje BM-07 trenutno nema otvorenih poslovnih pitanja.

---

# BM-08 Kategorije i oznake

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnih pravila za klasifikaciju događaja kroz kategorije i oznake.

## 2. Poslovni opis

Kategorije predstavljaju osnovnu poslovnu klasifikaciju događaja.

Oznake predstavljaju dodatnu klasifikaciju koja omogućava detaljniju organizaciju i pretragu sadržaja.

## 3. Poslovni koncept

Svaki događaj pripada jednoj primarnoj kategoriji, dok može imati jednu ili više oznaka.

## 4. Poslovna pravila

### BM-KO-01 — Definicija

> Kategorije i oznake predstavljaju poslovnu klasifikaciju sadržaja koja omogućava organizaciju, pretragu, filtriranje i prikaz događaja na javnom portalu.

### BM-KO-02 — Primarna kategorija

> Događaj može biti povezan sa jednom primarnom kategorijom. Primarna kategorija je obavezna prije odobravanja i objavljivanja događaja.

### BM-KO-03 — Oznake

> Događaj može biti povezan sa jednom ili više oznaka. Oznake su opcione i služe za dodatnu klasifikaciju i pretragu sadržaja.

### BM-KO-04 — Upravljanje

> Kategorijama i oznakama upravlja urednik, u skladu sa poslovnim pravilima sistema.

### BM-KO-05 — Aktivnost

> Kategorija ili oznaka može biti aktivna ili neaktivna. Neaktivne kategorije i oznake ne mogu se koristiti za nove događaje, ali ostaju povezane sa postojećim događajima radi očuvanja istorijskih podataka.

## 5. Otvorena pitanja

Za poglavlje BM-08 trenutno nema otvorenih poslovnih pitanja.

---

# BM-09 Mediji

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnog koncepta medija, njihove poslovne namjene, povezivanja sa poslovnim entitetima i upravljanja medijima u modulu Kalendara kulture.

## 2. Poslovni opis

Mediji predstavljaju digitalni sadržaj koji se koristi za vizuelno ili dokumentaciono predstavljanje poslovnih entiteta u modulu Kalendara kulture.

Mediji predstavljaju zajednički poslovni resurs koji se može koristiti za predstavljanje više poslovnih entiteta.

## 3. Poslovni koncept

Mediji omogućavaju vizuelno i dokumentaciono predstavljanje događaja, manifestacija i lokacija kroz jasno definisanu poslovnu namjenu, uz upravljanje njihovim životnim ciklusom i uređivačkim procesom.

## 4. Poslovna pravila

### BM-MD-01 — Definicija medija

> Medij predstavlja digitalni sadržaj koji se koristi za vizuelno ili dokumentaciono predstavljanje poslovnih entiteta u modulu Kalendara kulture.

### BM-MD-02 — Povezivanje medija

> Jedan medij može biti povezan sa jednim ili više događaja, manifestacija ili lokacija u modulu Kalendara kulture, u skladu sa poslovnim pravilima sistema.

### BM-MD-03 — Namjena medija

> Medij ima definisanu namjenu koja određuje njegovu poslovnu ulogu u predstavljanju poslovnog entiteta u modulu Kalendara kulture.

### BM-MD-04 — Aktivnost medija

> Medij može biti aktivan ili neaktivan. Neaktivan medij ne može se koristiti za nova povezivanja sa poslovnim entitetima, ali ostaje povezan sa postojećim poslovnim entitetima radi očuvanja istorijskih podataka.

### BM-MD-05 — Upravljanje medijima

> Moderator može predlagati medije kroz uređivanje događaja u ime Organizatora, dok urednik upravlja medijima u postupku odobravanja i objavljivanja sadržaja.

### BM-MD-06 — Naslovna fotografija događaja

> Sistem uvijek prikazuje jednu naslovnu fotografiju događaja. Ako Moderator ili Urednik ne postavi fotografiju događaja, sistem automatski prikazuje podrazumijevanu fotografiju povezanu sa kategorijom događaja. Korisnik nikada ne vidi događaj bez naslovne fotografije.

## 5. Otvorena pitanja

Za poglavlje BM-09 trenutno nema otvorenih poslovnih pitanja.

---

# BM-10 Statusi i životni ciklus događaja

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnih statusa događaja, dozvoljenih faza njegovog životnog ciklusa i osnovnih pravila prelaska između tih faza u modulu Kalendara kulture.

## 2. Poslovni opis

Životni ciklus događaja obuhvata poslovna stanja kroz koja događaj prolazi od početnog kreiranja, preko postupka odobravanja i objavljivanja, do otkazivanja ili automatskog arhiviranja.

Tok događaja zavisi od korisničke uloge koja upravlja događajem i od poslovnih pravila modula Kalendara kulture.

## 3. Poslovni koncept

Događaj uvijek ima jedan važeći poslovni status.

Promjena statusa predstavlja posljedicu dozvoljene poslovne radnje koju izvršava ovlašćena korisnička uloga ili sistem automatski, u skladu sa usvojenim poslovnim pravilima.

## 4. Poslovna pravila

### BM-ST-01 — Definicija životnog ciklusa

> Životni ciklus događaja predstavlja skup poslovnih statusa kroz koje događaj prolazi od kreiranja do automatskog arhiviranja u modulu Kalendara kulture.

### BM-ST-02 — Statusi događaja

> Događaj može imati jedan od sljedećih statusa:
>
> * Nacrt
> * Na odobrenju
> * Objavljen
> * Otkazan
> * Arhiviran

### BM-ST-03 — Kreiranje događaja

> Svaki novi događaj nastaje u statusu Nacrt. Događaj u statusu Nacrt nije vidljiv na javnom portalu i može ga uređivati Moderator ili Urednik, u skladu sa poslovnim pravilima sistema. Ukoliko događaj nema registrovanog organizatora, uređivanje nacrta vrši urednik. Događaj u statusu Nacrt može biti sačuvan bez svih podataka potrebnih za njegovo objavljivanje.

### BM-ST-04 — Slanje na odobrenje i objavljivanje

> Događaj u statusu Nacrt koji je kreirao Moderator u ime Organizatora može biti poslat na odobrenje kada ispunjava poslovne uslove za pregled od strane urednika. Slanjem na odobrenje status događaja se mijenja u Na odobrenju.
>
> Događaj koji pripada Organizatoru ne može biti direktno objavljen. Za takav događaj obavezan je standardni tok: Nacrt → Na odobrenju → Objavljen. Moderator ne može biti zaobiđen u tom toku.
>
> Urednik može direktno objaviti događaj iz statusa Nacrt, bez postupka odobravanja, isključivo kada događaj nema registrovanog Organizatora. To je jedini izuzetak od standardnog procesa odobravanja.

### BM-ST-05 — Vraćanje na doradu

> Urednik može vratiti događaj u status Nacrt radi dorade. Vraćanjem na doradu status događaja se mijenja iz Na odobrenju u Nacrt, uz obrazloženje razloga vraćanja.

### BM-ST-06 — Objavljivanje događaja

> Objavljivanjem događaja njegov status se mijenja u Objavljen. Objavljen događaj postaje vidljiv na javnom portalu u skladu sa poslovnim pravilima sistema. Objavljen događaj može se naknadno uređivati u skladu sa poslovnim pravilima sistema.

### BM-ST-07 — Otkazivanje događaja

> Objavljen događaj može biti otkazan. Otkazivanjem status događaja se mijenja u Otkazan, pri čemu događaj ostaje dostupan radi očuvanja istorijskih podataka i informisanja javnosti.
>
> Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje.
>
> Deaktivacijom Organizatora prestaje moderatorski kontekst za tog Organizatora. Moderator tada više nema pravo otkazivanja događaja tog Organizatora. Ako je potrebno otkazati događaj deaktiviranog Organizatora, tu radnju izvršava isključivo Urednik.
>
> Urednik može otkazati bilo koji objavljeni događaj.
>
> Otkazan događaj može se ponovo objaviti ukoliko prestanu razlozi zbog kojih je otkazan, dok je događaj još u statusu Otkazan. Ponovno objavljivanje je urednička radnja: isključivo Urednik može ponovo objaviti otkazani događaj. Prije ponovne objave Urednik provjerava i, po potrebi, ažurira podatke događaja i povezanih održavanja koristeći postojeća ovlašćenja. Ponovna objava nije automatska. Moderator ne može ponovo objaviti otkazani događaj.

### BM-ST-08 — Automatsko arhiviranje

> Događaj se automatski arhivira nakon završetka svih njegovih održavanja, bez ručne intervencije.
>
> Automatsko arhiviranje primjenjuje se na događaj u statusu Objavljen i na događaj u statusu Otkazan. Otkazani događaj nakon završetka svih održavanja prelazi u status Arhiviran.
>
> Arhiviran događaj ostaje dostupan radi očuvanja istorijskih podataka.

### BM-ST-09 — Promjena statusa

> Promjena statusa događaja može se izvršiti isključivo u skladu sa poslovnim pravilima modula Kalendara kulture i ovlašćenjima korisničkih uloga. Sistem ne dozvoljava promjenu statusa koja nije definisana poslovnim pravilima.

## 5. Otvorena pitanja

Za poglavlje BM-10 trenutno nema otvorenih poslovnih pitanja.

---

# BM-11 Portal Kalendara kulture

**Status poglavlja:** USVOJENO

## Poslovna pravila

### BM-PK-01 — Definicija Portala Kalendara kulture

> Portal Kalendara kulture predstavlja funkcionalni dio modula Kalendara kulture namijenjen pregledu, pretrazi i korišćenju sadržaja objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-02 — Odnos sa platformom Digital Kotor

> Portal Kalendara kulture predstavlja funkcionalni dio platforme Digital Kotor.
>
> Za korišćenje Portala Kalendara kulture zahtijeva se registracija korisnika.
>
> Upravljanje korisničkim identitetom, registracijom, prijavom i korisničkim profilom nije dio poslovnog domena Portala Kalendara kulture, već platforme Digital Kotor.

### BM-PK-03 — Pregled događaja

> Portal Kalendara kulture omogućava pregled događaja objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture. Pregled događaja obuhvata informacije potrebne za informisanje korisnika o održavanju kulturnih sadržaja.

### BM-PK-04 — Pregled manifestacija

> Portal Kalendara kulture omogućava pregled manifestacija objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture. Pregled manifestacije obuhvata informacije o manifestaciji i sa njom povezanim događajima.

### BM-PK-05 — Detaljan prikaz

> Portal Kalendara kulture omogućava pregled detaljnih informacija o objavljenim događajima i manifestacijama, uključujući sa njima povezana održavanja (sa terminima i lokacijama), kategorije, oznake, medije i druge javno objavljene podatke u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-06 — Pretraga

> Portal Kalendara kulture omogućava pretragu objavljenih događaja i manifestacija korišćenjem kriterijuma definisanih poslovnim pravilima modula Kalendara kulture.

### BM-PK-07 — Filtriranje

> Portal Kalendara kulture omogućava filtriranje objavljenih događaja i manifestacija korišćenjem kriterijuma definisanih poslovnim pravilima modula Kalendara kulture.

### BM-PK-08 — Načini prikaza

> Portal Kalendara kulture omogućava prikaz objavljenih događaja i manifestacija kroz jedan ili više načina prikaza, u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-09 — Prikaz održavanja i termina

> Portal Kalendara kulture omogućava pregled svih javno objavljenih održavanja događaja, uključujući termin svakog održavanja (Datum održavanja je obavezan, a vrijeme može biti definisano.). Kada događaj ima više održavanja, portal prikazuje sva održavanja sa njihovim terminima i lokacijama, u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-10 — Prikaz lokacija

> Portal Kalendara kulture omogućava pregled lokacija povezanih sa objavljenim događajima i manifestacijama, kada su one definisane u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-11 — Prikaz kategorija i oznaka

> Portal Kalendara kulture omogućava prikaz primarnih kategorija i oznaka povezanih sa objavljenim događajima i manifestacijama, u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-12 — Prikaz medija

> Portal Kalendara kulture omogućava prikaz medija povezanih sa objavljenim događajima, manifestacijama i lokacijama, u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-13 — Prikaz otkazanih i arhiviranih događaja

> Portal Kalendara kulture omogućava prikaz otkazanih i arhiviranih događaja u skladu sa poslovnim pravilima modula Kalendara kulture. Status otkazanog ili arhiviranog događaja mora biti jasno prikazan korisniku.

### BM-PK-14 — Povezani sadržaj

> Portal Kalendara kulture može prikazivati međusobno povezane događaje i manifestacije u skladu sa njihovim poslovnim vezama definisanim u modulu Kalendara kulture.

### BM-PK-15 — Istaknuti događaj

> Portal Kalendara kulture može imati istaknuti događaj.
>
> Istaknuti događaj mora biti javno objavljen događaj.
>
> Urednik odlučuje koji događaj je istaknut.
>
> U istom trenutku može biti istaknut najviše jedan događaj.
>
> Isticanje događaja ne mijenja njegov osnovni status.
>
> Događaj prestaje biti istaknut kada Urednik ukloni isticanje ili kada događaj više ne ispunjava uslove za javni prikaz.

---

# BM-12 Urednički portal

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definišu se poslovna pravila rada Uredničkog portala modula Kalendar kulture.

Urednički portal predstavlja poslovnu cjelinu kroz koju Moderatori i Urednici izvršavaju poslovne radnje definisane ovim Business Modelom. Organizator ne pristupa uredničkom portalu direktno.

Poslovna pravila rada pojedinačnih poslovnih entiteta definisana su odgovarajućim poglavljima ovog Business Modela.

## Poslovna pravila

### BM-EP-01 — Namjena

> Urednički portal namijenjen je upravljanju kulturnim sadržajem i sprovođenju uredničkog procesa od kreiranja događaja do njegovog objavljivanja.

### BM-EP-02 — Poslovne uloge

> Urednički portal koriste:
>
> * Moderatori;
> * Urednici.
>
> Organizator ne pristupa uredničkom portalu. Moderatori i Urednici koriste funkcionalnosti Uredničkog portala u skladu sa ovlašćenjima definisanim ovim Business Modelom.

### BM-EP-03 — Poslovne funkcionalnosti

> Urednički portal omogućava:
>
> * upravljanje podacima Organizatora;
> * upravljanje Događajima;
> * upravljanje Manifestacijama;
> * upravljanje održavanjima događaja;
> * upravljanje Medijima;
> * pregled statusa entiteta;
> * sprovođenje uredničkog procesa;
> * pregled poslovnih obavještenja i sistemskih informacija namijenjenih Moderatorima i Urednicima.

### BM-EP-04 — Poslovni procesi

> Svi poslovni procesi koji se izvršavaju kroz Urednički portal sprovode se u skladu sa poslovnim pravilima definisanim ovim Business Modelom.
>
> Urednički portal ne mijenja poslovna pravila već omogućava njihovu primjenu.

### BM-EP-05 — Poslovna odgovornost

> Svaka poslovna uloga odgovorna je za poslovne radnje koje izvrši koristeći Urednički portal.
>
> Odgovornost se određuje u skladu sa poslovnom ulogom.

### BM-EP-06 — Poslovna vidljivost

> Moderatorima i Urednicima dostupni su isključivo podaci i funkcionalnosti za koje imaju odgovarajuća poslovna ovlašćenja.
>
> Pristup poslovnim podacima određuje se poslovnim pravilima definisanim ovim Business Modelom.

### BM-EP-07 — Saradnja poslovnih uloga

> Moderatori i Urednici međusobno sarađuju kroz poslovne procese definisane ovim Business Modelom.
>
> Svaka poslovna uloga izvršava isključivo poslovne radnje koje su joj dodijeljene.

### BM-EP-08 — Jedinstven poslovni sistem

> Urednički portal predstavlja sastavni dio modula Kalendar kulture i koristi zajedničke poslovne entitete, poslovna pravila i definicije utvrđene ovim Business Modelom.

### BM-EP-09 — Evidencija aktivnosti

> Poslovno značajne radnje izvršene kroz Urednički portal evidentiraju se u Evidenciji aktivnosti u skladu sa pravilima definisanim ovim Business Modelom.

### BM-EP-10 — Završna odredba

> Urednički portal predstavlja poslovnu cjelinu kroz koju se izvršava urednički proces modula Kalendar kulture.
>
> Sve poslovne radnje izvršene kroz Urednički portal podliježu poslovnim pravilima definisanim ovim Business Modelom.

---

# BM-13 Newsletter

**Status poglavlja:** USVOJENO

## Poslovna pravila

### BM-NL-01 — Definicija

> Newsletter predstavlja funkcionalnost modula Kalendara kulture namijenjenu informisanju zainteresovanih korisnika o novoobjavljenim javno dostupnim kulturnim događajima i o poslovno značajnim promjenama događaja koje utiču na odluku o prisustvu (otkazivanje, odlaganje, promjena datuma, vremena ili lokacije održavanja).

### BM-NL-02 — Svrha

> Newsletter služi isključivo informisanju korisnika o kulturnim događajima i o njihovim poslovno značajnim promjenama u Kalendaru kulture.

### BM-NL-03 — Odnos prema uredničkom procesu

> Newsletter nije dio uredničkog procesa i ne koristi se za poslovnu komunikaciju između Organizatora, Moderatora, Urednika i Administratora platforme. Organizator, Moderator i Urednik ne upravljaju pretplatnicima, ne pokreću ručno slanje Newslettera i ne biraju ručno događaje za Newsletter.

### BM-NL-04 — Pretplata

> Svaki registrovani i verifikovani korisnik može se dobrovoljno prijaviti na newsletter Kalendara kulture. Prijava na newsletter nije uslov za korišćenje Kalendara kulture. Pretplatnik može izabrati sve Organizatore ili jednog ili više konkretnih Organizatora. Ako korisnik ne izabere nijednog konkretnog Organizatora, sistem smatra da je izabrao sve Organizatore. Izbor Organizatora je isključivo filter sadržaja i ne daje prava nad Organizatorom niti događajima.

### BM-NL-05 — Odjava

> Korisnik može u svakom trenutku odjaviti prijem newslettera. Odjava ne briše korisnički nalog niti utiče na pristup drugim modulima platforme.

### BM-NL-06 — Sadržaj newslettera

> Newsletter sadrži kratak pregled novoobjavljenih događaja i/ili poslovno značajnih promjena događaja koji odgovaraju aktivnoj pretplati i pravilima slanja. Za svaki događaj prikazuju se osnovne informacije i veza ka detaljima događaja, u skladu sa posljednjim poslovno važećim stanjem događaja u trenutku pripreme poruke. Događaji se grupišu po Organizatoru. Za svakog Organizatora Newsletter sadrži vezu ka objavljenom pregledu događaja tog Organizatora na portalu Kalendara kulture. Više novoobjavljenih događaja može biti objedinjeno u jednu Newsletter poruku. Isti događaj se ne prikazuje više puta zbog više termina; relevantni budući termini mogu biti prikazani unutar jedne stavke događaja.

### BM-NL-07 — Periodična provjera i prioritetna obavještenja

> Sistem periodično provjerava da li postoje novoobjavljeni događaji koji odgovaraju aktivnim pretplatama i, kada postoje, šalje objedinjeni Newsletter odgovarajućim pretplatnicima. Newsletter nije vezan za fiksni dan u sedmici niti za unaprijed definisanu kalendarsku sedmicu. Obavještenja o otkazivanju, odlaganju ili promjeni datuma, vremena ili lokacije predstavljaju prioritetna obavještenja i šalju se bez nepotrebnog odlaganja kako bi pretplatnici blagovremeno bili informisani. Tačan interval periodične provjere, način prioritetnog slanja i tehnička realizacija nisu predmet ovog Business Modela.

### BM-NL-08 — Nezavisnost od poslovnih procesa

> Pretplata na newsletter nema uticaja na prava korisnika niti na poslovne procese definisane ovim Business Modelom. Poslovni procesi funkcionišu nezavisno od prijave ili odjave korisnika na newsletter.

### BM-NL-09 — Objavljeni sadržaj i okidač prvog uključivanja

> Prvo uključivanje događaja u Newsletter kao novoobjavljenog sadržaja moguće je isključivo za događaje u statusu Objavljen. Javno objavljivanje događaja predstavlja poslovni okidač za to prvo uključivanje. Događaj ne mora biti poslat istog trenutka kada je objavljen; postaje kandidat za naredni odgovarajući Newsletter. Događaji koji nijesu objavljeni ne mogu biti uključeni kao novoobjavljeni sadržaj.

### BM-NL-10 — Relevantnost budućeg termina

> Događaj može biti uključen u Newsletter kao novoobjavljeni sadržaj samo ako u trenutku pripreme ima najmanje jedno buduće održavanje. Događaj bez budućeg održavanja ne ulazi kao novoobjavljeni sadržaj. Ovo pravilo ne sprečava prioritetno obavještenje o otkazivanju događaja ili termina koji je pretplatniku prethodno bio poslat.

### BM-NL-11 — Zaštita od ponovnog slanja prvog uključivanja

> Isti događaj se istom pretplatniku ne šalje ponovo kao novoobjavljeni sadržaj samo zato što sistem ponovo izvršava periodičnu provjeru. Događaj objavljen nakon prethodnog Newsletter slanja može biti uključen u naredno slanje ako je i dalje relevantan i odgovara aktivnoj pretplati.

### BM-NL-12 — Aktivni pretplatnik

> Aktivni pretplatnik je registrovani i verifikovani korisnik sa aktivnom Newsletter pretplatom koji nije izvršio odjavu. Postojanje odgovarajućih događaja nije dio definicije aktivnog pretplatnika.

### BM-NL-13 — Ne-slati prazan Newsletter

> Ako za konkretnog aktivnog pretplatnika u trenutku pripreme nema nijednog odgovarajućeg novoobjavljenog događaja niti prioritetnog obavještenja prema pravilima slanja, Newsletter mu se ne šalje. Sistem ne dodaje događaje drugih Organizatora samo da bi poruka imala sadržaj.

### BM-NL-14 — Uređivačke izmjene nisu okidač

> Ispravka pravopisnih grešaka, izmjena opisa, izmjena ili dodavanje fotografija, izmjena dodatnih informacija koje ne utiču na održavanje događaja i druge uređivačke izmjene koje ne mijenjaju način održavanja događaja ne predstavljaju Newsletter okidač.

### BM-NL-15 — Potvrda prve aktivacije

> Nakon prve uspješne aktivacije Newsletter pretplate sistem šalje potvrdu o aktiviranoj pretplati. Double opt-in nije obavezan u V1.

### BM-NL-16 — Granice V1

> U V1 opsegu Newslettera nisu: izbor kategorija događaja, personalizacija prema ponašanju, automatske preporuke, profilisanje, ručni izbor događaja od strane Urednika, Newsletter kampanje Organizatora, ručno slanje Newslettera, različiti Newsletteri po ulozi, te definisanje tačnog tehničkog intervala periodične ili prioritetne isporuke.

### BM-NL-17 — Poslovno značajne promjene kao okidač

> Otkazivanje događaja, odlaganje događaja, promjena datuma održavanja, promjena vremena održavanja i promjena lokacije održavanja predstavljaju poslovno značajne promjene i Newsletter okidače.

### BM-NL-18 — Publika obavještenja o promjeni

> Obavještenje o poslovno značajnoj promjeni događaja šalje se isključivo aktivnim pretplatnicima kojima je isti događaj prethodno bio uključen u Newsletter. Pretplatnici koji nisu dobili prvobitnu informaciju o događaju ne dobijaju obavještenje o njegovom otkazivanju ili izmjeni.

### BM-NL-19 — Promjene kod događaja sa više termina

> Ako je promijenjen ili otkazan samo jedan termin događaja sa više termina, obavještenje se odnosi samo na taj termin i ne tretira se kao otkazivanje cijelog događaja. Ako promjena utiče na kompletan događaj, obavještenje se odnosi na cijeli događaj.

### BM-NL-20 — Prioritetna obavještenja

> Obavještenja o otkazivanju, odlaganju ili promjeni datuma, vremena ili lokacije šalju se bez nepotrebnog odlaganja kako bi pretplatnici blagovremeno bili informisani. Prioritetna obavještenja mogu biti objedinjena ako time nije ugrožena njihova blagovremenost. Objedinjavanje više novoobjavljenih događaja u jednu poruku ostaje dozvoljeno za tip sadržaja prvog uključivanja.

### BM-NL-21 — Zaštita od ponovnog slanja iste promjene

> Ista poslovno značajna promjena istog događaja (ili istog termina) ne smije biti više puta poslata istom pretplatniku. Ovo pravilo je odvojeno od zaštite od ponovnog slanja prvog uključivanja događaja (BM-NL-11).

### BM-NL-22 — Višestruke poslovno značajne promjene prije slanja

> Ako prije slanja Newslettera nad istim događajem nastane više uzastopnih poslovno značajnih promjena, pretplatniku se dostavlja jedinstveno obavještenje koje odražava posljednje važeće stanje događaja. Ne šalje se istorija svih promjena niti međukoraci.

### BM-NL-23 — Posljednje važeće stanje

> Newsletter i prioritetna obavještenja prikazuju posljednje poslovno važeće stanje događaja u trenutku pripreme poruke.

### BM-NL-24 — Objedinjavanje prioritetnih promjena

> Prioritetna obavještenja mogu biti objedinjena ako time nije ugrožena njihova blagovremenost. Više gotovo istovremenih poslovno značajnih promjena može biti predstavljeno jednom porukom, uz zadržavanje zahtjeva za blagovremenim informisanjem pretplatnika.

### BM-NL-25 — Zabranjena kontradiktorna obavještenja

> Pretplatniku se ne šalju međusobno kontradiktorna obavještenja za isti događaj u okviru istog ciklusa pripreme Newslettera. Korisnik dobija jedno konačno poslovno stanje događaja.

---

# BM-14 Evidencija aktivnosti (Audit log)

**Status poglavlja:** USVOJENO

## Poslovna pravila

### BM-AL-01 — Definicija evidencije aktivnosti

> Evidencija aktivnosti predstavlja poslovni zapis o poslovno značajnim radnjama izvršenim u modulu Kalendara kulture. Njena svrha je dokumentovanje izvršenih poslovnih radnji, utvrđivanje odgovornosti korisnika i omogućavanje njihove naknadne provjere.

### BM-AL-02 — Odnos prema tehničkim logovima

> Evidencija aktivnosti predstavlja poslovnu evidenciju izvršenih radnji i ne predstavlja zamjenu za tehničke sistemske logove niti druge tehničke mehanizme evidencije.

### BM-AL-03 — Poslovno značajne aktivnosti

> Evidencija aktivnosti obuhvata isključivo poslovno značajne aktivnosti koje utiču na poslovne podatke ili poslovne procese definisane ovim Business Modelom. Aktivnosti koje nemaju poslovni značaj ne evidentiraju se u evidenciji aktivnosti.

### BM-AL-04 — Nepromjenjivost evidencije aktivnosti

> Jednom evidentirana aktivnost postaje trajni dio evidencije aktivnosti. Evidentirane aktivnosti ne mogu se naknadno mijenjati niti brisati kroz redovno korišćenje sistema.

### BM-AL-05 — Nezavisnost evidencije aktivnosti

> Evidencija aktivnosti služi isključivo dokumentovanju izvršenih poslovnih radnji. Njeno postojanje niti sadržaj ne utiču na tok poslovnih procesa, poslovna pravila niti prava korisnika definisana ovim Business Modelom.

### BM-AL-06 — Pristup evidenciji aktivnosti

> Pristup evidenciji aktivnosti ima isključivo Administrator platforme. Ostali korisnici sistema nemaju direktan pristup evidenciji aktivnosti.

### BM-AL-07 — Oblasti evidencije aktivnosti

> Evidencija aktivnosti obuhvata poslovno značajne aktivnosti koje se odnose na entitete i administrativne funkcije definisane ovim Business Modelom, uključujući zahtjeve za kreiranje Organizatora i zahtjeve za dodjelu ili uklanjanje Moderatora (podnosilac, predloženi Moderator gdje je primjenjivo, datum i vrijeme podnošenja, Urednik koji je odlučio, datum i vrijeme odluke). Poslovne aktivnosti koje se evidentiraju za pojedine oblasti definišu se funkcionalnom i tehničkom specifikacijom u skladu sa ovim Business Modelom.

### BM-AL-08 — Namjena evidencije aktivnosti

> Evidencija aktivnosti služi reviziji, kontroli i naknadnoj provjeri izvršenih poslovnih radnji. Evidencija aktivnosti nije sredstvo komunikacije niti predstavlja poslovno obavještenje.

---

# BM-15 Opšta poslovna pravila

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definišu se opšta poslovna pravila koja važe za sve poslovne cjeline Kalendara kulture, osim kada je pojedinim poglavljem ovog Business Modela izričito drugačije određeno.

Ova pravila predstavljaju osnov za dosljedno tumačenje i primjenu svih poslovnih procesa definisanih ovim Business Modelom.

## Poslovna pravila

### BM-GR-01 — Dosljednost poslovnih podataka

> Sistem mora obezbijediti da poslovni podaci ostanu međusobno usklađeni tokom cijelog životnog ciklusa entiteta.
>
> Poslovna radnja koja bi narušila dosljednost poslovnih podataka nije dozvoljena.

### BM-GR-02 — Jedinstveni izvor podataka

> Svaki poslovni podatak održava se na jednom mjestu u sistemu.
>
> Zajednički podaci koriste se kroz cijeli sistem kako bi se izbjeglo dupliranje podataka i obezbijedila njihova dosljednost.

### BM-GR-03 — Životni ciklus entiteta

> Svaki entitet prolazi kroz životni ciklus definisan ovim Business Modelom.
>
> Status predstavlja trenutno poslovno stanje entiteta.
>
> Promjena statusa predstavlja dio poslovnog procesa i može se izvršiti isključivo u skladu sa pravilima definisanim ovim Business Modelom.

### BM-GR-04 — Očuvanje poslovne istorije

> Poslovna istorija predstavlja sastavni dio sistema.
>
> Kada je potrebno onemogućiti dalje korišćenje entiteta, primjenjuju se poslovna pravila aktivacije, deaktivacije ili arhiviranja, u skladu sa prirodom pojedinog entiteta.
>
> Brisanje poslovnih podataka primjenjuje se isključivo kada je to izričito predviđeno ovim Business Modelom.

### BM-GR-05 — Automatske poslovne radnje

> Sistem može automatski izvršavati poslovne radnje kada je njihovo izvršavanje definisano poslovnim pravilima.
>
> Automatski izvršene radnje imaju isti poslovni značaj kao radnje koje izvršava korisnik.

### BM-GR-06 — Predvidivost poslovnog ponašanja

> Sistem primjenjuje poslovna pravila na dosljedan i predvidiv način.
>
> Jednaki poslovni uslovi uvijek proizvode isti poslovni rezultat, osim kada je ovim Business Modelom izričito definisano drugačije.

### BM-GR-07 — Primjena posebnih poslovnih pravila

> Kada je za pojedini poslovni proces ili entitet ovim Business Modelom propisano posebno pravilo, ono ima prednost u odnosu na opšta poslovna pravila iz ovog poglavlja.

---

# BM-16 Rječnik poslovnih pojmova

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definišu se osnovni poslovni pojmovi koji se koriste u Business Modelu modula Kalendar kulture.

Definicije predstavljaju zajednički referentni okvir za sve učesnike u planiranju, razvoju, održavanju i korišćenju sistema, sa ciljem obezbjeđivanja jedinstvenog razumijevanja poslovnih pravila i terminologije.

**Terminološko pravilo:** Pojam „Termin“ koristi se isključivo u značenju: Datum održavanja je obavezan, a vrijeme može biti definisano. Nije dozvoljeno koristiti riječ „termin“ kao naziv ili sinonim za pojedinačno održavanje događaja ili za poslovni entitet koji ima lokaciju, status, audit ili sopstveni životni ciklus.

## Poslovni pojmovi

### BM-GL-01 — Entitet

> Poslovna cjelina kojom sistem upravlja i o kojoj vodi podatke.
>
> Primjeri entiteta su Organizator, Manifestacija, Događaj, Održavanje događaja, Lokacija i Kategorija.

### BM-GL-02 — Životni ciklus

> Niz poslovnih stanja kroz koja entitet prolazi od svog nastanka do završetka ili arhiviranja, u skladu sa poslovnim pravilima.

### BM-GL-03 — Status

> Trenutno poslovno stanje entiteta.
>
> Status određuje koje su poslovne radnje nad entitetom dozvoljene u skladu sa ovim Business Modelom.

### BM-GL-04 — Poslovni proces

> Skup međusobno povezanih poslovnih radnji kojima se upravlja životnim ciklusom jednog ili više entiteta.

### BM-GL-05 — Poslovna radnja

> Pojedinačna aktivnost koja predstavlja dio poslovnog procesa i proizvodi poslovni rezultat ili mijenja stanje entiteta.

### BM-GL-06 — Organizator

> Poslovni entitet i nosilac sadržaja u Kalendaru kulture. Organizator nije korisnik sistema i nije korisnička uloga. Operativno upravljanje sadržajem u ime Organizatora obavljaju Moderatori.

### BM-GL-07 — Moderator

> Ovlašćeni predstavnik Organizatora koji u ime Organizatora koristi Kalendar kulture.
>
> Moderator je zasebna poslovna uloga i nije isto što i Urednik.
>
> Moderator upravlja podacima Organizatora, Manifestacijama i Događajima u skladu sa dodijeljenim ovlašćenjima.

### BM-GL-08 — Urednik

> Administrator Uredničkog portala Kalendara kulture, odgovoran za pregled, uređivanje, odobravanje i objavljivanje sadržaja.
>
> Uloga Urednika je isključiva unutar poslovnog modela Kalendara kulture. Urednik nije Organizator i nije Moderator Organizatora.

### BM-GL-09 — Administrator platforme

> Korisnik odgovoran za administraciju platforme, upravljanje korisnicima, sistemskim podešavanjima i evidencijom aktivnosti.
>
> Administrator platforme ne učestvuje u uredničkom procesu.

### BM-GL-10 — Događaj

> Osnovna poslovna cjelina Kalendara kulture koja predstavlja pojedinačni kulturni sadržaj namijenjen objavljivanju.
>
> Događaj može imati jedno ili više održavanja.

### BM-GL-22 — Održavanje događaja

> Jedno konkretno održavanje jednog događaja, sa sopstvenim terminom i, kada je primjenjivo, lokacijom, statusom i drugim poslovnim svojstvima.
>
> Jedan događaj može imati jedno ili više održavanja. Održavanje nije isto što i Termin. Datum održavanja je obavezan, a vrijeme može biti definisano.

### BM-GL-11 — Manifestacija

> Poslovna cjelina koja povezuje više međusobno povezanih Događaja u okviru jedinstvenog programa.

### BM-GL-12 — Termin

> Datum održavanja je obavezan, a vrijeme može biti definisano. Termin nije samostalan poslovni entitet.
>
> Termin uvijek postoji u kontekstu održavanja događaja i ne smije se koristiti kao sinonim za održavanje događaja niti za entitet koji ima lokaciju, status, audit ili sopstveni životni ciklus.

### BM-GL-13 — Lokacija

> Mjesto na kojem se događaj konkretno održava. Lokacija pripada održavanju događaja.

### BM-GL-14 — Kategorija

> Poslovna klasifikacija Događaja koja omogućava njegovo grupisanje i pretragu.

### BM-GL-15 — Mediji

> Fotografije, dokumenti i drugi digitalni prilozi povezani sa Organizatorom, Manifestacijom ili Događajem.

### BM-GL-16 — Korisnički portal

> Dio Kalendara kulture namijenjen korisnicima za pregled kulturnih događaja i korišćenje funkcionalnosti dostupnih u skladu sa njihovim ovlašćenjima.

### BM-GL-17 — Urednički portal

> Dio sistema namijenjen Urednicima za upravljanje poslovnim procesom pregleda, uređivanja, odobravanja i objavljivanja sadržaja.

### BM-GL-18 — Sistemska administracija

> Dio sistema namijenjen Administratoru platforme za upravljanje korisnicima, sistemskim podešavanjima i administrativnim funkcijama.

### BM-GL-19 — Newsletter

> Funkcionalnost namijenjena informisanju korisnika o novoobjavljenim kulturnim događajima i o poslovno značajnim promjenama događaja koje utiču na odluku o prisustvu.
>
> Newsletter nije dio uredničkog procesa niti predstavlja poslovno obavještenje. Javno objavljivanje događaja predstavlja poslovni okidač za prvo uključivanje; otkazivanje, odlaganje i promjena datuma, vremena ili lokacije takođe predstavljaju Newsletter okidače.

### BM-GL-20 — Evidencija aktivnosti

> Evidencija aktivnosti predstavlja skup poslovno značajnih zapisa koji omogućavaju reviziju, kontrolu, odgovornost korisnika i naknadnu provjeru izvršenih radnji.

### BM-GL-21 — Završna odredba

> Pojmovi definisani ovim poglavljem imaju isto značenje u svim dijelovima Business Modela, osim ako je za pojedinu poslovnu cjelinu izričito drugačije određeno.
>
> Dosljedna primjena ovih definicija obezbjeđuje jedinstveno tumačenje poslovnih pravila i terminologije kroz cjelokupnu dokumentaciju modula Kalendar kulture.

---

# BM-17 Arhitektura poslovnih cjelina

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definiše se poslovna arhitektura modula Kalendar kulture i međusobni odnos njegovih poslovnih cjelina.

Arhitektura poslovnih cjelina određuje odgovornosti, granice i međusobnu saradnju pojedinih dijelova sistema u skladu sa ovim Business Modelom.

## Poslovna arhitektura

### BM-AR-01 — Poslovne cjeline sistema

> Poslovnu arhitekturu modula Kalendar kulture čine sljedeće poslovne cjeline:
>
> * Portal Kalendara kulture
> * Urednički portal
> * Sistemska administracija
>
> Sve poslovne cjeline predstavljaju sastavne djelove jedinstvenog poslovnog sistema.

### BM-AR-02 — Portal Kalendara kulture

> Portal Kalendara kulture predstavlja javni dio sistema namijenjen pregledanju objavljenih kulturnih događaja i korišćenju javno dostupnih funkcionalnosti.
>
> Portal prikazuje isključivo javno objavljen sadržaj.

### BM-AR-03 — Urednički portal

> Urednički portal predstavlja poslovnu cjelinu namijenjenu Moderatorima i Urednicima za upravljanje kulturnim sadržajem i uredničkim procesom.
>
> Poslovna pravila rada Uredničkog portala definisana su odgovarajućim poglavljima ovog Business Modela.

### BM-AR-04 — Sistemska administracija

> Sistemska administracija predstavlja poslovnu cjelinu namijenjenu Administratoru platforme za upravljanje korisnicima, sistemskim podešavanjima, administrativnim funkcijama i tehničkim održavanjem sistema.
>
> Administrator platforme ne učestvuje u uredničkom procesu, osim kada je to ovim Business Modelom izričito definisano.

### BM-AR-05 — Poslovna nezavisnost

> Svaka poslovna cjelina ima jasno definisanu poslovnu odgovornost.
>
> Poslovne cjeline međusobno sarađuju kroz poslovne procese definisane ovim Business Modelom, pri čemu zadržavaju svoju poslovnu nezavisnost.

### BM-AR-06 — Jedinstveni poslovni model

> Sve poslovne cjeline koriste zajedničke poslovne entitete, poslovna pravila i definicije utvrđene ovim Business Modelom.
>
> Poslovni podaci predstavljaju jedinstven izvor podataka bez obzira na poslovnu cjelinu kroz koju se koriste.

### BM-AR-07 — Razdvajanje odgovornosti

> Poslovne odgovornosti pojedinih poslovnih cjelina ne smiju se preklapati, osim kada je to izričito definisano ovim Business Modelom.
>
> Prava pristupa, ovlašćenja i poslovne odgovornosti određuju se u skladu sa ulogom korisnika i poslovnom cjelinom kojoj pripadaju.

### BM-AR-08 — Završna odredba

> Arhitektura poslovnih cjelina predstavlja osnov za organizaciju svih poslovnih procesa modula Kalendar kulture.
>
> Sve poslovne cjeline primjenjuju jedinstvena poslovna pravila i međusobno funkcionišu kao sastavni djelovi jedinstvenog informacionog sistema.
