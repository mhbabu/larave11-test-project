<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .content {
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #333;
        }
        p {
            margin-bottom: 15px;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="content">
        {!! $htmlContent !!}
    </div>
</body>
</html>
