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
 *
 * TODO: Have the table definition values mean something (i.e., date fields will accept DateTime objects, etc)
 */

namespace Thoom\Db;

use Doctrine\DBAL\Connection;

abstract class ManagerAbstract
{
    /**
     * The current db connection
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * Instance of the ManagerFactory if one was created
     * @var ManagerFactory
     */
    protected $factory;

    /**
     * The entity class name that will this class will manage
     *
     * @var string
     */
    protected $entity;

    /**
     * Stores the table definition. Column keys are the database column names.
     *
     * Example: <pre>
     * protected $columns = array(
     *      'id' => array('type' => 'int')
     *      'name' => array('type' => 'string')
     *      'created' => array('type' => 'date')
     * );
     *
     * @var array
     */
    protected $columns = array();

    /**
     * Stores the table relationships. Column keys are the database column names.
     * Relationships hooks are defined in a QueryBuilder-styled array called "conditions".
     *
     * Example: <pre>
     * protected $relationships = array(
     *      'address' => array(
     *          'manager' => 'My\Db\User\AddressManager',
     *          'relation' => 'HasOne',
     *          'conditions' => array(
     *              'where' => array(
     *                  array(
     *                      'query' => 't.user_id',
     *                      'value' => 'entity.id',
     *                      'type' => 'AND',
     *                  ),
     *                  array(
     *                      'query' => 't.status',
     *                      'value' => 'active',
     *                  )
     *              )
     *          )
     *      ),
     *      'email' => array(
     *          'manager' => 'My\Db\User\EmailManager',
     *          'relation' => 'HasMany',
     *          'conditions' => array(
     *              'where' => array(
     *                  array(
     *                      'query' => 't.user_id'
     *                      'value' => 'entity.user_id'
     *                  array (
     *                      'query' => 't.status <> ? '
     *                      'value' => 'disabled'
     *                  )
     *              )
     *          )
     *      ),
     * );
     *
     * @var array
     */
    protected $relationships = array();

    /**
     * The name of the primary key's field
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The db table name that this class will manage
     *
     * @var string
     */
    protected $table;

    public function __construct(Connection $db)
    {
        $this->db = $db;

        if (!$this->table)
            $this->table = substr(strtolower(str_replace('Manager', '', strrchr(get_class($this), '\\'))), 1);
    }

    /**
     * Creates a new entity record in the database
     *
     * @param EntityAbstract $entity
     * @return int|bool
     */
    public function create(EntityAbstract $entity)
    {
        $values = $entity->modifiedArray();

        //TODO: If a DateTime object is passed in the values array, output to SQL format (Only handle this if DBAL doesn't)
        $results = $this->db->insert($this->table, $values);
        if ($results) {
            if (isset($values[$this->primaryKey]))
                return $values[$this->primaryKey];

            return $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * A Factory method that returns a fresh instance of the manager's entity
     * <br>If isModifiedArray is false, the array will be populated to the values array
     * <br>If isModifiedArray is true, the array will be populated to the modified array and used in subsequent db updates
     *
     * @param array $data
     * @param bool $isModifiedArray
     * @return EntityAbstract
     */
    public function fresh($data = array(), $isModifiedArray = false)
    {
        if ($isModifiedArray) {
            /* @var $entity EntityAbstract */
            $entity = new $this->entity($this->columns);

            if ($this->relationships)
                $entity->setRelationships(new RelationshipManager($this->relationships, $entity, $this->factory));

            return $entity->data($data);
        }

        return new $this->entity($this->columns, $data);
    }

    /**
     * Refreshes the current entity with values from the database
     *
     * @param EntityAbstract $entity
     * @return EntityAbstract|bool
     */
    public function refresh(EntityAbstract $entity)
    {
        $newEntity = $this->read($this->primaryKey($entity));
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
        $data = $this->db->fetchAssoc("SELECT * FROM $this->table WHERE $this->primaryKey = ?", array($primaryKey));
        if ($data)
            return $this->fresh($data, false);

        return false;
    }

    /**
     * Updates an entity instance.
     * Note: This does not refresh the entity!
     *
     * @param EntityAbstract $entity
     * @return int Number of rows affected
     */
    public function update(EntityAbstract $entity)
    {
        $values = $entity->modifiedArray();

        $valCount = count($values);
        if ($valCount < 1)
            return $entity;

        $keys = array();
        $bind = array();
        $i = 0;

        foreach ($values as $key => $val) {
            $keys[] = "$key = :$key$i";
            $bind[":$key$i"] = $val;
            $i++;
        }

        $bind[":$this->primaryKey$i"] = $this->primaryKey($entity);
        $query = "UPDATE $this->table SET " . implode(', ', $keys) . " WHERE $this->primaryKey = :$this->primaryKey$i";

        return $this->db->executeUpdate($query, $bind);
    }

    /**
     * Deletes an existing entity
     * <br>Note that this doesn't empty the entity!
     *
     * @param EntityAbstract $entity
     * @return int Number of rows affected
     */
    public function delete(EntityAbstract $entity)
    {
        return $this->db->executeUpdate("DELETE FROM $this->table WHERE $this->primaryKey = ?", array($this->primaryKey($entity)));
    }

    /**
     * Convenience method for describing the current table schema
     *
     * @return array
     */
    public function describe()
    {
        return $this->db->fetchAll("DESCRIBE " . $this->table);
    }

    /**
     * NYI: Return the first entity filtered by the conditions array sent
     *
     * @param array|string|QueryBuilder $query
     * @param array $params
     * @return bool|EntityAbstract
     */
    public function fetch($query, array $params = array())
    {
        if (is_array($query))
            $query = new QueryBuilder($this->table, $this->db, $query);

        if (count($params) < 1 && $query instanceof QueryBuilder)
            $params =  $query->params();

        $data = $this->db->fetchAssoc($query, $params);
        if ($data)
            return $this->fresh($data, false);

        return false;
    }

    /**
     * NYI: Return an EntityCollection of entities filtered by the conditions array sent
     *
     * @param array|string|QueryBuilder $query
     * @param array $params
     * @return bool|EntityCollection
     */
    public function fetchAll($query, array $params = array())
    {
        if (is_array($query))
            $query = new QueryBuilder($this->table, $this->db, $query);

        if (count($params) < 1 && $query instanceof QueryBuilder)
            $params =  $query->params();

        $data = $this->db->fetchAll($query, $params);

        if ($data) {
            $collection = new EntityCollection();
            foreach ($data as $item_data) {
                $collection->attach($this->fresh($item_data, false));
            }
            return $collection;
        }

        return false;
    }

//    /**
//     * Convenience method that will either create or update an entity based on a few factors:
//     * <br>If the primaryKey value is not found, it will forward the request to create
//     * <br>If the primaryKey value is found, it will perform an UPDATE. If no changes are found, it will forward to the create method
//     * @param EntityAbstract $entity
//     * @return bool|int
//     */
//    public function save(EntityAbstract $entity)
//    {
//        if (!$this->primaryKey($entity))
//            return $this->create($entity);
//
//        $result = $this->update($entity);
//        if (!$result)
//            return $this->create($entity);
//
//       return $result;
//    }


    /**
     * Get the manager's columns array definition
     *
     * @return array
     */
    public function columns()
    {
        return $this->columns;
    }

    public function db()
    {
        return $this->db;
    }

    /**
     * Get the primary key value for the entity passed
     *
     * @param EntityAbstract $entity
     * @return string
     */
    public function primaryKey(EntityAbstract $entity)
    {
        return $entity[$this->primaryKey];
    }

    public function table()
    {
        return $this->table;
    }

    /**
     * Instance of the ManagerFactory that created the object
     * The factory is used for relationship building
     *
     * @param ManagerFactory $factory
     */
    public function setFactory(ManagerFactory $factory)
    {
        $this->factory = $factory;
    }
}