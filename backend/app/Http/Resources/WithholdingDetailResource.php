<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithholdingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_type' => $this->tax_type,
            'tax_type_name' => $this->getTaxTypeName(),
            'tax_code' => $this->tax_code,
            'withholding_code' => $this->withholding_code,
            'withholding_percentage' => (float) $this->withholding_percentage,
            'withholding_description' => $this->getWithholdingDescription(),
            'base_amount' => (float) $this->base_amount,
            'withheld_amount' => (float) $this->withheld_amount,
            'supporting_doc' => [
                'type' => $this->supporting_doc_type,
                'type_label' => $this->getSupportingDocTypeLabel(),
                'number' => $this->supporting_doc_number,
                'date' => $this->supporting_doc_date?->toDateString(),
                'authorization' => $this->supporting_doc_auth,
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
