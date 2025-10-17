<?php

declare(strict_types=1);

namespace App\Components;

use App\Dto\SignupDTO;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\DTO;
use App\Modules\ForgeWire\Attributes\Service;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;
use App\Services\SignupService;

final class SignupWizard extends WireComponent
{
    #[State]
    public int $step = 1;
    #[State]
    public array $errors = [];
    #[State]
    public ?string $notice = null;

    #[DTO(SignupDTO::class)]
    public SignupDTO $form;

    #[Service(SignupService::class)]
    public SignupService $signup;

    public function mount(array $props = []): void
    {
        $this->step = (int)($props['step'] ?? 1);
        $this->form = $this->form ?? new SignupDTO();
    }

    #[Action]
    public function input(...$keys): void
    {
        $this->notice = null;
        $this->errors = [];
    }

    #[Action]
    public function back(): void
    {
        $this->notice = null;
        $this->errors = [];
        $this->step = max(1, $this->step - 1);
    }

    #[Action]
    public function next(): void
    {
        $this->notice = null;
        $this->errors = $this->validateStep($this->step);
        if ($this->errors) {
            return;
        }
        $this->step = min(3, $this->step + 1);
    }

    #[Action]
    public function goto(int $step): void
    {
        $this->errors = [];
        $want = max(1, min(3, $step));
        if ($want > $this->step) {
            $errs = $this->validateStep($this->step);
            if ($errs) {
                $this->errors = $errs;
                return;
            }
        }
        $this->step = $want;
    }

    #[Action]
    public function submit(): void
    {
        $errs = $this->validateAll();
        $this->errors = $errs;
        if ($errs) {
            return;
        }

        $id = $this->signup->register($this->form);
        $this->notice = "Welcome, {$this->form->fullName}! (#{$id})";

        $this->form->password = '';
        $this->form->confirmPassword = '';
        $this->step = 1;
    }

    private function validateStep(int $s): array
    {
        $e = [];
        if ($s === 1) {
            if (!filter_var($this->form->email, FILTER_VALIDATE_EMAIL)) {
                $e['email'] = 'Valid email required.';
            }
            if (strlen($this->form->password) < 6) {
                $e['password'] = 'Min 6 chars.';
            }
            if ($this->form->password !== $this->form->confirmPassword) {
                $e['confirmPassword'] = 'Passwords do not match.';
            }
        } elseif ($s === 2) {
            if ($this->form->fullName === '') {
                $e['fullName'] = 'Required.';
            }
            if ($this->form->role === '') {
                $e['role'] = 'Required.';
            }
        } elseif ($s === 3) {
            if (!$this->form->terms) {
                $e['terms'] = 'Please accept terms.';
            }
        }
        return $e;
    }

    private function validateAll(): array
    {
        $all = [];
        foreach ([1,2,3] as $s) {
            $all += $this->validateStep($s);
        }
        return $all;
    }

    public function render(): string
    {
        return raw($this->view('Wizard/Signup', [
            'step'   => $this->step,
            'form'   => $this->form,
            'errors' => $this->errors,
            'notice' => $this->notice,
        ]));
    }
}
