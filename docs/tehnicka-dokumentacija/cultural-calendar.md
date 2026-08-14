# Kalendar kulturnih događaja

**Poslednje ažuriranje:** 2026-08-14 (Newsletter ops: kanonski invokeri 6h / 5 min)
**Izvor u kodu:** `CulturalCalendarController`, `CulturalEventController`, `CulturalEvent` model; Newsletter: `SendCulturalCalendarNewsletter`, `SendCulturalCalendarPriorityNewsletter` (legacy weekly command ostaje na disku, runtime slanje isključeno)
**Tip dokumenta:** Technical Overview trenutne implementacije

---

## Namjena

Javni pregled kulturnih događaja Opštine Kotor: kalendar, lista događaja, arhiva, pregled po danu, newsletter pretplata.

---

## Ciljni poslovni model vs trenutna implementacija (terminologija)

**Ciljni poslovni model (BM / FS):**

* događaj ima jedno ili više **održavanja**;
* svako održavanje ima **termin** (datum i vrijeme) i može imati lokaciju, status i druga svojstva;
* **Termin** nije samostalan poslovni entitet.

**Trenutna implementacija V1:**

* nema zasebnog modela, tabele ni klase za održavanje događaja;
* polja `datum_od`, `datum_do`, `vrijeme`, `vrijeme_do` i `lokacija` (tekst) čuvaju se direktno na modelu `CulturalEvent`;
* jedan zapis događaja = jedan vremenski raspon + jedna tekstualna lokacija.

Ovo je implementaciono odstupanje od ciljnog modela, a ne greška Business Modela. Tehničko usklađivanje (nova tabela/model) zahtijeva zasebnu odluku i nije predmet ovog dokumenta.

---

## Poslovne uloge (Business Model) i trenutna implementacija

Business Model razlikuje sljedeće poslovne uloge:

* **Organizator** — nosilac sadržaja;
* **Moderator** (Moderator organizatora) — operativni korisnik Organizatora; **nije** Urednik;
* **Urednik** — odobrava Organizatore i sadržaj, te objavljuje događaje.

Funkcija **„Postani organizator“** usvojena je u Business Modelu: podnosilac zahtjeva nakon odobrenja Urednika automatski postaje prvi Moderator; naredne Moderatore predlažu postojeći Moderatori, a ovlašćenja dodjeljuje isključivo Urednik. Funkcionalnost **trenutno još nije implementirana** u aplikaciji.

### Trenutna implementacija

U produkcijskom kodu administracija događaja vezana je za tehničku ulogu `kk_admin`.

Uloga `kk_admin` odgovara poslovnoj ulozi **Urednika** Kalendara kulture i **ne** predstavlja ulogu Moderatora.

Organizator i Moderator, kao i proces „Postani organizator“, nisu još implementirani u aplikaciji.

---

## Rute (javne za prijavljene korisnike)

| Putanja | Ime | Opis |
|---------|-----|------|
| `/kalendar-kulture` | `cultural-calendar.index` | Početna kalendara |
| `/kalendar-kulture/pregled-dogadjaja` | `cultural-calendar.events` | Lista događaja |
| `/kalendar-kulture/arhiva-dogadjaja` | `cultural-calendar.archive` | Arhiva |
| `/kalendar-kulture/dogadjaj/{event}` | `cultural-calendar.show` | Detalj događaja |
| `/kalendar-kulture/dan/{date}` | `cultural-calendar.day` | Događaji za datum (nije standardni korisnički tok; za `kk_admin` redirect na create) |
| POST `/kalendar-kulture/newsletter` | `cultural-calendar.newsletter.store` | Pretplata |

**UI ponašanje (važeće):** u sekcijama **Pregled događaja**, **Arhiva događaja**, **Istaknuti događaji** i **Naredni događaji** kartice su klikabilne i otvaraju stranicu detalja događaja.

---

## Administracija (`kk_admin` / Urednik)

Resource rute: `/kalendar-kulture/dogadjaji` → `cultural-events.*`  
Kontroler: `CulturalEventController` (bez `show` akcije).

`kk_admin` je ograničen na kalendar modul (`RestrictRoleModuleAccess`).

---

## Model `CulturalEvent`

### Statusi (`STATUSES`)

`draft`, `published`, `archived`, `cancelled`

### Kategorije (`CATEGORIES`)

Puni spisak u `app/Models/CulturalEvent.php` (uključujući i stavku `Nešto drugo`). Za test prije objave v. [cultural-calendar-test-checklist.md](cultural-calendar-test-checklist.md).

### Polja (sažetak)

Naslov, opis, vremenski raspon na događaju (`datum_od`, `datum_do`, `vrijeme`, opciono `vrijeme_do`), tekstualna `lokacija`, kategorija, status, `created_by` (user).

Nema zasebnog entiteta za **održavanje događaja**; termin (datum/vrijeme) i lokacija nisu modelovani kao svojstva održavanja već kao polja na `CulturalEvent` (vidi odstupanje u registru ispod).

### Naslovna fotografija

Sistem uvijek prikazuje jednu naslovnu fotografiju. Ako nije postavljena sopstvena slika, koristi se podrazumijevana fotografija kategorije (`CulturalEvent::imageUrl()`).

---

## Newsletter

Kanonske Artisan komande. Na produkciji: Laravel Toolkit Scheduled Tasks (direktni invoker). **Ne** dodavati `schedule:run` ako ti taskovi već postoje.

| Komanda | Produkcijski cron | Namjena |
|---------|-------------------|---------|
| `cultural-calendar:send-newsletter` | `0 */6 * * *` | Redovni `first_include` |
| `cultural-calendar:send-newsletter-priority` | `*/5 * * * *` | Prioritetne poslovno značajne izmjene |

Timezone rasporeda = `config('app.timezone')` (default **`Europe/Belgrade`**).

Legacy `cultural-calendar:send-weekly-newsletter` nije kanonski invoker; runtime slanje je isključeno. **Ne** zakazivati je u Plesku.

Kanonska pretplata: `NewsletterSubscription` na `User` (rute `newsletter.*`). E-mail-only `NewsletterSubscriber` nije SSOT i nije produkcioni invoker.

---

# Odstupanja trenutne implementacije od usvojenog funkcionalnog modela

Ovo poglavlje predstavlja zvanični registar razlika između dokumentacije (Business Model / Functional Specification) i trenutne implementacije.

U budućnosti sva nova odstupanja evidentirati isključivo u ovom poglavlju. Business Model i Functional Specification se zbog toga ne mijenjaju.

| Funkcionalnost | Business Model / Functional Specification | Trenutna implementacija | Status |
| -------------- | ----------------------------------------- | ----------------------- | ------ |
| Upravljanje događajima | Moderator → Urednik workflow. | `kk_admin` direktno upravlja svim događajima. | Planirano za implementaciju prije pune produkcije. |
| Postani organizator | Postoji: podnosilac → prvi Moderator nakon odobrenja Urednika. | Nije implementirano. | Planirano. |
| Dodavanje Moderatora | Postojeći Moderator predlaže; Urednik odobrava i dodjeljuje ovlašćenja; trajni audit zahtjeva. | Nije implementirano. | Planirano. |
| Status „Na odobrenju“ | Postoji. | Ne postoji. | Planirano. |
| Manifestacije | Poseban poslovni entitet. | Nije implementirano. | Planirano. |
| Automatsko arhiviranje | Događaji se automatski arhiviraju nakon završetka svih održavanja. | Portal prikazuje istekle `published` događaje u arhivi, ali status ostaje nepromijenjen. | Planirano usklađivanje. |
| Održavanje događaja (BM-06) | Događaj ima 1..N održavanja; svako ima termin (datum/vrijeme) i može imati lokaciju, status, ulaznice; ponavljanje generiše više održavanja. | Nema modela/tabele održavanja; `CulturalEvent` ima flat `datum_od`/`datum_do`/`vrijeme`/`vrijeme_do`/`lokacija`. Jedan događaj = jedan vremenski raspon + jedna tekst lokacija. | Planirano; tehničko usklađivanje zahtijeva zasebnu odluku (nije automatski predloženo). |
| Izmjene objavljenog događaja (FS 5.5) | Izmjene se čuvaju kao prijedlog; javno je vidljiva posljednja odobrena verzija do odluke Urednika; najviše jedan aktivan prijedlog po događaju. | `kk_admin` izmjenom direktno mijenja objavljeni zapis; nema prijedloga izmjena, verzionisanja ni odobravanja. | Planirano. |
| Kreiranje događaja (FS 5.5.3) | Moderator kreira događaj za svog Organizatora; može sačuvati nacrt ili poslati na odobrenje; nacrt nije javan; obavezna polja se validiraju prije slanja na odobrenje. | `kk_admin` kreira događaj direktno bez Organizatora, bez nacrta u smislu Moderator → Urednik toka i bez statusa „Na odobrenju“. | Planirano. |
| Uređivanje događaja (FS 5.5.4) | Tri scenarija: slobodno uređivanje nacrta; uređivanje dok čeka odobrenje do početka pregleda; zaključavanje tokom pregleda; objavljen događaj samo preko prijedloga izmjena. | `kk_admin` direktno uređuje zapis bez zaključavanja, bez razlikovanja nacrta / na odobrenju / prijedloga izmjena. | Planirano. |
| Slanje na odobrenje (FS 5.5.5) | Akcija „Pošalji na odobrenje“, validacija, status „Na odobrenju“, obavještavanje Urednika, mogućnost povlačenja do početka pregleda, interna napomena. | Nema toka slanja na odobrenje; `kk_admin` objavljuje ili upravlja statusom direktno bez zahtjeva, povlačenja i interne napomene. | Planirano. |
| Pregled i odobravanje (FS 5.5.6) | Pokretanje uredničkog pregleda, zaključavanje prijedloga, uređivanje od strane Urednika, odobri / vrati na doradu sa obaveznim razlogom, povrat odgovornosti Moderatoru, audit odluka; V1 bez trajnog odbijanja. | Nema uredničkog pregleda ni zaključavanja; `kk_admin` direktno upravlja statusom i sadržajem bez obaveznog razloga vraćanja i bez odvojenog audita uredničkih odluka. | Planirano. |
| Upravljanje organizatorima (FS 5.6) | Entitet Organizator; Moderatori kao operativni pristup uredničkom portalu; prvi Moderator = podnosilac; naredne predlažu Moderatori; ovlašćenja dodjeljuje Urednik; deaktivacija bez brisanja istorije; audit zahtjeva. | Nema entiteta Organizator ni Moderatorskog konteksta; `kk_admin` upravlja događajima bez pripadnosti Organizatoru. | Planirano. |
| Događaj bez registrovanog Organizatora (BR-045 / BR-052) | Izuzetak: Urednik kreira/objavljuje radi javnog interesa; naknadno povezivanje sa Organizatorom bez izmjene audita, istorije i javnih verzija. | Nema formalnog izuzetka ni toka naknadnog povezivanja; `kk_admin` kreira događaje bez Organizatora, ali bez usvojenog administrativnog povezivanja. | Planirano. |

---

## Povezani dokumenti

- [Business Model](../business-model/Business_Model_Kalendar_kulture_MASTER.md) — BM-01 Organizator, BM-02 Moderator, BM-03 Urednik, BM-06 Održavanje događaja
- [Functional Specification](../functional-specifications/Functional-Specification.md)
- [cultural-calendar-test-checklist.md](cultural-calendar-test-checklist.md) — checklista za testiranje prije objave
- [roles-and-permissions.md](roles-and-permissions.md)
- [deployment-and-cron.md](deployment-and-cron.md)
