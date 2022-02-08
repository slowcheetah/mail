<?php


namespace humhub\modules\mail\widgets;


use humhub\components\Widget;
use humhub\libs\Helpers;
use humhub\libs\Html;
use humhub\modules\mail\helpers\Url;
use humhub\modules\mail\models\Message;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\Image;use Yii;

class ParticipantUserList extends Widget
{
    /**
     * @var Message
     */
    public $message;

    /**
     * @var array Users of conversation
     */
    public $users;

    /**
     * @var array Style option for participants names
     */
    public $namesOptions = [];

    /**
     * @var array
     */
    public $linkOptions = [];

    public function run()
    {
        if(empty($this->message->users)) {
            return '';
        }

        $this->users = $this->message->users;

        if (count($this->users) == 2) {
            $targetUser = $this->getFirstUser();
            $result = Html::beginTag('div', $this->namesOptions);
            $result .= Html::beginTag('a', ['href' => $targetUser->getUrl()]);
            $result .= $targetUser->displayName;
            $result .= Html::endTag('a');
            $result .= Html::endTag('div');
        } else {
            $result = Html::beginTag('div', $this->namesOptions);
            $result .= Html::beginTag('a', array_merge($this->getDefaultLinkOptions(), $this->linkOptions));
            $result .= \yii\helpers\Html::encode(Helpers::truncateText($this->message->title, 75));
            $result .= Html::endTag('a');
            $result .= Html::endTag('div');
        }

        return $result;
    }

    private function getDefaultLinkOptions()
    {
        return  [
            'href'=> '',
            'data-action-click' => 'ui.modal.load',
            'data-action-url' => Url::toConversationUserList($this->message)
        ];
    }

    private function getFirstUser()
    {
        foreach ($this->users as $participant) {
            if (!$participant->isCurrentUser()) {
                return $participant;
            }
        }

        return $this->message->getOriginator();
    }
}
