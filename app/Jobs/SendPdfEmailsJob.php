<?php

namespace App\Jobs;

use App\Services\GmailService;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPdfEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function handle(GmailService $gmailService, PdfService $pdfService)
    {
        // Send 25 emails with the content from PDF
        $gmailService->send25HtmlEmails($this->from, $this->to);

        // After sending emails, generate and store the PDF with email content
        $pdfService->generateAndStorePdf($this->to);
    }
}
