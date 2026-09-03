# Digital Kotor
# Pravni okvir Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-PRO-001
**Naziv:** Pravni okvir Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** NACRT
**Verzija:** 0.1.4
**Datum:** 2026-09-03

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Kreirana početna struktura KN-PRO-001. Identifikovan pravni izvor po naslovu. Pravni sadržaj čeka analizu zvaničnog teksta Odluke. Bez izmišljenih članova, kriterijuma, iznosa ili rokova. |
| 0.1.1 | 2026-08-18 | Pravna ekstrakcija Odluke: popunjen registar članova 1–32, izdvojeni akteri/condition registry/dokumentacija-obrasci/rokovi-finansije-kriterijumi-bodovanje-odluke/ugovor-praćenje-izvještavanje, uz `SOURCE ANOMALIES / LEGAL CLARIFICATIONS REQUIRED`. BM/FS/TS nijesu mijenjani. |
| 0.1.2 | 2026-08-18 | Validirana postojeća djelimična ekstrakcija prema oba izvora (osnovni tekst + PDF prilozi). Potvrđeni i obrađeni obrasci P1a/P1b/P2/P3/P4/P4a; uklonjene zastarjele `MISSING SOURCE ATTACHMENT` oznake za dostupne priloge; P3 skala 1–5 evidentirana kao eksplicitno pravilo iz obrasca; anomaly registar dopunjen razlikama član-tekst vs obrazac. Status ostaje NACRT. BM/FS/TS nijesu mijenjani. |
| 0.1.3 | 2026-08-18 | PO review Q1–Q6: donesene PO odluke evidentirane u `PO DECISION REGISTER`; source anomalies zadržane radi traceability-ja; uklonjeni BM blocker statusi za Q1–Q6. Metadata header usklađen sa changelogom (0.1.2→0.1.3). Status ostaje NACRT. BM/FS/TS nijesu mijenjani. |
| 0.1.4 | 2026-09-03 | Katalog source anomalies (§4.20): dodata stavka **4.20.L** — čl.13 referenca „Ugovor iz člana 27“ dok je ugovor u čl.26 (čl.27 = praćenje). Izvor se ne prepravlja; bez nove PO odluke; bez izmjene poslovnog značenja. BM/FS/TS/RG/kod nijesu mijenjani. Status ostaje NACRT. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument definiše pravni okvir cjeline Konkursi na platformi Digital Kotor.

Predstavlja osnov za usklađenost poslovnog modela, funkcionalnosti i tehničke realizacije sa važećim propisima Crne Gore i Opštine Kotor.

U verziji 0.1.0 dokument **samo** uspostavlja strukturu i evidentira identifikovani pravni izvor. **Ne** sadrži pravnu analizu, tumačenje članova niti izvedena poslovna pravila.

Tip dokumenta je **PRO** (DK-DS-001 §4). Ovo **nije** Product Owner odluka (`PO-*`) i **nije** `EP-PO-001`.

---

# Status razvoja

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | NACRT — identifikovan izvor; bez pravne analize |
| 2. Identifikovani pravni izvor | NACRT — naveden naslov Odluke |
| 3. Propis kao izvor istine | NACRT — metodološko pravilo; bez sadržaja Odluke |
| 4. Registar pravnih odredbi | LEGAL EXTRACTION COMPLETE (NACRT) |
| 5. Izvedena pravila prema poslovnom modelu | PENDING LEGAL SOURCE ANALYSIS |
| 6. Veza sa ostalom dokumentacijom | NACRT — hijerarhija dokumenata |
| 7. Završne odredbe | LEGAL EXTRACTION COMPLETE (NACRT) |

---

# Pravila upravljanja dokumentom

1. Pravni okvir pripada cjelini Konkursi (KN-PRO-001).

2. Cursor ne smije samostalno unositi, pretpostavljati ili dopunjavati pravne podatke.

3. Ne potvrđeni pravni podaci ne smiju se unositi. Dok zvanični tekst Odluke nije analiziran, sadržajne sekcije ostaju **PENDING LEGAL SOURCE ANALYSIS**.

4. Izmjene se evidentiraju kroz novi red u istoriji verzija / PATCH (`KN-PATCH-PRO-*` kada bude izdat; vidi KN-RG-001).

5. Postojeći tok ženskog preduzetništva nije automatski izvor pravnih pravila ovog dokumenta.

---

## Sadržaj

1. Uvod
2. Identifikovani pravni izvor
3. Propis kao izvor istine
4. Registar pravnih odredbi
5. Izvedena pravila prema poslovnom modelu
6. Veza sa ostalom dokumentacijom
7. Završne odredbe

---

# 1. Uvod

Cjelina **Konkursi** na platformi Digital Kotor dokumentuje se u namespace-u `KN`.

Ovaj dokument je pravni sloj tog paketa. Poslovna pravila se ne izmišljaju ovdje; izvode se kasnije iz zvaničnog teksta identifikovanog izvora i eksplicitnih PO odluka, pa se prenose u KN-BM-001.

---

# 2. Identifikovani pravni izvor

**Status:** primarni pravni izvor ekstraktovan i analiziran u ovom koraku (NACRT), uz evidentiranje anomalija/otvorenih pitanja.

Identifikovani pravni osnov (primarni izvor): tekst Odluke kako je dostavljen u PDF-u `DigitalKotor-LegalSources`.

Puni naziv Odluke:

**Odluka o podršci preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija**

Donosilac:

**Skupština Opštine Kotor**

Donesena:

**17.06.2026.**

Broj akta:

**11-016/26-12679**

Službeno glasilo:

**“Službeni list Crne Gore – Opštinski propisi”**

Broj glasila:

**027/26**

Datum objavljivanja:

**22.06.2026.**

Pravni osnov naveden u preambuli:

Odluka se donosi “na osnovu”:

* člana 38 stav 1 tačka 2, a u vezi sa članom 27 stav 1 tačka 6 Zakona o lokalnoj samoupravi (“Službeni list Crne Gore”, 2/18, 34/19, 38/20, 50/22, 84/22, 81/25 i 98/25);
* člana 36 stav 1 tačka 2 i člana 15 stav 1 tačka 6 Statuta Opštine Kotor (“Službeni list Crne Gore - Opštinski propisi”, broj 37/19).

Datum stupanja na snagu (prema članu 32):

* “Ova Odluka stupa na snagu osmog dana od dana objavljivanja u ‘Službenom listu Crne Gore - Opštinski propisi’.”
* **DERIVED DATE:** 30.06.2026 (računanje “osmog dana” kao 8. dan nakon dana objavljivanja 22.06.2026; ako vaš pravni standard računa “osmi dan” inkluzivno/isključivo od dana objavljivanja, potreban je PO/LEGAL proračun).

Članovi:

* U tekstu Odluke evidentirano je **32 člana (čl. 1–32)**.

---

# 3. Propis kao izvor istine

**Status:** metodološko pravilo paketa; nije pravna analiza.

Zvaničan izvor poslovnih pravila konkursa, kada bude analiziran, je identifikovana Odluka (i, prema `docs/tehnicka-dokumentacija/project-operations.md`, katalog propisa / Službeni list). Pravila se ne izmišljaju i ne preuzimaju nekritički iz postojeće implementacije.

Dok analiza nije završena, nijedna odredba se ne smatra unesenom u KN kanonski paket.

---

# 4. Registar pravnih odredbi

**Status:** LEGAL EXTRACTION COMPLETE (NACRT)

Za ovu funkcionalnu cjelinu u ovom koraku evidentirani su pravni tekst, rokovi i procesne odredbe za **čl. 1–32** (primarni izvor: dostavljeni PDF).

# 4. Registar pravnih odredbi (Član 1–32)

## Metapodaci registracije (primarno)

* Identifikacija akta: `11-016/26-12679` (donesena 17.06.2026; objavljena 22.06.2026; glasilo “Službeni list Crne Gore – Opštinski propisi”, br. 027/26)
* Donosilac: Skupština Opštine Kotor
* Ključni digitalni kanali (preko teksta): `digital.kotor.me` / vebsajt opštine i javni emiter
* Evidencija: registar je pravni sadržaj iz teksta Odluke; nije tehnički dizajn.

## 4.1 Registar članova (1–32)

> Napomena o formatu: za svaki član evidentirani su najmanje tražena polja: `Chapter`, `Subject`, `Actor(s)`, `Explicit legal rules`, `Deadlines`, `Documents/forms`, `Digital Kotor relevance`, `Dependencies`, `Open issue`.

### Član 1
* **Chapter:** I. OPŠTE ODREDBE
* **Subject:** predmet uređivanja
* **Actor(s):** preduzetnici/MMSP; Opština Kotor (implementacija kroz subvencije)
* **Explicit legal rules:**
  * Odlukom se uređuju uslovi, način i postupak dodjele subvencija kao instrumenta podrške preduzetnicima i mikro, malim i srednjim preduzećima (MMSP).
* **Deadlines:** nema
* **Documents/forms:** nema
* **Digital Kotor relevance:** indirektno (postupak dodjele kroz javni poziv, kasnije definisan)
* **Dependencies:** član 2, član 5
* **Open issue:** nema

### Član 2
* **Chapter:** I. OPŠTE ODREDBE
* **Subject:** izvor finansijskih sredstava
* **Actor(s):** Opština Kotor; Javni poziv (kao instrument raspodjele)
* **Explicit legal rules:**
  * sredstva obezbjeđuje budžet Opštine Kotor za tekuću godinu, budžetska pozicija planirana za razvoj preduzetništva;
  * ukupan iznos sredstava koji se raspodjeljuje definiše Javnim pozivom.
* **Deadlines:** nema
* **Documents/forms:** nema
* **Digital Kotor relevance:** kroz Javnic poziv
* **Dependencies:** član 5–6
* **Open issue:** nema

### Član 3
* **Chapter:** I. OPŠTE ODREDBE
* **Subject:** rodno osjetljiv jezik
* **Actor(s):** svi subjekti u tekstu Odluke
* **Explicit legal rules:**
  * pojmovi u muškom gramatičkom rodu obuhvataju i muški i ženski rod.
* **Deadlines:** nema
* **Documents/forms:** nema
* **Digital Kotor relevance:** nema
* **Dependencies:** cijeli tekst (terminologija)
* **Open issue:** nema

### Član 4
* **Chapter:** II. KORISNICI SUBVENCIJA
* **Subject:** osnovni uslovi za pravo na subvencije
* **Actor(s):** preduzetnici; MMSP
* **Explicit legal rules:**
  * pravo na subvencije imaju preduzetnici i MMSP ako:
    * imaju prebivalište/sjedište ili registrovanu djelatnost na teritoriji opštine Kotor;
    * se protiv istih ne vodi krivični postupak pred Osnovnim sudom;
    * uredno izmiruju poreske obaveze;
    * posluju u skladu sa važećim propisima.
* **Deadlines:** nema
* **Documents/forms:** posredno (dokazna dokumentacija je u članu 14)
* **Digital Kotor relevance:** provjera uslova kroz prijavu na Javni poziv
* **Dependencies:** član 14 (dokumenti), član 18/20 (provjera i evaluacija)
* **Open issue:** nema

### Član 5
* **Chapter:** III. JAVNI POZIV
* **Subject:** način dodjele putem Javnog poziva; učestalost i sadržaj
* **Actor(s):** Opština Kotor; Komisija; preduzetnici/MMSP
* **Explicit legal rules:**
  * subvencije se dodjeljuju putem Javnog poziva;
  * Javni poziv se može raspisati jedan put godišnje;
  * Javni poziv se raspisuje u trećem kvartalu tekuće godine;
  * Javni poziv naročito sadrži: ukupan iznos sredstava, najviši iznos, vrste subvencija, uslove za podnošenje prijave, podatke o dokumentaciji, kriterijume evaluacije plana ulaganja, rok i način podnošenja, informacije o informativnim sastancima i druge bitne podatke.
* **Deadlines:** treći kvartal; ostalo definisano Javnim pozivom
* **Documents/forms:** prijavni obrasci i plan ulaganja (detaljno u čl. 14–17)
* **Digital Kotor relevance:** posredno (način podnošenja kroz digitalni servis definisan u članu 14)
* **Dependencies:** član 6 (objava i trajanje); član 14 (podnošenje)
* **Open issue:** nema

### Član 6
* **Chapter:** III. JAVNI POZIV
* **Subject:** kanali objave Javnog poziva i trajanje
* **Actor(s):** Opština Kotor; šira javnost; preduzetnici/MMSP
* **Explicit legal rules:**
  * Javni poziv se objavljuje u jednom dnevnom listu, na vebsajtu Opštine Kotor, na digitalnom servisu opštine i lokalnom radio emiteru Radio Kotor-u;
  * Javni poziv je otvoren 20 dana od dana objavljivanja.
* **Deadlines:** 20 dana
* **Documents/forms:** nema
* **Digital Kotor relevance:** vebsajt i `www.digital.kotor.me` (digitalni servis)
* **Dependencies:** član 14 (rok prijave i elektronsko podnošenje)
* **Open issue:** nema

### Član 7
* **Chapter:** IV. KOMISIJA ZA DODJELU SUBVENCIJA
* **Subject:** nadležnosti, sastav i rad Komisije
* **Actor(s):** Komisija; sekretar Sekretarijata; predsjednik Komisije; članovi Komisije; sekretar Komisije; preduzetnici/MMSP (kao učesnici intervjua)
* **Explicit legal rules:**
  * raspodjelu vrši Komisija za dodjelu subvencija, koju imenuje sekretar Sekretarijata Rješenjem;
  * sekretar Sekretarijata može imenovati zamjenskog člana u slučaju odsustva;
  * Komisija ima 3 člana, jedan je predsjednik;
  * nadležnosti: raspisivanje javnog poziva, pregled/ocjena validnosti dokumentacije i plana ulaganja, provođenje intervjua, evaluacija planova prema kriterijumima, formiranje konačne rang liste, priprema predloga Odluke o subvencionisanju za tekuću godinu;
  * članovi: 2 predstavnika Opštine Kotor (predsjednik + sekretar Komisije) i 1 predstavnik poslovnog/strukovnog udruženja ili biznisa ili akademske zajednice;
  * član Komisije (ili društvo čiji je predstavnik član) nema pravo učešća u Javnom pozivu;
  * kvorum: prisustvo većine članova; sjednice mogu biti elektronske (zoom/teams; ili elektronsko uključenje preko viber/whatsapp);
  * ako nema kvoruma sjednica se odlaže;
  * za sprovođenje intervjua i donošenje punovažnih odluka obavezno prisustvo svih članova;
  * rad Komisije je javan;
  * Komisija radi u skladu sa Poslovnikom o radu Komisije koji donosi Sekretarijat;
  * članovi imaju pravo na naknadu;
  * svi članovi potpisuju Izjavu o tajnosti podataka i Izjavu o sprečavanju sukoba interesa;
  * mandat Komisije: godinu dana.
* **Deadlines:** mandat 1 godina
* **Documents/forms:** Poslovnik; izjave o tajnosti i sukobu interesa
* **Digital Kotor relevance:** elektronske sjednice; digitalni servis za podnošenje prijava i bodovanje (kasnije u čl. 14, 21, 23)
* **Dependencies:** član 18 (list e za ocjenjivanje); član 19–22 (rangiranje/bodovanje); član 23 (generisanje odluke)
* **Open issue:** nema

### Član 8
* **Chapter:** V. PRESTANAK MANDATA ČLANOVA KOMISIJE
* **Subject:** prestanak mandata članovima Komisije
* **Actor(s):** član Komisije; sekretar Sekretarijata (primjenom rješenja)
* **Explicit legal rules:**
  * mandat prestaje istekom vremena, na lični zahtjev ili razrješenjem.
* **Deadlines:** nema
* **Documents/forms:** rješenja
* **Dependencies:** član 9–11
* **Open issue:** nema

### Član 9
* **Chapter:** V. PRESTANAK MANDATA ČLANOVA KOMISIJE
* **Subject:** razlozi razrješenja člana Komisije
* **Actor(s):** sekretar Sekretarijata; član Komisije
* **Explicit legal rules:**
  * sekretar razrješava člana ako: (1) netačni/popušteni podaci od značaja pri imenovanju; (2) ne obavlja funkciju >6 mjeseci; (3) nesavjesno/nestručno obavlja; (4) osuđen na bezuslovnu kaznu zatvora; (5) osuđen za krivično djelo koje ga čini nedostojnim.
* **Deadlines:** nema
* **Documents/forms:** rješenje o razrješenju
* **Dependencies:** član 10
* **Open issue:** nema

### Član 10
* **Chapter:** V. PRESTANAK MANDATA ČLANOVA KOMISIJE
* **Subject:** postupak razrješenja i pravo na izjašnjenje
* **Actor(s):** sekretar Sekretarijata; član Komisije koji se razrješava
* **Explicit legal rules:**
  * postupak sprovodi sekretar;
  * član ima pravo izjasniti se o razlozima;
  * sekretar donosi rješenje.
* **Deadlines:** nema
* **Documents/forms:** rješenje
* **Dependencies:** član 9
* **Open issue:** nema

### Član 11
* **Chapter:** V. PRESTANAK MANDATA ČLANOVA KOMISIJE
* **Subject:** imenovanje zamjene nakon prestanka mandata
* **Actor(s):** sekretar Sekretarijata; novi član Komisije
* **Explicit legal rules:**
  * u slučaju prestanka prije isteka mandata, sekretar je dužan u roku 15 dana od prestanka imenovati novog člana;
  * mandat novoimenovanog člana traje do isteka mandata Komisije;
  * razriješeni član ne može ponovo biti imenovan za člana Komisije.
* **Deadlines:** 15 dana
* **Dependencies:** član 8–10
* **Open issue:** nema

### Član 12
* **Chapter:** VI. VRSTE SUBVENCIJA
* **Subject:** vrste subvencija (de minimis)
* **Actor(s):** preduzetnici; MMSP
* **Explicit legal rules:**
  * subvencije se dodjeljuju kao državna pomoć male vrijednosti (de minimis);
  * mogu aplicirati za:
    1) subvencije za razvoj autentičnih lokalnih proizvoda/usluga;
    2) subvencije za razvoj inovativnih proizvoda/usluga;
    3) subvencije za digitalizaciju.
* **Deadlines:** nema
* **Documents/forms:** vrste za prijavne obrasce i plan (P1a/P1b/P2)
* **Digital Kotor relevance:** kroz digitalno podnošenje
* **Dependencies:** član 14–17 (dokumentacija i plan ulaganja)
* **Open issue:** nema

### Član 13
* **Chapter:** VI. VRSTE SUBVENCIJA
* **Subject:** prihvatljivi i neprihvatljivi troškovi
* **Actor(s):** korisnik subvencije; Komisija (validacija)
* **Explicit legal rules:**
  * prihvatljivi troškovi ulaganja:
    * materijalna imovina (npr. mašine/alat/ICT oprema) koja se ne smije otuđiti minimalno 1 godinu od dana nabavke;
    * nematerijalna imovina (softver, baza podataka, franšiza i sl.) ako je neophodna;
    * nabavka sirovina/repro materijala;
    * bruto plate novozaposlenih na ugovor od minimalno 12 mjeseci (do 6 mjeseci navedeno u tekstu);
    * ulaganje u marketing i oglašavanje (detaljna lista: logo/branding, website izrada ili unapređenje i održavanje do 12 mjeseci; zakup domena/hosting do 12 mjeseci; štampanje, oglašavanje do 12 mjeseci, digitalni sadržaj, organizovanje promotivnih događaja, oprema, pakovanje/ambalaža, učešće na sajmovima/izložbama i sl.);
    * uvođenje sistema automatizacije online prodaje i e-commerce;
    * troškovi sertifikacije i uvođenja standarda i sl.
  * neprihvatljivi troškovi:
    * aktivnosti u nadležnosti/odgovornosti Vlade (npr. formalno obrazovanje, formalna zdravstvena zaštita);
    * kupovina/raspodjela humanitarne pomoći;
    * jednokratna izrada/štampanje knjiga/brošura/biltena/časopisa, ako publikacije nisu dio šireg programa/kontinuiranih aktivnosti;
    * nezakonite/štetne aktivnosti (npr. igre na sreću, duvan, alkoholna pića; izuzev proizvodnje vina i voćnih rakija);
    * poljoprivreda/marikutura/ribarstvo tamo gdje su posebna sredstva iz budžeta Opštine ili Ministarstva;
    * carine i uvozni troškovi;
    * novčane kazne, troškovi parničnog postupka;
    * troškovi podnosioca nastali prije potpisivanja Ugovora iz člana 27;
    * gotovinska plaćanja, plaćanja u naturi i plaćanja putem robne razmjene;
    * plaćanja za sopstvene usluge;
    * nemoralne i nelegalne aktivnosti.
* **Deadlines:** otuđenje zabranjeno minimalno 1 godinu od nabavke
* **Documents/forms:** plan ulaganja (P2), dokazivanje u izvještaju (P4/P4a)
* **Digital Kotor relevance:** plan i dokumentacija elektronski
* **Dependencies:** član 17 (P2), član 28–29 (izvještaji)
* **Open issue:** kod “bruto plate novozaposlenih lica na ugovor od minimalno 12 mjeseci (do 6 mjeseci)” postoji unutrašnja napetost u samom tekstu; `SOURCE AMBIGUITY` — **PO Q5 RESOLVED** (vidi 4.20.F / 4.21); izvorni tekst se ne mijenja.

### Član 14
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** prijava i prateća dokumentacija; rok prijave; elektronsko podnošenje
* **Actor(s):** preduzetnici; društva (MMSP); Komisija
* **Explicit legal rules:**
  * prijava i prateća dokumentacija podnosi se u roku 20 dana od dana objavljivanja Javnog poziva;
  * prijava podnosi Komisiji elektronski preko digitalnog servisa `digital.kotor.me` (www.digital.kotor.me);
  * preduzetnici prilažu:
    1) prijavu P1a;
    2) popunjen P2;
    3) ovjerenu kopiju lične karte;
    4) rješenje o upisu u CRPS;
    5) rješenje o registraciji PJ Poreske uprave;
    6) PDV rješenje ili potvrdu da nije PDV obveznik;
    7) potvrdu o nevođenju krivičnog postupka pred Osnovnim sudom;
    8) uvjerenje lokalne uprave o urednom izmirivanju poreza (prirez, doprinosi, lokalne takse i naknade), ne starije od 30 dana;
    9) uvjerenje o urednom izmirivanju poreza na nepokretnost, ne starije od 30 dana;
    10) potvrdu Poreske uprave o urednom izmirivanju poreza i doprinosa (ne starije od 30 dana);
    11) IOPPD obrazac (posljednji mjesec uplate poreza/doprinosa) ili potvrdu da nema zaposlenih (ovjerenu od Poreske uprave);
    12) dokaz o broju poslovnog žiro računa;
    13) predračune za planiranu nabavku.
  * društva prilažu:
    1) prijavu P1b;
    2) P2;
    3) ovjerenu kopiju lične karte ovlašćenog lica;
    4) upis u CRPS;
    5) registraciju PJ Poreske uprave;
    6) PDV rješenje ili potvrdu o neobveznosti;
    7) važeći Statut;
    8) važeći karton deponovanih potpisa;
    9) komplet godišnjih računa (bilans stanja, bilans uspjeha, analitika kupaca i dobavljača) za prethodnu godinu (izuzetak: društva registrovana u tekućoj godini);
       *napomena u tekstu:* ako nema analitiku kupaca (posao sa fizičkim licima, registar kasa) ima obavezu dostaviti periodični izvještaj sa registra kase;
    10) potvrdu o nevođenju krivičnog postupka na ime društva, izvršnog direktora i osnivača;
    11) uvjerenje lokalne uprave o urednim poreskim obavezama (prirez, doprinos, lokalne takse i naknade) ne starije 30 dana;
    12) uvjerenje o porezu na nepokretnost ne starije 30 dana;
    13) potvrdu Poreske uprave o urednom izmirivanju poreza i doprinosa ne starije 30 dana;
    14) IOPPD obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene;
    15) predračune.
  * Obrazac P1a, P1b i P2 su sastavni dio Odluke.
* **Deadlines:** 20 dana (prijava); starost uvjerenja 30 dana
* **Documents/forms:** P1a, P1b, P2 + nabrojane isprave
* **Digital Kotor relevance:** obavezno elektronsko podnošenje preko digitalnog servisa
* **Dependencies:** član 18 (administrativna provjera); član 19–22 (evaluacija/bodovanje)
* **Open issue:** nema

### Član 15
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** de minimis izjava i dodatne zabrane/obaveze nakon prijave
* **Actor(s):** preduzetnici; društva; korisnik subvencije; Komisija (posredno)
* **Explicit legal rules:**
  * preduzetnici/društva kojima su odobrena sredstva dužni su dati pisanu izjavu ovjerenu kod notara, popunjavanjem obrasca pomoći male vrijednosti, da li su u prethodne tri godine bili korisnici de minimis državne pomoći;
  * preduzetnik/društvo koje nije dostavilo Izvještaj o realizaciji plana ulaganja (P4) sa finansijskim izvještajem (P4a) i pratećom dokumentacijom za prethodno finansiran plan, ne može učestvovati u raspodjeli sredstava u godini raspodjele;
  * preduzetnik/društvo koje je koristilo budžetska sredstva po Javnom konkursu za razvoj ženskog preduzetništva i/ili Javnom konkursu za razvoj preduzetništva mladih, a nije dostavilo izvještaje, ne može učestvovati u dodjeli subvencija za godinu u kojoj se sredstva dodjeljuju;
  * korisnici sredstava Ugovorom se obavezuju da neće ugasiti biznis najmanje 3 godine od dana potpisivanja;
  * obrazac P4 i P4a su sastavni dio Odluke.
* **Deadlines:** “prethodne tri godine” (kontekst de minimis), rok izvještavanja definisan Ugovorom; 3 godine zabrana gašenja
* **Documents/forms:** de minimis izjava (obrazac pomoći male vrijednosti); P4 i P4a
* **Digital Kotor relevance:** izvještaji se podnose (čl. 28)
* **Dependencies:** član 16 (prijava); član 28–29 (izvještavanje/povrat)
* **Open issue:** termin “Javni konkurs” (žensko preduzetništvo / preduzetništvo mladih) ostaje kao u izvoru; ne prevoditi/korigovati.

### Član 16
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** prijava na Javni poziv (elektronski); sadržaj P1a i P1b
* **Actor(s):** preduzetnik; DOO/društvo; podnosilac prijave; digitalni servis opštine
* **Explicit legal rules:**
  * prijava se vrši elektronski podnošenjem odgovarajućeg prijavnog obrasca i prateće dokumentacije preko digitalnog servisa opštine Kotor (digital.kotor.me);
  * P1a (Prijava preduzetnik) sadrži podatke:
    * vrstu subvencije,
    * ime i prezime,
    * JMBG/PIB,
    * adresu,
    * šifru djelatnosti,
    * broj zaposlenih,
    * broj žiro računa,
    * kontakt telefon,
    * e-mail,
    * website,
    * datum registracije u CRPS,
    * naznaku tačnosti podataka i potpis preduzetnika.
  * P1b (Prijava DOO) sadrži podatke:
    * vrstu subvencije,
    * naziv društva,
    * ime i prezime odgovornog lica,
    * PIB društva,
    * sjedište,
    * datum osnivanja,
    * šifra djelatnosti,
    * broj zaposlenih,
    * broj žiro računa,
    * kontakt telefon odgovornog lica,
    * e-mail odgovornog lica,
    * website,
    * naznaku tačnosti podataka i potpis odgovornog lica.
  * “Preteća dokumentacija iz člana 15 ove Odluke” sastavni je dio prijave i elektronski se učitava u PDF formatu (putem digitalnog servisa).
* **Deadlines:** nema
* **Documents/forms:** P1a, P1b; prateća dokumentacija (upućuje na čl.15)
* **Digital Kotor relevance:** elektronsko podnošenje i PDF upload
* **Dependencies:** član 14 (dokumenti); član 15 (akte/izjave i obaveze)
* **Open issue:** cross-reference čl.16 → čl.15 — **PO Q2 RESOLVED** (vidi 4.21); izvorni tekst se ne mijenja.

### Član 17
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** sadržaj plana ulaganja (P2)
* **Actor(s):** preduzetnik/društvo; Komisija; digitalni servis
* **Explicit legal rules:**
  * Obrazac P2 popunjava se elektronski putem digitalnog servisa (digital.kotor.me) i sadržaj je propisan Odlukom;
  * P2 sadržaj:
    * I. OSNOVNI PODACI: 1) osnovni podaci o preduzetniku/društvu; 2) vrsta subvencije;
    * II. PLAN ULAGANJA: 3) opis planiranog ulaganja; 4) ciljevi ulaganja; 5) planirane aktivnosti;
    * III. FINANSIJSKI OKVIR ULAGANJA: 6) vrsta troška/opis/iznos; 7) ukupna vrijednost ulaganja; 8) sopstveno učešće; 9) očekivani iznos subvencije;
    * IV. AUTENTIČNOST/INOVATIVNOST/DIGITALIZACIJA;
    * V. OČEKIVANI REZULTATI;
    * VI. UTICAJ NA LOKALNU EKONOMIJU;
    * VII. ODRŽIVOST I DUGOROČNI EFEKTI.
  * Propisani P2 je sastavni dio Odluke.
* **Deadlines:** nema
* **Documents/forms:** P2
* **Digital Kotor relevance:** elektronsko popunjavanje
* **Dependencies:** član 19–22 (evaluacija i rangiranje plana)
* **Open issue:** nema

### Član 18
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** administrativna provjera; prigovor (pravni tok)
* **Actor(s):** Komisija; sekretarijat; podnosioci prijava; Komisija
* **Explicit legal rules:**
  * komisija zakazuje prvu sjednicu u roku od najviše 7 dana od isteka roka za prijavu;
  * komisija na prvoj sjednici pregleda elektronski zaprimljene prijave;
  * nepotpune prijave se označavaju u listi za ocjenjivanje iz člana 21 i komisija ih ne razmatra dalje;
  * komisija putem registrovanog mail-a podnosica obavještava podnosica o mogućnosti podnošenja Prigovora putem digitalnog servisa u roku 3 dana od slanja obavještenja;
  * komisija donosi odluku o prihvatanju ili odbijanju prigovora u roku 7 dana od prijema prigovora;
  * drugu sjednicu i intervju za potpune prijave komisija zakazuje u roku 7 dana od održavanja prve sjednice.
* **Deadlines:** 7 dana (prva sjednica); 3 dana (prigovor); 7 dana (odluka o prigovoru); 7 dana (druga sjednica/intervju)
* **Documents/forms:** prigovor (putem digitalnog servisa; forme nisu navedene)
* **Digital Kotor relevance:** obavještavanje registrovanim mailom; prigovor preko digitalnog servisa
* **Dependencies:** član 21 (lista za ocjenjivanje)
* **Open issue:** nema

### Član 19
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** finansijski limiti; raspodjela po rang listi
* **Actor(s):** korisnik subvencije; komisija
* **Explicit legal rules:**
  * jedan preduzetnik/društvo može konkurisati za dvije vrste subvencija iz člana 13 ove Odluke, a može biti podržan samo za jednu;
  * maksimalni iznos subvencije po korisniku: ne može preći 20% planiranog budžeta za ovu namjenu i ne može biti veći od 50% potrebnih sredstava za realizaciju aktivnosti iz plana ulaganja;
  * izuzetak: za subvenciju iz člana 13 tačka 1 (subvencije za razvoj autentičnih lokalnih proizvoda i usluga) iznos subvencije do 80% potrebnih sredstava;
  * sredstva se raspodjeljuju u skladu sa konačnom rang listom do utroška sredstava.
* **Deadlines:** nema
* **Documents/forms:** plan ulaganja (P2); konačna rang lista (čl.22)
* **Digital Kotor relevance:** konačna rang lista generiše se (čl.22)
* **Dependencies:** član 13 (tačka 1) — cross-reference evidentirano kao anomaly
* **Open issue:** `SOURCE CROSS-REFERENCE INCONSISTENCY` (vrste subvencija su u članu 12; troškovi u članu 13) — **PO Q1 RESOLVED** (vidi 4.21); izvorni tekst se ne mijenja.

### Član 20
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** kriterijumi evaluacije (pozitivni i eliminatorni)
* **Actor(s):** Komisija; preduzetnici/društva
* **Explicit legal rules:**
  * Komisija vrši evaluaciju prema pozitivnim kriterijumima (10):
    1) kvalitet plana ulaganja (ciljevi, opis aktivnosti, period realizacije);
    2) relevantnost ulaganja za razvoj biznisa;
    3) finansijska opravdanost (realni troškovi i jasna finansijska konstrukcija);
    4) jačanje konkurentnosti;
    5) lokalna autentičnost proizvoda/usluga;
    6) inovativnost (novina ili značajno unapređenje; nova tehnologija i sl.);
    7) doprinos digitalizaciji poslovanja (digitalni alati, online prodaja/promocija, sistemi za upravljanje);
    8) uticaj na lokalnu ekonomiju (zapošljavanje, turizam, saradnja sa lokalnim dobavljačima);
    9) održivost i dugoročni efekti (u izvoru: “Održivost ...... i dugoročni efekti”; segment “......” je prisutan u tekstu);
    10) prezentacija plana na intervjuu (jasan plan, spremno odgovaranje, vizija održivosti).
  * Eliminatorni kriterijumi (3):
    1) nedostatak formalnih uslova (nepotpuna dokumentacija);
    2) nedostavljanje izvještaja o realizaciji plana sa finansijskim izvještajem i pratećom dokumentacijom (fakture i izvodi) iz prethodnog perioda finansiranog/djelimično finansiranog iz budžeta Opštine;
    3) plan ulaganja nije u vezi sa subvencijama navedenim u članu 13 ove Odluke.
  * Obrazac P3 (lista evaluacije) elektronski popunjava Komisija; sadrži navedene podatke i konačnu ocjenu.
* **Deadlines:** nema (kriterijumi su dio evaluacije)
* **Documents/forms:** P3
* **Digital Kotor relevance:** elektronsko popunjavanje P3
* **Dependencies:** član 21 (bodovanje), član 13 (cross-reference u eliminatoru 3)
* **Open issue:** `SOURCE AMBIGUITY` za kriterijum 9 (“......” u tekstu); cross-reference eliminatora 3 — **PO Q6 RESOLVED** za kriterijum 9 (vidi 4.20.F / 4.21); izvorni tekst se ne mijenja.

### Član 21
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** bodovanje; preliminarna rang lista; zakazivanje treće sjednice
* **Actor(s):** Komisija; članovi Komisije
* **Explicit legal rules:**
  * nakon intervjua komisija boduje prijave na obrascu P3;
  * svaki član Komisije elektronski dodjeljuje bodove za svaki pozitivni kriterijum;
  * tokom bodovanja, članovi imaju uvid samo u svoje bodove;
  * prosječna ocjena po kriterijumu = zbir bodova svih članova / broj članova;
  * konačna ocjena = zbir prosječnih ocjena po svim kriterijumima;
  * po završetku ocjenjivanja automatski se formira preliminarna rang lista sa bodovima, bez utvrđenih iznosa;
  * komisija zakazuje treću sjednicu u roku 7 dana od održavanja druge sjednice i usmenih intervjua.
* **Deadlines:** 7 dana (treća sjednica)
* **Documents/forms:** P3
* **Digital Kotor relevance:** elektronsko dodjeljivanje bodova putem digitalnog servisa
* **Dependencies:** član 18 (druga sjednica/intervju); član 22 (konačna rang lista)
* **Open issue:** nema

### Član 22
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU
* **Subject:** konačna rang lista; prag 30 bodova; odluka podržava/odbija; iznosi; potpisi
* **Actor(s):** Komisija; predsjednik Komisije; članovi Komisije
* **Explicit legal rules:**
  * na trećoj sjednici komisija konstatuje za svaki plan iz preliminarne liste:
    * da li se podržava ili odbija;
    * iznos subvencije;
  * planovi ispod 30 bodova se neće podržati;
  * ako raspoloživa sredstva nisu dovoljna za sve, komisija podržava do utroška sredstava uz oslonac na rang listu;
  * predsjednik komisije elektronski unosi zaključke, napomene i detaljno obrazloženje odbijenih planova;
  * konačna rang lista (automatski generisana) sadrži: ime/naziv, vrstu subvencije, broj bodova, iznos potrebnih sredstava, iznos odobrenih sredstava i potpise svih članova.
* **Deadlines:** prag 30 bodova
* **Documents/forms:** konačna rang lista (lista iz čl.22)
* **Digital Kotor relevance:** elektronski unosi predsjednika; automatsko generisanje
* **Dependencies:** član 20–21
* **Open issue:** nema

### Član 23
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU (Odluka o dodjeli subvencija)
* **Subject:** predlog odluke o dodjeli; objava u roku
* **Actor(s):** Komisija; predsjednik Komisije; Sekretarijat; arhiva u aplikaciji; korisnici
* **Explicit legal rules:**
  * komisija putem digitalnog servisa generiše predlog odluke o dodjeli subvencija i predlaže Sekretarijatu;
  * nakon toga predsjednik komisije zatvara javni poziv i pohranjuje u arhivu aplikacije;
  * Sekretarijat objavljuje odluku o dodjeli subvencija u roku od 45 dana od isteka roka za prijavu na javni poziv.
* **Deadlines:** 45 dana; zatvaranje javnog poziva nakon predloga
* **Digital Kotor relevance:** digital.kotor.me generisanje/predlog; arhiva
* **Dependencies:** član 22 (konačna rang lista)
* **Open issue:** nema

### Član 24
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU (Odluka o dodjeli subvencija / rješenja)
* **Subject:** sadržaj odluke i pravna zaštita protiv rješenja o odbijanju
* **Actor(s):** preduzetnici/društva; Sekretarijat; Komisija; Upravni sud; podnosioci prijave
* **Explicit legal rules:**
  * odluka sadrži: ime/naziv korisnika; vrstu subvencije; iznose dodijeljene za planove; ukupan iznos sredstava potreban za realizaciju svakog plana;
  * odustanak nakon odluke: podnosilac daje Izjavu o odustanku Sekretarijatu; Sekretarijat donosi odluku o izmjeni i dopuni; prvobitno odobrena sredstva ostaju u budžetu;
  * Sekretarijat donosi Rješenja o dodjeli i dostavlja korisnicima;
  * Sekretarijat donosi Rješenja o odbijanju i obavještava o razlozima;
  * tužba Upravnom sudu protiv rješenja o odbijanju: 20 dana od prijema; tužba ne odlaže izvršenje odluke o dodjeli.
* **Deadlines:** 20 dana
* **Documents/forms:** Izjava o odustanku; Rješenja (odluke/rješenja)
* **Digital Kotor relevance:** objava i dostava kroz digitalni servis (čl.25)
* **Dependencies:** član 23–25
* **Open issue:** nema

### Član 25
* **Chapter:** VII. POSTUPAK PO JAVNOM POZIVU (Dostavljanje i objava odluke)
* **Subject:** komunikacija odluke o dodjeli
* **Actor(s):** svi učesnici javnog poziva; Opština Kotor; korisnici
* **Explicit legal rules:**
  * odluka dostavlja se svim učesnicima;
  * objavljuje se na vebsajtu Opštine, digitalnom servisu, putem lokalnog javnog emitera i na oglasnoj tabli.
* **Deadlines:** nema
* **Documents/forms:** nema
* **Digital Kotor relevance:** obavezna objava na digitalnom servisu
* **Dependencies:** član 23–24
* **Open issue:** nema

### Član 26
* **Chapter:** VIII. ZAKLJUČIVANJE UGOVORA
* **Subject:** preduslov ugovora i isplata; nadzor
* **Actor(s):** Sekretarijat; korisnici subvencije; ugovorne strane
* **Explicit legal rules:**
  * nakon donošenja odluke i njenog javnog objavljivanja, Sekretarijat zaključuje Ugovor u roku 10 dana od izvršnosti odluke;
  * ugovor uređuje: međusobna prava i obaveze, način korišćenja sredstava, izvještavanje i nadzor nad realizacijom;
  * u roku 10 dana od potpisivanja ugovora sredstva se uplaćuju na račun korisnika;
  * realizaciju zaključenog ugovora prati Sekretarijat.
* **Deadlines:** 10 dana (zaključenje); 10 dana (uplata nakon potpisivanja)
* **Documents/forms:** Ugovor
* **Digital Kotor relevance:** ugovor kao pravni dokument (tekst ne propisuje elektronsku formu)
* **Dependencies:** član 23–25
* **Open issue:** nema

### Član 27
* **Chapter:** IX. PRAĆENJE REALIZACIJE I PROCJENA USPJEŠNOSTI
* **Subject:** praćenje realizacije, terenska kontrola, preusmjeravanje i ćutanje organa
* **Actor(s):** Sekretarijat; preduzetnik/društvo
* **Explicit legal rules:**
  * praćenje realizacije vrši Sekretarijat;
  * razmatra: tok realizacije planiranih aktivnosti i realizaciju dodijeljenih sredstava;
  * Sekretarijat može izvršiti terensku kontrolu;
  * odstupanje od plana / nemogućnost utroška (npr. nedostupnost predmeta nabavke, skok cijena) => preduzetnik podnosi zahtjev Sekretarijatu za preusmjeravanje uz detaljno pojašnjenje razloga;
  * Sekretarijat odgovara u roku od 3 dana; u protivnom smatra se da je saglasan (**silent consent**).
* **Deadlines:** 3 dana
* **Documents/forms:** zahtjev za preusmjeravanje (forma nije propisana)
* **Digital Kotor relevance:** procesno; tekst ne navodi isključivo digitalni kanal
* **Dependencies:** član 26 (ugovor; nadzor)
* **Open issue:** nema

### Član 28
* **Chapter:** X. IZVJEŠTAJ O REALIZOVANIM BIZNIS PLANOVIMA
* **Subject:** Izvještaj korisnika (P4 i P4a) i prateća dokumentacija
* **Actor(s):** korisnik subvencije; Sekretarijat
* **Explicit legal rules:**
  * korisnik podnosi Izvještaj o realizaciji planirane nabavke (P4) sa finansijskim izvještajem (P4a) i pratećom dokumentacijom (fakture i izvodi sa banke) do isteka roka definisanog ugovorom;
  * korisnik opravdava iznos dodijeljene subvencije i sopstveno učešće;
  * obrasci P4 i P4a su sastavni dio Odluke.
* **Deadlines:** rok definisan ugovorom
* **Documents/forms:** P4, P4a; fakture; bankovni izvodi
* **Digital Kotor relevance:** tekst ne navodi isključivo digitalno podnošenje
* **Dependencies:** član 29 (posljedice ne dostavljanja)
* **Open issue:** nema

### Član 29
* **Chapter:** X. IZVJEŠTAJ O REALIZOVANIM BIZNIS PLANOVIMA
* **Subject:** povraćaj subvencije
* **Actor(s):** korisnik subvencije; Sekretarijat
* **Explicit legal rules:**
  * ako korisnik ne dostavi izvještaje i dokumentaciju u roku iz ugovora => nenamjensko trošenje sredstava;
  * slučaj gašenja/prodaje biznisa prije isteka perioda od 3 godine od dana potpisivanja ugovora => obaveza povraćaja u cjelosti na zahtjev Sekretarijata.
* **Deadlines:** 3 godine (od potpisivanja ugovora)
* **Documents/forms:** zahtjev Sekretarijata
* **Digital Kotor relevance:** nema
* **Dependencies:** član 15 (ne-gašenje najmanje 3 godine)
* **Open issue:** nema

### Član 30
* **Chapter:** X. IZVJEŠTAJ O REALIZOVANIM BIZNIS PLANOVIMA (javna promocija)
* **Subject:** javno predstavljanje realizovanih aktivnosti
* **Actor(s):** Sekretarijat; korisnici subvencija
* **Explicit legal rules:**
  * Sekretarijat može javno predstaviti realizovane aktivnosti i rezultate korisnika (čiji su planovi ulaganja podržani);
  * može organizovati prezentacije, sajmove i promotivne događaje radi predstavljanja planova i rezultata.
* **Deadlines:** nema
* **Documents/forms:** nema
* **Digital Kotor relevance:** nije isključivo digitalno, ali opšti kanal javnosti
* **Dependencies:** član 28–29 (efekti realizacije)
* **Open issue:** nema

### Član 31
* **Chapter:** XI. IZVJEŠTAVANJE SKUPŠTINE
* **Subject:** izvještaj Skupštini; rok
* **Actor(s):** Sekretarijat; Skupština Opštine Kotor; ugovorene godine korisnika
* **Explicit legal rules:**
  * Sekretarijat podnosi Skupštini Izvještaj o: podržanim planovima ulaganja, iznosu dodijeljenih subvencija, realizovanim projektima i njihovim efektima;
  * rok: 30 dana nakon isteka roka za podnošenje Izvještaja o realizaciji plana ulaganja od strane preduzetnika/društva;
  * rok iz ugovora je definisan u ugovoru o dodjeli subvencija za preduzetnike i **MMSP mladih** za određenu godinu.
* **Deadlines:** 30 dana
* **Documents/forms:** Izvještaj Skupštini; ugovor
* **Digital Kotor relevance:** nije eksplicitno navedeno
* **Dependencies:** član 28; član 26
* **Open issue:** `POTENTIAL LEGACY/COPY-PASTE TERMIN` “MMSP mladih” — **PO Q3 RESOLVED** (vidi 4.21); izvorni tekst se ne mijenja.

### Član 32
* **Chapter:** XII. PRELAZNE I ZAVRŠNE ODREDBE
* **Subject:** stupanje na snagu; identifikacija akta; napomena o prilozima
* **Actor(s):** Opština Kotor; Skupština; predjednik; korisnici
* **Explicit legal rules:**
  * stupanje na snagu: osmog dana od dana objavljivanja u “Službeni list Crne Gore - Opštinski propisi”;
  * akt broj: 11-016/26-12679;
  * mjesto i datum: Kotor, 17.6.2026. godine;
  * potpis: Predsjednik Skupštine (Vojin Batuta, s.r.);
  * napomena izdavača: “Priloge koji su sastavni dio ovog propisa možete pogledati ovdje.”
* **Deadlines:** osmi dan od objavljivanja
* **Documents/forms:** “prilozi” (u osnovnom PDF-u navedeni kao dodatak; fizički potvrđeni u posebnom PDF-u priloga: P1a, P1b, P2, P3, P4, P4a)
* **Digital Kotor relevance:** objava odluke u javnim kanalima (veb/digitalni servis) je ranije definisana
* **Dependencies:** član 23–25 (objava), član 28–31 (izvještaji)
* **Open issue:** prilozi nijesu ugrađeni u osnovni PDF, već postoje kao poseban sastavni izvor (`Odluka_o_podrsci_preduzetnicima_MMSP_subvencije_2026_prilozi.pdf`).

---

## 4.2 Registar pravnih aktera (bez mapiranja na aplikacione uloge)

> Format: uloga + nadležnosti + relevantni članovi (iz teksta).

1. **Skupština Opštine Kotor**
   * **Uloga:** donosilac odluke; prima izvještaj
   * **Nadležnosti:** donosi Odluku (preambula i čl.32); prima izvještaj (čl.31)
   * **Relevantni članovi:** čl.32, čl.31

2. **Predsjednik Skupštine Opštine Kotor**
   * **Uloga:** potpisnik odluke/radnji u okviru čl.32
   * **Nadležnosti:** potpis akta (potpis u izvornom tekstu)
   * **Relevantni članovi:** čl.32

3. **Sekretar Sekretarijata**
   * **Uloga:** imenuje komisiju i zamjenske članove; donosi rješenja/razrješenja
   * **Nadležnosti:** imenuje Komisiju rješenjem (čl.7); može imenovati zamjenskog člana (čl.7); razrješava člana komisije u slučajevima iz čl.9; sprovodi postupak razrješenja (čl.10); dužan imenovati novog člana u roku 15 dana (čl.11)
   * **Relevantni članovi:** čl.7, čl.9–11

4. **Sekretarijat**
   * **Uloga:** operativni organ u postupku i objavama
   * **Nadležnosti:** donosi Poslovnik o radu Komisije (čl.7); zakazuje/organizira objave odluka (čl.23); objavljuje odluku u roku 45 dana (čl.23); donosi odluke o izmjeni/dopuni (čl.24); donosi rješenja o dodjeli/odbijanju i dostavlja (čl.24); zaključuje ugovor i prati realizaciju (čl.26); odgovara na zahtjev za preusmjeravanje (čl.27); prima izvještaje P4/P4a (čl.28); ostvaruje javnu promociju (čl.30); podnosi izvještaj skupštini (čl.31)
   * **Relevantni članovi:** čl.7, čl.23–24, čl.26–31

5. **Komisija za dodjelu subvencija**
   * **Uloga:** evaluacija i rangiranje; priprema odluke
   * **Nadležnosti:** raspisivanje javnog poziva (čl.7); pregled validnosti dokumentacije i plana ulaganja (čl.7); intervju (čl.7); evaluacija prema kriterijumima (čl.7); formiranje konačne rang liste (čl.7); priprema predloga odluke (čl.7, čl.23); prva i druga sjednica i obrada nepotpunih prijava i prigovora (čl.18); bodovanje (čl.21); utvrđivanje konačne rang liste (čl.22)
   * **Relevantni članovi:** čl.7, čl.18–23

6. **Predsjednik Komisije**
   * **Uloga:** predsjedava i elektronski unosi zaključke
   * **Nadležnosti:** formira konačne zaključke; zatvara javni poziv nakon predloga odluke; elektronski unosi zaključke i napomene na konačnu rang listu
   * **Relevantni članovi:** čl.7, čl.22–23

7. **Sekretar Komisije**
   * **Uloga:** član Komisije (kao predstavnik opštine)
   * **Nadležnosti:** iz teksta nije dodijeljena posebna operativna funkcija osim članstva u Komisiji
   * **Relevantni članovi:** čl.7

8. **Član Komisije (predstavnik opštine)**
   * **Uloga:** evaluacija i bodovanje; potpis konačne rang liste; intervju
   * **Nadležnosti:** dodjeljuje bodove; prisustvuje intervjuu; potpisuje konačnu rang listu
   * **Relevantni članovi:** čl.7, čl.21–22

9. **Član Komisije (spoljašnji član)**
   * **Uloga:** evaluacija i bodovanje
   * **Nadležnosti:** dodjeljuje bodove; potpisuje rang listu
   * **Relevantni članovi:** čl.7, čl.21–22

10. **Zamjenski član**
   * **Uloga:** privremena zamjena odsutnog člana
   * **Nadležnosti:** imenovanje u slučaju odsustva prema rješenju sekretara Sekretarijata
   * **Relevantni članovi:** čl.7

11. **Podnosilac prijave (preduzetnik / društvo)**
   * **Uloga:** učesnik javnog poziva; kandidat za subvencije
   * **Nadležnosti:** podnosi prijavu i dokumentaciju (P1a/P1b/P2 + isprave), prigovor (čl.18), izvještavanje (čl.28–29) i zahtjev za preusmjeravanje (čl.27)
   * **Relevantni članovi:** čl.4, čl.14–16, čl.18, čl.27–29

12. **Upravni sud Crne Gore**
   * **Uloga:** pravna zaštita protiv rješenja o odbijanju
   * **Nadležnosti:** odlučuje po tužbi (prema čl.24); rok podnošenja 20 dana; tužba ne odlaže izvršenje odluke
   * **Relevantni članovi:** čl.24

---

## 4.3 Registar uslova za učešće (pravni uslovi i eliminatorne okolnosti)

### Osnovni uslovi (iz čl.4)
1) Prebivalište/sjedište ili registrovana djelatnost na teritoriji opštine Kotor — **čl.4**
2) Nema krivičnog postupka pred Osnovnim sudom — **čl.4**
3) Uredno izmirivanje poreskih obaveza — **čl.4**
4) Poslovanje u skladu sa važećim propisima — **čl.4**

### Dodatne zabrane / eliminatorne okolnosti (iz drugih članova)
5) Nepotpuna prijava => označavanje i ne razmatranje dalje — **čl.18** (lista ocjenjivanja iz čl.21)
6) Prigovor postoji kao pravni tok samo u vezi sa nepotpunostima (pravo, rok i odluka) — **čl.18**
7) Eliminatorni kriterijum: nedostatak formalnih uslova (nepotpuna dokumentacija) — **čl.20 tačka 1**
8) Eliminatorni kriterijum: nedostavljanje izvještaja o realizaciji plana sa finansijskim izvještajem i pratećom dokumentacijom u prethodnom periodu finansiranom/djelimično finansiranom iz budžeta — **čl.20 tačka 2**
9) Eliminatorni kriterijum: plan ulaganja nije u vezi sa subvencijama navedenim u članu 13 — **čl.20 tačka 3** *(cross-reference evidentirano kao anomaly)*
10) Ne može učestvovati u raspodjeli ako za plan ulaganja prethodnih godina nije dostavljen Izvještaj (P4 + P4a + fakture/bank izvodi) — **čl.15**
11) Ne može učestvovati ako je koristio budžetska sredstva po javnim konkursima za žensko preduzetništvo i/ili preduzetništvo mladih, a nije dostavio izvještaje — **čl.15**
12) Obaveza de minimis izjave (notarski ovjerena) za one kojima su odobrena sredstva — **čl.15**
13) Obaveza ne-gašenja biznisa najmanje 3 godine od potpisivanja ugovora — **čl.15**

---

## 4.4 Registar javnog poziva (čl.5–6)

1) **Ko raspisuje:** Komisija raspisuje Javni poziv — **čl.7 (nadležnosti)**
2) **Učestalost:** 1 put godišnje — **čl.5**
3) **Period raspisivanja:** treći kvartal tekuće godine — **čl.5**
4) **Minimalni obavezni sadržaj Javnog poziva (naročito):** iz čl.5 st. (navodi): ukupan iznos, najviši iznos, vrste subvencija, uslovi podnošenja, prateća dokumentacija, kriterijumi evaluacije plana ulaganja, rok i način podnošenja prijave i prateće dokumentacije, info sastanci, druge podatke značajne za sprovođenje — **čl.5**
5) **Kanali objave:** dnevni list; vebsajt Opštine; digitalni servis Opštine; Radio Kotor-u — **čl.6**
6) **Trajanje otvorenosti:** 20 dana od objavljivanja — **čl.6**
7) **Uloga digitalnog servisa:** digitalni servis je kanal i za podnošenje prijave (čl.14) i objavu/komunikaciju (čl.6 i čl.25)

**Vezа čl.5 ↔ čl.7 (Komisija):** Komisija ima nadležnost raspisivanja Javnog poziva (čl.7), a sadržaj/rules Javnog poziva (čl.5).

---

## 4.5 Registar Komisije (čl.7–11)

**Imenovanje / mandat**
* imenuje sekretar Sekretarijata rješenjem — **čl.7**
* mandat komisije: godinu dana — **čl.7**
* mandat prestaje: istekom, ličnim zahtjevom, razrješenjem — **čl.8**
* razrješenje po razlozima iz čl.9 — **čl.9**
* postupak razrješenja: pravo na izjašnjenje; rješenje sekretara — **čl.10**
* zamjena: rješenje o imenovanju u roku 15 dana; trajanje do isteka mandata; razriješeni ne može ponovo — **čl.11**

**Sastav**
* 3 člana; 1 predsjednik — **čl.7**
* 2 predstavnika opštine (predsjednik + sekretar komisije) — **čl.7**
* 1 spoljašnji član (udruženje/biznis/akademska zajednica) — **čl.7**
* Sekretar može imenovati zamjenskog člana u slučaju odsustva — **čl.7**

**Nadležnosti**
* raspisuje javni poziv;
* pregled i ocjena validnosti predate dokumentacije i plana ulaganja;
* intervju;
* evaluacija planova ulaganja;
* formiranje konačne rang liste;
* priprema predloga Odluke o subvencionisanju — **čl.7**

**Kvorum i sjednice**
* kvorum: većina članova;
* sjednice elektronski (zoom/teams ili elektronsko uključenje);
* ako nema kvoruma: sjednica se odlaže;
* intervju i punovažne odluke: prisustvo svih članova — **čl.7**

**Javnost rada i Poslovnik**
* rad Komisije je javan — **čl.7**
* Poslovnik o radu donosi Sekretarijat — **čl.7**

**Tajnost i sukob interesa**
* članovi potpisuju Izjavu o tajnosti i Izjavu o sprečavanju sukoba interesa — **čl.7**

**Zabrana učešća člana Komisije**
* član komisije (ili društvo čiji je predstavnik) nema pravo učešća — **čl.7**

---

## 4.6 Vrste subvencija (čl.12)

1) **Subvencije za razvoj autentičnih lokalnih proizvoda/usluga** — **čl.12 tačka 1**
   * opis: razvoj proizvoda/usluga zasnovanih na lokalnoj tradiciji, kulturnom naslijeđu, prirodnim resursima ili specifičnim zanatskim vještinama opštine.
2) **Subvencije za razvoj inovativnih proizvoda/usluga** — **čl.12 tačka 2**
   * opis: razvoj novog proizvoda/usluge ili značajno unapređenje; primjena novih tehnologija itd.
3) **Subvencije za digitalizaciju** — **čl.12 tačka 3**
   * opis: nabavka softvera, digitalne opreme i alata; izrada/unapređenje veb stranica; online prodaja; digitalni marketing; aktivnosti doprinose digitalnoj transformaciji.

---

## 4.7 Prihvatljivi i neprihvatljivi troškovi (čl.13)

### ACCEPTABLE COSTS (čl.13)
1) Materijalna imovina (mašine/tehnike/alati/ICT oprema) — uz zabranu otuđenja min. 1 godinu — **čl.13 (prihvatljivi)**
2) Nematerijalna imovina (softver, baza podataka, franšiza i sl. ako je neophodna) — **čl.13**
3) Nabavka sirovina i repro materijala — **čl.13**
4) Bruto plate novozaposlenih na ugovor min. 12 mjeseci (do 6 mjeseci u tekstu) — **čl.13**
5) Marketing i oglašavanje (logo/branding; website i održavanje do 12 mjeseci; domen/hosting do 12 mjeseci; štampanje; oglašavanje do 12 mjeseci na različitim kanalima; digitalni sadržaj; promotivni događaji; oprema; pakovanje; sajmovi/izložbe) — **čl.13**
6) Uvođenje automatizacije online prodaje i e-commerce — **čl.13**
7) Sertifikacija i uvođenje standarda — **čl.13**

### INELIGIBLE COSTS (čl.13)
1) Aktivnosti u nadležnosti Vlade (formalno obrazovanje, formalna zdravstvena zaštita i sl.) — **čl.13**
2) Kupovina/raspodjela humanitarne pomoći — **čl.13**
3) Jednokratno štampanje/izrada knjiga/biltena/časopisa ako nije dio šireg programa/kontinuiranih aktivnosti — **čl.13**
4) Nezakonite ili štetne po okolinu i opasne za zdravlje (npr. igre na sreću, duvan, alkoholna pića; izuzev vina i voćnih rakija) — **čl.13**
5) Poljoprivreda/marikutura/ribarstvo gdje su opredjeljena posebna sredstva iz budžeta Opštine ili Ministarstva — **čl.13**
6) Carine i uvozni troškovi — **čl.13**
7) Novčane kazne i troškovi parničnog postupka — **čl.13**
8) Troškovi podnosioca nastali u periodu prije potpisivanja Ugovora iz člana 27 — **čl.13**
9) Gotovinska plaćanja, plaćanja u naturi i plaćanja putem robne razmjene — **čl.13**
10) Plaćanja za sopstvene usluge — **čl.13**
11) Nemoralne i nelegalne aktivnosti — **čl.13**

**Open issue:** tekst člana 13 sadrži “(do 6 mjeseci)” uz “minimalno 12 mjeseci” — **PO Q5 RESOLVED** (vidi 4.20.F / 4.21); izvorni tekst se ne mijenja.

---

## 4.8 Dokumentacija prijave (čl.14 + čl.15–16) i checkliste

### PREDUZETNIK — P1a (pravna dokumentacija i sadržaj)
* Uvodni okvir: prijava i prateća dokumentacija (čl.14)
* Forma: **obrazac P1a** — **čl.14 (preduzetnici prilažu tačka 1)** i polja u čl.16
* Dodatno: de minimis izjava se odnosi na one kojima su odobrena sredstva — **čl.15**
* Polja P1a (čl.16):
  * vrstu subvencije;
  * ime i prezime;
  * JMBG/PIB;
  * adresu;
  * šifru djelatnosti;
  * broj zaposlenih;
  * broj žiro računa;
  * kontakt telefon;
  * e-mail;
  * website;
  * datum registracije u CRPS;
  * naznaka tačnosti podataka i potpis preduzetnika.

### DRUŠTVO / MMSP — P1b
* Forma: **obrazac P1b** — **čl.14 (društva prilažu tačka 1)** i polja u čl.16
* Polja P1b (čl.16):
  * vrstu subvencije;
  * naziv društva;
  * ime i prezime odgovornog lica;
  * PIB društva;
  * sjedište;
  * datum osnivanja;
  * šifra djelatnosti;
  * broj zaposlenih;
  * broj žiro računa;
  * kontakt telefon odgovornog lica;
  * e-mail odgovornog lica;
  * website;
  * naznaku tačnosti podataka i potpis odgovornog lica.

### P2 — plan ulaganja
* Popunjava se elektronski i sadržaj je propisan — **čl.17**
* (struktura upisana u poglavlju 4.11)

### P3 — lista za evaluaciju
* Komisija popunjava elektronski; sadrži evaluaciju i konačnu ocjenu — **čl.20**
* obrazac je sastavni dio — **čl.20**
* konkretno bodovanje se sprovodi kroz P3 — **čl.21**

### P4 — izvještaj korisnika; P4a — finansijski izvještaj
* sastavni dio Odluke — **čl.15**
* dostavlja se do isteka roka ugovora, uz finansijski izvještaj i prateću dokumentaciju — **čl.28**

**STATUS PRILOGA (potvrđeno iz fizičkog izvora):** P1a, P1b, P2, P3, P4 i P4a su pregledani u posebnom PDF-u priloga i tretiraju se kao sastavni pravni izvor.

---

## 4.9 Forme P1a/P1b (čl.16)

### P1a — Preduzetnik
* polja: vrsta subvencije, ime i prezime, JMBG/PIB, adresa, šifra djelatnosti, broj zaposlenih, broj žiro računa, kontakt telefon, e-mail, website, datum registracije u CRPS, izjava o tačnosti i potpis.
* obaveza: forma za prijavu preduzetnika — **čl.14** i polja — **čl.16**
* potvrda iz fizičkog obrasca P1a:
  * naslov: `Obrazac P1a`
  * naziv forme: prijava za oblik registracije **PREDUZETNIK**
  * završna izjava: “pod punom materijalnom i krivičnom odgovornošću...”
  * potpis: “Potpis preduzetnika” + `M.P.`
* poređenje član 16 ↔ obrazac P1a:
  * `FORM/TEXT INCONSISTENCY`: član 16 navodi “naznaku da za tačnost podataka odgovara preduzetnik”, a obrazac koristi širu formulaciju o materijalnoj i krivičnoj odgovornosti.

### P1b — DOO/društvo
* polja: vrsta subvencije, naziv društva, ime i prezime odgovornog lica, PIB, sjedište, datum osnivanja, šifra djelatnosti, broj zaposlenih, žiro račun, kontakt telefon odgovornog lica, e-mail, website, izjava o tačnosti i potpis.
* obaveza: forma za prijavu društava — **čl.14** i polja — **čl.16**
* potvrda iz fizičkog obrasca P1b:
  * naslov: `Obrazac P1b`
  * naziv forme: prijava za oblik registracije **DOO**
  * potpis: “Potpis odgovornog lica” + `M.P.`
* poređenje član 16 ↔ obrazac P1b:
  * `FORM/TEXT INCONSISTENCY`: član 16 koristi formulaciju “Prijava DOO”, dok čl.14 govori šire o društvima/MMSP; obrazac je eksplicitno za DOO.

### P2 — Plan ulaganja
* struktura: navedena u čl.17 i potvrđena u fizičkom obrascu `Obrazac P2`.
* potvrđena dodatna granularnost iz obrasca P2 (sastavni pravni izvor):
  * I. Osnovni podaci: `oblik registracije`, `preduzetnik/društvo`, `kontakt osoba`, `PIB`, `adresa/sjedište`, `kontakt telefon`, `e-mail`, `website`, `šifra djelatnosti`, `godina osnivanja`, `broj zaposlenih`, `broj žiro računa i naziv banke`;
  * II. Plan ulaganja: opis ulaganja, ciljevi ulaganja, aktivnosti (`aktivnost`, `kratak opis`, `period realizacije`);
  * III. Finansijski okvir: tabela (`vrsta troška`, `opis`, `iznos EUR`) + zbirna polja (ukupna vrijednost, sopstveno učešće, očekivani iznos subvencije);
  * IV–VII opisni segmenti: autentičnost/inovativnost/digitalizacija; očekivani rezultati; uticaj na lokalnu ekonomiju; održivost i dugoročni efekti;
  * potpisivanje: `Podnosilac`, `Potpis i pečat`.

---

## 4.10 Plan ulaganja — P2 (čl.17)

I. OSNOVNI PODACI
1) Osnovni podaci o preduzetniku/društvu
2) Vrsta subvencije za koju se aplicira

II. PLAN ULAGANJA
3) Opis planiranog ulaganja
4) Ciljevi ulaganja
5) Planirane aktivnosti

III. FINANSIJSKI OKVIR ULAGANJA
6) Vrsta troška, opis i iznos
7) Ukupna vrijednost ulaganja
8) Sopstveno učešće
9) Očekivani iznos subvencije

IV. AUTENTIČNOST / INOVATIVNOST / DIGITALIZACIJA
V. OČEKIVANI REZULTATI
VI. UTICAJ NA LOKALNU EKONOMIJU
VII. ODRŽIVOST I DUGOROČNI EFEKTI

### P2 — form/text poređenje (čl.17 ↔ obrazac P2)
* `FORM/TEXT INCONSISTENCY`: član 17 navodi strukturu kroz tematske cjeline, dok obrazac P2 dodatno konkretizuje pojedinačna polja i pitanja (npr. kontakt osoba, naziv banke, tabelarni unos aktivnosti i troškova).
* Klasifikacija razlike: **nije kontradikcija**, već razrada kroz sastavni obrazac.

---

## 4.11 Administrativna provjera i Prigovor (čl.18) — pravni tok

1) Prva sjednica zakazuje se najkasnije 7 dana od isteka roka za prijavu — **čl.18**
2) Komisija na prvoj sjednici pregleda elektronski zaprimljene prijave — **čl.18**
3) Ako je prijava nepotpuna => označavanje u listi ocjenjivanja iz čl.21 i prijava se ne razmatra dalje — **čl.18**
4) Obavještavanje o mogućnosti Prigovora: Komisija obavještava putem registrovanog mail-a podnosioca na digitalnom servisu — **čl.18**
5) Rok za podnošenje Prigovora: 3 dana od dana slanja obavještenja — **čl.18**
6) Komisija donosi odluku o prihvatanju/odbijanju Prigovora u roku 7 dana od prijema — **čl.18**
7) Druga sjednica i intervju: zakazuje se u roku 7 dana od održavanja prve sjednice za prijave koje su potpune — **čl.18**

---

## 4.12 Finansijska pravila (čl.19 + relevantne reference)

* Broj vrsta subvencija: iz teksta vrsta je 3 (čl.12 tačke 1–3); međutim čl.19 upućuje na “dvije vrste subvencija iz člana 13” (cross-reference anomaly).
* Broj subvencija po korisniku: može konkurisati za dvije vrste, ali podržan samo za jednu — **čl.19**
* Maksimalni iznos subvencije po korisniku:
  * do 20% planiranog budžeta za ovu namjenu;
  * i do 50% potrebnih sredstava za realizaciju aktivnosti iz plana ulaganja — **čl.19**
* Poseban procenat za “autentične lokalne proizvode i usluge” (kako upućeno u čl.19):
  * “iznos subvencije do 80% potrebnih sredstava” za subvenciju iz člana 13 tačka 1 — **čl.19**
* Raspodjela do utroška sredstava: u skladu sa konačnom rang listom do utroška sredstava — **čl.19**
* Prag od 30 bodova: finalna ocjena ispod 30 se ne podržava — **čl.22**
* Sopstveno učešće:
  * eksplicitno navedeno u planu ulaganja P2 (tačka 8) — **čl.17**;
  * obavezno opravdanje u izvještaju — **čl.28**.

**Open issue / anomaly:** cross-reference čl.19 → čl.13 — **PO Q1 RESOLVED** (vidi 4.21); izvorni tekst se ne mijenja.

---

## 4.13 Kriterijumi evaluacije i eliminacije (čl.20)

### Pozitivni kriterijumi (10)
1) Kvalitet plana ulaganja (definisani ciljevi, opis aktivnosti, period realizacije) — **čl.20 tačka 1** (skala bodova: nije u tekstu, bodovanje je numeričko ali bez skale; “broj bodova” se dodjeljuje elektronski)
2) Relevantnost ulaganja za razvoj biznisa — **čl.20 tačka 2**
3) Finansijska opravdanost (realni troškovi; jasna finansijska konstrukcija) — **čl.20 tačka 3**
4) Jačanje konkurentnosti — **čl.20 tačka 4**
5) Lokalna autentičnost proizvod/usluga — **čl.20 tačka 5**
6) Inovativnost — **čl.20 tačka 6**
7) Doprinos digitalizaciji poslovanja — **čl.20 tačka 7**
8) Uticaj na lokalnu ekonomiju — **čl.20 tačka 8**
9) Održivost i dugoročni efekti (u tekstu prisutan segment “......”) — **čl.20 tačka 9**  
   *Skala ocjene:* `EXPLICIT SOURCE RULE — FORM P3` (1–5).
10) Prezentacija plana ulaganja na intervjuu — **čl.20 tačka 10**  
   *Skala ocjene:* `EXPLICIT SOURCE RULE — FORM P3` (1–5).

### Eliminatorni kriterijumi (3)
1) Nedostatak formalnih uslova (nepotpuna dokumentacija) — **čl.20 tačka 1**
2) Nedostavljanje izvještaja o realizaciji plana sa finansijskim izvještajem i pratećom dokumentacijom — **čl.20 tačka 2**
3) Plan ulaganja nije u vezi sa subvencijama navedenim u članu 13 — **čl.20 tačka 3**  
   *Skala:* eliminatorni kriterijumi ostaju binarni (ispunjeno/neispunjeno), dok numeričko bodovanje pozitivnih kriterijuma koristi skalu 1–5 iz obrasca P3.

---

## 4.14 P3 i bodovanje (čl.21–22)

**Sadržaj P3:** lista za evaluaciju plana ulaganja; Komisija elektronski popunjava:
* ime i prezime preduzetnika / naziv društva;
* vrstu subvencije;
* naznaku potpune dokumentacije;
* evaluaciju plana ulaganja u brojkama i konačnu ocjenu plana — **čl.20**

**EXPLICIT SOURCE RULE — FORM P3**
* Skala ocjenjivanja je eksplicitno propisana u fizičkom obrascu P3:
  * `1` = “uopšte ne odgovara navedenom”
  * `5` = “u potpunosti odgovara navedenom”
* U obrascu su ocjenjivačke kolone: `Predsjednik`, `Član 1`, `Član 2`, `Član 3`, `Član 4`, te `Prosječna ocjena`.
* P3 uvodna referenca glasi: “Kriterijumi za ocjenu (Član 21 stav 2 Odluke)”.

**Individualno bodovanje članova:** svaki član komisije dodjeljuje bodove za svaki pozitivni kriterijum — **čl.21**

**Privatnost bodova:** dok bodovanje traje, članovi imaju uvid samo u svoje bodove — **čl.21**

**Način računanja prosjeka:**
* prosječna ocjena po kriterijumu = zbir bodova svih članova / broj članova komisije — **čl.21**

**Konačna ocjena:**
* zbir prosječnih ocjena po svim kriterijumima — **čl.21**

**Automatsko formiranje preliminarne rang liste:**
* preliminarna rang lista formira se automatski kada svi članovi ocjene sve planove — **čl.21**

**Treća sjednica:**
* komisija zakazuje treću sjednicu u roku 7 dana od druge sjednice i usmenih intervjua — **čl.21**

**Podržava/odbija i iznos subvencije:**
* odluka na trećoj sjednici: podržava/odbija + iznos — **čl.22**

**Prag od 30 bodova:** finalna ocjena ispod 30 se neće podržati — **čl.22**

**Generisanje konačne rang liste:**
* automatski generisana lista nakon popunjavanja podataka iz stava 4 člana 22 — **čl.22**

**Sadržaj konačne rang liste:** (ime/naziv, vrsta, broj bodova, iznos potrebnih sredstava, iznos odobrenih sredstava i potpisi svih članova) — **čl.22**

**Potpisi svih članova:** uključeni u konačnu rang listu — **čl.22**

**Open issue:** P3 referenca “Član 21 stav 2” vs kriterijumi u čl.20 — **PO Q4 RESOLVED** (vidi 4.20.G / 4.21); izvorni tekst se ne mijenja.

---

## 4.15 Odluka/rješenja i pravna zaštita (čl.23–25)

1) **Predlog Odluke o dodjeli subvencija:** generiše Komisija putem digitalnog servisa i predlaže Sekretarijatu — **čl.23**
2) **Objava Odluke o dodjeli:** Sekretarijat objavljuje u roku 45 dana od isteka roka za prijavu — **čl.23**
3) **Izmjene/dopune nakon odustanka:** ako podnosilac odustane, daje Izjavu o odustanku; Sekretarijat donosi odluku o izmjeni i dopuni; prvobitno odobrena sredstva ostaju u budžetu — **čl.24**
4) **Rješenja o dodjeli subvencija:** Sekretarijat donosi Rješenja o dodjeli (dostava korisnicima) — **čl.24**
5) **Rješenja o odbijanju:** Sekretarijat obavještava odbijene prijave o razlozima — **čl.24**
6) **Mogućnost tužbe Upravnom sudu:** protiv rješenja o odbijanju postoji mogućnost tužbe Upravnom sudu; rok 20 dana; tužba ne odlaže izvršenje — **čl.24**

**Kanali objave (dostava/objava odluke):** vebsajt, digitalni servis, lokalni javni emiter, oglasna tabla — **čl.25**

**Uočena terminologija:** koristi se riječ “tužba” (ne “žalba”) kada je definisana pravna zaštita — **čl.24**

---

## 4.16 Ugovor i isplata (čl.26)

* Preduslov za ugovor: nakon donošenja odluke o dodjeli subvencija i njenog javnog objavljivanja — **čl.26**
* Ko zaključuje: Sekretarijat sa preduzetnicima/društvima — **čl.26**
* Sa kim: korisnici kojima su dodijeljene subvencije — **čl.26**
* Rok: 10 dana od izvršnosti odluke; 10 dana od potpisivanja za uplatu — **čl.26**
* Šta ugovor uređuje: prava/obaveze, način korišćenja sredstava, izvještavanje i nadzor realizacije — **čl.26**
* Ko prati realizaciju: Sekretarijat — **čl.26**

---

## 4.17 Praćenje realizacije (čl.27)

* Odgovorni organ: Sekretarijat — **čl.27**
* Šta se prati: tok realizacije aktivnosti i realizacija dodijeljenih sredstava — **čl.27**
* Terenska kontrola: moguća — **čl.27**
* Odstupanje: ako sredstva nije moguće utrošiti prema ugovoru → zahtjev za preusmjeravanje — **čl.27**
* Obavezno obrazloženje: zahtjev uz detaljno pojašnjenje razloga — **čl.27**
* Rok Sekretarijata: 3 dana — **čl.27**
* Pravilo ćutanja: ako se ne odgovori u 3 dana, smatra se saglasnost — **čl.27**

---

## 4.18 Izvještavanje korisnika (čl.28–29)

* P4: Izvještaj o realizaciji planirane nabavke — **čl.28**
* P4a: Finansijski izvještaj — **čl.28**
* Fakture i izvodi sa banke: prateća dokumentacija — **čl.28**
* Ugovorni rok: do isteka roka definisanog ugovorom — **čl.28**
* Obaveza opravdanja subvencije i sopstvenog učešća — **čl.28**
* Povraćaj: nenamjensko trošenje ili gašenje/prodaja prije isteka 3 godine => povrat subvencije u cjelosti na zahtjev Sekretarijata — **čl.29**
* Poveznica na čl.15: obaveza da se biznis ne ugasi najmanje 3 godine — **čl.15 i čl.29**

### EXPLICIT SOURCE RULE — FORM P4
* P4 (`Obrazac P4`) sadrži obavezna identifikaciona polja:
  * ime i prezime preduzetnika ili nosioca biznisa;
  * pravni status i naziv biznisa;
  * vrsta subvencije;
  * iznos odobrenih sredstava;
  * broj ugovora;
  * izvještajni period.
* P4 sadržajna pitanja (1–8):
  1) ukratko opis svih aktivnosti u izvještajnom periodu;
  2) kratak opis uočenih problema;
  3) kratak opis uočenih uspjeha;
  4) broj novozaposlenih, vrsta ugovora i period;
  5) šta je nabavljeno korišćenjem odobrenih sredstava;
  6) eventualna odstupanja od prvobitnog plana i obrazloženje;
  7) zadovoljstvo saradnjom sa Opštinom Kotor — Sekretarijatom;
  8) preporuke za unapređenje saradnje privatnog sektora i Opštine.
* P4 napomene/potvrde:
  * izvještaj može biti proširen tabelama po potrebi;
  * potpisi i pečat su obavezni;
  * sastavni prilozi: finansijski izvještaj (P4a), fakture i izvod sa žiro računa banke;
  * odgovori se odnose na izvještajni period.

### EXPLICIT SOURCE RULE — FORM P4a
* P4a (`Obrazac P4a`) je tabelarni finansijski izvještaj sa kolonama:
  * `r.br.`
  * `Vrsta nabavke`
  * `Iznos računa (sa PDV-om i ostalim troškovima)`
  * `Dobavljač`
  * `Broj fakture`
  * `Datum izdavanja`
  * `Broj izvoda i datum plaćanja`
* Sadrži red `UKUPNO` i potpisno polje (`U Kotoru...`, `M.P.`, `Potpis`).

---

## 4.19 Javna promocija i izvještavanje Skupštine (čl.30–31)

* Mogućnost javnog predstavljanja: realizovane aktivnosti i rezultati korisnika — **čl.30**
* Promotivni događaji: prezentacije/sajmovi/promotivni događaji — **čl.30**
* Izvještaj Skupštini: podržani planovi, iznos, realizovani projekti i efekti — **čl.31**
* Rok: 30 dana nakon isteka roka za podnošenje izvještaja korisnika iz ugovora — **čl.31**
* Anomalija terminologije: u ugovoru se spominje “MMSP mladih” — **čl.31**

---

## 4.20 SOURCE ANOMALIES / LEGAL CLARIFICATIONS REQUIRED

> Posebno provjereno cross-reference po zadatom modelu: čl.19→čl.13; čl.16→čl.15; čl.31 “MMSP mladih”; čl.13→„Ugovor iz člana 27“; dodatne interne reference uključuju terminologiju “Javni konkurs”.

# 4.20.A. Član 19 → član 13
* U tekstu čl.19: “jedan preduzetnik/društvo može konkurisati za dvije vrste subvencija iz člana 13 ove Odluke...”; te “iznos subvencije do 80% ... za subvenciju iz člana 13 tačka 1”
* Međutim:
  * vrste subvencija su navedene u članu 12;
  * član 13 uređuje prihvatljive/neprihvatljive troškove.
* **SOURCE CROSS-REFERENCE INCONSISTENCY** (ne popravljati)
* **PO TREATMENT (Q1):** RESOLVED / NON-BLOCKING. Za kanonsku dokumentaciju i budući poslovni model, vrste subvencija se izvode iz **člana 12**. Upućivanje čl.19 na čl.13 ostaje evidentirano kao cross-reference; ne mijenja broj niti sadržaj vrsta subvencija. Član 13 ostaje izvor za prihvatljive/neprihvatljive troškove. Traceability: čl.12/čl.19/čl.13 → anomaly → PO Q1 → **KN-BM = READY FOR DERIVATION**.

# 4.20.B. Član 16 → član 15
* U tekstu čl.16: “Preteća dokumentacija iz člana 15 ove Odluke ... sastavni je dio prijave...”
* Tekst čl.14 daje detaljan spisak prateće dokumentacije za prijavu; čl.15 sadrži druge obaveze/izjave i zabrane.
* Evidentirati bez korekcije:
  * **SOURCE CROSS-REFERENCE INCONSISTENCY**
* **PO TREATMENT (Q2):** RESOLVED / NON-BLOCKING. Dokumentacioni zahtjevi za prijavu izvode se iz **člana 14** zajedno sa sastavnim obrascima. Upućivanje čl.16 na čl.15 ostaje evidentirano kao cross-reference; ne mijenja sadržaj dokumentacione checkliste. Traceability: čl.14/čl.16/čl.15 + obrasci → anomaly → PO Q2 → **KN-BM = READY FOR DERIVATION**.

# 4.20.C. Član 31 — „MMSP mladih“
* U tekstu čl.31: rok podnošenja izvještaja je definisan “Ugovorom o dodjeli subvencija za preduzetnike i MMSP mladih...”
* U aktu termin “MMSP mladih” se eksplicitno pojavljuje kao dio ugovorne formulacije (vidi i čl.15 gdje se spominju “Javni konkurs... preduzetništva mladih”).
* Evidentirati kao mogući legacy/copy-paste termin:
  * **POTENTIAL SUBSTANTIVE AMBIGUITY: “MMSP mladih” u ugovornoj formulaciji čl.31**
* **PO TREATMENT (Q3):** RESOLVED / NON-BLOCKING. Formulacija „MMSP mladih“ u čl.31 **ne tumači se** kao ograničenje korisnika ove Odluke na mlade. Razlozi: čl.31 uređuje izvještavanje Skupštini; Odluka ne propisuje starosni uslov; nema definicije „mladog preduzetnika“; P1a/P1b/P2/P3/P4/P4a ne uvode starosni kriterijum; čl.15 posebno pominje raniji konkurs za razvoj preduzetništva mladih. Originalna formulacija ostaje kao source anomaly. Traceability: čl.31 → anomaly → PO Q3 → **KN-BM = READY FOR DERIVATION**.

# 4.20.D. Druge reference / terminološke anomalije (ne popravljati)
1) Terminologija “Javni konkurs” vs “Javni poziv”:
   * čl.15 spominje “Javnom konkursu za razvoj ženskog preduzetništva” i “Javnom konkursu za razvoj preduzetništva mladih” kao prethodne programe kao uslov zabrane učešća.
   * Akt primarno koristi “Javni poziv” u čl.5–6; ovdje se koristi “Javni konkurs”.
   * Klasifikacija: **TERMINOLOGICAL / POTENTIAL SUBSTANTIVE AMBIGUITY** (zadržati izvorni tekst).

# 4.20.E. STATUS PRILOGA (potvrđeno iz fizičkog izvora)

Napomena izdavača u osnovnom aktu (“Priloge ... možete pogledati ovdje”) verifikovana je kroz poseban PDF sa prilozima.

Potvrđeno fizičko postojanje i pregled:
* **CONFIRMED ATTACHMENT — P1a**
* **CONFIRMED ATTACHMENT — P1b**
* **CONFIRMED ATTACHMENT — P2**
* **CONFIRMED ATTACHMENT — P3**
* **CONFIRMED ATTACHMENT — P4**
* **CONFIRMED ATTACHMENT — P4a**

Zaključak:
* prethodne `MISSING SOURCE ATTACHMENT` oznake za ove obrasce su zastarjele i uklonjene.

# 4.20.F. Otvorena pravna pitanja / source fidelity
* čl.20 tačka 9: tekst sadrži segment “......” u nazivu kriterijuma; zagrada sadrži potpuno objašnjenje; P3 i P2 razrađuju istu temu.
  * **SOURCE ANOMALY:** TYPOGRAPHIC/EDITORIAL (zadržava se).
  * **PO TREATMENT (Q6):** RESOLVED / NON-BLOCKING. Kriterijum se vodi kao „Održivost i dugoročni efekti“. Traceability: čl.20/P3/P2 → anomaly → PO Q6 → **KN-BM = READY FOR DERIVATION**.
* čl.13: dio “bruto plate novozaposlenih ... (do 6 mjeseci)” uz “minimalno 12 mjeseci”.
  * **SOURCE ANOMALY:** SOURCE AMBIGUITY (izvorni tekst se ne mijenja).
  * **PO TREATMENT (Q5):** RESOLVED / NON-BLOCKING. PO tumačenje (za kanonski poslovni model, ne kao korekcija izvora): (1) ugovor o radu novozaposlenog mora biti zaključen na najmanje 12 mjeseci; (2) prihvatljivi/subvencionisani trošak bruto plate može obuhvatiti period do 6 mjeseci. P4 potvrđuje evidenciju vrste ugovora i perioda. Traceability: čl.13/P4 → anomaly → PO Q5 → **KN-BM = READY FOR DERIVATION**.

# 4.20.G. Obrazac P3 reference i numeracija
* U obrascu P3 stoji referenca “Kriterijumi za ocjenu (Član 21 stav 2 Odluke)”.
* Normativni spisak kriterijuma je u članu 20, dok član 21 uređuje mehaniku bodovanja i rangiranja.
* Klasifikacija: **CROSS-REFERENCE / CITATION-ONLY ERROR**
* **PO TREATMENT (Q4):** RESOLVED / NON-BLOCKING. Pozitivni kriterijumi izvode se iz **člana 20** + **P3**. P3 sadrži svih 10 pozitivnih kriterijuma; skala 1–5 = `EXPLICIT SOURCE RULE — FORM P3`. Sva 3 eliminatorna kriterijuma ostaju normativno izvedena iz čl.20; P3 operacionalizuje provjeru potpunosti dokumentacije bez ukidanja ostalih eliminatornih kriterijuma. Traceability: čl.20/P3/čl.21–22 → anomaly → PO Q4 → **KN-BM = READY FOR DERIVATION**.

# 4.20.H. P1a/P1b vs član 16
* P1a i P1b su u suštini usklađeni sa čl.16 po ključnim poljima.
* Djelimične razlike u formulaciji:
  * izjava odgovornosti u obrascima je šira od sažetog opisa iz čl.16;
  * P1b je naslovljen kao DOO forma, dok čl.14/16 govori šire o društvima.
* Klasifikacija: **FORM/TEXT INCONSISTENCY** i **TERMINOLOGICAL** (bez izmjene izvora).

# 4.20.I. P2 vs član 17
* Član 17 daje okvirne cjeline, a obrazac P2 sadrži detaljnija podpolja/tabele.
* Klasifikacija: **FORM/TEXT INCONSISTENCY** (razrada obrascem, bez kontradikcije).

# 4.20.J. Terminološke varijacije
* `Javni poziv` / `Javni konkurs` — prisutno u tekstu (čl.5–6 naspram čl.15) — **TERMINOLOGICAL**.
* `plan ulaganja` / `biznis plan` — prisutno u evaluacionim dijelovima i napomenama — **TERMINOLOGICAL**.
* `dodjela` / `dodijela` — prisutne pravopisne varijacije u izvoru — **TYPOGRAPHIC**.
* `MMSP` / `DOO` / `društvo` — različit nivo opštosti kroz članove i obrasce — **TERMINOLOGICAL**.

# 4.20.K. PO/LEGAL CONFIRMATION — status nakon PO review (Q1–Q6)

**Q1–Q6:** RESOLVED / NON-BLOCKING (vidi `4.21 PO DECISION REGISTER`).

**NO KNOWN BM-BLOCKING LEGAL QUESTIONS REMAIN** (u okviru Q1–Q6).

**Ostala otvorena pitanja (nisu Q1–Q6, ne blokiraju BM derivaciju):**
* **DERIVED DATE** stupanja na snagu (čl.32): računanje „osmog dana“ — `DERIVED — REQUIRES PO/LEGAL CONFIRMATION` (nije BM blocker).
* **Tipografske/terminološke varijacije** (4.20.D, 4.20.J): `dodjela/dodijela`, `plan ulaganja/biznis plan`, `Javni poziv/Javni konkurs`, `MMSP/DOO/društvo` — dokumentovane; ne mijenjaju proces bez PO odluke o terminološkoj standardizaciji u BM.
* **FORM/TEXT INCONSISTENCY** (4.20.H, 4.20.I): P1a/P1b vs čl.16; P2 vs čl.17 — razrada obrascem; ne blokira BM derivaciju.
* **SOURCE CROSS-REFERENCE ANOMALY** (4.20.L): čl.13 → „Ugovor iz člana 27“ — katalog dopunjen; izvor i KN-BR-073 ne mijenjaju se.

# 4.20.L. Član 13 → „Ugovor iz člana 27“

* **SOURCE:** U čl.13 (neprihvatljivi troškovi) tekst referencira troškove nastale „prije potpisivanja Ugovora iz člana 27 ove Odluke“.
* **OBSERVATION:** Ugovor o dodjeli subvencija je normativno uređen **članom 26**. **Član 27** uređuje praćenje realizacije / kontrolu (uključujući preusmjeravanje sredstava).
* **HANDLING:** Izvor se **ne** prepravlja. KN dokumentacija čuva izvornu referencu i anomaliju evidentira radi sljedivosti. Poslovno pravilo ostaje: troškovi prije potpisivanja Ugovora nisu prihvatljivi (vidi KN-BM-001 / KN-BR-073: izvor citira „člana 27“; BM ne prepravlja citat).
* **STATUS:** **SOURCE CROSS-REFERENCE ANOMALY** (ne popravljati izvor).
* Nova PO odluka **nije** potrebna; žensko/mladi i V1 scope nijesu pogođeni.

---

## 4.21 PO DECISION REGISTER (Q1–Q6)

> PO odluke su **tumačenje za kanonsku dokumentaciju i budući KN-BM**; nisu korekcija doslovnog teksta Odluke niti njenih obrazaca. Source anomalies iz 4.20 ostaju evidentirane.

| ID | Status | Source anomaly (zadržano) | PO decision (interpretacija) | BM blocker | Traceability |
|----|--------|---------------------------|------------------------------|------------|--------------|
| Q1 | RESOLVED / NON-BLOCKING | čl.19 → čl.13 (CROSS-REFERENCE) | Vrste subvencija: **čl.12**. čl.13 = troškovi. | NO | čl.12/19/13 → anomaly → PO Q1 → KN-BM READY FOR DERIVATION |
| Q2 | RESOLVED / NON-BLOCKING | čl.16 → čl.15 (CROSS-REFERENCE) | Dokumentacija prijave: **čl.14** + obrasci. | NO | čl.14/16/15 → anomaly → PO Q2 → KN-BM READY FOR DERIVATION |
| Q3 | RESOLVED / NON-BLOCKING | čl.31 „MMSP mladih“ (POTENTIAL SUBSTANTIVE AMBIGUITY) | Nije ograničenje korisnika na mlade. | NO | čl.31 → anomaly → PO Q3 → KN-BM READY FOR DERIVATION |
| Q4 | RESOLVED / NON-BLOCKING | P3 „Član 21 stav 2“ (CITATION-ONLY ERROR) | Kriterijumi: **čl.20** + P3; mehanizam: čl.21–22. | NO | čl.20/P3/21/22 → anomaly → PO Q4 → KN-BM READY FOR DERIVATION |
| Q5 | RESOLVED / NON-BLOCKING | čl.13 12 mjeseci / do 6 mjeseci (SOURCE AMBIGUITY) | Ugovor min. 12 mj.; subvencionisani trošak plate do 6 mj. | NO | čl.13/P4 → anomaly → PO Q5 → KN-BM READY FOR DERIVATION |
| Q6 | RESOLVED / NON-BLOCKING | čl.20 t.9 „......“ (TYPOGRAPHIC/EDITORIAL) | Kriterijum: „Održivost i dugoročni efekti“ (čl.20+P3+P2). | NO | čl.20/P3/P2 → anomaly → PO Q6 → KN-BM READY FOR DERIVATION |

---

# 5. Izvedena pravila prema poslovnom modelu

**Status:** PENDING LEGAL SOURCE ANALYSIS

Lanac koji će se uspostaviti nakon analize Odluke:

`pravna odredba → poslovno pravilo (KN-BM-001) → BR (KN-FS-001) → TS → implementacija → test`

Ovaj nacrt **ne** prenosi pravna pravila u BM.

---

# 6. Veza sa ostalom dokumentacijom

Dokumentaciona hijerarhija (nije prenos sadržaja Odluke):

```text
DK-DS-001 / METHODOLOGY
        ↓
KN-RG-001
        ↓
KN-PRO-001
        ↓
KN-BM-001
        ↓
KN-FS-001
        ↓
KN-TS-001
```

| Dokument | Putanja |
|----------|---------|
| KN-RG-001 | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` |
| KN-BM-001 | `docs/business-model/Business_Model_Konkursi.md` |
| KN-FS-001 | `docs/functional-specifications/Functional-Specification_Konkursi.md` |
| KN-TS-001 | `docs/technical-specifications/Technical-Specification_Konkursi.md` |

Postojeći tok ženskog preduzetništva predstavlja raniju implementaciju cjeline Konkursi i biće predmet posebnog naknadnog dokumentacionog usklađivanja. Do završetka tog procesa ne predstavlja automatski kanonski izvor poslovnih pravila za KN-BM/FS/TS.

---

# 7. Završne odredbe

**Status:** LEGAL EXTRACTION COMPLETE (NACRT)

Ovaj dokument sadrži registar članova `1–32`, uključujući završne odredbe akta (čl. 32) i metapodatke o stupanju na snagu.

---

**Kraj dokumenta KN-PRO-001 v0.1.4**
