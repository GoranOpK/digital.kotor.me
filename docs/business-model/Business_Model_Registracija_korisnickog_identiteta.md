# Digital Kotor
# Business Model
## Funkcionalnost: Registracija i korisnički identitet Platforme Digital Kotor

**Oznaka dokumenta:** DK-BM-002
**Naziv:** Poslovni model registracije i korisničkog identiteta Platforme Digital Kotor
**Namespace / vlasništvo:** DK-* (platformski sloj Digital Kotora)
**Status dokumenta:** USVOJENO
**Verzija:** 1.0.0
**Datum:** 2026-09-03

Povezani dokumenti:

* Registar oznaka: **DK-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Digital-Kotor.md`
* Dokumentacioni standard: **DK-DS-001** — `docs/reference/Digital-Kotor-Documentation-Standard.md`

Ovaj dokument **nije** DK-BM-001 (Obavještenja).

Dokumenti `DK-UC-002`, `DK-FS-002` i `DK-TS-002` **nisu** kreirani ovim korakom.

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-09-03 | Otvoren dokumentacioni paket. Evidentiran PO-usvojeni platformski poslovni model registracije i korisničkog identiteta, uključujući pravilo da postojeći korisnici ne ponavljaju registraciju. Status dokumenta: U IZRADI. |
| 0.1.1 | 2026-09-03 | PO sadržajni pregled: akteri registracije; razdvajanje potvrde unosa i verifikacije e-maila; relokacija rezidentnosti Dijela stranog privrednog društva; PIB vs CRPS; granica prema Kalendaru kulture; arhitektura slojeva; prijava postojećeg korisnika i dopuna profila; uklonjen rječnik poslovnih pojmova. Status dokumenta ostaje U IZRADI. |
| 1.0.0 | 2026-09-03 | Status / closeout: Product Owner usvojio DK-BM-002 kao cjelinu. Status dokumenta: USVOJENO. Poslovni sadržaj neizmijenjen. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument je **poslovni SSOT** kanonskog modela registracije i korisničkog identiteta Platforme digital.kotor.me.

Dokument opisuje ciljni poslovni model. Ne opisuje trenutnu implementaciju, ne definiše tehničku realizaciju i ne predstavlja Functional Specification ni Technical Specification.

Poslovna pravila u ovom dokumentu su Product Owner-usvojena. Dokument kao cjelina ima status **USVOJENO**.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| 1. Predmet i granica | USVOJENO |
| 2. Akteri | USVOJENO |
| 3. Entiteti | USVOJENO |
| 4. Procesi | USVOJENO |
| 5. Vrsta subjekta | USVOJENO |
| 6. Fizičko lice | USVOJENO |
| 7. Preduzetnik | USVOJENO |
| 8. Pravno lice | USVOJENO |
| 9. Dio stranog privrednog društva | USVOJENO |
| 10. Zajednička poslovna pravila | USVOJENO |
| 11. Novi korisnici i postojeći korisnici | USVOJENO |
| 12. Granica prema Konkursima | USVOJENO |
| 13. Granica prema e-Plaćanju | USVOJENO |
| 14. Granica prema Kalendaru kulture | USVOJENO |
| 15. Poslovna arhitektura slojeva | USVOJENO |
| 16. Legacy / backward compatibility granica | USVOJENO; tehnička migracija nije predmet BM-a |
| 17. Otvorene stavke | USVOJENO; ostaju samo FS / TS / MIGRATION razrade |

---

# Pravila upravljanja Business Modelom

1. DK-BM-002 je zvanična poslovna specifikacija registracije i korisničkog identiteta Platforme Digital Kotor.

2. Usvojena poslovna pravila u ovom dokumentu predstavljaju jedini izvor istine (Single Source of Truth) za platformski korisnički identitet.

3. Poglavlja i pravila sa statusom PO-USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu ili Product Owner odluku.

4. Ovaj dokument ne uvodi nova poslovna pravila izvan usvojenih Product Owner odluka.

5. Ako postoji razlika između implementacije sistema i ovog Business Modela, implementacija se usklađuje sa Business Modelom, osim ako se odlukom ne izmijeni sam Business Model.

6. Dokument ne definiše DB kolone, ENUM vrijednosti, Laravel modele, migracije, kontrolere, Blade/HTML/JavaScript ni konkretan storage mapping.

---

## Sadržaj

1. Predmet i granica
2. Akteri
3. Entiteti
4. Procesi
5. Vrsta subjekta
6. Fizičko lice
7. Preduzetnik
8. Pravno lice
9. Dio stranog privrednog društva
10. Zajednička poslovna pravila
11. Novi korisnici i postojeći korisnici
12. Granica prema Konkursima
13. Granica prema e-Plaćanju
14. Granica prema Kalendaru kulture
15. Poslovna arhitektura slojeva
16. Legacy / backward compatibility granica
17. Otvorene stavke

---

# 1. Predmet i granica

DK-BM-002 je **zajednički platformski** poslovni model.

Predmet dokumenta je:

- registracija korisnika na Platformi digital.kotor.me;
- poslovna priroda registrovanog subjekta;
- korisnički identitet;
- osnovni identifikacioni podaci;
- osnovni kontaktni podaci;
- odnos novog modela prema postojećim korisnicima;
- poslovna pravila koja određuju kategorije subjekata.

Moduli poput Konkursa (`KN-*`), e-Plaćanja (`EP-*`) i Kalendara kulture (`KK-*`) koriste platformski korisnički identitet, ali zadržavaju sopstvena modulska pravila prihvatljivosti, dostupnosti, uloga i poslovnih procesa.

DK-BM-002 **ne** preuzima:

- `applicant_type` konkursa;
- `registration_form` konkursa;
- konkursne kriterijume prihvatljivosti;
- pravila konkretnog konkursa;
- EP pravila dostupnosti vrsta plaćanja;
- payment tok;
- uloge, ovlašćenja i poslovne procese Kalendara kulture.

---

# 2. Akteri

| Akter | Poslovna uloga u ovom modelu |
|-------|------------------------------|
| **Korisnik koji se registruje** | Nova osoba ili subjekt koji na Platformi otvara nalog prema kanonskom modelu. |
| **Postojeći korisnik** | Korisnik koji već ima nalog na Platformi. Zadržava nalog i istoriju. Ne ponavlja registraciju zbog uvođenja ovog modela. |
| **Platforma** | Prima registraciju, čuva identitet naloga, zahtijeva validne obavezne podatke i verifikaciju e-mail adrese. |

Konkursi, e-Plaćanje i Kalendar kulture koriste platformski korisnički identitet. Nijesu akteri procesa registracije.

---

# 3. Entiteti

Poslovni entiteti ovog modela:

| Entitet | Značenje |
|---------|----------|
| **Korisnički nalog** | Evidencija kojom se korisnik prijavljuje na Platformu. Korisničko ime je e-mail. |
| **Korisnički identitet** | Poslovni skup podataka koji opisuje ko je registrovani subjekt. |
| **Vrsta subjekta** | Jedna od tri kanonske kategorije: Fizičko lice, Pravno lice, Dio stranog privrednog društva. |
| **Fizičko lice** | Fizička osoba. Može, ali ne mora, biti registrovana kao preduzetnik. |
| **Preduzetnik** | Fizičko lice koje je registrovano za obavljanje djelatnosti. Nije posebna pravna priroda. |
| **Pravno lice** | Domaći pravni subjekt sa kontrolisanim pravnim oblikom. |
| **Dio stranog privrednog društva** | Posebna vrsta subjekta. Nije podvrsta Pravnog lica u ovom modelu. |
| **Ovlašćeno lice** | Fizička osoba koja zastupa Pravno lice pri registraciji. |
| **Zastupnik** | Fizička osoba koja zastupa Dio stranog privrednog društva pri registraciji. |
| **Korisnički profil** | Poslovni skup identifikacionih, kontaktnih i adresnih podataka vezan za isti nalog. |

---

# 4. Procesi

Ovaj BM opisuje poslovne procese, ne tehničku realizaciju.

## 4.1 Registracija novog korisnika

1. Korisnik bira vrstu subjekta.
2. Korisnik unosi obavezne i uslovno obavezne podatke za izabranu granu.
3. Korisnik ponovo unosi e-mail adresu i korisničku lozinku u odgovarajuća polja za potvrdu, pri čemu vrijednosti moraju odgovarati izvornim vrijednostima.
4. Korisnik pokreće poslovnu akciju **Registruj se**.
5. Platforma prima registraciju samo ako su sva aktivna obavezna polja popunjena i validna.
6. Primjenjuje se verifikacija e-mail adrese.

## 4.2 Korišćenje Platforme od strane postojećeg korisnika

1. Postojeći korisnik zadržava postojeći nalog i njegovu istoriju.
2. Postojeći korisnik može normalno da se prijavi na Platformu.
3. Ne kreira se novi nalog samo zbog uvođenja ovog modela.
4. Ako postojeći profil nema podatak koji novi model zahtijeva, dopuna profila se zahtijeva tek prije korišćenja funkcionalnosti za koju je taj podatak potreban.
5. Nepotpunost postojećeg profila sama po sebi ne predstavlja globalnu blokadu korisničkog naloga.

Konkretan ekran, UI ili tehnički gate dopune **nije** predmet ovog BM-a.

## 4.3 Prijava

E-mail je korisničko ime za prijavu. Tehnička realizacija prijave nije predmet ovog BM-a.

---

# 5. Vrsta subjekta

Registracija razlikuje **tri** osnovne vrste subjekta:

1. Fizičko lice
2. Pravno lice
3. Dio stranog privrednog društva

Dio stranog privrednog društva **nije** podvrsta Pravnog lica u ovom modelu.

Preduzetnik **nije** treća pravna priroda.

Preduzetnik ostaje Fizičko lice koje je registrovano za obavljanje djelatnosti.

---

# 6. Fizičko lice

## 6.1 Pitanje o preduzetništvu

Za Fizičko lice postoji poslovno pitanje:

**Da li ste registrovani kao preduzetnik?**

Vrijednosti, ovim redosljedom:

1. Da
2. Ne

Odgovor **Da** znači da se na Fizičko lice primjenjuju dodatna pravila Preduzetnika iz poglavlja 7.

Odgovor **Ne** znači da se primjenjuje skup podataka iz poglavlja 6.4.

## 6.2 Status rezidentnosti

Za Fizičko lice obavezan je Status rezidentnosti:

- Rezident
- Nerezident

Status rezidentnosti primjenjuje se i na Preduzetnika, jer je Preduzetnik Fizičko lice.

Pravna lica **nemaju** Status rezidentnosti.

## 6.3 Identifikacija fizičkog lica

### Rezident

- JMB je obavezan.
- JMB ima tačno 13 cifara.
- Validira se kontrolna cifra.

### Nerezident

Korisnik bira identifikacioni dokument:

- JMB
- Broj pasoša

Izabrani identifikacioni podatak je obavezan.

Ako je izabran JMB, primjenjuju se pravila validacije JMB-a.

Ako je izabran pasoš, Broj pasoša je obavezan.

Za Nerezidenta je obavezna **Država prebivališta**.

Ne uvoditi naselja.

## 6.4 Fizičko lice koje nije Preduzetnik

Poslovni skup podataka:

- Ime *
- Prezime *
- odgovarajući identifikacioni podatak *
- Država prebivališta * kada je Nerezident
- E-mail *
- Potvrdi e-mail *
- Korisnička lozinka *
- Potvrdi korisničku lozinku *
- Broj mobilnog telefona *
- Ulica i broj *
- Grad *

---

# 7. Preduzetnik

Preduzetnik koristi isti identitet fizičkog lica i ista pravila rezidentnosti.

Redosljed ključnih poslovnih podataka:

- Ime *
- Prezime *
- odgovarajući identifikacioni podatak *
- Naziv preduzetnika *
- PIB *

Za Nerezidenta primjenjuje se i:

- Država prebivališta *

PIB Preduzetnika je obavezan.

Usvojeno pravilo za PIB Preduzetnika:

- PIB ima tačno 8 cifara;
- validira se kontrolna cifra prema važećem pravilu.

Nakon poslovnih identifikacionih podataka slijede:

- E-mail *
- Potvrdi e-mail *
- Korisnička lozinka *
- Potvrdi korisničku lozinku *
- Broj mobilnog telefona *
- Ulica i broj *
- Grad *

---

# 8. Pravno lice

Pravno lice **nema** Status rezidentnosti.

## 8.1 Pravni oblik

Pravni oblik je kontrolisana lista sa **tačno** ovim vrijednostima:

1. Ortačko društvo (OD)
2. Komanditno društvo (KD)
3. Društvo sa ograničenom odgovornošću (DOO)
4. Akcionarsko društvo (AD)
5. Nevladino udruženje
6. Nevladina fondacija
7. Sportska organizacija

Ne uvoditi:

- Ustanove;
- Ostalo;
- generički NVO;
- Dio stranog privrednog društva kao pravni oblik.

## 8.2 Poslovni podaci

- Pravni oblik *
- Puni naziv pravnog lica *
- PIB *
- Ime ovlašćenog lica *
- Prezime ovlašćenog lica *
- Identifikacioni dokument ovlašćenog lica *

Identifikacioni dokument ovlašćenog lica:

- JMB
- Broj pasoša

Ako je JMB:

- JMB ovlašćenog lica *

Ako je pasoš:

- Broj pasoša ovlašćenog lica *
- Država izdavanja pasoša *

Zatim:

- E-mail *
- Potvrdi e-mail *
- Korisnička lozinka *
- Potvrdi korisničku lozinku *
- Broj mobilnog telefona *
- Ulica i broj *
- Grad *

## 8.3 PIB Pravnog lica

PIB Pravnog lica je obavezan.

Usvojeno pravilo:

- PIB ima tačno 8 cifara;
- validira se kontrolna cifra prema važećem pravilu.

---

# 9. Dio stranog privrednog društva

Dio stranog privrednog društva je **posebna vrsta subjekta**.

Dio stranog privrednog društva **nema** Status rezidentnosti. Status rezidentnosti je svojstvo Fizičkog lica i ne primjenjuje se na Dio stranog privrednog društva.

## 9.1 Poslovni podaci subjekta

- Naziv stranog privrednog društva *
- Naziv dijela stranog privrednog društva u Crnoj Gori *
- PIB *
- CRPS registracioni broj *

PIB Dijela stranog privrednog društva je obavezan podatak. Predstavlja poreski identifikacioni broj dodijeljen u Crnoj Gori. PIB je odvojen i različit podatak od CRPS registracionog broja. Platforma zahtijeva unos PIB-a prilikom registracije Dijela stranog privrednog društva.

Ovaj BM **ne** propisuje:

- da PIB Dijela stranog privrednog društva mora imati 8 cifara;
- da taj PIB mora proći ISO 7064 Modul 11,10 provjeru;
- format CRPS registracionog broja.

Tačna funkcionalna i tehnička validacija PIB-a Dijela stranog privrednog društva ostaje za `DK-FS-002` / `DK-TS-002`, prema važećim pravilima nadležnog organa. Ti dokumenti **nisu** kreirani ovim korakom.

## 9.2 Zastupnik

- Ime zastupnika *
- Prezime zastupnika *
- Identifikacioni dokument zastupnika *

Identifikacioni dokument zastupnika:

- JMB
- Broj pasoša

Ako je JMB:

- JMB zastupnika *

Ako je pasoš:

- Broj pasoša zastupnika *
- Država izdavanja pasoša *

Zatim:

- E-mail *
- Potvrdi e-mail *
- Korisnička lozinka *
- Potvrdi korisničku lozinku *
- Broj mobilnog telefona *
- Ulica i broj *
- Grad *

Ulica i broj + Grad predstavljaju adresu **dijela stranog privrednog društva u Crnoj Gori**.

---

# 10. Zajednička poslovna pravila

1. Sva aktivna polja označena sa `*` moraju biti popunjena i validna prije završetka registracije.

2. Uslovno polje je obavezno samo kada je njegova grana aktivna.

3. E-mail je korisničko ime za prijavu.

4. E-mail mora biti jedinstven.

5. Primjenjuje se verifikacija e-mail adrese.

6. Potvrdi e-mail mora odgovarati vrijednosti E-mail.

7. Potvrda e-maila nije zaseban poslovni podatak i ne čuva se kao takva.

8. Potvrdi korisničku lozinku mora odgovarati korisničkoj lozinci.

9. Potvrda korisničke lozinke nije zaseban poslovni podatak i ne čuva se kao takva.

10. Broj mobilnog telefona predstavlja jedno poslovno polje. Korisnički interfejs može koristiti izbor međunarodnog pozivnog broja. Tehnički način čuvanja ne pripada ovom BM-u.

11. Ulica i broj su obavezni.

12. Vrijednost `bb` je dozvoljena kada objekat nema broj.

13. Grad je obavezan.

14. Grad se **ne** ograničava na teritoriju Opštine Kotor.

15. Ne uvoditi naselja kao obavezni dio registracionog modela.

16. Poslovna akcija završetka registracije korisniku se prikazuje kao: **Registruj se**.

---

# 11. Novi korisnici i postojeći korisnici

## 11.1 Kanonsko pravilo za postojeće korisnike

Uvođenje novog modela registracije i korisničkog identiteta **ne zahtijeva ponovnu registraciju** postojećih korisnika Platforme. Postojeći korisnički nalog i njegova istorija ostaju sačuvani.

Postojeći korisnik može normalno da se prijavi na Platformu. Ako postojeći profil nema podatak koji novi model zahtijeva, dopuna profila se zahtijeva tek prije korišćenja funkcionalnosti za koju je taj podatak potreban. Nepotpunost postojećeg profila sama po sebi ne predstavlja globalnu blokadu korisničkog naloga.

Iz ovog pravila poslovno proizlazi:

- postojeći nalog se ne briše zbog uvođenja ovog modela;
- postojeći korisnik ne kreira novi nalog samo zbog novog modela;
- identitet naloga i njegova istorija ostaju povezani sa istim korisnikom;
- nedostajući obavezni podatak rješava se dopunom postojećeg profila.

Ovaj BM **ne** definiše kako će tehnički biti izvedena dopuna ili migracija. Ne propisuje SQL backfill, automatsku konverziju postojećih kategorija, automatsko postavljanje PIB-a, automatsko brisanje ili izmjenu statusa rezidentnosti, novu šemu podataka, middleware niti konkretan UI/redirect mehanizam dopune.

## 11.2 Novi korisnik

Nakon aktiviranja novog modela, novi korisnik registruje se prema kanonskom modelu ovog dokumenta.

## 11.3 Postojeći korisnik

Postojeći korisnik zadržava postojeći nalog i istoriju.

Postojeći korisnik može normalno da se prijavi na Platformu.

Ako mu nedostaje podatak potreban za korišćenje određene funkcionalnosti po novom modelu, dopunjava postojeći profil tek prije korišćenja te funkcionalnosti.

Nepotpunost postojećeg profila sama po sebi ne predstavlja globalnu blokadu korisničkog naloga.

Ne zahtijeva se ponovna registracija.

---

# 12. Granica prema Konkursima

DK-BM-002 definiše identitet korisnika Platforme.

`KN-*` zadržava:

- Podnosioca / Podnositeljku;
- prihvatljivost na konkretnom konkursu;
- `applicant_type`;
- `registration_form`;
- konkursne obrasce;
- prebivalište / sjedište kao uslov konkretnog konkursa;
- ostala konkursna pravila.

Ograničenje prebivališta ili sjedišta na Opštinu Kotor u konkretnom konkursu **ne** predstavlja ograničenje polja Grad pri platformskoj registraciji.

To su dva različita poslovna sloja.

---

# 13. Granica prema e-Plaćanju

DK-BM-002 je platformski kanon korisničkog identiteta.

`EP-*` kasnije treba da referencira ovaj model za:

- vrstu subjekta;
- pravni oblik;
- rezidentnost;
- identifikacione podatke;
- osnovni korisnički identitet.

e-Plaćanje zadržava svoja modulska pravila, uključujući:

- koja kategorija korisnika smije koristiti određenu vrstu plaćanja;
- dostupnost računa;
- payment tok;
- snapshot uplatioca;
- ostala EP poslovna pravila.

Ovaj dokument **ne** mijenja EP dokumentaciju.

---

# 14. Granica prema Kalendaru kulture

DK-BM-002 definiše platformski korisnički identitet.

Kalendar kulture koristi platformski identitet registrovanog korisnika.

Kalendar kulture samostalno definiše svoje uloge, ovlašćenja i poslovne procese.

Korisnička uloga u Kalendaru kulture **nije** Vrsta subjekta.

Uloga u Kalendaru kulture **ne** mijenja platformski korisnički identitet.

Ovaj dokument **ne** prenosi poslovna pravila Kalendara kulture i **ne** mijenja KK dokumentaciju.

---

# 15. Poslovna arhitektura slojeva

Poslovni slojevi se ne miješaju:

1. **Platformski identitet** — `DK-*`. Ko je korisnik Platforme i kojoj Vrsti subjekta pripada.
2. **Kalendar kulture** — `KK-*`. Uloge, ovlašćenja i poslovni procesi Kalendara kulture koriste platformski identitet, ali ne određuju Vrstu subjekta niti mijenjaju platformski identitet.
3. **Konkursi** — `KN-*`. Modulska prihvatljivost: ko može biti Podnosilac / Podnositeljka na konkretnom konkursu.
4. **e-Plaćanje** — `EP-*`. Modulska dostupnost: koja kategorija korisnika može koristiti konkretnu vrstu plaćanja.

DK-BM-002 ne definiše tehničku arhitekturu implementacije.

---

# 16. Legacy / backward compatibility granica

Postojeće runtime stanje može sadržati, između ostalog:

- postojeće kategorije korisnika;
- legacy kategorije;
- Preduzetnike bez PIB-a;
- pravna lica sa legacy statusom rezidentnosti;
- postojeće adrese;
- konkursne klasifikacije Podnosioca;
- druge veze prema korisniku.

DK-BM-002 **ne** rješava njihovu tehničku migraciju.

Postojeći korisnik može normalno da se prijavi na Platformu. Ako postojeći profil nema podatak koji novi model zahtijeva, dopuna profila se zahtijeva tek prije korišćenja funkcionalnosti za koju je taj podatak potreban. Nepotpunost postojećeg profila sama po sebi ne predstavlja globalnu blokadu korisničkog naloga.

Svako buduće FS, TS ili migration rješenje mora poštovati poslovno pravilo iz poglavlja 11:

- nema ponovne registracije postojećih korisnika;
- postojeći korisnički nalog ostaje sačuvan;
- istorija ostaje sačuvana;
- nedostajući podaci dopunjavaju se na postojećem profilu;
- tehnička migracija ne smije proizvoljno promijeniti poslovni identitet postojećeg korisnika;
- postojeće veze modula prema korisničkom nalogu moraju biti očuvane.

Detaljni gate, UI ili redirect mehanizam dopune **nije** predmet ovog BM-a. Pripada FS/TS.

Ove tehničke stavke se ne pretvaraju u nova Product Owner poslovna pitanja, osim ako stvarno tehničko ograničenje pokaže konflikt sa usvojenim poslovnim ponašanjem.

---

# 17. Otvorene stavke

Usvojena poslovna pravila u ovom dokumentu **nisu** otvorena pitanja.

Zatvoreno je:

Postojeći korisnik može normalno da se prijavi na Platformu. Dopuna nedostajućih podataka zahtijeva se tek prije korišćenja funkcionalnosti za koju su ti podaci potrebni.

Za PIB Dijela stranog privrednog društva:

- poslovna obaveznost PIB-a je zatvorena;
- odvojenost PIB-a od CRPS registracionog broja je zatvorena;
- tačna funkcionalna i tehnička validacija ostaje za FS/TS prema važećim pravilima nadležnog organa.

Sljedeće stavke **nisu** BM pitanja:

## A. FUNCTIONAL SPECIFICATION

- konkretno UI ponašanje;
- validacione poruke;
- funkcionalno ponašanje kontrolisanih listi;
- funkcionalni tok dopune profila u okviru usvojenog poslovnog pravila iz poglavlja 11;
- način prikaza verifikacije e-mail adrese;
- format CRPS registracionog broja, nakon pouzdane provjere;
- tačna funkcionalna validacija PIB-a Dijela stranog privrednog društva prema pravilima nadležnog organa.

## B. TECHNICAL DESIGN

- struktura baze;
- kolone i katalozi;
- mapiranje postojećeg `users.user_type` modela;
- tehničko čuvanje Vrste subjekta;
- tehničko čuvanje Pravnog oblika;
- tehničko čuvanje Statusa rezidentnosti;
- tehničko čuvanje identifikacionih podataka;
- način čuvanja broja mobilnog telefona;
- tehnička implementacija verifikacije e-mail adrese;
- tehnička realizacija jedinstvenosti e-maila;
- transitional / null stanja.

## C. MIGRATION / BACKWARD COMPATIBILITY

- migracije i backward compatibility;
- očuvanje postojećih veza drugih modula;
- Preduzetnici bez PIB-a;
- pravna lica sa legacy statusom rezidentnosti;
- postojeće adrese.

Buduće FS / TS / migration rješenje mora poštovati poglavlje 11 i poglavlje 16. Tehničke stavke se ne pretvaraju u nova Product Owner poslovna pitanja, osim ako stvarno tehničko ograničenje pokaže konflikt sa usvojenim poslovnim ponašanjem.

---

**Kraj dokumenta DK-BM-002 v1.0.0**