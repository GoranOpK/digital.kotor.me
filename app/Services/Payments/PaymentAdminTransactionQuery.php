<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentAdminTransactionQuery
{
    public const PER_PAGE = 20;

    /**
     * @param  array{
     *     status?: string|null,
     *     payment_type_id?: int|string|null,
     *     from?: string|null,
     *     to?: string|null,
     *     q?: string|null,
     *     user?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, PaymentTransaction>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $status = PaymentStatus::tryFrom((string) ($filters['status'] ?? ''));
        $typeId = isset($filters['payment_type_id']) && $filters['payment_type_id'] !== ''
            ? (int) $filters['payment_type_id']
            : null;
        $from = $this->date($filters['from'] ?? null);
        $to = $this->date($filters['to'] ?? null);
        $q = $this->trimmed($filters['q'] ?? null);
        $user = $this->trimmed($filters['user'] ?? null);

        return PaymentTransaction::query()
            ->with('user')
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($typeId !== null, fn ($query) => $query->where('payment_type_id', $typeId))
            ->when($from !== null, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->when($q !== null, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('merchant_transaction_id', $q)
                        ->orWhere('uuid', $q);
                });
            })
            ->when($user !== null, function ($query) use ($user) {
                $like = '%'.addcslashes($user, '%_\\').'%';
                $query->whereHas('user', function ($users) use ($like) {
                    $users->where('email', 'like', $like)
                        ->orWhere('name', 'like', $like);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    private function trimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function date(mixed $value): ?string
    {
        $trimmed = $this->trimmed($value);

        return $trimmed;
    }
}
