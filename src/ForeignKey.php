<?php

namespace Plansys\Db;

class ForeignKey {

    public static function set($foreignKey)
    {
        $column     = $foreignKey['columns'];
        $refTable   = $foreignKey['refTable'];
        $refColumns = $foreignKey['refColumns'];
        $name       = isset($foreignKey['name']) ? $foreignKey['name'] : null;
        $options    = isset($foreignKey['options']) ? $foreignKey['options'] : [];
        return new \Doctrine\DBAL\Schema\ForeignKeyConstraint($column, $refTable, $refColumns, $name, $options);
    }

    public static function setMultiple($foreignKeys)
    {
        $result = [];
        foreach ($foreignKeys as $foreignKey) {
            $result[] = self::set($foreignKey);
        }

        return $result;
    }
}