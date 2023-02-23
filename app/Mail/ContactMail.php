<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailArray;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($emailArray)
    {
        $this->emailArray = $emailArray;
    }
    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: new Address('pdts.portugal@gmail.com', 'PDTs.pt'),
            subject: 'We recieved your query'
        );
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.contact');
    }
}
