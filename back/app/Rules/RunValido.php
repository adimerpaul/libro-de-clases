<?php

namespace App\Rules;

use App\Support\Run;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RunValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Run::esValido($value)) {
            $fail('El RUN no es válido (revise el dígito verificador).');
        }
    }
}
