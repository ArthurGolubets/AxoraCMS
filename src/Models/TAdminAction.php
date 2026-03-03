<?php

namespace HolartWeb\HolartCMS\Models;

use Illuminate\Database\Eloquent\Model;

class TAdminAction extends Model
{
    protected $table = 't_admin_actions';

    protected $fillable = [
        'admin_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'ip_address',
        'user_agent',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the administrator who performed the action
     */
    public function admin()
    {
        return $this->belongsTo(TAdministrator::class, 'admin_id');
    }

    /**
     * Log an admin action
     */
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null, array $data = [])
    {
        // Check if table exists and logs module is installed
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('t_admin_actions')) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return null;
        }

        try {
            return static::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            // Silently fail if logging fails
            return null;
        }
    }
}
