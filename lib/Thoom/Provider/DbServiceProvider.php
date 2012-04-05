<?php
/**
 * DbServiceProvider Class
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 *
 * @since 4/4/12 5:39 PM
 */
namespace Thoom\Provider;

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\ServiceProviderInterface;

use Thoom\Db\ManagerFactory;

class DbServiceProvider implements ServiceProviderInterface
{

    /**
     * Sets up the DoctrineServiceProvider and sets up the Thoom\Db\ManagerFactory
     * @param \Silex\Application $app
     */
    function register(Application $app)
    {
        //Register the DoctrineServiceProvider
        $app->register(new DoctrineServiceProvider(), isset($app['db.options']) ? $app['db.options'] : array());

        $app['dbm'] = $app->share(function() use ($app)
        {
            $callback = isset($app['db.manager.callback']) ? $app['db.manager.callback'] : null;
            return new ManagerFactory($app['db'], $app['db.manager.format'], $callback);
        });
    }
}
