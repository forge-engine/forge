<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Controllers;

use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\ApiRoute;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\UserNotFoundException;
use Forge\Traits\AuthorizeRequests;
use Forge\Traits\ControllerHelper;
use Forge\Traits\PaginationHelper;

#[Service]
#[Middleware('api')]
final class ApiUserController
{
    use ControllerHelper;
    use AuthorizeRequests;
    use PaginationHelper;

    public function __construct(private UserRepository $userRepository)
    {
    }

    #[ApiRoute('/users', permissions: [Permission::UsersRead->value])]
    public function index(Request $request): Response
    {
        $this->authorize($request, [Permission::UsersRead->value]);

        $paginationParams = $this->getPaginationParams($request);

        $result = $this->userRepository->paginate(
            $paginationParams['page'],
            $paginationParams['limit'],
            $paginationParams['column'],
            $paginationParams['direction'],
            $paginationParams['search']
        );
        return $this->apiResponse($result['data'])
            ->withMeta($result['meta']);
    }

    #[ApiRoute('/users/{id}', 'GET', ['api'])]
    public function show(Request $request, string $id): Response
    {
        $userId = (int)$id;
        try {
            $user = $this->userRepository->findById($userId);
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
