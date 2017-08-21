<?php

namespace Plansys\Db;

class Init
{
    public $connParams;
    public $conn;
    public $notorm;

    public $tables;

    function __construct($connParams)
    {
        $this->connParams = $connParams;

        $conf = new \Doctrine\DBAL\Configuration();
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($this->connParams, $conf);
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->notorm = new \NotOrm($this->conn->getWrappedConnection());

        $this->reloadTables();
    }

    public static function listDb($base)
    {
        $settings = get_object_vars($base->settings);
        $result = [];
        foreach ($settings as $k => $s) {
            if (is_object($s)) {
                if (get_class($s) === 'Plansys\Db\Init') {
                    $result[$k] = $s;
                }
            }
        }

        return $result;
    }

    public function reloadTables()
    {
        $sm = $this->conn->getSchemaManager();
        $tables = $sm->listTableNames();
        $this->tables = [];
        foreach ($tables as $table) {
            $this->tables[$table] = new Table($this, $table);
        }
    }
    
    function __call($func, $args)
    {
        if ($func === 'conn') {
            return call_user_func_array([$this, $func], $args);
        }
        
        return call_user_func_array([$this->notorm, $func], $args);
    }

    public static function query($page, $params)
    {
        $query = new Query($page);
        return $query->getResult($params);
    }

    public static function getBase($host)
    {
        return [
            'dir'=> realpath(dirname(__FILE__) . '/..') . '/pages',
            'url' => $host . '/pages/'
        ];
    }
}
