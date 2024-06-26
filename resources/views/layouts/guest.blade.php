<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('PDTs', 'PDTs') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">

</head>
<style>
    .image-container {
        display: flex;
        justify-content: left;
        align-items: center;

    }

    a {
        background-color: transparent
    }

    a {
        color: inherit;
        text-decoration: inherit
    }

    .carousel-item img {
        object-fit: cover;
        /* ensure the image fills the container without changing aspect ratio */
        height: 100%;
        /* set the height of the image to 100% to ensure it fills the container */
        width: 100%;
        /* set the width of the image to 100% to ensure it fills the container */
    }

    .col-lg-4 {
        text-align: center;
        /* center the text and images inside the columns */
    }

    .col-lg-4 svg rect {
        fill: #333;
        /* make the background color of the placeholder image darker */
    }

    .col-lg-4 .btn-secondary {
        background-color: lightslategray;
        /* make the background color of the button darker */
    }
</style>

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">

        <p margin-top=100px> </p>
        <div class="card-header">

            <a href="{{ route('home') }}">
                <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
            </a>
            <div class="fixed top-0 right-0 px-6 py-4 sm:block">
                @if (Route::has('login'))

                @auth
                <a href="{{ url('/dashboard') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">PDTs</a>
                @else
                <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">Log in</a>

                @if (Route::has('register'))
                <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 dark:text-gray-500 underline">Register</a>
                @endif
                @endauth
                <a href="{{ route('home') }}" class="ml-4 text-sm text-gray-700 dark:text-gray-500 underline">Home</a>
                <a href="{{ route('contact.store') }}" class="ml-4 text-sm text-gray-700 dark:text-gray-500 underline">Contact-nos</a>

                @endif
            </div>
        </div>
        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>