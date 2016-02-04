<?php

namespace modules\syslog\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "api_call".
 *
 * @property integer $id
 * @property string $call_to
 * @property string $call_name
 * @property int $created_at
 * @property int $updated_at
 */
class ApiCall extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_call';
    }

    public $count;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sended'], 'boolean'],
            [['created_at', 'updated_at'], 'integer'],
            [['call_to', 'call_name'], 'string', 'max' => 255],
            [['call_to', 'call_name'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'call_to' => 'Call To',
            'call_name' => 'Call Name',
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
    
    public static function calc($callTo, $callName)
    {
        $call = new self();
        $call->setAttributes([
            'call_to' => $callTo,
            'call_name' => $callName,
        ]);
        if ($call->save()) {
            return true;
        } else {
            Yii::error("Error with calculation of calls:\n".var_export($call->errors, true), 'dev');
            return $call->errors;
        }
    }
}
