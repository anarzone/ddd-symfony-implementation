# HTTP Test Files Consolidation Summary

## Changes Made

### Files Deleted
- ❌ **http-api.http** - Removed (content distributed to domain-specific files)

### Files Created
- ✅ **stock-reservation.http** - New file for stock reservation & inventory tests

### Files Kept (Unchanged)
- ✅ **auth-tokens.http** - Authentication & token management
- ✅ **user-management.http** - User CRUD operations
- ✅ **warehouse-management.http** - Warehouse CRUD operations
- ✅ **quick-test.http** - Quick development tests

## New Organization

### By Domain (DDD-Aligned)

**Account Domain:**
- `auth-tokens.http` - Login, authentication, API token generation/management
- `user-management.http` - User CRUD, user workflows, authorization

**Inventory Domain:**
- `warehouse-management.http` - Warehouse CRUD, activate/deactivate
- `stock-reservation.http` - Stock reservations, inventory queries, concurrency

**Development:**
- `quick-test.http` - Quick tests without authentication

### Test Distribution from http-api.http

**Moved to warehouse-management.http:**
- Warehouse creation tests
- Warehouse listing
- Warehouse activation/deactivation
- Warehouse validation errors

**Moved to stock-reservation.http:**
- Stock reservation tests
- Stock level queries
- Reservation validation errors
- Concurrency tests
- Business logic tests

**Moved to auth-tokens.http:**
- Login authentication tests
- Token management tests

**Duplicate/Removed:**
- Authentication tests (already in auth-tokens.http)
- User workflows (already in user-management.http)

## Benefits of New Organization

1. **Domain-Driven**: Tests organized by bounded context (Account, Inventory)
2. **Easier Navigation**: Find tests faster by domain
3. **Better Maintainability**: Update tests in relevant domain file
4. **Clear Purpose**: Each file has a single, well-defined responsibility
5. **Reduced Duplication**: Eliminated duplicate tests across files

## File Usage Quick Reference

| I want to test... | Use this file |
|------------------|---------------|
| Login / Get API token | `auth-tokens.http` |
| Create/update users | `user-management.http` |
| Manage warehouses | `warehouse-management.http` |
| Reserve stock / Check inventory | `stock-reservation.http` |
| Quick testing (no auth) | `quick-test.http` |

## Environment Configuration

All files use `http-client.env.json`:
```json
{
    "dev": {
        "baseUrl": "https://fastreserve.test",
        "authToken": "your-token-here"
    }
}
```

## Running Tests

1. **Setup:**
   ```bash
   php bin/console doctrine:fixtures:load
   symfony server:start
   ```

2. **Get Token:**
   - Run login test in `auth-tokens.http`
   - Copy token to `http-client.env.json`

3. **Run Tests:**
   - Open relevant `.http` file for your domain
   - Click "Send Request" above each test

## Migration Notes

### If you were using http-api.http:

**For warehouse tests:** → Use `warehouse-management.http`
**For stock tests:** → Use `stock-reservation.http`
**For auth tests:** → Use `auth-tokens.http`
**For user tests:** → Use `user-management.http`

All test scenarios from `http-api.http` have been preserved in their respective domain files.
