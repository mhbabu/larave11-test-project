<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Contents</title>
</head>
<body>
    <h1>Email Contents</h1>
    @foreach ($emailContents as $content)
        <h2>{{ $content['subject'] }}</h2>
        <div>{!! $content['body'] !!}</div>
        <hr />
    @endforeach
</body>
</html>
