<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UploadedFile extends Model
{
    protected $fillable = [
        'uuid',
        'original_name',
        'stored_name',
        'disk_path',
        'owner_key',
        'mime',
        'size',
    ];

    protected static function booted(): void
    {
        static::creating(function (UploadedFile $file): void {
            if (empty($file->uuid)) {
                $file->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
