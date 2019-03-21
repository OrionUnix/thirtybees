<?php
/**
 * Copyright (C) 2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

class DifferentKeyCore implements SchemaDifference
{
    /**
     * @var TableSchema table
     */
    protected $table;

    /**
     * @var TableKey expected key definition
     */
    protected $key;

    /**
     * @var TableKey current key definition
     */
    protected $currentKey;

    public function __construct(TableSchema $table, TableKey $key, TableKey $currentKey)
    {
        $this->table = $table;
        $this->key = $key;
        $this->currentKey = $currentKey;
    }

    function describe()
    {
        return sprintf(
            Translate::getAdminTranslation('Different %1$s in table `%2$s`'),
            $this->key->describeKey(),
            $this->table->getName()
        );
    }


}