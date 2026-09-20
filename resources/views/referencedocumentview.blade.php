<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>{{$rd->rdName}}</h1>
            <div class='flex flex-col'>
                <h3 class="py-2">{{ Lg::t('Atributos do documento de referência:') }}</h3>

                <table class="" id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                        <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$rd->GUID}}</td>
                    </tr>
                    <x-uri-row entity="doc" :record="$rd" />
                    <tr>
                        <th>{{ Lg::t('Nome') }}</th>
                        <td>{{$rd->rdName}}</td>
                    </tr>
                    <tr>
                        <th>{{ Lg::t('Título') }}</th>
                        <td>{{$rd->title}}</td>
                    </tr>
                    <tr>
                        <th>{{ Lg::t('Descrição') }}</th>
                        <td>{{$rd->description}}</td>
                    </tr>
                    <tr>
                        <th>{{ Lg::t('Estado') }}</th>
                        <td>{{$rd->status}}</td>
                    </tr>
                </table>
                <h3 class="py-2">{{ Lg::t('Propriedades que utilizam este documento de referência:') }}</h3>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: left!important;">{{ Lg::t('Modelo de dados') }}</th>
                        <th style="text-align: left!important;">{{ Lg::t('Propriedade') }}</th>
                    </tr>

                    @foreach ($rdinprop as $proprd)
                    <tr>
                        <td>
                            @if ($proprd->pdtId)
                            <a href="{{ route('pdtsdownload', ['pdtID' => $proprd->pdtId]) }}">{{ Lg::f($proprd, 'pdtName') }} V{{ $proprd->versionNumber }}.{{ $proprd->revisionNumber }}</a>
                            @endif
                        </td>
                        <td>
                            @if ($proprd->propertyId)
                            <a href="{{ Uri::buildLink('classprop', ['Id' => $proprd->classPropertyId, 'namePt' => $proprd->namePt]) }}">{{ Lg::f($proprd, 'name') }}</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
