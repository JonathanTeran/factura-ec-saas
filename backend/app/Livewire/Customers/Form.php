<?php

namespace App\Livewire\Customers;

use App\Enums\IdentificationType;
use App\Models\Tenant\Customer;
use Livewire\Component;

class Form extends Component
{
    public ?Customer $customer = null;

    public string $identification_type = 'RUC';
    public string $identification_number = '';
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public bool $is_active = true;

    public function mount(?Customer $customer = null): void
    {
        if ($customer && $customer->exists) {
            if ($customer->tenant_id !== auth()->user()->tenant_id) {
                abort(403);
            }

            $this->customer = $customer;
            $this->identification_type = $customer->identification_type->value;
            $this->identification_number = $customer->identification_number;
            $this->name = $customer->name;
            $this->email = $customer->email ?? '';
            $this->phone = $customer->phone ?? '';
            $this->address = $customer->address ?? '';
            $this->is_active = $customer->is_active;
        }
    }

    protected function rules(): array
    {
        $customerId = $this->customer?->id;

        return [
            'identification_type' => ['required', 'in:' . implode(',', array_column(IdentificationType::cases(), 'value'))],
            'identification_number' => [
                'required',
                'string',
                'max:20',
                "unique:customers,identification_number,{$customerId},id,tenant_id," . auth()->user()->tenant_id,
            ],
            'name' => ['required', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:300'],
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'identification_type.required' => 'El tipo de identificación es requerido.',
            'identification_number.required' => 'El número de identificación es requerido.',
            'identification_number.unique' => 'Este número de identificación ya está registrado.',
            'name.required' => 'El nombre es requerido.',
            'email.email' => 'El correo electrónico no es válido.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->customer) {
            $this->customer->update($validated);
            session()->flash('success', 'Cliente actualizado exitosamente.');
        } else {
            Customer::create([
                'tenant_id' => auth()->user()->tenant_id,
                ...$validated,
            ]);
            session()->flash('success', 'Cliente creado exitosamente.');
        }

        $this->redirect(route('tenant.customers.index'));
    }

    public function render()
    {
        return view('livewire.customers.form', [
            'identificationTypes' => IdentificationType::cases(),
            'isEditing' => $this->customer !== null,
        ])->layout('layouts.tenant', [
            'title' => $this->customer ? 'Editar Cliente' : 'Nuevo Cliente',
        ]);
    }
}
