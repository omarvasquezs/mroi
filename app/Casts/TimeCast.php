<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Carbon\Carbon;
use InvalidArgumentException;

class TimeCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // Convert the database value to a Carbon instance with just the time portion
        return Carbon::parse($value)->format('H:i:s');
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            // Try to parse the time string
            try {
                return $value;
            } catch (\Exception $e) {
                throw new InvalidArgumentException("The {$key} value is not a valid time format.");
            }
        }

        throw new InvalidArgumentException("The {$key} value must be a string or null.");
    }
}