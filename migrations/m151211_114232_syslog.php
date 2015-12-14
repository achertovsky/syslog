<?php

use yii\db\Schema;
use yii\db\Migration;
use modules\syslog\models\Syslog;

class m151211_114232_syslog extends Migration
{
    public function up()
    {
        if (empty($this->db->getTableSchema('{{%syslog}}'))) {
            $this->createTable('{{%syslog}}', [
                'id' => $this->primaryKey(),
                'errors_json' => $this->text(),
                'log_source' => $this->smallInteger()->defaultValue(Syslog::TYPE_UNDEFINED),
                'user_id' => $this->integer(),
                'created_at' => $this->integer(),
                'updated_at' => $this->integer(),
            ]);
        }
        $this->addForeignKey('fk_user_syslog', '{{%syslog}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        if (!empty($this->db->getTableSchema('{{%syslog}}'))) {
            $this->dropTable('{{%syslog}}');
        }
    }
}
