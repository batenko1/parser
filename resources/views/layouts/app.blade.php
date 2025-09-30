<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Parser Dashboard') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    <script src="https://cdn.tailwindcss.com"></script>

</head>
<body class="bg-gray-100 text-gray-900">

<!-- Навбар -->
<nav class="bg-indigo-600 text-white shadow-md">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <a href="{{ url('/') }}" class="text-lg font-semibold hover:text-gray-200">
            Parser Dashboard
        </a>
        <div class="flex space-x-4">
            <a href="{{ route('index') }}" class="hover:text-gray-200">Результаты</a>
        </div>
    </div>
</nav>

<!-- Контент -->
<main class="container mx-auto px-4 py-6">
    @yield('content')
</main>

<!-- Футер -->
<footer class="bg-gray-800 text-gray-300 mt-8">
    <div class="container mx-auto px-4 py-4 text-center text-sm">
        &copy; {{ date('Y') }} Parser Dashboard. Всі права захищено.
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
</body>
</html>
