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

class DifferentDataTypeCore implements SchemaDifference
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
        return sprintf(
            Translate::getAdminTranslation('Column `%1$s`.`%2$s` has data type `%3$s` instead of `%4$s`'),
            $this->table->getName(),
            $this->column->getName(),
            $this->getDataType($this->currentColumn),
            $this->getDataType($this->column)
        );
    }

    public function getDataType(ColumnSchema $column)
    {
        $output = $column->getDataType();
        return $output;
    }


}