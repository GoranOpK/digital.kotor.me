# Digital Kotor
# Zajednički poslovni model modula Konkursi
## Modul: Konkursi

**Oznaka dokumenta:** KN-BM-001
**Naziv:** Zajednički poslovni model modula Konkursi
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** USVOJEN
**Verzija:** 1.0.0
**Datum:** 2026-08-25

Povezani dokumenti:

* Registar oznaka: **KN-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md`
* Poslovni profil mladih: **KN-BM-002** — `docs/business-model/Business_Model_Konkursi_Mladi.md` (planiran; fajl nije kreiran)
* Zajedničke funkcionalnosti modula Konkursi: **KN-FS-001** — `docs/functional-specifications/Functional-Specification_Konkursi.md` (planiran; fajl nije kreiran)
* Funkcionalni profil konkursa za podršku preduzetništvu mladih: **KN-FS-002** — `docs/functional-specifications/Functional-Specification_Konkursi_Mladi.md` (planiran; fajl nije kreiran)
* Zajednička tehnička specifikacija modula Konkursi: **KN-TS-001** — `docs/technical-specifications/Technical-Specification_Konkursi.md` (planiran; fajl nije kreiran)

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-19 | Uspostavljena početna struktura KN-BM-001 kao zajedničkog poslovnog modela modula Konkursi. Poslovni sadržaj poglavlja nije unesen. |
| 0.1.1 | 2026-08-21 | Dopunjeno poglavlje 1 — Uvod. |
| 0.1.2 | 2026-08-21 | Dopunjeno poglavlje 2 — Svrha i granice zajedničkog modela. |
| 0.1.3 | 2026-08-21 | Dopunjeno poglavlje 3 — Opseg. |
| 0.1.4 | 2026-08-24 | Dopunjeno poglavlje 4 — Granice prema BM profilima, FS i TS. |
| 0.1.5 | 2026-08-24 | Dopunjeno Poglavlje 5 — Poslovni principi; evidentirana zajednička poslovna pravila `BM-KN-001`–`BM-KN-013`. |
| 0.1.6 | 2026-08-24 | Dopunjeno Poglavlje 6 — Tip konkursa. |
| 0.1.7 | 2026-08-24 | Dopunjeno Poglavlje 7 — Poslovni profil tipa konkursa. |
| 0.1.8 | 2026-08-24 | Dopunjeno Poglavlje 8 — Verzionisanje profila i istorijska primjena. |
| 0.1.9 | 2026-08-24 | Dopunjeno Poglavlje 9 — Godišnja instanca konkursa. |
| 0.1.10 | 2026-08-24 | Dopunjeno Poglavlje 10 — Poziv i ponovljeni poziv. |
| 0.1.11 | 2026-08-24 | Dopunjeno Poglavlje 11 — Apstraktni akteri i uloge; evidentirano zajedničko poslovno pravilo `BM-KN-014`. |
| 0.1.12 | 2026-08-24 | Dopunjeno Poglavlje 12 — Apstraktne kategorije. |
| 0.1.13 | 2026-08-24 | Dopunjeno Poglavlje 13 — Konfigurabilne faze i statusi. |
| 0.1.14 | 2026-08-24 | Dopunjeno Poglavlje 14 — Obrasci i dokumenti kao pojmovi. |
| 0.1.15 | 2026-08-24 | Dopunjeno Poglavlje 15 — Opcione sposobnosti: ocjenjivanje i rangiranje. |
| 0.1.16 | 2026-08-24 | Dopunjeno Poglavlje 16 — Opciona sposobnost: prijava; evidentirano zajedničko poslovno pravilo `BM-KN-015`. |
| 0.1.17 | 2026-08-25 | Dopunjeno Poglavlje 17 — Opcioni pojmovi: odluka, ugovor, realizacija i izvještavanje. |
| 0.1.18 | 2026-08-25 | Dopunjeno Poglavlje 18 — Opciona sposobnost: arhiviranje. |
| 0.1.19 | 2026-08-25 | Uklonjena planirana Poglavlja 19–21; Poglavlje 18 utvrđeno kao završno sadržajno poglavlje KN-BM-001. |
| 0.1.20 | 2026-08-25 | Završne redakcijske korekcije: usklađeni nazivi Poglavlja 7 i 9 i kanonski lanac istorijske sljedivosti u §18.2. |
| 1.0.0 | 2026-08-25 | Formalno usvojen zajednički poslovni model modula Konkursi; završni audit i redakcijske korekcije zatvoreni. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Dokument ima status `USVOJEN`. Dok dokument ima status `U IZRADI`, redakcijske korekcije koje ne mijenjaju značenje mogu se unositi u okviru iste radne verzije. Kada se odobrenim dokumentacionim korakom doda ili promijeni sadržaj, obuhvat ili usvojeno pravilo dokumenta, povećava se radna verzija i dodaje novi red u istoriju verzija. Više povezanih izmjena jednog odobrenog dokumentacionog koraka mogu se evidentirati kao jedna radna verzija. Postojeći redovi istorije verzija ne mijenjaju se. PATCH oznaka se ne izdaje dok dokument nije formalno usvojen.

Nakon formalnog usvajanja, kontrolisane izmjene označavaju se prema `KN-PATCH-BM-{NNN}` i evidentiraju se u `KN-RG-001` tek pri prvoj stvarnoj upotrebi.

---

## Svrha dokumenta

Dokument je zajednički poslovni model modula Konkursi. Definiše apstraktne pojmove, odnose i mehanizam profila. Nije poslovni profil nijednog tipa konkursa, nije Functional Specification i nije Technical Specification.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | USVOJENO |
| 2. Svrha i granice zajedničkog modela | USVOJENO |
| 3. Opseg | USVOJENO |
| 4. Granice prema BM profilima, FS i TS | USVOJENO |
| 5. Poslovni principi | USVOJENO |
| 6. Tip konkursa | USVOJENO |
| 7. Poslovni profil tipa konkursa | USVOJENO |
| 8. Verzionisanje profila i istorijska primjena | USVOJENO |
| 9. Godišnja instanca konkursa | USVOJENO |
| 10. Poziv i ponovljeni poziv | USVOJENO |
| 11. Apstraktni akteri i uloge | USVOJENO |
| 12. Apstraktne kategorije | USVOJENO |
| 13. Konfigurabilne faze i statusi | USVOJENO |
| 14. Obrasci i dokumenti kao pojmovi | USVOJENO |
| 15. Opcione sposobnosti: ocjenjivanje i rangiranje | USVOJENO |
| 16. Opciona sposobnost: prijava | USVOJENO |
| 17. Opcioni pojmovi: odluka, ugovor, realizacija i izvještavanje | USVOJENO |
| 18. Opciona sposobnost: arhiviranje | USVOJENO |

---

# Pravila upravljanja dokumentom

1. KN-BM-001 je izvor istine samo za zajednički apstraktni model modula Konkursi.
2. Ne definiše konkretna pravila nijednog tipa konkursa. Ta pravila pripadaju odgovarajućem poslovnom profilu.
3. Ne pretpostavlja da svi konkursi imaju iste aktere, kategorije, potrebne dokumente, obrasce, faze, statuse, rokove ili kriterijume.
4. Interna oznaka `BM-KN-NNN` nije Document ID. Ne rezerviše se unaprijed. Evidentira se u `KN-RG-001` tek u istom koraku prve stvarne upotrebe konkretnog broja.
5. Dokumenti sa statusom `U IZRADI` mogu se korigovati bez PATCH oznake dok nijesu formalno usvojeni.
6. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno uvoditi poslovna pravila niti konkretan sadržaj profila.
7. Ako se implementacija razlikuje od usvojenog Business Modela, usklađuje se implementacija, osim ako se odlukom ne izmijeni sam Business Model.

---

# Upravljanje promjenama

Svaka izmjena usvojenog sadržaja mora biti rezultat odobrene poslovne ili projektne odluke. Dok dokument ima status `U IZRADI`, radne verzije vode se prema pravilu navedenom uz istoriju verzija. PATCH oznaka se ne izdaje dok dokument nije formalno usvojen.

---

## Sadržaj

1. Uvod
2. Svrha i granice zajedničkog modela
3. Opseg
4. Granice prema BM profilima, FS i TS
5. Poslovni principi
6. Tip konkursa
7. Poslovni profil tipa konkursa
8. Verzionisanje profila i istorijska primjena
9. Godišnja instanca konkursa
10. Poziv i ponovljeni poziv
11. Apstraktni akteri i uloge
12. Apstraktne kategorije
13. Konfigurabilne faze i statusi
14. Obrasci i dokumenti kao pojmovi
15. Opcione sposobnosti: ocjenjivanje i rangiranje
16. Opciona sposobnost: prijava
17. Opcioni pojmovi: odluka, ugovor, realizacija i izvještavanje
18. Opciona sposobnost: arhiviranje

---

# 1. Uvod

Status poglavlja: USVOJENO

`KN-BM-001` je zajednički poslovni model modula Konkursi na platformi Digital Kotor. Dokument uspostavlja zajednički okvir za dokumentovanje različitih tipova konkursa, uz obavezno razdvajanje zajedničkog modela od poslovnih profila pojedinačnih tipova konkursa.

Dokument se primjenjuje na sve postojeće i buduće tipove konkursa koji se vode kroz modul Konkursi. Ne pretpostavlja da različiti tipovi konkursa imaju iste aktere, kategorije, potrebne dokumente, obrasce, faze, statuse, rokove ili kriterijume.

`KN-BM-001` nalazi se poslije registra `KN-RG-001` u dokumentacionom lancu i predstavlja osnovu za izradu zasebnih poslovnih profila, funkcionalnih specifikacija i zajedničke tehničke specifikacije modula.

Konkretna pravila konkursa za podršku preduzetništvu mladih ne pripadaju ovom dokumentu. Ona će biti dokumentovana u poslovnom profilu `KN-BM-002`.

---

# 2. Svrha i granice zajedničkog modela

Status poglavlja: USVOJENO

Svrha dokumenta `KN-BM-001` je da uspostavi zajednički poslovni okvir na kojem se zasnivaju različiti tipovi konkursa u modulu Konkursi.

Zajednički poslovni model definiše:

* zajedničke poslovne pojmove i njihove međusobne odnose;
* razdvajanje tipa konkursa, poslovnog profila, godišnje instance i pojedinačnog poziva;
* način na koji poslovni profil određuje posebnosti jednog tipa konkursa;
* zajedničke poslovne principe koji važe za upravljanje različitim profilima;
* poslovne sposobnosti koje profil može uključiti kada su potrebne za konkretan tip konkursa.

`KN-BM-001` ne definiše:

* konkretne aktere, kategorije, potrebne dokumente, obrasce, faze, statuse, rokove, kriterijume, bodove ili eliminacione uslove pojedinačnog tipa konkursa;
* godišnje datume, iznose, raspoloživa sredstva, sastav odgovornih tijela ili druge podatke pojedinačne godišnje instance i poziva;
* ekrane, korisnička polja, funkcionalne validacije i detaljno ponašanje sistema, jer pripadaju odgovarajućoj funkcionalnoj specifikaciji;
* arhitekturu, bazu podataka, API-je, runtime vrijednosti i način tehničke realizacije, jer pripadaju tehničkoj specifikaciji;
* cjelovit prepis ili opštu pravnu analizu odluka i drugih promjenljivih pravnih akata.

Pravila konkretnog tipa konkursa dokumentuju se u njegovom poslovnom profilu. Za konkurs za podršku preduzetništvu mladih taj dokument je `KN-BM-002`. Odluka i njeni prilozi koriste se u tom profilu kao izvor za pravila koja utiču na aktere, podatke, dokumente, obrasce, uslove, validacije, faze i ponašanje platforme.

Ako izvor ne daje dovoljno jasno pravilo za poslovni model ili platformu, nejasnoća se evidentira kao otvoreno pitanje i ne rješava se pretpostavkom.

---

# 3. Opseg

Status poglavlja: USVOJENO

Dokumentacioni opseg `KN-BM-001` obuhvata zajednički poslovni sloj modula Konkursi i njegovu primjenu kroz zasebne poslovne profile različitih tipova konkursa.

U opseg dokumenta ulaze:

* zajednički pojmovi potrebni za opisivanje različitih tipova konkursa;
* odnosi između tipa konkursa, poslovnog profila, verzije profila, godišnje instance i poziva;
* apstraktni model aktera, uloga, kategorija, faza, statusa, obrazaca i potrebnih dokumenata;
* način razdvajanja zajedničkog okvira od posebnosti pojedinačnog tipa konkursa;
* opcione poslovne sposobnosti koje profil može uključiti u skladu sa pravilima konkretnog tipa konkursa;
* granice prema poslovnim profilima, funkcionalnim specifikacijama i tehničkoj specifikaciji;
* zajednički rječnik i otvorena pitanja koja se odnose na zajednički model.

Opseg dokumenta ne zavisi od broja tipova konkursa, godišnjih instanci ili pojedinačnih poziva koji će se voditi kroz platformu.

Konkretne vrijednosti i pravila unose se u odgovarajući poslovni profil, godišnju instancu ili poziv, u skladu sa njihovom prirodom i izvorom. Njihovo navođenje u zajedničkom modelu dozvoljeno je samo kao neutralan primjer koji ne uspostavlja pravilo za druge tipove konkursa.

---

# 4. Granice prema BM profilima, FS i TS

Status poglavlja: USVOJENO

Dokumentacioni slojevi modula Konkursi razdvajaju se na sljedeći način.

## KN-BM-001 — zajednički poslovni model

`KN-BM-001` definiše zajednički apstraktni poslovni model modula Konkursi.

Odgovara na pitanje:

**Koje poslovne pojmove, odnose i sposobnosti modul Konkursi mora moći da podrži za različite tipove konkursa?**

Ne definiše konkretna poslovna pravila pojedinačnog tipa konkursa.

Postojanje određenog pravila, forme, kriterijuma, statusa ili ponašanja u trenutnoj implementaciji ne čini ga automatski zajedničkim poslovnim pravilom.

## KN-BM-00x — poslovni profil konkretnog tipa konkursa

Zaseban poslovni profil definiše poslovne posebnosti konkretnog tipa konkursa.

Tu pripadaju, kada su relevantni za taj tip:

* konkretni akteri i njihove poslovne uloge;
* kategorije podnosilaca;
* uslovi podobnosti;
* potrebni dokumenti;
* poslovni obrasci;
* faze i statusi specifični za profil;
* rokovi definisani pravilima tipa konkursa;
* kriterijumi;
* bodovanje;
* eliminacioni uslovi;
* druga poslovna pravila konkretnog tipa konkursa.

Za konkurs za podršku preduzetništvu mladih planirani poslovni profil je `KN-BM-002`.

Godišnje promjenljive vrijednosti ne treba unositi u poslovni profil ako predstavljaju podatke konkretne godišnje instance ili poziva.

## KN-FS-001 — zajednička funkcionalna specifikacija

`KN-FS-001` definiše funkcionalno ponašanje zajedničkih i konfigurabilnih sposobnosti modula Konkursi.

Tu pripadaju funkcionalni tokovi, ponašanje sistema, korisničke interakcije, zajedničke validacije i način funkcionalnog korišćenja zajedničkih sposobnosti.

`KN-FS-001` ne smije pretvarati pravilo jednog poslovnog profila u univerzalnu funkcionalnost svih konkursa.

## KN-FS-00x — funkcionalni profil konkretnog tipa konkursa

Funkcionalni profil prevodi poslovna pravila odgovarajućeg `KN-BM-00x` profila u konkretno funkcionalno ponašanje platforme.

Tu mogu pripadati:

* konkretna polja;
* konkretne forme;
* funkcionalne validacije;
* uslovna polja;
* tokovi karakteristični za taj tip konkursa;
* funkcionalna primjena dokumentacionih zahtjeva;
* funkcionalna primjena kriterijuma, bodovanja i drugih pravila profila.

Za konkurs za podršku preduzetništvu mladih planirani funkcionalni profil je `KN-FS-002`.

## KN-TS-001 — zajednička tehnička specifikacija

`KN-TS-001` definiše tehničku realizaciju modula Konkursi.

Tu pripadaju, između ostalog:

* arhitektura;
* modeli i relacije;
* tabele i kolone;
* runtime vrijednosti;
* rute;
* kontroleri i servisi;
* autorizacioni mehanizmi;
* storage;
* tehnički konfiguracioni mehanizam;
* integracije;
* tehničko mapiranje poslovnih i funkcionalnih pravila na implementaciju.

Poseban tehnički dokument za pojedinačni tip konkursa kreira se samo ako taj tip zahtijeva tehničku realizaciju koja nije obuhvaćena zajedničkim `KN-TS-001`, u skladu sa `KN-RG-001`.

## Odnos dokumentacije i implementacije

**Postojeća implementacija nije izvor poslovnog pravila.**

Poslovna pravila usvajaju se u odgovarajućem Business Model dokumentu.

Postojeći kod može:

* dokazati trenutno implementirano ponašanje;
* pomoći u identifikovanju postojećeg tehničkog stanja;
* pokazati odstupanje između implementacije i usvojenog modela;

ali se pravilo ne smije proglasiti zajedničkim poslovnim pravilom samo zato što trenutno postoji u zajedničkom modelu, kontroleru, tabeli, formi ili drugom dijelu implementacije.

Ako implementacija odstupa od usvojenog Business Modela, primjenjuje se postojeće pravilo upravljanja `KN-BM-001`: usklađuje se implementacija, osim ako se posebnom poslovnom odlukom ne izmijeni Business Model.

Ovo poglavlje ne nalaže izmjenu aplikacionog koda.

---

# 5. Poslovni principi

Status poglavlja: USVOJENO

Ovo poglavlje definiše zajedničke poslovne principe modula Konkursi.

## BM-KN-001 — Profil određuje posebnosti tipa konkursa

Svaki tip konkursa koji se vodi kroz modul Konkursi ima odgovarajući poslovni profil koji definiše njegove poslovne posebnosti.

Zajednički model ne smije pretpostaviti da pravilo jednog poslovnog profila važi za druge tipove konkursa.

## BM-KN-002 — Instanca ne mijenja poslovni profil

Godišnja instanca i pojedinačni poziv primjenjuju odgovarajuću verziju poslovnog profila i ne mijenjaju njegova poslovna pravila.

Vrijednosti koje se mijenjaju od instance do instance ili od poziva do poziva evidentiraju se kao podaci i konfiguracija instance odnosno poziva kada ih profil dozvoljava.

Ako je potrebno promijeniti poslovno pravilo tipa konkursa, mijenja se odnosno verzioniše poslovni profil. Poslovno pravilo ne smije se prikriti kao konfiguracija godišnje instance ili poziva.

## BM-KN-003 — Istorijska primjena verzije profila

Svaka godišnja instanca konkursa primjenjuje određenu verziju poslovnog profila.

Naknadna izmjena ili nova verzija poslovnog profila ne mijenja poslovna pravila već postojećih instanci koje su nastale prema ranijoj verziji profila.

Mora biti moguće utvrditi koja verzija poslovnog profila je važila za konkretnu godišnju instancu.

Ovo pravilo ne određuje način tehničkog čuvanja te veze.

## BM-KN-004 — Zajednička sposobnost nije obavezna za svaki profil

Zajednički model modula Konkursi definiše poslovne sposobnosti koje različiti poslovni profili mogu koristiti.

Postojanje sposobnosti u zajedničkom modelu ne znači da je ona obavezna za svaki tip konkursa.

Poslovni profil određuje koje zajedničke sposobnosti koristi i pod kojim poslovnim pravilima.

## BM-KN-005 — Profil određuje obaveznost i kombinaciju sposobnosti

Poslovni profil određuje:

* koje zajedničke poslovne sposobnosti konkretni tip konkursa koristi;
* koje su za taj tip obavezne;
* koje se ne koriste;
* i, gdje je poslovno relevantno, njihov međusobni redosljed i zavisnosti.

Ovo pravilo ne definiše konkretne faze niti kombinaciju sposobnosti nijednog pojedinačnog tipa konkursa.

## BM-KN-006 — Konfiguracija ne smije mijenjati poslovno značenje

Konfiguracija godišnje instance ili poziva može određivati samo vrijednosti i izbore koje odgovarajući poslovni profil dozvoljava kao promjenljive.

Konfiguracijom se ne smije zaobići, izmijeniti niti proširiti poslovno pravilo profila.

Promjena vrijednosti koju profil definiše kao promjenljivu predstavlja konfiguraciju instance ili poziva.

Promjena poslovnog pravila zahtijeva odgovarajuću promjenu odnosno novu verziju poslovnog profila.

## BM-KN-007 — Promjena zajedničkog modela samo zbog zajedničke potrebe

Uvođenje novog tipa konkursa samo po sebi ne mijenja zajednički poslovni model.

`KN-BM-001` proširuje se samo kada novi ili postojeći poslovni profil otkrije poslovni pojam, odnos ili sposobnost koja pripada zajedničkom okviru, a koju postojeći zajednički model još ne podržava.

Pravilo koje je potrebno samo jednom tipu konkursa ostaje u njegovom poslovnom profilu.

## BM-KN-008 — Poslovni profil mora biti samodovoljan za tumačenje pravila tipa konkursa

Poslovni profil mora sadržati poslovna pravila konkretnog tipa konkursa koja su potrebna za jednoznačno definisanje njegovog ponašanja na platformi.

Profil može upućivati na odluke, pravilnike, javne pozive i druge poslovne ili pravne izvore.

Poslovno pravilo koje utiče na ponašanje platforme ne smije ostati definisano isključivo spoljnim dokumentom.

Ovo pravilo ne zahtijeva prepisivanje cjelokupnog izvornog pravnog ili poslovnog dokumenta u poslovni profil.

## BM-KN-009 — Nejasnoća se ne rješava pretpostavkom

Ako poslovno pravilo nije moguće jednoznačno utvrditi iz odobrenog poslovnog izvora, nejasnoća se evidentira kao otvoreno pitanje.

Nejasno ili nedostajuće pravilo ne smije se dopuniti pretpostavkom izvedenom iz:

* postojeće implementacije;
* ranije godišnje instance;
* drugog tipa konkursa;
* tehničke pogodnosti.

Pravilo se dokumentuje tek nakon odgovarajućeg poslovnog ili pravnog razjašnjenja i odobrenja.

## BM-KN-010 — Poslovni izvor mora biti sljediv

Poslovno pravilo konkretnog profila koje je izvedeno iz odluke, pravilnika, javnog poziva ili drugog poslovnog odnosno pravnog izvora mora biti sljedivo do izvora iz kojeg je izvedeno.

Sljedivost ne zahtijeva prepisivanje izvornog dokumenta, već mora omogućiti da se utvrdi na osnovu čega je poslovno pravilo definisano.

Ako pravilo nije izvedeno iz spoljnog izvora već predstavlja odobrenu poslovnu odluku platforme ili Product Ownera, njegovo porijeklo ne smije se pogrešno pripisivati pravnom ili drugom spoljnom izvoru.

Ovim pravilom se ne određuje tehnički način realizacije sljedivosti.

## BM-KN-011 — Poslovni profil i godišnja instanca moraju ostati istorijski razumljivi

Završena ili istorijska godišnja instanca konkursa mora ostati razumljiva prema:

* poslovnom profilu;
* verziji poslovnog profila;
* vrijednostima koje su važile za tu instancu i njene pozive.

Naknadne izmjene poslovnog profila, konfiguracije budućih instanci ili zajedničkog modela ne smiju promijeniti poslovno značenje istorijskih prijava, odluka, rezultata i drugih zapisa nastalih u okviru te instance.

Ovim pravilom se ne određuje tehnički mehanizam očuvanja istorijskog stanja.

## BM-KN-012 — Poslovni profil ne smije zavisiti od tehničke realizacije

Poslovni profil definiše poslovno značenje i pravila konkretnog tipa konkursa nezavisno od načina njihove tehničke realizacije.

Modeli, tabele, kolone, rute, kontroleri, forme i druge tehničke strukture ne određuju sadržaj poslovnog profila.

Tehnička realizacija može se mijenjati bez promjene poslovnog profila kada poslovno značenje i poslovna pravila ostaju nepromijenjeni.

Promjena tehničke realizacije ne smije prikriti stvarnu promjenu poslovnog pravila.

## BM-KN-013 — Tip konkursa ima stabilan poslovni identitet

Tip konkursa predstavlja trajno prepoznatljivu poslovnu cjelinu koja može imati:

* više verzija poslovnog profila;
* više godišnjih instanci;
* više poziva.

Promjena godine, konkretnih vrijednosti instance, poziva ili verzije poslovnog profila sama po sebi ne stvara novi tip konkursa.

Novi tip konkursa postoji kada se uspostavlja zasebna poslovna cjelina koja zahtijeva sopstveni poslovni profil.

Ovim pravilom se ne utvrđuje lista konkretnih tipova konkursa.

## BM-KN-014 — Administrator konkursa ne može biti član Komisije

Administrator konkursa ne može biti član Komisije.

Isto lice ne može istovremeno, u okviru istog konkursa odnosno njegovog sprovođenja, obavljati poslovnu ulogu Administratora konkursa i člana Komisije.

Ovo je zajednička poslovna granica modula. Konkretni poslovni profil može dodatno razraditi sastav Komisije i uslove članstva, ali ne može ukinuti ovu nespojivost.

Ovim pravilom se ne određuje tehnički način sprovođenja zabrane.

## BM-KN-015 — Jedna aktivno podnijeta Prijava po Podnosiocu i Pozivu

Podnosilac može imati najviše jednu aktivno podnijetu Prijavu na istom Pozivu.

Povlačenje Prijave ne briše njen istorijski zapis.

Nakon povlačenja Podnosilac može podnijeti novu Prijavu na isti Poziv samo dok traje rok za podnošenje.

Nova Prijava je novi poslovni zapis.

Istekom roka za podnošenje nova Prijava na taj Poziv više nije dozvoljena.

Ovo je zajednička poslovna granica modula. Konkretni poslovni profil ne može ukinuti ovu granicu.

Ovim pravilom se ne određuje tehnički način sprovođenja niti do kojeg trenutka je povlačenje dozvoljeno.

---

# 6. Tip konkursa

Status poglavlja: USVOJENO

Ovo poglavlje definiše poslovni pojam tipa konkursa i njegove osnovne odnose prema poslovnom profilu i godišnjoj instanci.

## 6.1. Definicija tipa konkursa

Tip konkursa je trajno prepoznatljiva poslovna kategorija konkursa na platformi Digital Kotor, određena zajedničkom poslovnom svrhom i skupom posebnih poslovnih pravila koji zahtijevaju sopstveni poslovni profil.

Tip konkursa nije:

* godina;
* godišnja instanca;
* pojedinačni poziv;
* verzija poslovnog profila.

Promjena godine, instance, poziva ili verzije poslovnog profila sama po sebi ne stvara novi tip konkursa.

## 6.2. Kada nastaje novi tip konkursa

Novi tip konkursa uspostavlja se kada konkurs ima zasebnu poslovnu svrhu i/ili takav skup posebnih poslovnih pravila da se ne može jednoznačno opisati postojećim poslovnim profilom bez promjene poslovnog identiteta tog profila.

Svaka promjena poslovnog pravila ne stvara novi tip konkursa.

Ako poslovna cjelina zadržava isti poslovni identitet, promjena pravila može zahtijevati novu verziju postojećeg poslovnog profila.

Ako nastaje nova poslovna cjelina koja zahtijeva sopstveni poslovni profil, uspostavlja se novi tip konkursa.

Ovo poglavlje ne razrađuje pravila verzionisanja profila.

## 6.3. Stabilan poslovni identitet

Svaki tip konkursa mora imati stabilan i jedinstven poslovni identitet.

Naziv i opis tipa mogu se mijenjati bez promjene njegovog identiteta pod uslovom da se time ne mijenja poslovna cjelina koju tip predstavlja.

Identitet jednog tipa konkursa ne koristi se ponovo za drugi tip konkursa.

Ovo poglavlje ne određuje tehnički format identifikatora. Tehnička realizacija pripada FS/TS razradi.

## 6.4. Aktivnost i istorija tipa

Tip konkursa koji je korišćen za najmanje jednu godišnju instancu ne briše se iz poslovnog kataloga.

Kada više nije namijenjen za formiranje novih godišnjih instanci, može se označiti kao neaktivan.

Neaktivnost tipa:

* ne mijenja njegov poslovni identitet;
* ne uklanja njegov poslovni profil;
* ne uklanja verzije poslovnog profila;
* ne uklanja postojeće godišnje instance;
* ne uklanja pozive;
* ne mijenja istorijske podatke.

Neaktivnost tipa konkursa nije isto što i završetak, zatvaranje ili arhiviranje konkretne godišnje instance.

Ovo poglavlje ne određuje tehnički status ili kolonu za aktivnost.

## 6.5. Ponovna aktivacija

Neaktivan tip konkursa može se ponovo aktivirati ako se ponovo uspostavlja ista poslovna cjelina.

Ponovna aktivacija:

* ne mijenja poslovni identitet tipa;
* ne stvara novi tip samo zbog vremenskog prekida;
* ne znači automatski ponovno korišćenje posljednje verzije poslovnog profila.

Prije formiranja nove godišnje instance mora biti određena odgovarajuća važeća verzija poslovnog profila.

Ako se više ne radi o istoj poslovnoj cjelini, ne koristi se reaktivacija starog tipa, već se razmatra uspostavljanje novog tipa konkursa.

Ovo poglavlje ne razrađuje kako se određuje važeća verzija profila.

## 6.6. Odnos tipa i poslovnog profila

Svaki tip konkursa ima tačno jedan poslovni profil kao nosioca svojih posebnih poslovnih pravila.

Poslovni profil može imati više uzastopnih verzija.

Odnos je:

**Tip konkursa → Poslovni profil → Verzije poslovnog profila**

Jedan poslovni profil pripada tačno jednom tipu konkursa i ne dijeli se između različitih tipova.

Sličnosti između različitih tipova ne rješavaju se dijeljenjem istog poslovnog profila.

Ono što je zaista zajedničko različitim tipovima pripada zajedničkom poslovnom modelu `KN-BM-001`.

Verzionisanje pravila istog tipa ne stvara novi Document ID poslovnog profila samo zato što je nastala nova verzija njegovih pravila.

Detaljna pravila poslovnog profila i njegovog verzionisanja pripadaju Poglavljima 7 i 8.

## 6.7. Odnos tipa i godišnje instance

Jedan tip konkursa može imati:

**0..N godišnjih instanci.**

Svaka godišnja instanca pripada tačno jednom tipu konkursa.

Pripadnost godišnje instance tipu nakon njenog nastanka ne mijenja se.

Tip može postojati i prije nego što postoji njegova prva godišnja instanca.

Ovo poglavlje ne definiše životni ciklus godišnje instance niti način rješavanja eventualno pogrešno formirane instance.

## 6.8. Preduslov za formiranje godišnje instance

Nova godišnja instanca može se formirati samo za tip konkursa za koji je određena odgovarajuća važeća verzija poslovnog profila.

Samo postojanje tipa konkursa nije dovoljno za formiranje godišnje instance.

Ovo pravilo ne određuje:

* kako se verzija profila označava kao važeća;
* da li mora biti najnovija;
* tehnički način povezivanja instance i verzije profila;
* tehničku validaciju ili status.

Ta pitanja pripadaju Poglavlju 8 i odgovarajućem FS/TS sloju.

---

# 7. Poslovni profil tipa konkursa

Status poglavlja: USVOJENO

Ovo poglavlje definiše poslovni profil tipa konkursa: njegov sadržaj i granice, odnos prema poslovnim i pravnim izvorima i otvorenim pitanjima, te osnovni odnos prema verzijama istog profila.

Detaljna pravila verzionisanja pripadaju Poglavlju 8.

## 7.1. Definicija

Poslovni profil je kanonski poslovni opis jednog tipa konkursa.

Poslovni profil:

* pripada tačno jednom tipu konkursa;
* predstavlja nosioca posebnih poslovnih pravila tog tipa;
* mora sadržati pravila potrebna da se konkretni tip jednoznačno razlikuje i poslovno primjenjuje na platformi.

Jedan poslovni profil ne dijeli se između različitih tipova konkursa.

Ono što je zaista zajedničko različitim tipovima pripada zajedničkom poslovnom modelu `KN-BM-001`.

## 7.2. Poslovni sadržaj profila

U zavisnosti od prirode konkretnog tipa konkursa, poslovnom profilu mogu pripadati:

* cilj i poslovna svrha tipa konkursa;
* poslovni akteri i njihove uloge kada su specifični za taj tip;
* dozvoljeni podnosioci i njihove kategorije;
* uslovi podobnosti;
* poslovne faze i statusi specifični za profil;
* poslovni obrasci;
* potrebna dokumentacija;
* rokovi koji predstavljaju pravilo tipa konkursa;
* eliminacioni uslovi;
* pravila ocjenjivanja, kada profil koristi ocjenjivanje;
* pravila bodovanja, kada profil koristi bodovanje;
* pravila rangiranja, kada profil koristi rangiranje;
* pravila odluke;
* pravila ugovora, kada profil koristi ugovaranje;
* pravila realizacije i izvještavanja, kada su te sposobnosti dio profila;
* druga posebna poslovna pravila konkretnog tipa;
* poslovni i pravni izvori iz kojih su relevantna pravila izvedena.

Ova lista ne znači da svaki poslovni profil mora koristiti sve navedene sposobnosti.

Primjenjuju se postojeći principi `BM-KN-004` i `BM-KN-005`: poslovni profil određuje koje zajedničke sposobnosti koristi i pod kojim poslovnim pravilima.

## 7.3. Šta ne pripada poslovnom profilu

Poslovnom profilu ne pripadaju konkretne godišnje vrijednosti koje profil dozvoljava da se mijenjaju po godišnjoj instanci ili pozivu.

Takve vrijednosti pripadaju odgovarajućoj godišnjoj instanci odnosno pozivu.

Ne unose se u poslovni profil kao poslovna pravila:

* konkretan godišnji budžet samo zato što važi za jednu instancu;
* datum otvaranja konkretnog poziva;
* datum zatvaranja konkretnog poziva;
* druge konkretne vrijednosti koje su po svojoj prirodi podaci ili konfiguracija instance/poziva.

Takođe, poslovnom profilu ne pripadaju tehnički detalji poput:

* tabela i kolona;
* modela;
* kontrolera;
* ruta;
* storage mehanizama;
* tehničkih ENUM vrijednosti;
* konkretne tehničke realizacije validacije;
* drugih implementacionih detalja.

Funkcionalna i tehnička realizacija pripada odgovarajućem FS odnosno TS sloju.

## 7.4. Potpunost poslovnog profila

Poslovni profil mora biti dovoljno potpun da se iz njega mogu izvesti funkcionalni zahtjevi konkretnog tipa konkursa bez potrebe da se poslovna pravila naknadno nagađaju iz:

* izvornog akta;
* postojeće implementacije;
* ranije godišnje instance;
* drugog poslovnog profila;
* tehničke pogodnosti.

Ovo ne znači da poslovni profil mora prepisivati cjelokupne pravne ili poslovne izvore.

Profil mora sadržati poslovna pravila koja utiču na ponašanje platforme u skladu sa `BM-KN-008`.

## 7.5. Izvori poslovnog profila

Poslovni profil nastaje na osnovu:

* odobrenih poslovnih izvora;
* relevantnih pravnih izvora;
* eksplicitno usvojenih poslovnih odluka kada određeno pitanje nije uređeno spoljnim izvorom.

Za svako pravilo mora biti moguće razlikovati njegovo stvarno poslovno porijeklo kada je to relevantno za sljedivost.

Primjenjuje se `BM-KN-010` — Poslovni izvor mora biti sljediv.

## 7.6. Pravilo izvedeno iz spoljnog izvora

Kada poslovno pravilo proizilazi iz odluke, pravilnika, javnog poziva ili drugog relevantnog izvora:

* poslovni profil definiše pravilo koje utiče na ponašanje platforme;
* obezbjeđuje se sljedivost do odgovarajućeg izvora;
* nije potrebno nepotrebno prepisivati cijeli izvorni dokument.

Spoljni dokument ne zamjenjuje poslovni profil za pravila koja utiču na ponašanje platforme.

## 7.7. Odobrena poslovna odluka

Ako spoljni izvor ne uređuje određeno pitanje, ali ostavlja prostor da ono bude uređeno poslovnom odlukom, pravilo može biti definisano eksplicitno odobrenom poslovnom odlukom.

U tom slučaju mora biti jasno da pravilo:

* nije izvedeno iz pravnog ili drugog spoljnog izvora;
* predstavlja odobrenu poslovnu odluku.

Poslovnoj odluci ne smije se pogrešno pripisivati pravni izvor.

## 7.8. Otvoreno pitanje

Ako je relevantni izvor:

* nejasan;
* kontradiktoran;
* nepotpun;
* ili ne daje odgovor potreban za jednoznačno poslovno pravilo,

nejasnoća se evidentira kao **otvoreno pitanje**.

Otvoreno pitanje ne smije se zatvarati pretpostavkom izvedenom iz implementacije, ranije instance, drugog tipa konkursa ili tehničke pogodnosti.

Relevantni dio poslovnog profila ostaje neusvojen dok se pitanje ne razjasni i odgovarajuće poslovno pravilo ne odobri.

## 7.9. Otvorena pitanja i korišćenje profila

Poslovni profil može tokom izrade sadržati otvorena pitanja.

Međutim, verzija poslovnog profila koja se koristi kao poslovni osnov za formiranje nove godišnje instance ne smije imati neriješeno otvoreno pitanje koje utiče na poslovna pravila potrebna za pravilno sprovođenje te instance.

Ovo pravilo ne zahtijeva da dokument nema nijednu otvorenu dokumentacionu napomenu koja nema uticaj na poslovno sprovođenje konkursa.

Ovo poglavlje ne određuje tehnički status niti workflow otvorenog pitanja.

## 7.10. Trajni identitet poslovnog profila

Poslovni profil predstavlja trajni poslovni opis jednog tipa konkursa i ima sopstveni Document ID.

Promjena poslovnih pravila istog tipa konkursa ne stvara novi poslovni profil samo zbog promjene pravila.

Takve promjene evidentiraju se kroz verzije istog poslovnog profila, dok god je očuvan poslovni identitet tipa konkursa.

## 7.11. Promjena poslovnog pravila

Nova poslovna verzija profila nastaje kada se mijenja poslovno pravilo ili poslovno značenje profila.

Redakcijske, jezičke ili dokumentacione korekcije koje ne mijenjaju poslovno značenje ne predstavljaju same po sebi novu poslovnu verziju pravila.

Ovo poglavlje ne određuje:

* format oznake poslovne verzije;
* način aktivacije;
* datum početka važenja;
* način povlačenja verzije;
* tehnički mehanizam verzionisanja.

To pripada Poglavlju 8 i kasnijem FS/TS sloju.

## 7.12. Istorijska granica verzije

Nova ili izmijenjena verzija poslovnog profila ne smije naknadno promijeniti poslovno značenje godišnje instance koja je već vezana za raniju verziju profila.

Detaljna pravila istorijske primjene pripadaju Poglavlju 8.

---

# 8. Verzionisanje profila i istorijska primjena

Status poglavlja: USVOJENO

Ovo poglavlje definiše poslovni model verzionisanja poslovnog profila i istorijsku vezu između verzije profila i godišnje instance.

Ovo poglavlje ne razrađuje tehnički format verzije, bazu, statuse, workflow ili način čuvanja veze.

## 8.1. Značenje verzije poslovnog profila

Poslovni profil može imati jednu ili više verzija.

Svaka verzija predstavlja tačno određeno stanje poslovnih pravila konkretnog tipa konkursa.

Nova poslovna verzija nastaje kada se mijenja:

* poslovno pravilo;
* poslovno značenje;
* ili drugi sadržaj koji mijenja način poslovne primjene profila.

Redakcijske, jezičke i dokumentacione korekcije koje ne mijenjaju poslovno značenje ne predstavljaju same po sebi novu poslovnu verziju pravila.

## 8.2. Veza godišnje instance i verzije profila

Za svaku godišnju instancu mora biti jednoznačno određena verzija poslovnog profila koju ta instanca primjenjuje.

Godišnja instanca ne primjenjuje poslovni profil apstraktno, već određeno stanje njegovih poslovnih pravila.

Veza:

**Godišnja instanca → određena verzija poslovnog profila**

predstavlja dio istorijskog poslovnog značenja te instance.

## 8.3. Istorijska nepromjenljivost veze

Kada je godišnja instanca formirana na osnovu određene verzije poslovnog profila, ta istorijska veza ne mijenja se naknadno na način koji bi promijenio poslovna pravila prema kojima je instanca nastala ili sprovedena.

Naknadno nastala verzija poslovnog profila ne primjenjuje se retroaktivno na postojeće godišnje instance.

Promjena poslovnih pravila za buduću primjenu ne mijenja istorijsko poslovno značenje prethodnih instanci.

## 8.4. Priprema nove verzije

Nova verzija poslovnog profila može se pripremati za buduću primjenu.

Samo postojanje novije verzije ne utiče na postojeće godišnje instance.

Nova verzija ne postaje automatski poslovni osnov postojećih instanci.

## 8.5. Jedna verzija i više godišnjih instanci

Jedna verzija poslovnog profila može biti poslovni osnov za više godišnjih instanci istog tipa konkursa.

To je dozvoljeno kada je za svaku od tih instanci ta verzija određena kao odgovarajuća važeća verzija poslovnog profila.

Nije obavezno kreirati novu verziju profila samo zato što nastaje nova godišnja instanca.

## 8.6. Najnovija verzija nije automatski važeća za novu instancu

Ne smije se pretpostaviti da je numerički ili vremenski najnovija verzija poslovnog profila automatski verzija koja se koristi za svaku novu godišnju instancu.

Za novu godišnju instancu mora biti eksplicitno određena odgovarajuća važeća verzija poslovnog profila.

Ovo poglavlje ne definiše funkcionalni ili tehnički način tog određivanja.

## 8.7. Zaštita korišćene verzije

Verzija poslovnog profila koja je već korišćena kao poslovni osnov najmanje jedne godišnje instance ne mijenja se na način koji mijenja njeno poslovno značenje.

Ako je potrebno promijeniti poslovna pravila za buduće instance, nastaje nova poslovna verzija profila.

Redakcijske korekcije koje ne mijenjaju poslovno značenje mogu se voditi prema pravilima upravljanja dokumentacijom, bez retroaktivne promjene poslovnih pravila istorijske instance.

## 8.8. Povlačenje ili zamjena verzije

Verzija poslovnog profila može prestati da bude namijenjena za buduću primjenu.

Takva promjena ne uklanja:

* samu verziju iz istorije profila;
* godišnje instance koje je koriste;
* poslovna pravila prema kojima su te instance nastale;
* istorijsku sljedivost.

Povlačenje, zamjena ili prestanak buduće primjene verzije ne djeluje retroaktivno.

## 8.9. Granice prema FS i TS

Ovo poglavlje ne određuje:

* format oznake verzije;
* numerički ili drugi tehnički model verzionisanja;
* tehničke statuse verzije;
* način aktivacije;
* način povlačenja;
* workflow odobravanja;
* datum i tehnički mehanizam početka važenja;
* način čuvanja veze između godišnje instance i verzije profila;
* strukturu baze podataka;
* tehničke zaštite od izmjene istorijske verzije.

Funkcionalna pravila pripadaju odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

---

# 9. Godišnja instanca konkursa

Status poglavlja: USVOJENO

Ovo poglavlje definiše značenje godišnje instance, njen odnos prema tipu konkursa i verziji poslovnog profila, koje vrste konkretnih vrijednosti joj pripadaju, granicu prema pozivu i prema poslovnim pravilima profila, te istorijsko poslovno značenje instance.

Osnovna hijerarhija usvojena za dalji model je:

**Tip konkursa → Poslovni profil → Verzija profila → Godišnja instanca → Poziv → Prijava**

Ova hijerarhija u ovom poglavlju služi za poslovno razgraničenje pojmova. Detaljna pravila Poziva i Prijave ne razrađuju se ovdje.

## 9.1. Definicija godišnje instance

Godišnja instanca je konkretno sprovođenje određenog tipa konkursa za određeni poslovni period, zasnovano na tačno određenoj verziji njegovog poslovnog profila.

Godišnja instanca nije:

* novi tip konkursa;
* novi poslovni profil;
* nova verzija poslovnog profila;
* pojedinačni poziv;
* pojedinačna prijava.

Pojam „godišnja“ označava konkretno sprovođenje konkursa za odgovarajući period i ne smije se tumačiti kao tehnička instanca objekta.

## 9.2. Poslovni identitet godišnje instance

Svaka godišnja instanca ima sopstveni poslovni identitet.

Svaka godišnja instanca:

* pripada tačno jednom tipu konkursa;
* zasniva se na tačno određenoj verziji poslovnog profila tog tipa;
* predstavlja zasebno konkretno sprovođenje konkursa.

Jedan tip konkursa može imati više godišnjih instanci.

Godišnja instanca ne može pripadati više tipova konkursa.

Pripadnost instance tipu nakon njenog nastanka ne mijenja se.

## 9.3. Odnos instance i verzije poslovnog profila

Godišnja instanca primjenjuje poslovna pravila tačno određene verzije poslovnog profila.

Veza:

**Godišnja instanca → određena verzija poslovnog profila**

predstavlja dio poslovnog i istorijskog identiteta instance.

Nova godišnja instanca ne zahtijeva automatski novu verziju poslovnog profila.

Više godišnjih instanci može koristiti istu verziju poslovnog profila ako se poslovna pravila nijesu promijenila i ako je ta verzija za svaku od njih određena kao odgovarajuća važeća verzija.

Ovo poglavlje ne ponavlja detaljna pravila verzionisanja iz Poglavlja 8.

## 9.4. Konkretne vrijednosti godišnje instance

Godišnja instanca nosi konkretne vrijednosti koje odgovarajuća verzija poslovnog profila dozvoljava da budu određene za konkretno sprovođenje konkursa.

U zavisnosti od poslovnog profila, takve vrijednosti mogu uključivati, kada su relevantne:

* godinu odnosno poslovni period instance;
* raspoloživi budžet za konkretnu instancu;
* odgovornu organizacionu cjelinu;
* planirani okvir sprovođenja;
* druge vrijednosti koje poslovni profil eksplicitno ili po svom poslovnom značenju dozvoljava da se određuju po instanci.

Ova lista nije univerzalni obavezni skup podataka za svaki tip konkursa.

Poslovni profil određuje koje vrijednosti postoje i koje od njih mogu biti promjenljive po godišnjoj instanci.

Ovo poglavlje ne uvodi dodatne konkretne godišnje vrijednosti koje nijesu poslovno odobrene.

## 9.5. Granica između poslovnog profila i godišnje instance

Poslovni profil definiše poslovno pravilo.

Godišnja instanca određuje konkretnu vrijednost samo tamo gdje poslovni profil dozvoljava da ta vrijednost bude promjenljiva po instanci.

Primjer poslovnog razgraničenja:

* pravilo da se za svaku godišnju instancu određuje raspoloživi budžet pripada poslovnom profilu;
* konkretan iznos budžeta za određenu godišnju instancu pripada toj instanci.

Konfiguracija godišnje instance ne smije:

* izmijeniti poslovno pravilo profila;
* zaobići poslovno pravilo profila;
* proširiti poslovno pravilo profila izvan onoga što profil dozvoljava;
* prikriti promjenu poslovnog pravila kao godišnju konfiguraciju.

Ako je potrebno promijeniti poslovno pravilo za buduće sprovođenje konkursa, primjenjuju se pravila verzionisanja poslovnog profila iz Poglavlja 8.

## 9.6. Granica između godišnje instance i poziva

Godišnja instanca i poziv nijesu isti poslovni pojam.

Godišnja instanca predstavlja konkretno sprovođenje određenog tipa konkursa za odgovarajući poslovni period.

Poziv predstavlja konkretno otvaranje prijava unutar odgovarajuće godišnje instance.

Jedna godišnja instanca može imati jedan ili više poziva ako odgovarajući poslovni profil to dozvoljava.

Zbog toga se u zajedničkom poslovnom modelu ne smije pretpostaviti:

**jedna godišnja instanca = tačno jedan poziv**

Konkretne vrijednosti koje pripadaju pojedinačnom pozivu ne treba automatski tretirati kao vrijednosti godišnje instance.

Na primjer, datum otvaranja i datum zatvaranja konkretnog poziva pripadaju nivou poziva kada predstavljaju rokove tog pojedinačnog poziva.

Detaljna poslovna pravila Poziva pripadaju Poglavlju 10.

## 9.7. Godišnja instanca i prijave

Prijava se ne vezuje za apstraktni tip konkursa niti neposredno za poslovni profil.

U poslovnoj hijerarhiji prijava nastaje u okviru odgovarajućeg poziva, koji pripada godišnjoj instanci.

Osnovna hijerarhija je:

**Tip konkursa → Poslovni profil → Verzija profila → Godišnja instanca → Poziv → Prijava**

Detaljna pravila prijave ne razrađuju se u ovom poglavlju.

## 9.8. Istorijsko poslovno značenje godišnje instance

Godišnja instanca mora ostati istorijski razumljiva prema:

* tipu konkursa kojem pripada;
* verziji poslovnog profila koju primjenjuje;
* konkretnim vrijednostima koje su važile za tu instancu;
* pozivima i drugim istorijskim poslovnim zapisima koji joj pripadaju.

Naknadne izmjene:

* poslovnog profila;
* novih verzija profila;
* budućih godišnjih instanci;
* zajedničkog poslovnog modela;

ne smiju retroaktivno promijeniti poslovno značenje istorijske godišnje instance.

Ovo poglavlje ne određuje tehnički način očuvanja istorijskog stanja.

---

# 10. Poziv i ponovljeni poziv

Status poglavlja: USVOJENO

Ovo poglavlje definiše poslovno značenje Poziva, njegov odnos prema godišnjoj instanci i verziji poslovnog profila, konkretne vrijednosti Poziva, granicu prema poslovnim pravilima, mogućnost više poziva unutar iste godišnje instance, poslovno značenje ponovljenog poziva, odnos Poziva i Prijave, te istorijsku granicu Poziva.

Ovo poglavlje ne razrađuje statuse, workflow, produženje roka ili ponovno otvaranje istog Poziva.

## 10.1. Definicija Poziva

Poziv je konkretno otvaranje mogućnosti za podnošenje prijava u okviru određene godišnje instance konkursa.

Poziv nije:

* tip konkursa;
* poslovni profil;
* verzija poslovnog profila;
* godišnja instanca;
* prijava.

Svaki Poziv pripada tačno jednoj godišnjoj instanci.

Osnovna poslovna hijerarhija ostaje:

**Tip konkursa → Poslovni profil → Verzija profila → Godišnja instanca → Poziv → Prijava**

## 10.2. Odnos prema godišnjoj instanci i verziji profila

Poziv se sprovodi u okviru poslovnih pravila godišnje instance kojoj pripada.

Pošto je godišnja instanca zasnovana na tačno određenoj verziji poslovnog profila, Poziv primjenjuje poslovna pravila te iste verzije.

Poziv ne bira nezavisno drugu verziju poslovnog profila.

Poziv ne može promijeniti istorijsku vezu:

**Godišnja instanca → određena verzija poslovnog profila**

## 10.3. Konkretne vrijednosti Poziva

Poziv može nositi konkretne vrijednosti koje pripadaju baš tom otvaranju mogućnosti za podnošenje prijava.

U zavisnosti od odgovarajućeg poslovnog profila, takve vrijednosti mogu uključivati, kada su relevantne:

* datum otvaranja Poziva;
* datum zatvaranja Poziva;
* konkretni rok za podnošenje prijava;
* druge vrijednosti koje poslovni profil dozvoljava da se određuju ili razlikuju po Pozivu.

Ova lista nije univerzalni obavezni skup podataka za svaki tip konkursa.

Poslovni profil određuje koje vrijednosti postoje i koje od njih smiju biti promjenljive po Pozivu.

Ovo poglavlje ne uvodi dodatne konkretne vrijednosti Poziva koje nijesu poslovno odobrene.

## 10.4. Granica između Poziva i poslovnih pravila

Poziv određuje konkretne vrijednosti samo tamo gdje poslovni profil dozvoljava da se vrijednost određuje ili razlikuje po Pozivu.

Poziv ne smije:

* mijenjati poslovna pravila profila;
* mijenjati poslovna pravila godišnje instance;
* zaobići poslovno pravilo profila;
* proširiti poslovno pravilo izvan onoga što profil dozvoljava;
* koristiti konfiguraciju Poziva kao zamjenu za promjenu poslovnog pravila.

Posebno, novi ili ponovljeni Poziv ne smije samim svojim postojanjem mijenjati, na primjer:

* kategorije dozvoljenih podnosilaca;
* uslove podobnosti;
* obaveznu dokumentaciju;
* kriterijume;
* bodovanje;
* eliminacione uslove;
* druga poslovna pravila profila.

Ako je za buduće sprovođenje potrebno promijeniti poslovno pravilo, primjenjuju se pravila poslovnog profila i njegovog verzionisanja.

## 10.5. Više poziva unutar godišnje instance

Jedna godišnja instanca može imati jedan ili više Poziva ako odgovarajući poslovni profil to dozvoljava.

Zajednički poslovni model ne pretpostavlja:

**jedna godišnja instanca = tačno jedan Poziv**

Mogućnost više Poziva nije automatski dostupna svakom tipu konkursa.

Odgovarajući poslovni profil određuje da li taj tip konkursa dozvoljava više Poziva unutar iste godišnje instance i pod kojim poslovnim pravilima.

## 10.6. Ponovljeni poziv

Ponovljeni poziv je novi Poziv unutar iste godišnje instance.

Ponovljeni poziv:

* nije nova godišnja instanca;
* nije novi tip konkursa;
* nije novi poslovni profil;
* nije nova verzija poslovnog profila samo zato što predstavlja novo otvaranje prijava.

Ponovljeni poziv može postojati samo ako ga odgovarajući poslovni profil dozvoljava i ako se time ne mijenjaju poslovni identitet godišnje instance i poslovna pravila verzije profila koju instanca primjenjuje.

Razlozi zbog kojih konkretan tip konkursa može imati ponovljeni Poziv pripadaju njegovom poslovnom profilu.

Zajednički `KN-BM-001` ne određuje univerzalni katalog razloga za ponovljeni Poziv.

## 10.7. Poziv i Prijava

Prijava nastaje u okviru konkretnog Poziva.

Svaka Prijava pripada tačno jednom Pozivu.

Time mora biti moguće jednoznačno utvrditi:

* na koji Poziv je Prijava podnijeta;
* kojoj godišnjoj instanci taj Poziv pripada;
* koju verziju poslovnog profila ta godišnja instanca primjenjuje.

Kasniji ili ponovljeni Poziv ne premješta postojeću Prijavu sa prethodnog Poziva.

Detaljna poslovna pravila Prijave ne razrađuju se u ovom poglavlju.

## 10.8. Istorijsko poslovno značenje Poziva

Poziv koji je postao istorijski relevantan mora ostati razumljiv prema:

* godišnjoj instanci kojoj pripada;
* verziji poslovnog profila koju ta instanca primjenjuje;
* konkretnim vrijednostima koje su važile za taj Poziv;
* Prijavama koje su nastale u okviru tog Poziva.

Kasniji ili ponovljeni Poziv ne smije retroaktivno promijeniti:

* istorijsko značenje prethodnog Poziva;
* vrijednosti koje su važile za prethodni Poziv;
* pripadnost Prijava prethodnom Pozivu.

Ovo poglavlje ne određuje tehnički način očuvanja istorijskog stanja.

---

# 11. Apstraktni akteri i uloge

Status poglavlja: USVOJENO

Zajednički poslovni model modula Konkursi koristi samo četiri zajednička poslovna aktera:

1. Podnosilac;
2. Administrator konkursa;
3. Komisija;
4. Administrator platforme.

`KN-BM-001` definiše zajedničke poslovne aktere i njihove osnovne granice.

Konkretni poslovni profil `KN-BM-00x` određuje:

* koji od relevantnih aktera učestvuju u tom tipu konkursa;
* njihove konkretne poslovne nadležnosti;
* pravila njihovog djelovanja specifična za taj tip konkursa.

Zajednički model ne smije nepotrebno razlagati poslovne odgovornosti na dodatne apstraktne aktere. Ako konkretan profil zahtijeva posebnost koja nije pokrivena zajedničkim modelom, ona se prvo poslovno razmatra; ne uvodi se automatski u `KN-BM-001`.

## 11.1. Podnosilac

Podnosilac je lice ili subjekt koji učestvuje na konkretnom Pozivu podnošenjem Prijave.

Na zajedničkom nivou Podnosilac:

* priprema Prijavu;
* podnosi Prijavu;
* obezbjeđuje podatke i dokumentaciju koje zahtijeva odgovarajući poslovni profil.

`KN-BM-001` ne određuje koje konkretne kategorije lica ili subjekata mogu biti Podnosioci.

To određuje poslovni profil konkretnog tipa konkursa.

Zato različiti tipovi konkursa mogu imati različite kategorije dozvoljenih Podnosilaca, a i dalje koristiti zajednički poslovni pojam **Podnosilac**.

Ovo poglavlje ne razrađuje:

* uslove podobnosti;
* identifikacione podatke Podnosioca;
* konkretne forme;
* dokumentaciju konkretnog tipa konkursa;
* tehničku autentifikaciju ili autorizaciju.

## 11.2. Administrator konkursa

Administrator konkursa je poslovna uloga odgovorna za operativno upravljanje sprovođenjem konkursa na platformi.

U granicama odgovarajućeg poslovnog profila, modul mora moći podržati da Administrator konkursa:

* upravlja sprovođenjem godišnje instance;
* upravlja Pozivima;
* sprovodi administrativnu obradu Prijava;
* obavlja druge operativne poslovne radnje koje mu konkretni poslovni profil dodjeljuje.

Ova zajednička definicija ne znači da Administrator konkursa automatski ima svaku moguću poslovnu nadležnost.

Konkretni poslovni profil određuje njegov precizan obim poslovnih ovlašćenja.

Administrator konkursa ne dobija automatski ovlašćenja koja pripadaju Komisiji.

## 11.3. Komisija

Komisija je kolektivni poslovni akter koji učestvuje u poslovnoj obradi Prijava kada je Komisija predviđena odgovarajućim poslovnim profilom.

Konkretni poslovni profil određuje stvarne nadležnosti Komisije.

U zavisnosti od profila, te nadležnosti mogu uključivati, kada su relevantne:

* pregled Prijava;
* ocjenjivanje;
* bodovanje;
* rangiranje;
* utvrđivanje prijedloga;
* donošenje određene odluke;
* druge poslovne radnje koje konkretni profil povjerava Komisiji.

Ova lista ne znači da Komisija u svakom tipu konkursa ima sve navedene nadležnosti.

`KN-BM-001` ne propisuje univerzalno da Komisija uvijek ocjenjuje, boduje, rangira ili donosi konačnu odluku.

To određuje odgovarajući poslovni profil.

## 11.4. Administrator platforme

Administrator platforme je uloga odgovorna za platformske i zajedničke administrativne aspekte modula Konkursi.

Administratorska privilegija na platformi sama po sebi ne predstavlja poslovno ovlašćenje za sprovođenje konkretnog konkursa.

Administrator platforme samim postojanjem te uloge ne postaje automatski:

* Administrator konkursa;
* član Komisije.

Poslovna ovlašćenja u konkretnom konkursu moraju proizilaziti iz odgovarajućeg poslovnog modela i njegove funkcionalne realizacije, a ne samo iz nivoa tehničke privilegije korisnika.

Detaljno mapiranje na tehničke role i autorizaciju pripada FS/TS sloju.

## 11.5. Konkretne nadležnosti

`KN-BM-001` definiše zajedničke poslovne aktere i njihove osnovne granice.

Odgovarajući poslovni profil određuje:

* koji akteri učestvuju u konkretnom tipu konkursa;
* koje poslovne radnje svaki od njih obavlja;
* obim njihovih poslovnih nadležnosti;
* posebna pravila njihovog djelovanja.

Ne proglašava se svaka potencijalna radnja pojedinog aktera univerzalnim pravilom svih konkursa.

## 11.6. Nespojivost uloga

Primjenjuje se `BM-KN-014` — Administrator konkursa ne može biti član Komisije.

Isto lice ne može istovremeno, u okviru istog konkursa odnosno njegovog sprovođenja, obavljati poslovnu ulogu:

* Administratora konkursa;

i

* člana Komisije.

Ovo je zajednička poslovna granica modula i ne ostavlja se pojedinačnom poslovnom profilu da je dozvoli ili ukine.

Konkretni poslovni profil može dodatno razraditi sastav Komisije i uslove članstva, ali ne može ukinuti ovu zajedničku nespojivost.

Ovo poglavlje ne određuje tehnički način sprovođenja ove zabrane.

Autorizacija, validacija dodjele uloga i tehnička zaštita pripadaju FS/TS sloju.

---

# 12. Apstraktne kategorije

Status poglavlja: USVOJENO

Ovo poglavlje definiše poslovno značenje apstraktne kategorije, da kategorije nijesu obavezne za svaki tip konkursa, da `KN-BM-001` ne definiše univerzalni katalog konkretnih kategorija, da konkretni poslovni profil određuje koje kategorije koristi i njihova pravila, te granicu prema godišnjoj instanci, Pozivu i implementaciji.

Ovo poglavlje ne kreira katalog konkretnih kategorija.

## 12.1. Apstraktna kategorija

Apstraktna kategorija je poslovna klasifikacija koju konkretni poslovni profil može koristiti za razlikovanje Podnosilaca, Prijava ili drugih poslovnih elemenata kada je takva klasifikacija potrebna za poslovna pravila konkretnog tipa konkursa.

Kategorija postoji radi poslovnog razlikovanja tamo gdje takvo razlikovanje ima stvarno značenje za konkretni tip konkursa.

Samo postojanje tehničke vrijednosti, polja, ENUM-a, forme ili druge implementacione strukture ne čini određenu vrijednost kanonskom poslovnom kategorijom.

## 12.2. Opcionost kategorija

Zajednički poslovni model ne zahtijeva da svaki tip konkursa koristi poslovne kategorije.

Konkretni poslovni profil može:

* koristiti jednu ili više kategorija kada su potrebne njegovim poslovnim pravilima;
* koristiti različite vrste poslovnog razvrstavanja;
* ne koristiti kategorije ako za taj tip konkursa takvo razvrstavanje nije potrebno.

Postojanje kategorija u jednom poslovnom profilu ne znači da iste ili ekvivalentne kategorije moraju postojati u drugom profilu.

## 12.3. Granica zajedničkog modela

`KN-BM-001` ne definiše univerzalni katalog konkretnih kategorija koji važi za sve tipove konkursa.

Zajednički model definiše sposobnost da konkretni poslovni profil koristi poslovne kategorije kada su mu potrebne.

Konkretne kategorije pripadaju odgovarajućem poslovnom profilu.

Kategorija se ne proglašava zajedničkom samo zato što:

* trenutno postoji u implementaciji;
* koristi je postojeći konkurs;
* koristi je više postojećih konkursa;
* postoji kao vrijednost u bazi, formi ili kodu.

Da bi nešto postalo zajedničko poslovno pravilo ili zajednička poslovna kategorija, mora biti eksplicitno poslovno usvojeno na zajedničkom nivou.

## 12.4. Kategorije konkretnog poslovnog profila

Odgovarajući `KN-BM-00x` određuje, kada su kategorije relevantne:

* koje kategorije postoje;
* šta svaka kategorija poslovno znači;
* na koji poslovni element se kategorija odnosi;
* pod kojim uslovima se određena kategorija primjenjuje;
* koja poslovna pravila zavise od kategorije;
* da li određena kategorija utiče na podobnost, obradu, ocjenjivanje ili drugo ponašanje konkretnog tipa konkursa, kada je to relevantno.

Ne pretpostavlja se da svaka kategorija utiče na sve navedene oblasti.

Profil određuje stvarno poslovno značenje i posljedice kategorije.

## 12.5. Predmet kategorizacije

Apstraktni pojam kategorije nije ograničen samo na kategorije Podnosilaca.

Konkretni poslovni profil može koristiti poslovnu kategorizaciju Podnosilaca, Prijava ili drugih poslovnih elemenata ako je takva klasifikacija potrebna njegovim pravilima.

Međutim, `KN-BM-001` ne uvodi unaprijed konkretne vrste kategorizacije niti unaprijed definisane konkretne kategorije.

Ovim poglavljem se ne zaključuje da određena konkretna kategorija mora ili ne mora postojati u konkretnom poslovnom profilu.

## 12.6. Kategorija i pravila profila

Postojanje kategorije i poslovna pravila koja se na nju primjenjuju moraju biti poslovno razdvojivi.

Sama oznaka ili naziv kategorije nije dovoljna da odredi:

* ko joj pripada;
* kako se pripadnost utvrđuje;
* koja prava ili ograničenja iz nje proizilaze;
* kakav uticaj ima na Prijavu ili sprovođenje konkursa.

Ta pitanja određuje konkretni poslovni profil kada su relevantna.

Pravila kategorije ne izvode se iz njenog naziva.

## 12.7. Profil, instanca i Poziv

Poslovno značenje kategorije pripada poslovnom profilu kada predstavlja pravilo konkretnog tipa konkursa.

Godišnja instanca ili Poziv mogu nositi konkretnu vrijednost povezanu sa kategorijom samo ako odgovarajući poslovni profil dozvoljava da takva vrijednost bude promjenljiva na tom nivou.

Godišnja instanca ili Poziv ne smiju:

* stvarati novu poslovnu kategoriju mimo profila;
* mijenjati poslovno značenje kategorije;
* mijenjati pravila pripadnosti kategoriji;
* koristiti konfiguraciju kao zamjenu za izmjenu poslovnog profila.

Ako se mijenja poslovno značenje kategorije ili pravilo njene primjene za buduće sprovođenje, primjenjuju se pravila verzionisanja poslovnog profila iz Poglavlja 8.

## 12.8. Istorijsko značenje kategorije

Kada je kategorija uticala na poslovno značenje konkretne godišnje instance, Poziva ili Prijave, kasnija promjena poslovnog profila ne smije retroaktivno promijeniti značenje kategorije koje je važilo za istorijsko sprovođenje.

Primjenjuju se već usvojeni principi istorijske primjene verzije poslovnog profila.

Ovo poglavlje ne određuje tehnički način čuvanja istorijske vrijednosti ili veze.

## 12.9. Funkcionalna i tehnička realizacija

Ovo poglavlje ne određuje:

* tehnički identifikator kategorije;
* runtime vrijednosti;
* ENUM vrijednosti;
* strukturu baze;
* tabele ili kolone;
* način izbora kategorije u korisničkom interfejsu;
* način validacije;
* automatsko određivanje kategorije;
* ručno određivanje kategorije;
* autorizaciju za upravljanje kategorijama;
* tehnički konfiguracioni mehanizam.

Funkcionalno ponašanje pripada odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

---

# 13. Konfigurabilne faze i statusi

Status poglavlja: USVOJENO

Ovo poglavlje definiše zajednički apstraktni poslovni model Faza i Statusa, opcionost njihovog korišćenja, odnos prema konkretnom poslovnom profilu, granicu prema godišnjoj instanci i Pozivu, istorijsku primjenu i granicu prema FS/TS sloju.

Ovo poglavlje ne kreira univerzalni katalog konkretnih Faza ili Statusa.

## 13.1. Faza

Faza je poslovni segment toka kroz koji konkretni tip konkursa može prolaziti kada njegov poslovni profil predviđa fazno sprovođenje.

Faza predstavlja poslovno značenje dijela procesa, a ne tehnički ekran, rutu, ENUM vrijednost, servis ili drugi implementacioni element.

Zajednički `KN-BM-001` ne određuje konkretne Faze pojedinačnih tipova konkursa.

Konkretni poslovni profil određuje, kada koristi Faze:

* koje Faze postoje;
* njihovo poslovno značenje;
* njihov međusobni odnos;
* redosljed, kada je redosljed poslovno propisan;
* uslove prelaska između Faza, kada su takvi uslovi relevantni.

## 13.2. Status

Status je poslovno stanje konkretnog poslovnog elementa u određenom trenutku, kada odgovarajući poslovni profil koristi statuse.

Status mora imati jasno poslovno značenje.

Samo postojanje tehničke vrijednosti statusa u kodu ili bazi ne čini tu vrijednost kanonskim poslovnim statusom.

Konkretni poslovni profil određuje:

* koje Statuse koristi;
* na koji poslovni element se Status odnosi;
* značenje svakog Statusa;
* poslovne uslove pod kojima Status nastaje ili se mijenja;
* dozvoljene poslovne prelaze između Statusa, kada profil koristi definisane prelaze.

## 13.3. Razdvajanje Faze i Statusa

Faza i Status nijesu isti poslovni pojam.

Faza opisuje poslovni segment toka.

Status opisuje poslovno stanje konkretnog poslovnog elementa.

Konkretni poslovni profil određuje da li koristi:

* Faze;
* Statuse;
* i Faze i Statuse;
* ili nijedan od ta dva pojma ako nijesu potrebni za taj tip konkursa.

Zajednički model ne smije pretpostaviti da svaka Faza mora imati sopstveni Status niti da svaki Status predstavlja zasebnu Fazu.

## 13.4. Opcionost

Faze i Statusi nijesu obavezni za svaki tip konkursa.

Poslovni profil može:

* koristiti formalno definisane Faze bez zasebnog kataloga Statusa;
* koristiti Statuse bez formalnog faznog modela;
* koristiti i Faze i Statuse;
* ne koristiti formalne Faze ili Statuse kada nijesu potrebni njegovim poslovnim pravilima.

Postojanje Faza ili Statusa u jednom poslovnom profilu ne znači da ih drugi poslovni profil mora koristiti.

Primjenjuju se `BM-KN-004` i `BM-KN-005`.

## 13.5. Konkretne Faze i Statusi pripadaju profilu

`KN-BM-001` ne definiše univerzalni katalog Faza ili Statusa koji važi za sve tipove konkursa.

Konkretni `KN-BM-00x` određuje Faze i Statuse konkretnog tipa konkursa kada su oni potrebni.

Faza ili Status se ne proglašavaju zajedničkim samo zato što:

* postoje u današnjoj implementaciji;
* koristi ih postojeći konkurs;
* koristi ih više konkursa;
* postoje u tabeli;
* postoje kao ENUM;
* postoje kao string vrijednost u kodu;
* postoje kao naziv ekrana ili akcije.

Postojeća implementacija nije izvor poslovnog pravila.

## 13.6. Prelazi između Faza i Statusa

Kada konkretni poslovni profil definiše Faze ili Statuse, može definisati i poslovno dozvoljene prelaze između njih.

Profil određuje, kada je relevantno:

* iz koje Faze ili Statusa je prelaz moguć;
* u koju Fazu ili Status je prelaz moguć;
* poslovne preduslove prelaza;
* poslovnog aktera koji je ovlašćen da izvrši odgovarajuću poslovnu radnju, kada je to dio poslovnog pravila;
* poslovne posljedice prelaza.

`KN-BM-001` ne definiše univerzalnu state-machine šemu za sve konkurse.

Tehničko sprovođenje prelaza pripada FS/TS sloju.

## 13.7. Profil je izvor poslovnog značenja Faze i Statusa

Godišnja instanca ili Poziv ne smiju samostalno:

* uvoditi novu poslovnu Fazu;
* uvoditi novi poslovni Status;
* mijenjati značenje postojeće Faze;
* mijenjati značenje postojećeg Statusa;
* mijenjati poslovno dozvoljene prelaze mimo pravila poslovnog profila.

Instanca ili Poziv mogu koristiti konkretne vrijednosti ili stanje samo u granicama koje odgovarajuća verzija poslovnog profila dozvoljava.

Konfiguracija instance ili Poziva ne smije biti zamjena za promjenu poslovnog profila.

## 13.8. Promjena poslovnog značenja

Ako se za buduće sprovođenje konkursa mijenja:

* poslovno značenje Faze;
* poslovno značenje Statusa;
* redosljed Faza koji predstavlja poslovno pravilo;
* dozvoljeni poslovni prelaz;
* poslovni preduslov prelaza;

primjenjuju se pravila verzionisanja poslovnog profila iz Poglavlja 8.

Takva promjena nije obična konfiguracija godišnje instance ili Poziva.

## 13.9. Istorijsko značenje Faze i Statusa

Faza ili Status koji su imali poslovno značenje u okviru istorijske godišnje instance, Poziva, Prijave ili drugog poslovnog zapisa moraju ostati razumljivi prema verziji poslovnog profila koja je tada važila.

Kasnija promjena poslovnog profila ne smije retroaktivno promijeniti značenje:

* istorijske Faze;
* istorijskog Statusa;
* istorijskog prelaza;
* poslovnog rezultata nastalog tim prelazom.

Ovo poglavlje ne određuje tehnički način očuvanja istorijskog stanja.

Ovo poglavlje ne definiše kompletni životni ciklus godišnje instance, Poziva, poslovnog profila, verzije profila niti otvorenog pitanja. Definiše samo apstraktni model Faza, Statusa i njihovih poslovnih prelaza kada ih konkretni profil koristi.

Poglavlja 15–18 posebno obrađuju neke opcione poslovne sposobnosti i ne iscrpljuju se unaprijed ovim poglavljem.

## 13.10. Funkcionalna i tehnička realizacija

Ovo poglavlje ne određuje:

* tehničke ENUM vrijednosti;
* string vrijednosti statusa;
* state-machine biblioteku;
* modele;
* tabele;
* kolone;
* rute;
* kontrolere;
* middleware;
* policy-je;
* tehničke dozvole;
* UI dugmad za prelaz;
* automatske cron/job tranzicije;
* tehnički način čuvanja istorije Statusa;
* audit implementaciju.

Funkcionalno ponašanje pripada odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

---

# 14. Obrasci i dokumenti kao pojmovi

Status poglavlja: USVOJENO

Ovo poglavlje definiše poslovni pojam Obrasca i Dokumenta, njihovu opcionost, odnos prema konkretnom poslovnom profilu, granicu prema kategorijama, Fazama i Statusima, granicu prema godišnjoj instanci i Pozivu, istorijsku primjenu i granicu prema FS/TS sloju.

Ovo poglavlje ne kreira konkretan katalog Obrasca niti konkretan katalog Dokumenta.

## 14.1. Obrazac

Obrazac je poslovno definisana struktura podataka koju konkretni poslovni profil zahtijeva od Podnosioca ili drugog poslovnog aktera radi sprovođenja konkursa.

Obrazac definiše poslovno:

* koju vrstu podataka ili poslovnih cjelina je potrebno prikupiti;
* od kog poslovnog aktera;
* u kojem poslovnom kontekstu;
* za koju poslovnu svrhu.

Obrazac nije isto što i:

* ekran;
* HTML forma;
* Blade/Vue komponenta;
* PDF;
* Word dokument;
* tabela baze;
* tehnička request/validation klasa;
* drugi implementacioni element.

Zajednički `KN-BM-001` ne određuje konkretne Obrasce pojedinačnih tipova konkursa.

## 14.2. Dokument

Dokument je zaseban poslovni dokaz, prilog, potvrda, izjava ili drugi sadržaj koji poslovni profil zahtijeva ili dozvoljava u okviru sprovođenja konkretnog tipa konkursa.

Dokument u poslovnom modelu predstavlja poslovni zahtjev ili poslovni sadržaj.

Dokument nije isto što i:

* fizički fajl u storage-u;
* tehnički upload;
* MIME tip;
* putanja fajla;
* cloud objekat;
* baza podataka;
* tehnički attachment zapis.

Zajednički `KN-BM-001` ne određuje konkretni katalog Dokumenta za sve konkurse.

## 14.3. Opcionost

Zajednički poslovni model ne zahtijeva da svaki tip konkursa koristi isti broj niti isti tip Obrasca ili Dokumenta.

Konkretni poslovni profil može, u zavisnosti od svojih poslovnih pravila:

* ne koristiti poseban Obrazac;
* koristiti jedan Obrazac;
* koristiti više Obrasca;
* ne zahtijevati posebne Dokumente;
* zahtijevati jedan ili više Dokumenta;
* dozvoliti određene opcione Dokumente.

Postojanje Obrasca ili Dokumenta u jednom poslovnom profilu ne znači da isti element mora postojati u drugom poslovnom profilu.

Primjenjuju se `BM-KN-004` i `BM-KN-005`.

## 14.4. Pravila konkretnog profila

Odgovarajući `KN-BM-00x` određuje, kada su Obrasci ili Dokumenti relevantni:

### Za Obrazac

* koji Obrazac postoji;
* ko ga popunjava ili obezbjeđuje;
* koju poslovnu svrhu ima;
* koje poslovne cjeline ili podatke sadrži;
* u kojoj Fazi ili poslovnom trenutku se koristi, kada je to relevantno;
* pod kojim uslovima se primjenjuje;
* da li zavisi od određene kategorije ili drugog poslovnog pravila.

### Za Dokument

* koji Dokument je potreban ili dozvoljen;
* ko ga obezbjeđuje;
* njegovu poslovnu svrhu;
* da li je obavezan ili opcion;
* za koje kategorije ili druge poslovne uslove važi;
* u kojoj Fazi ili poslovnom trenutku mora biti dostavljen, kada je to relevantno;
* koje poslovne posljedice nastaju ako nedostaje, kada profil definiše takvu posljedicu.

Ne pretpostavlja se da svaki profil koristi sve navedene mogućnosti.

## 14.5. Razdvajanje pojmova

Obrazac i Dokument nijesu isti poslovni pojam.

Obrazac predstavlja poslovno strukturisano prikupljanje podataka.

Dokument predstavlja zaseban poslovni dokaz, prilog ili drugi sadržaj.

Konkretni poslovni profil određuje njihov međusobni odnos kada ga koristi.

Ne pretpostavlja se da:

* svaki Obrazac mora imati Dokument;
* svaki Dokument mora biti dio Obrasca;
* svaki podatak u Obrascu mora biti potvrđen posebnim Dokumentom.

To određuje poslovni profil konkretnog tipa konkursa.

## 14.6. Kategorije i zahtjevi

Poslovni profil može definisati različite Obrasce ili Dokumente za različite poslovne kategorije kada je takva razlika dio njegovih poslovnih pravila.

Međutim:

* godišnja instanca;
* Poziv;
* tehnička implementacija

ne smiju samostalno stvarati nove kategorijski zavisne zahtjeve mimo poslovnog profila.

Zahtjevi za Obrasce ili Dokumente ne izvode se samo iz naziva kategorije.

## 14.7. Poslovni trenutak korišćenja

Kada poslovni profil koristi Faze ili Statuse, može definisati poslovni trenutak u kojem:

* određeni Obrazac mora biti popunjen;
* određeni Dokument mora biti dostavljen;
* određeni Obrazac ili Dokument više nije moguće ili potrebno dostavljati;
* nedostatak Obrasca ili Dokumenta proizvodi određenu poslovnu posljedicu.

Ovo poglavlje ne definiše konkretne Faze, Statuse ili prelaze.

Njihovo poslovno značenje pripada poslovnom profilu i Poglavlju 13.

## 14.8. Profil je izvor zahtjeva

Poslovni profil određuje:

* koji Obrasci postoje;
* koji Dokumenti postoje;
* njihovo poslovno značenje;
* uslove njihove primjene.

Godišnja instanca ili Poziv mogu nositi samo one konkretne vrijednosti povezane sa Obrascem ili Dokumentom koje poslovni profil dozvoljava da budu promjenljive na tom nivou.

Godišnja instanca ili Poziv ne smiju samostalno:

* uvoditi novi poslovni Obrazac;
* uvoditi novi poslovni Dokument;
* mijenjati poslovnu obaveznost Dokumenta;
* mijenjati poslovni sadržaj Obrasca;
* mijenjati poslovne posljedice nedostajućeg Dokumenta;
* koristiti konfiguraciju kao zamjenu za promjenu poslovnog profila.

Ako se za buduće sprovođenje mijenja poslovni zahtjev Obrasca ili Dokumenta, primjenjuju se pravila verzionisanja poslovnog profila iz Poglavlja 8.

## 14.9. Istorijsko značenje Obrasca i Dokumenta

Obrazac ili dokumentacioni zahtjev koji je važio za istorijsku godišnju instancu, Poziv ili Prijavu mora ostati razumljiv prema verziji poslovnog profila koja je tada važila.

Kasnija promjena poslovnog profila ne smije retroaktivno promijeniti:

* koje poslovne podatke je istorijski Obrazac zahtijevao;
* koji Dokumenti su bili potrebni ili dozvoljeni;
* obaveznost Dokumenta;
* poslovnu posljedicu nedostajućeg Dokumenta;
* značenje istorijski podnijetog Obrasca ili Dokumenta.

Ovo poglavlje ne određuje tehnički način čuvanja istorijske verzije forme ili fajla.

Ovo poglavlje ne definiše niti proglašava zajedničkim konkretne Obrasce ili Dokumente pojedinačnih tipova konkursa. Ovim poglavljem se ne zaključuje da određeni konkretni Obrazac ili Dokument mora ili ne mora postojati u konkretnom poslovnom profilu.

## 14.10. Funkcionalna i tehnička realizacija

Ovo poglavlje ne određuje:

* izgled Obrasca;
* broj ekrana;
* UI polja;
* HTML/Blade/Vue implementaciju;
* način funkcionalne validacije;
* Form Request klase;
* format fajla;
* PDF/Word/Excel format;
* MIME tip;
* veličinu fajla;
* storage;
* cloud storage;
* naziv direktorijuma;
* upload/download;
* zamjenu fajla;
* verzionisanje fajla;
* elektronski potpis;
* antivirus kontrolu;
* tehnički način povezivanja Dokumenta i Prijave;
* tehnički identifikator Obrasca ili Dokumenta.

Funkcionalno ponašanje pripada odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

---

# 15. Opcione sposobnosti: ocjenjivanje i rangiranje

Status poglavlja: USVOJENO

Ovo poglavlje definiše zajednički apstraktni poslovni okvir za ocjenjivanje, bodovanje i rangiranje, isključivo kao opcione poslovne sposobnosti.

Zajednički model ne pretpostavlja da svaki konkurs ima ocjenjivanje, koristi bodove, ima rang-listu, koristi Komisiju za svaku od ovih radnji, koristi isti model kriterijuma ili isti način donošenja rezultata.

Konkretna pravila pripadaju odgovarajućem poslovnom profilu `KN-BM-00x`.

## 15.1. Ocjenjivanje

Ocjenjivanje je opciona poslovna sposobnost kojom se Prijava razmatra prema unaprijed definisanim poslovnim kriterijumima kada odgovarajući poslovni profil koristi takav način obrade.

Ocjenjivanje ne mora postojati u svakom tipu konkursa.

Konkretni poslovni profil određuje, kada koristi ocjenjivanje:

* da li ocjenjivanje postoji;
* predmet ocjenjivanja;
* kriterijume;
* pravila primjene kriterijuma;
* aktera koji vrši ocjenjivanje;
* poslovne preduslove za ocjenjivanje;
* poslovni rezultat ocjenjivanja.

`KN-BM-001` ne definiše univerzalni katalog kriterijuma.

## 15.2. Bodovanje

Bodovanje je opciona poslovna sposobnost dodjeljivanja numeričke ili druge poslovno definisane vrijednosti Prijavi ili njenom dijelu prema pravilima konkretnog poslovnog profila.

Ocjenjivanje i bodovanje nijesu nužno isti pojam.

Profil može:

* koristiti ocjenjivanje sa bodovima;
* koristiti ocjenjivanje bez bodova;
* koristiti bodovanje kao dio šireg modela ocjenjivanja;
* ne koristiti bodovanje.

Ako profil koristi bodovanje, on određuje najmanje poslovno značenje bodova i pravila prema kojima nastaju.

`KN-BM-001` ne definiše univerzalnu skalu bodova i ne pretpostavlja konkretnu skalu.

## 15.3. Rangiranje

Rangiranje je opciona poslovna sposobnost uspostavljanja poslovnog poretka između Prijava kada odgovarajući poslovni profil zahtijeva takav poredak.

Rangiranje nije obavezno čak ni kada konkurs koristi ocjenjivanje ili bodovanje.

Konkretni poslovni profil određuje:

* da li rangiranje postoji;
* na osnovu kojih poslovnih rezultata nastaje;
* koje Prijave učestvuju u rangiranju;
* pravila poretka;
* pravila za jednake rezultate, ako su potrebna;
* poslovno značenje nastalog poretka.

`KN-BM-001` ne definiše univerzalni algoritam rangiranja.

## 15.4. Ocjenjivanje, bodovanje i rangiranje nijesu isto

Ocjenjivanje, bodovanje i rangiranje nijesu isti poslovni pojam.

Zajednički model ne pretpostavlja:

**ocjenjivanje = bodovanje = rangiranje**

Moguće je da konkretni profil koristi različite kombinacije ovih sposobnosti.

Na apstraktnom nivou moguće su, između ostalog, sljedeće kombinacije:

* ocjenjivanje bez bodovanja;
* ocjenjivanje i bodovanje bez rangiranja;
* ocjenjivanje, bodovanje i rangiranje.

Ove kombinacije su samo apstraktne mogućnosti. Ovim poglavljem se ne tvrdi da konkretni tip konkursa koristi neku od njih.

Primjenjuju se `BM-KN-004` i `BM-KN-005`.

## 15.5. Odnos prema Komisiji

Primjenjuje se Poglavlje 11.

Komisija može imati nadležnosti u vezi sa pregledom, ocjenjivanjem, bodovanjem ili rangiranjem samo kada joj odgovarajući poslovni profil te nadležnosti dodjeljuje.

Zajednički model ne propisuje da Komisija uvijek ocjenjuje, uvijek boduje ili uvijek rangira.

Ovo poglavlje ne uvodi nove zajedničke aktere. Zajednički akteri ostaju:

1. Podnosilac;
2. Administrator konkursa;
3. Komisija;
4. Administrator platforme.

Primjenjuje se `BM-KN-014` — Administrator konkursa ne može biti član Komisije.

## 15.6. Kriterijumi

Kriterijum je poslovno pravilo prema kojem se određeni aspekt Prijave razmatra ili ocjenjuje kada profil koristi kriterijume.

Konkretni poslovni profil određuje:

* koje kriterijume koristi;
* poslovno značenje svakog kriterijuma;
* način njegove poslovne primjene;
* da li kriterijum nosi bodove;
* maksimalne/minimalne vrijednosti ako postoje;
* težinu ili ponder ako postoji;
* druge poslovne posljedice kriterijuma.

`KN-BM-001` ne definiše univerzalni katalog kriterijuma.

Ovo poglavlje ne unosi konkretne kriterijume pojedinačnih tipova konkursa.

## 15.7. Granica prema eliminacionim uslovima

Kriterijum, bodovni kriterijum i eliminacioni uslov ne izjednačavaju se automatski.

Konkretni poslovni profil određuje njihovo poslovno značenje i međusobni odnos.

Ako neispunjenje određenog uslova znači da Prijava ne može nastaviti dalju obradu ili ocjenjivanje, takva posljedica mora biti poslovno definisana u konkretnom profilu.

`KN-BM-001` ne kreira zajednički katalog eliminacionih uslova.

## 15.8. Granica prema odluci

Rezultat ocjenjivanja, broj bodova ili mjesto na rang-listi ne smiju se na zajedničkom nivou automatski izjednačiti sa konačnom poslovnom odlukom.

Konkretni poslovni profil određuje:

* odnos rezultata ocjenjivanja prema odluci;
* da li rangiranje predstavlja prijedlog;
* da li predstavlja osnov za neku narednu poslovnu radnju;
* da li postoji zasebna odluka;
* ko je nadležan za tu odluku.

Detaljna apstraktna sposobnost Odluke pripada Poglavlju 17.

## 15.9. Faze i Statusi

Ako poslovni profil koristi Faze ili Statuse, može odrediti:

* kada Prijava ulazi u ocjenjivanje;
* kada se bodovanje vrši;
* kada nastaje rangiranje;
* poslovne preduslove za te radnje;
* poslovne posljedice njihovog završetka.

Ovo poglavlje ne uvodi konkretne Faze ili Statuse i ne uspostavlja univerzalni workflow ocjenjivanja.

Primjenjuje se Poglavlje 13.

## 15.10. Istorijsko značenje rezultata

Ocjena, bodovi, rang ili drugi rezultat koji je nastao u istorijskom sprovođenju konkursa mora ostati poslovno razumljiv prema verziji poslovnog profila koja je tada važila.

Kasnija promjena profila ne smije retroaktivno promijeniti poslovno značenje:

* kriterijuma;
* ocjene;
* boda;
* pondera;
* rezultata;
* mjesta u poretku;
* pravila prema kojem je rezultat nastao.

Ovo poglavlje ne određuje tehnički način snapshotovanja ili čuvanja istorijskih rezultata.

## 15.11. Profil je izvor pravila ocjenjivanja

Godišnja instanca ili Poziv ne smiju samostalno:

* uvesti novi kriterijum;
* ukloniti kriterijum;
* promijeniti poslovno značenje kriterijuma;
* promijeniti način bodovanja;
* promijeniti ponder;
* promijeniti pravilo rangiranja;
* promijeniti eliminacioni uslov;

osim ako odgovarajući poslovni profil eksplicitno definiše neku vrijednost kao promjenljivu na nivou instance ili Poziva.

Konfiguracija ne smije biti zamjena za promjenu poslovnog pravila.

Ako se mijenja poslovno pravilo za buduće sprovođenje, primjenjuje se Poglavlje 8.

## 15.12. Funkcionalna i tehnička realizacija

Ovo poglavlje ne određuje:

* UI za ocjenjivanje;
* forme Komisije;
* tabele;
* kolone;
* ENUM-e;
* modele;
* kontrolere;
* rute;
* permissions;
* policy-je;
* formule u kodu;
* JavaScript obračun;
* SQL obračun;
* automatsko sortiranje;
* način zaključavanja ocjene;
* audit implementaciju;
* tehnički način čuvanja pojedinačnih ocjena članova Komisije.

Funkcionalno ponašanje pripada odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

---

# 16. Opciona sposobnost: prijava

Status poglavlja: USVOJENO

Ovo poglavlje definiše poslovni pojam Prijave kao opcione sposobnosti, njen odnos prema Podnosiocu, Pozivu, Obrascu i Dokumentu, te zajednička pravila podnošenja, povlačenja i istorijskog očuvanja.

Konkretni sadržaj Prijave, uslovi podnošenja, režim izmjene i dopune, te trenutak do kojeg je povlačenje dozvoljeno pripadaju odgovarajućem poslovnom profilu `KN-BM-00x`, osim zajedničke granice `BM-KN-015`.

## 16.1. Poslovni pojam Prijave

Prijava je poslovni zapis kojim Podnosilac učestvuje na konkretnom Pozivu.

Svaka Prijava pripada tačno jednom Pozivu, a preko Poziva odgovarajućoj godišnjoj instanci i verziji poslovnog profila koju ta instanca primjenjuje.

Razlikuju se:

* Podnosilac = lice ili subjekt koji konkuriše;
* Prijava = konkretno učešće Podnosioca na Pozivu;
* Obrazac = struktura poslovnih podataka koju Prijava može koristiti;
* Dokument = poslovni prilog ili dokaz koji može pripadati Prijavi.

Prijava nije isto što i Podnosilac, Obrazac ili Dokument.

## 16.2. Sadržaj Prijave određuje poslovni profil

`KN-BM-001` ne propisuje univerzalni sadržaj Prijave.

Konkretni `KN-BM-00x` određuje, kada je relevantno:

* podatke Prijave;
* Obrasce;
* obavezne ili opcione Dokumente;
* uslovne podatke ili Dokumente;
* izjave ili potvrde;
* druge poslovne elemente potrebne za potpunu Prijavu.

Postojeća forma ili implementacija nije izvor poslovnog pravila.

## 16.3. Priprema i podnošenje Prijave

Priprema Prijave i njeno podnošenje nijesu ista poslovna radnja.

Podnosilac može pripremati Prijavu prije podnošenja ako konkretni poslovni profil predviđa takav način rada.

Prijava u pripremi još nije podnijeta na Poziv.

Podnošenje je posebna poslovna radnja kojom Podnosilac predaje Prijavu na konkretni Poziv.

Konkretni profil određuje poslovne uslove pod kojima Prijava može biti podnijeta.

Ovo poglavlje ne uvodi univerzalne tehničke statuse Prijave.

## 16.4. Izmjena, dopuna i povlačenje

`KN-BM-001` ne propisuje isti režim izmjene i dopune za svaki konkurs.

Konkretni poslovni profil određuje:

* da li se podnijeta Prijava može mijenjati;
* da li je moguća dopuna;
* ko i pod kojim uslovima može inicirati ili izvršiti dopunu;
* poslovni period u kojem je izmjena ili dopuna moguća;
* do kojeg trenutka je povlačenje dozvoljeno;
* druge poslovne posljedice izmjene, dopune i povlačenja.

Izmjena ili dopuna postojeće Prijave nije automatski nova Prijava.

## 16.5. Rok i blagovremenost

Prijava se podnosi u okviru konkretnog Poziva.

Konkretni profil određuje pravila blagovremenosti i poslovne posljedice neblagovremenosti.

Konkretni datum ili vrijeme zatvaranja pojedinačnog Poziva pripada Pozivu kada predstavlja promjenljivu vrijednost tog Poziva.

`KN-BM-001` ne propisuje univerzalni konkretni rok niti univerzalnu posljedicu kašnjenja.

## 16.6. Potpunost Prijave

Potpunost Prijave određuje konkretni poslovni profil.

Profil određuje, kada je relevantno:

* obavezne podatke;
* Obrasce;
* Dokumente;
* izjave ili potvrde;
* uslovne zahtjeve;
* trenutak provjere potpunosti;
* poslovnu posljedicu nepotpunosti.

Potpunost Prijave nije isto što i podobnost Podnosioca ili prihvatljivost Prijave.

`KN-BM-001` ne propisuje univerzalno da se nepotpuna Prijava odbija.

## 16.7. Jedna aktivno podnijeta Prijava po Pozivu

Primjenjuje se `BM-KN-015` — Jedna aktivno podnijeta Prijava po Podnosiocu i Pozivu.

Podnosilac može imati najviše jednu aktivno podnijetu Prijavu na istom Pozivu.

Ovo nije opcija pojedinačnog poslovnog profila da je ukine.

Ovo pravilo ne znači da Podnosilac može ikada imati samo jednu Prijavu na Pozivu. Nakon povlačenja može nastati nova Prijava u skladu sa §16.8.

## 16.8. Nova Prijava nakon povlačenja

Ako Podnosilac povuče već podnijetu Prijavu, može podnijeti novu Prijavu na isti Poziv sve dok nije istekao rok za podnošenje Prijava.

Povučena Prijava:

* ne briše se;
* ostaje istorijski zapis.

Nova Prijava:

* predstavlja novi poslovni zapis;
* ne reaktivira prethodnu povučenu Prijavu;
* ne prepisuje istorijski zapis prethodne Prijave.

## 16.9. Istek roka

Istekom roka za podnošenje nije moguće podnijeti novu Prijavu na taj Poziv.

Ako se Prijava povuče nakon isteka roka, pod uslovom da konkretni profil uopšte dozvoljava povlačenje u tom trenutku, to ne daje pravo na podnošenje nove Prijave.

Već podnijeta Prijava nastavlja dalji poslovni postupak prema pravilima konkretnog profila.

Naknadna dopuna postojeće Prijave može postojati samo ako je konkretni poslovni profil dozvoljava.

Razlikuju se:

* rok za podnošenje nove Prijave;

od

* eventualne kasnije obrade ili dopune već podnijete Prijave.

## 16.10. Poslovno značenje povlačenja

Povlačenje je poslovna radnja Podnosioca kojom odustaje od dalje obrade konkretne Prijave.

Povučena Prijava:

* ne briše se;
* ostaje istorijski zapis;
* više ne učestvuje u daljoj obradi, ocjenjivanju, bodovanju, rangiranju ili odlučivanju;
* ne reaktivira se.

Ako rok još traje, Podnosilac može podnijeti novu Prijavu u skladu sa §16.8.

`KN-BM-001` ne propisuje univerzalno do kojeg trenutka je povlačenje dozvoljeno. To određuje konkretni poslovni profil.

## 16.11. Istorijska nepromjenljivost

Prijava koja je postala poslovno relevantan zapis mora ostati istorijski razumljiva prema pravilima koja su za nju važila.

Mora biti moguće poslovno utvrditi najmanje:

* Poziv kojem je pripadala;
* godišnju instancu;
* odgovarajuću verziju poslovnog profila;
* Podnosioca;
* poslovno relevantne podatke, Obrasce i Dokumente;
* poslovno relevantne radnje nad Prijavom, uključujući podnošenje i povlačenje kada su nastale.

Povučena Prijava ostaje istorijski zapis.

Nova Prijava nakon povlačenja ne smije prepisati, zamijeniti niti izbrisati istorijsko značenje prethodne Prijave.

Kasnija promjena profila, Obrasca ili zahtijevane Dokumentacije ne smije učiniti istorijsku Prijavu poslovno nerazumljivom.

Ovo poglavlje ne određuje tehnički mehanizam snapshot, version ili audit čuvanja.

## 16.12. Funkcionalna i tehnička realizacija

Poslovni model definiše poslovno značenje i poslovna pravila Prijave.

Funkcionalna specifikacija definiše funkcionalno ponašanje sistema kojim se ta pravila ostvaruju.

Tehnička specifikacija definiše tehničku realizaciju.

Ovo poglavlje ne određuje:

* DB modele, tabele ili kolone;
* Laravel validacije;
* runtime statuse;
* autosave;
* upload ili storage mehanizme;
* UI dugmad;
* tehničko zaključavanje;
* policy ili middleware;
* tehničku provjeru rokova;
* tehnički način sprečavanja više aktivno podnijetih Prijava;
* tehnički snapshot, versioning ili audit mehanizam.

Funkcionalno ponašanje pripada odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

---

# 17. Opcioni pojmovi: odluka, ugovor, realizacija i izvještavanje

Status poglavlja: USVOJENO

Ovo poglavlje definiše opcione poslovne pojmove Odluke, Prijedloga odluke, pojedinačnog akta, Ugovora, Realizacije i Izvještavanja na zajedničkom apstraktnom nivou.

Konkretni poslovni tok pojedinačnog tipa konkursa pripada odgovarajućem `KN-BM-00x`, a ne ovom poglavlju.

Primjenjuju se `BM-KN-004` i `BM-KN-005`.

## 17.1. Odluka

Odluka je poslovni rezultat postupka odlučivanja o jednoj ili više Prijava, kada konkretni poslovni profil predviđa donošenje Odluke.

Odluka nije obavezna sposobnost svakog tipa konkursa.

Konkretni poslovni profil određuje, kada je relevantno:

* ko donosi Odluku;
* o čemu se odlučuje;
* koji prethodni poslovni rezultati predstavljaju osnov za odlučivanje;
* moguće poslovne rezultate Odluke;
* poslovne posljedice Odluke.

Rezultat ocjenjivanja, broj bodova, rang, preliminarna rang-lista ili prijedlog Komisije nijesu sami po sebi konačna Odluka, osim ako konkretni poslovni profil izričito određuje drugačije.

## 17.2. Prijedlog odluke

Konkretni poslovni profil može predvidjeti Prijedlog odluke kao zaseban poslovni rezultat koji prethodi konačnoj Odluci.

Ako profil koristi Prijedlog odluke:

* Prijedlog odluke nije konačna Odluka;
* njegovo formiranje samo po sebi ne proizvodi poslovne posljedice koje profil vezuje za konačnu Odluku;
* profil određuje ko ga formira;
* profil određuje na osnovu kojih prethodnih rezultata nastaje;
* profil određuje ko je nadležan da ga odobri, potvrdi ili na drugi način donese konačnu Odluku.

`KN-BM-001` ne propisuje da svaki konkurs mora imati Prijedlog odluke.

Prijedlog odluke nije isto što i preliminarna rang-lista.

Rang-lista je rezultat rangiranja.

Prijedlog odluke je zaseban poslovni rezultat postupka odlučivanja kada ga profil koristi.

## 17.3. Konačna Odluka i nadležni donosilac

Konkretni poslovni profil određuje poslovnog aktera ili nadležno tijelo koje donosi konačnu Odluku.

Komisija ne postaje automatski donosilac konačne Odluke samo zato što je pregledala, ocjenjivala, bodovala, rangirala ili formirala prijedlog.

Administrator konkursa takođe nema automatsko ovlašćenje da donese konačnu Odluku.

Isti akter može imati više nadležnosti samo kada ih konkretni profil zaista predviđa i kada se time ne krše zajedničke poslovne granice, uključujući `BM-KN-014`.

Ovo poglavlje ne uvodi novog zajedničkog aktera. Zajednički akteri ostaju oni definisani Poglavljem 11:

* Podnosilac;
* Administrator konkursa;
* Komisija;
* Administrator platforme.

Konkretni profil može odrediti stvarno nadležno lice ili tijelo za konkretni konkurs bez proglašavanja tog lica ili tijela novim zajedničkim akterom svih konkursa.

## 17.4. Obuhvat Odluke

Konkretni poslovni profil određuje da li se Odluka donosi:

* pojedinačno za jednu Prijavu;
* zbirno za više Prijava;
* ili kroz kombinaciju zbirnog i pojedinačnog odlučivanja.

`KN-BM-001` ne propisuje:

* jedna Prijava = jedna Odluka;
* jedan Poziv = jedna Odluka;
* jedna godišnja instanca = jedna Odluka.

Odluka i pojedinačni akt, uključujući Rješenje kada ga konkretni profil koristi, nijesu automatski isti poslovni pojam.

Ako konkretni konkurs koristi pojedinačni akt kao poseban akt koji proizlazi iz prethodne Odluke, profil mora definisati njihov odnos.

## 17.5. Poslovni rezultat Odluke

Konkretni poslovni profil određuje moguće poslovne rezultate Odluke i njihove posljedice za Prijavu i dalje sprovođenje konkursa.

`KN-BM-001` ne propisuje univerzalne ishode Odluke.

Profil određuje:

* moguće rezultate;
* poslovne uslove za njihov nastanak;
* da li rezultat zavisi od bodova, praga, ranga, raspoloživih sredstava ili drugih prethodno utvrđenih činjenica;
* dalje poslovne posljedice;
* druge poslovno relevantne vrijednosti koje Odluka može odrediti za konkretnu Prijavu.

Prelazak praga, broj bodova ili mjesto na rang-listi sami po sebi ne određuju rezultat Odluke, osim ako konkretni poslovni profil upravo tako propisuje.

## 17.6. Obrazloženje Odluke

Konkretni poslovni profil određuje kada rezultat odlučivanja mora imati obrazloženje, ko ga utvrđuje i koje poslovne zahtjeve obrazloženje mora ispuniti.

Kada ga profil zahtijeva, obrazloženje predstavlja poslovno relevantan dio rezultata odlučivanja.

Obrazloženje se ne izvodi naknadno iz tehničkog loga, komentara ili drugog tehničkog podatka koji profil nije odredio kao poslovno obrazloženje.

`KN-BM-001` ne propisuje univerzalni tekst obrazloženja, univerzalnu strukturu, univerzalni katalog razloga odbijanja niti univerzalno ko piše obrazloženje.

Profil može zahtijevati obrazloženje samo za određene rezultate.

## 17.7. Odluka i pojedinačni akti

Konkretni poslovni profil može odrediti da iz jedne Odluke nastaju jedan ili više pojedinačnih akata za pojedinačne Prijave.

Odluka i pojedinačni akt, na primjer Rješenje kada ga profil koristi, nijesu isti poslovni pojam.

Jedna Odluka može biti poslovni osnov za više pojedinačnih akata.

Svaki pojedinačni akt pripada tačno jednoj Prijavi.

Pojedinačni akt ne mijenja sadržaj Odluke, već predstavlja njenu primjenu na konkretnu Prijavu.

`KN-BM-001` ne propisuje da svaki konkurs mora imati pojedinačna Rješenja i ne proglašava Rješenje obaveznom univerzalnom sposobnošću svih konkursa.

## 17.8. Ugovor

Ugovor je zaseban poslovni odnos koji može nastati kao posljedica odgovarajućeg rezultata Odluke, kada konkretni poslovni profil predviđa ugovaranje.

Ugovor nije obavezan za svaki tip konkursa.

Pozitivna Odluka ne znači automatski da je Ugovor zaključen.

Konkretni profil određuje:

* sa kim se Ugovor zaključuje;
* koji rezultat Odluke predstavlja preduslov za ugovaranje;
* poslovne uslove pod kojima Ugovor može nastati;
* poslovno relevantan sadržaj Ugovora;
* poslovne posljedice Ugovora;
* odnos pojedinačnog akta ili Rješenja i Ugovora kada oba postoje.

Između Odluke i Ugovora profil može zahtijevati pojedinačni akt, dodatnu dokumentaciju, ispunjenje uslova ili drugu poslovnu radnju.

`KN-BM-001` ne propisuje univerzalno sadržaj Ugovora, rok za potpisivanje, način potpisivanja niti posljedice nepotpisivanja.

## 17.9. Realizacija

Realizacija je opciona poslovna sposobnost koja predstavlja sprovođenje odobrene aktivnosti, projekta, programa ili druge obaveze nakon odgovarajućeg rezultata odlučivanja, kada konkretni poslovni profil predviđa praćenje takvog sprovođenja.

Realizacija nije obavezna za svaki tip konkursa.

Pozitivna Odluka sama po sebi ne znači da je Realizacija započela.

Ugovor nije univerzalni preduslov Realizacije. To određuje konkretni poslovni profil.

Profil određuje:

* predmet Realizacije;
* kada Realizacija počinje;
* kada Realizacija završava;
* obaveze odgovarajućeg subjekta tokom Realizacije;
* rokove;
* poslovne rezultate;
* druge poslovne uslove Realizacije.

Realizacija nije ograničena samo na trošenje dodijeljenog novca.

Realizacija i Izvještavanje nijesu isti poslovni pojam.

## 17.10. Izvještavanje

Izvještavanje je opciona poslovna sposobnost kojom se, kada to konkretni poslovni profil zahtijeva, evidentira i dokazuje sprovođenje Realizacije ili ispunjenje drugih obaveza nastalih iz konkursa.

Izvještavanje nije obavezno za svaki tip konkursa.

Realizacija i Izvještavanje nijesu isti poslovni pojam.

Postojanje Realizacije ne znači automatski da konkurs mora imati formalno Izvještavanje.

Konkretni profil određuje:

* ko ima obavezu izvještavanja;
* o čemu se izvještava;
* rokove i periode izvještavanja;
* podatke, Obrasce i Dokumente koje izvještaj mora sadržati;
* da li postoji jedan ili više izvještaja;
* poslovne posljedice nedostavljanja, kašnjenja ili neispunjavanja zahtjeva izvještavanja.

`KN-BM-001` ne propisuje univerzalne vrste izvještaja.

Izvještaj nije isto što i Dokument iz Poglavlja 14.

Izvještaj je poslovni zapis ili sposobnost koji može sadržati ili zahtijevati Dokumente.

## 17.11. Istorijska nepromjenljivost

Poslovno relevantni zapisi nastali kroz odlučivanje, ugovaranje, Realizaciju i Izvještavanje moraju ostati istorijski razumljivi prema poslovnim pravilima koja su važila kada su nastali.

Posebno:

* kasnija verzija profila ne smije retroaktivno promijeniti značenje postojeće Odluke;
* Prijedlog odluke mora ostati razumljiv kao Prijedlog odluke;
* pojedinačni akt ili Rješenje mora ostati poveziv sa Odlukom i Prijavom iz kojih proizlazi;
* kasnija izmjena pravila ugovaranja ne mijenja poslovno značenje već nastalog Ugovora;
* podaci o Realizaciji i Izvještavanju moraju ostati razumljivi prema pravilima konkretnog istorijskog sprovođenja;
* kasnije promjene Obrasca, Dokumenta, rokova ili drugih pravila ne smiju učiniti istorijske zapise poslovno nerazumljivim.

Istorijska nepromjenljivost ne znači da poslovni zapis nikada ne može biti zakonito promijenjen.

Konkretni profil može dozvoliti, na primjer, izmjenu Ugovora aneksom, korekciju Izvještaja ili drugu poslovno dozvoljenu naknadnu radnju.

U tom slučaju originalno stanje i kasnija poslovno dozvoljena promjena moraju ostati istorijski razumljivi.

Ovo poglavlje ne određuje tehnički snapshot, version ili audit mehanizam.

## 17.12. Funkcionalna i tehnička realizacija

Poslovni model definiše poslovno značenje Odluke, Prijedloga odluke, pojedinačnog akta, Ugovora, Realizacije i Izvještavanja, kao i njihove međusobne poslovne odnose kada ih konkretni poslovni profil koristi.

Funkcionalna specifikacija definiše funkcionalno ponašanje sistema kojim se poslovna pravila ostvaruju.

Tehnička specifikacija definiše tehničku realizaciju.

Ovo poglavlje ne određuje:

* tehničke statuse;
* DB modele, tabele ili kolone;
* način tehničkog generisanja Odluke, Prijedloga odluke, Rješenja ili Ugovora;
* PDF ili DOCX generisanje;
* elektronsko potpisivanje;
* tehnički workflow odobravanja;
* UI akcije ili dugmad;
* policy ili middleware;
* tehničko računanje ili prenos rezultata rangiranja;
* storage mehanizme;
* snapshot, versioning ili audit implementaciju;
* tehnički način čuvanja aneksa ili korekcija.

Postojeća implementacija nije izvor poslovnog pravila.

Funkcionalno ponašanje pripada odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

Ovo poglavlje ne razrađuje Arhiviranje.

---

# 18. Opciona sposobnost: arhiviranje

Status poglavlja: USVOJENO

Ovo poglavlje definiše Arhiviranje kao opcionu poslovnu sposobnost na zajedničkom apstraktnom nivou.

Konkretni poslovni profil određuje da li konkretni tip konkursa koristi Arhiviranje i pod kojim poslovnim uslovima.

Primjenjuju se `BM-KN-004` i `BM-KN-005`.

## 18.1. Poslovno značenje Arhiviranja

Arhiviranje je opciona poslovna sposobnost kojom se završeno ili na drugi način istorijski relevantno sprovođenje konkursa zadržava kao istorijski poslovni zapis, bez nastavka redovnog aktivnog sprovođenja.

Arhiviranje nije obavezno za svaki tip konkursa.

Arhiviranje nije brisanje.

Arhiviranje ne mijenja istorijske podatke i ne mijenja poslovno značenje onoga što se ranije dogodilo.

Konkretni poslovni profil određuje šta se može arhivirati i pod kojim poslovnim uslovima.

`KN-BM-001` ne propisuje da se obavezno arhivira svaki Tip konkursa, godišnja instanca, Poziv, Prijava, Odluka ili drugi pojedinačni poslovni element.

Neaktivan Tip konkursa nije isto što i arhivirana godišnja instanca.

Prestanak budućeg korišćenja Tipa konkursa ne znači da su prethodna sprovođenja time arhivirana.

Arhiviranje konkretnog sprovođenja ne znači da je Tip konkursa postao neaktivan.

## 18.2. Arhiviranje i brisanje

Arhiviranje ne predstavlja brisanje poslovnog zapisa niti uklanjanje njegove istorije.

Arhiviranjem:

* poslovni zapis se ne briše;
* istorijski relevantne veze moraju ostati razumljive;
* ne prekida se poslovna sljedivost;
* ne mijenjaju se retroaktivno vrijednosti koje su važile tokom sprovođenja;
* novija verzija poslovnog profila ne mijenja istorijsko značenje arhiviranog zapisa.

Kada postoje, mora ostati moguća istorijska sljedivost kroz relevantne veze:

Tip konkursa → Poslovni profil → verzija profila → godišnja instanca → Poziv → Prijava → rezultati obrade ili odlučivanja → kasniji poslovni zapisi.

`KN-BM-001` ne uvodi univerzalno pravilo da se ništa nikada ne smije obrisati.

Eventualno brisanje, zakonsko uklanjanje ili pravila čuvanja podataka predstavljaju zasebna pitanja.

Precizna zajednička granica je: Arhiviranje samo po sebi nije brisanje.

## 18.3. Predmet Arhiviranja

Konkretni poslovni profil određuje koji poslovni element može biti predmet arhiviranja i pod kojim uslovima.

Profil određuje:

* šta se arhivira;
* kada su ispunjeni uslovi za arhiviranje;
* poslovne posljedice arhiviranja.

`KN-BM-001` ne propisuje univerzalnu listu elemenata koji moraju imati sopstveno arhiviranje.

Ovo poglavlje ne uvodi automatsku poslovnu kaskadu po kojoj arhiviranje jednog elementa automatski arhivira sve povezane elemente.

Arhiviranje jednog poslovnog elementa ne znači automatski da su svi povezani elementi dobili isti Status ili poslovno stanje.

Povezani istorijski zapisi ipak moraju ostati međusobno razumljivi i sljedivi.

## 18.4. Poslovni uslovi Arhiviranja

Poslovni element može biti arhiviran samo kada su ispunjeni uslovi arhiviranja koje određuje odgovarajući poslovni profil.

`KN-BM-001` ne propisuje univerzalni trenutak arhiviranja.

Ovo poglavlje ne pretpostavlja da arhiviranje nastupa zatvaranjem Poziva, donošenjem Odluke, završetkom Ugovora, završetkom Realizacije ili završetkom Izvještavanja.

Ugovor, Realizacija i Izvještavanje nijesu ni obavezne sposobnosti svakog profila.

Ako ih profil koristi, može njihovo završavanje odrediti kao preduslov arhiviranja.

Ako ih profil ne koristi, njihovo nepostojanje ne može samo po sebi blokirati arhiviranje.

Arhiviranje ne smije služiti kao prečica za prekid aktivnog poslovnog procesa.

Ako je poslovni element još aktivan prema pravilima konkretnog profila, ne arhivira se samo zato što ga neko želi ukloniti iz aktivnog prikaza.

Želja da element ne bude na aktivnoj listi nije isto što i poslovna spremnost za arhiviranje.

## 18.5. Arhiviranje ne mijenja poslovni rezultat

Arhiviranje ne predstavlja novi poslovni rezultat i ne mijenja rezultate koji su prethodno nastali tokom sprovođenja konkursa.

Arhiviranje:

* ne mijenja rezultat Prijave;
* ne mijenja Odluku;
* ne mijenja dodijeljena prava, obaveze ili iznose;
* ne mijenja sadržaj Ugovora;
* ne mijenja rezultate Realizacije;
* ne mijenja rezultate Izvještavanja;
* ne poništava prethodne poslovne radnje;
* samo po sebi ne predstavlja odobravanje, odbijanje ili završavanje nezavršenog poslovnog postupka.

Arhiviranje kao poslovna sposobnost ne znači da `KN-BM-001` uvodi univerzalni poslovni Status `Arhiviran`.

Konkretni profil može koristiti takav Status ako ga njegovo poslovno pravilo zahtijeva.

Arhiviranje je poslovna sposobnost.

Status `Arhiviran` nije obavezni univerzalni Status svih konkursa.

## 18.6. Naknadne poslovne radnje

Arhiviranje označava da poslovni element više nije u redovnom aktivnom sprovođenju, ali konkretni poslovni profil određuje da li su i koje naknadne poslovne radnje nad arhiviranim zapisom dozvoljene.

Arhivirani zapis ne vraća se automatski u aktivno sprovođenje.

Arhiviranje samo po sebi ne daje pravo na ponovno otvaranje postupka.

`KN-BM-001` ne propisuje univerzalnu zabranu svake naknadne poslovne radnje.

Ako postoji poslovni osnov, konkretni profil može predvidjeti naknadnu radnju nad istorijskim zapisom.

Takva radnja:

* mora biti eksplicitno poslovno dozvoljena;
* mora ostati istorijski sljediva;
* ne smije prikriveno prepisati ono što se istorijski dogodilo.

Ovo poglavlje ne proglašava univerzalno dozvoljenim aneks Ugovora, poslovno dozvoljenu korekciju Izvještaja, naknadni akt niti drugu naknadnu radnju. Takve radnje mogu postojati samo kada ih konkretni poslovni profil predviđa.

Ponovno aktiviranje arhiviranog elementa može postojati samo ako ga konkretni poslovni profil eksplicitno predviđa.

## 18.7. Nadležnost za Arhiviranje

Konkretni poslovni profil određuje koji poslovni akter može izvršiti arhiviranje određenog poslovnog elementa kada arhiviranje zahtijeva poslovnu radnju aktera.

Ovo poglavlje ne dodjeljuje automatsko pravo arhiviranja Administratoru konkursa, Administratoru platforme niti Komisiji.

Administrator platforme ne dobija poslovno ovlašćenje za arhiviranje samo na osnovu tehničkih privilegija.

Profil određuje nadležnog aktera kada je poslovna radnja aktera potrebna.

Ovo poglavlje ne pretpostavlja da arhiviranje mora uvijek biti ručna radnja.

Poslovni model određuje uslove i eventualnu poslovnu nadležnost.

Da li je tehnička realizacija automatska ili ručna pripada FS ili TS sloju.

## 18.8. Istorijska sljedivost

Arhiviranje mora očuvati istorijsku razumljivost poslovnog elementa i njegovih poslovno relevantnih veza prema pravilima koja su važila tokom njegovog sprovođenja.

Kada su relevantni, nakon arhiviranja moraju ostati istorijski razumljivi:

* Tip konkursa;
* verzija poslovnog profila;
* godišnja instanca;
* Poziv;
* Prijave;
* rezultati obrade;
* rezultati ocjenjivanja;
* bodovanje;
* rangiranje;
* Odluke;
* pojedinačni akti;
* Ugovori;
* Realizacija;
* Izvještavanje.

Arhiviranje ne prekida vezu sa verzijom poslovnog profila samo zato što je kasnije nastala nova verzija.

Arhiviranje ne znači da poslovni model mora napraviti novi snapshot cijelog konkursa.

Poslovni model zahtijeva istorijsku razumljivost i sljedivost.

Tehnički način njenog očuvanja pripada FS ili TS sloju.

Primjenjuju se `BM-KN-003` i `BM-KN-011`.

## 18.9. Arhiviranje i dostupnost podataka

Arhiviranje samo po sebi ne određuje način prikaza, pretrage ili pristupa arhiviranom poslovnom zapisu.

Ovo poglavlje ne pretpostavlja da je arhivirani zapis nedostupan, niti da je dostupan svakome.

Konkretni poslovni profil određuje poslovna pravila dostupnosti kada su ona poslovno relevantna.

To može uključivati, kada profil tako odredi, istorijski pristup sopstvenim Prijavama, Odlukama, pojedinačnim aktima, Ugovorima, Izvještajima ili drugim relevantnim zapisima.

`KN-BM-001` to ne propisuje univerzalno svim profilima.

Javna dostupnost istorijskih podataka takođe se ne određuje samim arhiviranjem.

Način prikaza, poseban ekran, filteri, pretraga i slična UI pitanja nijesu predmet `KN-BM-001`.

Istorijska sljedivost ne znači da svaki akter ima pristup svakom istorijskom podatku.

## 18.10. Funkcionalna i tehnička realizacija

Poslovni model definiše poslovno značenje arhiviranja, uslove pod kojima je arhiviranje poslovno dozvoljeno, poslovne posljedice arhiviranja, eventualnu poslovnu nadležnost i zahtjev očuvanja istorijske sljedivosti.

Funkcionalna specifikacija definiše funkcionalno ponašanje sistema.

Tehnička specifikacija definiše tehničku realizaciju.

Ovo poglavlje ne određuje:

* tehnički Status ili ENUM;
* modele, tabele ili kolone;
* UI akcije, dugmad, stranice, kartice, filtere ili pretragu;
* policy, permission ili middleware;
* cron ili job;
* soft-delete;
* tehničku kaskadu;
* snapshot ili audit implementaciju;
* fizički način čuvanja arhiviranih podataka.

Arhiviranje nije soft delete.

Arhiviranje kao poslovna sposobnost nije obavezni univerzalni Status `Arhiviran`.

Postojeća implementacija nije izvor poslovnog pravila.

Funkcionalno ponašanje pripada odgovarajućem FS sloju.

Tehnička realizacija pripada `KN-TS-001`.

---

**Kraj dokumenta KN-BM-001 v1.0.0**
