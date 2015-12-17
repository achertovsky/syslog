<?php

use yii\db\Schema;
use yii\db\Migration;

class m151217_185629_remove_user_relation extends Migration
{
    public function up()
    {
        $this->dropForeignKey('fk_user_syslog', '{{%syslog}}');
    }

    public function down()
    {
        $this->addForeignKey('fk_user_syslog', '{{%syslog}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }
}
