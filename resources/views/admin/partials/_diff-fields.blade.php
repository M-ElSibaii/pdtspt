{{-- Field-level changes as old → new rows. Expects $changes ([field => ['old','new']]) and
     $labels ([field => human label]). Empty values show as "(empty)". --}}
@if (!empty($changes))
    <table class="adm-table" style="margin-top:6px;">
        <tbody>
            @foreach ($changes as $field => $c)
                <tr>
                    <td style="width:200px; vertical-align:top;" class="adm-small adm-muted">{{ $labels[$field] ?? $field }}</td>
                    <td style="vertical-align:top;">
                        <span class="adm-diff-old">{{ \Illuminate\Support\Str::limit((string) ($c['old'] ?? ''), 300) ?: '(empty)' }}</span>
                        <span class="adm-muted">→</span>
                        <span class="adm-diff-new">{{ \Illuminate\Support\Str::limit((string) ($c['new'] ?? ''), 300) ?: '(empty)' }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
