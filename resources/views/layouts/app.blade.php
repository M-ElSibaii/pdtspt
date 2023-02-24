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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js"></script>


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
        }

        #tblpdts td,
        #tblpdts th {
            border: 1px solid #ddd;
            padding: 8px;
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
    </style>
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

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>