<?php

use yii\db\Schema;
use yii\db\Migration;

class m151221_114155_api_call_timestamp extends Migration
{
    public function up()
    {
        if (empty($this->db->getTableSchema('{{%api_call}}')->getColumn('created_at'))) {
            $this->addColumn('{{%api_call}}', 'created_at', $this->integer());
        }
        if (empty($this->db->getTableSchema('{{%api_call}}')->getColumn('updated_at'))) {
            $this->addColumn('{{%api_call}}', 'updated_at', $this->integer());
        }
        if (empty($this->db->getTableSchema('{{%api_call}}')->getColumn('sended'))) {
            $this->addColumn('{{%api_call}}', 'sended', $this->smallInteger()->defaultValue(0));
        }
    }

    public function down()
    {
        if (!empty($this->db->getTableSchema('{{%api_call}}')->getColumn('created_at'))) {
            $this->dropColumn('{{%api_call}}', 'created_at');
        }
        if (!empty($this->db->getTableSchema('{{%api_call}}')->getColumn('updated_at'))) {
            $this->dropColumn('{{%api_call}}', 'updated_at');
        }
        if (!empty($this->db->getTableSchema('{{%api_call}}')->getColumn('sended'))) {
            $this->dropColumn('{{%api_call}}', 'sended');
        }
    }
}
