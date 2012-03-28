<?php
/**
 * ManagerAbstract
 *
 * Base class to extend to entity managers
 *
 * This class will provide some CRUD methods for accessing the tb table
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

    protected $columns = array();

    protected $columnsAsKeys = array();

    protected $entity;

    protected $table;

    protected $primaryKey = 'id';


    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->table = substr(strtolower(str_replace('Manager', '', strrchr(get_class($this), '\\'))), 1);
    }

    /**
     * Creates a new entity record in the database
     *
     * @param EntityAbstract $entity
     * @return EntityAbstract
     */
    public function create(EntityAbstract $entity)
    {
        $values = $entity->toArray();
        $results = $this->db->insert($this->table, $values);
        if ($results) {
            if (!isset($values[$this->primaryKey]))
                $values[$this->primaryKey] = $this->db->lastInsertId();

            return $entity->resetData($values);
        }
    }

    /**
     * A Factory method that returns a fresh instance of the manager's entity
     * @param array $data
     * @return mixed
     */
    public function fresh($data = array())
    {
        return new $this->entity($this, $data);
    }


    /**
     * Returns an entity object for the primaryKey sent.
     *
     * @param string $primaryKey
     * @return EntityAbstract
     */
    public function read($primaryKey)
    {
        $data = $this->db->fetchAssoc("SELECT * FROM $this->table WHERE $this->primaryKey = ?", array($primaryKey));
        if ($data)
            return $this->fresh($data);
    }

    /**
     * Puts a new entity instance into the database.
     *
     * @param EntityAbstract $entity
     * @return EntityAbstract
     */
    public function update(EntityAbstract $entity)
    {

    }

    /**
     * Deletes an existing entity
     *
     * @param \Thoom\Db\EntityAbstract $entity
     * @return int Number of rows affected
     */
    public function delete(EntityAbstract $entity)
    {
        return $this->db->executeUpdate("DELETE FROM $this->table WHERE $this->primaryKey = ?", array($entity[$this->primaryKey]));
    }

    /**
     * Convenience method for describing the current table schema
     * @return array
     */
    public function describe()
    {
        return $this->db->fetchAll("DESCRIBE $this->table");
    }

    //Getter Methods
    public function primaryKey()
    {
        return $this->primaryKey;
    }

    public function columns()
    {
        return $this->columns();
    }

    public function columnsAsKeys()
    {
        if (count($this->columnsAsKeys) < 1){
            foreach ($this->columns as $key) {
                $this->columnsAsKeys[$key] = null;
            }
        }

        return $this->columnsAsKeys;
    }
}
