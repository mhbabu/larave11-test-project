<!DOCTYPE html>
<html>
<head>
    <title>Gmail Inbox Export</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .message { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .header { font-weight: bold; margin-bottom: 5px; }
        .body { white-space: pre-wrap; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Gmail Inbox Messages</h1>

    @foreach ($messages as $message)
    <div style="margin-bottom: 20px;">
        <strong>From:</strong> {{ strip_tags($message['from']) }}<br>
        <strong>Subject:</strong> {{ strip_tags($message['subject']) }}<br>
        <strong>Body:</strong><br>
        <pre style="white-space: pre-wrap;">{{ strip_tags($message['body']) }}</pre>
    </div>
    @endforeach

</body>
</html>
