<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\PaymentMethodService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class PaymentMethodController
{
    use ControllerHelper;

    public function __construct(
        private readonly PaymentMethodService $paymentMethodService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing/payment-methods')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $methods = $tenantId ? $this->paymentMethodService->getForTenant($tenantId) : [];

        $data = [
            'title' => 'Payment Methods',
            'methods' => $methods,
        ];

        return $this->view(view: "pages/billing/payment-methods", data: $data);
    }

    #[Route('/billing/payment-methods', 'POST')]
    public function store(Request $request): Response
    {
        $tenantId = $this->billableResolver->resolve();

        if (!$tenantId) {
            Flash::set('error', 'Unable to identify billing entity.');
            return Redirect::to('/billing/payment-methods');
        }

        $this->paymentMethodService->create($tenantId, $request->postData);

        Flash::set('success', 'Payment method added.');
        return Redirect::to('/billing/payment-methods');
    }

    #[Route('/billing/payment-methods/{id}/delete', 'POST')]
    public function destroy(string $id): Response
    {
        $tenantId = $this->billableResolver->resolve();

        if (!$tenantId) {
            Flash::set('error', 'Unable to identify billing entity.');
            return Redirect::to('/billing/payment-methods');
        }

        $this->paymentMethodService->delete($id, $tenantId);

        Flash::set('success', 'Payment method removed.');
        return Redirect::to('/billing/payment-methods');
    }
}
