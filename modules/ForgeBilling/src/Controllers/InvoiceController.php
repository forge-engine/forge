<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\InvoiceService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class InvoiceController
{
    use ControllerHelper;

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing/invoices')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $invoices = $tenantId ? $this->invoiceService->getForTenant($tenantId) : [];

        $data = [
            'title' => 'Invoices',
            'invoices' => $invoices,
        ];

        return $this->view(view: "pages/billing/invoices", data: $data);
    }

    #[Route('/billing/invoices/{id}')]
    public function show(string $id): Response
    {
        $invoice = $this->invoiceService->getById($id);
        $items = $invoice ? $this->invoiceService->getItems($id) : [];

        $data = [
            'title' => $invoice ? 'Invoice ' . $invoice->number : 'Invoice Not Found',
            'invoice' => $invoice,
            'items' => $items,
        ];

        return $this->view(view: "pages/billing/invoice-detail", data: $data);
    }
}
