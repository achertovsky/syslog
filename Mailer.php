<?php

namespace achertovsky\syslog;

use Yii;

class Mailer extends \yii\base\Component
{
    /** @var string */
    public $viewPath = '@views/syslog/mail';

    /** @var string|array Default: `Yii::$app->params['adminEmail']` OR `no-reply@example.com` */
    public $sender;

    /** @var string */
    public $summarySubject;

    /** @var \achertovsky\user\Module */
    protected $module;

    /**
     * @return string
     */
    public function getSummarySubject()
    {
        if ($this->summarySubject == null) {
            $this->setSummarySubject(Yii::t('syslog', 'Summary from {0}', Yii::$app->name));
        }

        return $this->summarySubject;
    }
    
    /**
     * @param string $summarySubject
     */
    public function setSummarySubject($summarySubject)
    {
        $this->summarySubject = $summarySubject;
    }

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        $this->module = Yii::$app->getModule('syslog');
        if (empty($this->module)) {
            throw new Exception("You forgot to enable Syslog module in application or renamed it.\n"
            . "Please, enable module or refer to this file and change module name.");
        }
    }

    /**
     * Sends summary an email
     * @param string  $email
     * @param array $summary
     * @return bool
     */
    public function sendSummaryMessage($email, $summary)
    {
        return $this->sendMessage($email,
            $this->getSummarySubject(),
            'summary',
            ['summary' => $summary]
        );
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $view
     * @param array  $params
     *
     * @return bool
     */
    protected function sendMessage($to, $subject, $view, $params = [])
    {
        /** @var \yii\mail\BaseMailer $mailer */
        $mailer = Yii::$app->mailer;
        $mailer->viewPath = $this->viewPath;
        $mailer->getView()->theme = Yii::$app->view->theme;

        if ($this->sender === null) {
            $this->sender = isset(Yii::$app->params['adminEmail']) ? Yii::$app->params['adminEmail'] : 'no-reply@example.com';
        }

        return $mailer->compose(['html' => $view, 'text' => 'text/' . $view], $params)
            ->setTo($to)
            ->setFrom($this->sender)
            ->setSubject($subject)
            ->send();
    }
}
