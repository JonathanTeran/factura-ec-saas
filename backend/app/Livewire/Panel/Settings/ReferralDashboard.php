<?php

namespace App\Livewire\Panel\Settings;

use App\Models\Billing\ReferralCommission;
use App\Models\Tenant\Tenant;
use Livewire\Component;
use Livewire\WithPagination;

class ReferralDashboard extends Component
{
    use WithPagination;

    public string $referralCode = '';
    public string $referralLink = '';

    public function mount(): void
    {
        $tenant = auth()->user()->tenant;

        $this->referralCode = $tenant->referral_code ?? '';
        $this->referralLink = $this->generateReferralLink($this->referralCode);
    }

    private function generateReferralLink(string $code): string
    {
        if (empty($code)) {
            return '';
        }

        return url('/register?ref=' . $code);
    }

    // ==================== COMPUTED PROPERTIES ====================

    public function getTotalReferralsProperty(): int
    {
        $tenant = auth()->user()->tenant;

        return Tenant::where('referred_by_tenant_id', $tenant->id)->count();
    }

    public function getActiveReferralsProperty(): int
    {
        $tenant = auth()->user()->tenant;

        return Tenant::where('referred_by_tenant_id', $tenant->id)
            ->whereNotNull('current_plan_id')
            ->count();
    }

    public function getTotalEarnedProperty(): float
    {
        $tenant = auth()->user()->tenant;

        return (float) ReferralCommission::forReferrer($tenant->id)
            ->whereIn('status', [
                ReferralCommission::STATUS_APPROVED,
                ReferralCommission::STATUS_PAID,
            ])
            ->sum('commission_amount');
    }

    public function getTotalPaidProperty(): float
    {
        $tenant = auth()->user()->tenant;

        return (float) ReferralCommission::forReferrer($tenant->id)
            ->paid()
            ->sum('commission_amount');
    }

    public function getPendingAmountProperty(): float
    {
        $tenant = auth()->user()->tenant;

        return (float) ReferralCommission::forReferrer($tenant->id)
            ->whereIn('status', [
                ReferralCommission::STATUS_PENDING,
                ReferralCommission::STATUS_APPROVED,
            ])
            ->sum('commission_amount');
    }

    public function getCommissionsProperty()
    {
        $tenant = auth()->user()->tenant;

        return ReferralCommission::forReferrer($tenant->id)
            ->with('referredTenant')
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    // ==================== ACTIONS ====================

    public function copyLink(): void
    {
        $this->dispatch('copy-to-clipboard', text: $this->referralLink);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Enlace de referido copiado al portapapeles.',
        ]);
    }

    public function shareWhatsApp(): void
    {
        $message = urlencode(
            "Te invito a usar la mejor plataforma de facturación electrónica en Ecuador. "
            . "Regístrate con mi enlace y obtén beneficios: {$this->referralLink}"
        );

        $this->dispatch('open-url', url: "https://wa.me/?text={$message}");
    }

    // ==================== RENDER ====================

    public function render()
    {
        return view('livewire.panel.settings.referral-dashboard', [
            'totalReferrals' => $this->totalReferrals,
            'activeReferrals' => $this->activeReferrals,
            'totalEarned' => $this->totalEarned,
            'totalPaid' => $this->totalPaid,
            'pendingAmount' => $this->pendingAmount,
            'commissions' => $this->commissions,
        ])->layout('layouts.tenant', ['title' => 'Programa de Referidos']);
    }
}
