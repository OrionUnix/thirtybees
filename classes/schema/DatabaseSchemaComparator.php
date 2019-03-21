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

class DatabaseSchemaComparatorCore
{
    private $ignoreTables = [];

    /**
     * DatabaseSchemaComparatorCore constructor.
     * @param array $parameters
     */
    public function __construct($parameters = [])
    {
        if (isset($parameters['ignoreTables'])) {
            $this->ignoreTables = array_map(function($table) {
                return _DB_PREFIX_ . $table;
            }, $parameters['ignoreTables']);
        }
    }

    /**
     * @param DatabaseSchema $currentSchema
     * @param DatabaseSchema $targetSchema
     * @return SchemaDifference[]
     */
    public function getDifferences(DatabaseSchema $currentSchema, DatabaseSchema $targetSchema)
    {
        $differences = [];
        $tables = $this->getTables($targetSchema);

        foreach ($tables as $table) {
            if (!$currentSchema->hasTable($table->getName())) {
                $differences[] = new MissingTable($table);
            } else {
                $currentTable = $currentSchema->getTable($table->getName());
                $differences = array_merge($differences, $this->getTableDifferences($currentTable, $table));
            }
        }

        foreach ($this->getTables($currentSchema) as $table) {
            if (! $targetSchema->hasTable($table->getName())) {
                $differences[] = new ExtraTable($table);
            }
        }

        return $differences;
    }

    /**
     * @param TableSchema $currentSchema
     * @param TableSchema $targetSchema
     * @return SchemaDifference[]
     */
    public function getTableDifferences(TableSchema $currentSchema, TableSchema $targetSchema)
    {
        $differences = [];

        // TODO: compare table collation

        if ($currentSchema->getEngine() !== $targetSchema->getEngine()) {
            $differences[] = new DifferentEngine($targetSchema, $currentSchema->getEngine());
        }

        // 1) detect missing columns
        foreach ($this->getMissingColumns($currentSchema, $targetSchema) as $column) {
            $differences[] = new MissingColumn($targetSchema, $column);
        }

        // 2) find column differences
        foreach ($targetSchema->getColumns() as $targetColumn) {
            $currentColumn = $currentSchema->getColumn($targetColumn->getName());
            if ($currentColumn) {
                $differences = array_merge($differences, $this->getColumnDifferences($targetSchema, $currentColumn, $targetColumn));
            }
        }

        // 3) detect extra columns
        foreach ($this->getMissingColumns($targetSchema, $currentSchema) as $column) {
            $differences[] = new ExtraColumn($currentSchema, $column);
        }

        // 4) detect missing key
        foreach ($this->getMissingKeys($currentSchema, $targetSchema) as $key) {
            $differences[] = new MissingKey($targetSchema, $key);
        }

        // 5) find key differences
        foreach ($targetSchema->getKeys() as $targetKey) {
            $currentKey = $currentSchema->getKey($targetKey->getName());
            if ($currentKey) {
                if ($currentKey->getType() !== $targetKey->getType() || $currentKey->getColumns() !== $targetKey->getColumns()) {
                    $differences[] = new DifferentKey($targetSchema, $targetKey, $currentKey);
                }
            }
        }

        // 6) detect extra key
        foreach ($this->getMissingKeys($targetSchema, $currentSchema) as $key) {
            $differences[] = new ExtraKey($currentSchema, $key);
        }

        return $differences;
    }

    public function getMissingColumns(TableSchemaCore $currentSchema, TableSchemaCore $targetSchema)
    {
        $missingColumns = [];
        foreach ($targetSchema->getColumns() as $column) {
            if (! $currentSchema->hasColumn($column->getName())) {
                $missingColumns[] = $column;
            }
        }
        return $missingColumns;
    }

    /**
     * @param ColumnSchema $currentSchema
     * @param ColumnSchema $targetSchema
     * @return SchemaDifference[]
     */
    public function getColumnDifferences(TableSchema $table, ColumnSchema $current, ColumnSchema $target)
    {
        $differences = [];

        if ($current->getDataType() !== $target->getDataType()) {
            $differences[] = new DifferentDataType($table, $target, $current);
        }

        if ($current->getDefaultValue() !== $target->getDefaultValue()) {
            $differences[] = new DifferentDefaultValue($table, $target, $current);
        }

        if ($current->isNullable() !== $target->isNullable()) {
            $differences[] = new DifferentNullable($table, $target, $current);
        }

        if ($current->isAutoIncrement() !== $target->isAutoIncrement()) {
            $differences[] = new DifferentAutoIncrement($table, $target, $current);
        }

        return $differences;
    }

    public function getMissingKeys(TableSchemaCore $currentSchema, TableSchemaCore $targetSchema)
    {
        $missingKeys = [];
        foreach ($targetSchema->getKeys() as $key) {
            if (! $currentSchema->hasKey($key->getName())) {
                $missingKeys[] = $key;
            }
        }
        return $missingKeys;
    }

    private function getTables(DatabaseSchema $schema)
    {
        $tables = array_filter($schema->getTables(), function(TableSchema $table) {
            if ($this->ignoreTables) {
                return ! in_array($table->getName(), $this->ignoreTables);
            }
            return true;
        });
        usort($tables, function(TableSchema $a, TableSchema $b) {
            return strcmp($a->getName(), $b->getName());
        });
        return $tables;
    }
}