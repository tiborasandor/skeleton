<?php
declare(strict_types=1);

namespace system\middlewares;

class SessionMiddleware extends Middleware {

    public function __invoke(Request $request, RequestHandler $handler):Response {
        $this->session->start();
        $response = $handler->handle($request);
        $this->session->save();
        return $response;
    }

}

?>