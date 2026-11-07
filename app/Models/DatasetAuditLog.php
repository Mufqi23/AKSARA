<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DatasetAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'format',
        'filename',
        'created_count',
        'updated_count',
        'total_rows',
        'scope_label',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public static function recordSafe(array $attributes): void
    {
        if (! Schema::hasTable('dataset_audit_logs')) {
            return;
        }

        static::create($attributes);
    }
}

