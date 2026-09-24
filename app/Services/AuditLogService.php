<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function log(
        ?User $user,
        string $action,
        ?Model $entity = null,
        array $metadata = [],
        ?Request $request = null
    ): AuditLog {

        return AuditLog::create([
            'user_id' => $user?->id,

            'action' => $action,

            'entity_type' => $entity
                ? get_class($entity)
                : null,

            'entity_id' => $entity?->getKey(),

            'metadata' => $metadata,

            'ip_address' => $request?->ip(),
        ]);
    }
}