<?php

use humhub\modules\mail\helpers\Url;
use humhub\modules\mail\models\forms\ConversationTagsForm;
use humhub\modules\mail\widgets\ConversationTagPicker;
use humhub\modules\ui\view\components\View;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use yii\bootstrap\ActiveForm;

/* @var $this View */
/* @var $model ConversationTagsForm */

?>

<?php ModalDialog::begin(['header' => Yii::t('MailModule.base', 'Edit conversation title')]) ?>
<?php $form = ActiveForm::begin() ?>
    <div class="modal-body">
        <?= $form->field($model, 'title')->textInput()->label(false) ?>
    </div>
    <div class="modal-footer">
        <?= ModalButton::save(Yii::t('base', 'Save'))->submit()?>

        <?= ModalButton::cancel() ?>
    </div>
<?php ActiveForm::end() ?>
<?php ModalDialog::end() ?>
