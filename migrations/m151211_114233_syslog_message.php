<?php

use yii\db\Schema;
use yii\db\Migration;

class m151211_114233_syslog_message extends Migration
{
    public function up()
    {
        if (empty($this->db->getTableSchema('{{%syslog}}')->getColumn('message'))) {
            $this->addColumn('{{%syslog}}', 'message', $this->text());
        }
    }

    public function down()
    {
        if (!empty($this->db->getTableSchema('{{%syslog}}')->getColumn('message'))) {
            $this->dropColumn('{{%syslog}}', 'message');
        }
    }
}

