<?php

namespace achertovsky\syslog\models;

use Yii;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use modules\user\models\User;
use yii\base\Exception;
use yii\db\Expression;

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
    public $className = 'achertovsky\syslog\models\Syslog';
    
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
            [['issues', 'message'], 'string', 'min' => 0],
            [['log_source', 'created_at', 'updated_at', 'user_id'], 'integer', 'min' => 0],
            ['sended', 'boolean'],
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
     * @var int
     */
    const MAIL_SENDED = 1;
    const MAIL_NOT_SENDED = 0;
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
    
    /**
     * sends emails for defined emails, admin, developer
     * @param array $emails
     * @throws Exception
     */
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
        
        $logs = self::find()->where([
            'and',
            ['<', 'created_at', strtotime('today midnight')],
            ['=', 'sended', self::MAIL_NOT_SENDED],
        ])->indexBy('id')->all();
        self::updateAll([
            'sended' => self::MAIL_SENDED,
        ], [
            'and',
            ['in', 'id', array_keys($logs)],
        ]);
        //get calls
        $callsIds = ApiCall::find()->where([
            'and',
            ['<', 'created_at', strtotime('today midnight')],
            ['=', 'sended', Syslog::MAIL_NOT_SENDED],
        ])->indexBy('id')->all();
        $calls = ApiCall::find()->select([
            'id',
            'COUNT(call_name) as count',
            'call_name',
            'call_to',
        ])->where([
            'and',
            ['in', 'id', array_keys($callsIds)]
        ])->groupBy('call_name')->indexBy('id')->all();
        $callsStatistic = [];
        foreach ($calls as $key => $call) {
            $callsStatistic[$key]['call_quantity'] = $call->count;
            $callsStatistic[$key]['call_name'] = $call->call_name;
            $callsStatistic[$key]['call_to'] = $call->call_to;
        }
        //update calls
        ApiCall::updateAll([
            'sended' => self::MAIL_SENDED,
        ], [
            'and',
            ['in', 'id', array_keys($callsIds)],
        ]);
        $summary = [];
        foreach ($logs as $key => $log) {
            $attributes = $log->getAttributes();
            //decode JSON fieds for layout
            $attributes['issues'] = Json::decode($attributes['issues']);
            $attributes['message'] = Json::decode($attributes['message']);
            $attributes['log_source'] = self::getTypeNameById($attributes['log_source']);
            $summary[] = $attributes;
        }
        foreach ($emails as $email) {
            $log->mailer->sendSummaryMessage($email, $summary);
        }
    }
    
    /**
     * creates one level array from multidimensional
     * @param array $array
     * @return array
     */
    protected static function formatToOneLevelArray($array)
    {
        try {
            $resultArray = [];
            foreach ($array as $key => $elem) {
                if (is_object($elem)) {
                    $elem = (array)$elem;
                }
                if (is_array($elem)) {
                    $subArray = self::formatToOneLevelArray($elem);
                    foreach ($subArray as $subKey => $subElem) {
                        $resultArray[] = Syslog::resultArrayFilter($subKey, $subElem, count($subArray));
                    }
                    continue;
                }
                $resultArray[] = Syslog::resultArrayFilter($key, $elem, count($array));
            }
            return $resultArray;
        } catch (\Exception $ex) {
            Yii::error($ex->getMessage().' '.$ex->getTraceAsString().' '.var_export($array, true));
        }
    }
    
    /**
     * Function that makes result of formatOneLevelArray more friendly
     * @param string $key
     * @param string $elem
     * @return string
     */
    private static function resultArrayFilter($key, $elem, $totalCount)
    {
        if ($elem === 0) {
            $elem = '0';
        }
        if ($elem === false) {
            $elem = 'false';
        }
        if (is_null($elem)) {
            $elem = 'null';
        }
        if ($totalCount == 1 && empty($key) && !empty($elem)) {
            return $elem;
        } elseif ($totalCount > 1) {
            return "$key => $elem";
        }
        
        return '';
    }
    
    /**
    * encodes errors to json and saves to DB
    * @param int $userId
    * @param string, array $errors
    * @param int $type
    * @return true
    */
    public function log($errors = '', $message = '', $userId = 0, $type = self::TYPE_UNDEFINED)
    {
        if (empty($GLOBALS['syslogEndingFunc'])) {
            register_shutdown_function(function () {
                if (!isset($GLOBALS['syslogLogs'])) {
                    $GLOBALS['syslogLogs'] = [];
                }
                $logs = $GLOBALS['syslogLogs'];
                if (!empty($logs)) {
                    $this->getDb()->createCommand()->batchInsert(
                        $this->tableName(),
                        [
                            'log_source',
                            'issues',
                            'user_id',
                            'message',
                            'created_at',
                            'updated_at',
                        ],
                        $logs
                    )->execute();
                }
            });
            $GLOBALS['syslogEndingFunc'] = true;
        }
        try {
            if ($type == self::TYPE_UNDEFINED) {
                $type = php_sapi_name() == 'cli' ? self::TYPE_CRON : self::TYPE_FRONTEND;
            }
            if (is_null($errors)) {
                $errors = '';
            }
            if (is_null($message)) {
                $message = '';
            }
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
                //ignore errors
                foreach ($errors as $key => $error) {
                    foreach ($this->module->errorIgnoreList as $ignore) {
                        if (strpos($error, $ignore) !== false) {
                            unset($errors[$key]);
                        }
                    }
                }
                $errors = Json::encode($errors);
            }
            $this->setAttributes([
                'log_source' => $type,
                'issues' => $errors == '[]' ? '[""]' : $errors,
                'user_id' => $userId,
                'message' => $message == '[]' ? '[""]' : $message,
            ]);
            if ($this->issues == '[""]' && $this->message == '[""]') {
                return true;
            }
            if ($this->validate()) {
                $GLOBALS['syslogLogs'][] = [
                    $this->log_source,
                    $this->issues,
                    $this->user_id,
                    $this->message,
                    time(),
                    time(),
                ];
                return true;
            } else {
                Yii::error("Logs save errors occured. Listing:\n".var_export($this->errors, true).var_export($this->getAttributes(), true), 'syslog');
                return false;
            }
        } catch (\Exception $ex) {
            Yii::error('Syslog critical error: '.$ex->getMessage());
        }
    }
    
    /**
     * Generates log with filestack to current place
     * @param string $comment
     * @param boolean $markAsError
     * if $markAsError is true - generates ERROR log and generates Yii::error log
     */
    public function trackPoint($comment = '', $markAsError = false)
    {
        try {
            $debug = debug_backtrace();
            $fileLine = [];
            foreach ($debug as $trace) {
                if (empty($trace['file'])) {
                    continue;
                }
                $fileLine[] = "{$trace['file']}({$trace['line']})";
            }
            if ($markAsError) {
                $this->log(
                    $comment,
                    $fileLine
                );
                Yii::error("Check '$comment' error log in syslog");
            } else {
                $this->log(
                    '',
                    ArrayHelper::merge(
                        [$comment],
                        $fileLine
                    )
                );
            }
        } catch (\Exception $ex) {
            $this->log($ex->getMessage().' '.$ex->getTraceAsString());
        }
    }
}


