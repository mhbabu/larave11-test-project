<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
use Exception;

class SendMailGeneratePdfCommand extends Command
{
    protected $signature = 'sendmail-generate-pdf';
    protected $description = 'Send 25 emails with the PDF and store email content in a PDF';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $from = "mahadihassan.cse@gmail.com";
        $to   = "write2babu.cse@gmail.com";

        if (!$from || !$to) {
            $this->error('Sender or recipient email is not set in the .env file.');
            return;
        }

        try {
            $gmailService = new GmailService();

            $this->info('Sending emails...');

            $gmailService->send25HtmlEmails($from, $to);

            $this->info('All 25 emails sent successfully!');

        } catch (Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
        }
    }
}
