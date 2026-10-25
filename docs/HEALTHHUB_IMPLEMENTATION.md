# WPU HealthHub — Implementation Plan & Status

**Brand:** WPU HealthHub — *Your unified gateway to campus wellness.*

**Reference:** Comments on HSU EMR.docx (not present in repository — templates and official wording pending).

## Architecture (existing)

| Layer | Location | Role |
|-------|----------|------|
| Laravel shell | `app/`, `routes/web.php`, `resources/views/` | Unified login, patient/physician portals, admin calendar |
| Unified HIS workspace | `unified_portal/admin/admin.php` + `admin/pages/*` | Medical/dental records, certificates, referrals, reports |
| Database | `wpu_unified` (MySQL) | Shared schema for Laravel + legacy PHP |

## Module plan

| Phase | Module | Status |
|-------|--------|--------|
| 1 | System review & plan | In progress (this document) |
| 2 | Branding (`config/healthhub.php`, UI strings) | Partial — central config + main layouts |
| 3 | RBAC (physician, dentist, nurse, staff) | Partial — `HealthHubRbacService`, `wpu_healthhub.php`, record filtering |
| 4 | Patient classification (Dependent, Private Individual, conditional fields) | Partial — DB seed/migration; UI conditional fields pending |
| 5 | Secure consent + digital signature | **Integrated** — official WPU-QSF-OSAS-HSO-23 Medical Consent Form, multi-step signatures, print/PDF, audit log |
| 6 | Medical records (transaction, SODAP, case type) | Partial — schema; form reorder pending |
| 7 | Nurse's notes | Scaffold — tables; UI pending |
| 8 | Examination templates (clearance/LOA/RTS, APE, pre-enrollment) | Pending official templates |
| 9 | Medical certificates (purpose, numbering) | Existing module; purpose/number rules pending |
| 10 | Interagency & external referrals | Scaffold — tables; split workflows pending |
| 11 | Dispensary | Scaffold — tables; module UI pending |
| 12 | Dental records (form order, case type) | Partial — labels; form rework pending |
| 13 | Doctors Appointment | Partial — rename in Laravel calendar |
| 14 | Consent & refusal (standalone) | Scaffold |
| 15 | Reports & analytics | Existing reports; new metrics pending |
| 16 | Migrations | `2026_10_24_000001_healthhub_foundation.php` |
| 17 | Security | Existing `wpu_security.php`; RBAC enforcement expanding |
| 18–19 | UI polish & testing | Ongoing |

## Deployment

1. Back up the unified database.
2. From project root: `php artisan migrate`
3. Or run `unified_portal/database/migrations/2026_10_24_healthhub_foundation.sql` on hosts without Artisan (MySQL 8+ `IF NOT EXISTS` syntax may vary).
4. Set `.env`: `APP_NAME="WPU HealthHub"`, optional `HEALTHHUB_AUTHORIZED_EMAILS=hso@wpu.edu.ph`
5. Assign `admins.role` values: `physician`, `dentist`, `nurse`, `staff`, `admin`, `super_admin`.

## Outstanding (requires stakeholder input)

- Official Word/Excel templates for **refusal**, referral, APE, and pre-enrollment (medical consent template integrated from `docs/CONSENT.docx`)
- Full college and unit lists (partial data in `departments` / `organizational_units`)
- MC numbering rules for APE / Pre-employment vs other purposes
- Whether all staff admin accounts must use `@wpu.edu.ph` email login
