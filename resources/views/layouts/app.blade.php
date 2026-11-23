<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', config('registration.form_title')) | המכללה למינהל</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ $assetBase }}assets/css/app.css?v={{ @filemtime(public_path('assets/css/app.css')) }}">

  {{-- הסכמת עוגיות (CookieScript) --}}
  <script type="text/javascript" charset="UTF-8" src="//cdn.cookie-script.com/s/1605783ea492ba4625599329042ef31e.js"></script>

  {{-- ווידג'ט נגישות (accessiBe) --}}
  <script>
    (function () {
      var s = document.createElement('script');
      var h = document.querySelector('head') || document.body;
      s.src = 'https://acsbapp.com/apps/app/dist/js/app.js';
      s.async = true;
      s.onload = function () { acsbJS.init(); };
      h.appendChild(s);
    })();
  </script>
</head>
<body>
  @yield('body')
</body>
</html>
