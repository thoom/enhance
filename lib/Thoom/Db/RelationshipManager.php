<?php
/**
 * RelationsManager Class
 *
 * Injected into entities to help manage their relationships
 *
 * @author Zach Peacock <zpeacock@apptime.com>
 * @copyright Copyright (c) 2012, AppTime, LLC
 *
 * @since 4/10/12 5:52 PM
 */
namespace Thoom\Db;

class RelationshipManager
{
    /**
     * @var EntityAbstract
     */
    protected $entity;

    /**
     * @var ManagerFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $relationships = array();

    /**
     * @var array
     */
    protected $managers = array();

    /**
     * @var array
     */
    protected $entities = array();

    public function __construct(array $relationships, EntityAbstract $entity, ManagerFactory $factory)
    {
        $this->entity = $entity;
        $this->factory = $factory;
        $this->relationships = $relationships;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->entities))
            return $this->entities[$key];

        return $this->getEntities($key);
    }

    protected function getEntities($key)
    {
        if (!isset($this->relationships[$key]))
            return false;

        $relationship = $this->relationships[$key];

        if (!isset($this->managers[$relationship['manager']]))
            $this->managers[$relationship['manager']] = $this->factory->get($key);

        $manager = $this->managers[$relationship['manager']];
        /* @var $manager ManagerAbstract */

        $query = new QueryBuilder($manager->table(), $this->factory::connection(), $relationship['conditions']);

        $original_params = $query->params();
        $params = array();
        foreach ($original_params as $param) {
            if (stripos($param, 'entity.' === 0)) {
                $param = $this->entity[substr($param, 7)];
            }
            $params[] = $param;
        }

        if ($relationship['relation'] == 'HasOne')
            $this->entities[$key] = $manager->fetch($query, $params);
        else
            $this->entities[$key] = $manager->fetchAll($query, $params);

        return $this->entities[$key];
    }
}
