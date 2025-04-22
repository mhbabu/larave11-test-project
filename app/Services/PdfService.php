<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Smalot\PdfParser\Parser;

class PdfService
{
    public function convertPdfToHtml()
    {
        $directory      = storage_path('app/google');
        $pdfFile        = 'content.pdf';
        $htmlFile       = 'content.html';
        $htmlOutputPath = $directory . '/' . $htmlFile;

        // Only generate if it doesn't already exist
        if (!file_exists($htmlOutputPath)) {
            // Go to the correct directory first
            chdir($directory);

            // Now run the same command you'd use in terminal
            $command = "pdf2htmlEX $pdfFile $htmlFile";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception("PDF to HTML conversion failed. Code: $returnCode\nOutput:\n" . implode("\n", $output));
            }
        }

        return $htmlOutputPath;
    }

}
