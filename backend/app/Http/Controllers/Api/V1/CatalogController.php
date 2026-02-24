<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Enums\IdentificationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CatalogController extends ApiController
{
    public function identificationTypes(): JsonResponse
    {
        $types = collect(IdentificationType::cases())->map(fn ($type) => [
            'code' => $type->value,
            'name' => $type->label(),
            'length' => $type->length(),
        ]);

        return $this->success([
            'identification_types' => $types,
        ]);
    }

    public function documentTypes(): JsonResponse
    {
        $types = collect(DocumentType::cases())->map(fn ($type) => [
            'code' => $type->value,
            'name' => $type->label(),
            'sri_code' => $type->sriCode(),
        ]);

        return $this->success([
            'document_types' => $types,
        ]);
    }

    public function paymentMethods(): JsonResponse
    {
        $methods = DB::table('sri_catalogs')
            ->where('type', 'payment_method')
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'description']);

        return $this->success([
            'payment_methods' => $methods,
        ]);
    }

    public function taxRates(): JsonResponse
    {
        $rates = DB::table('sri_catalogs')
            ->where('type', 'tax_rate')
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'description', 'percentage']);

        return $this->success([
            'tax_rates' => $rates,
        ]);
    }

    public function retentionCodes(): JsonResponse
    {
        $codes = DB::table('sri_catalogs')
            ->whereIn('type', ['retention_iva', 'retention_renta'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get(['type', 'code', 'name', 'description', 'percentage']);

        return $this->success([
            'retention_codes' => $codes->groupBy('type'),
        ]);
    }
}
