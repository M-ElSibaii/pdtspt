@component('mail::message')
# Obrigado por nos contactar
<strong>Em breve responderemos à sua mensagem</strong><br>
<strong>Com os melhores cumprimentos</strong>
<br>
<strong>Detalhes do pedido de contacto: </strong><br>
<strong>Nome: </strong>{{ $emailArray['name'] }} <br>
<strong>Email: </strong>{{ $emailArray['email'] }} <br>
<strong>Assunto: </strong>{{ $emailArray['subject'] }} <br>
<strong>Mensagem: </strong>{{ $emailArray['message'] }} <br>
<br>
@endcomponent