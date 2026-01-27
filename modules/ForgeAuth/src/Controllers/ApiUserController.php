<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Controllers;

use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Models\User;
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
#[Middleware("api")]
final class ApiUserController
{
    use ControllerHelper;
    use AuthorizeRequests;
    use PaginationHelper;

    public function __construct() {}

    #[ApiRoute("/users", permissions: [Permission::USER_READ->value])]
    public function index(Request $request): Response
    {
        $this->authorize($request, [Permission::USER_READ->value]);

        $paginationParams = $this->getPaginationParamsForApi($request);

        $searchFields = ["email", "identifier", "status"];

        $paginator = User::paginate(
            $paginationParams["page"],
            $paginationParams["limit"],
            $paginationParams["column"],
            $paginationParams["direction"],
            $paginationParams["search"],
            [
                "searchFields" => $searchFields,
                "filters" => $paginationParams["filters"],
                "baseUrl" => $paginationParams["baseUrl"],
                "queryParams" => $paginationParams["queryParams"],
            ],
        );

        return $this->apiResponse($paginator->items())->withMeta(
            $paginator->meta(),
        );
    }

    #[ApiRoute("/users/{id}", "GET", ["api"])]
    public function show(Request $request, string $id): Response
    {
        $userId = (int) $id;
        try {
            $user = User::findById($userId);
            return $this->apiResponse($user);
        } catch (UserNotFoundException $e) {
            return $this->apiError("User not found", 404);
        }
    }

    #[ApiRoute("/users/export", "GET", ["api"])]
    public function export(Request $request): Response
    {
        $data = [];
        return $this->csvResponse($data, "users_export.csv");
    }
}
