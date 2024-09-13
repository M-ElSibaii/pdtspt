<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                {{-- <h1>{{$gopdd->gopNamePt}} </h1> --}}
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ $gopdd->gopNamePt }}</h1>
                    <p class="flex-none inline"> - V{{ $gopdd->versionNumber }}.{{ $gopdd->revisionNumber }}</p>
                    @if($gopdd->status == 'Active')
                    <span class="status-tag status-tag-active">Ativa</span>
                    @else
                    <span class="status-tag status-tag-inactive">Inativa</span>
                    @endif
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>Atributos de grupo de propriedades no dicionário de dados baseado em EN ISO 23386</h3>
                
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$gopdd->GUID}}</td>
                        </tr>
                        <tr>
                            <th>Nome En</th>
                            <td>{{$gopdd->gopNameEn}}</td>
                        </tr>
                        <tr>
                            <th>Nome Pt</th>
                            <td>{{$gopdd->gopNamePt}}</td>
                        </tr>
                        <tr>
                            <th>Descrição En</th>
                            <td>{{$gopdd->definitionEn}}</td>
                        </tr>
                        <tr>
                            <th>Descrição Pt</th>
                            <td>{{$gopdd->definitionPt}}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>{{$gopdd->status}}</td>
                        </tr>
                        <tr>
                            <th>Data de criação</th>
                            <td>{{$gopdd->dateOfCreation}}</td>
                        </tr>
                        <tr>
                            <th>Data de ativação</th>
                            <td>{{$gopdd->dateofActivation}}</td>
                        </tr>
                        <tr>
                            <th>Data da última alteração</th>
                            <td>{{$gopdd->dateOfLastChange}}</td>
                        </tr>
                        <tr>
                            <th>Data de revisão</th>
                            <td>{{$gopdd->dateOfRevision}}</td>
                        </tr>
                        <tr>
                            <th>Data da versão</th>
                            <td>{{$gopdd->dateOfVersion}}</td>
                        </tr>
                        <tr>
                            <th>Versão</th>
                            <td>{{$gopdd->versionNumber}}</td>
                        </tr>
                        <tr>
                            <th>Revisão</th>
                            <td>{{$gopdd->revisionNumber}}</td>
                        </tr>
                        <tr>
                            <th>Lista de grupo de propriedades substituídas</th>
                            <td style="display: flex; border: none;">
                                @php
                                $latestVersion = null;
                                $latestVersionNumber = null;
                                $latestRevisionNumber = null;
                                @endphp
                                @foreach ($gopversions as $version)
                                @if (($version->versionNumber < $gopdd->versionNumber) || ($version->versionNumber == $gopdd->versionNumber && $version->revisionNumber < $gopdd->revisionNumber))
                                        @if ($version->versionNumber != $latestVersionNumber || $version->revisionNumber != $latestRevisionNumber)
                                        @php
                                        $latestVersion = $version;
                                        $latestVersionNumber = $version->versionNumber;
                                        $latestRevisionNumber = $version->revisionNumber;
                                        @endphp
                                        @endif
                                        @endif
                                        @endforeach
                                        @if ($latestVersion)
                                        <form class="mb-3" action="{{ url('datadictionaryviewGOP/' . $latestVersion->Id . '-' . $latestVersion->GUID) }}">
                                            <button class="btn-link" type="submit" style="margin-right: 5px;" >{{ $latestVersion->versionNumber}}.{{$latestVersion->revisionNumber}}, </button>
                                        </form>
                                        @endif

                            </td>
                        </tr>
                        <tr>
                            <th>Lista de grupo de propriedades de substituição</th>
                            <td style="display: flex; border: none;">
                                @php
                                $latestVersion = null;
                                $latestVersionNumber = null;
                                $latestRevisionNumber = null;
                                @endphp
                                @foreach ($gopversions as $version)
                                @if (($version->versionNumber > $gopdd->versionNumber) || ($version->versionNumber == $gopdd->versionNumber && $version->revisionNumber > $gopdd->revisionNumber))
                                @if ($version->versionNumber != $latestVersionNumber || $version->revisionNumber != $latestRevisionNumber)
                                @php
                                $latestVersion = $version;
                                $latestVersionNumber = $version->versionNumber;
                                $latestRevisionNumber = $version->revisionNumber;
                                @endphp
                                @endif
                                @endif
                                @endforeach
                                @if ($latestVersion)
                                <form class="mb-3" action="{{ url('datadictionaryviewGOP/' . $latestVersion->Id . '-' . $latestVersion->GUID) }}">
                                    <button class="btn-link" type="submit" style="margin-right: 5px;" >{{ $latestVersion->versionNumber}}.{{$latestVersion->revisionNumber}}, </button>
                                </form>
                                @endif

                            </td>
                        </tr>
                        <tr>
                            <th>Relação com outros dicionários de dados</th>
                            <td>{{$gopdd->relationToOtherDataDictionaries}}</td>
                        </tr>
                        <tr>
                            <th>Língua dos criadores</th>
                            <td>{{$gopdd->creatorsLanguage}}</td>
                        </tr>
                        <tr>
                            <th>Representação visual</th>
                            <td>
                                {{$gopdd->visualRepresentation}}
                            </td>
                        </tr>
                        <tr>
                            <th>País de utilização</th>
                            <td>{{$gopdd->countryOfUse}}</td>
                        </tr>
                        <tr>
                            <th>País de origem</th>
                            <td>{{$gopdd->countryOfOrigin}}</td>
                        </tr>
                        <tr>
                            <th>Categoria de Grupo de propriedades</th>
                            <td>{{$gopdd->categoryOfGroupOfProperties}}</td>
                        </tr>
                        <tr>
                            <th>Grupo de propriedades-mãe</th>
                            <td>{{$gopdd->parentGroupOfProperties}}</td>
                        </tr>
                        <tr>
                            <th>Explicação da depreciação</th>
                            <td>{{$gopdd->depreciationExplanation}}</td>
                        </tr>
                        <tr>
                            <th>Data de depreciação</th>
                            <td>{{$gopdd->depreciationDate}}</td>
                        </tr>
                    </tbody>
                </table>
                <div class='flex py-2'>
                    <h4><strong>Grupo de Propriedades presente em:</strong></h4>
                </div>
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tr>
                        <th style="text-align: left!important; width:25%">Modelo de dados</th>
                        <th style="text-align: left!important;">Descrição da Grupo de Propriedades</th>
                    </tr>

                    @foreach ($gopinpdts as $goppdts)
                    <tr>
                        <td>
                            <a href="{{ route('pdtsdownload', ['pdtID' => $goppdts->pdtId]) }}">{{$pdts->where('Id', $goppdts->pdtId)->first()->pdtNamePt}} V{{$pdts->where('Id', $goppdts->pdtId)->first()->editionNumber}}.{{$pdts->where('Id', $goppdts->pdtId)->first()->versionNumber}}.{{$pdts->where('Id', $goppdts->pdtId)->first()->revisionNumber}}</a>
                        </td>
                        <td>{{$goppdts->definitionPt}}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</x-app-layout>