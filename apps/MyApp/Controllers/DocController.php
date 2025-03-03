<?php

namespace MyApp\Controllers;

use Forge\Core\Helpers\Debug;
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
            "category" => $request->getAttribute('category') ?? '',
            'section' => $request->getAttribute('slug') ?? ''
        ];

        return $this->view->render('docs.index', $data, 'docs');
    }
}
