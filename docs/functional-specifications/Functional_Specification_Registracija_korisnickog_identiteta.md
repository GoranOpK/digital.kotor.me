# Digital Kotor
# Functional Specification
## Funkcionalnost: Registracija i korisnički identitet Platforme Digital Kotor

**Oznaka dokumenta:** DK-FS-002
**Naziv:** Funkcionalna specifikacija registracije i korisničkog identiteta Platforme Digital Kotor
**Namespace / vlasništvo:** DK-* (platformski sloj Digital Kotora)
**Status dokumenta:** USVOJENO
**Verzija:** 1.0.0
**Datum:** 2026-09-04

Povezani dokumenti:

* Poslovni model (SSOT): **DK-BM-002** v1.0.0 USVOJENO — `docs/business-model/Business_Model_Registracija_korisnickog_identiteta.md`
* Registar oznaka: **DK-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Digital-Kotor.md`
* Dokumentacioni standard: **DK-DS-001** — `docs/reference/Digital-Kotor-Documentation-Standard.md`

Ovaj dokument **nije** DK-FS-001 (Obavještenja).

Dokument **DK-UC-002** **nije** kreiran.

Dokument **DK-TS-002** **nije** kreiran.

Ovaj dokument **ne** mijenja `DK-BM-002`.

Ovaj dokument **ne** tvrdi da je opisano ponašanje već implementirano na Platformi.

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-09-03 | Otvoren dokumentacioni paket Functional Specification. Inicijalna struktura i funkcionalno ponašanje izvedeno iz `DK-BM-002` v1.0.0. Status dokumenta: U IZRADI. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 1. Korekcija predmeta: potvrda unosa e-mail adrese i korisničke lozinke ponovnim unosom. Poglavlje 1 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 2. Korekcija izvora: ovaj dokument = zahtijevano funkcionalno ponašanje Platforme. Poglavlje 2 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 3. Sadržaj bez izmjena. Poglavlje 3 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 4. Sadržaj bez izmjena. Poglavlje 4 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 5. Sadržaj bez izmjena. Poglavlje 5 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 6. Korekcija §6.2: Vrsta identifikacionog dokumenta * za Nerezidenta; izbor obavezan; izabrani identifikacioni podatak obavezan. Poglavlje 6 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 7. Korekcija redosljeda ključnih polja: Država prebivališta * ostaje obavezna za Nerezidenta prema Poglavlju 6, bez fiksirane UI pozicije poslije PIB-a. Poglavlje 7 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 8. Terminološka korekcija: Vrsta identifikacionog dokumenta ovlašćenog lica *. Poglavlje 8 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 9. Korekcije §9.2: Vrsta identifikacionog dokumenta zastupnika *; JMB zastupnika = 13 cifara + kontrolna cifra. OPEN FS DECISION o JMB-u zastupnika zatvorena. Poglavlje 9 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 10. Sadržaj bez izmjena. Poglavlje 10 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 11. Korekcija §11.3: dok e-mail adresa nije verifikovana, Platforma ne omogućava pristup funkcijama koje zahtijevaju verifikovan nalog. Poglavlje 11 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 12. Dopuna: prikaz i ponovno sakrivanje lozinke; podrazumijevano sakriveno. OPEN FS DECISION o prikazu/sakrivanju lozinke zatvorena. Poglavlje 12 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 13. Dopuna: za `+382` korisnik unosi broj bez `+382` i bez početne nule. Poglavlje 13 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 14. Sadržaj bez izmjena. Poglavlje 14 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 15. Korekcija tačke 4: verifikacija e-mail adrese nakon uspješnog slanja, prema Poglavlju 11. Poglavlje 15 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 16. Korekcija §16.3: zahtijevano ponašanje dopune i nastavka funkcije; konkretna UI/tehnička realizacija pripada DK-TS-002. OPEN FS DECISION o UI mehanizmu dopune zatvorena. Poglavlje 16 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 17. Sadržaj bez izmjena. Poglavlje 17 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 18. Sadržaj bez izmjena. Poglavlje 18 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 19. Dodati prihvatni kriterijumi 13–18. Kriterijumi 1–12 bez izmjena. Poglavlje 19 evidentirano kao PO usvojeno. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled Poglavlja 20. Uklonjena OPEN FS DECISION o mapiranju KK / KN / EP funkcija na nedostajuća polja; mapiranje pripada modulskim FS dokumentima. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled: usvojena jedna zajednička kontrolisana lista država za Državu prebivališta i Državu izdavanja pasoša; izvor/struktura/čuvanje u DK-TS-002. OPEN FS DECISION o sadržaju liste država zatvorena. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-03 | 2026-09-03 | PO pregled: PIB Dijela stranog privrednog društva = 8 cifara + ISO 7064 Modul 11,10; CRPS registracioni broj zaseban, sa funkcionalnom validacijom propisane oznake i numeričkog rednog broja. OPEN FS DECISION o PIB/CRPS zatvorena. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 2026-09-04 | 2026-09-04 | PO pregled Poglavlja 20. Usvojeno pravilo validacionih poruka: crnogorski jezik; ista poruka klijent/server; bez engleskih framework poruka; kanonska poruka za e-mail. OPEN FS DECISIONS = NONE. Status dokumenta ostaje U IZRADI. Verzija ostaje 0.1.0. |
| 1.0.0 | 2026-09-04 | Status / closeout: Product Owner usvojio DK-FS-002 kao cjelinu. Status dokumenta: USVOJENO. Funkcionalni sadržaj neizmijenjen. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument odgovara na pitanje:

> Koje **posmatrano ponašanje** Digital Kotor mora obezbijediti za registraciju i korisnički identitet?

Dokument **ne** odgovara kako se to ponašanje tehnički implementira.

Jedini izvor poslovnih pravila je **DK-BM-002** v1.0.0.

---

# 1. Predmet i granica

DK-FS-002 definiše funkcionalno ponašanje Platforme potrebno da realizuje `DK-BM-002`.

Predmet:

- ulazak u registraciju;
- izbor Vrste subjekta;
- uslovno prikazivanje i obaveznost polja;
- funkcionalne validacije;
- tok registracije;
- potvrda unosa e-mail adrese i korisničke lozinke ponovnim unosom;
- verifikacija e-mail adrese;
- funkcionalni tok dopune postojećeg profila;
- funkcionalne granice prema KK / KN / EP.

Van predmeta ovog dokumenta:

- DB tabele, kolone, ENUM implementacija;
- Laravel modeli, kontroleri, middleware;
- migracije i servisna arhitektura;
- tehnički mapping legacy baze;
- deployment i produkcione komande.

To pripada budućem `DK-TS-002`.

---

# 2. Izvori i sljedivost

| Izvor | Uloga |
|-------|--------|
| `DK-BM-002` v1.0.0 | Jedini SSOT poslovnih pravila. |
| Ovaj dokument | Zahtijevano funkcionalno ponašanje Platforme. |
| `DK-TS-002` | Nije kreiran. Tehnička realizacija. |
| `DK-UC-002` | Nije kreiran. Nije pretpostavka ovog FS-a. |

Ako se FS i BM razlikuju, **BM pobjedjuje**.

---

# 3. Funkcionalni akteri

| Akter | Funkcionalna uloga |
|-------|--------------------|
| **Korisnik koji se registruje** | Otvara nalog prema kanonskom modelu. |
| **Postojeći korisnik** | Prijavljuje se na postojeći nalog. Ne ponavlja registraciju. Dopunjava profil samo kada mu nedostaje podatak potreban za konkretnu funkcionalnost. |
| **Platforma** | Prikazuje formu, primjenjuje uslovna polja i validacije, prima registraciju, zahtijeva verifikaciju e-maila, zahtijeva dopunu profila samo pred odgovarajućom funkcionalnošću. |

Konkursi, e-Plaćanje i Kalendar kulture **nisu** akteri registracije.

---

# 4. Ulazak u registraciju

1. Korisnik otvara tok registracije na Platformi.
2. Platforma prikazuje izbor Vrste subjekta prije ostalih grana podataka.
3. Dok Vrsta subjekta nije izabrana, grane Fizičko lice, Pravno lice i Dio stranog privrednog društva nisu aktivne.
4. Registracija se ne završava dok sva aktivna obavezna polja nisu popunjena i validna.
5. Poslovna akcija završetka se prikazuje kao: **Registruj se**.

---

# 5. Izbor Vrste subjekta

Platforma nudi **tačno tri** top-level izbora, ovim redosljedom:

1. Fizičko lice
2. Pravno lice
3. Dio stranog privrednog društva

Preduzetnik **nije** četvrti izbor Vrste subjekta.

Dio stranog privrednog društva **nije** stavka Pravnog oblika.

U jednom trenutku aktivna je samo jedna Vrsta subjekta.

---

# 6. Fizičko lice

Kada je izabrano Fizičko lice, Platforma prikazuje pitanje:

**Da li ste registrovani kao preduzetnik?**

Vrijednosti, ovim redosljedom:

1. Da
2. Ne

Odgovor **Da** aktivira granu Preduzetnika (poglavlje 7).

Odgovor **Ne** aktivira granu običnog Fizičkog lica (poglavlje 6.4).

## 6.1 Status rezidentnosti

Za Fizičko lice, uključujući Preduzetnika, obavezno je:

**Status rezidentnosti \***

- Rezident
- Nerezident

Pravno lice i Dio stranog privrednog društva **nemaju** ovo polje. Polje se ne prikazuje na tim granama.

## 6.2 Identifikacija

### Rezident

- **JMB \***
- tačno 13 cifara;
- validira se kontrolna cifra.

Polje **Država prebivališta** se ne prikazuje.

### Nerezident

**Vrsta identifikacionog dokumenta \***

- JMB
- Broj pasoša

Izbor je obavezan. Izabrani identifikacioni podatak je obavezan.

Ako je JMB:

- tačno 13 cifara;
- validira se kontrolna cifra.

Ako je pasoš:

- **Broj pasoša \***

**Država prebivališta \*** je obavezna. Vrijednost se bira iz kontrolisane liste prema pravilu u Poglavlju 10.

## 6.3 Naselja

Naselje se **ne** prikazuje i **nije** dio registracije.

## 6.4 Obično Fizičko lice

Kada je odgovor na pitanje o preduzetništvu **Ne**, aktivna obavezna polja su:

- Ime \*
- Prezime \*
- Status rezidentnosti \*
- odgovarajući identifikacioni podatak \*
- Država prebivališta \* samo kada je Nerezident
- zatim zajednička polja iz poglavlja 10–14

---

# 7. Preduzetnik

Preduzetnik ostaje Fizičko lice.

Primjenjuju se ista pravila rezidentnosti i identifikacije iz poglavlja 6.

Redosljed ključnih polja:

1. Ime \*
2. Prezime \*
3. odgovarajući identifikacioni podatak \*
4. Naziv preduzetnika \*
5. PIB \*
6. zatim zajednička polja iz poglavlja 10–14

Za Nerezidenta se dodatno primjenjuje obavezno polje Država prebivališta \* prema pravilima iz Poglavlja 6.

**PIB Preduzetnika:**

- obavezan;
- tačno 8 cifara;
- validna kontrolna cifra prema važećem pravilu usvojenom u `DK-BM-002`.

Algoritam kontrolne cifre nije predmet ovog FS-a.

---

# 8. Pravno lice

Pravno lice **nema** Status rezidentnosti. Polje se ne prikazuje.

## 8.1 Pravni oblik

**Pravni oblik \*** je zatvorena lista, tačno:

1. Ortačko društvo (OD)
2. Komanditno društvo (KD)
3. Društvo sa ograničenom odgovornošću (DOO)
4. Akcionarsko društvo (AD)
5. Nevladino udruženje
6. Nevladina fondacija
7. Sportska organizacija

Ne prikazuju se i ne prihvataju:

- Ustanove;
- Ostalo;
- generički NVO;
- Dio stranog privrednog društva.

## 8.2 Polja

- Pravni oblik \*
- Puni naziv pravnog lica \*
- PIB \*
- Ime ovlašćenog lica \*
- Prezime ovlašćenog lica \*

**Vrsta identifikacionog dokumenta ovlašćenog lica \***

- JMB
- Broj pasoša

Ako je JMB:

- **JMB ovlašćenog lica \***
- tačno 13 cifara;
- validira se kontrolna cifra.

Ako je pasoš:

- **Broj pasoša ovlašćenog lica \***
- **Država izdavanja pasoša \*** — kontrolisana lista

**PIB Pravnog lica:**

- obavezan;
- tačno 8 cifara;
- validna kontrolna cifra prema važećem pravilu usvojenom u `DK-BM-002`.

Zatim zajednička polja iz poglavlja 10–14.

---

# 9. Dio stranog privrednog društva

Posebna top-level Vrsta subjekta.

**Nema** Status rezidentnosti. Polje se ne prikazuje.

## 9.1 Polja subjekta

- Naziv stranog privrednog društva \*
- Naziv dijela stranog privrednog društva u Crnoj Gori \*
- PIB \*
- CRPS registracioni broj \*

PIB je obavezan. Predstavlja poreski identifikacioni broj dodijeljen u Crnoj Gori.

**PIB \***

- obavezan;
- tačno 8 cifara;
- validna kontrolna cifra prema ISO 7064 Modul 11,10.

PIB je odvojen podatak od CRPS registracionog broja.

Tehnička realizacija algoritma kontrolne cifre pripada `DK-TS-002`.

**CRPS registracioni broj \***

- obavezan;
- mora odgovarati važećem formatu registracionog broja koji CRPS dodjeljuje Dijelu stranog privrednog društva;
- funkcionalna validacija mora provjeriti propisanu identifikacionu oznaku za Dio stranog privrednog društva i propisani numerički redni broj.

PIB i CRPS registracioni broj su dva odvojena podatka i ne smiju se poistovjećivati.

Konkretna tehnička realizacija validacije pripada `DK-TS-002`.

## 9.2 Zastupnik

- Ime zastupnika \*
- Prezime zastupnika \*

**Vrsta identifikacionog dokumenta zastupnika \***

- JMB
- Broj pasoša

Ako je JMB:

- **JMB zastupnika \***
- tačno 13 cifara;
- validira se kontrolna cifra.

Ako je pasoš:

- **Broj pasoša zastupnika \***
- **Država izdavanja pasoša \***

Zatim zajednička polja iz poglavlja 10–14.

Ulica i broj + Grad predstavljaju adresu **dijela stranog privrednog društva u Crnoj Gori**.

---

# 10. Zajednička polja i validacije

1. Svako aktivno polje označeno sa `*` mora biti popunjeno i validno prije završetka registracije.
2. Uslovno polje je obavezno samo dok je njegova grana aktivna.
3. Neaktivna grana se ne prikazuje kao obavezna i njeni podaci se ne zahtijevaju.
4. Dugme **Registruj se** ne završava registraciju dok aktivna obavezna polja nisu validna.

Platforma koristi jednu zajedničku kontrolisanu listu država za polja Država prebivališta i Država izdavanja pasoša. Lista sadrži Crnu Goru i druge države, a korisnik bira jednu vrijednost iz liste i ne unosi naziv države slobodnim tekstom. Kanonski izvor, standard naziva, kodovi država, struktura i tehničko čuvanje liste definišu se u `DK-TS-002`.

Platforma prikazuje validacione poruke korisniku na crnogorskom jeziku i koristi terminologiju definisanu u `DK-FS-002`. Za isto validaciono pravilo i isto polje koristi se ista korisnička poruka bez obzira da li je validacija izvršena na klijentskoj ili serverskoj strani. Korisniku se ne prikazuju podrazumijevane framework poruke na engleskom jeziku.

Validaciona poruka mora jasno identifikovati podatak ili uslov koji nije ispunjen i omogućiti korisniku da razumije šta treba ispraviti.

Za nepodudaranje e-mail adresa kanonska poruka je:

**E-mail adrese se ne podudaraju.**

Tačan centralizovani katalog ostalih validacionih poruka i njegova tehnička realizacija definišu se u `DK-TS-002`, uz obavezno poštovanje naziva polja i funkcionalnih pravila iz `DK-FS-002`.

---

# 11. E-mail, potvrda i verifikacija

## 11.1 E-mail

- obavezan;
- validan format e-mail adrese;
- jedinstven na Platformi;
- predstavlja korisničko ime za prijavu.

## 11.2 Potvrdi e-mail

- ponovni unos;
- mora odgovarati vrijednosti E-mail;
- nije zaseban poslovni podatak i ne čuva se kao takav.

Ako se vrijednosti ne podudaraju, Platforma prikazuje:

**E-mail adrese se ne podudaraju.**

## 11.3 Verifikacija e-mail adrese

Nakon uspješnog slanja registracije primjenjuje se verifikacija e-mail adrese.

Dok e-mail adresa nije verifikovana, Platforma ne omogućava korisniku pristup funkcijama koje zahtijevaju verifikovan nalog.

Tehnička realizacija poruke/linka pripada `DK-TS-002`.

---

# 12. Korisnička lozinka

- **Korisnička lozinka \***
- **Potvrdi korisničku lozinku \*** — ponovni unos; mora odgovarati lozinci; ne čuva se kao zaseban poslovni podatak.

Sigurnosna pravila jačine lozinke **nisu** nova poslovna pravila ovog paketa. Primjenjuju se postojeća sigurnosna pravila Platforme. Ovaj FS ih ne mijenja i ne izmišlja nova.

Za polja Korisnička lozinka i Potvrdi korisničku lozinku Platforma omogućava korisniku prikaz i ponovno sakrivanje unesene vrijednosti. Podrazumijevano su vrijednosti sakrivene.

---

# 13. Mobilni telefon

**Broj mobilnog telefona \*** je jedno kompozitno UI polje sa izborom međunarodnog pozivnog broja.

Kada je izabran međunarodni pozivni broj za Crnu Goru (`+382`), korisnik u dio polja za broj telefona unosi broj bez `+382` i bez početne nule.

`DK-BM-002` ne propisuje poseban broj cifara za Crnu Goru. Ovaj FS ne uvodi takvo pravilo.

Tehnički način čuvanja pripada `DK-TS-002`.

---

# 14. Adresa

- **Ulica i broj \***
- vrijednost `bb` je dozvoljena kada objekat nema broj;
- **Grad \*** je posebno obavezno polje;
- Grad **nije** ograničen na teritoriju Opštine Kotor;
- **Naselje se ne uvodi**.

Za Dio stranog privrednog društva adresa je adresa dijela u Crnoj Gori.

---

# 15. Slanje registracije

1. Korisnik pokreće **Registruj se**.
2. Platforma prima registraciju samo ako su sva aktivna obavezna polja popunjena i validna.
3. E-mail i lozinka moraju odgovarati potvrdama unosa.
4. Nakon uspješnog slanja registracije primjenjuje se verifikacija e-mail adrese prema Poglavlju 11.
5. Ne kreira se drugi nalog samo zbog kasnije dopune profila.

---

# 16. Dopuna profila postojećeg korisnika

Ovo poglavlje razrađuje zatvoreno poslovno pravilo `DK-BM-002` §11 i §16. **Ne** donosi novu poslovnu odluku.

## 16.1 Zatvoreno poslovno ponašanje

Platforma mora obezbijediti sljedeće posmatrano ponašanje:

1. Postojeći korisnik **ne** ponavlja registraciju zbog uvođenja ovog modela.
2. Postojeći nalog ostaje.
3. Istorija naloga ostaje.
4. Postojeći korisnik može **normalno da se prijavi**.
5. Nepotpunost postojećeg profila **nije** globalna blokada naloga.
6. Dopuna se zahtijeva **tek prije korišćenja funkcionalnosti** za koju nedostaje obavezni podatak.
7. Nedostajući podatak se unosi na **postojećem** profilu, ne kroz novi nalog.

## 16.2 Funkcionalni model

1. Prijava postojećeg korisnika ne zahtijeva kompletiranje novog kanonskog profila.
2. Funkcije koje ne zavise od nedostajućeg podatka ostaju dostupne.
3. Kada korisnik pokrene funkciju kojoj nedostaje obavezni podatak, Platforma zahtijeva dopunu **samo tih** podataka na postojećem profilu.
4. Nakon uspješne dopune, korisnik može nastaviti tu funkciju bez nove registracije.
5. Platforma **ne** prikazuje globalni gate koji blokira cijeli nalog samo zbog nepotpunog profila.

## 16.3 Šta ovaj FS ne bira

`DK-BM-002` ne određuje konkretan UI mehanizam dopune. `DK-FS-002` zahtijeva da Platforma, kada korisnik pokrene funkciju za koju mu nedostaje obavezni podatak, omogući dopunu potrebnih podataka na postojećem profilu i nakon uspješne dopune omogući nastavak korišćenja te funkcije bez nove registracije.

Konkretna UI i tehnička realizacija ovog toka pripada `DK-TS-002`.

Ovaj FS **ne** mapira koje konkretne KK / KN / EP funkcije zahtijevaju koje polje. To pripada modulskim FS dokumentima, uz obavezno poštovanje `DK-BM-002`.

---

# 17. Funkcionalne zabrane

Platforma pri registraciji i dopuni profila **ne**:

- nudi Preduzetnika kao Vrstu subjekta;
- nudi Status rezidentnosti za Pravno lice ili Dio stranog privrednog društva;
- nudi Ustanove, Ostalo, generički NVO ili Dio stranog društva kao Pravni oblik;
- ograničava Grad na Opštinu Kotor;
- uvodi Naselje;
- zahtijeva ponovnu registraciju postojećeg korisnika;
- blokira prijavu samo zbog nepotpunog profila.

---

# 18. Granice prema modulima

## 18.1 Platforma (DK)

Ovaj FS definiše platformski identitet i registraciju.

## 18.2 Kalendar kulture (KK)

Uloge, ovlašćenja i procesi Kalendara kulture **ne** mijenjaju Vrstu subjekta niti platformski identitet.

## 18.3 Konkursi (KN)

Podnosilac / Podnositeljka, prihvatljivost, `applicant_type`, `registration_form` i konkursni uslovi (uključujući prebivalište/sjedište na teritoriji Opštine Kotor) **ne** mijenjaju platformski identitet i **ne** ograničavaju polje Grad pri registraciji.

## 18.4 e-Plaćanje (EP)

Dostupnost vrste plaćanja **ne** definiše platformski identitet.

Ovaj dokument **ne** mijenja KK, KN ni EP dokumentaciju.

---

# 19. Prihvatni kriterijumi

Format: Ako / Kada / Onda.

1. Ako korisnik otvori registraciju, onda vidi tačno tri Vrste subjekta: Fizičko lice, Pravno lice, Dio stranog privrednog društva.
2. Ako izabere Fizičko lice, onda vidi pitanje o preduzetništvu sa redoslijedom Da, Ne.
3. Ako je Fizičko lice Rezident, onda je JMB obavezan sa 13 cifara i kontrolnom cifrom, a Država prebivališta se ne prikazuje.
4. Ako je Fizičko lice Nerezident, onda bira JMB ili Broj pasoša i Država prebivališta je obavezna.
5. Ako je Preduzetnik, onda ostaje Fizičko lice i PIB je obavezan sa 8 cifara i kontrolnom cifrom.
6. Ako je Pravno lice, onda nema Status rezidentnosti i Pravni oblik je zatvorena lista od tačno sedam stavki.
7. Ako je Dio stranog privrednog društva, onda nema Status rezidentnosti, a PIB i CRPS su dva obavezna odvojena polja.
8. Ako se E-mail i Potvrdi e-mail ne podudaraju, onda se prikazuje: „E-mail adrese se ne podudaraju.“
9. Ako postojeći korisnik ima nepotpun profil, onda se može prijaviti i nije globalno blokiran.
10. Ako postojeći korisnik pokrene funkciju kojoj nedostaje podatak, onda Platforma zahtijeva dopunu postojećeg profila, ne novu registraciju.
11. Ako korisnik unosi Grad pri registraciji, onda unos nije ograničen na Opštinu Kotor i Naselje se ne traži.
12. Ako objekat nema broj, onda je `bb` dozvoljeno u polju Ulica i broj.
13. Ako je za identifikacioni dokument zastupnika Dijela stranog privrednog društva izabran JMB, onda je JMB zastupnika obavezan, ima tačno 13 cifara i validnu kontrolnu cifru.
14. Ako je za identifikacioni dokument ovlašćenog lica Pravnog lica ili zastupnika Dijela stranog privrednog društva izabran Broj pasoša, onda su Broj pasoša i Država izdavanja pasoša obavezni.
15. Ako korisnik uspješno pošalje registraciju, onda se primjenjuje verifikacija e-mail adrese, a dok e-mail adresa nije verifikovana Platforma ne omogućava pristup funkcijama koje zahtijevaju verifikovan nalog.
16. Ako korisnik unosi Korisničku lozinku i Potvrdu korisničke lozinke, onda vrijednosti moraju da se podudaraju, a oba polja podrazumijevano sakrivaju vrijednost i omogućavaju njen prikaz i ponovno sakrivanje.
17. Ako je za Broj mobilnog telefona izabran međunarodni pozivni broj Crne Gore `+382`, onda korisnik broj unosi bez `+382` i bez početne nule.
18. Ako postojeći korisnik uspješno dopuni podatke potrebne za pokrenutu funkciju, onda može nastaviti korišćenje te funkcije bez nove registracije.
19. Ako se registruje Dio stranog privrednog društva, onda je PIB obavezan, ima tačno 8 cifara i validnu kontrolnu cifru prema ISO 7064 Modul 11,10, a CRPS registracioni broj je zaseban obavezan podatak koji mora odgovarati važećem formatu registracionog broja za Dio stranog privrednog društva.

---

# 20. Otvorene funkcionalne stavke

Usvojena poslovna pravila `DK-BM-002` **nisu** otvorena pitanja.

## OPEN BUSINESS QUESTIONS

**NONE**

## OPEN FS DECISIONS

**NONE**

## RESERVED FOR TS

- storage mapping, ENUM, DB schema;
- Laravel modeli, kontroleri, middleware;
- mapiranje postojećeg `users.user_type`;
- tehničko čuvanje Vrste subjekta, Pravnog oblika, rezidentnosti, identifikatora i telefona;
- kanonski izvor, standard naziva, eventualni kodovi, struktura i tehničko čuvanje kontrolisane liste država;
- tehnička verifikacija e-maila i jedinstvenost;
- algoritam kontrolne cifre JMB / PIB gdje je validacija usvojena;
- tehnička realizacija validacije CRPS registracionog broja Dijela stranog privrednog društva;
- centralizovani katalog validacionih poruka i njegova tehnička realizacija;
- migracije i transitional / null stanja;
- očuvanje postojećih veza drugih modula.

---

# 21. Sljedivost prema DK-BM-002

Ova matrica evidentira sljedivost `DK-FS-002` prema `DK-BM-002` v1.0.0. Ne uvodi nova poslovna ni funkcionalna pravila.

| FS poglavlje | DK-BM-002 |
|--------------|-----------|
| 1 | §1 |
| 2 | `DK-BM-002` v1.0.0 kao poslovni SSOT (svrha dokumenta BM-a). Ovo poglavlje nije BM §2. |
| 3 | §2 |
| 5–9, 16 | §3 |
| 4 | §4.1 koraci 1, 4 i 5; §5; §10 tač. 1 i 16 |
| 4–15 | §11.2 |
| 5 | §5 |
| 6 | §6 |
| 7 | §7 |
| 8 | §8 |
| 9 | §9; §17 A (format CRPS registracionog broja; tačna funkcionalna validacija PIB-a Dijela stranog privrednog društva) |
| 10 | §10 tač. 1, 2 i 16; §17 A (validacione poruke; funkcionalno ponašanje kontrolisanih listi) |
| 11 | §4.1 koraci 3 i 6; §4.3; §10 tač. 3–7; §17 A (validacione poruke) |
| 12 | §4.1 korak 3; §10 tač. 8–9; §17 A (konkretno UI ponašanje: prikaz i sakrivanje lozinke) |
| 13 | §10 tač. 10; §17 A (konkretno UI ponašanje: unos broja uz +382) |
| 14 | §9.2 (adresa dijela u Crnoj Gori); §10 tač. 11–15 |
| 15 | §4.1 koraci 4–6; §10 tač. 1 i 16; §11.1; §11.2 |
| 16 | §4.2; §4.3; §11.1; §11.3; §16; §17 A (funkcionalni tok dopune profila u okviru usvojenog poslovnog pravila iz poglavlja 11) |
| 17 | §5; §6.2; §8.1; §9; §10 tač. 14 i 15; §11.1; §11.3; §16 |
| 18 | §12; §13; §14; §15 |
| 19 | izvedeno iz navedenih BM poglavlja i iz FS pravila ovlašćenih §17 A; ne uvodi nova pravila |
| 20 | §17 (usvojena BM pravila nisu otvorena); preostalo iz §17 B i §17 C rezervisano za TS |

---

**Kraj dokumenta DK-FS-002 v1.0.0**
