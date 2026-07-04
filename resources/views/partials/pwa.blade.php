@php($pwaBasePath = rtrim(request()->getBaseUrl() ?: '', '/'))
<link rel="manifest" href="{{ $pwaBasePath }}/manifest.webmanifest?v=2">
<meta name="service-worker-url" content="{{ $pwaBasePath }}/service-worker.js">
<meta name="theme-color" content="#FCEEDF">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="ForsagePrint">
<link rel="apple-touch-icon" href="{{ $pwaBasePath }}/icons/pwa-192.webp">
