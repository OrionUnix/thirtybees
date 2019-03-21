<?php


class InformationSchemaBuilderCore
{
    /** @var DatabaseSchema */
    protected $schema;

    /**
     * @return DatabaseSchema
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getSchema()
    {
        if (! $this->schema) {
            $this->schema = new DatabaseSchema();
            $this->processTables();
        }
        return $this->schema;
    }


    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processTables()
    {
        $connection = Db::getInstance();
        $tables = $connection->executeS('SELECT * FROM information_schema.TABLES WHERE table_schema = database()');
        foreach($tables as $row) {
            $this->schema->addTable(
                (new TableSchema($row['TABLE_NAME']))
                    ->setEngine($row['ENGINE'])
            );
        }

        $columns = $connection->executeS('SELECT * FROM information_schema.COLUMNS WHERE table_schema = database()');
        foreach($columns as $row) {
            $columnName = $row['COLUMN_NAME'];
            $tableName = $row['TABLE_NAME'];
            $autoIncrement = strpos($row['EXTRA'], 'auto_increment') !== false;
            $column = new ColumnSchema($columnName);;
            $column->setDataType($row['COLUMN_TYPE']);
            $column->setAutoIncrement($autoIncrement);
            $column->setNullable(strtoupper($row['IS_NULLABLE']) === 'YES');
            $column->setDefaultValue($row['COLUMN_DEFAULT']);
            $this->schema->getTable($tableName)->addColumn($column);
        }

        $constraints = $connection->executeS("
            SELECT * FROM information_schema.TABLE_CONSTRAINTS t
            INNER JOIN information_schema.KEY_COLUMN_USAGE k
            ON (t.constraint_name = k.constraint_name AND t.table_schema = k.table_schema AND t.table_name = k.table_name)
            WHERE t.table_schema = database()
            ORDER BY t.TABLE_NAME, t.CONSTRAINT_NAME, k.ORDINAL_POSITION
        ");
        foreach ($constraints as $row) {
            $tableName = $row['TABLE_NAME'];
            $constraintName = $row['CONSTRAINT_NAME'];
            $table = $this->schema->getTable($tableName);
            $constraint = $table->getKey($constraintName);
            if (! $constraint) {
                $constraint = new TableKey($this->getKeyType($row['CONSTRAINT_TYPE']), $constraintName);
                $table->addKey($constraint);
            }
            $constraint->addColumn($row['COLUMN_NAME']);
        }

        $stats = $connection->executeS("SELECT * FROM information_schema.STATISTICS s WHERE table_schema = database() ORDER BY s.TABLE_NAME, s.INDEX_NAME, s.SEQ_IN_INDEX");
        foreach ($stats as $row) {
            $tableName = $row['TABLE_NAME'];
            $keyName = $row['INDEX_NAME'];
            $table = $this->schema->getTable($tableName);
            $key = $table->getKey($keyName);
            if (! $key) {
                $key = new TableKey(TableKey::KEY, $keyName);
                $table->addKey($key);
            }
            if ($key->getType() === TableKey::KEY) {
                $key->addColumn($row['COLUMN_NAME']);
            }
        }
    }

    /**
     * @param $constraintType string database constranit type
     * @return int TableKey constant
     * @throws PrestaShopException
     */
    private function getKeyType($constraintType)
    {
       switch ($constraintType) {
           case 'PRIMARY KEY':
               return TableKey::PRIMARY_KEY;
           case 'UNIQUE':
               return TableKey::UNIQUE_KEY;
           case 'FOREIGN KEY':
               return TableKey::FOREIGN_KEY;
           default:
               throw new PrestaShopException('Unknown constraint type: ' . $constraintType);
       }
    }
}
