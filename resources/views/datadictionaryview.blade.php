<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                {{-- <h1>{{$propdd->namePt}} </h1> --}}
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ $propdd->namePt }}</h1>
                    <p class="flex-none inline"> - V{{ $propdd->versionNumber }}.{{ $propdd->revisionNumber }}</p>
                    @if($propdd->status == 'Active')
                    <span class="status-tag status-tag-active">Ativa</span>
                    @else
                    <span class="status-tag status-tag-inactive">Inativa</span>
                    @endif
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>Atributos de propriedade no dicionário de dados baseado em EN ISO 23386</h3>

                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$propdd->GUID}}</td>
                        </tr>
                        <tr>
                            <th>Nome En</th>
                            <td>{{$propdd->nameEnSc}}</td>
                        </tr>
                        <tr>
                            <th>Nome En Código</th>
                            <td>{{$propdd->nameEn}}</td>
                        </tr>
                        <tr>
                            <th>Nome Pt</th>
                            <td>{{$propdd->namePtSc}}</td>
                        </tr>
                        <tr>
                            <th>Nome Pt Código</th>
                            <td>{{$propdd->namePt}}</td>
                        </tr>
                        <tr>
                            <th>Descrição En</th>
                            <td>{{$propdd->definitionEn}}</td>
                        </tr>
                        <tr>
                            <th>Descrição Pt</th>
                            <td>{{$propdd->definitionPt}}</td>
                        </tr>
                        <tr>
                            <th>Unidades</th>
                            <td>{{$propdd->units}}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>{{$propdd->status}}</td>
                        </tr>
                        <tr>
                            <th>Documento de referência</th>
                            <td class="p-1.5">
                                @if ($referencedocument->rdName === 'n/a')
                                <span>n/a</span>
                                @else
                                <a href="{{ route('referencedocumentview', ['rdGUID' => $referencedocument->GUID]) }}">
                                    <p title="{{ $referencedocument->title }}">{{ $referencedocument->rdName }}</p>
                                </a>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Data de criação</th>
                            <td>{{$propdd->dateOfCreation}}</td>
                        </tr>
                        <tr>
                            <th>Data de ativação</th>
                            <td>{{$propdd->dateofActivation}}</td>
                        </tr>
                        <tr>
                            <th>Data da última alteração</th>
                            <td>{{$propdd->dateOfLastChange}}</td>
                        </tr>
                        <tr>
                            <th>Data de revisão</th>
                            <td>{{$propdd->dateOfRevision}}</td>
                        </tr>
                        <tr>
                            <th>Data da versão</th>
                            <td>{{$propdd->dateOfVersion}}</td>
                        </tr>
                        <tr>
                            <th>Versão</th>
                            <td>{{$propdd->versionNumber}}</td>
                        </tr>
                        <tr>
                            <th>Revisão</th>
                            <td>{{$propdd->revisionNumber}}</td>
                        </tr>
                        <tr>
                            <th>Lista de propriedades substituídas</th>
                            <td style="display: flex; border: none;">
                                @foreach ($propversions as $version)
                                @if ($version->versionNumber < $propdd->versionNumber || ($version->versionNumber == $propdd->versionNumber && $version->revisionNumber < $propdd->revisionNumber)) <form class="mb-3" action="{{ url('datadictionaryview/' . $version->Id . '-' . $version->GUID) }}">
                                            <button class="btn-link" type="submit" style="margin-right: 5px;">{{ $version->versionNumber}}.{{$version->revisionNumber}}, </button>
                                        </form>
                                        @endif
                                        @endforeach

                            </td>
                        </tr>
                        <tr>
                            <th>Lista de propriedades de substituição</th>
                            <td>
                                @foreach ($propversions as $version)
                                @if ($version->versionNumber > $propdd->versionNumber || ($version->versionNumber == $propdd->versionNumber && $version->revisionNumber > $propdd->revisionNumber))
                                <form class="mb-3" action="{{ url('datadictionaryview/' . $version->Id . '-' . $version->GUID) }}">
                                    <button class="btn-link" type="submit" style="margin-right: 5px;">{{ $version->versionNumber}}.{{$version->revisionNumber}}, </button>
                                </form>
                                @endif
                                @endforeach

                            </td>
                        </tr>
                        <tr>
                            <th>Relação com outros dicionários de dados</th>
                            <td>{{$propdd->relationToOtherDataDictionaries}}
                                {{-- Check if the relationToOtherDataDictionaries attribute exists and is not null --}}
                                @if(!is_null($propdd->relationToOtherDataDictionaries))
                                @php
                                // Remove parentheses and split by ',' to get individual elements
                                $relations = explode('),(', trim($propdd->relationToOtherDataDictionaries, '()'));

                                // Initialize variables for storing URLs
                                $propertyUrl = null;
                                $domainUrl = null;

                                // Iterate through each relation to check for bsdd.buildingsmart.org
                                foreach ($relations as $relation) {
                                // Split each relation into property URL and domain URL
                                $parts = explode(', ', $relation);

                                // If the second part (domain URL) matches bsdd.buildingsmart.org, store the property URL
                                if (isset($parts[1]) && trim($parts[1]) === 'bsdd.buildingsmart.org') {
                                $propertyUrl = trim($parts[0]); // Store the property URL
                                $domainUrl = trim($parts[1]); // Store the domain URL
                                break; // Stop the loop once we find the correct domain
                                }
                                }
                                @endphp

                                {{-- Only show the logo if the domain matches --}}
                                @if($domainUrl === 'bsdd.buildingsmart.org')
                                <a href="{{ $propertyUrl }}" target="_blank">
                                    <img src="{{ asset('img/IFCBSDD.png') }}" alt="IFC Logo" style="width:40px; height:auto; margin-left:10px;">
                                </a>
                                @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Língua dos criadores</th>
                            <td>{{$propdd->creatorsLanguage}}</td>
                        </tr>
                        <tr>
                            <th>Representação visual</th>
                            <td>
                                @if ($propdd->visualRepresentation == 'True')
                                <div class='col-sm'>
                                    <img src="{{ asset ('img/'.$propdd->nameEn.'.png')}}" alt='{{$propdd->nameEn}}' height='200'>
                                </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>País de utilização</th>
                            <td>{{$propdd->countryOfUse}}</td>
                        </tr>
                        <tr>
                            <th>País de origem</th>
                            <td>{{$propdd->countryOfOrigin}}</td>
                        </tr>
                        <tr>
                            <th>Quantidade física</th>
                            <td>{{$propdd->physicalQuantity}}</td>
                        </tr>
                        <tr>
                            <th>Dimensão</th>
                            <td>{{$propdd->dimension}}</td>
                        </tr>
                        <tr>
                            <th>Tipo de dados</th>
                            <td>{{$propdd->dataType}}</td>
                        </tr>
                        <tr>
                            <th>Propriedade dinâmica</th>
                            <td>{{$propdd->dynamicProperty}}</td>
                        </tr>
                        <tr>
                            <th>Parametros da propriedade dinâmica</th>
                            <td>{{$propdd->parametersOfTheDynamicProperty}}</td>
                        </tr>
                        <tr>
                            <th>Nomes dos valores de definição</th>
                            <td>{{$propdd->namesOfDefiningValues}}</td>
                        </tr>
                        <tr>
                            <th>Valores de definição</th>
                            <td>{{$propdd->definingValues}}</td>
                        </tr>
                        <tr>
                            <th>Tolerância</th>
                            <td>{{$propdd->tolerance}}</td>
                        </tr>
                        <tr>
                            <th>Formato digital</th>
                            <td>{{$propdd->digitalFormat}}</td>
                        </tr>
                        <tr>
                            <th>Formato de texto</th>
                            <td>{{$propdd->textFormat}}</td>
                        </tr>
                        <tr>
                            <th>Lista de valores possíveis na língua n</th>
                            <td>{{$propdd->listOfPossibleValuesInLanguageN}}</td>
                        </tr>
                        <tr>
                            <th>Valores-limite</th>
                            <td>{{$propdd->boundaryValues}}</td>
                        </tr>
                        <tr>
                            <th>Explicação da depreciação</th>
                            <td>{{$propdd->depreciationExplanation}}</td>
                        </tr>
                        <tr>
                            <th>Data de depreciação</th>
                            <td>{{$propdd->depreciationDate}}</td>
                        </tr>
                    </tbody>
                </table>
                <div class='flex py-2'>
                    <h4><strong>Propriedade presente em:</strong></h4>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: left!important; width:25%">Modelo de dados</th>
                        <th style="text-align: left!important;">Descrição da propriedade</th>
                    </tr>

                    @foreach ($propinpdts as $proppdts)
                    <tr>
                        <td>
                            <a href="{{ route('pdtsdownload', ['pdtID' => $proppdts->pdtID]) }}">{{$pdts->where('Id', $proppdts->pdtID)->first()->pdtNamePt}} V{{$pdts->where('Id', $proppdts->pdtID)->first()->editionNumber}}.{{$pdts->where('Id', $proppdts->pdtID)->first()->versionNumber}}.{{$pdts->where('Id', $proppdts->pdtID)->first()->revisionNumber}}</a>
                        </td>
                        <td>{{$proppdts->descriptionPt}}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</x-app-layout>