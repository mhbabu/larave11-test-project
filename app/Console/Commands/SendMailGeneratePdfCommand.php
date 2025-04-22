<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
use Mpdf\Mpdf;
use Exception;
use Illuminate\Support\Facades\DB;

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
        // NOW sending FROM write2babu --> TO mahadi
        $from              = "write2babu.cse@gmail.com";
        $to                = "mahadihassan.cse@gmail.com";
        $senderTokenFile   = DB::table('gmail_tokens')->where('gmail_address', $from)->value('token_file_path') ?? null;
        $receiverTokenFile = DB::table('gmail_tokens')->where('gmail_address', $to)->value('token_file_path') ?? null;


        try {
            $gmailService = new GmailService($senderTokenFile);

            $this->info('Sending emails...');
            $gmailService->send25HtmlEmails($from, $to);
            $this->info('All 25 emails sent successfully!');

            // Read from MAHADI's inbox using a second GmailService
            $recipientGmail = new GmailService('google/token-mahadi.json');
            $messages = $recipientGmail->listInboxMessages();

            // if (empty($messages)) {
            //     $this->error('No inbox messages found.');
            //     return;
            // }

            // $allMessages = [];

            // foreach ($messages as $messageItem) {
            //     $message = $recipientGmail->getMessage($messageItem->getId());
            //     $allMessages[] = $recipientGmail->extractEmailDetails($message);
            // }

            // $html    = view('pdf.gmail-inbox', ['messages' => $allMessages])->render();
            // $pdfPath = storage_path('app/gmail-inbox.pdf');

            // // Correct mPDF usage
            // $mpdf = new \Mpdf\Mpdf();
            // $mpdf->WriteHTML($html);
            // $mpdf->Output($pdfPath, 'F');

            // $this->info("Inbox PDF saved at: {$pdfPath}");

        } catch (Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
        }
    }

}
