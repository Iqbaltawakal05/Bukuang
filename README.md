# Bukuang — Personal Finance Management System (Backend REST API)

[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Build Status](https://img.shields.io/badge/Tests-62%20Passed-10B981?style=flat-square&logo=phpunit&logoColor=white)](tests)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

Bukuang Backend REST API is a scalable RESTful web service for personal financial management systems. Built with Laravel 11, Laravel Sanctum authentication, PostgreSQL precision numerical storage `DECIMAL(15, 2)`, and asynchronous background queue processing for data export and scheduled automated transactions.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [System Requirements](#system-requirements)
- [Installation & Setup](#installation--setup)
- [Automated Testing](#automated-testing)
- [API Endpoints Reference](#api-endpoints-reference)
- [License](#license)

---

## Overview

Bukuang provides a robust foundation for multi-user financial tracking, budgeting, target savings management, and reporting. The application strictly enforces data isolation per authenticated user, transaction decimal precision, and automated task processing via the Laravel Task Scheduler and Job Queues.

---

## Key Features

1. **Authentication & Profile Management (`/api/v1/auth`, `/api/v1/profile`)**
   - Secure registration, Sanctum Bearer token authentication, logout, profile updates, and password revision.

2. **Category Management (`/api/v1/categories`)**
   - Income and Expense categorization supporting default system categories and user-defined custom categories.

3. **Transaction Management (`/api/v1/transactions`)**
   - Full CRUD operations for income and expense transactions.
   - Comprehensive filtering by date range, transaction type, category, text search, sorting, and pagination.

4. **Budget Management (`/api/v1/budgets`)**
   - Monthly category budget allocations with dynamic calculations for spent amount, remaining balance, and threshold alerts (`NORMAL`, `WARNING`, `EXCEEDED`).
   - Database constraint enforcing single active budget per category per month.

5. **Financial Goals & Contributions (`/api/v1/financial-goals`)**
   - Target savings planning with progress percentage tracking.
   - Dedicated contribution endpoint (`POST /api/v1/financial-goals/{id}/contributions`) that accumulates deposits and automatically updates status to `completed` upon reaching target amount.

6. **Recurring Transactions & Scheduler (`/api/v1/recurring-transactions`)**
   - Scheduled automated transactions supporting daily, weekly, monthly, and yearly frequencies.
   - Artisan console command (`transactions:process-recurring`) for background processing.

7. **Dashboard Analytics & Charts (`/api/v1/dashboard`)**
   - Summary metrics: total balance, monthly income/expense/savings, budget usage, and recent transactions.
   - Visualization data endpoints: 6-month income vs. expense trend lines and category expense distribution.

8. **Reports Analytics (`/api/v1/reports`)**
   - Comprehensive reporting engine for daily, weekly, monthly, yearly, and custom date ranges with category percentage breakdowns.

9. **Export Engine & Background Queue (`/api/v1/exports`)**
   - Asynchronous report generation queue supporting PDF, CSV, and XLSX formats.
   - Job status monitoring and file download streaming.

---

## System Requirements

- **PHP**: `>= 8.3`
- **Composer**: `>= 2.0`
- **Database Engine**: PostgreSQL 16 (or MySQL / SQLite for development)
- **Required PHP Extensions**: `pdo`, `pdo_pgsql`, `mbstring`, `openssl`, `json`

---

## Installation & Setup

1. **Clone Repository**
   ```bash
   git clone https://github.com/Iqbaltawakal05/bukuang.git
   cd bukuang
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Update `.env` with your database configuration:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=bukuang
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

4. **Execute Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```

5. **Start Application Server**
   ```bash
   php artisan serve
   ```
   The API server will run at `http://127.0.0.1:8000`.

---

## Automated Testing

The codebase includes feature and integration test suites covering all API routes, authorization policies, calculation routines, and queue execution.

Run the test suite via Artisan:

```bash
php artisan test
```

**Test Execution Results**:
- **62 Test Cases**: 100% Passed
- **231 Assertions**

---

## API Endpoints Reference

All protected endpoints require a `Authorization: Bearer <token>` header.

| HTTP Method | Route | Description |
|---|---|---|
| `POST` | `/api/v1/auth/register` | Register new user account |
| `POST` | `/api/v1/auth/login` | Authenticate user and issue Sanctum token |
| `POST` | `/api/v1/auth/logout` | Revoke active Bearer token |
| `GET` | `/api/v1/profile` | Retrieve active user profile |
| `PUT` | `/api/v1/profile` | Update user profile information |
| `PUT` | `/api/v1/profile/password` | Update account password |
| `GET \| POST` | `/api/v1/categories` | List categories / Create custom category |
| `PUT \| DELETE`| `/api/v1/categories/{id}` | Update or soft-delete custom category |
| `GET \| POST` | `/api/v1/transactions` | List (with search/filter/sort) / Create transaction |
| `PUT \| DELETE`| `/api/v1/transactions/{id}` | Update or delete transaction |
| `GET \| POST` | `/api/v1/budgets` | List monthly budgets / Allocate category budget |
| `GET \| POST` | `/api/v1/financial-goals` | List / Create financial goal |
| `POST` | `/api/v1/financial-goals/{id}/contributions` | Record contribution towards financial goal |
| `GET \| POST` | `/api/v1/recurring-transactions` | List / Create recurring transaction schedule |
| `GET` | `/api/v1/dashboard/summary` | Fetch dashboard summary metrics |
| `GET` | `/api/v1/dashboard/charts` | Fetch 6-month trend and category pie chart data |
| `GET` | `/api/v1/reports/summary` | Fetch period report summary |
| `POST` | `/api/v1/exports` | Request background export job (PDF, CSV, XLSX) |
| `GET` | `/api/v1/exports/{id}/download` | Download generated export file |

---

## License

This software is open-sourced software licensed under the [MIT License](LICENSE).
