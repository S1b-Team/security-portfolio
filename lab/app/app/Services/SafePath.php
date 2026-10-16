<?php

namespace App\Services;

use App\Support\FixMode;
use Illuminate\Support\Facades\Storage;

/**
 * Resolve download paths under the private local disk root.
 * Fixed mode: basename + realpath jail. Vulnerable mode: raw join (lab only).
 */
final class SafePath
{
    /** @return array{0:?string,1:?string} [absolutePath|null, error|null] */
    public function resolve(string $requested): array
    {
        $root = Storage::disk('local')->path('');
        $rootReal = realpath($root) ?: rtrim($root, DIRECTORY_SEPARATOR);

        if (! FixMode::enabled()) {
            $joined = $rootReal.DIRECTORY_SEPARATOR.$requested;

            return [$joined, null];
        }

        $safe = basename(str_replace(["\\", "\0"], ['/', ''], $requested));
        if ($safe === '' || $safe === '.' || $safe === '..') {
            return [null, 'invalid_name'];
        }

        $candidate = $rootReal.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$safe;
        $real = realpath($candidate);
        if ($real === false) {
            $parent = realpath(dirname($candidate));
            $uploadsRoot = realpath($rootReal.DIRECTORY_SEPARATOR.'uploads') ?: ($rootReal.DIRECTORY_SEPARATOR.'uploads');
            if ($parent === false || ! str_starts_with($parent, $uploadsRoot)) {
                return [null, 'path_outside_storage'];
            }

            return [$candidate, null];
        }

        $uploadsRoot = realpath($rootReal.DIRECTORY_SEPARATOR.'uploads') ?: ($rootReal.DIRECTORY_SEPARATOR.'uploads');
        if (! str_starts_with($real, $uploadsRoot.DIRECTORY_SEPARATOR) && $real !== $uploadsRoot) {
            return [null, 'path_outside_storage'];
        }

        return [$real, null];
    }
}
