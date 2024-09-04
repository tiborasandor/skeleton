<?php
declare(strict_types=1);
namespace app\modules\user\actions;

final class PageAction extends Action {

    public function signin(Request $request, Response $response, $args): Response {
        return $this->view->render($response, 'signinPage.twig');
    }

    public function registration(Request $request, Response $response, $args): Response {
        return $this->view->render($response, 'registrationPage.twig');
    }

    public function profile(Request $request, Response $response, $args): Response {
        return $this->view->render($response, 'profilePage.twig');
    }

}
?>