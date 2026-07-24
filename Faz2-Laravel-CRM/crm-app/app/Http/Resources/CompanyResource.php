<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'logo_url' => $this->logo_path ? Storage::url($this->logo_path) : null,   // ham logo_path degil, tam URL
            // logo_path'in kendisi, created_at, updated_at -> bilincli olarak disarida birakildi
        ];
    }
}
