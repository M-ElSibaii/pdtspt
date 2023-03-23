@component('mail::message')
# foi acrescentado um comentário a um PDT
<br>
<strong>detalhes dos comentários: </strong><br>
<strong>Nome: </strong>{{ Auth::user()->name }} <br>
<strong>PDT: </strong>{{ $pdtName }} <br>
<strong>Propriedade: </strong>{{ $propertyName }} <br>
<strong>Comentários: </strong>{{ $commentbody }} <br>
<br>
<strong>Obrigado</strong><br>
<strong>Com os melhores cumprimentos</strong>

@endcomponent