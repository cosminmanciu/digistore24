<?php
declare(strict_types=1);

namespace App\Service;

class SanitizeService implements SanitizeInterface
{
    /**
     * @param string $string
     * @return bool
     */
    public function isValid(string $string): bool
    {
        // Validate the text parameter using regex
        if (!preg_match('/^[a-zA-Z0-9\s]+$/', trim($string))) {
            return false;
        }

        return true;
    }
}
