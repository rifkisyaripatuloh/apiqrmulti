<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PublicHttpsUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/[\x00-\x1F\x7F]/', $value) || strlen($value) > 2048 || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $fail('Gunakan URL HTTPS publik yang valid.');

            return;
        } $parts = parse_url($value);
        $host = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https' || ! $host || isset($parts['user']) || isset($parts['pass']) || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            $fail('Gunakan URL HTTPS publik yang valid.');

            return;
        } if (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            $fail('Gunakan URL HTTPS publik yang valid.');
        }
    }
}
