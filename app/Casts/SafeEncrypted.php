<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Like the built-in "encrypted" cast, but tolerant of values that cannot be
 * decrypted with the current APP_KEY (e.g. data encrypted under a different
 * key, or plaintext written directly to the DB). Reading such a value returns
 * null instead of throwing a DecryptException, so pages/exports that touch the
 * attribute degrade gracefully rather than returning a 500.
 *
 * Uses serialize=false to stay binary-compatible with Laravel's `encrypted`
 * cast, so values already stored by that cast continue to decrypt correctly.
 */
class SafeEncrypted implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decrypt($value, false);
        } catch (DecryptException $e) {
            Log::warning('SafeEncrypted: could not decrypt attribute', [
                'model'     => get_class($model),
                'id'        => $model->getKey(),
                'attribute' => $key,
            ]);

            return null;
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return [$key => $value === null ? null : Crypt::encrypt($value, false)];
    }
}
