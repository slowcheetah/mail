<?php

declare(strict_types=1);

namespace humhub\modules\mail\assets;

use yii\web\AssetBundle;
use yii\web\View;

class MailUnseenAsset extends AssetBundle
{
    /**
     * @inheritDoc
     */
    public $sourcePath = '@mail/resources/js';

    /**
     * @inheritDoc
     */
    public $js = [
        'humhub.mail.unseen.js'
    ];

    /**
     * @inheritDoc
     */
    public $jsOptions = ['position' => View::POS_END];

    /**
     * @inheritDoc
     */
    public $publishOptions = [
        'forceCopy' => true
    ];
}
