<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckImageMagick extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imagemagick:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proverava da li je ImageMagick instaliran i dostupan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Proveravam ImageMagick...');
        $this->newLine();

        // Proveri da li je ImageMagick ekstenzija učitana u PHP
        $this->info('1. PHP ImageMagick ekstenzija:');
        if (extension_loaded('imagick')) {
            $this->info('   ✓ Imagick ekstenzija je učitana');
            $imagickVersion = phpversion('imagick');
            if ($imagickVersion) {
                $this->info("   Verzija: {$imagickVersion}");
            }
        } else {
            $this->error('   ✗ Imagick ekstenzija NIJE učitana');
        }
        $this->newLine();

        // Proveri da li postoji convert komanda
        $this->info('2. ImageMagick convert komanda:');
        $convertPaths = [
            '/usr/bin/convert',
            '/usr/local/bin/convert',
            '/opt/plesk/php/8.3/bin/convert',
            'convert', // System PATH
        ];

        $found = false;
        foreach ($convertPaths as $path) {
            if ($path === 'convert') {
                // Pokušaj da nađeš preko which
                $output = [];
                $returnCode = 0;
                @exec('which convert 2>&1', $output, $returnCode);
                if ($returnCode === 0 && !empty($output)) {
                    $actualPath = trim($output[0]);
                    $this->info("   ✓ Pronađen na: {$actualPath}");
                    $this->checkConvertVersion($actualPath);
                    $found = true;
                    break;
                }
            } else {
                if (file_exists($path) && is_executable($path)) {
                    $this->info("   ✓ Pronađen na: {$path}");
                    $this->checkConvertVersion($path);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            $this->error('   ✗ convert komanda NIJE pronađena');
            $this->warn('   Pokušavam direktno...');
            $this->checkConvertVersion('convert');
        }
        $this->newLine();

        // Proveri da li DocumentProcessor može da nađe convert
        $this->info('3. DocumentProcessor provera:');
        $documentProcessor = app(\App\Services\DocumentProcessor::class);
        $reflection = new \ReflectionClass($documentProcessor);
        $method = $reflection->getMethod('findImageMagickConvert');
        $method->setAccessible(true);
        $convertPath = $method->invoke($documentProcessor);
        
        if ($convertPath) {
            $this->info("   ✓ DocumentProcessor pronašao: {$convertPath}");
        } else {
            $this->error('   ✗ DocumentProcessor NIJE pronašao convert');
        }
        $this->newLine();

        // Test konverzija
        $this->info('4. Test konverzija:');
        $this->testConversion($convertPath);
    }

    private function checkConvertVersion($path)
    {
        $output = [];
        $returnCode = 0;
        @exec("{$path} --version 2>&1", $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output)) {
            $version = trim($output[0]);
            $this->info("   Verzija: {$version}");
        } else {
            $this->warn("   Nije moguće dobiti verziju (return code: {$returnCode})");
            if (!empty($output)) {
                $this->warn("   Output: " . implode("\n   ", $output));
            }
        }
    }

    private function testConversion($convertPath)
    {
        if (!$convertPath) {
            $this->error('   ✗ Nema convert putanje za test');
            return;
        }

        // Kreiraj test sliku (1x1 crna slika)
        $testImagePath = sys_get_temp_dir() . '/test_imagemagick_' . uniqid() . '.png';
        $testPdfPath = sys_get_temp_dir() . '/test_imagemagick_' . uniqid() . '.pdf';

        try {
            // Kreiraj test PNG
            $command = sprintf(
                '%s -size 100x100 xc:white -pointsize 20 -fill black -gravity center -annotate +0+0 "Test" "%s" 2>&1',
                escapeshellarg($convertPath),
                escapeshellarg($testImagePath)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($testImagePath)) {
                $this->error('   ✗ Kreiranje test slike neuspešno');
                if (!empty($output)) {
                    $this->warn('   Output: ' . implode("\n   ", $output));
                }
                return;
            }

            $this->info('   ✓ Test slika kreirana');

            // Konvertuj u PDF
            $command = sprintf(
                '%s -density 300 "%s" -colorspace Gray "%s" 2>&1',
                escapeshellarg($convertPath),
                escapeshellarg($testImagePath),
                escapeshellarg($testPdfPath)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($testPdfPath)) {
                $this->error('   ✗ Konverzija u PDF neuspešna');
                if (!empty($output)) {
                    $this->warn('   Output: ' . implode("\n   ", $output));
                }
            } else {
                $pdfSize = filesize($testPdfPath);
                $pdfHeader = file_get_contents($testPdfPath, false, null, 0, 8);
                
                if (strpos($pdfHeader, '%PDF') === 0) {
                    $this->info("   ✓ PDF kreiran uspešno ({$pdfSize} bytes)");
                } else {
                    $this->error('   ✗ PDF je kreiran ali nije validan');
                    $this->warn("   Header: {$pdfHeader}");
                }
            }

            // Obriši test fajlove
            @unlink($testImagePath);
            @unlink($testPdfPath);

        } catch (\Exception $e) {
            $this->error('   ✗ Greška tokom testa: ' . $e->getMessage());
        }
    }
}

