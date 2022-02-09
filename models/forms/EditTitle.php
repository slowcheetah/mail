<?php

namespace humhub\modules\mail\models\forms;

use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageTag;
use Yii;
use yii\base\Model;
use yii\web\ForbiddenHttpException;

class EditTitle extends Model
{
    /**
     * @var MessageTag
     */
    public $title;

    /**
     * @var Message new message
     */
    public $messageInstance;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            ['title', 'string', 'min' => 4, 'max' => 50],
            ['title', 'trim'],
        ];
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return [
            'title' => Yii::t('MailModule.base', 'Conversation title'),
        ];
    }

    /**
     * Sends Change Username E-Mail
     */
    public function saveTitle()
    {
        $this->messageInstance->title = $this->title;
        $this->messageInstance->save();
        return true;
    }
}
