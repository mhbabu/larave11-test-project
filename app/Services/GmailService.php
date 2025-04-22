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

    public function __construct($tokenFile = 'google/token.json')
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->setAccessType('offline');
        $this->client->setScopes([
            Google_Service_Gmail::GMAIL_SEND,
            Google_Service_Gmail::GMAIL_READONLY,
        ]);
        $this->client->setRedirectUri(config('app.url') . '/oauth2callback');

        if (Storage::exists($tokenFile)) {
            $this->client->setAccessToken(json_decode(Storage::get($tokenFile), true));
        }

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                Storage::put($tokenFile, json_encode($this->client->getAccessToken()));
            } else {
                $authUrl = $this->client->createAuthUrl();
                throw new \Exception("Authorization required. Visit this URL to authorize: $authUrl");
            }
        }

        $this->service = new Google_Service_Gmail($this->client);
    }

    public function send25HtmlEmails(string $from, string $to)
    {
        $subject = 'Sample Email';

        $pdfService = new PdfService();
        $pdfHtml    = $pdfService->convertPdfToHtml();

        dd($pdfHtml);
        $htmlContent = file_get_contents($pdfHtml);


        if (!$pdfHtml) {
            return response()->json(['error' => 'Failed to convert PDF to HTML.'], 500);
        }

        for ($i = 1; $i <= 2; $i++) {
            $numberedSubject = $subject . " #$i";
            $this->sendHtmlEmail($from, $to, $numberedSubject, $htmlContent);
        }
    }


    public function sendHtmlEmail(string $from, string $to, string $subject, string $htmlContent)
    {
        // Render the HTML content using the Blade template
        $html = view('email.template', ['htmlContent' => $htmlContent, 'subject' => $subject])->render();

        $boundary = uniqid("boundary_");

        // Create the raw email message
        $rawMessage = "To: <$to>\r\n";
        $rawMessage .= "From: <$from>\r\n";
        $rawMessage .= "Subject: $subject\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

        $rawMessage .= "--$boundary\r\n";
        $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $rawMessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $rawMessage .= $html . "\r\n";
        $rawMessage .= "--$boundary--";

        // Base64 encode the raw message for Gmail API
        $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        // Create the Gmail message object
        $gmailMessage = new \Google_Service_Gmail_Message();
        $gmailMessage->setRaw($encodedMessage);

        // Send the email via Gmail API
        $this->service->users_messages->send('me', $gmailMessage);
    }



    public function listInboxMessages($maxPerPage = 100): array
    {
        $allMessages = [];
        $pageToken = null;

        do {
            $params = ['maxResults' => $maxPerPage, 'labelIds' => ['INBOX']];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $messagesList = $this->service->users_messages->listUsersMessages('me', $params);
            if ($messagesList->getMessages()) {
                $allMessages = array_merge($allMessages, $messagesList->getMessages());
            }

            $pageToken = $messagesList->getNextPageToken();
        } while ($pageToken);

        return $allMessages;
    }

    public function getMessage($messageId)
    {
        return $this->service->users_messages->get('me', $messageId);
    }

    public function extractEmailDetails($message)
    {
        $payload = $message->getPayload();
        $headers = collect($payload->getHeaders());

        $subject = optional($headers->firstWhere('name', 'Subject'))->value ?? '(No Subject)';
        $from    = optional($headers->firstWhere('name', 'From'))->value ?? '(Unknown Sender)';
        $body    = $this->getBody($payload);

        return [
            'from'    => strip_tags($from),
            'subject' => strip_tags($subject),
            'body'    => strip_tags($body),
        ];
    }

    // ✅ Added this method
    public function getBody($payload)
    {
        $body = '';

        if ($payload->getBody() && $payload->getBody()->getData()) {
            $body = $payload->getBody()->getData();
        } elseif ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                $mimeType = $part->getMimeType();
                if ($mimeType === 'text/plain' || $mimeType === 'text/html') {
                    $body = $part->getBody()->getData();
                    break;
                }
            }
        }

        // Decode Gmail's base64url format
        $body = strtr($body, '-_', '+/');
        return base64_decode($body);
    }
}
