<?php

namespace App\Helpers;

class ValidatorHelper
{
    public static function isCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(.)\1{13}$/', $cnpj)) {
            return false;
        }

        for ($t = 12; $t < 14; $t++) {
            $d = 0;
            $c = 0;
            $multipliers = ($t == 12) ? [5,4,3,2,9,8,7,6,5,4,3,2] : [6,5,4,3,2,9,8,7,6,5,4,3,2];

            for ($c = 0; $c < $t; $c++) {
                $d += $cnpj[$c] * $multipliers[$c];
            }

            $d = ((10 * $d) % 11) % 10;

            if ($cnpj[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}
