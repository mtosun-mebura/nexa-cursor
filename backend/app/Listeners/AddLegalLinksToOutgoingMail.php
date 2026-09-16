<?php

namespace App\Listeners;

use App\Support\NexaLegalLinks;
use Illuminate\Mail\Events\MessageSending;

class AddLegalLinksToOutgoingMail
{
    public function handle(MessageSending $event): void
    {
        $message = $event->message;
        $html = $message->getHtmlBody();
        if (is_string($html) && $html !== '') {
            $message->html(NexaLegalLinks::ensureHtmlFooter($html));
        }

        $text = $message->getTextBody();
        if (is_string($text) && $text !== '') {
            $message->text(NexaLegalLinks::ensureTextFooter($text));
        }
    }
}
