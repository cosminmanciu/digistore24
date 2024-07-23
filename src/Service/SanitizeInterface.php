<?php
declare(strict_types = 1);

namespace App\Service;

interface SanitizeInterface {

    /**
     * @param string $string
     * @return bool
     */
    public function isValid(string $string): bool;
}