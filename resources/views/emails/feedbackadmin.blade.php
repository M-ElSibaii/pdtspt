@component('mail::message')
# Feedback was added to a PDT
<br>
<strong>Feedback details: </strong><br>
<strong>user name: </strong>{{ Auth::user()->name }} <br>
<strong>PDT: </strong>{{ $pdtName }} <br>
<strong>property: </strong>{{ $propertyName }} <br>
<strong>feedback: </strong>{{ $commentbody }} <br>
<br>
<strong>Thank you</strong><br>
<strong>Best regards</strong>

@endcomponent