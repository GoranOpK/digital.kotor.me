<?php

namespace App\Services\Competitions;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PhpZip\ZipFile;
use RuntimeException;

/**
 * Popunjava službeni DOCX. Mijenja samo tekstualne čvorove i umnožava postojeći blok člana 1.
 */
class YouthDecisionDocxWriter
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function templatePath(): string
    {
        return resource_path('templates/pm/odluka-o-raspodjeli-sredstava.docx');
    }

    public function write(YouthDecisionProposal $proposal): string
    {
        $template = $this->templatePath();
        if (! is_file($template)) {
            throw new RuntimeException('Službeni template Odluke za mlade nije pronađen.');
        }

        $zip = new ZipFile();
        $zip->openFile($template);
        $document = $this->loadXml($zip->getEntryContents('word/document.xml'));
        $header = $this->loadXml($zip->getEntryContents('word/header1.xml'));

        $this->correctHeader($header);
        $this->correctStaticText($document);
        $this->fillHeaderLine($document, $proposal);
        $this->fillWinners($document, $proposal);
        $this->fillNarrative($document, $proposal);
        $this->fillSignatureAndDelivery($document, $proposal);

        $zip->addFromString('word/document.xml', $this->saveXml($document));
        $zip->addFromString('word/header1.xml', $this->saveXml($header));

        $path = tempnam(sys_get_temp_dir(), 'pm-odluka-');
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

    private function correctHeader(DOMDocument $header): void
    {
        foreach ($this->paragraphs($header) as $paragraph) {
            $text = $this->paragraphText($paragraph);
            if (str_contains($text, 'Sekretarijat za razvoj preduzetništva komunalne')) {
                $this->replaceExact(
                    $paragraph,
                    'Sekretarijat za razvoj preduzetništva komunalne poslove i saobraćaj',
                    'Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj'
                );
            }
        }
    }

    private function correctStaticText(DOMDocument $document): void
    {
        foreach ($this->paragraphs($document) as $paragraph) {
            $text = $this->paragraphText($paragraph);
            if (str_contains($text, 'ženskom preduzetništvu')) {
                $this->replaceContaining($paragraph, 'ženskom preduzetništvu', 'preduzetništvu mladih');
                $this->replaceExact($paragraph, ' 22.06. ', ' 22.06.');
            }
            if (str_contains($text, 'sledećih')) {
                $this->replaceExact($paragraph, 'sledećih', 'sljedećih');
                $this->replaceExact($paragraph, 'laca', 'ilaca');
            }
            if (str_contains($text, 'u roku od u roku od')) {
                $this->removeDuplicateDeadline($paragraph);
            }
            if (str_contains($text, '22.06. 2026')) {
                $this->replaceContaining($paragraph, '22.06. 2026', '22.06.2026');
            }
            if (str_contains($text, 'na drugoj sjednici  Komisije')) {
                $this->replaceExact($paragraph, 'na drugoj sjednici  Komisije ', 'na drugoj sjednici Komisije ');
            }
            if (str_contains($text, 'dredstava') || str_contains($text, 'Komisija je dana')) {
                $this->replaceExact($paragraph, ' biznis planova, Komisija je dana ', ' biznis planova, Komisija je ');
                $this->replaceContaining($paragraph, 'dredstava', 'sredstava');
                $this->replaceContaining($paragraph, ' dodjeljuje I ', ' dodjeljuje i ');
            }
            if (str_contains($text, 'sekretarijatu za razvoj')) {
                $this->replaceContaining($paragraph, 'sekretarijatu za razvoj', 'Sekretarijatu za razvoj');
            }
        }
    }

    private function fillHeaderLine(DOMDocument $document, YouthDecisionProposal $proposal): void
    {
        $paragraph = $this->requireParagraph($document, 'Broj:');
        $markers = $this->exactNodes($paragraph, 'XX');
        if (count($markers) < 2) {
            throw new RuntimeException('Template nema polje za broj i datum Odluke.');
        }
        $markers[0]->textContent = $proposal->decisionNumber;
        $markers[1]->textContent = $proposal->decisionDate;
        $this->replaceExact($paragraph, ' 2026. ', ' ');

        $this->replaceExact($this->requireParagraph($document, 'o raspodjeli sredstava za podršku'), 'XX', $proposal->competitionYear);
    }

    private function fillWinners(DOMDocument $document, YouthDecisionProposal $proposal): void
    {
        $block = $this->winnerBlock($document);
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
            $this->replaceExact($group[1], 'xx', $winner['plan']);
            $this->setParagraphText($group[2], 'Ukupno bodova: '.$winner['score']);
            $this->replaceExact($group[3], 'xx', $winner['approved']);
            $this->replaceExact($group[4], 'xx', $winner['total_value']);
        }
    }

    private function fillNarrative(DOMDocument $document, YouthDecisionProposal $proposal): void
    {
        $opening = $this->requireParagraph($document, 'raspisala je Javni konkurs');
        $years = $this->exactNodes($opening, 'XX');
        if (count($years) < 3) {
            throw new RuntimeException('Template nema polja za godinu i rok konkursa.');
        }
        $years[0]->textContent = $proposal->competitionYear;
        $years[1]->textContent = $proposal->publishedOn;
        $years[2]->textContent = $proposal->endedOn;

        $first = $this->requireParagraph($document, 'prvoj');
        $this->setParagraphText($first, $this->firstSessionParagraph($proposal));

        $oral = $this->requireParagraph($document, 'usmeno obrazloženje');
        $this->replaceExact(
            $oral,
            'na drugoj sjednici Komisije ',
            'na drugoj sjednici Komisije održanoj dana '.$proposal->secondSessionDate.'. godine '
        );
        $this->replaceExact($oral, 'XX', $proposal->oralDate);

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
        $this->planWord($closing, count($proposal->winners));
    }

    private function fillSignatureAndDelivery(DOMDocument $document, YouthDecisionProposal $proposal): void
    {
        foreach ($this->paragraphs($document) as $paragraph) {
            $previous = $this->previousParagraph($paragraph);
            if ($previous !== null && str_contains($this->paragraphText($previous), '___') && trim($this->paragraphText($paragraph)) === 'XX') {
                $this->replaceExact($paragraph, 'XX', 'Vladan Barović');
            }
        }

        $recipients = $this->requireParagraph($document, 'Podnosiocima prijave');
        $this->replaceExact($recipients, 'XX', (string) count($proposal->winners));
        $this->setParagraphText(
            $this->requireParagraph($document, 'Članovima Komisije'),
            'Članovima Komisije (x'.$proposal->commissionMemberCount.')'
        );
        $this->setParagraphText(
            $this->requireParagraph($document, 'Sekretarijatu 16'),
            'Sekretarijatu (x2)'
        );
    }

    private function firstSessionParagraph(YouthDecisionProposal $proposal): string
    {
        $receivedWord = $this->applicationsWord($proposal->receivedCount);
        $proceedWord = $this->applicationsWord($proposal->proceedCount);
        $proceedVerb = $proposal->proceedCount === 1 ? 'može' : 'mogu';

        return 'Nakon isteka roka za podnošenje prijava na Konkurs, Komisija je na prvoj sjednici održanoj dana '
            .$proposal->firstSessionDate
            .'. godine utvrdila da je pristiglo ukupno '
            .$proposal->receivedCount.' '.$receivedWord
            .'. Komisija je pregledala elektronski zaprimljene prijave, nakon čega je '
            .$this->incompleteClause($proposal->incompleteCount)
            .'. Podnosioci nepotpunih prijava imali su pravo prigovora Komisiji, nakon čega je Komisija donijela odluku o prihvatanju ('
            .$proposal->appealsAccepted
            .') ili odbijanju ('
            .$proposal->appealsRejected
            .') Prigovora. Komisija je konstatovala da '
            .$proposal->proceedCount.' '.$proceedWord.' '.$proceedVerb
            .' biti uzete u dalje razmatranje i vrednovanje po kriterijumima utvrđenim Odlukom i Konkursom.';
    }

    private function incompleteClause(int $count): string
    {
        if ($count === 0) {
            return 'konstatovala da nepotpunih prijava nema';
        }
        if ($count === 1) {
            return 'konstatovala da je 1 prijava nepotpuna u smislu priložene dokumentacije, te ista nije dalje razmatrana';
        }

        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return 'konstatovala da su '.$count.' prijave nepotpune u smislu priložene dokumentacije, te iste nijesu dalje razmatrane';
        }

        return 'konstatovala da je '.$count.' prijava nepotpuno u smislu priložene dokumentacije, te iste nijesu dalje razmatrane';
    }

    private function applicationsWord(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return 'prijave';
        }

        return 'prijava';
    }

    private function planWord(DOMElement $paragraph, int $count): void
    {
        $word = 'biznis planova';
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($count === 1) {
            $word = 'biznis plan';
        } elseif ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            $word = 'biznis plana';
        }
        $this->replaceExact($paragraph, 'biznis plan', $word);
        $this->replaceExact($paragraph, '/ova', '');
    }

    /**
     * @return list<DOMElement>
     */
    private function winnerBlock(DOMDocument $document): array
    {
        $start = $this->requireParagraph($document, 'ovdje ide');
        $block = [$start];
        $node = $start->nextSibling;
        while ($node !== null && count($block) < 5) {
            if ($node instanceof DOMElement && $node->localName === 'p') {
                $block[] = $node;
            }
            $node = $node->nextSibling;
        }
        if (count($block) !== 5) {
            throw new RuntimeException('Template nema blok člana 1.');
        }
        $texts = array_map(fn (DOMElement $paragraph) => $this->paragraphText($paragraph), $block);
        if (! str_contains($texts[1], 'Naziv biznis plana')
            || ! str_contains($texts[2], 'Ukupno bodova')
            || ! str_contains($texts[3], 'Iznos odobrenih')
            || ! str_contains($texts[4], 'Ukupna vrijednost')) {
            throw new RuntimeException('Blok člana 1 u template-u nije na očekivanom mjestu.');
        }

        return $block;
    }

    private function removeDuplicateDeadline(DOMElement $paragraph): void
    {
        $nodes = $this->textNodes($paragraph);
        $count = count($nodes);
        for ($index = 0; $index < $count - 3; $index++) {
            if ($nodes[$index]->textContent === 'roku'
                && $nodes[$index + 1]->textContent === ' od '
                && $nodes[$index + 2]->textContent === 'u '
                && $nodes[$index + 3]->textContent === 'roku') {
                $nodes[$index + 1]->textContent = '';
                $nodes[$index + 2]->textContent = '';
                $nodes[$index + 3]->textContent = '';

                return;
            }
        }
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

    private function previousParagraph(DOMElement $paragraph): ?DOMElement
    {
        $node = $paragraph->previousSibling;
        while ($node !== null) {
            if ($node instanceof DOMElement && $node->localName === 'p' && trim($this->paragraphText($node)) !== '') {
                return $node;
            }
            $node = $node->previousSibling;
        }

        return null;
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

    private function replaceContaining(DOMElement $paragraph, string $from, string $to): void
    {
        foreach ($this->textNodes($paragraph) as $node) {
            if (str_contains($node->textContent, $from)) {
                $node->textContent = str_replace($from, $to, $node->textContent);
            }
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
