# Biblioteka dokumenata i MEGA

**Poslednje ažuriranje:** 2026-06-30  
**Izvor u kodu:** `DocumentController`, `DocumentProcessor`, `ProcessDocumentJob`, `scripts/*.js`

Operativni setup i troubleshooting: [MEGA_BROWSER_UPLOAD_SETUP.md](MEGA_BROWSER_UPLOAD_SETUP.md), [MEGAJS_SETUP_COMPLETE.md](MEGAJS_SETUP_COMPLETE.md), [APPLICATION_DOCUMENTS_MEGA.md](APPLICATION_DOCUMENTS_MEGA.md).

---

## Namjena

Svaki prijavljeni korisnik (osim ograničenih uloga) ima **biblioteku dokumenata** — upload, kategorizacija, preuzimanje, vezivanje za prijavu na konkurs.

Dokumenti se obrađuju lokalno (PDF/slike), zatim se finalni PDF šalje na **MEGA.nz**.

---

## Tok uploada (važeće)

```
1. POST /documents (fajl + kategorija + opcioni expires_at)
2. DocumentProcessor — validacija, kvota, konverzija u PDF
3. ProcessDocumentJob (queue) ili sinhrona obrada
4. POST /documents/process-for-mega — priprema temp fajla
5. Browser: megajs upload (POST /api/mega/session za sesiju)
6. POST /documents/store-mega — metadata u bazi (cloud_path, mega_node_id)
```

Isti MEGA pattern koristi se i za **dokumente prijava** (`ApplicationController`).

---

## Kvota i formati

| Pravilo | Vrijednost |
|---------|------------|
| Max storage po korisniku | 20 MB (`DocumentProcessor::MAX_STORAGE_PER_USER`) |
| Formati uploada | PDF, JPG, JPEG, PNG |
| Max po fajlu (prijava) | 20 MB |

---

## Statusi dokumenta (`user_documents.status`)

`pending` → `processing` → `processed` / `failed` → `active`

---

## Kategorije biblioteke

- Lični dokumenti
- Finansijski dokumenti
- Poslovni dokumenti
- Ostali dokumenti

---

## Polja MEGA u bazi

| Kolona | Namjena |
|--------|---------|
| `cloud_path` | Putanja na MEGA |
| `mega_node_id` | ID čvora |
| `mega_file_name` | Ime fajla na MEGA |

Objašnjenje: [CLOUD_PATH_EXPLANATION.md](CLOUD_PATH_EXPLANATION.md).

Ista polja na `application_documents`.

---

## Istek dokumenata (`expires_at`)

- Opciono pri uploadu; mora biti **poslije** današnjeg datuma
- Dnevno brisanje: `documents:delete-expired` (v. [deployment-and-cron.md](deployment-and-cron.md))
- Briše lokalni fajl i pokreće Node skriptu za brisanje sa MEGA

---

## Env varijable

`MEGA_EMAIL`, `MEGA_PASSWORD`, `MEGA_BASE_FOLDER` (default `digital.kotor`), `NODE_BINARY` (default `node`).

V. [environment-variables.md](environment-variables.md).

---

## Sigurnosna napomena

MEGA sesija za browser upload koristi credentials sa servera (`DocumentController@getMegaSession`). Ovo je svjesni trade-off za direktan upload; formalni security pregled — v. [project-todo.md](project-todo.md).

---

## Rute (sažetak)

| Ruta | Namjena |
|------|---------|
| `documents.index` | Lista |
| `documents.store` | Upload |
| `documents.download` | Preuzimanje |
| `documents.destroy` | Brisanje |
| `mega.session` | API sesija za megajs |

**Ne koristiti** URL `/documents/ime.pdf` za statičke fajlove — Laravel tretira `/documents/{document}` kao rutu.

PDF uputstvo za konkurse: `/competitions/guide/pdf` → `public/pdf/`.

---

## Povezani dokumenti

- [application-lifecycle.md](application-lifecycle.md)
- [deployment-and-cron.md](deployment-and-cron.md)
