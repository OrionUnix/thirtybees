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

class ColumnSchemaCore
{
    /**
     * @var string column name
     */
    protected $columnName;

    /**
     * @var string data type
     */
    protected $dataType;

    /**
     * @var boolean nullable
     */
    protected $nullable = null;

    /**
     * @var boolean auto increment flag
     */
    protected $autoIncrement = false;

    /**
     * @var string column default value
     */
    protected $defaultValue = null;


    /**
     * TableSchemaCore constructor.
     *
     * @param $columnName string name of the database column
     */
    public function __construct($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->columnName;
    }

    /**
     * @param $dataType string database type
     * @return ColumnSchemaCore
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
        return $this;
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        // explicitly set nullable
        if (! is_null($this->nullable)) {
            return $this->nullable;
        }
        // auto increments should not be nullable
        if ($this->autoIncrement) {
            return false;
        }
        // if field has default value, it's usually NOT NULL
        if (! is_null($this->defaultValue)) {
            return ($this->defaultValue === ObjectModel::DEFAULT_NULL);
        }
        return true;
    }

    /**
     * @param bool $nullable
     * @return ColumnSchemaCore
     */
    public function setNullable($nullable)
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param bool $autoIncrement
     * @return ColumnSchemaCore
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue === ObjectModel::DEFAULT_NULL ? null : $this->defaultValue;
    }

    /**
     * @param string $defaultValue
     * @return ColumnSchemaCore
     */
    public function setDefaultValue($defaultValue)
    {
        if (is_null($defaultValue)) {
           $this->defaultValue = null;
        } else if (is_string($defaultValue)) {
            $this->defaultValue = $defaultValue;
        } else {
            $this->defaultValue = "$defaultValue";
        }
        return $this;
    }
}
