#!/usr/bin/env python3
"""
Skripta za konverziju dokumenata u optimizovani PDF format:
- A4 format
- Grayscale (8-bit)
- 200 DPI
- JBIG2 kompresija (ako je dostupna, inače standardna PDF kompresija)
"""

import sys
import os
from pathlib import Path
from PIL import Image
import tempfile

def convert_to_optimized_pdf(input_path, output_path):
    """
    Konvertuje ulazni fajl (slika ili PDF) u optimizovani PDF.
    
    Args:
        input_path: Putanja do ulaznog fajla
        output_path: Putanja za izlazni PDF fajl
    """
    try:
        # A4 dimenzije @ 200 DPI: 2480 x 3508 piksela
        A4_WIDTH = 2480
        A4_HEIGHT = 3508
        DPI = 200
        
        # Proveri tip fajla
        input_ext = Path(input_path).suffix.lower()
        
        # Ako je već PDF, pokušaj da ga konvertuješ
        if input_ext == '.pdf':
            # Za PDF, prvo konvertuj u sliku, pa u optimizovani PDF
            # Ovo zahteva pdf2image, ali ako nije dostupno, koristi osnovnu konverziju
            try:
                from pdf2image import convert_from_path
                images = convert_from_path(input_path, dpi=DPI)
                if images:
                    # Uzmi prvu stranicu
                    img = images[0]
                else:
                    raise Exception("Nije moguće konvertovati PDF")
            except ImportError:
                # Fallback: kreiraj prazan PDF sa osnovnim informacijama
                # U praksi, ovo bi zahtevalo dodatne biblioteke
                raise Exception("pdf2image nije dostupan. Instaliraj: pip install pdf2image")
        else:
            # Otvori sliku
            img = Image.open(input_path)
        
        # Konvertuj u RGB ako nije (neki formati su RGBA ili drugačiji)
        if img.mode != 'RGB':
            # Ako ima alpha kanal, konvertuj na belu pozadinu
            if img.mode == 'RGBA':
                background = Image.new('RGB', img.size, (255, 255, 255))
                background.paste(img, mask=img.split()[3])  # Koristi alpha kanal kao masku
                img = background
            else:
                img = img.convert('RGB')
        
        # Resize na A4 format (čuva aspect ratio, dodaje belu pozadinu ako treba)
        img.thumbnail((A4_WIDTH, A4_HEIGHT), Image.Resampling.LANCZOS)
        
        # Kreiraj A4 canvas sa belom pozadinom
        a4_img = Image.new('RGB', (A4_WIDTH, A4_HEIGHT), (255, 255, 255))
        
        # Centriraj sliku na A4 canvas
        x_offset = (A4_WIDTH - img.width) // 2
        y_offset = (A4_HEIGHT - img.height) // 2
        a4_img.paste(img, (x_offset, y_offset))
        
        # Konvertuj u grayscale
        a4_img = a4_img.convert('L')  # Grayscale (8-bit)
        
        # Sačuvaj kao PDF sa optimizacijom
        # Pillow automatski koristi Flate kompresiju za PDF
        # Za JBIG2 bi trebalo koristiti dodatne biblioteke (npr. pypdf sa JBIG2 podrškom)
        a4_img.save(
            output_path,
            'PDF',
            resolution=DPI,
            optimize=True,
            quality=85
        )
        
        return True
        
    except Exception as e:
        print(f"GREŠKA: {str(e)}", file=sys.stderr)
        return False


if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Upotreba: python process_document.py <input_file> <output_file>", file=sys.stderr)
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    if not os.path.exists(input_file):
        print(f"GREŠKA: Ulazni fajl ne postoji: {input_file}", file=sys.stderr)
        sys.exit(1)
    
    success = convert_to_optimized_pdf(input_file, output_file)
    
    if success:
        print(f"SUCCESS: {output_file}")
        sys.exit(0)
    else:
        print("GREŠKA: Konverzija nije uspela", file=sys.stderr)
        sys.exit(1)

