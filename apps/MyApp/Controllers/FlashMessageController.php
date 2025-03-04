<?php

namespace MyApp\Controllers;

use Forge\Core\Helpers\Debug;
use Forge\Core\Helpers\Redirect;
use Forge\Enums\FlashMessageType;
use Forge\Http\Exceptions\ValidationException;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Http\Session;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Http\Validator;

class FlashMessageController
{
    /**
     * @inject
     */
    private ViewEngineInterface $view;


    /**
     * @inject
     */
    private Session $session;

    public function index(Request $request): Response
    {
        return $this->view->render('flash.index');
    }


    /**
     * @throws \Exception
     */
    public function create(Request $request): Response
    {
        $this->session->start();
        $data = [
            'session' => $this->session
        ];

        return $this->view->render('flash.create', $data, 'base');
    }

    public function store(Request $request): Response
    {
        $rules = [
            'name' => 'required|max:10|min:1',
            'lastName' => 'required|min:3',
        ];
        $messages = [

        ];
        try {
            $request->validate($rules, $messages);

            $this->session->setFlash('success', 'Form submitted successfully!');
            return Redirect::to('/flash-message-test/create');

        } catch (ValidationException $e) {
            $errors = $e->errors();

            foreach ($errors as $field => $errorMessages) {
                foreach ($errorMessages as $errorMessage) {
                    $this->session->setFlash('error', $errorMessage);
                }
            }
            return Redirect::to('/flash-message-test/create');
        }
    }
}
