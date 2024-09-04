<?php
declare(strict_types=1);

namespace app\middlewares;

class AuthMiddleware extends Middleware {

    public function __invoke(Request $request, RequestHandler $handler):Response {
        global $app;
        $response = $app->getResponseFactory()->createResponse();
        $cId = $this->route->getArgument('cId');
        $loggedUser = $this->repository('user/UserRepository')->getLoggedUser($cId);
        $routeName = $this->route->getName();

        if ($loggedUser) {
            // Ha van belépve felhasználó
            $userRestrictedRoutes = ['userSigninPage', 'userRegistrationPage'];

            if (in_array($routeName, $userRestrictedRoutes)) {
                return $response->withRedirect($this->routeparser->urlFor('dashboard'), 302);
            }

            // user tárolása
            $this->view['user'] = $loggedUser;
            $request = $request->withAttribute('user', $loggedUser);
        } else {
            // Ha nincs belépve felhasználó
            $publicRoutes = ['userSigninPage', 'userRegistrationPage', 'userSigninJson', 'userRegistrationJson'];

            if (!in_array($routeName , $publicRoutes)) {
                return $response->withRedirect($this->routeparser->urlFor('userSigninPage'), 302);
            }
        }

        return $handler->handle($request);
    }

}

?>