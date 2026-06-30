# Plesk: cron za brisanje isteklih dokumenata

Skripta `delete-expired-documents.php` u root-u projekta pokreće `documents:delete-expired`.  
Koristi se **Plesk Scheduled Tasks → Run a PHP script**.

## Šta komanda radi

- Briše dokumente kod kojih je `expires_at` prošao.
- **Lokalni fajlovi:** briše sa diska i zapis iz baze.
- **MEGA fajlovi:** pokreće Node skriptu `scripts/delete-expired-mega.js` (megajs), briše sa MEGA-e, zatim briše zapis iz baze.

## Preduslovi

- **Node.js** dostupan u okruženju gde se pokreće PHP (cron). Ako `node` nije u PATH, u `.env` dodaj npr. `NODE_BINARY=/usr/bin/node` (putanja zavisi od Plesk / sistemske instalacije).
- **npm paketi:** `megajs`, `dotenv`. U root-u projekta pokreni `npm install` (Plesk Node.js tab ili SSH).
- **MEGA kredencijali** u `.env`: `MEGA_EMAIL`, `MEGA_PASSWORD`.

## Podešavanje u Plesku

1. **Scheduled Tasks** → **Add Task**
2. **Task type:** `Run a PHP script`
3. **Script path:** `delete-expired-documents.php` (npr. apsolutna putanja do projekta + `delete-expired-documents.php`)
4. **PHP verzija:** 8.3 (ili šta koristiš)
5. **Run:** `Cron style` → npr. `0 2 * * *` (dnevno u 02:00)
6. Sačuvaj i aktiviraj.

## Test (dry-run)

Iz root-a projekta:

```bash
php delete-expired-documents.php --dry-run
```

Ispisuje šta bi bilo obrisano, bez brisanja.

## Čišćenje privremenih download fajlova (temp_downloads)

Kada korisnik preuzima MEGA dokument, fajl se privremeno čuva u `temp_downloads/`. Laravel ga briše nakon slanja (`deleteFileAfterSend`). Ako to ne uspe (npr. prekid konekcije), fajl ostaje. Cron ga obriše posle 5 minuta.

**Skripta:** `cleanup-temp-downloads.php`  
**Komanda:** `documents:cleanup-temp-downloads --minutes=5`  
**Preporuka:** pokretati svakih 5–10 minuta, npr. `*/5 * * * *` (svakih 5 min)

Podešavanje: isto kao za `delete-expired-documents.php`, samo script path = `cleanup-temp-downloads.php`, cron = `*/5 * * * *`.

**Dry-run:** `php cleanup-temp-downloads.php` (ili ručno: `php artisan documents:cleanup-temp-downloads --dry-run`).

## Napomene

- Laravel 12 koristi `bootstrap/app.php`. Cron se radi preko **Run a PHP script**, ne preko `php artisan schedule:run`.
