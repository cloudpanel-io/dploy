<?php declare(strict_types=1);

namespace App\Util;

class Retry
{
    static public function retry(callable $fn, $retries = 2, $delay = 5): mixed
    {
        beginning:
        try {
            return $fn();
        } catch (\Exception $e) {
            if (!$retries) {
                throw $e;
            }
            $retries--;
            if ($delay) {
                sleep($delay);
            }
            goto beginning;
        }
    }
}