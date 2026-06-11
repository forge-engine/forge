<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas\Services;

use App\Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use App\Modules\ForgeSaas\Dto\SaasPlan;
use App\Modules\ForgeSaas\Dto\SaasSubscription;
use App\Modules\ForgeSaas\Enums\SubscriptionStatus;
use App\Modules\ForgeMultiTenant\DTO\Tenant;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;

#[Service]
final class SubscriptionManager implements SubscriptionManagerInterface
{
    private ?SaasSubscription $subscription = null;
    private bool $loaded = false;

    public function __construct(private readonly Container $container) {}

    public function forTenant(Tenant $tenant): static
    {
        if ($this->loaded) {
            return $this;
        }

        $this->loaded = true;
        $this->subscription = $this->fetchSubscription($tenant->id);
        return $this;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->currentPlan()?->hasFeature($feature) ?? false;
    }

    public function withinLimit(string $resource, int $currentCount): bool
    {
        $limit = $this->limitFor($resource);
        if ($limit === -1) {
            return true;
        }
        return $currentCount < $limit;
    }

    public function limitFor(string $resource): int
    {
        $limit = $this->currentPlan()?->limitFor($resource) ?? PHP_INT_MAX;
        return $limit === PHP_INT_MAX ? PHP_INT_MAX : (int) $limit;
    }

    public function onPlan(string $planSlug): bool
    {
        return $this->currentPlan()?->slug === $planSlug;
    }

    public function isActive(): bool
    {
        if ($this->subscription === null) {
            return false;
        }
        return in_array($this->subscription->status, [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL], true);
    }

    public function currentPlan(): ?SaasPlan
    {
        return $this->subscription?->plan;
    }

    public function currentSubscription(): ?SaasSubscription
    {
        return $this->subscription;
    }

    private function fetchSubscription(string $tenantId): ?SaasSubscription
    {
        try {
            $qb = $this->container->get(QueryBuilderInterface::class);

            $sub = $qb->setTable('tenant_subscriptions')
                ->whereRaw('tenant_id = ?', [$tenantId])
                ->first();

            if (!$sub) {
                return null;
            }

            $plan = $qb->setTable('saas_plans')
                ->whereRaw('id = ?', [$sub['plan_id']])
                ->first();

            if (!$plan) {
                return null;
            }

            $planDto = new SaasPlan(
                id: $plan['id'],
                name: $plan['name'],
                slug: $plan['slug'],
                features: json_decode($plan['features'], true) ?? [],
                limits: json_decode($plan['limits'], true) ?? [],
                isActive: (bool) $plan['is_active'],
            );

            return new SaasSubscription(
                id: $sub['id'],
                tenantId: $sub['tenant_id'],
                plan: $planDto,
                status: SubscriptionStatus::from($sub['status']),
                trialEndsAt: $sub['trial_ends_at'] ? new \DateTimeImmutable($sub['trial_ends_at']) : null,
                currentPeriodEndsAt: $sub['current_period_ends_at'] ? new \DateTimeImmutable($sub['current_period_ends_at']) : null,
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
