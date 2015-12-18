<?php

use yii\db\Schema;
use yii\db\Migration;
use achertovsky\syslog\models\Syslog;

class m151218_134456_syslog_sended_flag extends Migration
{
    public function up()
    {
        if (empty($this->db->getTableSchema('{{%syslog}}')->getColumn('sended'))) {
            $this->addColumn('{{%syslog}}', 'sended', $this->smallInteger()->defaultValue(Syslog::MAIL_NOT_SENDED));
        }
    }

    public function down()
    {
        if (!empty($this->db->getTableSchema('{{%syslog}}')->getColumn('sended'))) {
            $this->dropColumn('{{%syslog}}', 'sended');
        }
    }
}
