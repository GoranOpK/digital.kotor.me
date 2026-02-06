# MEGA upload za dokumente prijave na konkurs

Dokumenti upload-ovani za prijavu na konkurs (direct upload, ne iz biblioteke) se sada automatski podižu na MEGA.

## Tok

1. Korisnik upload-uje fajl u formi za prijavu na konkurs
2. DocumentProcessor obrađuje (PDF, 300 DPI, greyscale)
3. Node skripta `scripts/upload-to-mega.js` uploaduje na MEGA u `digital.kotor/applications/user_{id}/`
4. ApplicationDocument čuva: file_path (lokalno), cloud_path, mega_node_id, mega_file_name
5. **Dokument ostaje i lokalno** – briše se tek kada se konkurs završi i obave admin koraci

## Fallback

Ako lokalni fajl ne postoji (npr. obrisan), a postoji cloud_path, viewDocument i downloadDocument preuzimaju sa MEGA-e preko `scripts/download-from-mega.js`.

## Preduslovi

- Node, NODE_BINARY (u .env), megajs, dotenv – isto kao za biblioteku dokumenata i delete-expired-mega

## Migracija

```bash
php artisan migrate
```

Dodaje kolone: cloud_path, mega_node_id, mega_file_name u application_documents.
