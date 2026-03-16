<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the Auditable trait for a model.
     *
     * @return void
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            static::audit('created', $model);
        });

        static::updated(function ($model) {
            static::audit('updated', $model);
        });

        static::deleted(function ($model) {
            static::audit('deleted', $model);
        });

        // Only register the restored event if the model uses soft deletes
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(get_called_class()))) {
            static::restored(function ($model) {
                static::audit('restored', $model);
            });
        }
    }

    /**
     * Log an audit event.
     *
     * @param string $action
     * @param mixed $model
     * @return void
     */
    protected static function audit($action, $model)
    {
        // Don't log if we're in the process of running tests or seeding
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        $user = Auth::user();
        $request = app(Request::class);

        $auditLog = new AuditLog();
        $auditLog->user_id = $user?->id;
        $auditLog->user_name = $user?->name ?? 'System';
        $auditLog->action = $action;
        $auditLog->model_type = get_class($model);
        $auditLog->model_id = $model->getKey();
        $auditLog->model_name = $model->getNameForAudit() ?? $model->getKey();
        $auditLog->url = $request?->fullUrl();
        $auditLog->ip_address = $request?->ip();
        $auditLog->user_agent = $request?->userAgent();

        switch ($action) {
            case 'created':
                $auditLog->new_values = $model->getAttributes();
                break;
            case 'updated':
                $auditLog->old_values = $model->getRawOriginal();
                $auditLog->new_values = $model->getAttributes();
                break;
            case 'deleted':
                $auditLog->old_values = $model->getAttributes();
                break;
            case 'restored':
                $auditLog->new_values = $model->getAttributes();
                break;
        }

        $auditLog->save();
    }

    /**
     * Get the name of the model for audit purposes.
     * Override this method in your models to provide a meaningful name.
     *
     * @return string
     */
    public function getNameForAudit()
    {
        // Default implementation - override in specific models
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        if (property_exists($this, 'title')) {
            return $this->title;
        }

        if (property_exists($this, 'email')) {
            return $this->email;
        }

        return $this->getKey();
    }
}