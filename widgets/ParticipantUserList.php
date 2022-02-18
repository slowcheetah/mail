<?php

namespace humhub\modules\mail\widgets;

use humhub\components\Widget;
use humhub\libs\Helpers;
use humhub\libs\Html;
use humhub\modules\mail\helpers\Url;
use humhub\modules\mail\models\Message;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\Image;
use Yii;

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
     * @var array Style options for conversation participants counter
     */
    public $participantsCounterStyle = [];

    /**
     * @var array Style options for target user status
     */
    public $targetStatusStyle = [];

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
            $result = Html::beginTag('a', ['href' => $targetUser->getUrl()]);
            $result .= $targetUser->displayName;
            //$result .= Html::beginTag('div', $this->targetStatusStyle);
            //$result .= 'Был(а) в сети когда-то';
            //$result .= Html::endTag('div');
            $result .= Html::endTag('a');
            $result .= $this->getOccupation($targetUser);
        } else {
            $result = Html::beginTag('a', array_merge($this->getDefaultLinkOptions(), $this->linkOptions));
            $result .= \yii\helpers\Html::encode(Helpers::truncateText($this->message->title, 75));
            $result .= Html::beginTag('div', $this->participantsCounterStyle);
            $result .= Yii::t(
                'MusztabelModule.textbycount',
                '{n, plural, =0{Нет участников} =1{1 участник} one{# участник} few{# участника} many{# участников} other{# участника}}',
                ['n' => count($this->users)]
            );
            $result .= Html::endTag('div');
            $result .= Html::endTag('a');
        }

        return $result;
    }

    private function getDefaultLinkOptions()
    {
        return  [
            'href'=> '#',
            'data-action-click' => 'ui.modal.load',
            'data-action-url' => Url::toConversationUserList($this->message),
            'style' => ['color' =>  $this->view->theme->variable('info')]
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

    private function getOccupation($user)
    {
        $occupation = '';
        if (Yii::$app->getModule('rocketcore') && class_exists('\humhub\modules\rocketcore\widgets\UserOccupation')) {
            $occupation = \humhub\modules\rocketcore\widgets\UserOccupation::widget(['model' => $user]);
        }
        return $occupation;
    }
}
