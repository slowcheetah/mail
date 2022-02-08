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
     * @var array Style options for participants avatars
     */
    public $firstImageStyle = [];

    /**
     * @var array Style options for participants avatars
     */
    public $imagesStyle = [];

    /**
     * @var array Style options for participants avatars
     */
    public $oneImageStyle = [];

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
                if (!$result) {
                    $result .= Html::beginTag('div', $this->firstImageStyle);
                    $result .= Image::widget(['user' => $shuffledUsers[$imageUsersIds[$i]], 'width' => '40', 'link' => false]);
                    $result .= Html::endTag('div');
                } else {
                    $result .= Html::beginTag('div', $this->imagesStyle);
                    $result .= Image::widget(['user' => $shuffledUsers[$imageUsersIds[$i]], 'width' => '40', 'link' => false]);
                    $result .= Html::endTag('div');
                }
            }
        } elseif ($userCount == 2) {
            foreach ($this->users as $index => $user) {
                if (!$user->isCurrentUser()) {
                    $result .= Html::beginTag('div', $this->oneImageStyle);
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
}
