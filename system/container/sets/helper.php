<?php
/**
 * Helpers
 */
$container->set('helper', function(\Psr\Container\ContainerInterface $container) {
    $helpers = new \stdClass;

    /*
    foreach ($settings as $key => $value) {
        $helpers->{$key} = '';
    }
    */
    $key = 'array';
    $helpers->{$key} = new \system\helpers\ArrayHelper;

    return $helpers;
});
?>