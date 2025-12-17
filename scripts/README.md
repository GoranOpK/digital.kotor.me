# Dokument Processing Skripta

Ova Python skripta se koristi za automatsku konverziju dokumenata u optimizovani PDF format.

## Instalacija

### 1. Instaliraj Python zavisnosti

```bash
pip install -r requirements.txt
```

Ili ako koristiš Python 3 specifično:

```bash
pip3 install -r requirements.txt
```

### 2. Proveri da li je Python dostupan

```bash
python3 --version
# ili
python --version
```

### 3. Postavi dozvole za skriptu (opciono)

```bash
chmod +x process_document.py
```

## Korišćenje

Skripta se automatski poziva kroz Laravel `DocumentProcessor` servis, ali može se i ručno testirati:

```bash
python3 process_document.py input_file.jpg output_file.pdf
```

## Funkcionalnosti

- **Konverzija u A4 format** (2480x3508px @ 200 DPI)
- **Grayscale konverzija** (8-bit)
- **Optimizacija veličine** (PDF kompresija)
- **Podržani formati**: JPEG, PNG, PDF

## Napomene

- Skripta automatski centrira sliku na A4 canvas sa belom pozadinom
- PDF fajlovi se prvo konvertuju u sliku, pa u optimizovani PDF
- Za najbolje rezultate, koristi slike visoke rezolucije

