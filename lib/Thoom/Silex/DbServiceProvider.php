<?php
/**
 * DbServiceProvider Class
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 *
 * @since 4/4/12 5:39 PM
 */
namespace Thoom\Silex;

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
            $callback = isset($app['dbm.options']['callback']) ? $app['dbm.options']['callback'] : null;
            return new ManagerFactory($app['db'], $app['dbm.options']['format'], $callback);
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registers
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}
