<?php

use yii\db\Schema;
use yii\db\Migration;

class m151221_101931_api_calls_table extends Migration
{
    public function up()
    {
        if (empty($this->db->getTableSchema('{{%api_call}}'))) {
            $this->createTable('{{%api_call}}', [
                'id' => $this->primaryKey(),
                'call_to' => $this->string(255),
                'call_name' => $this->string(255),
            ]);
        }
    }

    public function down()
    {
        if (!empty($this->db->getTableSchema('{{%api_call}}'))) {
            $this->dropTable('{{%api_call}}');
        }
    }
}
