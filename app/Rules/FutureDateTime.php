<?php declare(strict_types=1);

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class FutureDateTime implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        try {
            $inputDate = Carbon::parse($value);
            $now = Carbon::now();

            // Compare dates in UTC to avoid timezone issues
            if ($inputDate->utc()->lte($now->utc())) {
                $fail('La fecha de inicio debe ser posterior a la fecha actual.');
            }
        } catch (\Exception $e) {
            $fail('La fecha proporcionada no es válida.');
        }
    }
}
