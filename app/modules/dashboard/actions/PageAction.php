<?php
declare(strict_types=1);

namespace app\modules\dashboard\actions;

final class PageAction extends Action {

    public function dashboard(Request $request, Response $response, $args): Response {
        // repository hívás modulon belül
        $name = $this->repository('MainRepository')->getName();

        // repository hívás másim modulból
        //$name = $this->repository('masikmodulneve/MainRepository')->functionName();

        // factory hívás modulon belül 
        //$name = $this->factory('MainFactory')->getName();
        
        return $this->view->render($response, 'dashboard.twig', ['data' => [
            'name' => $name
        ]]);
    }

}
?>