<?php

namespace App\Policies;

use App\Models\UploadedFile;
use App\Support\FixMode;
use Illuminate\Http\Request;

/**
 * Ownership check for download (no IDOR). Uses X-Lab-Owner header / owner_key.
 */
class UploadedFilePolicy
{
    public function download(?object $user, UploadedFile $file, ?string $ownerKey): bool
    {
        if (! FixMode::enabled()) {
            return true;
        }

        return is_string($ownerKey)
            && $ownerKey !== ''
            && hash_equals($file->owner_key, $ownerKey);
    }
}
