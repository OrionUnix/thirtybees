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

class DifferentDefaultValueCore implements SchemaDifference
{
    private $table;
    private $column;
    private $currentColumn;

    public function __construct(TableSchema $table, ColumnSchema $column, ColumnSchema $currentColumn)
    {
        $this->table = $table;
        $this->column = $column;
        $this->currentColumn = $currentColumn;
    }

    public function describe()
    {
        $table = $this->table->getName();
        $col = $this->column->getName();
        $value = $this->column->getDefaultValue();
        $currentValue = $this->currentColumn->getDefaultValue();
        return is_null($value)
            ? sprintf(Translate::getAdminTranslation('Column `%1$s`.`%2$s` should NOT have default value `%3$s`'), $table, $col, $currentValue)
            : sprintf(Translate::getAdminTranslation('Column `%1$s`.`%2$s` should have DEFAULT value `%3$s` instead of `%4$s`'), $table, $col, $value, $currentValue);
    }
}