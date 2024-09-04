<?php
declare(strict_types=1);

namespace system;

/**
 * Default defines
 */
define('DS', DIRECTORY_SEPARATOR);
define('SYSTEM_DIR', __DIR__);
define('ROOT_DIR', dirname(SYSTEM_DIR, 1));
define('APP_DIR', ROOT_DIR.DS.'app');
define('CACHE_DIR', ROOT_DIR.DS.'cache');
define('MODULES_DIR', APP_DIR.DS.'modules');
define('RESOURCES_DIR', APP_DIR.DS.'resources');
define('TEMPLATES_DIR', RESOURCES_DIR.DS.'templates');
define('PUBLIC_DIR', ROOT_DIR.DS.'public');
define('VENDOR_DIR', ROOT_DIR.DS.'vendor');

/**
 * Reqiure composer autoload
 */
$composer_autoload = VENDOR_DIR.DS.'autoload.php';
if (!file_exists($composer_autoload)) {
    die('The composer autoload file ('.$composer_autoload.') load failed.');
}
require_once $composer_autoload;

/**
 * Create app
 */
$app = \Slim\Factory\AppFactory::createFromContainer(new \DI\Container());
$app->setBasePath('/');

/**
 * Container init
 */
require_once SYSTEM_DIR.DS.'container'.DS.'container_init.php';

/**
 * Set timezone
 */
date_default_timezone_set($container->get('settings')['system']['timezone']);

/**
 * Set locale
 */
$locale = $container->get('settings')['system']['locale'];
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);

/**
 * System class aliases
 */
class_alias('\system\Middleware','\system\middlewares\Middleware');
class_alias('\Psr\Http\Message\ServerRequestInterface','\system\middlewares\Request');
class_alias('\Psr\Http\Server\RequestHandlerInterface','\system\middlewares\RequestHandler');
class_alias('\Slim\Http\Response','\system\middlewares\Response');
class_alias('\system\Middleware','\app\middlewares\Middleware');
class_alias('\Psr\Http\Message\ServerRequestInterface','\app\middlewares\Request');
class_alias('\Psr\Http\Server\RequestHandlerInterface','\app\middlewares\RequestHandler');
class_alias('\Slim\Http\Response','\app\middlewares\Response');
class_alias('\Slim\Routing\RouteCollectorProxy','RouteCollectorProxy');

/**
 * Respect validator custom rules
 */
\Respect\Validation\Factory::setDefaultInstance((new \Respect\Validation\Factory())
    ->withRuleNamespace('system\\validatorcustomrules')
);

/**
 * System Middlewares
 */
$middlewares[] = 'system\middlewares\RouteMiddleware';
$middlewares[] = 'system\middlewares\SessionMiddleware';

$settingsMiddlewares = $container->get('settings')['middlewares'];

$settingsMiddlewares = array_filter($settingsMiddlewares, function($v, $k) {
    return $v['enabled'] ?? false;
}, ARRAY_FILTER_USE_BOTH);

array_multisort(array_column($settingsMiddlewares, 'weight'), $settingsMiddlewares);
$settingsMiddlewares = array_keys($settingsMiddlewares);
$settingsMiddlewares = preg_filter('/^/', 'app\middlewares\\', $settingsMiddlewares);
$middlewares = array_merge($middlewares, $settingsMiddlewares);

/**
 * Load modules
 */
$modules = $container->get('settings')['modules'];
array_multisort(array_column($modules, 'weight'), $modules);

foreach ($modules as $module => $params) {
    if ($params['enabled']) {
        $module_dir = MODULES_DIR.DS.$module;

        /**
         * Class aliases
         */
        $prefix = "app\\modules\\$module";
        $class_aliases = [
            'Psr\\Http\\Server\\RequestHandlerInterface'    => "$prefix\\middlewares\\RequestHandler",
            'Psr\\Http\\Message\\ServerRequestInterface'    => [
                "$prefix\\middlewares\\Request",
                "$prefix\\actions\\Request"
            ],
            'Psr\\Http\\Message\\ResponseInterface'         => "$prefix\\actions\\Response",
            'Slim\\Http\\Response'                          => "$prefix\\middlewares\\Response",
            'system\\Middleware'                            => "$prefix\\middlewares\\Middleware",
            'system\\Action'                                => "$prefix\\actions\\Action",
            'system\\Repository'                            => "$prefix\\repositories\\Repository",
            'system\\Factory'                               => "$prefix\\factories\\Factory"
        ];

        foreach ($class_aliases as $original => $aliases) {
            $aliases = is_array($aliases) ? $aliases : [$aliases];
            foreach ($aliases as $alias) {
                if (!class_exists($alias, false)) {
                    class_alias($original,$alias);
                }
            }
        }

        /**
         * Register classes
         */
        foreach (['actions','factories','repositories','middlewares'] as $type) {
            $dir = $module_dir.DS.$type;
            if (is_dir($dir)) {
                $files = array_diff(scandir($dir), ['.', '..']);
                foreach ($files as $file) {
                    $file_info = pathinfo($dir.DS.$file);
                    if ($file_info['extension'] == 'php') {
                        $class_name = $file_info['filename'];
                        $class = "app\modules\\$module\\$type\\$class_name";
                        $container_name = "@$module\\$type\\$class_name";
                        if ($type === 'middlewares') {
                            $middlewares[] = $class;
                        } else {
                            $container->set($container_name, function (\Psr\Container\ContainerInterface $container) use ($class) {
                                return new $class($container);
                            });
                        }
                    }
                }
            }
        }

        /**
         * Include routes
         */
        $route_file = $module_dir.DS.'routes.php';
        if (file_exists($route_file)) {
            require_once $route_file;
            
            /**
             * Routes mod
             */
            $routes = $app->getRouteCollector()->getRoutes();
            foreach ($routes as $key => $route) {
                $callable = $route->getCallable();
                $e = explode('\\', $callable);

                if (count($e) === 1) {
                    $callable = ["@$module",'actions',$e[0]];
                } elseif(count($e) === 2) {
                    $callable = [$e[0],'actions',$e[1]];
                }

                if (count($e) === 1 || count($e) === 2) {
                    $callable = implode('\\', $callable);
                    $route->setCallable($callable);
                    $callableResolver = $route->getCallableResolver();
                    $callableResolver->resolve($callable);
                }
            }
        }
    }
}

foreach (array_reverse($middlewares) as $middleware) {
    $app->add(new $middleware($container));
}

$app->addRoutingMiddleware();
$routeCollector = $app->getRouteCollector();
//$routeCollector->setCacheFile(CACHE_DIR.DS.'routes.cache');
$app->add(new \Selective\BasePath\BasePathMiddleware($app));
$app->add(\Slim\Views\TwigMiddleware::createFromContainer($app));
$app->addErrorMiddleware(true, true, true, $container->get('log'));
$app->run();
?>