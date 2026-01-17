# Instrukcije za integraciju sa Mega.nz

Ovo je dokumentacija sa koracima koje treba da izvršite kroz Plesk da biste omogućili integraciju sa Mega.nz cloud storage.

## Pregled izmena

Sistem je sada konfigurisan da:
1. Uploaduje obrađene PDF fajlove na Mega.nz nakon obrade
2. Briše fajlove sa servera nakon uspešnog upload-a
3. Preuzima fajlove direktno sa Mega.nz
4. Briše fajlove sa Mega.nz kada se dokument obriše
5. Ne računa cloud fajlove u lokalnom prostoru (20 MB limit)

## Korak 1: Instalacija MEGA biblioteke

Trenutno, `MegaStorageService` je placeholder implementacija. Treba da instalirate odgovarajuću PHP biblioteku za rad sa Mega.nz API-jem.

### Opcija 1: Korišćenje Composer paketa (preporučeno)

Preko SSH terminala u Plesk-u ili preko Plesk File Manager-a, dodajte u `composer.json`:

```json
{
    "require": {
        "tuyenlaptrinh/php-mega-nz": "^1.0"
    }
}
```

Zatim izvršite:
```bash
composer install
```

### Opcija 2: Rucna implementacija sa HTTP API-jem

Ako ne možete instalirati Composer pakete, treba implementirati `MegaStorageService` metode direktno koristeći HTTP zahteve ka Mega.nz API-ju.

**Napomena:** Mega.nz API zahteva enkripciju/dekripciju i autentifikaciju. Implementacija je složena i preporučujemo korišćenje postojećih biblioteka.

## Korak 2: Konfiguracija

Dodajte u `.env` fajl (preko Plesk File Manager ili Settings > Configuration Files):

```env
MEGA_EMAIL=vas_email@example.com
MEGA_PASSWORD=vasa_sifra
MEGA_BASE_FOLDER=digital.kotor
```

**Napomena:** `MEGA_BASE_FOLDER` određuje osnovni folder na Mega.nz gde će se smeštati fajlovi. Podrazumevano je `digital.kotor`. Fajlovi će se smeštati u strukturi: `digital.kotor/documents/user_{userId}/`

**VAŽNO:** 
- Nikada ne uploadujte `.env` fajl na Git ili javne repozitorijume
- Koristite siguran način da prenesete credentials

## Korak 3: Migracija baze podataka

Preko Plesk-a (Websites & Domains > your-domain > Databases), ili preko SSH-a:

```bash
php artisan migrate
```

Ovo će dodati `cloud_path` kolonu u `user_documents` tabelu.

## Korak 4: Implementacija MegaStorageService

Nakon instalacije biblioteke, ažurirajte metode u `app/Services/MegaStorageService.php`:

### Primer za `upload()` metodu:

```php
public function upload(string $filePath, string $remotePath = ''): array
{
    try {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'Fajl ne postoji'];
        }

        // Primer sa tuyenlaptrinh/php-mega-nz bibliotekom:
        $mega = new \MegaClient($this->email, $this->password);
        $mega->login();
        
        $node = $mega->uploadFile($filePath, $remotePath);
        $cloudPath = $node->getHandle(); // ili neki drugi identifikator
        
        return [
            'success' => true,
            'cloud_path' => $cloudPath
        ];
    } catch (Exception $e) {
        Log::error('MEGA upload failed', ['error' => $e->getMessage()]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

### Primer za `download()` metodu:

```php
public function download(string $cloudPath): array
{
    try {
        $mega = new \MegaClient($this->email, $this->password);
        $mega->login();
        
        $content = $mega->downloadFile($cloudPath);
        
        return [
            'success' => true,
            'content' => $content
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

### Primer za `delete()` metodu:

```php
public function delete(string $cloudPath): bool
{
    try {
        $mega = new \MegaClient($this->email, $this->password);
        $mega->login();
        
        $mega->deleteFile($cloudPath);
        return true;
    } catch (Exception $e) {
        Log::error('MEGA delete failed', ['error' => $e->getMessage()]);
        return false;
    }
}
```

## Korak 5: Testiranje

Nakon implementacije:

1. Upload-ujte test dokument kroz aplikaciju
2. Proverite u log fajlovima (`storage/logs/laravel.log`) da li se fajl upload-ovao na Mega.nz
3. Proverite da li je lokalni fajl obrisan nakon upload-a
4. Testirajte download funkcionalnost
5. Testirajte brisanje dokumenta

## Korak 6: Monitoring

Pratite logove za potencijalne probleme:

```bash
tail -f storage/logs/laravel.log | grep MEGA
```

## Kreirane/Modifikovane datoteke

1. ✅ `database/migrations/2025_01_16_000001_add_cloud_path_to_user_documents_table.php` - Migracija
2. ✅ `app/Services/MegaStorageService.php` - Servis za rad sa Mega.nz (potrebno implementirati)
3. ✅ `app/Services/DocumentProcessor.php` - Modifikovan da uploaduje na Mega.nz
4. ✅ `app/Http/Controllers/DocumentController.php` - Modifikovan download/destroy metode
5. ✅ `app/Models/UserDocument.php` - Dodato `cloud_path` polje
6. ✅ `config/services.php` - Dodata MEGA konfiguracija

## Troubleshooting

### Problem: "MEGA credentials not configured"
**Rešenje:** Proverite da li su `MEGA_EMAIL` i `MEGA_PASSWORD` postavljeni u `.env` fajlu.

### Problem: "MEGA upload failed, keeping file locally"
**Rešenje:** 
- Proverite da li su credentials ispravni
- Proverite da li je `MegaStorageService` implementiran (nije placeholder)
- Proverite logove za detaljne greške

### Problem: Fajlovi se ne brišu sa servera
**Rešenje:** Proverite da li `upload()` metoda vraća `success => true` i `cloud_path` sa validnom vrednošću.

## Dodatne napomene

- Ako Mega.nz upload ne uspe, sistem će zadržati fajl lokalno kao fallback
- Cloud fajlovi se ne računaju u 20 MB limit lokalnog prostora
- Stari fajlovi (bez `cloud_path`) će i dalje raditi lokalno
