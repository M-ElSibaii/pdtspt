<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class=''>
                <div class="flex-none inline">
                    <h1 class="flex-none inline">{{ Lg::f($pdt, 'pdtName') }}</h1>
                    <p class="flex-none inline"> - <x-version-badge :version="$pdt->versionNumber" :revision="$pdt->revisionNumber" /></p>
                    <x-status-badge :status="$pdt->status" />
                    @if (Auth::check() && Auth::user()->isAdmin == 1 && $pdt->status == 'Active')
                        <a href="{{ route('admin.pdt.activeEdit', ['pdt' => $pdt->Id]) }}" class="btn btn-secondary" style="margin-left:8px;">Edit (limited)</a>
                        <a href="{{ route('admin.pdt.newVersion', ['pdt' => $pdt->Id]) }}" class="btn btn-secondary" style="margin-left:8px;">Create new version</a>
                    @endif
                    @if (Auth::check() && Auth::user()->isAdmin == 1 && $pdt->status == 'Preview')
                        <a href="{{ route('admin.previews.editor', ['pdt' => $pdt->Id]) }}" class="btn btn-secondary" style="margin-left:8px;">Edit / Publish / Delete (Preview)</a>
                    @endif
                </div>
            </div>
            <div class='py-2'>
                <h3 class='py-2'>{{ Lg::t('Atributos do Modelo de Dados de Produto baseado em EN ISO 23387') }}</h3>

                <table id='tblprop' cellpadding='0' cellspacing='0'>
                    <tbody>
                            <tr>
                                <th class="lg:w-1/4 md:w-1/4 sm:w-1/2">GUID</th>
                                <td class="lg:w-3/4 md:w-3/4 sm:w-1/2">{{$pdt->GUID}}</td>
                            </tr>
                            <x-uri-row entity="dt" :record="$pdt" />
                            <tr>
                                <th>{{ Lg::t('Nome') }}</th>
                                <td>{{ Lg::f($pdt, 'pdtName') }}</td>
                            </tr>
                            <tr>
                                <th>{{ Lg::t('Descrição') }}</th>
                                <td>{{ Lg::f($pdt, 'description') }}</td>
                            </tr>
                            <tr>
                                <th>{{ Lg::t('Estado') }}</th>
                                <td>{{$pdt->status}}</td>
                            </tr>
                            <tr>
                                <th>{{ Lg::t('Versão') }}</th>
                                <td>{{$pdt->versionNumber}}</td>
                            </tr>
                            <tr>
                                <th>{{ Lg::t('Revisão (número)') }}</th>
                                <td>{{$pdt->revisionNumber}}</td>
                            </tr>
                            <tr>
                                <th>{{ Lg::t('Data da versão') }}</th>
                                <td>{{$pdt->dateOfVersion}}</td>
                            </tr>
                            <tr>
                                <th>{{ Lg::t('Data de revisão') }}</th>
                                <td>{{$pdt->dateOfRevision}}</td>
                            </tr>
                            @if($objectType)
                            <tr>
                                <th>{{ Lg::t('ObjectType (Tipo de Objeto)') }}</th>
                                <td>
                                    <a href="{{ Uri::buildLink('class', $objectType) }}"><strong>{{ Lg::f($objectType, 'constructionObjectName') }}</strong></a><br/>
                                    GUID: {{$objectType->GUID}}<br/>
                                    {{ Lg::t('Descrição') }}: {{ Lg::f($objectType, 'description') }}
                                </td>
                            </tr>
                            @endif
                            @if(!empty($subtypeParents))
                            <tr>
                                <th>{{ Lg::t('Subtipo de (Object Type)') }}</th>
                                <td>
                                    @foreach($subtypeParents as $parent)
                                        <a href="{{ Uri::buildLink('dt', $parent['record']) }}">
                                            {{ Lg::f($parent['record'], 'pdtName') }}
                                        </a>@if(!$loop->last), @endif
                                    @endforeach
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <th>{{ Lg::t('Grupos de Propriedades') }}</th>
                                <td>
                                    {{ Lg::t(':count grupos', ['count' => count($groupsOfProperties)]) }}
                                    @if($inheritedGroupCount > 0)
                                    <br/><small style="color: #666;">{{ Lg::t('(inclui :count herdado(s) de supertipos — IsSubtypeOf)', ['count' => $inheritedGroupCount]) }}</small>
                                    @endif
                                </td>
                            </tr>
                          <tr>
                             <th>{{ Lg::t('Lista de versões anteriores') }}</th>
                                <td style="display: flex; border: none; flex-wrap: wrap;">
                                @php
                                    $olderVersions = $pdtVersions
                                        ->filter(function($v) use ($pdt) { return ($v->versionNumber < $pdt->versionNumber) || ($v->versionNumber == $pdt->versionNumber && $v->revisionNumber < $pdt->revisionNumber); })
                                        ->sortByDesc('versionNumber')
                                        ->sortByDesc('revisionNumber');
                                @endphp
                                    @forelse($olderVersions as $version)
                                    {{-- Earlier versions are reached through the pinned identifier. --}}
                                    <a class="btn-link" style="margin-right: 5px;" href="{{ Uri::buildLink('dt', $version, (int) $version->versionNumber) }}">
                                        V{{ $version->versionNumber }}.{{ $version->revisionNumber }}
                                    </a>
                                    @empty
                                    <span>{{ Lg::t('Nenhuma versão anterior') }}</span>
                                    @endforelse
                                </td>
                          </tr>

                    </tbody>
                </table>

                {{-- Relationship EDITING lives in the limited-edit / preview editors, not on
                     this public view. This page only DISPLAYS the subtype line (read-only). --}}

                <br>
                     <form class="mb-3" action="{{ route('pdtsdownload', ['pdtID' => $pdt->Id]) }}">
                                    <x-button-primary-pdts type="submit" title="{{ Lg::t('Ver PDT') }}" />
                                </form>
            </div>
        </div>
    </div>
</x-app-layout>
