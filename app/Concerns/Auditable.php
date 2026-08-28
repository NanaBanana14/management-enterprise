<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->recordAudit('created', [], $model->auditableAttributes()));

        static::updated(function ($model) {
            $changes = $model->auditableChanges();

            if ($changes['old'] === [] && $changes['new'] === []) {
                return;
            }

            $model->recordAudit('updated', $changes['old'], $changes['new']);
        });

        static::deleted(fn ($model) => $model->recordAudit('deleted', $model->auditableAttributes(), []));
    }

    protected function auditableAttributes(): array
    {
        return collect($this->attributesToArray())
            ->except($this->hiddenFromAudit())
            ->toArray();
    }

    protected function auditableChanges(): array
    {
        $dirty = collect($this->getChanges())->except($this->hiddenFromAudit());

        $old = $dirty->keys()->mapWithKeys(fn ($key) => [$key => $this->getOriginal($key)]);

        return ['old' => $old->toArray(), 'new' => $dirty->toArray()];
    }

    protected function hiddenFromAudit(): array
    {
        return array_merge(['password', 'remember_token', 'updated_at'], $this->auditExcept ?? []);
    }

    protected function recordAudit(string $action, array $old, array $new): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
