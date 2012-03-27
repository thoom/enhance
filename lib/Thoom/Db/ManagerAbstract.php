<?php
/**
 * ManagerAbstract
 *
 * Base class to extend to entity managers
 *
 * This class will provide some REST-style methods for accessing the tb table
 * The constructor assumes that the table name is {TableName}Manager
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 */

namespace Thoom\Db;

use Doctrine\DBAL\Connection;

abstract class ManagerAbstract
{
    protected $db;

    protected $entity;

    protected $table;

    protected $primaryKey = 'id';

    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->table = substr(strtolower(str_replace('Manager', '', strrchr(get_class($this), '\\'))), 1);
    }

    /**
     * Returns an entity object for the primaryKey sent. If null, an empty entity is returned
     *
     * @param null $primaryKey
     * @return mixed
     */
    public function get($primaryKey = null)
    {
        if (!$primaryKey)
            return new $this->entity;
    }

    public function put(EntityAbstract $values)
    {
        $results = $this->db->insert($this->table, $values);
        if ($results){
            if ($values[$this->primaryKey])
                return $values[$this->primaryKey];

            return $this->db->lastInsertId();
        }
    }

    public function post(EntityAbstract $values)
    {

    }

    public function describe()
    {
        return $this->db->query("DESCRIBE $this->table")->fetchAll();
    }

    public function primaryKey()
    {
        return $this->primaryKey;
    }
}
