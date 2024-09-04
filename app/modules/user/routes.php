<?php
$app->get('/signin','PageAction:signin')->setName('userSigninPage');
$app->get('/registration','PageAction:registration')->setName('userRegistrationPage');
$app->get('/profile','PageAction:profile')->setName('userProfilePage');

$app->group('/json', function (RouteCollectorProxy $group) {
    $group->post('/signin','JsonAction:signin')->setName('userSigninJson');
    $group->get('/signout','JsonAction:signout')->setName('userSignoutJson');
    $group->post('/registration','JsonAction:registration')->setName('userRegistrationJson');
});
?>