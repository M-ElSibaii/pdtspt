<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ $propertyDefinition ? Lg::f($propertyDefinition, 'name', 'N/A') : 'N/A' }}</h1>
                    <p class="flex-none inline"> - V{{ $propertyDefinition->versionNumber ?? 'N/A' }}.{{ $propertyDefinition->revisionNumber ?? 'N/A' }}</p>
                    @if(isset($propertyDefinition->status) && $propertyDefinition->status == 'Active')
                    <span class="status-tag status-tag-active">{{ Lg::t('Ativa') }}</span>
                    @else
                    <span class="status-tag status-tag-inactive">{{ Lg::t('Inativa') }}</span>
                    @endif
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>{{ Lg::t('Propriedade em Classe') }}</h3>

                <div style="background-color: #f5f5f5; padding: 15px; margin-bottom: 20px; border-left: 4px solid #007bff;">
                    <h4>{{ Lg::t('Contexto') }}</h4>
                    <table>
                        <tr>
                            <th style="width: 200px;">{{ Lg::t('Modelo de Dados (PDT)') }}</th>
                            <td>
                                @if ($pdt)
                                <a href="{{ Uri::buildLink('dt', $pdt, (int) $pdt->versionNumber) }}">{{ Lg::f($pdt, 'pdtName') }} V{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}</a>
                                @else
                                N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Grupo de propriedades') }}</th>
                            <td>
                                @if ($group)
                                <a href="{{ Uri::buildLink('gop', $group) }}">{{ Lg::f($group, 'gopName') }}</a>
                                @else
                                N/A
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">{{ Lg::t('Class de Propriedade ID') }}</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$property->Id}}</td>
                        </tr>
                        <x-uri-row entity="classprop" :record="$property" :label="Lg::pick('URI (propriedade em classe)', 'URI (class property)')" />
                        @if ($propertyDefinition)
                        <x-uri-row entity="prop" :record="$propertyDefinition" :label="Lg::pick('URI (propriedade)', 'URI (property)')" />
                        @endif
                        <tr>
                            <th>GUID</th>
                            <td>{{$propertyDefinition->GUID ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Nome') }}</th>
                            <td>{{ $propertyDefinition ? Lg::propertyName($propertyDefinition, 'N/A') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Descrição na Classe') }}</th>
                            <td>{{ Lg::f($property, 'description', 'N/A') }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Definição') }}</th>
                            <td>{{ $propertyDefinition ? Lg::f($propertyDefinition, 'definition', 'N/A') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Unidades') }}</th>
                            <td><x-reference-link type="unit" :value="$propertyDefinition->units ?? null" placeholder="N/A" /></td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Tipo de dados') }}</th>
                            <td>{{$propertyDefinition->dataType ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Estado') }}</th>
                            <td>{{$propertyDefinition->status ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Versão') }}</th>
                            <td>{{$propertyDefinition->versionNumber ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Revisão (número)') }}</th>
                            <td>{{$propertyDefinition->revisionNumber ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data de Revisão') }}</th>
                            <td>{{$propertyDefinition->dateOfRevision ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Data da Versão') }}</th>
                            <td>{{$propertyDefinition->dateOfVersion ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>{{ Lg::t('Documento de referência') }}</th>
                            <td class="p-1.5">
                                @if ($referencedocument && $referencedocument->rdName !== 'n/a')
                                <a href="{{ Uri::buildLink('doc', $referencedocument) }}">
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
                @if ($propertyDefinition)
                <x-button-primary-pdts :link="Uri::buildLink('prop', $propertyDefinition, (int) $propertyDefinition->versionNumber)" :title="Lg::t('Ver Propriedade no Dicionário de Dados')" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
