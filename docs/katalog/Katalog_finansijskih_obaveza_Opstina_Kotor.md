# Digital Kotor
# Katalog finansijskih obaveza Opštine Kotor
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-KF-001
**Modul:** e-Plaćanje
**Status dokumenta:** U IZRADI
**Verzija:** 0.5

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena kompletna struktura Kataloga. Definisana poglavlja, tabele i kolone. Katalog nije popunjen — čeka konačan spisak vrsta uplata. |
| 0.2 | 2026-07-27 | Popunjen Katalog na osnovu dostavljenog spiska vrsta uplata (Prihodi Opštine Kotor). Uneseno 17 kategorija i 41 pojedinačna vrsta uplate. Interna oznaka / šifra ostavljena prazna. |
| 0.3 | 2026-07-27 | Dopuna: uplatni računi kao referentni podaci iz Naredbe; Katalog je poslovni referentni dokument (nije šifrarnik ni implementacioni artefakt). |
| 0.4 | 2026-08-17 | Dokumentacioni corrective: oznaka EP-KF-001; namespace EP-*; pripadnost modulu e-Plaćanje. Bez izmjene sadržaja kataloga. |
| 0.5 | 2026-08-20 | Korak 6 ontologija: 17 vrsta plaćanja → 41 račun. Brojevi računa sačuvani. Ciljne grupe označene kao LEGACY / PARTIAL MAPPING. Bez izmišljenog mapiranja. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Katalog je **poslovni referentni dokument** vrsta plaćanja i računa koje V1 e-Plaćanje podržava.

Kanonska ontologija (Korak 6, 2026-08-20):

* **Vrsta plaćanja** — *šta* korisnik plaća (17);
* **Račun** — *gdje* se sredstva uplaćuju (41).

**SUPERSEDE:** „17 kategorija + 41 vrsta uplate, 1 račun po vrsti“.

Katalog **nije** šifrarnik i **nije** implementacioni artefakt (UR-01). Aplikacioni šifrarnik se izvodi kasnije.

---

# Status razvoja

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod i ontologija | USVOJENO (Korak 6) |
| 2. Obavezujuća pravila | USVOJENO |
| 3. Definicija pojmova | USVOJENO |
| 4. Pregled 17 vrsta plaćanja | POPUNJENO |
| 5. Vrste i računi (detalj) | POPUNJENO; mapping OPEN |
| 6. Zbirna tabela 41 računa | POPUNJENO |
| 7. Availability / korisničke kategorije | OPEN PRE-PRODUCTION |
| 8. Evidencija izmjena | STRUKTURA SPREMNA |

---

# Pravila upravljanja Katalogom

1. Katalog pripada modulu e-Plaćanja (EP-KF-001).
2. Unos se vrši isključivo na osnovu projektnog spiska. Zabranjeno je samostalno dopunjavanje tumačenjem propisa.
3. Brojevi računa su **referentni podaci** iz Naredbe. Navođenje nije hardkodiranje.
4. Pravni osnov: **Potrebno pravno potvrditi** dok nije potvrđen (P-07).
5. Korišćeni račun se ne briše; deaktivira se. Promjena broja = novi zapis.
6. Konačno mapiranje na korisničke kategorije (Korak 6 filter) **nije** usvojeno. Postojeće ciljne grupe = **LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING**.
7. Ne pretvarati „građani“ u resident/non-resident. Ne pretvarati „pravna lica“ u sve legal forms.

---

## Sadržaj

1. Uvod i ontologija
2. Obavezujuća pravila
3. Definicija pojmova
4. Pregled 17 vrsta plaćanja
5. Vrste plaćanja i pripadajući računi
6. Zbirna tabela 41 računa
7. Availability
8. Evidencija izmjena

---

# 1. Uvod i ontologija

Izvor: dostavljeni spisak Prihodi Opštine Kotor / Naredba o načinu uplate javnih prihoda, „Službeni list Crne Gore“, br. 006/25 od 29.01.2025.

**Aritmetika V1:** 17 vrsta plaćanja, 41 račun.

Jedna vrsta može imati 1..N računa.

Kanonski filter (EP-BM-001): `korisnik → dozvoljena vrsta plaćanja → dozvoljeni račun(i)`.

Račun ne može proširiti pravo koje korisnik nema na nivou vrste.

Bez aktivnog, validnog i dozvoljenog računa vrsta se korisniku ne prikazuje.

---

# 2. Obavezujuća pravila

| Oznaka | Pravilo |
|--------|---------|
| F-01 | 17 vrsta plaćanja i 41 račun iz projektnog spiska; računi u aplikaciji nisu hardkodirani. |
| P-06 | Katalog je jedan od osnovnih dokumenata razvoja. |
| P-07 | Pravni osnov po propisanim poljima; bez nepotvrđenih podataka. |
| P-08 | Izvorni sistem / nadležni organ ostaje mjerodavan za stvarnu obavezu. V1 ne preuzima zaduženja. |
| UR-01 | Računi u Katalogu = referentni podaci; Katalog ≠ šifrarnik. |

**Status popunjenosti:** 17 vrsta, 41 račun uneseni. Interna šifra vrste/računa = prazna do posebne odluke. Status aktivnosti = **TBD / REQUIRES VALIDATION**. Poziv na broj / model / šifra plaćanja / osnovna svrha po računu = **TBD / REQUIRES VALIDATION** osim gdje je već bilo u prethodnoj verziji (nije bilo).

---

# 3. Definicija pojmova

| Pojam | Definicija |
|-------|------------|
| Vrsta plaćanja | Šta korisnik plaća. Kanonska jedinica kataloga V1 (17). |
| Račun | Gdje se sredstva uplaćuju. Pripada tačno jednoj vrsti plaćanja (41). |
| Availability | Pravilo koje vrste/račune smije koristiti data korisnička kategorija. Konačno mapiranje OPEN. |
| LEGACY / PARTIAL MAPPING | Raniji unos „ciljna grupa“ (građani / preduzetnici / pravna lica) na nivou nekadašnje „vrste uplate“. Nije Korak 6 filter. |

**LEGACY / SUPERSEDED — DO NOT USE FOR NEW EP V1 CONTENT:**

* kategorija uplate (kao 17 kanonskih jedinica);
* vrsta uplate (kao 41 kanonska jedinica sa 1 računom).

---

# 4. Pregled 17 vrsta plaćanja

| RB | Naziv vrste plaćanja | Broj računa | Availability (Korak 6) | Napomena |
|----|----------------------|------------:|------------------------|----------|
| 1 | Prirez porezu na dohodak fizičkih lica | 1 | OPEN | |
| 2 | Lokalni porezi | 2 | OPEN | |
| 3 | Lokalne administrativne takse | 1 | OPEN | |
| 4 | Lokalne komunalne takse | 9 | OPEN | |
| 5 | Naknada za komunalno opremanje građevinskog zemljišta | 3 | OPEN; legacy ciljne grupe na računima | |
| 6 | Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze) | 3 | OPEN; legacy ciljne grupe na računima | |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 8 | OPEN | |
| 8 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze) | 3 | OPEN; legacy ciljne grupe na računima | |
| 9 | Prihodi koje svojom djelatnošću ostvare opštinski organi, organizacije i službe | 2 | OPEN | |
| 10 | Prihodi po osnovu kamata i kazni | 2 | OPEN | |
| 11 | Boravišna taksa | 1 | OPEN | |
| 12 | Turistička taksa | 1 | OPEN | |
| 13 | Članski doprinos u turističkim organizacijama | 1 | OPEN | |
| 14 | Troškovi postupka za slobodan pristup informacijama | 1 | OPEN | |
| 15 | Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa | 1 | OPEN | |
| 16 | Naknada troškova za premještanje vozila | 1 | OPEN | |
| 17 | Naknada za ekonomsko iskorišćavanje kulturnih dobara | 1 | OPEN | |
| **Σ** | | **41** | | |

Status aktivnosti svake vrste: **TBD / REQUIRES VALIDATION**.

Dozvoljene korisničke kategorije na nivou vrste: **OPEN PRE-PRODUCTION** (Korak 6 stavka 13).

---

# 5. Vrste plaćanja i pripadajući računi

Kolone računa:

| Kolona | Pravilo |
|--------|---------|
| Broj računa | Referentni podatak; ne mijenjati u ovom corrective-u |
| Naziv / opis računa | Raniji „puni naziv vrste uplate“ |
| Status | TBD / REQUIRES VALIDATION |
| Model / šifra plaćanja | TBD / REQUIRES VALIDATION |
| Poziv na broj | TBD / REQUIRES VALIDATION (system / user / optional / N/A) |
| Osnovna svrha | TBD / REQUIRES VALIDATION (sistem formira prema vrsti — EP-BM-001) |
| Availability override | nije usvojen |
| Legacy ciljna grupa | samo gdje je već postojala; **ne** Korak 6 mapping |
| Pravni osnov | Potrebno pravno potvrditi |
| Napomena | numeracija iz izvora |

## 5.1 Vrsta 1 — Prirez porezu na dohodak fizičkih lica

**Broj računa:** 1 (sistem može automatski odabrati kada bude dozvoljen)

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-9228009-77 | Prirez porezu na dohodak fizičkih lica. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 1.1 |

## 5.2 Vrsta 2 — Lokalni porezi

**Broj računa:** 2 (korisnik bira između dozvoljenih)

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-9228014-62 | Porez na nepokretnosti. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 2.1 |
| 530-9228020-44 | Porez na promet nepokretnosti. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 2.2 |

## 5.3 Vrsta 3 — Lokalne administrativne takse

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-9226777-87 | Administrativne takse. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 3.1 |

## 5.4 Vrsta 4 — Lokalne komunalne takse

**Broj računa:** 9

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92232405-51 | Komunalna taksa za korišćenje prostora na javnim površinama, osim radi prodaje štampe, knjiga i drugih publikacija, proizvoda starih i umjetničkih zanata i domaće radinosti. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.1 |
| 530-92232494-75 | Komunalna taksa za držanje (priređivanje) muzike u ugostiteljskim objektima, osim muzike koja se reprodukuje mehaničkim sredstvima (gramofon, magnetofon, radio, TV i sl.). | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.2 |
| 530-92232473-41 | Komunalna taksa za korišćenje vitrina radi izlaganja robe van poslovne prostorije. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.3 |
| 530-92232517-06 | Komunalna taksa za korišćenje reklamnih panoa i bilborda, osim pored magistralnih i regionalnih puteva. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.4 |
| 530-92232468-56 | Komunalna taksa za korišćenje prostora za parkiranje motornih i priključnih vozila, motocikala i bicikala na uređenim i obilježenim mjestima. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.5 |
| 530-92232538-40 | Komunalna taksa za korišćenje slobodnih površina za kampove, postavljanje šatora ili drugih objekata privremenog karaktera. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.6 |
| 530-92232431-70 | Komunalna taksa za držanje plovnih postrojenja, plovnih naprava i drugih objekata na vodi. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.7 |
| 530-92232447-22 | Komunalna taksa za držanje restorana i drugih ugostiteljskih objekata i zabavnih objekata na vodi. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.8 |
| 530-9223247-07 | Ostale komunalne takse. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 4.9 |

Napomena: naziv „Ostale komunalne takse“ je naziv stavke iz izvornog spiska Naredbe, **nije** usvojeno generičko korisničko `Ostalo`.

## 5.5 Vrsta 5 — Naknada za komunalno opremanje građevinskog zemljišta

**Broj računa:** 3

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92223906-37 | Naknada za komunalno opremanje građevinskog zemljišta za pravna lica. | TBD | pravna lica — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 5.1 |
| 530-92223911-22 | Naknada za komunalno opremanje građevinskog zemljišta za preduzetnike. | TBD | preduzetnici — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 5.2 |
| 530-92223932-56 | Naknada za komunalno opremanje građevinskog zemljišta za građane. | TBD | građani — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 5.3 |

## 5.6 Vrsta 6 — Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze)

**Broj računa:** 3

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92223927-71 | Naknada za korišćenje građevinskog zemljišta za pravna lica. | TBD | pravna lica — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 6.1 |
| 530-92223948-08 | Naknada za korišćenje građevinskog zemljišta za preduzetnike. | TBD | preduzetnici — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 6.2 |
| 530-92223953-90 | Naknada za korišćenje građevinskog zemljišta za građane. | TBD | građani — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 6.3 |

## 5.7 Vrsta 7 — Naknada za korišćenje opštinskih i nekategorisanih puteva

**Broj računa:** 8

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92262320-31 | Naknada za vanredni prevoz. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.1 |
| 530-92262329-04 | Naknada za postavljanje natpisa na putu i pored puta. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.2 |
| 530-92262321-28 | Naknada za zakup putnog zemljišta. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.3 |
| 530-92262322-25 | Naknada za zakup drugog zemljišta koje pripada upravljaču puta. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.4 |
| 530-92262323-22 | Naknada za priključenje prilaznog puta na javni put. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.5 |
| 530-92262324-19 | Naknada za postavljanje cjevovoda, vodovoda, kanalizacije, električnih, telefonskih i telegrafskih vodova na javnom putu i sl. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.6 |
| 530-92262326-13 | Naknada za izgradnju komercijalnih objekata kojima je omogućen pristup sa puta. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.7 |
| 530-92262327-10 | Naknada za korišćenje komercijalnih objekata kojima je omogućen pristup sa puta. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 7.8 |

## 5.8 Vrsta 8 — Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze)

**Broj računa:** 3

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92262296-06 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za pravna lica. | TBD | pravna lica — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 8.1 |
| 530-92262303-82 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za preduzetnike. | TBD | preduzetnici — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 8.2 |
| 530-92262319-34 | Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za građane. | TBD | građani — LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING | Potrebno pravno potvrditi | Numeracija iz izvora: 8.3 |

## 5.9 Vrsta 9 — Prihodi koje svojom djelatnošću ostvare opštinski organi, organizacije i službe

**Broj računa:** 2

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-9226121-18 | Prihodi opštinskih organa, organizacija i službi. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 8.1 |
| 530-9226228-85 | Ostali opštinski prihodi. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 8.2 |

## 5.10 Vrsta 10 — Prihodi po osnovu kamata i kazni

**Broj računa:** 2

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92262371-72 | Prihodi po osnovu kamata za neblagovremeno plaćene lokalne prihode. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 9.1 |
| 530-92262387-24 | Novčane kazne za koje je pokrenut prekršajni postupak prije 1. septembra 2011. godine. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 9.2 |

## 5.11 Vrsta 11 — Boravišna taksa

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-9223205-36 | Boravišna taksa. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 10.1 |

## 5.12 Vrsta 12 — Turistička taksa

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-9223206-33 | Turistička taksa. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 11.1 |

## 5.13 Vrsta 13 — Članski doprinos u turističkim organizacijama

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-9223207-30 | Članski doprinos u turističkim organizacijama. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 12.1 |

## 5.14 Vrsta 14 — Troškovi postupka za slobodan pristup informacijama

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92262334-86 | Troškovi postupka za slobodan pristup informacijama. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 13.1 |

## 5.15 Vrsta 15 — Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92262335-83 | Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 14.1 |

## 5.16 Vrsta 16 — Naknada troškova za premještanje vozila

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92262336-80 | Naknada troškova za premještanje vozila. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 15.1 |

## 5.17 Vrsta 17 — Naknada za ekonomsko iskorišćavanje kulturnih dobara

**Broj računa:** 1

| Broj računa | Naziv / opis | Status | Legacy ciljna grupa | Status pravnog osnova | Napomena |
|-------------|--------------|--------|---------------------|------------------------|----------|
| 530-92262337-77 | Naknada za ekonomsko iskorišćavanje kulturnih dobara. | TBD | — | Potrebno pravno potvrditi | Numeracija iz izvora: 16.1 |

---

# 6. Zbirna tabela 41 računa

| RB vrste | Naziv vrste plaćanja | Broj računa | Naziv / opis računa | Legacy ciljna grupa |
|---------:|----------------------|-------------|---------------------|---------------------|
| 1 | Prirez porezu na dohodak fizičkih lica | 530-9228009-77 | Prirez porezu na dohodak fizičkih lica. | — |
| 2 | Lokalni porezi | 530-9228014-62 | Porez na nepokretnosti. | — |
| 2 | Lokalni porezi | 530-9228020-44 | Porez na promet nepokretnosti. | — |
| 3 | Lokalne administrativne takse | 530-9226777-87 | Administrativne takse. | — |
| 4 | Lokalne komunalne takse | 530-92232405-51 | Komunalna taksa za korišćenje prostora na javnim površinama… | — |
| 4 | Lokalne komunalne takse | 530-92232494-75 | Komunalna taksa za držanje (priređivanje) muzike… | — |
| 4 | Lokalne komunalne takse | 530-92232473-41 | Komunalna taksa za korišćenje vitrina… | — |
| 4 | Lokalne komunalne takse | 530-92232517-06 | Komunalna taksa za korišćenje reklamnih panoa i bilborda… | — |
| 4 | Lokalne komunalne takse | 530-92232468-56 | Komunalna taksa za korišćenje prostora za parkiranje… | — |
| 4 | Lokalne komunalne takse | 530-92232538-40 | Komunalna taksa za korišćenje slobodnih površina za kampove… | — |
| 4 | Lokalne komunalne takse | 530-92232431-70 | Komunalna taksa za držanje plovnih postrojenja… | — |
| 4 | Lokalne komunalne takse | 530-92232447-22 | Komunalna taksa za držanje restorana… na vodi. | — |
| 4 | Lokalne komunalne takse | 530-9223247-07 | Ostale komunalne takse. | — |
| 5 | Naknada za komunalno opremanje građevinskog zemljišta | 530-92223906-37 | … za pravna lica. | LEGACY: pravna lica |
| 5 | Naknada za komunalno opremanje građevinskog zemljišta | 530-92223911-22 | … za preduzetnike. | LEGACY: preduzetnici |
| 5 | Naknada za komunalno opremanje građevinskog zemljišta | 530-92223932-56 | … za građane. | LEGACY: građani |
| 6 | Naknada za korišćenje građevinskog zemljišta (zaostale) | 530-92223927-71 | … za pravna lica. | LEGACY: pravna lica |
| 6 | Naknada za korišćenje građevinskog zemljišta (zaostale) | 530-92223948-08 | … za preduzetnike. | LEGACY: preduzetnici |
| 6 | Naknada za korišćenje građevinskog zemljišta (zaostale) | 530-92223953-90 | … za građane. | LEGACY: građani |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262320-31 | Naknada za vanredni prevoz. | — |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262329-04 | Naknada za postavljanje natpisa na putu i pored puta. | — |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262321-28 | Naknada za zakup putnog zemljišta. | — |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262322-25 | Naknada za zakup drugog zemljišta… | — |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262323-22 | Naknada za priključenje prilaznog puta… | — |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262324-19 | Naknada za postavljanje cjevovoda… | — |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262326-13 | Naknada za izgradnju komercijalnih objekata… | — |
| 7 | Naknada za korišćenje opštinskih i nekategorisanih puteva | 530-92262327-10 | Naknada za korišćenje komercijalnih objekata… | — |
| 8 | Naknada za izgradnju i održavanje lokalnih puteva… | 530-92262296-06 | … za pravna lica. | LEGACY: pravna lica |
| 8 | Naknada za izgradnju i održavanje lokalnih puteva… | 530-92262303-82 | … za preduzetnike. | LEGACY: preduzetnici |
| 8 | Naknada za izgradnju i održavanje lokalnih puteva… | 530-92262319-34 | … za građane. | LEGACY: građani |
| 9 | Prihodi opštinskih organa, organizacija i službi | 530-9226121-18 | Prihodi opštinskih organa, organizacija i službi. | — |
| 9 | Prihodi opštinskih organa, organizacija i službi | 530-9226228-85 | Ostali opštinski prihodi. | — |
| 10 | Prihodi po osnovu kamata i kazni | 530-92262371-72 | Prihodi po osnovu kamata… | — |
| 10 | Prihodi po osnovu kamata i kazni | 530-92262387-24 | Novčane kazne… prije 1. septembra 2011. | — |
| 11 | Boravišna taksa | 530-9223205-36 | Boravišna taksa. | — |
| 12 | Turistička taksa | 530-9223206-33 | Turistička taksa. | — |
| 13 | Članski doprinos u turističkim organizacijama | 530-9223207-30 | Članski doprinos u turističkim organizacijama. | — |
| 14 | Troškovi postupka za slobodan pristup informacijama | 530-92262334-86 | Troškovi postupka za slobodan pristup informacijama. | — |
| 15 | Taksa na elektroakustične i akustične uređaje nakon 24h | 530-92262335-83 | Taksa na upotrebu elektroakustičnih i akustičnih uređaja… | — |
| 16 | Naknada troškova za premještanje vozila | 530-92262336-80 | Naknada troškova za premještanje vozila. | — |
| 17 | Naknada za ekonomsko iskorišćavanje kulturnih dobara | 530-92262337-77 | Naknada za ekonomsko iskorišćavanje kulturnih dobara. | — |

---

# 7. Availability

**Status:** OPEN PRE-PRODUCTION DEPENDENCY

Konačno mapiranje 17 vrsta / 41 računa na korisničke kategorije (fizičko lice Rezident/Nerezident, Preduzetnik, konkretni pravni oblici) **nije** usvojeno.

9 računa u vrstama 5, 6 i 8 imaju naslijeđenu kolonu ciljne grupe (`građani` / `preduzetnici` / `pravna lica`). To je **LEGACY / PARTIAL MAPPING — REQUIRES KORAK 6 MAPPING**.

Nije izvršeno:

* građani → resident ili non-resident;
* pravna lica → svi pravni oblici;
* bilo koje drugo automatsko mapiranje.

---

# 8. Evidencija izmjena šifrarnika / kataloga

Izmjene se evidentiraju na nivou Kataloga. Aplikacioni šifrarnik, kada bude izveden, ažurira se zasebno.

| Datum | Vrsta / račun | Polje | Stara vrijednost | Nova vrijednost | Razlog / osnov | PATCH / odluka | Napomena |
|-------|---------------|-------|------------------|-----------------|----------------|----------------|----------|
| 2026-08-20 | ontologija | model | 17 kategorija + 41 vrsta uplate | 17 vrsta plaćanja + 41 račun | Korak 6 CLOSED | EP-KF 0.5 | Brojevi računa neizmijenjeni |

---

# Završne napomene

1. **17 PAYMENT TYPES = 17**
2. **41 ACCOUNTS = 41**
3. Svi brojevi računa iz verzije 0.4 sačuvani.
4. Interna šifra ostaje prazna do posebne odluke.
5. Pravni osnov: Potrebno pravno potvrditi.
6. USER CATEGORY MAPPING = PARTIAL / OPEN.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana struktura Kataloga (verzija 0.1). Tabele i kolone definisane; sadržaj vrsta uplata nije unesen. |
| 2026-07-27 | Verzija 0.2 — Popunjen Katalog: 17 kategorija, 41 vrsta uplate. Interna oznaka / šifra prazna. Pravni osnov: Potrebno pravno potvrditi. |
| 2026-07-27 | Verzija 0.3 — Usvojeno pravilo UR-01: uplatni računi = referentni podaci; Katalog ≠ šifrarnik / implementacioni artefakt. |
| 2026-08-17 | Verzija 0.4 — Dokumentacioni corrective: oznaka EP-KF-001; namespace EP-*; pripadnost modulu e-Plaćanje. Bez izmjene 17 kategorija, 41 vrste uplate, računa, pravnih osnova ili internih šifara. |
| 2026-08-20 | Verzija 0.5 — Korak 6 ontologija: 17 vrsta plaćanja → 41 račun. Stara ontologija SUPERSEDE. Brojevi računa sačuvani. Mapping nije izmišljen. |
