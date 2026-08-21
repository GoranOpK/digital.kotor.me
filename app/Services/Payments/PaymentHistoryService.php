<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentHistoryService
{
    public const PER_PAGE = 15;

    /**
     * @return LengthAwarePaginator<int, PaymentTransaction>
     */
    public function paginateFor(User $user, ?PaymentStatus $status = null): LengthAwarePaginator
    {
        return PaymentTransaction::query()
            ->where('user_id', $user->id)
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function statusFilter(?string $value): ?PaymentStatus
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return PaymentStatus::tryFrom($value);
    }
}
