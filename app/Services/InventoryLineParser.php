<?php

namespace App\Services;

class InventoryLineParser
{
    /**
     * Parse "key: value; key: value" segments into an array.
     * The first colon in a segment separates the key from the value.
     *
     * @return array<string, string>
     */
    public function parse(string $line): array
    {
        $parsed = [];

        foreach (explode(';', $line) as $segment) {
            $segment = trim($segment);

            if ($segment === '' || ! str_contains($segment, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $segment, 2);
            $key = trim($key);

            if ($key === '') {
                continue;
            }

            $parsed[$key] = trim($value);
        }

        return $parsed;
    }
}
