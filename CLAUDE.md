# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Testigo.cl** is a legal-tech crowdfunding platform that democratizes access to justice. Victims of abuse by large companies can submit their legal cases, specialized lawyers evaluate them, and investors fund promising cases in exchange for financial returns with positive social impact.

**Mission**: Enable victims who cannot afford legal representation to access justice while creating investment opportunities with social purpose.

### Technology Stack

- **Framework**: Laravel 12 (PHP 8.3)
- **Database**: PostgreSQL 15
- **Cache/Queue**: Redis 7
- **Web Server**: Nginx (via Docker)
- **Authentication**: Laravel Sanctum (token-based API)
- **Permissions**: Spatie Laravel Permission
- **API Documentation**: L5-Swagger (OpenAPI)

## System Actors

The platform serves four main actors, each with distinct roles:

1. **Víctima (Victim)**: Registers on the platform, submits legal cases with documentation, and receives updates on case progress
2. **Abogado (Lawyer)**: Evaluates submitted cases, assigns success probability, defines funding amounts and expected returns, approves or rejects cases
3. **Inversionista (Investor)**: Reviews published cases with risk/return details, funds cases through payment gateway, receives returns on successful cases
4. **Administrador (Admin)**: Supervises all processes, manages users and cases, monitors platform metrics and finances

## Business Model

### Revenue Streams
- Commission on funding (5-10%)
- Success fee on judicial returns

### Cost Structure
- Platform development and maintenance
- Marketing and user acquisition
- Legal costs and due diligence
- Payment gateway fees (2-4%)

### Key Metrics
- Number of published vs. funded cases
- Percentage of won lawsuits
- Average investor ROI
- CAC (Customer Acquisition Cost) vs. LTV (Lifetime Value)

## Main Use Cases

### Register Victim
**Actor**: Victim
1. Accesses the platform
2. Enters personal information
3. Receives validation email
4. Account is activated

### Submit Case
**Actor**: Victim
1. Enters case details (description, company, incident)
2. Attaches supporting documents
3. Case status set to "Pending Evaluation" (SUBMITTED)

### Evaluate Case
**Actor**: Lawyer
1. Reviews submitted case and documents
2. Assigns success probability
3. Defines funding amount, timeline, and expected return
4. Approves (APPROVED → PUBLISHED) or rejects case (REJECTED)

### Fund Case
**Actor**: Investor
1. Selects published case
2. Reviews return and risk details
3. Confirms investment via payment gateway
4. Case status changes to FUNDED when goal is reached

### Platform Control
**Actor**: Admin
1. Reviews dashboards and metrics
2. Validates processes
3. Manages users and cases
4. Monitors financial operations

## Information Flows

The platform orchestrates information between actors:

- **Victim → Platform**: Case registration + documents
- **Platform → Lawyer**: Notification of new case for evaluation
- **Lawyer → Platform**: Case status (REJECTED or PUBLISHED with funding details)
- **Platform → Investor**: Published case available for funding
- **Investor → Payment Gateway**: Financial contribution
- **Platform → Admin**: Real-time updates of statuses and metrics
- **Platform → All actors**: Email notifications on status changes

## Docker Commands

All services run in Docker containers. Use these commands from the repository root:

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f app

# Rebuild containers
docker-compose up -d --build

# Access app container shell
docker exec -it testigos_app bash

# Access database
docker exec -it testigos_db psql -U postgres -d testigos
```

## Laravel Commands (Inside Container)

Execute Laravel commands inside the `testigos_app` container:

```bash
# Run migrations
docker exec testigos_app php artisan migrate

# Run migrations with fresh database
docker exec testigos_app php artisan migrate:fresh --seed

# Clear cache
docker exec testigos_app php artisan cache:clear
docker exec testigos_app php artisan config:clear

# Run tests
docker exec testigos_app php artisan test

# Run specific test
docker exec testigos_app php artisan test --filter=TestName

# Run tinker (REPL)
docker exec -it testigos_app php artisan tinker

# Generate Swagger documentation
docker exec testigos_app php artisan l5-swagger:generate

# Install/update dependencies
docker exec testigos_app composer install
docker exec testigos_app composer update

# Code formatting
docker exec testigos_app ./vendor/bin/pint
```

## Architecture

### Action-Service Pattern

The codebase uses an **Action-Service pattern** to separate business logic from controllers:

- **Actions** (`app/Actions/`): Single-responsibility classes for atomic operations
  - Example: `CreateCaseAction`, `PublishCaseAction`, `EvaluateCaseAction`
  - Each action focuses on one specific task
  - Actions are composable and testable

- **Services** (`app/Services/`): Orchestrate multiple actions and handle cross-cutting concerns
  - Example: `CaseService`, `InvestmentService`, `DocumentService`
  - Services coordinate actions, notifications, and side effects
  - Services are injected into controllers

- **Controllers** (`app/Http/Controllers/Api/V1/`): Handle HTTP concerns only
  - Validate requests (via FormRequest classes)
  - Call services
  - Return API resources

### Key Architectural Layers

```
Controllers -> Services -> Actions -> Models -> Database
     |            |
     v            v
  Resources    Events -> Jobs
```

### Enums

The project uses PHP 8.2 enums extensively for type safety:

- `CaseStatus`: SUBMITTED, UNDER_REVIEW, APPROVED, PUBLISHED, FUNDED, IN_PROGRESS, COMPLETED, REJECTED
- `InvestmentStatus`: PENDING, CONFIRMED, ACTIVE, COMPLETED, CANCELLED
- `UserRole`: VICTIM, LAWYER, INVESTOR, ADMIN
- `DocumentType`: Various document types for cases

Enums include helper methods like `label()`, `color()`, and `canTransitionTo()` for state machine logic.

### Events & Jobs

- **Events** (`app/Events/`): Domain events that trigger side effects
  - `CaseCreated`, `CaseStatusChanged`, `InvestmentCreated`

- **Jobs** (`app/Jobs/`): Asynchronous background tasks
  - `SendCaseUpdateNotification`, `ProcessInvestmentReturn`

- **Commands** (`app/Console/Commands/`): Scheduled tasks
  - `UpdateCaseStatistics`, `ProcessInvestmentReturns`, `SendNotifications`

### Data Transfer Objects

DTOs (`app/Data/`) encapsulate request/response data:
- `CaseData`, `InvestmentData`, `UserData`

### API Resources

Resources (`app/Http/Resources/`) format API responses:
- `CaseResource`, `InvestmentResource`, `UserResource`
- Include relationships and computed attributes

## Domain Models

### User Roles

The system has 4 user roles (stored in `users.role` column and managed via Spatie permissions):

1. **Victim** (victim): Victims of corporate abuse who submit cases seeking legal representation
2. **Lawyer** (lawyer): Legal professionals who evaluate cases, assign success probabilities, and define funding parameters
3. **Investor** (investor): Individuals or entities who fund approved cases and receive returns on successful outcomes
4. **Admin** (admin): Platform administrators who supervise operations, validate processes, and manage users

### Core Entities

- **CaseModel** (`cases` table): Legal cases submitted by victims with funding goals and expected returns
  - Key fields: `title`, `description`, `company`, `category`, `funding_goal`, `current_funding`, `success_rate`, `expected_return`, `deadline`, `legal_analysis`, `evaluation_data`
  - Relationships: victim (User), lawyer (User), documents, investments, updates
  - Key methods: `isFullyFunded()`, `getFundingPercentageAttribute()`, `getRemainingFundingAttribute()`
  - Scopes: `published()`, `needsFunding()`, `status()`, `category()`

- **Investment**: Financial contributions from investors to fund legal cases
  - Key fields: `amount`, `expected_return_percentage`, `expected_return_amount`, `actual_return`, `status`, `payment_data`, `confirmed_at`, `completed_at`
  - Relationships: case (CaseModel), investor (User)
  - Tracks expected vs actual returns with ROI calculations
  - Status flow: PENDING → CONFIRMED → ACTIVE → COMPLETED (or CANCELLED)

- **User**: Platform users with role-based profiles
  - Relationships: victimCases, lawyerCases, investments
  - Has optional LawyerProfile or InvestorProfile

- **LawyerProfile**: Extended info for lawyers (license, specialties)
- **InvestorProfile**: Extended info for investors (accreditation, portfolio)
- **CaseDocument**: File attachments for cases
- **CaseUpdate**: Status updates and progress reports

### Case Status Flow

Cases follow a strict state machine (enforced by `CaseStatus::canTransitionTo()`):

```
SUBMITTED → UNDER_REVIEW → APPROVED → PUBLISHED → FUNDED → IN_PROGRESS → COMPLETED
    |              |
    v              v
REJECTED      REJECTED
(terminal)    (terminal)
```

**Status Transitions**:
1. **SUBMITTED**: Victim submits case with documents (initial state)
2. **UNDER_REVIEW**: Lawyer begins evaluation (lawyer-only action)
3. **APPROVED**: Lawyer approves case with success probability and funding details (lawyer-only)
4. **PUBLISHED**: Case is visible to investors with investment opportunity (lawyer/admin action)
5. **FUNDED**: Funding goal reached, case ready to proceed (automatic when `current_funding >= funding_goal`)
6. **IN_PROGRESS**: Legal proceedings have started (lawyer/admin action)
7. **COMPLETED**: Case resolved with outcome (lawyer/admin action)
8. **REJECTED**: Case rejected at any review stage (terminal state, lawyer/admin action)

**Actor Visibility**:
- Victims see their own cases (all statuses)
- Lawyers see SUBMITTED, UNDER_REVIEW cases (for evaluation) + their assigned cases
- Investors see only PUBLISHED cases that need funding
- Admins see all cases in all statuses

## API Documentation

Swagger documentation is maintained separately in `app/Swagger/`:

- **Schemas**: `app/Swagger/Schemas/` - OpenAPI schema definitions
- **Controllers**: `app/Swagger/Controllers/` - Endpoint documentation (not real controllers)
- **Access**: http://localhost:8080/api/documentation

After modifying Swagger docs, regenerate:
```bash
docker exec testigos_app php artisan l5-swagger:generate
```

## Permissions System

Uses Spatie Laravel Permission for granular access control:

- Permissions assigned to users or roles
- Key permissions: `view_cases`, `create_case`, `publish_case`, `create_investment`, `manage_users`, `verify_lawyers`
- Check permissions: `$user->can('permission_name')` or `@can('permission')` in Blade
- Middleware: `role:admin` or `permission:publish_case`

## Testing

- Tests use SQLite in-memory database (see `phpunit.xml`)
- Run full suite: `docker exec testigos_app php artisan test`
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`

## Database

PostgreSQL database exposed on host port `5434`:
```bash
psql -h localhost -p 5434 -U postgres -d testigos
```

Migrations in `database/migrations/` follow naming convention: `YYYY_MM_DD_HHMMSS_description.php`

## Services URLs

- **API**: http://localhost:8080
- **Swagger UI**: http://localhost:8080/api/documentation
- **PostgreSQL**: localhost:5434
- **Redis**: localhost:6381
