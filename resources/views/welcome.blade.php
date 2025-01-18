<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>G&F - Sistema Optica</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/js/login.js'])
</head>

<body class="custom-body d-flex flex-column min-vh-100">
    @if (request()->is('login'))
        <div id="login" class="flex-grow-1"></div>
    @else
        <div id="app" class="flex-grow-1"></div>
    @endif
    @vite('resources/js/main.js')
</body>

</html>