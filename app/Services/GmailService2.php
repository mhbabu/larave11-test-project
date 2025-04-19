<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class GmailService2
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

        if (Storage::exists('google/token.json')) {
            $this->client->setAccessToken(json_decode(Storage::get('google/token.json'), true));
        }

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                Storage::put('google/token.json', json_encode($this->client->getAccessToken()));
            } else {
                throw new \Exception('Authorization required. Visit: ' . $this->getAuthUrl());
            }
        }

        $this->service = new Google_Service_Gmail($this->client);
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
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

    public function extractEmailDetails($message): array
    {
        $payload = $message->getPayload();
        $headers = $payload->getHeaders();

        $subject = $from = $date = '';
        foreach ($headers as $header) {
            if ($header->getName() === 'Subject') $subject = $header->getValue();
            if ($header->getName() === 'From') $from = $header->getValue();
            if ($header->getName() === 'Date') $date = $header->getValue();
        }

        $body = $this->extractBody($payload);

        return [
            'subject' => $subject,
            'from'    => $from,
            'date'    => $date,
            'body'    => $body,
        ];
    }

    private function extractBody($payload): string
    {
        $bodyData = null;

        if ($payload->getBody() && $payload->getBody()->getData()) {
            $bodyData = $payload->getBody()->getData();
        } else {
            foreach ((array) $payload->getParts() as $part) {
                if (in_array($part->getMimeType(), ['text/plain', 'text/html'])) {
                    $bodyData = $part->getBody()->getData();
                    break;
                }
            }
        }

        return $bodyData ? base64_decode(strtr($bodyData, '-_', '+/')) : '';
    }

    /**
     * Convert PDF content to text
     */
    public function getTextFromPdf(): string
    {
        $pdfPath = storage_path('app/content.pdf');

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF not found at: $pdfPath");
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);

        return nl2br(e($pdf->getText()));
    }

    /**
     * Send the same email (PDF content) 25 times
     */
    public function send25HtmlEmails(string $from, string $to): void
    {
        $html = $this->getTextFromPdf();

        for ($i = 1; $i <= 25; $i++) {
            $subject = "PDF Content Email #$i";
            $message = new Google_Service_Gmail_Message();
            $message->setRaw($this->createRawHtmlEmail($from, $to, $subject, $html));
            $this->service->users_messages->send('me', $message);

            usleep(300000); // 0.3s delay to avoid quota issues
        }
    }

    /**
     * Create raw Gmail HTML message
     */
    private function createRawHtmlEmail(string $from, string $to, string $subject, string $html): string
    {
        $headers = "From: <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";

        $emailMessage = $headers . $html;
        return rtrim(strtr(base64_encode($emailMessage), '+/', '-_'), '=');
    }
}
