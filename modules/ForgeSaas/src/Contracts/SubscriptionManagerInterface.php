<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas\Contracts;

use App\Modules\ForgeSaas\Dto\SaasPlan;
use App\Modules\ForgeSaas\Dto\SaasSubscription;
use App\Modules\ForgeMultiTenant\DTO\Tenant;

interface SubscriptionManagerInterface
{
    public function forTenant(Tenant $tenant): static;

    public function hasFeature(string $feature): bool;

    public function withinLimit(string $resource, int $currentCount): bool;

    public function limitFor(string $resource): int;

    public function onPlan(string $planSlug): bool;

    public function isActive(): bool;

    public function currentPlan(): ?SaasPlan;

    public function currentSubscription(): ?SaasSubscription;
}