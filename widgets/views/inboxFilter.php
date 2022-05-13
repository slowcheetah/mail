<?php


use humhub\libs\Html;
use humhub\modules\mail\helpers\Url;
use humhub\modules\mail\models\forms\InboxFilterForm;
use humhub\modules\mail\widgets\ConversationTagPicker;
use humhub\modules\mail\widgets\ManageTagsLink;
use humhub\modules\ui\filter\widgets\PickerFilterInput;
use humhub\modules\ui\filter\widgets\TextFilterInput;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\ui\view\components\View;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\Link;


/* @var $this View */
/* @var $options array */
/* @var $model InboxFilterForm# */
?>

<?= Html::beginTag('div', $options) ?>
<div id="mail-filter-menu" class="clearfix">
    <?php $filterForm = ActiveForm::begin() ?>
    <?= TextFilterInput::widget(['id' => 'term', 'category' => 'term', 'options' => ['placeholder' => Yii::t('custom', 'Искать в чатах')]]) ?>
    <?php ActiveForm::end() ?>
</div>
<?= Html::endTag('div') ?>
