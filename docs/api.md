# EcoManager – API Reference

## Authentication

| Method | URL                  | Auth Required | Description           | Request Body                       |
|--------|----------------------|---------------|-----------------------|------------------------------------|
| POST   | `/api/login.php`     | No            | User login            | `{ "email": "...", "password": "..." }` |
| POST   | `/api/register.php`  | No            | Create new account    | `{ "full_name": "...", "email": "...", "password": "..." }` |
| GET    | `/api/logout.php`    | Yes           | Logout                | —                                  |

## User Profile

| Method | URL                  | Auth Required | Description           | Request Body                       |
|--------|----------------------|---------------|-----------------------|------------------------------------|
| GET    | `/api/profile.php`   | Yes           | Get current profile   | —                                  |
| PUT    | `/api/profile.php`   | Yes           | Update profile        | JSON with any updatable fields     |

## Reports

| Method | URL                       | Auth Required | Description                      | Query / Body                                                  |
|--------|---------------------------|---------------|----------------------------------|---------------------------------------------------------------|
| GET    | `/api/reports.php`        | Yes           | List reports                     | Optional query: `?status=&category=&from=&to=`                |
| POST   | `/api/reports.php`        | Yes           | Create new report                | `{ "title": "...", "description": "...", "latitude": 47.1, "longitude": 27.6, "waste_category_id": 2 }` |
| GET    | `/api/export.php?fmt=`    | Yes           | Export reports in multiple formats | `?fmt=csv` \| `?fmt=pdf` \| `?fmt=json`                       |

## Waste Deposits

| Method | URL                       | Auth Required | Description                      | Request Body                                                  |
|--------|---------------------------|---------------|----------------------------------|---------------------------------------------------------------|
| POST   | `/api/deposits.php`       | Yes           | Record waste deposit             | `{ "location_id": 3, "waste_category_id": 1, "quantity_kg": 12.5 }` |
| GET    | `/api/deposits.php`       | Yes           | List deposits (with filters)     | Optional query: `?user_id=&location_id=&date_from=&date_to=`  |

## Collections

| Method | URL                          | Auth Required | Description                         | Request Body                                           |
|--------|------------------------------|---------------|-------------------------------------|--------------------------------------------------------|
| POST   | `/api/collections.php`       | Yes           | Record a collection from a report   | `{ "report_id": 5, "quantity_kg": 8.3 }`               |
| GET    | `/api/collections.php`       | Yes           | List all collections (filters avail.) | Optional query: `?staff_id=&date_from=&date_to=`       |

## Admin Endpoints (role = `admin`)

| Method | URL                            | Description                  | Request Body / Query |
|--------|--------------------------------|------------------------------|----------------------|
| GET    | `/api/users.php`               | List all users               | —                    |
| POST   | `/api/users.php`               | Create new user              | JSON with user fields |
| PUT    | `/api/users.php?id=`           | Update existing user         | JSON with updated fields |
| DELETE | `/api/users.php?id=`           | Delete a user                | —                    |
| (Similarly for) | `/api/categories.php` | Manage waste categories      | CRUD operations via GET/POST/PUT/DELETE |
| (Similarly for) | `/api/locations.php`  | Manage locations             | CRUD operations via GET/POST/PUT/DELETE |
