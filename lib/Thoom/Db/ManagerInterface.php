<?php
/**
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 */

namespace Thoom\Db;

use Doctrine\DBAL\Connection as Connection;

interface ManagerInterface
{
    public function __construct(Connection $db);
}
