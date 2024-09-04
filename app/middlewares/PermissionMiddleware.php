<?php
declare(strict_types=1);

namespace app\middlewares;

class PermissionMiddleware extends Middleware {

    public function __invoke(Request $request, RequestHandler $handler):Response {
        global $app;
        $response = $app->getResponseFactory()->createResponse();

        if (!empty($this->arguments['role'])) {
            $roles = is_array($this->arguments['role']) ? $this->arguments['role'] : [$this->arguments['role']];
            $loggedUser = $request->getAttribute('user');
            $cId = $this->route->getArgument('cId') ?? $loggedUser['currentCompany']['id'] ?? null;

            if (!empty($cId)) {
                foreach ($roles as $key => &$value) {
                    $value.='::'.$cId;
                }
            }

            $r = $this->repository('user/RoleRepository')->checkRole($roles, $loggedUser);

            if (!$r) {
                $response = $response->withStatus(401);
                return $this->view->render($response, 'resources/401.twig');
            }
        }

        return $handler->handle($request);
    }

}

?>