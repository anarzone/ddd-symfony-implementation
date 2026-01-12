<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener]
class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Check if this is an HttpException with ValidationFailedException as previous
        if (!$exception instanceof HttpException) {
            return;
        }

        $previous = $exception->getPrevious();
        if (!$previous instanceof ValidationFailedException) {
            return;
        }

        $violations = $previous->getViolations();

        // Format violations into standard structure
        $formattedViolations = [];
        foreach ($violations as $violation) {
            $formattedViolations[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'title' => $violation->getMessage(),
                'code' => $violation->getCode() ?? 'VALIDATION_ERROR',
            ];
        }

        $event->setResponse(new JsonResponse([
            'title' => 'Validation Failed',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => 'The request data contains validation errors.',
            'violations' => $formattedViolations,
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
