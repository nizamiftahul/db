<?php

namespace Plansys\Db;

class Index {

    public static function set($index)
    {
        $name      = isset($index['name']) ? $index['name'] : "" ;
        $columns   = $index['columns'];
        $isUnique  = isset($index['isUnique']) ? true : false;
        $isPrimary = isset($index['isPrimary']) ? true : false;
        $flags     = isset($index['flags']) ? $index['flags'] : [];
        $options   = isset($index['options']) ? $index['options'] : [];
        
        return new \Doctrine\DBAL\Schema\Index($name, $columns, $isUnique, $isPrimary, $flags, $options);
    }

    public static function setMultiple($indexes)
    {
        $result = [];
        foreach ($indexes as $index) {
            $result[] = self::set($index);
        }

        return $result;
    }

}