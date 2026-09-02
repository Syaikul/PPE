<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman tidak ditemukan</title>
    <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('images/favicon.png') }}?v=2">
    <link rel="stylesheet" href="{{ asset('template') }}/assets/css/public-sans.css">
    <link rel="stylesheet" href="{{ asset('css/error-404.css') }}?v=2">
</head>
<body>
    @php
        $backUrl = auth()->check() ? route('home') : route('login');
    @endphp

    <div class="e404">
        <main class="e404-main">
            <section class="e404-copy-wrap">
                <h1 class="e404-title">404</h1>
                <p class="e404-copy">Sorry, that page could not be found</p>
                <a class="e404-btn" href="{{ $backUrl }}">Back to home</a>
            </section>

            <div class="e404-art">
                <img
                    src="{{ asset('template/assets/img/Icon.png') }}"
                    alt="Maskot Mesitech sedang mencari halaman"
                    width="1312"
                    height="1199"
                >
            </div>
        </main>
    </div>
</body>
</html>
