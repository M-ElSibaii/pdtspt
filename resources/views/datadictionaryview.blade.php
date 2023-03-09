<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Atributos de propriedade no dicionário de dados baseado em EN ISO 23386") }}
        </h2>
    </x-slot>

    <body>
        <main class="flex-shrink-0">
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <div class='home_content container'>
                            <div class='row'>
                                <h2>{{$propdd->namePt}} </h2>
                            </div>
                            <table id='tblprop' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <th style="width:50%">GUID</th>
                                    <td>{{$propdd->GUID}}</td>
                                </tr>
                                <th>Name En</th>
                                <td>{{$propdd->nameEn}}</td>
                                <tr>
                                    <th>Name Pt</th>
                                    <td>{{$propdd->namePt}}</td>
                                <tr>
                                    <th>Description</th>
                                    <td>{{$propdd->definitionEn}}</td>
                                </tr>
                                <th>Description Pt</th>
                                <td>{{$propdd->definitionPt}}</td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td>{{$propdd->units}}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{$propdd->status}}</td>
                                </tr>
                                <tr>
                                    <th>Date of creation</th>
                                    <td>{{$propdd->dateOfCreation}}</td>
                                </tr>
                                <tr>
                                    <th>dateofActivation</th>
                                    <td>{{$propdd->dateofActivation}}</td>
                                </tr>
                                <tr>
                                    <th>dateOfLastChange</th>
                                    <td>{{$propdd->dateOfLastChange}}</td>
                                </tr>
                                <tr>
                                    <th>dateOfRevision</th>
                                    <td>{{$propdd->dateOfRevision}}</td>
                                </tr>
                                <tr>
                                    <th>dateOfVersion</th>
                                    <td>{{$propdd->dateOfVersion}}</td>
                                </tr>
                                <tr>
                                    <th>Version</th>
                                    <td>{{$propdd->versionNumber}}</td>
                                </tr>
                                <tr>
                                    <th>Revision</th>
                                    <td>{{$propdd->revisionNumber}}</td>
                                </tr>
                                <tr>
                                    <th>listOfReplacedProperties</th>
                                    <td>
                                        @foreach ($propversions as $version)
                                        @if ($version->dateOfRevision < $propdd->dateOfRevision)
                                            <form class="mb-3" action="{{ route('datadictionaryview', ['propID' => $version->GUID , 'propV' => $version->versionNumber, 'propR' => $version->revisionNumber]) }}">
                                                <button class="btn btn-link" type="submit">{{ $version->versionNumber}}.{{$version->revisionNumber}}, </button>
                                            </form>
                                            @endif
                                            @endforeach
                                            {{$propdd->listOfReplacedProperties}}
                                    </td>
                                </tr>
                                <tr>
                                    <th>listOfReplacingProperties</th>
                                    <td>
                                        @foreach ($propversions as $version)
                                        @if ($version->dateOfRevision > $propdd->dateOfRevision)
                                        <form class="mb-3" action="{{ route('datadictionaryview', ['propID' => $version->GUID , 'propV' => $version->versionNumber, 'propR' => $version->revisionNumber]) }}">
                                            <button class="btn btn-link" type="submit">{{ $version->versionNumber}}.{{$version->revisionNumber}}, </button>
                                        </form>
                                        @endif
                                        @endforeach

                                        {{$propdd->listOfReplacingProperties}}
                                    </td>
                                </tr>
                                <tr>
                                    <th>relationToOtherDataDictionaries</th>
                                    <td>{{$propdd->relationToOtherDataDictionaries}}</td>
                                </tr>
                                <tr>
                                    <th>creatorsLanguage</th>
                                    <td>{{$propdd->creatorsLanguage}}</td>
                                </tr>
                                <tr>
                                    <th>visualRepresentation</th>
                                    <td>
                                        @if ($propdd->visualRepresentation == 'True')
                                        <div class='col-sm'>
                                            <img src="{{ asset ('img/'.$propdd->nameEn.'.png')}}" alt='{{$propdd->nameEn}}' height='200'>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>countryOfUse</th>
                                    <td>{{$propdd->countryOfUse}}</td>
                                </tr>
                                <tr>
                                    <th>countryOfOrigin</th>
                                    <td>{{$propdd->countryOfOrigin}}</td>
                                </tr>
                                <tr>
                                    <th>physicalQuantity</th>
                                    <td>{{$propdd->physicalQuantity}}</td>
                                </tr>
                                <tr>
                                    <th>dimension</th>
                                    <td>{{$propdd->dimension}}</td>
                                </tr>
                                <tr>
                                    <th>dataType</th>
                                    <td>{{$propdd->dataType}}</td>
                                </tr>
                                <tr>
                                    <th>dynamicProperty</th>
                                    <td>{{$propdd->dynamicProperty}}</td>
                                </tr>
                                <tr>
                                    <th>parametersOfTheDynamicProperty</th>
                                    <td>{{$propdd->parametersOfTheDynamicProperty}}</td>
                                </tr>
                                <tr>
                                    <th>namesOfDefiningValues</th>
                                    <td>{{$propdd->namesOfDefiningValues}}</td>
                                </tr>
                                <tr>
                                    <th>definingValues</th>
                                    <td>{{$propdd->definingValues}}</td>
                                </tr>
                                <tr>
                                    <th>tolerance</th>
                                    <td>{{$propdd->tolerance}}</td>
                                </tr>
                                <tr>
                                    <th>digitalFormat</th>
                                    <td>{{$propdd->digitalFormat}}</td>
                                </tr>
                                <tr>
                                    <th>textFormat</th>
                                    <td>{{$propdd->textFormat}}</td>
                                </tr>
                                <tr>
                                    <th>listOfPossibleValuesInLanguageN</th>
                                    <td>{{$propdd->listOfPossibleValuesInLanguageN}}</td>
                                </tr>
                                <tr>
                                    <th>boundaryValues</th>
                                    <td>{{$propdd->boundaryValues}}</td>
                                </tr>
                            </table>
                            <div class='row'>
                                <h4><strong>Propriedade presente em:</strong></h4>
                            </div>
                            <table id='tblprop' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <th style="width:50%">Modelo de dados</th>
                                    <th>Descrição da propriedade</th>
                                </tr>

                                @foreach ($propinpdts as $proppdts)
                                <tr>
                                    <td>{{$pdts->where('Id', $proppdts->pdtID)->first()->pdtNamePt}} V
                                        {{$pdts->where('Id', $proppdts->pdtID)->first()->versionNumber}}.{{$pdts->where('Id', $proppdts->pdtID)->first()->revisionNumber}}
                                    </td>
                                    <td>{{$proppdts->descriptionPt}}</td>
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