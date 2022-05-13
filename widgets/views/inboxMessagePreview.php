<?php

/**
 * Shows a  preview of given $userMessage (UserMessage).
 *
 * This can be the notification list or the message navigation
 */

use yii\helpers\Html;
use humhub\modules\mail\widgets\TimeAgo;
use humhub\libs\Helpers;
use humhub\modules\mail\helpers\Url;
use humhub\modules\user\widgets\Image;
use humhub\widgets\Label;


/* @var $userMessage \humhub\modules\mail\models\UserMessage */
/* @var $active bool */

$message = $userMessage->message;
$userCount = $message->getUsers()->count();
$participant = $message->getLastActiveParticipant();
$lastEntry = $message->lastEntry;
$users = $message->users;
$isNew = $userMessage->isUnread();
?>

<?php if ($lastEntry) : ?>
    <li data-message-preview="<?= $message->id ?>" class="messagePreviewEntry entry<?= $isNew ? ' unread' : ''?>" data-action-click="mail.notification.loadMessage" data-action-url="<?= Url::toMessenger($message)?>"  data-message-id="<?= $message->id ?>">
        <div class="mail-link">
            <div class="avatar">
                <?= Image::widget(['user' => $participant, 'width' => '40', 'link' => false])?>
                <?= Label::danger()->cssClass('new-message-badge')->style((!$isNew ? 'display:none' : '')) ?>
            </div>

            <div class="content">
                <h4 class="media-heading">
                    <?php
                    if ($userCount == 2) { // If conversation is between 2 users, then show target user's name
                        foreach ($message->users as $k => $user) {
                            if (!$user->isCurrentUser()) {
                                print '<a href="#" class="inbox-entry-title">' . Html::encode($user->displayName) . '</a>';
                            }
                        }
                    } else {
                        print '<a href="#" class="inbox-entry-title">' . Html::encode(Helpers::truncateText($message->title, 75)) . '</a>';
                    }
                    ?>
                    <?= TimeAgo::widget(['timestamp' => $message->updated_at]) ?>
                </h4>

                <p><?= $lastEntry->user->is(Yii::$app->user->getIdentity()) ? Yii::t('MailModule.base', 'You') : Html::encode($lastEntry->user->profile->firstname) ?>: <?= Html::encode($message->getPreview()) ?></p>
            </div>
        </div>
    </li>
<?php endif; ?>
