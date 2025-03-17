# Clerk Symfony Example - Project Summary

## Project Structure

This Symfony 7 project integrates with Clerk for authentication. Here's a breakdown of the key components:

### Security System

- **ClerkAuthenticator (`src/Security/ClerkAuthenticator.php`)**: A custom Symfony security authenticator that validates JWT tokens issued by Clerk.
- **Security Configuration (`config/packages/security.yaml`)**: Configures the security system to use our custom authenticator for the API routes.

### Controllers

- **ProtectedController (`src/Controller/ProtectedController.php`)**: Contains API endpoints, including a protected endpoint that requires authentication.

### Configuration

- **Environment Variables (`.env`)**: Contains Clerk configuration (secret key and authorized parties).
- **CORS Configuration (`config/packages/nelmio_cors.yaml`)**: Configures Cross-Origin Resource Sharing for API access from other domains.


## How Authentication Works

1. The client obtains a JWT token from Clerk's frontend SDK
2. The client includes this token in the `Authorization` header when making requests to the backend
3. The `ClerkAuthenticator` validates this token using the Clerk PHP SDK
4. If valid, the authenticated user identity is made available to controllers
5. Protected endpoints can access this user data or deny access if authentication fails

## How to Use the API

The API provides two main endpoints:

- **GET /api/clerk-jwt**: Returns the user ID if authenticated, or null if not
- **GET /api/get-gated**: Returns protected data (requires authentication)

## Integration with Clerk Frontend

A Clerk frontend application can integrate with this API by:

1. Using the Clerk frontend SDK to manage user authentication
2. Using the `getToken()` method from the session to obtain a JWT token
3. Including this token in API requests to gain access to protected resources

## Development

To run the application in development:

```bash
# Install dependencies
composer install

# Set environment variables
export CLERK_SECRET_KEY=your_clerk_secret_key

# Start the development server
symfony server:start
# OR
php -S localhost:8000 -t public/
``` 