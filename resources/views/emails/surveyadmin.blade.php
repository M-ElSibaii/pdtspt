@component('mail::message')
# As respostas foram acrescentadas ao inquérito de um PDT
<br>
<strong>Nome: </strong>{{ Auth::user()->name }} <br>
<strong>PDT: </strong>{{ $pdtName }} <br>
<br>
<strong>Obrigado</strong><br>
<strong>Com os melhores cumprimentos</strong>

@endcomponent