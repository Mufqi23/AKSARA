<?php

namespace App\Http\Requests;

use App\Services\Catalog;
use Illuminate\Foundation\Http\FormRequest;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function resourceName(): string
    {
        if ($this->route('resource')) {
            return (string) $this->route('resource');
        }

        return (string) ($this->segments()[2] ?? '');
    }

    public function rules(): array
    {
        return Catalog::rules($this->resourceName(), $this->route('id') ? (int) $this->route('id') : null, $this->all());
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->resourceName() === 'subjects' && ! $validator->errors()->any()
                && $this->filled('lecture_units') && abs((float) $this->units - (float) $this->lecture_units - (float) $this->laboratory_units) > 0.0001) {
                $validator->errors()->add('units', 'Total Jam Pelajaran (JP) harus sama dengan jumlah JP Teori ditambah JP Praktik.');
            }
        });
    }

    public function attributes(): array
    {
        [, $fields] = Catalog::definition($this->resourceName());

        return collect($fields)->mapWithKeys(fn ($type, $field) => [$field => Catalog::fieldLabel($field, $this->resourceName())])->all();
    }
}
