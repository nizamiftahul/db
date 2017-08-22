<?php

namespace db\Pages;

class Table extends \Yard\Page {

    public function isArray($value)
    {
        if (is_array($value)) return $value;
        return [];
    }

    public function query($app, $params)
    {
        $mode  = $params['mode'];
        $table = $params['table'];

        switch ($mode) {
            case 'create':
                $columns = $this->isArray(@$params['columns']);
                $indexes = $this->isArray(@$params['indexes']);
                $parents = $this->isArray(@$params['parents']);
                
                \Plansys\Db\Table::create($app, $table, $columns, $indexes, $parents);
                $app->db->syncTable();
                break;
            
            case 'alter':
                $addColumns = $this->isArray(@$params['addColumns']);
                $changeColumns = $this->isArray(@$params['changeColumns']);
                $removeColumns = $this->isArray(@$params['removeColumns']);
                $addIndexes = $this->isArray(@$params['addIndexes']);
                $addParents = $this->isArray(@$params['addParents']);

                $app->db->tables[$table]->alter($table, $addColumns, $changeColumns, $removeColumns, $addIndexes, $addParents);
                break;

            case 'createIndex':
                $app->db->tables[$table]->createIndex($params['index']);
                break;
            
            case 'changeIndex':
                $app->db->tables[$table]->changeIndex($params['index']);
                break;

            case 'dropIndex':
                $app->db->tables[$table]->dropIndex($params['indexName']);
                break;
            
            case 'createParent':
                $parent = $params['parent'];
                $app->db->tables[$table]->createParent($parent);
                break;

            case 'changeParent':
                $parent = $params['parent'];
                $app->db->tables[$table]->changeParent($parent);
                break;
            
            case 'dropParent':
                $parent = $params['parentName'];
                $app->db->tables[$table]->dropParent($parent);
                break;
            
            case 'drop':
                $app->db->tables[$table]->drop();
                break;
        }
    }
    
}