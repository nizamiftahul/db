<?php

namespace Plansys\Db;

class Table {

    public $init;
    public $sm;
    public $command;
    public $table;
    public $childs = [];

    public function __construct($init, $table) 
    {
        $this->init = $init;
        $this->sm = $this->init->conn->getSchemaManager();
        $this->command = $this->init->notorm;
        $this->table = $table;
    }

    public static function create($app, string $tableName, array $columns, array $indexes=[], array $parents=[], $idGeneratorType = 0, array $options=[])
    {
        $sm = $app->db->conn->getSchemaManager();
        
        $columns = Column::setMultiple($columns);
        $indexes = Index::setMultiple($indexes);
        $parents = ForeignKey::setMultiple($parents);

        $table = new \Doctrine\DBAL\Schema\Table($tableName, $columns, $indexes, $parents, $idGenerator, $options);
        
        return $sm->createTable($table);
    }

    public function alter($tableName, array $addColumns = [], array $changeColumns = [], array $removeColumns = [], $addedIndexes = [], $addedParents = [])
    {
        $refTable = $this->sm->listTableDetails($this->table);
        
        $addColumns = $this->addColumns($addColumns);
        $changeColumns = $this->changeColumns($changeColumns);
        $removeColumns = $this->removeColumns($removeColumns);
        $addedIndexes = Index::setMultiple($addedIndexes);

        foreach ($addedIndexes as $k => $primary) {
            if ($primary->isPrimary()) {
                $addedIndexes['primary'] = $primary;
                unset($addedIndexes[$k]);
            }
        }

        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff($tableName, $addColumns, $changeColumns, $removeColumns, $addedIndexes, $changedIndexes = [], $removedIndexes = [], $refTable);
        $this->sm->alterTable($tableDiff);
        
        foreach ($addedParents as $parent) {
            $this->createParent($parent);
        }
        if (!empty($addedParents)) $this->unsetChild();

        return;
    }

    public function rename($newName)
    {
        $this->sm->renameTable($this->table, $newName);
    }

    public function drop()
    {
        $this->unsetChild();
        return $this->sm->dropTable($this->table);
    }

    // columns

    public function addColumns($columns)
    {
        return Column::setMultiple($columns);
    }

    public function changeColumns($columns)
    {
        $result = [];
        foreach ($columns as $column) {
            $result[] = Column::setDiff($column);
        }

        return $result;
    }

    public function removeColumns($columns)
    {
        return Column::setMultiple($columns);
    }

    // index

    public function createIndex($index)
    {
        $index = Index::set($index);
        return $this->sm->createIndex($index, $this->table);
    }

    public function changeIndex($index)
    {
        $index = Index::set($index);
        return $this->sm->dropAndCreateIndex($index, $this->table);
    }

    public function renameIndex($oldName, $newName)
    {
        return;
    }

    public function dropIndex($index)
    {
        return $this->sm->dropIndex($index, $this->table);
    }

    // foreign key

    public function createParent($fk)
    {
        $this->unsetChild();
        $fk = ForeignKey::set($fk);
        return $this->sm->CreateForeignKey($fk, $this->table);
    }

    public function changeParent($fk)
    {
        $this->unsetChild();
        $fk = ForeignKey::set($fk);
        return $this->sm->dropAndCreateForeignKey($fk, $this->table);
    }

    public function renameParent($oldName, $newName)
    {
        return;
    }

    public function dropParent($fk)
    {
        $this->unsetChild();
        return $this->sm->dropForeignKey($fk, $this->table);
    }

    // structure

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
                'name' => $index->getName(),
                'column' => $index->getColumns()[0],
                'type'   => $type
            ];
        }
        return $result;
    }

    public function unsetChild()
    {
        $this->childs = [];
    }

    public function getChilds()
    {
        if (empty($this->childs)) {
            $tables = $this->sm->listTableNames();
            foreach ($tables as $table) {
                $parents = $this->sm->listTableForeignKeys($table);
                foreach ($parents as $parent) {
                    if ($parent->getForeignTableName() == $this->table) {
                        $this->childs[$table] = $parent->getColumns();
                    }
                }
            }
        }
        return $this->childs;
    }

    public function getParents()
    {
        $foreignKeys = $this->sm->listTableForeignKeys($this->table);
        
        $result = [];
        foreach ($foreignKeys as $foreignKey) {
            $result[] = [
                'name'       => $foreignKey->getname(),
                'column'     => $foreignKey->getColumns()[0],
                'refTable'   => $foreignKey->getForeignTableName(),
                'refColumns' => $foreignKey->getForeignColumns()[0],
                'onUpdate'   => $foreignKey->onUpdate(),
                'onDelete'   => $foreignKey->onDelete()
            ];
        }

        return $result;
    }

    // data

    public function select($params)
    {
        $select = $params['select'];
        $where = $params['where'];
        $order = $params['order'];
        $group = $params['group'];
        $limit = $params['limit'];

        $result = $this->init->notorm->{$this->table}()
            ->select($select)
            ->group($group)
            ->order($order);
        
        if ($where['conditions'] != "" and !empty($where['params'])) {
            $result->where($where['conditions'], $where['params']);
        }
        
        if ($limit != "") {
            $result->limit($limit);
        }

        return $result;
    }

    public function insert($value)
    {
        return $this->init->notorm->{$this->table}()->insert($value);
    }

    public function update($value, $where)
    {
        $update = $this->init->notorm->{$this->table}()->where($where['conditions'], $where['params']);
        return $update->update($value);
    }

    public function delete($where)
    {
        $delete = $this->init->notorm->{$this->table}()->where($where['conditions'], $where['params']);
        return $delete->delete();
    }
}