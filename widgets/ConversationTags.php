<?php


namespace humhub\modules\mail\widgets;


use humhub\components\Widget;
use humhub\libs\Html;
use humhub\modules\mail\models\MessageTag;
use Yii;

class ConversationTags extends Widget
{
    const ID = 'conversation-tags-root';

    public $message;

    public function run()
    {   
    }

}