<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 | Доступ заборонено</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <div class="min-h-screen flex items-center justify-center px-6">
        <div class="w-full max-w-xl bg-white border border-gray-200 rounded-xl shadow-sm p-8 text-center">
            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-red-600">403</div>
            <h1 class="mt-3 text-2xl font-semibold text-gray-900">Доступ заборонено</h1>
            <p class="mt-4 text-base text-gray-700">
                {{ $message ?? 'У вас немає прав доступу до цієї сторінки.' }}
            </p>
            <div class="mt-6">
                <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    Повернутись назад
                </a>
            </div>
        </div>
    </div>
</body>
</html>
