<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Traits;

trait WrapColumnsTrait
{
    protected function wrap(int|string $value): string
    {

        if (is_int($value)) {
            return (string)$value;
        }
        $split = array_slice(
            preg_split('/\s+(as)\s+/i', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE),
            0,
            3);

        $ignore = ['*', 'as', 'AS'];
        $parts = [];
        foreach ($split as $part) {
            if (in_array($part, $ignore) || (str_starts_with($part, '`') && str_ends_with($part, '`'))) {
                $parts[] = $part;
                continue;
            }
            $parts[] = implode('.', array_map(static fn($p) => "`{$p}`", explode('.', $part)));
        }
        return implode(' ', $parts);
    }
}