<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\BillingPortalService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class BillingDashboardController
{
    use ControllerHelper;

    public function __construct(
        private readonly BillingPortalService $billingPortalService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();

        $data = [];

        if (!$tenantId) {

            $data = [
                'title' => 'Billing Overview',
                'subscription' => null,
                'latestInvoice' => null,
                'invoices' => [],
                'plans' => [],
                'isActive' => false,
                'onTrial' => false,
            ];
            return $this->view(view: "pages/billing/dashboard", data: $data);
        }

        $dataOverview = $this->billingPortalService->overview($tenantId);

        $data = [
            'title' => 'Billing Overview',
            'data' => $dataOverview,
        ];
        return $this->view(view: "pages/billing/dashboard", data: $data);
    }
}
