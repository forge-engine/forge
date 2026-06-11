<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\BillingSubscriptionService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class SubscriptionController
{
    use ControllerHelper;

    public function __construct(
        private readonly BillingSubscriptionService $billingSubscriptionService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing/subscription')]
    public function show(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $subscription = $tenantId ? $this->billingSubscriptionService->forTenant($tenantId)->current() : null;
        $data = [
            'title' => 'Subscription',
            'subscription' => $subscription,
        ];

        return $this->view(view: "pages/billing/subscription", data: $data);
    }

    #[Route('/billing/subscription/cancel', 'POST')]
    public function cancel(): Response
    {
        $tenantId = $this->billableResolver->resolve();

        if (!$tenantId) {
            Flash::set('error', 'Unable to identify billing entity.');
            return Redirect::to('/billing/subscription');
        }

        $this->billingSubscriptionService->cancel($tenantId);
        Flash::set('success', 'Subscription cancelled.');
        return Redirect::to('/billing/subscription');
    }
}
