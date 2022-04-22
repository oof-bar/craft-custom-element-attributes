<?php

namespace craft\contentmigrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220420_000000_AddCustomUserAttributes migration.
 * 
 * Heads up! This won't do anything if you leave it here. It'll have to be copied into your project's root `migrations` folder to be picked up.
 */
class m220420_000000_AddCustomUserAttributes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%customattributes_users}}', [
            'id' => $this->primaryKey(),
            'supervisorId' => $this->integer(),
            'badgeId' => $this->integer(),
        ]);

        // The first foreign key ensures this row's primary key matches the parent User record:
        $this->addForeignKey(null, '{{%customattributes_users}}', ['id'], Table::USERS, ['id'], 'CASCADE');

        // Then, we enforce the "invited by" ID to be for a valid User:
        $this->addForeignKey(null, '{{%customattributes_users}}', ['supervisorId'], Table::USERS, ['id'], 'SET NULL');

        // `badgeId` does not need a foreign key—we're pretending it's a reference to an external system or some kind!
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%customattributes_users}}');
    }
}
