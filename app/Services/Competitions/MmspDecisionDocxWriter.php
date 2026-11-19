<?php

namespace App\Services\Competitions;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PhpZip\ZipFile;
use RuntimeException;

/**
 * Popunjava službeni MMSP DOCX. Mijenja samo oznake za podatke.
 * Grb, zaglavlje, članovi, obrazloženje i potpis sekretara ostaju iz šablona.
 */
class MmspDecisionDocxWriter
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function templatePath(): string
    {
        return resource_path('templates/mmsp/odluka-o-dodjeli-subvencija.docx');
    }

    public function write(MmspDecisionProposal $proposal): string
    {
        $template = $this->templatePath();
        if (! is_file($template)) {
            throw new RuntimeException('Službeni template Odluke za MMSP nije pronađen.');
        }

        $zip = new ZipFile();
        $zip->openFile($template);
        $document = $this->loadXml($zip->getEntryContents('word/document.xml'));

        $this->fillHeaderLine($document, $proposal);
        $this->fillTitleYear($document, $proposal);
        $this->fillWinners($document, $proposal);
        $this->fillNarrative($document, $proposal);
        $this->fillDelivery($document, $proposal);
        $this->removeTextHighlights($document);

        $zip->addFromString('word/document.xml', $this->saveXml($document));

        $path = tempnam(sys_get_temp_dir(), 'mmsp-odluka-');
        if ($path === false) {
            $zip->close();
            throw new RuntimeException('Privremeni fajl Odluke nije kreiran.');
        }
        $target = $path.'.docx';
        @unlink($path);
        $zip->saveAsFile($target);
        $zip->close();

        return $target;
    }

    private function fillHeaderLine(DOMDocument $document, MmspDecisionProposal $proposal): void
    {
        $paragraph = $this->requireParagraph($document, 'Broj:');
        $markers = $this->exactNodes($paragraph, 'XX');
        if (count($markers) < 2) {
            throw new RuntimeException('Template nema polje za broj i datum Odluke.');
        }
        $markers[0]->textContent = '';
        $markers[1]->textContent = $proposal->decisionDate;
        $this->replaceExact($paragraph, ' 2026.', ' ');
    }

    private function fillTitleYear(DOMDocument $document, MmspDecisionProposal $proposal): void
    {
        $paragraph = $this->requireParagraph($document, 'o dodjeli subvencija za');
        $markers = $this->exactNodes($paragraph, 'XX');
        if ($markers === []) {
            throw new RuntimeException('Template nema godinu u naslovu Odluke.');
        }
        $markers[0]->textContent = $proposal->competitionYear;
    }

    private function fillWinners(DOMDocument $document, MmspDecisionProposal $proposal): void
    {
        $block = $this->winnerBlock($document);
        if ($proposal->winners === []) {
            $this->setParagraphText($block[0], 'Podnosilac/podnositeljka prijave: sredstva nijesu dodijeljena.');
            $this->setParagraphText($block[1], '');
            $this->setParagraphText($block[2], '');
            $this->setParagraphText($block[3], '');
            $this->setParagraphText($block[4], '');

            return;
        }

        $groups = [$block];
        $end = $block[4];
        for ($index = 1, $count = count($proposal->winners); $index < $count; $index++) {
            $clones = [];
            foreach ($block as $node) {
                $clone = $node->cloneNode(true);
                if (! $clone instanceof DOMElement) {
                    throw new RuntimeException('Blok člana 1 nije moguće umnožiti.');
                }
                $clones[] = $clone;
            }
            $reference = $end->nextSibling;
            foreach ($clones as $clone) {
                $end->parentNode?->insertBefore($clone, $reference);
            }
            $end = $clones[4];
            $groups[] = $clones;
        }

        foreach ($proposal->winners as $index => $winner) {
            $group = $groups[$index];
            $this->setParagraphText($group[0], 'Podnosilac/podnositeljka prijave: '.$winner['identity']);
            $this->setParagraphText($group[1], 'Vrsta subvencije koja se dodjeljuje: '.$winner['subsidy']);
            $this->setParagraphText($group[2], 'Ukupno bodova: '.$winner['score']);
            $this->replaceExact($group[3], 'xx', $winner['approved']);
            $this->replaceExact($group[4], 'xx', $winner['total_value']);
        }
    }

    private function fillNarrative(DOMDocument $document, MmspDecisionProposal $proposal): void
    {
        $opening = $this->requireParagraph($document, 'raspisala je Javni poziv');
        $years = $this->exactNodes($opening, 'XX');
        if (count($years) < 3) {
            throw new RuntimeException('Template nema polja za godinu i rok javnog poziva.');
        }
        $years[0]->textContent = $proposal->competitionYear;
        $years[1]->textContent = $proposal->publishedOn;
        $years[2]->textContent = $proposal->endedOn;

        $first = $this->requireParagraph($document, 'prvoj sjednici');
        $firstFields = $this->exactNodes($first, 'XX');
        if (count($firstFields) < 3) {
            throw new RuntimeException('Template nema polja prve sjednice.');
        }
        $firstFields[0]->textContent = $proposal->firstSessionDate;
        $firstFields[1]->textContent = (string) $proposal->receivedCount;
        $firstFields[2]->textContent = (string) $proposal->proceedCount;
        $incomplete = $this->exactNodes($first, 'X');
        if ($incomplete === []) {
            throw new RuntimeException('Template nema polje za nepotpune prijave.');
        }
        $incomplete[0]->textContent = (string) $proposal->incompleteCount;
        $this->replaceFirstContaining($first, 'prihvatanju', 'prihvatanju ('.$proposal->appealsAccepted.')');
        $this->replaceFirstContaining($first, 'odbijanju', 'odbijanju ('.$proposal->appealsRejected.')');

        $second = $this->requireParagraph($document, 'drugoj sjednici');
        $this->replaceExact($second, 'XX', $proposal->secondSessionDate);

        $third = $this->requireParagraph($document, 'trećoj sjednici');
        $this->replaceExact($third, 'XX', $proposal->thirdSessionDate);

        $closing = $this->requireParagraph($document, 'generisala predlog Odluke');
        $fields = $this->exactNodes($closing, 'XX');
        if (count($fields) < 3) {
            throw new RuntimeException('Template nema zbirna polja predloga Odluke.');
        }
        $fields[0]->textContent = $proposal->competitionYear;
        $fields[1]->textContent = (string) count($proposal->winners);
        $fields[2]->textContent = $proposal->awardedTotal;
    }

    private function fillDelivery(DOMDocument $document, MmspDecisionProposal $proposal): void
    {
        $recipients = $this->requireParagraph($document, 'Podnosiocima prijave');
        $this->replaceExact($recipients, 'XX', (string) count($proposal->winners));
    }

    /**
     * @return list<DOMElement>
     */
    private function winnerBlock(DOMDocument $document): array
    {
        $paragraphs = $this->paragraphs($document);
        foreach ($paragraphs as $index => $paragraph) {
            if (! str_contains($this->paragraphText($paragraph), 'ovdje ide')) {
                continue;
            }

            $block = array_slice($paragraphs, $index, 5);
            if (count($block) < 5
                || ! str_contains($this->paragraphText($block[1]), 'Vrsta subvencije')
                || ! str_contains($this->paragraphText($block[2]), 'Ukupno bodova')
                || ! str_contains($this->paragraphText($block[3]), 'Iznos subvencije')
                || ! str_contains($this->paragraphText($block[4]), 'Ukupna vrijednost')
            ) {
                break;
            }

            return $block;
        }

        throw new RuntimeException('Template nema blok podnosioca u članu 1.');
    }

    private function requireParagraph(DOMDocument $document, string $needle): DOMElement
    {
        foreach ($this->paragraphs($document) as $paragraph) {
            if (str_contains($this->paragraphText($paragraph), $needle)) {
                return $paragraph;
            }
        }

        throw new RuntimeException('Template nema očekivani pasus: '.$needle);
    }

    /**
     * @return list<DOMElement>
     */
    private function paragraphs(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', self::W);
        $paragraphs = [];
        foreach ($xpath->query('//w:p') ?: [] as $paragraph) {
            if ($paragraph instanceof DOMElement) {
                $paragraphs[] = $paragraph;
            }
        }

        return $paragraphs;
    }

    /**
     * @return list<DOMElement>
     */
    private function textNodes(DOMElement $paragraph): array
    {
        $xpath = new DOMXPath($paragraph->ownerDocument);
        $xpath->registerNamespace('w', self::W);
        $nodes = [];
        foreach ($xpath->query('.//w:t', $paragraph) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @return list<DOMElement>
     */
    private function exactNodes(DOMElement $paragraph, string $text): array
    {
        return array_values(array_filter(
            $this->textNodes($paragraph),
            fn (DOMElement $node) => $node->textContent === $text
        ));
    }

    private function paragraphText(DOMElement $paragraph): string
    {
        $text = '';
        foreach ($this->textNodes($paragraph) as $node) {
            $text .= $node->textContent;
        }

        return $text;
    }

    private function setParagraphText(DOMElement $paragraph, string $text): void
    {
        $nodes = $this->textNodes($paragraph);
        if ($nodes === []) {
            throw new RuntimeException('Pasus template-a nema tekst.');
        }
        $nodes[0]->textContent = $text;
        for ($index = 1, $count = count($nodes); $index < $count; $index++) {
            $nodes[$index]->textContent = '';
        }
    }

    private function replaceExact(DOMElement $paragraph, string $from, string $to): void
    {
        foreach ($this->textNodes($paragraph) as $node) {
            if ($node->textContent === $from) {
                $node->textContent = $to;
            }
        }
    }

    private function replaceFirstContaining(DOMElement $paragraph, string $needle, string $replacement): void
    {
        foreach ($this->textNodes($paragraph) as $node) {
            if (str_contains($node->textContent, $needle)) {
                $node->textContent = str_replace($needle, $replacement, $node->textContent);

                return;
            }
        }

        throw new RuntimeException('Template nema polje: '.$needle);
    }

    private function removeTextHighlights(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', self::W);
        $highlights = $xpath->query('//w:highlight');
        if ($highlights === false) {
            return;
        }

        $nodes = [];
        foreach ($highlights as $highlight) {
            $nodes[] = $highlight;
        }
        foreach ($nodes as $highlight) {
            $highlight->parentNode?->removeChild($highlight);
        }
    }

    private function loadXml(string $xml): DOMDocument
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;
        if (! $document->loadXML($xml)) {
            throw new RuntimeException('XML Odluke nije pročitan.');
        }

        return $document;
    }

    private function saveXml(DOMDocument $document): string
    {
        $xml = $document->saveXML();
        if (! is_string($xml) || $xml === '') {
            throw new RuntimeException('XML Odluke nije sačuvan.');
        }
        if (! str_contains($xml, 'standalone=')) {
            $xml = preg_replace(
                '/^<\?xml version="1\.0" encoding="UTF-8"\?>/',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
                $xml,
                1
            ) ?? $xml;
        }

        return $xml;
    }
}
