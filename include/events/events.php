<?php

namespace App\Events;

class Events {

    function measureRatioChanger (\Bitrix\Main\Event $event)
    {
        dump($event);
    }
}