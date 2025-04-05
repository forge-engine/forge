<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Controllers;

use App\Modules\ForgeAuth\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Exceptions\UserNotFoundException;
use Forge\Traits\ControllerHelper;
use Forge\Traits\PaginationHelper;

#[Service]
#[Middleware('web')]
final class WebUserController
{
    use ControllerHelper;
    use PaginationHelper;

    public function __construct(private UserRepository $userRepository)
    {
    }

    #[Route('/users')]
    public function index(Request $request): Response
    {
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

    #[Route('/users/{id}')]
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

    #[Route('/users/export')]
    public function export(Request $request): Response
    {
        $data = []; //$this->userRepository->getExportData();
        return $this->csvResponse($data, 'users_export.csv');
    }
}
