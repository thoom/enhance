Thoom Enhance
=============

Summary
-------

Currently this is a collection of classes that are being developed to enhance using the Silex framework for building
simple websites. It has some utility classes and some abstract db classes that can be used to enhance the DBAL that
is included with Silex.

The Db classes use an Entity-Manager relationship, where an entity in abstract represents a database row. The manager
is it's tie to the database and provides CRUD methods to interact with it.

The manager relies on the entity to tell it what columns should be passed to the database. The manager really only knows
what table it is attached too and how it requests additional data it needs from the entity.

Understandably, use Doctrine ORM is an alternate option for use with Silex and other micro-frameworks. This framework is
not meant as a full-featured replacement, but rather a lighter-weight alternative.

Thoom\\Db Usage
---------------

To use with Silex, I recommend using it's DI capability, with the database connection stored in $app['db']:

    $app['mgr.game'] = $app->share(function($app)
    {
        return new MyNamespace\GameManager($app['db']);
    });

Then in your $app _controllers_ you can access the manager using DI:

    $app->get('/user/{primary_key}', function($primary_key) use ($app)
    {
        $user = $app['mgr.user']->read($primary_key);

        return $app['twig']->render('index.html.twig', array('name' => $user['name'], 'email' => $user['email']));
    });

Thoom\\Generator Usage
----------------------

To create a Uuid:

    $uuid = Thoom\Generator\Uuid::create();
    //outputs something like: ef8dbbaf-681a-4329-b58c-262a6c2c1fb4


To create a random alphanumeric string, lowercase only, 16 characters:

    use Thoom\Generator\RandomString;

    // .... code ... //

    $string = RandomString::alnum(16, RandomString::ALPHANUM_LOWER);
    //outputs something like: asb0z93dg91st73l


To add custom characters (like a dash) to a random string:

    use Thoom\Generator\RandomString;

    // .... code ... //

    $string = RandomString::user(16, array('-'), RandomString::ALPHANUM_LOWER);
    //outputs something like: p2am-53s9xrzb63n
