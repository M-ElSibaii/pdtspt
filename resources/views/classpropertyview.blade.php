<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ $propertyDefinition->namePt ?? 'N/A' }}</h1>
                    <p class="flex-none inline"> - V{{ $propertyDefinition->versionNumber ?? 'N/A' }}.{{ $propertyDefinition->revisionNumber ?? 'N/A' }}</p>
                    @if(isset($propertyDefinition->status) && $propertyDefinition->status == 'Active')
                    <span class="status-tag status-tag-active">Ativa</span>
                    @else
                    <span class="status-tag status-tag-inactive">Inativa</span>
                    @endif
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>Propriedade em Classe</h3>

                <div style="background-color: #f5f5f5; padding: 15px; margin-bottom: 20px; border-left: 4px solid #007bff;">
                    <h4>Contexto</h4>
                    <table>
                        <tr>
                            <th style="width: 200px;">Modelo de Dados (PDT)</th>
                            <td><a href="{{ route('pdtsdownload', ['pdtID' => $pdt->Id ?? 'N/A']) }}">{{ $pdt->pdtNamePt ?? 'N/A' }} V{{ $pdt->versionNumber ?? 'N/A' }}.{{ $pdt->revisionNumber ?? 'N/A' }}</a></td>
                        </tr>
                        <tr>
                            <th>Grupo de Propriedades</th>
                            <td><a href="{{ url('datadictionaryviewGOP/' . ($group->Id ?? 'N/A') . '-' . \App\Http\Controllers\ProductdatatemplatesController::convertToPascalCase($group->gopNamePt ?? '')) }}">{{ $group->gopNamePt ?? 'N/A' }}</a></td>
                        </tr>
                    </table>
                </div>

                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">Class de Propriedade ID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$property->Id}}</td>
                        </tr>
                        <tr>
                            <th>URI (Class de Propriedade)</th>
                            <td>
                                <a href="https://pdts.pt/classpropertyview/{{$property->Id}}-{{\App\Http\Controllers\ProductdatatemplatesController::sanitizePascalCase($propertyDefinition->namePt ?? '')}}" target="_blank">
                                    https://pdts.pt/classpropertyview/{{$property->Id}}-{{\App\Http\Controllers\ProductdatatemplatesController::sanitizePascalCase($propertyDefinition->namePt ?? '')}}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>GUID</th>
                            <td>{{$propertyDefinition->GUID ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Nome En</th>
                            <td>{{$propertyDefinition->nameEn ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Nome Pt</th>
                            <td>{{$propertyDefinition->namePt ?? 'N/A'}}</td>
                        </tr>               
                        <tr>
                            <th>Descrição na Classe</th>
                            <td>{{$property->descriptionPt ?? 'N/A'}}</td>
                        </tr>
                         <tr>
                            <th>Descrição na Classe En</th>
                            <td>{{$property->descriptionEn ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Unidades</th>
                            <td>{{$propertyDefinition->units ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Tipo de dados</th>
                            <td>{{$propertyDefinition->dataType ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>{{$propertyDefinition->status ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Versão</th>
                            <td>{{$propertyDefinition->versionNumber ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Revisão</th>
                            <td>{{$propertyDefinition->revisionNumber ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Data de Revisão</th>
                            <td>{{$propertyDefinition->dateOfRevision ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Data da Versão</th>
                            <td>{{$propertyDefinition->dateOfVersion ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Documento de referência</th>
                            <td class="p-1.5">
                                @if ($referencedocument && $referencedocument->rdName !== 'n/a')
                                <a href="{{ route('referencedocumentview', ['rdGUID' => $referencedocument->GUID]) }}">
                                    <p title="{{ $referencedocument->title }}">{{ $referencedocument->rdName }}</p>
                                </a>
                                @else
                                <span>n/a</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                <br>
                     <form class="mb-3" action="{{ url('datadictionaryview/' . $property->propertyId . '-' . \App\Http\Controllers\ProductdatatemplatesController::sanitizePascalCase($propertyDefinition->namePt)) }}">
                      <x-button-primary-pdts type="submit" title="Ver Propriedade no Dicionário de Dados" />
                     </form>
            </div>
        </div>
    </div>
</x-app-layout>
