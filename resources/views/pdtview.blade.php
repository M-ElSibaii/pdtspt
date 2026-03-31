<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ $pdt->pdtNamePt }}</h1>
                    <p class="flex-none inline"> - V{{ $pdt->versionNumber }}.{{ $pdt->revisionNumber }}</p>
                    @if($pdt->status == 'Active')
                    <span class="status-tag status-tag-active">Ativa</span>
                    @else
                    <span class="status-tag status-tag-inactive">Inativa</span>
                    @endif
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>Atributos do Modelo de Dados de Produto baseado em EN ISO 23387</h3>
                
                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                        <tr>
                            <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                            <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$pdt->GUID}}</td>
                        </tr>
                        <tr>
                            <th>URI</th>
                            <td>
                                <a href="https://pdts.pt/pdtview/{{$pdt->Id}}-{{$pdt->GUID}}" target="_blank">
                                    https://pdts.pt/pdtview/{{$pdt->Id}}-{{$pdt->GUID}}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Nome En</th>
                            <td>{{$pdt->pdtNameEn}}</td>
                        </tr>
                        <tr>
                            <th>Nome Pt</th>
                            <td>{{$pdt->pdtNamePt}}</td>
                        </tr>
                        <tr>
                            <th>Descrição En</th>
                            <td>{{$pdt->descriptionEn}}</td>
                        </tr>
                        <tr>
                            <th>Descrição Pt</th>
                            <td>{{$pdt->descriptionPt}}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>{{$pdt->status}}</td>
                        </tr>
                        <tr>
                            <th>Versão</th>
                            <td>{{$pdt->versionNumber}}</td>
                        </tr>
                        <tr>
                            <th>Revisão</th>
                            <td>{{$pdt->revisionNumber}}</td>
                        </tr>
                        <tr>
                            <th>Edição</th>
                            <td>{{$pdt->editionNumber}}</td>
                        </tr>
                        <tr>
                            <th>Data da versão</th>
                            <td>{{$pdt->dateOfVersion}}</td>
                        </tr>
                        <tr>
                            <th>Data de revisão</th>
                            <td>{{$pdt->dateOfRevision}}</td>
                        </tr>
                        @if($objectType)
                        <tr>
                            <th>ObjectType (Tipo de Construção)</th>
                            <td>
                                <strong>{{$objectType->constructionObjectNamePt}}</strong>
                                (EN: {{$objectType->constructionObjectNameEn}})<br/>
                                GUID: {{$objectType->GUID}}<br/>
                                Descrição: {{$objectType->descriptionPt}}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Grupos de Propriedades</th>
                            <td>
                                {{ count($groupsOfProperties) }} grupos
                                @if($masterPropertiesCount > 0)
                                <br/><small style="color: #666;">+ Grupos de Propriedades do Master Data Template</small>
                                @endif
                            </td>
                        </tr>
                    <tr>
    <th>Lista de versões anteriores</th>
    <td style="display: flex; border: none; flex-wrap: wrap;">
    @php
        $olderVersions = $pdtVersions
            ->filter(function($v) use ($pdt) { return ($v->versionNumber < $pdt->versionNumber) || ($v->versionNumber == $pdt->versionNumber && $v->revisionNumber < $pdt->revisionNumber); })
            ->sortByDesc('versionNumber')
            ->sortByDesc('revisionNumber');
    @endphp
        @forelse($olderVersions as $version)
        <form class="mb-3" action="{{ url('pdtview/' . $version->Id . '-' . $version->GUID) }}">
            <button class="btn-link" type="submit" style="margin-right: 5px;">
                V{{ $version->versionNumber }}.{{ $version->revisionNumber }}
            </button>
        </form>
        @empty
        <span>Nenhuma versão anterior</span>
        @endforelse
    </td>
</tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>