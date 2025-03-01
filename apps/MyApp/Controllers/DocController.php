<?php

namespace MyApp\Controllers;

use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\ViewEngineInterface;

class DocController
{
    /**
     * @inject
     */
    private ViewEngineInterface $view;

    public function index(Request $request): Response
    {
        $data = [
            "user" => "jhon"
        ];

        return $this->view->render('docs.index', $data, 'docs');
    }
}
