<?php

namespace App\Livewire\Panel\Accounting;

use App\Models\Accounting\CostCenter;
use Livewire\Component;

class CostCenterList extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $code = '';
    public string $name = '';
    public ?int $parent_id = null;
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:150',
            'parent_id' => 'nullable|integer|exists:cost_centers,id',
            'is_active' => 'boolean',
        ];
    }

    public function getCompanyProperty()
    {
        return auth()->user()->tenant->companies()->first();
    }

    public function getCostCentersProperty()
    {
        if (!$this->company) {
            return collect();
        }

        return CostCenter::where('company_id', $this->company->id)
            ->roots()
            ->with(['children' => function ($q) {
                $q->with(['children' => function ($q2) {
                    $q2->orderBy('code');
                }])->orderBy('code');
            }])
            ->orderBy('code')
            ->get();
    }

    public function getAllCostCentersProperty()
    {
        if (!$this->company) {
            return collect();
        }

        return CostCenter::where('company_id', $this->company->id)
            ->orderBy('code')
            ->get();
    }

    public function openForm(?int $id = null): void
    {
        $this->resetForm();

        if ($id) {
            $costCenter = CostCenter::findOrFail($id);
            $this->editingId = $costCenter->id;
            $this->code = $costCenter->code;
            $this->name = $costCenter->name;
            $this->parent_id = $costCenter->parent_id;
            $this->is_active = $costCenter->is_active;
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->parent_id = null;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate();

        $company = $this->company;

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se encontro una empresa configurada.',
            ]);
            return;
        }

        // Verificar codigo unico
        $codeExists = CostCenter::where('company_id', $company->id)
            ->where('code', $this->code)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($codeExists) {
            $this->addError('code', 'Este codigo ya esta en uso.');
            return;
        }

        // Calcular nivel
        $level = 1;
        if ($this->parent_id) {
            $parent = CostCenter::find($this->parent_id);
            $level = $parent ? $parent->level + 1 : 1;
        }

        $data = [
            'company_id' => $company->id,
            'tenant_id' => auth()->user()->tenant_id,
            'code' => $this->code,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'level' => $level,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $costCenter = CostCenter::findOrFail($this->editingId);
            $costCenter->update($data);
            $message = 'Centro de costo actualizado.';
        } else {
            CostCenter::create($data);
            $message = 'Centro de costo creado.';
        }

        $this->closeForm();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);
    }

    public function toggleActive(int $id): void
    {
        $costCenter = CostCenter::findOrFail($id);
        $costCenter->update(['is_active' => !$costCenter->is_active]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $costCenter->is_active ? 'Centro de costo activado.' : 'Centro de costo desactivado.',
        ]);
    }

    public function delete(int $id): void
    {
        $costCenter = CostCenter::findOrFail($id);

        if ($costCenter->children()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se puede eliminar un centro de costo con sub-centros.',
            ]);
            return;
        }

        $costCenter->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Centro de costo eliminado.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.accounting.cost-center-list', [
            'costCenters' => $this->costCenters,
            'allCostCenters' => $this->allCostCenters,
        ])->layout('layouts.tenant', ['title' => 'Centros de Costo']);
    }
}
