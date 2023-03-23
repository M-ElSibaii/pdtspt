@component('mail::message')
# Foi acrescentado um comentário a um PDT sobre o qual comentou
<br>
<strong>Detalhes do comentário: </strong><br>
<strong>PDT: </strong>{{ $pdtName }} <br>
<strong>propriedade: </strong>{{ $propertyName }} <br>
<strong>comentário: </strong>{{ $commentbody }} <br>
<br>
<strong>Obrigado</strong><br>
<strong>Com os melhores cumprimentos</strong>

@endcomponent