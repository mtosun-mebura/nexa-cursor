<?php

namespace App\Enums\AiChat;

enum AiChatResponseMode: string
{
    case List = 'list';
    case Count = 'count';
    case Summary = 'summary';
}
