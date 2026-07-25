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
| BM-06 Termin | USVOJENO |
| BM-07 Lokacija | USVOJENO |
| BM-08 Kategorije i oznake | USVOJENO |
| BM-09 Mediji | USVOJENO |
| BM-10 Statusi i životni ciklus događaja | USVOJENO |
| BM-11 Portal Kalendara kulture | USVOJENO |
| BM-12 Urednički portal | NIJE ZAPOČETO |
| BM-13 Newsletter | USVOJENO |
| BM-14 Evidencija aktivnosti (Audit log) | USVOJENO |
| BM-15 Opšta poslovna pravila | USVOJENO |
| BM-16 Rječnik poslovnih pojmova | USVOJENO |
| BM-17 Arhitektura poslovnih cjelina | NIJE ZAPOČETO |

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
   - BM-06 Termin
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

Poslovna cjelina Organizator definiše ko može biti nosilac sadržaja u modulu Kalendar kulture, na koji način stiče taj status, koja su njegova ovlašćenja nad sadržajem i kakav je njegov odnos prema ostalim poslovnim cjelinama.

## 2. Poslovni opis

Organizator je poslovna uloga na platformi Digital Kotor. Organizator može biti fizičko ili pravno lice registrovano na platformi. Organizator je nosilac sadržaja u modulu Kalendar kulture.

## 3. Poslovni koncept

Korisnik podnosi zahtjev „Postani organizator“. Korisnik stiče status Organizatora nakon odobrenja Urednika.

Organizator može kreirati sadržaj, uređivati sadržaj, čuvati nacrt sadržaja i poslati sadržaj Uredniku na odobravanje. Organizator ne može samostalno objaviti sadržaj.

Organizator ovlašćuje jednog ili više Moderatora organizatora da upravljaju sadržajem u njegovo ime. Sadržaj koji Moderator organizatora kreira ili uređuje vodi se u ime Organizatora.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-ORG-01 | Organizator može biti fizičko ili pravno lice registrovano na platformi Digital Kotor. |
| BM-ORG-02 | Korisnik podnosi zahtjev za sticanje statusa Organizatora kroz funkcionalnost „Postani organizator“. |
| BM-ORG-03 | Korisnik stiče status Organizatora nakon odobrenja Urednika. |
| BM-ORG-04 | Organizator može kreirati, uređivati i čuvati nacrt sadržaja, kao i poslati sadržaj Uredniku na odobravanje. |
| BM-ORG-05 | Organizator ne može samostalno objaviti sadržaj. |
| BM-ORG-06 | Organizator ovlašćuje jednog ili više Moderatora organizatora da upravljaju sadržajem u njegovo ime. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Moderator organizatora** — Organizator ovlašćuje jednog ili više Moderatora organizatora da upravljaju sadržajem u njegovo ime. Obaveza da Organizator ima najmanje jednog Moderatora organizatora definisana je u poglavlju BM-02 Moderator organizatora. Mogućnost da isti korisnik istovremeno ima ulogu Organizatora i Moderatora organizatora, kao i primjena ovlašćenja prema ulozi koju korisnik trenutno koristi, definisane su u poglavlju BM-02 Moderator organizatora.
- **Urednik** — odobrava zahtjev za sticanje statusa Organizatora, te odobrava i objavljuje sadržaj koji Organizator pošalje na odobravanje.
- **Događaj** — događaj se vodi u ime Organizatora.
- **Manifestacija** — Organizator može biti povezan sa Manifestacijama u skladu sa poslovnim pravilima Manifestacije.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-02 Moderator organizatora

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Moderator organizatora definiše ulogu ovlašćenog korisnika koji upravlja sadržajem u ime Organizatora, obim njegovih ovlašćenja, pravila o broju Moderatora organizatora i postupak njihovog uklanjanja.

## 2. Poslovni opis

Moderator organizatora je poslovna uloga koja upravlja sadržajem u modulu Kalendar kulture u ime Organizatora.

## 3. Poslovni koncept

Moderator organizatora ne postaje nosilac sadržaja. Sadržaj koji Moderator organizatora kreira ili uređuje vodi se u ime Organizatora koji ga je ovlastio.

Moderator organizatora može obavljati sve radnje nad sadržajem koje može obavljati Organizator, osim samostalne objave sadržaja. To obuhvata kreiranje sadržaja, uređivanje sadržaja, čuvanje nacrta i slanje sadržaja Uredniku na odobravanje. Sadržaj koji Moderator organizatora kreira ili uređuje mora biti poslat Uredniku na odobravanje prije objave.

Jedan korisnik može biti Moderator organizatora za jednog ili više Organizatora. Isti korisnik može istovremeno imati ulogu Organizatora i Moderatora organizatora, a sistem u tom slučaju primjenjuje ovlašćenja u skladu sa ulogom koju korisnik trenutno koristi.

Organizator može imati jednog ili više Moderatora organizatora i mora imati najmanje jednog Moderatora organizatora. Moderator organizatora može pokrenuti postupak uklanjanja drugog Moderatora organizatora istog Organizatora, a uklanjanje odobrava Urednik.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-MOD-01 | Moderator organizatora upravlja sadržajem u ime Organizatora. |
| BM-MOD-02 | Jedan korisnik može biti Moderator organizatora za jednog ili više Organizatora. |
| BM-MOD-03 | Jedan korisnik može istovremeno imati ulogu Organizatora i Moderatora organizatora. |
| BM-MOD-04 | Kada korisnik ima više uloga, sistem primjenjuje ovlašćenja u skladu sa ulogom koju korisnik trenutno koristi. |
| BM-MOD-05 | Moderator organizatora može obavljati sve radnje nad sadržajem koje može obavljati Organizator, osim samostalne objave sadržaja. |
| BM-MOD-06 | Sadržaj koji kreira ili uređuje Moderator organizatora mora biti poslat Uredniku na odobravanje prije objave. |
| BM-MOD-07 | Organizator mora imati najmanje jednog Moderatora organizatora. |
| BM-MOD-08 | Moderator organizatora može pokrenuti postupak uklanjanja drugog Moderatora organizatora istog Organizatora. |
| BM-MOD-09 | Moderator organizatora smatra se uklonjenim tek nakon odobrenja Urednika. |
| BM-MOD-10 | Sistem neće dozvoliti uklanjanje posljednjeg Moderatora organizatora. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Organizator** — ovlašćuje Moderatora organizatora i ostaje nosilac sadržaja.
- **Urednik** — pregleda i odobrava sadržaj koji Moderator organizatora pošalje na odobravanje, te odobrava uklanjanje Moderatora organizatora.
- **Događaj** — Moderator organizatora kreira i uređuje događaj u ime Organizatora.
- **Lokacija** — Moderator organizatora može predlagati nove lokacije u skladu sa poslovnim pravilima Lokacije.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-03 Urednik

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Urednik definiše ulogu koja odobrava sticanje statusa Organizatora, obavlja urednički pregled, odobravanje i objavu sadržaja u modulu Kalendar kulture, te odobrava uklanjanje Moderatora organizatora.

## 2. Poslovni opis

Urednik je poslovna uloga koja odobrava zahtjeve za sticanje statusa Organizatora, pregleda, uređuje, odobrava i objavljuje događaje, vraća ih na doradu kada su potrebne suštinske izmjene i odobrava uklanjanje Moderatora organizatora.

## 3. Poslovni koncept

Urednik obezbjeđuje kvalitet i dosljednost javno objavljenog sadržaja kroz pregled, uređivanje, vraćanje na doradu, odobravanje i objavljivanje događaja. Sadržaj koji pošalju Organizator ili Moderator organizatora Urednik pregleda i odobrava prije objave. Objavu sadržaja vrši isključivo Urednik.

Urednik može samostalno kreirati, uređivati i objaviti događaj kada za događaj nije registrovan Organizator na platformi Digital Kotor ili kada postoji opravdan javni interes za njegovo objavljivanje. Kada se za takav događaj evidentira registrovani Organizator, upravljanje događajem može biti preneseno tom Organizatoru u skladu sa poslovnim pravilima sistema.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-UR-01 | Urednik odobrava zahtjev za sticanje statusa Organizatora. |
| BM-UR-02 | Urednik pregleda, uređuje, odobrava i objavljuje događaje. |
| BM-UR-03 | Urednik vraća događaje na doradu kada su potrebne suštinske izmjene. |
| BM-UR-04 | Urednik pregleda i odobrava sadržaj koji šalju Organizator ili Moderator organizatora. |
| BM-UR-05 | Urednik odobrava uklanjanje Moderatora organizatora. |
| BM-UR-06 | Urednik može samostalno kreirati, uređivati i objaviti događaj kada za događaj nije registrovan Organizator na platformi Digital Kotor ili kada postoji opravdan javni interes za njegovo objavljivanje. |
| BM-UR-07 | Kada se za događaj evidentira registrovani Organizator, upravljanje događajem koji je kreirao Urednik može biti preneseno tom Organizatoru u skladu sa poslovnim pravilima sistema. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Organizator** — Urednik odobrava zahtjev za sticanje statusa Organizatora i može mu prenijeti upravljanje događajem kreiranim bez registrovanog Organizatora.
- **Moderator organizatora** — Urednik pregleda i odobrava sadržaj koji Moderator organizatora pošalje na odobravanje i odobrava uklanjanje Moderatora organizatora.
- **Događaj** — Urednik pregleda, uređuje, odobrava, objavljuje i vraća na doradu događaje, a u propisanim slučajevima može i kreirati događaj.
- **Lokacija** — Urednik odobrava ili odbija nove lokacije predložene za zajednički katalog lokacija.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-04 Događaj

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Događaj definiše osnovnu programsku cjelinu Kalendara kulture, njena osnovna svojstva, odnos prema terminima, manifestaciji, organizatoru, kategoriji i lokaciji, te pravila arhiviranja i otkazivanja.

## 2. Poslovni opis

Događaj predstavlja osnovnu programsku cjelinu Kalendara kulture koja opisuje kulturni sadržaj. Događaj može imati jedan ili više termina održavanja.

## 3. Poslovni koncept

Događaj može biti kreiran bez definisanog termina isključivo dok se nalazi u statusu Nacrt. Za slanje događaja na odobrenje mora biti definisan najmanje jedan termin održavanja. Objavljeni događaj uvijek mora imati najmanje jedan termin.

Događaj može biti samostalan ili biti dio jedne manifestacije. Pripadnost manifestaciji nije obavezna.

Lokacija nije svojstvo događaja već svojstvo termina. Svaki termin može imati svoju lokaciju.

Događaj pripada jednoj primarnoj kategoriji. Dodatna klasifikacija događaja može se vršiti korišćenjem oznaka (tagova). Događaj može biti sačuvan kao nacrt bez izabrane primarne kategorije. Za slanje događaja na odobrenje mora biti izabrana jedna primarna kategorija. Svaki objavljeni događaj mora imati jednu primarnu kategoriju.

Svaki događaj mora imati organizatora. Ako organizator nije registrovan u sistemu, Urednik može kreirati događaj u njegovo ime, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik.

Nakon isteka posljednjeg termina održavanja sistem automatski arhivira događaj. Arhiviranje se ne izvršava ručno.

Događaj može biti otkazan. Otkazani događaj ostaje evidentiran u sistemu i dobija status „Otkazan“.

Pojedinačni termin događaja može biti otkazan bez uticaja na ostale termine istog događaja.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-DG-01 | Događaj može biti kreiran bez definisanog termina isključivo dok se nalazi u statusu Nacrt. Za slanje događaja na odobrenje mora biti definisan najmanje jedan termin održavanja. Objavljeni događaj uvijek mora imati najmanje jedan termin. |
| BM-DG-02 | Događaj može biti samostalan ili biti dio jedne manifestacije. Pripadnost manifestaciji nije obavezna. Detaljna pravila definišu se u BM-05 Manifestacija. |
| BM-DG-03 | Lokacija nije svojstvo događaja već svojstvo termina. Svaki termin može imati svoju lokaciju. Detaljna pravila definišu se u BM-07 Lokacija. |
| BM-DG-04 | Nakon isteka posljednjeg termina održavanja sistem automatski arhivira događaj. Arhiviranje se ne izvršava ručno. Detaljna pravila prikaza arhive definišu se u BM-11 Portal Kalendara kulture. |
| BM-DG-05 | Događaj može biti otkazan. Otkazani događaj ostaje evidentiran u sistemu i dobija status „Otkazan“. Pojedinačni termin događaja može biti otkazan bez uticaja na ostale termine istog događaja. Detaljna pravila za termine definišu se u BM-06 Termin. |
| BM-DG-06 | Događaj pripada jednoj primarnoj kategoriji. Dodatna klasifikacija događaja može se vršiti korišćenjem oznaka (tagova). Detaljna pravila o kategorijama i oznakama (tagovima) definišu se u BM-08 Kategorija. |
| BM-DG-07 | Događaj može biti sačuvan kao nacrt bez izabrane primarne kategorije. Za slanje događaja na odobrenje mora biti izabrana jedna primarna kategorija. Svaki objavljeni događaj mora imati jednu primarnu kategoriju. |
| BM-DG-08 | Svaki događaj mora imati organizatora. Ako organizator nije registrovan u sistemu, Urednik može kreirati događaj u njegovo ime, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik. |

## 5. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-05 Manifestacija

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Manifestacija definiše njena osnovna svojstva, odnos prema Događajima i Terminima, način određivanja trajanja, te pravila uređivanja, odobravanja, otkazivanja i arhiviranja.

## 2. Poslovni opis

Manifestacija predstavlja zasebnu programsku cjelinu Kalendara kulture koja pod zajedničkim nazivom, identitetom i programskim okvirom objedinjuje jedan ili više povezanih Događaja.

## 3. Poslovni koncept

Manifestacija može biti kreirana bez Događaja isključivo dok se nalazi u statusu Nacrt. Za slanje Manifestacije na odobrenje mora sadržati najmanje jedan Događaj. Objavljena Manifestacija mora sadržati najmanje jedan Događaj.

Manifestacija može sadržati jedan ili više Događaja. Jedan Događaj može pripadati najviše jednoj Manifestaciji, a pripadnost Događaja Manifestaciji nije obavezna.

Manifestacija nema sopstvene Termine. Termine održavanja imaju isključivo Događaji koji pripadaju Manifestaciji.

Početak Manifestacije određuje se najranijim Terminom svih Događaja koji joj pripadaju, a završetak posljednjim Terminom svih Događaja koji joj pripadaju. Trajanje Manifestacije sistem određuje automatski na osnovu Termina Događaja.

Manifestacija se automatski arhivira nakon isteka posljednjeg Termina posljednjeg Događaja koji joj pripada. Arhiviranje se ne izvršava ručno.

Manifestacija može biti otkazana. Otkazana Manifestacija ostaje evidentirana u sistemu i dobija status „Otkazana“. Otkazivanje Manifestacije ne briše njene Događaje. Pravila za status Događaja uređuju se u skladu sa poslovnim pravilima definisanim u BM-04 Događaj.

Manifestacija predstavlja samostalnu programsku cjelinu i ima sopstvene podatke, uključujući naziv, opis, naslovnu fotografiju i ostale pripadajuće informacije. Manifestacija ne nasljeđuje ove podatke od Događaja koji joj pripadaju.

Manifestaciju može kreirati Organizator ili Moderator organizatora u ime svog Organizatora. Urednik može kreirati Manifestaciju u ime bilo kojeg Organizatora, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik.

Manifestacija može biti sačuvana u statusu Nacrt. Dok se nalazi u statusu Nacrt, može se slobodno uređivati. Za slanje na odobrenje mora ispunjavati poslovna pravila definisana u BM-MF-02 i ostala pravila propisana ovim poglavljem.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-MF-01 | Manifestacija predstavlja zasebnu programsku cjelinu Kalendara kulture koja pod zajedničkim nazivom, identitetom i programskim okvirom objedinjuje jedan ili više povezanih Događaja. |
| BM-MF-02 | Manifestacija može biti kreirana bez Događaja isključivo dok se nalazi u statusu Nacrt. Za slanje Manifestacije na odobrenje mora sadržati najmanje jedan Događaj. Objavljena Manifestacija mora sadržati najmanje jedan Događaj. |
| BM-MF-03 | Manifestacija može sadržati jedan ili više Događaja. Jedan Događaj može pripadati najviše jednoj Manifestaciji. Pripadnost Događaja Manifestaciji nije obavezna. Detaljna pravila za Događaje definišu se u BM-04 Događaj. |
| BM-MF-04 | Manifestacija nema sopstvene Termine. Termine održavanja imaju isključivo Događaji koji pripadaju Manifestaciji. Detaljna pravila za Termine definišu se u BM-06 Termin. |
| BM-MF-05 | Početak Manifestacije određuje se najranijim Terminom svih Događaja koji joj pripadaju. Završetak Manifestacije određuje se posljednjim Terminom svih Događaja koji joj pripadaju. Trajanje Manifestacije sistem određuje automatski na osnovu Termina Događaja. |
| BM-MF-06 | Manifestacija se automatski arhivira nakon isteka posljednjeg Termina posljednjeg Događaja koji joj pripada. Arhiviranje se ne izvršava ručno. Detaljna pravila prikaza arhive definišu se u BM-11 Portal Kalendara kulture. |
| BM-MF-07 | Manifestacija može biti otkazana. Otkazana Manifestacija ostaje evidentirana u sistemu i dobija status „Otkazana“. Otkazivanje Manifestacije ne briše njene Događaje. Pravila za status Događaja uređuju se u skladu sa poslovnim pravilima definisanim u BM-04 Događaj. |
| BM-MF-08 | Manifestacija predstavlja samostalnu programsku cjelinu i ima sopstvene podatke, uključujući naziv, opis, naslovnu fotografiju i ostale pripadajuće informacije. Manifestacija ne nasljeđuje ove podatke od Događaja koji joj pripadaju. Detaljna pravila za Medije definišu se u BM-09 Mediji. |
| BM-MF-09 | Manifestaciju može kreirati Organizator ili Moderator organizatora u ime svog Organizatora. Urednik može kreirati Manifestaciju u ime bilo kojeg Organizatora, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik. |
| BM-MF-10 | Manifestacija može biti sačuvana u statusu Nacrt. Dok se nalazi u statusu Nacrt, može se slobodno uređivati. Za slanje na odobrenje mora ispunjavati poslovna pravila definisana u BM-MF-02 i ostala pravila propisana ovim poglavljem. |

## 5. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-06 Termin

**Status poglavlja:** USVOJENO

## 1. Svrha

Svrha ovog poglavlja je definisanje poslovnog koncepta termina kao pojedinačnog održavanja događaja, njegovog vremenskog određenja, lokacije, ponavljanja, statusa i drugih osnovnih poslovnih svojstava.

## 2. Poslovni opis

Termin predstavlja pojedinačno održavanje događaja. Jedan događaj može imati jedan ili više termina.

Termin se ne posmatra kao samostalan programski sadržaj, već uvijek pripada jednom događaju.

## 3. Poslovni koncept

Termin omogućava da se za jedan događaj evidentira jedno ili više pojedinačnih održavanja, uključujući cjelodnevne, ponavljajuće, izmijenjene, odgođene ili otkazane termine.

Svaki termin ima sopstveno vremensko određenje i status, dok lokacija i informacije o ulaznicama mogu biti opcione.

## 4. Poslovna pravila

### BM-TR-01 — Definicija termina

> Termin predstavlja pojedinačno održavanje događaja u tačno određenom vremenu i, po potrebi, na određenoj lokaciji. Jedan događaj može imati jedan ili više termina održavanja.

### BM-TR-02 — Veza termina i događaja

> Termin uvijek pripada jednom događaju. Termin ne može postojati samostalno niti može biti povezan sa više događaja.

### BM-TR-03 — Obavezni vremenski podaci

> Svaki termin mora imati definisan datum i vrijeme početka, kao i datum i vrijeme završetka. Ostali podaci termina uređuju se posebnim poslovnim pravilima i mogu biti opcioni.

### BM-TR-04 — Lokacija termina

> Termin može biti definisan bez lokacije. Kada je lokacija definisana, ona predstavlja svojstvo termina i uređuje se u skladu sa poslovnim pravilima definisanim u BM-07 Lokacija.

### BM-TR-05 — Cjelodnevni termin

> Termin može biti označen kao cjelodnevni. Za cjelodnevni termin nije obavezno definisati vrijeme početka i završetka, dok datum početka i završetka ostaju obavezni.

### BM-TR-06 — Ponavljanje termina

> Termini događaja mogu biti kreirani kao pojedinačni ili kao ponavljajući. Sistem podržava dnevno, sedmično i mjesečno ponavljanje, kao i ručno dodavanje pojedinačnih termina.

### BM-TR-07 — Izuzeci u ponavljajućoj seriji

> Pojedinačni termin u okviru ponavljajuće serije može biti izmijenjen ili otkazan bez uticaja na ostale termine iste serije. Izmjene i otkazivanja primjenjuju se isključivo na odabrani termin.

### BM-TR-08 — Izmjena objavljenog termina

> Termin objavljenog događaja može se izmijeniti. Sve izmjene podliježu istim pravilima uređivanja i odobravanja koja važe za događaj, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik.

### BM-TR-09 — Status termina

> Svaki termin ima vlastiti status, nezavisno od ostalih termina istog događaja. Status termina određuje njegovo trenutno stanje i može biti različit od statusa drugih termina događaja.

### BM-TR-10 — Dozvoljeni statusi termina

> Termin može imati jedan od sljedećih statusa:
>
> * **Planiran** — termin je aktivan i biće održan prema objavljenim podacima.
> * **Otkazan** — termin neće biti održan.
> * **Odgođen** — termin neće biti održan u planiranom vremenu i očekuje se novi datum ili vrijeme održavanja.
> * **Završen** — termin je održan ili je istekao.
>
> Status **Završen** sistem dodjeljuje automatski nakon isteka termina.

### BM-TR-11 — Ulaznice i cijena

> Termin može sadržati informacije o ulaznicama i cijeni. Podaci o ulaznicama su opcioni i definišu se za svaki termin pojedinačno.

## 5. Otvorena pitanja

Za poglavlje BM-06 trenutno nema otvorenih poslovnih pitanja.

Teme koje nijesu obuhvaćene ovim poglavljem ne treba dodavati bez nove, izričito usvojene poslovne odluke i narednog numerisanog PATCH-a.

---

# BM-07 Lokacija

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnog koncepta lokacije kao mjesta održavanja termina događaja i pravila njenog korišćenja u sistemu.

## 2. Poslovni opis

Lokacija predstavlja mjesto održavanja jednog ili više termina događaja.

Lokacija nije vezana za događaj već za termin, u skladu sa ranije usvojenim poslovnim pravilima.

## 3. Poslovni koncept

Lokacije predstavljaju zajednički poslovni resurs koji može koristiti više događaja kroz njihove termine.

## 4. Poslovna pravila

### BM-LK-01 — Definicija lokacije

> Lokacija predstavlja mjesto održavanja jednog ili više termina događaja. Lokacija može biti unaprijed definisana ili određena naknadno, u skladu sa poslovnim pravilima sistema.

### BM-LK-02 — Ponovna upotreba lokacije

> Jedna lokacija može biti povezana sa jednim ili više termina različitih događaja. Lokacija se koristi kao zajednički poslovni entitet i ne kreira se ponovo za svaki termin.

### BM-LK-03 — Naziv lokacije

> Lokacija mora imati naziv. Ostali podaci o lokaciji uređuju se posebnim poslovnim pravilima i mogu biti opcioni.

### BM-LK-04 — Naknadno određivanje lokacije

> Lokacija može biti definisana ili određena naknadno, u skladu sa potrebama organizacije događaja.

### BM-LK-05 — Aktivnost lokacije

> Lokacija može biti aktivna ili neaktivna. Neaktivna lokacija ne može se koristiti za nove termine događaja, ali ostaje povezana sa postojećim terminima radi očuvanja istorijskih podataka.

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

> Organizator može predlagati medije kroz uređivanje svojih događaja, dok urednik upravlja medijima u postupku odobravanja i objavljivanja sadržaja.

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

> Svaki novi događaj nastaje u statusu Nacrt. Događaj u statusu Nacrt nije vidljiv na javnom portalu i može ga uređivati organizator ili urednik, u skladu sa poslovnim pravilima sistema. Ukoliko događaj nema registrovanog organizatora, uređivanje nacrta vrši urednik. Događaj u statusu Nacrt može biti sačuvan bez svih podataka potrebnih za njegovo objavljivanje.

### BM-ST-04 — Slanje na odobrenje i objavljivanje

> Događaj u statusu Nacrt koji je kreirao organizator može biti poslat na odobrenje kada ispunjava poslovne uslove za pregled od strane urednika. Slanjem na odobrenje status događaja se mijenja u Na odobrenju. Događaj u statusu Nacrt koji je kreirao urednik može biti direktno objavljen, bez postupka odobravanja.

### BM-ST-05 — Vraćanje na doradu

> Urednik može vratiti događaj u status Nacrt radi dorade. Vraćanjem na doradu status događaja se mijenja iz Na odobrenju u Nacrt, uz obrazloženje razloga vraćanja.

### BM-ST-06 — Objavljivanje događaja

> Objavljivanjem događaja njegov status se mijenja u Objavljen. Objavljen događaj postaje vidljiv na javnom portalu u skladu sa poslovnim pravilima sistema. Objavljen događaj može se naknadno uređivati u skladu sa poslovnim pravilima sistema.

### BM-ST-07 — Otkazivanje događaja

> Objavljen događaj može biti otkazan u skladu sa poslovnim pravilima sistema. Otkazivanjem status događaja se mijenja u Otkazan, pri čemu događaj ostaje dostupan radi očuvanja istorijskih podataka i informisanja javnosti. Otkazan događaj može se ponovo objaviti ukoliko prestanu razlozi zbog kojih je otkazan.

### BM-ST-08 — Automatsko arhiviranje

> Događaj se automatski arhivira nakon isteka svih njegovih termina, u skladu sa poslovnim pravilima sistema. Arhiviran događaj ostaje dostupan radi očuvanja istorijskih podataka.

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

> Portal Kalendara kulture predstavlja funkcionalni dio platforme Digital Kotor. Upravljanje korisničkim identitetom, registracijom, prijavom i korisničkim profilom nije dio poslovnog domena Portala Kalendara kulture, već platforme Digital Kotor.

### BM-PK-03 — Pregled događaja

> Portal Kalendara kulture omogućava pregled događaja objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture. Pregled događaja obuhvata informacije potrebne za informisanje korisnika o održavanju kulturnih sadržaja.

### BM-PK-04 — Pregled manifestacija

> Portal Kalendara kulture omogućava pregled manifestacija objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture. Pregled manifestacije obuhvata informacije o manifestaciji i sa njom povezanim događajima.

### BM-PK-05 — Detaljan prikaz

> Portal Kalendara kulture omogućava pregled detaljnih informacija o objavljenim događajima i manifestacijama, uključujući sa njima povezane termine, lokacije, kategorije, oznake, medije i druge javno objavljene podatke u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-06 — Pretraga

> Portal Kalendara kulture omogućava pretragu objavljenih događaja i manifestacija korišćenjem kriterijuma definisanih poslovnim pravilima modula Kalendara kulture.

### BM-PK-07 — Filtriranje i sortiranje

> Portal Kalendara kulture omogućava filtriranje i sortiranje objavljenih događaja i manifestacija korišćenjem kriterijuma definisanih poslovnim pravilima modula Kalendara kulture.

### BM-PK-08 — Načini prikaza

> Portal Kalendara kulture omogućava prikaz objavljenih događaja i manifestacija kroz jedan ili više načina prikaza, u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-09 — Prikaz termina

> Portal Kalendara kulture omogućava pregled svih javno objavljenih termina događaja, u skladu sa poslovnim pravilima modula Kalendara kulture.

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

---

# BM-13 Newsletter

**Status poglavlja:** USVOJENO

## Poslovna pravila

### BM-NL-01 — Definicija

> Newsletter predstavlja funkcionalnost modula Kalendara kulture namijenjenu periodičnom informisanju zainteresovanih korisnika o javno objavljenim kulturnim događajima.

### BM-NL-02 — Svrha

> Newsletter služi isključivo informisanju korisnika o kulturnim događajima objavljenim u Kalendaru kulture.

### BM-NL-03 — Odnos prema uredničkom procesu

> Newsletter nije dio uredničkog procesa i ne koristi se za poslovnu komunikaciju između Organizatora, Moderatora, Urednika i Administratora platforme.

### BM-NL-04 — Pretplata

> Svaki korisnik može se dobrovoljno prijaviti na newsletter Kalendara kulture. Prijava na newsletter nije uslov za korišćenje Kalendara kulture.

### BM-NL-05 — Odjava

> Korisnik može u svakom trenutku odjaviti prijem newslettera.

### BM-NL-06 — Sadržaj newslettera

> Newsletter sadrži pregled kulturnih događaja odabranih u skladu sa poslovnim pravilima sistema. Način izbora i prikaza sadržaja newslettera definiše se funkcionalnom i tehničkom specifikacijom i nije predmet ovog Business Modela.

### BM-NL-07 — Periodično slanje

> Sistem može periodično slati newsletter svim aktivnim pretplatnicima. Učestalost i način slanja definišu se funkcionalnom i tehničkom specifikacijom i sistemskim podešavanjima.

### BM-NL-08 — Nezavisnost od poslovnih procesa

> Pretplata na newsletter nema uticaja na prava korisnika niti na poslovne procese definisane ovim Business Modelom. Poslovni procesi funkcionišu nezavisno od prijave ili odjave korisnika na newsletter.

### BM-NL-09 — Objavljeni sadržaj

> Newsletter može sadržati isključivo događaje koji su javno objavljeni u Kalendaru kulture. Događaji koji nijesu objavljeni ne mogu biti uključeni u newsletter.

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

> Evidencija aktivnosti obuhvata poslovno značajne aktivnosti koje se odnose na poslovne objekte i administrativne funkcije definisane ovim Business Modelom. Poslovne aktivnosti koje se evidentiraju za pojedine oblasti definišu se funkcionalnom i tehničkom specifikacijom u skladu sa ovim Business Modelom.

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

## Poslovni pojmovi

### BM-GL-01 — Entitet

> Poslovna cjelina kojom sistem upravlja i o kojoj vodi podatke.
>
> Primjeri entiteta su Organizator, Moderator, Manifestacija, Događaj, Termin, Lokacija i Kategorija.

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

> Pravno ili fizičko lice koje organizuje kulturne događaje i koristi Kalendar kulture za njihovu prijavu i upravljanje.

### BM-GL-07 — Moderator

> Ovlašćeni predstavnik Organizatora koji u ime Organizatora koristi Kalendar kulture.
>
> Moderator upravlja podacima Organizatora, Manifestacijama i Događajima u skladu sa dodijeljenim ovlašćenjima.

### BM-GL-08 — Urednik

> Korisnik odgovoran za pregled, uređivanje, odobravanje i objavljivanje sadržaja u Kalendaru kulture.

### BM-GL-09 — Administrator platforme

> Korisnik odgovoran za administraciju platforme, upravljanje korisnicima, sistemskim podešavanjima i evidencijom aktivnosti.
>
> Administrator platforme ne učestvuje u uredničkom procesu.

### BM-GL-10 — Događaj

> Osnovna poslovna cjelina Kalendara kulture koja predstavlja pojedinačni kulturni sadržaj namijenjen objavljivanju.
>
> Događaj može imati jedan ili više Termina.

### BM-GL-11 — Manifestacija

> Poslovna cjelina koja povezuje više međusobno povezanih Događaja u okviru jedinstvenog programa.

### BM-GL-12 — Termin

> Pojedinačno održavanje Događaja koje definiše vrijeme i mjesto njegovog održavanja.

### BM-GL-13 — Lokacija

> Mjesto na kojem se održava jedan ili više Termina različitih Događaja.

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

> Funkcionalnost namijenjena periodičnom informisanju korisnika o kulturnim događajima.
>
> Newsletter nije dio uredničkog procesa niti predstavlja poslovno obavještenje.

### BM-GL-20 — Evidencija aktivnosti

> Evidencija aktivnosti predstavlja skup poslovno značajnih zapisa koji omogućavaju reviziju, kontrolu, odgovornost korisnika i naknadnu provjeru izvršenih radnji.

### BM-GL-21 — Završna odredba

> Pojmovi definisani ovim poglavljem imaju isto značenje u svim dijelovima Business Modela, osim ako je za pojedinu poslovnu cjelinu izričito drugačije određeno.
>
> Dosljedna primjena ovih definicija obezbjeđuje jedinstveno tumačenje poslovnih pravila i terminologije kroz cjelokupnu dokumentaciju modula Kalendar kulture.
