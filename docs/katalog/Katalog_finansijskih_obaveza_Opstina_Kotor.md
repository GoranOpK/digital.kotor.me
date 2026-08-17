# Digital Kotor
# Katalog finansijskih obaveza Opštine Kotor
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-KF-001
**Modul:** e-Plaćanje
**Status dokumenta:** U IZRADI
**Verzija:** 0.4

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena kompletna struktura Kataloga. Definisana poglavlja, tabele i kolone. Katalog nije popunjen — čeka konačan spisak vrsta uplata. |
| 0.2 | 2026-07-27 | Popunjen Katalog na osnovu dostavljenog spiska vrsta uplata (Prihodi Opštine Kotor). Uneseno 17 kategorija i 41 pojedinačna vrsta uplate. Interna oznaka / šifra ostavljena prazna. |
| 0.3 | 2026-07-27 | Dopuna: uplatni računi kao referentni podaci iz Naredbe; Katalog je poslovni referentni dokument (nije šifrarnik ni implementacioni artefakt). |
| 0.4 | 2026-08-17 | Dokumentacioni corrective: oznaka EP-KF-001; namespace EP-*; pripadnost modulu e-Plaćanje. Bez izmjene sadržaja kataloga. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Katalog predstavlja **poslovni referentni dokument** finansijskih obaveza (vrsta uplata) i pripadajućih uplatnih računa koje modul e-Plaćanje podržava u okviru projekta Digital Kotor.

Katalog je jedini projektni izvor liste vrsta uplata za dokumentaciju i projektovanje (F-01, P-06).

**Katalog nije šifrarnik i ne predstavlja implementacioni artefakt.** U narednim fazama razvoja iz Kataloga će biti izveden odgovarajući šifrarnik koji će predstavljati konfiguracioni izvor podataka za aplikaciju.

---

# Status razvoja

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | U IZRADI |
| 2. Obavezujuća pravila unosa | USVOJENO |
| 3. Definicija pojmova u Katalogu | U IZRADI |
| 4. Struktura podataka – kategorije | POPUNJENO |
| 5. Struktura podataka – vrste uplata | STRUKTURA SPREMNA |
| 6. Pregled po kategorijama | POPUNJENO |
| 7. Zbirna tabela vrsta uplata | POPUNJENO |
| 8. Evidencija izmjena šifrarnika | STRUKTURA SPREMNA |

---

# Pravila upravljanja Katalogom

1. Katalog pripada modulu e-Plaćanja (EP-KF-001).

2. Unos vrsta uplata vrši se isključivo na osnovu spiska dostavljenog u okviru projekta.

3. Zabranjeno je samostalno dopunjavanje Kataloga tumačenjem propisa ili drugim spoljnim izvorima.

4. Brojevi uplatnih računa u Katalogu predstavljaju **referentne podatke** preuzete iz važeće Naredbe o načinu uplate javnih prihoda. Njihovo navođenje služi isključivo za dokumentovanje poslovnih podataka koji važe u trenutku izrade dokumentacije. To **ne predstavlja hardkodiranje** niti projektovanje implementacije.

5. Ako pravni osnov nije potvrđen, status pravnog osnova je **Potrebno pravno potvrditi** (P-07). Ne unose se pretpostavljeni pravni podaci.

6. Izmjene Kataloga evidentiraju se kroz PATCH i, po potrebi, u tabeli evidencije izmjena (poglavlje 8).

7. Aplikacioni šifrarnik (konfiguracioni izvor podataka) izvodi se iz Kataloga u narednim fazama razvoja; Katalog sam po sebi nije taj šifrarnik.

---

## Sadržaj

1. Uvod
2. Obavezujuća pravila unosa
3. Definicija pojmova u Katalogu
4. Struktura podataka – kategorije
5. Struktura podataka – vrste uplata
6. Pregled po kategorijama
7. Zbirna tabela vrsta uplata
8. Evidencija izmjena šifrarnika

---

# 1. Uvod

Katalog finansijskih obaveza prema Opštini Kotor sadrži:

* kategorije uplata (logička organizacija i prikaz);
* pojedinačne vrste uplata (jedinice koje sistem mora podržati);
* uplatne račune i ostale atribute propisane projektnim odlukama.

U skladu sa F-01:

* glavne numerisane cjeline predstavljaju **kategorije** uplata;
* svaka podstavka sa posebnim nazivom i računom predstavlja zasebnu **vrstu uplate**;
* sistem mora podržati svaku pojedinačnu vrstu uplate koja je obuhvaćena projektom.

---

# 2. Obavezujuća pravila unosa

| Oznaka | Pravilo |
|--------|---------|
| F-01 | Obuhvat V1: pojedinačne vrste uplata i uplatni računi iz projektnog spiska; računi u aplikaciji nisu hardkodirani. |
| P-06 | Katalog je jedan od pet osnovnih dokumenata razvoja modula. |
| P-07 | Pravni osnov se evidentira po propisanim poljima; bez nepotvrđenih podataka. |
| P-08 | Izvorni sistem / nadležni organ ostaje mjerodavan za podatke o obavezi. |
| UR-01 | Uplatni računi u Katalogu = referentni podaci iz Naredbe; Katalog ≠ šifrarnik; aplikacija koristi konfiguracioni izvor. |

**Status popunjenosti Kataloga:** POPUNJEN — 17 kategorija, 41 pojedinačna vrsta uplate (izvor: dostavljeni spisak Prihodi Opštine Kotor / Naredba o načinu uplate javnih prihoda, „Službeni list Crne Gore“, br. 006/25 od 29.01.2025.).

---

# 3. Definicija pojmova u Katalogu

| Pojam | Definicija |
|-------|------------|
| Kategorija uplate | Logička grupa vrsta uplata; služi isključivo za organizaciju i prikaz korisnicima (F-01). |
| Vrsta uplate | Pojedinačna finansijska obaveza sa posebnim nazivom i uplatnim računom; jedinica podrške sistema (F-01). |
| Uplatni račun | Broj računa za uplatu pripadajuće vrste uplate; **referentni podatak** preuzet iz važeće Naredbe o načinu uplate javnih prihoda (dokumentacioni zapis, ne hardkod). |
| Interna oznaka / šifra | Interni identifikator vrste uplate u projektu. |
| Status primjene | Status da li se vrsta uplate primjenjuje u modulu (npr. aktivna / neaktivna — vrijednosti će se usvojiti posebnom odlukom). |
| Pravni osnov | Veza na propis u skladu sa P-07 i Pravnim okvirom. |
| Ciljna grupa | Napomena o tome kome je obaveza namijenjena (građani, preduzetnici, pravna lica ili više grupa), kada je moguće utvrditi (F-01). |

---

# 4. Struktura podataka – kategorije

Tabela kategorija služi za logičku organizaciju. Redovi se popunjavaju nakon dostave spiska.

## 4.1 Definicija kolona

| Kolona | Opis | Obavezno |
|--------|------|----------|
| Oznaka kategorije | Interna oznaka kategorije | Da |
| Redni broj / numeracija | Broj ili oznaka cjeline iz izvornog spiska | Da, kada postoji |
| Naziv kategorije | Pun naziv kategorije | Da |
| Opis | Kratak opis namjene kategorije | Ne |
| Status | Status kategorije u Katalogu | Da |
| Napomena | Dodatne napomene | Ne |

## 4.2 Tabela kategorija

| Oznaka kategorije | Redni broj / numeracija | Naziv kategorije | Opis | Status | Napomena |
|-------------------|-------------------------|------------------|------|--------|----------|
| 1 | 1 | Prirez porezu na dohodak fizičkih lica | | | |
| 2 | 2 | Lokalni porezi | | | |
| 3 | 3 | Lokalne administrativne takse | | | |
| 4 | 4 | Lokalne komunalne takse | | | |
| 5 | 5 | Naknada za komunalno opremanje građevinskog zemljišta | | | |
| 6 | 6 | Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze) | | | |
| 7 | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | | | |
| 8 | 8 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze) | | | |
| 9 | 9 | Prihodi koje svojom djelatnošću ostvare opštinski organi, organizacije i službe | | | |
| 10 | 10 | Prihodi po osnovu kamata i kazni | | | |
| 11 | 11 | Boravišna taksa | | | |
| 12 | 12 | Turistička taksa | | | |
| 13 | 13 | Članski doprinos u turističkim organizacijama | | | |
| 14 | 14 | Troškovi postupka za slobodan pristup informacijama | | | |
| 15 | 15 | Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa | | | |
| 16 | 16 | Naknada troškova za premještanje vozila | | | |
| 17 | 17 | Naknada za ekonomsko iskorišćavanje kulturnih dobara | | | |

---

# 5. Struktura podataka – vrste uplata

Svaka pojedinačna vrsta uplate mora imati najmanje atribute iz F-01, uz polja pravnog osnova iz P-07.

## 5.1 Definicija kolona

| Kolona | Opis | Izvor pravila | Obavezno |
|--------|------|---------------|----------|
| Interna oznaka / šifra | Interni identifikator vrste uplate | F-01 | Da |
| Oznaka kategorije | Veza na kategoriju | F-01 | Da |
| Naziv kategorije | Naziv kategorije (za čitljivost) | F-01 | Da |
| Puni naziv vrste uplate | Zvanični naziv | F-01 | Da |
| Uplatni račun | Broj uplatnog računa | F-01 | Da |
| Status primjene | Status primjene u modulu | F-01 | Da |
| Ciljna grupa | Građani / preduzetnici / pravna lica / više grupa / nije utvrđeno | F-01 | Kada je moguće utvrditi |
| Naziv propisa | Zvanični naziv propisa | P-07 | Kada je potvrđen |
| Broj i godina službenog glasila | Oznaka službenog glasila | P-07 | Kada je potvrđen |
| Relevantni član propisa | Član / stav | P-07 | Kada je potvrđen |
| Nadležni organ | Organ nadležan za obavezu | P-07 | Kada je potvrđen |
| Napomene o primjeni | Napomene o primjeni | P-07 | Kada postoje |
| Status pravnog osnova | Potvrđen / Potrebno pravno potvrditi | P-07 | Da |
| Izvorni sistem / nadležni organ (napomena) | Napomena o mjerodavnom izvoru podataka | P-08 | Kada je poznato |
| Napomena | Ostale napomene | — | Ne |

---

# 6. Pregled po kategorijama

## 6.1 Prirez porezu na dohodak fizičkih lica

**Oznaka kategorije:** 1

**Redni broj / numeracija:** 1

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Prirez porezu na dohodak fizičkih lica. | 530-9228009-77 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 1.1 |

---

## 6.2 Lokalni porezi

**Oznaka kategorije:** 2

**Redni broj / numeracija:** 2

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Porez na nepokretnosti. | 530-9228014-62 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 2.1 |
| | Porez na promet nepokretnosti. | 530-9228020-44 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 2.2 |

---

## 6.3 Lokalne administrativne takse

**Oznaka kategorije:** 3

**Redni broj / numeracija:** 3

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Administrativne takse. | 530-9226777-87 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 3.1 |

---

## 6.4 Lokalne komunalne takse

**Oznaka kategorije:** 4

**Redni broj / numeracija:** 4

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Komunalna taksa za korišćenje prostora na javnim površinama, osim radi prodaje štampe, knjiga i drugih publikacija, proizvoda starih i umjetničkih zanata i domaće radinosti. | 530-92232405-51 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.1 |
| | Komunalna taksa za držanje (priređivanje) muzike u ugostiteljskim objektima, osim muzike koja se reprodukuje mehaničkim sredstvima (gramofon, magnetofon, radio, TV i sl.). | 530-92232494-75 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.2 |
| | Komunalna taksa za korišćenje vitrina radi izlaganja robe van poslovne prostorije. | 530-92232473-41 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.3 |
| | Komunalna taksa za korišćenje reklamnih panoa i bilborda, osim pored magistralnih i regionalnih puteva. | 530-92232517-06 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.4 |
| | Komunalna taksa za korišćenje prostora za parkiranje motornih i priključnih vozila, motocikala i bicikala na uređenim i obilježenim mjestima. | 530-92232468-56 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.5 |
| | Komunalna taksa za korišćenje slobodnih površina za kampove, postavljanje šatora ili drugih objekata privremenog karaktera. | 530-92232538-40 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.6 |
| | Komunalna taksa za držanje plovnih postrojenja, plovnih naprava i drugih objekata na vodi. | 530-92232431-70 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.7 |
| | Komunalna taksa za držanje restorana i drugih ugostiteljskih objekata i zabavnih objekata na vodi. | 530-92232447-22 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.8 |
| | Ostale komunalne takse. | 530-9223247-07 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 4.9 |

---

## 6.5 Naknada za komunalno opremanje građevinskog zemljišta

**Oznaka kategorije:** 5

**Redni broj / numeracija:** 5

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Naknada za komunalno opremanje građevinskog zemljišta za pravna lica. | 530-92223906-37 | | pravna lica | Potrebno pravno potvrditi | Numeracija iz izvora: 5.1 |
| | Naknada za komunalno opremanje građevinskog zemljišta za preduzetnike. | 530-92223911-22 | | preduzetnici | Potrebno pravno potvrditi | Numeracija iz izvora: 5.2 |
| | Naknada za komunalno opremanje građevinskog zemljišta za građane. | 530-92223932-56 | | građani | Potrebno pravno potvrditi | Numeracija iz izvora: 5.3 |

---

## 6.6 Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze)

**Oznaka kategorije:** 6

**Redni broj / numeracija:** 6

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Naknada za korišćenje građevinskog zemljišta za pravna lica. | 530-92223927-71 | | pravna lica | Potrebno pravno potvrditi | Numeracija iz izvora: 6.1 |
| | Naknada za korišćenje građevinskog zemljišta za preduzetnike. | 530-92223948-08 | | preduzetnici | Potrebno pravno potvrditi | Numeracija iz izvora: 6.2 |
| | Naknada za korišćenje građevinskog zemljišta za građane. | 530-92223953-90 | | građani | Potrebno pravno potvrditi | Numeracija iz izvora: 6.3 |

---

## 6.7 Naknada za korišćenje opštinskih i nekategorisanih puteva

**Oznaka kategorije:** 7

**Redni broj / numeracija:** 7

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Naknada za vanredni prevoz. | 530-92262320-31 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.1 |
| | Naknada za postavljanje natpisa na putu i pored puta. | 530-92262329-04 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.2 |
| | Naknada za zakup putnog zemljišta. | 530-92262321-28 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.3 |
| | Naknada za zakup drugog zemljišta koje pripada upravljaču puta. | 530-92262322-25 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.4 |
| | Naknada za priključenje prilaznog puta na javni put. | 530-92262323-22 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.5 |
| | Naknada za postavljanje cjevovoda, vodovoda, kanalizacije, električnih, telefonskih i telegrafskih vodova na javnom putu i sl. | 530-92262324-19 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.6 |
| | Naknada za izgradnju komercijalnih objekata kojima je omogućen pristup sa puta. | 530-92262326-13 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.7 |
| | Naknada za korišćenje komercijalnih objekata kojima je omogućen pristup sa puta. | 530-92262327-10 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 7.8 |

---

## 6.8 Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze)

**Oznaka kategorije:** 8

**Redni broj / numeracija:** 8

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za pravna lica. | 530-92262296-06 | | pravna lica | Potrebno pravno potvrditi | Numeracija iz izvora: 8.1 |
| | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za preduzetnike. | 530-92262303-82 | | preduzetnici | Potrebno pravno potvrditi | Numeracija iz izvora: 8.2 |
| | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za građane. | 530-92262319-34 | | građani | Potrebno pravno potvrditi | Numeracija iz izvora: 8.3 |

---

## 6.9 Prihodi koje svojom djelatnošću ostvare opštinski organi, organizacije i službe

**Oznaka kategorije:** 9

**Redni broj / numeracija:** 9

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Prihodi opštinskih organa, organizacija i službi. | 530-9226121-18 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 8.1 |
| | Ostali opštinski prihodi. | 530-9226228-85 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 8.2 |

---

## 6.10 Prihodi po osnovu kamata i kazni

**Oznaka kategorije:** 10

**Redni broj / numeracija:** 10

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Prihodi po osnovu kamata za neblagovremeno plaćene lokalne prihode. | 530-92262371-72 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 9.1 |
| | Novčane kazne za koje je pokrenut prekršajni postupak prije 1. septembra 2011. godine. | 530-92262387-24 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 9.2 |

---

## 6.11 Boravišna taksa

**Oznaka kategorije:** 11

**Redni broj / numeracija:** 11

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Boravišna taksa. | 530-9223205-36 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 10.1 |

---

## 6.12 Turistička taksa

**Oznaka kategorije:** 12

**Redni broj / numeracija:** 12

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Turistička taksa. | 530-9223206-33 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 11.1 |

---

## 6.13 Članski doprinos u turističkim organizacijama

**Oznaka kategorije:** 13

**Redni broj / numeracija:** 13

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Članski doprinos u turističkim organizacijama. | 530-9223207-30 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 12.1 |

---

## 6.14 Troškovi postupka za slobodan pristup informacijama

**Oznaka kategorije:** 14

**Redni broj / numeracija:** 14

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Troškovi postupka za slobodan pristup informacijama. | 530-92262334-86 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 13.1 |

---

## 6.15 Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa

**Oznaka kategorije:** 15

**Redni broj / numeracija:** 15

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa. | 530-92262335-83 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 14.1 |

---

## 6.16 Naknada troškova za premještanje vozila

**Oznaka kategorije:** 16

**Redni broj / numeracija:** 16

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Naknada troškova za premještanje vozila. | 530-92262336-80 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 15.1 |

---

## 6.17 Naknada za ekonomsko iskorišćavanje kulturnih dobara

**Oznaka kategorije:** 17

**Redni broj / numeracija:** 17

| Interna oznaka / šifra | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Status pravnog osnova | Napomena |
|------------------------|-------------------------|---------------|-----------------|--------------|------------------------|----------|
| | Naknada za ekonomsko iskorišćavanje kulturnih dobara. | 530-92262337-77 | | | Potrebno pravno potvrditi | Numeracija iz izvora: 16.1 |

---

# 7. Zbirna tabela vrsta uplata

Zbirna tabela obuhvata sve pojedinačne vrste uplata u projektu, nezavisno od prikaza po kategorijama.

| Interna oznaka / šifra | Oznaka kategorije | Naziv kategorije | Puni naziv vrste uplate | Uplatni račun | Status primjene | Ciljna grupa | Naziv propisa | Broj i godina službenog glasila | Relevantni član propisa | Nadležni organ | Napomene o primjeni | Status pravnog osnova | Izvorni sistem / nadležni organ (napomena) | Napomena |
|------------------------|-------------------|------------------|-------------------------|---------------|-----------------|--------------|---------------|---------------------------------|-------------------------|----------------|---------------------|------------------------|--------------------------------------------|----------|
| | 1 | Prirez porezu na dohodak fizičkih lica | Prirez porezu na dohodak fizičkih lica. | 530-9228009-77 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 1.1 |
| | 2 | Lokalni porezi | Porez na nepokretnosti. | 530-9228014-62 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 2.1 |
| | 2 | Lokalni porezi | Porez na promet nepokretnosti. | 530-9228020-44 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 2.2 |
| | 3 | Lokalne administrativne takse | Administrativne takse. | 530-9226777-87 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 3.1 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za korišćenje prostora na javnim površinama, osim radi prodaje štampe, knjiga i drugih publikacija, proizvoda starih i umjetničkih zanata i domaće radinosti. | 530-92232405-51 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.1 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za držanje (priređivanje) muzike u ugostiteljskim objektima, osim muzike koja se reprodukuje mehaničkim sredstvima (gramofon, magnetofon, radio, TV i sl.). | 530-92232494-75 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.2 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za korišćenje vitrina radi izlaganja robe van poslovne prostorije. | 530-92232473-41 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.3 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za korišćenje reklamnih panoa i bilborda, osim pored magistralnih i regionalnih puteva. | 530-92232517-06 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.4 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za korišćenje prostora za parkiranje motornih i priključnih vozila, motocikala i bicikala na uređenim i obilježenim mjestima. | 530-92232468-56 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.5 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za korišćenje slobodnih površina za kampove, postavljanje šatora ili drugih objekata privremenog karaktera. | 530-92232538-40 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.6 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za držanje plovnih postrojenja, plovnih naprava i drugih objekata na vodi. | 530-92232431-70 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.7 |
| | 4 | Lokalne komunalne takse | Komunalna taksa za držanje restorana i drugih ugostiteljskih objekata i zabavnih objekata na vodi. | 530-92232447-22 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.8 |
| | 4 | Lokalne komunalne takse | Ostale komunalne takse. | 530-9223247-07 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 4.9 |
| | 5 | Naknada za komunalno opremanje građevinskog zemljišta | Naknada za komunalno opremanje građevinskog zemljišta za pravna lica. | 530-92223906-37 | | pravna lica | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 5.1 |
| | 5 | Naknada za komunalno opremanje građevinskog zemljišta | Naknada za komunalno opremanje građevinskog zemljišta za preduzetnike. | 530-92223911-22 | | preduzetnici | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 5.2 |
| | 5 | Naknada za komunalno opremanje građevinskog zemljišta | Naknada za komunalno opremanje građevinskog zemljišta za građane. | 530-92223932-56 | | građani | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 5.3 |
| | 6 | Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze) | Naknada za korišćenje građevinskog zemljišta za pravna lica. | 530-92223927-71 | | pravna lica | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 6.1 |
| | 6 | Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze) | Naknada za korišćenje građevinskog zemljišta za preduzetnike. | 530-92223948-08 | | preduzetnici | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 6.2 |
| | 6 | Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze) | Naknada za korišćenje građevinskog zemljišta za građane. | 530-92223953-90 | | građani | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 6.3 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za vanredni prevoz. | 530-92262320-31 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.1 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za postavljanje natpisa na putu i pored puta. | 530-92262329-04 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.2 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za zakup putnog zemljišta. | 530-92262321-28 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.3 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za zakup drugog zemljišta koje pripada upravljaču puta. | 530-92262322-25 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.4 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za priključenje prilaznog puta na javni put. | 530-92262323-22 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.5 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za postavljanje cjevovoda, vodovoda, kanalizacije, električnih, telefonskih i telegrafskih vodova na javnom putu i sl. | 530-92262324-19 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.6 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za izgradnju komercijalnih objekata kojima je omogućen pristup sa puta. | 530-92262326-13 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.7 |
| | 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | Naknada za korišćenje komercijalnih objekata kojima je omogućen pristup sa puta. | 530-92262327-10 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 7.8 |
| | 8 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze) | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za pravna lica. | 530-92262296-06 | | pravna lica | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 8.1 |
| | 8 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze) | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za preduzetnike. | 530-92262303-82 | | preduzetnici | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 8.2 |
| | 8 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze) | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za građane. | 530-92262319-34 | | građani | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 8.3 |
| | 9 | Prihodi koje svojom djelatnošću ostvare opštinski organi, organizacije i službe | Prihodi opštinskih organa, organizacija i službi. | 530-9226121-18 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 8.1 |
| | 9 | Prihodi koje svojom djelatnošću ostvare opštinski organi, organizacije i službe | Ostali opštinski prihodi. | 530-9226228-85 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 8.2 |
| | 10 | Prihodi po osnovu kamata i kazni | Prihodi po osnovu kamata za neblagovremeno plaćene lokalne prihode. | 530-92262371-72 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 9.1 |
| | 10 | Prihodi po osnovu kamata i kazni | Novčane kazne za koje je pokrenut prekršajni postupak prije 1. septembra 2011. godine. | 530-92262387-24 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 9.2 |
| | 11 | Boravišna taksa | Boravišna taksa. | 530-9223205-36 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 10.1 |
| | 12 | Turistička taksa | Turistička taksa. | 530-9223206-33 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 11.1 |
| | 13 | Članski doprinos u turističkim organizacijama | Članski doprinos u turističkim organizacijama. | 530-9223207-30 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 12.1 |
| | 14 | Troškovi postupka za slobodan pristup informacijama | Troškovi postupka za slobodan pristup informacijama. | 530-92262334-86 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 13.1 |
| | 15 | Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa | Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa. | 530-92262335-83 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 14.1 |
| | 16 | Naknada troškova za premještanje vozila | Naknada troškova za premještanje vozila. | 530-92262336-80 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 15.1 |
| | 17 | Naknada za ekonomsko iskorišćavanje kulturnih dobara | Naknada za ekonomsko iskorišćavanje kulturnih dobara. | 530-92262337-77 | | | | | | | | Potrebno pravno potvrditi | | Numeracija iz izvora: 16.1 |

---

# 8. Evidencija izmjena šifrarnika

Tabela služi za praćenje izmjena uplatnih računa i drugih atributa u Katalogu nakon početnog unosa (npr. izmjena važećih propisa).

Napomena: naziv poglavlja zadržan iz strukture dokumenta. Izmjene se evidentiraju na nivou **Kataloga** (poslovni referentni dokument). Aplikacioni šifrarnik, kada bude izveden, ažurira se zasebno u skladu sa tehničkom specifikacijom.

| Datum | Interna oznaka / šifra | Polje | Stara vrijednost | Nova vrijednost | Razlog / osnov | PATCH / odluka | Napomena |
|-------|------------------------|-------|------------------|-----------------|----------------|----------------|----------|
| — | — | — | — | — | — | — | — |

*Tabela je prazna.*

---

# Završne napomene

1. Katalog je **popunjen** sa 17 kategorija i **41** pojedinačnom vrstom uplate.
2. Izvor podataka: dostavljeni spisak Prihodi Opštine Kotor (Naredba o načinu uplate javnih prihoda, „Službeni list Crne Gore“, br. 006/25 od 29.01.2025.).
3. Brojevi uplatnih računa u Katalogu su **referentni podaci** iz važeće Naredbe; navođenje nije hardkodiranje niti implementacioni dizajn.
4. Katalog je **poslovni referentni dokument**, nije šifrarnik i nije implementacioni artefakt.
5. Kolona **Interna oznaka / šifra** ostaje prazna do posebne projektne odluke.
6. Numeracija stavki iz izvora prenijeta je u kolonu **Napomena** (bez korišćenja kao interne šifre).
7. Pravni osnov za sve unesene vrste označen je kao **Potrebno pravno potvrditi**.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana struktura Kataloga (verzija 0.1). Tabele i kolone definisane; sadržaj vrsta uplata nije unesen. |
| 2026-07-27 | Verzija 0.2 — Popunjen Katalog: 17 kategorija, 41 vrsta uplate. Interna oznaka / šifra prazna. Pravni osnov: Potrebno pravno potvrditi. |
| 2026-07-27 | Verzija 0.3 — Usvojeno pravilo UR-01: uplatni računi = referentni podaci; Katalog ≠ šifrarnik / implementacioni artefakt. |
| 2026-08-17 | Verzija 0.4 — Dokumentacioni corrective: oznaka EP-KF-001; namespace EP-*; pripadnost modulu e-Plaćanje. Bez izmjene 17 kategorija, 41 vrste uplate, računa, pravnih osnova ili internih šifara. |
