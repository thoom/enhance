<?php
/**
 * QueryBuilder
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 *
 * @since 4/17/12 3:14 PM
 */

namespace Thoom\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as QBuilder;

class QueryBuilder
{
    protected $db;
    protected $tablename;
    protected $conditions;

    protected $query;
    protected $params;


    public function __construct($tablename, Connection $connection, array $conditions)
    {
        $this->db = $connection;
        $this->tablename = $tablename;
        $this->conditions = $conditions;

        $this->buildQuery();
    }

    public function query()
    {
        return $this->query;
    }

    public function params()
    {
        return $this->params;
    }

    protected function buildQuery()
    {
        $query = $this->db->createQueryBuilder()->select('t.*')->from($this->tablename, 't');

        foreach ($this->conditions as $key => $condition) {
            if ($key == 'where')
                $this->where($condition, $query);
        }

        $this->query = $query;
    }

    /**
     *
     * array(
     *  array(
     *      'condition' => 't.columnName'
     *      'value' => 'someValue'
     *      'type' => 'AND'
     *  ),
     *  array(
     *      'condition' => t'columnName <> ?'
     *      'value' => 'someValue'
     *      'type' => 'OR'
     *  )
     * )
     *
     * @param array $conditions
     * @param \Doctrine\DBAL\Query\QueryBuilder $qbuilder
     */
    protected function where(array $conditions, QBuilder $qbuilder)
    {
        if (isset($conditions['condition'])) {
            $this->processWhereCondition($conditions, $qbuilder);
            return;
        }
        foreach ($conditions as $condition) {
            $this->processWhereCondition($condition, $qbuilder);
        }
    }

    protected function processWhereCondition(array $condition, QBuilder $qbuilder)
    {
        $query = $condition['condition'];
        if (stripos($query, '?') === false)
            $query .= " = ?";

        $type = isset($condition['type']) ? strtoupper($condition['type']) : 'AND';
        if ($type == 'AND')
            $qbuilder->where($query);
        else
            $qbuilder->orWhere($query);

        if (isset($condition['value']))
            $this->params[] = $condition['value'];
    }

    public function __toString()
    {
        return (string)$this->query();
    }
}