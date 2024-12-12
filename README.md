# Invoice Calculator System

## Overview
This system manages customer invoices by calculating charges based on different event types (registration, activation, and appointment) while considering previous charges to provide fair pricing.

## Core Features
- Creates and retrieves customer invoices
- Calculates charges based on event types
- Deducts previous charges from current charges
- Handles multiple users per customer
- Provides detailed invoice items

## Event Types and Pricing
The system handles three types of events with different base prices:
- Registration: 50 SAR
- Activation: 100 SAR
- Appointment: 200 SAR

## Business Rules
1. **Previous Charge Deduction**:
   - Each new charge is reduced by the user's previous highest charge
   - Example: If a user had a 50 SAR registration charge, their next activation charge would be 50 SAR (100 - 50)

2. **Event Priority**:
   - Appointment > Activation > Registration
   - The highest priority event in the period determines the charge

3. **Date Range**:
   - Events are only considered if they fall within the invoice period
   - Previous charges are from invoices before the current period

## Technical Implementation

### Key Components

1. **InvoiceCalculator Service** (`app/Services/InvoiceCalculator.php`)
   - Handles all charge calculations
   - Manages event prioritization
   - Tracks previous charges
   - Creates invoice items

2. **Invoice Controller** (`app/Http/Controllers/InvoiceController.php`)
   - Manages HTTP endpoints
   - Handles request validation
   - Returns formatted responses

### Database Structure

1. **Customers Table**
   - id
   - name
   - other customer details

2. **Users Table**
   - id
   - customer_id
   - name
   - email
   - password
   - created_at (used for registration event)

3. **Sessions Table**
   - id
   - user_id
   - activated_at
   - appointment_at

4. **Invoices Table**
   - id
   - customer_id
   - start_date
   - end_date
   - total_amount

5. **Invoice Items Table**
   - id
   - invoice_id
   - user_id
   - event_type
   - amount

### API Endpoints

1. **Create Invoice**
   ```http
   POST /api/invoices
   ```
   Payload:
   ```json
   {
       "customer_id": 1,
       "start": "2024-01-01",
       "end": "2024-01-31"
   }
   ```

2. **View Invoice**
   ```http
   GET /api/invoices/{id}
   ```

### Example Response
```json
{
    "data": {
        "id": 1,
        "total_amount": 250.00
    },
    "items": [
        {
            "user_id": 1,
            "event_type": "registration",
            "amount": "50.00"
        },
        {
            "user_id": 2,
            "event_type": "activation",
            "amount": "50.00"
        },
        {
            "user_id": 3,
            "event_type": "appointment",
            "amount": "150.00"
        }
    ]
}
```

## Testing

### Test Database Seeder
The `TestDatabaseSeeder` creates a test environment with:
- One customer
- Three users with different event combinations
- Previous invoice items for testing charge deductions

### Running Tests
To run the tests, use the following command:
```bash
php artisan test
```

This will execute the tests and provide detailed output for each test case.


### Key Test Cases
1. Registration only user (50 SAR)
2. Registration + Activation user (50 SAR after deduction)
3. Full event chain user (150 SAR after deduction)

## Development Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Set up the database:
   ```bash
   php artisan migrate
   ```
4. Run tests:
   ```bash
   php artisan test
   ```

## Environment Configuration
Ensure your `.env` and `.env.testing` files are properly configured with:
- Database connections
- Appropriate test database settings

## Error Handling
- Validates input data
- Handles missing customers
- Logs errors appropriately
- Returns meaningful error responses