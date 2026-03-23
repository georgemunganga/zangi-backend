<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Zangi Admin</title>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/admin/main.jsx'])
    </head>
    <body class="bg-stone-100 text-slate-900 antialiased">
        <div id="admin-root"></div>
    </body>
</html>
