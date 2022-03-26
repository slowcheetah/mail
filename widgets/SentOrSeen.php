<?php

namespace humhub\modules\mail\widgets;

use humhub\components\Widget;
use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\mail\widgets\ConversationEntry;
use Yii;

class SentOrSeen extends Widget
{
    /**
     * @var MessageEntry
     */
    public $entry;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var Message
     */
    private $entrySender;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     * @throws \Throwable
     */
    public function run()
    {
        if (!$this->entry) return false;

        $this->message = $this->entry->message;
        $usersCount = $this->message->getUsers()->count();
        $this->entrySender = $this->entry->user;

        if (($usersCount == 2) && ($this->entrySender->is(Yii::$app->user->getIdentity()))) {
            $receiver = $this->getReceivers();
            if (($this->message->lastEntry->id !== $this->entry->id) && !$this->message->lastEntry->user->is(Yii::$app->user->getIdentity())) {
                return '
                        <div class="marked">
                            <div class="icon"><svg width="16" height="16" viewBox="0 0 14 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.8293 0.623719C14.0371 0.80556 14.0581 1.12144 13.8763 1.32926L6.87629 9.32926C6.78516 9.4334 6.65495 9.49512 6.51664 9.49973C6.37833 9.50433 6.2443 9.45141 6.14645 9.35356L3.14645 6.35356C2.95118 6.1583 2.95118 5.84172 3.14645 5.64645C3.34171 5.45119 3.65829 5.45119 3.85355 5.64645L6.47565 8.26855L13.1237 0.670755C13.3056 0.462936 13.6214 0.441877 13.8293 0.623719ZM0.146447 5.64645C0.341709 5.45119 0.658291 5.45119 0.853553 5.64645L3.85355 8.64645C4.04882 8.84172 4.04882 9.1583 3.85355 9.35356C3.65829 9.54882 3.34171 9.54882 3.14645 9.35356L0.146447 6.35356C-0.0488155 6.1583 -0.0488155 5.84172 0.146447 5.64645Z" style="fill:rgb(4,82,105);"/></svg></div>
                        </div>';
            } elseif ($this->message->getUserMessage($receiver->id)->isUnreadEntry($this->entry, $receiver->id)) {
                return '
                        <div class="marked">
                            <div class="icon"><svg width="16" height="16" viewBox="0 0 14 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><path d="M13.829,0.624C14.037,0.806 14.058,1.121 13.876,1.329L6.876,9.329C6.785,9.433 6.655,9.495 6.517,9.5C6.378,9.504 6.244,9.451 6.146,9.354L3.146,6.354C2.951,6.158 2.951,5.842 3.146,5.646C3.342,5.451 3.658,5.451 3.854,5.646L6.476,8.269L13.124,0.671C13.306,0.463 13.621,0.442 13.829,0.624Z" style="fill:rgb(4,82,105);"/></svg></div>
                        </div>';
            } else {
                return '
                        <div class="marked">
                            <div class="icon"><svg width="16" height="16" viewBox="0 0 14 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.8293 0.623719C14.0371 0.80556 14.0581 1.12144 13.8763 1.32926L6.87629 9.32926C6.78516 9.4334 6.65495 9.49512 6.51664 9.49973C6.37833 9.50433 6.2443 9.45141 6.14645 9.35356L3.14645 6.35356C2.95118 6.1583 2.95118 5.84172 3.14645 5.64645C3.34171 5.45119 3.65829 5.45119 3.85355 5.64645L6.47565 8.26855L13.1237 0.670755C13.3056 0.462936 13.6214 0.441877 13.8293 0.623719ZM0.146447 5.64645C0.341709 5.45119 0.658291 5.45119 0.853553 5.64645L3.85355 8.64645C4.04882 8.84172 4.04882 9.1583 3.85355 9.35356C3.65829 9.54882 3.34171 9.54882 3.14645 9.35356L0.146447 6.35356C-0.0488155 6.1583 -0.0488155 5.84172 0.146447 5.64645Z" style="fill:rgb(4,82,105);"/></svg></div>
                        </div>';
            }
        } else {
            return false;
        }
    }

    private function getReceivers() {
        $receivers = [];
        foreach ($this->message->getUsers()->all() as $k => $v) {
            if ($v->getId() != Yii::$app->user->id) {
                $receivers = $v;
            }
        }
        return $receivers;
    }

}
