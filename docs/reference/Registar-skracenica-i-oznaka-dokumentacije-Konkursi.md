# Digital Kotor
# Registar skraćenica i oznaka dokumentacije Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-RG-001
**Naziv:** Registar skraćenica i oznaka dokumentacije Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** U IZRADI
**Verzija:** 0.1.10
**Datum:** 2026-08-26

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Uspostavljen KN-RG-001 kao jedinstveni registar oznaka modula Konkursi. Razdvojeni namespace cijelog modula i obuhvat trenutne dokumentacione faze. Razdvojeni zajednički konfigurabilni okvir od zasebnih poslovnih i funkcionalnih profila tipa konkursa. Registrovan kanonski lanac KN-RG-001 → KN-BM-001 → KN-BM-002 → KN-FS-001 → KN-FS-002 → KN-TS-001. Uvedeno pravilo da se oznake evidentiraju u istom koraku u kojem se prvi put koriste. Opcioni tipovi dokumenata, PATCH instance i interne oznake nijesu rezervisani unaprijed. |
| 0.1.1 | 2026-08-19 | Evidentirana struktura interne oznake `BM-KN-NNN` i ažuriran status `KN-BM-001` na `U IZRADI`. |
| 0.1.2 | 2026-08-24 | Evidentirane prve konkretne zajedničke poslovne oznake `BM-KN-001`–`BM-KN-013`. |
| 0.1.3 | 2026-08-24 | Evidentirano zajedničko poslovno pravilo `BM-KN-014`. |
| 0.1.4 | 2026-08-24 | Evidentirano zajedničko poslovno pravilo `BM-KN-015`. |
| 0.1.5 | 2026-08-25 | Ažuriran status `KN-BM-001` na `USVOJEN`. |
| 0.1.6 | 2026-08-25 | Ažuriran status `KN-BM-002` na `U IZRADI` i evidentirana struktura interne oznake `BM-ML-NNN`. Konkretne oznake profila mladih nijesu uvedene. |
| 0.1.7 | 2026-08-26 | Registrovan poslovni profil `KN-BM-003` (Žensko preduzetništvo). Evidentirana dva otvorena pravna pitanja tog profila, bez uvođenja novih skraćenica. |
| 0.1.8 | 2026-08-26 | U `KN-BM-003` v1.0.1 / `KN-PATCH-BM-001` zatvorena OPEN LEGAL ISSUE #1 i OPEN LEGAL ISSUE #2. Evidentiran `KN-PATCH-BM-001`. Nema aktivnih formalnih OPEN pitanja iz `KN-BM-003`. |
| 0.1.9 | 2026-08-26 | Registrovan funkcionalni profil `KN-FS-003` (Žensko preduzetništvo), status `U IZRADI`. `KN-FS-001` i `KN-FS-002` ostaju planirani. |
| 0.1.10 | 2026-08-26 | Evidentiran `KN-PATCH-BM-002` / `KN-BM-003` v1.0.2. Usklađeni tok Prijave, eliminatorni kriterijum 1 sa čl. 17, nacrt ocjena i nepromjenjivost nakon završavanja. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Dokument ima status `U IZRADI` i nije formalno usvojen. Dok dokument ima status `U IZRADI`, redakcijske korekcije koje ne mijenjaju značenje mogu se unositi u okviru iste radne verzije. Kada se odobrenim dokumentacionim korakom doda ili promijeni sadržaj, obuhvat ili usvojeno pravilo dokumenta, povećava se radna verzija i dodaje novi red u istoriju verzija. Više povezanih izmjena jednog odobrenog dokumentacionog koraka mogu se evidentirati kao jedna radna verzija. Postojeći redovi istorije verzija ne mijenjaju se. PATCH oznaka se ne izdaje dok dokument nije formalno usvojen.

---

## Svrha dokumenta

KN-RG-001 je referentni i živi dokument. Predstavlja jedinstveni registar skraćenica i dokumentacionih oznaka modula **Konkursi**.

Nije poslovni pojmovnik. Ne definiše poslovna pravila, uslove konkursa, rokove, iznose, bodovanje, uloge ni statuse. Ne zamjenjuje KN-BM, KN-FS ili KN-TS dokumente.

Uključene su samo oznake koje se stvarno koriste u dokumentaciji Konkursa. Oznake se ne uvode niti rezervišu unaprijed bez stvarne upotrebe.

Platformski dokumentacioni standard: `DK-DS-001`.
Proces: `docs/METHODOLOGY.md`.

---

# Status razvoja

| Poglavlje | Status |
|-----------|--------|
| 1. Identitet i svrha | U IZRADI |
| 2. Obuhvat registra | U IZRADI |
| 3. Pravilo namespace-a | U IZRADI |
| 4. Dokumentacione oznake | U IZRADI |
| 5. Opcioni tipovi dokumenata | U IZRADI |
| 6. PATCH model | U IZRADI |
| 7. Runtime vrijednosti | U IZRADI |
| 8. Interne oznake | U IZRADI |
| 9. Drugi moduli | U IZRADI |
| 10. Zabranjene oznake | U IZRADI |
| 11. Pravila održavanja | U IZRADI |

---

# Pravila upravljanja dokumentom

1. KN-RG-001 nije izvor istine za poslovna ili pravna pravila.
2. Prednost imaju vlasnički dokumenti: KN-BM, KN-FS, KN-TS.
3. Svaka nova skraćenica ili interna oznaka evidentira se u `KN-RG-001` u istom dokumentacionom koraku u kojem se prvi put koristi, uključujući dokumente sa statusom `U IZRADI`. Uz oznaku se navode značenje, namjena, dokument u kojem se koristi, tip konkursa kojem pripada i status. Oznake se ne uvode niti rezervišu unaprijed bez stvarne upotrebe.
4. Dokumenti sa statusom `U IZRADI` mogu se korigovati bez PATCH oznake dok nijesu formalno usvojeni.
5. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno uvoditi poslovna pravila niti oznake koje se još ne koriste.

---

## Sadržaj

1. Identitet i svrha
2. Obuhvat registra
3. Pravilo namespace-a
4. Dokumentacione oznake
5. Opcioni tipovi dokumenata
6. PATCH model
7. Runtime vrijednosti
8. Interne oznake
9. Drugi moduli
10. Zabranjene oznake
11. Pravila održavanja

---

# 1. Identitet i svrha

KN-RG-001 je jedinstveni registar skraćenica i dokumentacionih oznaka modula Konkursi (`KN-*`).

Namijenjen je članovima projektnog tima, analitičarima, testerima i tehničkim saradnicima koji rade na dokumentaciji Konkursa.

Odgovara na pitanja:

* koji je dokumentacioni namespace;
* koji su tipovi i kanonski document ID-evi trenutne faze;
* koje su oznake aktivne, a koje zabranjene;
* kako se nove oznake evidentiraju.

KN-RG-001:

* **nije** poslovni pojmovnik;
* **ne** definiše poslovna ni tehnička pravila;
* **ne** zamjenjuje BM, FS ili TS.

---

# 2. Obuhvat registra

Namespace `KN` obuhvata dokumentaciju svih konkursa u okviru modula Konkursi. Trenutna dokumentaciona faza uspostavlja zajednički konfigurabilni okvir modula Konkursi, usvojeni poslovni profil konkursa za podršku ženskom preduzetništvu (`KN-BM-003`), funkcionalni profil tog tipa konkursa u izradi (`KN-FS-003`), poslovni profil konkursa za podršku preduzetništvu mladih (`KN-BM-002`, status `U IZRADI`) i planirani funkcionalni profil konkursa za podršku preduzetništvu mladih (`KN-FS-002`).

Dokumentacija se dijeli na:

1. zajednički konfigurabilni okvir modula Konkursi;
2. zasebne poslovne i funkcionalne profile svakog tipa konkursa;
3. godišnje instance konkursa kao podatke i konfiguraciju sistema.

Zajednički dokumenti ne smiju pretpostaviti da svi konkursi imaju iste aktere, kategorije, dokumente, obrasce, faze ili pravila.

Registar razdvaja, kada budu uvedene:

* zajedničke oznake modula Konkursi;
* oznake pojedinačnih tipova konkursa;
* interne oznake poslovnih i funkcionalnih pravila;
* dokumentacione oznake od runtime vrijednosti.

Ove kategorije se ne spajaju. Prazne kategorije se ne popunjavaju unaprijed.

Usvojeni dokumentacioni lanac ove faze:

`KN-RG-001 → KN-BM-001 → KN-BM-002 → KN-BM-003 → KN-FS-001 → KN-FS-002 → KN-FS-003 → KN-TS-001`

Razgraničenje sadržaja:

* `KN-BM-001` ne definiše konkretne aktere i dokumente svih konkursa; definiše zajednički model i mehanizam profila;
* `KN-BM-002` definiše poslovne posebnosti konkursa za mlade;
* `KN-BM-003` definiše poslovne posebnosti konkursa za podršku ženskom preduzetništvu;
* `KN-FS-001` definiše zajedničke i konfigurabilne funkcionalnosti;
* `KN-FS-002` definiše funkcionalnu primjenu profila mladih;
* `KN-FS-003` definiše funkcionalnu primjenu profila ženskog preduzetništva;
* `KN-TS-001` definiše zajedničku tehničku realizaciju;
* godišnja instanca konkursa nije novi BM/FS/TS dokument.

Filename **nije** document ID. Document ID živi u zaglavlju dokumenta (`DK-DS-001` §2).

---

# 3. Pravilo namespace-a

Svi kanonski Document ID-evi modula Konkursi koriste namespace prefiks `KN-`. Interne poslovne, funkcionalne i runtime oznake nisu Document ID-evi i njihov format se odobrava zasebno prije prve upotrebe.

Numeracija document ID-eva je lokalna unutar namespace-a `KN` i tipa dokumenta. Rupe u numeraciji su dozvoljene (`DK-DS-001` §1). Dokument se ne kreira samo da bi se popunio broj.

Lista namespace prefiksa platforme **nije** zatvorena. Prefiks `KN` je usvojen. Novi prefiks se ne uvodi ovim dokumentom.

---

# 4. Dokumentacione oznake

## 4.1 Prefiksi dokumenata

Prefiksi pripadaju **namespace-u KN**.

| Oznaka | Puni naziv | Značenje | Gdje se koristi | Status |
|--------|------------|----------|-----------------|--------|
| **KN** | Konkursi | Prefiks dokumentacionog namespace-a cijelog modula Konkursi. | Svi KN dokumenti | aktivna |
| **KN-RG** | Registar skraćenica i oznaka dokumentacije Konkursa | Tip dokumenta: RG. | KN-RG-001 | aktivna |
| **KN-BM** | Poslovni model Konkursa | Tip dokumenta: poslovni model. | KN-BM-001; KN-BM-002; KN-BM-003 | aktivna |
| **KN-FS** | Funkcionalna specifikacija Konkursa | Tip dokumenta: funkcionalna specifikacija. | KN-FS-001; KN-FS-002; KN-FS-003 | aktivna |
| **KN-TS** | Tehnička specifikacija Konkursa | Tip dokumenta: tehnička specifikacija. | KN-TS-001 | aktivna |

## 4.2 Kanonski dokumenti ove faze

| Oznaka | Dokument | Putanja | Obuhvat sadržaja | Status |
|--------|----------|---------|------------------|--------|
| **KN-RG-001** | Registar skraćenica i oznaka dokumentacije Konkursa | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` | namespace KN; granice faze; pravila evidencije oznaka | U IZRADI |
| **KN-BM-001** | Zajednički poslovni model modula Konkursi | `docs/business-model/Business_Model_Konkursi.md` | Zajednički pojmovi, konfigurabilni okvir i pravila razdvajanja tipova konkursa | USVOJEN |
| **KN-BM-002** | Poslovni profil konkursa za podršku preduzetništvu mladih | `docs/business-model/Business_Model_Konkursi_Mladi.md` | Akteri, kategorije, dokumenti, obrasci, faze i poslovna pravila profila mladih koja utiču na platformu | U IZRADI |
| **KN-BM-003** | Poslovni profil: Konkurs za podršku ženskom preduzetništvu | `docs/business-model/Business_Model_Konkursi_Zensko_Preduzetnistvo.md` | Akteri, faze, ocjenjivanje, rangiranje, predlog i konačna Odluka profila ženskog preduzetništva prema Odluci 027/26 | USVOJEN |
| **KN-FS-001** | Zajedničke funkcionalnosti modula Konkursi | `docs/functional-specifications/Functional-Specification_Konkursi.md` | Zajedničke i konfigurabilne funkcionalnosti modula | planiran; fajl nije kreiran |
| **KN-FS-002** | Funkcionalni profil konkursa za podršku preduzetništvu mladih | `docs/functional-specifications/Functional-Specification_Konkursi_Mladi.md` | Funkcionalno ponašanje, forme i validacije profila mladih izvedeni iz `KN-BM-002` | planiran; fajl nije kreiran |
| **KN-FS-003** | Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu | `docs/functional-specifications/Functional-Specification_Konkursi_Zensko_Preduzetnistvo.md` | Funkcionalno ponašanje Platforme za profil ženskog preduzetništva izvedeno iz `KN-BM-003` | U IZRADI |
| **KN-TS-001** | Zajednička tehnička specifikacija modula Konkursi | `docs/technical-specifications/Technical-Specification_Konkursi.md` | Zajednička arhitektura, konfiguracioni mehanizam i tehnički model modula | planiran; fajl nije kreiran |

## 4.3. Otvorena pravna pitanja evidentirana u KN dokumentima

`KN-RG-001` nije izvor poslovnih pravila i ne zatvara pravna pitanja. Nova skraćenica se ne uvodi.

Trenutno nema aktivnih formalnih otvorenih pravnih pitanja evidentiranih iz `KN-BM-003`.

U `KN-BM-003` v1.0.1 / `KN-PATCH-BM-001` zatvorena su:

* preklapanje maksimalne granice 20% sa 10%/5%: primjenjuje se maksimalna granica od 20%; procenti se ne sabiraju; 20% nije automatski dodijeljeni iznos;
* obaveznost drugog Konkursa: Komisija može raspisati drugi Javni konkurs; raspisivanje nije automatsko; ako se raspisuje, najkasnije do isteka trećeg kvartala tekuće godine.

U `KN-BM-003` v1.0.2 / `KN-PATCH-BM-002` usklađeni su: stanja Prijave U pripremi / Podnesena / Povučena; eliminatorni kriterijum 1 sa postupkom čl. 17; nacrt ocjena od prve sjednice; nepromjenjivost tek nakon završavanja individualnog ocjenjivanja.

Normativna praznina o sudbini završenih ocjena pri zamjeni člana Komisije dokumentovana je u `KN-BM-003` Poglavlju 12. Nije blocking OPEN i nije posebna oznaka.

---

# 5. Opcioni tipovi dokumenata

Dodatni tip dokumenta uvodi se prema `DK-DS-001` tek kada postane stvarno potreban i nakon posebnog odobrenja.

Konkretne oznake opcionih tipova se **ne** rezervišu unaprijed. Kada odobrena oznaka bude prvi put upotrijebljena, evidentira se u ovom registru u istom dokumentacionom koraku.

---

# 6. PATCH model

Naknadne kontrolisane izmjene dokumentacije modula Konkursi označavaju se prema obrascu `KN-PATCH-{TIP}-{NNN}`. Konkretna PATCH oznaka evidentira se u `KN-RG-001` tek kada se prvi put stvarno upotrijebi. Dokumenti sa statusom `U IZRADI` mogu se korigovati bez PATCH oznake dok nijesu formalno usvojeni.

| Oznaka | Dokument | Verzija dokumenta | Datum | Sadržaj |
|--------|----------|-------------------|--------|---------|
| **KN-PATCH-BM-001** | KN-BM-003 | 1.0.1 | 2026-08-26 | Zatvorena OPEN LEGAL ISSUE #1 i OPEN LEGAL ISSUE #2. (1) Pri preklapanju 20% sa 10% ili 5% primjenjuje se maksimalna granica od 20%; procenti se ne sabiraju; 20% nije automatski dodijeljeni iznos. (2) Komisija može raspisati drugi Javni konkurs; raspisivanje nije automatsko; ako se raspisuje, najkasnije do isteka trećeg kvartala tekuće godine. |
| **KN-PATCH-BM-002** | KN-BM-003 | 1.0.2 | 2026-08-26 | Usklađenje sa Odlukom 027/26: stanja Prijave; eliminatorni kriterijum 1 kroz čl. 17; nacrt ocjena od prve sjednice; nepromjenjivost tek nakon završavanja individualnog ocjenjivanja. |

---

# 7. Runtime vrijednosti

Dokumentacione oznake modula Konkursi nisu isto što i runtime vrijednosti aplikacije. Vrijednosti koje postoje u kodu ili bazi ne postaju automatski usvojene oznake. Runtime oznake definišu se i odobravaju u `KN-TS-001`, a u `KN-RG-001` evidentiraju se samo ako se koriste za sljedivost između dokumentacije i implementacije.

Trenutno nema evidentiranih runtime oznaka.

---

# 8. Interne oznake

Interne oznake poslovnih i funkcionalnih pravila ne uvode se unaprijed. Njihova struktura odobrava se prije prve upotrebe u odgovarajućem dokumentu. Oznake moraju razlikovati zajednička pravila modula, pravila tipa konkursa i, samo ako bude potrebno, pravila godišnje instance. Svaka odobrena oznaka evidentira se u `KN-RG-001` u istom koraku u kojem se prvi put koristi.

| Oznaka | Značenje | Namjena | Dokument | Tip konkursa / sloj | Status |
|--------|----------|---------|----------|---------------------|--------|
| **BM-KN-NNN** | Zajedničko poslovno pravilo modula Konkursi | Označavanje pravila zajedničkog poslovnog okvira | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna struktura |
| **BM-KN-001** | Profil određuje posebnosti tipa konkursa | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-002** | Instanca ne mijenja poslovni profil | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-003** | Istorijska primjena verzije profila | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-004** | Zajednička sposobnost nije obavezna za svaki profil | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-005** | Profil određuje obaveznost i kombinaciju sposobnosti | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-006** | Konfiguracija ne smije mijenjati poslovno značenje | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-007** | Promjena zajedničkog modela samo zbog zajedničke potrebe | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-008** | Poslovni profil mora biti samodovoljan za tumačenje pravila tipa konkursa | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-009** | Nejasnoća se ne rješava pretpostavkom | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-010** | Poslovni izvor mora biti sljediv | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-011** | Poslovni profil i godišnja instanca moraju ostati istorijski razumljivi | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-012** | Poslovni profil ne smije zavisiti od tehničke realizacije | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-013** | Tip konkursa ima stabilan poslovni identitet | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-014** | Administrator konkursa ne može biti član Komisije | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-KN-015** | Jedna aktivno podnijeta Prijava po Podnosiocu i Pozivu | Zajedničko poslovno pravilo | `KN-BM-001` | Zajednički sloj modula Konkursi | aktivna |
| **BM-ML-NNN** | Poslovno pravilo profila konkursa za podršku preduzetništvu mladih | Označavanje konkretnih poslovnih pravila u `KN-BM-002` | `KN-BM-002` | Profil konkursa za podršku preduzetništvu mladih | aktivna struktura |

* `BM-KN-NNN` nije Document ID.
* Konkretni brojevi se ne rezervišu unaprijed.
* Evidentirane konkretne oznake: `BM-KN-001`–`BM-KN-015`. Naredni brojevi nisu rezervisani.
* `BM-ML-NNN` nije Document ID.
* Konkretni brojevi nijesu rezervisani.
* `BM-ML-001` još nije uveden.
* Konkretna oznaka evidentira se u istom koraku prve odobrene upotrebe.

---

# 9. Drugi moduli

`KN-RG-001` vodi oznake modula Konkursi. Oznake drugih modula i zajedničkih platformskih funkcionalnosti vode se u njihovim matičnim registrima. Kada modul Konkursi koristi zajedničku funkcionalnost drugog modula, veza se evidentira u odgovarajućem BM, FS ili TS dokumentu, bez preuzimanja tuđe oznake u registar Konkursa.

---

# 10. Zabranjene oznake

| Oznaka | Razlog |
|--------|--------|
| **OM-*** | Poseban namespace ne postoji (`DK-DS-001` §1). |

Trenutno nema oznaka sa statusom **ZASTARJELO**.

---

# 11. Pravila održavanja

* KN-RG-001 se ažurira u istom dokumentacionom koraku u kojem nastane nova stvarno korišćena skraćenica, novi tip dokumenta, kada se promijeni zvanični naziv oznake, ili kada oznaka postane zastarjela.
* Uz svaku novu oznaku navode se značenje, namjena, dokument u kojem se koristi, tip konkursa kojem pripada i status.
* Zastarjela oznaka se ne briše bez traga — označava se kao **ZASTARJELO** i upućuje na važeći naziv.
* U KN-RG-001 se **ne** unose: poslovni pojmovnik; opšte tehničke skraćenice; poslovna pravila; tuđe oznake drugih modula; unaprijed rezervisane oznake bez stvarne upotrebe.
* Dodavanje novog tipa konkursa zahtijeva poseban odobreni dokumentacioni korak. Zajednički BM, FS i TS dokumenti mijenjaju se samo ako novi tip zahtijeva proširenje zajedničkog konfigurabilnog okvira. Svaki novi tip konkursa dobija zaseban poslovni profil i, kada je potreban, zaseban funkcionalni profil sa sopstvenim akterima, kategorijama, dokumentima, obrascima, fazama i pravilima. Poseban tehnički dokument kreira se samo kada tip zahtijeva tehničku realizaciju koja nije obuhvaćena zajedničkim `KN-TS-001`. Svaki novi dokument mora biti posebno odobren i evidentiran u `KN-RG-001` prije prve upotrebe.

---

**Kraj dokumenta KN-RG-001 v0.1.10**
