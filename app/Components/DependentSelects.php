<?php

namespace App\Components;

use App\Services\LocationService;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Attributes\Service;
use App\Modules\ForgeWire\Core\WireComponent;
use Forge\Core\Session\SessionInterface;

final class DependentSelects extends WireComponent
{
    #[Service] private LocationService $loc;
    #[Service] private SessionInterface $session;

    #[State] public string $country = '';
    #[State] public string $state   = '';
    #[State] public string $city    = '';

    #[State] public array $countries = [];
    #[State] public array $states    = [];
    #[State] public array $cities    = [];

    public function mount(array $props = []): void
    {
        $this->country = $props['country'] ?? $this->country;
        $this->state   = $props['state']   ?? $this->state;
        $this->city    = $props['city']    ?? $this->city;
        $this->prepareOptions();
    }

    private function prepareOptions(): void
    {
        $this->countries = $this->session->get('loc:countries')
            ?? tap($this->loc->countries(), fn ($v) => $this->session->set('loc:countries', $v));

        if ($this->country) {
            $this->states = $this->session->get("loc:states:{$this->country}")
                ?? tap($this->loc->states($this->country), fn ($v) => $this->session->set("loc:states:{$this->country}", $v));
            if (!in_array($this->state, array_column($this->states, 'value'), true)) {
                $this->state = '';
                $this->city  = '';
            }
        } else {
            $this->states = [];
            $this->state  = '';
            $this->city   = '';
        }

        if ($this->country && $this->state) {
            $key = "loc:cities:{$this->country}-{$this->state}";
            $this->cities = $this->session->get($key)
                ?? tap($this->loc->cities($this->country, $this->state), fn ($v) => $this->session->set($key, $v));

            if (!in_array($this->city, array_column($this->cities, 'value'), true)) {
                $this->city = '';
            }
        } else {
            $this->cities = [];
            $this->city   = '';
        }
    }

    public function render(): string
    {
        $this->prepareOptions();

        return $this->view('DependentSelects/View', [
            'countries' => $this->countries,
            'states'    => $this->states,
            'cities'    => $this->cities,
            'country'   => $this->country,
            'state'     => $this->state,
            'city'      => $this->city,
        ]);
    }
}
