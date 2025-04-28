<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Thread PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 30px;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .email-block {
            border-bottom: 1px solid #ccc;
            padding-bottom: 15px;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .email-header {
            margin-bottom: 10px;
        }
        .email-header strong {
            display: inline-block;
            width: 80px;
        }
        .email-body {
            margin-top: 10px;
            word-wrap: break-word;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <h2>Email Thread between {{ $sender }} and {{ $receiver }}</h2>

    @foreach ($messages as $message)
        <div class="email-block">
            <div class="email-header">
                <p><strong>From:</strong> {{ $message['from'] ?? 'N/A' }}</p>
                <p><strong>To:</strong> {{ $message['to'] ?? 'N/A' }}</p>
                <p><strong>Subject:</strong> {{ $message['subject'] ?? 'No Subject' }}</p>
                @if (!empty($message['date']))
                    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($message['date'])->format('F j, Y, g:i a') }}</p>
                @endif
            </div>
            <div class="email-body">
                {!! $message['body'] !!}
            </div>
        </div>
    @endforeach
</body>
</html>
