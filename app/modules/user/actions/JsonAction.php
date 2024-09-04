<?php
declare(strict_types=1);
namespace app\modules\user\actions;

final class JsonAction extends Action {

    public function signin(Request $request, Response $response, $args): Response {
        try {
            $result = $this->repository('UserRepository')->signin([
                'email' => $request->getParam('email', null),
                'password' => $request->getParam('password', null)
            ]);

            if (!$result) {
                return $response->withJson([
                    'status' => 'error',
                    'messages' => [
                        'email' => 'Hibás e-mail cím vagy jelszó',
                        'password' => ''
                    ]
                ], 400);
            }

            return $response->withJson([
                'status' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            return $response->withJson([
                'status' => 'error',
                'message' => $this->throwMessage($th)
            ],400);
        }
    }

    public function signout(Request $request, Response $response, $args): Response {
        $this->repository('UserRepository')->signout();
        return $response->withRedirect($this->routeparser->urlFor('userSigninPage'), 302);
    }

    public function registration(Request $request, Response $response, $args): Response {
        try {
            $params = $request->getParams();

            $errors = $this->repository('ValidateRepository')->validate($params);

            if (!empty($errors)) {
                return $response->withJson([
                    'status' => 'error',
                    'messages' => $errors
                ], 400);
            }

            $userId = $this->factory('UserFactory')->registration($params);

            $flash = $this->session->getFlash();
            $flash->add('success', 'Sikeres regisztráció');

            $this->repository('UserRepository')->signin($params);

            return $response->withJson([
                'status' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            return $response->withJson([
                'status' => 'error',
                'message' => $this->throwMessage($th)
            ],400);
        }
    }

}
?>