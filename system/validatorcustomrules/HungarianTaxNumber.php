<?php
declare(strict_types=1);

namespace system\validatorcustomrules;

use \Respect\Validation\Rules\AbstractRule;

final class HungarianTaxNumber extends AbstractRule {
    public function validate($input): bool {
        // Az adószám validálása
        if (!preg_match('/^\d{8}-\d{1}-\d{2}$/i', $input)) {
            return false;
        }

        // Ellenőrzés, hogy az adószám helyes ellenőrző számmal rendelkezik-e
        $taxNumber = current(explode('-', $input));
        $checkDigit = $taxNumber % 10;;

        $weight = [9, 7, 3, 1, 9, 7, 3];
        $checksum = 0;

        for ($i = 0; $i < 7; $i++) {
            $checksum += (int)$taxNumber[$i] * $weight[$i];
        }

        $remainder = $checksum % 10;
        $expectedCheckDigit = (10 - $remainder) % 10;

        return (int)$checkDigit === $expectedCheckDigit;
    }
}
?>