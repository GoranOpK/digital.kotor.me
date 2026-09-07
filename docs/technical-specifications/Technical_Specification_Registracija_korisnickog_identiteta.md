# Digital Kotor
# Technical Specification
## Funkcionalnost: Registracija i korisnički identitet Platforme Digital Kotor

**Oznaka dokumenta:** DK-TS-002
**Naziv:** Tehnička specifikacija registracije i korisničkog identiteta Platforme Digital Kotor
**Namespace / vlasništvo:** DK-* (platformski sloj Digital Kotora)
**Status dokumenta:** USVOJENO
**Verzija:** 1.0.5
**Datum:** 2026-09-07

Povezani dokumenti:

* Poslovni model (SSOT): **DK-BM-002** v1.0.4 USVOJENO — `docs/business-model/Business_Model_Registracija_korisnickog_identiteta.md`
* Funkcionalna specifikacija (SSOT): **DK-FS-002** v1.0.4 USVOJENO — `docs/functional-specifications/Functional_Specification_Registracija_korisnickog_identiteta.md`
* Registar oznaka: **DK-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Digital-Kotor.md`
* Dokumentacioni standard: **DK-DS-001** — `docs/reference/Digital-Kotor-Documentation-Standard.md`
* Metodologija TS: `docs/METHODOLOGY.md` (M-TS-001 … M-TS-005)

Ovaj dokument **nije** DK-TS-001 (Obavještenja).

Dokument **DK-UC-002** **nije** kreiran i **nije** pretpostavka ovog TS-a.

Ovaj dokument **ne** mijenja `DK-BM-002` ni `DK-FS-002`.

Ovaj dokument **ne** uvodi nova poslovna ni funkcionalna pravila.

Ovaj dokument **ne** tvrdi da je opisano ponašanje već usklađeno sa `DK-FS-002` u runtime-u.

Dokument kao cjelina ima status **USVOJENO**. Usvajanje specifikacije v1.0.0 **nije** izvršenje implementacije. Produkciono izvršenje D15 Step 8 (capability i logički cutover) je evidentirano u v1.0.1 / §14.1 kao **CLOSED / PRODUCTION PASS**. Implementacija lokalizacije kanonskog kataloga država je evidentirana u v1.0.2 / §14.2. Implementacija post-cutover dopune postojećih poslovnih subjekata je evidentirana u v1.0.3 / §14.3. Produkcioni deploy te dopune i Level A non-mutating smoke su evidentirani u v1.0.4 / §14.3. Lokalna implementacija registration correctiva (GET/POST `/register`) je evidentirana u v1.0.5 / §14.4. PO status te implementacije: **PO USVOJENO**. To **nije** production accepted i **nije** REG-14 / new-user E2E. D1–D15 **nisu** reotvorene. Kasnije D15 faze (stabilizacija, mirror OFF, CONTRACT, fizički DROP) **ostaju otvorene**. Step 9 **ostaje OPEN**. Level B real remediation **nije** izvršen.

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-09-04 | Otvoren dokumentacioni paket Technical Specification. Evidentiran AS-IS tehnički baseline i OPEN TECHNICAL DECISIONS. Status dokumenta: U IZRADI. Bez tehničkih odluka. Bez izmjene aplikacionog koda. |
| 0.1.1 | 2026-09-05 | Usklađenje sa DK-BM-002 v1.0.1 i DK-FS-002 v1.0.1: konceptualna kardinalnost jedan korisnički nalog : jedan platformski korisnički identitet. Poglavlja 3, 4, 5, 6, 9, 12 i 13. Fizički storage ostaje OPEN TECHNICAL DECISION 1. Status dokumenta ostaje U IZRADI. |
| 0.1.2 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 1: ciljni fizički storage obrazac naloga, tankog identity sloja i jedne aktivne subject-specific strukture. Poglavlja 6, 12 i 13. Decisions 2–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.3 | 2026-09-05 | Usklađenje sa DK-BM-002 v1.0.2 i DK-FS-002 v1.0.2: domen pravila 1:1 za registrovani platformski subjekt. PO usvojio OPEN TECHNICAL DECISION 2: NEW-MODEL-FIRST + LEGACY COMPATIBILITY. Poglavlja 6, 12, 13 i 14. Decisions 3–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.4 | 2026-09-05 | Konsolidovano predloženo mapiranje OPEN TECHNICAL DECISION 3: legacy `users.user_type` → ciljni identitet, bez proizvoljne promjene poslovnog identiteta. Poglavlja 6, 12, 13 i 14. Odluka 3 ostaje OPEN — spremna za PO pregled. Status dokumenta ostaje U IZRADI. |
| 0.1.5 | 2026-09-05 | Terminološko-semantički corrective Poglavlja 6.18: ovlašćeno lice / zastupnik; crnogorska terminologija. Mapiranje i status odluke 3 neizmijenjeni. Odluka 3 ostaje OPEN — spremna za PO pregled. Status dokumenta ostaje U IZRADI. |
| 0.1.6 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 3: semantičko mapiranje legacy `users.user_type` na ciljni identitet. Poglavlja 6, 12, 13 i 14. Decisions 4–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.7 | 2026-09-05 | Konsolidovan predloženi obrazac OPEN TECHNICAL DECISION 4: kompatibilnost KN `applicant_type` sa kanonskim platformskim identitetom. Poglavlja 6, 9, 12, 13 i 14. Odluka 4 ostaje OPEN — spremna za PO pregled. Decisions 5–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.8 | 2026-09-05 | Minor corrective Poglavlja 6.19: ručni izbor / implementacioni raskorak; crnogorska poslovna terminologija. Mapping, snapshot i status odluke 4 neizmijenjeni. Odluka 4 ostaje OPEN — spremna za PO pregled. Status dokumenta ostaje U IZRADI. |
| 0.1.9 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 4: kompatibilnost KN `applicant_type` sa kanonskim platformskim identitetom. Poglavlja 6, 9, 12, 13 i 14. Decisions 5–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.10 | 2026-09-05 | Konsolidovan predloženi obrazac OPEN TECHNICAL DECISION 5: kompatibilnost EP availability sa kanonskim platformskim identitetom. Poglavlja 6, 9, 12, 13 i 14. Odluka 5 ostaje OPEN — spremna za PO pregled. PO-usvojena pododluka: Nevladina fondacija i DSPD u EP V1 fail-closed dok se za njih posebno ne usvoje EP pravila dostupnosti. Decisions 6–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.11 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 5: kompatibilnost EP availability sa kanonskim platformskim identitetom. Poglavlja 6, 9, 12, 13 i 14. Decisions 6–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.12 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 6: zajednički kanonski katalog država; stabilni identifikator zasnovan na ISO 3166-1 alpha-2; identitetski podatak čuva kanonski country code. Poglavlja 6, 7, 9, 12, 13 i 14. Decisions 7–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.13 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 7: arhitektura kanonskih JMB/PIB validatora; pure identifier validator + Laravel Rule adapter; JMB 13 cifara + kontrolna cifra bez semantičke validacije datuma/regiona; PIB 8 cifara + ISO 7064 Modul 11,10 za Preduzetnika, Pravno lice i DSPD. Poglavlja 6, 7, 9, 12, 13 i 14. Decisions 8–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.14 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 8: kanonska V1 validacija CRPS registracionog broja; tačno 8 cifara; identifikaciona oznaka 1–6 prema aktivnom subjektu/Pravnom obliku; redni broj 0000001–9999999; bez checksum-a; digits-only kanonski zapis; pure CRPS validator + Laravel Rule adapter. Poglavlja 3, 6, 7, 9, 12, 13 i 14. Odluka 1 ostaje CLOSED; evidentiran follow-up alignment fizičke lokacije CRPS-a u FL/PL strukturama. Decisions 9–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.15 | 2026-09-05 | D1 CRPS coverage alignment: konceptualna fizička raspodjela CRPS-a u već usvojenoj D1 arhitekturi, prema DK-BM-002 v1.0.3, DK-FS-002 v1.0.3 i odluci 8. Preduzetnik CRPS u FL subject-specific strukturi; OD/KD/AD/DOO CRPS u PL subject-specific strukturi; DSPD CRPS ostaje u DSPD strukturi; NVO/sport bez CRPS polja u V1; centralni identity sloj bez CRPS. Odluka 1 ostaje CLOSED / nije reotvorena. Odluka 8 neizmijenjena. Decisions 9–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.16 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 9: kanonski katalog crnogorskih validacionih poruka registracije i korisničkog identiteta; server autoritativan; ista poruka za isto polje/pravilo/uslov; bez Laravel/default English poruka. Tačno sačuvano: „E-mail adrese se ne podudaraju.“ Pravila jačine lozinke nijesu dio ciljne validacije ovog paketa; AS-IS platformsko ponašanje D9 ne mijenja. Poglavlja 6, 7, 12, 13 i 14. Decisions 10–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.17 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 10: dopuna kanonskog identiteta postojećih korisnika; HYBRID (deterministička D3 projekcija + REQUIRE-ON-USE / DECLARE-ON-USE); field-/need-specific; newly active ≠ missing; bez globalne ponovne registracije. Poglavlja 4, 5, 6, 7, 9, 12, 13 i 14. Decisions 11–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.18 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 11: lokalizacija korisničkog sadržaja platformske e-mail poruke za verifikaciju; crnogorski jezik; kanonski termin E-mail adresa; jedan kanonski tekst; bez first_name; bez normativnog isticanja linka. Poglavlja 4, 6, 9, 10, 12, 13 i 14. Decisions 12–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.19 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 12: tehnički mehanizam e-mail verifikacije; `users.email_verified_at`; signed / time-limited / current-e-mail-bound zahtjev; rok 60 minuta; autentikacija prije verifikacije; link nije login; ponovno slanje kao tehnički recovery; bez kataloga verifikovanih funkcija. Poglavlja 4, 5, 6, 9, 10, 12, 13 i 14. Decisions 13–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.20 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 13: tehnička realizacija uklanjanja AS-IS ograničenja Grada na Opštinu Kotor u platformskoj registraciji i relevantnom profilu; Grad slobodan tekst; bez kataloga gradova; DSPD semantika adrese ogranka u Crnoj Gori bez lažne geografske validacije; NEW BUSINESS RULE REQUIRED: NO. Poglavlja 2, 6, 7, 9, 12, 13 i 14. Decisions 14–15 ostaju OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.21 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 14: migraciona strategija / backfill postojećih korisničkih podataka na D1 ciljni identitet; OPTION B; isti nalog; bez nagađanja; invalid legacy ≠ D10 missing; kanonski identitet jedini SSOT; izvedeni `users.user_type` mirror samo gdje je representable; Foundation bez lažnog mirror-a; NEW BUSINESS RULE REQUIRED: NO. Poglavlja 6, 7, 9, 10, 12, 13 i 14. Decision 15 ostaje OPEN. Status dokumenta ostaje U IZRADI. |
| 0.1.22 | 2026-09-05 | PO usvojio OPEN TECHNICAL DECISION 15: rollout / transition / cutover / rollback; OPTION 3 — SHADOW-FIRST HYBRID / REFINED; reader capability prije writer authority; isti logički cutover; bez opšteg dual-write-a; raw legacy nije current replica; rollback rizik od aktivacije kanonskog pisca; Foundation hard non-representability; NEW BUSINESS RULE REQUIRED: NO. Poglavlja 6, 7, 9, 10, 12, 13 i 14. Decisions 1–15 CLOSED / PO USVOJENO. Otvorenih tehničkih odluka: 0. Status dokumenta ostaje U IZRADI. Usvajanje D15 **ne** autorizuje produkcioni deploy, census, backfill ni DROP. |
| 1.0.0 | 2026-09-05 | Finalno PO usvajanje DK-TS-002 kao cjeline. Status dokumenta: USVOJENO. D1–D15 CLOSED / PO USVOJENO. OPEN TECHNICAL DECISIONS: NONE. Otvorenih odluka: 0. Finalization Review: PASS. BM → FS sljedivost: PASS. FS → TS sljedivost: PASS. D1–D15 fully integrated: YES. Real contradictions: 0. Finalization blockers: 0. Normativni sadržaj D1–D15 nije mijenjan. Usvajanje specifikacije **nije** izvršenje implementacije, census, backfill, migracije, produkcionog rollout-a, fizičkog DROP-a legacy kolona ni deploy-a. |
| 1.0.1 | 2026-09-06 | Status-only production closeout D15 Step 8. Capability i logički cutover = PO USVOJENO / CLOSED / PRODUCTION PASS. GATE 1 CLOSED / PASS. GATE 2 CLOSED / PASS. Kanonski identitet je produkcioni autoritet. R1 ACTIVE. D1–D15 nijesu reotvorene. Normativni sadržaj Poglavlja 1–13 nije mijenjan. BM/FS KEEP. Kasnije D15 faze ostaju otvorene. |
| 1.0.2 | 2026-09-06 | Implementaciona evidencija lokalizacije kanonskog kataloga država: 249 ISO + XK; crnogorski display label; identitet ostaje code-based; shared display-label source za registracioni phone picker; calling-code identitet ostaje odvojen; bez DB migracije/backfill/reconcile; D1–D15 nijesu reotvorene; Step 8 autoritet i Step 9 observation model neizmijenjeni. BM/FS KEEP. DK-RG-001 KEEP. |
| 1.0.3 | 2026-09-07 | Implementaciona evidencija PO-usvojene post-cutover dopune postojećih poslovnih subjekata bez kanonskog grafa (12 legacy DOO + 1 legacy Preduzetnik). REQUIRE-ON-USE; `CanonicalIdentityWriter::createForUser()`; bez novog writera; bez globalnog login/dashboard gate-a; CRPS mark 5/1; AP user-supplied; Corrective 01: `DerivedUserTypeMirror` ostaje `final`. D1–D15 nijesu reotvorene. Step 9 ostaje OPEN. BM/FS KEEP. DK-RG-001 KEEP. |
| 1.0.4 | 2026-09-07 | Produkcioni deploy commit-a `59f3d46cc38f4c3792cf6b5d5324828533793f7e` i Level A non-mutating production smoke = PASS. Live `about`: Config/Events/Routes NOT CACHED; Views CACHED; operator `view:cache`. Bez migracije/seeder-a/composer/npm/.env/flag/EP. Level B NOT YET EXECUTED. D1–D15 nijesu reotvorene. Step 9 ostaje OPEN. BM/FS KEEP. DK-RG-001 KEEP. |
| 1.0.5 | 2026-09-07 | Nenormativna evidencija registration correctiva. Implementacija usvojenog V1 registration ugovora na kanonskom GET/POST `/register` putu = **PO USVOJENO**. Evidencija ostaje IMPLEMENTATION / CORRECTIVE. **Nije** production accepted. **Nije** REG-14 / new-user E2E. D1–D15 nijesu reotvorene. D16 nije kreiran. Step 9 ostaje OPEN. BM/FS KEEP. DK-RG-001 KEEP. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument opisuje tehničku realizaciju usvojenog `DK-FS-002` za registraciju i korisnički identitet Platforme.

DK-TS-002:

* tehnički razrađuje usvojena pravila `DK-BM-002` i `DK-FS-002`;
* **ne** uvodi nova poslovna pravila;
* **ne** mijenja Functional Specification;
* **ne** predstavlja napisan aplikativni kod;
* na otvaranju **ne** donosi ciljne tehničke odluke.

Tehničke odluke 1–15 su CLOSED / PO USVOJENO. Trenutno nema otvorenih tehničkih odluka.

Jedini izvor poslovnih pravila je **DK-BM-002** v1.0.4.

Jedini izvor zahtijevanog posmatranog ponašanja je **DK-FS-002** v1.0.4.

Ako se TS i FS razlikuju, **FS pobjedjuje** za funkcionalno ponašanje. Ako se FS i BM razlikuju, **BM pobjedjuje**.

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | USVOJENO |
| 2. Arhitektonski principi | USVOJENO |
| 3. Tehnički model | USVOJENO |
| 4. Tokovi | USVOJENO |
| 5. Autorizacija i ovlašćenja | USVOJENO |
| 6. Model podataka | USVOJENO |
| 7. Validacije | USVOJENO |
| 8. Evidencija aktivnosti (Audit) | USVOJENO |
| 9. Integracije | USVOJENO |
| 10. Nefunkcionalni zahtjevi | USVOJENO |
| 11. Granice V1 (Out of Scope) | USVOJENO |
| 12. Otvorena pitanja | USVOJENO |
| 13. Matrica sljedivosti | USVOJENO |
| 14. Napomene za implementaciju | USVOJENO |

Ukupan status dokumenta: **USVOJENO**.

Istorijski redovi verzija 0.1.0–0.1.22 koji navode U IZRADI ili OPEN odluke opisuju **tadašnje** stanje te verzije. Nisu trenutno normativno stanje.

## Evidencija Finalization Review i finalnog PO usvajanja

| Stavka | Rezultat |
|--------|----------|
| FINALIZATION REVIEW | PASS |
| BM → FS TRACEABILITY | PASS |
| FS → TS TRACEABILITY | PASS |
| D1–D15 FULLY INTEGRATED | YES |
| REAL CONTRADICTIONS | 0 |
| FINALIZATION BLOCKERS | 0 |
| OPEN TECHNICAL DECISIONS | NONE |
| OPEN DECISIONS | 0 |
| D1–D15 | CLOSED / PO USVOJENO |
| PO FINAL DECISION | DK-TS-002 = USVOJENO |

Usvajanje DK-TS-002 v1.0.0 **ne** autorizuje implementaciju, data census, pristup bazi, backfill, izvršenje migracije, produkcioni rollout, fizički DROP legacy kolona ni deploy. Naredna implementaciona faza mora posebno slijediti usvojene D14/D15 kapije.

Produkcioni D15 Step 8 closeout (capability i logički cutover) je evidentiran u v1.0.1 / §14.1. Lokalizacija kanonskog kataloga država je evidentirana u v1.0.2 / §14.2. Post-cutover dopuna postojećih poslovnih subjekata je evidentirana u v1.0.3 / §14.3. Produkcioni deploy te dopune i Level A smoke su evidentirani u v1.0.4 / §14.3. To **ne** mijenja značenje usvajanja v1.0.0, **ne** reotvara D1–D15 i **ne** zatvara kasnije D15 faze (stabilizacija, preostala kompatibilnost, mirror OFF, CONTRACT, fizički DROP). Step 9 **ostaje OPEN**. Level B real remediation **nije** izvršen.

---

# Pravila upravljanja dokumentom

1. DK-TS-002 pripada platformskom sloju `DK-*` (registracija i korisnički identitet).
2. Tehnički sadržaj mora ostati usklađen sa `DK-BM-002` v1.0.4 i `DK-FS-002` v1.0.4.
3. Nova poslovna i funkcionalna pravila se ne uvode kroz DK-TS-002.
4. `OPEN BUSINESS QUESTIONS` i `OPEN FS DECISIONS` ostaju **NONE**. Tehničke dileme žive samo kao `OPEN TECHNICAL DECISIONS`.
5. AS-IS baseline se ne pretvara automatski u ciljno rješenje.
6. Izmjene sadržaja evidentiraju se novim redom u istoriji verzija.

---

# 1. Pregled funkcionalne cjeline

**Sekcijska sljedivost:** DK-BM-002 §1, §4, §11, §15, §17; DK-FS-002 §1, §2, §18, §20

## 1.1 Predmet

Predmet ovog TS-a je tehnička realizacija:

* registracije novog korisnika prema kanonskom modelu `DK-FS-002`;
* čuvanja platformskog korisničkog identiteta;
* verifikacije e-mail adrese;
* dopune postojećeg profila bez ponovne registracije;
* tehničkih granica prema KK / KN / EP.

## 1.2 Tehnička granica

U obuhvatu su, kada budu odlučene i razrađene:

* rute, kontroleri, view-ovi i validacije registracije i profila;
* `User` model i `users` šema;
* mapiranje postojećeg `users.user_type`;
* katalog država;
* JMB / PIB / CRPS validatori;
* katalog korisničkih validacionih poruka;
* tehnička verifikacija e-maila;
* migracije i transitional stanja;
* očuvanje postojećih veza drugih modula.

Van obuhvata ovog dokumenta na otvaranju:

* izmjena `DK-BM-002` / `DK-FS-002`;
* kreiranje `DK-UC-002`;
* izmjena KN / EP / KK poslovnih pravila;
* implementacija u ovom dokumentacionom koraku.

## 1.3 Mapiranje predloženih tema na M-TS-005

M-TS-005 nalaže fiksna nazive i redoslijed 14 poglavlja. Teme iz pripremne analize smještene su kao podpoglavlja, bez spajanja tema različitog tehničkog rizika:

| Tema | TS poglavlje |
|------|----------------|
| Predmet i tehnička granica; izvori | §1 |
| Postojeća tehnička arhitektura | §2, §4 |
| Ciljni model identiteta | §3 |
| Model podataka i storage | §6 |
| Vrsta subjekta i Pravni oblik; FL/Preduzetnik; Pravno lice; DSPD | §3, §6 |
| Identifikatori; rezidentnost i države; telefon; adresa | §6, §7 |
| E-mail, autentifikacija, verifikacija; lozinka | §4, §5, §7 |
| Serverska i klijentska validacija; poruke | §7 |
| Dopuna profila | §4, §6.25, §7.19 |
| Legacy / transitional; migracija; rollback | §6, §10, §12 |
| KN / EP / KK | §9 |
| Sigurnost; testovi; deployment | §10 |
| Tehničke zabrane | §11, §14 |
| Otvorene tehničke odluke | §12 |
| Prihvatni tehnički kriterijumi | §10, §13 |
| Sljedivost ka DK-FS-002 | §13 |

---

# 2. Arhitektonski principi

**Sekcijska sljedivost:** DK-BM-002 §11, §15, §16; DK-FS-002 §16, §18

## 2.1 Mjesto u sistemu

Registracija i korisnički identitet pripadaju **platformskom sloju** `DK-*`.

Platforma je vlasnik:

* registracije;
* platformskog korisničkog identiteta;
* osnovnog profila;
* verifikacije e-mail adrese.

KN koristi platformski identitet u granicama svog funkcionalnog modela. Zadržava sopstveni konkursni model i `applicant_type`. **Ne** određuje platformski korisnički identitet.

EP koristi relevantne platformske identitetske podatke za svoja pravila dostupnosti. **Nije** vlasnik platformskog korisničkog identiteta.

KK koristi platformski korisnički nalog. Zadržava sopstveni autorizacioni model. **Ne** određuje Vrstu subjekta ni Pravni oblik Platforme.

Platformska uloga i autorizacija **nisu** Vrsta subjekta. Tehnički model uloga ne poistovjećuje se sa poslovnim identitetom korisnika.

## 2.2 AS-IS arhitektura (činjenica, nije cilj)

Postojeća registracija i korisnički identitet već imaju aktivnu tehničku realizaciju u Platformi.

Ta realizacija je tranziciono AS-IS stanje. **Nije** automatski ciljni model `DK-TS-002`.

Postojeće veze i zavisnosti prema drugim modulima moraju biti uzete u obzir pri budućoj tehničkoj razradi. Način te razrade **nije** odlučen ovim poglavljem.

## 2.3 Principi (usvojeni BM/FS, bez izbora storage-a)

* Platformski identitet i modulska prihvatljivost/dostupnost/uloge se ne miješaju poslovno.
* Platformska uloga i Vrsta subjekta ostaju konceptualno odvojene.
* Postojeći korisnik ne ponavlja registraciju.
* Nepotpun profil sam po sebi nije globalna blokada naloga.
* Istorija postojećeg naloga i njegove postojeće veze moraju ostati očuvane.
* Tehnička promjena identitetskog modela ne smije proizvoljno promijeniti poslovni identitet postojećeg korisnika.
* Tehnička promjena ne smije proizvoljno promijeniti značenje postojećih KN podataka.
* Tehnička promjena ne smije proizvoljno promijeniti postojeća EP pravila dostupnosti.

Kako se ova ograničenja tehnički čuvaju **nije** odlučeno ovim poglavljem.

## 2.4 Konflikt AS-IS vs FS (evidentiran; ciljna korekcija usvojena odlukom 13)

Postojeća platformska validacija adrese i Grada ograničava vrijednosti na Opštinu Kotor.

`DK-FS-002` §14 / §17 i `DK-BM-002` §10 tač. 14: Grad **nije** ograničen na Opštinu Kotor.

To je tehnički nalaz. AS-IS ograničenje je suprotno ciljnom BM/FS ponašanju. Ciljna tehnička realizacija uklanjanja tog ograničenja usvojena je odlukom 13 (Poglavlje 6.28). Ovo poglavlje **ne** implementira korekciju. AS-IS runtime ostaje tehnički dug do implementacije.

---

# 3. Tehnički model

**Sekcijska sljedivost:** DK-BM-002 §3, §5–§9, §11, §15, §16; DK-FS-002 §5–§9, §16, §18

Ovo poglavlje razrađuje **ciljni konceptualni** tehnički model usvojenog `DK-BM-002` / `DK-FS-002`. Fizički model podataka **nije** određen ovim poglavljem. Razrađuje se u odgovarajućim kasnijim poglavljima.

## 3.1 Konceptualne cjeline

### Korisnički nalog

Korisnički nalog je platformska cjelina preko koje korisnik pristupa Platformi.

Nalog se **ne** poistovjećuje sa Vrstom subjekta, Pravnim oblikom, Statusom rezidentnosti niti sa platformskom ili modulskom ulogom.

### Platformski korisnički identitet

Platformski korisnički identitet je kanonski identitet korisnika na nivou Platforme.

Obuhvata identitetske podatke koji zavise od Vrste subjekta i aktivnog identitetskog konteksta. Fizički obrazac čuvanja usvojen je odlukom 1 (Poglavlje 6.15). Ovo poglavlje **ne** usvaja konačne nazive SQL tabela.

Pravilo 1:1 primjenjuje se na nalog koji predstavlja registrovani platformski subjekt. Takav nalog ima tačno jedan platformski korisnički identitet. Taj identitet pripada tačno tom jednom nalogu (`DK-BM-002` v1.0.2 §3; `DK-FS-002` v1.0.2 §5). Isti takav nalog ne može paralelno predstavljati više platformskih korisničkih identiteta. Korisnički profil pripada tom istom nalogu i tom jedinstvenom identitetu.

Fizički obrazac te kardinalnosti usvojen je odlukom 1: za nalog registrovanog platformskog subjekta postoji tačno jedan fizički zapis platformskog identiteta (Poglavlje 6.15). Interni ili staff nalog koji postoji isključivo radi platformske uloge **nije** obavezan da ima taj identity zapis samo zato što postoji `users` nalog. Ovo poglavlje **ne** usvaja konačne nazive SQL tabela niti migraciju.

### Uloge i autorizacije

Platformske i modulske uloge i autorizacije konceptualno su odvojene od platformskog korisničkog identiteta.

Uloga **nije** Vrsta subjekta. Uloga **nije** Pravni oblik. KN `applicant_type` **nije** platformska Vrsta subjekta. Interni ili staff nalog koji postoji isključivo radi platformske uloge **ne** mora imati platformski korisnički identitet Fizičkog lica, Pravnog lica ili Dijela stranog privrednog društva samo zato što nalog postoji. Ne uvodi se četvrta Vrsta subjekta za staff.

## 3.2 Vrsta subjekta

Ciljni koncept ima **tačno tri** Vrste subjekta:

1. Fizičko lice
2. Pravno lice
3. Dio stranog privrednog društva

Preduzetnik **nije** četvrta Vrsta subjekta.

U jednom trenutku aktivna je samo jedna Vrsta subjekta.

## 3.3 Fizičko lice

Fizičko lice može biti, ili ne biti, registrovano kao Preduzetnik.

Status rezidentnosti pripada Fizičkom licu. Vrijednosti:

* Rezident
* Nerezident

Identifikacioni kontekst Fizičkog lica:

* Rezident — JMB je lični identifikacioni podatak prema usvojenom `DK-FS-002`.
* Nerezident — koristi izabrani identifikacioni dokument prema usvojenom `DK-FS-002`. Kada je dokument pasoš, država izdavanja pripada tom identifikacionom kontekstu.

Kanonska arhitektura validatora JMB-a i PIB-a usvojena je odlukom 7 (Poglavlje 6.22). Identifikatori Fizičkog lica pripadaju subject-specific strukturi Fizičkog lica (Poglavlje 6.15).

## 3.4 Preduzetnik

Preduzetnik ostaje Fizičko lice. Zadržava lični identitet Fizičkog lica. **Nije** drugi platformski identitet niti četvrta Vrsta subjekta; ostaje poslovni kontekst istog identiteta Fizičkog lica (`DK-BM-002` v1.0.2 §3). Fizički ostaje u strukturi Fizičkog lica (Poglavlje 6.15). Posebna entrepreneur identity struktura se **ne** uvodi.

Uz lični identitet, Preduzetnik ima:

* poslovni naziv preduzetnika;
* PIB;
* CRPS registracioni broj (`DK-BM-002` v1.0.3 §7; `DK-FS-002` v1.0.3 §7).

PIB i CRPS registracioni broj su konceptualno različiti podaci. Tehnička validacija CRPS-a usvojena je odlukom 8 (Poglavlje 6.23). Konceptualna fizička lokacija CRPS-a Preduzetnika je subject-specific struktura Fizičkog lica (Poglavlje 6.15; D1 CRPS coverage alignment). Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena.

Status rezidentnosti primjenjuje se na Preduzetnika kao na Fizičko lice.

## 3.5 Pravno lice

Pravno lice je posebna Vrsta subjekta. Ima sopstveni identitet pravnog lica, PIB, Pravni oblik i podatke ovlašćenog lica prema `DK-FS-002`.

Za Pravne oblike Ortačko društvo (OD), Komanditno društvo (KD), Akcionarsko društvo (AD) i Društvo sa ograničenom odgovornošću (DOO) kanonski identitet u V1 uključuje i CRPS registracioni broj (`DK-BM-002` v1.0.3 §8; `DK-FS-002` v1.0.3 §8). CRPS se u V1 **ne prikuplja** za Nevladino udruženje, Nevladinu fondaciju i Sportsku organizaciju.

PIB i CRPS registracioni broj su konceptualno različiti podaci. CRPS **nije** obavezan samo zato što je Vrsta subjekta Pravno lice. Tehnička validacija CRPS-a usvojena je odlukom 8 (Poglavlje 6.23). Konceptualna fizička lokacija CRPS-a za OD, KD, AD i DOO je subject-specific struktura Pravnog lica (Poglavlje 6.15; D1 CRPS coverage alignment). Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena.

Pravno lice **nema** Status rezidentnosti.

Pravni oblik primjenjuje se **samo** na Pravno lice. **Nije** Vrsta subjekta, **nije** platformska uloga i **nije** KN `applicant_type`.

Kanonski Pravni oblici su **tačno**:

1. Ortačko društvo (OD)
2. Komanditno društvo (KD)
3. Društvo sa ograničenom odgovornošću (DOO)
4. Akcionarsko društvo (AD)
5. Nevladino udruženje
6. Nevladina fondacija
7. Sportska organizacija

Ne uvode se: generičko NVO; Ustanova; Ostalo; Dio stranog privrednog društva kao Pravni oblik.

Ovlašćeno lice ima sopstveni lični identifikacioni kontekst prema usvojenom `DK-FS-002`. **Nije** platformski korisnik niti drugi platformski identitet. Fizički je zaseban 1:1 povezani zapis u kontekstu Pravnog lica (Poglavlje 6.15). Konačan naziv tabele **nije** usvojen.

## 3.6 Dio stranog privrednog društva

Dio stranog privrednog društva je posebna Vrsta subjekta. **Nije** Pravno lice u platformskom modelu. **Nije** Pravni oblik. **Nema** Status rezidentnosti kao platformski identitetski atribut.

Konceptualni model razlikuje:

* strano privredno društvo;
* dio / ogranak u Crnoj Gori;
* zastupnika;
* PIB dijela / ogranka prema usvojenom `DK-FS-002`;
* CRPS registracioni broj;
* identifikacioni podatak zastupnika prema usvojenom `DK-FS-002`.

PIB i CRPS registracioni broj su konceptualno različiti podaci.

CRPS registracioni broj **nije** identifikator isključivo Dijela stranog privrednog društva. U V1 se zahtijeva i za Preduzetnika i za Pravno lice oblika OD, KD, AD i DOO (`DK-BM-002` v1.0.3; `DK-FS-002` v1.0.3). Tehnička validacija CRPS-a usvojena je odlukom 8 (Poglavlje 6.23).

CRPS DSPD-a ostaje u subject-specific strukturi Dijela stranog privrednog društva (Poglavlje 6.15). CRPS Preduzetnika i CRPS za OD/KD/AD/DOO pripadaju odgovarajućim FL / PL subject-specific strukturama (D1 CRPS coverage alignment). Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena. Konačan naziv tabele **nije** usvojen.

## 3.7 Razdvajanje identifikatora

Konceptualno su odvojeni:

* JMB Fizičkog lica;
* identifikacioni dokument Nerezidenta;
* PIB poslovnog subjekta;
* CRPS registracioni broj;
* identifikacioni podatak ovlašćenog lica;
* identifikacioni podatak zastupnika.

Jedan konceptualni identifikator **ne** preuzima automatski značenje drugog. Fizičke kolone **nisu** određene ovim poglavljem.

## 3.8 Konceptualne invarijante

Sljedeće invarijante proizlaze iz usvojenog `DK-BM-002` / `DK-FS-002`. Ovo poglavlje **ne** uvodi lifecycle promjene Vrste subjekta.

* Za nalog registrovanog platformskog subjekta postoji tačno jedan platformski korisnički identitet, a taj identitet pripada tačno tom jednom nalogu (`DK-BM-002` v1.0.2 §3; `DK-FS-002` v1.0.2 §5).
* Interni ili staff nalog koji postoji isključivo radi platformske uloge **nije** obavezan da ima taj identitet samo zato što nalog postoji.
* Isti nalog registrovanog platformskog subjekta ne može paralelno predstavljati više platformskih korisničkih identiteta.
* Platformski korisnički identitet ima jednu aktivnu Vrstu subjekta. Ta aktivna Vrsta subjekta pripada tom jedinstvenom identitetu.
* Preduzetnik ostaje Fizičko lice. **Nije** drugi platformski identitet.
* Pravni oblik primjenjuje se samo na Pravno lice.
* Status rezidentnosti primjenjuje se samo na Fizičko lice, uključujući Preduzetnika.
* Pravno lice i Dio stranog privrednog društva nemaju Status rezidentnosti.
* Identifikacioni podaci zavise od aktivnog identitetskog konteksta.
* Uslovni podatak koji nije primjenjiv na aktivni kontekst ne određuje aktivni identitet.
* Platformska ili modulska uloga ne određuje Vrstu subjekta niti Pravni oblik.

## 3.9 Postojeći korisnici

Na konceptualnom nivou:

* postojeći korisnik zadržava isti korisnički nalog;
* nema ponovne registracije;
* postojeća istorija i veze naloga ostaju očuvane;
* nedostajući podaci ciljnog identitetskog modela mogu biti predmet dopune profila;
* nepotpun profil sam po sebi nije globalna blokada naloga.

UI dopune, gate, migracija, backfill, legacy mapping i trenutak zahtijevanja podataka **nisu** određeni ovim poglavljem.

## 3.10 AS-IS vs ciljni model

Postojeći tehnički model spaja identitetske koncepte koje ciljni model razdvaja. Ta realizacija je tranziciono AS-IS stanje. **Nije** automatski ciljni model `DK-TS-002`.

---

# 4. Tokovi

**Sekcijska sljedivost:** DK-BM-002 §3, §4, §5–§9, §11; DK-FS-002 §4, §5–§9, §11, §15, §16

Ovo poglavlje razrađuje **ciljne tokove** usvojenog `DK-BM-002` / `DK-FS-002`. Ne određuje kontrolere, rute, middleware, notifikacije, storage ni validacione algoritme.

## 4.1 Ciljni tok registracije

1. Korisnik pristupa registraciji na Platformi.
2. Bira Vrstu subjekta. Dok Vrsta subjekta nije izabrana, grane Fizičko lice, Pravno lice i Dio stranog privrednog društva nisu aktivne.
3. Platforma aktivira identitetski kontekst izabrane Vrste subjekta.
4. Prikazuju se i aktiviraju zajednički podaci i podaci primjenjivi na izabrani kontekst.
5. Korisnik popunjava aktivne obavezne podatke, uključujući ponovni unos e-mail adrese i korisničke lozinke radi potvrde, prema usvojenom `DK-FS-002`.
6. Korisnik bira **Registruj se**.
7. Platforma prihvata završetak registracije samo kada aktivni obavezni podaci ispunjavaju usvojene zahtjeve.
8. Uspješnim završetkom nastaje nalog registrovanog platformskog subjekta sa tačno jednim platformskim korisničkim identitetom. Registracija ne kreira više paralelnih platformskih identiteta za isti nalog (`DK-FS-002` v1.0.2 §5).
9. Pokreće se tok verifikacije e-mail adrese.
10. Dalje korišćenje funkcija koje zahtijevaju verifikovan nalog zavisi od uspješne verifikacije.

Detaljne validacije pripadaju Poglavlju 7.

## 4.2 Tok — Fizičko lice

Kada je Vrsta subjekta **Fizičko lice**, tok uključuje pitanje:

**Da li ste registrovani kao preduzetnik?**

Vrijednosti: Da / Ne.

Zatim Status rezidentnosti: Rezident / Nerezident.

* Rezident — aktivan je JMB identifikacioni kontekst prema `DK-FS-002`.
* Nerezident — aktivan je izabrani identifikacioni dokument prema `DK-FS-002`. Kada je izabran pasoš, aktivna je i država izdavanja prema `DK-FS-002`.

Ako je odgovor na preduzetništvo **Da**, aktiviraju se poslovni naziv preduzetnika i PIB. Preduzetnik ostaje Fizičko lice; **nije** nova Vrsta subjekta.

Ako je odgovor **Ne**, preduzetnički podaci nisu dio aktivnog toka.

## 4.3 Tok — Pravno lice

Kada je Vrsta subjekta **Pravno lice**, tok uključuje:

* izbor Pravnog oblika iz usvojenog zatvorenog kataloga;
* podatke identiteta Pravnog lica;
* PIB;
* podatke ovlašćenog lica;
* identifikacioni kontekst ovlašćenog lica;
* zajedničke podatke registracije.

U ovom toku nema Statusa rezidentnosti i nema pitanja o Preduzetniku. Ovlašćeno lice fizički je zaseban 1:1 povezani zapis u kontekstu Pravnog lica (Poglavlje 6.15). Konačan naziv tabele **nije** usvojen.

## 4.4 Tok — Dio stranog privrednog društva

Kada je Vrsta subjekta **Dio stranog privrednog društva**, tok uključuje:

* podatke stranog privrednog društva;
* podatke dijela / ogranka u Crnoj Gori;
* PIB;
* CRPS registracioni broj;
* podatke zastupnika;
* identifikacioni kontekst zastupnika;
* zajedničke podatke registracije.

U ovom toku nema Statusa rezidentnosti, nema Pravnog oblika i nema pitanja o Preduzetniku. Zastupnik fizički je zaseban 1:1 povezani zapis u kontekstu Dijela stranog privrednog društva (Poglavlje 6.15). Konačan naziv tabele **nije** usvojen.

## 4.5 Aktivni identitetski kontekst

U jednom trenutku aktivna je samo jedna Vrsta subjekta.

Samo trenutno primjenjivi podaci učestvuju u završetku registracije. Podatak iz neaktivnog identitetskog konteksta **ne** određuje aktivni platformski korisnički identitet.

Način zadržavanja ili brisanja prethodno unesenih vrijednosti u formi **nije** određen ovim poglavljem.

## 4.6 Tok verifikacije e-mail adrese

Nakon uspješne registracije postoji platformski korisnički nalog i pokreće se verifikacija e-mail adrese. Korisnik završava verifikacioni tok. E-mail adresa dobija verifikovano stanje. Funkcije koje zahtijevaju verifikovan nalog mogu postati dostupne u skladu sa svojim pravilima.

Dok verifikacija nije završena:

* postojeći nalog ostaje isti nalog;
* korisnik se ne registruje ponovo;
* funkcije koje zahtijevaju verifikovan nalog ostaju nedostupne.

Korisnički vidljivi sadržaj platformske e-mail poruke za verifikaciju usvojen je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam verifikacije usvojen je odlukom 12 (Poglavlje 6.27): stanje na nalogu, slanje nakon uspješne registracije, autentikacija prije verifikacije, rok, ponovno slanje kao tehnički recovery. Verification link **nije** mehanizam prijave. Ovo poglavlje **ne** sastavlja katalog funkcija koje zahtijevaju verifikovan nalog.

## 4.7 Greška i prekid registracije

Ako aktivni obavezni podaci ne zadovoljavaju usvojene zahtjeve, registracija se ne završava uspješno. Korisnik može ispraviti podatke u toku registracije. Prekid prije uspješnog završetka ne predstavlja uspješno završen platformski korisnički identitet.

HTTP kodovi, retry, redovi, transakcione granice i rollback **nisu** određeni ovim poglavljem.

## 4.8 Tok dopune postojećeg profila

1. Postojeći korisnik koristi postojeći nalog.
2. Ne prolazi ponovnu registraciju.
3. Nepotpun profil sam po sebi nije globalna blokada naloga.
4. Korisnik pristupa funkcionalnosti.
5. Ako toj konkretnoj funkcionalnosti nedostaje identitetski podatak koji joj je potreban, može se zahtijevati dopuna.
6. Platforma omogućava dopunu potrebnog identitetskog podatka na postojećem profilu.
7. Nakon uspješne dopune korisnik nastavlja prema funkcionalnosti koja je dopunu zahtijevala.

Dopuna ili izmjena identitetskih podataka na postojećem nalogu **ne** kreira drugi platformski identitet (`DK-FS-002` v1.0.2 §16).

Platforma je odgovorna za tok i čuvanje dopune platformskog korisničkog identiteta. Konkretni modul ili funkcionalnost, u granicama svog usvojenog modela, određuje kada mu je određeni platformski identitetski podatak potreban.

Konkretan tehnički mehanizam dopune profila usvojen je odlukom 10 (Poglavlje 6.25).

Ovaj tok **nije** lifecycle promjene Vrste subjekta, Pravnog oblika, PIB-a, JMB-a, CRPS registracionog broja, Statusa rezidentnosti niti preduzetništva nakon registracije. Takvi tokovi **nisu** definisani u `DK-BM-002` / `DK-FS-002` i **nisu** predmet ovog poglavlja.

## 4.9 Kontinuitet postojećeg korisnika

Postojeći korisnik zadržava isti korisnički nalog, bez ponovne registracije. Postojeća istorija i veze naloga ostaju očuvane.

Dopuna nedostajućih podataka vrši se na postojećem profilu. **Ne** kreira drugi platformski identitet. Sama po sebi ne predstavlja promjenu Vrste subjekta niti drugu lifecycle promjenu platformskog korisničkog identiteta koja nije definisana u `DK-BM-002` / `DK-FS-002`.

Nepotpun profil sam po sebi nije globalna blokada naloga.

## 4.10 AS-IS vs ciljni tok

Postojeća tehnička realizacija ne predstavlja u cjelosti ciljne tokove `DK-BM-002` / `DK-FS-002`. Konkretna postojeća implementacija nije ciljni model ovog poglavlja.

---

# 5. Autorizacija i ovlašćenja

**Sekcijska sljedivost:** DK-BM-002 §2, §11, §15; DK-FS-002 §4, §11.3, §16, §18

Ovo poglavlje razrađuje **ciljni autorizacioni model** usvojenog `DK-BM-002` / `DK-FS-002`. Ne uvodi nova poslovna ovlašćenja. Ne određuje middleware, gate, policy, rute ni storage uloga.

## 5.1 Pristup registraciji

Registracija novog korisničkog naloga dostupna je neregistrovanom korisniku. Prethodna autentifikacija nije uslov za ulazak u registraciju.

Prije registracije nema autorizacionog razlikovanja prema Vrsti subjekta. Izbor Vrste subjekta određuje identitetski tok, a ne autorizacionu ulogu.

## 5.2 Korisnički nalog i verifikacija e-mail adrese

Razlikuju se tri autorizacione činjenice:

* korisnički nalog postoji;
* e-mail adresa jeste ili nije verifikovana;
* konkretna funkcionalnost može imati dodatne uslove pristupa.

Uspješnom registracijom postoji korisnički nalog. Dok e-mail adresa nije verifikovana, funkcije koje zahtijevaju verifikovan nalog ostaju nedostupne (`DK-FS-002` §11.3).

Aktivan neverifikovani nalog **smije** se autentikovati. Verifikacija **nije** preduslov prijave. Autentikacija i pristup funkcijama koje zahtijevaju verifikovan nalog ostaju odvojene činjenice.

Verifikacija e-mail adrese **ne** daje automatski pristup svim funkcijama. Nakon verifikacije ostali uslovi konkretne funkcionalnosti i dalje se primjenjuju.

Tehnički mehanizam i primitiv provjere kanonskog stanja verifikacije usvojeni su odlukom 12 (Poglavlje 6.27). Odluka 12 **ne** sastavlja katalog funkcija koje zahtijevaju verifikovan nalog. Postojeći AS-IS globalni `verified` obuhvat ruta **nije** automatski ciljni katalog.

## 5.3 Dopuna sopstvenog profila

Postojeći korisnik koristi postojeći nalog. Kada funkcionalnosti nedostaje potreban platformski identitetski podatak, korisniku se može zahtijevati dopuna. Korisnik dopunjava nedostajući podatak sopstvenog platformskog korisničkog identiteta na postojećem profilu. Dopuna ili izmjena identitetskih podataka na postojećem nalogu **ne** kreira drugi platformski identitet (`DK-FS-002` v1.0.2 §16). Nakon uspješne dopune nastavlja prema funkcionalnosti koja je podatak zahtijevala.

Nepotpun profil sam po sebi nije globalna blokada naloga (`DK-BM-002` §11; `DK-FS-002` §16).

Dopuna nedostajućeg podatka **nije** slobodna izmjena identiteta. Ovo poglavlje **ne** uvodi pravo korisnika da proizvoljno mijenja Vrstu subjekta, Pravni oblik, JMB, PIB, CRPS registracioni broj, Status rezidentnosti niti status Preduzetnika. Takav lifecycle **nije** definisan u `DK-BM-002` / `DK-FS-002`.

Konkretan tehnički mehanizam dopune profila usvojen je odlukom 10 (Poglavlje 6.25).

## 5.4 Tuđi platformski korisnički identitet

`DK-BM-002` i `DK-FS-002` nisu usvojili ovlašćenje drugog korisnika ili administratorske uloge da mijenja ili dopunjava tuđi platformski korisnički identitet.

Ovo poglavlje takvo ovlašćenje **ne** uvodi. Time se ne donosi nova poslovna zabrana izvan ovog BM/FS/TS obuhvata.

## 5.5 Uloga i identitet

Platformska uloga **nije** Vrsta subjekta. Modulska uloga **nije** Vrsta subjekta. Uloga **nije** Pravni oblik. Uloga **ne** određuje Status rezidentnosti. KN `applicant_type` **nije** platformska Vrsta subjekta. Vrsta subjekta sama po sebi **ne** određuje platformsku ili modulsku ulogu (`DK-BM-002` §15; `DK-FS-002` §18).

Autorizacioni model **nije** zamjena za platformski korisnički identitet. Storage uloga i mapping **nisu** određeni ovim poglavljem.

## 5.6 Platforma i moduli

Platforma je vlasnik korisničkog naloga, platformskog korisničkog identiteta, osnovnog profila, verifikacije e-mail adrese i platformskog toka dopune identiteta.

Konkretni modul ili funkcionalnost primjenjuje sopstvena usvojena pravila pristupa. Može zahtijevati platformski identitetski podatak kada je to predviđeno njegovim usvojenim modelom. **Ne** redefiniše platformski korisnički identitet.

KN, EP i KK mapping, API i integracioni ugovori **nisu** predmet ovog poglavlja.

## 5.7 Neverifikovana e-mail adresa i nepotpun profil

Neverifikovana e-mail adresa i nepotpun profil su **dvije različite** autorizacione činjenice. Ne spajaju se u jedno ciljno pravilo.

Neverifikovana e-mail adresa ograničava funkcije koje zahtijevaju verifikovan nalog.

Nepotpun profil sam po sebi nije globalna blokada naloga. Autorizaciona posljedica nedostajućeg podatka nastaje samo u kontekstu funkcionalnosti kojoj je taj podatak potreban prema njenom usvojenom modelu.

## 5.8 Kontinuitet postojećeg naloga

Postojeći korisnik koristi postojeći nalog. Ne prolazi ponovnu registraciju radi usklađivanja sa ciljnim identitetskim modelom. Ne dobija novi nalog radi zaobilaženja dopune. Ne dobija drugi platformski identitet. Dopuna se vrši na postojećem profilu. Istorija i postojeće veze naloga ostaju očuvane.

Ovo je kontinuitet postojećeg naloga, a ne novo ovlašćenje.

## 5.9 AS-IS vs ciljni model

Postojeća autorizaciona realizacija nije sama po sebi ciljni autorizacioni model `DK-TS-002`.

---

# 6. Model podataka

**Sekcijska sljedivost:** DK-BM-002 §3, §5–§11, §15, §16; DK-FS-002 §5–§14, §16, §18, §20

Ovo poglavlje razrađuje **ciljni konceptualni model podataka** usvojenog `DK-BM-002` / `DK-FS-002` i **PO-usvojeni fizički storage obrazac** odluke 1. Konačni nazivi SQL tabela, migracija i rollback **nisu** usvojeni ovim poglavljem.

## 6.1 Konceptualne cjeline

Korisnički nalog je platformska cjelina preko koje korisnik pristupa Platformi. Jedan postojeći nalog ostaje isti nalog. Nalog **nije** Vrsta subjekta. Nalog **nije** platformska ni modulska uloga.

Pravilo 1:1 primjenjuje se na nalog koji predstavlja registrovani platformski subjekt. Takav nalog ima tačno jedan platformski korisnički identitet. Taj identitet pripada tačno tom jednom nalogu (`DK-BM-002` v1.0.2 §3; `DK-FS-002` v1.0.2 §5). Isti takav nalog ne može paralelno predstavljati više platformskih identiteta. Korisnički profil pripada tom istom nalogu i tom jedinstvenom identitetu. Identitet sadrži identitetske podatke primjenjive na aktivni identitetski kontekst. Ima jednu aktivnu Vrstu subjekta. Ta aktivna Vrsta subjekta pripada tom jedinstvenom identitetu.

Ova kardinalnost je usvojena i konceptualno i fizički: za nalog registrovanog platformskog subjekta postoji tačno jedan fizički zapis platformskog identiteta (Poglavlje 6.15). Interni ili staff nalog koji postoji isključivo radi platformske uloge **nije** obavezan da ima taj identity zapis samo zato što postoji `users` nalog. Konačni nazivi SQL tabela **nisu** usvojeni.

Ovo poglavlje **ne** uvodi history ni versioning identiteta.

## 6.2 Vrsta subjekta

Konceptualni model ima **tačno tri** Vrste subjekta (`DK-BM-002` §5; `DK-FS-002` §5):

1. Fizičko lice
2. Pravno lice
3. Dio stranog privrednog društva

Preduzetnik **nije** četvrta Vrsta subjekta (`DK-BM-002` §5; `DK-FS-002` §5). Jedan platformski korisnički identitet ima jednu aktivnu Vrstu subjekta. To **ne** znači da postoji istorija prethodnih Vrsta subjekta.

Kanonska Vrsta subjekta čuva se u centralnom identity sloju registrovanog platformskog subjekta (Poglavlje 6.15). Fizička reprezentacija pojedinačne vrijednosti **nije** predmet odluke 1. Ciljni kanonski SSOT Vrste subjekta je novi identitetski model. `users.user_type` je privremena kompatibilnost, a ne drugi SSOT (Poglavlje 6.17; odluka 2). Semantičko mapiranje postojećih redova usvojeno je odlukom 3 (Poglavlje 6.18).

## 6.3 Fizičko lice

Za Fizičko lice konceptualni podaci obuhvataju:

* ime;
* prezime;
* Status rezidentnosti;
* identifikacioni kontekst;
* JMB kada je primjenjiv prema usvojenom `DK-FS-002`;
* identifikacioni dokument Nerezidenta kada je primjenjiv;
* državu izdavanja kada je primjenjiva u aktivnom identifikacionom kontekstu;
* Državu prebivališta kada je primjenjiva za Nerezidenta;
* status Preduzetnika;
* poslovni naziv Preduzetnika kada je primjenjiv;
* PIB Preduzetnika kada je primjenjiv;
* CRPS registracioni broj Preduzetnika kada je primjenjiv (`DK-BM-002` v1.0.3 §7; `DK-FS-002` v1.0.3 §7);
* Ulicu i broj;
* Grad.

Broj mobilnog telefona pripada centralnom identity sloju, ne ovoj strukturi (Poglavlje 6.15). Validacioni algoritmi **nisu** predmet ovog poglavlja.

## 6.4 Preduzetnik

Preduzetnik je Fizičko lice sa dodatnim poslovnim kontekstom. Zadržava lični identitet Fizičkog lica. **Nije** drugi platformski identitet niti četvrta Vrsta subjekta (`DK-BM-002` v1.0.2 §3, §5, §7).

Kada je Fizičko lice Preduzetnik, konceptualno postoje poslovni naziv Preduzetnika, PIB i CRPS registracioni broj. Status rezidentnosti i lični identifikacioni kontekst ostaju identitet Fizičkog lica. PIB i CRPS su različiti podaci.

Fizički ostaje u strukturi Fizičkog lica: status/kontekst Preduzetnika i, kada je Preduzetnik = Da, uslovni poslovni naziv, PIB i CRPS registracioni broj. Posebna entrepreneur identity struktura se **ne** uvodi (Poglavlje 6.15). Kada je Preduzetnik = Ne, CRPS **nije** primjenjiv. Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena. Tehnička validacija CRPS-a usvojena je odlukom 8 (Poglavlje 6.23).

## 6.5 Pravno lice

Za Pravno lice konceptualni podaci obuhvataju:

* puni naziv;
* PIB;
* CRPS registracioni broj kada je Pravni oblik OD, KD, AD ili DOO (`DK-BM-002` v1.0.3 §8; `DK-FS-002` v1.0.3 §8);
* Pravni oblik;
* ovlašćeno lice;
* identifikacioni kontekst ovlašćenog lica;
* Ulicu i broj;
* Grad.

Broj mobilnog telefona pripada centralnom identity sloju (Poglavlje 6.15). Ovlašćeno lice **nije** platformski korisnik niti drugi platformski identitet. Fizički je zaseban 1:1 povezani zapis u kontekstu Pravnog lica. Njegovi identifikacioni podaci **ne** koriste ista fizička polja kao identifikatori Fizičkog lica. CRPS **ne** pripada zapisu ovlašćenog lica.

Pravno lice **nema** Status rezidentnosti i **nema** status Preduzetnika. Pravni oblik pripada **samo** Pravnom licu (`DK-BM-002` §8; `DK-FS-002` §8). **Nije** Vrsta subjekta i **nije** uloga.

Kanonski zatvoreni katalog Pravnih oblika je **tačno**:

1. Ortačko društvo (OD)
2. Komanditno društvo (KD)
3. Društvo sa ograničenom odgovornošću (DOO)
4. Akcionarsko društvo (AD)
5. Nevladino udruženje
6. Nevladina fondacija
7. Sportska organizacija

Pravni oblik pripada strukturi Pravnog lica (Poglavlje 6.15). Fizička reprezentacija kataloga vrijednosti **nije** usvojena odlukom 1.

CRPS se u V1 **ne prikuplja** za Nevladino udruženje, Nevladinu fondaciju i Sportsku organizaciju. CRPS **nije** obavezan samo zato što je Vrsta subjekta Pravno lice. Za OD, KD, AD i DOO, CRPS pripada subject-specific strukturi Pravnog lica (Poglavlje 6.15; D1 CRPS coverage alignment). Posebne tabele po Pravnom obliku se **ne** uvode. Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena. Tehnička validacija CRPS-a usvojena je odlukom 8 (Poglavlje 6.23).

## 6.6 Dio stranog privrednog društva

Za Dio stranog privrednog društva konceptualni podaci obuhvataju:

* strano privredno društvo;
* naziv stranog privrednog društva;
* dio / ogranak u Crnoj Gori;
* naziv dijela / ogranka;
* PIB;
* CRPS registracioni broj;
* zastupnika;
* identifikacioni kontekst zastupnika;
* Ulicu i broj;
* Grad.

Broj mobilnog telefona pripada centralnom identity sloju (Poglavlje 6.15). Zastupnik **nije** platformski korisnik niti drugi platformski identitet. Fizički je zaseban 1:1 povezani zapis u kontekstu Dijela stranog privrednog društva. Njegovi identifikacioni podaci **ne** koriste ista fizička polja kao identifikatori Fizičkog lica. CRPS **ne** pripada zapisu zastupnika.

Dio stranog privrednog društva je posebna Vrsta subjekta. **Nije** Pravni oblik. **Nema** Status rezidentnosti kao platformski identitetski atribut. **Nema** status Preduzetnika. Adresa i Grad, kada su primjenjivi, predstavljaju adresu dijela / ogranka u Crnoj Gori (`DK-BM-002` §9.2; `DK-FS-002` §14).

PIB i CRPS registracioni broj su konceptualno različiti podaci (`DK-BM-002` §9; `DK-FS-002` §9). CRPS registracioni broj **nije** identifikator isključivo Dijela stranog privrednog društva. CRPS DSPD-a ostaje u subject-specific strukturi Dijela stranog privrednog društva (Poglavlje 6.15). CRPS **ne** pripada zapisu zastupnika. Tehnička validacija CRPS-a usvojena je odlukom 8 (Poglavlje 6.23).

## 6.7 Identifikacioni konteksti

Konceptualno su odvojeni:

* JMB Fizičkog lica;
* identifikacioni dokument Nerezidenta;
* PIB poslovnog subjekta;
* CRPS registracioni broj;
* identifikacioni podatak ovlašćenog lica;
* identifikacioni podatak zastupnika.

Jedan identifikator **ne** preuzima značenje drugog. PIB **nije** CRPS registracioni broj (`DK-BM-002` §9; `DK-FS-002` §9). Identifikacioni podatak korisnika **nije** automatski identifikacioni podatak ovlašćenog lica niti zastupnika.

## 6.8 Status rezidentnosti

Status rezidentnosti pripada **samo** Fizičkom licu (`DK-BM-002` §6.2; `DK-FS-002` §6.1). Vrijednosti: Rezident / Nerezident.

Preduzetnik ima Status rezidentnosti zato što ostaje Fizičko lice. Pravno lice **nema** Status rezidentnosti. Dio stranog privrednog društva **nema** Status rezidentnosti kao platformski identitetski atribut.

Ovo poglavlje **ne** uvodi poseban status bivšeg Nerezidenta niti automatski Rezident za Pravna lica. Nullable, default i backfill **nisu** određeni ovim poglavljem. Status rezidentnosti i Država prebivališta **nisu** isti podatak (Poglavlje 6.21).

## 6.9 Država kao kontrolisani podatak

`DK-FS-002` usvaja jednu zajedničku kontrolisanu listu država, bez slobodnog teksta (`DK-FS-002` §10). Na konceptualnom nivou država pripada identitetskom kontekstu kada je primjenjiva (Država prebivališta, Država izdavanja pasoša).

Kanonski katalog, stabilni identifikator i način target reference usvojeni su odlukom 6 (Poglavlje 6.21). Država prebivališta i Država izdavanja pasoša koriste isti katalog, a ostaju različiti poslovni podaci.

## 6.10 Zajednički platformski podaci

Zajednički podaci prema usvojenom `DK-FS-002` obuhvataju e-mail adresu, autentifikacionu lozinku, broj mobilnog telefona, adresu (ulica i broj) i Grad. Naselje **nije** dio ovog modela. Zajednička pravila unosa **ne** znače da svi ti podaci žive u istoj fizičkoj strukturi (Poglavlje 6.15).

Razlikuju se:

* trajni platformski / identitetski podatak;
* autentifikacioni podatak;
* prolazni registracioni unos.

E-mail adresa je korisničko ime za prijavu i trajni platformski podatak. Potvrda e-mail adrese je registracioni unos za potvrdu i **nije** poseban trajni identitetski podatak (`DK-BM-002` §10; `DK-FS-002` §11.2).

Autentifikaciona lozinka je autentifikacioni podatak. **Nije** plaintext trajni identitetski podatak. Potvrda lozinke je registracioni unos za potvrdu i **nije** trajni podatak (`DK-BM-002` §10; `DK-FS-002` §12). Hash algoritam i fizički storage lozinke **nisu** određeni ovim poglavljem.

Broj mobilnog telefona je jedan konceptualni platformski podatak. Pripada centralnom identity sloju (Poglavlje 6.15). Registracioni unos može imati komponente (uključujući međunarodni pozivni broj) prema `DK-FS-002`. E.164 i normalizacija **nisu** određeni ovim poglavljem.

Ulica i broj i Grad imaju zajednička pravila unosa i validacije. Vrijednost `bb` je dozvoljena kada objekat nema broj. Grad **nije** ograničen na Opštinu Kotor (`DK-BM-002` §10 tač. 14; `DK-FS-002` §14). Fizički pripadaju odgovarajućoj subject-specific strukturi, jer predstavljaju adresu konkretnog subjekta. Za Dio stranog privrednog društva to je adresa i Grad dijela / ogranka u Crnoj Gori. Tehnička realizacija uklanjanja postojećeg Kotor ograničenja usvojena je odlukom 13 (Poglavlje 6.28). Ovo poglavlje ga **ne** duplicira.

## 6.11 Konceptualni odnosi i integritet

Sljedeće invarijante proizlaze iz usvojenog `DK-BM-002` / `DK-FS-002`. Fizički obrazac je usvojen u Poglavlju 6.15. Ove invarijante **ne** usvajaju konačne nazive tabela, UNIQUE na identifikatorima niti migraciju.

* Za nalog registrovanog platformskog subjekta postoji tačno jedan platformski korisnički identitet, a taj identitet pripada tačno tom jednom nalogu (`DK-BM-002` v1.0.2 §3; `DK-FS-002` v1.0.2 §5).
* Interni ili staff nalog koji postoji isključivo radi platformske uloge **nije** obavezan da ima taj identitet samo zato što nalog postoji.
* Isti nalog registrovanog platformskog subjekta ne može paralelno predstavljati više platformskih korisničkih identiteta.
* Platformski korisnički identitet ima jednu aktivnu Vrstu subjekta. Ta aktivna Vrsta subjekta pripada tom jedinstvenom identitetu.
* Preduzetnik ostaje Fizičko lice. **Nije** drugi platformski identitet.
* Pravni oblik primjenjuje se samo na Pravno lice.
* Status rezidentnosti primjenjuje se samo na Fizičko lice, uključujući Preduzetnika.
* Pravno lice nema Status rezidentnosti.
* Dio stranog privrednog društva nema Status rezidentnosti kao platformski identitetski atribut.
* Podaci ovlašćenog lica pripadaju kontekstu Pravnog lica.
* Podaci zastupnika pripadaju kontekstu Dijela stranog privrednog društva.
* Uslovni podaci učestvuju u identitetu samo kada su primjenjivi aktivnom kontekstu.
* Potvrda e-mail adrese nije trajni identitetski podatak.
* Potvrda lozinke nije trajni podatak.
* Platformska ili modulska uloga nije Vrsta subjekta (`DK-BM-002` §15; `DK-FS-002` §18).
* KN `applicant_type` nije platformska Vrsta subjekta (`DK-FS-002` §18).
* PIB i CRPS registracioni broj ostaju različiti podaci (`DK-BM-002` §9; `DK-FS-002` §9).
* CRPS pripada subject-specific strukturi aktivnog subjekta, ne tankom centralnom identity sloju (Poglavlje 6.15).
* CRPS **ne** pripada zapisu ovlašćenog lica niti zapisu zastupnika.

## 6.12 Postojeći korisnici

Konceptualni model podržava isti postojeći korisnički nalog, očuvanje istorije i postojećih veza naloga, i dopunu nedostajućih ciljnih podataka na postojećem profilu. Dopuna ili izmjena identitetskih podataka na postojećem nalogu **ne** kreira drugi platformski identitet (`DK-FS-002` v1.0.2 §16). Nepotpunost profila sama po sebi nije globalna blokada naloga (`DK-BM-002` §11, §16; `DK-FS-002` §16).

Mehanizam dopune usvojen je odlukom 10 (Poglavlje 6.25). Migraciona strategija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Transitional nullable i default **nisu** određeni ovim poglavljem.

## 6.13 Platforma i modulski podaci

Konceptualno su odvojeni: platformski korisnički identitet; platformska i modulska uloga; KN `applicant_type`; KN konkursni podaci; EP pravila dostupnosti; KK autorizacione uloge.

KN `applicant_type` **nije** platformska Vrsta subjekta (`DK-FS-002` §18). EP pravila dostupnosti **ne** određuju storage model platformskog identiteta. KK uloge **ne** predstavljaju identitetske atribute Platforme (`DK-BM-002` §15; `DK-FS-002` §18). KN `applicant_type`, EP availability i KK uloge **nijesu** dodatni platformski identiteti (`DK-BM-002` v1.0.2 §3).

Postojeće međumodulske veze prema korisničkom nalogu moraju ostati očuvane. Mapping, adapter, API i integracioni ugovor **nisu** predmet ovog poglavlja.

## 6.14 Lifecycle identiteta

`DK-BM-002` / `DK-FS-002` ne definišu lifecycle slobodne promjene Vrste subjekta, statusa Preduzetnika, Statusa rezidentnosti, Pravnog oblika, JMB-a, PIB-a niti CRPS registracionog broja nakon registracije.

Ovo poglavlje **ne** uvodi history model, versioning, vremenski interval važenja identitetskog zapisa niti evidenciju prethodnih Vrsta subjekta. Time se **ne** donosi nova poslovna zabrana izvan sada usvojenog obuhvata.

## 6.15 Fizički storage model (PO usvojeno — odluka 1)

Ciljni fizički storage obrazac platformskog korisničkog identiteta je **PO usvojen**. Odluka **ne** usvaja konačne nazive SQL tabela, UNIQUE na identifikatorima, migraciju niti rollback. Kanonski country catalog contract usvojen je odlukom 6 (Poglavlje 6.21). Ova odluka **ne** uvodi obaveznu DB tabelu `countries`.

### Nalog

`users` ostaje fizička struktura korisničkog naloga i autentikacije.

`users.id` ostaje fizičko sidro postojećeg korisničkog naloga i postojećih veza modula. To je ciljno account / FK sidro. **Nije** identitetski zapis.

Na `users` ostaje account / auth / infrastrukturni sloj, uključujući: account PK / `users.id`; e-mail kao korisničko ime; password / auth podatke; stanje verifikacije e-mail adrese; activation / account state; auth / session podatke; timestamps; role / authz i infrastrukturne podatke koji nijesu identitet. Kanonska semantika `users.email_verified_at` usvojena je odlukom 12 (Poglavlje 6.27).

Identitetski podaci **nisu** ciljna odgovornost `users` tabele. Postojeća AS-IS identitetska polja na `users` ostaju istorijska činjenica. Fizički prelaz tih kolona usvojen je odlukom 14 (Poglavlje 6.29), uz semantičko mapiranje odluke 3. Odluka 2 usvaja samo obrazac kompatibilnosti: kanonski SSOT je novi identitetski model, a `users.user_type` je privremena kompatibilnost (Poglavlje 6.17). Ova odluka **ne** projektuje ALTER ni redoslijed migracije.

Jedinstvenost e-maila ostaje postojeća usvojena norma. Nova jedinstvenost JMB-a, PIB-a, Broja pasoša ili CRPS registracionog broja se **ne** uvodi.

### Platformski identitet

Platformsko-identitetski podaci odvajaju se od naloga.

Za nalog koji predstavlja registrovani platformski subjekt postoji tačno jedan fizički zapis platformskog korisničkog identiteta. Interni ili staff nalog koji postoji isključivo radi platformske uloge **nije** obavezan da ima taj identity zapis samo zato što postoji `users` nalog. Ne uvodi se četvrta Vrsta subjekta ni posebna staff identity struktura.

Centralni identity sloj je tanak i sadrži:

* vezu prema korisničkom nalogu;
* kanonsku Vrstu subjekta;
* broj mobilnog telefona.

CRPS registracioni broj **ne** pripada tankom centralnom identity sloju. Ostaje subject-specific, jer njegova primjena zavisi od aktivne Vrste subjekta i, za Pravno lice, od aktivnog Pravnog oblika.

### Subject-specific strukture

Za jedan platformski identitet postoji tačno jedna aktivna subject-specific struktura, prema Vrsti subjekta:

* Fizičko lice;
* Pravno lice;
* Dio stranog privrednog društva.

`Ulica i broj` i `Grad` pripadaju odgovarajućoj subject-specific strukturi. Imaju zajednička pravila unosa / validacije, ali predstavljaju adresu konkretnog subjekta. Za Dio stranog privrednog društva to je adresa i Grad dijela / ogranka u Crnoj Gori.

### Fizičko lice i Preduzetnik

Subject-specific struktura Fizičkog lica nosi identitetske podatke Fizičkog lica prema već usvojenom konceptualnom modelu, uključujući `Ulicu i broj` i `Grad`.

Preduzetnik **nije** posebna Vrsta subjekta i **nije** drugi platformski identitet. Fizički ostaje u strukturi Fizičkog lica. Posebna entrepreneur identity struktura se **ne** uvodi.

Kada je Preduzetnik = Da, ista FL subject-specific struktura nosi i poslovne podatke Preduzetnika, uključujući poslovni naziv, PIB i CRPS registracioni broj.

Kada je Preduzetnik = Ne, CRPS **nije** primjenjiv.

Ovo je D1 CRPS coverage alignment unutar već usvojene D1 arhitekture. Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena. Konačni nazivi tabela i kolona **nisu** usvojeni.

### Pravno lice i ovlašćeno lice

Subject-specific struktura Pravnog lica nosi Pravni oblik i poslovne identitetske podatke Pravnog lica, uključujući `Ulicu i broj` i `Grad`.

Za Ortačko društvo (OD), Komanditno društvo (KD), Akcionarsko društvo (AD) i Društvo sa ograničenom odgovornošću (DOO), PL subject-specific struktura uključuje PIB i CRPS registracioni broj.

Za Nevladino udruženje, Nevladinu fondaciju i Sportsku organizaciju CRPS se u V1 **ne prikuplja**.

Posebne tabele po Pravnom obliku se **ne** uvode samo zato što se primjena CRPS-a razlikuje.

Ovlašćeno lice Pravnog lica **nije** platformski korisnik i **nije** drugi platformski identitet. Fizički je zaseban 1:1 povezani zapis u kontekstu Pravnog lica. Njegovi identifikacioni podaci **ne** koriste ista fizička polja kao identifikatori Fizičkog lica. CRPS **ne** pripada zapisu ovlašćenog lica. CRPS identificira registrovani poslovni subjekt, ne fizičko lice koje ga zastupa. Lifecycle / history i više ovlašćenih lica se **ne** uvode.

Ovo je D1 CRPS coverage alignment unutar već usvojene D1 arhitekture. Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena. Konačni nazivi tabela i kolona **nisu** usvojeni.

### Dio stranog privrednog društva i zastupnik

Subject-specific struktura Dijela stranog privrednog društva nosi podatke DSPD-a prema usvojenom konceptualnom modelu, uključujući PIB, CRPS registracioni broj, `Ulicu i broj` i `Grad`. Konceptualna lokacija DSPD CRPS-a **nije** izmijenjena.

Zastupnik **nije** platformski korisnik i **nije** drugi platformski identitet. Fizički je zaseban 1:1 povezani zapis u kontekstu DSPD-a. Njegovi identifikacioni podaci **ne** koriste ista fizička polja kao identifikatori Fizičkog lica. CRPS **ne** pripada zapisu zastupnika. Lifecycle / history i više zastupnika se **ne** uvode.

Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23) i ovim usklađenjem **nije** izmijenjena. CRPS **nije** identifikator isključivo DSPD-a.

### Konceptualna topologija

Nazivi u ovom prikazu su arhitektonski. **Nisu** usvojeni nazivi SQL tabela.

```
users  (account / auth; staff/internal account need not have identity)
  |
  | 1:1  (only when the account represents a registered platform subject)
  v
platform identity  (thin; no CRPS)
  |
  +-- exactly one Physical Person structure
  |      +-- Entrepreneur context inside FL when applicable
  |           (business name, PIB, CRPS)
  |
  +-- exactly one Legal Entity structure
  |      +-- CRPS when Legal Form is OD / KD / AD / DOO
  |      +-- 1:1 Authorized Person record (no CRPS)
  |
  +-- exactly one Foreign Branch structure
         +-- PIB, CRPS
         +-- 1:1 Representative record (no CRPS)
```

U jednom trenutku aktivna je tačno jedna od tri subject-specific grane.

### Granica odluke 1

Ova odluka **ne** zatvara rollback / safety (15). Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Obrazac kompatibilnosti `users.user_type` usvojen je odlukom 2 (Poglavlje 6.17). Semantičko mapiranje legacy vrijednosti usvojeno je odlukom 3 (Poglavlje 6.18). Kompatibilnost KN `applicant_type` usvojena je odlukom 4 (Poglavlje 6.19). Kompatibilnost EP availability usvojena je odlukom 5 (Poglavlje 6.20). Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22). Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23). Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27). Odluka 1 **ne** određuje fizički prelaz AS-IS kolona.

**D1 CRPS coverage alignment.** Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena. Konceptualna raspodjela CRPS polja usklađena je sa `DK-BM-002` v1.0.3 / `DK-FS-002` v1.0.3 / odlukom 8: Preduzetnik → FL subject-specific; OD/KD/AD/DOO → PL subject-specific; DSPD → DSPD subject-specific; Nevladino udruženje / Nevladina fondacija / Sportska organizacija → bez CRPS polja u V1; centralni identity sloj bez CRPS. Ovo **nije** nova numerisana tehnička odluka, **nije** SQL šema i **nije** migracija. Tretman postojećih / pre-2026 vrijednosti usvojen je odlukom 14 (Poglavlje 6.29).

Ova odluka **ne** uvodi: encryption, retention, soft-delete, multi-identity, workflow promjene Vrste subjekta, country table / FK / ENUM / API.

## 6.16 AS-IS vs ciljni model

Postojeći storage model spaja pojedine identitetske koncepte koje ciljni model razdvaja. Postojeća fizička realizacija zato **nije** automatski ciljni model `DK-TS-002`.

## 6.17 Prelaz `users.user_type` (PO usvojeno — odluka 2)

Ciljni obrazac kompatibilnosti je **NEW-MODEL-FIRST + LEGACY COMPATIBILITY**. Odluka je **PO usvojena**.

Novi identitetski model je **jedini** kanonski SSOT Vrste subjekta. `users.user_type` je privremena kompatibilnost za postojeće komponente sistema koje zavise od `users.user_type`. **Nije** drugi SSOT. **Nije** univerzalno ogledalo ciljne Vrste subjekta.

Ciljno čitanje Vrste subjekta ide iz novog identitetskog modela. Postojeće zavisne komponente mogu i dalje čitati `users.user_type` dok traje kompatibilnost. Ova odluka **ne** određuje klasu, accessor, adapter ni redoslijed uklanjanja.

Gdje ciljna Vrsta subjekta **nema** vjernu reprezentaciju u `users.user_type`, lažna vrijednost se **ne** upisuje. To obuhvata:

* ciljno Pravno lice čiji Pravni oblik je Nevladina fondacija;
* ciljno Dio stranog privrednog društva kao novi kanonski upis.

Postojeća ENUM vrijednost za Dio stranog privrednog društva **nije** reaktivirana kao kanonski novi upis. Univerzalno ogledalo **nije** usvojeno.

Izvedeni representable compatibility mirror, uključujući DSPD legacy string kao projekciju a **ne** kao SSOT, usvojen je odlukom 14 (Poglavlje 6.29). Odluka 2 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena.

Postojeći upis `users.user_type` iz profila ostaje AS-IS činjenica. **Nije** ciljno ponašanje i **nije** riješen ovom odlukom.

Kompatibilnost KN `applicant_type` usvojena je odlukom 4 (Poglavlje 6.19). Kompatibilnost EP availability usvojena je odlukom 5 (Poglavlje 6.20). Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22).

Ova odluka **ne** zatvara: trenutak uklanjanja `users.user_type`; rollback (15); promjenu Vrste subjekta nakon registracije. Fizički prelaz AS-IS kolona i compatibility mirror semantika usvojeni su odlukom 14 (Poglavlje 6.29). Semantičko mapiranje postojećih redova usvojeno je odlukom 3 (Poglavlje 6.18). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30).

Ova odluka **ne** uvodi: SQL nazive tabela, UNIQUE, dual authority, četvrtu Vrstu subjekta za staff, ni staff identity strukturu. Interni ili staff nalog bez registrovanog subjekta **ne** dobija vještački `users.user_type` samo zato što nalog postoji.

## 6.18 Mapiranje legacy `users.user_type` (CLOSED / PO USVOJENO — odluka 3)

Ovo poglavlje dokumentuje PO-usvojeno semantičko mapiranje postojećih `users.user_type` vrijednosti na ciljni platformski identitet. Status: **CLOSED / PO USVOJENO**.

Mapiranje obuhvata sve poznate legacy vrijednosti koje schema/runtime podržava. Trenutni produkcijski census **nije** poslovno pravilo i **ne** sužava ovu matricu.

Odluka **ne** uvodi nova poslovna pravila. Ciljne Vrste subjekta ostaju tačno tri (`DK-BM-002` §5). Preduzetnik ostaje kontekst Fizičkog lica. Ciljni katalog Pravnog oblika ostaje zatvoren (`DK-BM-002` §8.1). Interni/administrativni (staff/internal) nalog **nije** automatski registrovani subjekt (odluka 1; `DK-BM-002` v1.0.2 §3). Kompatibilnost `users.user_type` ostaje odluka 2.

### Princip

Legacy podatak se automatski mapira samo kada postojeća sačuvana vrijednost **sama po sebi** jednoznačno određuje ciljnu semantiku.

Automatsko mapiranje **ne** smije: izmišljati Vrstu subjekta; izmišljati Pravni oblik; izmišljati status Preduzetnika; proizvoljno mijenjati poslovni identitet; iz pomoćnih polja zaključivati klasifikaciju koju legacy `users.user_type` sama ne određuje.

`company_name`, PIB, ime, prezime, JMB i Broj pasoša **nisu** dovoljan dokaz za promjenu identitetske klasifikacije.

Mapiranje identitetske semantike **nije** isto što i kompletnost ciljnog profila. Deterministički identitet može ostati nepotpun. Dopuna usvojena je odlukom 10 (Poglavlje 6.25). Fizički prelaz usvojen je odlukom 14 (Poglavlje 6.29).

### Matrica

| Legacy `users.user_type` | Ciljna Vrsta subjekta | Kontekst / Pravni oblik | Automatsko mapiranje | Napomena |
|--------------------------|------------------------|-------------------------|----------------------|----------|
| `Fizičko lice` | Fizičko lice | Preduzetnik = Ne | DA, za registrovani subjekt | Interni/administrativni nalog **ne** dobija identitet samo zbog ove vrijednosti |
| `Preduzetnik` | Fizičko lice | Preduzetnik = Da | DA | **Nije** četvrta Vrsta subjekta; **nije** drugi identitet |
| `Ortačko društvo` | Pravno lice | Ortačko društvo (OD) | DA | Profil može biti nepotpun |
| `Komanditno društvo` | Pravno lice | Komanditno društvo (KD) | DA | Profil može biti nepotpun |
| `Društvo sa ograničenom odgovornošću` | Pravno lice | Društvo sa ograničenom odgovornošću (DOO) | DA | Profil može biti nepotpun |
| `Akcionarsko društvo` | Pravno lice | Akcionarsko društvo (AD) | DA | Profil može biti nepotpun |
| `Nevladino udruženje` | Pravno lice | Nevladino udruženje | DA | Profil može biti nepotpun |
| `Sportska organizacija` | Pravno lice | Sportska organizacija | DA | Profil može biti nepotpun |
| `Dio stranog društva (predstavništvo ili poslovna jedinica)` | Dio stranog privrednog društva | — | DA za Vrstu subjekta | **Ne** mapirati kao Pravno lice |
| `Udruženje (nvo, fondacije, sportske organizacije)` | Pravno lice | **nije** determinističan | Vrsta subjekta DA; Pravni oblik **NE** | Izjašnjenje / pregled / dopuna; odluka 10; fizički prelaz odluka 14 |
| `Ustanova (državne i privatne)` | — | nema ciljni Pravni oblik | **NE** | legacy izuzetak bez ciljnog mapiranja |
| `Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)` | — | nema ciljni Pravni oblik | **NE** | legacy izuzetak bez ciljnog mapiranja |
| `NULL` | — | — | **NE** samo iz NULL | Interni/administrativni: bez registrovanog identiteta. Registrovani nalog bez klasifikacije: izjašnjenje / dopuna |

Istorijska tipografska varijanta `Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)`, ako se fizički pojavi, tretira se isto kao trenutna vrijednost `Druge organizacije`.

### Registrovani subjekt — pojedinačna pravila

**`Fizičko lice`.** Cilj: Vrsta subjekta = Fizičko lice. Preduzetnik = Ne. PIB ili `company_name` na tom nalogu **ne** pretvara ga automatski u Preduzetnika. Konfliktni ili nepotpuni podaci mogu zahtijevati izjašnjenje / pregled / dopunu; ova odluka **ne** određuje taj mehanizam. Ako nalog po usvojenom pravilu za interni/administrativni nalog **nije** registrovani platformski subjekt, legacy `Fizičko lice` **ne** kreira platformski identitet.

**`Preduzetnik`.** Cilj: Vrsta subjekta = Fizičko lice. Preduzetnik = Da. **Ne** kreira se drugi identitet. Ako nedostaju ciljno obavezni podaci (uključujući poslovni naziv ili PIB), mapiranje tipa ostaje determinističko, a profil je nepotpun.

**Šest kanonskih pravnih oblika.** `Ortačko društvo`, `Komanditno društvo`, `Društvo sa ograničenom odgovornošću`, `Akcionarsko društvo`, `Nevladino udruženje` i `Sportska organizacija` mapiraju se na Pravno lice sa odgovarajućim usvojenim Pravnim oblikom.

**DSPD legacy.** `Dio stranog društva (predstavništvo ili poslovna jedinica)` mapira se na Vrstu subjekta Dio stranog privrednog društva. **Ne** mapira se kao Pravno lice. Automatski se **ne**: razdvaja jedan `company_name` na naziv stranog društva i naziv dijela u Crnoj Gori; izmišlja CRPS; izmišlja zastupnika; uzima postojeće ime/prezime kao zastupnika.

**NVO bundle.** `Udruženje (nvo, fondacije, sportske organizacije)` mapira se na Pravno lice. Pravni oblik **nije** determinističan. Automatski se **ne** dodjeljuje Nevladino udruženje, Nevladina fondacija ni Sportska organizacija. Izjašnjenje / dopuna Pravnog oblika usvojena je odlukom 10 (Poglavlje 6.25).

**Ustanova.** `Ustanova (državne i privatne)` **nema** ciljni Pravni oblik. **Ne** mapira se na postojeći oblik. **Ne** uvodi se novi oblik. Klasifikacija: **legacy izuzetak bez ciljnog mapiranja**. Odluka 10 **ne** izmišlja V1 mapiranje; funkcionalnost koja zahtijeva podržani registrovani identitet ostaje fail-closed (Poglavlje 6.25). Fizička migracija usvojena je odlukom 14 (Poglavlje 6.29).

**Druge organizacije.** `Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)` **nema** ciljni Pravni oblik. **Ne** mapira se na Nevladino udruženje, Nevladinu fondaciju, Ostalo ni bilo koji drugi postojeći oblik. **Ne** uvodi se Ustanova, politička partija, vjerska zajednica, komora, sindikat, Ostalo ni generički catch-all. Klasifikacija: **legacy izuzetak bez ciljnog mapiranja**. Odluka 10 **ne** izmišlja V1 mapiranje; funkcionalnost koja zahtijeva podržani registrovani identitet ostaje fail-closed (Poglavlje 6.25). Fizička migracija usvojena je odlukom 14 (Poglavlje 6.29).

**`NULL`.** `users.user_type = NULL` **ne** znači automatski interni/administrativni nalog, Fizičko lice ni nepotpun registrovani subjekt. Klasifikacija koristi stvarni kontekst naloga. Interni/administrativni nalog po usvojenom pravilu **ne** dobija registrovani identitet samo zbog `users` reda. Registrovani subjekt bez legacy klasifikacije **ne** dobija proizvoljnu Vrstu subjekta; tok izjašnjenja / dopune usvojen je odlukom 10 (Poglavlje 6.25); fizička migracija usvojena je odlukom 14 (Poglavlje 6.29).

### Pomoćna polja

`Fizičko lice` + PIB **ne** znači automatski Preduzetnik. `Fizičko lice` + `company_name` **ne** znači automatski Preduzetnik. Ime i prezime na Pravnom licu ili DSPD-u **ne** znače automatski ovlašćeno lice ni zastupnika.

Postojeća `first_name`, `last_name`, `jmb` i `passport_number` **ne** smiju se automatski pretvoriti u ciljni zapis ovlašćenog lica Pravnog lica ni zastupnika DSPD-a. Ciljni model te podatke tretira kao posebne podatke o ovlašćenom licu odnosno zastupniku, povezane sa Pravnim licem odnosno DSPD-om, a ne kao identitetske podatke samog subjekta. Legacy polja **nisu** dovoljan dokaz te funkcije.

Takva polja mogu biti signal za pregled / dopunu. **Ne** proizvode sama novu ciljnu identitetsku činjenicu.

### Granica odluke 3

Ova odluka **ne** određuje: SQL redoslijed migracije; redoslijed kreiranja/backfill struktura; deploy; dual-read / dual-write timing; rollback; uklanjanje `users.user_type`; KN `applicant_type`; EP availability; arhitekturu validatora; UI dopune; korisnički tok migracije; politiku kasnije promjene Vrste subjekta.

To ostaje u odlukama 14 i 15, gdje je primjenjivo. Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Kompatibilnost KN `applicant_type` usvojena je odlukom 4 (Poglavlje 6.19). Kompatibilnost EP availability usvojena je odlukom 5 (Poglavlje 6.20). Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26).

### Nenormativna evidencija — produkcijski census

Ovaj podnaslov je **nenormativan**. **Nije** poslovno pravilo. **Ne** sužava matricu iznad.

Evidencija stanja produkcije u trenutku provjere, koju je PO ručno potvrdio read-only census-om produkcione `users` tabele:

Ukupno naloga: **48**.

| `users.user_type` | Broj |
|-------------------|-----:|
| `Fizičko lice` | 33 |
| `Društvo sa ograničenom odgovornošću` | 12 |
| `Preduzetnik` | 1 |
| `NULL` | 2 |

Ostale poznate legacy vrijednosti, uključujući `Ustanova (državne i privatne)`, `Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)`, DSPD legacy, NVO bundle, OD, KD, AD, Nevladino udruženje i Sportska organizacija: **0**.

Dva `NULL` naloga: `kk_admin` / active = 1; `konkurs_admin` / active = 1. Oba su interni/administrativni nalozi.

## 6.19 Kompatibilnost KN `applicant_type` (CLOSED / PO USVOJENO — odluka 4)

Ovo poglavlje dokumentuje PO-usvojeni tehnički obrazac kompatibilnosti KN `applicant_type` sa kanonskim platformskim identitetom. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila. **Ne** uvodi novu platformsku Vrstu subjekta. **Ne** pretvara KN `applicant_type` u platformski identitet. **Ne** koristi `users.user_type` kao novi kanonski izvor. Ovaj TS **ne** mijenja KN dokumentaciju.

### Šta KN `applicant_type` jeste

KN `applicant_type` je **KN-specifična klasifikacija Podnosioca** za konkretnu Prijavu i konkretni profil konkursa (`KN-BM-001` §12; `KN-FS-003` §7.4; `DK-BM-002` §12; `DK-FS-002` §18.3).

Klasifikacija pripada KN kontekstu. **Nije**:

* platformska Vrsta subjekta;
* platformski identitetski SSOT;
* zamjena za Pravni oblik;
* četvrta ili dodatna Vrsta subjekta.

### Izvor za novu Prijavu

Za **novu** Prijavu KN klasifikacija se određuje sistemski iz:

**kanonskog platformskog identiteta** + **pravila konkretnog profila konkursa**.

Konceptualno:

Platformski identitet + pravila profila konkursa → KN klasifikacija Podnosioca.

**Ne** uvodi se globalni univerzalni mapping za sve konkurse. Katalog kategorija Podnosioca pripada konkretnom profilu (`KN-BM-001` §12).

`users.user_type`:

* **nije** novi SSOT;
* **nije** primarni izvor za novi KN tok;
* može postojati samo u okviru već usvojene odluke 2 (privremena legacy kompatibilnost).

### Profil ženskog preduzetništva — determinističko mapiranje

Za profil konkursa za podršku ženskom preduzetništvu (`KN-BM-003`, `KN-FS-003`) važe tačno tri deterministička mapiranja:

| Platformski identitet | KN `applicant_type` |
|-----------------------|---------------------|
| Vrsta subjekta = Fizičko lice; Preduzetnik = Ne | `fizicko_lice` |
| Vrsta subjekta = Fizičko lice; Preduzetnik = Da | `preduzetnica` |
| Vrsta subjekta = Pravno lice; Pravni oblik = Društvo sa ograničenom odgovornošću | `doo` |

`Preduzetnik = Da` je dovoljan platformski signal za KN `preduzetnica`. `Preduzetnik = Ne` je dovoljan platformski signal za KN `fizicko_lice`. Dodatna KN činjenica **nije** potrebna za izbor između ta dva `applicant_type`.

Ova tri mapiranja **ne** znače da su svi ostali uslovi učešća automatski ispunjeni.

### Granica prema drugim KN činjenicama

KN `applicant_type` **nije** isto što i:

* postojanje registrovanog biznisa;
* faza biznisa;
* namjera buduće registracije;
* uslovi učešća konkretnog konkursa;
* osnov za +2 boda;
* dokumentaciona obaveznost polja ili priloga.

Registrovani biznis ostaje zasebna KN činjenica Prijave (`KN-FS-003` §7.4, §7.7, §7.8, §7.10 Q3). **Ne** bira `applicant_type`. Faza biznisa ostaje izbor Podnositeljke unutar Prijave. Namjera registracije i +2 bodovi ostaju KN uslovi učešća / bodovanje, ne identitetska klasifikacija.

### Nepodržane kombinacije u profilu ženskog preduzetništva

U **tom** profilu nema automatskog mapiranja u postojeće KN `applicant_type` vrijednosti za:

* Pravno lice + Ortačko društvo;
* Pravno lice + Komanditno društvo;
* Pravno lice + Akcionarsko društvo;
* Pravno lice + Nevladino udruženje;
* Pravno lice + Nevladina fondacija;
* Pravno lice + Sportska organizacija;
* Dio stranog privrednog društva;
* interni/administrativni nalog bez registrovanog platformskog identiteta;
* nepotpun ili neodređen platformski identitet;
* legacy izuzetak bez ciljnog mapiranja.

**Nema mapiranje u ovom profilu** **ne** znači da je platformski identitet globalno zabranjen za KN. Znači samo da konkretni profil nema odgovarajuću KN kategoriju Podnosioca. Drugi ili budući profil konkursa može imati sopstvene dozvoljene kategorije.

Nepodržana ili nedeterministička platformska kombinacija **ne** smije dobiti izmišljenu KN klasifikaciju.

### `ostalo` kao rezervna vrijednost (catch-all)

AS-IS `ostalo` je legacy/runtime rezervna vrijednost (catch-all). Može obuhvatiti semantički različite kategorije, uključujući AD, OD, KD, NVO, Sportsku organizaciju, DSPD, Ustanovu, Druge organizacije, NULL i nepoznate vrijednosti.

Za **novi** KN tok zasnovan na kanonskom platformskom identitetu **zabranjena** je generička rezervna vrijednost (fallback):

nepodržano / nepoznato → `ostalo`.

`ostalo` se **ne** briše. Postojeće istorijske Prijave sa `ostalo` ostaju AS-IS. Staro `ostalo` se **ne** reinterpretira retroaktivno u konkretan Pravni oblik niti u novu KN klasifikaciju.

### Snapshot

KN `applicant_type` za Prijavu ostaje snapshot.

Tok za novu Prijavu:

1. pročitaj kanonski platformski identitet;
2. primijeni pravila konkretnog profila konkursa;
3. odredi KN klasifikaciju;
4. upiši je u Prijavu;
5. od tada istorijska Prijava zadržava tu vrijednost.

Naknadna promjena platformskog identiteta, Pravnog oblika, statusa Preduzetnika ili korisničkog profila **ne** prepisuje automatski sačuvani `applications.applicant_type`.

### Postojeće Prijave

Postojeće istorijske Prijave: **KEEP AS-IS**.

**Ne** vrši se retroaktivno prepisivanje vrijednosti `preduzetnica`, `doo`, `fizicko_lice`, `ostalo`. Postojeći snapshot ostaje istorijska činjenica Prijave.

### Ručni izbor

`KN-FS-003` §7.4 već određuje da tip Podnositeljke dolazi sa Platforme i **ne** bira se ručno. Za novi KN tok zasnovan na kanonskom platformskom identitetu KN klasifikacija mora biti sistemski izvedena.

AS-IS runtime koji korisniku prikazuje radio izbor predstavlja implementacioni raskorak u odnosu na već usvojeni `KN-FS-003`. Ova odluka **ne** uvodi novo poslovno pravilo i **ne** implementira korekciju postojećeg runtime-a. Usklađivanje AS-IS runtime-a sa već usvojenim `KN-FS-003` predstavlja implementacioni zadatak nakon PO usvajanja odgovarajućeg ciljnog tehničkog modela.

### `registration_form`

`registration_form` **nije** platformska Vrsta subjekta. Postojeći snapshot ostaje istorijski. Novi KN tok zasnovan na kanonskom platformskom identitetu **ne** smije koristiti `users.user_type` kao kanonski SSOT za određivanje platformskog identiteta.

Fizički prelaz `registration_form` **nije** predmet ove odluke.

### Granica odluke 4

Ova odluka **ne** određuje: fizičku klasu ili naziv adaptera; SQL migracije; backfill; deploy redoslijed; dual-read / dual-write timing; ponašanje nacrta tokom migracionog prozora; rollback; uklanjanje legacy `users.user_type`; kompatibilnost EP availability; validatore; rute / HTTP / modal dopune; migracionu strategiju; rollback strategiju.

To ostaje u odlukama 14 i 15 ili u implementacionom planu nakon PO usvajanja, gdje je primjenjivo. Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Kompatibilnost EP availability usvojena je odlukom 5 (Poglavlje 6.20). Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22). Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23). Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Rute, HTTP i modal dopune **nisu** propisani odlukom 10.

### Nenormativna evidencija — postojeći test contract

Ovaj podnaslov je **nenormativan**. **Nije** ciljni contract.

`KonkursApplicantTypeCompatibilityTest` predstavlja AS-IS compatibility contract za legacy Path A (`users.user_type` → view `applicantType`). Postojeći `users.user_type` mapping **nije** ciljni contract novog KN toka zasnovanog na kanonskom platformskom identitetu.

## 6.20 Kompatibilnost EP availability (CLOSED / PO USVOJENO — odluka 5)

Ovo poglavlje dokumentuje PO-usvojeni tehnički obrazac kompatibilnosti EP availability sa kanonskim platformskim identitetom. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi EP-specifičnu identity klasifikaciju. **Ne** uvodi analog KN `applicant_type`. **Ne** redefiniše usvojenu F11 poslovnu matricu dostupnosti. **Ne** koristi `users.user_type` kao kanonski identitetski SSOT. Ovaj TS **ne** mijenja EP dokumentaciju.

Decision 5 polazi od **aktuelnog usvojenog F11 mappinga** (`EP-BM-001`; `EP-KF-001`). Starija rečenica u `EP-TS-001` Faza 3 da je 17/41 mapping OPEN **nije** izvor ove odluke. Korekcija EP-TS-001 **nije** predmet ovog koraka.

### Šta EP availability jeste

EP availability je **modulsko pravilo dostupnosti** vrsta plaćanja i računa. Pripada EP kontekstu (`EP-BM-001` §6a, §8, §9.1; `EP-FS-001` §4, FR-CAT-02; `DK-BM-002` §13; `DK-FS-002` §18.4).

**Nije**:

* platformska Vrsta subjekta;
* platformski identitetski SSOT;
* druga identity klasifikacija;
* zamjena za Status rezidentnosti;
* zamjena za Pravni oblik;
* analog KN `applicant_type`.

EP **ne** definiše sopstvene platformske vrste korisnika. EP availability **evaluira** kanonske platformske identitetske karakteristike prema usvojenim EP pravilima dostupnosti.

### Ciljni obrazac

Ciljni obrazac odluke 5 je **direktna evaluacija kanonskih identitetskih karakteristika**.

Konceptualno:

**kanonski platformski identitet** + **usvojena EP pravila dostupnosti** → **dostupne vrste plaćanja** → **dostupni računi**.

**Ne** uvodi se:

platformski identitet → EP identitet → availability.

EP **nema** drugi identity SSOT.

Relevantne karakteristike za konkretno availability pravilo:

* Vrsta subjekta: Fizičko lice; Pravno lice; Dio stranog privrednog društva;
* za Fizičko lice: Preduzetnik = Da / Ne; Rezidentnost = Rezident / Nerezident;
* za Pravno lice: Pravni oblik;
* za DSPD: Vrsta subjekta = DSPD, bez izmišljene kategorije Pravnog lica i bez legacy približnog mapiranja.

Preduzetnik **nije** četvrta Vrsta subjekta. Ostaje poslovni kontekst Fizičkog lica.

Rezidentnost ostaje samo za Fizičko lice, uključujući Fizičko lice koje je Preduzetnik. **Ne** uvodi se rezidentnost za Pravno lice ni DSPD.

### Status `users.user_type`

Ciljna EP availability **ne** čita `users.user_type` kao kanonski identitetski SSOT.

AS-IS runtime trenutno ključuje type-level i account-level pravila na legacy 8-vrijednosni `users.user_type` i na `residential_status` samo za Fizičko lice / Preduzetnik. Pravni oblik je u AS-IS modelu utopljen u `user_type`. Preduzetnik je u AS-IS modelu poseban `user_type`. To je istorijska činjenica, **nije** ciljni SSOT.

`users.user_type` može privremeno učestvovati **samo** kroz legacy compatibility mehanizam dozvoljen odlukom 2, dok traje tranzicija. Takav mehanizam:

* **nije** ciljni EP identity model;
* **nije** drugi SSOT;
* **ne** smije proizvoditi lažnu legacy vrijednost;
* **ne** smije omogućiti Fondaciju ili DSPD kroz približno mapiranje.

Privremena projekcija prema postojećih 8 EP ključeva, ako se koristi, je **privremena migraciona mogućnost** u okviru odluke 2 i odluke 14 (Poglavlje 6.29). **Nije** ciljna arhitektura odluke 5.

Tačan period i fizički način tranzicije **ne** određuje odluka 5. To pripada odluci 14.

Odluka 5 **ne** određuje naziv klase, interfejsa ni servisa.

### F11 matrica — STRICT KEEP

Odluka 5 **ne** redefiniše usvojenu F11 poslovnu matricu. **Ne** dodaje ni uklanja vrste plaćanja. **Ne** mijenja 17 vrsta ni 41 račun. **Ne** mijenja postojeća F11 prava. **Ne** proširuje LEGAL6 ni BIZ6. **Ne** aktivira katalog. **Ne** mijenja broj računa, svrhu, model, šifru plaćanja ni poziv na broj.

Postojeće semantičke razlike F11 skupova ostaju. Ovo je **prevod identitetskog ključa** postojeće matrice, **ne** novo poslovno mapiranje vrsta plaćanja.

| AS-IS EP ključ (`users.user_type` + rezidentnost gdje važi) | Ciljni identitetski kontekst |
|---|---|
| `Fizičko lice` + resident / non-resident | Vrsta subjekta = Fizičko lice; Preduzetnik = Ne; Rezidentnost |
| `Preduzetnik` + resident / non-resident | Vrsta subjekta = Fizičko lice; Preduzetnik = Da; Rezidentnost |
| `Ortačko društvo` | Vrsta subjekta = Pravno lice; Pravni oblik = Ortačko društvo (OD) |
| `Komanditno društvo` | Vrsta subjekta = Pravno lice; Pravni oblik = Komanditno društvo (KD) |
| `Društvo sa ograničenom odgovornošću` | Vrsta subjekta = Pravno lice; Pravni oblik = Društvo sa ograničenom odgovornošću (DOO) |
| `Akcionarsko društvo` | Vrsta subjekta = Pravno lice; Pravni oblik = Akcionarsko društvo (AD) |
| `Nevladino udruženje` | Vrsta subjekta = Pravno lice; Pravni oblik = Nevladino udruženje |
| `Sportska organizacija` | Vrsta subjekta = Pravno lice; Pravni oblik = Sportska organizacija |

### FL2 / PRED2

Razlika **mora** ostati:

* FL2 → Fizičko lice + Preduzetnik = Ne + Rezidentnost;
* PRED2 → Fizičko lice + Preduzetnik = Da + Rezidentnost.

Ta dva konteksta **ne** smiju se stopiti samo zato što oba imaju Vrstu subjekta = Fizičko lice.

### LEGAL6 / BIZ6

LEGAL6 obuhvata postojeće podržane pravne kategorije F11:

* Ortačko društvo;
* Komanditno društvo;
* Društvo sa ograničenom odgovornošću;
* Akcionarsko društvo;
* Nevladino udruženje;
* Sportska organizacija.

BIZ6 semantički razlikuje:

* Fizičko lice + Preduzetnik = Da;
* Pravno lice + OD / KD / DOO / AD;

od:

* Nevladinog udruženja;
* Sportske organizacije;
* Fizičkog lica + Preduzetnik = Ne.

BIZ6 se **ne** proširuje po analogiji. ALL8 i ALL8_MINUS_FL zadržavaju postojeće F11 značenje kao unija navedenih skupova.

### PO-usvojena pododluka — Nevladina fondacija i DSPD

Product Owner je eksplicitno usvojio:

**Nevladina fondacija** i **DSPD** u EP V1 za sada ostaju **bez automatske dostupnosti** vrsta plaćanja i računa dok za njih ne budu posebno usvojena EP pravila dostupnosti.

To znači:

* Pravno lice + Pravni oblik = Nevladina fondacija → nema poklapanja sa EP availability pravilom → fail-closed;
* Dio stranog privrednog društva → nema poklapanja sa EP availability pravilom → fail-closed.

Ovo **nije**:

* tvrdnja da ti subjekti pravno ne mogu plaćati Opštini;
* trajna zabrana korišćenja e-Plaćanja;
* uklanjanje iz platformskog identitetskog modela;
* mapiranje na neku drugu kategoriju.

Znači samo da aktuelna usvojena EP V1/F11 matrica im **nije** dodijelila pravila dostupnosti. Ako se kasnije poslovno usvoje EP pravila za njih, EP availability matrica može biti posebno proširena. Takvo proširenje **nije** predmet odluke 5.

### Zabranjeno približno mapiranje

**Zabranjeno** je:

* Nevladina fondacija → Nevladino udruženje;
* DSPD → Pravno lice / DOO / AD / drugi postojeći oblik;
* legacy izuzetak → najbliža postojeća kategorija;
* nepoznato / nepodržano → `ostalo`.

Ako nema jednoznačno definisanog EP availability pravila: **fail-closed**. Nema generičkog catch-all pravila.

Legacy vrijednosti bez ciljnog mapiranja iz odluke 3, uključujući `Ustanova (državne i privatne)` i `Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)`, **ne** smiju dobiti EP availability preko približne LEGAL6/BIZ6 kategorije.

### Type-level i account-level

Odluka 5 **ne** mijenja postojeću dvostepenu logiku:

1. dostupnost vrste plaćanja;
2. dostupnost računa.

Korisnik može koristiti konkretan račun samo ako je vrsta dozvoljena, račun aktivan/validan, i account-level pravilo dozvoljava korisnikov kanonski identitetski kontekst.

Account-level pravilo **može** suziti pravo type-level pravila. **Ne može** ga proširiti.

Zadržava se:

* 0 dozvoljenih računa → vrsta nije dostupna;
* 1 dozvoljeni račun → automatski izbor;
* 2+ dozvoljenih računa → korisnik bira samo među dozvoljenim računima.

### Interni / admin nalog

EP availability u ciljnom modelu polazi od **registrovanog platformskog identiteta**.

Interni/admin nalog koji **nema** registrovani platformski identitet nema identitetski kontekst za EP availability → **fail-closed**.

Uloga:

* **nije** Vrsta subjekta;
* **ne** stvara platformski identitet;
* **ne** daje EP availability.

**Ne** uvodi se role-based EP zabrana. Pravilo je identity-based.

Legacy `users.user_type = Fizičko lice` **nije** sam po sebi dovoljan dokaz da interni/admin nalog predstavlja registrovani platformski subjekt.

Ako nalog sa staff ulogom **zasebno** predstavlja validan registrovani platformski identitet, sama staff uloga **ne** poništava taj identitet.

### Nepotpun / neodređen identitet

Ako identitetske karakteristike potrebne za konkretno EP availability pravilo **nijesu** jednoznačno određene: **fail-closed**. **Ne** uvodi se default.

Primjeri:

* Fizičko lice bez poznatog statusa Preduzetnika → **ne** pretpostavljati Preduzetnik = Ne;
* Fizičko lice za pravilo koje zahtijeva Rezidentnost, a ona nije deklarisana → **ne** pretpostavljati Rezident;
* Pravno lice bez poznatog Pravnog oblika → **ne** pretpostavljati LEGAL6.

Nepotpuni identitet se **ne** mapira na najbližu kategoriju.

Korisnički mehanizam kojim se nedostajući podatak dopunjava usvojen je odlukom 10 (Poglavlje 6.25). Odluka 5 određuje samo availability ponašanje dok potrebni identitetski podatak nije poznat.

Postojeći EP declare-on-use za Rezidentnost Fizičkog lica / Preduzetnika **ne** redizajnira se ovom odlukom. Rezidentnost se **ne** pretpostavlja. Completion / declaration mehanizam usvojen je odlukom 10. Odluka 5 **ne** uvodi novi UI.

### Uplatilac

Uplatilac je prijavljeni korisnik / njegov registrovani platformski identitet. Plaćanje u ime drugog **nije** dio V1. Podaci uplatioca dolaze iz kanonskog platformskog profila/identiteta. EP availability klasifikacija **nije** zaseban identitet uplatioca.

### Snapshot transakcije

Postojeće EP transakcije: **KEEP AS-IS**.

Postojeći snapshot `user_type` / `user_type_label` **ne** prepisuje se retroaktivno. **Ne** vrši se rewrite, backfill, reinterpretacija ni konverzija istorijskih snapshot vrijednosti u ovom koraku odluke 5.

Ciljni **novi** EP tok treba da sačuva podatke potrebne da istorijska transakcija ostane razumljiva nezavisno od kasnije promjene live identiteta. Fizička JSON/SQL šema snapshot-a **nije** usvojena ovom odlukom. Tačna migracija istorijskih redova pripada odluci 14.

### Granica odluke 5

Ova odluka **ne** određuje: konkretan naziv adaptera/servisa; finalna SQL imena; SQL migracije; deploy redoslijed; dual-read / dual-write timing; DROP `users.user_type`; production activation EP kataloga; Bankart integraciju; Bankart URL; credentials; callback/payload; gateway-specific pravila; rollback / safety (15). Migraciona strategija usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). D15 **ne** aktivira Bankart. Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22). Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23). Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27).

## 6.21 Zajednički kanonski katalog država (CLOSED / PO USVOJENO — odluka 6)

Ovo poglavlje dokumentuje PO-usvojeni tehnički obrazac zajedničkog kontrolisanog kataloga država. Status: **CLOSED / PO USVOJENO**.

Odluka tehnički konkretizuje već usvojeni zahtjev `DK-FS-002` §10. **Ne** uvodi Državljanstvo. **Ne** redefiniše Rezidentnost. **Ne** spaja Državu prebivališta i Državu izdavanja pasoša u jedno poslovno polje. Ovaj TS **ne** mijenja `DK-BM-002`, `DK-FS-002`, EP ni KN dokumentaciju.

### Katalog

Platforma koristi **jedan** zajednički kanonski katalog država za target identitetske podatke koji zahtijevaju izbor države.

Katalog je: platformski; shared; canonical; **system-managed**; controlled.

U V1 **nije** administratorski poslovni katalog. Administrator platforme ili modula **ne** dobija pravo da proizvoljno dodaje države, briše države, mijenja kanonske kodove niti mijenja semantiku kataloga. Admin CRUD kataloga **nije** zahtjev ove odluke.

Korisnik **ne** unosi naziv države slobodnim tekstom (`DK-FS-002` §10).

### Isti katalog, različiti podaci

Država prebivališta i Država izdavanja pasoša koriste **isti** kanonski katalog.

**Nisu** isti poslovni podatak. **Nisu** ista semantika. **Ne** smiju se spojiti u jedno univerzalno country polje.

Konceptualno:

* Država prebivališta → shared country catalog;
* Država izdavanja pasoša → shared country catalog.

Odvojene reference / polja u odgovarajućim identitetskim strukturama (odluka 1).

Nazivi `residence_country_code` i `passport_issuing_country_code` su **konceptualni primjeri**. **Nisu** finalni SQL ugovor.

### Stabilni identifikator

Kanonski stabilni identifikator države zasniva se na **ISO 3166-1 alpha-2**.

Primjeri: Crna Gora → `ME`; Srbija → `RS`; Hrvatska → `HR`; Italija → `IT`; Njemačka → `DE`.

Kanonska target vrijednost u identitetskom podatku je **stabilni country code**. Naziv države **nije** kanonski identifikator. Telefonski pozivni broj **nije** kanonski identifikator. Autoincrement DB ID **nije** zahtijevani target identifikator.

Ova odluka **ne** unosi kompletnu listu država. Konkretan tehnički izvor kompletne liste realizuje se implementaciono uz očuvanje ovog ugovora.

### Kontrolisani platformski izuzetak

ISO 3166-1 alpha-2 je osnovni standard. Katalog **može** sadržati eksplicitno dokumentovan platformski izuzetak kada Platforma mora predstaviti teritoriju ili referencu za koju ne postoji odgovarajući formalno dodijeljeni ISO 3166-1 alpha-2 kod.

Takav izuzetak mora biti: eksplicitan; kontrolisan; sistemski definisan; stabilan; dokumentovan; bez konflikta sa postojećim kanonskim kodovima. **Ne** predstavlja se kao formalni ISO kod ako to nije.

Postojeći registracioni picker `Kosovo` / `+383` **nije** country catalog i **nije** izvor izuzetka. Ova odluka usvaja **opšti mehanizam** izuzetka. **Ne** tvrdi da je `XK` formalni ISO 3166-1 alpha-2 kod. **Ne** donosi političko-statusnu odluku. **Ne** projektuje sadržaj liste prema telefonskom pickeru.

### Storage i prikaz

U odgovarajućem identitetskom podatku čuva se **stabilni country code**, a ne slobodni naziv, pozivni broj niti obavezni `countries.id`.

Naziv države je **display** vrijednost kataloga. Korisnik u UI bira i vidi naziv. Identitetski zapis **ne** čuva slobodno uneseni naziv kao kanonsku vrijednost.

Koncept: `ME` → Crna Gora; `RS` → Srbija; `IT` → Italija; `DE` → Njemačka.

### Fizička realizacija

Usvojeni obrazac je kombinacija:

* application-level static canonical catalog (izvor liste);
* standardized country code u svakom relevantnom identitetskom polju, uz shared validaciju i label resolver.

Dozvoljena je code / config / package-backed implementacija. DB tabela `countries` **nije** obavezna target arhitektura. FK na autoincrement id **nije** zahtjev.

Ako se kasnije implementaciono izabere drugi fizički mehanizam, mora očuvati: jedan shared catalog; stabilni canonical code; kontrolisanu validaciju; isti kod kroz sve relevantne reference; bez free-text country SSOT-a.

Ova odluka **ne** usvaja: finalno SQL ime kolone; dužinu kolone; DB ENUM; FK; index; Laravel class/cast; package; config/lang filename.

### Nerezidentno Fizičko lice

Za Vrstu subjekta = Fizičko lice i Rezidentnost = Nerezident, uključujući Preduzetnik = Ne i Preduzetnik = Da, Država prebivališta je **obavezna**. Vrijednost mora biti validan kanonski code iz shared kataloga.

**Ne** izvodi se iz pasoša, JMB-a, adrese, telefonskog pozivnog broja, državljanstva niti drugih podataka.

### Rezidentno Fizičko lice

Za Vrstu subjekta = Fizičko lice i Rezidentnost = Rezident, Država prebivališta **se ne prikazuje**. **Ne** upisuje se automatski `ME` samo zato što je korisnik Rezident.

Rezidentnost = Rezident **ne** znači `residence_country = ME`. Rezidentnost i država ostaju odvojeni. **Nema** silent default.

**Ne** uvodi se automatsko: country → residential_status, niti residential_status → country.

### Pasoš Fizičkog lica

Za Nerezidentno Fizičko lice `DK-BM-002` / `DK-FS-002` zahtijevaju Državu prebivališta i, ako je pasoš izabrani identifikator, broj pasoša. **Ne** uvode zaseban target podatak Država izdavanja pasoša Fizičkog lica. Ova odluka taj zahtjev **ne** izmišlja.

### Ovlašćeno lice Pravnog lica

Ako ovlašćeno lice koristi pasoš: Država izdavanja pasoša je **obavezna**. Vrijednost je kanonski country code. Polje je odvojeno od države prebivališta korisnika, državljanstva i sjedišta Pravnog lica.

### Zastupnik DSPD

Ako zastupnik koristi pasoš: Država izdavanja pasoša je **obavezna**. Vrijednost je kanonski country code. **Nije** država strane kompanije, država prebivališta zastupnika, državljanstvo, niti država adrese dijela društva u Crnoj Gori.

### Van obuhvata ove odluke

* **Državljanstvo / nationality / citizenship** se **ne** uvodi. Shared catalog **nije** razlog za uvođenje tog podatka.
* Postojeći registration picker (naziv, zastava, `+382` / `+381` / `+1` / `+7` / `+39` …) **nije** shared country catalog. Više država može dijeliti isti pozivni broj. Ova odluka **ne** redizajnira `PhoneNumber::normalize`, profile phone input niti E.164 ponašanje.
* `KotorAddress`, `city`, `address`, KN `company_seat` i KN Kotor eligibility **nisu** country catalog. Tehnička realizacija uklanjanja Kotor ograničenja u platformskom identitetu usvojena je odlukom 13 (Poglavlje 6.28). KN Kotor eligibility **nije** D13.
* EP availability (odluka 5) **ne** dobija country kao novi kriterijum. F11 / FL2 / PRED2 / LEGAL6 / BIZ6 **neizmijenjeni**. Bankart country/payload: **NOT DETERMINED**.
* KN **ne** dobija sopstveni country SSOT ovom odlukom. Application snapshot **nije** platformski identity SSOT.
* Postojeći Nerezidenti bez Države prebivališta: dopuna usvojena odlukom 10 (Poglavlje 6.25); backfill usvojen odlukom 14 (Poglavlje 6.29). **Ne** upisivati državu iz legacy podataka.
* Istorijski snapshoti se **ne** prepisuju. Ako budući snapshot treba country, canonical code je stabilna osnova. Code / label / oba odlučuje konkretni snapshot contract tamo gdje ga zahtijeva.
* Rollout / cutover / rollback usvojeni odlukom 15 (Poglavlje 6.30).

### Normativni sažetak

1. Jedan shared canonical country catalog.
2. System-managed u V1; bez admin CRUD-a.
3. Controlled list; slobodni tekst zabranjen.
4. Stabilni identifikator zasnovan na ISO 3166-1 alpha-2.
5. Eksplicitno dokumentovani platformski izuzeci dozvoljeni gdje su potrebni i **ne** smiju biti lažno predstavljeni kao formalni ISO kodovi.
6. Identity storage koristi canonical country code.
7. Display label dolazi iz shared kataloga / resolvera.
8. Država prebivališta i Država izdavanja pasoša koriste isti katalog, a ostaju različiti podaci.
9. Nerezidentno Fizičko lice: Država prebivališta obavezna.
10. Rezidentno Fizičko lice: Država prebivališta se ne prikazuje; nema automatskog upisa `ME`.
11. Ovlašćeno lice Pravnog lica + pasoš: Država izdavanja pasoša obavezna.
12. Zastupnik DSPD + pasoš: Država izdavanja pasoša obavezna.
13. Fizičko lice: ova odluka **ne** uvodi Državu izdavanja pasoša.
14. Državljanstvo: van obuhvata.
15. Telefonski pozivni brojevi: **nisu** country catalog.
16. EP availability: neizmijenjena (odluka 5).
17. KN: neizmijenjen.
18. Dopuna postojećih korisnika: usvojena odlukom 10 (Poglavlje 6.25).
19. Migracija / backfill: usvojena odlukom 14 (Poglavlje 6.29).
20. Rollout / cutover / rollback: usvojeni odlukom 15 (Poglavlje 6.30).

### Granica odluke 6

Ova odluka **ne** određuje: rollback (15); production migration izvršenje; rollout; admin CRUD; Bankart mapping; API; tačnu Laravel klasu; config implementaciju; tačna SQL imena; KN/EP migraciju. Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22). Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23). Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27).

## 6.22 Arhitektura JMB / PIB validatora (CLOSED / PO USVOJENO — odluka 7)

Ovo poglavlje dokumentuje PO-usvojeni tehnički obrazac kanonske validacije JMB-a i PIB-a. Status: **CLOSED / PO USVOJENO**.

Odluka određuje **kako** se već usvojena pravila validnosti tehnički centralizuju. **Ne** redefiniše kada je JMB ili PIB obavezan. **Ne** uvodi novu uniqueness politiku. **Ne** određuje finalni tekst validacionih poruka; katalog poruka usvojen je odlukom 9 (Poglavlje 6.24). Ovaj TS **ne** mijenja `DK-BM-002` ni `DK-FS-002`.

### Kanonska arhitektura

Platforma ima **jedan** kanonski validator za JMB i **jedan** kanonski validator za PIB. Ista algoritamska pravila koriste svi relevantni platformski i modularni tokovi. Nezavisna kopija poslovnog checksum algoritma po modulu ili toku **nije** dozvoljena.

Usvojeni obrazac:

* PURE IDENTIFIER VALIDATOR;
* LARAVEL VALIDATION RULE ADAPTER nad pure validatorom.

Konceptualno:

HTTP / FormRequest → Laravel Rule → pure identifier validator.

Pure validator je nezavisan od HTTP, UI, DB i business-context sloja. Laravel Rule **nije** izvor matematičkog algoritma.

Nazivi `JmbValidator`, `PibValidator`, `ValidJmb` i `ValidPib` su **arhitektonski primjeri**. Finalna imena PHP klasa **nisu** propisana ovom odlukom.

### Odgovornost pure validatora

Pure validator provjerava samo:

* format identifikatora;
* kontrolnu cifru / algoritamsku validnost.

Pure validator **ne** odlučuje: da li je polje obavezno; Vrstu subjekta; status Preduzetnika; Rezidentnost; korisničku ulogu; HTTP request; DB uniqueness; postojanje korisnika; finalnu UI poruku.

Requiredness ostaje u odgovarajućem business / validation flow sloju. Uniqueness ostaje izvan ove odluke. Finalne validacione poruke pripadaju odluci 9.

### JMB

Kada je JMB aktivan identifikator, kanonska algoritamska validacija je:

* tačno 13 cifara;
* validna kontrolna cifra.

Kanonski JMB validator provjerava **format + control digit**.

**Ne** uvodi dodatnu semantičku validaciju datuma rođenja, mjeseca, godine, regiona niti drugih semantičkih djelova JMB-a.

Postojeća AS-IS `HomeController::validateJMB` implementacija, koja pored checksum-a provjerava datum / godinu / region, **nije** target norma i **nije** kanonski validator. To je compatibility / runtime činjenica koja se usklađuje u implementacionoj fazi.

Ova odluka **ne** mijenja već usvojena pravila kada je JMB obavezan (`DK-BM-002` §6.3, §8.2, §9.2; `DK-FS-002` §6.2, §8.2, §9.2; Poglavlje 7.5).

### PIB

Kada je PIB aktivan identifikator, kanonska algoritamska validacija je:

* tačno 8 cifara;
* kontrolna cifra prema **ISO 7064 Modul 11,10**.

Isto checksum pravilo važi za relevantne PIB kontekste:

* Preduzetnik;
* Pravno lice;
* Dio stranog privrednog društva.

Za Preduzetnika i Pravno lice pravilo je potvrđeno važećim crnogorskim propisom: *Pravilnik o bližem načinu određivanja poreskog identifikacionog broja*, „Službeni list Crne Gore“, br. 004/26, 12.01.2026. Propis utvrđuje da se PIB pravnih lica i fizičkih lica koja obavljaju samostalnu djelatnost, uključujući preduzetnike, sastoji od osam cifara, pri čemu je osma kontrolni broj određen prema ISO 7064, Modul 11,10.

Za DSPD je ISO 7064 Modul 11,10 već target pravilo `DK-FS-002` §9.1.

Ova odluka **ne** uvodi različite checksum algoritme po PIB kontekstu. Kanonski PIB checksum algoritam je ISO 7064 Modul 11,10. Poslovni konteksti Preduzetnik / Pravno lice / DSPD ostaju različiti tamo gdje `DK-BM-002` / `DK-FS-002` tako zahtijevaju.

`DK-BM-002` za Preduzetnika i Pravno lice koristi formulaciju „važeće pravilo“ bez imenovanja algoritma, a za DSPD ne propisuje eksplicitno 8 cifara ni ISO 7064. `DK-FS-002` za DSPD već imenuje ISO 7064 Modul 11,10. Ova odluka tehnički precizira algoritam. `DK-BM-002` i `DK-FS-002` **ostaju neizmijenjeni**.

### Server authority i klijent

Server-side validacija je autoritativna.

Browser / client-side validacija **može** provjeravati requiredness prema konkretnom UI toku, osnovni format i broj cifara.

Kanonski checksum **ne** održava se kao nezavisna duplirana JavaScript implementacija. Postojeća JS kopija JMB checksum algoritma **nije** target SSOT.

Ova odluka **ne** uvodi novi frontend framework, build pipeline niti generated / shared JS source.

### Registracija i profil

Registracija i profil koriste isti kanonski identifier-validity validator za isti identifikator. Isti JMB ili PIB **ne** smije biti algoritamski validan u jednom toku, a algoritamski nevalidan u drugom zbog različite implementacije checksum-a.

Requiredness između tokova može biti različit samo ako to proizlazi iz usvojenih `DK-BM-002` / `DK-FS-002` pravila.

### Konkursi

Kada KN validira JMB ili PIB vrijednost, koristi isti kanonski algoritamski validator.

Ovo **ne** znači da KN snapshot postaje platformski identity SSOT. KN zadržava svoje snapshot podatke i competition-specific poslovna pravila. KN **ne** definiše sopstveni JMB/PIB checksum algoritam.

### e-Plaćanje

Ova odluka **ne** uvodi EP-specifični JMB/PIB validator. Ako EP u budućnosti treba algoritamsku validaciju platformskog JMB/PIB podatka, koristi isti kanonski platformski validator. Bankart pretpostavke se **ne** uvode.

### Uniqueness, poruke, migracija

Ova odluka **ne** usvaja novu uniqueness politiku za JMB ni PIB. Postojeći DB unique constraints i runtime unique provjere ostaju AS-IS compatibility facts i **ne** postaju nova target norma kroz ovu odluku.

Finalni tekstovi validacionih poruka usvojeni su odlukom 9 (Poglavlje 6.24). Pure validator **nije** vezan za finalni UI tekst.

Ova odluka **ne** provjerava postojeće production vrijednosti, **ne** radi checksum census, backfill, cleanup istorijskih podataka niti migraciju. To pripada prvenstveno odluci 14.

### Granica odluke 7

Ova odluka **ne** određuje: rollback (15); finalna SQL imena/strukturu; novu uniqueness politiku; passport validator architecture; country catalog; Bankart payload; production migration; deploy; tačna imena PHP klasa. Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27).

---

## 6.23 Validacija CRPS registracionog broja (CLOSED / PO USVOJENO — odluka 8)

Ovo poglavlje dokumentuje PO-usvojenu tehničku validaciju CRPS registracionog broja za Digital Kotor V1. Status: **CLOSED / PO USVOJENO**.

Obaveznost i prikaz CRPS polja dolaze iz `DK-BM-002` v1.0.3 i `DK-FS-002` v1.0.3. Ova odluka određuje **identifier validity**, ne requiredness.

Zvanični osnov ciljnog zapisa: *Pravilnik o bližem sadržaju registracionog broja za privredne i druge subjekte i stečajnu masu i evropskog jedinstvenog identifikatora za privredne subjekte*, „Službeni list Crne Gore“, br. 116/2025, čl. 2–4; primjena od 01.01.2026.

### Primjena u V1

CRPS je obavezan identitetski podatak za:

* Preduzetnika;
* Ortačko društvo (OD);
* Komanditno društvo (KD);
* Akcionarsko društvo (AD);
* Društvo sa ograničenom odgovornošću (DOO);
* Dio stranog privrednog društva.

CRPS se u V1 **ne prikuplja** za:

* Fizičko lice koje nije Preduzetnik;
* Nevladino udruženje;
* Nevladinu fondaciju;
* Sportsku organizaciju.

CRPS **nije** DSPD-only identifikator. CRPS **nije** obavezan samo zato što je Vrsta subjekta Pravno lice.

### Kanonska V1 struktura

Za sve V1 subjekte kod kojih je CRPS obavezan, kanonska vrijednost ima **tačno 8 numeričkih cifara**:

* pozicija 1: identifikaciona oznaka;
* pozicije 2–8: sedmocifreni redni broj.

Kanonski zapis je **samo cifre**. U kanonsku vrijednost **ne** ulaze: crtica, kosa crta, razmaci, prefiks „CRPS“, broj promjene ni drugi prezentacioni metapodaci.

Historijski/prezentacioni oblik `5-0764790` **nije** kanonski target zapis. Odgovarajuća kanonska vrijednost komponenti registracionog broja je `50764790`. Ova odluka **ne** migrira postojeće podatke. Tumačenje i migracija historijskih zapisa pripadaju odluci 14.

CRPS ima **tekstualnu** identifier semantiku. Leading zeros u sedmocifrenom rednom broju **moraju** ostati. CRPS se **ne** tretira kao aritmetički integer. Ova odluka **ne** definiše ni mijenja database šemu.

### Identifikaciona oznaka

Prva cifra **mora** odgovarati aktivnom kanonskom subjektu / Pravnom obliku:

| Aktivni kontekst | Oznaka |
|------------------|--------|
| Preduzetnik | 1 |
| Ortačko društvo (OD) | 2 |
| Komanditno društvo (KD) | 3 |
| Akcionarsko društvo (AD) | 4 |
| Društvo sa ograničenom odgovornošću (DOO) | 5 |
| Dio stranog privrednog društva | 6 |

Strukturno ispravan CRPS čija oznaka ne odgovara aktivnom kontekstu **nije** validan. Primjer: Preduzetnik + CRPS koji počinje cifrom 2 = invalid. OD + CRPS koji počinje cifrom 1 = invalid.

Ovo pravilo **ne** generalizuje 8-cifreni zapis na CRPS subjekte van V1 required-subject scope (oznake 7–13).

### Redni broj

Pozicije 2–8 su tačno sedam cifara. Dozvoljeni opseg: **0000001–9999999**.

* `0000000` = invalid;
* `0000001` = donja granica, uz ispravnu oznaku;
* `9999999` = gornja granica, uz ispravnu oznaku.

### Checksum

Ciljna V1 validacija CRPS-a **nema** checksum / kontrolnu cifru.

**Ne** primjenjuju se: JMB checksum; PIB ISO 7064 Modul 11,10; drugi izmišljeni checksum.

### Arhitektura validatora

Postoji **jedan** kanonski CRPS identifier validator. Obrazac slijedi odluku 7, ali **nije** ista implementaciona klasa kao JMB/PIB:

* PURE IDENTIFIER VALIDATOR;
* LARAVEL VALIDATION RULE ADAPTER nad pure validatorom.

Caller određuje očekivanu identifikacionu oznaku iz kanonskog identitetskog konteksta. Pure validator prima CRPS vrijednost i očekivanu oznaku, ili ekvivalentni kanonski kontekst iz kojeg se oznaka deterministički izvodi.

Nazivi klasa su arhitektonski. Finalna imena PHP klasa **nisu** propisana.

### Odgovornost pure validatora

Pure CRPS validator provjerava samo:

1. vrijednost se sastoji od cifara;
2. tačna dužina = 8;
3. prva cifra jednaka očekivanoj identifikacionoj oznaci;
4. pozicije 2–8 predstavljaju dozvoljeni opseg 0000001–9999999.

Pure validator **ne** odlučuje: da li je CRPS obavezan; da li je Fizičko lice Preduzetnik; izabrani Pravni oblik; ulogu; autorizaciju; HTTP stanje; postojanje korisnika; DB uniqueness; dopunu postojećeg korisnika; migraciju / backfill; finalnu korisničku validacionu poruku.

### Requiredness

Requiredness / application flow: `DK-BM-002` + `DK-FS-002`.

Identifier validity: ova odluka.

### Server i klijent

Server-side validacija je autoritativna.

Klijent **može** za UX provjeriti: samo cifre; očekivanu dužinu.

Nezavisni klijentski semantički SSOT za mapiranje identifikacionih oznaka **nije** dozvoljen. Kanonska semantička validacija ostaje na serveru.

### Registracija i profil

Registracija i profil koriste iste kanonske CRPS validity semantike tamo gdje se validira kanonski identity CRPS. Odvojeni CRPS algoritmi se **ne** uvode. Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25).

### Konkursi

KN `applications.crps_number` ostaje KN snapshot. **Nije** platformski identity SSOT.

Ako KN algoritamski validira CRPS u kontekstu u kojem je poznat primjenjivi subjekt / Pravni oblik, koristi se isti kanonski algoritam. Ova odluka **ne** redizajnira KN, **ne** mijenja KN dokumentaciju, **ne** reinterpretira historijske KN snapshot-e i **ne** migrira KN podatke.

### Legacy / pre-2026

Ova odluka definiše **ciljnu** validaciju. **Ne** odlučuje tretman postojećih / pre-2026 CRPS vrijednosti.

Odluci 14 pripadaju: postojeće sačuvane CRPS vrijednosti; historijski separator zapisi; historijski sufiksi broja promjene; kompatibilnost; migracija; backfill.

Tretman usvojen odlukom 14 (Poglavlje 6.29): separator-containing CRPS **nije** automatska normalizacija; sirovi zapis se čuva; **ne** diže se iz KN snapshot-a u kanonski identitet. D8 korespondencija historijskog prikaza ostaje definicija ciljnog oblika pri novom unosu / korekciji, **ne** tihi rewrite postojećeg reda.

**LEGACY DATA HANDLING: D14 (CLOSED / PO USVOJENO — Poglavlje 6.29).**

### Uniqueness

Ova odluka **ne** uvodi platformsko / database uniqueness pravilo. D7 granica ostaje: NO NEW UNIQUENESS POLICY.

### Uslovi greške

Ova odluka zatvara uslove greške, ne konačni tekst poruke:

* kanonska vrijednost nije numerička;
* pogrešna dužina;
* pogrešna identifikaciona oznaka;
* redni broj van dozvoljenog opsega.

Konačne crnogorske validacione poruke usvojene su odlukom 9 (Poglavlje 6.24).

### Primjeri (nije stvarni subjekt)

„Kandidat“ znači identifier validity, uz requiredness i ostala form pravila van pure validatora.

* Preduzetnik `10000001` — validan kandidat (oznaka 1 + 0000001);
* Preduzetnik `19999999` — validan kandidat (oznaka 1 + 9999999);
* Preduzetnik `10000000` — invalid (redni broj 0000000);
* Preduzetnik `20000001` — invalid za Preduzetnika (oznaka 2 = OD);
* OD `20000001` — validan kandidat;
* KD `30000001` — validan kandidat;
* AD `40000001` — validan kandidat;
* DOO `50000001` — validan kandidat;
* DSPD `60000001` — validan kandidat.

### Konceptualna lokacija CRPS-a (odluka 1)

Odluka 8 **ne** određuje fizičku lokaciju CRPS polja i **ne** mijenja validacionu semantiku usvojenu ovim poglavljem.

Konceptualna raspodjela usklađena je u Poglavlju 6.15 (D1 CRPS coverage alignment): Preduzetnik → FL subject-specific; OD/KD/AD/DOO → PL subject-specific; DSPD → DSPD subject-specific. Centralni identity sloj **ne** nosi CRPS. Ovlašćeno lice i zastupnik **ne** nose CRPS.

Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena. Ova odluka **ne** donosi fizičku šemu, **ne** dodaje migraciju i **ne** prepisuje odluku 1.

### Granica odluke 8

Ova odluka **ne** određuje: rollback (15); finalna SQL imena; VARCHAR dužinu; uniqueness; KN redizajn. Migracija / legacy CRPS usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27).

---

## 6.24 Kanonske validacione poruke (CLOSED / PO USVOJENO — odluka 9)

Ovo poglavlje dokumentuje PO-usvojeni kanonski katalog korisničkih validacionih poruka za registraciju i kanonski identitet. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila, nove validatore, uniqueness politiku, šemu, migraciju, autorizaciju ni HTTP ponašanje. Requiredness i identifier validity ostaju `DK-BM-002` / `DK-FS-002` / odluke 7 i 8.

Ovaj katalog je **jedini** normativni skup korisničkih validacionih poruka ovog TS-a. Polja u Poglavlju 7 referenciraju ga; ne dupliciraju ga.

### Jezik i granica teksta

Korisničke poruke su na **crnogorskom jeziku**. Koriste kanonske BM/FS nazive polja.

U korisničkom tekstu **ne** izlažu se: regex; checksum; ISO 7064; Laravel; database; payload; validator; SQL; interni identifikatori zapisa.

Korisniku se **ne** prikazuju podrazumijevane framework poruke na engleskom jeziku.

### Obrasci

* Nedostajući obavezan unos: `Unesite {label}.`
* Nedostajući obavezan izbor: `Izaberite {label}.`
* Format kada je koristan očekivani oblik: `{Label} mora imati tačno N cifara.`
* Semantička neispravnost: `{Label} nije ispravan.`
* Nepodudaranje: kanonska tačna poruka za taj uslov.
* Jedinstvenost e-maila: samo nužni tekst.

### Prioritet

Za isto aktivno polje / uslov prikazuje se **jedna** poruka, ovim redoslijedom:

1. neaktivna grana → **nema poruke** i **ne** blokira;
2. obaveznost / nedostajući izbor;
3. osnovni format / dužina;
4. semantička validacija;
5. cross-field validacija.

Primjer: PIB sa 7 cifara → samo `PIB mora imati tačno 8 cifara.` **Ne** prikazuje se i `PIB nije ispravan.`

### Server i klijent

Server-side validacija je autoritativna.

Isto polje + isto pravilo + isti uslov koriste **istu** kanonsku poruku u: registraciji; profilu / update-u; svakom drugom kanonskom identitetskom toku koji izvršava istu validaciju.

Klijent **može** reproducirati istu kanonsku poruku samo za provjere koje mu je dozvoljeno da radi. **Ne** izmišlja drugi tekst.

Kanonski skup poruka konceptualno pripada application validation sloju. Finalna imena PHP klasa / fajlova **nisu** propisana.

### Tačno sačuvana poruka

**E-mail adrese se ne podudaraju.**

Pravopis, interpunkcija i kapitalizacija **ne** mijenjaju se.

### Uslovne grane

Kada uslovna grana postane neaktivna, greške koje pripadaju **isključivo** toj grani: **ne** emituju se; **ne** ostaju aktivne; **ne** blokiraju slanje.

Primjeri: Preduzetnik Da → Ne; PL DOO → NVO (CRPS); Nerezident → Rezident (Država prebivališta); pasoš → JMB.

Ova odluka **ne** odlučuje da li se neaktivne vrijednosti brišu, zadržavaju ili čiste.

### Jačina lozinke

Pravila jačine lozinke **nijesu** dio ciljne normativne validacije DK registracije korisničkog identiteta u ovom paketu.

Postojeće platformsko ponašanje ostaje AS-IS dok se posebno ne odluči.

Poznata AS-IS runtime činjenica uključuje ponašanje minimalno 8 karaktera. Ta AS-IS činjenica **nije** ciljni zahtjev odluke 9. Odluka 9 **ne** usvaja `min:8` kao target i **ne** nalaže uklanjanje postojećeg AS-IS ponašanja.

U ovaj katalog **ne** ulazi poruka `Lozinka mora imati najmanje 8 karaktera.` dok PO posebno ne usvoji to pravilo kao cilj.

### Jedinstvenost

Jedinstvenost e-mail adrese ostaje već usvojeni zahtjev prijave. Nova jedinstvenost JMB-a, PIB-a, CRPS-a i Broja pasoša se **ne** uvodi.

### Kanonski katalog

| Polje / uslov | Primjena | Kanonska poruka |
|---------------|----------|-----------------|
| Vrsta subjekta / nedostaje | svi tokovi | Izaberite vrstu subjekta. |
| E-mail / obavezno | svi tokovi | Unesite e-mail adresu. |
| E-mail / format | svi tokovi | E-mail adresa nije ispravna. |
| E-mail / već u upotrebi | svi tokovi | E-mail adresa je već u upotrebi. |
| Potvrdi e-mail / obavezno | kada se prikazuje potvrda | Potvrdite e-mail adresu. |
| E-mail / nepodudaranje | kada se prikazuje potvrda | E-mail adrese se ne podudaraju. |
| Korisnička lozinka / obavezno | svi tokovi | Unesite korisničku lozinku. |
| Potvrdi korisničku lozinku / obavezno | kada se prikazuje potvrda | Potvrdite korisničku lozinku. |
| Lozinka / nepodudaranje | kada se prikazuje potvrda | Korisničke lozinke se ne podudaraju. |
| Pozivni broj / nedostaje | svi tokovi | Izaberite pozivni broj. |
| Broj mobilnog telefona / obavezno | svi tokovi | Unesite broj mobilnog telefona. |
| Broj mobilnog telefona / +382 sa prefiksom ili početnom nulom | izabran +382 | Unesite broj mobilnog telefona bez pozivnog broja i bez početne nule. |
| Ulica i broj / obavezno | svi tokovi; `bb` je validno | Unesite ulicu i broj. |
| Grad / obavezno | svi tokovi | Unesite grad. |
| Ime / obavezno | Fizičko lice | Unesite ime. |
| Prezime / obavezno | Fizičko lice | Unesite prezime. |
| Preduzetnik Da/Ne / nedostaje | Fizičko lice | Odgovorite da li ste registrovani kao preduzetnik. |
| Status rezidentnosti / nedostaje | samo Fizičko lice | Izaberite status rezidentnosti. |
| Vrsta identifikacionog dokumenta / nedostaje | FL Nerezident | Izaberite vrstu identifikacionog dokumenta. |
| JMB / obavezno | FL kada je JMB aktivan | Unesite JMB. |
| JMB / format | JMB aktivan | JMB mora imati tačno 13 cifara. |
| JMB / semantička neispravnost nakon ispravnog formata | JMB aktivan | JMB nije ispravan. |
| Broj pasoša / obavezno | FL Nerezident + pasoš | Unesite broj pasoša. |
| Država prebivališta / obavezno | FL Nerezident | Izaberite državu prebivališta. |
| Država prebivališta / nije iz kataloga | FL Nerezident | Izaberite državu prebivališta iz liste. |
| Naziv preduzetnika / obavezno | FL + Preduzetnik = Da | Unesite naziv preduzetnika. |
| PIB / obavezno | Preduzetnik; Pravno lice; DSPD | Unesite PIB. |
| PIB / format | isti | PIB mora imati tačno 8 cifara. |
| PIB / semantička neispravnost nakon ispravnog formata | isti | PIB nije ispravan. |
| CRPS / obavezno | Preduzetnik; OD; KD; AD; DOO; DSPD | Unesite CRPS registracioni broj. |
| CRPS / format | isti | CRPS registracioni broj mora imati tačno 8 cifara. |
| CRPS / pogrešna oznaka | Preduzetnik | CRPS registracioni broj ne odgovara preduzetniku. |
| CRPS / pogrešna oznaka | OD, KD, AD, DOO | CRPS registracioni broj ne odgovara izabranom pravnom obliku. |
| CRPS / pogrešna oznaka | DSPD | CRPS registracioni broj ne odgovara dijelu stranog privrednog društva. |
| CRPS / neispravan redni broj | CRPS obavezan, format i oznaka ispravni | CRPS registracioni broj nije ispravan. |
| Pravni oblik / nedostaje ili van kataloga | Pravno lice | Izaberite pravni oblik. |
| Puni naziv pravnog lica / obavezno | Pravno lice | Unesite puni naziv pravnog lica. |
| Ime ovlašćenog lica / obavezno | Pravno lice | Unesite ime ovlašćenog lica. |
| Prezime ovlašćenog lica / obavezno | Pravno lice | Unesite prezime ovlašćenog lica. |
| Dokument ovlašćenog lica / nedostaje | Pravno lice | Izaberite vrstu identifikacionog dokumenta ovlašćenog lica. |
| JMB ovlašćenog lica / obavezno | PL + JMB | Unesite JMB ovlašćenog lica. |
| Broj pasoša ovlašćenog lica / obavezno | PL + pasoš | Unesite broj pasoša ovlašćenog lica. |
| Država izdavanja pasoša / obavezno | PL ovlašćeno lice + pasoš; DSPD zastupnik + pasoš | Izaberite državu izdavanja pasoša. |
| Država izdavanja pasoša / nije iz kataloga | isti pasoš tokovi | Izaberite državu izdavanja pasoša iz liste. |
| Naziv stranog privrednog društva / obavezno | DSPD | Unesite naziv stranog privrednog društva. |
| Naziv dijela stranog privrednog društva u Crnoj Gori / obavezno | DSPD | Unesite naziv dijela stranog privrednog društva u Crnoj Gori. |
| Ime zastupnika / obavezno | DSPD | Unesite ime zastupnika. |
| Prezime zastupnika / obavezno | DSPD | Unesite prezime zastupnika. |
| Dokument zastupnika / nedostaje | DSPD | Izaberite vrstu identifikacionog dokumenta zastupnika. |
| JMB zastupnika / obavezno | DSPD + JMB | Unesite JMB zastupnika. |
| Broj pasoša zastupnika / obavezno | DSPD + pasoš | Unesite broj pasoša zastupnika. |

JMB format i `JMB nije ispravan.` koriste se i za ovlašćeno lice i za zastupnika, uz gornje required labele.

**Ne ulaze u katalog:** poruke za CRPS Nevladinog udruženja, Nevladine fondacije i Sportske organizacije (CRPS se u V1 ne prikuplja); poruke jačine lozinke; format Broja pasoša; E.164 / broj cifara telefona van usvojenog +382 pravila; jedinstvenost JMB/PIB/CRPS/pasoša; poruke datuma/godine/regiona JMB-a; Država izdavanja pasoša za nerezidentno Fizičko lice; Status rezidentnosti za Pravno lice i DSPD.

Korisnik vidi **naziv** države, ne ISO kod.

### Terminologija

Kanonski nazivi: E-mail adresa; Korisnička lozinka; Broj mobilnog telefona; JMB; PIB; CRPS registracioni broj; Naziv preduzetnika; Puni naziv pravnog lica; Pravni oblik; Status rezidentnosti; Država prebivališta; Država izdavanja pasoša; Ulica i broj; Grad; Ovlašćeno lice; Zastupnik.

### Privatnost

Poruka jedinstvenosti e-maila **ne** otkriva ime drugog korisnika, nalog, interni ID niti deaktivirano stanje.

### Granica odluke 9

Ova odluka **ne** određuje: rollback (15); finalna imena PHP klasa; šemu; uniqueness osim već usvojenog e-maila; jačinu lozinke kao ciljno pravilo. Migracija / legacy vrijednosti usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Mehanizam dopune postojećeg korisnika usvojen je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27). Taj sadržaj **nije** validaciona poruka i **ne** ulazi u ovaj katalog. Mehanizam feedback odluke 12 **nije** dio ovog kataloga.

---

## 6.25 Dopuna kanonskog identiteta postojećeg korisnika (CLOSED / PO USVOJENO — odluka 10)

Ovo poglavlje dokumentuje PO-usvojeni tehnički mehanizam dopune kanonskog korisničkog identiteta postojećih korisnika. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila. Razrađuje već usvojeno `DK-BM-002` §4.2, §11, §16 i `DK-FS-002` §16. Konceptualna fizička alokacija ostaje odluka 1. Mapiranje klasifikacije ostaje odluka 3. Validacione poruke ostaju odluka 9.

Ovaj TS **ne** mijenja `DK-BM-002` ni `DK-FS-002`. **Ne** redizajnira KK, KN ni EP.

### Postojeći nalog

Postojeći korisnik **nije** globalno prisiljen na ponovnu registraciju. Nepotpun postojeći identitet / profil **nije** sam po sebi globalna blokada naloga.

Postojeći korisnik:

* može se normalno prijaviti;
* može koristiti nepovezanu funkcionalnost čiji sopstveni zahtjevi jesu ispunjeni;
* dopunjava nedostajuće kanonske identitetske podatke **samo** kada ih konkretna funkcionalnost stvarno zahtijeva.

Dopuna **nije**: nova registracija; drugi nalog; drugi identitet; globalni next-login gate; globalni full-profile gate.

`users.id` ostaje sidro postojećeg naloga. Dopuna popunjava kanonski identitet **istog** naloga.

### Okidač: HYBRID

**A. Deterministička projekcija.** Gdje odluka 3 jednoznačno mapira legacy semantiku, kanonska klasifikacija **smije** se projektovati bez korisničke interakcije.

Primjeri: `Fizičko lice` → FL / Preduzetnik = Ne; `Preduzetnik` → FL / Preduzetnik = Da; OD/KD/AD/DOO/Nevladino udruženje/Sportska organizacija → PL + taj Pravni oblik; DSPD legacy → DSPD.

Korisnik **ne** mora ponovo potvrditi determinističku klasifikaciju. Dopuna **ne** dozvoljava proizvoljnu reklasifikaciju.

**B. Interaktivna dopuna.** Nedostajući ili nerazriješeni poslovni podatak traži se samo kada funkcionalnost to zahtijeva: **REQUIRE-ON-USE / DECLARE-ON-USE**.

Ne uvodi se globalni next-login completion ni globalni profile-completion gate. Volontarni posjet profilu **nije** okidač dopune.

### Granularnost

Dopuna je **FIELD-/NEED-SPECIFIC**. Traži se:

1. nedostajući podatak koji trenutna funkcionalnost zahtijeva;
2. plus direktno zavisna **aktivna** polja čije kanonske vrijednosti **takođe** nedostaju ili nijesu razriješene.

**NEWLY ACTIVE ≠ MISSING.** Polje se **ne** unosi ponovo samo zato što je njegova grana postala aktivna. Već prisutan i razriješen kanonski podatak se **REUSE-uje**.

Ne forsira se kompletna registraciona forma. Ne forsira se unos nepovezanih polja.

### Bez pune revalidacije

Tokom dopune validiraju se: podatak koji se stvarno unosi / mijenja; direktno zavisni aktivni podaci koji se dopunjavaju.

**Ne** revalidiraju se automatski nepovezana postojeća legacy / profilska polja samo zato što se dopunjava drugi podatak.

Primjer: dopuna Statusa rezidentnosti **ne** zahtijeva sama po sebi revalidaciju / ponovni unos e-maila, lozinke, telefona, adrese niti već razriješenog JMB-a / pasoša.

Kanonske poruke: odluka 9 (Poglavlje 6.24). Drugi katalog poruka za postojeće korisnike se **ne** uvodi. Informativni banner, ako se kasnije koristi, **nije** validaciona poruka.

### Status rezidentnosti

Za postojeće Fizičko lice / Preduzetnika bez Statusa rezidentnosti: **DECLARE-ON-USE**. Korisnik izjavljuje Rezident ili Nerezident tek kada funkcionalnost taj status zahtijeva.

**Ne**: izvoditi rezidentnost; auto-backfill; zahtijevati je pri sljedećoj prijavi; globalno zaključati nalog.

**Nerezident — CASE A.** Postoji upotrebljiv kanonski JMB: JMB se REUSE-uje; izbor dokumenta se **ne** traži; JMB se **ne** unosi ponovo; Država prebivališta se traži samo ako nedostaje.

**Nerezident — CASE B.** Nema upotrebljivog kanonskog JMB-a ni pasoša: obavezni su izbor JMB ili Broj pasoša, izabrani identifikator, i Država prebivališta ako nedostaje.

**Nerezident — CASE C.** Postoji upotrebljiv kanonski pasoš, a Država prebivališta nedostaje: pasoš se REUSE-uje; pasoš se **ne** unosi ponovo; izbor dokumenta se **ne** traži ponovo; traži se Država prebivališta.

Za nerezidentno Fizičko lice Država izdavanja pasoša se **ne** uvodi.

**Rezident.** Država prebivališta ostaje neaktivna. JMB se traži samo ako kanonska vrijednost nedostaje. Postojeći upotrebljivi kanonski JMB se REUSE-uje. Pasoš grana se **ne** uvodi.

### Ambiguous legacy

Ambiguous legacy identitet se **nikada** ne pogađa i **nikada** se približno ne mapira.

Gdje se podržani V1 identitet može eksplicitno razriješiti, korisnik izjavljuje nerazriješenu kanonsku činjenicu kada funkcionalnost to zahtijeva. Gdje podržani V1 cilj **ne** postoji, funkcionalnost koja zahtijeva identitet ostaje fail-closed. Nepovezana funkcionalnost ostaje dostupna gdje su njeni zahtjevi ispunjeni.

**NVO bundle.** Legacy `Udruženje (nvo, fondacije, sportske organizacije)`: Vrsta subjekta = Pravno lice; Pravni oblik unresolved. Kada funkcionalnost zahtijeva Pravni oblik, korisnik bira **samo**:

1. Nevladino udruženje
2. Nevladina fondacija
3. Sportska organizacija

**Ne** nudi se: OD; KD; AD; DOO; DSPD; Ustanova; Druge organizacije; Ostalo.

Sportska organizacija ostaje u ambiguity skupu jer je dio originalnog legacy značenja bundle-a. Nalog već sačuvan kao `Sportska organizacija` ili `Nevladino udruženje` **nije** bundle slučaj (odluka 3).

**Ustanova / Druge organizacije.** Ova odluka **ne** izmišlja V1 mapiranje. **Ne** mapiraju se približno na DOO, NVO, Sportsku organizaciju niti drugi podržani Pravni oblik. Funkcionalnost koja zahtijeva podržani registrovani subjekt: fail-closed za tu funkcionalnost dok ne postoji podržano poslovno rješenje. Nepovezan pristup se **ne** blokira globalno.

### Interni / admin nalog

Uloga **nije** identitet. Interni / admin-only nalog **nije** prisiljen da kreira registrovani subjekt samo zato što `users` red postoji.

Administrativna funkcionalnost koja ne zahtijeva registrovani subjekt: **nema** completion gate. Ako isti nalog kasnije pokuša da djeluje kao registrovani subjekt, podržani kanonski identitet mora postojati prije te subject akcije. Do tada: fail-closed **samo** za tu akciju. NULL / admin nalog se **ne** mapira automatski na Fizičko lice.

### Moduli

Ova odluka definiše **platformski** mehanizam dopune. Zahtijevajući modul ostaje odgovoran kada mu je konkretan kanonski podatak potreban prema usvojenom BM/FS. KK, KN i EP se **ne** redizajniraju.

Pravilo: modul X zahtijeva podatak X + podatak X nedostaje / unresolved → fokusirana dopuna prije te akcije. Drugi modul se **ne** blokira automatski.

**e-Plaćanje.** Odluka 5 ostaje. EP koristi kanonski identitet i fail-closed je kada su potrebni identitetski podaci nepoznati, nedostaju ili nijesu podržani. Ako EP treba Status rezidentnosti, a on nedostaje: DECLARE-ON-USE prije EP akcije. Ako se podržani identitet ne može uspostaviti: EP ostaje nedostupan. Približno mapiranje zabranjeno.

**Konkursi.** Odluka 4 ostaje. KN `applicant_type` i identitetski podaci prijave ostaju KN snapshot. **Nisu** kanonski identity SSOT. Kanonski identitet se **ne** rekonstruše iz istorijskih KN prijava. Istorijski KN snapshot se **ne** prepisuje nakon dopune. Novi KN tok: ako usvojeni profil konkursa zahtijeva nedostajući kanonski podatak, fokusirana dopuna može prethoditi nastavku.

**Kalendar kulture.** KK uloge **nisu** Vrsta subjekta. Nedostatak Statusa rezidentnosti, CRPS-a ili Pravnog oblika **ne** izvodi sam po sebi KK completion blokadu. Okidač je samo već usvojeni KK zahtjev za konkretan kanonski podatak.

### Prekid i uspjeh

Ako korisnik odustane / izađe iz fokusirane dopune: nevalidna djelimična vrijednost **ne** postaje autoritativni kanonski identitet; zahtijevana funkcionalnost ostaje nedostupna dok dopuna ne uspije; nepovezana dozvoljena funkcionalnost ostaje dostupna; nalog / sesija se **ne** zaključava globalno.

Nakon uspješne dopune: kanonski podatak se čuva u kanonskom identitetu istog postojećeg naloga; taj konkretni completion need se gasi; naredni pristupi REUSE-uju sačuvani podatak; korisnik nastavlja / vraća se na prvobitno zahtijevanu funkcionalnost gdje je izvodljivo. Bez novog naloga, drugog identiteta i ponovne registracije.

Rute, HTTP kodovi, kontroleri i modal **nisu** propisani.

### Autorizacija i snapshoti

Dopuna identiteta **ne**: mijenja uloge; daje ovlašćenja; zaobilazi autorizaciju; zaobilazi status naloga; mijenja vlasništvo; pretvara interni nalog u subjekt samo zato što postoji UI dopune.

Dopuna identiteta **nije** autorizacija.

Dopuna **ne** prepisuje retroaktivno: istorijske KN prijave; istorijske `applicant_type` snapshot-e; istorijske EP / payment snapshot-e; audit zapise; druge nepromjenjive poslovne snapshot-e.

### Status dopune

Nova globalna oznaka `identity_complete` / `profile_complete` / `needs_completion` se **ne** uvodi. Potreba je izvedena: da li zahtijevana funkcionalnost treba kanonski podatak koji trenutno nedostaje ili nije razriješen? Nova DB kolona se **ne** uvodi.

### D10 / D14

Nedostajući / unresolved podatak = odluka 10. Postojeći podatak koji je nevalidan ili nekanonski po ciljnim pravilima = odluka 14 (Poglavlje 6.29). Prazan kanonski cilj nakon odbijanja nevalidnog legacy izvora **nije** D10 missing.

| Scenario | Odluka |
|----------|--------|
| Nedostaje Status rezidentnosti | 10 |
| Nedostaje obavezna država | 10 |
| Nedostaje CRPS, kada ga trenutna funkcija zahtijeva | 10 |
| Nedostaje identifikator, kada ga trenutna funkcija zahtijeva | 10 |
| Nedostaje Pravni oblik legacy NVO bundle-a | 10, eksplicitno razrješenje |
| Postojeći nevalidan JMB | 14 |
| Postojeći nevalidan PIB | 14 |
| Postojeći stari / nekanonski CRPS | 14 |
| Postojeći nekompatibilan telefon | 14 |
| Postojeća nekompatibilna adresa | 14 |
| Fizička migracija / backfill | 14 |

Ova odluka **ne** pretvara D14 slučajeve u obavezan ponovni unos tokom nepovezanog completion toka.

### Lifecycle klasifikacije

Ova odluka **ne** definiše: FL ne-preduzetnik → Preduzetnik; Preduzetnik → više nije Preduzetnik; DOO → drugi Pravni oblik; kasniju izmjenu NVO podtipa; druge pravne identitetske prijelaze. Dopuna popunjava nedostajuće činjenice **trenutno poznatog** identitetskog konteksta.

### Granica odluke 10

Ova odluka **ne** određuje: rollback (15); imena tabela / kolona; middleware; kontroler; rutu; modal; HTTP status; rollout. Migracija / backfill / grandfathering usvojeni su odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27). Verifikacija e-mail adrese **nije** dopuna identiteta.

---

## 6.26 Lokalizacija e-mail poruke za verifikaciju (CLOSED / PO USVOJENO — odluka 11)

Ovo poglavlje dokumentuje PO-usvojeni korisnički vidljivi sadržaj platformske e-mail poruke za verifikaciju. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila. Razrađuje jezičku i terminološku realizaciju već usvojenog koraka verifikacije e-mail adrese (`DK-BM-002` §4.1, §10 tač. 5; `DK-FS-002` §11.3). Ovaj TS **ne** mijenja `DK-BM-002` ni `DK-FS-002`.

### Predmet

Odluka 11 reguliše **samo** korisnički vidljivi sadržaj platformske e-mail poruke za verifikaciju: jezik; terminologiju; subject; greeting; body copy; natpis dugmeta akcije; salutation; te korisnički vidljivi framework helper / subcopy koji pripada **istoj** e-mail poruci.

Odluka **ne** određuje tehnički mehanizam verifikacije.

### Jezik i terminologija

Poruka je na **crnogorskom jeziku**.

Kanonski termini: **E-mail adresa**; **Platforma Digital Kotor**.

Kanonski korisnički tekst **nije** `email adresa`.

Korisniku se **ne** prikazuju podrazumijevane Laravel / framework poruke na engleskom jeziku u ovoj e-mail poruci.

Ova odluka **ne** uvodi: višejezičnu verification poruku; per-user locale; čuvanje jezičke preference; language selector; Laravel translation arhitekturu kao normativni zahtjev; novu locale DB kolonu.

### Granica prema odluci 9

Odluka 9 ostaje **CLOSED / PO USVOJENO**. Ovaj e-mail tekst **nije** validaciona poruka i **ne** ulazi u katalog Poglavlja 6.24. Ista jezička i terminološka politika, zaseban sadržajni artefakt.

### Jedan kanonski tekst

Isti kanonski korisnički vidljivi tekst koristi se gdje god se šalje **ista** platformska e-mail poruka za verifikaciju.

Odluka **ne** određuje kada ni zašto se dodatni slanje vrši. Ponovno slanje **nije** usvojeno ovom odlukom. Tehnički mehanizam, uključujući ponovno slanje iste poruke, usvojen je odlukom 12 (Poglavlje 6.27).

### Pozdrav

Pozdrav **ne** zavisi od `first_name` i **ne** pretpostavlja da nalog pripada Fizičkom licu.

Kanonski pozdrav: `Poštovani/a,`

### Privatnost

Poruka **ne** sadrži: JMB; PIB; CRPS registracioni broj; broj pasoša; korisničku lozinku; role / uloge; interne identifikatore.

### Kanonski tekst

Tačan korisnički vidljivi tekst:

**Subject:** `Verifikacija e-mail adrese – Digital Kotor`

**Greeting:** `Poštovani/a,`

**Body line 1:** `Hvala vam što ste se registrovali na Platformu Digital Kotor.`

**Body line 2:** `Molimo vas da kliknete na dugme ispod da biste verifikovali svoju e-mail adresu.`

**Action button:** `Verifikujte e-mail adresu`

**Body line 3:** `Ako niste kreirali nalog, ignorišite ovu poruku.`

**Salutation:**

`Srdačan pozdrav,`

`Tim Digital Kotor`

Ako framework prikaže dodatni korisnički vidljivi helper / subcopy za istu verification akciju, taj tekst mora biti na crnogorskom jeziku i **ne** smije biti u suprotnosti sa kanonskim tekstom odluke 11. Tačan tehnički template tog helpera **nije** propisan.

AS-IS tekstovi kao `Verifikacija email adrese - Digital Kotor`, `Verifikuj email adresu`, personalizacija `first_name` i podrazumijevani engleski MailMessage subcopy **nisu** cilj.

### Granica prema odluci 12

Odluka 11 **ne** normira tehnički mehanizam verifikacije. Tehnički mehanizam usvojen je odlukom 12 (Poglavlje 6.27).

Kanonski tekst odluke 11 **ne** sadrži rečenicu o isticanju linka i **nije** izmijenjen odlukom 12. Odluka 12 usvojila je rok verification linka od 60 minuta. Ako implementacija prikaže korisničku informaciju o roku, ta informacija pripada mehanizmu odluke 12 i **ne** smije biti u suprotnosti sa kanonskim tekstom odluke 11. Odluka 11 ostaje važeća nezavisno od mehanizma odluke 12.

### Nalog, dopuna i moduli

Verifikacija e-mail adrese je **account** činjenica (odluka 1). Ova odluka **ne** premješta stanje verifikacije u kanonski identitet subjekta, **ne** kreira identitetske tabele, drugi identitet, klasifikaciju, Vrstu subjekta, Pravni oblik ni status Preduzetnika.

Dopuna identiteta (odluka 10) **nije** verifikacija e-maila. Ova odluka **ne** uvodi ponovnu registraciju, identity-completion gate, next-login identity gate ni punu revalidaciju profila. Ako postojeći korisnik primi **istu** platformsku verification e-mail poruku prema mehanizmu odluke 12, primjenjuje se kanonski tekst odluke 11. Odluka 11 **ne** određuje da li i kada se to slanje vrši.

Nakon već usvojenog uspješnog završetka registracije, kada se platformska verification e-mail poruka pošalje, njen korisnički vidljivi sadržaj slijedi ovu odluku. Odluka **ne** redefiniše uspjeh registracije, trenutak slanja ni mehanizam.

KK, KN i EP poslovna pravila, snapshoti i eligibility **ne** mijenjaju se. Odluka **ne** redizajnira modulsku poštu.

Odluka važi za **e-mail poruku**. **Ne** normira samostalno: verify-email web stranicu; dashboard banner; profilski UI; Breeze prijevode; 403 poruke; password-reset, newsletter, KN, EP ni KK poštu.

Prethodno poslate e-mail poruke su istorijske komunikacije. Odluka ih **ne** prepisuje, **ne** migrira i **ne** backfill-uje. Primjenjuje se na kanonski ciljni tekst budućih slanja nakon implementacije.

Sadržaj **ne** daje autorizaciju, **ne** mijenja uloge ni vlasništvo naloga i **ne** dokazuje verifikaciju sam po sebi. Akcija verification linka usvojena je odlukom 12 (Poglavlje 6.27). Verification link **nije** mehanizam prijave.

### Granica odluke 11

Ova odluka **ne** određuje: tehnički mehanizam e-mail verifikacije (usvojen odlukom 12, Poglavlje 6.27); rollback (15); imena klasa; rute; template fajlove; HTTP status; šemu; locale kolonu. Migracija usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28). Kanonski tekst ovog poglavlja ostaje neizmijenjen.

---

## 6.27 Tehnički mehanizam e-mail verifikacije (CLOSED / PO USVOJENO — odluka 12)

Ovo poglavlje dokumentuje PO-usvojeni tehnički mehanizam već usvojenog koraka verifikacije e-mail adrese. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila. Razrađuje tehničku realizaciju `DK-BM-002` §4.1, §10 tač. 5 i `DK-FS-002` §11.3. Ovaj TS **ne** mijenja `DK-BM-002` ni `DK-FS-002`.

**NEW BUSINESS RULE REQUIRED: NO.**

Katalog funkcija koje zahtijevaju verifikovan nalog i zahtjev verifikacije na staff/admin površinama **nisu** apsorbovani ovom odlukom. Ostaju u pravilima konkretne funkcionalnosti / modula.

### Predmet

Odluka 12 definiše **tehnički mehanizam** verifikacije e-mail adrese: kanonsko stanje na nalogu; slanje nakon uspješne registracije; autentičnost i rok linka; autorizaciju zahtjeva; prelaz stanja; idempotentnost; prijavu prije verifikacije; tehnički primitiv provjere; ponovno slanje kao recovery; zaštitu od zloupotrebe; ponovnu verifikaciju pri izmjeni e-mail adrese; neuspjeh isporuke; granicu postojećih i internih naloga; serverski autoritet; šemu; sigurnost i privatnost.

Odluka 11 ostaje **CLOSED / PO USVOJENO**. Odluka 11 vlasnik je **COPY / LANGUAGE / TERMINOLOGY**. Odluka 12 vlasnik je **MECHANISM / LINK / EXPIRY / STATE / RESEND**. Kanonski tekst odluke 11 **nije** izmijenjen.

### Kanonsko stanje

Verifikacija je **account** činjenica (odluka 1). **Nije** Vrsta subjekta, Pravni oblik, status Preduzetnika, stanje dopune identiteta niti identitetsko stanje registrovanog subjekta.

Kanonsko stanje ostaje:

`users.email_verified_at`

* **NULL** — e-mail adresa **nije** verifikovana.
* **TIMESTAMP** — e-mail adresa **jeste** verifikovana, u tom trenutku.

**Ne** uvodi se: `verification_status`; `is_verified`; `identity_verified`; `verification_state`; identitetsko polje verifikacije.

Link ili isporuka **PENDING / EXPIRED / FAILED** **nisu** persisted stanja naloga.

### Slanje nakon registracije

Nakon uspješnog kreiranja novog naloga Platforma pokreće verifikaciju slanjem **iste** platformske e-mail poruke odluke 11 na **trenutnu** e-mail adresu naloga.

Nalog postoji **prije** završetka verifikacije. Neuspjeh ili nezavršetak verifikacije **ne** briše nalog.

### Autentičnost linka

Zahtjev za verifikaciju mora biti:

* serverski validiran;
* kriptografski zaštićen;
* vremenski ograničen;
* vezan za nalog;
* vezan za **trenutnu** e-mail adresu.

Izmjena relevantnih parametara mora učiniti verifikaciju nevažećom.

Tehnička realizacija **smije** koristiti framework-native signed URL mehanizam. Posebna tabela tokena **nije** obavezna. Nova šema se **ne** uvodi.

### Rok

Normativni vijek verification linka je **60 minuta**. Trajanje **ne** varira po okruženju.

Odluka 12 vlasnik je ovog tehničkog pravila. Kanonski tekst odluke 11 **ne** sadrži rečenicu o roku i **nije** dopunjen njome. Ako implementacija prikaže korisničku informaciju o roku, ta informacija pripada mehanizmu odluke 12 i **ne** smije biti u suprotnosti sa odlukom 11.

### Verification link nije autentikacija

Verification link **nije** mehanizam prijave i **nije** magic-login vjerodostojnost.

Link sam po sebi:

* **ne** prijavljuje korisnika;
* **ne** uspostavlja sesiju;
* **ne** prebacuje trenutnu sesiju na drugi nalog.

Verifikacija zahtijeva **autentikovani** nalog koji odgovara nalogu vezanom za zahtjev.

Ako je link otvoren dok niko nije autentikovan: verifikacija se **ne** završava odmah; korisnik se prvo autentikuje; nakon uspješne autentikacije **istog** naloga verifikacija može da se nastavi.

Ako je autentikovan **drugi** nalog: verifikacija se **odbija**; sesija se **ne** prebacuje; drugi nalog se **ne** verifikuje.

AS-IS ponašanje koje iz verification URL-a poziva prijavu ciljnog naloga **nije** cilj.

### Autorizacija zahtjeva

Verifikacija se završava samo kada **sva tri** uslova važe:

1. zahtjev je autentičan i nije istekao;
2. zahtjev je vezan za trenutnu e-mail adresu;
3. autentikovani nalog odgovara nalogu vezanom za zahtjev.

Neispunjenje bilo kojeg uslova: **nema** promjene stanja verifikacije.

### Uspjeh i autoritet servera

Za važeći zahtjev na neverifikovanom nalogu server postavlja `users.email_verified_at` na trenutak uspješne verifikacije.

Samo serverska validacija i persistencija su autoritativne. Klijent, redirect, klik, e-mail tekst ili UI stanje **ne** mogu označiti nalog verifikovanim.

Server provjerava: autentičnost; rok; vezu za trenutnu e-mail adresu; poklapanje autentikovanog naloga; zatim upisuje timestamp.

### Idempotentnost

Ako je nalog već verifikovan i zahtjev je inače važeći:

* verifikacija je bezbjedan no-op;
* originalni `email_verified_at` se **ne** osvježava;
* nalog ostaje verifikovan;
* zahtjev **ne** stvara grešku samo zato što je nalog već verifikovan.

Nevažeći ili istekli ponovljeni zahtjev **ne** mijenja već verifikovano stanje.

### Nevažeći, izmijenjeni ili istekli zahtjev

Ako je zahtjev: izmijenjen; kriptografski nevažeć; istekao; vezan za pogrešnu e-mail adresu; vezan za drugi nalog; korišćen dok je autentikovan drugi nalog — stanje verifikacije se **ne** mijenja.

Mehanizam feedback mora biti korisnički vidljiv na **crnogorskom jeziku**. **Ne** ulazi u katalog odluke 9. **Ne** ulazi u kanonski e-mail tekst odluke 11. Tačan UI tekst smije se dokumentovati kao mehanizam feedback odluke 12, **bez** platformskog projekta lokalizacije.

### Prijava prije verifikacije

**Aktivan** neverifikovani nalog **smije** se autentikovati. Verifikacija **nije** preduslov prijave.

Autentikacija i pristup funkcijama koje zahtijevaju verifikovan nalog ostaju odvojene. Globalna zabrana prijave neverifikovanih naloga se **ne** uvodi.

### Primitiv provjere; bez kataloga funkcija

Ako funkcionalnost, prema usvojenom BM/FS ili modulskom pravilu, zahtijeva verifikovan nalog, Platforma **mora** serverski provjeriti kanonsko stanje `users.email_verified_at` prije dozvole te funkcionalnosti.

Odluka 12 **ne** definiše katalog funkcija koje zahtijevaju verifikaciju. **Ne** kanonizuje postojeći AS-IS globalni `verified` middleware obuhvat kao ciljni proizvodni katalog. **Ne** proglašava dashboard, profil, KN, EP, KK ni admin globalno obaveznim za verifikaciju samo zato što sadašnje rute koriste `verified` middleware.

Zahtjev pripada pravilu konkretne funkcionalnosti / modula. Odluka 12 daje samo ponovljivi tehnički primitiv.

### Površine recovery-ja

Autentikovani neverifikovani korisnik mora moći da pristupi tehničkim površinama potrebnim za završetak verifikacije, uključujući: verification notice; akciju ponovnog slanja; odjavu.

Provjera verifikovanog stanja **ne** smije zatvoriti korisnika u petlju bez recovery-ja. Nepovezane aplikacione površine se ovdje **ne** definišu.

### Ponovno slanje

Ponovno slanje je **tehnički recovery** već usvojenog koraka verifikacije. **Nije** nova registracija, novi nalog, kampanja podsjetnika, marketing niti izmjena poslovne prihvatljivosti.

Autentikovani vlasnik **neverifikovanog** naloga smije zatražiti još jedno slanje **iste** poruke odluke 11 na **trenutnu** e-mail adresu. **Ne** šalje se na drugu adresu. **Ne** zahtijeva novi tekst odluke 11. Automatske kampanje podsjetnika se **ne** uvode.

### Zaštita od zloupotrebe

Verifikacija i ponovno slanje moraju imati serversko kratkoročno ograničenje učestalosti / zaštitu od zloupotrebe. To je **sigurnosna kontrola**, ne poslovna kvota.

Tačna vrijednost (uključujući AS-IS `6/minutu`) **nije** normativna semantika odluke 12. Konkretna stopa je implementacioni / konfiguracioni parametar.

Dnevne ili mjesečne kvote i kazne naloga se **ne** uvode.

### Izmjena e-mail adrese

Verifikacija dokazuje **trenutnu** e-mail adresu.

Kada se e-mail adresa naloga promijeni:

1. prethodno verifikovano stanje se briše;
2. nalog postaje neverifikovan za novu adresu;
3. stari verification linkovi **ne** smiju verifikovati novu adresu;
4. nova poruka odluke 11 šalje se na **novu** trenutnu adresu;
5. nova adresa prolazi isti mehanizam odluke 12.

Verifikovano stanje se **ne** zadržava preko stvarne izmjene e-mail adrese.

### Neuspjeh isporuke

Kreiranje naloga i isporuka e-maila su odvojeni tehnički ishodi.

Ako je nalog uspješno kreiran, a slanje verifikacione poruke ne uspije: registracija se **ne** rollback-uje; nalog se **ne** briše; nalog ostaje neverifikovan; recovery je ponovno slanje i/ili operativni oporavak isporuke.

Odluka **ne** uvodi: brisanje naloga; automatski rollback registracije; rok do kada nalog mora biti verifikovan; automatsko isticanje naloga.

### Postojeći korisnici

Već verifikovani nalozi ostaju verifikovani. Neverifikovani ostaju neverifikovani dok mehanizam nije završen.

**Ne** radi se: tiha verifikacija; ponovna registracija; drugi nalog; identity-completion gate; backfill verifikovanog stanja; masovno slanje svim postojećim neverifikovanim korisnicima.

Odluka 10 ostaje odvojena. Fizički census / backfill usvojen je odlukom 14 (Poglavlje 6.29).

### Interni / admin nalozi

Interni / staff / admin nalozi koriste istu kanonsku account činjenicu verifikacije ako je stanje verifikacije potrebno.

Odluka 12 **ne**: tjera staff kroz javni registracioni verification UI; definiše koje admin/staff funkcije zahtijevaju verifikovanu e-mail adresu; uvodi staff verification poslovnu politiku; uvodi staff izuzeća kao poslovno pravilo.

Da li konkretna administrativna funkcija zahtijeva verifikovan nalog pripada pravilima te funkcionalnosti. Provisioning smije postaviti početno stanje prema sopstvenim usvojenim pravilima. Provisioning se ovdje **ne** redizajnira.

### Šema

Nova šema **nije** potrebna. Zadržava se `users.email_verified_at`.

**Ne** uvodi se: nova tabela verifikacije; tabela tokena; identitetska tabela verifikacije; enum statusa; locale polje — osim ako buduća implementacija dokaže stvarni tehnički blocker koji zahtijeva poseban pregled.

Odluka **ne** propisuje izvršenje migracije. Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30).

### Sigurnost i privatnost

U verification porukama, greškama i logovima **ne** izlažu se: JMB; PIB; CRPS; broj pasoša; lozinka; uloge; interni osjetljivi identifikatori.

Sirovi signed verification URL se **ne** loguje. Tajni ključevi i tokeni se **ne** izlažu. Autentikovani nalog **nikada** se ne prebacuje na nalog iz linka.

### Moduli

* **Platforma:** DIRECT — kanonski mehanizam.
* **KK:** INDIRECT samo ako KK pravilo koristi account stanje verifikacije. KK uloge se **ne** mijenjaju.
* **KN:** **nema** izmjene `applicant_type` niti istorijskih snapshot-a.
* **EP:** **nema** izmjene kanonskog identiteta niti fail-closed pravila odluke 5.

Odluka **ne** određuje modulsku prihvatljivost.

### AS-IS tehnički dug (nije cilj; nije implementacija)

1. Trenutni verify kontroler može gostu da iz linka pozove prijavu ciljnog naloga; to **nije** cilj.
2. Široki AS-IS `verified` middleware obuhvat **nije** automatski ciljni katalog.
3. Izmjena e-maila u profilu trenutno briše `email_verified_at`, ali **ne** šalje nužno novu poruku odluke 11.
4. Kanonski tekst odluke 11 još **nije** implementiran.
5. Trenutne mehanizam-poruke mogu biti na engleskom.

Ova odluka te korekcije **ne** implementira.

### Prihvatni scenariji odluke 12

1. Novi nalog: kreiran neverifikovan; šalje se poruka odluke 11.
2. Važeći link + isti autentikovani nalog: postavlja se timestamp.
3. Važeći link + gost: **nema** trenutne verifikacije; prvo autentikacija.
4. Važeći link + drugi autentikovani nalog: odbijeno; bez prebacivanja sesije; bez promjene stanja.
5. Izmijenjeni link: bez promjene stanja.
6. Link stariji od 60 minuta: bez promjene stanja; ponovno slanje dostupno nakon autentikacije.
7. Već verifikovan: bezbjedan no-op; timestamp neizmijenjen.
8. Aktivan neverifikovani nalog: prijava dozvoljena.
9. Funkcija koja po svom pravilu zahtijeva verifikovan nalog: blokirana dok nije verifikovana.
10. Funkcija bez takvog zahtjeva: odluka 12 **ne** nameće globalnu blokadu.
11. Ponovno slanje: ista poruka odluke 11 na trenutnu adresu; samo autentikovani neverifikovani vlasnik.
12. Zloupotreba: serverski kratkoročno ograničena.
13. Verifikovani korisnik mijenja e-mail: stanje obrisano; nova poruka odluke 11; nova verifikacija obavezna.
14. Link stare adrese nakon izmjene: nevažeć za novu adresu.
15. Neuspjeh isporuke: nalog ostaje; neverifikovan; bez rollback-a / brisanja.
16. Postojeći verifikovani korisnik: ostaje verifikovan.
17. Postojeći neverifikovani korisnik: ostaje neverifikovan; bez tihe verifikacije.
18. Staff nalog: odluka 12 **ne** nameće javni registracioni verification tok i **ne** sastavlja staff katalog.

### Granica odluke 12

Ova odluka **ne** određuje: rollback / safety (15); imena klasa, ruta i middleware-a; HTTP status; tačnu stopu throttle-a; katalog funkcija koje zahtijevaju verifikaciju; staff/admin katalog; kanonski tekst odluke 11. Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Uklanjanje Kotor ograničenja usvojeno je odlukom 13 (Poglavlje 6.28).

---

## 6.28 Grad / uklanjanje Kotor ograničenja (CLOSED / PO USVOJENO — odluka 13)

Ovo poglavlje dokumentuje PO-usvojenu tehničku realizaciju uklanjanja postojećeg ograničenja Grada na Opštinu Kotor u platformskoj registraciji i relevantnom profilu. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila. Razrađuje tehničku realizaciju već usvojenih `DK-BM-002` §10 tač. 11–15, §12 i `DK-FS-002` §14, §17, §18.3, §19 tač. 11. Ovaj TS **ne** mijenja `DK-BM-002` ni `DK-FS-002`.

**NEW BUSINESS RULE REQUIRED: NO.**

**CITY LIMITED TO KOTOR: NO.**

**KOTOR PLATFORM OWNERSHIP IMPLIES KOTOR ADDRESS: NO.**

**CITY INPUT: FREE TEXT.**

**CONTROLLED CITY CATALOG: NO.**

### Predmet

Odluka 13 definiše **tehničku realizaciju** uklanjanja AS-IS ograničenja Grada na Opštinu Kotor iz platformske registracije i relevantnog profila.

Odluka **ne** reotvara već usvojeno BM/FS pravilo da Grad nije ograničen na Opštinu Kotor. To pravilo ostaje usvojeno. Odluka 13 bira tehnički cilj uklanjanja suprotnog AS-IS ponašanja.

### Obaveznost Grada

Grad je obavezan na svim identitetskim granama koje BM/FS već zahtijevaju adresu:

* Fizičko lice — Rezident;
* Fizičko lice — Nerezident;
* Preduzetnik;
* Pravno lice;
* Dio stranog privrednog društva.

Ovlašćeno lice **nema** posebno polje Grad.

Zastupnik Dijela stranog privrednog društva **nema** posebno polje Grad.

Nove adresne grane se **ne** uvode.

### Grad nije ograničen na Kotor

Grad **nije** ograničen na:

* string `Kotor`;
* Opštinu Kotor;
* naselja na teritoriji Opštine Kotor;
* poštanske brojeve 85310 / 85330;
* bilo koje drugo Kotor-specifično adresno pravilo platformskog identiteta.

Kotor ostaje **jedan validan primjer**, ne jedina vrijednost.

### Vlasništvo Platforme

Činjenica da je Digital Kotor platforma Opštine Kotor **ne** implicira:

* da korisnik mora živjeti u Kotoru;
* da Preduzetnik mora biti registrovan u Kotoru;
* da Pravno lice mora imati adresu / sjedište u Kotoru;
* da dio / ogranak Dijela stranog privrednog društva mora biti u Kotoru.

Identitetska pravila se **ne** izvode iz institucionalnog vlasništva Platforme.

### Unos i katalog

Grad je **slobodan tekst**.

Kontrolisani katalog Gradova se **ne** uvodi. **Ne** uvodi se: katalog opština; globalna baza gradova; katalog gradova zavisan od države; lista naselja Opštine Kotor kao platformsko identitetsko pravilo; ISO subdivision kod; geokodiranje; API verifikacije adrese.

Kanonski katalog država odluke 6 ostaje odvojen. Grad **nije** country catalog vrijednost.

### Grad i Država

Grad i Država su odvojeni podaci.

Za Nerezidenta:

* Grad je slobodan tekstualni dio adrese;
* Država prebivališta je kontrolisana vrijednost kataloga odluke 6.

Unakrsna geografska validacija Država/Grad se **ne** uvodi. Pravilo „Grad mora pripadati izabranoj Državi“ se **ne** usvaja.

### Grad, Opština i Naselje

Grad **nije** Opština.

Polje Opština se **ne** uvodi.

Polje Naselje se **ne** uvodi (`DK-BM-002` §10 tač. 15; `DK-FS-002` §6.3, §14, §17).

Postojeće liste naselja Opštine Kotor **nisu** ciljni platformski identitetski katalog.

### Fizičko lice — Rezident

Rezidentno Fizičko lice smije unijeti bilo koju validnu slobodnu tekstualnu vrijednost Grada.

Primjeri: Kotor — validno; Podgorica — validno; Budva — validno; Nikšić — validno; Herceg Novi — validno.

Rezidentnost u Crnoj Gori **nije** prebivalište u Kotoru.

### Fizičko lice — Nerezident

Nerezidentno Fizičko lice smije unijeti strani Grad.

Primjeri: Hrvatska + Dubrovnik — validno; Ujedinjeno Kraljevstvo + London — validno; Srbija + Beograd — validno.

Grad i Država prebivališta ostaju odvojene činjenice. Ograničenje Grada samo na Crnu Goru se **ne** uvodi.

### Preduzetnik

Preduzetnik ostaje Fizičko lice. Grad pripada adresi Fizičkog lica. Druga poslovna adresa / drugi Grad se ovom odlukom **ne** uvodi.

Primjeri: Herceg Novi — validno; Podgorica — validno; Kotor — validno.

### Pravno lice

Grad predstavlja adresu Pravnog lica. **Nije** ograničen na Kotor.

Primjeri: DOO Podgorica — validno; DOO Budva — validno; DOO Kotor — validno; Nevladino udruženje Cetinje — validno; Sportska organizacija Bar — validno.

V1 identifikatori Crne Gore **ne** impliciraju Kotor.

### Dio stranog privrednog društva

Za Dio stranog privrednog društva Grad **semantički** predstavlja Grad adrese dijela / ogranka **u Crnoj Gori** (`DK-BM-002` §9.2; `DK-FS-002` §14). To je upstream semantički zahtjev.

Grad i dalje ostaje slobodan tekst.

Odluka **ne** uvodi: katalog crnogorskih gradova; katalog opština; geokodiranje; eksternu verifikaciju adrese; lažnu geografsku validaciju.

Razlikuju se:

* **SEMANTIČKA VALIDNOST** — značenje polja je adresa dijela / ogranka u Crnoj Gori;
* **TEHNIČKI GEOGRAFSKI DOKAZ** — V1 slobodni tekst **nema** autoritativni geografski izvor kojim bi server automatski dokazao da je uneseni Grad u Crnoj Gori.

Primjeri:

* DSPD / Grad = Podgorica — semantički validno;
* DSPD / Grad = Budva — semantički validno;
* DSPD / Grad = Kotor — semantički validno;
* DSPD / Grad = London — **nije** ciljno-validan DSPD Grad. Semantički je neispravan za deklarisano značenje adrese dijela / ogranka u Crnoj Gori. V1 validacija slobodnog teksta **nema** autoritativni geografski izvor da tu činjenicu automatski dokaže ili odbije. Odluka 13 namjerno **ne** uvodi lažnu geografsku izvjesnost.

### Registracija i profil

Registracija i relevantni profil koriste **isto polje**, **istu obaveznost**, **istu semantiku** i **istu validaciju** za Grad.

Kotor ograničenje se **ne** zadržava u profilu ako je uklonjeno iz registracije, niti obrnuto.

### Server i klijent

Server je autoritativan.

Server **ne smije** odbiti Grad samo zato što vrijednost:

* nije Kotor;
* nije naselje Opštine Kotor;
* nije na teritoriji Opštine Kotor.

Klijent **ne smije** forsirati niti implicirati Kotor-only.

Odluka **ne** uklanja legitimnu validaciju obaveznosti, string tipa i tehničkog limita čuvanja.

### Validacija

Poslovni semantički zahtjev: Grad mora biti prisutan kada je adresna grana aktivna.

Kanonska poruka ostaje odluka 9: `Unesite grad.`

Nova D9 poruka se **ne** uvodi. AS-IS poruka `Grad mora biti naselje na teritoriji Opštine Kotor.` **nije** cilj.

Ne uvodi se proizvoljna geografska sintaksa. ASCII-only pravilo se **ne** uvodi. Višeriječni nazivi, dijakritici, crtica i apostrof **ne** smiju se odbijati samo zbog sintakse.

Postojeći tehnički limit čuvanja smije ostati implementaciono / storage ograničenje. Trim vanjskog whitespace-a smije ostati implementaciona higijena.

**Ne** uvodi se normativno: title-case; velika slova; transliteracija; automatska zamjena u Kotor.

### Postojeći korisnici

Odluka 10 ostaje **CLOSED / PO USVOJENO**.

Postojeći upotrebljiv kanonski Grad se **REUSE-uje**. Primjeri: Podgorica — REUSE; Budva — REUSE; Kotor — REUSE. Ponovni unos Kotora se **ne** zahtijeva.

Nedostajući Grad: focused dopuna odluke 10, kada ga funkcija zahtijeva.

Postojeći strukturno nevalidan / nekanonski Grad: odluka 14 (Poglavlje 6.29).

Puna revalidacija profila se **ne** uvodi.

Istorijski Grad = Kotor **nije** automatski pogrešan. Ako je stvarna adresa korisnika, ostaje validan. **Ne** prepisuje se automatski samo zato što je stari UI istorijski forsirao Kotor.

### Moduli

**MODULE-SPECIFIC KOTOR ELIGIBILITY: SEPARATE FROM PLATFORM IDENTITY.**

Modul smije nezavisno zahtijevati prebivalište ili sjedište na teritoriji Opštine Kotor, ili drugi geografski uslov prihvatljivosti, ako to postoji u usvojenom BM/FS tog modula.

Primjer: kanonski platformski Grad = Podgorica; konkretni KN konkurs zahtijeva Opštinu Kotor. Platformski identitet ostaje validan. KN smije odbiti tu prijavu po sopstvenom pravilu.

KN Kotor eligibility se **ne** premješta u registraciju. KN / EP / KK se ovom odlukom **ne** redizajniraju.

### Šema

Nova šema se **ne** uvodi. Konceptualni cilj odluke 1 ostaje: `Ulica i broj` i `Grad` pripadaju subject-specific strukturi. Fizički AS-IS `users.city` ostaje legacy implementacija tokom kompatibilnosti. Fizički prelaz usvojen je odlukom 14 (Poglavlje 6.29). Odluka 13 **ne** propisuje izvršenje migracije.

### AS-IS tehnički dug (nije cilj; nije implementacija)

Nisu cilj platformskog identiteta:

1. `HomeController` Kotor restrikcija adrese / Grada;
2. `ProfileUpdateRequest` Kotor restrikcija adrese / Grada;
3. upotreba `KotorAddress` / `isInKotorMunicipality` kao platformsko identitetsko pravilo;
4. Kotor-only helper tekst u registraciji i profilu;
5. AS-IS poruka da Grad mora biti naselje Opštine Kotor;
6. lista naselja Opštine Kotor kao DK identitetski katalog.

KN modulska Kotor validacija **nije** tehnički dug odluke 13 samo zato što koristi sličnu pomoćnu logiku. Klasifikuje se odvojeno kao modulska prihvatljivost.

Ova odluka te korekcije **ne** implementira.

### Prihvatni scenariji odluke 13

1. FL Rezident / Grad = Kotor: **ACCEPT**.
2. FL Rezident / Grad = Podgorica: **ACCEPT**.
3. FL Rezident / Grad = Budva: **ACCEPT**.
4. FL Nerezident / Hrvatska / Dubrovnik: **ACCEPT**.
5. FL Nerezident / Ujedinjeno Kraljevstvo / London: **ACCEPT**.
6. Preduzetnik / Grad = Herceg Novi: **ACCEPT**.
7. Preduzetnik / Grad = Kotor: **ACCEPT**.
8. DOO / Grad = Podgorica: **ACCEPT**.
9. DOO / Grad = Kotor: **ACCEPT**.
10. Nevladino udruženje / Grad = Cetinje: **ACCEPT**.
11. Sportska organizacija / Grad = Bar: **ACCEPT**.
12. DSPD / Grad = Podgorica: **SEMANTIČKI VALIDNO**.
13. DSPD / Grad = Kotor: **SEMANTIČKI VALIDNO**.
14. DSPD / Grad = London: **SEMANTIČKI NEISPRAVAN ZA DEKLARISANO ZNAČENJE DSPD ADRESE**; V1 validacija slobodnog teksta **nema** autoritativni geografski izvor da ovu vrijednost automatski dokaže ili odbije; odluka 13 **ne** uvodi lažnu geografsku validaciju. **Nije** dokumentovan kao ciljno-validan DSPD Grad.
15. Nedostaje Grad: **REJECT** sa `Unesite grad.`
16. Postojeći Grad = Podgorica: **REUSE**.
17. Kanonski Grad = Podgorica + KN konkurs zahtijeva Opštinu Kotor: platformski identitet ostaje validan; KN smije primijeniti sopstvenu prihvatljivost.
18. Profil Grad Kotor → Budva: **ACCEPT**.

### Granica odluke 13

Ova odluka **ne** određuje rollback / safety / deploy redoslijed (15); imena klasa; rute; šemu; uklanjanje runtime koda `KotorAddress`; KN / EP / KK redizajn. Migracija / backfill / fizički prelaz adrese usvojeni su odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Kanonska poruka odluke 9 ostaje `Unesite grad.` Odluka 10 ostaje vlasnik dopune postojećeg korisnika. Odluka 13 **nije** reotvorena.

---

## 6.29 Migracija / backfill postojećih korisničkih podataka (CLOSED / PO USVOJENO — odluka 14)

Ovo poglavlje dokumentuje PO-usvojenu migracionu strategiju prelaska postojećih korisničkih podataka na usvojeni D1 ciljni identitetski model. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila. Razrađuje tehničku realizaciju već usvojenih `DK-BM-002` §11 i §16, `DK-FS-002` §16 i odluka 1–13. Ovaj TS **ne** mijenja `DK-BM-002` ni `DK-FS-002`. Odluke 1–13 **ostaju CLOSED / PO USVOJENO** i **nisu** reotvorene. Odluka 10 **nije** reotvorena. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Odluka 14 **nije** reotvorena.

**NEW BUSINESS RULE REQUIRED: NO.**

**ADOPTED MIGRATION STRATEGY: OPTION B.**

### Predmet

Odluka 14 definiše **strategiju fizičkog prelaza** postojećih korisničkih podataka na D1 ciljni identitetski model, uz isti korisnički nalog, očuvanje poslovnog identiteta i postojećih veza, **bez nagađanja** poslovnih činjenica.

Odluka određuje: šta se transformiše; šta se ne transformiše; deterministička mapiranja; backfill semantiku; validan vs nevalidan legacy; invalid vs missing; kompatibilnost; dry-run; idempotency; očuvanje sirovog legacyja; read precedence; izvedeni compatibility mirror.

### Šta odluka 14 nije

Odluka **nije**:

* „migriraj sve“ / obavezna kompletnost svakog postojećeg profila;
* globalna ponovna registracija;
* next-login profile gate;
* produkcioni rollout, cutover, feature switch;
* rollback / deploy procedura;
* destruktivni legacy cleanup / DROP kolona.

### Usvojena strategija — OPTION B

Usvojeno je:

**DETERMINISTIČKI BACKFILL**
\+ **CANONICAL-FIRST READS**
\+ **KONTROLISANA LEGACY KOMPATIBILNOST**
\+ **D10 DOPUNA ZA GENUINO NEDOSTAJUĆE / UNRESOLVED**
\+ **D14 KOREKCIJA ZA INVALID LEGACY**
\+ **FAIL-CLOSED SAMO GDJE ZAHTIJEVANA FUNKCIJA TREBA KANONSKI PODATAK KOJI NEDOSTAJE / JE NEVALIDAN / NIJE PODRŽAN**.

Ovo **nije** preporuka. **PO USVOJENO.**

Nisu usvojeni: big-bang da svi nalozi odmah budu kompletni kanonski profili; lazy-only migracija kao jedina strategija; opšti dual-write svih identitetskih atributa kao dva SSOT-a.

### Očuvanje naloga

Migracija **čuva**:

* `users.id` kao stabilno account sidro;
* isti nalog;
* istu e-mail adresu;
* istu lozinku / password hash;
* istu dodjelu uloge;
* isti `activation_status`;
* isto vlasništvo / postojeće FK veze;
* isti account identitet.

Migracija **ne smije**: kreirati drugi nalog; mijenjati vlasnika; mijenjati ulogu; aktivirati ili deaktivirati nalog; resetovati lozinku; mijenjati e-mail; prebacivati sesiju; tiho verifikovati e-mail.

### Fizički D1 cilj

Kanonski identitet je fizički odvojen od account sloja (odluka 1). D14 zahtijeva fizičko uspostavljanje D1 identitetskih struktura i backfill prema ovoj odluci.

Odluka **ne** kanonizuje: tačna SQL imena tabela; imena migracionih klasa; indexe; imena komandi.

### Deterministička D3 projekcija

Automatski se projektuje **samo** D3 jednoznačno mapiranje.

| Legacy `users.user_type` | Cilj |
|--------------------------|------|
| `Fizičko lice` | FL; Preduzetnik = Ne |
| `Preduzetnik` | FL; Preduzetnik = Da |
| `Ortačko društvo` | PL / OD |
| `Komanditno društvo` | PL / KD |
| `Akcionarsko društvo` | PL / AD |
| `Društvo sa ograničenom odgovornošću` | PL / DOO |
| `Nevladino udruženje` | PL / Nevladino udruženje |
| `Sportska organizacija` | PL / Sportska organizacija |
| `Dio stranog društva (predstavništvo ili poslovna jedinica)` | DSPD (samo Vrsta subjekta) |

Klasifikacija se **ne** izvodi iz: `company_name`; PIB; CRPS; adrese; Grada; e-maila; uloge; KN prijave; telefona; istorijskog snapshot-a — osim ako D3 već eksplicitno daje to mapiranje.

### NVO bundle

Legacy `Udruženje (nvo, fondacije, sportske organizacije)`:

* Vrsta subjekta: Pravno lice;
* Pravni oblik: **UNRESOLVED**;
* **nema** automatskog podtipa.

Kada funkcija zahtijeva oblik, D10 razrješenje bira **samo**: Nevladino udruženje; Nevladina fondacija; Sportska organizacija.

Podtip se **ne** pogađa iz imena, PIB-a, CRPS-a, KN, adrese, e-maila, uloge ni slobodnog teksta.

Nakon kanonskog razrješenja stari bundle **nije** autoritativan i **nije** target fact. Smije ostati samo kao istorijski / sirovi legacy tokom kompatibilnosti.

### Nepodržani legacy tipovi

`Ustanova (državne i privatne)` i `Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)`:

* **nema** približnog V1 mapiranja;
* **ne** mapiraju se na PL / Ostalo, NVO, FL ni izmišljenu kategoriju;
* nalog / auth / uloge ostaju;
* kanonski podržani subjekt se **ne** kreira samo zbog tih vrijednosti;
* funkcija koja zahtijeva podržani kanonski identitet: **fail-closed** za tu funkciju;
* **nema** globalnog login lock-a.

### Interni / admin nalozi

Uloga **nije** identitet.

Staff / internal / admin nalog bez registrovanog subjekta **ne** mapira se automatski na FL ni na drugi subjekt samo zbog uloge (`admin`, `superadmin`, `kk_admin`, `konkurs_admin`, `komisija` ili druge staff uloge).

Nalog / auth / uloga ostaju. Subject identity se kreira / razrješava samo ako stvarni registrovani-subjekt slučaj to zahtijeva, prema D10 / upstream pravilima.

### Validan legacy podatak

**SOURCE PRESENT + VALID / CANONICAL → REUSE / deterministička migracija u cilj.**

Ponovni unos se **ne** zahtijeva samo zato što se mijenja fizičko čuvanje.

Primjeri, uz primjenjivost grane: validan JMB; validan PIB; upotrebljiv broj pasoša; upotrebljiva imena; validan poslovni naziv; validna adresa; validan Grad uključujući ne-Kotor Grad; validan Status rezidentnosti za FL; tačno postojeće `email_verified_at` stanje.

### D10 / D14 granica

| | Izvor | Owner |
|--|--------|--------|
| **A** | SOURCE MISSING | **D10** |
| **B** | SOURCE PRESENT + VALID | REUSE / migracija |
| **C** | SOURCE PRESENT + INVALID / NONCANONICAL | **D14 korekcija** |
| **D** | SOURCE PRESENT + AMBIGUOUS | bez nagađanja; D14 klasifikacija; D10 eksplicitno izjašnjenje samo gdje D10 već vlasniči unresolved poslovnu činjenicu |

**INVALID LEGACY ≠ D10 MISSING.**

Nevalidna legacy vrijednost koja se pri backfill-u **ne kopira** u kanonsko polje, pa kanonsko polje ostane prazno, **ne** prekvalifikuje se u D10 missing.

D14 ostaje vlasnik **uslova korekcije**. Fokusirani korisnički tok korekcije **smije** koristiti isti obrazac interakcije kao D10 focused completion. Razlog ostaje: **D14 invalid legacy**, ne originally missing / D10.

D10 **nije** reotvorena.

### Nema globalnog completion gate-a

D14 **ne** uvodi: globalni `profile_complete`; `identity_complete`; `migration_status` kao obavezno poslovno stanje; next-login full-profile completion; ponovnu registraciju; globalni lock naloga.

Korisnik sa missing / invalid / unsupported podatkom blokiran je **samo** gdje zahtijevana funkcija stvarno treba taj kanonski podatak / identitet. Nepovezana funkcionalnost ostaje dostupna gdje je inače dozvoljena.

### Persisted migration status

**PERSISTED MIGRATION STATUS REQUIRED: NO.**

**Ne** uvodi se normativni zahtjev za `migration_status`, `legacy_invalid`, `profile_complete`, `identity_complete` niti slično DB stanje samo da se razlikuju D10 i D14.

Tokom kompatibilnosti razlika se izvodi iz: kanonske vrijednosti + sačuvanog sirovog legacyja + kanonskog validatora.

Minimalni invarijant: sirovi legacy ostaje dostupan tokom kompatibilnosti; nevalidan raw se **ne** kopira kao validan kanon; nevalidan raw **nikad** nije fallback-validan.

### JMB

Odluka 7 ostaje.

* VALID po D7 → REUSE / migracija u kanonski FL JMB;
* MISSING kada je potreban → D10;
* PRESENT ali INVALID / NONCANONICAL → D14 korekcija; **ne** kopirati kao kanonski-validan; sačuvati raw; **bez** tihe popravke; **bez** checksum repair.

**Ne** uvodi se: semantička validacija datuma / regiona; nova uniqueness; semantička inferencija.

### PIB

Odluka 7 ostaje.

* VALID po D7 → REUSE / migracija;
* MISSING kada je potreban → D10;
* PRESENT ali INVALID / NONCANONICAL → D14; **ne** kopirati kao validan kanonski PIB; sačuvati raw; **bez** tihe popravke; **bez** LEFT-truncate repair; **bez** PIB iz CRPS.

Istorijske vrijednosti pogođene prethodnim `LEFT(pib,8)` ponašanjem **nisu** automatski ispravne samo zato što imaju 8 karaktera. Moraju zadovoljiti D7.

### CRPS

Odluka 8 ostaje.

Trenutni platformski `users` identitet **nema** kanonski CRPS izvor.

KN `applications.crps_number` **ne** diže se u kanonski platformski identitet. Ostaje istorijski / modulski snapshot, **nije** identity SSOT.

Gdje je CRPS obavezan (Preduzetnik, OD, KD, AD, DOO, DSPD): nedostajući kanonski CRPS dopunjava se prema D10 kada funkcija zahtijeva.

NVO / fondacija / sportska organizacija u V1: **nema** kanonskog CRPS identity polja. Istorijski KN CRPS se **ne** briše samo zato što nije V1 platformski identitet.

### Normalizacija identifikatora

Za JMB / PIB / CRPS backfill:

| Transformacija | Usvojeno |
|----------------|----------|
| Outer whitespace trim | **DA** |
| Internal whitespace removal | **NE** |
| Hyphen removal | **NE** |
| Slash removal | **NE** |
| Leading zero insertion | **NE** |
| Leading zero removal | **NE** |
| Checksum repair | **NE** |
| Transliteration | **NE** |

Strukturna mutacija **nije** automatska migraciona normalizacija.

Identifikator sa separatorom = NONCANONICAL / INVALID LEGACY; sačuvati raw; **ne** tiho pretvarati u kanonski identitet, čak i ako mehanička transformacija može proizvesti cifre koje prolaze validator.

Trim ≠ strukturna popravka.

D8 korespondencija historijskog prikaza `5-0764790` → kanonski `50764790` ostaje definicija **ciljnog oblika** pri novom unosu / korekciji. **Nije** tihi rewrite postojećeg reda.

### Status rezidentnosti

Odluke 3 i 10 ostaju. Rezidentnost važi **samo** za FL, uključujući Preduzetnika.

Postojeći FL:

* validan `resident` / `non-resident` → REUSE / migracija;
* missing → D10 declare-on-use; **nema** inferencije.

Postojeći PL / DSPD legacy `residential_status`: **ne** projektuje se u kanonski identitet. Sirova vrijednost smije ostati tokom kompatibilnosti.

**Ne** inferiše se rezidentnost iz: adrese; JMB; Grada; države; telefona; uloge; Kotora.

Fizičko brisanje zastarjele PL / DSPD vrijednosti **nije** obavezno za ovu odluku i smije uslijediti samo kada kompatibilnost / rollout to dozvoli (odluka 15, Poglavlje 6.30 / kasniji cleanup).

### Država i pasoš

Odluka 6 ostaje. Trenutni `users` identitet **nema** autoritativni country identity izvor.

**Nema** country backfill-a iz: telefona; IP; adrese; Grada; broja pasoša; JMB; uloge; KN snapshot-a; e-maila.

FL Nerezident bez Države prebivališta: **D10**.

Država izdavanja pasoša ovlašćenog lica / zastupnika, ako izvor ne postoji: **ne pogađati**.

Upotrebljiv FL broj pasoša smije se REUSE-ovati gdje je semantika jednoznačna. **Ne** izmišlja se Država izdavanja pasoša za FL Nerezidenta. Država prebivališta **nije** Država izdavanja pasoša. AP / DSPD representative podaci se **ne** izvode iz FL pasoša. Ako legacy storage ne razlikuje ciljnu semantiku: **NO GUESS**.

### Ovlašćeno lice i zastupnik DSPD

**Ne** konstruisu se automatski Ovlašćeno lice ni Zastupnik iz generičkog legacy `first_name` / `last_name` / `jmb` / `passport_number`, osim ako već usvojeni deterministički izvor dokazuje tu ulogu / semantiku.

Trenutni legacy account-person podaci **ne** postaju automatski AP / representative identitet. Nedostajući obavezni AP / representative podaci slijede D10 kada su potrebni.

### DSPD

Legacy DSPD klasifikacija smije se deterministički projektovati na Vrstu subjekta DSPD.

Iz jednog legacy `company_name` **ne** dijele se niti pogađaju: naziv stranog društva vs naziv dijela / ogranka u Crnoj Gori, osim ako postoji deterministički kanonski izvor.

Zastupnik se **ne** fabricira. Nedostajući CRPS: D10 kada je potreban. Grad ostaje odluka 13: adresa dijela / ogranka u Crnoj Gori; bez lažne geografske validacije.

### Telefon

Nova E.164 migraciona norma se **ne** uvodi. Upotrebljiv postojeći telefon smije se REUSE-ovati prema postojećim ciljnim pravilima. Malformed / nekanonski legacy telefon: D14 korekcija. Legacy lokalni telefon se **ne** reinterpretira tiho kao nova poslovna činjenica. Država / rezidentnost se **ne** izvode iz telefona.

### Adresa / Grad

Odluka 13 ostaje.

* Validna postojeća adresa / Grad → REUSE;
* Validan ne-Kotor Grad → REUSE;
* Istorijski Grad = Kotor → **ne** prepisuje se samo zato što je stari UI mogao forsirati Kotor;
* Missing Grad → D10 gdje je potreban;
* Malformed / nekanonski Grad → D14 korekcija.

Puna adresa u jednom polju: **nema** masovnog split-a Kotor-oriented parserom, osim ako je split dokazano deterministički i ciljno-siguran. Trenutni Kotor locality helper **nije** opšti identity migration parser.

**NO** city guess. **NO** Kotor rewrite. **NO** municipality inference. **NO** geocoding.

### E-mail verifikacija

Odluka 12 ostaje.

`email_verified_at`: TIMESTAMP → sačuvati **tačno** stanje; NULL → ostaje NULL.

Migracija **ne smije**: bulk verify; inferisati verifikaciju iz logina, uloge, aktivacije, KN/EP istorije; osvježavati verified timestamp.

### Uloge / aktivacija / auth

Migracija **ne mijenja**: `role_id`; uloge; permissions; `activation_status`; password / password hash; remember / session identity; e-mail; `users.id`.

Account sloj ostaje odvojen od migracije subject identity.

### Jedinstvenost identifikatora

**NEW IDENTIFIER UNIQUENESS: NO.**

D14 **ne** uvodi novu ciljnu uniqueness za JMB, PIB, pasoš ni CRPS.

Postojeća AS-IS DB uniqueness na tim kolonama smije se evidentirati kao tehnički legacy constraint / rizik. **Ne** reinterpretira se kao novo D1 / D14 poslovno pravilo. Duplikati pripadaju dry-run / data-quality izvještaju. Nalozi se **ne** spajaju tiho.

### Istorijski snapshoti

Istorijski / modulski snapshoti se **ne** prepisuju samo zato što se kanonski identitet mijenja.

Najmanje ostaju netaknuti: KN prijave; KN `applicant_type`; KN CRPS / PIB / JMB snapshot polja; EP transaction snapshoti; KK istorijske / organizer veze; newsletter istorijski payloadi gdje je primjenjivo; audit / history zapisi.

Istorijski snapshot **nije** trenutni kanonski identity SSOT.

### Kanonski SSOT

**CANONICAL IDENTITY MODEL IS THE ONLY SSOT: YES.**

Legacy `users.*` identitetske kolone su compatibility storage / sirovi legacy tokom prelaza. **Nisu** nezavisna ciljna istina kada kanonski identitet postoji.

### `users.user_type` compatibility mirror

`users.user_type` smije privremeno biti **DERIVED COMPATIBILITY MIRROR** samo gdje kanonska klasifikacija ima determinističku legacy reprezentaciju.

To je: **CANONICAL WRITE + DERIVED COMPATIBILITY PROJECTION**.

To **nije**: dva nezavisna SSOT-a.

Pravila:

1. kanonski identitet je autoritativan;
2. mirror se izvodi **samo iz** kanona;
3. mirror **nije** nezavisno autoritativan;
4. target-aware čitaoci preferiraju kanon;
5. konflikt → kanon pobjeđuje;
6. mirror **ne** rekonstruise missing kanonske činjenice;
7. mirror postoji samo gdje je representable;
8. životni vijek / uklanjanje mirror-a = odluka 15 (Poglavlje 6.30).

Odluka 2 ostaje CLOSED. Mirror **nije** reaktivacija `users.user_type` kao kanonskog upisa / SSOT.

### Representable mirror vrijednosti

Gdje je primjenjivo, izvedena projekcija koristi već usvojene D3 storage stringove:

| Kanonski cilj | Mirror `users.user_type` |
|---------------|--------------------------|
| FL / Preduzetnik = Ne | `Fizičko lice` |
| FL / Preduzetnik = Da | `Preduzetnik` |
| PL / OD | `Ortačko društvo` |
| PL / KD | `Komanditno društvo` |
| PL / AD | `Akcionarsko društvo` |
| PL / DOO | `Društvo sa ograničenom odgovornošću` |
| PL / Nevladino udruženje | `Nevladino udruženje` |
| PL / Sportska organizacija | `Sportska organizacija` |
| DSPD | `Dio stranog društva (predstavništvo ili poslovna jedinica)` |

DSPD mirror je **izvedena kompatibilnost** za legacy čitaoce, **nije** drugi SSOT i **nije** reotvaranje odluke 2.

### Foundation — no fake mirror

Kanonsko **PL / Nevladina fondacija** **nema** validnu legacy `users.user_type` reprezentaciju.

**NO FAKE MIRROR.**

**Ne** upisuje se kao: Nevladino udruženje; Sportska organizacija; legacy NVO bundle; Ostalo; NULL-as-semantic-foundation; ni druga izmišljena vrijednost.

Kanonski Foundation nalog **smije** postojati. Legacy-only čitalac koji ne može predstaviti Foundation mora biti migriran **prije** enablement-a Foundation-capable toka koji od tog čitaoca zavisi.

D14 invarijant: **NO FAKE MIRROR**. Redoslijed čitalaca / enablement usvojen je odlukom 15 (Poglavlje 6.30).

Nakon D10 razrješenja starog NVO bundle-a:

* Nevladino udruženje → kanon PL/NVO; deterministički mirror smije biti udruženje;
* Sportska organizacija → kanon PL/sport; deterministički mirror smije biti sport;
* Nevladina fondacija → kanon PL/Foundation; **nema** validnog mirror-a.

Stari ambiguous bundle **nije** autoritativan, **nije** target fact, **nije** Foundation mirror.

### Opšti dual-write

**GENERAL IDENTITY DUAL-WRITE: NO.**

**Ne** održavaju se kanonske i legacy kopije svih JMB / PIB / pasoš / adresa / Grad / residential_status / company podataka kao dva writable SSOT-a.

Novi ciljni identitetski upisi idu u kanonski model. Dozvoljena je samo eksplicitno usvojena izvedena compatibility projekcija representable `users.user_type` mirror-a. Trajanje prelaza usvojeno je odlukom 15 (Poglavlje 6.30).

### Read precedence

* Ako kanonski identitet **postoji**: kanon pobjeđuje; target-aware čitalac koristi kanon.
* Ako kanonski identitet **još ne postoji**: eksplicitno odobreni D2 compatibility čitalac smije koristiti legacy tokom prelaza.
* Ako je kanonsko polje prazno **jer je legacy INVALID**: **nema** fallback-a nevalidnog raw-a kao validnog.
* Ako je izvor genuine MISSING: D10.
* Ako je kanonska poslovna činjenica UNRESOLVED: ostaje unresolved; **ne** inferisati iz mirror-a / raw legacyja.
* Konflikt: validan kanon pobjeđuje; conflict se prijavi / pregleda; **nema** tihog overwrite-a iz legacyja.

### Očuvanje sirovog legacyja

Tokom kompatibilnosti sirove `users.*` identitetske kolone **ostaju**. Podržavaju: kompatibilnost; migracioni audit; invalid-vs-missing distinkciju; rollback izvor gdje je primjenjivo.

Nova archive tabela **nije** obavezna za day-1. Sirova legacy vrijednost **nije** kanonska istina samo zato što je sačuvana.

### DROP legacy polja

**LEGACY FIELD DROP NOW: NO.**

D14 closeout **ne** drop-uje: `users.user_type`; legacy JMB / PIB / pasoš; address / city; ostale legacy identitetske kolone.

Fizički DROP dozvoljen je tek nakon: migracije čitalaca; migracije pisaca; izlaska iz kompatibilnosti; data safety; rollback razmatranja. Eligibility i trenutak usvojeni su odlukom 15 (Poglavlje 6.30). Fizičko izvršenje DROP-a ostaje kasniji, odvojeno kontrolisan cleanup. D15 **ne** autorizuje fizički DROP.

### Obavezni dry-run

**MANDATORY PRE-MIGRATION DRY-RUN: YES.**

Prije bilo kojeg produkcionog migration / backfill **upisa** izvršava se READ-ONLY census / dry-run.

Izvještava kategorije kao: deterministička mapiranja; missing D10; invalid D14; ambiguous NVO bundle; nepodržani tipovi; staff / no identity; konflikti; anomalije identifikatora; anomalije rezidentnosti; anomalije adrese / Grada; manual review.

Usvajanje D14 **ne** znači da je takav census već izvršen.

**DATA CENSUS EXECUTED: NO.**

To **ne** blokira usvajanje strategije. **Blokira** slijepo izvršenje migracije.

Discovery odluke 14 **nije** pristupao nijednoj bazi. **DATABASE USED: NONE.** **PRODUCTION DATABASE ACCESSED: NO.** **WRITE OPERATIONS: NONE.** Prethodni dokumentarni D3 brojevi **nisu** svježi D14 database census.

### Privatnost izvještaja

Izvještaji minimizuju izlaganje osjetljivih podataka. Preferirati: `users.id`; kategoriju; brojeve; maskirane vrijednosti gdje treba.

Rutinski se **ne** ispisuju puni: JMB; pasoš; PIB; CRPS; e-mail; adresa.

**Nikad** se ne ispisuju: lozinka; password hash; session secret; token; credentials.

Restricted manual-review izlaz smije sadržati samo minimum stvarno potreban.

### Idempotency

**MIGRATION / BACKFILL MUST BE IDEMPOTENT: YES.**

Ponovno izvršenje **ne smije**: kreirati dupli identitet; drugi subjekt; mijenjati `users.id`; overwrite-ovati validan kanon starijim legacyjem; osvježavati `email_verified_at`; mijenjati stanje naloga; prepisivati istoriju; duplicirati AP / zastupnika.

Identitet ostaje sidro na istom `users.id`.

### Konflikt

Za rerun / djelimični prelaz: **validan kanon pobjeđuje**.

Legacy smije popuniti kanon samo gdje: kanonski cilj **nedostaje**; mapiranje je determinističko; legacy vrijednost je validna / kanonska za cilj; **nema** konflikta.

Različita kanonska vs legacy vrijednost: **nema** tihog overwrite-a. Prijava / pregled. Legacy **nije** noviji samo zato što je još prisutan.

### Politika grešaka

* VALIDATION FAILURE → ne kopirati nevalidno polje kao kanon; prijaviti; nastaviti sigurnu obradu gdje je moguće.
* AMBIGUOUS MAPPING → ne pogađati; prijaviti.
* UNSUPPORTED TYPE → nema lažnog kanonskog subjekta; prijaviti.
* CONFLICT → ne overwrite-ovati; prijaviti / pregledati.
* TECHNICAL WRITE FAILURE → transakcija / chunk **ne** ostavlja korumpirani djelimični identitet.

Jedan loš nalog **ne** smije tiho korumpirati druge. Tačna veličina chunk-a **nije** normativno poslovno pravilo.

### Fail-closed

Ako zahtijevana ciljna funkcija treba kanonski identitet / podatak koji je missing, invalid, ambiguous ili unsupported, ta funkcija smije ostati nedostupna dok se uslov ne razriješi.

**Ne** zaključavaju se globalno: prijava; nalog; nepovezana platformska funkcionalnost — osim ako drugo već usvojeno pravilo to nezavisno zahtijeva.

### AS-IS tehnički dug (nije cilj; nije implementacija)

Nisu cilj, ostaju tehnički dug do implementacije prema odluci 15 (Poglavlje 6.30):

1. identitetska polja preopterećena na `users`;
2. legacy `user_type` enum;
3. AS-IS uniqueness na JMB / PIB / pasošu;
4. nedostatak kanonskog CRPS storage-a na platformskom identitetu;
5. nedostatak country storage-a na `users`;
6. nedostatak AP / DSPD representative struktura;
7. Kotor-oriented address helper kao identity parser;
8. legacy pretpostavke normalizacije telefona;
9. istorijski rizik PIB truncacije;
10. legacy `residential_status` na PL / DSPD;
11. legacy čitaoci `users.user_type`.

AS-IS ponašanje se **ne** pretvara u ciljna pravila.

### Prihvatni scenariji odluke 14

1. Legacy FL, validan JMB, validan Grad, nedostaje Status rezidentnosti: FL klasifikacija + validni podaci migriraju; rezidentnost ostaje missing; D10 declare-on-use; nema globalnog bloka.
2. Legacy Preduzetnik, validan PIB, nema CRPS identity izvora: FL / Preduzetnik = Da; PIB migrira; CRPS ostaje missing; D10 kada je potreban.
3. Legacy DOO, validan PIB, `residential_status=resident`: PL/DOO; PIB migrira; rezidentnost se **ne** projektuje na PL.
4. Legacy NVO bundle: PL; Pravni oblik unresolved; no guess; D10 razrješenje kada je potrebno.
5. Legacy Ustanova: nalog sačuvan; nema podržanog kanonskog subjekta; nema približnog mapiranja.
6. Staff nalog / `user_type` NULL: nalog / uloga sačuvani; nema automatskog subject identity.
7. Legacy FL, nevalidan JMB: kanonski JMB se ne popunjava kao validan; raw sačuvan; D14 korekcija; **nije** D10 missing; fokusirana korekcija ako funkcija zahtijeva; nema globalnog login bloka.
8. Legacy FL, JMB NULL: D10 missing; razlikuje se od scenarija 7.
9. Validan Grad = Podgorica: **REUSE**.
10. Istorijski Grad = Kotor: **REUSE** as-is; nema automatskog prepisa.
11. Malformed Grad: D14 korekcija; nema lažnog Grada.
12. `email_verified_at` timestamp: tačno sačuvati.
13. `email_verified_at` NULL: ostaje NULL.
14. Novi kanonski DOO dok legacy čitalac postoji: kanon = SSOT; izvedeni representable `user_type` mirror smije biti DOO; mirror nije drugi SSOT.
15. Novi kanonski Foundation: kanon PL/Foundation; **nema** lažnog `user_type`; legacy-only čitalac mora biti migriran prije zavisnog enablement-a; redoslijed usvojen odlukom 15 (Poglavlje 6.30).
16. Kanonski DOO vs zastarjeli mirror AD: kanon pobjeđuje; conflict report; nema target odluke iz mirror-a.
17. Kanonski JMB prazan jer je raw legacy JMB nevalidan: **nema** fallback-a raw nevalidne vrijednosti.
18. Identifikator samo sa vanjskim razmacima: trim dozvoljen; zatim kanonska validacija cifara.
19. Identifikator sa unutrašnjom crticom: **nema** auto-strip; invalid / noncanonical legacy; raw sačuvan.
20. KN istorijski CRPS se razlikuje / trenutni identitet nema CRPS: KN snapshot netaknut; snapshot se **ne** diže u kanonski identitet.
21. Ponovni run migracije: nema duplog identiteta; nema overwrite-a validnog kanona starijim legacyjem.
22. Nepodržani nalog pristupa nepovezanoj funkciji: nepovezana funkcija ostaje dostupna ako je inače autorizovana.

### Granica odluke 14

Ova odluka **ne** određuje: produkcioni deploy; rollout redoslijed; feature switch; cutover; rollback proceduru; backup/restore operativu; trenutak uklanjanja compatibility mirror-a; trenutak fizičkog DROP-a legacy kolona; Foundation-capable rollout sequencing; tačna SQL imena; imena migracionih klasa; izvršenje dry-run-a ni backfill-a. Ta invarijantna prelaza usvojena su odlukom 15 (Poglavlje 6.30). Odluka 14 **ne** izvršava deploy, census, backfill ni DROP.

Odluka 14 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena.

---

## 6.30 Rollout / transition / cutover / rollback (CLOSED / PO USVOJENO — odluka 15)

Ovo poglavlje dokumentuje PO-usvojenu strategiju uvođenja usvojenog D1–D14 kanonskog identiteta u postojeću platformu, uz cutover, rollback i safety invarijante. Status: **CLOSED / PO USVOJENO**.

Odluka **ne** uvodi nova poslovna pravila. Razrađuje tehničku realizaciju već usvojenih `DK-BM-002` §11 i §16, `DK-FS-002` §16 i odluka 1–14. Ovaj TS **ne** mijenja `DK-BM-002` ni `DK-FS-002`. Odluke 1–14 **ostaju CLOSED / PO USVOJENO** i **nisu** reotvorene. Odluka 14 **nije** reotvorena. D14 semantika backfill-a ostaje.

**NEW BUSINESS RULE REQUIRED: NO.**

**ADOPTED ROLLOUT STRATEGY: OPTION 3 — SHADOW-FIRST HYBRID / REFINED.**

**D15 PO USVAJANJE NE AUTORIZUJE PRODUKCIONI DEPLOY.**

### Predmet

Odluka 15 definiše **invarijante prelaza**: kako se usvojeni D1–D14 kanonski identitet uvodi bez loma naloga, modulskih tokova, istorijskih snapshot-a, autentikacije, autorizacije i rollback sigurnosti.

Odluka određuje: redoslijed rollout / cutover; reader capability prije writer authority; logičku cutover granicu; zabrana leftover legacy identity pisaca; no-lost-update granicu; rollback model i granice; Foundation enablement; ulogu sirovog legacyja nakon cutover-a; životni vijek `users.user_type` mirror-a; eligibility DROP-a; census / dry-run kao execution gate; backup; observability; stabilization.

Odluka **ne** izvršava: produkcioni pristup bazi; data census; migraciju; backfill; deploy; fizički DROP.

### Šta odluka 15 nije

Odluka **nije**:

* nalog za produkcioni deploy;
* izvršenje dry-run / census / backfill / cutover;
* big-bang kompletnost svih naloga;
* opšti dual-write identitetskih atributa;
* fizički DROP legacy kolona;
* Bankart aktivacija;
* novo poslovno pravilo downtime-a, logout-a, completeness statusa ili Foundation eligibility.

Big-bang se **ne** usvaja. Lazy-only kao jedina strategija se **ne** usvaja.

### Usvojena strategija — OPTION 3 / REFINED

Usvojeno je:

**EXPAND**
\+ **CANONICAL-CAPABLE KOD SA SWITCH-EVIMA OFF**
\+ **OBAVEZAN READ-ONLY DRY-RUN / CENSUS**
\+ **D14 DETERMINISTIČKI BACKFILL**
\+ **POST-BACKFILL VERIFY**
\+ **SHADOW COMPARE**
\+ **FINALNA RECONCILIJACIJA**
\+ **JEDAN LOGIČKI CUTOVER (WRITER + REQUIRED READERS)**
\+ **STABILIZACIJA**
\+ **RETIRE COMPATIBILITY**
\+ **CONTRACT ELIGIBILITY**
\+ **Fizički DROP samo kao kasniji odvojeni cleanup**.

Ovo **nije** preporuka. **PO USVOJENO.**

Normativni redoslijed:

1. EXPAND ciljne šeme / modela.
2. Deploy canonical-capable reader / writer koda sa switch-evima **OFF**.
3. Obavezan produkciono-bezbjedan READ-ONLY dry-run / data census.
4. Deterministički D14 backfill.
5. Obavezna post-backfill verifikacija.
6. Shadow comparison.
7. Finalna reconcilijacija konkurentnih legacy izmjena.
8. **FINALNI LOGIČKI CUTOVER:** kanonski pisac ON; required canonical-first čitaoci ON; inkompatibilni legacy identity tokovi disabled; nijedan aktivan legacy identity pisac ne ostaje; izvedeni representable `users.user_type` mirror samo gdje je još potreban.
9. Stabilizacioni / observation period.
10. Uklanjanje preostalih kompatibilnih legacy čitalaca.
11. Mirror OFF kada nema potrošača.
12. Legacy fallback OFF kada eligibility gate-ovi prođu.
13. CONTRACT / cleanup eligibility.
14. Fizički legacy DROP samo u kasnijem, odvojeno kontrolisanom cleanup-u.

Expand-first: **DA**. Canonical identity model je jedini SSOT: **DA**.

### Delta 1 — Reader capability prije writer authority

Prije aktivacije kanonskog pisca, svaki čitalac **zahtevan pogođenim tokom** mora već biti canonical-capable.

Reader kod **smije** biti deployovan ranije dok je **OFF** ili **SHADOW**, ali mora biti spreman prije promjene writer authority.

Inkompatibilan legacy identity čitalac **ne** smije ostati autoritativan dok je kanonski pisac autoritativan.

### Delta 2 — Nema aktivnog legacy identity pisca nakon cutover-a

Nakon autoriteta kanonskog pisca:

**NO** aktivan legacy identity pisac ne smije ostati.

Jedini izuzetak: izvedeni representable `users.user_type` compatibility mirror, samo dok ga aktivan kompatibilan leftover consumer još zahtijeva.

Dozvoljeno ostaje:

* account-layer upis (`activation_status`, uloge / auth, e-mail, `email_verified_at`, password);
* istorijski / modulski snapshot upis prema usvojenoj modulskoj semantici (KN prijava; EP transaction snapshot), uz uslov da live izvor identiteta bude kanon gdje je potreban.

D10 residential declaration i admin putevi koji kreiraju / mijenjaju registrovani subject identity su **legacy identity pisci**. Moraju biti migrirani, ugašeni ili uključeni u isti logički cutover. **Ne** smiju ostati aktivni nakon cutover-a.

### Delta 3 — Isti logički cutover

Kanonski pisac i svi required canonical-first čitaoci aktiviraju se na **istoj LOGIČKOJ CUTOVER granici**.

To **ne** zahtijeva: isti commit; istu DB transakciju; tačan implementacioni mehanizam.

Zahtijeva da produkcija **ne** uđe u nesigurno stanje u kojem je kanonski pisac autoritativan, a required inkompatibilan legacy identity čitalac ostaje autoritativan.

Modulski authoritative reader **prije** globalnog writer cutover-a dozvoljen je samo za read-only tokove, nakon backfill-a, uz D14 read precedence (kanon ako postoji; nema fallback-a nevalidnog raw-a). Platformski register / profile pisac mijenja identitet globalno: svi tokovi koji troše taj identitet moraju biti capable ili ugašeni.

### Delta 4 — Rollback rizik počinje na aktivaciji pisca

Bezbjednost legacy-only rollback-a mijenja se kada kanonski pisac postane autoritativan.

**Prije** kanonskog pisca: legacy-only rollback **smije** ostati bezbjedan, uz uobičajenu deployment kompatibilnost.

**Nakon** što je kanonski pisac prihvatio kanonske identitetske izmjene: legacy-only rollback **nije** opšte bezbjedan.

Razlog: legacy `users.*` **nije** više garantovano da sadrži trenutne identitetske vrijednosti. Postojanje sirovih kolona **nije** dokaz rollback sigurnosti.

### Delta 5 — Foundation hard non-representability

Nevladina fondacija **nema** validan legacy `users.user_type` mirror.

Foundation stoga stvara **jaču NON-REPRESENTABILITY granicu**.

Nakon što kanonski Foundation podatak postoji: **NO** legacy-only downgrade. **NO** fake mapping.

Foundation-capable registracija / tok ostaje disabled dok:

* pogođeni čitaoci nisu canonical-aware;
* ne ostane required `user_type`-only zavisnost;
* rollback nije canonical-aware;
* relevantna modulska pravila mogu bezbjedno obraditi Foundation ili fail-closed.

EP fail-closed za Foundation, dok EP pravila nisu posebno usvojena, **ostaje validan**. D15 **ne** aktivira Bankart i **ne** zahtijeva EP Foundation eligibility.

Ova granica **nije** prva rollback-risk granica. Rollback unsafety za freshness počinje na aktivaciji kanonskog pisca (Delta 4).

### Delta 6 — Sirovi legacy nije current replica

Sirovi legacy `users.*` ostaje sačuvan tokom kompatibilnosti prema odluci 14.

Nakon cutover-a kanonskog pisca smije služiti:

* audit / history;
* dijagnostiku;
* pre-cutover poređenje.

**Nije:**

* kanonski SSOT;
* trenutna identity replica;
* autoritativni fallback;
* opšta rollback istina.

Za novo kreirani kanonski nalog: kanon postoji → **NO** legacy fallback. Invalid raw **nikad** nije autoritativni fallback.

### Delta 7 — `user_type` mirror failure boundary

Izvedeni `users.user_type` mirror postoji **samo** dok ga aktivan kompatibilan leftover consumer zahtijeva.

Dok je potreban: kanonski classification write **ne smije** tiho uspjeti ako required mirror projekcija padne. Koristi se atomic / fail-safe ponašanje. Tačan mehanizam **nije** normativno kanonizovan.

Kada nema potrošača: mirror **nije** više write obaveza i mora biti eligible za uklanjanje.

Foundation: **NO MIRROR.**

Persisted registry / tabela potrošača se **ne** uvodi. Rollout mora imati implementation inventory aktivnih leftover čitalaca.

### Delta 8 — Mirror ne pokriva ostala identitetska polja

`users.user_type` mirror rješava **samo** klasifikacionu kompatibilnost.

**Ne** čini bezbjednim legacy čitaoce:

JMB; PIB; pasoš; `residential_status`; adresa; Grad; telefon; company podaci; Ovlašćeno lice; Zastupnik DSPD; CRPS.

Takvi čitaoci moraju biti canonical-aware ili njihov tok mora biti disabled.

### Delta 9 — No-lost-update cutover granica

Finalna reconcilijacija je **obavezna**.

Između finalne reconcilijacije i autoriteta kanonskog pisca mora postojati:

**NO LOST-UPDATE WINDOW.**

Nijedan neuhvaćen legacy identity upis ne smije ostati.

Implementacija **smije** koristiti: kratki identity-write freeze; ograničeni maintenance window; transakcioni mehanizam; drugi tehnički bezbjedan mehanizam.

Globalni platformski downtime **nije** poslovno pravilo.

### Dry-run / census gate

**MANDATORY PRE-MIGRATION DRY-RUN: YES.**

Prije bilo kojeg produkcionog backfill **upisa**.

Usvajanje D15 **ne** znači da je census izvršen.

**DATA CENSUS EXECUTED: NO.**

Produkcioni rollout / backfill ostaje blokiran dok required dry-run / census i njegova verifikacija ne prođu. Discovery i closeout odluke 15 **nisu** pristupali nijednoj bazi. **DATABASE USED: NONE.** **PRODUCTION DATABASE ACCESSED: NO.** **WRITE OPERATIONS: NONE.**

### Backfill / verify / shadow

D14 semantika ostaje: deterministički; idempotentan; sidro `users.id`; bez nagađanja; bez invalid-as-valid copy; bez mutacije istorijskih snapshot-a; bez nove uniqueness; bez zamjene naloga.

D10 = missing. D14 = invalid / noncanonical korekcija. Valid = REUSE / MIGRATE.

Post-backfill verifikacija: **OBAVEZNA.** Shadow compare: **DA**, samo gdje je D14 mapiranje determinističko; mismatch **ne** auto-overwrite-uje validan kanon osim D14 determinističkog rerun-a. Shadow faza čuva legacy writer kao autoritet.

### Rollback model

Preferirani rollback **nakon** aktivacije kanonskog pisca:

1. feature disable / switch;
2. canonical-aware prethodni application build;
3. forward-fix.

Reverse data migracija: **NIJE DEFAULT.**

Legacy-only downgrade: **nije** opšte bezbjedan nakon autoriteta kanonskog pisca.

Database restore: **disaster recovery**, nije rutinski application rollback. **Ne** smije tiho baciti legitimne post-cutover kanonske izmjene.

### Rollback ere

Opisne granice, **nisu** kanonski dokumentacioni identifikatori:

* **Prije** autoriteta kanonskog pisca: kanon je additive / shadow; legacy-only code rollback smije biti bezbjedan uz uobičajenu deployment kompatibilnost.
* **Nakon** autoriteta kanonskog pisca, **prije** Foundation / neregistrovane kanonske činjenice: freshness incompatibility; legacy-only rollback **nije** opšte bezbjedan.
* **Nakon** kanonske Nevladine fondacije ili druge neregistrovane činjenice: representability incompatibility; legacy-only rollback **definitivno nije** bezbjedan.

### Backup / restore

Verifikovani DB backup je **obavezan** prije prvog produkcionog migration / backfill **upisa**.

D15 **ne** definiše vendor-specific backup komande. Restore spremnost mora biti potvrđena prije produkcione write faze.

### Observability

Produkcioni rollout zahtijeva observability najmanje za: neuspjeh kreiranja kanonskog identiteta; neuspjeh migracije; konflikte; fallback usage; legacy reader usage; mirror failures; D10 triggere; D14 invalid-data triggere; Foundation unsupported-reader pokušaje.

Rutinski logovi **ne** sadrže sirove osjetljive identitetske vrijednosti.

### Stabilizacija

Kompatibilnost se **ne** uklanja odmah nakon cutover-a.

Obavezan je definisan stabilization / observation period. Tačan broj dana **nije** usvojen ovom odlukom.

Uklanjanje kompatibilnosti zahtijeva dokaz da su preostale zavisnosti nestale.

### Legacy reader retirement

Legacy identity čitalac se uklanja kada: kanonsko čitanje je autoritativno; nijedna poslovna odluka ne zavisi od sirovog `users.*` identiteta; relevantni testovi prolaze; fallback / mirror zavisnost je uklonjena.

Uklanjanje leftover čitaoca koji koristi mirror **obavezno** je prije gašenja mirror-a.

Istorijski snapshot display (KN `applicant_type`, EP snapshot label) **nije** live identity čitalac.

### DROP / CONTRACT eligibility

D15 **NE** autorizuje fizički DROP. D15 definiše samo eligibility.

Fizičko uklanjanje legacy identitetskih kolona je kasniji, odvojeno kontrolisan tehnički cleanup.

DROP eligibility zahtijeva najmanje: autoritativne kanonske pisce; autoritativne kanonske čitaoce; verifikovani backfill; nema required legacy fallback-a; nema legacy identity pisca; nema legacy identity čitaoca; nula required mirror potrošača; završenu stabilizaciju; rollback više ne zavisi od sirovog legacyja; backup / restore spremnost; nema preostale produkcione code / schema zavisnosti.

DROP / contract je **granica ireverzibilnosti**.

### Modulne granice

**KN:** istorijski snapshoti neizmijenjeni. Live identity čitanja moraju postati canonical-aware prije pogođenog cutover-a.

**EP:** identitetski prelaz **nezavisan** je od Bankart-a. D15 **ne** aktivira i **ne** implementira Bankart.

**KK:** role / account autorizacija ostaje odvojena od registrovanog subjekta. **Ne** forsirati KK staff / admin u javni subject identity.

**Newsletter:** nema identity migration pravila osim ako postoji stvarna live identity zavisnost.

### Account invarijanti

Čuva se: `users.id`; e-mail; password hash; uloge; `activation_status`; `email_verified_at`; postojeće account veze; sesije osim ako tehnički dokaz zahtijeva drugačije.

**Ne** uvodi se: forsirani logout kao politika; masovna verifikacija; reset lozinke; drugi nalog; tiho kreiranje subjekta za staff / admin.

### Execution authorization

D15 PO usvajanje je **TECHNICAL DECISION CLOSEOUT**.

**Ne** autorizuje: produkcioni pristup bazi; izvršenje census-a; izvršenje migracije; izvršenje backfill-a; produkcioni rollout; deploy; legacy DROP.

To ostaju odvojene implementacione i produkcione aktivnosti sa sopstvenim kontrolisanim koracima.

### Granica odluke 15

Ova odluka **ne** određuje: tačna SQL imena; imena migracionih klasa; imena feature switch ključeva; tačan broj dana stabilizacije; vendor backup komande; izvršenje census-a, backfill-a, deploy-a ni DROP-a.

Odluka 15 **ostaje CLOSED / PO USVOJENO**. Odluke 1–14 **nisu** reotvorene.

---

# 7. Validacije

**Sekcijska sljedivost:** DK-BM-002 §5–§11, §16; DK-FS-002 §5–§17

Ovo poglavlje razrađuje **ciljni validacioni model** usvojenog `DK-BM-002` / `DK-FS-002`. Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27). Tehnička realizacija uklanjanja Kotor ograničenja Grada usvojena je odlukom 13 (Poglavlje 6.28). Migracija / backfill postojećih korisničkih podataka usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23). Fizički storage obrazac identiteta usvojen je odlukom 1 (Poglavlje 6.15). Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22).

## 7.1 Aktivni identitetski kontekst

Validiraju se podaci primjenjivi aktivnom identitetskom kontekstu (`DK-BM-002` §10 tač. 1–2; `DK-FS-002` §10).

* Svi aktivni obavezni podaci moraju zadovoljiti usvojena pravila prije uspješnog završetka odgovarajućeg toka.
* Uslovni podatak postaje obavezan samo kada je njegov uslov aktivan.
* Neaktivno uslovno polje samo zbog svoje nepopunjenosti **ne** blokira završetak toka.
* Neaktivni podatak **ne** određuje aktivni platformski identitet.

Ovo poglavlje **ne** određuje šta se fizički događa sa prethodno unesenom neaktivnom vrijednošću. Validacione poruke koje pripadaju isključivo neaktivnoj grani **ne** emituju se i **ne** blokiraju slanje (odluka 9, Poglavlje 6.24).

## 7.2 Vrsta subjekta

Vrsta subjekta je obavezan izbor. Dozvoljene su **tačno** tri vrijednosti (`DK-BM-002` §5; `DK-FS-002` §5):

1. Fizičko lice
2. Pravno lice
3. Dio stranog privrednog društva

Preduzetnik **nije** četvrta Vrsta subjekta (`DK-BM-002` §5; `DK-FS-002` §5). Vrijednost izvan usvojenog skupa nije validna.

## 7.3 Fizičko lice i Preduzetnik

U aktivnom toku Fizičkog lica pitanje **Da li ste registrovani kao preduzetnik?** ima vrijednosti Da / Ne. Izbor mora biti napravljen (`DK-BM-002` §6.1; `DK-FS-002` §6).

Kada je odgovor **Da**, Fizičko lice ostaje Fizičko lice, a poslovni naziv Preduzetnika, PIB i CRPS registracioni broj su primjenjivi obavezni podaci (`DK-BM-002` v1.0.3 §7; `DK-FS-002` v1.0.3 §7). PIB i CRPS su različiti podaci. Identifier validity CRPS-a usvojena je odlukom 8 (Poglavlje 6.23).

Kada je odgovor **Ne**, preduzetnički podaci nisu obavezni. CRPS **nije** primjenjiv.

Treća vrijednost se **ne** uvodi.

U aktivnom toku Fizičkog lica, uključujući Preduzetnika, obavezni su i ime i prezime.

## 7.4 Status rezidentnosti

Za Fizičko lice, uključujući Preduzetnika, Status rezidentnosti je obavezan izbor: Rezident / Nerezident (`DK-BM-002` §6.2; `DK-FS-002` §6.1). Default izbor se **ne** uvodi.

Za Pravno lice Status rezidentnosti **nije** primjenjiv. Za Dio stranog privrednog društva Status rezidentnosti **nije** primjenjiv.

Ovo poglavlje **ne** uvodi poseban status bivšeg Nerezidenta niti automatski Rezident za Pravno lice.

## 7.5 JMB

Kada je JMB aktivan, obavezan je, ima tačno 13 cifara i validira se kontrolna cifra (`DK-BM-002` §6.3; `DK-FS-002` §6.2).

Ista pravila primjenjuju se kada je JMB aktivan identifikacioni podatak ovlašćenog lica ili zastupnika (`DK-FS-002` §8.2, §9.2).

Kanonski JMB validator provjerava format i kontrolnu cifru. **Ne** uvodi dodatne semantičke provjere datuma rođenja, mjeseca, godine niti regiona. Arhitektura validatora usvojena je odlukom 7 (Poglavlje 6.22). Kanonske JMB poruke: Poglavlje 6.24.

## 7.6 Identifikacija Nerezidenta

Za Nerezidenta Fizičko lice, uključujući Preduzetnika, izbor identifikacionog podatka je JMB ili Broj pasoša. Izbor je obavezan. Izabrani podatak je obavezan. Država prebivališta je obavezna (`DK-BM-002` §6.3; `DK-FS-002` §6.2).

Država izdavanja pasoša **nije** obavezna u ovom kontekstu. Format Broja pasoša se **ne** uvodi. Kanonske poruke identifikacije i Države prebivališta: Poglavlje 6.24.

## 7.7 PIB

PIB se validira samo u aktivnom kontekstu u kojem je primjenjiv. Postojeći unosni format **nije** ciljni standard. Arhitektura validatora usvojena je odlukom 7 (Poglavlje 6.22).

**Preduzetnik.** PIB je obavezan. Ima tačno 8 cifara (`DK-BM-002` §7; `DK-FS-002` §7). Validira se kontrolna cifra prema ISO 7064 Modul 11,10 (odluka 7).

**Pravno lice.** PIB je obavezan. Ima tačno 8 cifara (`DK-BM-002` §8.3; `DK-FS-002` §8.2). Validira se kontrolna cifra prema ISO 7064 Modul 11,10 (odluka 7).

**Dio stranog privrednog društva.** PIB je obavezan. Ima tačno 8 cifara. Validira se kontrolna cifra prema ISO 7064 Modul 11,10 (`DK-FS-002` §9.1; odluka 7). PIB je odvojen podatak od CRPS registracionog broja.

Kanonski PIB checksum algoritam je **isti** za Preduzetnika, Pravno lice i DSPD: ISO 7064 Modul 11,10. Poslovni konteksti ostaju različiti. Kanonske PIB poruke: Poglavlje 6.24.

## 7.8 CRPS registracioni broj

CRPS registracioni broj je obavezan i različit od PIB-a u aktivnim V1 kontekstima (`DK-BM-002` v1.0.3; `DK-FS-002` v1.0.3):

* Preduzetnik;
* Ortačko društvo (OD);
* Komanditno društvo (KD);
* Akcionarsko društvo (AD);
* Društvo sa ograničenom odgovornošću (DOO);
* Dio stranog privrednog društva.

CRPS se u V1 **ne prikuplja** za Fizičko lice koje nije Preduzetnik, Nevladino udruženje, Nevladinu fondaciju i Sportsku organizaciju.

CRPS **nije** DSPD-only identifikator. CRPS **nije** obavezan samo zato što je Vrsta subjekta Pravno lice. Requiredness dolazi iz BM/FS. Identifier validity usvojena je odlukom 8 (Poglavlje 6.23).

Kanonska V1 vrijednost ima tačno 8 numeričkih cifara: identifikaciona oznaka (pozicija 1) + sedmocifreni redni broj (pozicije 2–8). Oznaka mora odgovarati aktivnom subjektu / Pravnom obliku: 1 Preduzetnik, 2 OD, 3 KD, 4 AD, 5 DOO, 6 DSPD. Redni broj: 0000001–9999999. Checksum se **ne** primjenjuje. Kanonski zapis je samo cifre.

Pure CRPS validator provjerava samo identifier validity. **Ne** odlučuje requiredness, uniqueness ni konačnu korisničku poruku. Server je autoritativan. Registracija i profil koriste iste kanonske semantike. Legacy / pre-2026 tretman usvojen je odlukom 14 (Poglavlje 6.29). Konačne validacione poruke usvojene su odlukom 9 (Poglavlje 6.24).

Usvojena odluka 1 smješta CRPS u subject-specific strukturu aktivnog subjekta (Poglavlje 6.15; D1 CRPS coverage alignment): Preduzetnik → FL; OD/KD/AD/DOO → PL; DSPD → DSPD. Centralni identity sloj, ovlašćeno lice i zastupnik **ne** nose CRPS. Odluka 1 **ostaje CLOSED / PO USVOJENO** i **nije** reotvorena.

## 7.9 Pravni oblik

Za Pravno lice Pravni oblik je obavezan izbor iz tačno zatvorenog kataloga (`DK-BM-002` §8.1; `DK-FS-002` §8.1):

1. Ortačko društvo (OD)
2. Komanditno društvo (KD)
3. Društvo sa ograničenom odgovornošću (DOO)
4. Akcionarsko društvo (AD)
5. Nevladino udruženje
6. Nevladina fondacija
7. Sportska organizacija

Vrijednost van kataloga nije validna. Pravni oblik **nije** primjenjiv na Fizičko lice niti na Dio stranog privrednog društva. U aktivnom toku Pravnog lica obavezan je i puni naziv pravnog lica (`DK-FS-002` §8.2). Storage kataloga **nije** određen ovim poglavljem.

CRPS je obavezan kada je Pravni oblik OD, KD, AD ili DOO. CRPS se u V1 **ne prikuplja** kada je Pravni oblik Nevladino udruženje, Nevladina fondacija ili Sportska organizacija (`DK-BM-002` v1.0.3 §8; `DK-FS-002` v1.0.3 §8). Identifier validity CRPS-a usvojena je odlukom 8 (Poglavlje 6.23).

## 7.10 Ovlašćeno lice

U aktivnom toku Pravnog lica obavezni su ime ovlašćenog lica, prezime ovlašćenog lica i identifikacioni kontekst ovlašćenog lica (`DK-BM-002` §8.2; `DK-FS-002` §8.2).

Identifikacioni izbor je JMB ili Broj pasoša. Izabrani identifikacioni podatak je obavezan.

Kada je izabran pasoš, Broj pasoša ovlašćenog lica i Država izdavanja pasoša su obavezni (`DK-FS-002` §8.2).

Identifikacioni podatak ovlašćenog lica **nije** automatski identifikacioni podatak korisnika.

## 7.11 Zastupnik

U aktivnom toku Dijela stranog privrednog društva obavezni su ime zastupnika, prezime zastupnika i identifikacioni kontekst zastupnika (`DK-BM-002` §9.2; `DK-FS-002` §9.2).

Identifikacioni izbor je JMB ili Broj pasoša. Izabrani identifikacioni podatak je obavezan.

Kada je izabran pasoš, Broj pasoša zastupnika i Država izdavanja pasoša su obavezni (`DK-FS-002` §9.2).

Identifikacioni podatak zastupnika **nije** automatski identifikacioni podatak korisnika.

U istom aktivnom toku obavezni su i naziv stranog privrednog društva i naziv dijela / ogranka u Crnoj Gori (`DK-FS-002` §9.1).

## 7.12 Kontrolisana lista država

Kada je država aktivan podatak, vrijednost mora biti iz jedne zajedničke kontrolisane liste država. Slobodan tekst nije validan izbor države (`DK-FS-002` §10).

Vrijednost je kanonski country code prema odluci 6 (Poglavlje 6.21). Naziv dolazi iz shared kataloga / resolvera. Kompletna lista država **nije** sadržaj ovog poglavlja. Kanonske korisničke poruke koriste naziv države, ne ISO kod (Poglavlje 6.24).

## 7.13 E-mail adresa

E-mail adresa je obavezna, mora imati validan format e-mail adrese, mora biti jedinstvena na Platformi i predstavlja korisničko ime za prijavu (`DK-BM-002` §10 tač. 3–4; `DK-FS-002` §11.1).

Korisnik unosi e-mail adresu ponovo radi potvrde. Potvrda mora odgovarati prvom unosu i **nije** trajni podatak (`DK-BM-002` §10 tač. 6–7; `DK-FS-002` §11.2).

Ako se vrijednosti ne podudaraju, korisnička poruka je tačno:

**E-mail adrese se ne podudaraju.**

Ovo poglavlje uvodi jedinstvenost samo za e-mail adresu, jer je to usvojeno u `DK-BM-002` / `DK-FS-002`. Jedinstvenost drugih identifikatora **nije** predmet ovog poglavlja. Ostale kanonske e-mail poruke: Poglavlje 6.24.

## 7.14 Korisnička lozinka

Korisnička lozinka je obavezna. Korisnik unosi lozinku ponovo radi potvrde. Potvrda mora odgovarati lozinci i **nije** trajni podatak (`DK-BM-002` §10 tač. 8–9; `DK-FS-002` §12). Kanonske poruke obaveznosti i nepodudaranja: Poglavlje 6.24.

Ciljna validacija lozinke ovog paketa obuhvata obaveznost i potvrdu / podudaranje. Pravila jačine lozinke **nijesu** dio ciljne normativne validacije DK registracije korisničkog identiteta u ovom paketu. Postojeće platformsko ponašanje ostaje AS-IS dok se posebno ne odluči. Odluka 9 **ne** usvaja pravilo jačine lozinke kao cilj i **ne** nalaže uklanjanje postojećeg AS-IS ponašanja.

Hash algoritam i fizički storage lozinke **nisu** predmet ovog poglavlja.

## 7.15 Broj mobilnog telefona

Broj mobilnog telefona je obavezan. Registracioni unos je kompozitan, sa izborom međunarodnog pozivnog broja (`DK-BM-002` §10 tač. 10; `DK-FS-002` §13).

Kada je izabran međunarodni pozivni broj za Crnu Goru (`+382`), korisnik u dio polja za broj telefona unosi broj bez `+382` i bez početne nule (`DK-FS-002` §13).

Ovo poglavlje **ne** uvodi poseban broj cifara, E.164 storage ni normalizacioni algoritam. Kanonske poruke telefona: Poglavlje 6.24.

## 7.16 Adresa i Grad

Ulica i broj su obavezni kada su primjenjivi. Vrijednost `bb` je dozvoljena kada objekat nema broj. Grad je obavezan. Grad **nije** ograničen na Opštinu Kotor (`DK-BM-002` §10 tač. 11–14; `DK-FS-002` §14).

Za Dio stranog privrednog društva adresa i Grad su adresa dijela / ogranka u Crnoj Gori (`DK-FS-002` §14). Semantika i granica tehničkog geografskog dokaza usvojene su odlukom 13 (Poglavlje 6.28).

Naselje **nije** dio validacionog modela registracije.

Kanonske poruke adrese i grada: Poglavlje 6.24. Nova poruka se odlukom 13 **ne** uvodi. Ostaje `Unesite grad.`

Tehnička realizacija uklanjanja postojećeg Kotor ograničenja usvojena je odlukom 13 (Poglavlje 6.28). Ovo poglavlje ga **ne** duplicira.

## 7.17 Validacione poruke i klijent / server

Korisničke validacione poruke prikazuju se na crnogorskom jeziku, koriste terminologiju `DK-FS-002` i za isto validaciono pravilo i isto polje koriste istu korisničku poruku bez obzira da li je validacija izvršena na klijentskoj ili serverskoj strani. Korisniku se ne prikazuju podrazumijevane framework poruke na engleskom jeziku (`DK-FS-002` §10).

Poruka mora jasno identifikovati podatak ili uslov koji nije ispunjen.

Kanonski katalog korisničkih validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Ovo poglavlje ga **ne** duplicira. Server je autoritativan. Ista poruka važi za isto polje, isto pravilo i isti uslov u registraciji, profilu / update-u i svakom drugom kanonskom identitetskom toku. Klijent smije reproducirati samo tu kanonsku poruku. Korisniku se **ne** prikazuju podrazumijevane framework poruke na engleskom jeziku.

## 7.18 Ponašanje kod nevalidnog unosa

Nevalidan aktivni obavezan podatak sprečava uspješan završetak odgovarajućeg toka (`DK-FS-002` §10, §15). Korisniku mora biti omogućeno da ispravi nevalidan podatak. Podatak **ne** postaje validan platformski identitet samo zato što je poslat sa klijentske strane.

## 7.19 Postojeći korisnik i dopuna profila

Postojeći korisnik se **ne** registruje ponovo. Nedostajući podatak dopunjava se na postojećem profilu. Nepotpunost profila sama po sebi **nije** globalna blokada naloga (`DK-BM-002` §11, §16; `DK-FS-002` §16).

Validacija dopune primjenjuje usvojena pravila podatka koji se stvarno dopunjava. Dopuna **nije** obavezno ponovno popunjavanje kompletne registracije. Newly active **nije** isto što i missing. Već razriješen kanonski podatak se REUSE-uje. Puna revalidacija nepovezanih polja se **ne** uvodi.

Mehanizam dopune usvojen je odlukom 10 (Poglavlje 6.25). Kanonske poruke: Poglavlje 6.24.

## 7.20 AS-IS vs ciljni model

Postojeća validaciona realizacija **nije** automatski ciljni validacioni model `DK-TS-002`.

---

# 8. Evidencija aktivnosti (Audit)

**Sekcijska sljedivost:** Poseban audit izvor u `DK-BM-002` / `DK-FS-002` za ovu cjelinu **ne postoji**. `DK-BM-002` §11, §16 i `DK-FS-002` §16 koriste se samo za razliku istorije postojećeg naloga od audit evidencije.

Ovo poglavlje definiše **ciljnu tehničku granicu** evidencije aktivnosti. Ne uvodi poseban audit model. Ne određuje storage, arhitekturu, sadržaj događaja ni retention.

## 8.1 Normativna granica

`DK-BM-002` i `DK-FS-002` **ne** usvajaju poseban audit model registracije i korisničkog identiteta za ovu funkcionalnu cjelinu.

Ovaj TS **ne** uvodi takav model samostalno. Ne uvodi audit obavezu bez BM/FS izvora. Ne uvodi poslovne operacije radi evidencije.

## 8.2 Istorija naloga i audit evidencija

Postojeći korisnik se **ne** registruje ponovo. Nedostajući podatak, gdje je predviđeno, dopunjava se na postojećem korisničkom profilu. Očuvanje postojećeg naloga i njegove istorije **nije** isto što i poseban audit trail (`DK-BM-002` §11, §16; `DK-FS-002` §16).

Iz očuvanja istorije naloga **ne** izvodi se nova obaveza audit evidencije.

Dopuna postojećeg profila **nije** nova registracija, **nije** novi korisnički identitet i **nije** nova audit semantika.

Ovo poglavlje **ne** uvodi audit evidenciju registracije, dopune profila niti izmjene identitetskih podataka. Time se **ne** pretpostavlja da su takve izmjene poslovno dozvoljene.

## 8.3 AS-IS granica

Postojeća runtime realizacija evidencije aktivnosti **nije** automatski ciljni audit model `DK-TS-002`.

Ovo poglavlje **ne** usvaja postojeće događaje, loggere, tabele ni audit mehanizme drugih modula kao ciljni model.

Lozinka i potvrda lozinke **nisu** audit sadržaj.

## 8.4 Otvorena tehnička pitanja

Konkretan audit model ove cjeline **nije** odlučen ovim poglavljem. Ovo poglavlje **ne** dodaje novu numerisanu tehničku odluku.

Ako bude kasnije normativno potreban ili posebno usvojen, zahtijevaće odluke o skupu događaja, actor/subject modelu, sadržaju događaja, tretmanu osjetljivih podataka, storage-u, retention-u, arhitekturi i odnosu prema postojećem audit sistemu. Ovo poglavlje **ne** donosi te odluke.

---

# 9. Integracije

**Sekcijska sljedivost:** DK-BM-002 §3, §12–§16; DK-FS-002 §11.3, §16, §18

Ovo poglavlje definiše **ciljne tehničke granice integracije** usvojenog `DK-BM-002` / `DK-FS-002`. Ne uvodi API, adapter, event ni servis. Ne određuje fizički način dijeljenja podataka.

## 9.1 Integraciona granica platformskog identiteta

Platformski korisnički identitet pripada `DK-*`. DK model je platformski SSOT (`DK-BM-002` §15; `DK-FS-002` §18.1).

KN, EP, KK i druge funkcionalne cjeline koriste platformski korisnički identitet. **Ne** definišu njegovu kanonsku strukturu, **ne** mijenjaju njegovu poslovnu semantiku i **ne** postaju vlasnici Vrste subjekta, Statusa rezidentnosti, identifikacionih podataka niti Pravnog oblika. KN `applicant_type`, EP availability i KK uloge **nijesu** dodatni platformski identiteti (`DK-BM-002` v1.0.2 §3).

Postojeći korisnik zadržava postojeći nalog. Dopuna ili usklađivanje identitetskog modela **ne** znači novi nalog, novu registraciju, gubitak postojećih veza sa modulima niti ponovno kreiranje korisnika (`DK-BM-002` §16; `DK-FS-002` §16).

Ovo poglavlje **ne** određuje fizički način očuvanja tih veza. `users.id` ostaje fizičko account / FK sidro (odluka 1). Migraciona strategija usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30).

## 9.2 Konkursi (KN)

KN `applicant_type` je konkursna klasifikacija. **Nije** platformska Vrsta subjekta (`DK-BM-002` §12; `DK-FS-002` §18.3).

KN pravilo prebivališta ili sjedišta na teritoriji Opštine Kotor za konkretni konkurs **nije** platformsko pravilo polja Grad pri registraciji. KN Kotor eligibility **nije** DK Grad (`DK-BM-002` §12; `DK-FS-002` §18.3). Ta granica usvojena je i odlukom 13 (Poglavlje 6.28).

Kompatibilnost sa KN `applicant_type` pri usklađivanju platformskog identiteta usvojena je odlukom 4 (Poglavlje 6.19). Ovaj TS **ne** mijenja KN dokumentaciju.

## 9.3 e-Plaćanje (EP)

EP pravila dostupnosti plaćanja mogu koristiti kanonski DK identitet kao ulaz u sopstvena modulska pravila. Ta pravila **ne** definišu platformski korisnički identitet (`DK-BM-002` §13; `DK-FS-002` §18.4).

EP availability **nije** Vrsta subjekta. EP availability **nije** platformska definicija Statusa rezidentnosti.

Kompatibilnost sa EP availability pri usklađivanju platformskog identiteta usvojena je odlukom 5 (Poglavlje 6.20). Ovaj TS **ne** mijenja EP dokumentaciju.

## 9.4 Kalendar kulture (KK)

KK koristi platformski korisnički nalog i identitet. KK poslovne i autorizacione uloge **nisu** Vrsta subjekta i **ne** mijenjaju platformski korisnički identitet (`DK-BM-002` §14, §15; `DK-FS-002` §18.2).

Ovaj TS **ne** prenosi KK poslovna pravila u registraciju i **ne** mijenja KK dokumentaciju. Posebna tehnička integracija DK↔KK **nije** usvojena ovim poglavljem.

## 9.5 E-mail verifikacija

E-mail verifikacija je funkcionalni korak platformskog registracionog toka (`DK-FS-002` §11.3). Dok e-mail adresa nije verifikovana, funkcije koje zahtijevaju verifikovan nalog ostaju nedostupne.

Tehnički mehanizam verifikacije usvojen je odlukom 12 (Poglavlje 6.27). Korisnički vidljivi sadržaj platformske e-mail poruke za verifikaciju usvojen je odlukom 11 (Poglavlje 6.26). Odluka 12 **ne** sastavlja katalog KN / EP / KK funkcija.

## 9.6 Ostale integracione granice

`DK-BM-002` / `DK-FS-002` **ne** usvajaju spoljnu integraciju za kontrolisanu listu država, JMB, PIB, CRPS registracioni broj niti Broj pasoša. Validaciona pravila iz Poglavlja 7 **nisu** dokaz takve integracije. Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21); to **nije** usvajanje spoljne integracije. Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22); to **nije** usvajanje spoljne integracije. Tehnička validacija CRPS-a usvojena je odlukom 8 (Poglavlje 6.23); to **nije** usvajanje spoljne integracije. Konceptualna lokacija CRPS-a usklađena je odlukom 1 (Poglavlje 6.15; D1 CRPS coverage alignment): FL Preduzetnik, PL OD/KD/AD/DOO, DSPD. Centralni identity sloj **ne** nosi CRPS. KN `applications.crps_number` ostaje KN snapshot, ne platformski identity SSOT.

Dopuna nedostajućeg podatka vrši se na postojećem profilu. Nepotpunost profila sama po sebi **nije** globalna blokada naloga (`DK-FS-002` §16). Mehanizam dopune usvojen je odlukom 10 (Poglavlje 6.25). Ovo poglavlje **ne** uvodi completion ugovor prema KN ili EP.

Registracija formira ili dopunjava platformski korisnički identitet prema usvojenim pravilima. Autentikacija potvrđuje pristup postojećem nalogu. E-mail verifikacija je funkcionalni korak potvrde e-mail adrese gdje ga FS zahtijeva. Autorizacija modula određuje šta prijavljeni korisnik smije u tom modulu. Ovo poglavlje **ne** uvodi framework arhitekturu iz te razlike.

## 9.7 AS-IS granica

Postojeća realizacija **nije** automatski ciljni integracioni model `DK-TS-002`.

---

# 10. Nefunkcionalni zahtjevi

**Sekcijska sljedivost:** DK-BM-002 §10, §16; DK-FS-002 §11, §12, §16, §19

Ovo poglavlje definiše **ciljne NFR granice** koje proizlaze iz usvojenog `DK-BM-002` / `DK-FS-002`. Ne uvodi dodatni NFR katalog. Ne određuje mehanizam, storage ni deploy.

## 10.1 Normativna granica

Nefunkcionalni zahtjevi ove cjeline izvode se samo iz usvojenog `DK-BM-002` / `DK-FS-002`. Gdje ti dokumenti ne definišu konkretan NFR parametar, ovaj TS ga **ne** izmišlja.

## 10.2 Sigurnost i integritet

E-mail adresa je obavezna, mora imati validan format i mora biti jedinstvena na Platformi (`DK-BM-002` §10 tač. 3–4; `DK-FS-002` §11.1). Potvrda e-mail adrese u registracionom unosu mora odgovarati prvom unosu i **nije** trajni podatak (`DK-FS-002` §11.2). E-mail verifikacija je usvojeni funkcionalni korak (`DK-FS-002` §11.3). Tehnički mehanizam verifikacije usvojen je odlukom 12 (Poglavlje 6.27): `users.email_verified_at`; signed / time-limited / current-e-mail-bound zahtjev; rok 60 minuta; autentikacija prije verifikacije; verification link **nije** mehanizam prijave. Korisnički vidljivi sadržaj platformske e-mail poruke za verifikaciju usvojen je odlukom 11 (Poglavlje 6.26). Verifikacija i ponovno slanje imaju serversku kratkoročnu zaštitu od zloupotrebe; tačna stopa **nije** normativna.

Korisnička lozinka i potvrda lozinke su obavezne. Potvrda mora odgovarati lozinci i **nije** trajni podatak (`DK-BM-002` §10 tač. 8–9; `DK-FS-002` §12). Polja lozinke i potvrde podrazumijevano sakrivaju vrijednost i omogućavaju prikaz i ponovno sakrivanje (`DK-FS-002` §12). Ovo poglavlje **ne** uvodi pravila jačine lozinke, hash algoritam niti čuvanje lozinke kao audit sadržaja.

Ovo poglavlje uvodi jedinstvenost samo za e-mail adresu. Jedinstvenost JMB-a, PIB-a, Broja pasoša i CRPS registracionog broja **nije** predmet ovog poglavlja. Odluka 7 **ne** usvaja novu uniqueness politiku za JMB ni PIB. Validnost identifikatora nije isto što i jedinstvenost u storage-u.

## 10.3 Provjerljivost i testna granica

Implementacija mora biti provjerljiva prema usvojenim funkcionalnim i validacionim pravilima, uključujući prihvatne kriterijume `DK-FS-002` §19.

Konkretna test strategija, test klase i testni okvir **nisu** usvojeni ovim poglavljem.

## 10.4 Deployment, migracija i rollback

Ovaj dokument **ne** predstavlja nalog za produkcioni deploy. Usvajanje ovog TS-a **ne** nalaže izvršenje migracije, izmjenu produkcionih podataka niti deploy aplikacionog koda.

Usklađivanje postojećih podataka **ne** smije proizvoljno promijeniti poslovni identitet korisnika niti prekinuti postojeće veze naloga (`DK-BM-002` §16). Postojeći korisnik se **ne** registruje ponovo. Nedostajući podatak dopunjava se na postojećem profilu. Nepotpunost profila sama po sebi **nije** globalna blokada naloga (`DK-FS-002` §16).

Migraciona strategija usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Usvajanje D15 **ne** nalaže izvršenje census-a, backfill-a, deploy-a ni DROP-a. Mehanizam dopune profila usvojen je odlukom 10 (Poglavlje 6.25). Fizički storage obrazac identiteta usvojen je odlukom 1 (Poglavlje 6.15). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27); odluka 12 **ne** propisuje izvršenje migracije.

## 10.5 Neusvojeni NFR parametri

`DK-BM-002` / `DK-FS-002` **ne** usvajaju SLA, latenciju, throughput, MFA, CAPTCHA, encryption algoritam, retention, monitoring niti backup parametre za ovu cjelinu. Ovo poglavlje ih **ne** uvodi. Kratkoročna zaštita od zloupotrebe za verifikaciju i ponovno slanje usvojena je odlukom 12 (Poglavlje 6.27) kao mehanizamska sigurnosna kontrola, **ne** kao opšti NFR katalog i **ne** kao normativna tačna stopa.

## 10.6 AS-IS granica

Postojeća realizacija sigurnosti, validacija, testova i deployment procesa **nije** automatski ciljni NFR model `DK-TS-002`.

---

# 11. Granice V1 (Out of Scope)

**Sekcijska sljedivost:** DK-BM-002 §1, §10, §12–§15; DK-FS-002 §6.3, §14, §17, §18

Ovo poglavlje navodi samo već usvojena V1 isključenja registracije i platformskog korisničkog identiteta. Ne sadrži otvorene tehničke odluke.

* KN `applicant_type`, KN `registration_form` i konkursna pravila prihvatljivosti, uključujući prebivalište ili sjedište na teritoriji Opštine Kotor za konkretni konkurs, **ne** postaju platformski identitetski model. KN `applicant_type` **nije** Vrsta subjekta. KN Kotor eligibility **nije** platformsko pravilo polja Grad (`DK-BM-002` §1, §12, §15; `DK-FS-002` §18.3).

* EP payment tok i EP pravila dostupnosti **ne** definišu platformski korisnički identitet. EP availability **nije** Vrsta subjekta i **nije** platformska definicija Statusa rezidentnosti (`DK-BM-002` §1, §13, §15; `DK-FS-002` §18.4).

* KK poslovne i autorizacione uloge **ne** postaju Vrsta subjekta niti dio kanonskog modela korisničkog identiteta (`DK-BM-002` §1, §14, §15; `DK-FS-002` §18.2).

* Naselje **nije** dio V1 modela registracionih podataka (`DK-BM-002` §10 tač. 15; `DK-FS-002` §6.3, §14, §17).

Otvorene tehničke odluke vode se u Poglavlju 12.

---

# 12. Otvorena pitanja

**Sekcijska sljedivost:** DK-BM-002 §17; DK-FS-002 §20

## OPEN BUSINESS QUESTIONS

**NONE**

## OPEN FS DECISIONS

**NONE**

## PO-USVOJENE TEHNIČKE ODLUKE

Ukupno tehničkih odluka: **15**. Zatvorena: **15**. Otvoreno: **0**.

1. Fizički storage model platformskog korisničkog identiteta. **PO USVOJENO.** Nalog je fizički odvojen od kanonskog identiteta. Za nalog registrovanog platformskog subjekta postoji tačno jedan fizički zapis platformskog identiteta. Interni ili staff nalog koji postoji isključivo radi platformske uloge **nije** obavezan da ima taj identity zapis samo zato što postoji `users` nalog. Centralni identity sloj je tanak (veza prema nalogu, kanonska Vrsta subjekta, broj mobilnog telefona) i **ne** nosi CRPS. Za jedan identitet postoji tačno jedna aktivna subject-specific struktura (Fizičko lice, Pravno lice ili Dio stranog privrednog društva). `Ulica i broj` i `Grad` pripadaju toj strukturi. Preduzetnik ostaje u strukturi Fizičkog lica; kada je Preduzetnik = Da, ista FL struktura nosi poslovni naziv, PIB i CRPS. Za OD/KD/AD/DOO, PL struktura nosi PIB i CRPS; NVO/sport bez CRPS polja u V1. DSPD struktura nosi PIB i CRPS. Ovlašćeno lice je zaseban 1:1 zapis uz Pravno lice, bez CRPS. Zastupnik je zaseban 1:1 zapis uz DSPD, bez CRPS. `users.id` ostaje fizičko account / FK sidro. D1 CRPS coverage alignment **nije** reotvaranje odluke 1. Razrada: Poglavlje 6.15. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15 i **ne** usvaja konačne nazive SQL tabela.

2. Prelaz sa postojećeg `users.user_type` na ciljni model Vrste subjekta. **PO USVOJENO.** Obrazac: NEW-MODEL-FIRST + LEGACY COMPATIBILITY. Novi identitetski model je jedini kanonski SSOT Vrste subjekta. `users.user_type` je privremena kompatibilnost za postojeće zavisne komponente, a ne drugi SSOT i ne univerzalno ogledalo. Ciljne vrijednosti bez vjerne reprezentacije (Nevladina fondacija) **ne** dobijaju lažni `users.user_type`. Postojeći profilski upis `users.user_type` ostaje AS-IS, ne cilj. KN usvojena odlukom 4. EP usvojena odlukom 5. Izvedeni representable compatibility mirror, uključujući DSPD legacy string kao projekciju a ne SSOT, usvojen je odlukom 14 (Poglavlje 6.29). Razrada: Poglavlje 6.17. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15, **ne** određuje trenutak uklanjanja kolone i **ne** usvaja SQL nazive, accessor ni adapter.

3. Mapiranje legacy vrijednosti `users.user_type` u ciljni kanonski identitet, bez proizvoljne promjene poslovnog identiteta. **PO USVOJENO.** Automatsko mapiranje samo gdje sačuvana vrijednost jednoznačno određuje semantiku. Ustanova i Druge organizacije = legacy izuzetak bez ciljnog mapiranja. NVO bundle: Vrsta subjekta = Pravno lice, Pravni oblik nije determinističan. Interni/administrativni nalog ne dobija identitet samo zbog `users` reda. Razrada: Poglavlje 6.18. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15. Dopuna postojećeg korisnika usvojena je odlukom 10.

4. Tehnička kompatibilnost KN `applicant_type` sa ciljnim platformskim identitetskim modelom. **PO USVOJENO.** KN `applicant_type` ostaje KN klasifikacija Podnosioca za konkretnu Prijavu i konkretni profil konkursa. **Nije** Vrsta subjekta, **nije** platformski identitetski SSOT, **nije** Pravni oblik. Novi tok: kanonski platformski identitet + pravila konkretnog profila konkursa. `users.user_type` **nije** primarni izvor. Za žensko preduzetništvo: FL + Preduzetnik = Ne → `fizicko_lice`; FL + Preduzetnik = Da → `preduzetnica`; PL + DOO → `doo`. Ostale ciljne kombinacije u tom profilu **nemaju** automatsko mapiranje. Generička rezervna vrijednost `ostalo` za novi tok **zabranjena**. Istorijski snapshot KEEP AS-IS. Razrada: Poglavlje 6.19. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15, **ne** određuje adapter klasu, SQL, backfill, deploy, dual-read/write, rollback niti uklanjanje `users.user_type`.

5. Tehnička kompatibilnost EP availability pravila sa ciljnim platformskim korisničkim identitetom. **PO USVOJENO.** EP availability ostaje modulsko pravilo dostupnosti. **Nije** Vrsta subjekta, **nije** platformski identitetski SSOT, **nije** EP-specifična identity klasifikacija. Ciljni obrazac: direktna evaluacija kanonskih identitetskih karakteristika (Vrsta subjekta; za Fizičko lice Preduzetnik i Rezidentnost; za Pravno lice Pravni oblik; za DSPD samo Vrsta subjekta). `users.user_type` **nije** ciljni SSOT. F11 poslovna matrica **neizmijenjena**; FL2/PRED2 i LEGAL6/BIZ6 semantika očuvana. Nevladina fondacija i DSPD u EP V1 fail-closed dok se za njih posebno ne usvoje EP pravila; približno mapiranje zabranjeno. Interni/admin nalog bez registrovanog identiteta fail-closed. Nepotpun identitet: bez default-a. Postojeći transaction snapshot KEEP AS-IS. Razrada: Poglavlje 6.20. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15, **ne** određuje adapter klasu, SQL, backfill, DROP `users.user_type`, production activation EP kataloga niti Bankart.

6. Tehnički model zajedničkog kontrolisanog kataloga država. **PO USVOJENO.** Jedan platformski shared canonical catalog, system-managed u V1, bez admin CRUD-a i bez slobodnog teksta. Stabilni identifikator zasniva se na ISO 3166-1 alpha-2. Identitetski podatak čuva canonical country code; display label dolazi iz kataloga / resolvera. Država prebivališta i Država izdavanja pasoša koriste isti katalog, a ostaju različiti podaci. Nerezidentno Fizičko lice: Država prebivališta obavezna. Rezidentno Fizičko lice: Država prebivališta se ne prikazuje i **ne** upisuje se automatski `ME`. Ovlašćeno lice Pravnog lica i Zastupnik DSPD, kada koriste pasoš, imaju obaveznu Državu izdavanja pasoša. Odluka **ne** uvodi Državu izdavanja pasoša Fizičkog lica niti Državljanstvo. Telefonski picker **nije** country SSOT. DB tabela `countries` **nije** obavezna. EP availability i KN **neizmijenjeni**. Dopuna postojećih korisnika usvojena odlukom 10; migracija / backfill usvojena odlukom 14 (Poglavlje 6.29); rollout / cutover / rollback usvojeni odlukom 15 (Poglavlje 6.30). Razrada: Poglavlje 6.21. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15 i **ne** usvaja finalna SQL imena, Laravel klasu, package niti kompletnu listu država.

7. Arhitektura tehničke implementacije već usvojenih pravila validnosti JMB-a i PIB-a. **PO USVOJENO.** Jedan kanonski JMB validator i jedan kanonski PIB validator. Obrazac: PURE IDENTIFIER VALIDATOR + LARAVEL VALIDATION RULE ADAPTER. Pure validator provjerava samo format i kontrolnu cifru; requiredness, uniqueness i UI poruka ostaju izvan njega. JMB: 13 cifara + kontrolna cifra, bez semantičke validacije datuma / godine / regiona. PIB: 8 cifara + ISO 7064 Modul 11,10 za Preduzetnika, Pravno lice i DSPD. Server je autoritativan; kanonski checksum **nije** JavaScript SSOT. Registracija i profil koriste isti identifier-validity validator. KN koristi isti algoritamski validator, a snapshot **nije** identity SSOT. EP-specifični validator se **ne** uvodi. Uniqueness ostaje AS-IS compatibility; poruke usvojene odlukom 9 (Poglavlje 6.24); migracija / census usvojena odlukom 14 (Poglavlje 6.29). Razrada: Poglavlje 6.22. Tehnička validacija CRPS-a usvojena je odlukom 8. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15 i **ne** propisuje finalna imena PHP klasa.

8. Tehnička validacija CRPS registracionog broja. **PO USVOJENO.** CRPS je obavezan za Preduzetnika, OD, KD, AD, DOO i DSPD. U V1 se **ne prikuplja** za Fizičko lice koje nije Preduzetnik, Nevladino udruženje, Nevladinu fondaciju i Sportsku organizaciju. Kanonska vrijednost: tačno 8 cifara; oznaka 1–6 prema aktivnom subjektu/Pravnom obliku; redni broj 0000001–9999999; bez checksum-a; digits-only. Obrazac: PURE IDENTIFIER VALIDATOR + LARAVEL RULE ADAPTER. Caller određuje očekivanu oznaku. Requiredness = BM/FS. Server je autoritativan. Registracija i profil koriste iste semantike. KN snapshot nije identity SSOT. Uniqueness se **ne** uvodi. Legacy / pre-2026 usvojeno odlukom 14 (Poglavlje 6.29). Poruke usvojene odlukom 9 (Poglavlje 6.24). Konceptualna lokacija CRPS-a usklađena je odlukom 1 (Poglavlje 6.15); validaciona semantika odluke 8 **nije** izmijenjena. Razrada: Poglavlje 6.23. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15 i **ne** uvodi šemu ni migraciju.

9. Kanonski katalog korisničkih validacionih poruka registracije i korisničkog identiteta. **PO USVOJENO / CLOSED.** Sve kanonske korisničke poruke su na crnogorskom jeziku. Server je autoritativan. Isto polje + isto pravilo + isti uslov koriste istu poruku u registraciji, profilu i drugim kanonskim identitetskim tokovima. Korisniku se **ne** prikazuju podrazumijevane framework poruke na engleskom jeziku. Tačno sačuvano: „E-mail adrese se ne podudaraju.“ Pravila jačine lozinke nijesu dio ciljne validacije ovog paketa; postojeće platformsko ponašanje ostaje AS-IS. Nova jedinstvenost JMB/PIB/CRPS/pasoša se **ne** uvodi. Dopuna postojećih korisnika usvojena odlukom 10. Korisnički sadržaj verification e-maila usvojen odlukom 11 i **nije** dio ovog kataloga. Legacy / migracija usvojena odlukom 14 (Poglavlje 6.29). Razrada: Poglavlje 6.24. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15 i **ne** propisuje finalna imena PHP klasa.

10. Dopuna kanonskog identiteta postojećeg korisnika. **PO USVOJENO / CLOSED.** Postojeći korisnik nije globalno prisiljen na ponovnu registraciju. Nepotpun profil nije globalna blokada. Model: HYBRID — deterministička D3 projekcija bez korisničke interakcije + REQUIRE-ON-USE / DECLARE-ON-USE za nedostajuće / unresolved podatke. Dopuna je field-/need-specific. Newly active ≠ missing. Već razriješen kanonski podatak se REUSE-uje. Nema pune revalidacije. NVO bundle: eksplicitno samo Nevladino udruženje / Nevladina fondacija / Sportska organizacija. Ustanova / Druge organizacije bez približnog V1 mapiranja. Interni/admin nalog bez subject completion gate za admin-only upotrebu. Nedostajući podatak = odluka 10; postojeći nevalidan legacy podatak = odluka 14. Istorijski snapshoti neizmijenjeni. Globalni `identity_complete` flag se ne uvodi. Poruke: odluka 9. Razrada: Poglavlje 6.25. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15 i **ne** propisuje rute, tabele ni migraciju.

11. Lokalizacija korisničkog sadržaja e-mail poruke za verifikaciju. **PO USVOJENO / CLOSED.** Korisnički vidljivi sadržaj platformske e-mail poruke za verifikaciju je na crnogorskom jeziku. Kanonski termin: E-mail adresa. Jedan kanonski tekst gdje god se šalje ista platformska verification e-mail poruka. Pozdrav: „Poštovani/a,“; **ne** zavisi od `first_name`. Bez podrazumijevanih engleskih Laravel / framework poruka. Ovaj tekst **nije** validaciona poruka odluke 9. Tehnički mehanizam (stanje, signed / time-limited link, rok 60 minuta, autentikacija prije verifikacije, ponovno slanje) usvojen je odlukom 12 (Poglavlje 6.27). Bez višejezičnog / per-user locale modela. Razrada: Poglavlje 6.26. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15.

12. Tehnički mehanizam e-mail verifikacije. **PO USVOJENO / CLOSED.** Kanonsko stanje: `users.email_verified_at` (NULL = neverifikovano; TIMESTAMP = verifikovano). Nakon uspješnog kreiranja naloga šalje se ista poruka odluke 11. Zahtjev je serverski validiran, kriptografski zaštićen, vremenski ograničen (60 minuta), vezan za nalog i trenutnu e-mail adresu. Verification link **nije** mehanizam prijave i **ne** uspostavlja sesiju. Verifikacija zahtijeva autentikovani nalog koji odgovara zahtjevu. Gost se prvo autentikuje. Drugi autentikovani nalog se odbija, bez prebacivanja sesije. Aktivan neverifikovani nalog smije se prijaviti. Odluka daje samo primitiv provjere; **ne** sastavlja katalog verifikovanih funkcija i **ne** kanonizuje AS-IS globalni `verified` middleware. Ponovno slanje je tehnički recovery iste poruke odluke 11 za autentikovanog neverifikovanog vlasnika. Serverski kratkoročni throttle je obavezan; tačna stopa **nije** normativna. Izmjena e-mail adrese briše verifikovano stanje i zahtijeva novu poruku odluke 11. Neuspjeh isporuke ne briše nalog. Postojeći nalozi se ne backfill-uju. Staff nije tjeran kroz javni registracioni verification tok. NEW BUSINESS RULE REQUIRED: NO. Razrada: Poglavlje 6.27. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15.

13. Tehnička realizacija uklanjanja postojećeg ograničenja Grada na Opštinu Kotor u platformskoj registraciji i relevantnom profilu. **PO USVOJENO / CLOSED.** Grad je obavezan gdje je adresna grana aktivna. Grad **nije** ograničen na Kotor ni na Opštinu Kotor. Vlasništvo Platforme **ne** implicira Kotor adresu. Unos: slobodan tekst. Kontrolisani katalog Gradova: **NO**. Rezident smije ne-Kotor Grad. Nerezident smije strani Grad. Preduzetnik: adresa Fizičkog lica. Pravno lice: adresa subjekta, nije Kotor-only. DSPD: semantika adrese dijela / ogranka u Crnoj Gori; slobodan tekst **ne** dokazuje geografiju; lažna geografska validacija se **ne** uvodi. Ovlašćeno lice i Zastupnik **nemaju** polje Grad. Registracija i profil: ista semantika. Server **ne** odbija Grad samo zato što nije Kotor. Kanonska poruka ostaje `Unesite grad.` Postojeći upotrebljiv Grad se REUSE-uje (odluka 10). KN Kotor eligibility ostaje odvojena. NEW BUSINESS RULE REQUIRED: NO. Razrada: Poglavlje 6.28. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30). Ova odluka sama **ne** izvršava D15 i **ne** propisuje rollback. Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29).

14. Migraciona strategija prelaska postojećih korisničkih podataka na usvojeni D1 ciljni identitetski model, uz isti korisnički nalog, očuvanje poslovnog identiteta i postojećih veza, bez nagađanja poslovnih činjenica. **PO USVOJENO / CLOSED.** Strategija: OPTION B — deterministički backfill + canonical-first reads + kontrolisana legacy kompatibilnost + D10 za genuine missing/unresolved + D14 korekcija za invalid legacy + fail-closed samo gdje funkcija zahtijeva kanonski podatak. `users.id` sidro neizmijenjeno. Invalid legacy ≠ D10 missing. Persisted `migration_status` se **ne** uvodi. Kanonski identitet je jedini SSOT. `users.user_type` smije biti izvedeni compatibility mirror samo gdje je representable; **nije** drugi SSOT. General dual-write identitetskih atributa: **NO**. Foundation: **NO FAKE MIRROR**. NVO bundle: PL + unresolved oblik. Ustanova / Druge organizacije: bez približnog mapiranja. Staff: bez automatskog subjekta. Validan JMB/PIB/Grad: REUSE. Identifier outer trim: DA; crtica / slash / checksum repair: NE. KN snapshot nije identity SSOT. `email_verified_at` tačno sačuvan. Uloge / aktivacija neizmijenjeni. DROP legacy kolona sada: **NO**. Obavezan dry-run prije write. Data census ovog closeout-a: **NO**. Idempotentno. NEW BUSINESS RULE REQUIRED: NO. Razrada: Poglavlje 6.29. Ova odluka sama **ne** izvršava D15 i **ne** izvršava deploy, rollback ni produkcioni backfill. Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30).

15. Rollout / transition / cutover / rollback za buduću implementaciju i migraciju usvojenog D1–D14 kanonskog identiteta. **PO USVOJENO / CLOSED.** Strategija: OPTION 3 — SHADOW-FIRST HYBRID / REFINED. Expand-first. Reader capability prije writer authority. Canonical writer i required canonical-first čitaoci na istoj logičkoj cutover granici. Nema aktivnog legacy identity pisca nakon cutover-a, osim izvedenog representable `users.user_type` mirror-a dok je potreban. General dual-write: **NO**. Sirovi legacy sačuvan, ali **nije** current replica nakon writer cutover-a. Legacy-only rollback **nije** opšte bezbjedan nakon autoriteta kanonskog pisca. Foundation: hard non-representability; **NO FAKE MIRROR**. Mirror rješava samo klasifikaciju. No-lost-update granica obavezna. Obavezan dry-run; data census ovog closeout-a: **NO**. Backfill semantika ostaje D14. Preferirani post-writer rollback: feature disable → canonical-aware previous build → forward-fix. Reverse data migracija nije default. Backup prije prvog produkcionog write-a. Observability bez sirovih PII. Stabilizacija obavezna; tačan broj dana nije usvojen. DROP eligibility only; fizički DROP kasniji cleanup. D15 usvajanje **ne** autorizuje produkcioni deploy, census, backfill ni DROP. NEW BUSINESS RULE REQUIRED: NO. Razrada: Poglavlje 6.30.

## OPEN TECHNICAL DECISIONS

**NONE**

Svih 15 tehničkih odluka je **CLOSED / PO USVOJENO**.

---

# 13. Matrica sljedivosti

**Sekcijska sljedivost:** BM/FS izvori navedeni po redovima prema `Sekcijska sljedivost` PO-usvojenih Poglavlja 1–12. Ova matrica ne uvodi nova pravila.

| TS | DK-FS-002 | DK-BM-002 | Sljedivost / granica |
|----|-----------|-----------|----------------------|
| §1 | §1, §2, §18, §20 | §1, §4, §11, §15, §17 | pregled cjeline i tehničke granice; tehničke odluke evidentirane u §12 |
| §2 | §16, §18 | §11, §15, §16 | ciljni arhitektonski principi; platformski sloj; KN/EP/KK granice; AS-IS nije cilj; AS-IS Kotor konflikt evidentiran; ciljna korekcija Grada PO usvojena odlukom 13 (§6.28) |
| §3 | §5–§9, §16, §18 | §3, §5–§9, §11, §15, §16 | ciljni konceptualni tehnički model; kardinalnost 1:1 za nalog registrovanog platformskog subjekta iz BM v1.0.2 §3 i FS v1.0.2 §5; staff/internal nalog nije automatski identitet; fizički obrazac u §6.15 (odluka 1) |
| §4 | §4, §5–§9, §11, §15, §16 | §3, §4, §5–§9, §11 | ciljni tokovi registracije i dopune; registracija i dopuna ne kreiraju paralelni identitet (FS v1.0.2 §5, §16); mehanizam dopune PO usvojen odlukom 10 (§6.25); korisnički sadržaj verification e-maila PO usvojen odlukom 11 (§6.26); tehnički mehanizam verifikacije PO usvojen odlukom 12 (§6.27) |
| §5 | §4, §11.3, §16, §18 | §2, §11, §15 | ciljna autorizacija; nalog, verifikacija e-maila i nepotpun profil su različite činjenice; dopuna ne kreira drugi identitet (FS v1.0.2 §16); mehanizam dopune PO usvojen odlukom 10 (§6.25); tehnički mehanizam verifikacije PO usvojen odlukom 12 (§6.27); bez kataloga verifikovanih funkcija |
| §6 | §5–§14, §16, §18, §20 | §3, §5–§11, §15, §16 | ciljni konceptualni model podataka; kardinalnost 1:1 iz BM v1.0.2 §3 i FS v1.0.2 §5, §16; fizički storage obrazac PO usvojen odlukom 1 (§6.15); D1 CRPS coverage alignment: Preduzetnik CRPS u FL, OD/KD/AD/DOO CRPS u PL, DSPD CRPS u DSPD, centralni sloj bez CRPS; D1 nije reotvorena; obrazac kompatibilnosti `users.user_type` PO usvojen odlukom 2 (§6.17); legacy mapiranje PO usvojeno odlukom 3 (§6.18); KN `applicant_type` kompatibilnost PO usvojena odlukom 4 (§6.19); EP availability kompatibilnost PO usvojena odlukom 5 (§6.20); kanonski katalog država PO usvojen odlukom 6 (§6.21); arhitektura JMB/PIB validatora PO usvojena odlukom 7 (§6.22); validacija CRPS-a PO usvojena odlukom 8 (§6.23); kanonski katalog validacionih poruka PO usvojen odlukom 9 (§6.24); dopuna postojećeg korisnika PO usvojena odlukom 10 (§6.25); korisnički sadržaj verification e-maila PO usvojen odlukom 11 (§6.26); tehnički mehanizam e-mail verifikacije PO usvojen odlukom 12 (§6.27); uklanjanje Kotor ograničenja Grada PO usvojeno odlukom 13 (§6.28); migracija / backfill PO usvojena odlukom 14 (§6.29); rollout / cutover / rollback PO usvojeni odlukom 15 (§6.30) |
| §7 | §5–§17 | §5–§11, §16 | ciljna pravila validnosti; usvojena FS pravila nisu OPEN; kanonski katalog država PO usvojen odlukom 6 (§6.21); arhitektura JMB/PIB validatora PO usvojena odlukom 7 (§6.22); CRPS validacija PO usvojena odlukom 8 (§6.23); kanonski katalog poruka PO usvojen odlukom 9 (§6.24); dopuna postojećeg korisnika PO usvojena odlukom 10 (§6.25); konceptualna lokacija CRPS-a = subject-specific (odluka 1, §6.15); D1 nije reotvorena; uklanjanje Kotor ograničenja Grada PO usvojeno odlukom 13 (§6.28); migracija / backfill PO usvojena odlukom 14 (§6.29); rollout / cutover / rollback PO usvojeni odlukom 15 (§6.30) |
| §8 | — | — | nema usvojenog audit zahtjeva ni modela; TS ga ne uvodi |
| §9 | §11.3, §16, §18 | §3, §12–§16 | DK platformski identitet je SSOT; KN/EP/KK nijesu dodatni platformski identiteti (BM v1.0.2 §3); KN kompatibilnost PO usvojena odlukom 4 (§6.19); EP kompatibilnost PO usvojena odlukom 5 (§6.20); korisnički sadržaj verification e-maila PO usvojen odlukom 11 (§6.26); tehnički mehanizam verifikacije PO usvojen odlukom 12 (§6.27); KN Kotor eligibility nije DK Grad (odluka 13, §6.28) |
| §10 | §11, §12, §16, §19 | §10, §16 | ciljne NFR granice; bez izmišljenih NFR; fizički storage obrazac PO usvojen odlukom 1; mehanizam dopune PO usvojen odlukom 10 (§6.25); korisnički sadržaj verification e-maila PO usvojen odlukom 11 (§6.26); tehnički mehanizam verifikacije PO usvojen odlukom 12 (§6.27); migracija (14) PO USVOJENA (§6.29); rollout / cutover / rollback (15) PO USVOJENI (§6.30) |
| §11 | §6.3, §14, §17, §18 | §1, §10, §12–§15 | usvojena V1 isključenja: KN, EP, KK; Naselje nije dio V1 registracionih podataka; OPEN ≠ Out of Scope |
| §12 | §20 | §17 | OPEN BUSINESS QUESTIONS = NONE; OPEN FS DECISIONS = NONE; OPEN TECHNICAL DECISIONS = NONE; odluke 1–15 PO USVOJENE / CLOSED; lokalizacija verification e-maila (11) CLOSED; tehnički mehanizam e-mail verifikacije (12) CLOSED; uklanjanje Kotor ograničenja Grada (13) CLOSED; migracija (14) CLOSED; rollout / cutover / rollback (15) CLOSED |
| §13 | — | — | ova matrica; derivat usvojenih BM/FS i Poglavlja 1–12 |
| §14 | — | — | nenormativne implementacione napomene; ne uvodi nova pravila niti zatvara §12 |

Prihvatni kriterijumi za implementaciju izvode se iz usvojenih pravila `DK-FS-002`, uključujući §19 gdje je primjenjivo. Ova matrica ne uvodi nove prihvatne kriterijume. Tehničke odluke 1–15 iz §12 su CLOSED / PO USVOJENO.

---

# 14. Napomene za implementaciju

**Sekcijska sljedivost:** Nema posebnog BM/FS izvora pravila; ovo poglavlje je strogo nenormativno prema M-TS-005.

Ovo poglavlje je **strogo nenormativno**. Ne uvodi poslovna, funkcionalna ni tehnička pravila. Ne mijenja normativni sadržaj Poglavlja 1–13.

Implementacija mora slijediti usvojena normativna Poglavlja 1–13. Postojeća runtime realizacija **nije** automatski ciljni model.

Tehničke odluke 1–15 iz §12 su **CLOSED / PO USVOJENO**. Usvajanje odluke 15 **ne** autorizuje produkcioni deploy, census, backfill ni DROP. Fizički storage obrazac identiteta usvojen je odlukom 1 (Poglavlje 6.15). Obrazac kompatibilnosti `users.user_type` usvojen je odlukom 2 (Poglavlje 6.17). Semantičko mapiranje legacy `users.user_type` usvojeno je odlukom 3 (Poglavlje 6.18). Kompatibilnost KN `applicant_type` usvojena je odlukom 4 (Poglavlje 6.19). Kompatibilnost EP availability usvojena je odlukom 5 (Poglavlje 6.20). Kanonski katalog država usvojen je odlukom 6 (Poglavlje 6.21). Arhitektura JMB/PIB validatora usvojena je odlukom 7 (Poglavlje 6.22). Tehnička validacija CRPS registracionog broja usvojena je odlukom 8 (Poglavlje 6.23). Kanonski katalog validacionih poruka usvojen je odlukom 9 (Poglavlje 6.24). Dopuna postojećeg korisnika usvojena je odlukom 10 (Poglavlje 6.25). Lokalizacija korisničkog sadržaja verification e-maila usvojena je odlukom 11 (Poglavlje 6.26). Tehnički mehanizam e-mail verifikacije usvojen je odlukom 12 (Poglavlje 6.27). Tehnička realizacija uklanjanja Kotor ograničenja Grada usvojena je odlukom 13 (Poglavlje 6.28). Migracija / backfill usvojena je odlukom 14 (Poglavlje 6.29). Rollout / cutover / rollback usvojeni su odlukom 15 (Poglavlje 6.30).

Implementacioni redoslijed izvršenja (census, backfill, deploy) **nije** nalog ovog poglavlja. D15 **odluka** closeout u v1.0.0 je dokumentacioni i **nije** izvršenje. Finalno PO usvajanje DK-TS-002 v1.0.0 **nije** izvršenje implementacije. Produkciono izvršenje Step 8 je evidentirano u §14.1. Lokalizacija kanonskog kataloga država je evidentirana u §14.2. Post-cutover dopuna postojećih poslovnih subjekata je evidentirana u §14.3. Produkcioni deploy te dopune i Level A smoke su evidentirani u istom §14.3. Lokalna implementacija registration correctiva je evidentirana u §14.4; PO status te implementacije = **PO USVOJENO**. Evidencija ostaje IMPLEMENTATION / CORRECTIVE. **Nije** production accepted.

## 14.1 D15 Step 8 — production closeout (nenormativno; 2026-09-06)

Ovo podpoglavlje je **strogo nenormativno**. Evidencija je IMPLEMENTATION / PRODUCTION. **Ne** mijenja D1–D15. **Ne** uvodi novo poslovno pravilo. **Ne** autorizuje mirror OFF, CONTRACT, fizički DROP niti uključivanje EP identity tokova.

**PO odluka:** DK-TS-002 / D15 / STEP 8 capability i logički cutover = **PO USVOJENO** = **CLOSED / PRODUCTION PASS**.

Kanonski upstream ostaje: DK-BM-002 v1.0.4 USVOJENO; DK-FS-002 v1.0.4 USVOJENO; DK-TS-002 v1.0.0 USVOJENO (normativno). Ova verzija 1.0.1 je status-only.

### Produkciono stanje nakon logičkog cutover-a

| Flag | Vrijednost |
|------|------------|
| `IDENTITY_CANONICAL_READ` | `true` |
| `IDENTITY_CANONICAL_WRITE` | `true` |
| `IDENTITY_WRITE_FREEZE` | `false` |
| `IDENTITY_EP_IDENTITY_FLOWS` | `false` |

Kanonski identitet je produkcioni autoritet. **R1 = ACTIVE.** Aktivan legacy identity autoritet = **NE**. EP hard gate ostaje **OPEN**; live EP identity tokovi ostaju **durably disabled**.

### GATE 1 — zaštićeni prozor

**PO USVOJENO / CLOSED / PASS.**

- HTTP freeze proven: `POST /register` → HTTP 403, poruka `Registracija subjekta je trenutno onemogućena.`
- Produkcijski `max_execution_time` = 30 s. FastCGI worker recycle: Plesk PHP Settings Apply (nije PHP-FPM).
- Finalna stabilna populaciona granica: N1=67, N2=67, **N=67**.

Finalni Step 7 production DRY-RUN:

| Stavka | Vrijednost |
|--------|------------|
| mode | dry-run |
| census_max_user_id | 67 |
| live_max_user_id | 67 |
| row_count | 48 |
| reconcile_passed | true |
| graph_readiness_passed | true |
| cutover_ready | false |
| population_boundary_protected | false |
| would_create / created | 0 / 0 |
| would_update / updated | 0 / 0 |
| skipped_idempotent | 17 |
| source_drift | 0 |
| conflict | 0 |
| canonical_invalid | 0 |
| failed | 0 |
| ep_gate | OPEN |

**Step 7 APPLY: NOT REQUIRED / NOT EXECUTED.**

Finalni production VERIFY:

| Stavka | Vrijednost |
|--------|------------|
| mode | verify |
| census_max_user_id | 67 |
| row_count | 48 |
| verified | 17 |
| skipped_not_eligible | 31 |
| skipped_outside_census_boundary | 0 |
| missing_canonical | 0 |
| canonical_mismatch | 0 |
| source_drift | 0 |
| canonical_graph_invalid | 0 |
| unexpected_canonical | 0 |
| failed | 0 |

### GATE 2 — logički cutover

**PO USVOJENO / CLOSED / PASS.**

Redoslijed: `canonical_read=true` → FastCGI recycle → GET smoke PASS → `canonical_write=true` → FastCGI recycle → controlled existing-profile canonical writer smoke (`Profil je uspješno ažuriran.`) → freeze=false → final dashboard/profile HTTP smoke PASS.

Kanonski HTTP persist se dogodio. Zato je **R1 ACTIVE**. Legacy-only rollback je nesiguran.

### Post-cutover stabilization audit

Verdict: **A — POST-CUTOVER STABLE / READY FOR STEP 8 CLOSEOUT.**

- canonical identity authoritative = YES
- active legacy identity authority = NO
- registration / profile / admin writers safe = YES
- non-backfillable use-gates safe = YES
- EP durable disable safe = YES
- high-risk code corrective required = NO
- NEW BUSINESS RULE REQUIRED = NO

### Šta Step 8 **ne** zatvara

Prema D15 redoslijedu, nakon logičkog cutover-a ostaje **OPEN**:

- stabilizacioni / observation period (tačan broj dana nije usvojen);
- migracija preostalih kompatibilnih leftover čitalaca;
- mirror OFF eligibility;
- formalni fallback-OFF retire;
- CONTRACT eligibility;
- fizički DROP legacy kolona.

Te faze **ne** drže Step 8 otvorenim.

## 14.2 Country catalog localization (nenormativno; 2026-09-06)

Ovo podpoglavlje je **strogo nenormativno**. Evidencija je IMPLEMENTATION. **Ne** mijenja D1–D15. **Ne** uvodi novo poslovno pravilo. **Ne** reotvara Step 4, Step 5, Step 7, Step 8 ni kanonski identity cutover. **Ne** mijenja Step 9 observation model.

**PO odluka:** kompletna lokalizacija display labela kanonskog kataloga država = **PO USVOJENO**.

Realizovano:

- kompletan `CountryCatalog` display set: **249** ISO 3166-1 alpha-2 + dokumentovani izuzetak **XK** = **250**;
- kanonski identitet države ostaje **code-based** (ISO alpha-2 / XK); label je isključivo display vrijednost;
- korisnički nazivi su crnogorski, prema usvojenoj 250/250 mapi, uključujući PO override: `PS` = Palestina; `SH` = Sveta Jelena, Asension i Tristan da Kunja; `UM` = Udaljena ostrva Sjedinjenih Američkih Država;
- zaključani oblici ostaju: Crna Gora, Njemačka, Bjelorusija, Sjeverna Makedonija, Švajcarska, Jermenija, Češka, Nizozemska, Mijanmar, Ruska Federacija, Sjedinjene Američke Države, Kosovo;
- registracioni phone picker koristi **shared display-label source** (`CountryCatalog::label`); calling prefix / E.164 ostaje odvojen od country identity;
- calling code vrijednosti nijesu mijenjane;
- DB migracija, seeder, `countries` tabela, identity backfill i Step 7 reconcile **nisu** izvršeni i **nisu** potrebni;
- produkcioni identity flagovi i kanonski autoritet iz §14.1 **neizmijenjeni**.

Ova evidencija **nije** nova numerisana tehnička odluka. Odluka 6 ostaje CLOSED / PO USVOJENO.

## 14.3 Post-cutover existing business subjects remediation (nenormativno; 2026-09-07)

Ovo podpoglavlje je **strogo nenormativno**. Evidencija je IMPLEMENTATION, a od v1.0.4 i PRODUCTION DEPLOY / LEVEL A. **Ne** mijenja D1–D15. **Ne** uvodi D16. **Ne** uvodi novo poslovno pravilo. **Ne** reotvara Step 4, Step 7 ni Step 8. **Ne** zatvara Step 9. **Ne** aktivira EP identity tokove. **Ne** tvrdi da je Level B izvršen niti da je bilo koji od 13 korisnika već dopunio identitet u produkciji.

**PO odluka:** POST-CUTOVER EXISTING BUSINESS SUBJECTS REMEDIATION = **PO USVOJENO**. Corrective 01 = PASS / prihvaćen kao dio implementacije.

Obuhvat: **12** postojećih legacy DOO naloga bez kanonskog grafa i **1** postojeći legacy Preduzetnik nalog bez kanonskog grafa. Pet običnih legacy Fizičkih lica i staff nalozi **nisu** u ovom implementacionom obuhvatu. Produkcijski identitetski podaci se ovdje **ne** navode.

### Realizacija

1. Postojeći nalog se zadržava. Novi `users` red se **ne** kreira.
2. Nedostajući kanonski graf se kreira isključivo postojećim `CanonicalIdentityWriter::createForUser()`. Novi kanonski writer **ne** postoji.
3. Legacy `users.user_type` se koristi samo za deterministički izbor grane dopune dok kanonski graf **nije** prisutan. Nakon uspješne dopune kanonski graf je current identity SSOT.
4. Okidač je D10 REQUIRE-ON-USE. **Nema** globalnog login/dashboard completion gate-a.
5. Namjenski tok: **Dopunite podatke o subjektu** (`GET/POST /identitet/dopuna-subjekta`; `identity.completion.create` / `identity.completion.store`).
6. Zaključana mapiranja: DOO → `legal_entity` + `doo`; Preduzetnik → `physical_person` + `is_entrepreneur=true`. Korisnik **ne** može reklasifikovati subjekt u ovom toku.
7. Completeness ovog prolaza zahtijeva CRPS. Očekivana identifikaciona oznaka: DOO = **5**; Preduzetnik = **1**.
8. Podaci ovlašćenog lica DOO su user-supplied. Nosioc naloga se **ne** pretpostavlja tiho kao ovlašćeno lice. Legacy `users.residential_status` za DOO se ignoriše.
9. Nema tihog defaulta države `ME`. Nema tihe `+382` normalizacije/defaulta. Pozivni broj se bira eksplicitno. Calling-code identitet ostaje odvojen od ISO country identiteta.
10. Kanonski create i izvedeni `users.user_type` mirror su atomični. `DerivedUserTypeMirror` ostaje `final`. Nema legacy identity dual-write-a osim već usvojenog izvedenog `users.user_type` mirror-a.
11. Malformed postojeći kanonski graf fail-closed. Drugi CURRENT POST je isključivo idempotentni uspjeh; **ne** postaje `updateLiveGraph` i **ne** overlay-uje identitet.

### REQUIRE-ON-USE integracija

`requireCurrentSubject` **nije** oslabljen niti globalno izmijenjen.

KN integracija postoji samo u `ApplicationController::create` i `ApplicationController::store`. Ako subject gate padne i autentikovani korisnik pripada ovoj kohorti dopune: čuva se sigurna KN return putanja, redirect na `identity.completion.create`, nakon uspjeha povratak na dozvoljenu KN destinaciju. Ako korisnik nije eligible, ostaje postojeće fail-closed ponašanje.

Pregled konkursa, login, dashboard, KK i profile GET **nisu** completion okidači. `applications.submit` **nije** dio ovog slice-a. EP **nije** aktiviran.

### Validacija

DOO: zaključano `legal_entity` / `doo`; obavezan puni naziv; kanonski PIB; obavezan CRPS sa mark **5**; adresa i grad nijesu Kotor-restricted; obavezno ovlašćeno lice; identifikacija JMB **ili** pasoš + država izdavanja; bez residential status.

Preduzetnik: zaključano `physical_person` / `is_entrepreneur=true`; residential status se primjenjuje; usvojena JMB/pasoš grana zadržana; kanonski PIB; obavezan CRPS sa mark **1**; nerezidentna država prebivališta kroz `CountryCatalog`; bez defaulta `ME`.

### Transakcija / bezbjednost

Cilj je uvijek autentikovani tekući korisnik. `user_id` iz rute/tijela **nije** autoritet. Kreira se tačno jedan kanonski graf. Malformed postojeći graf se ovim create path-om **ne** popravlja. Outer transakcija pokriva `CanonicalIdentityWriter::createForUser()` + `DerivedUserTypeMirror::sync()`. Pad mirror-a rollback-uje kreirani graf. Produkciona apstrakcija **nije** dodata samo radi rollback testa.

### Test evidencija (prihvaćena)

Remediation testovi: **30** passed, **0** failed, **228** assertions.

Relevantni identity regression: **97** passed, **0** failed, **2231** assertions.

Najširi lokalni suite: **2136** passed, **6** failed, **2** skipped, **17469** assertions. Šest padova je `FakePaymentGatewayTest` (payment preview očekivao 200, dobio 302). Klasifikovano kao uočeni nevezani/pre-existing payment/EP regression iz implementacionog pregleda. **Nijesu** popravljeni. Cijeli suite **nije** PASS.

### Produkcioni deploy / Level A (nenormativno; 2026-09-07)

**POST-CUTOVER EXISTING BUSINESS SUBJECTS REMEDIATION PRODUCTION DEPLOY / LEVEL A = PASS.**

Produkcioni kandidat deploy-ovan: `59f3d46cc38f4c3792cf6b5d5324828533793f7e` (`feat(identity): add existing business identity completion`).

Live runtime nakon deploy-a: Laravel **12.29.0**; PHP **8.3.33**; `APP_ENV=production`; Debug OFF; URL `digital.kotor.me`; timezone Europe/Belgrade.

Deploy je zahtijevao produkcioni pull/update na prihvaćenu `origin/main` reviziju. **Nisu** izvršeni: migracija, seeder, composer dependency update, npm/asset build, izmjena `.env`, izmjena identity flagova, aktivacija EP. `optimize:clear` **nije** izvršen. FastCGI recycle **nije** bio potreban ni izvršen za ovaj code-only deploy.

Post-deploy `php artisan about`: Config = NOT CACHED; Events = NOT CACHED; Routes = NOT CACHED; Views = CACHED. Zato što su Views bili CACHED, a remedijacija dodaje novi Blade view, operator je izvršio `php artisan view:cache`. Rezultat: Blade templates cached successfully.

Kanonski autoritet iz §14.1 **neizmijenjen** (`IDENTITY_CANONICAL_READ=true`, `IDENTITY_CANONICAL_WRITE=true`, `IDENTITY_WRITE_FREEZE=false`, `IDENTITY_EP_IDENTITY_FLOWS=false`). **R1 = ACTIVE.** Rollback ovog deploy-a **nije** povratak na legacy-only identity authority.

**LEVEL A NON-MUTATING PRODUCTION SMOKE = PASS.**

1. Postojeći kanonski korisnik: login radi; dashboard radi; profile GET radi.
2. Postojeći kanonski korisnik: GET `/identitet/dopuna-subjekta` — forma dopune **nije** prikazana; korisnik je bezbjedno preusmjeren.
3. Anonimni korisnik: GET `/identitet/dopuna-subjekta` — authentication granica primijenjena; korisnik preusmjeren na login.

Nije izvršen identity remediation POST. Nije dopunjen nijedan legacy poslovni nalog.

**LEVEL B REAL REMEDIATION = NOT YET EXECUTED.** Nema produkcionog dokaza da je legacy DOO ili Preduzetnik ušao u formu, poslao dopunu, kreirao kanonski graf, kreirao red ovlašćenog lica, sinhronizovao izvedeni `users.user_type`, vratio se u KN tok ili izvršio idempotentni drugi POST. Ti koraci **nisu** PRODUCTION PASS.

### Step 9

**STEP 9 = OPEN.**

Ovaj deploy i Level A smoke daju dodatni pozitivni post-cutover stabilization dokaz. **Ne** zatvaraju automatski G9-1, G9-3 ni Step 9 kao cjelinu. Usvojeni evidence-based Step 9 observation model **nije** izmijenjen. Level B, kada bude legitimno izvršen, može postati dodatni post-cutover dokaz. **Ne** tvrdi se da je bilo koji od 13 korisnika već završio dopunu u produkciji.

Ova evidencija **nije** nova numerisana tehnička odluka. Odluka 10 ostaje CLOSED / PO USVOJENO. Odluka 15 ostaje CLOSED / PO USVOJENO. Step 8 ostaje CLOSED / PRODUCTION PASS.

## 14.4 Registration corrective (nenormativno; 2026-09-07)

Ovo podpoglavlje je **strogo nenormativno**. Evidencija je IMPLEMENTATION / CORRECTIVE. **Ne** mijenja D1–D15. **Ne** uvodi D16. **Ne** uvodi novo poslovno pravilo. **Ne** reotvara Step 8. **Ne** zatvara Step 9. **Ne** tvrdi production new-user E2E.

**REGISTRATION CORRECTIVE IMPLEMENTATION = PO USVOJENO.**

Lokalno je usklađen aktivni kanonski `GET/POST /register` put sa već usvojenim V1 ugovorom (`HomeController` → `CanonicalHttpIdentityService` → `RegistrationIdentityMapper` → `CanonicalIdentityWriter` → `DerivedUserTypeMirror`).

Ova evidencija **nije** production accepted. REG-14 ostaje OPEN. Produkcioni new-user E2E **nije** izvršen. Identity flagovi, `.env`, produkciona baza, FastCGI i deploy **nijesu** dirani. Kanonski DB unique indexi identifikatora **nisu** uvedeni i **nisu** dio ovog closeout-a.

---

**Kraj dokumenta DK-TS-002 v1.0.5**
