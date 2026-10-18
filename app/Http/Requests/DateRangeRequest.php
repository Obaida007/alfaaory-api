<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * List filters shared by orders, deposits, wallet transactions and notifications.
 */
class DateRangeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            /** Start date (inclusive), Y-m-d. */
            'from' => ['nullable', 'date_format:Y-m-d'],
            /** End date (inclusive), Y-m-d. */
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function applyDateRange(Builder|Relation $query, string $column = 'created_at'): Builder|Relation
    {
        return $query
            ->when($this->validated('from'), fn ($q, $from) => $q->where($column, '>=', Carbon::parse($from)->startOfDay()))
            ->when($this->validated('to'), fn ($q, $to) => $q->where($column, '<=', Carbon::parse($to)->endOfDay()));
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }
}
