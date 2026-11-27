<?php

namespace App\Services\Competitions;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

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
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP proširenje zip nije uključeno, pa MMSP odluka ne može biti sačuvana.');
        }

        $template = $this->templatePath();
        if (! is_file($template)) {
            throw new RuntimeException('Službeni template Odluke za MMSP nije pronađen.');
        }

        $source = new ZipArchive();
        if ($source->open($template) !== true) {
            throw new RuntimeException('Službeni template Odluke za MMSP nije pročitan.');
        }
        $xml = $source->getFromName('word/document.xml');
        $source->close();
        if (! is_string($xml) || $xml === '') {
            throw new RuntimeException('Službeni template Odluke za MMSP nema tekst.');
        }
        $document = $this->loadXml($xml);

        $this->fillHeaderLine($document, $proposal);
        $this->fillTitleYear($document, $proposal);
        $this->fillWinners($document, $proposal);
        $this->fillNarrative($document, $proposal);
        $this->fillDelivery($document, $proposal);
        $this->removeTextHighlights($document);

        $path = tempnam(sys_get_temp_dir(), 'mmsp-odluka-');
        if ($path === false) {
            throw new RuntimeException('Privremeni fajl Odluke nije kreiran.');
        }
        $target = $path.'.docx';
        @unlink($path);
        if (! copy($template, $target)) {
            throw new RuntimeException('Privremeni fajl Odluke nije kreiran.');
        }
        $output = new ZipArchive();
        if ($output->open($target) !== true) {
            $this->discard($target);
            throw new RuntimeException('Privremeni fajl Odluke nije otvoren.');
        }
        if ($output->addFromString('word/document.xml', $this->saveXml($document)) !== true) {
            $output->close();
            $this->discard($target);
            throw new RuntimeException('Tekst Odluke nije upisan u dokument.');
        }
        if ($output->close() !== true) {
            $this->discard($target);
            throw new RuntimeException('Dokument Odluke nije zatvoren.');
        }

        return $target;
    }

    private function discard(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
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
        $this->replaceExact($paragraph, ' 2026.', '');
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
        if (! $this->replaceInParagraph($opening, 'trajao 20 dana', 'trajao '.MmspDecisionInflection::days($proposal->callDurationDays))) {
            throw new RuntimeException('Template nema trajanje javnog poziva.');
        }

        $this->setParagraphText(
            $this->requireParagraph($document, 'prvoj sjednici'),
            MmspDecisionInflection::firstSessionParagraph(
                $proposal->firstSessionDate,
                $proposal->receivedCount,
                $proposal->incompleteCount,
                $proposal->appealsAccepted,
                $proposal->appealsRejected,
                $proposal->proceedCount,
            )
        );

        $second = $this->requireParagraph($document, 'drugoj sjednici');
        $this->replaceExact($second, 'XX', $proposal->secondSessionDate);

        $third = $this->requireParagraph($document, 'trećoj sjednici');
        $this->replaceExact($third, 'XX', $proposal->thirdSessionDate);

        $closing = $this->requireParagraph($document, 'generisala predlog Odluke');
        $fields = $this->exactNodes($closing, 'XX');
        if (count($fields) < 3) {
            throw new RuntimeException('Template nema zbirna polja predloga Odluke.');
        }
        $winnerCount = count($proposal->winners);
        if ($winnerCount === 0) {
            $zeroClause = 'kojom se konstatuje da sredstva nijesu dodijeljena';
            $replacedFunds = $this->replaceInParagraph(
                $closing,
                'kojom se dodjeljuju sredstva za XX plan/ova ulaganja u ukupnom iznosu XX eura',
                $zeroClause
            ) || $this->replaceInParagraph(
                $closing,
                'kojom se dodjeljuju sredstva za XX planova ulaganja u ukupnom iznosu XX eura',
                $zeroClause
            );
            if (! $replacedFunds) {
                throw new RuntimeException('Template nema rečenicu o iznosu dodijeljenih sredstava.');
            }
            $year = $this->exactNodes($closing, 'XX');
            if ($year === []) {
                throw new RuntimeException('Template nema godinu predloga Odluke.');
            }
            $year[0]->textContent = $proposal->competitionYear.'.';

            return;
        }

        $fields[0]->textContent = $proposal->competitionYear.'.';
        $fields[1]->textContent = (string) $winnerCount;
        $fields[2]->textContent = $proposal->awardedTotal;
        $plan = MmspDecisionInflection::plans($winnerCount);
        if (! $this->replaceInParagraph($closing, 'plan/ova', $plan)
            && ! $this->replaceInParagraph($closing, 'planova', $plan)) {
            throw new RuntimeException('Template nema broj planova ulaganja.');
        }
    }

    private function fillDelivery(DOMDocument $document, MmspDecisionProposal $proposal): void
    {
        $recipients = $this->requireParagraph($document, 'Podnosiocima');
        $this->replaceExact($recipients, 'XX', (string) $proposal->participantCount);
        $members = $this->requireParagraph($document, 'Članovima Komisije');
        $this->replaceExact($members, '3', (string) $proposal->commissionMemberCount);
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

    private function replaceInParagraph(DOMElement $paragraph, string $from, string $to): bool
    {
        $nodes = $this->textNodes($paragraph);
        $full = '';
        $lengths = [];
        foreach ($nodes as $node) {
            $lengths[] = strlen($node->textContent);
            $full .= $node->textContent;
        }
        $position = strpos($full, $from);
        if ($position === false) {
            return false;
        }

        $end = $position + strlen($from);
        $cursor = 0;
        $inserted = false;
        foreach ($nodes as $index => $node) {
            $start = $cursor;
            $nodeEnd = $cursor + $lengths[$index];
            $cursor = $nodeEnd;
            if ($nodeEnd <= $position || $start >= $end) {
                continue;
            }
            $prefix = $start < $position ? substr($node->textContent, 0, $position - $start) : '';
            $suffix = $nodeEnd > $end ? substr($node->textContent, $end - $start) : '';
            $node->textContent = $prefix.($inserted ? '' : $to).$suffix;
            $inserted = true;
        }

        return true;
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
