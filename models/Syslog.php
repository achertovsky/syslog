<?php

namespace achertovsky\syslog\models;

use Yii;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use modules\user\models\User;
use yii\base\Exception;

/**
 * This is the model class for table "syslog".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $issues
 * @property integer $log_source
 * @property integer $scanned_item
 * @property integer $created_at
 * @property integer $updated_at
 */
class Syslog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'syslog';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['log_source'], 'required'],
            [['issues'], 'string', 'min' => 0],
            [['log_source', 'created_at', 'updated_at', 'user_id', 'scanned_item'], 'integer', 'min' => 0]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'issues' => 'Issues',
            'log_source' => 'Log Source',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
    
    /** @inheritdoc */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => TimestampBehavior::className(),
            ],
        ]);
    }
    
    protected $module;
    protected $mailer;
    /** @inheritdoc */
    public function init() {
        parent::init();
        $this->module = Yii::$app->getModule('syslog');
        if (empty($this->module)) {
            throw new Exception("You forgot to enable Syslog module in application or renamed it.\n"
            . "Please, enable module or refer to this file and change module name.");
        }
        $this->mailer = new $this->module->mailer;
        if (empty($this->mailer)) {
            throw new Exception("Please, define mailer for Syslog module");
        }
    }
    
    /**
     * defines scanned_item
     * @var int
     */
    const ITEM_NOT_SCANNED = 0;
    const ITEM_SCANNED = 1;
    /**
     * defines log_source
     * @var int
     */
    const TYPE_UNDEFINED = 0;
    const TYPE_CRON = 1;
    const TYPE_FRONTEND = 2;
    public static function getTypeNameById($id)
    {
        switch ($id) {
            case 0:
                return 'Undefined';
            case 1:
                return 'Cron';
            case 2:
                return 'Frontend';
        }
    }
    

    public static function sendSummary($emails = [])
    {
        $log = new self();
        $module = $log->module;
        if ($module->sendToDev) {
            $emails['dev'] = $module->devEmail ? $module->devEmail : Yii::$app->params['devEmail'];
            if (empty($emails['dev'])) {
                throw new Exception('Define devEmail in Syslog module or in Yii::$app->params[\'devEmail\']');
            }
        }
        if ($module->sendToAdmin) {
            $emails['admin'] = $module->adminEmail ? $module->adminEmail : Yii::$app->params['adminEmail'];
            if (empty($emails['admin'])) {
                throw new Exception('Define adminEmail in Syslog module or in Yii::$app->params[\'adminEmail\']');
            }
        }
        
        $logs = Syslog::find()->where([
            'and',
            ['<', 'created_at', strtotime('today midnight')],
        ])->all();
        $summary = [];
        foreach ($logs as $log) {
            $attributes = $log->getAttributes();
            //teststring
            $type = self::getTypeNameById($attributes['log_source']);
            if (!empty($attributes['errors'])) {
                $summary[$attributes['user_id']][$type]['errors'] = $attributes['errors'];
            }
            $summary[$attributes['user_id']][$type]['items_scanned'] = $attributes['errors'];
        }
        
        return true;
    }
    
    /**
     * creates one level array from multidimensional
     * @param array $array
     * @return array
     */
    protected static function formatToOneLevelArray($array)
    {
        foreach ($array as $elem) {
            if (is_array($elem)) {
                $subArray = self::formatToOneLevelArray($elem);
                foreach ($subArray as $subElem) {
                    $resultArray[] = $subElem;
                }
                continue;
            }
            $resultArray[] = $elem;
        }
        return $resultArray;
    }
    
    /**
        * encodes errors to json and saves to DB
        * @param int $userId
        * @param string, array $errors
        * @param int $type
        * @return true
        */
    public static function log($errors = null, $message = null, $userId = 0, $type = self::TYPE_UNDEFINED)
    {
        if (is_string($errors)) {
            $temp = $errors;
            unset($errors);
            $errors[] = $temp;
        }
        if (is_string($message)) {
            $temp = $message;
            unset($message);
            $message[] = $temp;
        }
        if (is_array($message)) {
            $message = self::formatToOneLevelArray($message);
            $message = Json::encode($message);
        }
        if (is_array($errors)) {
            $errors = self::formatToOneLevelArray($errors);
            $errors = Json::encode($errors);
        }
        $log = new self();
        $log->setAttributes([
            'log_source' => $type,
            'issues' => $errors,
            'user_id' => $userId,
            'message' => $message,
        ]);
        if ($log->save()) {
            Yii::trace("Logged info:\n".var_export($log->getAttributes(), true), 'syslog');
            return true;
        } else {
            Yii::error("Logs save errors occured. Listing:\n".var_export($log->errors, true), 'syslog');
            return false;
        }
    }
}
