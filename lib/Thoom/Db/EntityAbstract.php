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

use ArrayAccess, ArrayIterator, Countable, BadMethodCallException, InvalidArgumentException, IteratorAggregate, Serializable;

abstract class EntityAbstract implements ArrayAccess, Countable, IteratorAggregate, Serializable
{
    /**
     * This should only hold data that is considered committed and has a key in the manager's columns array.
     * Data in this array will be used to filter out unmodified data in the modified array.
     *
     * @var array
     */
    protected $values = array();

    /**
     * Holds data that is considered modified and has a key in the columns array.
     *
     * @var array
     */
    protected $modified = array();

    /**
     * Anything that is sent in but does not have a key in the columns array.
     * It will not be used in the
     *
     * @var array
     */
    protected $container = array();

    /**
     * Builds the entity and populates the values array
     * <br>The columns array should be built with all of the columns in the database as the keys.
     *
     * @param array $columns The manager has the columns() method that should be used to populate this array
     * @param array $data Any data that should be populated. If keys from this array match the columns() keys, they will be
     * populated to the values array. Otherwise, they will appear in the container array.
     */
    public function __construct(array $columns, array $data = array())
    {
        $this->values = array_fill_keys(array_keys($columns), null);

        $parsed = $this->parseArrayData($data);

        $this->values = array_merge($this->values, $parsed['columns']);
        $this->container = $parsed['container'];
    }

    /**
     * Takes the array and moves the data to either a columns or container array depending on whether the key is in the values array
     *
     * @param array $data
     * @return array
     */
    protected function parseArrayData(array $data = array())
    {
        $parsed = array('columns' => array(), 'container' => array());
        foreach ($data as $key => $val) {
            if (array_key_exists($key, $this->values))
                $parsed['columns'][$key] = $val;
            else
                $parsed['container'][$key] = $val;
        }

        return $parsed;
    }

    /**
     * Values are added to the modified array rather than the data array
     *
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
    public function resetData($data = array(), $resetContainer = false)
    {
        if ($data instanceof self)
            $data = $data->toArray();

        if (!is_array($data))
            throw new InvalidArgumentException("Array or " . get_class($this) . " instance expected");

        $this->values = array_fill_keys(array_keys($this->values), null);
        $parsed = $this->parseArrayData($data);

        $this->values = array_merge($this->values, $parsed['columns']);
        $this->modified = array();
        $this->container = $resetContainer ? $parsed['container'] : array_merge($this->container, $parsed['container']);

        return $this;
    }

    /**
     * Empty's the container array that stores arbitrary data
     *
     * @return EntityAbstract
     */
    public function resetContainer()
    {
        $this->container = array();

        return $this;
    }

    /**
     * Returns the column data with modified data replacing defaults
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->values, $this->modified);
    }

    //Getters
    /**
     * Returns a subset of the modified array that only includes
     * the values that the entity believes have differed from the last entity refresh
     *
     * @return array
     */
    public function modifiedArray()
    {
        return $this->modified;
    }

    //Magic methods
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
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
        return isset($this->values[$offset]) || isset($this->modified[$offset]) || isset($this->container[$offset]);
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
        if (array_key_exists($offset, $this->modified))
            return $this->modified[$offset];

        if (array_key_exists($offset, $this->values))
            return $this->values[$offset];

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
        if (array_key_exists($offset, $this->values))
            $this->modified[$offset] = $value;
        else
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
        return serialize($this->values);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     */
    public function unserialize($serialized)
    {
        $this->values = unserialize($serialized);
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