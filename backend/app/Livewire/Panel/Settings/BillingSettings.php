<?php

namespace App\Livewire\Panel\Settings;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Billing\BankAccount;
use App\Models\Billing\Plan;
use App\Models\Billing\PaymentMethodSetting;
use App\Models\Billing\Subscription;
use App\Models\Billing\Payment;
use App\Models\Billing\Coupon;
use App\Services\Billing\BillingService;
use App\Models\User;
use App\Enums\UserRole;
use App\Notifications\BankTransferPendingNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithFileUploads;

class BillingSettings extends Component
{
    use WithFileUploads;

    // Plan selection
    public ?int $selectedPlanId = null;
    public string $billingCycle = 'monthly';
    public string $couponCode = '';
    public ?array $couponInfo = null;

    // Payment
    public string $paymentMethod = 'credit_card';
    public string $cardToken = '';
    public string $billingName = '';
    public string $billingEmail = '';
    public string $billingIdentification = '';
    public string $billingAddress = '';
    public string $billingPhone = '';

    // Bank transfer
    public $transferReceipt = null;
    public string $transferReference = '';

    // UI
    public bool $showUpgradeModal = false;
    public bool $showCancelModal = false;
    public string $cancelReason = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->billingName = $user->name;
        $this->billingEmail = $user->email;

        $enabledMethods = PaymentMethodSetting::enabled()->get();
        if ($enabledMethods->count() === 1) {
            $this->paymentMethod = $enabledMethods->first()->code;
        } elseif ($enabledMethods->isNotEmpty()) {
            $this->paymentMethod = $enabledMethods->first()->code;
        }
    }

    public function getPlansProperty()
    {
        return Plan::active()->ordered()->get();
    }

    public function getCurrentSubscriptionProperty(): ?Subscription
    {
        return auth()->user()->tenant?->activeSubscription;
    }

    public function getPendingSubscriptionProperty(): ?Subscription
    {
        return Subscription::where('tenant_id', auth()->user()->tenant_id)
            ->where('status', SubscriptionStatus::INCOMPLETE)
            ->latest()
            ->first();
    }

    public function getCurrentPlanProperty(): ?Plan
    {
        return auth()->user()->tenant?->currentPlan;
    }

    public function getPaymentHistoryProperty()
    {
        return Payment::where('tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function getBankAccountsProperty()
    {
        return BankAccount::active()->get();
    }

    public function getEnabledPaymentMethodsProperty()
    {
        return PaymentMethodSetting::enabled()->get();
    }

    public function getUsageStatsProperty(): array
    {
        $tenant = auth()->user()->tenant;
        $plan = $this->currentPlan;

        if (!$plan) {
            return [];
        }

        return [
            'documents' => [
                'used' => $tenant->documentsThisMonth()->count(),
                'limit' => $plan->max_documents_per_month,
                'unlimited' => $plan->max_documents_per_month === -1,
            ],
            'users' => [
                'used' => $tenant->users()->count(),
                'limit' => $plan->max_users,
                'unlimited' => $plan->max_users === -1,
            ],
            'companies' => [
                'used' => $tenant->companies()->count(),
                'limit' => $plan->max_companies,
                'unlimited' => $plan->max_companies === -1,
            ],
        ];
    }

    public function openUpgradeModal(?int $planId = null): void
    {
        $this->selectedPlanId = $planId;
        $this->couponCode = '';
        $this->couponInfo = null;
        $this->showUpgradeModal = true;
    }

    public function closeUpgradeModal(): void
    {
        $this->showUpgradeModal = false;
        $this->selectedPlanId = null;
        $this->transferReceipt = null;
        $this->transferReference = '';

        $firstMethod = PaymentMethodSetting::enabled()->first();
        $this->paymentMethod = $firstMethod?->code ?? 'transfer';
    }

    public function applyCoupon(): void
    {
        if (empty($this->couponCode)) {
            return;
        }

        $coupon = Coupon::findByCode($this->couponCode);

        if (!$coupon || !$coupon->isValid()) {
            $this->addError('couponCode', 'Cupón inválido o expirado.');
            $this->couponInfo = null;
            return;
        }

        if (!$coupon->canBeUsedByTenant(auth()->user()->tenant_id)) {
            $this->addError('couponCode', 'Ya has usado este cupón.');
            $this->couponInfo = null;
            return;
        }

        if ($this->selectedPlanId && !$coupon->isApplicableToPlan($this->selectedPlanId)) {
            $this->addError('couponCode', 'Este cupón no aplica al plan seleccionado.');
            $this->couponInfo = null;
            return;
        }

        $plan = Plan::find($this->selectedPlanId);
        $price = $this->billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $discount = $coupon->calculateDiscount($price);

        $this->couponInfo = [
            'code' => $coupon->code,
            'name' => $coupon->name,
            'discount' => $discount,
            'discount_label' => $coupon->getDiscountLabel(),
        ];

        $this->resetErrorBag('couponCode');
    }

    public function removeCoupon(): void
    {
        $this->couponCode = '';
        $this->couponInfo = null;
    }

    public function getSelectedPlanPriceProperty(): float
    {
        $plan = Plan::find($this->selectedPlanId);

        if (!$plan) {
            return 0;
        }

        $price = $this->billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $discount = $this->couponInfo['discount'] ?? 0;

        return max(0, $price - $discount);
    }

    public function subscribe(): void
    {
        $enabledCodes = $this->enabledPaymentMethods->pluck('code')->implode(',');

        $rules = [
            'selectedPlanId' => 'required|exists:plans,id',
            'billingCycle' => 'required|in:monthly,yearly',
            'paymentMethod' => "required|in:{$enabledCodes}",
            'billingName' => 'required|string|max:300',
            'billingEmail' => 'required|email',
            'billingIdentification' => 'required|string|max:20',
        ];

        if ($this->paymentMethod === 'transfer') {
            $rules['transferReceipt'] = 'required|image|max:5120';
            $rules['transferReference'] = 'required|string|max:100';
        }

        $this->validate($rules, [
            'selectedPlanId.required' => 'Debes seleccionar un plan.',
            'billingName.required' => 'El nombre es obligatorio.',
            'billingEmail.required' => 'El email es obligatorio.',
            'billingEmail.email' => 'El email no es válido.',
            'billingIdentification.required' => 'El RUC o Cédula es obligatorio.',
            'billingIdentification.max' => 'El RUC o Cédula no debe superar 20 caracteres.',
            'transferReceipt.required' => 'El comprobante de transferencia es obligatorio.',
            'transferReceipt.image' => 'El comprobante debe ser una imagen (JPG, PNG, etc.).',
            'transferReceipt.max' => 'El comprobante no debe superar los 5MB.',
            'transferReference.required' => 'La referencia de transferencia es obligatoria.',
        ]);

        try {
            $plan = Plan::find($this->selectedPlanId);

            if ($this->paymentMethod === 'transfer') {
                $this->createBankTransferSubscription($plan);
            } else {
                $this->createCardSubscription($plan);
            }
        } catch (\Exception $e) {
            $this->addError('payment', $e->getMessage());
        }
    }

    private function createBankTransferSubscription(Plan $plan): void
    {
        $tenant = auth()->user()->tenant;

        // Cancel previous subscription if exists
        $tenant->activeSubscription?->cancel('Upgrade a nuevo plan');

        // Handle coupon
        $coupon = null;
        $discountAmount = 0;

        if ($this->couponCode) {
            $coupon = Coupon::findByCode($this->couponCode);
            if ($coupon && $coupon->canBeUsedByTenant($tenant->id)) {
                $price = $this->billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
                $discountAmount = $coupon->calculateDiscount($price);
                $coupon->incrementUses();
            }
        }

        // Calculate price
        $price = $this->billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $finalPrice = max(0, $price - $discountAmount);

        // Store receipt
        $receiptPath = $this->transferReceipt->store('payment-receipts', 'public');

        // Create subscription in INCOMPLETE status (pending payment approval)
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::INCOMPLETE,
            'billing_cycle' => $this->billingCycle,
            'amount' => $finalPrice,
            'discount_percent' => $coupon ? $coupon->discount_value : 0, 
            'coupon_code' => $coupon?->code,
            'currency' => 'USD',
            'starts_at' => now(),
            'ends_at' => $this->billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
        ]);

        // Calculate tax
        $taxAmount = round($finalPrice * 0.15, 2);

        // Create payment record with PENDING status
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::PENDING,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'amount' => $finalPrice,
            'tax_amount' => $taxAmount,
            'total_amount' => $finalPrice + $taxAmount,
            'currency' => 'USD',
            'transfer_receipt_path' => $receiptPath,
            'transfer_reference' => $this->transferReference,
            'billing_name' => $this->billingName,
            'billing_email' => $this->billingEmail,
            'billing_identification' => $this->billingIdentification,
            'billing_address' => $this->billingAddress,
            'billing_phone' => $this->billingPhone,
            'description' => "Transferencia bancaria - {$plan->name}",
        ]);

        // Update tenant plan
        $tenant->update(['current_plan_id' => $plan->id]);

        // Notify admins of pending transfer
        $admins = User::withoutGlobalScopes()->where('role', UserRole::SUPER_ADMIN)->get();
        foreach ($admins as $admin) {
            $admin->notify(new BankTransferPendingNotification($payment));
        }

        $this->closeUpgradeModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tu comprobante ha sido enviado. Revisaremos tu pago y activaremos tu suscripción en un plazo de 24h.',
        ]);
    }

    private function createCardSubscription(Plan $plan): void
    {
        $billingService = app(BillingService::class);

        $subscription = $billingService->createSubscription(
            auth()->user()->tenant,
            $plan,
            $this->billingCycle,
            [
                'payment_method' => $this->paymentMethod,
                'card_token' => $this->cardToken,
                'billing_name' => $this->billingName,
                'billing_email' => $this->billingEmail,
                'billing_identification' => $this->billingIdentification,
                'billing_address' => $this->billingAddress,
                'billing_phone' => $this->billingPhone,
            ],
            $this->couponCode ?: null
        );

        $this->closeUpgradeModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Suscripción a {$plan->name} activada correctamente.",
        ]);
    }

    public function openCancelModal(): void
    {
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function cancelSubscription(): void
    {
        $subscription = $this->currentSubscription;

        if (!$subscription) {
            return;
        }

        try {
            $billingService = app(BillingService::class);
            $billingService->cancelSubscription($subscription, $this->cancelReason);

            $this->closeCancelModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Suscripción cancelada. Podrás seguir usando el servicio hasta ' . $subscription->ends_at->format('d/m/Y'),
            ]);

        } catch (\Exception $e) {
            $this->addError('cancel', $e->getMessage());
        }
    }

    public function resumeSubscription(): void
    {
        $subscription = $this->currentSubscription;

        if (!$subscription || !$subscription->isCanceled()) {
            return;
        }

        $subscription->resume();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Suscripción reactivada correctamente.',
        ]);
    }

    public function downloadInvoice(int $paymentId)
    {
        $payment = Payment::where('tenant_id', auth()->user()->tenant_id)
            ->with(['subscription.plan', 'tenant'])
            ->findOrFail($paymentId);

        $pdf = Pdf::loadView('pdf.payment-invoice', [
            'payment' => $payment,
        ])->setPaper('a4');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "comprobante-{$payment->invoice_number}.pdf"
        );
    }

    public function render()
    {
        return view('livewire.panel.settings.billing-settings', [
            'plans' => $this->plans,
            'currentSubscription' => $this->currentSubscription,
            'pendingSubscription' => $this->pendingSubscription,
            'currentPlan' => $this->currentPlan,
            'paymentHistory' => $this->paymentHistory,
            'usageStats' => $this->usageStats,
            'selectedPlanPrice' => $this->selectedPlanPrice,
            'bankAccounts' => $this->bankAccounts,
            'enabledPaymentMethods' => $this->enabledPaymentMethods,
        ])->layout('layouts.tenant', ['title' => 'Facturación']);
    }
}
