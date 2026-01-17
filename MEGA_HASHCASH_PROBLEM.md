# MEGA Hashcash Problem - Analiza i Rešenja

## Problem:

MEGA API zahteva **Hashcash proof-of-work** sa **bits=192**:
- To znači da SHA1 hash mora počinjati sa **48 hex karaktera nula** (192 bita)
- Prosečan broj pokušaja: **2^192** = ogromno broj
- Trenutno rešavanje: **2.3M hashes/s**, probano **100M puta** = **43 sekundi** = **0 rezultata**

## Zašto je to problem:

Bits=192 je **ekstremno teško** i praktično **nemoguće** u realnom vremenu:
- Za bits=192, prosečno treba **2^192 pokušaja**
- To je **~6.3 × 10^57** pokušaja u proseku
- Sa 2.3M hashes/s, to bi trajalo **~8.7 × 10^43 godine**

## Moguća rešenja:

### 1. **Smanji bits (ako je moguće)**
- Možda MEGA dozvoljava niže bits za određene zahteve
- Problem: Ne znamo da li postoji način da zatražimo niže bits

### 2. **Koristi MEGA WebDAV**
- Možda WebDAV ne zahteva Hashcash
- Problem: Ne znamo da li MEGA podržava WebDAV

### 3. **Koristi zvanični MEGA SDK**
- MEGA SDK možda ima način da zaobiđe Hashcash
- Problem: Nema zvanični PHP SDK

### 4. **Koristi megacmd CLI**
- Već smo to pokušali ali nije moguće na Plesk serveru
- Problem: Ne može se instalirati megacmd

### 5. **Background job za Hashcash**
- Rešavaj Hashcash u pozadini, pa koristi kada bude rešeno
- Problem: Još uvek previše teško, i rešenje ističe

### 6. **Alternative storage servis**
- Ako MEGA ne radi, možemo koristiti druge servise (AWS S3, Google Drive, itd.)
- Problem: Korisnik želi MEGA

### 7. **Proveri da li MEGA dozvoljava session caching**
- Možda možemo jednom da se ulogujemo i čuvamo session
- Problem: Session možda ističe brzo

### 8. **Koristi MEGA API iz browser-a (JavaScript)**
- MEGA možda ne zahteva Hashcash za browser zahteve
- Problem: Nije moguće direktno iz PHP-a

## Preporuka:

Pošto bits=192 je praktično nemoguće rešiti u realnom vremenu, **predlažem alternativu**:

### Opcija A: Koristi drugi storage servis
- **AWS S3** - ima zvanični PHP SDK
- **Google Drive API** - ima zvanični PHP SDK
- **DigitalOcean Spaces** - kompatibilan sa S3 API
- **Backblaze B2** - ima API

### Opcija B: Ostavi fajlove lokalno
- Ako MEGA ne radi, možemo ostaviti fajlove lokalno
- Sistem već radi sa lokalnim fajlovima
- Problem: Fajlovi zauzimaju prostor na serveru

### Opcija C: Nastavi sa MEGA ali sa optimizacijama
- Povećaj max attempts (1B+)
- Optimizuj hash algoritam (možda korišćenjem C ekstenzije)
- Background job za rešavanje Hashcash-a
- Cache rešenja (ako su validna duže vreme)

## Odluka:

**Korisnik treba da odluči:**
1. Da li želi da probamo alternativne storage servise?
2. Da li želi da ostavimo fajlove lokalno?
3. Da li želi da nastavimo sa MEGA optimizacijama (može potrajati)?
