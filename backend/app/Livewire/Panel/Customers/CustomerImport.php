<?php

namespace App\Livewire\Panel\Customers;

use App\Imports\CustomerImport as CustomerImportClass;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class CustomerImport extends Component
{
    use WithFileUploads;

    public $file;
    public bool $imported = false;
    public int $failedCount = 0;
    public array $errors = [];

    public function import()
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $import = new CustomerImportClass($tenantId);

        Excel::import($import, $this->file);

        $failures = $import->failures();
        $this->failedCount = count($failures);
        $this->errors = [];

        foreach ($failures as $failure) {
            $this->errors[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ];
        }

        $this->imported = true;
        $this->reset('file');

        if ($this->failedCount === 0) {
            session()->flash('success', 'Clientes importados exitosamente.');
        } else {
            session()->flash('warning', "Importacion completada con {$this->failedCount} errores.");
        }
    }

    public function resetImport()
    {
        $this->reset(['file', 'imported', 'failedCount', 'errors']);
    }

    public function render()
    {
        return view('livewire.panel.customers.customer-import');
    }
}
