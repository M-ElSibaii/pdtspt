<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class FeedbackMailUsers extends Mailable
{
    use Queueable, SerializesModels;
    public $commentbody;
    //public $userName;
    public $pdtName;
    public $propertyName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($commentbody, $pdtName, $propertyName)
    {
        $this->commentbody = $commentbody;
        $this->pdtName = $pdtName;
        // $this->userName = $userName;
        $this->propertyName = $propertyName;
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
            subject: 'Feedback was added on a PDT you commented on',
        );
    }

    public function build()
    {
        return $this->markdown('emails.feedbackuser');
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
