# Digital Kotor
# Zajednički poslovni model modula Konkursi
## Modul: Konkursi

**Oznaka dokumenta:** KN-BM-001
**Naziv:** Zajednički poslovni model modula Konkursi
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** U IZRADI
**Verzija:** 0.1.3
**Datum:** 2026-08-21

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

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Dokument ima status `U IZRADI` i nije formalno usvojen. Dok dokument ima status `U IZRADI`, redakcijske korekcije koje ne mijenjaju značenje mogu se unositi u okviru iste radne verzije. Kada se odobrenim dokumentacionim korakom doda ili promijeni sadržaj, obuhvat ili usvojeno pravilo dokumenta, povećava se radna verzija i dodaje novi red u istoriju verzija. Više povezanih izmjena jednog odobrenog dokumentacionog koraka mogu se evidentirati kao jedna radna verzija. Postojeći redovi istorije verzija ne mijenjaju se. PATCH oznaka se ne izdaje dok dokument nije formalno usvojen.

Nakon formalnog usvajanja, kontrolisane izmjene označavaju se prema `KN-PATCH-BM-{NNN}` i evidentiraju se u `KN-RG-001` tek pri prvoj stvarnoj upotrebi.

---

## Svrha dokumenta

Dokument je zajednički poslovni model modula Konkursi. Definiše apstraktne pojmove, odnose i mehanizam profila. Nije poslovni profil nijednog tipa konkursa, nije Functional Specification i nije Technical Specification.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | U IZRADI |
| 2. Svrha i granice zajedničkog modela | U IZRADI |
| 3. Opseg | U IZRADI |
| 4. Granice prema BM profilima, FS i TS | U IZRADI |
| 5. Poslovni principi | U IZRADI |
| 6. Tip konkursa | U IZRADI |
| 7. Poslovni profil | U IZRADI |
| 8. Verzionisanje profila i istorijska primjena | U IZRADI |
| 9. Godišnja instanca | U IZRADI |
| 10. Poziv i ponovljeni poziv | U IZRADI |
| 11. Apstraktni akteri i uloge | U IZRADI |
| 12. Apstraktne kategorije | U IZRADI |
| 13. Konfigurabilne faze i statusi | U IZRADI |
| 14. Obrasci i dokumenti kao pojmovi | U IZRADI |
| 15. Opcione sposobnosti: ocjenjivanje i rangiranje | U IZRADI |
| 16. Opciona sposobnost: prijava | U IZRADI |
| 17. Opcioni pojmovi: odluka, ugovor, realizacija i izvještavanje | U IZRADI |
| 18. Opciona sposobnost: arhiviranje | U IZRADI |
| 19. Rječnik zajedničkih pojmova | U IZRADI |
| 20. Otvorena pitanja zajedničkog modela | U IZRADI |
| 21. Registar usvojenih odluka | U IZRADI |

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
7. Poslovni profil
8. Verzionisanje profila i istorijska primjena
9. Godišnja instanca
10. Poziv i ponovljeni poziv
11. Apstraktni akteri i uloge
12. Apstraktne kategorije
13. Konfigurabilne faze i statusi
14. Obrasci i dokumenti kao pojmovi
15. Opcione sposobnosti: ocjenjivanje i rangiranje
16. Opciona sposobnost: prijava
17. Opcioni pojmovi: odluka, ugovor, realizacija i izvještavanje
18. Opciona sposobnost: arhiviranje
19. Rječnik zajedničkih pojmova
20. Otvorena pitanja zajedničkog modela
21. Registar usvojenih odluka

---

# 1. Uvod

Status poglavlja: U IZRADI

`KN-BM-001` je zajednički poslovni model modula Konkursi na platformi Digital Kotor. Dokument uspostavlja zajednički okvir za dokumentovanje različitih tipova konkursa, uz obavezno razdvajanje zajedničkog modela od poslovnih profila pojedinačnih tipova konkursa.

Dokument se primjenjuje na sve postojeće i buduće tipove konkursa koji se vode kroz modul Konkursi. Ne pretpostavlja da različiti tipovi konkursa imaju iste aktere, kategorije, potrebne dokumente, obrasce, faze, statuse, rokove ili kriterijume.

`KN-BM-001` nalazi se poslije registra `KN-RG-001` u dokumentacionom lancu i predstavlja osnovu za izradu zasebnih poslovnih profila, funkcionalnih specifikacija i zajedničke tehničke specifikacije modula.

Konkretna pravila konkursa za podršku preduzetništvu mladih ne pripadaju ovom dokumentu. Ona će biti dokumentovana u poslovnom profilu `KN-BM-002`.

---

# 2. Svrha i granice zajedničkog modela

Status poglavlja: U IZRADI

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

Status poglavlja: U IZRADI

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

Status poglavlja: U IZRADI

---

# 5. Poslovni principi

Status poglavlja: U IZRADI

---

# 6. Tip konkursa

Status poglavlja: U IZRADI

---

# 7. Poslovni profil

Status poglavlja: U IZRADI

---

# 8. Verzionisanje profila i istorijska primjena

Status poglavlja: U IZRADI

---

# 9. Godišnja instanca

Status poglavlja: U IZRADI

---

# 10. Poziv i ponovljeni poziv

Status poglavlja: U IZRADI

---

# 11. Apstraktni akteri i uloge

Status poglavlja: U IZRADI

---

# 12. Apstraktne kategorije

Status poglavlja: U IZRADI

---

# 13. Konfigurabilne faze i statusi

Status poglavlja: U IZRADI

---

# 14. Obrasci i dokumenti kao pojmovi

Status poglavlja: U IZRADI

---

# 15. Opcione sposobnosti: ocjenjivanje i rangiranje

Status poglavlja: U IZRADI

---

# 16. Opciona sposobnost: prijava

Status poglavlja: U IZRADI

---

# 17. Opcioni pojmovi: odluka, ugovor, realizacija i izvještavanje

Status poglavlja: U IZRADI

---

# 18. Opciona sposobnost: arhiviranje

Status poglavlja: U IZRADI

---

# 19. Rječnik zajedničkih pojmova

Status poglavlja: U IZRADI

---

# 20. Otvorena pitanja zajedničkog modela

Status poglavlja: U IZRADI

---

# 21. Registar usvojenih odluka

Status poglavlja: U IZRADI

---

**Kraj dokumenta KN-BM-001 v0.1.3**
