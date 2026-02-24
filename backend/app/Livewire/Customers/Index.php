<?php

namespace App\Livewire\Customers;

use App\Models\Tenant\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(Customer $customer): void
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            session()->flash('error', 'No tienes permiso para eliminar este cliente.');
            return;
        }

        if ($customer->documents()->exists()) {
            session()->flash('error', 'No se puede eliminar el cliente porque tiene documentos asociados.');
            return;
        }

        $customer->delete();
        session()->flash('success', 'Cliente eliminado exitosamente.');
    }

    public function render()
    {
        $customers = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('identification_number', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->withCount('documents')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.customers.index', compact('customers'))
            ->layout('layouts.tenant', ['title' => 'Clientes']);
    }
}
