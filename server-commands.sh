#!/bin/bash

# ============================================
# Komande za pokretanje na serveru
# ============================================

# 1. Pokretanje migracije
echo "Pokretanje migracije..."
php artisan migrate

# 2. Provera da li postoji jobs tabela (ako ne postoji, kreiraj je)
echo "Provera jobs tabele..."
php artisan queue:table 2>/dev/null || echo "Jobs tabela već postoji ili je kreirana"
php artisan migrate

# 3. Provera queue konfiguracije
echo "Provera queue konfiguracije..."
grep QUEUE_CONNECTION .env || echo "QUEUE_CONNECTION=database" >> .env

# 4. Clear cache
echo "Brisanje cache-a..."
php artisan config:clear
php artisan cache:clear

# 5. Pokretanje queue workera u pozadini
echo "Pokretanje queue workera..."
echo "Napomena: Ova komanda će raditi u pozadini. Za zaustavljanje koristite: pkill -f 'queue:work'"
nohup php artisan queue:work --tries=3 --timeout=300 > storage/logs/queue.log 2>&1 &

# 6. Provera da li queue worker radi
sleep 2
if ps aux | grep -v grep | grep "queue:work" > /dev/null; then
    echo "✓ Queue worker je pokrenut!"
    echo "Proces ID: $(ps aux | grep -v grep | grep 'queue:work' | awk '{print $2}')"
else
    echo "✗ Queue worker nije pokrenut. Proverite logove: tail -f storage/logs/queue.log"
fi

echo ""
echo "============================================"
echo "Sledeće komande za upravljanje:"
echo "============================================"
echo "Provera logova: tail -f storage/logs/queue.log"
echo "Provera procesa: ps aux | grep queue:work"
echo "Zaustavljanje: pkill -f 'queue:work'"
echo "Provera failed jobs: php artisan queue:failed"
echo "============================================"

