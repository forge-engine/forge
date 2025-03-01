<?php

namespace MyApp\Controllers;

use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Debug;
use Forge\Core\Helpers\Path;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Core\Contracts\Modules\MarkDownInterface;

class DocController
{
    /**
     * @inject
     */
    private ViewEngineInterface $view;

    /**
     * @inject
     */
    private MarkDownInterface $markdown;

    public function index(Request $request): Response
    {
        $fileToParse = Path::appPath('resources/docs_md/about.md');
        //$this->markdown->parseFile($fileToParse);

        return $this->view->render('landing.index');
    }
}
