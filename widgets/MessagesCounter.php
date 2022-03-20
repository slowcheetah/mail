<?php

namespace humhub\modules\mail\widgets;

use humhub\components\Widget;
use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\mail\widgets\ConversationEntry;
use humhub\modules\user\models\User;
use Yii;

class MessagesCounter extends Widget
{
    /**
     * @var Message
     */
    public $message;

    /**
     * @var User
     */
    public $user;

    /**
     * @var integer
     */
    private $entryCount;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     * @throws \Throwable
     */
    public function run()
    {
        if (!$this->message) return false;
        $this->entryCount = $this->message->getUserMessage()->getNewEntriesCount($this->message);

        if ($this->entryCount > 0) {
            return '
                    <div class="chat-count">
                        <div class="chat-badge-messages">' . $this->entryCount . '</div>
                    </div>';
        } else {
            return false;
        }
    }

}
