<?php

namespace achertovsky\syslog;

use Yii;

class Module extends \yii\base\Module
{
    /**
     * defines mailer class for this module.
     * @var string
     */
    public $mailer = 'achertovsky\syslog\Mailer';
    /**
     * defines is send summary to developer or not
     * by default uses Yii::$app->params['devMail'] if exists
     * if defined uses $devMail
     */
    public $sendToDev = false;
    public $devEmail = '';
    /**
     * defines is send summary to admin or not
     * by default uses Yii::$app->params['adminMail'] if exists
     * if defined uses $adminMail
     */
    public $sendToAdmin = false;
    public $adminEmail = '';
}
