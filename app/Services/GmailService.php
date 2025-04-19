<?php
namespace App\Services;

use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Support\Facades\Storage;
use Google_Client;

class GmailService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->setAccessType('offline');
        $this->client->setScopes([
            Google_Service_Gmail::GMAIL_SEND,
            Google_Service_Gmail::GMAIL_READONLY,
        ]);
        $this->client->setRedirectUri(config('app.url') . '/oauth2callback');

        // Load token from storage if available
        if (Storage::exists('google/token.json')) {
            $this->client->setAccessToken(json_decode(Storage::get('google/token.json'), true));
        }

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                Storage::put('google/token.json', json_encode($this->client->getAccessToken()));
            } else {
                // Authorization required, you can implement the authorization URL here
                $authUrl = $this->client->createAuthUrl();
                throw new \Exception("Authorization required. Visit this URL to authorize: $authUrl");
            }
        }
        

        $this->service = new Google_Service_Gmail($this->client);
    }

    public function send25HtmlEmails(string $from, string $to)
    {
        $subject = 'Sample Email';
        
        // Extract text from the PDF file
        $pdfService = new PdfService();
        $pdfText = $pdfService->extractText(storage_path('app/google/content.pdf'));

        // HTML content with the extracted PDF text
        $html = '
        <html>
            <body>
                <h1>Hello, this is a test email</h1>
                <p>' . nl2br($pdfText) . '</p>
                <p>Thank you!</p>
            </body>
        </html>';

        // Send the emails
        for ($i = 1; $i <= 25; $i++) {
            $numberedSubject = $subject . " #$i";
            $this->sendHtmlEmail($from, $to, $numberedSubject, $html);
        }
    }

    public function sendHtmlEmail(string $from, string $to, string $subject, string $html)
    {
        $boundary = uniqid("boundary_");

        // Construct full raw email with headers + body
        $rawMessage = "To: <$to>\r\n";
        $rawMessage .= "From: <$from>\r\n";
        $rawMessage .= "Subject: $subject\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

        // Message body
        $rawMessage .= "--$boundary\r\n";
        $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $rawMessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $rawMessage .= $html . "\r\n";
        $rawMessage .= "--$boundary--";

        // Encode and send
        $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $gmailMessage = new \Google_Service_Gmail_Message();
        $gmailMessage->setRaw($encodedMessage);

        $this->service->users_messages->send('me', $gmailMessage);
    }


}
