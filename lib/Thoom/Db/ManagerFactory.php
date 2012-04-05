<?php
/**
 * ManagerFactory Class
 *
 * Creates managers
 *
 * @author Zach Peacock <zpeacock@apptime.com>
 * @copyright Copyright (c) 2012, AppTime, LLC
 *
 * @since 4/4/12 5:52 PM
 */

namespace Thoom\Db;

use Doctrine\DBAL\Connection;

class ManagerFactory
{
    /**
     * The database connection
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * An array of manager objects returned in the get method
     * @var array
     */
    protected $managers = array();

    /**
     * Manager format used for short names
     * @var string
     */
    protected $managerFormat;

    /**
     * A callable reference used to alter the name passed in the get/fresh methods before it's used to create an instance
     * (An example would be to capitalize the name)
     * @var string
     */
    protected $formatCallback;

    /**
     * Sets variables needed in the object
     *
     * @param \Doctrine\DBAL\Connection $db
     * @param string $managerFormat string in a printf format used in get/fresh methods
     * @param null $formatCallback
     */
    public function __construct(Connection $db, $managerFormat, $formatCallback = null)
    {
        $this->db = $db;
        $this->managerFormat = $managerFormat;
    }

    /**
     * Returns an object based on the name passed
     * If an object already exists, it returns that one
     *
     * @param string $name
     * @return EntityAbstract
     */
    public function get($name)
    {
        $className = $this->className($name);
        if (!isset($this->managers[$className]))
            $this->managers[$className] == new $className($this->db);

        return $this->managers[$className];
    }

    /**
     * Always returns a new instance of the manager based on the name passed
     * @param string $name
     * @return EntityAbstract
     */
    public function fresh($name)
    {
        $className = $this->className($name);
        return new $className($this->db);
    }

    /**
     * Builds the manager classname based on the name passed + the formatCallback + the managerFormat
     * @param string $name
     * @return string
     */
    protected function className($name)
    {
        if (is_array($name)) {
            if (is_callable($this->formatCallback))
                $name = array_map($this->formatCallback, $name);

            return vsprintf($this->managerFormat, $name);
        }

        if (is_callable($this->formatCallback))
            $name = call_user_func_array($this->formatCallback, array($name));

        return sprintf($this->managerFormat, $name);
    }
}
