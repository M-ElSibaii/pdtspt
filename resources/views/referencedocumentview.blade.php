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
                <h3 class="py-6">Propriedades que utilizam este documento de referência:</h3>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: left!important;">Modelo de dados</th>
                        {{-- <th style="text-align: left!important;">Versão</th> --}}
                        <th style="text-align: left!important;">Propriedade</th>
                        <th style="text-align: left!important;">GUID de propriedade</th>
                    </tr>

                    @foreach ($rdinprop as $proprd)

                    <tr>
                        <td>
                            <form class="mb-3" action="{{ route('pdtsdownload', ['pdtID' => $proprd->pdtID]) }}">
                                <x-button-primary-pdts 
                                    type="submit"
                                    title="{{$proprd->pdtNamePt}} V{{$proprd->versionNumber}}.{{$proprd->revisionNumber}}"/>
                            </form>
                            <a href="{{ route('pdtsdownload', ['propID' => $proprd->pdtID]) }}">{{$proprd->pdtNamePt}} V{{$proprd->versionNumber}}.{{$proprd->revisionNumber}}</a>

                            {{-- {{$proprd->pdtNamePt}} --}}
                        </td>
                        {{-- <td>{{$proprd->versionNumber}}.{{$proprd->revisionNumber}}</td> --}}
                        <td>
                            <a href="{{ route('datadictionaryview', ['propID' => $proprd->GUID , 'propV' => $proprd->versionNumber, 'propR' => $proprd->revisionNumber]) }}">{{ $proprd->namePt }}</a>
                            {{-- {{$proprd->namePt}} --}}
                        </td>
                        <td>{{$proprd->GUID}}</td>
                    </tr>

                    @endforeach
                </table>
            </div>
        </div>
    </div>
</x-app-layout>