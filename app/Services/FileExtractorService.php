<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileExtractorService
{
    /**
     * Extract type + text + meta from an uploaded file.
     *
     * Returns:
     *   [
     *     'type'           => 'image' | 'text' | 'pdf' | 'excel',
     *     'extracted_text' => string|null,
     *     'meta'           => array,
     *   ]
     */
    public function extract(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => $this->handleImage($file),
            $ext === 'txt'                                          => $this->handleText($file),
            $ext === 'pdf'                                          => $this->handlePdf($file),
            in_array($ext, ['xls', 'xlsx'])                        => $this->handleExcel($file),
            default => throw new \InvalidArgumentException("Unsupported file type: .{$ext}"),
        };
    }

    // ── Handlers ──────────────────────────────────────────────────

    private function handleImage(UploadedFile $file): array
    {
        [$width, $height] = @getimagesize($file->getPathname()) ?: [null, null];

        return [
            'type'           => 'image',
            'extracted_text' => null,
            'meta'           => [
                'width'  => $width,
                'height' => $height,
            ],
        ];
    }

    private function handleText(UploadedFile $file): array
    {
        $content = file_get_contents($file->getPathname());
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        $content = mb_substr($content, 0, 8000); // cap at 8 000 chars

        return [
            'type'           => 'text',
            'extracted_text' => $content,
            'meta'           => ['chars' => mb_strlen($content)],
        ];
    }

    private function handlePdf(UploadedFile $file): array
    {
        // Requires: composer require smalot/pdfparser
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($file->getPathname());
            $text   = $pdf->getText();
            $pages  = count($pdf->getPages());
            $text   = mb_substr($text, 0, 8000);

            return [
                'type'           => 'pdf',
                'extracted_text' => $text,
                'meta'           => ['page_count' => $pages],
            ];
        } catch (\Throwable $e) {
            return [
                'type'           => 'pdf',
                'extracted_text' => '[PDF text extraction failed: ' . $e->getMessage() . ']',
                'meta'           => ['error' => $e->getMessage()],
            ];
        }
    }

    private function handleExcel(UploadedFile $file): array
    {
        // Requires: composer require phpoffice/phpspreadsheet
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheetNames  = [];
            $allText     = '';

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $name         = $sheet->getTitle();
                $sheetNames[] = $name;
                $rows         = [];

                foreach ($sheet->toArray() as $row) {
                    $cleaned = array_filter(array_map('trim', $row), fn ($v) => $v !== '');
                    if (!empty($cleaned)) {
                        $rows[] = implode(' | ', $cleaned);
                    }
                }

                $allText .= "Sheet: {$name}\n" . implode("\n", $rows) . "\n\n";
            }

            $allText = mb_substr($allText, 0, 8000);

            return [
                'type'           => 'excel',
                'extracted_text' => $allText,
                'meta'           => [
                    'sheet_count' => count($sheetNames),
                    'sheet_names' => $sheetNames,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'type'           => 'excel',
                'extracted_text' => '[Excel extraction failed: ' . $e->getMessage() . ']',
                'meta'           => ['error' => $e->getMessage()],
            ];
        }
    }
}