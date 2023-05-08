<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyMailAdmin extends Mailable
{
    use Queueable, SerializesModels;

    //public $userName;
    public $pdtName;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($pdtName)
    {

        $this->pdtName = $pdtName;
        //  $this->userName = $userName;

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
            subject: 'As respostas foram acrescentadas ao inquérito de um PDT',
        );
    }

    public function build()
    {
        return $this->markdown('emails.surveyadmin');
    }
    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
