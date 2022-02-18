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

class ParticipantImages extends Widget
{
    /**
     * @var Message
     */
    public $message;

    /**
     * @var User first user
     */
    public $user;

    /**
     * @var array Users of conversation
     */
    public $users;

    /**
     * @var array Style options for participants avatars
     */
    public $imagesGroupStyle = [];

    /**
     * @var array Style options for conversation participant first avatar
     */
    public $firstImageStyle = [];

    /**
     * @var array Style options for conversation participant first avatar if participant is disabled
     */
    public $firstImageStyleDisabled = [];

    /**
     * @var array Style options for conversation participants other avatars
     */
    public $imagesStyle = [];

    /**
     * @var array Style options for conversation participants other avatars if participant is disabled
     */
    public $imagesStyleDisabled = [];

    /**
     * @var array Style options for target user avatar in 1-to-1 chat
     */
    public $oneImageStyle = [];

    /**
     * @var array Style options for target user avatar in 1-to-1 chat if user is disabled
     */
    public $oneImageStyleDisabled = [];

    /**
     * @var integer Max avatar items (including show more button) to display, should be > 2
     */
    public $maxImageEntries = 3;

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
            $result = Html::beginTag('div', $this->imagesGroupStyle);
            $result .= Html::beginTag('a', ['href' => $targetUser->getUrl()]);
            $result .= $this->renderUserImages();
            $result .= Html::endTag('a');
            $result .= Html::endTag('div');
        } else {
            $result = Html::beginTag('div', $this->imagesGroupStyle);
            $result .= Html::beginTag('a', array_merge($this->getDefaultLinkOptions(), $this->linkOptions));
            $result .= $this->renderUserImages();
            $result .= Html::endTag('a');
            $result .= Html::endTag('div');
        }

        return $result;
    }

    private function renderUserImages() {
        $userCount = count($this->users);
        $shuffledUsers = $this->users;
        shuffle($shuffledUsers);
        $result = '';

        if ($userCount > 2) {
            $imageUsersIds = array_rand($this->users, $this->maxImageEntries);
            foreach ($imageUsersIds as $i => $v) {
                $randomUser = $shuffledUsers[$imageUsersIds[$i]];
                if (!$result) {
                    $result .= $this->isDisabled($randomUser) ? Html::beginTag('div', $this->firstImageStyleDisabled) : Html::beginTag('div', $this->firstImageStyle);
                    $result .= Image::widget(['user' => $randomUser, 'width' => '40', 'link' => false]);
                    $result .= Html::endTag('div');
                } else {
                    $result .= $this->isDisabled($randomUser) ? Html::beginTag('div', $this->imagesStyleDisabled) : Html::beginTag('div', $this->imagesStyle);
                    $result .= Image::widget(['user' => $randomUser, 'width' => '40', 'link' => false]);
                    $result .= Html::endTag('div');
                }
            }
        } elseif ($userCount == 2) {
            foreach ($this->users as $index => $user) {
                if (!$user->isCurrentUser()) {
                    $result .= $this->isDisabled($user) ? Html::beginTag('div', $this->oneImageStyleDisabled) : Html::beginTag('div', $this->oneImageStyle);
                    $result .= Image::widget(['user' => $user, 'width' => '40', 'link' => false]);
                    $result .= Html::endTag('div');
                }
            }
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

    private function isDisabled($user)
    {
        $userDisabled = '';
        if (Yii::$app->getModule('musztabel')) {
            $userDisabled = class_exists('\humhub\modules\musztabel\widgets\PattyStatus')
                ? \humhub\modules\musztabel\widgets\PattyStatus::widget(['model' => $user])
                : false;
        }
        return $userDisabled ? true : false;
    }
}
