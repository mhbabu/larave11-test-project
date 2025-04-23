<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Services\GmailService;
use Illuminate\Support\Facades\Storage;
use Google_Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GmailController extends Controller
{
    public function redirectToGoogle()
    {
        $gmailService = new GmailService();
        return redirect()->to($gmailService->getAuthUrl());
    }

    public function handleOAuth2Callback(Request $request) {
        try {
            $client = new Google_Client();
            $client->setAuthConfig(storage_path('app/google/credentials.json'));
            $client->setRedirectUri(config('app.url') . '/oauth2callback');
            $client->setAccessType('offline');
            $client->setScopes([
                \Google_Service_Gmail::GMAIL_SEND,
                \Google_Service_Gmail::GMAIL_READONLY,
                \Google_Service_Gmail::GMAIL_MODIFY
            ]);
    
            // Fetch the token using the code from the request
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
            
            Storage::put('google/token.json', json_encode($token)); // store token
    
            return response()->json([
                'status'  => 'success',
                'message' => 'Authorization successful. You can now run the Artisan command.'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during the authorization process.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function sendEmails()
    {
        // trigger the command to send 25 emails and generate the PDF
        Artisan::call('sendmail-generate-pdf');
        return 'Emails sent successfully and PDF generated!';
    }
}
