# FastReserve Authentication & User Management Guide

## Overview

FastReserve uses **admin-created users** as the authentication model, following best practices for B2B inventory management systems. All user management operations are performed by administrators only.

## Authentication Flow

```
1. Admin (from fixtures) → Login → Get API Token
2. Admin → Create New Users → Users can now login
3. New Users → Login → Get their own API Tokens
4. Users → Use API Tokens → Access protected endpoints
```

## Initial Setup

### 1. Load Fixtures

```bash
php bin/console doctrine:fixtures:load
```

This creates:
- **Admin User**: `admin@fastreserve.com` / `admin123`
- **5 Warehouses**
- **1,000 Stock Items** (200 per warehouse)

### 2. Start Symfony Server

```bash
php bin/console server:run
```

## API Endpoints

### Public Endpoints (No Authentication Required)

#### `POST /auth/login`
Login and automatically generate an API token.

**Request:**
```json
{
  "email": "admin@fastreserve.com",
  "password": "admin123",
  "description": "Optional token description",
  "expiresInDays": 30  // Optional, default 30 days
}
```

**Response (201 Created):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "email": "admin@fastreserve.com",
    "roles": ["ROLE_ADMIN", "ROLE_USER"]
  },
  "token": "abc123xyz...",  // Your API token - SAVE THIS!
  "tokenId": 1
}
```

#### `GET /api/stock/{id}/level`
Get stock level information (publicly accessible).

### Admin-Only Endpoints (Requires ROLE_ADMIN)

**Authentication:** `Authorization: Bearer {admin_token}`

#### `POST /api/users`
Create a new user.

**Request:**
```json
{
  "email": "new.user@fastreserve.com",
  "password": "securePassword123",
  "roles": ["ROLE_USER"]  // or ["ROLE_ADMIN"] for admin users
}
```

**Response (201 Created):**
```json
{
  "id": 2,
  "email": "new.user@fastreserve.com",
  "roles": ["ROLE_USER"],
  "createdAt": "2024-12-27T20:00:00+00:00"
}
```

#### `GET /api/users`
List all users in the system.

**Response (200 OK):**
```json
[
  {
    "id": 1,
    "email": "admin@fastreserve.com",
    "roles": ["ROLE_ADMIN", "ROLE_USER"],
    "createdAt": "2024-12-27T20:00:00+00:00"
  },
  {
    "id": 2,
    "email": "new.user@fastreserve.com",
    "roles": ["ROLE_USER"],
    "createdAt": "2024-12-27T20:00:00+00:00"
  }
]
```

### User Endpoints (Requires ROLE_USER or ROLE_ADMIN)

**Authentication:** `Authorization: Bearer {api_token}`

#### `GET /api/tokens`
List all API tokens for the authenticated user.

#### `POST /api/tokens`
Generate an additional API token.

**Request:**
```json
{
  "description": "Mobile app token",
  "expiresInDays": 60
}
```

**Response (201 Created):**
```json
{
  "id": 2,
  "token": "xyz789abc...",
  "description": "Mobile app token",
  "createdAt": "2024-12-27T20:00:00+00:00",
  "expiresAt": "2025-02-25T20:00:00+00:00"
}
```

#### `DELETE /api/tokens/{id}`
Revoke an API token.

### Protected Resource Endpoints

#### `GET /api/admin/warehouses` (ROLE_ADMIN only)
List all warehouses.

#### `POST /api/reserve` (ROLE_USER or ROLE_ADMIN)
Reserve stock items.

## Complete Workflow Example

### Step 1: Admin Login

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@fastreserve.com",
    "password": "admin123",
    "description": "Initial admin token"
  }'
```

**Save the token from the response:** `abc123xyz...`

### Step 2: Create a New User

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer abc123xyz..." \
  -d '{
    "email": "warehouse.manager@fastreserve.com",
    "password": "manager123",
    "roles": ["ROLE_USER"]
  }'
```

### Step 3: Login as New User

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "warehouse.manager@fastreserve.com",
    "password": "manager123",
    "description": "Warehouse manager token"
  }'
```

**Save the new token:** `xyz789abc...`

### Step 4: Use Token to Access Protected Resources

```bash
# List warehouses (admin only - will fail for regular user)
curl http://localhost:8000/api/admin/warehouses \
  -H "Authorization: Bearer abc123xyz..."

# Reserve stock (user or admin)
curl -X POST http://localhost:8000/api/reserve \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer xyz789abc..." \
  -d '{
    "stockId": 1,
    "quantity": 10,
    "minutesValid": 15
  }'
```

## HTTP Test Files

### `tests/user-management.http`
Comprehensive test suite covering:
- Admin login
- User creation (admin only)
- User listing (admin only)
- New user login
- Token management
- Authorization testing
- Error cases and validation

### `tests/auth-tokens.http`
Focuses specifically on:
- Login variations
- Token generation
- Token listing
- Token revocation

## Security Features

### Password Hashing
All passwords are hashed using Symfony's default password hasher (bcrypt).

### API Token Storage
- Tokens are hashed using `password_hash()` with PASSWORD_BCRYPT
- Plain tokens are only shown once at creation/login time
- Tokens can be revoked but not deleted

### Access Control
- `/auth/login` - Public
- `/api/users` - Admin only
- `/api/tokens` - Authenticated users
- `/api/admin/*` - Admin only
- `/api/reserve` - Authenticated users
- `/api/stock/*` - Public

## Testing with REST Client

### VS Code
Install the "REST Client" extension and click "Send Request" above each HTTP request.

### PhpStorm
Built-in HTTP client support. Just open the `.http` files and click the green play button.

## Common Workflows

### Creating a Warehouse Staff User
1. Login as admin
2. Create user with `ROLE_USER`
3. Share credentials with staff member
4. Staff member logs in and gets their own token

### Creating Another Admin
1. Login as existing admin
2. Create user with `ROLE_ADMIN`
3. New admin can now manage users and warehouses

### Rotating Tokens
1. Login to get a new token
2. Use the new token in your application
3. (Optional) Revoke old tokens via `DELETE /api/tokens/{id}`

## Architecture

### DDD Structure
- **Domain**: User, ApiToken entities with business logic
- **Application**: Commands/Queries for user operations
- **Infrastructure**: Controllers, persistence, security

### Command Handlers
- `CreateUserHandler` - Creates new users with password hashing
- `GenerateApiTokenHandler` - Generates secure random tokens
- `ListUsersQueryHandler` - Lists all users (admin only)
- `ListApiTokensQueryHandler` - Lists user's tokens

### Security
- `ApiTokenAuthenticator` - Custom authenticator for API tokens
  - Skips `/auth/login` (allows public login)
  - Skips `/api/stock/*` (allows public stock queries)
  - Requires Bearer token for all other `/api/*` endpoints
- `AuthController` - Public login endpoint
- `UserController` - Admin-only user management
- `ApiTokenController` - Token management for authenticated users

### How Authentication Works

1. **Public Endpoints** (no token required):
   - `/auth/login` - Get your first API token
   - `/api/stock/*` - View stock levels

2. **Protected Endpoints** (require Bearer token):
   - `/api/users` - User management (admin only)
   - `/api/tokens` - Token management
   - `/api/admin/*` - Admin operations
   - `/api/reserve` - Stock reservations

3. **Flow**:
   ```
   User → POST /auth/login (email/password)
        ↓
   Returns API token
        ↓
   User → GET /api/users (Authorization: Bearer {token})
        ↓
   Access granted
   ```

## Troubleshooting

### "Invalid credentials" on login
- Check email/password matches fixtures: `admin@fastreserve.com` / `admin123`
- Verify fixtures are loaded: `php bin/console doctrine:fixtures:load`

### "User with this email already exists"
- User creation is idempotent - you cannot create duplicate emails
- List users to see existing ones: `GET /api/users`

### "Access denied" or 403 Forbidden
- Verify your user has the required role
- Admin endpoints require `ROLE_ADMIN`
- Check your token is valid and not expired

### "No API token provided" or 401 Unauthorized
- Ensure `Authorization: Bearer {token}` header is set
- Check token hasn't been revoked
- Verify token hasn't expired

## Architecture

### DDD Structure
- **Domain**: User, ApiToken entities with business logic
- **Application**: Commands/Queries for user operations
- **Infrastructure**: Controllers, persistence, security

### Command Handlers
- `CreateUserHandler` - Creates new users with password hashing
- `GenerateApiTokenHandler` - Generates secure random tokens
- `ListUsersQueryHandler` - Lists all users (admin only)
- `ListApiTokensQueryHandler` - Lists user's tokens

### Security
- `ApiTokenAuthenticator` - Custom authenticator for API tokens
  - Skips `/auth/login` (allows public login)
  - Skips `/api/stock/*` (allows public stock queries)
  - Requires Bearer token for all other `/api/*` endpoints
- `AuthController` - Public login endpoint
- `UserController` - Admin-only user management
- `ApiTokenController` - Token management for authenticated users
