<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\EventListener;

use App\Account\Domain\Exception\DomainException;
use App\Account\Domain\Exception\UserEmailExistsException;
use App\Account\Domain\Exception\UserNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener]
class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Unwrap Messenger HandlerFailedException
        if ($exception instanceof \Symfony\Component\Messenger\Exception\HandlerFailedException) {
            $exception = $exception->getPrevious();
        }

        if ($exception instanceof UserNotFoundException) {
            $event->setResponse(new JsonResponse([
                'error' => $exception->getMessage(),
            ], Response::HTTP_UNAUTHORIZED));
        } elseif ($exception instanceof UserEmailExistsException) {
            $event->setResponse(new JsonResponse([
                'title' => 'Validation Error',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => $exception->getMessage(),
                'violations' => [
                    [
                        'propertyPath' => 'email',
                        'title' => 'This email is already in use.',
                        'code' => 'EMAIL_ALREADY_EXISTS',
                    ],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        } elseif ($exception instanceof DomainException) {
            $event->setResponse(new JsonResponse([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST));
        }
    }
}
