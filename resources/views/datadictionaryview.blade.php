<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <h1>Atributos de propriedade no dicionário de dados baseado em EN ISO 23386</h1>
            <div class='py-6'>
                <div class=''>
                    <h1>{{$propdd->namePt}} </h1>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$propdd->GUID}}</td>
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
                            <th>Date of activation</th>
                            <td>{{$propdd->dateofActivation}}</td>
                        </tr>
                        <tr>
                            <th>Date of last change</th>
                            <td>{{$propdd->dateOfLastChange}}</td>
                        </tr>
                        <tr>
                            <th>Date of revision</th>
                            <td>{{$propdd->dateOfRevision}}</td>
                        </tr>
                        <tr>
                            <th>Date of version</th>
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
                            <th>List of replaced properties</th>
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
                            <th>List of replacing properties</th>
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
                    </tbody>
                </table>
                <div class='flex py-6'>
                    <h4><strong>Propriedade presente em:</strong></h4>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: right!important; width:50%">Modelo de dados</th>
                        <th style="text-align: right!important;">Descrição da propriedade</th>
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
</x-app-layout>