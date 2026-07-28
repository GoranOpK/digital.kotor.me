# Digital Kotor
# Metodologija dokumentacije

**Status dokumenta:** AKTIVAN
**Verzija:** 0.1

---

# Odnos postojeće i nove dokumentacije

Digital Kotor već posjeduje značajnu količinu tehničke i operativne dokumentacije nastale tokom razvoja projekta.

Nova metodologija **ne uvodi retroaktivnu reorganizaciju postojeće dokumentacije**.

Postojeći dokumenti ostaju važeći i predstavljaju opis trenutnog stanja sistema, osim ako se za određeni dokument ne donese posebna odluka o izmjeni.

Nova metodologija se primjenjuje prvenstveno:

* na nove funkcionalnosti,
* na velike rekonstrukcije postojećih modula,
* na nove poslovne zahtjeve koji zahtijevaju Business Model, Functional Specification, Change Request i Technical Specification.

---

## Technical Overview dokumenti

Dokumenti koji opisuju postojeću implementaciju modula mogu ostati u tehničkoj dokumentaciji kao **Technical Overview** dokumenti.

Njihova svrha je:

* pregled postojeće implementacije,
* pregled arhitekture modula,
* pregled ruta, kontrolera, modela i integracija,
* pomoć pri razumijevanju postojećeg sistema.

Technical Overview dokumenti **nisu izvor istine** za:

* poslovna pravila,
* funkcionalne zahtjeve,
* planirane izmjene.

Za te oblasti koriste se:

* Business Model,
* Functional Specification,
* Change Request Register,
* Technical Specification.

---

## Odnos Business Modela, Functional Specification i implementacije

Za projekat Kalendar kulture usvaja se sljedeće pravilo:

### Business Model

Business Model opisuje ciljni poslovni model sistema.

Business Model se NE prilagođava trenutnoj implementaciji.

### Functional Specification

Functional Specification opisuje funkcionalnosti koje proizvod treba da ima nakon implementacije usvojenog poslovnog modela.

Functional Specification ne dokumentuje privremena tehnička ograničenja.

Ako implementacija kasni za usvojenim poslovnim modelom, Functional Specification ostaje usklađena sa Business Modelom.

### Technical Overview

Technical Overview opisuje isključivo trenutno stanje implementacije.

Technical Overview je jedino mjesto gdje se dokumentuju odstupanja između:

* Business Modela,
* Functional Specification-a,
* trenutne implementacije.

Registar odstupanja za Kalendar kulture vodi se u poglavlju „Odstupanja trenutne implementacije od usvojenog funkcionalnog modela“ dokumenta `docs/tehnicka-dokumentacija/cultural-calendar.md`.

---

## Kalendar kulture

Dokument:

`docs/tehnicka-dokumentacija/cultural-calendar.md`

zadržava postojeću ulogu tehničkog pregleda (Technical Overview) trenutne implementacije.

Ne mijenja se u okviru uvođenja nove metodologije.

Njegov sadržaj služi kao referenca za razumijevanje postojećeg sistema i buduću izradu Technical Specification.

---

## Pravila za Technical Specification dokumente

### M-TS-001 — Više TS dokumenata po jednom Feature-u

Jedan Feature može imati jedan ili više Technical Specification (TS) dokumenata.

Svaki TS dokument obrađuje jednu logički zaokruženu funkcionalnu cjelinu unutar Feature-a.

Veza između Feature-a i pripadajućih TS dokumenata evidentira se u Feature Registry-ju i matricama sljedivosti.

### M-TS-002 — Globalna numeracija TS dokumenata

Svi TS dokumenti imaju jedinstvenu globalnu numeraciju (TS-001, TS-002, TS-003...).

Numeracija TS dokumenata je nezavisna od Feature ID-a.

Jedan Feature može biti povezan sa više različito numerisanih TS dokumenata.

Postojeće oznake TS dokumenata ne mijenjaju se bez prethodne provjere registra i međusobnih referenci.

### M-TS-003 — Obavezno poglavlje „Granice V1 (Out of Scope)“

Svaki TS dokument mora sadržati posebno poglavlje:

**Granice V1 (Out of Scope)**.

U tom poglavlju navode se isključivo funkcionalnosti za koje je već usvojena odluka da nisu dio V1 ili trenutnog obuhvata dokumenta.

U poglavlje „Granice V1 (Out of Scope)“ ne ulaze otvorena pitanja, nepoznanice, tehničke dileme, nedonesene poslovne odluke niti nedovršene analize.

Otvorena pitanja ostaju u zasebnom poglavlju „Otvorena pitanja“.

Ako za određeni TS dokument trenutno nema dodatnih usvojenih isključenja van V1, poglavlje ipak mora postojati i sadržati jasnu napomenu o tome.

### M-TS-004 — Sekcijska i rule-level sljedivost

Svaki TS dokument mora sadržati sljedivost prema Business Model dokumentima i Functional Specification dokumentima na dva nivoa:

1. sekcijska sljedivost;
2. rule-level sljedivost.

Sekcijska sljedivost se navodi na početku svakog većeg poglavlja kroz pregled relevantnih izvora iz BM-a i FS-a.

Rule-level sljedivost je obavezna za poslovno kritična i tehnički značajna pravila, posebno za:

* poslovno kritične validacije;
* autorizaciona ograničenja;
* životne cikluse i promjene statusa;
* pravila integriteta podataka;
* sigurnosna pravila;
* automatske sistemske radnje;
* važne tehničke posljedice poslovnih odluka.

Rule-level sljedivost ne mora biti dodata svakoj rečenici, atributu ili opštem opisu kada je izvor već jasan kroz sekcijsku sljedivost.

### M-TS-005 — Standardna struktura Technical Specification dokumenta

#### Svrha

M-TS-005 definiše jedinstvenu strukturu, redosljed i pravila sadržaja Technical Specification dokumenata u projektu Digital Kotor, radi dosljednosti, čitljivosti, sljedivosti i kontrolisanog prelaza iz Business Model i Functional Specification dokumentacije u tehničku realizaciju.

#### Opšte pravilo

Svaki Technical Specification dokument mora koristiti istu standardnu strukturu, isti redosljed i iste nazive poglavlja.

Technical Specification dokument:

* ne smije uvoditi nova poslovna pravila;
* ne smije mijenjati pravila usvojena u Business Model ili Functional Specification dokumentima;
* mora tehnički razraditi već usvojena poslovna i funkcionalna pravila;
* mora poštovati pravila sljedivosti definisana kroz M-TS-004.

#### Obavezni uvodni dio

Svaki TS dokument mora sadržati:

1. Istoriju verzija
2. Svrhu dokumenta
3. Status razvoja
4. Pravila upravljanja dokumentom

Uvodni elementi nisu dio numerisanih poglavlja.

#### Standardna numerisana poglavlja

Svaki TS dokument mora sadržati sljedeća poglavlja, ovim redosljedom i pod ovim nazivima:

1. Pregled funkcionalne cjeline
2. Arhitektonski principi
3. Tehnički model
4. Tokovi
5. Autorizacija i ovlašćenja
6. Model podataka
7. Validacije
8. Evidencija aktivnosti (Audit)
9. Integracije
10. Nefunkcionalni zahtjevi
11. Granice V1 (Out of Scope)
12. Otvorena pitanja
13. Matrica sljedivosti
14. Napomene za implementaciju

#### Obaveznost poglavlja

Sva standardna poglavlja su strukturno obavezna i moraju postojati u svakom TS dokumentu.

Ako za određeno poglavlje trenutno nema primjenjivog sadržaja, u njemu se navodi:

> Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne smije se izmišljati sadržaj samo radi popunjavanja poglavlja.

#### Numeracija

* Oznaka dokumenta, poput `TS-001`, `TS-002` ili `TS-003`, navodi se u zaglavlju i metapodacima dokumenta.
* Naslovi poglavlja koriste jednostavnu numeraciju:
  * `1.`
  * `2.`
  * `3.`
* Oznaka dokumenta ne ponavlja se u naslovu svakog poglavlja.
* Obrasci poput `TS-002 / 1`, `TS-002 / 2`, `TS-002 / 3` nisu dozvoljeni u standardnoj strukturi.

#### Pravila preimenovanja i izmjene strukture

* Nazivi standardnih poglavlja ne mogu se mijenjati bez nove metodološke odluke.
* Redosljed poglavlja ne može se mijenjati bez nove metodološke odluke.
* Standardna poglavlja ne mogu se spajati, uklanjati ili zamjenjivati bez nove metodološke odluke.
* Dodatna podpoglavlja su dozvoljena kada su potrebna za konkretnu funkcionalnu cjelinu.
* Dodatna podpoglavlja ne smiju mijenjati svrhu standardnog poglavlja kojem pripadaju.

#### Granice pojedinih poglavlja

**2. Arhitektonski principi**

Opisuje:

* mjesto funkcionalne cjeline u sistemu;
* odgovornosti i zavisnosti;
* arhitektonska ograničenja;
* principe dizajna;
* razloge za usvojeni tehnički pristup.

Ne treba da opisuje konkretan kod ili framework-specifičnu realizaciju.

**3. Tehnički model**

Opisuje:

* tehničke i konceptualne cjeline;
* glavne entitete;
* odnose;
* statuse;
* lifecycle;
* odgovornosti komponenti.

**6. Model podataka**

Opisuje:

* podatke i njihove poslovne i tehničke uloge;
* ključne atribute;
* veze;
* integritet;
* konceptualni model podataka.

Ne predstavlja SQL šemu, migracioni plan niti fizički dizajn baze, osim ako je takav nivo detalja izričito usvojen kao dio obuhvata dokumenta.

**7. Validacije**

Može sadržati podcjeline:

* poslovne validacije;
* tehničke validacije;
* validacije integriteta;
* validacije stanja i prelaza.

Validacije koje blokiraju tok moraju imati rule-level sljedivost.

**14. Napomene za implementaciju**

Ovo poglavlje je strogo nenormativno.

Ne smije:

* uvoditi nova poslovna pravila;
* uvoditi nova funkcionalna pravila;
* mijenjati BM ili FS;
* mijenjati normativni sadržaj drugih TS poglavlja.

Može sadržati:

* tehničke preporuke;
* poznata tehnička ograničenja;
* preporučeni redosljed implementacije;
* praktične napomene implementacionom timu.

#### API i implementacioni nivo

TS može definisati:

* potrebne interfejse;
* ugovore između komponenti;
* API ponašanje;
* ulazne i izlazne podatke;
* greške i očekivane odgovore;
* sigurnosna i autorizaciona pravila interfejsa.

TS ne treba da ulazi u:

* konkretan programski kod;
* framework-specifičnu implementaciju;
* fizičku realizaciju;
* migracije;
* detalje koji pripadaju implementacionom zadatku;

osim kada je takav nivo detalja izričito usvojen kao dio obuhvata konkretnog TS dokumenta.

#### Sljedivost

U skladu sa M-TS-004:

**Sekcijska sljedivost**

Obavezna je na početku svih relevantnih TS poglavlja.

Veze se navode prema odgovarajućim:

* BM pravilima;
* FS sekcijama;
* BR pravilima.

**Rule-level sljedivost**

Obavezna je za pravila koja definišu:

* statuse i lifecycle prelaze;
* autorizacione dozvole i zabrane;
* validacije koje blokiraju tok;
* audit događaje;
* integritet podataka;
* automatske sistemske radnje;
* sigurnosna ograničenja;
* druge poslovno kritične ili tehnički značajne odluke.

**Matrica sljedivosti**

Poglavlje `13. Matrica sljedivosti` je obavezno.

Mora najmanje povezivati:

* BM pravilo ili sekciju;
* FS/BR pravilo ili sekciju;
* odgovarajuće TS poglavlje ili konkretno TS pravilo.

#### Granice V1 i otvorena pitanja

**11. Granice V1 (Out of Scope)**

Sadrži isključivo već usvojene odluke da određena funkcionalnost, ponašanje ili tehnički obuhvat nije dio V1.

Ne sadrži neriješena pitanja.

**12. Otvorena pitanja**

Sadrži isključivo pitanja za koja odluka još nije donesena.

Ne sadrži:

* zatvorene odluke;
* usvojena isključenja;
* implementacione napomene.

Redosljed poglavlja ostaje:

1. Granice V1
2. Otvorena pitanja

jer se prvo evidentiraju donesene odluke o isključenjima, a zatim nedonesene odluke.

#### Posljedice za postojeće TS dokumente

* M-TS-005 se primjenjuje na sve nove TS dokumente.
* Postojeći TS dokumenti ne usklađuju se automatski u ovom koraku.
* Usklađivanje svakog postojećeg TS dokumenta vrši se kroz zaseban, dokumentovan zadatak.
* Usklađivanje mora sačuvati postojeći normativni sadržaj i ne smije uvoditi nove poslovne odluke.
* Redakcijsko premještanje sadržaja mora biti jasno odvojeno od sadržajnih izmjena.

---

## Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-26 | Definisan odnos između postojeće tehničke dokumentacije i nove metodologije. Uveden pojam Technical Overview dokumenta. Potvrđeno da se postojeća dokumentacija ne reorganizuje retroaktivno. |
| 2026-07-26 | Usvojeno pravilo odnosa Business Model / Functional Specification / Technical Overview; registar odstupanja vodi se isključivo u Technical Overview dokumentu modula. |
| 2026-07-28 | Dodata pravila M-TS-001 do M-TS-004 za Technical Specification dokumente (više TS po Feature-u, globalna numeracija, obavezno poglavlje „Granice V1 (Out of Scope)“, sekcijska i rule-level sljedivost). |
| 2026-07-28 | Dodato pravilo M-TS-005 — Standardna struktura Technical Specification dokumenta (jedinstvena struktura, redosljed i nazivi poglavlja za sve TS dokumente). |
