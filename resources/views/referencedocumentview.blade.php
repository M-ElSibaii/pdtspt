<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Atributos do documento de referência') }}
        </h2>
    </x-slot>

    <body>
        <main class="flex-shrink-0">
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <div class='home_content container'>
                            <div class='row'>
                                <h3><strong>{{$rd->rdName}}</strong></h3>
                            </div>
                            <table id='tblprop' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <th>GUID</th>
                                    <td>{{$rd->GUID}}</td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>{{$rd->rdName}}</td>
                                </tr>
                                <tr>
                                    <th>Title</th>
                                    <td>{{$rd->title}}</td>
                                <tr>
                                    <th>Description</th>
                                    <td>{{$rd->description}}</td>
                                <tr>
                                    <th>Status</th>
                                    <td>{{$rd->status}}</td>
                                <tr>
                            </table>
                            <div class='row'>
                                <h4><strong>Propriedades que utilizam este documento de referência:</strong></h4>
                            </div>
                            <table id='tblprop' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <th>Modelo de dados</th>
                                    <th>Versão</th>
                                    <th>Propriedade</th>
                                    <th>GUID de propriedade</th>
                                </tr>

                                @foreach ($rdinprop as $proprd)

                                <tr>
                                    <td>{{$proprd->pdtNamePt}}</td>
                                    <td>{{$proprd->versionNumber}}.{{$proprd->revisionNumber}}</td>
                                    <td>{{$proprd->namePt}}</td>
                                    <td>{{$proprd->GUID}}</td>
                                </tr>

                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</x-app-layout>