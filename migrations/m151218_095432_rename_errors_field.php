<?php

use yii\db\Schema;
use yii\db\Migration;

class m151218_095432_rename_errors_field extends Migration
{
    public function up()
    {
        if (!empty($this->db->getTableSchema('{{%syslog}}')->getColumn('errors_json'))) {
            $this->dropColumn('{{%syslog}}', 'errors_json');
            $this->addColumn('{{%syslog}}', 'issues', $this->text());
        }
    }

    public function down()
    {
        if (!empty($this->db->getTableSchema('{{%syslog}}')->getColumn('issues'))) {
            $this->dropColumn('{{%syslog}}', 'issues');
            $this->addColumn('{{%syslog}}', 'errors_json', $this->text());
        }
    }
}
