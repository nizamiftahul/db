<?php

namespace Plansys\Db;

class Column {

    public static function set($column)
    {
        $name    = $column['name'];
        $type    = \Doctrine\DBAL\Types\Type::getType($column['type']);
        $options = isset($column['options']) ? $column['options'] : [];

        return new \Doctrine\DBAL\Schema\Column($name, $type, $options);
    }

    public static function setDiff($column)
    {
        $refColumn = $column['refColumn'];
        $newColumn = self::set($column['newColumn']);
    
        return new \Doctrine\DBAL\Schema\ColumnDiff($refColumn, $newColumn);
    }

    public static function setMultiple($columns)
    {
        $result = [];
        foreach ($columns as $column) {
            $result[] = self::set($column);
        }

        return $result;
    }

}