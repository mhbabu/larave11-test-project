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
        $pdfPath        = storage_path('app/google/content.pdf');
        $htmlOutputPath = storage_path('app/google/content.html');

        // Only generate if it doesn't already exist
        if (!file_exists($htmlOutputPath)) {
            $command = "pdf2htmlEX \"$pdfPath\" \"$htmlOutputPath\"";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception("PDF to HTML conversion failed. Code: $returnCode");
            }
        }

        return $htmlOutputPath;
    }

}
