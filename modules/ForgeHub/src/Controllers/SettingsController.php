<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Middleware('hub-permissions')]
final class SettingsController
{
  use ControllerHelper;
  use SecurityHelper;

  public function __construct(
    private readonly ForgeAuthService $authService
  ) {
  }

  #[Route("/hub/settings")]
  public function index(): Response
  {
    $user = $this->authService->user();
    if ($user === null) {
      Flash::set('error', 'You must be logged in to view settings.');
      return Redirect::to('/auth/login');
    }

    $data = [
      'title' => 'Settings',
      'user' => $user,
    ];

    return $this->view(view: "pages/settings", data: $data);
  }

  #[Route("/hub/settings/password", "POST")]
  public function updatePassword(Request $request): Response
  {
    $user = $this->authService->user();
    if ($user === null) {
      Flash::set('error', 'You must be logged in to update your password.');
      return Redirect::to('/auth/login');
    }

    $data = $this->sanitize($request->postData);

    if (empty($data['current_password']) || empty($data['new_password']) || empty($data['confirm_password'])) {
      Flash::set('error', 'All password fields are required.');
      return Redirect::to('/hub/settings');
    }

    if (!password_verify($data['current_password'], $user->password)) {
      Flash::set('error', 'Current password is incorrect.');
      return Redirect::to('/hub/settings');
    }

    if ($data['new_password'] !== $data['confirm_password']) {
      Flash::set('error', 'New password and confirmation do not match.');
      return Redirect::to('/hub/settings');
    }

    if (strlen($data['new_password']) < 6) {
      Flash::set('error', 'New password must be at least 6 characters.');
      return Redirect::to('/hub/settings');
    }

    try {
      $user->password = password_hash($data['new_password'], PASSWORD_BCRYPT);
      $user->save();

      Flash::set('success', 'Password updated successfully.');
    } catch (\Throwable $e) {
      Flash::set('error', 'Failed to update password: ' . $e->getMessage());
    }

    return $this->redirect('/hub/settings');
  }
}
