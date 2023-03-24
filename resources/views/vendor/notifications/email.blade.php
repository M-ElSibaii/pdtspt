@component('mail::message')
# Ola!
<strong>Por favor clique no botão abaixo para verificar o seu endereço de correio electrónico.</strong><br>

{{-- Action Button --}}
@isset($actionText)
<?php
$color = match ($level) {
    'success', 'error' => $level,
    default => 'primary',
};
?>
<x-mail::button :url="$actionUrl" :color="$color">
    {{ $actionText }}
</x-mail::button>
@endisset

@isset($actionText)
<x-slot:subcopy>
    @lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
    'actionText' => $actionText,
    ]
    ) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
@endcomponent