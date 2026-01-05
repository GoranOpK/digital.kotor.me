<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentProcessor
{
    /**
     * Maksimalna veličina prostora po korisniku (20 MB)
     */
    const MAX_STORAGE_PER_USER = 20 * 1024 * 1024; // 20 MB u bajtovima

    /**
     * Procesira upload-ovani dokument i kreira optimizovanu verziju fajla.
     * Konvertuje u greyscale, 300 DPI i PDF format.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $userId
     * @param string|null $baseFilename Opciono: base filename (bez ekstenzije) - ako se prosledi, koristi se umesto generisanog
     * @return array ['success' => bool, 'file_path' => string|null, 'file_size' => int|null, 'error' => string|null]
     */
    public function processDocument($file, int $userId, ?string $baseFilename = null): array
    {
        try {
            // Dozvoljeni tipovi – slike (JPEG/PNG) i PDF
            $mime = $file->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!in_array($mime, $allowedMimes)) {
                return [
                    'success' => false,
                    'error' => 'Tip fajla nije dozvoljen. Dozvoljeni su: JPEG, PNG, PDF.'
                ];
            }

            // Direktorijum za korisnika
            $directory = "documents/user_{$userId}";

            // Generiši ime fajla: id-korisnika-datum-upload-a-XXXXXXXX.pdf
            // Ako je prosleđen baseFilename, koristi ga (uklanjuje _original ako postoji)
            if ($baseFilename !== null) {
                // Ukloni _original ako postoji u baseFilename
                $baseFilename = str_replace('_original', '', $baseFilename);
                $outputFilename = "{$baseFilename}.pdf";
            } else {
                // Generiši novo ime
                $date = now()->format('Ymd'); // YYYYMMDD format
                $randomString = bin2hex(random_bytes(4)); // 8 karaktera (4 bytes = 8 hex karaktera)
                $baseFilename = "{$userId}-{$date}-{$randomString}";
                $outputFilename = "{$baseFilename}.pdf";
            }
            $outputPath = "{$directory}/{$outputFilename}";

            // Pokušaj da koristiš PHP Imagick ekstenziju direktno (najbrže i najpouzdanije)
            $pdfData = false;
            
            if (extension_loaded('imagick')) {
                $pdfData = $this->processWithPhpImagick($file->getRealPath(), $mime);
            }
            
            // Ako PHP Imagick nije dostupan, pokušaj sa convert komandom
            if ($pdfData === false) {
                $convertPath = $this->findImageMagickConvert();
                if ($convertPath) {
                    $pdfData = $this->processWithImageMagick($file->getRealPath(), $mime, $convertPath);
                }
            }
            
            // Ako ImageMagick nije dostupan ili nije uspeo, koristi GD metodu
            if ($pdfData === false) {
                $pdfData = $this->processWithGd($file, $mime);
            }

            if ($pdfData === false) {
                return [
                    'success' => false,
                    'error' => 'Greška pri obradi dokumenta. Proverite da li je ImageMagick instaliran ili da li je fajl validan.',
                ];
            }

            // Snimi PDF fajl u storage (na istom mestu gde će biti izvorni fajl)
            Storage::disk('local')->put($outputPath, $pdfData);

            $fileSize = strlen($pdfData);

            return [
                'success' => true,
                'file_path' => $outputPath,
                'file_size' => $fileSize,
                'error' => null,
            ];

        } catch (Exception $e) {
            Log::error('Document processing exception', [
                'message' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Greška pri obradi dokumenta: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Proverava da li korisnik ima dovoljno prostora
     *
     * @param int $userId
     * @param int $additionalBytes
     * @return bool
     */
    public function hasEnoughStorage(int $userId, int $additionalBytes): bool
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        $currentUsage = $user->used_storage_bytes ?? 0;
        $totalNeeded = $currentUsage + $additionalBytes;

        return $totalNeeded <= self::MAX_STORAGE_PER_USER;
    }

    /**
     * Ažurira korišćen prostor za korisnika
     *
     * @param int $userId
     * @param int $bytesToAdd (može biti negativno za brisanje)
     * @return void
     */
    public function updateUserStorage(int $userId, int $bytesToAdd): void
    {
        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->used_storage_bytes = max(0, ($user->used_storage_bytes ?? 0) + $bytesToAdd);
            $user->save();
        }
    }

    /**
     * Briše fajl i ažurira storage
     *
     * @param string $filePath
     * @param int $userId
     * @return bool
     */
    public function deleteDocument(string $filePath, int $userId): bool
    {
        try {
            if (Storage::disk('local')->exists($filePath)) {
                $fileSize = Storage::disk('local')->size($filePath);
                Storage::disk('local')->delete($filePath);
                
                // Ažuriraj storage (oduzmi)
                $this->updateUserStorage($userId, -$fileSize);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('Document deletion failed', [
                'file_path' => $filePath,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Kreira GD image resource iz fajla na osnovu MIME tipa.
     *
     * @param string $path
     * @param string $mime
     * @return resource|false
     */
    protected function createImageResource(string $path, string $mime)
    {
        try {
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                return imagecreatefromjpeg($path);
            }

            if ($mime === 'image/png') {
                return imagecreatefrompng($path);
            }
        } catch (Exception $e) {
            Log::error('Image resource creation failed', [
                'path' => $path,
                'mime' => $mime,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Obrađuje dokument koristeći PHP Imagick ekstenziju direktno
     *
     * @param string $filePath
     * @param string $mime
     * @return string|false PDF data
     */
    protected function processWithPhpImagick(string $filePath, string $mime)
    {
        try {
            Log::info('Starting PHP Imagick conversion', [
                'mime' => $mime,
                'file_path' => $filePath
            ]);

            $imagick = new \Imagick();
            
            // Postavi DPI na 300 (bolji kvalitet za dokumente)
            $imagick->setResolution(300, 300);
            
            if ($mime === 'application/pdf') {
                // Za PDF: učitaj prvu stranicu
                $imagick->readImage($filePath . '[0]');
            } else {
                // Za slike: učitaj direktno
                $imagick->readImage($filePath);
            }
            
            // Konvertuj u greyscale
            $imagick->transformImageColorspace(\Imagick::COLORSPACE_GRAY);
            
            // Ukloni ICC profile i metapodatke
            $imagick->stripImage();
            
            // Postavi format na PDF
            $imagick->setImageFormat('pdf');
            
            // Postavi kompresiju za manji PDF
            // Koristimo JPEG kompresiju sa kvalitetom 70 za veću kompresiju
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(70);
            
            // Optimizuj PDF za manju veličinu
            $imagick->setOption('pdf:use-trimbox', 'true');
            
            // Kreiraj PDF
            $pdfData = $imagick->getImageBlob();
            
            $imagick->clear();
            $imagick->destroy();
            
            Log::info('PHP Imagick conversion completed', [
                'pdf_size' => strlen($pdfData)
            ]);
            
            // Proveri da li je PDF validan
            if ($pdfData === false || empty($pdfData) || strlen($pdfData) < 100) {
                Log::error('PHP Imagick PDF data is invalid or too small', [
                    'data_length' => $pdfData ? strlen($pdfData) : 0
                ]);
                return false;
            }
            
            // Proveri da li PDF počinje sa validnim PDF header-om
            if (strpos($pdfData, '%PDF') !== 0) {
                Log::error('PHP Imagick PDF data does not start with valid PDF header', [
                    'header' => substr($pdfData, 0, 8),
                    'data_length' => strlen($pdfData)
                ]);
                return false;
            }
            
            return $pdfData;
            
        } catch (\Exception $e) {
            Log::error('PHP Imagick processing exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Obrađuje dokument koristeći ImageMagick convert komandu (fallback)
     *
     * @param string $filePath
     * @param string $mime
     * @param string $convertPath
     * @return string|false PDF data
     */
    protected function processWithImageMagick(string $filePath, string $mime, string $convertPath)
    {
        try {
            $tempPdfPath = sys_get_temp_dir() . '/' . uniqid('processed_', true) . '.pdf';

            // Direktna konverzija u greyscale PDF sa 300 DPI
            // Koristimo jednostavnije opcije za bolju kompatibilnost
            Log::info('Starting ImageMagick conversion', [
                'mime' => $mime,
                'file_path' => $filePath,
                'temp_pdf_path' => $tempPdfPath
            ]);
            
            if ($mime === 'application/pdf') {
                // Za PDF: konvertuj prvu stranicu direktno u greyscale PDF sa 300 DPI
                // Koristimo -colorspace Gray i -compress JPEG sa quality 70 za kompresiju
                $command = sprintf(
                    '%s -density 300 "%s[0]" -colorspace Gray -compress JPEG -quality 70 "%s" 2>&1',
                    escapeshellarg($convertPath),
                    escapeshellarg($filePath),
                    escapeshellarg($tempPdfPath)
                );
            } else {
                // Za slike: konvertuj direktno u greyscale PDF sa 300 DPI
                // -colorspace Gray konvertuje u greyscale
                // -compress JPEG -quality 70 za kompresiju
                $command = sprintf(
                    '%s -density 300 "%s" -colorspace Gray -compress JPEG -quality 70 "%s" 2>&1',
                    escapeshellarg($convertPath),
                    escapeshellarg($filePath),
                    escapeshellarg($tempPdfPath)
                );
            }
            
            Log::info('ImageMagick command', ['command' => $command]);

            $startTime = microtime(true);
            exec($command, $output, $returnCode);
            $executionTime = microtime(true) - $startTime;
            
            Log::info('ImageMagick conversion completed', [
                'execution_time' => round($executionTime, 2) . ' seconds',
                'return_code' => $returnCode,
                'output_lines' => count($output),
                'file_exists' => file_exists($tempPdfPath)
            ]);

            if ($returnCode !== 0 || !file_exists($tempPdfPath)) {
                Log::error('ImageMagick PDF conversion failed', [
                    'command' => $command,
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode,
                    'execution_time' => round($executionTime, 2) . ' seconds'
                ]);
                return false;
            }

            // Proveri da li je PDF validan (ima minimalnu veličinu)
            $fileSize = filesize($tempPdfPath);
            if ($fileSize < 100) {
                Log::error('Generated PDF is too small, likely corrupted', [
                    'file_size' => $fileSize,
                    'temp_path' => $tempPdfPath
                ]);
                if (file_exists($tempPdfPath)) {
                    unlink($tempPdfPath);
                }
                return false;
            }

            // Pročitaj PDF data
            $pdfData = file_get_contents($tempPdfPath);
            
            // Obriši privremeni fajl odmah nakon čitanja
            if (file_exists($tempPdfPath)) {
                unlink($tempPdfPath);
            }

            // Proveri da li je PDF data validan
            if ($pdfData === false || empty($pdfData) || strlen($pdfData) < 100) {
                Log::error('PDF data is invalid or too small', [
                    'data_length' => $pdfData ? strlen($pdfData) : 0
                ]);
                return false;
            }

            // Proveri da li PDF počinje sa validnim PDF header-om
            if (strpos($pdfData, '%PDF') !== 0) {
                Log::error('PDF data does not start with valid PDF header', [
                    'header' => substr($pdfData, 0, 8),
                    'data_length' => strlen($pdfData)
                ]);
                return false;
            }

            // Proveri da li PDF ima validan footer (%%EOF)
            if (strpos($pdfData, '%%EOF') === false) {
                Log::warning('PDF may be incomplete - EOF marker not found', [
                    'data_length' => strlen($pdfData),
                    'last_100_chars' => substr($pdfData, -100)
                ]);
            }

            return $pdfData;
        } catch (Exception $e) {
            Log::error('ImageMagick processing exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obrađuje dokument koristeći GD (fallback metoda)
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $mime
     * @return string|false PDF data
     */
    protected function processWithGd($file, string $mime)
    {
        try {
            // Učitaj sliku ili PDF
            $imageResource = null;
            if ($mime === 'application/pdf') {
                // Za PDF, prvo konvertuj u sliku koristeći ImageMagick ako je dostupan
                $imageResource = $this->convertPdfToImage($file->getRealPath());
                if (!$imageResource) {
                    return false;
                }
            } else {
                // Za slike, učitaj direktno
                $imageResource = $this->createImageResource($file->getRealPath(), $mime);
                if (!$imageResource) {
                    return false;
                }
            }

            // Konvertuj u greyscale
            $greyscaleImage = $this->convertToGreyscale($imageResource);
            if (!$greyscaleImage) {
                imagedestroy($imageResource);
                return false;
            }

            // Kreiraj PDF sa 300 DPI (bolji kvalitet)
            $pdfData = $this->createPdfFromImage($greyscaleImage, 300);
            
            imagedestroy($imageResource);
            imagedestroy($greyscaleImage);

            return $pdfData;
        } catch (Exception $e) {
            Log::error('GD processing exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Konvertuje PDF u sliku koristeći ImageMagick
     *
     * @param string $pdfPath
     * @return resource|false
     */
    protected function convertPdfToImage(string $pdfPath)
    {
        try {
            // Proveri da li je ImageMagick dostupan
            $convertPath = $this->findImageMagickConvert();
            if (!$convertPath) {
                Log::error('ImageMagick convert not found');
                return false;
            }

            // Kreiraj privremeni fajl za output slike
            $tempImagePath = sys_get_temp_dir() . '/' . uniqid('pdf_convert_', true) . '.png';
            
            // Konvertuj prvu stranicu PDF-a u PNG sa 300 DPI
            $command = sprintf(
                '%s -density 300 -quality 100 "%s[0]" "%s" 2>&1',
                escapeshellarg($convertPath),
                escapeshellarg($pdfPath),
                escapeshellarg($tempImagePath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tempImagePath)) {
                Log::error('PDF to image conversion failed', [
                    'command' => $command,
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode
                ]);
                return false;
            }

            // Učitaj konvertovanu sliku
            $imageResource = @imagecreatefrompng($tempImagePath);
            
            // Obriši privremeni fajl
            if (file_exists($tempImagePath)) {
                @unlink($tempImagePath);
            }
            
            if ($imageResource === false) {
                Log::error('Failed to load converted PNG image');
                return false;
            }

            return $imageResource;
        } catch (Exception $e) {
            Log::error('PDF to image conversion exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Konvertuje sliku u greyscale
     *
     * @param resource $imageResource
     * @return resource|false
     */
    protected function convertToGreyscale($imageResource)
    {
        try {
            $width = imagesx($imageResource);
            $height = imagesy($imageResource);

            if ($width === false || $height === false) {
                return false;
            }

            // Kreiraj novu greyscale sliku
            $greyscale = imagecreatetruecolor($width, $height);
            
            if ($greyscale === false) {
                return false;
            }

            // Optimizovana greyscale konverzija - koristi imagefilter ako je dostupan
            if (function_exists('imagefilter')) {
                // Kopiraj originalnu sliku
                imagecopy($greyscale, $imageResource, 0, 0, 0, 0, $width, $height);
                // Primeni greyscale filter (brže od piksel-po-piksel metode)
                imagefilter($greyscale, IMG_FILTER_GRAYSCALE);
            } else {
                // Fallback: piksel po piksel metoda sa optimizacijom
                // Kreiraj cache za boje da izbegnemo ponovno alociranje
                $colorCache = [];
                
                for ($x = 0; $x < $width; $x++) {
                    for ($y = 0; $y < $height; $y++) {
                        $rgb = imagecolorat($imageResource, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        
                        // Izračunaj greyscale vrednost (luminance formula)
                        $grey = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                        
                        // Koristi cache za boje
                        if (!isset($colorCache[$grey])) {
                            $colorCache[$grey] = imagecolorallocate($greyscale, $grey, $grey, $grey);
                            if ($colorCache[$grey] === false) {
                                // Ako alokacija ne uspe, koristi najbližu postojeću boju
                                $colorCache[$grey] = imagecolorclosest($greyscale, $grey, $grey, $grey);
                            }
                        }
                        
                        imagesetpixel($greyscale, $x, $y, $colorCache[$grey]);
                    }
                }
            }

            return $greyscale;
        } catch (Exception $e) {
            Log::error('Greyscale conversion exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Kreira PDF iz slike sa određenom DPI rezolucijom
     *
     * @param resource $imageResource
     * @param int $dpi
     * @return string|false PDF data
     */
    protected function createPdfFromImage($imageResource, int $dpi = 300)
    {
        try {
            // Proveri da li je ImageMagick dostupan
            $convertPath = $this->findImageMagickConvert();
            if (!$convertPath) {
                // Fallback: koristi GD za kreiranje jednostavnog PDF-a
                return $this->createPdfFromImageGd($imageResource, $dpi);
            }

            // Sačuvaj sliku privremeno
            $tempImagePath = sys_get_temp_dir() . '/' . uniqid('img_', true) . '.png';
            $pngResult = @imagepng($imageResource, $tempImagePath);
            
            if ($pngResult === false || !file_exists($tempImagePath)) {
                Log::error('Failed to save temporary PNG image');
                return false;
            }

            // Kreiraj privremeni PDF
            $tempPdfPath = sys_get_temp_dir() . '/' . uniqid('pdf_', true) . '.pdf';

            // Konvertuj PNG u PDF sa 300 DPI (bolji kvalitet)
            // -colorspace Gray konvertuje u greyscale
            // -compress JPEG -quality 70 za kompresiju
            $command = sprintf(
                '%s -density 300 -colorspace Gray -compress JPEG -quality 70 "%s" "%s" 2>&1',
                escapeshellarg($convertPath),
                escapeshellarg($tempImagePath),
                escapeshellarg($tempPdfPath)
            );

            exec($command, $output, $returnCode);

            // Obriši privremenu sliku
            if (file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }

            if ($returnCode !== 0 || !file_exists($tempPdfPath)) {
                Log::error('Image to PDF conversion failed', [
                    'command' => $command,
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode
                ]);
                return false;
            }

            // Proveri da li je PDF validan (ima minimalnu veličinu)
            $fileSize = filesize($tempPdfPath);
            if ($fileSize < 100) {
                Log::error('Generated PDF is too small, likely corrupted', [
                    'file_size' => $fileSize,
                    'temp_path' => $tempPdfPath
                ]);
                if (file_exists($tempPdfPath)) {
                    unlink($tempPdfPath);
                }
                return false;
            }

            // Pročitaj PDF data
            $pdfData = file_get_contents($tempPdfPath);
            
            // Obriši privremeni PDF
            if (file_exists($tempPdfPath)) {
                unlink($tempPdfPath);
            }

            // Proveri da li je PDF data validan
            if ($pdfData === false || empty($pdfData) || strlen($pdfData) < 100) {
                Log::error('PDF data is invalid or too small', [
                    'data_length' => $pdfData ? strlen($pdfData) : 0
                ]);
                return false;
            }

            return $pdfData;
        } catch (Exception $e) {
            Log::error('PDF creation exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Fallback metoda za kreiranje PDF-a koristeći GD (jednostavniji pristup)
     *
     * @param resource $imageResource
     * @param int $dpi
     * @return string|false PDF data
     */
    protected function createPdfFromImageGd($imageResource, int $dpi = 300)
    {
        try {
            $width = imagesx($imageResource);
            $height = imagesy($imageResource);

            if ($width === false || $height === false) {
                return false;
            }

            // Izračunaj dimenzije u inčima (za 300 DPI)
            $widthInches = $width / $dpi;
            $heightInches = $height / $dpi;

            // Kreiraj jednostavan PDF koristeći FPDF format
            // Format: PDF 1.4 sa jednostavnom strukturom
            $pdf = "%PDF-1.4\n";
            
            // Objekti sa offsetima
            $objectOffsets = [];
            
            // Object 1: Catalog
            $objectOffsets[1] = strlen($pdf);
            $pdf .= "1 0 obj\n";
            $pdf .= "<<\n";
            $pdf .= "/Type /Catalog\n";
            $pdf .= "/Pages 2 0 R\n";
            $pdf .= ">>\n";
            $pdf .= "endobj\n";

            // Sačuvaj sliku kao PNG za embedovanje u PDF
            ob_start();
            imagepng($imageResource);
            $imageData = ob_get_clean();
            
            if ($imageData === false || empty($imageData)) {
                Log::error('Failed to generate PNG image data');
                return false;
            }

            // Object 2: Pages
            $objectOffsets[2] = strlen($pdf);
            $pdf .= "2 0 obj\n";
            $pdf .= "<<\n";
            $pdf .= "/Type /Pages\n";
            $pdf .= "/Kids [3 0 R]\n";
            $pdf .= "/Count 1\n";
            $pdf .= ">>\n";
            $pdf .= "endobj\n";

            // Object 3: Page
            $objectOffsets[3] = strlen($pdf);
            $pdf .= "3 0 obj\n";
            $pdf .= "<<\n";
            $pdf .= "/Type /Page\n";
            $pdf .= "/Parent 2 0 R\n";
            $pdf .= "/MediaBox [0 0 " . ($widthInches * 72) . " " . ($heightInches * 72) . "]\n";
            $pdf .= "/Resources <<\n";
            $pdf .= "/XObject <<\n";
            $pdf .= "/Im1 4 0 R\n";
            $pdf .= ">>\n";
            $pdf .= ">>\n";
            $pdf .= "/Contents 5 0 R\n";
            $pdf .= ">>\n";
            $pdf .= "endobj\n";

            // Kompresuj PNG podatke koristeći FlateDecode (zlib kompresija)
            $compressedData = gzcompress($imageData);
            if ($compressedData === false) {
                // Fallback: koristi nekompresovane podatke
                $compressedData = $imageData;
                $filter = '/ASCIIHexDecode'; // Jednostavniji filter
            } else {
                $filter = '/FlateDecode';
            }

            // Object 4: Image XObject
            $objectOffsets[4] = strlen($pdf);
            $pdf .= "4 0 obj\n";
            $pdf .= "<<\n";
            $pdf .= "/Type /XObject\n";
            $pdf .= "/Subtype /Image\n";
            $pdf .= "/Width $width\n";
            $pdf .= "/Height $height\n";
            $pdf .= "/ColorSpace /DeviceGray\n";
            $pdf .= "/BitsPerComponent 8\n";
            $pdf .= "/Filter $filter\n";
            $pdf .= "/Length " . strlen($compressedData) . "\n";
            $pdf .= ">>\n";
            $pdf .= "stream\n";
            $pdf .= $compressedData;
            $pdf .= "\nendstream\n";
            $pdf .= "endobj\n";

            // Object 5: Contents stream
            $objectOffsets[5] = strlen($pdf);
            $contentsStream = "q\n" . ($widthInches * 72) . " 0 0 " . ($heightInches * 72) . " 0 0 cm\n/Im1 Do\nQ\n";
            $pdf .= "5 0 obj\n";
            $pdf .= "<<\n";
            $pdf .= "/Length " . strlen($contentsStream) . "\n";
            $pdf .= ">>\n";
            $pdf .= "stream\n";
            $pdf .= $contentsStream;
            $pdf .= "endstream\n";
            $pdf .= "endobj\n";

            // xref table
            $xrefOffset = strlen($pdf);
            $pdf .= "xref\n";
            $pdf .= "0 6\n";
            // Free object (obj 0)
            $pdf .= "0000000000 65535 f \n";
            // Objekti 1-5
            for ($i = 1; $i <= 5; $i++) {
                $offset = isset($objectOffsets[$i]) ? $objectOffsets[$i] : 0;
                $pdf .= str_pad((string)$offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
            }

            // trailer
            $pdf .= "trailer\n";
            $pdf .= "<<\n";
            $pdf .= "/Size 6\n";
            $pdf .= "/Root 1 0 R\n";
            $pdf .= ">>\n";
            $pdf .= "startxref\n";
            $pdf .= $xrefOffset . "\n";
            $pdf .= "%%EOF\n";

            return $pdf;
        } catch (Exception $e) {
            Log::error('GD PDF creation exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Pronalazi putanju do ImageMagick convert komande
     *
     * @return string|false
     */
    protected function findImageMagickConvert()
    {
        $possiblePaths = [
            'convert',
            '/usr/bin/convert',
            '/usr/local/bin/convert',
            '/opt/local/bin/convert',
            'C:\\Program Files\\ImageMagick\\convert.exe',
            'C:\\ImageMagick\\convert.exe',
        ];

        foreach ($possiblePaths as $path) {
            $command = escapeshellarg($path) . ' -version 2>&1';
            exec($command, $output, $returnCode);
            if ($returnCode === 0) {
                return $path;
            }
        }

        return false;
    }
}

