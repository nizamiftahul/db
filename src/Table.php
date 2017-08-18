<?php

namespace Plansys\Db;

class Table {

    public $init;
    public $sm;
    public $table;

    public function __construct($init, $table) 
    {
        $this->init = $init;
        $this->sm = $this->init->conn->getSchemaManager();
        $this->table = $table;
    }

    public static function create($app, $params)
    {
        $sm = $app->db->conn->getSchemaManager();

        $name = $params['tableName'];
        $arrColumns = $params['columns'];
        $primaryKey = $params['primaryKey'];
        $arrIndexes = $params['indexes'];
        $arrForeignKeys = $params['foreignKeys'];;
        $idGenerator = $params['idGenerator'];
        $options = $params['options'];

        $columns = [];
        foreach ($arrColumns as $column) {
            $columns[] = Table::setColumn($column);
        }

        $indexes = [];
        foreach ($arrIndexes as $index) {
            $indexes[] = Table::setIndex($index);
        }
        
        $foreignKeys = [];
        foreach ($arrForeignKeys as $foreignKey) {
            $foreignKeys[] = Table::setForeignKey($foreignKey);
        }
    
        $table = new \Doctrine\DBAL\Schema\Table($name, $columns, $indexes, $foreignKeys, $idGenerator, $options);

        if (!empty($primaryKey)) {
            $table->setPrimaryKey($primaryKey);
        }

        $app->db->conn->beginTransaction();
        try {
            $sm->createTable($table);
        } catch (\Exception $e) {
            $app->db->conn->rollBack();
            echo \Doctrine\DBAL\Schema\SchemaException::tableAlreadyExists($params['tableName'])->getMessage();
        }

    }

    public function alter($params)
    {
        $tableName = $params['tableName'];
        $arrAddColumns = $params['addColumns'];
        $arrChangeColumns = $params['changeColumns'];
        $arrRemoveColumns = $params['removeColumns'];
        $arrAddIndexes = $params['addIndexes'];
        $arrChangeIndexes = $params['changeIndexes'];
        $arrRemoveIndexes = $params['removeIndexes'];
        $refTable = $this->sm->listTableDetails($this->table);

        $addColumns = [];
        foreach ($arrAddColumns as $column) {
            $addColumns[] = Table::setColumn($column);
        }

        $changeColumns = [];
        foreach ($arrChangeColumns as $column) {
            $changeColumns[] = Table::setColumnDiff($column);
        }

        $removeColumns = [];
        foreach ($arrRemoveColumns as $column) {
            $removeColumns[] = Table::setColumn($column);
        }

        $addIndexes = [];
        foreach ($arrAddIndexes as $index) {
            $addIndexes[] = Table::setIndex($index);
        }

        $changeIndexes = [];
        foreach ($arrChangeIndexes as $index) {
            $changeIndexes[] = Table::setIndex($index);
        }

        $removeIndexes = [];
        foreach ($arrRemoveIndexes as $index) {
            $removeIndexes[] = Table::setIndex($index);
        }

        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff($tableName, $addColumns, $changeColumns, $removeColumns,
            $addIndexes, $changeIndexes, $removeIndexes);
        
        $this->init->conn->beginTransaction();
        try {
            $sm->createTable($table);
        } catch (\Exception $e) {
            $this->init->conn->rollBack();
            var_dump($e);
        }
        return $this->sm->alterTable($tableDiff);
    }

    public static function setColumn($column)
    {
        $name = $column['name'];
        $type = \Doctrine\DBAL\Types\Type::getType($column['type']);
        $options = $column['options'];

        return new \Doctrine\DBAL\Schema\Column($name, $type, $options);
    }

    public static function setColumnDiff($columnDiff)
    {
        $refColumn = $columnDiff['refColumn'];
        $newColumn = $columnDiff['newColumn'];
        $column = Table::setColumn($newColumn);

        return new \Doctrine\DBAL\Schema\ColumnDiff($refColumn, $column);
    }

    public static function setIndex($index)
    {
        $name = $index['name'];
        $columns = $index['columns'];
        $isUnique = $index['isUnique'];
        $isPrimary = $index['isPrimary'];
        $flags = $index['flags'];
        $options = $index['options'];

        return new \Doctrine\DBAL\Schema\Index($name, $columns, $isUnique, $isPrimary, $flags, $options);
    }

    public static function setForeignKey($fk)
    {
        $column = $fk['columns'];
        $refTable = $fk['refTable'];
        $refColumns = $fk['refColumns'];
        $name = $fk['name'];
        $options = $fk['options'];

        return new \Doctrine\DBAL\Schema\ForeignKeyConstraint($column, $refTable, $refColumns, $name, $options);
    }    

    public function addIndexes($indexes)
    {
        foreach ($indexes as $index) {
            $this->sm->createIndex(Index::init($index), $this->table);
        }
    }

    public function addForeignKeys($foreignKeys)
    {
        foreach ($foreignKeys as $foreignKey) {
            $this->sm->createForeignKey(ForeignKey::init($foreignKey), $this->table);
        }
    }

    public function getColumns()
    {
        $columns = $this->sm->listTableColumns($this->table);
        $result = [];

        foreach ($columns as $column) {
            $result[] = [
                'name'          => $column->getName(),
                'type'          => $column->getType()->getName(),
                'length'        => $column->getLength(),
                'unsigned'      => $column->getUnsigned(),
                'fixed'         => $column->getFixed(),
                'notNull'       => $column->getNotnull(),
                'default'       => $column->getDefault(),
                'autoIncrement' => $column->getAutoincrement()

            ];
        }

        return $result;
    }

    public function getIndexes()
    {
        $indexes = $this->sm->listTableIndexes($this->table);
        
        $result = [];
        foreach ($indexes as $index) {
            if ($index->isPrimary()) {
                $type = 'Primary';
            } else if ($index->isUnique()) {
                $type = 'Unique';
            } else {
                $type = 'Index';
            }

            $result[] = [
                'column' => $index->getColumns()[0],
                'type'   => $type
            ];
        }

        return $result;
    }

    public function getForeignKeys()
    {
        $foreignKeys = $this->sm->listTableForeignKeys($this->table);
        
        $result = [];
        foreach ($foreignKeys as $foreignKey) {
            $result[] = [
                'column'     => $foreignKey->getColumns()[0],
                'refTable'   => $foreignKey->getForeignTableName(),
                'refColumns' => $foreignKey->getForeignColumns()[0],
                'onUpdate'   => $foreignKey->onUpdate(),
                'onDelete'   => $foreignKey->onDelete()
            ];
        }

        return $result;
    }

}