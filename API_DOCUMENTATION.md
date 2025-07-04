# MyAppleCare API Documentation

This API provides endpoints for managing tickets, users, parts, and repairs in the MyAppleCare system.

## Getting Started

### Base URL
```
http://localhost:8000/api
```

### API Documentation
The interactive API documentation is available at:
```
http://localhost:8000/api/documentation
```

### Authentication
This API uses Bearer Token authentication. Include the token in the Authorization header:
```
Authorization: Bearer your_token_here
```

## Endpoints Overview

### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout  
- `GET /api/me` - Get current user info

### Tickets
- `GET /api/tickets` - Get all tickets
- `POST /api/tickets` - Create a new ticket
- `GET /api/tickets/{id}` - Get ticket by ID
- `PUT /api/tickets/{id}` - Update a ticket
- `DELETE /api/tickets/{id}` - Delete a ticket
- `GET /api/tickets/filter` - Filter tickets by status
- `GET /api/tickets/search` - Search tickets

### Key Features

#### Ticket Management
- Create support tickets with customer information
- Assign tickets to technicians using the `repaired_by` field
- Track ticket status (open, in_progress, completed)
- Set priority levels (low, medium, high)
- Support for different device categories (iPhone, Android, Other)

#### Ticket Assignment
When updating a ticket, you can assign it to a technician:
```json
{
  "repaired_by": 2,
  "status": "in_progress"
}
```

#### Filtering and Search
- Filter tickets by status: `/api/tickets/filter?status=open`
- Search tickets by device model, customer name, or ID: `/api/tickets/search?search=iPhone`

## Authentication Flow

1. Login with email and password to get a token
2. Include the token in subsequent requests
3. Use the token until logout or expiration

## Error Handling

The API returns consistent error responses:
```json
{
  "status": "error",
  "message": "Error description"
}
```

## Development

To regenerate the API documentation:
```bash
php artisan l5-swagger:generate
```

To start the development server:
```bash
php artisan serve
```
