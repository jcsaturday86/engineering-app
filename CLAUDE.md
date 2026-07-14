# Engineering Permit Management System (EPMS)

## Tech Stack
- PHP 8.2+ / Laravel 12 / MariaDB 12.3
- Blade + Tailwind CSS (CDN) + Alpine.js (CDN) + Chart.js
- DomPDF for PDFs, Maatwebsite Excel for exports
- Spatie Permission for RBAC, Spatie Activitylog for audit

## Local Setup
- XAMPP on Windows 11
- MariaDB at `C:\Program Files\MariaDB 12.3\bin\`
- DB credentials: root / sfcity98
- Database: epms_db
- URL: http://localhost:8100 (artisan serve) or http://localhost/engineering-app/public

## Running
```bash
php artisan serve --port=8100
```

## Default Admin
- Email: admin@epms.local
- Password: password123 (must change on first login)

## Architecture
- Service Layer: app/Services/
- DTOs: app/DTOs/
- Actions: app/Actions/
- Enums: app/Enums/
- All business logic in Services, controllers are thin
- Soft deletes on all transaction tables
- Activity logging on Application, Assessment, Collection, Permit

## Workflow States (Building Permit)
draft → submitted → zoning_assessed → engineering_assessed → billed → paid → permit_generated → released

## Workflow States (Occupancy Permit)
draft → submitted → engineering_assessed → billed → paid → permit_generated → released

## Roles
super-admin, administrator, engineering-officer, engineering-staff, planning-officer, planning-staff, treasury-officer, treasury-staff, client

## Self-Healing
SelfHealingServiceProvider auto-creates database, runs migrations, seeds roles/settings/admin if missing on every boot.

## Print Forms
All 6 discipline permit PDFs (Architectural, Structural, Electrical, Sanitary, Mechanical, Electronics) plus the BP/OP unified application forms, Building Permit, and Occupancy Permit are complete — background-image-overlay DomPDF templates in `resources/views/pdf/`, routed via `ApplicationController::printDiscipline()`. Every one prints a "computer-generated document / printed on / printed by" footer on each page.

## Testing
```bash
php artisan test
```
