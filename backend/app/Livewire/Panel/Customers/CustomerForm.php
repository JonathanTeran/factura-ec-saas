<?php

namespace App\Livewire\Panel\Customers;

use App\Models\Tenant\Customer;
use App\Enums\IdentificationType;
use Livewire\Component;

class CustomerForm extends Component
{
    public ?Customer $customer = null;

    public string $identification_type = 'ruc';
    public string $identification = '';
    public string $business_name = '';
    public string $trade_name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        $customerId = $this->customer?->id;

        return [
            'identification_type' => 'required|in:ruc,cedula,pasaporte,consumidor_final,exterior',
            'identification' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($customerId) {
                    $exists = Customer::where('tenant_id', auth()->user()->tenant_id)
                        ->where('identification', $value)
                        ->when($customerId, fn($q) => $q->where('id', '!=', $customerId))
                        ->exists();

                    if ($exists) {
                        $fail('Esta identificación ya está registrada.');
                    }
                },
            ],
            'business_name' => 'required|string|max:300',
            'trade_name' => 'nullable|string|max:300',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:300',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'identification_type.required' => 'Seleccione el tipo de identificación.',
        'identification.required' => 'La identificación es requerida.',
        'business_name.required' => 'La razón social es requerida.',
        'email.email' => 'Ingrese un correo electrónico válido.',
    ];

    public function mount(?Customer $customer = null): void
    {
        if ($customer && $customer->exists) {
            if ($customer->tenant_id !== auth()->user()->tenant_id) {
                abort(403);
            }

            $this->customer = $customer;

            $this->identification_type = $this->customer->identification_type;
            $this->identification = $this->customer->identification;
            $this->business_name = $this->customer->business_name;
            $this->trade_name = $this->customer->trade_name ?? '';
            $this->email = $this->customer->email ?? '';
            $this->phone = $this->customer->phone ?? '';
            $this->address = $this->customer->address ?? '';
            $this->is_active = $this->customer->is_active;
        }
    }

    public function updatedIdentificationType(): void
    {
        if ($this->identification_type === 'consumidor_final') {
            $this->identification = '9999999999999';
            $this->business_name = 'CONSUMIDOR FINAL';
        } else {
            if ($this->identification === '9999999999999') {
                $this->identification = '';
                $this->business_name = '';
            }
        }
    }

    public function validateIdentification(): void
    {
        if (empty($this->identification)) {
            return;
        }

        $isValid = match ($this->identification_type) {
            'ruc' => $this->validateRuc($this->identification),
            'cedula' => $this->validateCedula($this->identification),
            default => true,
        };

        if (!$isValid) {
            $this->addError('identification', 'La identificación no es válida.');
        }
    }

    private function validateRuc(string $ruc): bool
    {
        if (strlen($ruc) !== 13) {
            return false;
        }

        if (!preg_match('/^[0-9]{13}$/', $ruc)) {
            return false;
        }

        // Las 3 últimas deben ser 001
        if (substr($ruc, -3) !== '001') {
            return false;
        }

        return $this->validateCedula(substr($ruc, 0, 10));
    }

    private function validateCedula(string $cedula): bool
    {
        if (strlen($cedula) !== 10) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = (int) $cedula[$i] * $coeficientes[$i];
            $suma += $valor >= 10 ? $valor - 9 : $valor;
        }

        $residuo = $suma % 10;
        $verificador = $residuo === 0 ? 0 : 10 - $residuo;

        return $verificador === (int) $cedula[9];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tenant_id' => auth()->user()->tenant_id,
            'identification_type' => $this->identification_type,
            'identification' => $this->identification,
            'business_name' => $this->business_name,
            'trade_name' => $this->trade_name ?: null,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->customer) {
            $this->customer->update($data);
            $message = 'Cliente actualizado correctamente.';
        } else {
            Customer::create($data);
            $message = 'Cliente creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->redirect(route('panel.customers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.panel.customers.customer-form', [
            'identificationTypes' => IdentificationType::cases(),
        ])->layout('layouts.tenant', [
            'title' => $this->customer ? 'Editar Cliente' : 'Nuevo Cliente',
        ]);
    }
}
