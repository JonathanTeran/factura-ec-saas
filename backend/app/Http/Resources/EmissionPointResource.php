<?php

namespace App\Http\Resources;

use App\Enums\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmissionPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $series = $this->relationLoaded('branch') && $this->branch
            ? $this->branch->getFormattedCode().'-'.$this->getFormattedCode()
            : null;

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'series' => $series,
            'sequentials' => $this->whenLoaded('sequentialNumbers', fn () => $this->sequentialNumbers->map(function ($row) {
                $type = $row->document_type instanceof DocumentType ? $row->document_type : DocumentType::tryFrom((string) $row->document_type);

                return [
                    'document_type' => $type?->value ?? (string) $row->document_type,
                    'document_type_label' => $type?->label() ?? (string) $row->document_type,
                    'current_number' => (int) $row->current_number,
                    'next_number' => (int) $row->current_number + 1,
                ];
            })->values()),
            // Siguiente número de factura ("001-001-000000015"), lo que más consulta el usuario.
            'next_invoice_number' => $this->when($series !== null && $this->relationLoaded('sequentialNumbers'), function () use ($series) {
                $row = $this->sequentialNumbers->first(fn ($r) => ($r->document_type instanceof DocumentType ? $r->document_type->value : (string) $r->document_type) === DocumentType::FACTURA->value);

                return $series.'-'.str_pad((string) (((int) ($row?->current_number ?? 0)) + 1), 9, '0', STR_PAD_LEFT);
            }),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
        ];
    }
}
