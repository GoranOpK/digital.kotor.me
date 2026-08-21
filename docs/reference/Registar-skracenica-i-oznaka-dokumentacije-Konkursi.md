# Digital Kotor
# Registar skraćenica i oznaka dokumentacije Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-RG-001
**Naziv:** Registar skraćenica i oznaka dokumentacije Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** U IZRADI
**Verzija:** 0.1.1
**Datum:** 2026-08-19

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Uspostavljen KN-RG-001 kao jedinstveni registar oznaka modula Konkursi. Razdvojeni namespace cijelog modula i obuhvat trenutne dokumentacione faze. Razdvojeni zajednički konfigurabilni okvir od zasebnih poslovnih i funkcionalnih profila tipa konkursa. Registrovan kanonski lanac KN-RG-001 → KN-BM-001 → KN-BM-002 → KN-FS-001 → KN-FS-002 → KN-TS-001. Uvedeno pravilo da se oznake evidentiraju u istom koraku u kojem se prvi put koriste. Opcioni tipovi dokumenata, PATCH instance i interne oznake nijesu rezervisani unaprijed. |
| 0.1.1 | 2026-08-19 | Evidentirana struktura interne oznake `BM-KN-NNN` i ažuriran status `KN-BM-001` na `U IZRADI`. |

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

Namespace `KN` obuhvata dokumentaciju svih konkursa u okviru modula Konkursi. Trenutna dokumentaciona faza uspostavlja zajednički konfigurabilni okvir modula Konkursi i zasebni poslovni i funkcionalni profil konkursa za podršku preduzetništvu mladih.

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

`KN-RG-001 → KN-BM-001 → KN-BM-002 → KN-FS-001 → KN-FS-002 → KN-TS-001`

Razgraničenje sadržaja:

* `KN-BM-001` ne definiše konkretne aktere i dokumente svih konkursa; definiše zajednički model i mehanizam profila;
* `KN-BM-002` definiše poslovne posebnosti konkursa za mlade;
* `KN-FS-001` definiše zajedničke i konfigurabilne funkcionalnosti;
* `KN-FS-002` definiše funkcionalnu primjenu profila mladih;
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
| **KN-BM** | Poslovni model Konkursa | Tip dokumenta: poslovni model. | KN-BM-001; KN-BM-002 | aktivna |
| **KN-FS** | Funkcionalna specifikacija Konkursa | Tip dokumenta: funkcionalna specifikacija. | KN-FS-001; KN-FS-002 | aktivna |
| **KN-TS** | Tehnička specifikacija Konkursa | Tip dokumenta: tehnička specifikacija. | KN-TS-001 | aktivna |

## 4.2 Kanonski dokumenti ove faze

| Oznaka | Dokument | Putanja | Obuhvat sadržaja | Status |
|--------|----------|---------|------------------|--------|
| **KN-RG-001** | Registar skraćenica i oznaka dokumentacije Konkursa | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` | namespace KN; granice faze; pravila evidencije oznaka | U IZRADI |
| **KN-BM-001** | Zajednički poslovni model modula Konkursi | `docs/business-model/Business_Model_Konkursi.md` | Zajednički pojmovi, konfigurabilni okvir i pravila razdvajanja tipova konkursa | U IZRADI |
| **KN-BM-002** | Poslovni profil konkursa za podršku preduzetništvu mladih | `docs/business-model/Business_Model_Konkursi_Mladi.md` | Akteri, kategorije, dokumenti, obrasci, faze i poslovna pravila profila mladih koja utiču na platformu | planiran; fajl nije kreiran |
| **KN-FS-001** | Zajedničke funkcionalnosti modula Konkursi | `docs/functional-specifications/Functional-Specification_Konkursi.md` | Zajedničke i konfigurabilne funkcionalnosti modula | planiran; fajl nije kreiran |
| **KN-FS-002** | Funkcionalni profil konkursa za podršku preduzetništvu mladih | `docs/functional-specifications/Functional-Specification_Konkursi_Mladi.md` | Funkcionalno ponašanje, forme i validacije profila mladih izvedeni iz `KN-BM-002` | planiran; fajl nije kreiran |
| **KN-TS-001** | Zajednička tehnička specifikacija modula Konkursi | `docs/technical-specifications/Technical-Specification_Konkursi.md` | Zajednička arhitektura, konfiguracioni mehanizam i tehnički model modula | planiran; fajl nije kreiran |

---

# 5. Opcioni tipovi dokumenata

Dodatni tip dokumenta uvodi se prema `DK-DS-001` tek kada postane stvarno potreban i nakon posebnog odobrenja.

Konkretne oznake opcionih tipova se **ne** rezervišu unaprijed. Kada odobrena oznaka bude prvi put upotrijebljena, evidentira se u ovom registru u istom dokumentacionom koraku.

---

# 6. PATCH model

Naknadne kontrolisane izmjene dokumentacije modula Konkursi označavaju se prema obrascu `KN-PATCH-{TIP}-{NNN}`. Konkretna PATCH oznaka evidentira se u `KN-RG-001` tek kada se prvi put stvarno upotrijebi. Dokumenti sa statusom `U IZRADI` mogu se korigovati bez PATCH oznake dok nijesu formalno usvojeni.

Nijedna `KN-PATCH-*` oznaka trenutno nije izdata.

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

* `BM-KN-NNN` nije Document ID.
* Konkretni brojevi se ne rezervišu unaprijed.
* Prva konkretna oznaka nastaje tek kada se napiše i odobri prvo zajedničko poslovno pravilo.

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

**Kraj dokumenta KN-RG-001 v0.1.1**
