# DDD Improvement Plan for FastReserve

**Current Status:** 80% Complete for Solid DDD Practice
**Target:** 95%+ Complete (Exceptional DDD Implementation)
**Last Updated:** 2025-01-10

---

## Executive Summary

This document outlines actionable improvements to complete the DDD implementation in the FastReserve project. The current implementation is **solid** but missing key patterns that would make it **exceptional** for learning and production use.

### Current Strengths ✅
- Clear bounded contexts (Account, Inventory)
- Tactical DDD patterns (Aggregates, Value Objects, Repositories)
- CQRS implementation
- Proper layering (Domain, Application, Infrastructure)
- Production-grade features (security, concurrency control)

### Key Gaps ❌
- No domain events
- Incomplete aggregate encapsulation
- Missing specification pattern
- No event sourcing (optional)

---

## Priority Matrix

| Priority | Item | Impact | Effort | Timeline |
|----------|------|--------|--------|----------|
| **P0** | Complete Aggregate Encapsulation | High | Low | 2-3 hours |
| **P0** | Implement Domain Events | High | Medium | 1-2 days |
| **P1** | Add Specification Pattern | Medium | Medium | 3-4 hours |
| **P1** | Create Domain Event Handlers | High | Medium | 1 day |
| **P2** | Event Sourcing (Optional) | Low | High | 3-5 days |
| **P2** | CQRS Read Models (Optional) | Low | High | 1-2 days |

---

## Phase 1: Complete Aggregate Encapsulation (P0)

**Goal:** Ensure all aggregates properly encapsulate their state and expose intent-revealing methods.

**Time Estimate:** 2-3 hours

### 1.1 Fix User Entity Encapsulation

**Current Issue:**
```php
// src/Account/Application/Command/UpdateUserHandler.php:33
if ($message->roles) {
    $user->roles = $message->roles;  // ❌ Direct property access
}
```

**Solution:**

#### Step 1: Make `roles` property private
```php
// src/Account/Domain/Model/User.php
#[ORM\Column(type: Types::JSON)]
private array $roles = ['ROLE_USER'];
```

#### Step 2: Add domain method
```php
// src/Account/Domain/Model/User.php
public function updateRoles(array $roles): void
{
    if (empty($roles)) {
        throw new \InvalidArgumentException('Roles cannot be empty');
    }

    // Ensure ROLE_USER is always present
    if (!in_array('ROLE_USER', $roles)) {
        $roles[] = 'ROLE_USER';
    }

    $this->roles = $roles;
}
```

#### Step 3: Update handler
```php
// src/Account/Application/Command/UpdateUserHandler.php
if ($message->roles) {
    $user->updateRoles($message->roles);  // ✅ Intent-revealing method
}
```

#### Step 4: Keep getter for Symfony Security
```php
// src/Account/Domain/Model/User.php
public function getRoles(): array
{
    return $this->roles;
}
```

### 1.2 Add Email Change Domain Logic

**Current Issue:**
```php
// src/Account/Domain/Model/User.php
public string $email;  // ❌ Public property
```

**Solution:**

#### Step 1: Make `email` property private
```php
#[ORM\Column(type: Types::STRING, length: 180)]
private string $email;
```

#### Step 2: Add domain method
```php
public function changeEmail(string $newEmail): void
{
    if ($this->email === $newEmail) {
        return;  // No change needed
    }

    $this->email = $newEmail;
}
```

#### Step 3: Add getter
```php
public function getEmail(): string
{
    return $this->email;
}
```

### 1.3 Review All Entities for Public Properties

Checklist:
- [ ] `User.php` - Make `email`, `roles` private ✓
- [ ] `ApiToken.php` - Already has good encapsulation ✓
- [ ] `Stock.php` - Already has property hooks ✓
- [ ] `Warehouse.php` - Already has domain methods ✓
- [ ] `Reservation.php` - Mix of public/private (review)

**Reservation Review:**
```php
// Current: Some public properties (ok for value objects)
public ?string $orderReference = null;
public int $quantity;
public User $user;

// Consider: Should these be private with methods?
// - orderReference: Already has convertToSale() method ✅
// - quantity: Immutable after creation, public is acceptable ✅
// - user: Immutable after creation, public is acceptable ✅
```

### 1.4 Add Domain Methods to Warehouse

**Current:**
```php
// Warehouse has good methods already
public function activate(): void { }
public function deactivate(): void { }
public function changeType(WarehouseTypeEnum $type): void { }
```

**Consider Adding:**
```php
// Business rule: Can only deactivate if no active reservations
public function deactivate(): void
{
    foreach ($this->stocks as $stock) {
        $activeReservations = $stock->getReservedQuantity();
        if ($activeReservations > 0) {
            throw new \DomainException(
                'Cannot deactivate warehouse with active reservations'
            );
        }
    }

    $this->isActive = false;
}
```

### 1.5 Testing Checklist

After implementing encapsulation:
- [ ] Run user update tests: `POST /api/users/{uuid}`
- [ ] Test role update maintains ROLE_USER
- [ ] Test email change with same email (no-op)
- [ ] Test warehouse deactivation with active reservations (should fail)
- [ ] Run PHPStan to verify no direct property access violations
- [ ] Update fixtures to use domain methods

---

## Phase 2: Implement Domain Events (P0)

**Goal:** Enable aggregates to publish domain events for side effects, auditing, and integration.

**Time Estimate:** 1-2 days

### 2.1 Create Domain Event Base Classes

#### Step 1: Create base event class
```php
// src/Shared/Domain/Event/DomainEvent.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

abstract class DomainEvent
{
    private function __construct(
        public readonly Uuid $eventId,
        public readonly DateTimeImmutable $occurredAt,
    ) {
    }

    public static function now(): self
    {
        return new static(
            eventId: Uuid::v7(),
            occurredAt: new DateTimeImmutable()
        );
    }
}
```

#### Step 2: Create event dispatcher interface
```php
// src/Shared/Domain/Event/EventDispatcherInterface.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface EventDispatcherInterface
{
    public function dispatch(DomainEvent $event): void;

    /**
     * @param array<DomainEvent> $events
     */
    public function dispatchAll(array $events): void;
}
```

### 2.2 Add Aggregate Event Recording

#### Step 1: Add trait for aggregates
```php
// src/Shared/Domain/Model/AggregateRootTrait.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

use App\Shared\Domain\Event\DomainEvent;

trait AggregateRootTrait
{
    /** @var array<DomainEvent> */
    private array $domainEvents = [];

    protected function recordDomainEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    public function hasDomainEvents(): bool
    {
        return !empty($this->domainEvents);
    }
}
```

#### Step 2: Add trait to aggregates
```php
// src/Inventory/Domain/Model/Stock/Stock.php
use App\Shared\Domain\Model\AggregateRootTrait;

class Stock
{
    use AggregateRootTrait;

    public function reserve(int $quantity, User $user, int $minutesValid = 15): Reservation
    {
        // ... existing logic ...

        $reservation = new Reservation($this, $quantity, $user, $minutesValid);
        $this->reservations->add($reservation);

        // Record domain event
        $this->recordDomainEvent(new StockReservedEvent(
            stockId: $this->uuid,
            quantity: $quantity,
            userId: $user->uuid,
            reservationId: $reservation->uuid
        ));

        return $reservation;
    }
}
```

### 2.3 Define Concrete Domain Events

#### Inventory Context Events
```php
// src/Inventory/Domain/Event/StockReservedEvent.php
<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

class StockReservedEvent extends DomainEvent
{
    public function __construct(
        public readonly Uuid $stockId,
        public readonly int $quantity,
        public readonly Uuid $userId,
        public readonly Uuid $reservationId,
    ) {
        parent::__construct(
            eventId: Uuid::v7(),
            occurredAt: new \DateTimeImmutable()
        );
    }

    public function getEventName(): string
    {
        return 'stock.reserved';
    }
}
```

```php
// src/Inventory/Domain/Event/ReservationExpiredEvent.php
<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

class ReservationExpiredEvent extends DomainEvent
{
    public function __construct(
        public readonly Uuid $reservationId,
        public readonly Uuid $stockId,
        public readonly int $quantity,
    ) {
        parent::__construct(
            eventId: Uuid::v7(),
            occurredAt: new \DateTimeImmutable()
        );
    }

    public function getEventName(): string
    {
        return 'reservation.expired';
    }
}
```

#### Account Context Events
```php
// src/Account/Domain/Event/UserRegisteredEvent.php
<?php

declare(strict_types=1);

namespace App\Account\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

class UserRegisteredEvent extends DomainEvent
{
    public function __construct(
        public readonly Uuid $userId,
        public readonly string $email,
    ) {
        parent::__construct(
            eventId: Uuid::v7(),
            occurredAt: new \DateTimeImmutable()
        );
    }

    public function getEventName(): string
    {
        return 'user.registered';
    }
}
```

```php
// src/Account/Domain/Event/ApiTokenGeneratedEvent.php
<?php

declare(strict_types=1);

namespace App\Account\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

class ApiTokenGeneratedEvent extends DomainEvent
{
    public function __construct(
        public readonly Uuid $tokenId,
        public readonly Uuid $userId,
        public readonly ?string $description,
    ) {
        parent::__construct(
            eventId: Uuid::v7(),
            occurredAt: new \DateTimeImmutable()
        );
    }

    public function getEventName(): string
    {
        return 'api_token.generated';
    }
}
```

### 2.4 Implement Event Dispatcher

#### Step 1: Create Symfony messenger dispatcher
```php
// src/Shared/Infrastructure/Event/MessengerEventDispatcher.php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private MessageBusInterface $eventBus
    ) {
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }

    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
```

#### Step 2: Configure event bus in Messenger
```yaml
# config/messenger.yaml
messenger:
    buses:
        event.bus:
            middleware: [doctrine_transaction]

    routing:
        # Async event handling
        'App\Inventory\Domain\Event\StockReservedEvent': async
        'App\Account\Domain\Event\UserRegisteredEvent': async
        'App\Shared\Domain\Event\DomainEvent': async  # Fallback
```

### 2.5 Dispatch Events in Command Handlers

#### Update command handlers to dispatch events
```php
// src/Inventory/Application/Command/ReserveStockHandler.php
<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Shared\Infrastructure\Event\MessengerEventDispatcher;
// ... other imports ...

#[AsMessageHandler]
class ReserveStockHandler
{
    public function __construct(
        private StockRepositoryInterface $stockRepository,
        private UserRepositoryInterface $userRepository,
        private StockDomainService $domainService,
        private MessengerEventDispatcher $eventDispatcher,  // ← Add
    ) {
    }

    public function __invoke(ReserveStockMessage $message): void
    {
        // ... existing logic ...

        $domainService->reserveStock($stock, $user, $message->quantity, $message->minutesValid);
        $this->stockRepository->save($stock);

        // Dispatch domain events
        if ($stock->hasDomainEvents()) {
            $this->eventDispatcher->dispatchAll($stock->pullDomainEvents());
        }
    }
}
```

### 2.6 Create Event Handlers

#### Example: Send notification on stock reservation
```php
// src/Inventory/Infrastructure/EventHandler/SendStockReservedNotificationHandler.php
<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\EventHandler;

use App\Inventory\Domain\Event\StockReservedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
class SendStockReservedNotificationHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(StockReservedEvent $event): void
    {
        $this->logger->info('Stock reserved', [
            'stock_id' => $event->stockId->toRfc4122(),
            'quantity' => $event->quantity,
            'user_id' => $event->userId->toRfc4122(),
            'reservation_id' => $event->reservationId->toRfc4122(),
        ]);

        // TODO: Send email, push notification, etc.
    }
}
```

#### Example: Audit log for user registration
```php
// src/Account/Infrastructure/EventHandler/AuditUserRegistrationHandler.php
<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\EventHandler;

use App\Account\Domain\Event\UserRegisteredEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
class AuditUserRegistrationHandler
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        // Log to audit table
        $this->em->getConnection()->insert('audit_log', [
            'event_type' => 'user.registered',
            'entity_id' => $event->userId->toRfc4122(),
            'data' => json_encode(['email' => $event->email]),
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
        ]);
    }
}
```

### 2.7 Testing Checklist

- [ ] Unit test: Stock entity records events on reserve
- [ ] Unit test: User entity records events on registration
- [ ] Integration test: Events dispatched in command handler
- [ ] Integration test: Event handlers receive events
- [ ] Test event propagation through messenger
- [ ] Test transaction rollback (events should not dispatch)
- [ ] Add event fixtures for testing

---

## Phase 3: Specification Pattern (P1)

**Goal:** Encapsulate complex business rules into composable, reusable specifications.

**Time Estimate:** 3-4 hours

### 3.1 Create Specification Interface

```php
// src/Shared/Domain/Specification/Specification.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

interface Specification
{
    /**
     * Check if the candidate satisfies the specification
     */
    public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * Combine with another specification (AND)
     */
    public function and(Specification $other): Specification;

    /**
     * Combine with another specification (OR)
     */
    public function or(Specification $other): Specification;

    /**
     * Negate this specification (NOT)
     */
    public function not(): Specification;
}
```

### 3.2 Create Abstract Specification

```php
// src/Shared/Domain/Specification/AbstractSpecification.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

abstract class AbstractSpecification implements Specification
{
    abstract public function isSatisfiedBy(mixed $candidate): bool;

    public function and(Specification $other): Specification
    {
        return new AndSpecification($this, $other);
    }

    public function or(Specification $other): Specification
    {
        return new OrSpecification($this, $other);
    }

    public function not(): Specification
    {
        return new NotSpecification($this);
    }
}
```

### 3.3 Create Composite Specifications

```php
// src/Shared/Domain/Specification/AndSpecification.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

class AndSpecification extends AbstractSpecification
{
    public function __construct(
        private Specification $left,
        private Specification $right
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            && $this->right->isSatisfiedBy($candidate);
    }
}
```

```php
// src/Shared/Domain/Specification/OrSpecification.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

class OrSpecification extends AbstractSpecification
{
    public function __construct(
        private Specification $left,
        private Specification $right
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            || $this->right->isSatisfiedBy($candidate);
    }
}
```

```php
// src/Shared/Domain/Specification/NotSpecification.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

class NotSpecification extends AbstractSpecification
{
    public function __construct(
        private Specification $specification
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return !$this->specification->isSatisfiedBy($candidate);
    }
}
```

### 3.4 Implement Domain Specifications

#### Active Reservations
```php
// src/Inventory/Domain/Specification/ActiveReservationSpecification.php
<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Specification;

use App\Inventory\Domain\Model\Stock\Reservation;
use App\Shared\Domain\Specification\AbstractSpecification;

class ActiveReservationSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Reservation) {
            return false;
        }

        return $candidate->isActive();
    }
}
```

#### Expired Reservations
```php
// src/Inventory/Domain/Specification/ExpiredReservationSpecification.php
<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Specification;

use App\Inventory\Domain\Model\Stock\Reservation;
use App\Shared\Domain\Specification\AbstractSpecification;

class ExpiredReservationSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Reservation) {
            return false;
        }

        return $candidate->isExpired();
    }
}
```

#### Warehouse Capacity
```php
// src/Inventory/Domain/Specification/WarehouseHasCapacitySpecification.php
<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Specification;

use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Shared\Domain\Specification\AbstractSpecification;

class WarehouseHasCapacitySpecification extends AbstractSpecification
{
    public function __construct(
        private int $requiredCapacity
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Warehouse) {
            return false;
        }

        return $candidate->getCapacity() >= $this->requiredCapacity;
    }
}
```

#### User Email Specification
```php
// src/Account/Domain/Specification/ValidEmailSpecification.php
<?php

declare(strict_types=1);

namespace App\Account\Domain\Specification;

use App\Account\Domain\Model\User;
use App\Shared\Domain\Specification\AbstractSpecification;

class ValidEmailSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof User) {
            return false;
        }

        return filter_var($candidate->getEmail(), FILTER_VALIDATE_EMAIL) !== false;
    }
}
```

### 3.5 Use Specifications in Domain Services

```php
// src/Inventory/Domain/Service/ReservationCleanupService.php
<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Service;

use App\Inventory\Domain\Model\Stock\Reservation;
use App\Inventory\Domain\Specification\ActiveReservationSpecification;
use App\Inventory\Domain\Specification\ExpiredReservationSpecification;

class ReservationCleanupService
{
    public function __construct(
        private ActiveReservationSpecification $activeSpec,
        private ExpiredReservationSpecification $expiredSpec
    ) {
    }

    /**
     * Find reservations that are active but expired
     */
    public function findStaleReservations(array $reservations): array
    {
        return array_filter(
            $reservations,
            fn (Reservation $r) => $this->activeSpec->and($this->expiredSpec)->isSatisfiedBy($r)
        );
    }
}
```

### 3.6 Testing Checklist

- [ ] Test: ActiveReservationSpecification filters correctly
- [ ] Test: ExpiredReservationSpecification filters correctly
- [ ] Test: Composite AND specification
- [ ] Test: Composite OR specification
- [ ] Test: Composite NOT specification
- [ ] Test: Specifications in domain services
- [ ] Benchmark: Specification performance vs inline logic

---

## Phase 4: Enhance Domain Events (P1)

**Goal:** Add more domain events and create useful event handlers.

**Time Estimate:** 1 day

### 4.1 Add More Domain Events

```php
// src/Inventory/Domain/Event/WarehouseCreatedEvent.php
class WarehouseCreatedEvent extends DomainEvent
{
    public function __construct(
        public readonly Uuid $warehouseId,
        public readonly string $name,
        public readonly int $capacity,
    ) {
        parent::__construct();
    }
}
```

```php
// src/Inventory/Domain/Event/WarehouseDeactivatedEvent.php
class WarehouseDeactivatedEvent extends DomainEvent
{
    public function __construct(
        public readonly Uuid $warehouseId,
        public readonly int $activeReservationsCount,
    ) {
        parent::__construct();
    }
}
```

```php
// src/Inventory/Domain/Event/ReservationCancelledEvent.php
class ReservationCancelledEvent extends DomainEvent
{
    public function __construct(
        public readonly Uuid $reservationId,
        public readonly Uuid $stockId,
        public readonly int $quantityReleased,
    ) {
        parent::__construct();
    }
}
```

### 4.2 Create Event Store (Optional - for audit trail)

```php
// src/Shared/Infrastructure/Persistence/Doctrine/EventStore.php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Event\DomainEvent;
use Doctrine\DBAL\Connection;

class EventStore
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function append(DomainEvent $event): void
    {
        $this->connection->insert('domain_events', [
            'event_id' => $event->eventId->toRfc4122(),
            'event_type' => $event::class,
            'event_data' => json_encode($event),
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function getEventsForAggregate(string $aggregateId): array
    {
        // Fetch and return events
    }
}
```

### 4.3 Add Event Handlers

#### Analytics Handler
```php
// src/Shared/Infrastructure/EventHandler/AnalyticsEventHandler.php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventHandler;

use App\Inventory\Domain\Event\StockReservedEvent;
use Psr\Log\LoggerInterface;

class AnalyticsEventHandler
{
    public function __construct(
        private LoggerInterface $analyticsLogger
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onStockReserved(StockReservedEvent $event): void
    {
        $this->analyticsLogger->info('STOCK_RESERVED', [
            'stock_id' => $event->stockId,
            'quantity' => $event->quantity,
            'timestamp' => $event->occurredAt->getTimestamp(),
        ]);
    }
}
```

#### Notification Handler
```php
// src/Inventory/Infrastructure/EventHandler/ReservationNotificationHandler.php
<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\EventHandler;

use App\Inventory\Domain\Event\ReservationExpiredEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ReservationNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onReservationExpired(ReservationExpiredEvent $event): void
    {
        // Send email notification about expired reservation
        $email = (new Email())
            ->from('noreply@fastreserve.com')
            ->to('user@example.com')
            ->subject('Reservation Expired')
            ->text('Your reservation has expired.');

        $this->mailer->send($email);
    }
}
```

---

## Phase 5: Advanced Topics (P2 - Optional)

These are **optional** enhancements that would make the project exceptional but are not required for solid DDD practice.

### 5.1 Event Sourcing (3-5 days)

**Goal:** Store all domain events and rebuild aggregates from event history.

**Key Components:**
- Event store implementation
- Aggregate snapshotting
- Event replay functionality
- Event versioning

**When to implement:**
- Need complete audit trail
- Want temporal queries
- Complex business workflows

### 5.2 CQRS Read Models (1-2 days)

**Goal:** Separate read-optimized database from write model.

**Key Components:**
- Denormalized read tables
- Projection handlers
- Read model repositories
- Event-driven updates

**When to implement:**
- Complex query requirements
- Performance optimization
- Reporting needs

### 5.3 Saga Pattern (2-3 days)

**Goal:** Coordinate multi-aggregate transactions without distributed transactions.

**Key Components:**
- Saga manager
- Saga state machine
- Compensation actions
- Timeout handling

**When to implement:**
- Reservation → Payment → Order flow
- Multi-warehouse reservation
- Third-party integrations

---

## Implementation Roadmap

### Week 1: Foundation (P0)
- **Day 1:** Complete aggregate encapsulation
  - Fix User entity
  - Review all entities
  - Update handlers
  - Run tests

- **Day 2-3:** Implement domain events
  - Create base classes
  - Add trait to aggregates
  - Define events

### Week 2: Integration (P0-P1)
- **Day 1:** Implement event dispatcher
  - Configure Messenger
  - Create handlers

- **Day 2:** Update command handlers
  - Dispatch events
  - Test integration

- **Day 3-4:** Specification pattern
  - Create interface
  - Implement specifications
  - Use in domain services

### Week 3+: Enhancement (P1-P2)
- Domain event handlers
- More specifications
- Optional: Event sourcing
- Optional: CQRS read models

---

## Testing Strategy

### Unit Tests
```php
// tests/Unit/Domain/Specification/ActiveReservationSpecificationTest.php
class ActiveReservationSpecificationTest extends TestCase
{
    public function test_active_reservation_satisfies_spec(): void
    {
        $spec = new ActiveReservationSpecification();
        $reservation = $this->createActiveReservation();

        $this->assertTrue($spec->isSatisfiedBy($reservation));
    }
}
```

### Integration Tests
```php
// tests/Integration/Event/DomainEventDispatchTest.php
class DomainEventDispatchTest extends KernelTestCase
{
    public function test_stock_reservation_dispatches_event(): void
    {
        $stock = $this->stockRepository->find($id);
        $user = $this->userRepository->find($userId);

        $stock->reserve(10, $user);

        $this->assertTrue($stock->hasDomainEvents());
        $events = $stock->pullDomainEvents();
        $this->assertInstanceOf(StockReservedEvent::class, $events[0]);
    }
}
```

### Performance Tests
```php
// tests/Performance/SpecificationPerformanceTest.php
class SpecificationPerformanceTest extends TestCase
{
    public function test_specification_overhead(): void
    {
        $spec = new ActiveReservationSpecification();
        $reservations = $this->generateReservations(1000);

        $start = microtime(true);
        $filtered = array_filter($reservations, fn($r) => $spec->isSatisfiedBy($r));
        $time = microtime(true) - $start;

        $this->assertLessThan(0.01, $time); // < 10ms for 1000 items
    }
}
```

---

## Success Criteria

### Phase 1: Aggregate Encapsulation ✅
- [ ] No public properties in entities (except value objects)
- [ ] All state changes go through domain methods
- [ ] Business rules enforced in entities
- [ ] Symfony Security still works (getters maintained)

### Phase 2: Domain Events ✅
- [ ] Aggregates record events
- [ ] Events dispatched via Messenger
- [ ] Event handlers process events
- [ ] Events survive transactions (committed only)

### Phase 3: Specifications ✅
- [ ] Complex business rules in specifications
- [ ] Specifications composable (AND, OR, NOT)
- [ ] Used in domain services and queries
- [ ] Unit tests for all specifications

### Overall ✅
- [ ] All PHPStan checks pass
- [ ] All tests pass (unit + integration)
- [ ] Documentation updated (ARCHITECTURE.md)
- [ ] API tests still pass (user-management.http)

---

## Additional Resources

### Books
- **"Domain-Driven Design"** by Eric Evans
- **"Implementing Domain-Driven Design"** by Vaughn Vernon
- **"Patterns, Principles, and Practices of Domain-Driven Design"** by Scott Millett

### Articles
- [DDD Tactical Patterns](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
- [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html)

### Symfony + DDD
- [Symfony Messenger Best Practices](https://symfony.com/doc/current/messenger.html)
- [Doctrine Best Practices](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/best-practices.html)

---

## Conclusion

Following this improvement plan will elevate FastReserve from **solid** to **exceptional** DDD implementation. The key is to:

1. **Complete foundations first** (encapsulation, events)
2. **Add patterns incrementally** (specifications, handlers)
3. **Test thoroughly** at each step
4. **Document decisions** as you go

**Estimated total time:** 40-50 hours for P0-P1 phases
**Result:** Production-grade DDD codebase suitable for learning and real-world use.

---

**Status Tracking:**

- [ ] Phase 1: Complete Aggregate Encapsulation (P0)
- [ ] Phase 2: Implement Domain Events (P0)
- [ ] Phase 3: Specification Pattern (P1)
- [ ] Phase 4: Enhance Domain Events (P1)
- [ ] Phase 5: Advanced Topics (P2 - Optional)

**Last Updated:** 2025-01-10