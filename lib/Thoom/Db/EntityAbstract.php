<?php
/**
 * EntityAbstract Class
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 *
 * @since 3/27/12 4:00 PM
 */
namespace Thoom\Db;

use ArrayObject, BadMethodCallException;

class EntityAbstract extends ArrayObject
{
    public function __call($func, $argv)
    {
        if (!is_callable($func) || substr($func, 0, 6) !== 'array_') {
            throw new BadMethodCallException(__CLASS__ . '->' . $func);
        }
        return call_user_func_array($func, array_merge(array($this->getArrayCopy()), $argv));
    }
}
