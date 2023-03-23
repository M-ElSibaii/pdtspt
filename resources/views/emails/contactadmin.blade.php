@component('mail::message')
# Recebeu a seguinte pedido de contacto
<br>
<strong>Detalhes do pedido de contacto: </strong><br>
<strong>Nome: </strong>{{ $emailArray['name'] }} <br>
<strong>Email: </strong>{{ $emailArray['email'] }} <br>
<strong>Assunto: </strong>{{ $emailArray['subject'] }} <br>
<strong>Mensagem: </strong>{{ $emailArray['message'] }} <br>
<br>
<strong>Obrigado</strong><br>
<strong>Com os melhores cumprimentos</strong>

@endcomponent