<?php
/**
 * ManagerAbstract
 *
 * Base class to extend to entity managers
 *
 * This class will provide some CRUD methods for accessing the tb table
 * The constructor assumes that the table name is {TableName}Manager, but will only autopopulate the table name if one is not entered
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 */

namespace Thoom\Db;

use Doctrine\DBAL\Connection;

abstract class ManagerAbstract
{
    /**
     * The current db connection
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * The entity class name that will this class will manage
     * @var string
     */
    protected $entity;

    /**
     * Stores the table definition. Column keys are the database column names.
     * TODO: Have the table definition values mean something (i.e., date fields will accept DateTime objects, etc)
     *
     * @var array
     */
    static protected $columns = array(
        /*
         * 'id' => array('type' => 'int')
         * 'name' => array('type' => 'string')
         * 'created' => array('type' => 'date')
         * 'etc'
         */
    );

    /**
     *
     * @var string
     */
    static protected $primaryKey = 'id';

    /**
     * The db table name that this class will manage
     * @var string
     */
    static protected $table;


    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Creates a new entity record in the database
     *
     * @param EntityAbstract $entity
     * @return EntityAbstract|bool
     */
    public function create(EntityAbstract $entity)
    {
        $values = $entity->modifiedArray();
        $results = $this->db->insert(static::table(), $values);
        if ($results) {
            if (!isset($values[static::primaryKey()]))
                $values[static::primaryKey()] = $this->db->lastInsertId();

            return $entity->resetData($values);
        }

        return false;
    }

    /**
     * A Factory method that returns a fresh instance of the manager's entity
     * If isModifiedArray is false, the array will be populated to the values array
     * If isModifiedArray is true, the array will be populated to the modified array and used in subsequent db updates
     *
     * @param array $data
     * @param bool $isModifiedArray
     * @return EntityAbstract
     */
    public function fresh($data = array(), $isModifiedArray = true)
    {
        if ($isModifiedArray) {
            /* @var $entity EntityAbstract */
            $entity = new $this->entity();

            return $entity->data($data);
        }

        return new $this->entity($data);
    }

    /**
     * Refreshes the current entity with values from the database
     *
     * @param EntityAbstract $entity
     * @return EntityAbstract|bool
     */
    public function refresh(EntityAbstract $entity)
    {
        $newEntity = $this->read($entity[static::primaryKey()]);
        if ($newEntity instanceof $this->entity)
            return $entity->resetData($newEntity);

        return false;
    }

    /**
     * Returns an entity object for the primaryKey sent.
     *
     * @param string $primaryKey
     * @return EntityAbstract|bool
     */
    public function read($primaryKey)
    {
        $data = $this->db->fetchAssoc("SELECT * FROM {static::table()} WHERE {static::primaryKey()} = ?", array($primaryKey));
        if ($data)
            return $this->fresh($data, false);

        return false;
    }

    /**
     * Updates an entity instance.
     *
     * @param EntityAbstract $entity
     * @return EntityAbstract
     */
    public function update(EntityAbstract $entity)
    {
        $values = $entity->modifiedArray();

        //TODO: Add functionality to update the table...
        //Only pull in the modified data... not the original

        return $entity->resetData($values);
    }

    /**
     * Deletes an existing entity
     * Note that this doesn't empty the entity!
     *
     * @param \Thoom\Db\EntityAbstract $entity
     * @return int Number of rows affected
     */
    public function delete(EntityAbstract $entity)
    {
        return $this->db->executeUpdate("DELETE FROM {static::table()} WHERE {static::primaryKey()} = ?", array($entity[static::primaryKey()]));
    }

    /**
     * Convenience method for describing the current table schema
     * @return array
     */
    public function describe()
    {
        return $this->db->fetchAll("DESCRIBE {static::table()}");
    }

    static public function columns()
    {
        return static::$columns;
    }

    static public function primaryKey()
    {
        return static::$primaryKey;
    }

    static public function table()
    {
        return static::$table;
    }
}