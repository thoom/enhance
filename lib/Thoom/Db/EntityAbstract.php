<?php
/**
 * EntityAbstract Class
 *
 * Base class to extend entitys from
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 *
 * @since 3/27/12 4:00 PM
 */

namespace Thoom\Db;

use ArrayAccess, ArrayIterator, Countable, BadMethodCallException, InvalidArgumentException, IteratorAggregate, Serializable, Traversable;

abstract class EntityAbstract implements ArrayAccess, Countable, IteratorAggregate, Serializable, Traversable
{
    protected $manager;

    protected $columns;
    protected $columnsAsKeys;
    protected $primaryKey;

    protected $values = array();
    protected $modified = array();

    protected $container = array();

    public function __construct(ManagerAbstract $manager, array $data = array())
    {
        $this->manager = $manager;

        $parsed = $this->parseArrayData($data);

        $this->values = array_merge($this->columnsAsKeys(), $parsed['columns']);

        $this->container = $parsed['container'];
    }

    protected function parseArrayData(array $data = array())
    {
        $parsed = array('columns' => array(), 'container' => array());
        foreach ($data as $key => $val) {
            if (in_array($key, $this->columns()))
                $parsed['columms'][$key] = $val;
            else
                $parsed['container'][$key] = $val;
        }

        return $parsed;
    }

    /**
     * Values are added to the modified array rather than the data array
     * @param array|EntityAbstract $values
     * @return array
     */
    public function data($values)
    {
        if ($values instanceof self)
            $values = $values->toArray();

        if (!is_array($values))
            throw new InvalidArgumentException("Array or " . get_class($this) . " instance expected");

        $parsed = $this->parseArrayData($values);

        $this->modified = array_merge($this->modified, $parsed['columns']);
        $this->container = array_merge($this->container, $parsed['container']);

        return $this;
    }

    /**
     * Resets the entity to its default state:
     * <br>The $values array is re-populated with column data from the $data array
     * <br>The $modified array is emptied
     * <br>The $container array is re-populated, or if $resetContainer is false, merged
     *
     * @param array|EntityAbstract $data
     * @param bool $resetContainer
     * @return EntityAbstract
     */
    public function resetData($data, $resetContainer = true)
    {
        if ($data instanceof self)
            $data = $data->toArray();

        if (!is_array($data))
            throw new InvalidArgumentException("Array or " . get_class($this) . " instance expected");


        $parsed = $this->parseArrayData($data);

        $this->values = array_merge($this->columnsAsKeys(), $parsed['columns']);
        $this->modified = array();
        $this->container = $resetContainer ? $parsed['container'] : array_merge($this->container, $parsed['container']);

        return $this;
    }

    public function toArray()
    {
        return array_merge($this->values, $this->modified);
    }

    //Getters
    public function columns()
    {
        if (!$this->columns)
            $this->columns = $this->manager->columns();

        return $this->columns;
    }

    public function columnsAsKeys()
    {
        if (!$this->columnsAsKeys)
            $this->columnsAsKeys = $this->manager->columnsAsKeys();

        return $this->columnsAsKeys;
    }

    public function modifiedArray()
    {
        return array_diff_assoc($this->modified, $this->values);
    }

    public function primaryKey()
    {
        if (!$this->primaryKey)
            $this->primaryKey = $this->manager->primaryKey();

        return $this->primaryKey;
    }

    //Magic methods
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    //Interface methods
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean Returns true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return in_array($offset, $this->columns()) || array_key_exists($offset, $this->container);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (in_array($offset, $this->columns())) {
            if (array_key_exists($offset, $this->modified))
                return $this->modified[$offset];

            if (array_key_exists($offset, $this->values))
                return $this->values[$offset];
        }

        if (isset($this->container[$offset]))
            return $this->container[$offset];

        return null;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (in_array($offset, $this->columns()))
            $this->modified[$offset] = $value;

        $this->container[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->modified))
            unset($this->modified[$offset]);
        else if (array_key_exists($offset, $this->container))
            unset($this->container[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing Iterator or
     * Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or &null;
     */
    public function serialize()
    {
        return serialize(array(
                'primaryKey' => $this[$this->primaryKey()],
                'data' => $this->toArray(),
                'columns' => $this->columns(),
                'columnsAsKeys' => $this->columnsAsKeys())
        );
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return mixed the original value unserialized.
     */
    public function unserialize($serialized)
    {
        $values = unserialize($serialized);

        $this->primaryKey = $values['primaryKey'];
        $this->columns = $values['columns'];
        $this->columnsAsKeys = $values['columnsAsKeys'];

        return $this->resetData($values['data']);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->toArray()) + count($this->container);
    }
}