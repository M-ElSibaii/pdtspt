<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('PDTs', 'PDTs') }}</title>

    <!-- links for card styles -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://unpkg.com/@coreui/coreui@3.2/dist/css/coreui.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet" />
    <link href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css" rel="stylesheet" />
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet" />
    {{-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"> --}}
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js"></script>


    <link rel="canonical" href="https://getbootstrap.com/docs/5.3/examples/carousel/">
    <link href="/docs/5.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <!-- Favicons -->
    <link rel="apple-touch-icon" href="/docs/5.3/assets/img/favicons/apple-touch-icon.png" sizes="180x180">
    <link rel="icon" href="/docs/5.3/assets/img/favicons/favicon-32x32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="/docs/5.3/assets/img/favicons/favicon-16x16.png" sizes="16x16" type="image/png">
    <link rel="manifest" href="/docs/5.3/assets/img/favicons/manifest.json">
    <link rel="mask-icon" href="/docs/5.3/assets/img/favicons/safari-pinned-tab.svg" color="#712cf9">
    <link rel="icon" href="/docs/5.3/assets/img/favicons/favicon.ico">
    <meta name="theme-color" content="#712cf9">
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Scripts and styles for pdtdownlad tables -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js">
    </script>

    <script>
        function scrolldown() {
            const element = document.getElementById("content");
            element.scrollIntoView();
        }
    </script>

    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        .b-example-divider {
            height: 3rem;
            background-color: rgba(0, 0, 0, .1);
            border: solid rgba(0, 0, 0, .15);
            border-width: 1px 0;
            box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
        }

        .b-example-vr {
            flex-shrink: 0;
            width: 1.5rem;
            height: 100vh;
        }

        .bi {
            vertical-align: -.125em;
            fill: currentColor;
        }

        .nav-scroller {
            position: relative;
            z-index: 2;
            height: 2.75rem;
            overflow-y: hidden;
        }

        .nav-scroller .nav {
            display: flex;
            flex-wrap: nowrap;
            padding-bottom: 1rem;
            margin-top: -1px;
            overflow-x: auto;
            text-align: center;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .image-container {
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
        }

        .image-container img {
            margin: 0 30px;
            /* optional: add some spacing between the images */
            max-width: 100%;
            height: auto;
        }

        .property-image {

            width: auto;
            height: 300px;

        }

        body {

            font-family: 'Source Sans Pro', sans-serif;


        }


        h1 {
            padding-bottom: 0;
            margin-bottom: 0;
        }

        h3 {
            margin-top: 0;
            font-weight: 300;
        }

        .div-comment {

            background-color: whitesmoke;
            border-radius: 5px;
            border: 1px solid black;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .div-username {
            margin-top: 10px;
        }

        .container {
            max-width: 40em;
            margin: 10px auto;
        }

        .ac-label {
            font-weight: 700;
            position: relative;
            padding: .5em 1em;
            margin-bottom: .5em;
            display: block;
            cursor: pointer;
            background-color: whiteSmoke;
            transition: background-color .15s ease-in-out;
        }

        .ac-input:checked+label,
        .ac-label:hover {
            background-color: #999;
        }

        .ac-label:after,
        .ac-input:checked+.ac-label:after {
            content: "+";
            position: absolute;
            display: block;
            right: 0;
            top: 0;
            width: 2em;
            height: 100%;
            line-height: 2.25em;
            text-align: center;
            background-color: #e5e5e5;
            transition: background-color .15s ease-in-out;
        }

        .ac-label:hover:after,
        .ac-input:checked+.ac-label:after {
            background-color: #b5b5b5;
        }

        .ac-input:checked+.ac-label:after {
            content: "-";
        }

        .ac-input {
            display: none;
        }

        .ac-text,
        .ac-sub-text {
            opacity: 0;
            height: 0;
            margin-bottom: .5em;
            transition: opacity .5s ease-in-out;
            overflow: hidden;
        }

        a:link {
            color: black;
            background-color: transparent;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background-color: gray !important;
        }

        .ac-input:checked~.ac-text,
        .ac-sub .ac-input:checked~.ac-sub-text {
            opacity: 1;
            height: auto;
        }

        .ac-sub .ac-label {
            background: none;
            font-weight: 800;
            padding: .2em 2em;
            margin-bottom: 0;
        }

        .ac-sub .ac-label:checked {
            background: none;
            border-bottom: 1px solid whitesmoke;
        }

        .ac-sub .ac-label:after,
        .ac-sub .ac-input:checked+.ac-label:after {
            left: 0;
            background: none;
        }

        .ac-sub .ac-input:checked+label,
        .ac-sub .ac-label:hover {
            background: none;
        }

        .ac-sub-text {
            padding: 0 1em 0 2em;
        }

        #tblpdts {
            font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        button.btn-link {
            max-width: 100%;
            word-wrap: break-word;
        }

        #tblpdts td,
        #tblpdts th {
            border: 1px solid #ddd;
            padding: 8px;
            word-wrap: break-word;
        }

        #tblpdts tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        #tblpdts tr:hover {
            background-color: #ddd;
        }

        #tblpdts th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: center;
            background-color: #242424;
            ;
            color: white;
        }

        #tblprop {
            font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        #tblprop td,
        #tblprop th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        #tblprop th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: center;
            background-color: #242424;
            ;
            color: white;
        }

        .carousel-item img {
            object-fit: cover;
            /* ensure the image fills the container without changing aspect ratio */
            height: 100%;
            /* set the height of the image to 100% to ensure it fills the container */
            width: 100%;
            /* set the width of the image to 100% to ensure it fills the container */
        }

        .col-lg-4 .btn-secondary {
            background-color: black;
            /* make the background color of the button darker */
        }

        .col-lg-4 {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
    </style>
    <link href="carousel.css" rel="stylesheet">
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endif
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <script src="/docs/5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>