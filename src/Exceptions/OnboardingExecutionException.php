<?php

namespace hexa_package_publication_onboarding\Exceptions;

use hexa_package_publication_onboarding\Data\OnboardingOperation;
use Throwable;

final class OnboardingExecutionException extends OnboardingException
{
    public function __construct(
        string $message,
        public readonly OnboardingOperation $operation,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
