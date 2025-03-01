<?php

namespace MyApp\Controllers;

use Forge\Core\Helpers\Debug;
use Forge\Http\Request;
use Forge\Http\Response;
use MyApp\Models\User;
use Forge\Core\Contracts\Modules\ViewEngineInterface;

class UserController
{
    /**
     * @inject
     */
    private ViewEngineInterface $view;

    public function index(Request $request): Response
    {
        $users = User::all();
        $data = [
            'users' => $users,
        ];

        return $this->view->render('users.index', $data);
    }

    public function create(Request $request): Response
    {
        return (new Response())->html('User Create Form');
    }

    public function store(Request $request): Response
    {
        return (new Response())->html('created');
    }

    public function show(Request $request, $id): Response
    {
        echo 'user by id';
        return (new Response())->html('User Show by Param');
    }

    public function edit(Request $request): Response
    {
        return (new Response())->html('User Edit /id');
    }

    public function update(Request $request): Response
    {
        return (new Response())->html('User Patch/Put');
    }

    public function destroy(Request $request): Response
    {
        return (new Response())->html('User delete user/id');
    }
}
