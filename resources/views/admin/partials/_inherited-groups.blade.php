{{-- Read-only list of the groups a template inherits (IsSubtypeOf ancestors, master), shown in
     the admin editors under the template's own groups so the origin filter covers them too.
     Expects $inheritedGroups from PdtInheritanceService::inheritedGroupsWithProperties().
     Nothing here is editable: inherited groups are edited in the template they come from. --}}
@if (!empty($inheritedGroups))
    <section class="adm inherited-groups" style="margin-top:18px;">
        <h2 style="margin:0 2px 8px;">{{ Lg::t('Grupos herdados (só leitura)') }}</h2>
        @foreach ($inheritedGroups as $item)
            @php $g = $item['group']; $o = $item['origin']; $n = count($item['properties']); @endphp
            <details class="adm-card" data-origin="{{ $o['key'] }}" style="margin:0 0 10px; border-left:6px solid {{ $o['color'] }};">
                <summary style="cursor:pointer; padding:10px 14px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                    <span class="origin-swatch" style="background: {{ $o['color'] }};" aria-hidden="true"></span>
                    <strong>{{ $g->gopNameEn ?: '—' }}</strong>
                    <span class="adm-muted">/ {{ $g->gopNamePt ?: '—' }}</span>
                    <span class="adm-pill is-neutral">{{ $o['label'] }}</span>
                    <span class="adm-small adm-muted">{{ $n }} {{ $n === 1 ? 'property' : 'properties' }}</span>
                </summary>
                <div style="padding:0 14px 12px;">
                    <p class="adm-small adm-muted" style="margin:0 0 6px;">{{ Lg::t('Estes grupos vêm de :source. Edite-os no modelo de onde vêm.', ['source' => $o['label']]) }}</p>
                    @if ($n)
                        <table class="adm-small" style="width:100%; border-collapse:collapse;">
                            <thead><tr style="text-align:left;"><th>{{ Lg::t('Propriedade') }}</th><th>Data type</th><th>{{ Lg::t('Unidade') }}</th></tr></thead>
                            <tbody>
                                @foreach ($item['properties'] as $p)
                                    <tr style="border-top:1px solid #e2e8f0;">
                                        <td>{{ $p->nameEn }} <span class="adm-muted">/ {{ $p->namePt }}</span></td>
                                        <td>{{ $p->dataType }}</td>
                                        <td>{{ $p->units ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="adm-small adm-muted" style="margin:0;">{{ Lg::t('Sem propriedades') }}</p>
                    @endif
                </div>
            </details>
        @endforeach
    </section>
@endif
