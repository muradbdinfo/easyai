<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class FileExtractorService
{
    // Returns ['type', 'extracted_text', 'meta']
    public function extract(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match(true) {
            in_array($ext, ['jpg','jpeg','png','gif','webp']) => $this->handleImage($file),
            $ext === 'txt'                                    => $this->handleText($file),
            $ext === 'pdf'                                    => $this->handlePdf($file),
            in_array($ext, ['xls','xlsx'])                   => $this->handleExcel($file),
            default => throw new \InvalidArgumentException("Unsupported file type: $ext"),
        };
    }

    private function handleImage(UploadedFile $file): array
    {
        [$width, $height] = @getimagesize($file->getPathname()) ?: [null, null];
        return [
            'type'           => 'image',
            'extracted_text' => null,
            'meta'           => ['width' => $width, 'height' => $height],
        ];
    }

    private function handleText(UploadedFile $file): array
    {
        $content = file_get_contents($file->getPathname());
        // Truncate to 8000 chars to fit context
        $content = mb_substr($content, 0, 8000);
        return [
            'type'           => 'text',
            'extracted_text' => $content,
            'meta'           => ['chars' => mb_strlen($content)],
        ];
    }

    private function handlePdf(UploadedFile $file): array
    {
        try {
            $parser   = new PdfParser();
            $pdf      = $parser->parseFile($file->getPathname());
            $text     = $pdf->getText();
            $pages    = count($pdf->getPages());
            // Truncate to 8000 chars
            $text = mb_substr($text, 0, 8000);
            return [
                'type'           => 'pdf',
                'extracted_text' => $text,
                'meta'           => ['page_count' => $pages],
            ];
        } catch (\Exception $e) {
            return [
                'type'           => 'pdf',
                'extracted_text' => '[Could not extract PDF text: ' . $e->getMessage() . ']',
                'meta'           => ['error' => $e->getMessage()],
            ];
        }
    }

    private function handleExcel(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheets      = [];
            $allText     = '';
            $sheetNames  = [];

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $name       = $sheet->getTitle();
                $sheetNames[] = $name;
                $rows       = [];
                foreach ($sheet->toArray() as $row) {
                    $rows[] = implode(' | ', array_filter(array_map('trim', $row)));
                }
                $sheetText  = implode("\n", array_filter($rows));
                $allText   .= "Sheet: $name\n" . $sheetText . "\n\n";
            }

            // Truncate to 8000 chars
            $allText = mb_substr($allText, 0, 8000);
            return [
                'type'           => 'excel',
                'extracted_text' => $allText,
                'meta'           => [
                    'sheet_count' => count($sheetNames),
                    'sheet_names' => $sheetNames,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'type'           => 'excel',
                'extracted_text' => '[Could not extract Excel data: ' . $e->getMessage() . ']',
                'meta'           => ['error' => $e->getMessage()],
            ];
        }
    }
}