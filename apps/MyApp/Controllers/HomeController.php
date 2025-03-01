<?php

namespace MyApp\Controllers;

use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Http\Session;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;

class HomeController
{
    /**
     * @inject
     */
    private ViewEngineInterface $view;


    /**
     * @inject
     */
    private Session $session;

    /**
     * @inject
     * @var DatabaseInterface $db
     */
    private DatabaseInterface $db;

    public function index(Request $request): Response
    {
        $this->session->start();
        $this->session->set('debugbar_test_key', "From from debugbar session");

        $data = [
            'title' => 'Forge Framework',
            'users' => ['name' => 'Bob L', 'isLoggedIn' => true],
            'links' => [
                [
                    'label' => 'Documentation',
                    'url' => '#'
                ],
                [
                    'label' => 'Modules',
                    'url' => "https://github.com/forge-engine/modules",
                ],
                [
                    'label' => 'Forge',
                    'url' => 'https://github.com/forge-engine/forge'
                ],
                [
                    'label' => 'GitHub',
                    'url' => 'https://github.com/forge-engine'
                ],
            ]
        ];
        $error = new \ErrorException("Demo");


        //$url = $storage->temporaryUrl('uploads', 'user/avatar.jpg', 3600);
        //echo $url;
        //Debug::exceptionCollector($error);
        //Debug::addEvent('[Test]: ', 'start');

        //$this->db->query("SELECT * FROM forge_migrations");


        return $this->view->render('landing.index', $data, 'base');
    }

    public function uploadForm(Request $request): Response
    {
        return $this->view->render('landing.upload-form');
    }
}
