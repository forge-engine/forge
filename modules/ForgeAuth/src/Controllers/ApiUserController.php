<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Controllers;

use App\Modules\ForgeAuth\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\ApiRoute;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\UserNotFoundException;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('api')]
final class ApiUserController
{
    use ControllerHelper;

    public function __construct(private UserRepository $userRepository)
    {
    }

    #[ApiRoute('/users', middlewares: ['api'])]
    public function index(Request $request): Response
    {
        $page = isset($request->queryParams['page']) && is_numeric($request->queryParams['page']) ? (int)$request->queryParams['page'] : 1;
        $limit = isset($request->queryParams['per_page']) && is_numeric($request->queryParams['per_page']) ? (int)$request->queryParams['per_page'] : 10;

        $page = max(1, $page);
        $limit = max(1, $limit);

        $baseUrl = $request->getUrl();

        $result = $this->userRepository->paginate($page, $limit, $baseUrl);
        return $this->apiResponse($result['data'])
            ->withMeta($result['meta']);
    }

    #[ApiRoute('/users/{id}', 'GET', ['api'])]
    public function show(Request $request, array $params): Response
    {
        $id = (int)$params["id"];
        try {
            $user = $this->userRepository->findById($id);
            return $this->apiResponse($user);
        } catch (UserNotFoundException $e) {
            return $this->apiError('User not found', 404);
        }
    }

    #[ApiRoute('/users/export', 'GET', ['api'])]
    public function export(Request $request): Response
    {
        $data = []; //$this->userRepository->getExportData();
        return $this->csvResponse($data, 'users_export.csv');
    }
}
