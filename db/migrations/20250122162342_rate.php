<?php

declare(strict_types=1);

/**
 * This file is part of the CNBExchangeRate package
 *
 * https://github.com/Spoje-NET/CNB-Tools
 *
 * (c) Spoje.Net IT s.r.o. <https://spojenet.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Phinx\Migration\AbstractMigration;

final class Rate extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('rates');
        $table->addColumn('date', 'date')
            ->addColumn('currency', 'string', ['limit' => 100])
            ->addColumn('amount', 'double')
            ->addColumn('code', 'string', ['limit' => 10])
            ->addColumn('rate', 'decimal', ['precision' => 10, 'scale' => 4])
            ->addIndex(['code'])
            ->addIndex(['date'])
            ->create();
    }
}
