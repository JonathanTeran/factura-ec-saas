<?php

namespace App\Livewire\Panel\Pos;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\PosSession;
use App\Models\Tenant\Product;
use App\Services\Pos\PosService;
use Livewire\Component;

class PosDashboard extends Component
{
    // Session state
    public ?int $activeSessionId = null;
    public bool $showOpenSessionModal = false;

    // Open session form
    public int $companyId = 0;
    public int $branchId = 0;
    public int $emissionPointId = 0;
    public float $openingAmount = 0;

    // Close session form
    public bool $showCloseSessionModal = false;
    public float $closingAmount = 0;
    public string $closingNotes = '';

    // Transaction form
    public string $productSearch = '';
    public array $cart = [];
    public string $paymentMethod = 'cash';
    public ?int $customerId = null;
    public float $amountReceived = 0;
    public string $transactionNotes = '';

    public function mount(): void
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant->hasFeature('pos')) {
            abort(403, 'Tu plan no incluye Punto de Venta.');
        }

        // Check for active session
        $session = PosSession::where('tenant_id', $tenant->id)
            ->where('opened_by', auth()->id())
            ->open()
            ->first();

        if ($session) {
            $this->activeSessionId = $session->id;
        }
    }

    public function openSession(PosService $service): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
            'branchId' => 'required|exists:branches,id',
            'emissionPointId' => 'required|exists:emission_points,id',
            'openingAmount' => 'numeric|min:0',
        ]);

        try {
            $session = $service
                ->forTenant(auth()->user()->tenant)
                ->openSession([
                    'company_id' => $this->companyId,
                    'branch_id' => $this->branchId,
                    'emission_point_id' => $this->emissionPointId,
                    'opening_amount' => $this->openingAmount,
                ]);

            $this->activeSessionId = $session->id;
            $this->showOpenSessionModal = false;

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Caja abierta exitosamente.']);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function closeSession(PosService $service): void
    {
        $this->validate([
            'closingAmount' => 'required|numeric|min:0',
        ]);

        $session = PosSession::findOrFail($this->activeSessionId);

        try {
            $service
                ->forTenant(auth()->user()->tenant)
                ->closeSession($session, $this->closingAmount, $this->closingNotes ?: null);

            $this->activeSessionId = null;
            $this->showCloseSessionModal = false;
            $this->reset(['closingAmount', 'closingNotes']);

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Caja cerrada exitosamente.']);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function addToCart(int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->findOrFail($productId);

        // Check if already in cart
        foreach ($this->cart as &$item) {
            if ($item['product_id'] === $product->id) {
                $item['quantity']++;
                $item['total'] = $item['quantity'] * $item['unit_price'];
                return;
            }
        }

        $this->cart[] = [
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => 1,
            'unit_price' => (float) $product->unit_price,
            'discount' => 0,
            'tax_rate' => (float) ($product->tax_rate ?? 15),
            'total' => (float) $product->unit_price,
        ];
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);
            return;
        }

        $this->cart[$index]['quantity'] = $quantity;
        $this->cart[$index]['total'] = $quantity * $this->cart[$index]['unit_price'];
    }

    public function processTransaction(PosService $service): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'El carrito esta vacio.']);
            return;
        }

        $session = PosSession::findOrFail($this->activeSessionId);

        try {
            $transaction = $service
                ->forTenant(auth()->user()->tenant)
                ->createTransaction($session, [
                    'payment_method' => $this->paymentMethod,
                    'customer_id' => $this->customerId,
                    'amount_received' => $this->amountReceived ?: null,
                    'notes' => $this->transactionNotes ?: null,
                ], $this->cart);

            $change = $transaction->change_amount;
            $this->reset(['cart', 'paymentMethod', 'customerId', 'amountReceived', 'transactionNotes', 'productSearch']);
            $this->paymentMethod = 'cash';

            $message = 'Venta registrada. #' . $transaction->transaction_number;
            if ($change > 0) {
                $message .= ' - Cambio: $' . number_format($change, 2);
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getCartTotalProperty(): float
    {
        $subtotal = collect($this->cart)->sum('total');
        $tax = collect($this->cart)->sum(fn($item) => $item['total'] * $item['tax_rate'] / 100);
        return $subtotal + $tax;
    }

    public function getCartSubtotalProperty(): float
    {
        return collect($this->cart)->sum('total');
    }

    public function getCartTaxProperty(): float
    {
        return collect($this->cart)->sum(fn($item) => $item['total'] * $item['tax_rate'] / 100);
    }

    public function getChangeAmountProperty(): float
    {
        if ($this->amountReceived <= 0) return 0;
        return max(0, $this->amountReceived - $this->cartTotal);
    }

    public function getActiveSessionProperty(): ?PosSession
    {
        if (!$this->activeSessionId) return null;

        return PosSession::with(['branch', 'emissionPoint', 'transactions'])
            ->find($this->activeSessionId);
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->productSearch) < 2) return collect();

        return Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('main_code', 'like', "%{$this->productSearch}%")
                    ->orWhere('barcode', 'like', "%{$this->productSearch}%");
            })
            ->limit(10)
            ->get();
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)->get();
    }

    public function getBranchesProperty()
    {
        if (!$this->companyId) return collect();
        return Branch::where('company_id', $this->companyId)->get();
    }

    public function getEmissionPointsProperty()
    {
        if (!$this->branchId) return collect();
        return EmissionPoint::where('branch_id', $this->branchId)->get();
    }

    public function getCustomersProperty()
    {
        return Customer::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.panel.pos.pos-dashboard', [
            'session' => $this->activeSession,
            'searchResults' => $this->searchResults,
            'companies' => $this->companies,
            'branches' => $this->branches,
            'emissionPoints' => $this->emissionPoints,
            'customers' => $this->customers,
        ])->layout('layouts.tenant', ['title' => 'Punto de Venta']);
    }
}
