<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-4">
            <h3>{{ __("Modelo de Dados do Produto baseado na EN ISO 23387") }}</h3>
            <div>
                <div class="flex flex-row">
                    <div class="grow block py-2">
                        <div class="flex-none inline">
                            <h1 class="flex-none inline">{{ $pdt->pdtNamePt }}</h1>
                            <p class="flex-none inline"> - V{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}</p>
                            @if ($pdt->status == 'Active')
                            <span class="status-tag status-tag-active">Ativa</span>
                            @endif
                            @if ($pdt->status == 'Preview')
                            <span class="status-tag status-tag-inactive">Inativa</span>
                            @endif
                            @if ($pdt->status == 'InActive')
                            <span class="status-tag status-tag-inactive">Inativa</span>
                            @endif

                        </div>
                    </div>
                    <div class="flex flex-row gap-2 py-2">
                        <x-secondary-button id="json" class="btn">
                            <i class="fa fa-download"></i>&nbsp;JSON
                        </x-secondary-button>
                        <x-secondary-button id="xml" class="btn">
                            <i class="fa fa-download"></i>&nbsp;XML
                        </x-secondary-button>
                        <x-secondary-button id="csv" class="btn">
                            <i class="fa fa-download"></i>&nbsp;CSV/XLS
                        </x-secondary-button>
                    </div>
                </div>
                <div class="overflow-auto" style="overflow-y: auto; max-height: calc(100vh - 300px); border: #cbd5e1 1px Solid;">
                    <table class="table-auto" id="tblpdts" cellpadding="0" cellspacing="0">
                        <thead class="sticky top-0 z-50">
                            <tr>
                                <th>Propriedade</th>
                                <th>Unidade</th>
                                <th>Descrição</th>
                                <th>Documento de referência</th>
                            </tr>
                        </thead>
                        @foreach($sorted_combined_groups as $group)
                        <tbody>
                            <tr>
                                <td class="text-left content-start bg-slate-300 p-3" colspan="5">
                                    <input class="text-left expand" type="checkbox" name="{{ $group[0]->gopNamePt }}" id="{{ $group[0]->gopNamePt }}" data-toggle="toggle">
                                    <label class="my-auto text-left cursor-pointer" for="{{ $group[0]->gopNamePt }}">Grupo de propriedades -
                                        <a href="{{ url('datadictionaryviewGOP/' . $group[0]->Id . '-' . \App\Http\Controllers\ProductdatatemplatesController::convertToPascalCase($group[0]->gopNamePt)) }}">
                                            {{ $group[0]->gopNamePt }}
                                        </a>
                                    </label>
                                </td>
                            </tr>
                        </tbody>

                        <tbody class="hide">
                            @foreach($group as $propertyGroup)
                            @foreach($joined_properties as $property)
                            @if($property->gopID == $propertyGroup->Id)
                            <!-- Apply the 'master-template-row' class if the property is from master template -->
                            <tr class="{{ $property->from_master ? 'master-template-row' : '' }}">
                               <td class="p-1.5 property-td">
    @if(!is_null($property->relationToOtherDataDictionaries))
        @php
            $relations = explode('),(', trim($property->relationToOtherDataDictionaries, '()'));
            $propertyUrl = null;
            $domainUrl = null;

            foreach ($relations as $relation) {
                $parts = explode(', ', $relation);
                if (isset($parts[1]) && trim($parts[1]) === 'bsdd.buildingsmart.org') {
                    $propertyUrl = trim($parts[0]);
                    $domainUrl = trim($parts[1]);
                    break;
                }
            }
        @endphp

        @if($domainUrl === 'bsdd.buildingsmart.org')
            {{-- bsDD link exists: show EN name first, then logo, then PT name --}}
            <a href="{{ url('datadictionaryview/' . $property->propertyId . '-' . \App\Http\Controllers\ProductdatatemplatesController::sanitizePascalCase($property->namePt)) }}">
                {{ $property->nameEn }}
            </a>
            <a href="{{ $propertyUrl }}" target="_blank">
                <img src="{{ asset('img/IFCBSDD.png') }}" alt="IFC Logo" style="width:40px; height:auto; margin-left:10px;">
            </a>
            <a href="{{ url('datadictionaryview/' . $property->propertyId . '-' . \App\Http\Controllers\ProductdatatemplatesController::sanitizePascalCase($property->namePt)) }}">
                {{ $property->namePt }}
            </a>
        @else
            {{-- No bsDD link: original behaviour --}}
            <a href="{{ url('datadictionaryview/' . $property->propertyId . '-' . \App\Http\Controllers\ProductdatatemplatesController::sanitizePascalCase($property->namePt)) }}">
                {{ $property->namePt }}
            </a>
        @endif
    @else
        {{-- No relation at all: original behaviour --}}
        <a href="{{ url('datadictionaryview/' . $property->propertyId . '-' . \App\Http\Controllers\ProductdatatemplatesController::sanitizePascalCase($property->namePt)) }}">
            {{ $property->namePt }}
        </a>
    @endif

    @if($property->status == 'InActive')
        <span class="status-tag status-tag-inactive">Inativa</span>
    @endif
</td>
                                <td class="p-1.5">{{ $property->units ? $property->units : 'Sem unidade' }}</td>
                                <td class="p-1.5">
                                    <div class="flex flex-col">

                                        <p><span style="color: darkgrey;"> {{ $property->namePtSc }}:</span><br>{{ $property->descriptionPt }}</p>
                                        @if($property->visualRepresentation == "TRUE")
                                        <div class="col-sm">
                                            <img src="{{ asset ('img/'.$property->nameEn.'.png')}}" alt='{{$property->nameEn}}' class="property-image">
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                @php
                                $referenceDoc = $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first();
                                @endphp
                                @if ($referenceDoc && ($referenceDoc->rdName == 'n/a' || !$referenceDoc->rdName))
                                <td class="p-1.5">
                                    <a>n/a</a>
                                </td>
                                @else
                                <td class="p-1.5">
                                    <a href="{{ route('referencedocumentview', ['rdGUID' => $property->referenceDocumentGUID]) }}">
                                        <p title="{{ $referenceDoc->title }}">{{ $referenceDoc->rdName }}</p>
                                    </a>
                                </td>
                                @endif
                            </tr>
                            @endif
                            @endforeach
                            @endforeach
                        </tbody>
                        @endforeach
                    </table>
                </div>
                <h6 style="padding-top: 5px"> Nota: As propriedades do modelo de dados mestre são destacadas a cinzento </h6>

                <div class="my-6 text-end">
                    <a href="/dashboard">
                        <x-secondary-button id="backButton" type="button">
                            Anterior
                        </x-secondary-button>
                    </a>
                </div>
            </div>
        </div>

       <table hidden id="tblpdtsh">
    <tr>
        <th style="width: 15%;">Grupo de propriedades</th>
        <th>Propriedade</th>
        <th style="width: 7%;">Unidade</th>
        <th style="width: 40%;">Descrição</th>
        <th style="width: 16%;">Documento de referência</th>
    </tr>
    @foreach($sorted_combined_groups as $group)
    @foreach($group as $propertyGroup)
    @foreach($joined_properties as $property)
    @if($property->gopID == $propertyGroup->Id)

        @php
            $propertyUrl = null;
            $domainUrl = null;

            if (!is_null($property->relationToOtherDataDictionaries)) {
                $relations = explode('),(', trim($property->relationToOtherDataDictionaries, '()'));
                foreach ($relations as $relation) {
                    $parts = explode(', ', $relation);
                    if (isset($parts[1]) && trim($parts[1]) === 'bsdd.buildingsmart.org') {
                        $propertyUrl = trim($parts[0]);
                        $domainUrl = trim($parts[1]);
                        break;
                    }
                }
            }
        @endphp

        <tr>
            <td>{{ $propertyGroup->gopNamePt }}</td>
            <td>
                @if($domainUrl === 'bsdd.buildingsmart.org')
                    {{ $property->nameEn }} - {{ $property->namePt }}
                @else
                    {{ $property->namePt }}
                @endif
            </td>
            <td>{{ $property->units }}</td>
            <td>{{ $property->namePtSc }}: {{ $property->descriptionPt }}</td>
            <td>
                @if($referenceDocument->where('GUID', $property->referenceDocumentGUID)->first())
                    {{ $referenceDocument->where('GUID', $property->referenceDocumentGUID)->first()->rdName }}
                @else
                    n/a
                @endif
            </td>
        </tr>

    @endif
    @endforeach
    @endforeach
    @endforeach
</table>


        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>
        <script src="https://rawcdn.githack.com/FuriosoJack/TableHTMLExport/v2.0.0/src/tableHTMLExport.js"></script>


        <script>
            $(document).ready(function() {
                $('[data-toggle="toggle"]').change(function() {
                    $(this).parents().next('.hide').toggle();
                });
            });

            //export json - server-side EN ISO 23387 format
            $("#json").on("click", function() {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const url = "{{ route('pdt.export.json', $pdt->Id) }}";
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Download failed');
                    return response.blob();
                })
                .then(blob => {
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = "{{ $pdt->pdtNamePt }}_V{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}.json";
                    link.click();
                })
                .catch(error => {
                    alert('Erro ao descarregar JSON: ' + error.message);
                });
            });

            //export xml - server-side EN ISO 23387 format with XSD validation
            $("#xml").on("click", function() {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const url = "{{ route('pdt.export.xml', $pdt->Id) }}";
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Download failed');
                    return response.blob();
                })
                .then(blob => {
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = "{{ $pdt->pdtNamePt }}_V{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}.xml";
                    link.click();
                })
                .catch(error => {
                    alert('Erro ao descarregar XML: ' + error.message);
                });
            });

            //export csv
            $("#csv").on("click", function() {
                $("#tblpdtsh").tableHTMLExport({
                    type: "csv",
                    filename: "{{ $pdt->pdtNamePt }} data template V{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}.csv"
                });
            });
     
        </script>


</x-app-layout>