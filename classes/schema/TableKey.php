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

class TableKeyCore
{
    const PRIMARY_KEY = 1;
    const UNIQUE_KEY = 2;
    const FOREIGN_KEY = 3;
    const KEY = 4;

    /**
     * @var string Key type, see type constants above
     */
    protected $type;

    /**
     * @var string key name
     */
    protected $name;

    /**
     * @var array name of columns in order
     */
    protected $columns;


    public function __construct($type, $name, $columns = [])
    {
        $this->type = $type;
        $this->name = $type === static::PRIMARY_KEY ? 'PRIMARY' : $name;
        $this->columns = $columns;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @param $columnName
     */
    public function addColumn($columnName)
    {
        $this->columns[] = $columnName;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return TableKeyCore
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     * @return TableKeyCore
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
        return $this;
    }

    public function describeKey()
    {
        switch ($this->type) {
            case TableKey::PRIMARY_KEY:
                return Translate::getAdminTranslation('primary key');
            case TableKey::UNIQUE_KEY:
                return sprintf(Translate::getAdminTranslation('unique key `%1$s`'), $this->name);
            case TableKey::FOREIGN_KEY;
                return sprintf(Translate::getAdminTranslation('foreign key `%1$s`'), $this->name);
            default:
                return sprintf(Translate::getAdminTranslation('key `%1$s`'), $this->name);
        }
    }
}
