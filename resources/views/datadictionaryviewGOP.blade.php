<x-app-layout>
    @php
        // Nearest earlier / later version of this lineage (one link each, as before).
        $sorted = $gopversions->sortBy(fn($v) => [$v->versionNumber, $v->revisionNumber])->values();
        $isEarlier = fn($v) => $v->versionNumber < $gopdd->versionNumber
            || ($v->versionNumber == $gopdd->versionNumber && $v->revisionNumber < $gopdd->revisionNumber);
        $isLater = fn($v) => $v->versionNumber > $gopdd->versionNumber
            || ($v->versionNumber == $gopdd->versionNumber && $v->revisionNumber > $gopdd->revisionNumber);
        $previous = $sorted->filter($isEarlier)->last();
        $next = $sorted->filter($isLater)->first();
        $hasDoc = is_object($referencedocument) && $referencedocument->rdName !== 'n/a';
    @endphp
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ Lg::f($gopdd, 'gopName') }}</h1>
                    <p class="flex-none inline"> - <x-version-badge :version="$gopdd->versionNumber" :revision="$gopdd->revisionNumber" /></p>
                    <x-status-badge :status="$gopdd->status" />
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>{{ Lg::t('Atributos de grupo de propriedades no dicionário de dados baseado em EN ISO 23386') }}</h3>

                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$gopdd->GUID}}</td>
                        </tr>
                        <x-uri-row entity="gop" :record="$gopdd" />
                        <tr>
                            <th>{{ Lg::t('Nome') }}</th>
                            <td>{{ Lg::f($gopdd, 'gopName') }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Definição') }}</th>
                            <td>{{ Lg::f($gopdd, 'definition') }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Estado') }}</th>
                            <td>{{$gopdd->status}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Documento de referência') }}</th>
                            <td class="p-1.5">
                                @if ($hasDoc)
                                <a href="{{ Uri::buildLink('doc', $referencedocument) }}">
                                    <p title="{{ $referencedocument->title }}">{{ $referencedocument->rdName }}</p>
                                </a>
                                @else
                                <span>n/a</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de criação') }}</th>
                            <td>{{$gopdd->dateOfCreation}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de ativação') }}</th>
                            <td>{{$gopdd->dateofActivation}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data da última alteração') }}</th>
                            <td>{{$gopdd->dateOfLastChange}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de revisão') }}</th>
                            <td>{{$gopdd->dateOfRevision}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data da versão') }}</th>
                            <td>{{$gopdd->dateOfVersion}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Versão') }}</th>
                            <td>{{$gopdd->versionNumber}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Revisão (número)') }}</th>
                            <td>{{$gopdd->revisionNumber}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Lista de grupo de propriedades substituídas') }}</th>
                            <td>
                                @if ($previous)
                                <a class="btn-link" href="{{ Uri::buildLink('gop', $previous) }}">{{ $previous->versionNumber }}.{{ $previous->revisionNumber }}</a>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Lista de grupo de propriedades de substituição') }}</th>
                            <td>
                                @if ($next)
                                <a class="btn-link" href="{{ Uri::buildLink('gop', $next) }}">{{ $next->versionNumber }}.{{ $next->revisionNumber }}</a>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Relação com outros dicionários de dados') }}</th>
                            <td>{{$gopdd->relationToOtherDataDictionaries}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Língua dos criadores') }}</th>
                            <td>{{$gopdd->creatorsLanguage}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Representação visual') }}</th>
                            <td>{{$gopdd->visualRepresentation}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('País de utilização') }}</th>
                            <td>{{$gopdd->countryOfUse}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('País de origem') }}</th>
                            <td>{{$gopdd->countryOfOrigin}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Categoria de Grupo de propriedades') }}</th>
                            <td>{{$gopdd->categoryOfGroupOfProperties}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Grupo de propriedades-mãe') }}</th>
                            <td>{{$gopdd->parentGroupOfProperties}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Explicação da depreciação') }}</th>
                            <td>{{$gopdd->depreciationExplanation}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de depreciação') }}</th>
                            <td>{{$gopdd->depreciationDate}}</td>
                        </tr>
                    </tbody>
                </table>
                <div class='flex py-2'>
                    <h4><strong>{{ Lg::t('Grupo de Propriedades presente em:') }}</strong></h4>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: left!important; width:25%">{{ Lg::t('Modelo de dados') }}</th>
                        <th style="text-align: left!important;">{{ Lg::t('Descrição da Grupo de Propriedades') }}</th>
                    </tr>

                    @foreach ($gopinpdts as $goppdts)
                    @php $inPdt = $pdts->where('Id', $goppdts->pdtId)->first(); @endphp
                    @if ($inPdt)
                    <tr>
                        <td>
                            <a href="{{ route('pdtsdownload', ['pdtID' => $inPdt->Id]) }}">{{ Lg::f($inPdt, 'pdtName') }} V{{ $inPdt->versionNumber }}.{{ $inPdt->revisionNumber }}</a>
                        </td>
                        <td>{{ Lg::f($goppdts, 'definition') }}</td>
                    </tr>
                    @endif
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
