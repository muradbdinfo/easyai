<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelIOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Smalot\PdfParser\Config as PdfConfig;

class DocumentParserService
{
    public function parse(string $filePath, string $fileType): string
    {
        $fullPath = storage_path('app/' . $filePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("File not found: {$fullPath}");
        }

        return match (strtolower($fileType)) {
            'pdf'          => $this->parsePdf($fullPath),
            'txt', 'md'    => $this->parsePlainText($fullPath),
            'docx', 'doc'  => $this->parseWord($fullPath),
            'xlsx', 'xls'  => $this->parseExcel($fullPath),
            default        => throw new \Exception("Unsupported file type: {$fileType}"),
        };
    }

    // -- PDF --------------------------------------------------------------

    private function parsePdf(string $path): string
    {
        // Raise memory limit for large PDFs (Cambridge past papers can be 50MB+)
        $prevMemory = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $config = new PdfConfig();
            $config->setRetainImageContent(false); // skip images ? faster + less memory
            $config->setIgnoreEncryptionExceptions(true);

            $parser = new PdfParser([], $config);
            $pdf    = $parser->parseFile($path);
            $text   = $pdf->getText();

            // Normalize whitespace while preserving paragraph breaks
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n{3,}/', "\n\n", $text);

            return trim($text);

        } finally {
            ini_set('memory_limit', $prevMemory);
        }
    }

    // -- Plain text / Markdown --------------------------------------------

    private function parsePlainText(string $path): string
    {
        $content = file_get_contents($path);
        return mb_convert_encoding($content, 'UTF-8', 'auto');
    }

    // -- Word -------------------------------------------------------------

    private function parseWord(string $path): string
    {
        $phpWord = WordIOFactory::load($path);
        $text    = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $text .= $child->getText() . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        return trim($text);
    }

    // -- Excel -------------------------------------------------------------

    private function parseExcel(string $path): string
    {
        $spreadsheet = ExcelIOFactory::load($path);
        $text        = '';

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $text .= "Sheet: " . $sheet->getTitle() . "\n";

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $val = $cell->getValue();
                    if ($val !== null && $val !== '') {
                        $cells[] = $val;
                    }
                }
                if (!empty($cells)) {
                    $text .= implode(' | ', $cells) . "\n";
                }
            }

            $text .= "\n";
        }

        return trim($text);
    }
}