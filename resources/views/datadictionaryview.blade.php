<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>Atributos de propriedade no dicionário de dados baseado em EN ISO 23386</h1>
            <div class='py-6'>
                <div class=''>
                    {{-- <h1>{{$propdd->namePt}} </h1> --}}
                    <div class="flex-none inline">
                        <h1 class="flex-none inline">{{ $propdd->namePt }}</h1>
                        <p class="flex-none inline"> - V{{ $propdd->versionNumber }}.{{ $propdd->revisionNumber }}</p>
                        @if($propdd->status == 'Active')
                        <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Ativa</span>
                        @else
                        <span class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">InActiva</span>
                        @endif
                    </div>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$propdd->GUID}}</td>
                        </tr>
                        <tr>
                            <th>Nome En</th>
                            <td>{{$propdd->nameEn}}</td>
                        </tr>
                        <tr>
                            <th>Nome Pt</th>
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
                            <td>
                                @foreach ($propversions as $version)
                                @if ($version->versionNumber < $propdd->versionNumber || ($version->versionNumber == $propdd->versionNumber && $version->revisionNumber < $propdd->revisionNumber)) <form class="mb-3" action="{{ url('datadictionaryview/' . $version->Id . '-' . $version->GUID) }}">
                                            <button class="btn btn-link" type="submit">{{ $version->versionNumber}}.{{$version->revisionNumber}}, </button>
                                        </form>
                                        @endif
                                        @endforeach
                                        {{$propdd->listOfReplacedProperties}}
                            </td>
                        </tr>
                        <tr>
                            <th>Lista de propriedades de substituição</th>
                            <td>
                                @foreach ($propversions as $version)
                                @if ($version->versionNumber > $propdd->versionNumber || ($version->versionNumber == $propdd->versionNumber && $version->revisionNumber > $propdd->revisionNumber))
                                <form class="mb-3" action="{{ url('datadictionaryview/' . $version->Id . '-' . $version->GUID) }}">
                                    <button class="btn btn-link" type="submit">{{ $version->versionNumber}}.{{$version->revisionNumber}}, </button>
                                </form>
                                @endif
                                @endforeach
                                {{$propdd->listOfReplacingProperties}}
                            </td>
                        </tr>
                        <tr>
                            <th>Relação com outros dicionários de dados</th>
                            <td>{{$propdd->relationToOtherDataDictionaries}}</td>
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
                    </tbody>
                </table>
                <div class='flex py-6'>
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