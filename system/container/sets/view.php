<?php
/**
 * Twig view
 */
$container->set('view', function(\Psr\Container\ContainerInterface $container) {
    $settings = $container->get('settings');

    $loader = new \Twig\Loader\FilesystemLoader($settings['system']['twig']['template_dir']);

    foreach ($settings['modules'] as $module_name => $module_settings) {
        $module_templates_dir = MODULES_DIR.DS.$module_name.DS.'resources'.DS.'templates';
        if ($module_settings['enabled'] && is_dir($module_templates_dir)) {
            $loader->addPath($module_templates_dir, $module_name);
        }
    }
    
    // Twig extra functions
    $functions[] = new \Twig\TwigFunction('is_active_path', function ($string, $class = 'active') use ($container) {
        $actualRouteName = $container->get('route')->getName();
        $actualRouteArguments = $container->get('route')->getArguments();
        $actualRouteUrl = $container->get('routeparser')->urlFor($actualRouteName, $actualRouteArguments);
        $active = $actualRouteName == $string ? true : false;

        if (!$active) {
            $pattern = '/^'. preg_quote($string, '/') .'/';
            $active = preg_match($pattern, $actualRouteUrl) ? true : false;
        }
        return $active ? $class : '';
    });

    $functions[] = new \Twig\TwigFunction('role', function ($roles) use ($container) {
        $repo = new \system\Repository($container);
        $roleRepository = $repo->repository('user/RoleRepository');
        $loggedUser = $container->get('view')['user'];
        $roles = is_array($roles) ? $roles : [$roles];
        $cId = $loggedUser['currentCompany']['id'] ?? null;

        if (!empty($cId)) {
            foreach ($roles as $key => &$value) {
                $value.='::'.$cId;
            }
        }

        return $roleRepository->checkRole($roles, $loggedUser);
    });

    // Twig filters

    $cache = $settings['system']['twig']['cache'] ? $settings['system']['twig']['cache_dir'] : false;

    $twig = new \system\View($loader, ['cache' => $cache, 'debug' => true]);
    $twig->addExtension(new \Twig\Extension\DebugExtension());

    $flash = $container->get('session')->getFlash();
    $twig->getEnvironment()->addGlobal('flash', $flash);
    foreach ($functions as $function) {
        $twig->getEnvironment()->addFunction($function);
    }

    return $twig;
});
?>