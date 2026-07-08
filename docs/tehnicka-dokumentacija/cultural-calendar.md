# Kalendar kulturnih događaja

**Poslednje ažuriranje:** 2026-06-30  
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
| `/kalendar-kulture/dan/{date}` | `cultural-calendar.day` | Događaji za datum |
| POST `/kalendar-kulture/newsletter` | `cultural-calendar.newsletter.store` | Pretplata |

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

Puni spisak u `app/Models/CulturalEvent.php` (Koncerti, Predstave, Izložbe, …). Za test prije objave v. [cultural-calendar-test-checklist.md](cultural-calendar-test-checklist.md).

### Polja (sažetak)

Naslov, opis, datum/vrijeme, lokacija, kategorija, status, `created_by` (user).

---

## Newsletter

**Komanda:** `cultural-calendar:send-weekly-newsletter`  
**Scheduler:** ponedjeljak 09:00, timezone `Europe/Podgorica` (`routes/console.php`)

Mail klase:

- `CulturalCalendarNewsletterWeeklyMail`
- `CulturalCalendarNewsletterWelcomeMail`

Model pretplatnika: `NewsletterSubscriber`.

---

## Povezani dokumenti

- [cultural-calendar-test-checklist.md](cultural-calendar-test-checklist.md) — checklista za testiranje prije objave
- [roles-and-permissions.md](roles-and-permissions.md)
- [deployment-and-cron.md](deployment-and-cron.md)
