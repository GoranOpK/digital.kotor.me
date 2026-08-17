# Digital Kotor
# Metodologija dokumentacije

**Status dokumenta:** AKTIVAN
**Verzija:** 1.0
**Datum:** 2026-08-17

Ovo je **metodologija cijelog Digital Kotora**: proces, primjena i TS struktura.

Normativni dokumentacioni standard (identiteti, tipovi, namespace, CR/PATCH/FT/runtime):

**DK-DS-001** — `docs/reference/Digital-Kotor-Documentation-Standard.md`

Ovaj dokument **nema** `DK-*` document ID. Metodologija ostaje globalna.

---

# 1. Svrha i obuhvat

Metodologija se primjenjuje na:

* nove poslovne module;
* nove funkcionalnosti;
* velike rekonstrukcije postojećih modula;
* kontrolisane dokumentacione corrective pakete.

**Ne** uvodi retroaktivnu reorganizaciju postojeće dokumentacije. Postojeći dokumenti ostaju važeći dok poseban PO paket ne odredi drugačije.

Kalendar kulture (`KK-*`), e-Plaćanje (`EP-*`) i platformski sloj Obavještenja (`DK-BM/UC/FS/TS-001`) su referentni primjeri već usvojenog namespace modela. Metodologija ih ne tretira kao jedini mogući oblik projekta.

---

# 2. Odnos prema DK-DS-001

| Dokument | Uloga |
|----------|--------|
| **DK-DS-001** | Kratki normativni standard: šta je document ID, koji su tipovi, koji su namespace-i, šta je KEEP. |
| **METHODOLOGY.md** | Proces: kako se piše TS, kako se vodi TO, kako se radi corrective, kako se čuva legacy. |
| **{NS}-RG-001** | Registar oznaka **tog** modula. Nije globalni katalog svih modula. |

Ako se normativno pravilo i ovaj dokument razlikuju, za identitet oznaka mjerodavan je **DK-DS-001**.

---

# 3. Canonical vs Technical Overview / Operations

## 3.1 Canonical module documentation

Za novi poslovni modul obavezni su BM, FS, TS i RG, u namespace-u tog modula. Uslovni i opcioni tipovi: vidi DK-DS-001 §3.

To je izvor istine za:

* poslovna pravila;
* funkcionalne zahtjeve;
* planiranu tehničku realizaciju usvojenog BM/FS.

## 3.2 Technical Overview i operations

Dokumenti u `docs/tehnicka-dokumentacija/` (architecture, rute, deploy, Plesk, handoff, project status, stubs) su **reference / operations**.

Njihova svrha:

* pregled postojeće implementacije;
* arhitektura, rute, modeli, integracije;
* operativa, deploy, okruženje.

**Nisu** zamjena za BM/FS/TS. **Ne** dobijaju automatski `{NS}-BM/FS/TS` ID.

Auth, korisnici, uloge i biblioteka dokumenata ostaju **DK REFERENCE / OPERATIONS** dok PO ne otvori zaseban paket. Ne kreirati `DK-BM-002` / `DK-FS-002` / `DK-TS-002` usput.

## 3.3 Business Model, Functional Specification i implementacija

Pravilo važi za **svaki** poslovni modul Digital Kotora, ne samo za Kalendar kulture.

### Business Model

Opisuje ciljni poslovni model. **Ne** prilagođava se trenutnoj implementaciji.

### Functional Specification

Opisuje ponašanje koje proizvod treba da ima nakon implementacije usvojenog poslovnog modela. Ne dokumentuje privremena tehnička ograničenja. Ako implementacija kasni, FS ostaje usklađena sa BM.

### Technical Overview

Opisuje trenutno stanje implementacije. Jedino je mjesto gdje se dokumentuju odstupanja između BM, FS i trenutnog koda.

Primjer (Kalendar kulture, KEEP): registar odstupanja u `docs/tehnicka-dokumentacija/cultural-calendar.md`. Drugi moduli, ako imaju TO, vode sopstveni registar odstupanja u svom TO dokumentu — ne u tuđem BM/FS.

---

# 4. Namespace, document ID i oznake

Normativno: **DK-DS-001** §1–§9.

Sažetak za primjenu:

* Document ID = `{NS}-{TYPE}-{NNN}`; numeracija lokalna unutar namespace-a i tipa.
* Usvojeni namespace-i: `KK`, `EP`, `DK`, `KN` (Konkursi — rezervisan; dokumenti se ne kreiraju ovom metodologijom).
* Tenderi nemaju namespace.
* Poslovna oznaka, feature ID, CR instance, PATCH ID i runtime ključ **nijesu** document ID.
* Promjena document ID-a **nikada** automatski ne mijenja runtime/stable ključ (npr. `KK-TS-003` vs `source_module = TS-003`).

---

# 5. Traceability i closeout

Minimalni lanac novog poslovnog modula:

`{NS}-BM-*` → `{NS}-FS-*` → `{NS}-TS-*`

Gdje je primjenjivo: BM → FS → TS → implementation → tests → closeout.

Closeout feature-a ili faze **nije** sinonim za status dokumenta. Četiri dimenzije statusa: vidi DK-DS-001 §12.

---

# 6. RG, Feature Registry, CR, PATCH

* Svaki novi poslovni modul: `{NS}-RG-001` (MANDATORY).
* Feature Registry: ONLY WHEN NEEDED (`{NS}-FR-001`). Globalni `FT-*` nije obavezan za nove module. Postojeći `FT-001`…`FT-004` KEEP.
* CR: novi moduli `{NS}-CR-{NNN}`; KK `CR-001`…`CR-004B` KEEP. `CR-004B` se ovim dokumentom **ne** reinterpretira.
* PATCH: novi moduli `{NS}-PATCH-{TYPE}-{NNN}`; KK `PATCH-*` / `PATCH-FS-*` KEEP; EP `EP-PATCH-*` KEEP.

---

# 7. Status, verzija, changelog

Za **nove** kanonske dokumente: Document ID, naziv, namespace/modul, verzija, status dokumenta, datum.

Kanonski status dokumenta za nove dokumente: `NACRT` · `U IZRADI` · `USVOJENO` · `SUPERSEDED` · `ARHIVIRANO`. Postojeći `STABLE` se ne prepisuje retroaktivno.

Changelog: novi red; stari redovi KEEP. Razlikovati poslovnu, funkcionalnu, tehničku, administrativnu i status/closeout izmjenu.

---

# 8. Folderi i imenovanje

**KEEP CURRENT TYPE-BASED STRUCTURE.** Ne grupisati repo po modulima.

Kanonski FS/TS folderi (PATCH-DOC-STRUCTURE-001, 2026-07-31):

```text
docs/functional-specifications/
docs/technical-specifications/
```

Singular folderi `docs/functional-specification/` i `docs/technical-specification/` više se ne koriste.

Filename **nije** document ID. Postojeći nazivi fajlova mogu ostati legacy. Za nove dokumente: čitljiv filename sa tipom i predmetom, latinica bez dijakritika; SSOT je document ID u zaglavlju.

---

# 9. Cross-module granice

* `KK-*` ne registruje e-Plaćanje, Konkurse niti platformski FT-004 kao aktivni KK sadržaj.
* `EP-*` ne registruje Kalendar kulture.
* `DK-*` je platformski sloj; `DK-RG-001` nije katalog svih poslovnih oznaka svih modula.
* `KN-*` je usvojeni namespace Konkursa; žensko i omladinsko su tokovi unutar istog `Competition` framework-a. Migracija KN dokumentacije je **zaseban** paket.
* Homofoni se ne spajaju: `BM-EP-*` (KK urednički portal) ≠ `EP-*` (e-Plaćanje); `PO-*` (Product Owner) ≠ `EP-PO-001` (pravni okvir e-Plaćanja); novi pravni okvir = `PRO`.

---

# 10. Controlled corrective

Dokumentaciona arhitektura se mijenja paketno:

1. read-only audit;
2. PO odluka;
3. documentation-only izmjena;
4. validacija: `app/`, `routes/`, `database/`, `resources/`, `tests/`, `config/` nijesu dirani;
5. commit/push bez produkcionog deploya, osim ako PO eksplicitno naredi drugačije.

Ne radi se mehanički rename, ne usvaja se namespace bez PO, ne mijenja se runtime ključ „usput“.

---

# 11. Legacy stability

Stabilni ID-evi se ne migriraju radi estetske dosljednosti: KK PATCH, KK CR, `FT-*`, `EP-PO-001`, runtime `source_module` vrijednosti.

Usklađivanje postojećih dokumenata na ovu metodologiju ide **po modulu**, kroz zaseban corrective, ne masovno.

---

# 12. Pravila za Technical Specification dokumente

Ova poglavlja ostaju procesna pravila pisanja TS. Identifikatori koriste modulni namespace (DK-DS-001).

### M-TS-001 — Više TS dokumenata po jednom Feature-u / modulu

Jedan Feature ili poslovni modul može imati jedan ili više Technical Specification (TS) dokumenata.

Svaki TS dokument obrađuje jednu logički zaokruženu funkcionalnu cjelinu.

Veza između Feature-a (ako postoji) i pripadajućih TS dokumenata evidentira se u Feature Registry-ju tog modula — ako modul ima FR — i u matricama sljedivosti.

### M-TS-002 — Namespace dokumentacionih identifikatora

Dokumentacioni identifikatori imaju namespace poslovnog modula ili platformskog sloja. Numeracija je lokalna unutar tog namespace-a i tipa dokumenta.

Usvojeni prefiksi (lista **nije** zatvorena; novi prefiks samo PO odlukom):

* `KK-*` — Kalendar kulture;
* `EP-*` — e-Plaćanje;
* `DK-*` — zajednička/platformska dokumentacija;
* `KN-*` — Konkursi (rezervisan; kanonski dokumenti nijesu kreirani ovim paketom).

Tenderi nemaju usvojeni namespace.

Numeracija TS dokumenata je nezavisna od Feature ID-a.

Jedan Feature može biti povezan sa više različito numerisanih TS dokumenata unutar namespace-a svog modula.

Postojeće oznake TS dokumenata ne mijenjaju se bez prethodne provjere registra, međusobnih referenci i runtime dual-key audita.

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

Primjer: `KK-BM-001` → `KK-FS-001` → `KK-TS-003`. Drugi moduli: `EP-BM-001` → `EP-FS-001` → `EP-TS-001`; `DK-BM-001` → `DK-FS-001` → `DK-TS-001`.

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

* Oznaka dokumenta, poput `KK-TS-001`, `EP-TS-001` ili `DK-TS-001`, navodi se u zaglavlju i metapodacima dokumenta. Prefiks je namespace poslovnog modula ili platformskog sloja; numeracija je lokalna unutar tog namespace-a.
* Naslovi poglavlja koriste jednostavnu numeraciju:
  * `1.`
  * `2.`
  * `3.`
* Oznaka dokumenta ne ponavlja se u naslovu svakog poglavlja.
* Obrasci poput `KK-TS-001 / 1`, `KK-TS-001 / 2`, `KK-TS-001 / 3` nisu dozvoljeni u standardnoj strukturi.

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
* preporučeni redoslijed implementacije;
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
* BR / FR pravilima.

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
* FS/BR/FR pravilo ili sekciju;
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

# 13. Razvojni prioritet (kanonski)

* **e-Plaćanje** = trenutni razvojni prioritet. Sljedeći razvojni korak (nije ovaj dokumentacioni paket): priprema lokalnog development/test okruženja za e-Plaćanje.
* **Omladinski konkurs** = ODLOŽEN ZA SADA.
* **Tenderi** = STUB / FUTURE MODULE.
* **Konkursi (`KN-*`)** = namespace usvojen; dokumentaciona migracija nije ovaj paket.

---

## Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-31 | PATCH-DOC-STRUCTURE-001 — kanonski folderi: `docs/functional-specifications/` i `docs/technical-specifications/`; singular folderi uklonjeni. |
| 2026-07-26 | Definisan odnos između postojeće tehničke dokumentacije i nove metodologije. Uveden pojam Technical Overview dokumenta. Potvrđeno da se postojeća dokumentacija ne reorganizuje retroaktivno. |
| 2026-07-26 | Usvojeno pravilo odnosa Business Model / Functional Specification / Technical Overview; registar odstupanja vodi se isključivo u Technical Overview dokumentu modula. |
| 2026-07-28 | Dodata pravila M-TS-001 do M-TS-004 za Technical Specification dokumente (više TS po Feature-u, globalna numeracija, obavezno poglavlje „Granice V1 (Out of Scope)“, sekcijska i rule-level sljedivost). |
| 2026-07-28 | Dodato pravilo M-TS-005 — Standardna struktura Technical Specification dokumenta (jedinstvena struktura, redosljed i nazivi poglavlja za sve TS dokumente). |
| 2026-08-17 | M-TS-002: prelazak sa globalne TS numeracije na modulni dokumentacioni namespace (`KK-*`, `EP-*`, budući `DK-*`; lista prefiksa nije zatvorena). Administrativna migracija dokumentacionog ID-a Kalendara kulture na `KK-*` namespace. Poslovni i tehnički sadržaj, status i closeout ostaju nepromijenjeni. |
| 2026-08-17 | M-TS-002 primjer: `DK-TS-001` sada postoji kao platformska dokumentacija Digital Kotora. Metodologija ostaje globalna i nema `DK-*` document ID. |
| 2026-08-17 | Digital Kotor Documentation Standard v1: metodologija generalizovana na cijelu platformu; normativni standard `DK-DS-001`; M-TS-001…M-TS-005 sačuvani kao TS proces. Namespace `KN` rezervisan. e-Plaćanje = trenutni razvojni prioritet. Omladinski konkurs odložen. Bez izmjene aplikacionog koda. |
