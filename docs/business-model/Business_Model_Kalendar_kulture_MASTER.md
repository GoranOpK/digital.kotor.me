# Digital Kotor
# Business Model
## Modul: Kalendar kulture

**Status dokumenta:** Stable
**Verzija:** 1.0.0

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-25 | Usvojena poglavlja BM-01 Organizator, BM-02 Moderator organizatora i BM-03 Urednik. Uspostavljena metodologija rada Business Modela modula Kalendar kulture. |
| PATCH-001 | 2026-07-25 | Revizija zaglavlja dokumenta (verzija 0.1, status U IZRADI; Status razvoja). |
| PATCH-002 | 2026-07-25 | Istorija verzija i Pravila upravljanja Business Modelom. |
| PATCH-003 | 2026-07-25 | Finalizacija metodologije rada (Upravljanje promjenama). |
| PATCH-004 | 2026-07-25 | BM-04 Događaj — USVOJENO. |
| PATCH-005 | 2026-07-25 | Finalizacija poglavlja BM-04 Događaj. |
| PATCH-006 | 2026-07-25 | BM-05 Manifestacija — USVOJENO. |
| PATCH-007 | 2026-07-25 | BM-06 Termin — USVOJENO. |
| PATCH-008 | 2026-07-25 | BM-07 Lokacija i BM-08 Kategorije i oznake — USVOJENO. |
| PATCH-009 | 2026-07-25 | BM-09 Mediji — USVOJENO. |
| PATCH-010 | 2026-07-25 | BM-10 Statusi i životni ciklus događaja — USVOJENO. Usvojeno: „Vraćen na doradu“ nije status događaja, već poslovna radnja kojom se događaj vraća iz statusa „Na odobrenju“ u status „Nacrt“. |
| PATCH-011 | 2026-07-25 | Korekcija numeracije poglavlja Korisnički portal i usklađivanje internih referenci nakon uvođenja BM-10 Statusi i životni ciklus događaja. |
| PATCH-012 | 2026-07-25 | Preimenovanje poglavlja BM-11 u „Portal Kalendara kulture“ i uklanjanje poslovnih pravila koja pripadaju platformi Digital Kotor. |
| PATCH-013 | 2026-07-25 | BM-11 Portal Kalendara kulture — USVOJENO (BM-PK-01–BM-PK-14). |
| PATCH-014 | 2026-07-25 | BM-14 Evidencija aktivnosti (Audit log) — USVOJENO (BM-AL-01–BM-AL-08). |
| PATCH-015 | 2026-07-25 | BM-13 Newsletter — USVOJENO (BM-NL-01–BM-NL-09); uklonjene ranije rezervacije BM-13 Poslovna obavještenja i BM-13.1 Newsletter. |
| PATCH-016 | 2026-07-26 | BM-15 Opšta poslovna pravila — USVOJENO (BM-GR-01–BM-GR-07). |
| PATCH-017 | 2026-07-26 | BM-16 Rječnik poslovnih pojmova — USVOJENO (BM-GL-01–BM-GL-21). |
| PATCH-018 | 2026-07-26 | BM-17 Arhitektura poslovnih cjelina — USVOJENO (BM-AR-01–BM-AR-08). |
| PATCH-019 | 2026-07-26 | BM-12 Urednički portal — USVOJENO (BM-EP-01–BM-EP-10). |
| PATCH-020 | 2026-07-26 | Dopuna BM-01/BM-02: tok „Postani organizator“ (podaci Organizatora i prvog Moderatora, odobrenje Urednika, verifikacija Moderatora); napomena da funkcionalnost još nije implementirana; pojašnjenje da Moderator nije Urednik; BM-MD-06 — podrazumijevana naslovna fotografija kategorije. |
| PATCH-021 | 2026-07-26 | Usklađivanje sa izuzetkom za događaje bez registrovanog Organizatora (javni interes): dopuna BM-01/BM-03/BM-04; usklađeni BM-ORG-04 napomena, BM-UR-06, BM-UR-07 i BM-DG-08. |
| PATCH-022 | 2026-07-26 | Konačni model upravljanja Moderatorima: podnosilac zahtjeva postaje prvi Moderator; naredne Moderatore predlažu postojeći Moderatori; ovlašćenja dodjeljuje isključivo Urednik; trajni audit zahtjeva. |
| PATCH-023 | 2026-07-26 | Terminološka migracija: postojeći poslovni koncept „Termin“, koji je predstavljao pojedinačno održavanje događaja, preimenovan je u „Održavanje događaja“. Pojam „Termin“ sužen je na datum i vrijeme održavanja. Poslovna logika nije promijenjena. Zahvaćeni: BM-04, BM-05, BM-06, BM-07, BM-10, BM-11, BM-12, BM-16 (uključujući BM-GL-22). Oznake BM-TR-* zadržane kao istorijske oznake pravila. |
| PATCH-024 | 2026-07-26 | Usvojeno: Datum održavanja je obavezan, a vrijeme može biti definisano. Za cjelodnevni događaj definiše se samo datum održavanja. Usklađeni BM-TR-01, BM-TR-03, BM-TR-05, BM-GL-12, BM-GL-22, BM-PK-09, BM-16, dijagrami i formulacije termina u BM-04/BM-06. |
| PATCH-025 | 2026-07-26 | BM-11 Portal: obavezna registracija za korišćenje portala uz zadržavanje domena identiteta na platformi (BM-PK-02); uklonjeno sortiranje iz BM-PK-07; dodato BM-PK-15 Istaknuti događaj. BM-AR-02 zadržan bez izmjene. |
| PATCH-026 | 2026-07-26 | BM-06: definisan kompletan poslovni workflow statusa „Odgođen“ za održavanje događaja (BM-TR-10 usklađen; dodati BM-TR-12–BM-TR-15). |
| PATCH-027 | 2026-07-27 | Definisana poslovna ovlašćenja za upravljanje statusima održavanja (Planiran, Odgođen, Otkazan) u skladu sa modelom uloga. Dodati BM-TR-16–BM-TR-18; usklađen BM-TR-08. |
| PATCH-028 | 2026-07-27 | Terminološko usklađivanje: u BM-EP-03 i BM-AL-07 usvojen termin „entitet“ umjesto ranijeg neusklađenog naziva. Poslovna logika nije mijenjana. |
| PATCH-029 | 2026-07-27 | Model korisnika: Organizator = poslovni entitet (nije uloga/korisnik); zahtjev za kreiranje Organizatora sa predloženim početnim Moderatorom; Urednik isključiva uloga; BM-MOD-03/04 usklađeni (aktivni kontekst Organizatora). |
| PATCH-030 | 2026-07-27 | Ulaznice i cijena van poslovnog opsega V1: BM-TR-11 — upravljanje informacijama o ulaznicama i cijeni nije dio opsega V1; uklonjene reference u BM-06 konceptu i BM-16 terminološkim pravilima. |
| PATCH-031 | 2026-07-27 | BM-13 Newsletter: model zasnovan na novoobjavljenim događajima (objavljivanje = okidač; periodična provjera; objedinjavanje; bez fiksnog sedmičnog perioda). Usklađeni BM-NL-01, BM-NL-06, BM-NL-07, BM-NL-09; dodati BM-NL-10–BM-NL-16. |
| PATCH-032 | 2026-07-27 | BM-13 Newsletter: poslovno značajne promjene kao dodatni okidači (otkazivanje, odlaganje, promjena datuma/vremena/lokacije); prioritetna obavještenja; publika = pretplatnici kojima je događaj već poslat. Usklađeni BM-NL-01, BM-NL-06, BM-NL-07, BM-NL-14, BM-NL-16; dodati BM-NL-17–BM-NL-21. |
| PATCH-033 | 2026-07-27 | BM-13 Newsletter: višestruke poslovno značajne promjene → jedinstveno obavještenje sa posljednjim važećim stanjem; objedinjavanje prioritetnih obavještenja uz blagovremenost; zabrana kontradiktornih poruka u istom ciklusu. Usklađeni BM-NL-06, BM-NL-20; dodati BM-NL-22–BM-NL-25. |
| PATCH-034 | 2026-07-28 | Nova poslovna odluka za deaktivaciju Organizatora: Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. Usklađeni BM-ORG-12 i BM-UR-10. |
| PATCH-035 | 2026-07-28 | Ovlašćenja za otkazivanje i ponovnu objavu događaja (N-DG-01): Moderator može otkazati objavljeni događaj svog Organizatora; Urednik može otkazati bilo koji objavljeni događaj; isključivo Urednik može ponovo objaviti otkazani događaj (nije automatski; može ažurirati podatke prije objave). Usklađeni BM-DG-05, BM-DG-09, BM-ST-07, BM-MOD-16, BM-UR-11. |
| PATCH-036 | 2026-07-28 | Korekcija otkazivanja nakon deaktivacije Organizatora: deaktivacijom prestaje moderatorski kontekst; Moderator više ne izvršava poslovne radnje nad događajima tog Organizatora; otkazivanje događaja deaktiviranog Organizatora isključivo Urednik. Usklađeni BM-ORG-12, BM-DG-05, BM-ST-07, BM-MOD-16. |
| PATCH-037 | 2026-07-29 | PO-DG-05: direktna objava Urednika isključivo za događaj bez Organizatora (usklađen BM-ST-04). PO-DG-06: otkazani događaj automatski prelazi u Arhiviran nakon završetka svih održavanja (usklađeni BM-DG-04, BM-ST-08). Zatvoreni N-DG-05 i N-DG-06. |
| PATCH-038 | 2026-07-29 | PO-MF-01–PO-MF-08: životni ciklus Manifestacije; opcioni Organizator; dodavanje/uklanjanje Događaja na objavljenoj; uslovi objave; kardinalnost; nezavisni lifecycle; naslovna fotografija; Web stranica / Više informacija; bez sopstvenih kategorija i lokacija. Usklađeni BM-05, BM-GL-11, BM-PK-10/11. |
| PATCH-039 | 2026-07-29 | PO-MF-09–PO-MF-12: trajni uslov ≥1 Objavljen Događaj; izračun trajanja iz važećih održavanja; Otkazana→Arhivirana nakon isteka trajanja; ovlašćenja otkaza. Zatvoreni N-MF-01–N-MF-04. Evidentiran N-MF-05 kao napomena (centralna evidencija Manifestacija). |
| PATCH-040 | 2026-07-30 | PO-LOC-01–PO-LOC-07: Lokacija kao samostalan entitet centralnog kataloga; jedinstvenost i obrada duplikata; ovlašćenja Moderator/Urednik; lifecycle Aktivna/Deaktivirana; referencijalni integritet i atomski merge; audit istorija nad Lokacijom; V1 = isključivo fizičke Lokacije. Usklađeni BM-07 i BM-GL-13. |
| PATCH-041 | 2026-07-30 | Korekcija PO-LOC-01 i PO-LOC-05: centralni katalog Lokacija je opcioni katalog za ponovno korišćenje (nije obavezan i nije jedini izvor svih Lokacija); dozvoljen ručni unos naziva Lokacije bez obavezne kataloške reference; referencijalni integritet i merge primjenjuju se samo kada postoji veza sa katalogom. Usklađeni BM-07 i BM-GL-13. |
| PATCH-042 | 2026-07-30 | Documentation Consistency Patch (CR-003): terminološko pojašnjenje BM-AL-07 — uklonjena dvosmislena formulacija „Održavanje (gdje je u katalogu)“ i zamijenjena jednoznačnim opisom aktivnosti nad Održavanjem u okviru kataloga Događaji iz FS §5.16. Bez izmjene poslovnih pravila. |
| PATCH-043 | 2026-07-30 | TS7-PO-01–TS7-PO-06: BM-08 Kategorije i oznake — poslovni katalog (ne ENUM); oznake u V1; lifecycle Aktivna/Neaktivna; bez migracije test podataka; bez kategorije „Nešto drugo“; katalogom upravlja isključivo Urednik; Moderator samo koristi; usklađeni BM-GL-14 i BM-GL-23. |
| PATCH-044 | 2026-07-31 | TS8-01–TS8-09: BM-09 Mediji — samostalan entitet bez poslovnog vlasnika; zatvoreni katalog namjena (naslovna događaja, naslovna manifestacije, podrazumijevana fotografija kategorije); kardinalnosti i fallback; tip Fotografija (JPEG/PNG/WebP, 5 MB); lifecycle Aktivan/Neaktivan; ovlašćenja Moderator/Urednik; pretraga i metapodaci; usklađeni BM-GL-15 i BM-PK-12. |
| PATCH-045 | 2026-07-31 | TS-009 faza 1 (IA-01, PO-TS9-03A, PO-TS9-04A, PO-TS9-05A, PO-TS9-05B): evolutivni razvoj Portala; stranica „Pretraga i pregled“; filteri; zadržavanje postojećih prikaza; lista na Pretrazi i pregledu; mjesečni kalendar samo na početnoj. Dodati BM-PK-16–BM-PK-20; usklađeni BM-PK-06–BM-PK-08 i BM-AR-02. |
| PATCH-046 | 2026-07-31 | TS-009 faza 2 (PO-TS9-06A–PO-TS9-06D): Hero (statički identitet); istaknuti događaji (max 3, Urednik, aktuelni); statistike (3 klikabilne kartice; treća = naziv izabranog mjeseca); lista ispod kalendara (naredni max 3 / dan; dugme „Prikaži sve događaje“). Usklađen BM-PK-15; dodati BM-PK-21–BM-PK-23. |
| PATCH-047 | 2026-07-31 | TS-009 faza 3 (PO-TS9-07A–PO-TS9-07E): Manifestacije kao zasebna cjelina javnog portala (navigacija, lista, detalj, program, veza ↔ Događaji). Usklađeni BM-PK-04, BM-PK-08, BM-MF-13; dodati BM-PK-24–BM-PK-28. |
| PATCH-048 | 2026-07-31 | TS-009 Final Review: terminološko razdvajanje Oznaka (BM-08) od Tagova medija (BM-09) u BM-DG-06 i pripadajućem tekstu BM-04. Bez izmjene poslovne logike. |
| PATCH-049 | 2026-07-31 | §5 Poslovni principi — popunjeno sistematizacijom opštih principa izvedenih iz usvojenih BM-01…BM-17. Bez novih poslovnih pravila i bez proširenja obima. |
| PATCH-050 | 2026-07-31 | §7 Završne odredbe — uvedeno završno poglavlje o ulozi i održavanju Business Model dokumenta. Bez novih poslovnih pravila sistema. |
| 0.5.0 | 2026-07-31 | Final Review. Završna dokumentaciona revizija: §5 i §7 kompletirani (PATCH-049/050); BM-01…BM-17 USVOJENO; usvojene PO/IA odluke ugrađene; bez novih poslovnih pravila. Bez izmjene FS/TS/IS/Feature Registry/implementacije. |
| 1.0.0 | 2026-07-31 | Stable. Business Model je uspješno prošao Final Review i predstavlja referentni poslovni dokument modula Kalendar kulture. Bez izmjene poslovnih pravila, numeracije, identifikatora ili sljedivosti. Bez izmjene FS/TS/IS/Feature Registry/implementacije. |
| PATCH-051 | 2026-08-06 | CR-004B (javni prikaz otkazanih): usklađen BM-PK-13 (cancelled ostaje; portalna Arhiva = vremenska površina ≠ interni archived; bez javne dostupnosti archived); usklađen BM-AR-02 i BM-PK-15 (otkazani nisu u Istaknutim — bez izmjene flaga); usklađen BM-PK-22 (statistike = javno dostupni published|cancelled). Bez izmjene BM-DG-04 / BM-DG-05 / BM-ST-07. Bez izmjene implementacije. |
| PATCH-052 | 2026-08-06 | PO-N-TR-02-01–03: zatvaranje N-TR-02 — serija nije poslovni entitet; generator dnevno/sedmično/mjesečno završava brojem ili krajnjim datumom (max 100); ručna i generisana održavanja ravnopravna nakon generisanja. Usklađeni BM-TR-06, BM-TR-07. Bez novog entiteta / lifecycle / statusa. Bez izmjene implementacije. |
| PATCH-053 | 2026-08-06 | PO-DG-07: status Otkazan je terminalan za povratak u Objavljen (superseduje isključivo dio PATCH-035 / N-DG-01 koji je dozvoljavao Otkazan → Objavljen). Novi program = novi zapis; Odgođen ostaje jedini mehanizam promjene termina postojećeg događaja; Otkazan = istorijski zapis (izmjena sadržaja zabranjena; izuzetak: razlog otkazivanja / napomena urednika). Usklađeni BM-DG-09, BM-ST-07, BM-UR-11, BM-MOD-16, BM-TR-12, BM-ST-09; dodati BM-DG-10. Bez izmjene FS/TS/Feature Registry/implementacije. |
| PATCH-054 | 2026-08-07 | PO-ORG-01–PO-ORG-04: katalog polja Organizatora V1 (BM-ORG-13); Moderator isključivo preko postojećeg aktivnog `user_id` (BM-MOD-17); Organizator nastaje tek pri odobrenju (pojašnjen BM-ORG-03); pristup uredničkom portalu iz aktivnog moderatorskog ovlašćenja bez nove platformske uloge (BM-MOD-18). Usklađen BM-ORG-07. Bez izmjene implementacije. |
| PATCH-055 | 2026-08-08 | **PO-AUTO-01 / PO-AUTO-02:** Otkazivanje Događaja (Objavljen → Otkazan) u istoj poslovnoj operaciji automatski postavlja sva Planirana i Odgođena Održavanja u Otkazan; Završen/Otkazan Održavanja ostaju nepromijenjena. Preciziran trenutak Sistemskog Planiran → Završen (vrijeme_do ako postoji; inače kraj kalendarskog dana `datum`; aplikaciona vremenska zona). Usklađeni BM-DG-05, BM-DG-10, BM-DG-11 (novo), BM-TR-10, BM-ST-08. **PO-AUTO-01 superseduje** prethodnu interpretaciju/pravilo po kojem otkazivanje Događaja ne mijenja statuse njegovih Održavanja. Bez izmjene implementacije. |
| PATCH-056 | 2026-08-08 | **PO-DG-08 / PO-DG-09:** preciziran BR-052 / BM-UR-07 / BM-DG-08 — naknadno povezivanje isključivo za Objavljen + bez Organizatora; jednosmjerno NULL → Aktivan Organizator; bez unlink/reassign u V1; status ostaje Objavljen. BM-DG-10 (Organizator na Otkazanom) nepromijenjen. Bez izmjene implementacije. |
| PATCH-057 | 2026-08-08 | **PO-DG-10:** pojednostavljeni V1 tok prvog odobravanja Događaja — Na odobrenju = sadržajno zaključan; Urednik samo Odobri ili Vrati na doradu (razlog obavezan); bez Moderator povlačenja; bez „Počni pregled“; bez direktnog uređivanja Urednika na Na odobrenju. Dodati BM-ST-10, BM-MOD-19; usklađeni BM-UR-02, BM-ST-04. Ne mijenja Prijedlog izmjene Objavljenog. Bez izmjene implementacije. |
| PATCH-058 | 2026-08-08 | **PO-N-TR-02-04:** preciziran V1 generator Održavanja — samo Nacrt; dnevno +1 / sedmično +7 / mjesečno clamp; broj XOR krajnji datum; max 100; šablon vremena/lokacije; Planiran; duplikati → odbij cijelu operaciju; atomičnost; bez preview; bez Proposal/Objavljen generatora. Usklađeni BM-TR-06, BM-TR-07. Bez izmjene implementacije. |
| PATCH-059 | 2026-08-08 | **TS7-PO-07:** konačni početni V1 katalog kategorija Događaja (14 naziva, usvojeni redoslijed); značenja; kategorija ≠ Manifestacija; kategorija ≠ tip Organizatora; odbačene legacy vrijednosti; semantičko mapiranje legacy→kanonski; tehnički cutover ostaje TS-009. Dodati BM-KO-09–BM-KO-11; usklađen BM-GL-14. Bez izmjene implementacije. |
| PATCH-060 | 2026-08-09 | **Faza 6A (javni portal Događaja) — usvojene PO odluke:** potvrda PO-EV-01 (bez migracije/dual-read/dual-write legacy `CulturalEvent`); očuvanje postojećeg izgleda (IA-01); kartica = prvo naredno relevantno Održavanje + „+ još N termina“; sistemsko sortiranje Pretrage; Odgođen na detalju vs kartici; CAT-CUTOVER na `CulturalCategory`; Faza 6A ≠ 6B (Manifestacije); V1 bez javnog `cancellation_reason` (BR-272); legacy URL smije 404. Usklađeni BM-PK-09, BM-PK-11, BM-PK-13, BM-DG-10, BM-UR-11, BM-ST-07, BM-KO-11; dodati BM-PK-29–BM-PK-33. Bez izmjene implementacije. |
| PATCH-061 | 2026-08-09 | **PO-6A11-01 — kanonski javni status Događaja (multi-OCC):** apsolutni prioritet Otkazan; za Objavljen agregat U toku → Predstoji → Završen; postponed-only i 0 Održavanja → bez vremenskog statusa; vremenski istek Planiranog (ne samo status Završen); Odgođen nije badge Događaja. Dodat BM-PK-34; usklađen BM-PK-13. Bez izmjene lifecycle / arhive. |
| PATCH-062 | 2026-08-09 | **PO-6A09-01…06 — Javna Arhiva vs interni Arhiviran:** Arhiviran = interni lifecycle; Javna Arhiva = odvojeni istorijski pogled; aktivni public skup ostaje published\|cancelled; archive-only query; očuvanje statusa prije arhiviranja (published/cancelled); istorijski badge Otkazan/Završen; kartica = posljednje istorijsko OCC; sort DESC. Dodat BM-PK-35; usklađeni BM-PK-13, BM-ST-08, BM-DG-04. Bez izmjene implementacije. |
| PATCH-063 | 2026-08-10 | **Urednički tok kreiranja, pripreme i neposrednog upravljanja Događajem (PO-U-01…19):** Urednik u create toku ne bira registrovanog Organizatora; opcion ručni naziv neregistrovanog Organizatora (samo naziv); poslovno „U pripremi“ / „Sačuvaj i nastavi“ (bez novog statusa; Moderator zadržava „Nacrt“); zajednička lista Događaji; brisanje samo prije prve objave; direktna objava bez approval; direktno uređivanje dozvoljenog sadržaja Objavljenog u uredničkom toku (Prijedlog izmjene Moderatora ostaje); Odgođen bez poznatog novog termina; Prvobitni termin; razlozi odgađanja/otkazivanja Održavanja i Događaja opcioni i mogu biti javno prikazani; Otkazan ostaje terminalan. Usklađeni BM-UR-02/06/07/11, BM-DG-01/07/08/10, BM-ST-03/04/06/07, BM-TR-10/14/15, BM-PK-13/31/33, BM-ORG-04; dodati BM-UR-12–BM-UR-16, BM-DG-12–BM-DG-13, BM-ST-11, BM-TR-19–BM-TR-20, BM-PK-36, BM-EP-11, BM-GL-24–BM-GL-25. **Supersede:** dio BM-PK-33 / BM-DG-10 / BM-UR-11 / BM-ST-07 / BM-PK-13 koji zabranjuje javni prikaz opcionog razloga otkazivanja; pojašnjenje da „Nacrt“ nije poslovna faza Urednikovog direktnog toka. Bez izmjene FS/TS/Feature Registry/implementacije. |
| PATCH-064 | 2026-08-10 | **Informativna naslovna vidljivost Odgođenog Događaja (PO-064-01…14):** Objavljen Događaj bez narednog Planiranog Održavanja, ali sa Odgođenim Održavanjem bez poznatog novog termina, ostaje informativno vidljiv na naslovnoj do isteka prvobitnog datuma uključivo; kartica prikazuje „Odgođeno“ i „Prvobitni termin“ (prvobitni datum nije važeći termin; Odgođeno ≠ Planirano/upcoming). Više Odgođenih: informativnu karticu određuje najbliže Odgođeno čiji prvobitni datum još nije istekao; nakon isteka prelaz na sljedeće takvo. Planirano ima apsolutni prioritet za standardni termin kartice. Novi Planirani termin vraća standardno ponašanje. Nakon isteka posljednjeg — bez naslovne vidljivosti po ovom osnovu; Pretraga i detalj ostaju. Usklađeni BM-PK-23/29/31/34/36; dodati BM-PK-37, BM-GL-26. Bez izmjene lifecycle / Otkazano / arhive / newsletter / Urednik–Moderator tokova. Bez izmjene FS/TS/Feature Registry/implementacije. |
| PATCH-065 | 2026-08-11 | **PO-6B-08 / PO-6B-09:** Javna vidljivost Otkazane Manifestacije do isteka izvedenog perioda (aktivna lista + oznaka „Otkazana“ + detalj + program; zatim automatska Arhivirana); javni prikaz veze Događaj→Manifestacija samo kada je MF javno dostupna (Objavljena / Otkazana / Arhivirana); anti-leak za Nacrt / Na odobrenju / Vraćena; bez statusa MF na detalju Događaja. Usklađeni BM-PK-25, BM-PK-28; dodati BM-PK-38, BM-PK-39. Potvrđeno: MF nema status Odgođena (BM-MF-11); MF/Event lifecycle nezavisni (BM-MF-15). Bez izmjene implementacije. |
| PATCH-066 | 2026-08-11 | **PO-6B-10:** Globalno sortiranje rezultata Pretrage kada je Tip sadržaja = Sve — Događaji i Manifestacije u jednom hronološkom poretku po vremenskom ključu (Event: prvo naredno relevantno Održavanje; MF: početak izvedenog perioda); NULL last; tie: Naziv → tip kao tehnički tie-breaker → ID. Tip sadržaja nije poslovni prioritet. Tip=Događaji zadržava BM-PK-30; Tip=Manifestacije zadržava poredak liste Manifestacija. Dodat BM-PK-40; usklađen BM-PK-30. Bez izmjene implementacije u ovom docs paketu. |
| PATCH-067 | 2026-08-11 | **PO-ORG-05:** Napomena Urednika na zahtjevu za kreiranje Organizatora — pri odobravanju opciona; pri odbijanju obavezna (ne-prazna); bez napomene odbijanje nije dozvoljeno; zahtjev ostaje Podnesen; napomena se trajno čuva uz odluku. Usklađeni BM-ORG-09, BM-ORG-11; dodat BM-ORG-14. Ispravljena zastarjela napomena o implementaciji u BM-01 (zahtjevi su implementirani). Usklađeno sa FS PATCH-FS-068 / TS-001 v0.3.1. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (PATCH-001, PATCH-002...),
- datum,
- kratak naziv,
- kratak opis izmjene.

Naziv PATCH-a predstavlja zvanični naziv izmjene i koristi se u istoriji verzija.

**Terminološka napomena (važeći pojam):** Istorijski zapisi (npr. PATCH-007) mogu koristiti raniji naziv „Termin“ za poslovnu cjelinu koja danas nosi naziv **Održavanje događaja** (PATCH-023). Važeći poslovni termin za tu cjelinu je **Održavanje** / **Održavanje događaja**. Istorijski redovi se ne mijenjaju.

---

## Svrha dokumenta

Dokument predstavlja referentni poslovni model za planiranje, razvoj, testiranje i održavanje sistema.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| BM-01 Organizator | USVOJENO |
| BM-02 Moderator organizatora | USVOJENO |
| BM-03 Urednik | USVOJENO |
| BM-04 Događaj | USVOJENO |
| BM-05 Manifestacija | USVOJENO |
| BM-06 Održavanje događaja | USVOJENO |
| BM-07 Lokacija | USVOJENO |
| BM-08 Kategorije i oznake | USVOJENO |
| BM-09 Mediji | USVOJENO |
| BM-10 Statusi i životni ciklus događaja | USVOJENO |
| BM-11 Portal Kalendara kulture | USVOJENO |
| BM-12 Urednički portal | USVOJENO |
| BM-13 Newsletter | USVOJENO |
| BM-14 Evidencija aktivnosti (Audit log) | USVOJENO |
| BM-15 Opšta poslovna pravila | USVOJENO |
| BM-16 Rječnik poslovnih pojmova | USVOJENO |
| BM-17 Arhitektura poslovnih cjelina | USVOJENO |

---

# Pravila upravljanja Business Modelom

1. Business Model predstavlja zvaničnu poslovnu specifikaciju modula Kalendar kulture.

2. Posljednja usvojena verzija Business Modela predstavlja jedini izvor istine (Single Source of Truth).

3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu odluku ili usvojenu izmjenu dokumenta.

4. Kompletan Business Model generiše se isključivo na izričit zahtjev.

5. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno prepisivati, preformulisati ili reorganizovati usvojeni sadržaj.

6. Ako postoji razlika između implementacije sistema i Business Modela, implementacija se usklađuje sa Business Modelom, osim ako se poslovnom odlukom ne izmijeni sam Business Model.

---

# Upravljanje promjenama

Svaka izmjena Business Modela mora biti rezultat usvojene poslovne odluke i evidentirana kroz odgovarajući PATCH.

---

## Sadržaj

1. Uvod
2. Svrha
3. Ciljevi
4. Opseg
5. Poslovni principi
6. Poslovni model
   - BM-01 Organizator
   - BM-02 Moderator organizatora
   - BM-03 Urednik
   - BM-04 Događaj
   - BM-05 Manifestacija
   - BM-06 Održavanje događaja
   - BM-07 Lokacija
   - BM-08 Kategorije i oznake
   - BM-09 Mediji
   - BM-10 Statusi i životni ciklus događaja
   - BM-11 Portal Kalendara kulture
   - BM-12 Urednički portal
   - BM-13 Newsletter
   - BM-14 Evidencija aktivnosti (Audit log)
   - BM-15 Opšta poslovna pravila
   - BM-16 Rječnik poslovnih pojmova
   - BM-17 Arhitektura poslovnih cjelina
7. Završne odredbe

---

# 1. Uvod

Business Model definiše poslovna pravila, poslovne entitete, korisničke uloge i način funkcionisanja modula Kalendar kulture. Dokument predstavlja osnov za izradu funkcionalne i tehničke specifikacije.

---

# 2. Svrha

Dokument predstavlja referentni poslovni model za planiranje, razvoj, testiranje i održavanje sistema.

---

# 3. Ciljevi

Definisati poslovna pravila

Definisati korisničke uloge

Definisati poslovne entitete

Obezbijediti osnov za funkcionalnu i tehničku specifikaciju.

---

# 4. Opseg

Dokument opisuje poslovna pravila i poslovne procese, bez definisanja tehničke implementacije.

---

# 5. Poslovni principi

Poslovni principi sažimaju opšte orijentacije već usvojene u BM-01…BM-17. Ne zamjenjuju pojedinačna poslovna pravila.

1. **Jedan izvor istine** — Posljednja usvojena verzija Business Modela predstavlja jedini izvor istine za poslovna pravila modula Kalendar kulture.

2. **Business Model definiše poslovna pravila** — Poslovna pravila, entiteti, uloge i životni ciklusi utvrđuju se u ovom dokumentu; druga dokumentacija ih razrađuje, a ne zamjenjuje.

3. **Odvojenost od implementacije** — Business Model opisuje poslovna pravila i procese bez definisanja tehničke implementacije.

4. **Sljedivost dokumentacije** — Poslovna, funkcionalna, tehnička i implementaciona dokumentacija moraju ostati međusobno sljedive.

5. **Događaj i Manifestacija** — Događaj može biti samostalan ili pripadati najviše jednoj Manifestaciji; pripadnost Manifestaciji nije obavezna.

6. **Održavanja pripadaju Događaju** — Održavanja postoje isključivo u okviru Događaja; Manifestacija nema sopstvena održavanja.

7. **Javni portal i javni sadržaj** — Portal Kalendara kulture prikazuje isključivo javno objavljen, odnosno javno dostupan sadržaj u skladu sa poslovnim pravilima.

8. **Jedinstveni poslovni model** — Sve poslovne cjeline koriste zajedničke entitete, pravila i definicije utvrđene ovim Business Modelom.

9. **Razdvajanje odgovornosti** — Poslovne odgovornosti i ovlašćenja određuju se ulogom korisnika i poslovnom cjelinom; ne preklapaju se osim kada je to izričito definisano.

10. **Evidencija i odgovornost** — Poslovno značajne radnje evidentiraju se radi odgovornosti, kontrole i revizije, u skladu sa BM-14.

---

# 6. Poslovni model

# BM-01 Organizator

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Organizator definiše poslovni entitet koji je nosilac sadržaja u modulu Kalendar kulture, način njegovog kreiranja kroz zahtjev registrovanog korisnika, odnos prema Moderatorima i Uredniku, te pravila da Organizator nije korisnik sistema niti korisnička uloga.

## 2. Poslovni opis

Organizator je poslovni entitet u okviru Kalendara kulture i nosilac sadržaja.

Organizator nije korisnik sistema i nije korisnička uloga. Organizator nema korisnički nalog na osnovu statusa Organizatora, ne prijavljuje se u sistem, ne pristupa portalu kao Organizator, ne izvršava neposredno radnje u sistemu i nema sopstvenu korisničku sesiju.

Organizator može predstavljati, između ostalog: ustanovu, preduzeće, udruženje, nevladinu organizaciju, neformalnu grupu, fizičko lice koje organizuje događaje ili drugi subjekt koji se pojavljuje kao organizator događaja.

Radnje u ime Organizatora izvršava jedan ili više registrovanih korisnika koji imaju ovlašćenje Moderatora za tog Organizatora.

## 3. Poslovni koncept

Registrovani korisnik Digital Kotor može u modulu Kalendar kulture podnijeti zahtjev za kreiranje Organizatora.

Podnošenjem zahtjeva korisnik ne postaje Organizator, ne dobija automatski novu korisničku ulogu, ne postaje automatski Moderator i ne postaje vlasnik Organizatora. Korisnik samo inicira postupak kreiranja novog entiteta Organizatora.

Tok procesa:

1. Registrovani korisnik pokreće zahtjev za kreiranje Organizatora (iniciranje zahtjeva).
2. Zahtjev sadrži podatke o predloženom Organizatoru kao poslovnom entitetu, podatke potrebne za identifikovanje predloženog početnog Moderatora i podatak da li je predloženi Moderator sam podnosilac ili drugi registrovani korisnik.
3. Zahtjev se šalje Uredniku.
4. Urednik pregleda i odobrava ili odbija zahtjev.
5. Ako Urednik odobri zahtjev:

   * kreira se novi entitet Organizatora (zapis ne postoji prije odobrenja);
   * predloženi korisnik dobija ovlašćenje Moderatora za tog konkretnog Organizatora;
   * uspostavlja se poslovna veza između Moderatora i Organizatora.
6. Ako Urednik odbije zahtjev:

   * Organizator se ne odobrava kao aktivan poslovni entitet;
   * predloženi korisnik ne dobija moderatorska ovlašćenja;
   * podnosilac zahtjeva ne dobija novu ulogu niti druga posebna prava.

Podnosilac zahtjeva i predloženi Moderator mogu biti ista osoba ili dvije različite osobe. Podnosilac može sebe predložiti za Moderatora, ali to nije obavezno. Samo podnošenje zahtjeva ne daje moderatorska ovlašćenja ni podnosiocu ni predloženom korisniku.

Jedan registrovani korisnik može podnijeti zahtjev za kreiranje neograničenog broja Organizatora. Svaki zahtjev predstavlja poseban postupak i Urednik ga razmatra nezavisno.

Operativno upravljanje sadržajem u ime Organizatora obavljaju Moderatori. Organizator ne pristupa uredničkom portalu. Moderatori ne mogu samostalno objaviti sadržaj.

Svaki naredni Moderator može biti predložen isključivo od strane postojećeg aktivnog Moderatora povezanog sa tim Organizatorom. Moderator ne dodjeljuje ovlašćenja; samo podnosi zahtjev. Pristup i ovlašćenja novom Moderatoru dodjeljuje isključivo Urednik nakon odobrenja.

Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. Deaktivacijom Organizatora prestaje moderatorski kontekst za tog Organizatora; Moderatori više ne izvršavaju poslovne radnje nad njegovim događajima.

**Napomena o implementaciji:** Zahtjev za kreiranje Organizatora i upravljanje Moderatorima usvojeni su kao dio poslovnog modela i implementirani su u aplikaciji (TS-001 / Faza 2). **PATCH-067** uklanja raniju zastarjelu formulaciju „još nisu implementirani“.

**Napomena o nazivu:** Raniji naziv funkcionalnosti „Postani organizator“ zamijenjen je poslovno preciznijim nazivom „zahtjev za kreiranje Organizatora“.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-ORG-01 | Organizator je poslovni entitet i nosilac sadržaja u Kalendaru kulture. Organizator nije korisnik sistema i nije korisnička uloga. |
| BM-ORG-02 | Registrovani korisnik podnosi zahtjev za kreiranje Organizatora. Podnošenjem zahtjeva korisnik ne postaje Organizator, ne postaje Moderator i ne dobija novu korisničku ulogu. |
| BM-ORG-03 | Nakon odobrenja Urednika **kreira se** novi entitet Organizatora (zapis ne postoji prije odobrenja). Odobrenje ne dodjeljuje podnosiocu status korisničke uloge Organizatora. |
| BM-ORG-04 | Organizator je nosilac sadržaja. Operativno kreiranje, uređivanje i čuvanje nacrta sadržaja, kao i slanje sadržaja Uredniku na odobravanje, obavljaju Moderatori u ime Organizatora. Događaje registrovanog Organizatora kreira Moderator tog Organizatora; Urednik u svom create toku ne bira registrovanog Organizatora (BM-UR-12). Ovo pravilo ne isključuje da Urednik samostalno kreira i objavi Događaj za neregistrovanog Organizatora, odnosno Događaj bez veze sa registrovanim Organizatorom, u skladu sa BM-UR-06, BM-UR-12, BM-UR-13 i BM-DG-08. |
| BM-ORG-05 | Moderator ne može samostalno objaviti sadržaj. |
| BM-ORG-06 | Organizator ima jednog ili više Moderatora koji upravljaju sadržajem u njegovo ime. Organizator ne dodjeljuje ovlašćenja Moderatorima. |
| BM-ORG-07 | Zahtjev za kreiranje Organizatora sadrži podatke Organizatora prema BM-ORG-13, identifikaciju predloženog početnog Moderatora preko postojećeg aktivnog korisničkog naloga (`user_id`) i podatak da li je predloženi Moderator sam podnosilac ili drugi registrovani korisnik. Podnosilac i predloženi Moderator mogu biti ista ili različite osobe. |
| BM-ORG-08 | Nakon odobrenja zahtjeva za kreiranje Organizatora, predloženi korisnik dobija ovlašćenje početnog Moderatora za tog Organizatora. Moderatorska ovlašćenja nastaju tek nakon odobrenja Urednika. |
| BM-ORG-09 | Sistem trajno evidentira za zahtjev za kreiranje Organizatora: podnosioca zahtjeva, predloženog Moderatora, datum i vrijeme podnošenja, Urednika koji je odlučio, datum i vrijeme odluke, ishod odluke (odobreno/odbijeno) i napomenu Urednika kada je unesena (obavezna pri odbijanju — BM-ORG-14). |
| BM-ORG-10 | Jedan registrovani korisnik može podnijeti zahtjev za kreiranje neograničenog broja Organizatora. Svaki zahtjev predstavlja poseban postupak. |
| BM-ORG-11 | Ako Urednik odbije zahtjev, Organizator se ne odobrava kao aktivan poslovni entitet, predloženi korisnik ne dobija moderatorska ovlašćenja, a podnosilac ne dobija novu ulogu. Odbijanje ne sprečava podnošenje novog zahtjeva. Odbijanje zahtijeva obaveznu napomenu Urednika (BM-ORG-14). |
| BM-ORG-12 | Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. Deaktivacijom Organizatora prestaje moderatorski kontekst za tog Organizatora. Nakon deaktivacije Moderatori više nemaju pravo izvršavanja poslovnih radnji nad događajima tog Organizatora. Ako je potrebno otkazati događaj deaktiviranog Organizatora, tu radnju izvršava isključivo Urednik. |
| BM-ORG-13 | Poslovni podaci Organizatora u V1: naziv (obavezno); opis, kontakt e-mail, kontakt telefon, web sajt (opciono); status Aktivan/Deaktiviran; sistemski datumi. Van V1: PIB, matični broj, adresa, GPS, društvene mreže, logo i ostali pravni podaci. |
| BM-ORG-14 | Napomena Urednika na zahtjevu za kreiranje Organizatora (PO-ORG-05): pri **odobrenju** je **opciona** (može biti prazna); pri **odbijanju** je **obavezna** (ne-prazna nakon trimovanja). Bez validne napomene Sistem ne izvršava odbijanje: status zahtjeva ostaje Podnesen; Organizator i početni Moderator grant se ne kreiraju; nema parcijalnog upisa odluke. Validaciona poruka: „Napomena je obavezna prilikom odbijanja zahtjeva.“ Unesena napomena trajno se čuva uz odluku. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Moderator organizatora** — Moderatori upravljaju sadržajem u ime Organizatora. Obaveza da Organizator ima najmanje jednog aktivnog Moderatora definisana je u BM-02.
- **Urednik** — odobrava ili odbija zahtjev za kreiranje Organizatora i dodjeljuje ovlašćenja Moderatorima; odobrava i objavljuje sadržaj.
- **Događaj** — događaj se vodi u ime Organizatora. Izuzetno, Urednik može kreirati i objaviti događaj bez registrovanog Organizatora radi javnog interesa; naknadno povezivanje sa registrovanim Organizatorom uređeno je u BM-03 i BM-04.
- **Manifestacija** — Organizator može biti opciono povezan sa Manifestacijom kao nosilac ili partner; Manifestacija može postojati i bez Organizatora (urednička / platformska), u skladu sa BM-05.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-02 Moderator organizatora

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Moderator organizatora definiše korisničku ulogu, odnosno poslovno ovlašćenje registrovanog korisnika da postupa u ime konkretnog Organizatora, obim njegovih ovlašćenja, pravila o broju Moderatora, postupak predlaganja i odobravanja novih Moderatora te postupak njihovog uklanjanja.

## 2. Poslovni opis

Moderator organizatora je poslovna uloga registrovanog korisnika koja upravlja sadržajem u modulu Kalendar kulture u ime konkretnog Organizatora.

Moderator organizatora **nije** Urednik i **nije** nosilac sadržaja. Moderator nije Organizator. Moderator je operativni korisnik ovlašćen da izvršava radnje u ime Organizatora.

## 3. Poslovni koncept

Moderator organizatora ne postaje nosilac sadržaja. Sadržaj koji Moderator organizatora kreira ili uređuje vodi se u ime Organizatora.

Moderator organizatora može obavljati operativne radnje nad sadržajem u ime Organizatora, osim samostalne objave sadržaja. To obuhvata kreiranje događaja, uređivanje događaja, otkazivanje objavljenog događaja dok je Organizator aktivan i dok postoji aktivni moderatorski kontekst, upravljanje manifestacijama, čuvanje nacrta i slanje sadržaja Uredniku na odobravanje. Deaktivacijom Organizatora moderatorski kontekst prestaje. Sadržaj koji Moderator organizatora kreira ili uređuje mora biti poslat Uredniku na odobravanje prije objave. Iz statusa Otkazan nije dozvoljen povratak u Objavljen; Moderator ne mijenja sadržaj otkazanog događaja.

Jedan korisnik može biti Moderator organizatora za jednog ili više Organizatora. Pri svakoj radnji Moderator postupa u kontekstu konkretnog Organizatora (aktivni kontekst Organizatora).

Organizator može imati jednog ili više Moderatora organizatora i mora imati najmanje jednog aktivnog Moderatora organizatora.

**Početni Moderator:** Predloženi korisnik iz odobrenog zahtjeva za kreiranje Organizatora, nakon odobrenja Urednika, dobija ovlašćenje početnog Moderatora tog Organizatora. Predloženi Moderator može biti podnosilac zahtjeva ili drugi registrovani korisnik.

**Naredni Moderatori:** Svaki naredni Moderator može biti predložen isključivo od strane postojećeg aktivnog Moderatora povezanog sa tim Organizatorom (iniciranje zahtjeva). Moderator ne dodjeljuje ovlašćenja; samo podnosi zahtjev. Pristup i ovlašćenja novom Moderatoru dodjeljuje isključivo Urednik nakon pregleda i odobrenja (odobravanje zahtjeva i dodjela ovlašćenja). Tek nakon odobrenja Urednika novi Moderator postaje aktivan.

Moderator organizatora može pokrenuti postupak uklanjanja drugog Moderatora organizatora istog Organizatora, a uklanjanje odobrava Urednik.

Za zahtjeve vezane za Moderatore sistem trajno evidentira: podnosioca zahtjeva, datum i vrijeme podnošenja, Urednika koji je odobrio i datum i vrijeme odobrenja.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-MOD-01 | Moderator organizatora upravlja sadržajem u ime Organizatora. |
| BM-MOD-02 | Jedan korisnik može biti Moderator organizatora za jednog ili više Organizatora. |
| BM-MOD-03 | Podnosilac zahtjeva za kreiranje Organizatora može, ali ne mora, biti predložen kao početni Moderator. Podnosilac i predloženi Moderator mogu biti ista ili različite osobe. |
| BM-MOD-04 | Kada je Moderator povezan sa više Organizatora, pri svakoj radnji postupa u kontekstu konkretnog Organizatora. Sistem primjenjuje ovlašćenja i pripadnost sadržaja u skladu sa tim aktivnim kontekstom Organizatora. |
| BM-MOD-05 | Moderator organizatora može obavljati operativne radnje nad sadržajem u ime Organizatora, osim samostalne objave sadržaja. |
| BM-MOD-06 | Sadržaj koji kreira ili uređuje Moderator organizatora mora biti poslat Uredniku na odobravanje prije objave. |
| BM-MOD-07 | Organizator mora imati najmanje jednog aktivnog Moderatora organizatora. |
| BM-MOD-08 | Moderator organizatora može pokrenuti postupak uklanjanja drugog Moderatora organizatora istog Organizatora. |
| BM-MOD-09 | Moderator organizatora smatra se uklonjenim tek nakon odobrenja Urednika. |
| BM-MOD-10 | Sistem neće dozvoliti uklanjanje posljednjeg aktivnog Moderatora organizatora. |
| BM-MOD-11 | Moderator organizatora nije Urednik; urednička ovlašćenja se ne prenose ulozi Moderatora. Moderator nije Organizator. |
| BM-MOD-12 | Početni Moderator je predloženi korisnik iz odobrenog zahtjeva za kreiranje Organizatora. Ovlašćenja dobija tek nakon odobrenja Urednika. |
| BM-MOD-13 | Svaki naredni Moderator može biti predložen isključivo od strane postojećeg aktivnog Moderatora povezanog sa tim Organizatorom. Moderator ne dodjeljuje ovlašćenja; samo podnosi zahtjev. |
| BM-MOD-14 | Pristup i ovlašćenja novom Moderatoru dodjeljuje isključivo Urednik nakon pregleda i odobrenja zahtjeva. Tek nakon odobrenja Moderator postaje aktivan. |
| BM-MOD-15 | Sistem trajno evidentira za zahtjeve vezane za Moderatore: podnosioca zahtjeva, datum i vrijeme podnošenja, Urednika koji je odobrio i datum i vrijeme odobrenja. |
| BM-MOD-16 | Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje. Deaktivacijom Organizatora moderatorski kontekst prestaje i Moderator više nema pravo otkazivanja događaja tog Organizatora. Iz statusa Otkazan nije dozvoljen povratak u Objavljen. Moderator ne mijenja sadržaj otkazanog događaja. |
| BM-MOD-17 | Moderator Organizatora može biti isključivo korisnik sa postojećim registrovanim i aktivnim nalogom Digital Kotor. Identifikacija je preko `user_id`. Nije dozvoljeno predlaganje ili kreiranje Moderatora unosom slobodnog imena ili e-mail adrese. |
| BM-MOD-18 | Moderator ima pristup uredničkom portalu Kalendara kulture na osnovu aktivnog moderatorskog ovlašćenja nad najmanje jednim aktivnim Organizatorom. Moderator nije nova platformska uloga. Platformska uloga Urednika ostaje isključivo `kk_admin`. |
| BM-MOD-19 | Nakon što Moderator pošalje Događaj na odobrenje (Nacrt → Na odobrenju), u V1 **ne** može povući Događaj nazad u Nacrt niti uređivati njegov sadržaj dok je status Na odobrenju (PO-DG-10). Ponovno uređivanje moguće je tek nakon što Urednik vrati Događaj na doradu. Ovo pravilo **ne** mijenja tok Prijedloga izmjene Objavljenog Događaja. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Organizator** — ostaje nosilac sadržaja kao poslovni entitet; Moderatori upravljaju sadržajem u njegovo ime.
- **Urednik** — pregleda i odobrava sadržaj koji Moderator pošalje na odobravanje; odobrava zahtjeve za dodjelu i uklanjanje Moderatora te dodjeljuje ovlašćenja.
- **Događaj** — Moderator organizatora kreira, uređuje i može otkazati objavljeni događaj u ime aktivnog Organizatora u aktivnom kontekstu; nakon deaktivacije Organizatora nema pravo poslovnih radnji nad njegovim događajima; ne može samostalno objaviti događaj; ne vraća otkazani događaj u Objavljen niti mijenja sadržaj otkazanog događaja.
- **Lokacija** — Moderator organizatora može predlagati nove lokacije u skladu sa poslovnim pravilima Lokacije.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-03 Urednik

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Urednik definiše isključivu administrativnu ulogu Uredničkog portala Kalendara kulture: odobravanje ili odbijanje zahtjeva za kreiranje Organizatora, dodjelu ovlašćenja Moderatorima, urednički pregled, odobravanje i objavu sadržaja Moderatorskog toka, samostalno kreiranje i neposredno upravljanje Događajima bez registrovanog Organizatora, otkazivanje bilo kojeg objavljenog događaja, unos ili dopunu opcionog razloga otkazivanja (napomene urednika), te odobravanje uklanjanja Moderatora organizatora.

## 2. Poslovni opis

Urednik je administrator Uredničkog portala Kalendara kulture. Urednik nije običan registrovani korisnik javnog portala i ne koristi funkcionalnosti namijenjene običnim registrovanim korisnicima.

Urednik nije Organizator i nije Moderator Organizatora. Uloga Urednika je isključiva unutar poslovnog modela Kalendara kulture: Urednik nema kombinaciju uloge Urednika sa ulogom Moderatora niti sa statusom običnog registrovanog korisnika, ne mijenja aktivnu poslovnu ulogu i uvijek postupa u svojstvu Urednika.

Urednik odobrava ili odbija zahtjeve za kreiranje Organizatora, odobrava zahtjeve za dodjelu ovlašćenja novim Moderatorima, pregleda, odobrava i objavljuje događaje koje Moderatori pošalju na odobrenje, vraća ih na doradu kada su potrebne suštinske izmjene, samostalno kreira i objavljuje Događaje bez registrovanog Organizatora, može otkazati bilo koji objavljeni događaj, može unijeti ili dopuniti opcion razlog otkazivanja (napomenu urednika) i odobrava uklanjanje Moderatora organizatora. Iz statusa Otkazan nije dozvoljen povratak u status Objavljen. Za prvi tok Na odobrenju Urednik u V1 ne uređuje sadržaj Događaja direktno (PO-DG-10).

Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora.

## 3. Poslovni koncept

Urednik obezbjeđuje kvalitet i dosljednost javno objavljenog sadržaja kroz pregled, vraćanje na doradu, odobravanje i objavljivanje događaja Moderatorskog toka, te kroz samostalno kreiranje i neposredno upravljanje Događajima bez registrovanog Organizatora. Sadržaj koji pošalju Moderatori Urednik pregleda i odobrava prije objave; sadržajne korekcije na Na odobrenju u V1 idu isključivo preko vraćanja na doradu (PO-DG-10). Objavu sadržaja vrši isključivo Urednik.

Urednik koristi isključivo Urednički portal u okviru svojih poslovnih ovlašćenja i ne podnosi zahtjeve kao običan registrovani korisnik.

Urednik je isključivo ovlašćen da dodijeli pristup i ovlašćenja novom Moderatoru nakon pregleda i odobrenja zahtjeva.

Urednik samostalno kreira Događaje za neregistrovane Organizatore, odnosno Događaje koji nijesu vezani za registrovanog Organizatora. U tom toku Urednik ne bira registrovanog Organizatora i ne šalje Događaj na odobrenje; kada su ispunjeni uslovi za objavu, Urednik ga direktno objavljuje. Može opciono unijeti ručno naziv neregistrovanog Organizatora (samo naziv). Po registraciji odgovarajućeg Organizatora već Objavljen događaj bez registrovanog Organizatora može se naknadno povezati sa Organizatorom (BM-UR-07). Naknadno povezivanje predstavlja administrativnu dopunu podataka i ne smije mijenjati audit, istoriju događaja niti javno objavljene verzije.

Urednikov poslovni tok za takav Događaj je: Novi događaj → Sačuvaj i nastavi → U pripremi → dopuna Događaja i Održavanja → Objavi. „Nacrt“ nije poslovna faza Urednikovog direktnog toka; „U pripremi“ nije novi status životnog ciklusa Događaja.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-UR-01 | Urednik odobrava ili odbija zahtjev za kreiranje Organizatora. |
| BM-UR-02 | Urednik pregleda, odobrava i objavljuje događaje. Za Događaj u statusu **Na odobrenju** (prvi tok prije objave) Urednik u V1 **ne** uređuje sadržaj direktno; donosi odluku Odobri ili Vrati na doradu (PO-DG-10 / BM-ST-10). Uređivanje Događaja **U pripremi** bez registrovanog Organizatora (BM-UR-14), direktno uređivanje dozvoljenog sadržaja Objavljenog Događaja u uredničkom toku (BM-UR-16) i uredničke radnje nad Prijedlogom izmjene Objavljenog Događaja Moderatorskog toka ostaju po postojećim pravilima. |
| BM-UR-03 | Urednik vraća događaje na doradu kada su potrebne suštinske izmjene. |
| BM-UR-04 | Urednik pregleda i odobrava sadržaj koji šalju Moderatori. |
| BM-UR-05 | Urednik odobrava uklanjanje Moderatora organizatora. |
| BM-UR-06 | Urednik samostalno kreira i objavljuje Događaj bez registrovanog Organizatora kada je to potrebno radi pravovremenog informisanja građana i ostvarivanja javnog interesa. Takav Događaj nije predmet postupka odobravanja; Urednik ga ne šalje na odobrenje. Kada su ispunjeni postojeći uslovi za objavu (najmanje jedno validno Održavanje sa datumom i ostala usvojena pravila), Urednik ga direktno objavljuje (BM-ST-04). |
| BM-UR-07 | Urednik može jednokratno naknadno povezati već **Objavljen** događaj bez registrovanog Organizatora sa **Aktivnim** Organizatorom (PO-DG-08, PO-DG-09). Status događaja ostaje Objavljen. Operacija je administrativna dopuna: ne mijenja sadržaj događaja, istoriju događaja, prethodne javne verzije i ne kreira Prijedlog izmjene. Nakon uspješnog povezivanja ova radnja više nije dostupna za taj događaj. Uklanjanje Organizatora i zamjena jednog Organizatora drugim nijesu dozvoljeni kroz ovo pravilo. Ova radnja nije dodjela registrovanog Organizatora Moderatorskom Nacrtu prije objave — to je redovno uređivanje Nacrta Moderatora. Urednik u svom create toku ne dodjeljuje registrovanog Organizatora (BM-UR-12). |
| BM-UR-08 | Urednik odobrava zahtjeve za dodjelu ovlašćenja novim Moderatorima i isključivo on dodjeljuje pristup novom Moderatoru. |
| BM-UR-09 | Urednik je isključiva uloga Uredničkog portala. Urednik nije Organizator, nije Moderator Organizatora, ne kombinuje ulogu Urednika sa statusom običnog registrovanog korisnika u poslovnom modelu Kalendara kulture, ne mijenja aktivnu poslovnu ulogu i uvijek postupa kao Urednik. |
| BM-UR-10 | Urednik može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora. |
| BM-UR-11 | Urednik može otkazati bilo koji objavljeni događaj. Iz statusa Otkazan nije dozvoljen povratak u status Objavljen. Razlog otkazivanja Događaja je **opcion**: akcija Otkaži događaj omogućava unos razloga, ali razlog nije uslov za otkazivanje. Ako je razlog unesen, može se javno prikazati kao napomena o otkazivanju, uz standardizovano sistemsko obavještenje (BM-DG-10, BM-PK-36). Terminalnost Otkazan ostaje neizmijenjena (BM-DG-09). |
| BM-UR-12 | Urednik u svom create toku **ne** bira registrovanog Organizatora i **ne** kreira Događaj u ime registrovanog Organizatora. Događaje registrovanog Organizatora kreira Moderator tog Organizatora i šalje ih Uredniku na odobrenje. |
| BM-UR-13 | Za Događaj koji Urednik kreira bez registrovanog Organizatora, podatak „Organizator“ unosi se isključivo ručno kao tekstualni naziv neregistrovanog Organizatora. Polje je **opciono**. Dozvoljen je samo naziv; ne uvode se e-mail, telefon, veb-sajt, adresa, kontakt osoba niti drugi podaci Organizatora u okviru ove funkcionalnosti. Ručni unos ne kreira zapis registrovanog Organizatora, ne dodjeljuje nalog, ne dodjeljuje Moderatora i ne pokreće postupak odobravanja. Događaj može biti objavljen i bez navedenog Organizatora. |
| BM-UR-14 | Urednikov poslovni tok za Događaj bez registrovanog Organizatora je: Novi događaj → **Sačuvaj i nastavi** → **U pripremi** → dopuna → **Objavi**. Akcija „Sačuvaj i nastavi“ čuva započeti Događaj, ne objavljuje ga i ne šalje ga na odobrenje. „U pripremi“ označava Događaj koji je Urednik sačuvao, a koji još nikada nije bio objavljen; **nije** novi status životnog ciklusa i **nije** isto što i Moderatorski poslovni **Nacrt**. Takvi Događaji prikazuju se na zajedničkoj listi „Događaji“ (bez posebnog ekrana ili navigacije). Osnovne akcije za „U pripremi“ su Uredi i Obriši; Objavi se vrši iz samog Događaja. |
| BM-UR-15 | Urednik može trajno obrisati Događaj **samo** ako taj Događaj nikada nije bio objavljen (stanje „U pripremi“). Nakon prve objave trajno brisanje nije dozvoljeno, bez obzira na kasnije stanje (Objavljen, Otkazan, Arhiviran). Otkazan Događaj se ne briše. |
| BM-UR-16 | Urednik može **direktno** uređivati sadržajne podatke već Objavljenog Događaja koji pripada uredničkom toku (bez registrovanog Organizatora), radi ispravke ili dopune, bez Prijedloga izmjene i bez postupka odobravanja. Obuhvat, u granicama postojećeg modela, uključuje: naslov, opis, ručno uneseni naziv Organizatora, kategoriju, naslovni medij, oznake i druge obične sadržajne podatke za koje ne postoji posebno pravilo životnog ciklusa. Odgađanje Održavanja, otkazivanje Održavanja, otkazivanje Događaja i druge poslovno značajne radnje životnog ciklusa **ne** rade se kroz običan „Uredi“; koriste posebne akcije. Ovo pravilo **ne** ukida tok Prijedloga izmjene za Objavljeni Događaj Moderatorskog / registrovanog Organizatora. |

## 5. Odnosi sa drugim poslovnim cjelinama

- **Organizator** — Urednik odobrava ili odbija zahtjev za kreiranje Organizatora, može u bilo kojem trenutku deaktivirati Organizatora bez prethodnog zahtjeva Organizatora ili Moderatora i može jednokratno naknadno povezati već Objavljen događaj bez registrovanog Organizatora sa Aktivnim Organizatorom, u skladu sa BM-UR-07 (PO-DG-08 / PO-DG-09). U create toku Urednik ne bira registrovanog Organizatora (BM-UR-12).
- **Moderator organizatora** — Urednik pregleda i odobrava sadržaj koji Moderator pošalje na odobravanje; odobrava zahtjeve za dodjelu i uklanjanje Moderatora te dodjeljuje ovlašćenja. Moderatorski tok Nacrt → Pošalji na odobrenje ostaje netaknut.
- **Događaj** — Urednik pregleda, odobrava, objavljuje i vraća na doradu događaje Moderatorskog toka; u V1 ne uređuje sadržaj dok je status Na odobrenju (PO-DG-10); samostalno kreira, priprema, objavljuje, uređuje i u propisanim slučajevima briše Događaje bez registrovanog Organizatora; može otkazati bilo koji objavljeni događaj, može unijeti ili dopuniti opcion razlog otkazivanja i ne vraća otkazani događaj u Objavljen.
- **Lokacija** — Urednik odobrava ili odbija nove lokacije predložene za zajednički katalog lokacija.
- **Kategorije i oznake** — Urednik isključivo upravlja katalogom kategorija i katalogom oznaka, u skladu sa BM-08.
- **Lokacija** — Urednik odobrava ili odbija nove lokacije predložene za zajednički katalog lokacija.
- **Kategorije i oznake** — Urednik isključivo upravlja katalogom kategorija i katalogom oznaka, u skladu sa BM-08.

## 6. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-04 Događaj

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Događaj definiše osnovnu programsku cjelinu Kalendara kulture, njena osnovna svojstva, odnos prema održavanjima događaja, manifestaciji, organizatoru, kategoriji i lokaciji, te pravila arhiviranja i otkazivanja.

## 2. Poslovni opis

Događaj predstavlja osnovnu programsku cjelinu Kalendara kulture koja opisuje kulturni sadržaj. Događaj može imati jedno ili više održavanja.

## 3. Poslovni koncept

```text
Događaj
    │
    └── ima jedno ili više održavanja
            ├── ima termin (Datum održavanja je obavezan, a vrijeme može biti definisano.)
            ├── može imati lokaciju
            └── može imati status i druga svojstva
```

Događaj može biti kreiran bez definisanog održavanja isključivo dok još nije objavljen: u Moderatorskom toku dok je u statusu **Nacrt**, a u uredničkom toku dok je **U pripremi**. Za slanje događaja na odobrenje mora biti definisano najmanje jedno održavanje. Objavljeni događaj uvijek mora imati najmanje jedno održavanje.

Događaj može biti samostalan ili biti dio jedne manifestacije. Pripadnost manifestaciji nije obavezna.

Lokacija nije svojstvo događaja već svojstvo održavanja događaja. Svako održavanje može imati svoju lokaciju.

Događaj pripada jednoj primarnoj kategoriji. Dodatna klasifikacija događaja može se vršiti korišćenjem oznaka. Oznake nisu isto što i tagovi medija (BM-09). Događaj može biti sačuvan kao Nacrt (Moderator) ili U pripremi (Urednik) bez izabrane primarne kategorije. Za slanje događaja na odobrenje mora biti izabrana jedna primarna kategorija. Svaki objavljeni događaj mora imati jednu primarnu kategoriju.

Događaj registrovanog Organizatora povezan je sa tačno jednim registrovanim Organizatorom i kreira ga Moderator tog Organizatora. Urednik samostalno kreira Događaj bez veze sa registrovanim Organizatorom i može opciono unijeti ručno naziv neregistrovanog Organizatora (BM-UR-13 / BM-DG-12). Takav Događaj može biti objavljen i bez navedenog Organizatora. Po registraciji odgovarajućeg Organizatora već Objavljen događaj bez registrovanog Organizatora može se naknadno povezati sa Organizatorom kao administrativna dopuna podataka, bez izmjene audita, istorije događaja i javno objavljenih verzija.

Nakon završetka svih održavanja sistem automatski arhivira događaj, bez obzira da li je događaj u statusu Objavljen ili Otkazan. Arhiviranje se ne izvršava ručno.

Događaj može biti otkazan. Otkazani događaj ostaje evidentiran u sistemu, dobija status „Otkazan“ i tretira se kao istorijski zapis.

Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje. Deaktivacijom Organizatora moderatorski kontekst prestaje; Moderator tada više nema pravo otkazivanja događaja tog Organizatora. Urednik može otkazati bilo koji objavljeni događaj, uključujući događaje deaktiviranog Organizatora.

Pri prelazu Objavljen → Otkazan, u okviru iste poslovne operacije otkazivanja Događaja, sva Održavanja koja su u tom trenutku u statusu Planiran ili Odgođen automatski prelaze u status Otkazan. Održavanja koja su već u statusu Završen ili Otkazan ostaju nepromijenjena. Nakon te operacije na Otkazanom Događaju ne smije ostati Planirano niti Odgođeno Održavanje (PO-AUTO-01).

Iz statusa Otkazan nije dozvoljen povratak u status Objavljen. Ako se isti kulturni program kasnije ponovo organizuje, ne reaktivira se postojeći događaj; kreira se novi događaj kao novi zapis sa novim životnim ciklusom. Promjena termina postojećeg događaja koji nije otkazan vrši se isključivo kroz status Odgođen na održavanju, u skladu sa BM-06.

Nakon što je operacija otkazivanja Događaja (uključujući automatsko otkazivanje otvorenih Održavanja) završena, nije dozvoljena naknadna izmjena sadržajnih podataka događaja ni povezanih održavanja. Jedini izuzetak je razlog otkazivanja (napomena urednika), koji je opcion i koji Urednik može unijeti ili dopuniti. Ako je razlog unesen, može se javno prikazati kao napomena o otkazivanju, uz standardizovano sistemsko obavještenje (BM-DG-10 / BM-PK-36).

Pojedinačno održavanje događaja može biti otkazano bez uticaja na ostala održavanja istog događaja i bez prelaska cijelog Događaja u Otkazan. To pravilo ne isključuje automatsko otkazivanje svih otvorenih Održavanja pri otkazivanju cijelog Događaja (PO-AUTO-01).

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-DG-01 | Događaj može biti kreiran bez definisanog održavanja isključivo dok još nije objavljen: u Moderatorskom toku dok je u statusu **Nacrt**, a u uredničkom toku dok je **U pripremi**. Za slanje događaja na odobrenje mora biti definisano najmanje jedno održavanje. Objavljeni događaj uvijek mora imati najmanje jedno održavanje. |
| BM-DG-02 | Događaj može biti samostalan ili biti dio jedne manifestacije. Pripadnost manifestaciji nije obavezna. Detaljna pravila definišu se u BM-05 Manifestacija. |
| BM-DG-03 | Lokacija nije svojstvo događaja već svojstvo održavanja događaja. Svako održavanje može imati svoju lokaciju. Detaljna pravila definišu se u BM-07 Lokacija. |
| BM-DG-04 | Nakon završetka svih održavanja sistem automatski arhivira događaj. Automatsko arhiviranje primjenjuje se na događaj u statusu Objavljen i na događaj u statusu Otkazan. Arhiviranje se ne izvršava ručno. Pri prelazu u Arhiviran Sistem mora pouzdano sačuvati iz kojeg relevantnog javnog statusa (Objavljen ili Otkazan) je Događaj arhiviran, radi istorijskog javnog prikaza (BM-PK-35 / PO-6A09-02). Detaljna pravila prikaza Javne Arhive definišu se u BM-11 Portal Kalendara kulture (BM-PK-13 / BM-PK-35). |
| BM-DG-05 | Događaj može biti otkazan. Otkazani događaj ostaje evidentiran u sistemu i dobija status „Otkazan“. Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje. Deaktivacijom Organizatora moderatorski kontekst prestaje i Moderator više nema pravo otkazivanja događaja tog Organizatora; otkazivanje događaja deaktiviranog Organizatora izvršava isključivo Urednik. Urednik može otkazati bilo koji objavljeni događaj. Pri otkazivanju Događaja (Objavljen → Otkazan) primjenjuje se BM-DG-11. Pojedinačno održavanje događaja može biti otkazano bez uticaja na ostala održavanja istog događaja i bez prelaska cijelog Događaja u Otkazan; to ne isključuje BM-DG-11. Detaljna pravila za održavanja definišu se u BM-06 Održavanje događaja. |
| BM-DG-06 | Događaj pripada jednoj primarnoj kategoriji. Dodatna klasifikacija događaja može se vršiti korišćenjem oznaka. Oznake nisu isto što i tagovi medija (BM-09). Detaljna pravila o kategorijama i oznakama definišu se u BM-08 Kategorije i oznake. |
| BM-DG-07 | Događaj može biti sačuvan kao Nacrt (Moderator) ili U pripremi (Urednik) bez izabrane primarne kategorije. Za slanje događaja na odobrenje mora biti izabrana jedna primarna kategorija. Svaki objavljeni događaj mora imati jednu primarnu kategoriju. |
| BM-DG-08 | Događaj registrovanog Organizatora povezan je sa tačno jednim registrovanim Organizatorom. **Urednički tok:** Događaj koji kreira Urednik nije povezan sa registrovanim Organizatorom; Urednik ne bira registrovanog Organizatora u create toku (BM-UR-12). Takav Događaj može imati opcion ručno uneseni naziv neregistrovanog Organizatora (BM-DG-12) i može biti objavljen i bez ikakvog navedenog Organizatora. **Naknadna dopuna (BM-UR-07 / PO-DG-08 / PO-DG-09):** kada odgovarajući Organizator bude registrovan i Aktivan, Urednik može jednokratno i jednosmjerno povezati taj Objavljeni događaj sa Aktivnim Organizatorom. Ovo pravilo ne dozvoljava proizvoljnu kasniju promjenu Organizatora, uklanjanje veze niti prebacivanje na drugog Organizatora. |
| BM-DG-09 | Status Otkazan predstavlja terminalno stanje događaja u smislu povratka u Objavljen. Iz statusa Otkazan nije dozvoljen povratak u status Objavljen. Ako se isti kulturni program kasnije ponovo organizuje, ne reaktivira se postojeći događaj; kreira se novi događaj kao novi zapis sa novim životnim ciklusom. Promjena termina postojećeg događaja koji nije otkazan vrši se isključivo kroz status Odgođen na održavanju, u skladu sa BM-06. |
| BM-DG-10 | Događaj u statusu Otkazan tretira se kao istorijski zapis. Nakon završene operacije otkazivanja (uključujući BM-DG-11) nije dozvoljena naknadna izmjena naziva, opisa, Organizatora, kategorije, datuma, vremena, lokacije, fotografija niti drugih sadržajnih podataka događaja ili povezanih održavanja. Jedini izuzetak je razlog otkazivanja (napomena urednika). Razlog otkazivanja Događaja je **opcion**: nije uslov za otkazivanje; Urednik može unijeti ili dopuniti razlog. Ako je razlog unesen, **može se javno prikazati** kao napomena o otkazivanju, uz standardizovano sistemsko obavještenje (BM-PK-36). **PATCH-063 superseduje** ranije formulacije BM-DG-10 / BM-PK-33 / BM-UR-11 / BM-ST-07 / BM-PK-13 koje su zabranjivale javni prikaz teksta razloga ili zahtijevale zasebnu PO odluku za javni prikaz. Automatsko otkazivanje otvorenih Održavanja iz BM-DG-11 dio je same operacije otkazivanja Događaja i ne predstavlja zabranjenu naknadnu izmjenu. Terminalnost statusa Otkazan (BM-DG-09) ostaje neizmijenjena. Otkazan Događaj se ne briše (BM-UR-15 / BM-DG-13). |
| BM-DG-11 | Pri prelazu Događaja Objavljen → Otkazan, u okviru iste atomske poslovne operacije otkazivanja, sva Održavanja koja su u tom trenutku u statusu Planiran ili Odgođen automatski prelaze u status Otkazan. Održavanja u statusu Završen ili Otkazan ostaju nepromijenjena. Nakon operacije na Otkazanom Događaju ne smije ostati Planirano niti Odgođeno Održavanje. Ovo nije prelaz Planiran → Završen; to je posljedica otkazivanja roditeljskog Događaja (PO-AUTO-01). |
| BM-DG-12 | Ručno uneseni naziv neregistrovanog Organizatora je opcion tekstualni podatak Događaja u uredničkom toku. Nije registrovani Organizator, ne kreira zapis Organizatora, ne pokreće odobravanje i ne zamjenjuje vezu sa registrovanim Organizatorom. Kada postoji veza sa registrovanim Organizatorom, važi ta veza; ručni naziv nije alternativni način dodjele registrovanog Organizatora. |
| BM-DG-13 | Trajno brisanje Događaja dozvoljeno je isključivo ako Događaj nikada nije bio objavljen. Nakon prve objave brisanje nije dozvoljeno (uključujući Objavljen, Otkazan i Arhiviran). |

## 5. Otvorena pitanja

Nema otvorenih pitanja.

---

# BM-05 Manifestacija

**Status poglavlja:** USVOJENO

## 1. Svrha poslovne cjeline

Poslovna cjelina Manifestacija definiše programsku cjelinu koja grupiše Događaje pod zajedničkim identitetom, odnos prema Događajima i Organizatoru, nezavisni životni ciklus, način određivanja trajanja, uslove objave, postepeno dopunjavanje programa, te pravila uređivanja, odobravanja, otkazivanja i arhiviranja.

## 2. Poslovni opis

Manifestacija predstavlja zasebnu programsku cjelinu Kalendara kulture koja pod zajedničkim nazivom, identitetom i programskim okvirom objedinjuje jedan ili više povezanih Događaja.

Manifestacija = programska cjelina koja grupiše Događaje.
Događaj = sadržaj i programska stavka.
Održavanje = konkretan termin izvođenja.

## 3. Poslovni koncept

### 3.1 Odnos prema Događaju

Manifestacija može sadržati jedan ili više Događaja. Jedan Događaj može pripadati najviše jednoj Manifestaciji. Pripadnost Događaja Manifestaciji nije obavezna. Istovremeno povezivanje jednog Događaja sa više Manifestacija nije dozvoljeno. Promjena pripadnosti vrši se premještanjem Događaja iz jedne Manifestacije u drugu.

```text
Manifestacija 1 ───── N Događaj
Događaj 0..1 ───── 1 Manifestacija
```

Manifestacija može biti kreirana bez Događaja isključivo dok se nalazi u statusu Nacrt. Za slanje Manifestacije na odobrenje mora sadržati najmanje jedan Događaj.

### 3.2 Organizator

Organizator Manifestacije je opcioni podatak. Manifestacija može, ali ne mora biti povezana sa Organizatorom. Kada Organizator postoji, predstavlja nosioca ili partnera Manifestacije. Kada Organizator nije definisan, Manifestacija se smatra uredničkom odnosno platformskom Manifestacijom kojom upravlja Urednik.

Manifestacija može objedinjavati Događaje različitih Organizatora i Događaje bez Organizatora. Organizator Manifestacije ne mora biti isti kao Organizator svih pripadajućih Događaja. Nepostojanje Organizatora ne sprečava kreiranje, slanje na odobrenje, objavu, otkazivanje ili arhiviranje Manifestacije.

### 3.3 Održavanje, lokacija i kategorije

Manifestacija nema sopstvena održavanja. Održavanja imaju isključivo Događaji koji pripadaju Manifestaciji.

Lokacija ne pripada Manifestaciji; lokacija pripada održavanju događaja.

Manifestacija nema sopstvene kategorije. Kategorije pripadaju Događaju. Ako javni portal prikazuje kategorije povezane sa Manifestacijom, one mogu biti samo izvedene iz Objavljenih Događaja koje Manifestacija sadrži i nisu samostalno sačuvan atribut Manifestacije.

### 3.4 Trajanje

Trajanje Manifestacije ne unosi se ručno. Početak i završetak sistem određuje automatski iz važećih održavanja Objavljenih Događaja koji pripadaju Manifestaciji.

U izračun ulaze samo održavanja koja:

* pripadaju Objavljenim Događajima Manifestacije;
* nijesu u statusu Otkazan;
* nijesu u statusu Odgođen bez potvrđenog novog termina;
* imaju definisan datum (i vrijeme kada je uneseno; cjelodnevna održavanja ulaze po datumu u skladu sa BM-06).

Otkazana održavanja ne ulaze u izračun. Odgođena održavanja bez potvrđenog novog termina ne ulaze; nakon potvrde novog termina ponovo ulaze.

Početak određuje najranije važeće održavanje. Završetak određuje najkasnije važeće održavanje.

### 3.5 Životni ciklus

Statusi Manifestacije su:

1. Nacrt
2. Na odobrenju
3. Vraćena na doradu
4. Objavljena
5. Otkazana
6. Arhivirana

Manifestacija nema status **Odgođena**. Odgađanje pripada isključivo entitetu Održavanje. Manifestacija može ostati u statusu Objavljena i kada su pojedina održavanja njenih Događaja odgođena. Promjene termina prikazuju se kroz Događaje i Održavanja.

Manifestacija, Događaj i Održavanje imaju nezavisne životne cikluse. Promjena statusa Manifestacije ne mijenja automatski status Događaja niti Održavanja. Otkazivanje ili arhiviranje Manifestacije ne otkazuje i ne arhivira automatski Događaje niti Održavanja.

Objavljena Manifestacija arhivira se automatski nakon isteka planiranog trajanja (završetka važećih održavanja). Otkazana Manifestacija ostaje Otkazana do isteka planiranog trajanja Manifestacije, nakon čega je Sistem automatski arhivira. Arhiviranje ne mijenja statuse Događaja ni Održavanja. Manifestacija ostaje dostupna kroz arhivu i audit.

### 3.6 Objava i program

Manifestacija može biti objavljena samo kada ima najmanje jedan Događaj i najmanje jedan pripadajući Događaj ima status Objavljen.

Objavljena Manifestacija mora u svakom trenutku imati najmanje jedan Objavljeni Događaj. Nije dozvoljeno ukloniti niti premjestiti posljednji Objavljeni Događaj ako bi Manifestacija ostala bez javno dostupnog programa. Sistem odbija takvu radnju uz validacionu poruku. Ovo ne mijenja nezavisni životni ciklus Događaja.

Manifestacija može sadržati Događaje u različitim statusima. U programu Objavljene Manifestacije na javnom portalu prikazuju se Objavljeni Događaji; Otkazani Događaji ostaju prikazani uz oznaku „Otkazano“; završeni Objavljeni Događaji ostaju prikazani. Nacrti i događaji na odobrenju / vraćeni na doradu nisu javno vidljivi. Neobjavljeni Događaji mogu biti povezani sa Manifestacijom u uredničkom portalu. Program Manifestacije može se postepeno dopunjavati.

Objavljenoj Manifestaciji dozvoljeno je dodavanje i uklanjanje Događaja bez promjene statusa Manifestacije i bez ponovnog odobravanja Manifestacije, uz poštovanje uslova da ostane najmanje jedan Objavljeni Događaj. Svaki Događaj zadržava sopstveni životni ciklus. Novi Događaj mora proći svoj redovni urednički proces prije javne objave. Uklanjanje Događaja iz Manifestacije ne briše Događaj i ne mijenja njegov status.

### 3.7 Podaci Manifestacije

Manifestacija ima sopstvene podatke, uključujući naziv, opis, opcionu naslovnu fotografiju i opciono polje Web stranica / Više informacije. Manifestacija ne nasljeđuje ove podatke od Događaja. Naslovna fotografija je nezavisna od fotografija Događaja; sistem ne preuzima automatski fotografiju Događaja. Kada fotografija nije postavljena, javni portal koristi podrazumijevanu ilustraciju ili placeholder.

SEO slug nije poslovni zahtjev V1. Sistem može koristiti interni identifikator ili tehnički URL. Eksterni URL (Web stranica / Više informacije) ne zamjenjuje podatke o terminima i lokacijama u sistemu.

### 3.8 Kreiranje i uređivanje

Manifestaciju može kreirati Moderator organizatora u ime svog Organizatora. Urednik može kreirati Manifestaciju u ime bilo kojeg Organizatora ili bez registrovanog Organizatora, u skladu sa BM-03.

Manifestacija može biti sačuvana u statusu Nacrt i slobodno uređivana. Za slanje na odobrenje mora ispunjavati BM-MF-02 i ostala pravila ovog poglavlja.

Manifestacija može biti otkazana. Otkazivanje izvršava Moderator u aktivnom kontekstu Organizatora kojim upravlja Manifestacija (u ime tog Organizatora), isključivo za Manifestacije tog Organizatora. Urednik može otkazati bilo koju Manifestaciju. Administrator platforme nema redovnu poslovnu ulogu u otkazivanju. Otkazivanje ne briše Događaje i ne mijenja njihove statuse niti statuse Održavanja.

## 4. Poslovna pravila

| Oznaka | Pravilo |
|--------|---------|
| BM-MF-01 | Manifestacija predstavlja zasebnu programsku cjelinu Kalendara kulture koja pod zajedničkim nazivom, identitetom i programskim okvirom objedinjuje jedan ili više povezanih Događaja. |
| BM-MF-02 | Manifestacija može biti kreirana bez Događaja isključivo dok se nalazi u statusu Nacrt. Za slanje Manifestacije na odobrenje mora sadržati najmanje jedan Događaj. |
| BM-MF-03 | Manifestacija može sadržati jedan ili više Događaja. Jedan Događaj može pripadati najviše jednoj Manifestaciji. Pripadnost nije obavezna. Promjena pripadnosti vrši se premještanjem. Detaljna pravila za Događaje: BM-04. |
| BM-MF-04 | Manifestacija nema sopstvena održavanja. Održavanja imaju isključivo Događaji. Detaljna pravila: BM-06. |
| BM-MF-05 | Početak i završetak Manifestacije sistem određuje automatski iz važećih održavanja Objavljenih Događaja (isključujući Otkazana i Odgođena bez potvrđenog novog termina). Ručni unos trajanja nije poslovni zahtjev. |
| BM-MF-06 | Objavljena Manifestacija se automatski arhivira nakon isteka planiranog trajanja. Otkazana Manifestacija ostaje Otkazana do isteka planiranog trajanja, zatim je Sistem automatski arhivira. Arhiviranje se ne izvršava ručno. Arhiviranje Manifestacije ne arhivira automatski Događaje niti Održavanja. Prikaz arhive: BM-11. |
| BM-MF-07 | Manifestacija može biti otkazana i dobija status Otkazana. Moderator u aktivnom kontekstu Organizatora može otkazati Manifestaciju kojom taj Organizator upravlja. Urednik može otkazati bilo koju Manifestaciju. Administrator platforme nema redovnu poslovnu ulogu u otkazivanju. Otkazivanje ne briše Događaje i ne mijenja njihove statuse niti statuse Održavanja. |
| BM-MF-08 | Manifestacija ima sopstvene podatke (naziv, opis, opciona naslovna fotografija, opciono polje Web stranica / Više informacije). Ne nasljeđuje podatke od Događaja. Bez SEO slug-a kao poslovnog zahtjeva V1. Mediji: BM-09. |
| BM-MF-09 | Manifestaciju može kreirati Moderator u ime svog Organizatora. Urednik može kreirati Manifestaciju u ime bilo kojeg Organizatora ili bez Organizatora (BM-03). |
| BM-MF-10 | Manifestacija može biti sačuvana kao Nacrt i uređivana. Za slanje na odobrenje mora ispunjavati BM-MF-02 i ostala pravila ovog poglavlja. |
| BM-MF-11 | Statusi Manifestacije: Nacrt, Na odobrenju, Vraćena na doradu, Objavljena, Otkazana, Arhivirana. Nema statusa Odgođena. Odgađanje pripada Održavanju. |
| BM-MF-12 | Organizator Manifestacije je opcioni. Manifestacija može objedinjavati Događaje različitih Organizatora i Događaje bez Organizatora. Organizator MF ne mora biti isti kao Organizator svih Događaja. |
| BM-MF-13 | Objava Manifestacije zahtijeva najmanje jedan Događaj i najmanje jedan pripadajući Događaj u statusu Objavljen. U programu Objavljene Manifestacije na javnom portalu prikazuju se Objavljeni Događaji; Otkazani Događaji ostaju prikazani uz oznaku „Otkazano“; završeni Objavljeni Događaji ostaju prikazani. Nacrti i događaji na odobrenju / vraćeni na doradu nisu javno vidljivi. Program se može postepeno dopunjavati. |
| BM-MF-14 | Objavljenoj Manifestaciji dozvoljeno je dodavanje i uklanjanje Događaja bez promjene statusa Manifestacije i bez ponovnog odobravanja, uz uslov da Manifestacija zadrži najmanje jedan Objavljeni Događaj. Uklanjanje ne briše Događaj niti mijenja njegov status. |
| BM-MF-15 | Životni ciklusi Manifestacije, Događaja i Održavanja su nezavisni. Promjena statusa Manifestacije ne mijenja automatski statuse Događaja ni Održavanja. |
| BM-MF-16 | Manifestacija nema sopstvene kategorije ni lokacije. Kategorije pripadaju Događaju; lokacija pripada Održavanju. Izvedeni prikaz kategorija na portalu nije sačuvan atribut Manifestacije. |
| BM-MF-17 | Naslovna fotografija Manifestacije je opciona (najviše jedna) i nezavisna od fotografija Događaja. Bez automatske zamjene fotografijom Događaja. |
| BM-MF-18 | Polje Web stranica / Više informacije je opciono i može sadržati eksterni URL. Ne zamjenjuje termine ni lokacije u sistemu. |
| BM-MF-19 | Objavljena Manifestacija mora u svakom trenutku imati najmanje jedan Objavljeni Događaj. Uklanjanje ili premještanje posljednjeg Objavljenog Događaja nije dozvoljeno; Sistem odbija radnju. |
| BM-MF-20 | Manifestacija je ravnopravan poslovni entitet. Poslovno značajne aktivnosti nad Manifestacijom vode se u centralnoj Evidenciji aktivnosti (BM-14), u skladu sa katalogom u Functional Specification. |

## 5. Otvorena pitanja

Nema otvorenih poslovnih pitanja.

Napomena (N-MF-05, nije Product Owner odluka): Manifestacija ulazi u centralnu Evidenciju aktivnosti kao ravnopravan poslovni entitet; detaljan katalog stavki definiše Functional Specification / TS-005.

---

# BM-06 Održavanje događaja

**Status poglavlja:** USVOJENO

Napomena o oznakama pravila: identifikatori `BM-TR-*` zadržani su kao istorijske tehničke oznake radi stabilnosti referenci. Tekst pravila više ne definiše Termin kao poslovni entitet; opisuju **održavanje događaja**. Pojam **Termin** u ovom poglavlju označava: Datum održavanja je obavezan, a vrijeme može biti definisano.

## 1. Svrha

Svrha ovog poglavlja je definisanje poslovnog koncepta održavanja događaja kao jednog konkretnog održavanja jednog događaja, uključujući njegov termin (Datum održavanja je obavezan, a vrijeme može biti definisano.), lokaciju, ponavljanje, status i druga osnovna poslovna svojstva.

## 2. Poslovni opis

Održavanje događaja predstavlja jedno konkretno održavanje jednog događaja. Jedan događaj može imati jedno ili više održavanja.

Održavanje se ne posmatra kao samostalan programski sadržaj, već uvijek pripada jednom događaju.

Svako održavanje ima termin. Datum održavanja je obavezan, a vrijeme može biti definisano. Termin nije samostalan poslovni entitet.

## 3. Poslovni koncept

```text
Događaj 1 ───── 1..N Održavanja događaja
                      ├── Termin (Datum održavanja je obavezan, a vrijeme može biti definisano.)
                      └── Lokacija (opciono)
```

Održavanje omogućava da se za jedan događaj evidentira jedno ili više konkretnih održavanja, uključujući cjelodnevna, ponavljajuća, izmijenjena, odgođena ili otkazana održavanja.

Svako održavanje ima sopstveni termin i status, dok lokacija može biti opciona.

## 4. Poslovna pravila

### BM-TR-01 — Definicija održavanja događaja

> Održavanje događaja predstavlja jedno konkretno održavanje jednog događaja, sa sopstvenim terminom (Datum održavanja je obavezan, a vrijeme može biti definisano.) i, po potrebi, lokacijom. Jedan događaj može imati jedno ili više održavanja.

### BM-TR-02 — Veza održavanja i događaja

> Održavanje uvijek pripada jednom događaju. Održavanje ne može postojati samostalno niti može biti povezano sa više događaja.

### BM-TR-03 — Termin održavanja

> Datum održavanja je obavezan, a vrijeme može biti definisano. Termin nije samostalan poslovni entitet. Ostali podaci održavanja uređuju se posebnim poslovnim pravilima i mogu biti opcioni.

### BM-TR-04 — Lokacija održavanja

> Održavanje može biti definisano bez lokacije. Kada je lokacija definisana, ona predstavlja svojstvo održavanja i uređuje se u skladu sa poslovnim pravilima definisanim u BM-07 Lokacija.

### BM-TR-05 — Cjelodnevno održavanje

> Održavanje može biti označeno kao cjelodnevno. Za cjelodnevni događaj definiše se samo datum održavanja.

### BM-TR-06 — Ponavljanje i više održavanja

> Održavanja događaja mogu biti kreirana kao pojedinačna (ručno) ili kroz jednokratno pravilo ponavljanja koje **generiše** više održavanja jednog događaja. Serija / pravilo ponavljanja **nije** poslovni entitet, nema lifecycle ni status i ne ostaje kao trajni objekat nakon generisanja.
>
> **Obuhvat V1 (PO-N-TR-02-04):** Generator je dostupan **isključivo** dok je Događaj u statusu **Nacrt** (uključujući Nacrt vraćen na doradu). Nije dostupan na Na odobrenju, Objavljen, Otkazan niti Arhiviran. U V1 generator se **ne** povezuje sa Prijedlogom izmjena Objavljenog.
>
> Sistem u V1 podržava isključivo **dnevno** (+1 kalendarski dan), **sedmično** (+7 kalendarskih dana / isti dan sedmice) i **mjesečno** (izvorni broj dana u mjesecu; na kraćem mjesecu clamp na posljednji dan, bez trajne promjene izvornog ciljnog dana), kao i ručno dodavanje. Početni datum je obavezan i uvijek prvo Održavanje. Završetak: **tačno jedan** od uslova — **broj** Održavanja **ili** **krajnji datum** (XOR). Krajnji datum je inkluzivan. Po jednom generisanju najviše **100** Održavanja; prelazak limita odbija cijelu operaciju (bez djelimičnog rezultata).
>
> Generisanje je **atomska** operacija (sva ili nijedno). Potpuno identičan termin (isti datum, vremena, cjelodnevnost i Lokacija) na istom Događaju ili unutar iste operacije **odbija** cijelu operaciju. Sva generisana Održavanja dobijaju status **Planiran** i dijele zajednički šablon vremena/lokacije. Bez preview faze u V1.
>
> Van V1: RRULE, beskonačne serije, intervali (npr. svake dvije sedmice), napredna kalendarska pravila, trajna pravila ponavljanja, Edit entire series, Regenerate, generator na Objavljenom / Proposal toku.
>
> Svako generisano ili ručno dodato održavanje dobija sopstveni termin. Nakon generisanja nastaju **nezavisna** održavanja; sistem **ne** razlikuje ručno dodato od generisanog — sva čine jedinstvenu listu održavanja događaja.

### BM-TR-07 — Izuzeci nad pojedinačnim održavanjem

> Pojedinačno održavanje (bez obzira na način nastanka) može biti izmijenjeno ili otkazano bez uticaja na ostala održavanja istog događaja. Izmjene i otkazivanja primjenjuju se isključivo na odabrano održavanje. Pomjeranje znači promjenu termina (datuma i/ili vremena) jednog održavanja.
>
> Ne postoji izmjena „cijele serije“, regeneracija niti ponovno pokretanje generatora nad postojećim održavanjima. Nakon generisanja nema trajne „serijske“ veze koja upravlja budućim ponašanjem.

### BM-TR-08 — Izmjena objavljenog održavanja

> Održavanje objavljenog događaja može se izmijeniti (uključujući promjenu termina, lokacije ili drugih podataka održavanja). Izmjene podataka održavanja, osim postavljanja statusa **Planiran**, **Odgođen** i **Otkazan** uređenih pravilima BM-TR-16 i BM-TR-17, podliježu istim pravilima uređivanja i odobravanja koja važe za događaj, u skladu sa poslovnim pravilima definisanim u BM-03 Urednik.

### BM-TR-09 — Status održavanja

> Svako održavanje ima vlastiti status, nezavisno od ostalih održavanja istog događaja. Status održavanja određuje njegovo trenutno stanje i može biti različit od statusa drugih održavanja događaja. Status održavanja nije status događaja.

### BM-TR-10 — Dozvoljeni statusi održavanja

> Održavanje može imati jedan od sljedećih statusa:
>
> * **Planiran** — održavanje je aktivno i biće održano prema objavljenim podacima.
> * **Odgođen** — održavanje neće biti održano u prvobitnom terminu. Novi termin **nije** obavezan u trenutku odgađanja; može ostati nepoznat dok se ne odredi. Prvobitni termin ostaje istorijski podatak. Razlog odgađanja je opcion.
> * **Otkazan** — održavanje neće biti održano.
> * **Završen** — održavanje je održano ili je prošao njegov termin.
>
> Status **Završen** Sistem dodjeljuje automatski isključivo iz statusa **Planiran**, nakon što je termin istekao (PO-AUTO-02):
>
> * ako je definisano vrijeme završetka (`vrijeme_do`) — nakon trenutka **datum + vrijeme_do** u aplikacionoj vremenskoj zoni;
> * ako `vrijeme_do` nije definisano (uključujući samo datum, datum uz samo vrijeme početka, ili cjelodnevno održavanje) — nakon **završetka kalendarskog dana** polja `datum` u aplikacionoj vremenskoj zoni.
>
> Sistem ne dodjeljuje status Završen Održavanjima u statusu Odgođen ili Otkazan. Odgođeno Održavanje mora prvo preći u Planiran (novi termin) ili Otkazan.

### BM-TR-11 — Ulaznice i cijena

> Upravljanje informacijama o ulaznicama i cijeni nije dio poslovnog opsega verzije V1.

### BM-TR-12 — Odgođen pripada održavanju

> Status **Odgođen** odnosi se isključivo na održavanje događaja. Status **Odgođen** nije status događaja.
>
> Status **Odgođen**, uz povratak u status **Planiran** nakon određivanja novog termina, predstavlja jedini poslovni mehanizam za promjenu termina postojećeg događaja. Otkazani događaj se ne vraća u status Objavljen radi novog termina niti radi ponovne organizacije istog programa.

### BM-TR-13 — Tranzicije iz statusa Planiran

> Iz statusa **Planiran** održavanje može preći u status:
>
> * **Odgođen**;
> * **Otkazan**;
> * **Završen**.

### BM-TR-14 — Tranzicije iz statusa Odgođen

> Iz statusa **Odgođen** održavanje može preći u status:
>
> * **Planiran**, nakon određivanja novog termina;
> * **Otkazan**.
>
> Prelaz u Odgođen **ne** zahtijeva unos novog datuma. Druge tranzicije iz statusa **Odgođen** nisu dozvoljene.

### BM-TR-15 — Povratak iz statusa Odgođen u Planiran

> Prilikom prelaska iz statusa **Odgođen** u status **Planiran** radi se o istom održavanju događaja. Novo održavanje se ne kreira. Unosi se novi datum/vrijeme; ne kreira se novi Događaj samo zato što je termin pomjeren. Istorija održavanja ostaje sačuvana (uključujući evidenciju aktivnosti gdje je primjenjivo).

### BM-TR-16 — Ovlašćenja za status održavanja sa registrovanim Organizatorom

> Kada održavanje pripada događaju sa registrovanim Organizatorom:
>
> * Moderator može u ime Organizatora zatražiti odgađanje ili promjenu termina.
> * Organizator ne mijenja direktno status objavljenog održavanja (Organizator nije korisnik i ne izvršava radnje u sistemu).
> * Moderator postavlja status **Odgođen**, **Planiran** (nakon određivanja novog termina) i **Otkazan**, u skladu sa poslovnim pravilima tranzicija statusa održavanja.

### BM-TR-17 — Ovlašćenja za status održavanja bez registrovanog Organizatora

> Kada održavanje pripada događaju bez registrovanog Organizatora, ista ovlašćenja za postavljanje statusa **Odgođen**, **Planiran** (nakon određivanja novog termina) i **Otkazan** ima Urednik.

### BM-TR-18 — Obuhvat ovlašćenja za status održavanja

> Pravila BM-TR-16 i BM-TR-17 odnose se isključivo na status održavanja. Ne mijenjaju status događaja niti postojeći urednički workflow događaja.

### BM-TR-19 — Odgađanje bez poznatog novog termina

> Održavanje može biti označeno kao **Odgođeno** i kada novi termin još nije poznat. Novi datum nije obavezan u trenutku odgađanja. Prvobitni termin ostaje istorijski podatak. Razlog odgađanja je **opcion**; ako je unesen, može se javno prikazati kao napomena (BM-PK-36).

### BM-TR-20 — Otkazivanje pojedinačnog Održavanja

> Ako se otkaže jedno Održavanje:
>
> * samo to Održavanje dobija status **Otkazan**;
> * ostala aktivna Održavanja ostaju nepromijenjena;
> * cijeli Događaj **ne** dobija status Otkazan samo zbog jednog otkazanog Održavanja;
> * otkazano Održavanje više se ne tretira kao predstojeće aktivno Održavanje;
> * prvobitni datum ostaje vidljiv;
> * razlog otkazivanja Održavanja je **opcion**; ako je unesen, može se javno prikazati kao napomena (BM-PK-36).
>
> Ovo ne isključuje BM-DG-11 pri otkazivanju cijelog Događaja.

## 5. Otvorena pitanja

Za poglavlje BM-06 trenutno nema otvorenih poslovnih pitanja.

Teme koje nijesu obuhvaćene ovim poglavljem ne treba dodavati bez nove, izričito usvojene poslovne odluke i narednog numerisanog PATCH-a.

---

# BM-07 Lokacija

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnog koncepta Lokacije kao samostalnog poslovnog entiteta opcionog centralnog kataloga za ponovno korišćenje Lokacija, pravila njenog korišćenja kroz Događaje i Održavanja, poslovnih ovlašćenja, životnog ciklusa, jedinstvenosti, referencijalnog integriteta i audita.

## 2. Poslovni opis

Lokacija predstavlja mjesto na kojem se događaj konkretno održava. Kataloška Lokacija vodi se kao samostalan entitet opcionog centralnog kataloga Lokacija.

Lokacija pripada održavanju događaja, a ne terminu, u skladu sa usvojenim poslovnim pravilima. Događaj ili Održavanje mogu koristiti katalošku Lokaciju putem stabilnog identifikatora ili ručno unijeti naziv Lokacije bez obavezne veze sa katalogom.

Organizator je poslovni entitet i nosilac sadržaja, ali nije korisnik sistema i ne izvršava operativne radnje. Operativne radnje nad Lokacijama izvršavaju Moderatori i Urednici u skladu sa ovlašćenjima.

## 3. Poslovni koncept

Centralni katalog Lokacija predstavlja opcioni katalog za ponovno korišćenje često korišćenih Lokacija.

Jedna Lokacija može biti korišćena kroz više Događaja i više Održavanja.

Moderator može odabrati postojeću Lokaciju iz kataloga ili ručno unijeti naziv Lokacije kada odgovarajuća Lokacija ne postoji u katalogu. Korišćenje kataloga nije obavezno za kreiranje ili uređivanje Događaja i Održavanja.

## 4. Poslovna pravila

### BM-LK-01 — Definicija lokacije

> Kataloška Lokacija je samostalan poslovni entitet opcionog centralnog kataloga Lokacija i predstavlja mjesto održavanja događaja. Moderator može odabrati postojeću Lokaciju iz kataloga ili ručno unijeti naziv Lokacije. Korišćenje kataloga nije obavezno za kreiranje ili uređivanje Događaja i Održavanja.

### BM-LK-02 — Ponovna upotreba lokacije

> Jedna Lokacija može biti povezana sa jednim ili više održavanja različitih događaja i koristiti se kroz više događaja. Lokacija se koristi kao zajednički poslovni entitet i ne kreira se ponovo za svako održavanje.

### BM-LK-03 — Naziv lokacije

> Lokacija mora imati naziv. Ostali podaci o Lokaciji uređuju se posebnim poslovnim pravilima i mogu biti opcioni.

### BM-LK-04 — Naknadno određivanje lokacije

> Lokacija može biti definisana ili određena naknadno, u skladu sa potrebama organizacije događaja. Ručno uneseni naziv Lokacije može naknadno biti predložen za unos u katalog radi buduće ponovne upotrebe.

### BM-LK-05 — Aktivnost lokacije

> Kataloška Lokacija ima status **Aktivna** ili **Deaktivirana**. Samo Aktivna kataloška Lokacija može se birati za nove veze iz Događaja i Održavanja. Deaktivirana kataloška Lokacija ostaje povezana sa postojećim istorijskim vezama.

### BM-LK-06 — Jedinstvenost lokacije

> Identične Lokacije nijesu dozvoljene u centralnom katalogu. Sistem provjerava postojeće Lokacije. Mogući duplikati prijavljuju se Uredniku, a konačnu odluku o postupanju donosi Urednik.

### BM-LK-07 — Ovlašćenja nad lokacijama

> Organizator nije operativna uloga i ne kreira, ne predlaže, ne uređuje niti odobrava Lokacije. Moderator predlaže Lokacije u ime Organizatora. Urednik odobrava, odbija, vraća na doradu, uređuje katalog Lokacija, rješava moguće duplikate, deaktivira i ponovo aktivira Lokacije. Administrator platforme nema redovnu poslovnu ulogu u upravljanju Lokacijama.

### BM-LK-08 — Fizičko brisanje lokacije

> Fizičko brisanje Lokacije nije dio redovnog poslovnog procesa.

### BM-LK-09 — Referencijalni integritet

> Referenca na centralni katalog Lokacija je opciona. Kada Događaj ili Održavanje koriste katalošku Lokaciju, veza se čuva putem stabilnog identifikatora i sistem čuva referencijalni integritet te veze. Kada je Lokacija unesena ručno, referenca na katalog nije obavezna i odsustvo kataloške reference nije povreda referencijalnog integriteta.

### BM-LK-10 — Spajanje (merge) lokacija

> Spajanje (merge) primjenjuje se samo na Lokacije koje postoje u katalogu. Merge automatski preusmjerava postojeće kataloške reference sa izvorne na ciljnu Lokaciju i mora biti atomska operacija bez djelimično preusmjerenih referenci. Ručno uneseni tekst Lokacije ne mijenja se automatski kroz merge kataloga.

### BM-LK-11 — Audit lokacija

> Sistem vodi istoriju nad Lokacijama za najmanje: kreiranje, izmjenu, odobrenje, odbijanje, vraćanje na doradu, deaktivaciju, aktivaciju i spajanje. Evidencija sadrži najmanje datum i vrijeme, korisnika, vrstu radnje, staru vrijednost i novu vrijednost. Jednom evidentirani audit zapis nije moguće mijenjati niti brisati kroz redovno korišćenje sistema i ne predstavlja rollback mehanizam.

### BM-LK-12 — Opseg V1

> U V1 opsegu Lokacija podržane su isključivo fizičke Lokacije. Online i hibridne Lokacije nijesu dio V1 i zahtijevaju novu Product Owner odluku.

## 5. Otvorena pitanja

Za poglavlje BM-07 trenutno nema otvorenih poslovnih pitanja.

---

# BM-08 Kategorije i oznake

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnih pravila za klasifikaciju događaja kroz Kategorije i Oznake kao proširive poslovne kataloge, njihova ovlašćenja, životni ciklus i odnos prema Događaju.

## 2. Poslovni opis

Kategorije predstavljaju osnovnu poslovnu klasifikaciju događaja i vode se kao zapisi **poslovnog kataloga**. Kategorije ne predstavljaju tehničku ENUM listu. Katalog kategorija je proširiv.

Oznake predstavljaju dodatnu klasifikaciju događaja. Oznake ulaze u V1. Oznake nisu zamjena za primarnu kategoriju. Jedan događaj može imati više oznaka.

Kategorije i oznake definišu se kao novi poslovni katalog. Ne radi se migracija postojećih test podataka; postojeće test kategorije nisu referentni poslovni podaci. Ne uvodi se kompatibilnost sa starim ENUM/string modelom niti tranzicioni model.

Kategorija „Nešto drugo“ više ne postoji u poslovnom modelu. Ako nijedna postojeća kategorija nije odgovarajuća, Urednik proširuje katalog novom kategorijom.

**TS7-PO-07:** Product Owner je usvojio **konačni početni V1 katalog** od 14 kategorija Događaja (sa redoslijedom). To je početni poslovni sadržaj kataloga za V1 / cutover režim — **nije** tehnička ENUM lista. Katalog ostaje proširiv (BM-KO-01, BM-KO-07). Tehnički cutover i mapiranje postojećih legacy podataka pripadaju **TS-009** / Roadmap Fazi 6.

## 3. Poslovni koncept

Događaj može biti sačuvan kao nacrt bez primarne kategorije. Za slanje na odobrenje i za objavu mora imati tačno jednu primarnu kategoriju iz kataloga. Događaj može imati nula ili više oznaka iz kataloga oznaka.

Organizator je poslovni entitet i nije operativna uloga. Moderator je poslovno ovlašćenje koje pri uređivanju događaja bira postojeće Aktivne kategorije i oznake. Katalogom kategorija i katalogom oznaka upravlja isključivo Urednik. Administrator platforme nema redovnu poslovnu ulogu u upravljanju ovim katalozima.

Ne uvodi se workflow za predlaganje kategorija ili oznaka, dodatni statusi odobravanja ni dodatna ovlašćenja.

**Kategorija odgovara na pitanje „koja je vrsta ovog Događaja?“.** Manifestacija je šira programska cjelina; tip Organizatora nije kategorija Događaja (BM-KO-10).

## 4. Poslovna pravila

### BM-KO-01 — Poslovni katalog

> Kategorije i oznake predstavljaju poslovnu klasifikaciju sadržaja koja omogućava organizaciju, pretragu, filtriranje i prikaz događaja na javnom portalu. Kategorije se vode kao zapisi poslovnog kataloga. Kategorije ne predstavljaju tehničku ENUM listu. Katalog kategorija i katalog oznaka su proširivi.

### BM-KO-02 — Primarna kategorija

> Događaj može biti povezan sa tačno jednom primarnom kategorijom iz kataloga. Primarna kategorija je obavezna prije slanja na odobrenje i prije objavljivanja događaja. U statusu Nacrt događaj može biti sačuvan bez primarne kategorije.

### BM-KO-03 — Oznake

> Oznake ulaze u V1. Događaj može biti povezan sa jednom ili više oznaka iz kataloga oznaka. Oznake su opcione i služe za dodatnu klasifikaciju i pretragu sadržaja. Oznake nisu zamjena za primarnu kategoriju.

### BM-KO-04 — Upravljanje katalogom

> Katalogom kategorija upravlja isključivo Urednik. Katalogom oznaka upravlja isključivo Urednik. Moderator koristi postojeće kategorije i oznake prilikom uređivanja događaja i ne upravlja katalogom. Organizator nije operativna uloga i ne upravlja katalogom. Administrator platforme nema redovnu poslovnu ulogu u upravljanju katalogom kategorija ni oznaka. Ne uvodi se workflow za predlaganje kategorija ili oznaka, dodatni statusi odobravanja ni dodatna ovlašćenja.

### BM-KO-05 — Životni ciklus

> Kategorija ili oznaka ima status **Aktivna** ili **Neaktivna**. Nova kategorija i nova oznaka kreiraju se sa statusom Aktivna. Dozvoljena je ponovna aktivacija (reaktivacija). Fizičko brisanje nije dio redovnog poslovnog procesa.

### BM-KO-06 — Deaktivacija i istorija

> Neaktivna kategorija ili oznaka ne može se dodijeliti novom događaju niti koristiti za nove veze. Deaktivacija ne mijenja istorijske podatke. Postojeći događaji zadržavaju referencu na kategoriju ili oznaku koja je kasnije deaktivirana.

### BM-KO-07 — Proširenje kataloga umjesto „Nešto drugo“

> Kategorija „Nešto drugo“ ne postoji u poslovnom modelu. Ako nijedna postojeća kategorija nije odgovarajuća, Urednik proširuje katalog novom kategorijom. Oznake ne predstavljaju zamjenu za kategoriju.

### BM-KO-08 — Novi katalog bez migracije test podataka

> Kategorije i oznake definišu se kao novi poslovni katalog. Ne radi se migracija postojećih test podataka. Ne uvodi se kompatibilnost sa starim ENUM/string modelom. Ne pravi se tranzicioni model. Postojeće test kategorije nisu referentni poslovni podaci.

### BM-KO-09 — Konačni početni V1 katalog kategorija Događaja (TS7-PO-07)

> Product Owner je usvojio konačni početni V1 katalog kategorija Događaja. Redoslijed je usvojen. Katalog ostaje proširiv zapisima koje kreira Urednik; ova lista nije tehnička ENUM lista niti zabranjuje kasnije dodavanje novih kategorija po BM-KO-01 / BM-KO-07.
>
> Usvojeni početni V1 redoslijed (1–14):
>
> 1. Koncerti
> 2. Predstave
> 3. Sportski događaji
> 4. Izložbe
> 5. Književni programi
> 6. Filmske projekcije
> 7. Dječiji programi
> 8. Konferencije
> 9. Radionice
> 10. Publikacije
> 11. Performansi
> 12. Prezentacije i predavanja
> 13. Paneli i tribine
> 14. Sajmovi
>
> Poslovna značenja (sažeto):
>
> * **Koncerti** — dominantno muzička izvođenja pred publikom (solisti, bendovi, ansambli, horovi i srodno). Koncert unutar Manifestacije ostaje kategorija Koncerti.
> * **Predstave** — strukturisana scenska izvođenja (pozorišna, dramska, komična, lutkarska, plesna i srodna). Performans je zasebna kategorija.
> * **Sportski događaji** — dominantni sadržaj je sportska aktivnost, takmičenje, susret ili drugi sportski program.
> * **Izložbe** — organizovano javno izlaganje (umjetničko, fotografsko, istorijsko, muzejsko, kulturno, multimedijalno i srodno). „Likovne manifestacije“ nijesu kategorija Događaja.
> * **Književni programi** — književne večeri, razgovori sa piscima, čitanja, susreti autora i publike i srodni programi književnog stvaralaštva. Razlikovati od **Publikacije**.
> * **Filmske projekcije** — projekcije filmova i odgovarajućih audiovizuelnih djela pred publikom. Filmski festival nije kategorija Događaja (Manifestacija); pojedinačne projekcije = Filmske projekcije.
> * **Dječiji programi** — programi prvenstveno namijenjeni djeci kao poseban/mješovit format, samo ako nijesu prirodnije klasifikovani drugom kategorijom (npr. dječija predstava → Predstave; dječija radionica → Radionice; dječija projekcija → Filmske projekcije).
> * **Konferencije** — konferencije, simpozijumi, stručni skupovi i srodni konferencijski programi. Razlikovati od pojedinačnih **Panela i tribina**. Šira višednevna cjelina može biti Manifestacija.
> * **Radionice** — dominantno aktivno/praktično učešće (kreativne, umjetničke, edukativne, zanatske i srodne). Bez podkategorija u V1.
> * **Publikacije** — dominantni sadržaj je predstavljanje konkretne publikacije (knjiga, monografija, časopis, zbornik, katalog, stručno izdanje i srodno). Zamjenjuje legacy naziv „Promocije publikacija“.
> * **Performansi** — performativni umjetnički programi koji nijesu klasične strukturisane predstave. Razlikovati od **Predstava**.
> * **Prezentacije i predavanja** — dominantan format je izlaganje/predstavljanje teme, projekta, istraživanja, znanja ili kulturnog/istorijskog sadržaja. Zamjenjuje legacy „Prezentacije“. Razlikovati od panela/tribina i radionica.
> * **Paneli i tribine** — moderirana diskusija, razgovor, razmjena stavova, panel, tribina i srodni diskusioni formati (ne samo „o kulturi“). Zamjenjuje legacy „Paneli o kulturi“.
> * **Sajmovi** — sajamski događaji (knjige, umjetnost, rukotvorine, tradicionalni proizvodi i srodno). Šira višednevna cjelina sa više Događaja može biti Manifestacija.

### BM-KO-10 — Kategorija ≠ Manifestacija; kategorija ≠ tip Organizatora

> Kategorija klasifikuje **vrstu Događaja**. Manifestacija je šira programska cjelina kojoj Događaj može pripadati; nije kategorija Događaja. Tip ili naziv Organizatora (npr. Mjesna zajednica, NVU) nije kategorija Događaja — pripada modelu Organizatora.
>
> Primjeri: projekcija unutar festivala → **Filmske projekcije**; izložba unutar manifestacije → **Izložbe**; koncert unutar manifestacije → **Koncerti**; radionica unutar manifestacije → **Radionice**.

### BM-KO-11 — Legacy vrijednosti koje se ne prenose kao kanonske V1 kategorije

> Sljedeće legacy string vrijednosti **nijesu** kanonske V1 kategorije Događaja: Filmski festivali; Likovne manifestacije; Manifestacije u organizaciji Mjesnih zajednica; Manifestacije u organizaciji NVU; Nešto drugo (već zabranjeno BM-KO-07).
>
> Semantičko mapiranje (PO, ne tehnička migracija / ne runtime adapter): Koncerti→Koncerti; Predstave→Predstave; Sportski događaji→Sportski događaji; Izložbe→Izložbe; Književne večeri→Književni programi; Filmske projekcije→Filmske projekcije; Radionice→Radionice; Promocije publikacija→Publikacije; Performansi→Performansi; Prezentacije→Prezentacije i predavanja; Paneli o kulturi→Paneli i tribine. Nove kategorije bez legacy ekvivalenta: Dječiji programi; Konferencije; Sajmovi (bez automatskog mapiranja).
>
> **Faza 6A / javni cutover (PO-EV-01):** legacy `CulturalEvent` zapisi su isključivo testni. **Ne** migrira se legacy sadržaj; **ne** uvodi se dual-read/dual-write; **ne** uvodi se URL/legacy alias mapa kategorija; javni portal koristi isključivo kanonski katalog `CulturalCategory` (BM-PK-32). Semantičko mapiranje iznad ostaje referentno za razumijevanje rename-a, ne kao produkcijski adapter.

## 5. Odnosi sa drugim poslovnim cjelinama

- **Događaj** — referencira jednu primarnu kategoriju (obavezno za slanje/objavu) i opciono više oznaka.
- **Urednik** — isključivo upravlja katalogom kategorija i katalogom oznaka.
- **Moderator** — bira postojeće Aktivne kategorije i oznake pri uređivanju događaja; ne upravlja katalogom.
- **Organizator** — poslovni entitet; nije operativna uloga nad katalogom; tip Organizatora nije kategorija Događaja.
- **Manifestacija** — nije kategorija Događaja; Događaji unutar Manifestacije zadržavaju vlastitu kategoriju vrste.
- **Administrator platforme** — nema redovnu poslovnu ulogu nad katalogom; sistemska administracija.
- **TS-009 / javni portal** — Faza 6A cutover: kanonski katalog `CulturalCategory` (BM-PK-32); bez migracije legacy sadržaja (PO-EV-01 / BM-KO-11).

## 6. Otvorena pitanja

Za poglavlje BM-08 trenutno nema otvorenih poslovnih pitanja (javni CAT-CUTOVER = TS-009 Faza 6A).

---

# BM-09 Mediji

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnog koncepta medija, zatvorenog kataloga namjena, povezivanja sa poslovnim entitetima, životnog ciklusa, ovlašćenja, metapodataka i prikaza u modulu Kalendara kulture.

## 2. Poslovni opis

Medij je **samostalan poslovni entitet** i **zajednički platformski resurs**. Medij **nema poslovnog vlasnika**.

Jedini poslovni tip medija u V1 je **Fotografija**.

Medij ima tačno jednu poslovnu namjenu iz zatvorenog kataloga namjena. Namjena nije isto što i tip medija, format datoteke, ekstenzija ili MIME tip.

## 3. Poslovni koncept

U V1 mediji služe vizuelnom predstavljanju događaja, manifestacija i kategorija kroz tri namjene:

1. Naslovna fotografija događaja (cover događaja);
2. Naslovna fotografija manifestacije (cover manifestacije);
3. Podrazumijevana fotografija kategorije.

Upload medija vrši se isključivo tokom uređivanja događaja, manifestacije ili kategorije. Ne postoji zaseban poslovni ekran isključivo za upload.

Kreator (creator) medija služi isključivo auditu, istoriji i logovima — nije vlasništvo.

## 4. Poslovna pravila

### BM-MD-01 — Definicija medija

> Medij je samostalan poslovni entitet tipa Fotografija u modulu Kalendara kulture. Medij je zajednički platformski resurs i nema poslovnog vlasnika.

### BM-MD-02 — Povezivanje medija

> Jedan medij može biti povezan sa jednim ili više poslovnih entiteta u skladu sa svojom namjenom: događaji (naslovna fotografija događaja), manifestacije (naslovna fotografija manifestacije) ili kategorije (podrazumijevana fotografija kategorije). U V1 ne postoje poslovne veze medija sa lokacijama niti organizatorima.

### BM-MD-03 — Namjena medija

> Medij ima tačno jednu poslovnu namjenu iz zatvorenog kataloga. Katalog namjena nije korisnički konfigurabilan i ne uređuje se kroz aplikaciju. Proširenje kataloga moguće je isključivo novom Product Owner odlukom i odgovarajućim PATCH-om dokumentacije. Namjene V1: Naslovna fotografija događaja; Naslovna fotografija manifestacije; Podrazumijevana fotografija kategorije. Isti medij-zapis ne može istovremeno imati dvije različite namjene.

### BM-MD-04 — Aktivnost medija

> Medij ima status **Aktivan** ili **Neaktivan**. Soft delete se ne koristi. Neaktivan medij ne može dobiti nova poslovna povezivanja, ali ostaje povezan sa postojećim entitetima i nastavlja da se prikazuje kroz postojeće veze dok se veza ne ukloni ili medij ne zamijeni. Dozvoljena je reaktivacija (Neaktivan → Aktivan). Trajno brisanje medija dozvoljeno je isključivo kada medij nema nijednu poslovnu vezu. Deaktivacija ne briše postojeće veze niti fizički fajl i ne mijenja automatski događaj, manifestaciju ili kategoriju.

### BM-MD-05 — Ovlašćenja nad medijima

> Upload medija moguć je isključivo tokom uređivanja događaja, manifestacije ili kategorije. Moderator uploaduje, povezuje, zamjenjuje vezu i uklanja vezu isključivo u okviru svog organizacionog konteksta; Moderator ne mijenja medij-zapis (poslovne i tehničke atribute medija) niti aktivira, deaktivira, reaktivira ili trajno briše medij. Urednik uploaduje u okviru svojih ovlašćenja i upravlja medij-zapisom; isključivo Urednik aktivira, deaktivira, reaktivira i trajno briše medij. Organizator nije operativna uloga. Administrator platforme nema redovnu poslovnu ulogu u upravljanju medijima. Prije svake izmjene i prije trajnog brisanja sistem ponovo provjerava ovlašćenja i uslove; ako uslovi nisu ispunjeni, operacija se odbija.

### BM-MD-06 — Naslovna fotografija događaja i hijerarhija prikaza

> Događaj može imati najviše jednu direktno povezanu naslovnu fotografiju (kardinalnost 0..1). Direktna naslovna fotografija nije obavezna za objavu. Na javnom portalu događaj uvijek ima jednu prikazanu fotografiju po hijerarhiji: (1) direktno povezana naslovna fotografija događaja; (2) podrazumijevana fotografija primarne kategorije događaja; (3) globalni tehnički placeholder događaja. Fallback nije poslovna veza događaj–medij: sistem ne kreira vezu, ne kopira medij kategorije na događaj i ne smatra fallback naslovnom fotografijom događaja. Uklanjanje jedine naslovne fotografije događaja je dozvoljeno i aktivira istu hijerarhiju prikaza. Globalni tehnički placeholder nije poslovni medij, nema namjenu i nije zapis u katalogu medija.

### BM-MD-07 — Naslovna fotografija manifestacije

> Manifestacija može imati najviše jednu naslovnu fotografiju (0..1). Ako nije povezana, koristi se placeholder manifestacije. Placeholder manifestacije nije poslovni medij, nije zapis u katalogu medija i nije povezan poslovnom vezom. Ne postoji automatsko preuzimanje fotografije sa događaja na manifestaciju niti obrnuto, niti automatsko povezivanje ili kopiranje medij-zapisa. Ako isti fizički fajl treba obje namjene, postoje dva odvojena medij-zapisa sa različitim namjenama.

### BM-MD-08 — Podrazumijevana fotografija kategorije

> Kategorija može imati najviše jednu podrazumijevanu fotografiju (0..1). Veza je opciona; Aktivna kategorija ne mora imati podrazumijevanu fotografiju. Jedan medij sa ovom namjenom može biti povezan sa jednom ili više kategorija. Podrazumijevana fotografija kategorije ne smatra se naslovnom fotografijom događaja.

### BM-MD-09 — Kardinalnost medija prema entitetima

> Medij sa namjenom „Naslovna fotografija događaja“ može biti povezan sa jednim ili više događaja (1..N). Medij sa namjenom „Naslovna fotografija manifestacije“ može biti povezan sa jednom ili više manifestacija (1..N). Medij sa namjenom „Podrazumijevana fotografija kategorije“ može biti povezan sa jednom ili više kategorija (1..N). Dijeljenje medija nije obavezno. Sistem ne smije automatski povezivati medij sa drugim entitetima niti automatski kreirati duplikate medij-zapisa.

### BM-MD-10 — Uklanjanje veze

> Uklanjanje medija sa jednog događaja, manifestacije ili kategorije uklanja samo tu vezu. Ne briše medij, ne briše fizički fajl i ne utiče na druge entitete povezane sa istim medijem.

### BM-MD-11 — Tip i formati fotografije

> Jedini poslovni tip medija u V1 je Fotografija. Dozvoljeni formati: JPEG, PNG, WebP. Dozvoljene ekstenzije: `.jpg`, `.jpeg`, `.png`, `.webp`. Dozvoljeni MIME tipovi: `image/jpeg`, `image/png`, `image/webp`. Maksimalna veličina jedne fotografije je 5 MB (5120 KB). Nisu dozvoljeni: SVG, GIF, BMP, TIFF, HEIC/HEIF, animirane slike i svi formati koji nisu izričito dozvoljeni. Sistem mora potvrditi međusobnu podudarnost sadržaja, MIME tipa i ekstenzije. Serverska validacija je mjerodavna. Minimalne dimenzije i obavezni odnos stranica nisu poslovni uslov prijema. V1 ne zahtijeva automatski resize, thumbnail, kompresiju ni konverziju formata.

### BM-MD-12 — Vidljivost kataloga medija (nije vlasništvo)

> Pri ponovnoj upotrebi medija Moderator vidi samo medije svog organizacionog konteksta. Urednik vidi kompletan katalog medija. Ovo je pravilo vidljivosti, ne vlasništva.

### BM-MD-13 — Pretraga medija

> Moderator pretražuje medije po nazivu i opisu u okviru svog organizatora. Urednik pretražuje kompletan katalog uz filtere: status, namjena, organizator, kreator. Prikaz rezultata je u vidu kartica (thumbnail, naziv, namjena, dimenzije, veličina, datum) uz navigaciju load more ili infinite scroll.

### BM-MD-14 — Poslovni metapodaci medija

> Obavezni poslovni metapodaci: naziv, ALT tekst. Opcioni: opis, autor, izvor, licenca, tagovi. Tagovi postoje u modelu podataka, ali nisu dio V1 korisničkog interfejsa.

### BM-MD-15 — Tehnički metapodaci medija

> Sistem automatski vodi najmanje: originalni naziv datoteke, interni naziv, MIME tip, format, dimenzije, veličinu, vrijeme uploada, kreatora, vrijeme posljednje izmjene i status.

### BM-MD-16 — Dupli upload

> Pri uploadu sistem provjerava identičnu datoteku. Ako postoji, prikazuje se upozorenje i korisnik bira nastavak uploada ili korišćenje postojećeg medija. Duplikati nisu zabranjeni. Provjera sličnih (neidentičnih) fotografija nije dio V1.

### BM-MD-17 — Opseg V1

> U V1 ne ulaze: galerije fotografija, dokumenti kao poslovni medij, video, audio, mediji lokacija, mediji organizatora, proizvoljne korisničke namjene, uređivi katalog namjena, soft delete, scenario sa dva Urednika kao poslovno pravilo. Tehnička zaštita od uređivanja istog zapisa u više browser tabova nije poslovno pravilo.

## 5. Odnosi sa drugim cjelinama

- **Događaj** — opciona veza 0..1 na naslovnu fotografiju; prikaz uvijek jedne fotografije po BM-MD-06.
- **Manifestacija** — opciona veza 0..1 na naslovnu fotografiju; BM-MD-07.
- **Kategorija** — opciona veza 0..1 na podrazumijevanu fotografiju; BM-MD-08.
- **Moderator / Urednik** — ovlašćenja BM-MD-05, BM-MD-12, BM-MD-13.
- **Portal** — prikaz medija u skladu sa BM-PK-12 i hijerarhijom BM-MD-06.

## 6. Otvorena pitanja

Za poglavlje BM-09 trenutno nema otvorenih poslovnih pitanja.

---

# BM-10 Statusi i životni ciklus događaja

**Status poglavlja:** USVOJENO

## 1. Svrha

Definisanje poslovnih statusa događaja, dozvoljenih faza njegovog životnog ciklusa i osnovnih pravila prelaska između tih faza u modulu Kalendara kulture.

## 2. Poslovni opis

Životni ciklus događaja obuhvata poslovna stanja kroz koja događaj prolazi od početnog kreiranja, preko postupka odobravanja i objavljivanja, do otkazivanja ili automatskog arhiviranja.

Tok događaja zavisi od korisničke uloge koja upravlja događajem i od poslovnih pravila modula Kalendara kulture.

## 3. Poslovni koncept

Događaj uvijek ima jedan važeći poslovni status.

Promjena statusa predstavlja posljedicu dozvoljene poslovne radnje koju izvršava ovlašćena korisnička uloga ili sistem automatski, u skladu sa usvojenim poslovnim pravilima.

## 4. Poslovna pravila

### BM-ST-01 — Definicija životnog ciklusa

> Životni ciklus događaja predstavlja skup poslovnih statusa kroz koje događaj prolazi od kreiranja do automatskog arhiviranja u modulu Kalendara kulture.

### BM-ST-02 — Statusi događaja

> Događaj može imati jedan od sljedećih statusa:
>
> * Nacrt
> * Na odobrenju
> * Objavljen
> * Otkazan
> * Arhiviran
>
> **„U pripremi“ nije status životnog ciklusa.** Predstavlja poslovni prikaz za Urednikov još neobjavljeni Događaj (BM-UR-14 / BM-ST-03). Moderatorski tok zadržava poslovni status **Nacrt**.

### BM-ST-03 — Kreiranje događaja

> Svaki novi događaj nastaje u početnom neobjavljenom stanju životnog ciklusa koje Moderatorski tok prikazuje i tretira kao **Nacrt**, a urednički tok kao **U pripremi**. Takav događaj nije vidljiv na javnom portalu.
>
> **Moderator:** kreira Nacrt u ime registrovanog Organizatora; može ga uređivati i kasnije poslati na odobrenje.
>
> **Urednik:** kreira Događaj bez registrovanog Organizatora akcijom **Sačuvaj i nastavi**; Događaj se prikazuje kao **U pripremi**; Urednik ga uređuje i direktno objavljuje kada su ispunjeni uslovi. „Nacrt“ nije poslovna faza Urednikovog direktnog toka.
>
> Događaj u tom početnom stanju može biti sačuvan bez svih podataka potrebnih za objavljivanje.

### BM-ST-04 — Slanje na odobrenje i objavljivanje

> Događaj u statusu Nacrt koji je kreirao Moderator u ime Organizatora može biti poslat na odobrenje kada ispunjava poslovne uslove za pregled od strane urednika. Slanjem na odobrenje status događaja se mijenja u Na odobrenju. Od tog trenutka Događaj je sadržajno zaključan do uredničke odluke (BM-ST-10 / PO-DG-10).
>
> Događaj koji pripada registrovanom Organizatoru ne može biti direktno objavljen. Za takav događaj obavezan je standardni tok: Nacrt → Na odobrenju → Objavljen. Moderator ne može biti zaobiđen u tom toku.
>
> Urednik može direktno objaviti događaj iz stanja **U pripremi**, bez postupka odobravanja, isključivo kada događaj nema registrovanog Organizatora. To je jedini izuzetak od standardnog procesa odobravanja. Urednik u tom toku ne koristi akciju „Pošalji na odobrenje“.

### BM-ST-05 — Vraćanje na doradu

> Urednik može vratiti događaj u status Nacrt radi dorade. Vraćanjem na doradu status događaja se mijenja iz Na odobrenju u Nacrt, uz obrazloženje razloga vraćanja. Nakon vraćanja Moderator ponovo uređuje Nacrt; oznaka prvog slanja na odobrenje se ne briše.

### BM-ST-06 — Objavljivanje događaja

> Objavljivanjem događaja njegov status se mijenja u Objavljen. Objavljen događaj postaje vidljiv na javnom portalu u skladu sa poslovnim pravilima sistema.
>
> Objavljen događaj bez registrovanog Organizatora Urednik može naknadno direktno uređivati u skladu sa BM-UR-16. Objavljen događaj registrovanog Organizatora uređuje se kroz postojeći tok Prijedloga izmjene. Poslovno značajne radnje životnog ciklusa (odgađanje, otkazivanje Održavanja, otkazivanje Događaja) koriste posebne akcije.

### BM-ST-07 — Otkazivanje događaja

> Objavljen događaj može biti otkazan. Otkazivanjem status događaja se mijenja u Otkazan, pri čemu događaj ostaje dostupan radi očuvanja istorijskih podataka i informisanja javnosti o otkazivanju i tretira se kao istorijski zapis. Informisanje javnosti ostvaruje se standardizovanim sistemskim obavještenjem; ako je unesen opcion razlog otkazivanja, taj razlog može se javno prikazati kao napomena (BM-DG-10 / BM-PK-36).
>
> Moderator može samostalno otkazati objavljeni događaj isključivo dok Organizator ima status Aktivan i isključivo za Organizatora u čijem aktivnom kontekstu ima aktivno moderatorsko ovlašćenje.
>
> Deaktivacijom Organizatora prestaje moderatorski kontekst za tog Organizatora. Moderator tada više nema pravo otkazivanja događaja tog Organizatora. Ako je potrebno otkazati događaj deaktiviranog Organizatora, tu radnju izvršava isključivo Urednik.
>
> Urednik može otkazati bilo koji objavljeni događaj. Razlog otkazivanja je **opcion** i nije uslov za otkazivanje.
>
> Status Otkazan predstavlja terminalno stanje događaja u smislu povratka u Objavljen. Iz statusa Otkazan nije dozvoljen povratak u status Objavljen. Ako se isti kulturni program kasnije ponovo organizuje, ne reaktivira se postojeći događaj; kreira se novi događaj kao novi zapis sa novim životnim ciklusom. Otkazan Događaj se ne briše.
>
> Promjena termina postojećeg događaja koji nije otkazan vrši se isključivo kroz status Odgođen na održavanju, u skladu sa BM-06.
>
> Nakon otkazivanja nije dozvoljena naknadna izmjena sadržajnih podataka događaja ni povezanih održavanja, osim razloga otkazivanja (napomene urednika), u skladu sa BM-DG-10. Automatsko otkazivanje otvorenih Održavanja (BM-DG-11 / PO-AUTO-01) dio je same operacije otkazivanja Događaja.

### BM-ST-08 — Automatsko arhiviranje

> Događaj se automatski arhivira nakon završetka svih njegovih održavanja, bez ručne intervencije.
>
> „Završetak svih održavanja“ znači da nijedno Održavanje nije u statusu Planiran niti Odgođen (Održavanja u statusu Završen ili Otkazan ne sprečavaju arhiviranje), u skladu sa BM-06 i predikatom arhiviranja.
>
> Automatsko arhiviranje primjenjuje se na događaj u statusu Objavljen i na događaj u statusu Otkazan. Otkazani događaj nakon što su otvorena Održavanja zatvorena prema BM-DG-11 (i eventualno ostala Održavanja već u Završen/Otkazan) prelazi u status Arhiviran kada je predikat ispunjen.
>
> Arhiviran događaj ostaje dostupan radi očuvanja istorijskih podataka.
>
> **Očuvanje izvornog javnog statusa (PO-6A09-02):** Pri prelazu u Arhiviran Sistem mora pouzdano sačuvati da li je Događaj arhiviran iz statusa Objavljen ili Otkazan. Interni status Arhiviran sam po sebi nije javni badge. Javni istorijski ishod uređuje BM-PK-35.

### BM-ST-09 — Promjena statusa

> Promjena statusa događaja može se izvršiti isključivo u skladu sa poslovnim pravilima modula Kalendara kulture i ovlašćenjima korisničkih uloga. Sistem ne dozvoljava promjenu statusa koja nije definisana poslovnim pravilima.
>
> Sistem ne dozvoljava prelaz iz statusa Otkazan u status Objavljen.

### BM-ST-10 — V1 zaključavanje Događaja na odobrenju (PO-DG-10)

> Za **prvi tok** Nacrt → Na odobrenju → Objavljen u V1 važi:
>
> * status **Na odobrenju** je jedinstveno stanje čekanja odluke Urednika; nije podstatus „pregled započet“ niti posebna akcija „Počni pregled“;
> * od trenutka slanja na odobrenje Događaj je **sadržajno zaključan** (Moderator i Urednik ne uređuju sadržaj ni Održavanja);
> * Urednik donosi isključivo: **Odobri** (→ Objavljen) ili **Vrati na doradu** (→ Nacrt, razlog obavezan);
> * Moderator **ne** povlači Događaj iz Na odobrenju u Nacrt.
>
> Ovo pravilo **ne** mijenja tok Prijedloga izmjene Objavljenog Događaja (gdje ostaju postojeća pravila slanja, početka pregleda, uređivanja Prijedloga, povlačenja prije pregleda i odluke Urednika).
>
> Ovo pravilo **ne** mijenja direktno uređivanje Objavljenog Događaja bez registrovanog Organizatora od strane Urednika (BM-UR-16 / BM-ST-11).

### BM-ST-11 — Direktno uređivanje Objavljenog Događaja u uredničkom toku

> Za Objavljen Događaj bez registrovanog Organizatora Urednik direktno čuva dozvoljene sadržajne izmjene bez Prijedloga izmjene i bez odobravanja (BM-UR-16).
>
> Za Objavljen Događaj registrovanog Organizatora ostaje postojeći tok Prijedloga izmjene; Moderatorski tok nije ukinut.
>
> Odgađanje Održavanja, otkazivanje Održavanja, otkazivanje Događaja i druge radnje životnog ciklusa ostaju posebne akcije.

## 5. Otvorena pitanja

Za poglavlje BM-10 trenutno nema otvorenih poslovnih pitanja.

---

# BM-11 Portal Kalendara kulture

**Status poglavlja:** USVOJENO

## Poslovna pravila

### BM-PK-01 — Definicija Portala Kalendara kulture

> Portal Kalendara kulture predstavlja funkcionalni dio modula Kalendara kulture namijenjen pregledu, pretrazi i korišćenju sadržaja objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-02 — Odnos sa platformom Digital Kotor

> Portal Kalendara kulture predstavlja funkcionalni dio platforme Digital Kotor.
>
> Za korišćenje Portala Kalendara kulture zahtijeva se registracija korisnika.
>
> Upravljanje korisničkim identitetom, registracijom, prijavom i korisničkim profilom nije dio poslovnog domena Portala Kalendara kulture, već platforme Digital Kotor.

### BM-PK-03 — Pregled događaja

> Portal Kalendara kulture omogućava pregled događaja objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture. Pregled događaja obuhvata informacije potrebne za informisanje korisnika o održavanju kulturnih sadržaja.

### BM-PK-04 — Pregled manifestacija

> Portal Kalendara kulture omogućava pregled manifestacija objavljenih u skladu sa poslovnim pravilima modula Kalendara kulture.
>
> Manifestacije predstavljaju zasebnu sadržajnu cjelinu Portala i ne predstavljaju se kroz kategorije događaja (BM-PK-24).
>
> Pregled obuhvata listu, detalj i program Manifestacije, u skladu sa BM-PK-24–BM-PK-28.

### BM-PK-05 — Detaljan prikaz

> Portal Kalendara kulture omogućava pregled detaljnih informacija o objavljenim događajima i manifestacijama, uključujući sa njima povezana održavanja (sa terminima i lokacijama), kategorije, oznake, medije i druge javno objavljene podatke u skladu sa poslovnim pravilima modula Kalendara kulture.

### BM-PK-06 — Pretraga

> Portal Kalendara kulture omogućava pretragu objavljenih događaja i manifestacija.
>
> Centralno mjesto za pretragu i pregled događaja je stranica „Pretraga i pregled“ (BM-PK-17).
>
> Pretraga na početnoj stranici nije dio poslovnog modela početne stranice; napredna pretraga i filtriranje pripadaju stranici „Pretraga i pregled“.

### BM-PK-07 — Filtriranje

> Portal Kalendara kulture omogućava filtriranje objavljenih događaja i manifestacija.
>
> Filteri su sastavni dio stranice „Pretraga i pregled“ i detaljno su definisani pravilom BM-PK-18.

### BM-PK-08 — Načini prikaza

> Portal Kalendara kulture omogućava prikaz objavljenih događaja i manifestacija kroz načine prikaza definisane ovim Business Modelom.
>
> Zadržavaju se postojeći prikazi Portala; ne uvodi se redizajn Portala (BM-PK-16, BM-PK-19).
>
> Stranica „Pretraga i pregled“ koristi isključivo prikaz liste. Mjesečni kalendar ostaje isključivo na početnoj stranici (BM-PK-20).
>
> Stranice liste i detalja Manifestacija predstavljaju usvojenu novu funkcionalnu cjelinu za već usvojeni poslovni entitet Manifestacija (BM-05), a ne redizajn Portala (BM-PK-24).

### BM-PK-09 — Prikaz održavanja i termina

> Portal Kalendara kulture omogućava pregled javno relevantnih održavanja događaja, uključujući termin svakog održavanja (Datum održavanja je obavezan, a vrijeme može biti definisano.).
>
> Na **Detalju Događaja** portal prikazuje **sva** javno relevantna Održavanja sa njihovim terminima i lokacijama, u skladu sa poslovnim pravilima modula Kalendara kulture.
>
> Na **kartici Događaja** (liste / Pretraga) portal ne prikazuje kompletnu listu Održavanja; važi BM-PK-29.

### BM-PK-10 — Prikaz lokacija

> Portal Kalendara kulture omogućava pregled lokacija povezanih sa održavanjima objavljenih događaja, uključujući događaje koji pripadaju objavljenim manifestacijama, kada su lokacije definisane u skladu sa poslovnim pravilima. Lokacija nije atribut Manifestacije.

### BM-PK-11 — Prikaz kategorija i oznaka

> Portal Kalendara kulture omogućava prikaz primarnih kategorija i oznaka povezanih sa objavljenim događajima. Za objavljenu Manifestaciju portal može prikazati kategorije i oznake samo kao izvedene iz njenih Objavljenih Događaja; one nisu samostalno sačuvan atribut Manifestacije.
>
> Nakon cutover-a Faze 6A primarna kategorija na javnom portalu dolazi isključivo iz kanonskog kataloga (BM-PK-32 / BM-08).

### BM-PK-12 — Prikaz medija

> Portal Kalendara kulture omogućava prikaz medija povezanih sa objavljenim događajima i manifestacijama, te prikaz fotografije događaja u skladu sa hijerarhijom naslovne fotografije / podrazumijevane fotografije kategorije / tehničkog placeholdera (BM-09). U V1 portal ne prikazuje medije lokacija niti organizatora.

### BM-PK-13 — Prikaz otkazanih i arhiviranih događaja

> Portal Kalendara kulture omogućava prikaz otkazanih i istorijskih događaja u skladu sa poslovnim pravilima modula Kalendara kulture.
>
> **Princip (PO-6A09-01):** **Arhiviran** je interni završni lifecycle status. **Javna Arhiva** je istorijski pogled na ranije javne Događaje, a ne lista svih zapisa čiji je interni status Arhiviran.
>
> **Javni prikaz otkazanih (CR-004B / PO-CR4B-01…10; usklađenje PO-6A09):**
>
> * Otkazani događaj ostaje javno dostupan dok je u statusu Otkazan. Do planiranog termina prikazuje se na aktivnim javnim površinama; nakon isteka ulazi u Javnu Arhivu uz badge **Otkazan**.
> * Aktivni javni skup (naslovna, Pretraga, featured, upcoming, aktivni counts, aktivni detalj) ostaje **`published` | `cancelled`**. Interni status `archived` **ne** ulazi u taj skup.
> * **Portalna / Javna Arhiva ≠ jednostavno „svi `archived`"**. Arhiva je poseban istorijski pogled (BM-PK-35).
> * Ne prikazuje se među Istaknutim događajima. Flag isticanja se ne mijenja — otkazani se samo isključuju iz javnog prikaza Istaknutih.
> * Na Detaljima otkazanog se prikazuje fiksno sistemsko obavještenje; ako je unesen opcion razlog otkazivanja, može se javno prikazati kao napomena (BM-PK-36). **PATCH-063 superseduje** raniju zabranu javnog prikaza teksta razloga iz PATCH-060 / BM-PK-33.
> * Ne uvode se novi filteri, URL parametri ni search modovi za otkazane isključivo zbog CR-004B.
>
> **Lifecycle `cancelled → archived` (BM-DG-04 / BR-065) i očuvanje Otkazan:** CR-004B historijski nije implementirao taj prelaz (PO-CR4B-09). Prelaz **jeste** usvojeni lifecycle. Za kanonsku Javnu Arhivu obavezno je trajno očuvanje informacije da je Događaj arhiviran iz Otkazan, tako da javni badge ostane **Otkazan** (BM-PK-35 / PO-6A09-02 / PO-6A09-04). Terminalnost Otkazan (BM-DG-09) ostaje neizmijenjena — nema republish ni povratka u Objavljen.
>
> Status otkazanog ili istorijski završenog događaja mora biti jasno prikazan korisniku (javni status badge: **Otkazan** ili **Završen** — ne „Arhiviran").
>
> Kanonska agregatna pravila vremenskog statusa Objavljenog Događaja uređuje **BM-PK-34** (PO-6A11-01). Kanonska Javna Arhiva uređuje **BM-PK-35** (PO-6A09-01…06).

### BM-PK-14 — Povezani sadržaj

> Portal Kalendara kulture može prikazivati međusobno povezane događaje i manifestacije u skladu sa njihovim poslovnim vezama definisanim u modulu Kalendara kulture.

### BM-PK-15 — Istaknuti događaji

> Portal Kalendara kulture može imati istaknute događaje na početnoj stranici.
>
> U jednom trenutku mogu biti istaknuta najviše tri (3) događaja.
>
> Prikazuju se isključivo javno objavljeni i aktuelni događaji.
>
> Otkazani događaji se ne prikazuju među Istaknutim. Flag isticanja se ne mijenja otkazivanjem — portal samo isključuje otkazane iz javnog prikaza Istaknutih (BM-PK-13 / CR-004B).
>
> Istaknute događaje određuje Urednik. Sistem ih ne bira automatski.
>
> Isticanje događaja ne mijenja njegov osnovni status.
>
> Događaj prestaje biti istaknut kada Urednik ukloni isticanje ili kada događaj više ne ispunjava uslove za javni prikaz (nije aktuelan).
>
> Ako nema nijednog istaknutog događaja, prikazuje se neutralno prazno stanje. Na javnom portalu ne prikazuju se administrativne poruke.

### BM-PK-16 — Evolutivni razvoj Portala (IA-01)

> Portal Kalendara kulture razvija se evolutivno.
>
> Cilj nije redizajn Portala, već evolucija postojećeg rješenja kroz minimalne i strogo neophodne izmjene.
>
> Zadržavaju se postojeća struktura i korisnički tokovi, uz izmjene samo kada su neophodne za usklađivanje sa poslovnim pravilima.

### BM-PK-17 — Pretraga i pregled

> Stranica „Pretraga i pregled“ predstavlja centralno mjesto za pretragu i pregled događaja na Portalu Kalendara kulture.
>
> Raniji naziv iste funkcionalne stranice u implementaciji i navigaciji bio je „Pregled događaja“ (preimenovanje: PO-TS9-03A).

### BM-PK-18 — Filteri na stranici Pretraga i pregled

> Filteri su sastavni dio stranice „Pretraga i pregled“ i uvijek su vidljivi.
>
> Podržani filteri su:
>
> * datum;
> * kategorija;
> * lokacija;
> * manifestacija.
>
> Filteri se mogu kombinovati.
>
> Postoji opcija „Poništi filtere“.
>
> Aktivni filteri čuvaju se u URL parametrima.

### BM-PK-19 — Zadržavanje postojećih prikaza

> Zadržavaju se postojeći prikazi Portala Kalendara kulture.
>
> Ne uvode se novi ekrani radi proširenja informacione arhitekture van usvojenih poslovnih odluka.

### BM-PK-20 — Lista i mjesečni kalendar

> Stranica „Pretraga i pregled“ koristi isključivo prikaz liste.
>
> Na stranici „Pretraga i pregled“ ne uvodi se dodatni kalendarski prikaz.
>
> Mjesečni kalendar ostaje isključivo na početnoj stranici Portala.

### BM-PK-21 — Hero sekcija početne stranice (PO-TS9-06A)

> Hero sekcija je sastavni dio početne stranice Portala Kalendara kulture.
>
> Hero zadržava postojeći vizuelni identitet i ostaje statički.
>
> Hero nije uređiv iz administracije, ne koristi podatke iz baze, nema CTA dugmadi, nema promotivnih poruka, nema rotacije sadržaja i nema video sadržaja.
>
> Hero služi isključivo kao identitet modula Kalendara kulture.

### BM-PK-22 — Statistike na početnoj stranici (PO-TS9-06C)

> Početna stranica prikazuje tri statističke kartice: Danas; Ove sedmice; Izabrani mjesec.
>
> Treća kartica prikazuje naziv trenutno izabranog mjeseca u kalendaru (ne fiksni naziv „Ovog mjeseca“).
>
> Sve tri kartice su klikabilne. Klik vodi na stranicu „Pretraga i pregled“ sa odgovarajućim aktivnim datumskim filterom.
>
> Ako statistika ima vrijednost 0, kartica ostaje klikabilna.
>
> Statistike prikazuju javno dostupne događaje (`published` | `cancelled`) u odgovarajućem vremenskom skupu (CR-004B / BM-PK-13). Nema novih filtera ni URL parametara.
>
> Statistike ostaju na postojećem mjestu na početnoj stranici.

### BM-PK-23 — Lista ispod kalendara (PO-TS9-06D)

> Lista ispod mjesečnog kalendara ostaje na postojećem mjestu na početnoj stranici.
>
> Ako datum nije izabran, lista prikazuje „Naredni događaji" — najviše tri (3) naredna događaja.
>
> Ako je datum izabran, lista prikazuje „Događaji za izabrani datum“ — sve događaje za taj datum.
>
> Na kraju liste postoji dugme „Prikaži sve događaje": bez izabranog datuma otvara „Pretragu i pregled" bez datumskog filtera; sa izabranim datumom otvara „Pretragu i pregled" sa istim datumskim filterom.
>
> Ako nema događaja, prikazuje se postojeća poruka o praznom stanju.
>
> **PATCH-064:** Lista na naslovnoj može uključiti Objavljeni Događaj koji nema naredno Planirano Održavanje, ali ispunjava uslove **informativne naslovne vidljivosti** Odgođenog (BM-PK-37 / BM-GL-26). Takva kartica nije „naredni“ događaj po važećem Planiranom terminu; komunikacija je informativna („Odgođeno" / „Prvobitni termin").

### BM-PK-24 — Manifestacije kao zasebna cjelina Portala (PO-TS9-07A)

> Manifestacije predstavljaju zasebnu sadržajnu cjelinu Portala Kalendara kulture.
>
> Manifestacije se ne predstavljaju kroz kategorije događaja.
>
> Glavna navigacija Portala sadrži stavku „Manifestacije“.
>
> Portal obezbjeđuje listu javno objavljenih Manifestacija, detalj Manifestacije i program Manifestacije.
>
> Ne radi se redizajn Portala; uvodi se samo nova funkcionalna cjelina za već usvojeni poslovni entitet Manifestacija (BM-05).

### BM-PK-25 — Lista Manifestacija (PO-TS9-07B)

> Stranica „Manifestacije“ prikazuje listu javno dostupnih Manifestacija koje nijesu Arhivirane.
>
> U aktivnu listu ulaze Objavljene Manifestacije. Otkazane Manifestacije ostaju u aktivnoj listi do isteka izvedenog perioda, uz jasnu oznaku „Otkazana“ (BM-PK-38 / PO-6B-08). Arhivirane Manifestacije ne ulaze u aktivnu listu (BM-PK-38; portalni detalj Arhivirane: BM-11 / PO-6B-03).
>
> Sortiranje: (1) datum početka, (2) naziv.
>
> Paginacija: 12 Manifestacija po stranici, standardna paginacija.
>
> Kartica sadrži: naslovnu fotografiju; naziv; period održavanja; kratak opis; broj objavljenih događaja u programu; link „Detalji manifestacije“. Za Otkazanu Manifestaciju kartica mora jasno prikazivati oznaku „Otkazana“.
>
> U V1 lista Manifestacija nema pretragu ni filtere.
>
> Ako nema Manifestacija, prikazuje se neutralna poruka.

### BM-PK-26 — Detalj Manifestacije (PO-TS9-07C)

> Detalj Manifestacije prikazuje: naslovnu fotografiju; naziv; period održavanja; Organizatora (kada postoji); lokaciju kada je dostupna kao javna informacija (Manifestacija nema sopstvenu lokaciju kao atribut — BM-MF-16; lokacije događaja prikazuju se u programu); zvaničnu web stranicu kada postoji; opis; program Manifestacije.
>
> Program se prikazuje ispod osnovnih informacija. Ako program nije javno dostupan, prikazuje se odgovarajuća poruka.
>
> U V1 se ne uvode: galerije; video; dijeljenje; rezervacije; komentari; dodatne multimedijalne funkcionalnosti.

### BM-PK-27 — Program Manifestacije na Portalu (PO-TS9-07D)

> Program je grupisan po datumima i hronološki sortiran: (1) datum, (2) vrijeme, (3) naziv.
>
> Svako održavanje prikazuje se kao zaseban unos sa: vremenom; nazivom događaja; lokacijom (ako postoji); linkom „Detalji događaja“.
>
> Završeni događaji ostaju prikazani. Otkazani događaji ostaju prikazani uz oznaku „Otkazano“.
>
> Ako vrijeme nije definisano, prikazuje se oznaka „Vrijeme nije definisano“.
>
> Ako nema javnog programa, prikazuje se odgovarajuća poruka.

### BM-PK-28 — Veza Manifestacija ↔ Događaji na Portalu (PO-TS9-07E)

> Kardinalnost i pripadnost uređene su pravilima BM-MF-03 / BM-DG-02: jedna Manifestacija — više Događaja; Događaj pripada najviše jednoj Manifestaciji; Događaj može postojati bez Manifestacije; Manifestacija je programski okvir; Događaj ostaje osnovni poslovni entitet.
>
> Na detalju Događaja koji pripada Manifestaciji prikazuje se informacija o pripadnosti Manifestaciji **samo kada je povezana Manifestacija javno dostupna** (BM-PK-39 / PO-6B-09). Semantika: oznaka pripadnosti + naziv Manifestacije koji vodi na njen canonical javni detalj. Status Manifestacije se **ne** prikazuje na detalju Događaja (Otkazana Manifestacija ≠ Otkazan Događaj).
>
> Program na detalju Manifestacije vodi na detalj Događaja. Obezbijeđena je dvosmjerna navigacija kada su oba entiteta javno dostupna prema svojim pravilima.
>
> Događaji ostaju vidljivi u Pretrazi i pregledu, kalendaru, statistikama i arhivi bez obzira na pripadnost Manifestaciji.
>
> Uklanjanje ili arhiviranje Manifestacije ne briše Događaje (BM-MF-14, BM-MF-15). Otkazivanje Manifestacije ne mijenja statuse Događaja ni Održavanja (BM-MF-07, BM-MF-15).

### BM-PK-29 — Kartica Događaja sa više Održavanja

> Na kartici Događaja, u **standardnom režimu**, prikazuje se **prvo naredno relevantno Planirano Održavanje** (termin i pripadajuća lokacija tog Održavanja). Planirano Održavanje ima **apsolutni prioritet** za izbor glavnog (važećeg) termina kartice nad Odgođenim Održavanjem.
>
> Ako Događaj ima dodatna relevantna **Planirana** Održavanja, kartica prikazuje oznaku **„+ još N termina"**, gdje je N broj dodatnih relevantnih Planiranih Održavanja.
>
> Kartica ne prikazuje kompletnu listu Održavanja. Rješenje se uklapa u postojeći dizajn kartice uz minimalne vizuelne izmjene (BM-PK-16 / IA-01).
>
> Na Detalju Događaja prikazuju se sva javno relevantna Održavanja (BM-PK-09).
>
> **PATCH-064:** Pojam „naredno relevantno Održavanje" za **standardni termin kartice** ne uključuje Odgođeno Održavanje i ne čini Odgođeno Planiranim niti „upcoming". Kada nema narednog Planiranog Održavanja, a postoje uslovi za posebnu **informativnu naslovnu vidljivost**, važi BM-PK-37 (zaseban mehanizam; nije redefinicija ovog pravila).

### BM-PK-30 — Sortiranje Pretrage po narednom Održavanju

> Događaji na stranici „Pretraga i pregled“ sortiraju se **rastuće** prema datumu (i vremenu, kada postoji) **prvog narednog relevantnog Održavanja**.
>
> Za Događaj sa više Održavanja: dok postoji naredno relevantno Održavanje, ono određuje poziciju; kada jedno prođe, sljedeće naredno relevantno postaje ključ sortiranja.
>
> Ovo je **sistemsko** sortiranje. Ne uvodi se korisnički izbor sortiranja (BM-PK-07 ostaje bez korisničkog sortiranja).
>
> **PATCH-066 / PO-6B-10:** Ovo pravilo ostaje SSOT za rezultat **samo Događaja** (Tip sadržaja = Događaji). Kada je Tip sadržaja = Sve, važi BM-PK-40 (zajednički poredak Događaja i Manifestacija).

### BM-PK-31 — Odgođeno Održavanje na javnom portalu

> Status **Odgođen** nije isto što i **Otkazan**. Odgođen ostaje status Održavanja, ne Događaja.
>
> Na **Detalju Događaja**, dok je Održavanje Odgođeno a novi termin još nije poznat, portal prikazuje oznaku **„Odgođeno“** i prethodni datum kao **„Prvobitni termin“**. Ne prikazuje se izmišljeni novi datum niti oznaka „uskoro“. Ako postoji razlog odgađanja, može se javno prikazati kao napomena. Kada novi termin bude unesen i Održavanje ponovo postane Planiran, javno se prikazuje novi termin; prethodna promjena može ostati u istoriji/evidenciji aktivnosti bez obaveze da opterećuje glavni javni prikaz.
>
> Na **kartici Događaja** u **standardnom režimu** (kada postoji naredno Planirano Održavanje): stari odgođeni termin **ne** prikazuje se kao važeći glavni termin kartice. Kartica prikazuje prvo naredno relevantno **Planirano** Održavanje; za dodatna relevantna Planirana Održavanja važi BM-PK-29.
>
> **PATCH-064:** Kada **nema** narednog Planiranog Održavanja, a ispunjeni su uslovi informativne naslovne vidljivosti, kartica na naslovnoj **može** prikazati Odgođeno Održavanje isključivo kao **informativni** sadržaj („Odgođeno" / „Prvobitni termin"), prema BM-PK-37. Prvobitni datum **nije** važeći termin održavanja; Odgođeno **ne** postaje Planirano niti „upcoming“.

### BM-PK-32 — Kanonske kategorije na javnom portalu (CAT-CUTOVER)

> Nakon prelaska Faze 6A javni portal koristi isključivo kanonski katalog `CulturalCategory`.
>
> Filter kategorija puni se dinamički iz aktivnog kanonskog kataloga. URL filter koristi kanonski naziv kategorije.
>
> `CulturalEvent::CATEGORIES` **nije** izvor kategorija za kanonski javni portal. Ne uvodi se legacy alias mapa niti kompatibilnost sa starim nazivima kategorija. Legacy kategorije se ne migriraju (PO-EV-01 / BM-KO-11).
>
> Preduslov cutover-a: svih **14** usvojenih početnih kategorija (BM-KO-09) mora postojati u `cultural_categories`.

### BM-PK-33 — Sistemsko obavještenje o otkazivanju (izmijenjeno PATCH-063)

> Za otkazani Događaj na Detaljima ostaje fiksno sistemsko obavještenje o otkazivanju (BM-PK-13).
>
> **PATCH-063 superseduje** ranije V1 pravilo koje je zabranjivalo javni prikaz teksta razloga otkazivanja i koje je zahtijevalo zasebnu Product Owner odluku za taj prikaz.
>
> Razlog otkazivanja Događaja je **opcion**. Ako je unesen, **može se javno prikazati** kao napomena o otkazivanju, uz sistemsko obavještenje (BM-DG-10 / BM-PK-36). Terminalnost statusa Otkazan (BM-DG-09) ostaje neizmijenjena.

### BM-PK-34 — Javni status Događaja sa više Održavanja (PO-6A11-01)

> Javni status Događaja (Predstoji / U toku / Završen / Otkazan) nije isto što i status pojedinačnog Održavanja. Oznake Održavanja (Odgođeno / Otkazano / Završeno) ne postaju automatski status cijelog Događaja. **Odgođen** nije javni status Događaja.
>
> **Otkazan (apsolutni prioritet):** Ako je status Događaja Otkazan, javni status je uvijek **Otkazan**, bez obzira na Održavanja, datume i termine. Razlog otkazivanja ne ulazi u određivanje statusa (BM-PK-33).
>
> **Objavljen Događaj — agregat (kanonski model):**
>
> 1. **U toku** — ako najmanje jedno **Planirano** Održavanje trenutno traje (u svom važećem vremenskom intervalu). Ima prioritet nad drugim budućim Planiranim Održavanjem.
> 2. **Predstoji** — ako nijedno Planirano nije u toku i postoji najmanje jedno buduće Planirano Održavanje.
> 3. **Završen** — ako Događaj ima najmanje jedno Održavanje, ali nema Planiranog koje je u toku ili buduće. U obzir se uzima i **vremenski istek** Planiranog Održavanja (ne samo status Završen na Održavanju). Pojedinačno Otkazano Održavanje ne čini Događaj Otkazanim.
> 4. **Bez vremenskog statusa** — ako Objavljen Događaj nema nijedno Održavanje, ili ako su sva Održavanja samo **Odgođena** (postponed-only) bez Planiranog koje omogućava pouzdano vremensko određivanje. Ne prikazuje se Predstoji / U toku / Završen / Odgođen kao status Događaja; pojedinačno Odgođeno Održavanje i dalje ima oznaku na Detalju (BM-PK-31).
>
> Odgođeno Održavanje ne koristi se kao zamjenski termin za računanje javnog vremenskog statusa Događaja. Kartična relevantnost Planiranog Održavanja (BM-PK-29) i informativna naslovna vidljivost Odgođenog (BM-PK-37) ostaju **zasebna** pravila; BM-PK-37 ne mijenja agregatni vremenski status iz ovog paragrafa.
>
> Za **interni status Arhiviran** javni istorijski badge uređuje **BM-PK-35** (ne ovaj paragraf).

### BM-PK-35 — Javna Arhiva Događaja (kanonski; PO-6A09-01…06)

> **Arhiviran** je interni završni lifecycle status. **Javna Arhiva** je istorijski pogled na ranije javne Događaje, a ne lista svih zapisa sa statusom Arhiviran.
>
> **Aktivne javne površine** (naslovna, Pretraga, featured, upcoming, aktivni counts, aktivni detalj) koriste isključivo skup **`published` | `cancelled`**. Interni `archived` se **ne** dodaje u taj skup.
>
> **Javna Arhiva** koristi **poseban** istorijski ugovor / query (nije isti kao aktivni public query), koji može uključiti:
>
> * `published` ili `cancelled` koji po vremenskom kriterijumu pripadaju istoriji (prošli termini); i
> * `archived` zapise koji su ranije bili javni (`published` ili `cancelled`) i za koje je sačuvan izvorni javni status pri arhiviranju.
>
> U oba slučaja Javna Arhiva zahtijeva **i** dokazano prethodno javno stanje **i** istorijski kriterijum Održavanja (pouzdano istorijsko Održavanje za kartični datum/redoslijed). Samo `archived_from_status` nije dovoljan.
>
> Nacrt i Na odobrenju **nikada** ne ulaze u Javnu Arhivu. Samo postojanje `archived` nije dovoljno ako zapis nije bio u relevantnom javnom statusu.
>
> **Očuvanje izvornog statusa (PO-6A09-02):** Pri arhiviranju Sistem čuva da li je izvor bio Objavljen ili Otkazan. Ne koristi se `cancellation_reason`, OCC status niti audit parsing kao SSOT za taj podatak.
>
> **Javni badge (PO-6A09-04):** Ne postoji javni badge „Arhiviran". Istorijski:
>
> * arhiviran iz Otkazan → **Otkazan**;
> * arhiviran iz Objavljen → **Završen**.
>
> **Direct URL (PO-6A09-03):** Ako Događaj pripada dozvoljenom javnom istorijskom skupu (uključujući odgovarajući `archived`), detalj je dostupan (200). Draft/pending i ostali nejavni → 404. Ovo ne čini `archived` globalno javnim.
>
> **Kartica Arhive (PO-6A09-05):** prikazuje **posljednje relevantno istorijsko Održavanje** po datumu/vremenu. Ne koristi se pravilo „prvog narednog relevantnog" Održavanja za aktivne kartice (BM-PK-29).
>
> **Sortiranje Arhive (PO-6A09-06):** najnoviji istorijski Događaji prvi — po datumu (i vremenu) posljednjeg relevantnog istorijskog Održavanja opadajuće, uz deterministički tie-breaker. Vrijeme izvršenja sistemskog arhiviranja nije poslovni ključ sortiranja.

### BM-PK-36 — Javni prikaz opcionih napomena i Prvobitnog termina

> Kada je Održavanje **Odgođeno** a novi termin još nije poznat, javni portal prikazuje oznaku Odgođeno i prethodni datum kao **Prvobitni termin**, u skladu sa BM-PK-31 / BM-TR-19.
>
> Opcioni razlozi odgađanja Održavanja, otkazivanja Održavanja i otkazivanja Događaja, ako su unijeti, **mogu se javno prikazati** kao napomene. Razlozi nisu obavezni za izvršenje tih radnji.
>
> Za otkazani Događaj ostaje i standardizovano sistemsko obavještenje (BM-PK-13 / BM-PK-33). Terminalnost Otkazan ostaje neizmijenjena.
>
> **PATCH-064:** Isti pojmovi **Odgođeno** i **Prvobitni termin** koriste se i na informativnoj naslovnoj kartici kada važi BM-PK-37. Prvobitni termin i tada ostaje istorijski / informativni podatak, ne važeći termin održavanja. Pravila Pretrage i detalja iz PATCH-063 ostaju na snazi.

### BM-PK-37 — Informativna naslovna vidljivost Odgođenog (PATCH-064)

> **Svrha:** Poseban mehanizam **informativne naslovne vidljivosti** kojim se građanima saopštava važna promjena Objavljenog Događaja koji je prethodno imao termin, a trenutno nema naredno Planirano Održavanje. Ovo **nije** standardni režim kartice iz BM-PK-29 i **ne** redefiniše Odgođeno u Planirano ili „upcoming".
>
> **Uslov primjene (PO-064-02):** Mehanizam se primjenjuje isključivo kada Objavljeni Događaj:
>
> * **nema** naredno Planirano Održavanje; i
> * ima najmanje jedno **Odgođeno** Održavanje za koje novi termin još **nije** poznat.
>
> **Prioritet Planiranog (PO-064-01):** Ako postoji makar jedno naredno relevantno Planirano Održavanje, kartica koristi to Održavanje po BM-PK-29. Posebna informativna logika Odgođenog **ne** određuje glavni termin kartice.
>
> **Prikaz kartice (PO-064-03…07):** Dok važi informativna naslovna vidljivost, kartica jasno prikazuje:
>
> * **„Odgođeno"**;
> * **„Prvobitni termin: [datum]"** (prvobitni datum relevantnog Odgođenog Održavanja).
>
> Prvobitni datum **nije** važeći termin održavanja. Status Održavanja ostaje Odgođeno; Održavanje **ne** postaje Planirano i **ne** tretira se kao upcoming.
>
> **Granica datuma (PO-064-03):** Događaj ostaje informativno vidljiv na naslovnoj **do isteka prvobitnog datuma relevantnog Odgođenog Održavanja, uključujući taj dan**. Primjer: prvobitni datum 17.08.2026. — 17.08. je još podoban; 18.08. taj prvobitni datum je istekao za potrebe naslovne vidljivosti. Tehnička implementacija granice (vremenska zona, upit) uređuje se u TS-u, ne u BM-u.
>
> **Jedno Odgođeno:** Važi gornje pravilo za to jedno Održavanje.
>
> **Više Odgođenih bez Planiranog (PO-064-08…10):** Informativnu karticu određuje **najbliže Odgođeno Održavanje čiji prvobitni datum još nije istekao**. Događaj ostaje informativno vidljiv na naslovnoj dok postoji najmanje jedno takvo Odgođeno Održavanje. Kada prvobitni datum trenutno prikazanog Odgođenog istekne, kartica prelazi na **sljedeće najbliže** Odgođeno čiji prvobitni datum još nije istekao (PO-064-09).
>
> **Nakon isteka posljednjeg (PO-064-11):** Kada više ne postoji naredno Planirano Održavanje niti Odgođeno Održavanje čiji prvobitni datum još nije istekao, Događaj se **više ne prikazuje** na naslovnoj po ovom posebnom informativnom osnovu.
>
> **Šta se ne mijenja (PO-064-12, PO-064-14):** Događaj se ne briše; status Događaja se ne mijenja zbog isteka informativne vidljivosti; ostaje dostupan kroz „Pretraga i pregled" i na javnom detalju; detalj nastavlja BM-PK-31 / BM-PK-36. Ne mijenjaju se lifecycle, statusni modeli, Otkazano, automatsko arhiviranje, Urednik/Moderator tokovi, approval, Change Proposal, newsletter, brisanje, niti PATCH-063 pravila o opcionim razlozima.
>
> **Novi termin / povratak u Planirano (PO-064-13):** Ako se Odgođenom Održavanju odredi novi važeći termin i Održavanje se vrati u Planirano (BM-TR-14 / BM-TR-15): prestaje informativna naslovna vidljivost **tog** Održavanja po prvobitnom terminu; novi Planirani termin učestvuje u standardnom izboru po BM-PK-29; kartica prikazuje novi važeći termin; prvobitni datum se **ne** koristi kao aktivni termin kartice. Istorijski prikaz na detalju ostaje po postojećim pravilima BM-PK-31 / BM-PK-36.
>
> **Pojam:** „Informativno relevantno Odgođeno Održavanje" = Odgođeno Održavanje bez poznatog novog termina čiji prvobitni datum još nije istekao za potrebe naslovne vidljivosti (BM-GL-26). Odvojeno je od narednog relevantnog Planiranog Održavanja (BM-PK-29).

### BM-PK-38 — Javna vidljivost Otkazane Manifestacije (PO-6B-08)

> Manifestacija sa statusom Otkazana ostaje javno vidljiva do isteka njenog izvedenog perioda.
>
> Otkazana Manifestacija ostaje na aktivnoj javnoj listi Manifestacija do isteka izvedenog perioda; kartica i detalj jasno prikazuju oznaku „Otkazana“; program ostaje vidljiv.
>
> Otkazivanje Manifestacije ne mijenja automatski statuse povezanih Događaja ni Održavanja (BM-MF-07, BM-MF-15).
>
> Nakon isteka izvedenog perioda, postojeći lifecycle mehanizam automatski prevodi Otkazanu Manifestaciju u Arhiviranu (BM-MF-06).
>
> Arhivirana Manifestacija više nije dio aktivne javne liste; njen direktni canonical javni detalj ostaje dostupan kao istorijski zapis.
>
> Otkazana nije obavezna međufaza: Objavljena → Arhivirana i Objavljena → Otkazana → Arhivirana su različiti legitimni tokovi.
>
> Manifestacija u V1 nema status Odgođena; odgađanje pripada Održavanju (BM-MF-11).

### BM-PK-39 — Javni prikaz veze Događaj → Manifestacija (PO-6B-09)

> Ako Događaj pripada Manifestaciji, na javnom detalju Događaja informacija o pripadnosti prikazuje se samo kada je povezana Manifestacija javno dostupna.
>
> Matrica: Nacrt / Na odobrenju / Vraćena na doradu — bez prikaza i bez linka (anti-leak); Objavljena / Otkazana / Arhivirana — prikaz i link na canonical javni detalj Manifestacije.
>
> Za neobjavljenu Manifestaciju ne smije procuriti naziv, link, status, identifikator ni drugi javni trag pripadnosti. Pravilo je serversko (poslovna vidljivost), ne samo vizuelno skrivanje.
>
> Na detalju Događaja ne prikazuje se status Manifestacije uz naziv. Status Manifestacije vidi se na njenom javnom detalju.

### BM-PK-40 — Globalno sortiranje Pretrage kada je Tip sadržaja = Sve (PO-6B-10)

> Kada je na stranici „Pretraga i pregled“ izabran Tip sadržaja **Sve**, Događaji i Manifestacije prikazuju se u **jednom** zajedničkom rezultatu i sortiraju se **zajedno** prema zajedničkom vremenskom ključu.
>
> Vremenski ključ:
>
> * za **Događaj** — prvo naredno relevantno Održavanje (ista semantika kao BM-PK-30);
> * za **Manifestaciju** — početak izvedenog perioda Manifestacije (isti izvor kao za javnu listu Manifestacija).
>
> Redoslijed:
>
> 1. zapisi sa definisanim vremenskim ključem prije zapisa bez ključa;
> 2. vremenski ključ rastuće;
> 3. Naziv rastuće;
> 4. Tip sadržaja samo kao **stabilni tehnički** tie-breaker (nije poslovni prioritet);
> 5. identifikator rastuće.
>
> Zapisi bez vremenskog ključa dolaze na kraj.
>
> **Ne** smije se grupisati rezultat kao „prvo svi Događaji, zatim sve Manifestacije“ niti obrnuto. Tip sadržaja nije poslovni prioritet za raspored slotova.
>
> Ovo pravilo važi **samo** za Tip = Sve.
>
> * Tip = Događaji → BM-PK-30 (nepromijenjen).
> * Tip = Manifestacije → postojeći poredak aktivne liste Manifestacija (datum početka → naziv; nepromijenjen).

---

---

# BM-12 Urednički portal

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definišu se poslovna pravila rada Uredničkog portala modula Kalendar kulture.

Urednički portal predstavlja poslovnu cjelinu kroz koju Moderatori i Urednici izvršavaju poslovne radnje definisane ovim Business Modelom. Organizator ne pristupa uredničkom portalu direktno.

Poslovna pravila rada pojedinačnih poslovnih entiteta definisana su odgovarajućim poglavljima ovog Business Modela.

## Poslovna pravila

### BM-EP-01 — Namjena

> Urednički portal namijenjen je upravljanju kulturnim sadržajem i sprovođenju uredničkog procesa od kreiranja događaja do njegovog objavljivanja.

### BM-EP-02 — Poslovne uloge

> Urednički portal koriste:
>
> * Moderatori;
> * Urednici.
>
> Organizator ne pristupa uredničkom portalu. Moderatori i Urednici koriste funkcionalnosti Uredničkog portala u skladu sa ovlašćenjima definisanim ovim Business Modelom.

### BM-EP-03 — Poslovne funkcionalnosti

> Urednički portal omogućava:
>
> * upravljanje podacima Organizatora;
> * upravljanje Događajima (uključujući urednički tok U pripremi → Objavi i Moderatorski tok Nacrt → Pošalji na odobrenje);
> * upravljanje Manifestacijama;
> * upravljanje održavanjima događaja;
> * upravljanje Medijima;
> * pregled statusa entiteta;
> * sprovođenje uredničkog procesa;
> * pregled poslovnih obavještenja i sistemskih informacija namijenjenih Moderatorima i Urednicima.

### BM-EP-04 — Poslovni procesi

> Svi poslovni procesi koji se izvršavaju kroz Urednički portal sprovode se u skladu sa poslovnim pravilima definisanim ovim Business Modelom.
>
> Urednički portal ne mijenja poslovna pravila već omogućava njihovu primjenu.

### BM-EP-05 — Poslovna odgovornost

> Svaka poslovna uloga odgovorna je za poslovne radnje koje izvrši koristeći Urednički portal.
>
> Odgovornost se određuje u skladu sa poslovnom ulogom.

### BM-EP-06 — Poslovna vidljivost

> Moderatorima i Urednicima dostupni su isključivo podaci i funkcionalnosti za koje imaju odgovarajuća poslovna ovlašćenja.
>
> Pristup poslovnim podacima određuje se poslovnim pravilima definisanim ovim Business Modelom.

### BM-EP-07 — Saradnja poslovnih uloga

> Moderatori i Urednici međusobno sarađuju kroz poslovne procese definisane ovim Business Modelom.
>
> Svaka poslovna uloga izvršava isključivo poslovne radnje koje su joj dodijeljene.

### BM-EP-08 — Jedinstven poslovni sistem

> Urednički portal predstavlja sastavni dio modula Kalendar kulture i koristi zajedničke poslovne entitete, poslovna pravila i definicije utvrđene ovim Business Modelom.

### BM-EP-09 — Evidencija aktivnosti

> Poslovno značajne radnje izvršene kroz Urednički portal evidentiraju se u Evidenciji aktivnosti u skladu sa pravilima definisanim ovim Business Modelom.

### BM-EP-10 — Završna odredba

> Urednički portal predstavlja poslovnu cjelinu kroz koju se izvršava urednički proces modula Kalendar kulture.
>
> Sve poslovne radnje izvršene kroz Urednički portal podliježu poslovnim pravilima definisanim ovim Business Modelom.

### BM-EP-11 — Lista Događaji i urednički tok U pripremi

> Događaji Urednika u stanju **U pripremi** prikazuju se na zajedničkoj listi **Događaji**. Ne uvodi se poseban ekran, navigaciona stavka niti modul za „U pripremi“.
>
> Na istoj listi mogu postojati Događaji različitih relevantnih stanja. Za „U pripremi“ osnovne akcije su **Uredi** i **Obriši**; objavljivanje se vrši iz samog Događaja (BM-UR-14 / BM-UR-15).
>
> Moderatorski tok zadržava poslovni **Nacrt** i akciju **Pošalji na odobrenje**.

---

# BM-13 Newsletter

**Status poglavlja:** USVOJENO

## Poslovna pravila

### BM-NL-01 — Definicija

> Newsletter predstavlja funkcionalnost modula Kalendara kulture namijenjenu informisanju zainteresovanih korisnika o novoobjavljenim javno dostupnim kulturnim događajima i o poslovno značajnim promjenama događaja koje utiču na odluku o prisustvu (otkazivanje, odlaganje, promjena datuma, vremena ili lokacije održavanja).

### BM-NL-02 — Svrha

> Newsletter služi isključivo informisanju korisnika o kulturnim događajima i o njihovim poslovno značajnim promjenama u Kalendaru kulture.

### BM-NL-03 — Odnos prema uredničkom procesu

> Newsletter nije dio uredničkog procesa i ne koristi se za poslovnu komunikaciju između Organizatora, Moderatora, Urednika i Administratora platforme. Organizator, Moderator i Urednik ne upravljaju pretplatnicima, ne pokreću ručno slanje Newslettera i ne biraju ručno događaje za Newsletter.

### BM-NL-04 — Pretplata

> Svaki registrovani i verifikovani korisnik može se dobrovoljno prijaviti na newsletter Kalendara kulture. Prijava na newsletter nije uslov za korišćenje Kalendara kulture. Pretplatnik može izabrati sve Organizatore ili jednog ili više konkretnih Organizatora. Ako korisnik ne izabere nijednog konkretnog Organizatora, sistem smatra da je izabrao sve Organizatore. Izbor Organizatora je isključivo filter sadržaja i ne daje prava nad Organizatorom niti događajima.

### BM-NL-05 — Odjava

> Korisnik može u svakom trenutku odjaviti prijem newslettera. Odjava ne briše korisnički nalog niti utiče na pristup drugim modulima platforme.

### BM-NL-06 — Sadržaj newslettera

> Newsletter sadrži kratak pregled novoobjavljenih događaja i/ili poslovno značajnih promjena događaja koji odgovaraju aktivnoj pretplati i pravilima slanja. Za svaki događaj prikazuju se osnovne informacije i veza ka detaljima događaja, u skladu sa posljednjim poslovno važećim stanjem događaja u trenutku pripreme poruke. Događaji se grupišu po Organizatoru. Za svakog Organizatora Newsletter sadrži vezu ka objavljenom pregledu događaja tog Organizatora na portalu Kalendara kulture. Više novoobjavljenih događaja može biti objedinjeno u jednu Newsletter poruku. Isti događaj se ne prikazuje više puta zbog više termina; relevantni budući termini mogu biti prikazani unutar jedne stavke događaja.

### BM-NL-07 — Periodična provjera i prioritetna obavještenja

> Sistem periodično provjerava da li postoje novoobjavljeni događaji koji odgovaraju aktivnim pretplatama i, kada postoje, šalje objedinjeni Newsletter odgovarajućim pretplatnicima. Newsletter nije vezan za fiksni dan u sedmici niti za unaprijed definisanu kalendarsku sedmicu. Obavještenja o otkazivanju, odlaganju ili promjeni datuma, vremena ili lokacije predstavljaju prioritetna obavještenja i šalju se bez nepotrebnog odlaganja kako bi pretplatnici blagovremeno bili informisani. Tačan interval periodične provjere, način prioritetnog slanja i tehnička realizacija nisu predmet ovog Business Modela.

### BM-NL-08 — Nezavisnost od poslovnih procesa

> Pretplata na newsletter nema uticaja na prava korisnika niti na poslovne procese definisane ovim Business Modelom. Poslovni procesi funkcionišu nezavisno od prijave ili odjave korisnika na newsletter.

### BM-NL-09 — Objavljeni sadržaj i okidač prvog uključivanja

> Prvo uključivanje događaja u Newsletter kao novoobjavljenog sadržaja moguće je isključivo za događaje u statusu Objavljen. Javno objavljivanje događaja predstavlja poslovni okidač za to prvo uključivanje. Događaj ne mora biti poslat istog trenutka kada je objavljen; postaje kandidat za naredni odgovarajući Newsletter. Događaji koji nijesu objavljeni ne mogu biti uključeni kao novoobjavljeni sadržaj.

### BM-NL-10 — Relevantnost budućeg termina

> Događaj može biti uključen u Newsletter kao novoobjavljeni sadržaj samo ako u trenutku pripreme ima najmanje jedno buduće održavanje. Događaj bez budućeg održavanja ne ulazi kao novoobjavljeni sadržaj. Ovo pravilo ne sprečava prioritetno obavještenje o otkazivanju događaja ili termina koji je pretplatniku prethodno bio poslat.

### BM-NL-11 — Zaštita od ponovnog slanja prvog uključivanja

> Isti događaj se istom pretplatniku ne šalje ponovo kao novoobjavljeni sadržaj samo zato što sistem ponovo izvršava periodičnu provjeru. Događaj objavljen nakon prethodnog Newsletter slanja može biti uključen u naredno slanje ako je i dalje relevantan i odgovara aktivnoj pretplati.

### BM-NL-12 — Aktivni pretplatnik

> Aktivni pretplatnik je registrovani i verifikovani korisnik sa aktivnom Newsletter pretplatom koji nije izvršio odjavu. Postojanje odgovarajućih događaja nije dio definicije aktivnog pretplatnika.

### BM-NL-13 — Ne-slati prazan Newsletter

> Ako za konkretnog aktivnog pretplatnika u trenutku pripreme nema nijednog odgovarajućeg novoobjavljenog događaja niti prioritetnog obavještenja prema pravilima slanja, Newsletter mu se ne šalje. Sistem ne dodaje događaje drugih Organizatora samo da bi poruka imala sadržaj.

### BM-NL-14 — Uređivačke izmjene nisu okidač

> Ispravka pravopisnih grešaka, izmjena opisa, izmjena ili dodavanje fotografija, izmjena dodatnih informacija koje ne utiču na održavanje događaja i druge uređivačke izmjene koje ne mijenjaju način održavanja događaja ne predstavljaju Newsletter okidač.

### BM-NL-15 — Potvrda prve aktivacije

> Nakon prve uspješne aktivacije Newsletter pretplate sistem šalje potvrdu o aktiviranoj pretplati. Double opt-in nije obavezan u V1.

### BM-NL-16 — Granice V1

> U V1 opsegu Newslettera nisu: izbor kategorija događaja, personalizacija prema ponašanju, automatske preporuke, profilisanje, ručni izbor događaja od strane Urednika, Newsletter kampanje Organizatora, ručno slanje Newslettera, različiti Newsletteri po ulozi, te definisanje tačnog tehničkog intervala periodične ili prioritetne isporuke.

### BM-NL-17 — Poslovno značajne promjene kao okidač

> Otkazivanje događaja, odlaganje događaja, promjena datuma održavanja, promjena vremena održavanja i promjena lokacije održavanja predstavljaju poslovno značajne promjene i Newsletter okidače.

### BM-NL-18 — Publika obavještenja o promjeni

> Obavještenje o poslovno značajnoj promjeni događaja šalje se isključivo aktivnim pretplatnicima kojima je isti događaj prethodno bio uključen u Newsletter. Pretplatnici koji nisu dobili prvobitnu informaciju o događaju ne dobijaju obavještenje o njegovom otkazivanju ili izmjeni.

### BM-NL-19 — Promjene kod događaja sa više termina

> Ako je promijenjen ili otkazan samo jedan termin događaja sa više termina, obavještenje se odnosi samo na taj termin i ne tretira se kao otkazivanje cijelog događaja. Ako promjena utiče na kompletan događaj, obavještenje se odnosi na cijeli događaj.

### BM-NL-20 — Prioritetna obavještenja

> Obavještenja o otkazivanju, odlaganju ili promjeni datuma, vremena ili lokacije šalju se bez nepotrebnog odlaganja kako bi pretplatnici blagovremeno bili informisani. Prioritetna obavještenja mogu biti objedinjena ako time nije ugrožena njihova blagovremenost. Objedinjavanje više novoobjavljenih događaja u jednu poruku ostaje dozvoljeno za tip sadržaja prvog uključivanja.

### BM-NL-21 — Zaštita od ponovnog slanja iste promjene

> Ista poslovno značajna promjena istog događaja (ili istog termina) ne smije biti više puta poslata istom pretplatniku. Ovo pravilo je odvojeno od zaštite od ponovnog slanja prvog uključivanja događaja (BM-NL-11).

### BM-NL-22 — Višestruke poslovno značajne promjene prije slanja

> Ako prije slanja Newslettera nad istim događajem nastane više uzastopnih poslovno značajnih promjena, pretplatniku se dostavlja jedinstveno obavještenje koje odražava posljednje važeće stanje događaja. Ne šalje se istorija svih promjena niti međukoraci.

### BM-NL-23 — Posljednje važeće stanje

> Newsletter i prioritetna obavještenja prikazuju posljednje poslovno važeće stanje događaja u trenutku pripreme poruke.

### BM-NL-24 — Objedinjavanje prioritetnih promjena

> Prioritetna obavještenja mogu biti objedinjena ako time nije ugrožena njihova blagovremenost. Više gotovo istovremenih poslovno značajnih promjena može biti predstavljeno jednom porukom, uz zadržavanje zahtjeva za blagovremenim informisanjem pretplatnika.

### BM-NL-25 — Zabranjena kontradiktorna obavještenja

> Pretplatniku se ne šalju međusobno kontradiktorna obavještenja za isti događaj u okviru istog ciklusa pripreme Newslettera. Korisnik dobija jedno konačno poslovno stanje događaja.

---

# BM-14 Evidencija aktivnosti (Audit log)

**Status poglavlja:** USVOJENO

## Poslovna pravila

### BM-AL-01 — Definicija evidencije aktivnosti

> Evidencija aktivnosti predstavlja poslovni zapis o poslovno značajnim radnjama izvršenim u modulu Kalendara kulture. Njena svrha je dokumentovanje izvršenih poslovnih radnji, utvrđivanje odgovornosti korisnika i omogućavanje njihove naknadne provjere.

### BM-AL-02 — Odnos prema tehničkim logovima

> Evidencija aktivnosti predstavlja poslovnu evidenciju izvršenih radnji i ne predstavlja zamjenu za tehničke sistemske logove niti druge tehničke mehanizme evidencije.

### BM-AL-03 — Poslovno značajne aktivnosti

> Evidencija aktivnosti obuhvata isključivo poslovno značajne aktivnosti koje utiču na poslovne podatke ili poslovne procese definisane ovim Business Modelom. Aktivnosti koje nemaju poslovni značaj ne evidentiraju se u evidenciji aktivnosti.

### BM-AL-04 — Nepromjenjivost evidencije aktivnosti

> Jednom evidentirana aktivnost postaje trajni dio evidencije aktivnosti. Evidentirane aktivnosti ne mogu se naknadno mijenjati niti brisati kroz redovno korišćenje sistema.

### BM-AL-05 — Nezavisnost evidencije aktivnosti

> Evidencija aktivnosti služi isključivo dokumentovanju izvršenih poslovnih radnji. Njeno postojanje niti sadržaj ne utiču na tok poslovnih procesa, poslovna pravila niti prava korisnika definisana ovim Business Modelom.

### BM-AL-06 — Pristup evidenciji aktivnosti

> Pristup evidenciji aktivnosti ima isključivo Administrator platforme. Ostali korisnici sistema nemaju direktan pristup evidenciji aktivnosti.

### BM-AL-07 — Oblasti evidencije aktivnosti

> Evidencija aktivnosti obuhvata poslovno značajne aktivnosti koje se odnose na entitete i administrativne funkcije definisane ovim Business Modelom, uključujući Organizatora, Moderatora, Događaj, Manifestaciju, aktivnosti nad Održavanjem događaja (u okviru kataloga Događaji definisanog u FS §5.16) i Newsletter, te zahtjeve za kreiranje Organizatora i zahtjeve za dodjelu ili uklanjanje Moderatora (podnosilac, predloženi Moderator gdje je primjenjivo, datum i vrijeme podnošenja, Urednik koji je odlučio, datum i vrijeme odluke). Poslovne aktivnosti koje se evidentiraju za pojedine oblasti definišu se funkcionalnom i tehničkom specifikacijom u skladu sa ovim Business Modelom. Manifestacija je ravnopravan poslovni entitet u centralnoj evidenciji.

### BM-AL-08 — Namjena evidencije aktivnosti

> Evidencija aktivnosti služi reviziji, kontroli i naknadnoj provjeri izvršenih poslovnih radnji. Evidencija aktivnosti nije sredstvo komunikacije niti predstavlja poslovno obavještenje.

---

# BM-15 Opšta poslovna pravila

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definišu se opšta poslovna pravila koja važe za sve poslovne cjeline Kalendara kulture, osim kada je pojedinim poglavljem ovog Business Modela izričito drugačije određeno.

Ova pravila predstavljaju osnov za dosljedno tumačenje i primjenu svih poslovnih procesa definisanih ovim Business Modelom.

## Poslovna pravila

### BM-GR-01 — Dosljednost poslovnih podataka

> Sistem mora obezbijediti da poslovni podaci ostanu međusobno usklađeni tokom cijelog životnog ciklusa entiteta.
>
> Poslovna radnja koja bi narušila dosljednost poslovnih podataka nije dozvoljena.

### BM-GR-02 — Jedinstveni izvor podataka

> Svaki poslovni podatak održava se na jednom mjestu u sistemu.
>
> Zajednički podaci koriste se kroz cijeli sistem kako bi se izbjeglo dupliranje podataka i obezbijedila njihova dosljednost.

### BM-GR-03 — Životni ciklus entiteta

> Svaki entitet prolazi kroz životni ciklus definisan ovim Business Modelom.
>
> Status predstavlja trenutno poslovno stanje entiteta.
>
> Promjena statusa predstavlja dio poslovnog procesa i može se izvršiti isključivo u skladu sa pravilima definisanim ovim Business Modelom.

### BM-GR-04 — Očuvanje poslovne istorije

> Poslovna istorija predstavlja sastavni dio sistema.
>
> Kada je potrebno onemogućiti dalje korišćenje entiteta, primjenjuju se poslovna pravila aktivacije, deaktivacije ili arhiviranja, u skladu sa prirodom pojedinog entiteta.
>
> Brisanje poslovnih podataka primjenjuje se isključivo kada je to izričito predviđeno ovim Business Modelom.

### BM-GR-05 — Automatske poslovne radnje

> Sistem može automatski izvršavati poslovne radnje kada je njihovo izvršavanje definisano poslovnim pravilima.
>
> Automatski izvršene radnje imaju isti poslovni značaj kao radnje koje izvršava korisnik.

### BM-GR-06 — Predvidivost poslovnog ponašanja

> Sistem primjenjuje poslovna pravila na dosljedan i predvidiv način.
>
> Jednaki poslovni uslovi uvijek proizvode isti poslovni rezultat, osim kada je ovim Business Modelom izričito definisano drugačije.

### BM-GR-07 — Primjena posebnih poslovnih pravila

> Kada je za pojedini poslovni proces ili entitet ovim Business Modelom propisano posebno pravilo, ono ima prednost u odnosu na opšta poslovna pravila iz ovog poglavlja.

---

# BM-16 Rječnik poslovnih pojmova

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definišu se osnovni poslovni pojmovi koji se koriste u Business Modelu modula Kalendar kulture.

Definicije predstavljaju zajednički referentni okvir za sve učesnike u planiranju, razvoju, održavanju i korišćenju sistema, sa ciljem obezbjeđivanja jedinstvenog razumijevanja poslovnih pravila i terminologije.

**Terminološko pravilo:** Pojam „Termin“ koristi se isključivo u značenju: Datum održavanja je obavezan, a vrijeme može biti definisano. Nije dozvoljeno koristiti riječ „termin“ kao naziv ili sinonim za pojedinačno održavanje događaja ili za poslovni entitet koji ima lokaciju, status, audit ili sopstveni životni ciklus.

## Poslovni pojmovi

### BM-GL-01 — Entitet

> Poslovna cjelina kojom sistem upravlja i o kojoj vodi podatke.
>
> Primjeri entiteta su Organizator, Manifestacija, Događaj, Održavanje događaja, Lokacija i Kategorija.

### BM-GL-02 — Životni ciklus

> Niz poslovnih stanja kroz koja entitet prolazi od svog nastanka do završetka ili arhiviranja, u skladu sa poslovnim pravilima.

### BM-GL-03 — Status

> Trenutno poslovno stanje entiteta.
>
> Status određuje koje su poslovne radnje nad entitetom dozvoljene u skladu sa ovim Business Modelom.

### BM-GL-04 — Poslovni proces

> Skup međusobno povezanih poslovnih radnji kojima se upravlja životnim ciklusom jednog ili više entiteta.

### BM-GL-05 — Poslovna radnja

> Pojedinačna aktivnost koja predstavlja dio poslovnog procesa i proizvodi poslovni rezultat ili mijenja stanje entiteta.

### BM-GL-06 — Organizator

> Poslovni entitet i nosilac sadržaja u Kalendaru kulture. Organizator nije korisnik sistema i nije korisnička uloga. Operativno upravljanje sadržajem u ime Organizatora obavljaju Moderatori.

### BM-GL-07 — Moderator

> Ovlašćeni predstavnik Organizatora koji u ime Organizatora koristi Kalendar kulture.
>
> Moderator je zasebna poslovna uloga i nije isto što i Urednik.
>
> Moderator upravlja podacima Organizatora, Manifestacijama i Događajima u skladu sa dodijeljenim ovlašćenjima.

### BM-GL-08 — Urednik

> Administrator Uredničkog portala Kalendara kulture, odgovoran za pregled, odobravanje i objavljivanje sadržaja Moderatorskog toka, te za samostalno kreiranje, pripremu, objavu i neposredno upravljanje Događajima bez registrovanog Organizatora.
>
> Uloga Urednika je isključiva unutar poslovnog modela Kalendara kulture. Urednik nije Organizator i nije Moderator Organizatora.

### BM-GL-09 — Administrator platforme

> Korisnik odgovoran za administraciju platforme, upravljanje korisnicima, sistemskim podešavanjima i evidencijom aktivnosti.
>
> Administrator platforme ne učestvuje u uredničkom procesu.

### BM-GL-10 — Događaj

> Osnovna poslovna cjelina Kalendara kulture koja predstavlja pojedinačni kulturni sadržaj namijenjen objavljivanju.
>
> Događaj može imati jedno ili više održavanja.

### BM-GL-22 — Održavanje događaja

> Jedno konkretno održavanje jednog događaja, sa sopstvenim terminom i, kada je primjenjivo, lokacijom, statusom i drugim poslovnim svojstvima.
>
> Jedan događaj može imati jedno ili više održavanja. Održavanje nije isto što i Termin. Datum održavanja je obavezan, a vrijeme može biti definisano.

### BM-GL-11 — Manifestacija

> Poslovna cjelina koja povezuje više međusobno povezanih Događaja u okviru jedinstvenog programa. Ima sopstveni životni ciklus, nezavisan od životnih ciklusa Događaja i Održavanja. Organizator je opcioni. Nema sopstvenih kategorija, lokacija ni održavanja.

### BM-GL-12 — Termin

> Datum održavanja je obavezan, a vrijeme može biti definisano. Termin nije samostalan poslovni entitet.
>
> Termin uvijek postoji u kontekstu održavanja događaja i ne smije se koristiti kao sinonim za održavanje događaja niti za entitet koji ima lokaciju, status, audit ili sopstveni životni ciklus.

### BM-GL-13 — Lokacija

> Samostalan poslovni entitet opcionog centralnog kataloga Lokacija koji predstavlja mjesto na kojem se događaj konkretno održava. Korišćenje kataloške Lokacije je opciono; kada se koristi, referencira se stabilnim identifikatorom. Ručno uneseni naziv Lokacije je dozvoljen bez obavezne kataloške veze. U V1 obuhvata isključivo fizičke Lokacije.

### BM-GL-14 — Kategorija

> Zapis poslovnog kataloga koji predstavlja osnovnu klasifikaciju Događaja (**vrsta Događaja**). Kategorija nije tehnička ENUM vrijednost. Katalog kategorija je proširiv. Početni V1 sadržaj kataloga usvojen je odlukom TS7-PO-07 / BM-KO-09. Događaj ima najviše jednu primarnu kategoriju; primarna kategorija je obavezna prije slanja na odobrenje i objave. Kategorija nije Manifestacija niti tip Organizatora (BM-KO-10).

### BM-GL-15 — Mediji

> Samostalan poslovni entitet tipa Fotografija i zajednički platformski resurs bez poslovnog vlasnika. U V1 se povezuje sa Događajem (naslovna fotografija), Manifestacijom (naslovna fotografija) ili Kategorijom (podrazumijevana fotografija) u skladu sa zatvorenim katalogom namjena (BM-09).

### BM-GL-16 — Korisnički portal

> Dio Kalendara kulture namijenjen korisnicima za pregled kulturnih događaja i korišćenje funkcionalnosti dostupnih u skladu sa njihovim ovlašćenjima.

### BM-GL-17 — Urednički portal

> Dio sistema namijenjen Urednicima za upravljanje poslovnim procesom pregleda, uređivanja, odobravanja i objavljivanja sadržaja.

### BM-GL-18 — Sistemska administracija

> Dio sistema namijenjen Administratoru platforme za upravljanje korisnicima, sistemskim podešavanjima i administrativnim funkcijama.

### BM-GL-19 — Newsletter

> Funkcionalnost namijenjena informisanju korisnika o novoobjavljenim kulturnim događajima i o poslovno značajnim promjenama događaja koje utiču na odluku o prisustvu.
>
> Newsletter nije dio uredničkog procesa niti predstavlja poslovno obavještenje. Javno objavljivanje događaja predstavlja poslovni okidač za prvo uključivanje; otkazivanje, odlaganje i promjena datuma, vremena ili lokacije takođe predstavljaju Newsletter okidače.

### BM-GL-20 — Evidencija aktivnosti

> Evidencija aktivnosti predstavlja skup poslovno značajnih zapisa koji omogućavaju reviziju, kontrolu, odgovornost korisnika i naknadnu provjeru izvršenih radnji.

### BM-GL-23 — Oznaka

> Zapis poslovnog kataloga koji predstavlja dodatnu klasifikaciju Događaja. Oznake ulaze u V1. Jedan događaj može imati više oznaka. Oznaka nije zamjena za primarnu kategoriju. Katalogom oznaka upravlja isključivo Urednik.

### BM-GL-24 — U pripremi

> Poslovni prikaz Urednikovog Događaja koji je sačuvan, a još nikada nije bio objavljen. Nije novi status životnog ciklusa Događaja. Nije isto što i Moderatorski **Nacrt**.

### BM-GL-25 — Ručno uneseni naziv Organizatora

> Opcion tekstualni naziv neregistrovanog Organizatora na Događaju u uredničkom toku. Obuhvata samo naziv. Ne kreira registrovanog Organizatora i ne pokreće postupak odobravanja.

### BM-GL-26 — Informativna naslovna vidljivost Odgođenog

> Poseban javni mehanizam na naslovnoj strani kojim Objavljeni Događaj, bez narednog Planiranog Održavanja, ostaje vidljiv zbog Odgođenog Održavanja bez poznatog novog termina, do isteka prvobitnog datuma uključivo (BM-PK-37). **Nije** isto što i Planirano Održavanje, naredno relevantno Planirano Održavanje (BM-PK-29), niti „upcoming" termin. Kartica u tom režimu komunicira „Odgođeno" i „Prvobitni termin"; prvobitni datum nije važeći termin održavanja.

### BM-GL-21 — Završna odredba

> Pojmovi definisani ovim poglavljem imaju isto značenje u svim dijelovima Business Modela, osim ako je za pojedinu poslovnu cjelinu izričito drugačije određeno.
>
> Dosljedna primjena ovih definicija obezbjeđuje jedinstveno tumačenje poslovnih pravila i terminologije kroz cjelokupnu dokumentaciju modula Kalendar kulture.

---

# BM-17 Arhitektura poslovnih cjelina

**Status poglavlja:** USVOJENO

## Svrha

Ovim poglavljem definiše se poslovna arhitektura modula Kalendar kulture i međusobni odnos njegovih poslovnih cjelina.

Arhitektura poslovnih cjelina određuje odgovornosti, granice i međusobnu saradnju pojedinih dijelova sistema u skladu sa ovim Business Modelom.

## Poslovna arhitektura

### BM-AR-01 — Poslovne cjeline sistema

> Poslovnu arhitekturu modula Kalendar kulture čine sljedeće poslovne cjeline:
>
> * Portal Kalendara kulture
> * Urednički portal
> * Sistemska administracija
>
> Sve poslovne cjeline predstavljaju sastavne djelove jedinstvenog poslovnog sistema.

### BM-AR-02 — Portal Kalendara kulture

> Portal Kalendara kulture predstavlja javni dio sistema namijenjen pregledanju javno dostupnih kulturnih događaja i korišćenju javno dostupnih funkcionalnosti.
>
> Portal prikazuje javno dostupan sadržaj u skladu sa BM-PK-13 i ostalim pravilima Portala. Javno dostupni događaji uključuju `published` i `cancelled` u skladu sa pravilima prikaza (aktivne površine i portalna Arhiva po datumu). Interni status `archived` nije javno otvoren kroz CR-004B. Interni lifecycle status nije isto što i javni status prikaza.
>
> Portal se razvija evolutivno u skladu sa BM-PK-16 (IA-01).

### BM-AR-03 — Urednički portal

> Urednički portal predstavlja poslovnu cjelinu namijenjenu Moderatorima i Urednicima za upravljanje kulturnim sadržajem i uredničkim procesom.
>
> Poslovna pravila rada Uredničkog portala definisana su odgovarajućim poglavljima ovog Business Modela.

### BM-AR-04 — Sistemska administracija

> Sistemska administracija predstavlja poslovnu cjelinu namijenjenu Administratoru platforme za upravljanje korisnicima, sistemskim podešavanjima, administrativnim funkcijama i tehničkim održavanjem sistema.
>
> Administrator platforme ne učestvuje u uredničkom procesu, osim kada je to ovim Business Modelom izričito definisano.

### BM-AR-05 — Poslovna nezavisnost

> Svaka poslovna cjelina ima jasno definisanu poslovnu odgovornost.
>
> Poslovne cjeline međusobno sarađuju kroz poslovne procese definisane ovim Business Modelom, pri čemu zadržavaju svoju poslovnu nezavisnost.

### BM-AR-06 — Jedinstveni poslovni model

> Sve poslovne cjeline koriste zajedničke poslovne entitete, poslovna pravila i definicije utvrđene ovim Business Modelom.
>
> Poslovni podaci predstavljaju jedinstven izvor podataka bez obzira na poslovnu cjelinu kroz koju se koriste.

### BM-AR-07 — Razdvajanje odgovornosti

> Poslovne odgovornosti pojedinih poslovnih cjelina ne smiju se preklapati, osim kada je to izričito definisano ovim Business Modelom.
>
> Prava pristupa, ovlašćenja i poslovne odgovornosti određuju se u skladu sa ulogom korisnika i poslovnom cjelinom kojoj pripadaju.

### BM-AR-08 — Završna odredba

> Arhitektura poslovnih cjelina predstavlja osnov za organizaciju svih poslovnih procesa modula Kalendar kulture.
>
> Sve poslovne cjeline primjenjuju jedinstvena poslovna pravila i međusobno funkcionišu kao sastavni djelovi jedinstvenog informacionog sistema.

---

# 7. Završne odredbe

1. Business Model predstavlja **referentni poslovni dokument** modula Kalendar kulture.

2. Business Model je **izvor poslovnih pravila** za planiranje, razvoj, testiranje i održavanje sistema. Posljednja usvojena verzija predstavlja jedini izvor istine za poslovna pravila ovog modula.

3. **Functional Specification** razrađuje usvojena poslovna pravila u funkcionalne zahtjeve i ponašanje sistema, bez zamjene ovog Business Modela.

4. **Technical Specification** razrađuje tehničku realizaciju usvojenih poslovnih i funkcionalnih pravila.

5. **Implementation Strategy** definiše redoslijed, faznost i način uvođenja usvojene tehničke specifikacije u implementaciju.

6. Promjene Business Modela vrše se isključivo kroz **usvojenu metodologiju** upravljanja dokumentom i usvojene poslovne odluke.

7. Istorija izmjena vodi se kroz **PATCH evidenciju** u ovom dokumentu. Postojeći redovi istorije se ne mijenjaju.

8. Do sticanja statusa **Stable**, ovaj dokument je **živi dokument**: dopunjuje se i usklađuje kroz evidentirane PATCH-eve. Nakon Stable statusa, izmjene se i dalje vrše isključivo kroz usvojenu metodologiju i PATCH evidenciju.
