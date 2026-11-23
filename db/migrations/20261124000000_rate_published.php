<?php

declare(strict_types=1);

/**
 * This file is part of the CNBCache package
 *
 * https://github.com/Spoje-NET/CNB-cache
 *
 * (c) Spoje.Net IT s.r.o. <https://spojenet.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Phinx\Migration\AbstractMigration;

/**
 * Day of the CNB list a rate comes from. Existing rows stay NULL and are verified when asked for.
 */
final class RatePublished extends AbstractMigration
{
    public function change(): void
    {
        $this->table('rates')->addColumn('published', 'date', ['null' => true])->update();
    }
}
