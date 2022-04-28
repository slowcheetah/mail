<?php

declare(strict_types=1);

namespace humhub\modules\mail\widgets;

use humhub\widgets\JsWidget;

class MailConversationDummy extends JsWidget
{
    public $jsWidget = 'mail.conversationDummy';

    public function run()
    {
        return $this->render('mailConversationDummy');
    }
}
