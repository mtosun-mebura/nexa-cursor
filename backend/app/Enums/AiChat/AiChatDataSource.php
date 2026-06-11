<?php

namespace App\Enums\AiChat;

enum AiChatDataSource: string
{
    case Rag = 'rag';
    case Sql = 'sql';
    case PublicRates = 'public_rates';
    case Quote = 'quote';
    case Denied = 'denied';
    case Blocked = 'blocked';
}
