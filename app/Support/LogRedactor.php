<?php

namespace App\Support;

/** Error messages from mail transports and queues often echo the recipient or a link; logs must not keep either. */
class LogRedactor
{
    public static function scrub(string $message): string
    {
        $message = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[email]', $message) ?? $message;
        $message = preg_replace('/\b[A-Za-z0-9]{40,}\b/', '[token]', $message) ?? $message;

        return mb_substr($message, 0, 300);
    }
}
