# Pre-deploy checklist: MEGA brisanje + cron isteklih

Proveri pre podizanja na server.

## 1. Migracija

```bash
php artisan migrate
```

Dodaje `mega_node_id` i `mega_file_name` u `user_documents`. **Obavezno prvo migracija, pa deploy** – inače storeMegaMetadata i Blade mogu da puknu.

## 2. Artisan komanda

```bash
php artisan documents:delete-expired --dry-run
```

Očekivano: "Nema isteklih dokumenata." ili lista [dry-run] bez brisanja. Ako pukne, proveri `DeleteExpiredDocuments` i log.

## 3. Cron skripta (Plesk)

```bash
php delete-expired-documents.php --dry-run
```

Isti efekat kao gore. Koristi se u Plesk Scheduled Tasks → Run a PHP script.

## 4. Frontend build

```bash
npm run build
```

Proveri da nema grešaka. Nova JS uključuje `deleteFromMega` i izmene u `mega-upload.js`.

## 5. Redosled na serveru

1. `git pull`
2. `php artisan migrate`
3. `npm run build`
4. (Opciono) Podesi Plesk task za `delete-expired-documents.php` – v. `PLESK_DELETE_EXPIRED_CRON.md`

## 6. Šta je dodato / izmenjeno

- **Migration:** `mega_node_id`, `mega_file_name` u `user_documents`
- **storeMegaMetadata:** upisuje `mega_node_id` i `mega_file_name`
- **destroy:** i dalje briše samo lokalne fajlove i zapis; MEGA brisanje je u browseru
- **Blade:** forma za brisanje ima `data-is-mega`, `data-mega-file-name`; `handleDocumentDelete` poziva `deleteFromMega` za MEGA dok ako ima `mega_file_name`
- **mega-upload.js:** `deleteFromMega(megaFileName)` – megajs `storage.find` + `file.delete(true)`
- **DeleteExpiredDocuments:** `documents:delete-expired` – briše istekle (samo DB za MEGA-only)
- **delete-expired-documents.php:** root skripta za Plesk cron
- **routes/console.php:** uklonjen `Schedule::`; ostaje komentar da se koristi PHP skripta + Plesk

## 7. Stari dokumenti (pre ovog deploy-a)

Nemaju `mega_file_name`. Klik "Obriši" za njih: preskače se MEGA brisanje (nema imena), šalje se forma, backend briše zapis. Fajl na MEGA ostaje.
