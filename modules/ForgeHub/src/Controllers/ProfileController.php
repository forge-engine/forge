<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Models\Profile;
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
final class ProfileController
{
  use ControllerHelper;
  use SecurityHelper;

  public function __construct(
    private readonly ForgeAuthService $authService
  ) {
  }

  #[Route("/hub/profile")]
  public function index(): Response
  {
    $user = $this->authService->user();
    if ($user === null) {
      Flash::set('error', 'You must be logged in to view your profile.');
      return Redirect::to('/auth/login');
    }

    $profile = $user->relation('profile')->first();

    $data = [
      'title' => 'Profile',
      'user' => $user,
      'profile' => $profile,
    ];

    return $this->view(view: "pages/profile", data: $data);
  }

  #[Route("/hub/profile", "POST")]
  public function update(Request $request): Response
  {
    $user = $this->authService->user();
    if ($user === null) {
      Flash::set('error', 'You must be logged in to update your profile.');
      return Redirect::to('/auth/login');
    }

    $data = $this->sanitize($request->postData);

    try {
      if (isset($data['identifier'])) {
        $user->identifier = $data['identifier'];
      }

      if (isset($data['email'])) {
        $user->email = $data['email'];
      }

      $user->save();

      $profile = $user->relation('profile')->first();
      if ($profile === null) {
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->first_name = $data['first_name'] ?? '';
      }

      if (isset($data['first_name'])) {
        $profile->first_name = $data['first_name'];
      }

      if (isset($data['last_name'])) {
        $profile->last_name = $data['last_name'] ?? null;
      }

      if (isset($data['phone'])) {
        $profile->phone = $data['phone'] ?? null;
      }

      $profile->save();

      Flash::set('success', 'Profile updated successfully.');
    } catch (\Throwable $e) {
      Flash::set('error', 'Failed to update profile: ' . $e->getMessage());
    }

    return Redirect::to('/hub/profile');
  }
}
