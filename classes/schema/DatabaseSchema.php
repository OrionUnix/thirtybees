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

class DatabaseSchemaCore
{
    /**
     * @var TableSchema[]
     */
    protected $tables;

    public function __construct()
    {
        $this->tables = [];
    }

    /**
     * @param TableSchema $table
     */
    public function addTable(TableSchema $table)
    {
        $this->tables[] = $table;
    }

    /**
     * @return TableSchema[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {
        foreach ($this->tables as $table) {
            if ($table->getName() === $tableName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $tableName
     * @return TableSchema | null
     */
    public function getTable($tableName)
    {
        foreach ($this->tables as $table) {
            if ($table->getName() === $tableName) {
                return $table;
            }
        }
        return null;
    }
}
