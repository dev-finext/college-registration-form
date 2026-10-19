<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', config('registration.form_title')) | המכללה למינהל</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ $assetBase }}assets/css/app.css?v={{ @filemtime(public_path('assets/css/app.css')) }}">
</head>
<body>
  @yield('body')
</body>
</html>
