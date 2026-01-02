# FastReserve HTTP API Tests

This directory contains HTTP test files for testing the FastReserve API endpoints.

## Files

### 1. `quick-test.http`
Simplified tests for quick development and testing.
- No authentication required (temporarily disable security in controllers)
- Focuses on happy path and basic scenarios
- Perfect for initial development and debugging

### 2. `http-api.http`
Comprehensive test suite covering:
- All endpoints with various scenarios
- Authentication tests
- Validation tests
- Error handling
- Edge cases
- Performance tests
- Complete user workflows

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

### Update Base URL
Edit the `@baseUrl` variable at the top of the `.http` files:
```http
@baseUrl = http://localhost:8000
```

### For Authenticated Tests (http-api.http)

#### Option 1: Temporarily Disable Security
Comment out security checks in controllers:

**ReservationController.php** (around line 28-31):
```php
// if (!$user) {
//     return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
// }
```

**WarehouseController.php** (around line 16-17):
```php
// #[IsGranted('ROLE_ADMIN')]
```

#### Option 2: Use JWT Authentication
1. Install lexik/jwt-authentication-bundle
2. Configure JWT keys
3. Create a user
4. Generate token:
```bash
php bin/console lexik:jwt:generate-token
```
5. Update `@authToken` variable:
```http
@authToken = Bearer YOUR_GENERATED_TOKEN_HERE
```

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
