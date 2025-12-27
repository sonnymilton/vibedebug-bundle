<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Chat\Exception;

use RuntimeException;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Throwable;

final class UnsupportedProfileException extends RuntimeException
{
    public function __construct(
        public readonly Profile $profile,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function forProfile(Profile $profile): self
    {
        return new self($profile, sprintf('Profile for token %s is not supported', $profile->getToken()));
    }
}
