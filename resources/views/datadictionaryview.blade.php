<x-app-layout>
    @php
        $isEarlier = fn($v) => $v->versionNumber < $propdd->versionNumber
            || ($v->versionNumber == $propdd->versionNumber && $v->revisionNumber < $propdd->revisionNumber);
        $isLater = fn($v) => $v->versionNumber > $propdd->versionNumber
            || ($v->versionNumber == $propdd->versionNumber && $v->revisionNumber > $propdd->revisionNumber);
        $hasDoc = is_object($referencedocument) && $referencedocument->rdName !== 'n/a';

        // bSDD link, if relationToOtherDataDictionaries names one.
        $bsddUrl = null;
        if (!is_null($propdd->relationToOtherDataDictionaries)) {
            foreach (explode('),(', trim($propdd->relationToOtherDataDictionaries, '()')) as $relation) {
                $parts = explode(', ', $relation);
                if (isset($parts[1]) && trim($parts[1]) === 'bsdd.buildingsmart.org') {
                    $bsddUrl = trim($parts[0]);
                    break;
                }
            }
        }
        $enumGroups = \App\Services\UriService::enumValueGroups($propdd);
        $enumValues = \App\Services\UriService::enumValuesOf($propdd);
    @endphp
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ Lg::f($propdd, 'name') }}</h1>
                    <p class="flex-none inline"> - <x-version-badge :version="$propdd->versionNumber" :revision="$propdd->revisionNumber" /></p>
                    <x-status-badge :status="$propdd->status" />
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>{{ Lg::t('Atributos de propriedade no dicionário de dados baseado em EN ISO 23386') }}</h3>

                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$propdd->GUID}}</td>
                        </tr>
                        <x-uri-row entity="prop" :record="$propdd" />
                        <tr>
                            <th>{{ Lg::t('Nome') }}</th>
                            <td>{{ Lg::fSc($propdd, 'name') }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::pick('Nome (código)', 'Name (code form)') }}</th>
                            <td>{{ Lg::f($propdd, 'name') }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Definição') }}</th>
                            <td>{{ Lg::f($propdd, 'definition') }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Unidades') }}</th>
                            <td><x-reference-link type="unit" :value="$propdd->units" /></td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Representação Visual') }}</th>
                            <td>
                                @if($propdd->visualRepresentation == "TRUE" || $propdd->visualRepresentation == 'True')
                                <div class="col-sm">
                                    <img src="{{ asset ('img/'.$propdd->nameEn.'.png')}}" alt='{{$propdd->nameEn}}' class="property-image">
                                </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Estado') }}</th>
                            <td>{{$propdd->status}}</td>
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
                            <td>{{$propdd->dateOfCreation}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de ativação') }}</th>
                            <td>{{$propdd->dateofActivation}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data da última alteração') }}</th>
                            <td>{{$propdd->dateOfLastChange}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de revisão') }}</th>
                            <td>{{$propdd->dateOfRevision}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data da versão') }}</th>
                            <td>{{$propdd->dateOfVersion}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Versão') }}</th>
                            <td>{{$propdd->versionNumber}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Revisão (número)') }}</th>
                            <td>{{$propdd->revisionNumber}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Lista de propriedades substituídas') }}</th>
                            <td>
                                @foreach ($propversions->filter($isEarlier) as $version)
                                    <a class="btn-link" style="margin-right: 5px;" href="{{ Uri::buildLink('prop', $version, (int) $version->versionNumber) }}">{{ $version->versionNumber }}.{{ $version->revisionNumber }}</a>
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Lista de propriedades de substituição') }}</th>
                            <td>
                                @foreach ($propversions->filter($isLater) as $version)
                                    <a class="btn-link" style="margin-right: 5px;" href="{{ Uri::buildLink('prop', $version, (int) $version->versionNumber) }}">{{ $version->versionNumber }}.{{ $version->revisionNumber }}</a>
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Relação com outros dicionários de dados') }}</th>
                            <td>{{$propdd->relationToOtherDataDictionaries}}
                                @if($bsddUrl)
                                <a href="{{ $bsddUrl }}" target="_blank">
                                    <img src="{{ asset('img/IFCBSDD.png') }}" alt="IFC Logo" style="width:40px; height:auto; margin-left:10px;">
                                </a>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Língua dos criadores') }}</th>
                            <td>{{$propdd->creatorsLanguage}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('País de utilização') }}</th>
                            <td>{{$propdd->countryOfUse}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('País de origem') }}</th>
                            <td>{{$propdd->countryOfOrigin}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Quantidade física') }}</th>
                            <td><x-reference-link type="quantitykind" :value="$propdd->physicalQuantity" /></td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Dimensão') }}</th>
                            <td><x-reference-link type="dimension" :value="$propdd->dimension" /></td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Tipo de dados') }}</th>
                            <td>{{$propdd->dataType}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Propriedade dinâmica') }}</th>
                            <td>{{$propdd->dynamicProperty}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Parametros da propriedade dinâmica') }}</th>
                            <td>{{$propdd->parametersOfTheDynamicProperty}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Nomes dos valores de definição') }}</th>
                            <td>{{$propdd->namesOfDefiningValues}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Valores de definição') }}</th>
                            <td>{{$propdd->definingValues}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Tolerância') }}</th>
                            <td>{{$propdd->tolerance}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Formato digital') }}</th>
                            <td>{{$propdd->digitalFormat}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Formato de texto') }}</th>
                            <td>{{$propdd->textFormat}}</td>
                        </tr>
                        <tr id="possible-values">
                            <th>{{ Lg::t('Lista de valores possíveis na língua n') }}</th>
                            <td>
                                @if ($enumGroups)
                                    {{-- Values are shown in the reader's language where the data has
                                         them; the identifier below each set is language-independent. --}}
                                    @php $shown = $enumGroups[Lg::current()] ?? $enumGroups['en'] ?? reset($enumGroups); @endphp
                                    {{ implode(', ', $shown) }}
                                    @if ($enumValues)
                                        <br>
                                        <small style="color:#6b7280;">
                                            @foreach ($enumValues as $value)
                                                <a href="{{ Uri::link('enum', \App\Services\UriService::enumCode($propdd, $value)) }}">{{ $value }}</a>@if(!$loop->last) · @endif
                                            @endforeach
                                        </small>
                                    @endif
                                @else
                                    {{$propdd->listOfPossibleValuesInLanguageN}}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Valores-limite') }}</th>
                            <td>{{$propdd->boundaryValues}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Explicação da depreciação') }}</th>
                            <td>{{$propdd->depreciationExplanation}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de depreciação') }}</th>
                            <td>{{$propdd->depreciationDate}}</td>
                        </tr>
                    </tbody>
                </table>
                <div class='flex py-2'>
                    <h4><strong>{{ Lg::t('Propriedade presente em:') }}</strong></h4>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: left!important; width:25%">{{ Lg::t('Modelo de dados') }}</th>
                        <th style="text-align: left!important;">{{ Lg::t('Descrição da propriedade') }}</th>
                    </tr>

                    @foreach ($propinpdts as $proppdts)
                    @php $inPdt = $pdts->where('Id', $proppdts->pdtID)->first(); @endphp
                    @if ($inPdt)
                    <tr>
                        <td>
                            <a href="{{ route('pdtsdownload', ['pdtID' => $inPdt->Id]) }}">{{ Lg::f($inPdt, 'pdtName') }} V{{ $inPdt->versionNumber }}.{{ $inPdt->revisionNumber }}</a>
                        </td>
                        <td>
                            <a href="{{ Uri::buildLink('classprop', $proppdts) }}">{{ Lg::f($proppdts, 'description') }}</a>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
