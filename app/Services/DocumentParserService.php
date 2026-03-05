<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

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
            'txt', 'md'    => file_get_contents($fullPath),
            'docx', 'doc'  => $this->parseWord($fullPath),
            'xlsx', 'xls'  => $this->parseExcel($fullPath),
            default        => throw new \Exception("Unsupported file type: {$fileType}"),
        };
    }

    private function parsePdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($path);
        return $pdf->getText();
    }

    private function parseWord(string $path): string
    {
        $phpWord  = WordIOFactory::load($path);
        $text     = '';

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