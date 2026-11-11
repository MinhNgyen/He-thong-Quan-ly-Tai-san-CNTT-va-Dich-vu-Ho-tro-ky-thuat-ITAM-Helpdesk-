<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AuditLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Ghi nhật ký hành vi nhạy cảm (K8, BM: A09). Không bao giờ ghi mật khẩu hoặc token vào metadata.
 */
class AuditService
{
    private const SENSITIVE_KEYS = ['password', 'password_confirmation', 'token', 'current_password'];

    public function __construct(private readonly AuditLogRepository $logs)
    {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function log(string $action, ?User $user = null, ?Model $entity = null, array $metadata = []): void
    {
        $user ??= Auth::user();
        $request = request();

        $this->logs->create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            'metadata' => $metadata === [] ? null : array_diff_key($metadata, array_flip(self::SENSITIVE_KEYS)),
        ]);
    }

    /** @param array{action?: ?string, q?: ?string, from?: ?string, to?: ?string} $filters */
    public function search(array $filters): LengthAwarePaginator
    {
        return $this->logs->paginate($filters);
    }

    /** @return list<string> */
    public function actions(): array
    {
        return $this->logs->distinctActions();
    }
}
