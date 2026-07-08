# Kalendar kulturnih događaja

**Poslednje ažuriranje:** 2026-07-08 (detalj + newsletter UX)  
**Izvor u kodu:** `CulturalCalendarController`, `CulturalEventController`, `CulturalEvent` model, `SendCulturalCalendarWeeklyNewsletter`

---

## Namjena

Javni pregled kulturnih događaja Opštine Kotor: kalendar, lista događaja, arhiva, pregled po danu, newsletter pretplata.

---

## Rute (javne za prijavljene korisnike)

| Putanja | Ime | Opis |
|---------|-----|------|
| `/kalendar-kulture` | `cultural-calendar.index` | Početna kalendara |
| `/kalendar-kulture/pregled-dogadjaja` | `cultural-calendar.events` | Lista događaja |
| `/kalendar-kulture/arhiva-dogadjaja` | `cultural-calendar.archive` | Arhiva |
| `/kalendar-kulture/dogadjaj/{event}` | `cultural-calendar.show` | Detalj događaja |
| `/kalendar-kulture/dan/{date}` | `cultural-calendar.day` | Događaji za datum |
| POST `/kalendar-kulture/newsletter` | `cultural-calendar.newsletter.store` | Pretplata |

**UI ponašanje (važeće):** u sekcijama **Pregled događaja**, **Arhiva događaja**, **Istaknuti događaji** i **Naredni događaji** kartice su klikabilne i otvaraju stranicu detalja događaja.

---

## Administracija (`kk_admin`)

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

Naslov, opis, datum/vrijeme (`vrijeme` + opciono `vrijeme_do`), lokacija, kategorija, status, `created_by` (user).

---

## Newsletter

**Komanda:** `cultural-calendar:send-weekly-newsletter`  
**Scheduler:** ponedjeljak 09:00, timezone `Europe/Podgorica` (`routes/console.php`)

**UX i ponašanje prijave:**

- poruka o statusu newsletter prijave prikazuje se na vrhu stranice kalendara (odmah ispod tabova),
- uneseni e-mail u polju za newsletter je jasno vidljiv (tamna boja teksta i čitljiv placeholder),
- welcome mail se šalje samo pri prvoj prijavi adrese (ne i pri ponovnoj prijavi iste već aktivne adrese).

Mail klase:

- `CulturalCalendarNewsletterWeeklyMail`
- `CulturalCalendarNewsletterWelcomeMail`

Model pretplatnika: `NewsletterSubscriber`.

---

## Povezani dokumenti

- [cultural-calendar-test-checklist.md](cultural-calendar-test-checklist.md) — checklista za testiranje prije objave
- [roles-and-permissions.md](roles-and-permissions.md)
- [deployment-and-cron.md](deployment-and-cron.md)
