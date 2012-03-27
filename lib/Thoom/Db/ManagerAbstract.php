<?php
/**
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 */

namespace Thoom\Db;

use Doctrine\DBAL\Connection;

abstract class ManagerAbstract implements ManagerInterface
{
    protected $db;
    protected $entity;
    protected $tableName;
    protected $primaryKey = 'id';

    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->tableName = substr(strtolower(str_replace('Manager', '', strrchr(get_class($this), '\\'))), 1);
    }

    public function insert(array $values)
    {
        $results = $this->db->insert($this->tableName, $values);
        if ($results){
            if ($values[$this->primaryKey])
                return $values[$this->primaryKey];

            return $this->db->lastInsertId();
        }
    }

    public function describe()
    {
        return $this->db->query("DESCRIBE $this->tableName")->fetchAll();
    }

    public function primaryKey()
    {
        return $this->primaryKey;
    }
}
