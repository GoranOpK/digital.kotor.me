# Plesk: cron za brisanje isteklih dokumenata

Skripta `delete-expired-documents.php` u root-u projekta pokreće `documents:delete-expired`.  
Koristi se **Plesk Scheduled Tasks → Run a PHP script** (isto kao `queue-worker.php` / `recalculate-storage.php`).

## Podešavanje u Plesku

1. **Scheduled Tasks** → **Add Task**
2. **Task type:** `Run a PHP script`
3. **Script path:** izaberi `delete-expired-documents.php` (npr. putanja kao za `queue-worker.php`: `digital.kotor.me/delete-expired-documents.php` ili apsolutna do projekta)
4. **PHP verzija:** 8.3 (ili ono što koristiš)
5. **Run:** `Cron style`
6. **Cron expression:** npr. `0 2 * * *` (jednom dnevno u 02:00)
7. Sačuvaj i aktiviraj task.

## Šta komanda radi

- Briše dokumente kod kojih je `expires_at` već prošao (datum isteka koji je korisnik uneo).
- Uklanja lokalne fajlove (ako postoje) i zapis iz baze.
- Za dokumente koji su **samo na MEGA** (nema lokalnog fajla): briše samo zapis u bazi. **Fajlovi na MEGA se iz cron-a ne brišu** (megajs radi u browser-u). Komanda ispisuje koliko je takvih bilo.

## Test (dry-run)

Ručno, iz root-a projekta:

```bash
php delete-expired-documents.php --dry-run
```

Ispisuje šta bi bilo obrisano, bez brisanja.

## Napomene

- Laravel 12 koristi `bootstrap/app.php`; nema `Kernel.php`.
- Za Plesk se ne koristi `php artisan schedule:run` ni `Schedule::` u `routes/console.php`.  
  Cron se radi isključivo preko **Run a PHP script** → `delete-expired-documents.php`.
