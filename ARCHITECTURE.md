# FastReserve Architecture Documentation

## Overview

**FastReserve** is a high-performance inventory reservation system built with **Symfony 8.0**, **PHP 8.4**, and **Domain-Driven Design (DDD)** principles. It provides real-time stock reservation capabilities with pessimistic locking to prevent race conditions.

### Core Purpose
Manage inventory stock across multiple warehouses with temporary reservation functionality, allowing users to reserve items for a limited time before converting to sales.

---

## Architecture Pattern: DDD + CQRS

### Domain-Driven Design Layers

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│                    (Controllers, DTOs)                      │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                  Application Layer                          │
│            (Commands, Queries, Handlers)                    │
│                   CQRS Pattern                              │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                    Domain Layer                             │
│     (Entities, Value Objects, Domain Services)              │
│                  Business Logic                             │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                 Infrastructure Layer                        │
│       (Repositories, Persistence, Security, Fixtures)       │
└─────────────────────────────────────────────────────────────┘
```

### CQRS Implementation

**Command Bus** (Write operations):
- Commands: `ReserveStockMessage`, `CreateWarehouseMessage`, `CreateUserMessage`
- Async processing via Messenger
- Transactional middleware (doctrine_transaction)

**Query Bus** (Read operations):
- Queries: `GetStockLevelQuery`, `ListUsersQuery`
- Sync processing with connection ping middleware

---

## Domain Models

### Bounded Contexts

#### 1. Inventory Context

##### Aggregate Root: **Stock**
```php
class Stock {
    Uuid $id
    SKU $sku                          // Value Object
    int $totalQuantity
    Warehouse $warehouse              // Many-to-One
    Collection<Reservation> $reservations  // One-to-Many
    DateTimeImmutable $createdAt

    // Domain Logic
    getReservedQuantity(): int
    getAvailableQuantity(): int
    reserve(quantity, user, minutes): Reservation
    adjustQuantity(newQuantity): void
}
```

**Key Behaviors:**
- Prevents negative quantities
- Calculates available = total - reserved(active)
- Enforces reservation rules (15-60 min validity)
- Prevents reducing quantity below reserved amount

##### Entity: **Reservation**
```php
class Reservation {
    Uuid $id
    Stock $stock                      // Many-to-One (aggregate root)
    int $quantity
    User $user                        // Many-to-One
    ?string $orderReference           // Set when converted to sale
    DateTimeImmutable $createdAt
    DateTimeImmutable $expiresAt
    string $status                   // ACTIVE, CONVERTED_TO_SALE, EXPIRED, CANCELLED

    // State Management
    isExpired(): bool
    isActive(): bool
    cancel(): void
    convertToSale(orderRef): void
}
```

**Reservation Lifecycle:**
```
ACTIVE → [expires] → EXPIRED (still in DB, inactive)
ACTIVE → [cancel()] → CANCELLED
ACTIVE → [convertToSale()] → CONVERTED_TO_SALE
```

##### Value Objects

**SKU** (Stock Keeping Unit):
```php
readonly class SKU {
    string $code      // min 3 chars
    string $name
}
```

**Location**:
```php
readonly class Location {
    string $address
    string $city
    string $postalCode
    ?float $latitude     // validated: -90 to 90
    ?float $longitude    // validated: -180 to 180
}
```

##### Entity: **Warehouse**
```php
class Warehouse {
    Uuid $id
    string $name
    int $capacity
    bool $isActive
    WarehouseTypeEnum $type
    Location $location          // Embedded value object
    Collection<Stock> $stocks   // One-to-Many

    activate(), deactivate()
    changeType(type)
}
```

#### 2. Account Context

##### Entity: **User**
```php
class User implements UserInterface {
    Uuid $id
    string $email
    array $roles
    string $password            // SHA-256 hashed
    Collection<ApiToken> $apiTokens
    Collection<Reservation> $reservations
    DateTimeImmutable $createdAt

    promoteToAdmin(): void
    activeReservations(): Collection
}
```

##### Entity: **ApiToken**
```php
class ApiToken {
    Uuid $id
    string $token               // BCrypt hashed
    User $user
    ?string $description
    DateTimeImmutable $createdAt
    ?DateTimeImmutable $expiresAt
    ?DateTimeImmutable $lastUsedAt

    isValid(): bool
    verify(plainToken): bool
    markAsUsed(): void
}
```

---

## Domain Services

### **StockDomainService**

**Purpose:** Orchestrate complex business rules that span multiple aggregates.

```php
class StockDomainService {
    reserveStock(stock, user, quantity, minutes): Reservation {
        // 1. Check user's active reservations count
        if (user->activeReservations()->count() >= 10) {
            throw InsufficientStockException('Too many active reservations')
        }

        // 2. Delegate to Stock aggregate
        return stock->reserve(quantity, user, minutes)
    }
}
```

**Business Rule:** Users limited to 10 concurrent active reservations (prevents hoarding).

---

## Application Layer

### Command Handlers (Write Side)

#### **ReserveStockHandler**
```php
__invoke(ReserveStockMessage $message) {
    // 1. Load Stock with PESSIMISTIC WRITE lock
    stock = stockRepository->findWithLock(message->stockId)

    // 2. Load User
    user = userRepository->find(message->userId)

    // 3. Execute domain logic via service
    domainService->reserveStock(stock, user, quantity, minutes)

    // 4. Persist (inside transaction)
    stockRepository->save(stock)
}
```

**Why Pessimistic Locking?**
- Prevents race conditions: concurrent requests for same stock
- Ensures `getAvailableQuantity()` is accurate
- Database-level lock (`SELECT ... FOR UPDATE`)

#### **CreateWarehouseHandler**, **CreateUserHandler**, **GenerateApiTokenHandler**
- Standard CRUD operations
- Async processing via command bus

### Query Handlers (Read Side)

#### **GetStockLevelQueryHandler**
```php
__invoke(GetStockLevelQuery $query): StockLevelDto {
    stock = stockRepository->find(query->stockId)
    return StockLevelDto {
        stockId, skuCode, skuName,
        totalQuantity, availableQuantity, reservedQuantity,
        warehouseName, location
    }
}
```

**No locks needed** - read-only operations.

### DTOs (Data Transfer Objects)

**ReserveStockRequestDto**:
```php
UuidV7 $stockId        // validated
int $quantity          // positive, max 60
int $minutesValid      // 1-60 range, default 15
```

---

## Infrastructure Layer

### Repository Pattern

**Interface-Implementation Separation:**
```php
// Domain Layer (interface)
interface StockRepositoryInterface {
    findWithLock(UuidV7 $id): ?Stock
    findAllWithWarehouse(): ?array
    save(Stock $stock): void
}

// Infrastructure Layer (implementation)
class StockRepository extends ServiceEntityRepository
    implements StockRepositoryInterface {
    findWithLock($id) {
        return find($id, LockMode::PESSIMISTIC_WRITE)
    }
}
```

**Service Binding** (config/services.yaml):
```yaml
App\Inventory\Domain\Repository\StockRepositoryInterface:
    '@App\Inventory\Infrastructure\Persistence\Doctrine\StockRepository'
```

### Controllers

#### **ReservationController**
```php
POST /api/reserve
- Authentication required (ROLE_USER)
- MapRequestPayload → ReserveStockRequestDto
- Dispatches ReserveStockMessage to command bus (async)
- Returns 202 ACCEPTED

GET /api/stock/{id}/level
- Returns StockLevelDto
- Query bus (sync)
```

### Security: **ApiTokenAuthenticator**

```php
authenticate(Request $request) {
    // 1. Extract "Bearer <token>" from Authorization header
    // 2. Find token in database
    // 3. Validate: not expired, not revoked
    // 4. Update lastUsedAt timestamp
    // 5. Return SelfValidatingPassport with User
}
```

**Flow:**
```
Request → Authorization Header
         → ApiTokenAuthenticator
         → Verify token hash (password_verify)
         → Check expiry
         → Load User entity
         → Symfony Security Context
```

---

## Key Business Flows

### 1. Stock Reservation Flow

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ POST /api/reserve
       │ { stockId, quantity, minutesValid }
       │ Authorization: Bearer <token>
       ▼
┌─────────────────────┐
│  Security Firewall  │ → ApiTokenAuthenticator → User Entity
└─────────────────────┘
       │
       ▼
┌─────────────────────────┐
│ ReservationController   │
│ - Validate DTO          │
│ - Check User            │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│   Command Bus (async)           │
│   ReserveStockMessage           │
└────────┬────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│     ReserveStockHandler             │
│  1. findWithLock(stockId)           │ → Pessimistic Lock
│  2. Load user                       │
│  3. domainService->reserveStock()   │
│  4. repository->save()              │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│    StockDomainService               │
│  - Check user active reservations   │
│  - Call stock->reserve()            │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│    Stock Aggregate                  │
│  - Calculate available qty          │
│  - Create Reservation entity        │
│  - Add to reservations collection   │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│    Database Transaction             │
│  BEGIN                              │
│  INSERT INTO inventory_reservations │
│  COMMIT                             │
└─────────────────────────────────────┘
```

**Concurrency Protection:**
- Request A: Locks stock row, calculates availability, creates reservation
- Request B: **Blocked** at lock until A commits
- Request B: Reads updated availability (sees A's reservation)

### 2. Query Stock Level Flow

```
Client → GET /api/stock/{id}/level
      → ReservationController
      → Query Bus (sync)
      → GetStockLevelQueryHandler
      → StockRepository->find()
      → StockLevelDto (JSON)
```

**No locks, no writes, pure read.**

### 3. Authentication Flow

```
┌──────────────┐
│   Client     │
└──────┬───────┘
       │ POST /auth/login
       │ { email, password }
       ▼
┌─────────────────────┐
│  AuthController     │
│  - Verify password  │
│  - Generate token   │
│  - Return token     │
└─────────────────────┘

┌──────────────┐
│   Client     │
└──────┬───────┘
       │ GET /api/reserve (later)
       │ Authorization: Bearer abc123...
       ▼
┌──────────────────────────┐
│  ApiTokenAuthenticator   │
│  1. Extract token        │
│  2. Find in DB           │
│  3. password_verify()    │
│  4. Check expiry         │
│  5. Load User entity     │
│  6. Set Security Context │
└──────────────────────────┘
       │
       ▼
┌─────────────────────┐
│  Protected Endpoint │
│  (User available)   │
└─────────────────────┘
```

---

## Database Schema

### Tables

**inventory_stocks**
```
id (UUID, PK)
sku_code (VARCHAR 50, indexed)
sku_name (VARCHAR 255)
total_quantity (INT)
warehouse_id (UUID, FK)
created_at (TIMESTAMP)
```

**inventory_reservations**
```
id (UUID, PK)
stock_id (UUID, FK, CASCADE)
quantity (INT)
user_id (UUID, FK, CASCADE)
order_reference (VARCHAR 255, nullable)
created_at (TIMESTAMP)
expires_at (TIMESTAMP, indexed)
status (VARCHAR 20)
```

**inventory_warehouses**
```
id (UUID, PK)
name (VARCHAR 100)
capacity (INT)
is_active (BOOLEAN)
type (ENUM)
created_at (TIMESTAMP)
address (VARCHAR 100)
city (VARCHAR 50)
postal_code (VARCHAR 10)
latitude (DECIMAL 10,8)
longitude (DECIMAL 11,8)
```

**Account tables:**
- `users` (id, email, roles, password, created_at)
- `api_tokens` (id, token [hashed], user_id, description, created_at, expires_at, last_used_at)

---

## Configuration Highlights

### Messenger (CQRS Routing)

```yaml
messenger:
    transports:
        sync: 'sync://'
        async: '%env(MESSENGER_TRANSPORT_DSN)%'
        failed: 'doctrine://default?queue_name=failed'

    routing:
        'App\Inventory\Application\Command\*': async
        'App\Inventory\Application\Query\*': sync

    buses:
        command.bus:
            middleware: [doctrine_transaction]
        query.bus:
            middleware: [doctrine_ping_connection]
```

**Key Points:**
- Commands → Async queue (can be RabbitMQ, Redis, etc.)
- Queries → Sync (immediate response)
- Command bus wrapped in DB transaction
- Query bus only pings connection (no transaction)

### Security

```yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Account\Domain\Model\User
                property: email

    firewalls:
        api:
            pattern: ^/api/(users|tokens|reserve)
            stateless: true
            custom_authenticators:
                - App\Account\Infrastructure\Security\ApiTokenAuthenticator

    access_control:
        - { path: ^/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/stock, roles: PUBLIC_ACCESS }
        - { path: ^/api/reserve, roles: ROLE_USER }
        - { path: ^/api/users, roles: ROLE_ADMIN }
```

---

## Testing Data Fixtures

### **ReservationFixtures**
Generates 2000 reservations with realistic distribution:
- Random stock/user assignment
- Creation times: past 30 days
- Validity periods: 15, 30, 60, 120 minutes
- Status distribution:
  - Active: 70%
  - Converted to sale: 15%
  - Cancelled: 15%
- Memory optimization: flush every 10, clear every 100

**Dependencies:** `StockFixtures`, `UserFixtures` (loaded first)

---

## API Endpoints Summary

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/auth/login` | Public | User login, returns API token |
| GET | `/api/stock/{id}/level` | Public | Get stock availability |
| POST | `/api/reserve` | ROLE_USER | Reserve stock quantity |
| GET | `/api/users` | ROLE_ADMIN | List users |
| POST | `/api/tokens` | ROLE_USER | Generate API token |
| GET | `/api/tokens` | ROLE_USER | List my tokens |

---

## Key Design Decisions

### 1. **Pessimistic Locking**
- **Why:** Prevents overselling in high-concurrency scenarios
- **Trade-off:** Reduced throughput, increased DB lock contention
- **Alternative:** Optimistic locking (version field) with retry logic

### 2. **Reservation Expiration**
- **Design:** Reservations remain in DB after expiry (marked inactive)
- **Why:** Audit trail, analytics, can be cleaned up later
- **Query:** `isActive()` checks both status + expiry time

### 3. **BCrypt for API Tokens**
- **Why:** Compromise between security and performance
- **Note:** Not ideal for high-throughput APIs (consider JWT for scale)

### 4. **SHA-256 for User Passwords**
- **Current:** Simple hash (`hash('sha256', $password)`)
- **Recommendation:** Use `password_hash()` with bcrypt/argon2

### 5. **Separate Command/Query Buses**
- **Why:** Different consistency, performance, and scaling requirements
- **Commands:** Eventually consistent, async processing
- **Queries:** Strongly consistent, immediate response

---

## Performance Considerations

### Database Indexes
```php
#[ORM\Index(name: 'idx_stock_sku', columns: ['sku_code'])]
#[ORM\Index(name: 'idx_reservation_expiry', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_user_email', columns: ['email'])]
```

### Optimizations
1. **Pessimistic locking** on stock reservation (prevents race conditions)
2. **Batch processing** in fixtures (flush every 10, clear every 100)
3. **Query optimization:** `findAllWithWarehouse()` with JOIN fetch
4. **Async commands** prevent blocking HTTP requests

### Potential Bottlenecks
- High concurrency on popular stock items (lock contention)
- Token verification on every request (BCrypt is slow)
- Missing cache layer for stock queries

---

## Extension Points

### 1. **Add Event Sourcing**
- Emit domain events: `StockReserved`, `ReservationExpired`
- Async processing for notifications, analytics

### 2. **Implement Sagas**
- Multi-warehouse reservation coordination
- Payment integration after reservation

### 3. **Add Caching**
- Redis cache for stock levels (invalidated on writes)
- Cache user permissions/tokens

### 4. **Improve Security**
- JWT tokens instead of BCrypt (better performance)
- Rate limiting per user
- API key scopes (read/write permissions)

### 5. **Add Background Jobs**
- Cleanup expired reservations (cron job)
- Email notifications for expiring reservations
- Analytics aggregation

---

## Technology Stack

- **Framework:** Symfony 8.0
- **Language:** PHP 8.4
- **Database:** MySQL/PostgreSQL (via Doctrine ORM)
- **Message Bus:** Symfony Messenger
- **Authentication:** Custom API token authenticator
- **Validation:** Symfony Validator
- **Testing:** PHPUnit, Doctrine Fixtures
- **Code Quality:** PHPStan, PHP-CS-Fixer

---

## File Structure

```
src/
├── Account/
│   ├── Application/
│   │   ├── Command/          # CreateUserMessage, GenerateApiTokenMessage
│   │   └── Query/            # ListUsersQuery
│   ├── Domain/
│   │   ├── Model/            # User, ApiToken
│   │   └── Repository/       # Interfaces
│   └── Infrastructure/
│       ├── Controller/       # UserController, AuthController
│       ├── Persistence/      # Doctrine repositories
│       ├── Security/         # ApiTokenAuthenticator
│       └── DataFixtures/
├── Inventory/
│   ├── Application/
│   │   ├── Command/          # ReserveStockMessage, CreateWarehouseMessage
│   │   ├── Query/            # GetStockLevelQuery
│   │   └── Dto/              # Request/Response DTOs
│   ├── Domain/
│   │   ├── Model/            # Stock, Reservation, Warehouse, SKU, Location
│   │   ├── Service/          # StockDomainService
│   │   ├── Exception/        # InsufficientStockException
│   │   └── Repository/       # Interfaces
│   └── Infrastructure/
│       ├── Controller/       # ReservationController
│       ├── Persistence/      # Doctrine repositories
│       └── DataFixtures/
└── Kernel.php
```

---

## Conclusion

FastReserve demonstrates a clean, modern architecture combining:
- **Domain-Driven Design** for business logic isolation
- **CQRS** for separation of read/write concerns
- **Pessimistic Locking** for data consistency
- **Async Messaging** for scalability

The codebase is production-ready with comprehensive fixtures, security, and error handling.