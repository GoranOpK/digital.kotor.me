# Digital Kotor
# Funkcionalna specifikacija e-Plaćanja
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-FS-001
**Modul:** e-Plaćanje
**Status dokumenta:** U IZRADI
**Verzija:** 1.1.0

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Functional Specification modula e-Plaćanja. Unesene usvojene odluke P-01–P-08 i F-01. |
| EP-PATCH-FS-001 | 2026-07-27 | UR-01 – Uplatni računi: FS ne navodi konkretne brojeve računa; vrsta uplate ima referencu na račun iz Kataloga; Katalog ≠ šifrarnik. |
| EP-PATCH-FS-002 | 2026-07-27 | BP-01, BP-02, BP-03 – Pronalaženje vrste uplate; način popunjavanja podataka; pregled i potvrda prije plaćanja. |
| EP-PATCH-FS-003 | 2026-07-27 | BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja; korisnički tok nezavisan od računa i gateway implementacije. |
| EP-PATCH-FS-004 | 2026-07-27 | BP-05 – Obrada ishoda elektronskog plaćanja; funkcionalno ponašanje po ishodima; evidencija transakcije. |
| EP-PATCH-FS-005 | 2026-07-27 | BP-06 – Potvrda o izvršenom elektronskom plaćanju; pregled i preuzimanje; minimalni sadržaj. |
| EP-PATCH-FS-006 | 2026-07-27 | BP-07 – Izvor obaveznih podataka za elektronsko plaćanje (BP-07.1 do BP-07.5). |
| EP-PATCH-FS-007 | 2026-07-27 | BP-08 – Životni ciklus transakcije (BP-08.1 do BP-08.5). |
| EP-PATCH-FS-008A | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-06/BP-08: korisnička poruka ≠ status; početni status Kreirana; potvrda izvornom sistemu ≠ knjiženje. |
| EP-PATCH-FS-008B | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-08: evidencija bilježi trenutni status transakcije. |
| EP-PATCH-FS-009 | 2026-07-27 | BP-09 – Istorija transakcija i pregled plaćanja (BP-09.1 do BP-09.5). |
| EP-PATCH-FS-009A | 2026-07-27 | Redakcijsko usklađivanje: BP-06↔BP-09 (istorija); terminološko razdvajanje identifikatora transakcije. |
| EP-PATCH-FS-010 | 2026-08-17 | Dokumentacioni corrective: oznaka EP-FS-001; namespace EP-*; naziv modula e-Plaćanje. Bez izmjene funkcionalnih pravila. |
| EP-PATCH-FS-011 | 2026-08-20 | Usklađivanje FR sa zatvorenim Korakom 6 / EP-BM-001 v1.0.0. SUPERSEDE Kreirana/U toku i preuzimanja obaveze. Bez UI pixel-level. Bez application implementation-a. |

---

## Svrha dokumenta

Dokument prevodi zatvoreni poslovni model (EP-BM-001, Korak 6) u funkcionalne zahtjeve V1.

Poslovni SSOT ostaje EP-BM-001. Terminologija: EP-RG-001.

Ne projektuje UI piksele, API, gateway protokol ni bazu.

---

# Status razvoja Functional Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Svrha | USVOJENO |
| 2. Obuhvat V1 | USVOJENO |
| 3. Granice | USVOJENO |
| 4. Korisnici i eligibility | USVOJENO na nivou FR; platform deps OPEN |
| 5. Katalog i filter | USVOJENO |
| 6. Tok plaćanja | USVOJENO |
| 7. Statusi, idempotentnost, immutability | USVOJENO |
| 8. Istorija, potvrda, email | USVOJENO |
| 9. Administracija | USVOJENO |
| 10. Failure / empty / offline | USVOJENO |
| 11. Poslovna pravila (BR-P) | USVOJENO uz SUPERSEDE stare tabele |
| 12. Prihvatni kriterijumi | U IZRADI (gateway AC OPEN) |

Dokument ostaje **U IZRADI** jer AC i gateway detalji zavise od pre-production zavisnosti.

---

# Pravila upravljanja

1. FS mora ostati usklađena sa EP-BM-001. **KORAK 6 WINS.**
2. Usvojene FR se mijenjaju PATCH-em.
3. Application implementation nije predmet ovog PATCH-a.

---

## Sadržaj

1. Svrha
2. Obuhvat V1
3. Granice
4. Korisnici i eligibility
5. Katalog i filter
6. Tok plaćanja
7. Statusi i idempotentnost
8. Istorija, potvrda, email
9. Administracija
10. Failure / empty / offline
11. Poslovna pravila
12. Prihvatni kriterijumi

---

# 1. Svrha

**Status:** USVOJENO (P-01)

Modul omogućava korisnički iniciranu elektronsku uplatu prema kontrolisanom katalogu vrsta plaćanja i računa Opštine Kotor.

---

# 2. Obuhvat V1

**Status:** USVOJENO (F-01)

* 17 vrsta plaćanja, 41 račun (EP-KF-001).
* Korisnik plaća u svoje ime. `Plati za drugo lice` = OUT OF V1.
* Refund kroz user portal = OUT OF V1.

---

# 3. Granice

**Status:** USVOJENO (P-03, P-04, P-08, Korak 6)

Sistem **ne**:

* pronalazi/preuzima rješenja ili zaduženja;
* utvrđuje postojanje ili visinu obaveze;
* vodi saldo;
* potvrđuje izmirenje obaveze;
* prikuplja osjetljive kartične podatke.

---

# 4. Korisnici i eligibility

**Status:** USVOJENO (FR); platform corrective OPEN

## 4.1 Uplatilac

Podaci uplatioca dolaze iz kanonskog Digital Kotor profila. Korisnik ne unosi drugi identitet.

Prije gateway-a: **current profile**. Na nastanku transakcije: **immutable snapshot**.

## 4.2 Declare-on-use

Ako fizičko lice ili Preduzetnik nema kanonski `resident` / `non-resident`, sistem **ne** filtrira vrste plaćanja po pretpostavci.

Korisnik mora izjaviti status (**DECLARE-ON-USE GATE**) prije funkcije kojoj je status potreban.

Nema auto-backfill-a.

## 4.3 Pravno lice

EP filtering **ne** koristi `residential_status`. Koristi konkretan zakonski oblik kada availability to bude zahtijevalo.

Postojeći application fallback `resident` za pravno lice = platform dependency; FS ga ne tretirati kao kanonski EP status.

## 4.4 Availability

Konačno mapiranje 17/41 = OPEN PRE-PRODUCTION. Do tada FR zahtijeva da filter **postoji** kao mehanizam; konkretna pravila se ne izmišljaju.

---

# 5. Katalog i filter

**Status:** USVOJENO

## FR-CAT-01 Browse

Korisnik pregleda i pretražuje vrste plaćanja iz jednog kataloga. Nema paralelne liste. FS ne navodi konkretne brojeve računa (UR-01).

## FR-CAT-02 Filter

Redoslijed: `korisnik → dozvoljena vrsta → dozvoljeni račun(i)`.

Račun ne proširuje pravo sa nivoa vrste.

Vrsta se ne prikazuje bez najmanje jednog aktivnog, validnog i dozvoljenog računa.

## FR-CAT-03 Izbor računa

* 1 dozvoljeni račun: sistem može automatski odabrati.
* 2+ dozvoljenih: korisnik bira.
* Ručni unos računa: **zabranjen**.

## FR-CAT-04 Empty state

Ako nema dostupne vrste: modul dostupan; empty state; istorija/potvrde dostupne.

---

# 6. Tok plaćanja

**Status:** USVOJENO

## FR-FLOW-01

`formiranje` → `pregled` → `izričita potvrda` → `gateway`

Do gateway-a: nazad; izmjena vlastitih unosa/izbora; odustajanje = **NO TRANSACTION**.

## FR-FLOW-02 Iznos

Korisnik unosi iznos. EUR. > 0. Max 2 decimale. Sistem ne predlaže obavezu. Nema univerzalnog min/max dok gateway/konkretno plaćanje ne zahtijeva.

Provizija: Opština Kotor. Korisnik: no additional fee. Terećenje kartice = potvrđeni iznos.

## FR-FLOW-03 Svrha / poziv / model

* Svrha: sistem formira osnovni tekst prema vrsti.
* Poziv: per vrsta/račun (system / user / optional / N/A).
* Model i šifra: sistemski; nije proizvoljni unos.

## FR-FLOW-04 Re-check

Neposredno prije pokušaja gateway start-a sistem ponovo provjerava korisnika, profil, prava, vrstu, račun i COMPLETE+VALID konfiguraciju.

## FR-FLOW-05 Granica transakcije

Transakcija **U obradi** nastaje tek kad sistem pouzdano utvrdi da je pokušaj prihvaćen/pokrenut prema gateway-u.

Ako nije: NO TRANSACTION (nije Neuspješna/Otkazana).

---

# 7. Statusi i idempotentnost

**Status:** USVOJENO

## FR-ST-01 Četiri statusa

U obradi; Uspješna; Neuspješna; Otkazana.

**SUPERSEDE:** Kreirana; U toku; pending; Greška.

## FR-ST-02 SSOT

Browser return **NOT AUTHORITATIVE**. Konačni status = server-confirmed gateway result. Sistem ne nagađa.

Vrijeme samo ne mijenja U obradi.

Admin **Provjeri status** ako gateway podržava; admin ne bira rezultat.

## FR-ST-03 Idempotentnost

Jedna potvrda: max 1 transakcija, max 1 gateway attempt.

Dupli request istog zahtjeva: no new transaction.

Isti ponovljeni gateway callback: no duplicate effect.

Novi pokušaj nakon Neuspješna/Otkazana: nova transakcija + novi snapshot.

Ne zaključivati „ista obaveza“ iz istog iznosa/računa/svrhe/poziva.

## FR-ST-04 Immutability

Nakon gateway start-a transakcija je immutable. Promjene profila/kataloga ne mijenjaju postojeće zapise.

---

# 8. Istorija, potvrda, email

**Status:** USVOJENO

## FR-HIS-01

Korisnik vidi ONLY OWN. Sva četiri statusa. Minimum: datum/vrijeme, vrsta, iznos, status, DK transaction ID. Detalj: snapshot. Bez brisanja.

## FR-CONF-01 PDF

Samo Uspješna. PDF YES. Nije fiskalni račun, rješenje ni dokaz izmirenja. Minimum sadržaja: EP-BM-001 BP-06.

## FR-MAIL-01

Automatski email samo Uspješna, na email iz trenutka transakcije (snapshot). Fail ne mijenja status. Resend na current valid account email, bez izmjene snapshot-a.

---

# 9. Administracija

**Status:** USVOJENO

## FR-ADM-01 Katalog

Administrator platforme: CRUD vrste/računa u poslovnom smislu; aktivacija/deaktivacija; availability; parametri.

Aktivno za korisnike samo COMPLETE + VALID.

Audit: ko, kada, šta. Action kodovi nisu usvojeni.

Deaktivacija umjesto brisanja korišćenog zapisa. Promjena broja računa = novi zapis.

## FR-ADM-02 Transakcije

Pregled svih; snapshot; status history; gateway reference; status check.

Zabranjeno: ručni status Uspješna/Neuspješna/Otkazana; brisanje kroz regularni UI.

## FR-ADM-03 Offline

Disable new payments. Istorija/potvrde ostaju. U obradi nastavlja lifecycle.

---

# 10. Failure / empty / offline

**Status:** USVOJENO

* Nema dostupne vrste → empty state, modul dostupan.
* Odustajanje prije gateway-a → no transaction.
* Gateway nije pokrenut → no transaction.
* Email fail → finansijski status nepromijenjen.
* Gateway bez status-check → ostaje U obradi.
* Kontradiktorni gateway rezultati → ne last-response-wins.

---

# 11. Poslovna pravila

Stari identifikatori BR-P-001–066 ostaju **istorijski**. Aktivna pravila su Korak 6 / ova specifikacija / EP-BM-001.

| Stari ID (primjer) | Stara tvrdnja | Korak 6 |
|--------------------|---------------|---------|
| BR-P-051, BR-P-052, BR-P-055 | Kreirana; U toku; prelazi Kreirana→U toku | **SUPERSEDE** |
| BR-P-057, BR-P-058 | potvrda izvornom sistemu | **SUPERSEDE** za V1 |
| pravila o fiksnom/izvornom/predloženom iznosu | BP-07.1 stari modeli | **SUPERSEDE** |
| UR-01 / bez hardkod računa | računi iz kataloga | **KEEP** |
| potvrda samo Uspješna | BP-06 | **KEEP / UPDATE** (PDF, disclaimer) |
| P-03 granice | ne obračunava, ne rješenja | **KEEP** |

Nove FR oznake u ovom dokumentu (`FR-CAT-*`, `FR-FLOW-*`, `FR-ST-*`, `FR-HIS-*`, `FR-CONF-*`, `FR-MAIL-*`, `FR-ADM-*`) registrovane su u EP-RG-001. Nisu novi poslovni 6.x ID-evi.

---

# 12. Prihvatni kriterijumi

**Status:** U IZRADI

V1 prihvatni kriterijumi za gateway mehanizam, min/max iznose i production mapping **čekaju** pre-production zavisnosti (EP-BM-001 / 11).

Dokumentacioni AC (bez implementacije):

* filter ne prikazuje vrstu bez dozvoljenog računa;
* ručni račun nije moguć;
* odustajanje prije gateway-a ne kreira transakciju;
* četiri statusa, bez Kreirana/U toku/pending/Greška;
* potvrda/PDF/email samo Uspješna;
* pravno lice se ne filtrira po residential_status;
* declare-on-use prije EP filtera kada status nedostaje.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana početna verzija 0.1. Unesene odluke P-01–P-08 i F-01. |
| 2026-07-27 | EP-PATCH-FS-001 … EP-PATCH-FS-009A — BP-01 do BP-09 i redakcije. |
| 2026-08-17 | EP-PATCH-FS-010 — EP-FS-001 namespace. |
| 2026-08-20 | EP-PATCH-FS-011 / verzija 1.1.0 — FR usklađeni sa Korakom 6. Status dokumenta ostaje U IZRADI zbog gateway AC. |
