<?php

use Phinx\Migration\AbstractMigration;

class NumberFeedItems extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        // disable foreign key checks to ensure updates without issues.
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        
        $this->execute("ALTER TABLE `rss_campaign` ADD COLUMN `rss_items` int(11) DEFAULT '0' NOT NULL AFTER `rss_feed`;");
        
        // re-arm foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

    }
}
