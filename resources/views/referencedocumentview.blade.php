<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>Atributos do documento de referência</h1>
            <div class='flex flex-col'>
                <h1 class="py-6">{{$rd->rdName}}</h1>

                <table class="" id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                        <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$rd->GUID}}</td>
                    </tr>
                    <tr>
                        <th>Nome</th>
                        <td>{{$rd->rdName}}</td>
                    </tr>
                    <tr>
                        <th>Título</th>
                        <td>{{$rd->title}}</td>
                    </tr>
                    <tr>
                        <th>Descrição</th>
                        <td>{{$rd->description}}</td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td>{{$rd->status}}</td>
                    </tr>
                </table>
                <h3 class="py-6">Propriedades que utilizam este documento de referência:</h3>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: left!important;">Modelo de dados</th>
                        {{-- <th style="text-align: left!important;">Versão</th> --}}
                        <th style="text-align: left!important;">Propriedade</th>

                    </tr>

                    @foreach ($rdinprop as $proprd)

                    <tr>
                        <td>
                            <a href="{{ route('pdtsdownload', ['pdtID' => $proprd->Id]) }}">{{$proprd->pdtNamePt}} V{{$proprd->editionNumber}}.{{$proprd->versionNumber}}.{{$proprd->revisionNumber}}</a>

                            {{-- {{$proprd->pdtNamePt}} --}}
                        </td>
                        {{-- <td>{{$proprd->versionNumber}}.{{$proprd->revisionNumber}}</td> --}}
                        <td>
                            <a href="{{ url('datadictionaryview/' . $proprd->Id . '-' . $proprd->GUID) }}">{{ $proprd->namePt }}</a>
                            {{-- {{$proprd->namePt}} --}}
                        </td>

                    </tr>

                    @endforeach
                </table>
            </div>
        </div>
    </div>
</x-app-layout>