@component('mail::message')
# You recieved the following query
<br>
<strong>Contact request details: </strong><br>
<strong>Name: </strong>{{ $emailArray['name'] }} <br>
<strong>Email: </strong>{{ $emailArray['email'] }} <br>
<strong>Phone: </strong>{{ $emailArray['phone'] }} <br>
<strong>Subject: </strong>{{ $emailArray['subject'] }} <br>
<strong>Message: </strong>{{ $emailArray['message'] }} <br>
<br>
<strong>Thank you</strong><br>
<strong>Best regards</strong>

@endcomponent