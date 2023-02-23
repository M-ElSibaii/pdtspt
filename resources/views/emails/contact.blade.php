@component('mail::message')
# Thank you for contacting us
<strong>We will reply to your message shortly</strong><br>
<strong>Best regards</strong><br>
<br>
<strong>Contact request details: </strong><br>
<strong>Name: </strong>{{ $emailArray['name'] }} <br>
<strong>Email: </strong>{{ $emailArray['email'] }} <br>
<strong>Phone: </strong>{{ $emailArray['phone'] }} <br>
<strong>Subject: </strong>{{ $emailArray['subject'] }} <br>
<strong>Message: </strong>{{ $emailArray['message'] }} <br>
<br>
@endcomponent