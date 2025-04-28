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

    public function handle2()
    {
        // NOW sending FROM write2babu --> TO mahadi
        $from              = "write2babu.cse@gmail.com";
        $to                = "mahadihassan.cse@gmail.com";

        try {
            $gmailService = new GmailService();

            $this->info('Sending emails...');
            $gmailService->send25HtmlEmails($from, $to);
            $this->info('All 25 emails sent successfully!');

            // Read from MAHADI's inbox using a second GmailService
            $messages = $gmailService->listInboxMessages();

            if (empty($messages)) {
                $this->error('No inbox messages found.');
                return;
            }

            $allMessages = [];

            foreach ($messages as $messageItem) {
                $message = $recipientGmail->getMessage($messageItem->getId());
                $allMessages[] = $recipientGmail->extractEmailDetails($message);
            }

            $html    = view('pdf.gmail-inbox', ['messages' => $allMessages])->render();
            $pdfPath = storage_path('app/gmail-inbox.pdf');

            // Correct mPDF usage
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output($pdfPath, 'F');

            $this->info("Inbox PDF saved at: {$pdfPath}");

        } catch (Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
        }
    }
    public function handle()
    {
        $from = "write2babu.cse@gmail.com";
        $to   = "mahadihassan.cse@gmail.com";

        try {
            $gmailService = new GmailService();

            // 1. Send 25 emails
            // $this->info('Sending emails...');
            // $gmailService->send25HtmlEmails($from, $to);
            // $this->info('All 25 emails sent successfully!');

            // 2. Get messages sent by the sender
            $this->info('Fetching sent messages...');
            $sentMessages = $gmailService->listSentMessagesWithSubject('Sample Email');

            if (empty($sentMessages)) {
                $this->error('No sent messages found.');
                return;
            }

            // 3. Get thread ID from the first sent email
            $threadId = $sentMessages[0]->getThreadId();

            // 4. Get full thread (all 25 emails back & forth)
            $this->info("Fetching full thread...");
            $threadMessages = $gmailService->getThreadMessages($threadId);

            $allMessages = [];
            foreach ($threadMessages as $msg) {
                $allMessages[] = $gmailService->extractEmailDetails($msg);
            }

            // dd($allMessages);
            // 5. Render view and generate PDF
            $html = view('pdf.gmail-thread', ['messages' => $allMessages, 'sender' => $from, 'receiver' => $to])->render();
            $pdfPath = storage_path('app/google/gmail-thread.pdf');

            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output($pdfPath, 'F');

            $this->info("PDF generated successfully at: $pdfPath");

        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }


}
