<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository
{
    public function create(array $attributes): AuditLog
    {
        return AuditLog::create($attributes);
    }

    /**
     * @param array{action?: ?string, q?: ?string, from?: ?string, to?: ?string} $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q
                ->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"))
                ->orWhere('ip_address', 'like', "%{$term}%")
                ->orWhere('entity_type', 'like', "%{$term}%"));
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from'] . ' 00:00:00');
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to'] . ' 23:59:59');
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /** @return list<string> */
    public function distinctActions(): array
    {
        return AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action')->all();
    }
}
