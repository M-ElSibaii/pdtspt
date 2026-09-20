<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="flex-none inline">
                <h1 class="flex-none inline">{{ Lg::f($object, 'constructionObjectName') }}</h1>
                <p class="flex-none inline"> - <x-version-badge :version="$object->versionNumber" :revision="$object->revisionNumber" /></p>
                <x-status-badge :status="$object->status" />
            </div>
            <div class="py-2">
                <h3 class="py-2">{{ Lg::pick('Atributos do objeto de construção (ObjectType) baseado em EN ISO 23387', 'Construction object (ObjectType) attributes, per EN ISO 23387') }}</h3>
                <table id="tblprop" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{ $object->GUID }}</td>
                        </tr>
                        <x-uri-row entity="class" :record="$object" />
                        <tr><th>{{ Lg::t('Nome') }}</th><td>{{ Lg::f($object, 'constructionObjectName') }}</td></tr>
                        <tr><th>{{ Lg::t('Descrição') }}</th><td>{{ Lg::f($object, 'description') }}</td></tr>
                        <tr><th>{{ Lg::t('Estado') }}</th><td>{{ $object->status }}</td></tr>
                        <tr><th>{{ Lg::t('Versão') }}</th><td>{{ $object->versionNumber }}</td></tr>
                        <tr><th>{{ Lg::t('Revisão (número)') }}</th><td>{{ $object->revisionNumber }}</td></tr>
                        <tr><th>{{ Lg::t('Data da versão') }}</th><td>{{ $object->dateOfVersion }}</td></tr>
                        <tr><th>{{ Lg::t('Data de revisão') }}</th><td>{{ $object->dateOfRevision }}</td></tr>
                        <tr>
                            <th>{{ Lg::t('Documento de referência') }}</th>
                            <td>
                                @if ($referencedocument && $referencedocument->rdName !== 'n/a')
                                    <a href="{{ Uri::buildLink('doc', $referencedocument) }}" title="{{ $referencedocument->title }}">{{ $referencedocument->rdName }}</a>
                                @else
                                    n/a
                                @endif
                            </td>
                        </tr>
                        @if ($versions->count() > 1)
                        <tr>
                            <th>{{ Lg::t('Versões') }}</th>
                            <td>
                                @foreach ($versions as $v)
                                    <a href="{{ Uri::buildLink('class', $v, (int) $v->versionNumber) }}">V{{ $v->versionNumber }}.{{ $v->revisionNumber }}</a>@if(!$loop->last), @endif
                                @endforeach
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div class="flex py-2">
                    <h4><strong>{{ Lg::pick('Modelos de dados deste objeto:', 'Data templates of this object:') }}</strong></h4>
                </div>
                <table id="tblprop" cellpadding="0" cellspacing="0">
                    <tr>
                        <th style="text-align:left!important; width:40%">{{ Lg::t('Modelo de dados') }}</th>
                        <th style="text-align:left!important;">{{ Lg::t('Versão') }}</th>
                    </tr>
                    @forelse ($templates as $t)
                    <tr>
                        <td><a href="{{ Uri::buildLink('dt', $t, (int) $t->versionNumber) }}">{{ Lg::f($t, 'pdtName') }}</a></td>
                        <td>V{{ $t->versionNumber }}.{{ $t->revisionNumber }} <x-status-badge :status="$t->status" /></td>
                    </tr>
                    @empty
                    <tr><td colspan="2">—</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
