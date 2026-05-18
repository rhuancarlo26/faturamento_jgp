<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DavStatusMail extends Mailable
{
    public $dav;

    public function __construct($dav)
    {
        $this->dav = $dav;
    }

    public function build()
    {
        return $this->subject('Atualização de DAV')
            ->view('emails.dav-status');
    }
}