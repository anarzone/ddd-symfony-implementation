# FastReserve HTTP API Tests

This directory contains HTTP test files organized by domain for the FastReserve API.

## Test Files Organization

### 1. **auth-tokens.http** - Authentication & Token Management
Tests for user authentication and API token operations:
- Login with various configurations (default/custom expiry, descriptions)
- Login validation errors (invalid credentials, missing fields)
- API token generation and management
- Token revocation
- Token authentication testing

**Use when:** Testing authentication flows, token generation, or token management

---

### 2. **user-management.http** - User CRUD Operations
Complete user lifecycle management tests:
- Admin login
- Create users (standard and admin roles)
- List and update users
- User management validation errors
- Complete workflow: Admin → Create User → User Login → Use API

**Use when:** Testing user creation, updates, or complete user workflows

---

### 3. **warehouse-management.http** - Warehouse Operations
Warehouse CRUD operations tests:
- Create warehouses (STANDARD, COLD_STORAGE, AUTOMATED types)
- List and get warehouse information
- Activate/deactivate warehouses
- Validation errors (coordinates, missing fields, invalid types)
- Authorization checks (admin-only endpoints)

**Use when:** Testing warehouse operations

---

### 4. **stock-reservation.http** - Stock & Inventory
Stock reservation and inventory tests:
- Reserve stock with various durations (1-60 minutes)
- Stock reservation validation errors
- Stock level queries (public endpoints)
- Concurrent reservation tests
- Business logic tests (insufficient stock, non-existent items)

**Use when:** Testing stock reservation, inventory queries, or concurrency

---

### 5. **quick-test.http** - Quick Development Tests
Simplified tests for quick development:
- No authentication required (temporarily disable security)
- Focuses on happy path and basic scenarios
- Perfect for initial development and debugging

**Use when:** Quick testing during development (requires disabling auth)

## Prerequisites

### 1. Install REST Client Extension
- **VS Code**: Install "REST Client" by Huachao Mao
- **PhpStorm**: Built-in REST Client (no installation needed)

### 2. Start Symfony Server
```bash
symfony server:start
# or
php bin/console server:run
```

### 3. Setup Database
```bash
# Create database
php bin/console doctrine:database:create

# Create schema
php bin/console doctrine:schema:create

# Load fixtures (optional)
php bin/console doctrine:fixtures:load
```

## Configuration

### Environment Variables
Edit `http-client.env.json` to configure your environment:
```json
{
    "dev": {
        "baseUrl": "https://fastreserve.test",
        "authToken": "your-api-token-here"
    }
}
```

### Getting an API Token

**Option 1: Use Login Endpoint**
Run the login test in `auth-tokens.http`:
```http
POST {{baseUrl}}/auth/login
Content-Type: application/json

{
    "email": "admin@fastreserve.com",
    "password": "admin123",
    "description": "Development token"
}
```
Copy the returned token to `http-client.env.json`

**Option 2: Quick Testing (No Auth)**
For quick testing, temporarily disable security in controllers:

**ReservationController.php**: Comment out user check
**WarehouseController.php**: Comment out `#[IsGranted('ROLE_ADMIN')]`

*Remember to re-enable security after testing!*

## Running Tests

### Individual Requests
1. Open any `.http` file
2. Click "Send Request" link above the desired request
3. View response in the dedicated panel

### Using Named Requests
Named requests (e.g., `# @name createWarehouse`) can be referenced:
```http
@baseUrl = http://localhost:8000

# @name login
POST {{baseUrl}}/api/login
Content-Type: application/json

{"username":"admin","password":"password"}

###

# Use the token from login
@authToken = {{login.response.body.$.token}}

GET {{baseUrl}}/api/admin/warehouses
Authorization: Bearer {{authToken}}
```

## Test Coverage

### Warehouse Management
- ✅ Create warehouses (all types: STANDARD, COLD_STORAGE, HAZARDOUS, AUTOMATED)
- ✅ List all warehouses
- ✅ Get warehouse details
- ✅ Activate/deactivate warehouses
- ✅ Validation tests

### Stock Reservations
- ✅ Reserve stock (various quantities and durations)
- ✅ Check stock levels
- ✅ Insufficient stock scenarios
- ✅ Concurrent reservation tests

### Security
- ✅ Authentication tests
- ✅ Authorization tests (ROLE_ADMIN vs ROLE_USER)
- ✅ Access control tests

### Error Handling
- ✅ Not found (404)
- ✅ Validation errors (400)
- ✅ Unauthorized (401)
- ✅ Forbidden (403)
- ✅ Invalid input data

## Common Scenarios

### Scenario 1: Create and Reserve
```http
### 1. Create warehouse
POST {{baseUrl}}/api/admin/warehouses
{
    "name": "Test Warehouse",
    "capacity": 1000,
    "address": "123 Test St",
    "city": "Test City",
    "postalCode": "12345"
}

### 2. Reserve stock
POST {{baseUrl}}/api/reserve
{
    "stockId": 1,
    "quantity": 10
}

### 3. Check stock level
GET {{baseUrl}}/api/stock/1/level
```

### Scenario 2: Error Handling
```http
### Try to reserve more than available
POST {{baseUrl}}/api/reserve
{
    "stockId": 1,
    "quantity": 999999
}

### Expected: 400 Bad Request with error message
```

## Troubleshooting

### "Connection Refused"
- Ensure Symfony server is running: `symfony server:start`
- Check the `@baseUrl` matches your server address

### "401 Unauthorized"
- Either disable security temporarily (see Configuration)
- Or generate and configure JWT token

### "403 Forbidden"
- User doesn't have required role
- Ensure user has `ROLE_ADMIN` for admin endpoints

### "404 Not Found"
- Entity doesn't exist in database
- Check database has test data
- Run `php bin/console doctrine:fixtures:load`

### Schema Issues
```bash
# Drop and recreate database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

## Tips

1. **Variable Substitution**: Use `{{variableName}}` to reference variables
2. **Comments**: Lines starting with `#` are comments
3. **Separators**: Use `###` to separate multiple requests in a file
4. **File Organization**: Group related tests together with comment headers
5. **Named Requests**: Useful for chaining requests (auth flows, etc.)

## Additional Resources

- [Symfony REST Client Documentation](https://symfony.com/doc/current/controller.html#json-responses)
- [REST Client Extension Docs](https://marketplace.visualstudio.com/items?itemName=humao.rest-client)
- [HTTP File Format](https://youtrack.jetbrains.com/articles/WEBCLIENT-HTTP/HTTP-Client-in-IDEA-code-editor)

## Production Testing

For production testing:
1. Enable authentication (JWT)
2. Use environment-specific `@baseUrl`
3. Test with realistic payloads
4. Monitor rate limiting
5. Test concurrent requests for race conditions
6. Verify database transactions (especially for stock reservations)
