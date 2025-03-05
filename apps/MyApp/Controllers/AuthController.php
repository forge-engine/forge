<?php

namespace MyApp\Controllers;

use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Redirect;
use Forge\Modules\ForgeAuth\AuthService;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Http\Session;

class AuthController
{
    /***
     * @inject
     */
    private AuthService $auth;
    /***
     * @inject
     */
    private Session $session;

    /***
     * @inject
     */
    private ViewEngineInterface $view;

    public function __construct()
    {
        $this->view = App::getContainer()->get(ViewEngineInterface::class);
        $this->session = App::getContainer()->get(Session::class);
        $this->auth = App::getContainer()->get(AuthService::class);
    }

    public function loginForm(Request $request): Response
    {
        $data = [
            'request' => $request,
            'session' => $this->session
        ];
        return $this->view->render("modules.forge-auth.login", $data, 'base');
    }

    public function registerForm(Request $request): Response
    {
        $data = [
            'request' => $request,
            'session' => $this->session
        ];
        return $this->view->render("modules.forge-auth.register", $data, 'base');
    }

    public function login(Request $request): Response
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:6'
            ]);

            $user = $this->auth->login($credentials);
            return Redirect::to('/dashboard');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->session->setFlash('error', $errorMessage);

            return Redirect::to('/login');
        }
    }

    public function register(Request $request): Response
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required|min:8',
            ]);


            $user = $this->auth->register($credentials);
            return Redirect::to('/dashboard');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->session->setFlash('error', $errorMessage);

            return Redirect::to('/register');
        }
    }
}
