<?php


class ObjectModelSchemaBuilderCore
{
    protected $schema;

    public function getSchema()
    {
        if (!$this->schema) {
            $this->schema = new DatabaseSchema();
            $this->processCoreModels();
            $this->processObjectModels();
        }
        return $this->schema;
    }

    private function processModel($objectModel, $definition)
    {
        $primaryTable = $this->getTable($definition['table']);
        $this->schema->addTable($primaryTable);

        if (isset($definition['primary'])) {
            $primaryKey = new ColumnSchema($definition['primary']);
            $primaryKey->setDataType(static::getOption($definition, 'primaryKeyDbType', 'int(11) unsigned'));
            $primaryKey->setNullable(false);
            $primaryKey->setAutoIncrement(static::getOption($definition, 'autoIncrement', true));
            $primaryTable->addColumn($primaryKey);
            $primaryTable->addKey(new TableKey(TableKey::PRIMARY_KEY, 'PRIMARY', [$definition['primary']]));
        }

        $hasLangTable = static::checkOption($definition, 'multilang');
        $langTable = $this->getTable($definition['table'] . '_lang');
        if ($hasLangTable) {
            $langTableKey = new TableKey(TableKey::PRIMARY_KEY, 'PRIMARY', [$definition['primary'], 'id_lang']);

            // lang table must have the foreign key to primary table
            $foreignKey = new ColumnSchema($definition['primary']);
            $foreignKey->setDataType('int(11) unsigned');
            $foreignKey->setNullable(false);
            $langTable->addColumn($foreignKey);

            // lang table must have foreign key to shop table
            $idLangKey = new ColumnSchema('id_lang');
            $idLangKey->setDataType('int(11) unsigned');
            $idLangKey->setNullable(false);
            $langTable->addColumn($idLangKey);

            if (static::checkOption($definition, 'multilang_shop')) {
                // shop table must have foreign key to shop table
                $idShopKey = new ColumnSchema('id_shop');
                $idShopKey->setDataType('int(11) unsigned');
                $idShopKey->setNullable(false);
                $idShopKey->setDefaultValue('1');
                $langTable->addColumn($idShopKey);
                $langTableKey->addColumn('id_shop');
            }

            $langTable->addKey($langTableKey);
            $this->schema->addTable($langTable);
        }

        $hasShopTable = Shop::isTableAssociated($definition['table']) || static::checkOption($definition, 'multishop');
        $shopTable = $this->getTable($definition['table'] . '_shop');
        if ($hasShopTable) {

            // shop table must have the foreign key to primary table
            $foreignKey = new ColumnSchema($definition['primary']);
            $foreignKey->setDataType('int(11) unsigned');
            $foreignKey->setNullable(false);
            $shopTable->addColumn($foreignKey);

            // shop table must have foreign key to shop table
            $idShopKey = new ColumnSchema('id_shop');
            $idShopKey->setDataType('int(11) unsigned');
            $idShopKey->setNullable(false);
            $shopTable->addColumn($idShopKey);

            $shopTableKey = new TableKey(TableKey::PRIMARY_KEY, 'PRIMARY', [$definition['primary'], 'id_shop']);
            $shopTable->addKey($shopTableKey);
            $this->schema->addTable($shopTable);
        }

        if (isset($definition['fields'])) {
            foreach ($definition['fields'] as $field => $columnDefinition) {
                $column = new ColumnSchema($field);
                $column->setDataType($this->getDataType($columnDefinition, $objectModel, $field));
                if (array_key_exists('dbNullable', $columnDefinition)) {
                    $column->setNullable(!!$columnDefinition['dbNullable']);
                } else if (array_key_exists('required', $columnDefinition)) {
                    $column->setNullable(!$columnDefinition['required']);
                }

                $column->setDefaultValue(static::getOption($columnDefinition, 'dbDefault', static::getOption($columnDefinition, 'default', null)));
                if ($hasLangTable && static::checkOption($columnDefinition, 'lang')) {
                    $langTable->addColumn($column);
                } else {
                    if ($hasShopTable && static::checkOption($columnDefinition, 'shop')) {
                        if (!static::checkOption($columnDefinition, 'shopOnly')) {
                            $primaryTable->addColumn($column);
                        }
                        $shopTable->addColumn($column);
                    } else {
                        $primaryTable->addColumn($column);
                    }
                }

                if (static::checkOption($columnDefinition, 'unique')) {
                    $keyName = static::getOption($columnDefinition, 'unique', false);
                    if (! is_string($keyName)) {
                        $keyName = $field;
                    }
                    $uniqueKey = new TableKey(TableKey::UNIQUE_KEY, $keyName, [$field]);
                    $primaryTable->addKey($uniqueKey);
                }
            }
        }

        if (isset($definition['associations'])) {
            foreach ($definition['associations'] as $key => $association) {
                if (static::checkOption($association, 'joinTable')) {
                    $joinTable = $this->getTable($association['joinTable']);

                    // association table must have the foreign key to primary table
                    $sourceKey = new ColumnSchema(static::getOption($association, 'joinSourceField', $definition['primary']));
                    $sourceKey->setDataType('int(11) unsigned', 'int');
                    $sourceKey->setNullable(false);
                    $joinTable->addColumn($sourceKey);

                    // association table must have the foreign key to primary table
                    $targetObjectModel = (isset($association['object'])) ? $association['object'] : Tools::toCamelCase($key, true);
                    $targetDefinition = ObjectModel::getDefinition($targetObjectModel);
                    $targetKey = new ColumnSchema(static::getOption($association, 'joinTargetField', $targetDefinition['primary']));
                    $targetKey->setDataType('int(11) unsigned', 'int');
                    $targetKey->setNullable(false);
                    $joinTable->addColumn($targetKey);

                    $this->schema->addTable($joinTable);
                }
            }
        }

        if (isset($definition['keys'])) {
             foreach ($definition['keys'] as $tableName => $keys) {
                 $table = $this->schema->getTable(_DB_PREFIX_ . $tableName);
                 foreach ($keys as $keyName => $keyDefinition) {
                     $table->addKey(new TableKey($keyDefinition['type'], $keyName, $keyDefinition['columns']));
                 }
            }
        }
    }

    /**
     * @param $unprefixedTableName
     * @return TableSchema
     */
    private function getTable($unprefixedTableName)
    {
        $tableName = _DB_PREFIX_ . $unprefixedTableName;
        $table = $this->schema->getTable($tableName);
        if (!$table) {
            $table = new TableSchema($tableName);
            $table->setEngine(_MYSQL_ENGINE_);
        }
        return $table;
    }


    private function processCoreModels() {
        foreach (CoreModels::getModels() as $identifier => $definition) {
            $this->processModel($identifier, $definition);
        }
    }


    private function processObjectModels()
    {
        $directory = new RecursiveDirectoryIterator(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'classes');
        $iterator = new RecursiveIteratorIterator($directory);
        $ret = [];
        foreach ($iterator as $path) {
            $file = basename($path);
            if (preg_match("/^.+\.php$/i", $file)) {
                $className = str_replace(".php", "", $file);
                if ($className !== "index") {
                    if (! class_exists($className)) {
                        require_once($path);
                    }
                    if (class_exists($className)) {
                        $reflection = new ReflectionClass($className);
                        if ($reflection->isSubclassOf('ObjectModelCore') && !$reflection->isAbstract()) {
                            $definition = ObjectModel::getDefinition($className);
                            if ($definition && isset($definition['table'])) {
                                $this->processModel($className, $definition);
                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * @param $columnDefinition array
     * @param $objectModel string
     * @param $field
     * @return string
     * @throws PrestaShopException
     */
    private function getDataType($columnDefinition, $objectModel, $field)
    {
        if (static::checkOption($columnDefinition, 'dbType')) {
            return $columnDefinition['dbType'];
        }
        switch ($columnDefinition['type']) {
            case ObjectModel::TYPE_INT:
                $size = static::getOption($columnDefinition, 'size', 11);
                $signed = static::getOption($columnDefinition, 'signed', false);
                $type = ($size == 1) ? 'tinyint' : 'int';
                return $type . '(' . $size . ')' . ($signed ? '' : ' unsigned');
            case ObjectModel::TYPE_BOOL:
                return 'tinyint(1) unsigned';
            case ObjectModel::TYPE_STRING:
            case ObjectModel::TYPE_HTML:
                if (static::checkOption($columnDefinition, 'values')) {
                    return 'enum(\'' . implode("','", $columnDefinition['values']) . '\')';
                }
                $size = static::getOption($columnDefinition, 'size', ObjectModel::SIZE_MAX_VARCHAR);
                if ($size <= ObjectModel::SIZE_MAX_VARCHAR) {
                    return "varchar($size)";
                }
                if ($size <= ObjectModel::SIZE_TEXT) {
                    return 'text';
                }
                if ($size <= ObjectModel::SIZE_MEDIUM_TEXT) {
                    return 'mediumtext';
                }
                return 'longtext';
            case ObjectModel::TYPE_FLOAT:
                $size = static::getOption($columnDefinition, 'size', 20);
                $decimals = static::getOption($columnDefinition, 'decimals', 6);
                return "decimal($size,$decimals)";
            case ObjectModel::TYPE_PRICE:
                $size = static::getOption($columnDefinition, 'size', 20);
                $decimals = static::getOption($columnDefinition, 'decimals', 6);
                return "decimal($size,$decimals)";
            case ObjectModel::TYPE_DATE:
                return 'datetime';
            case ObjectModel::TYPE_NOTHING:
            case ObjectModel::TYPE_SQL:
                throw new PrestaShopException('Please change type for field `' . $field . '` in object model `' . $objectModel. '`, or set specific `dbType`');
            default:
                throw new PrestaShopException('Field `' . $field . '` in object model `' . $objectModel. '` has unknown type: ' . $columnDefinition['type']);
        }
    }

    private static function checkOption($array, $key)
    {
        return isset($array[$key]) && !!$array[$key];
    }

    private static function getOption($array, $key, $default)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }

}
