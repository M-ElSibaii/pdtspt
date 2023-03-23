<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMailAdmin extends Mailable
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
            subject: 'Recebeu um pedido de contacto',
        );
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.contactadmin');
    }
}
