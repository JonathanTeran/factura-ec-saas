<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Http\Requests\Api\SubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PlanResource;
use App\Models\Billing\BankAccount;
use App\Models\Billing\Coupon;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\User;
use App\Notifications\BankTransferPendingNotification;
use App\Services\Billing\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Suscripciones y Facturación
 */
class SubscriptionController extends ApiController
{
    public function __construct(
        private BillingService $billingService,
    ) {}

    /**
     * Listar planes disponibles
     *
     * Retorna todos los planes de suscripción activos.
     */
    public function plans(Request $request): JsonResponse
    {
        $plans = Plan::active()->ordered()->get();

        return $this->success([
            'plans' => PlanResource::collection($plans),
        ]);
    }

    /**
     * Suscripción actual
     *
     * Retorna la suscripción activa del tenant con detalles del plan.
     */
    public function current(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $subscription = $tenant->activeSubscription;

        if (!$subscription) {
            return $this->success([
                'subscription' => null,
                'plan' => $tenant->currentPlan ? new PlanResource($tenant->currentPlan) : null,
            ]);
        }

        $subscription->load(['plan', 'coupon']);

        return $this->success([
            'subscription' => new SubscriptionResource($subscription),
        ]);
    }

    /**
     * Crear suscripción
     *
     * Suscribe al tenant a un plan con procesamiento de pago.
     */
    public function subscribe(SubscriptionRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $plan = Plan::findOrFail($request->plan_id);

        try {
            $subscription = $this->billingService->createSubscription(
                $tenant,
                $plan,
                $request->billing_cycle,
                $request->validated(),
                $request->coupon_code
            );

            return $this->created([
                'subscription' => new SubscriptionResource($subscription->load(['plan', 'coupon'])),
            ], 'Suscripción creada exitosamente');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Cancelar suscripción
     *
     * Cancela la suscripción activa. El acceso continúa hasta la fecha de fin del periodo.
     */
    public function cancel(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $subscription = $tenant->activeSubscription;

        if (!$subscription) {
            return $this->error('No tienes una suscripción activa.', 400);
        }

        try {
            $this->billingService->cancelSubscription(
                $subscription,
                $request->input('reason')
            );

            $message = 'Suscripción cancelada.';
            if ($subscription->ends_at) {
                $message .= ' Tendrás acceso hasta ' . $subscription->ends_at->format('d/m/Y');
            }

            return $this->success([
                'subscription' => new SubscriptionResource($subscription->fresh()),
            ], $message);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Reactivar suscripción
     *
     * Reactiva una suscripción cancelada que aún no ha expirado.
     */
    public function resume(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        // Look for cancelled subscription (not through activeSubscription which filters by active/trialing)
        $subscription = $tenant->subscriptions()
            ->where('status', \App\Enums\SubscriptionStatus::CANCELLED)
            ->latest('created_at')
            ->first();

        if (!$subscription) {
            return $this->error('No tienes una suscripción cancelada para reactivar.', 400);
        }

        if ($subscription->hasEnded()) {
            return $this->error('La suscripción ya ha expirado. Debes crear una nueva.', 400);
        }

        $subscription->resume();

        return $this->success([
            'subscription' => new SubscriptionResource($subscription->fresh()),
        ], 'Suscripción reactivada exitosamente');
    }

    /**
     * Cambiar de plan
     *
     * Cambia la suscripción activa a un plan diferente.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $tenant = $request->user()->tenant;
        $subscription = $tenant->activeSubscription;

        if (!$subscription) {
            return $this->error('No tienes una suscripción activa.', 400);
        }

        $newPlan = Plan::findOrFail($request->plan_id);

        try {
            $subscription = $this->billingService->changePlan(
                $subscription,
                $newPlan,
                $request->billing_cycle
            );

            return $this->success([
                'subscription' => new SubscriptionResource($subscription->load('plan')),
            ], 'Plan cambiado exitosamente');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function payments(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $payments = $tenant->payments()
            ->with('subscription.plan')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($payments, PaymentResource::class);
    }

    /**
     * Consultar uso del plan
     *
     * Retorna el uso actual de documentos, usuarios y empresas vs los límites del plan.
     */
    public function usage(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $usage = $this->billingService->checkLimit($tenant, 'documents');
        $usersLimit = $this->billingService->checkLimit($tenant, 'users');
        $companiesLimit = $this->billingService->checkLimit($tenant, 'companies');

        return $this->success([
            'documents' => $usage,
            'users' => $usersLimit,
            'companies' => $companiesLimit,
        ]);
    }

    /**
     * Suscribirse mediante transferencia bancaria
     *
     * Crea una suscripción con pago pendiente por transferencia. Acepta multipart con comprobante.
     */
    public function subscribeBankTransfer(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'transfer_receipt' => 'required|image|max:5120',
            'transfer_reference' => 'required|string|max:100',
            'billing_name' => 'required|string|max:300',
            'billing_email' => 'required|email',
            'billing_identification' => 'nullable|string|max:20',
            'coupon_code' => 'nullable|string',
        ]);

        $tenant = $request->user()->tenant;
        $plan = Plan::findOrFail($request->plan_id);

        try {
            // Cancel previous subscription
            $tenant->activeSubscription?->cancel('Upgrade a nuevo plan');

            // Handle coupon
            $coupon = null;
            $discountAmount = 0;
            if ($request->coupon_code) {
                $coupon = Coupon::findByCode($request->coupon_code);
                if ($coupon && $coupon->canBeUsedByTenant($tenant->id)) {
                    $price = $request->billing_cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
                    $discountAmount = $coupon->calculateDiscount($price);
                    $coupon->incrementUses();
                }
            }

            $price = $request->billing_cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
            $finalPrice = max(0, $price - $discountAmount);

            // Store receipt
            $receiptPath = $request->file('transfer_receipt')->store('payment-receipts', 'public');

            // Create subscription in INCOMPLETE status
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::INCOMPLETE,
                'billing_cycle' => $request->billing_cycle,
                'amount' => $finalPrice,
                'discount_percent' => $coupon ? $coupon->discount_value : 0,
                'coupon_code' => $coupon?->code,
                'currency' => 'USD',
                'starts_at' => now(),
                'ends_at' => $request->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            ]);

            // Calculate tax
            $taxAmount = round($finalPrice * 0.15, 2);

            // Create PENDING payment
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
                'transfer_reference' => $request->transfer_reference,
                'billing_name' => $request->billing_name,
                'billing_email' => $request->billing_email,
                'billing_identification' => $request->billing_identification,
                'description' => "Transferencia bancaria - {$plan->name}",
            ]);

            $tenant->update(['current_plan_id' => $plan->id]);

            // Notify admins
            $admins = User::withoutGlobalScopes()->where('role', UserRole::SUPER_ADMIN)->get();
            foreach ($admins as $admin) {
                $admin->notify(new BankTransferPendingNotification($payment));
            }

            return $this->created([
                'subscription' => new SubscriptionResource($subscription->load('plan')),
                'payment' => new PaymentResource($payment),
            ], 'Comprobante recibido. Tu suscripción será activada una vez verificado el pago.');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Obtener cuentas bancarias para transferencia
     *
     * Retorna las cuentas bancarias activas para realizar transferencias.
     */
    public function bankAccounts(): JsonResponse
    {
        $accounts = BankAccount::active()->get()->map(fn ($account) => [
            'id' => $account->id,
            'bank_name' => $account->bank_name,
            'account_type' => $account->account_type,
            'account_number' => $account->account_number,
            'holder_name' => $account->holder_name,
            'holder_identification' => $account->holder_identification,
            'instructions' => $account->instructions,
        ]);

        return $this->success(['bank_accounts' => $accounts]);
    }

    /**
     * Consultar estado de un pago
     *
     * Retorna el estado actual de un pago específico del tenant.
     */
    public function paymentStatus(Request $request, int $id): JsonResponse
    {
        $payment = Payment::where('tenant_id', $request->user()->tenant_id)
            ->with('subscription.plan')
            ->findOrFail($id);

        return $this->success([
            'payment' => new PaymentResource($payment),
        ]);
    }

    /**
     * Validar cupón de descuento
     *
     * Verifica si un cupón es válido y calcula el descuento aplicable.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'plan_id' => 'nullable|exists:plans,id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        $coupon = Coupon::findByCode($request->code);

        if (!$coupon || !$coupon->isValid()) {
            return $this->error('Cupón inválido o expirado.', 400);
        }

        if (!$coupon->canBeUsedByTenant($request->user()->tenant_id)) {
            return $this->error('Ya has utilizado este cupón.', 400);
        }

        if ($request->plan_id && !$coupon->isApplicableToPlan($request->plan_id)) {
            return $this->error('Este cupón no aplica al plan seleccionado.', 400);
        }

        $discount = 0;
        if ($request->plan_id) {
            $plan = Plan::find($request->plan_id);
            $price = $request->billing_cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
            $discount = $coupon->calculateDiscount($price);
        }

        return $this->success([
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'discount_label' => $coupon->getDiscountLabel(),
                'calculated_discount' => $discount,
            ],
        ]);
    }
}
