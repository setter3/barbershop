@props(['title' => null, 'bodyClass' => ''])

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>66783582</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="{{ $bodyClass }}">
        {{ $slot }}
    </body>
</html>
