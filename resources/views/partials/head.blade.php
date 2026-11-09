<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#0e7490" />

<title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

@isset($description)
    {{-- Public pages: shown by search engines and in link previews (WhatsApp, etc.). --}}
    <meta name="description" content="{{ $description }}" />
    <meta property="og:site_name" content="{{ config('app.name') }}" />
    <meta property="og:title" content="{{ $title ?? config('app.name') }}" />
    <meta property="og:description" content="{{ $description }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    @isset($ogImage)
        <meta property="og:image" content="{{ $ogImage }}" />
    @endisset
@endisset

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
