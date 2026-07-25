# Hospital Management System (HMS)

A REST API implementation of the Hospital Management System described in the
accompanying Software Engineering lab manual (Sara Shabir, 4th Semester,
New Aligarh Degree College Manga, affiliated with University of Punjab Lahore).

It implements the full role-based clinical data flow documented in the manual's
use-case, activity, sequence, and class diagrams:

```
Login (Universal) -> Receptionist (Creates Profile) -> Nurse (Appends Vitals)
  -> Doctor (Views Record & Orders Services) -> Lab Tech (Uploads Reports)
  -> Pharmacist (Dispenses Stock) -> Accountant (Collects Consolidated Bill)
  -> Logout (Secures Station)
```

## Tech stack

- Node.js / Express
- MongoDB with Mongoose
- JWT authentication with Role-Based Access Control (RBAC)
- bcrypt password hashing

## Features implemented (mapped to lab manual sections)

| # | Manual Feature | Module |
|---|---|---|
| 1 | Generic Authentication Module (Login/Logout, idle auto-logout after 10 min) | `authController`, `authMiddleware` |
| 2 | Receptionist (Front Desk) | `receptionistController` |
| 3 | Nurse (Triage & Ward Management) | `nurseController` |
| 4 | Doctor (Clinical Decision Maker) | `doctorController` |
| 5 | Lab Technician / Radiologist | `labController` |
| 6 | Pharmacist (Dispensing & Stock Control) | `pharmacistController` |
| 7 | Billing Accountant (Finance & Checkout) | `billingController` |
| 8 | Patient Portal (read-only) | `patientPortalController` |
| 9 | System Administrator (IT & Security) | `adminController` |

## Project structure

```
hms/
├── src/
│   ├── config/db.js              MongoDB connection
│   ├── models/                   Mongoose schemas (User, Patient, Encounter, ...)
│   ├── controllers/              Business logic per role
│   ├── routes/                   Express routers with RBAC per role
│   ├── middleware/                JWT auth, RBAC, error handling
│   ├── utils/                    JWT signing, audit logging, DB seed
│   ├── app.js                    Express app assembly
│   └── server.js                 Entry point
├── .env.example
├── package.json
└── README.md
```

## Setup

```bash
git clone <this-repo-url>
cd hms
npm install
cp .env.example .env   # then edit MONGO_URI / JWT_SECRET as needed
npm run seed            # creates one demo user per role + sample pharmacy stock
npm run dev              # starts the API on http://localhost:5000
```

Requires a running MongoDB instance (local or Atlas) reachable at `MONGO_URI`.

### Demo accounts (created by `npm run seed`)

All demo accounts use the password `password123`.

| Role | Email |
|---|---|
| Admin | admin@hms.test |
| Receptionist | receptionist@hms.test |
| Nurse | nurse@hms.test |
| Doctor | doctor@hms.test |
| Lab Technician | labtech@hms.test |
| Pharmacist | pharmacist@hms.test |
| Billing Accountant | billing@hms.test |
| Patient | patient@hms.test |

## Authentication

`POST /api/auth/login` with `{ email, password }` returns a JWT and the user's
role. Send the token on subsequent requests as `Authorization: Bearer <token>`.

Every authenticated request refreshes `lastActivityAt`. If more than
`IDLE_TIMEOUT_MINUTES` (default 10) pass with no requests, the next request is
rejected with `401` and an `AUTO_LOGOUT_IDLE` event is written to the system
log — implementing the manual's "Idle Auto-Logout (Security Guard)" feature.

## API walkthrough (matches the manual's data-flow diagram)

1. `POST /api/auth/login` — any role logs in and gets a token.
2. `POST /api/receptionist/patients` — register a new patient (or `GET /api/receptionist/patients/search?q=` for returning patients).
3. `POST /api/receptionist/checkin` — check in, generates a token number and queues the patient for the Nurse.
4. `GET /api/nurse/queue` then `PUT /api/nurse/encounters/:id/vitals` — Nurse records vitals; high-risk vitals are auto-flagged and the patient moves to the Doctor's queue.
5. `GET /api/doctor/queue`, `GET /api/doctor/encounters/:id`, `POST /api/doctor/encounters/:id/notes`, `POST /api/doctor/encounters/:id/lab-orders`, `POST /api/doctor/encounters/:id/prescriptions`, `PUT /api/doctor/encounters/:id/close`.
6. `GET /api/lab/worklist`, `PUT /api/lab/orders/:id/collect`, `PUT /api/lab/orders/:id/results`, `PUT /api/lab/orders/:id/publish` — publishing returns the encounter to the Doctor's queue.
7. `GET /api/pharmacist/prescriptions`, `PUT /api/pharmacist/prescriptions/:id/dispense` — deducts stock and forwards cost to Billing.
8. `GET /api/billing/encounters/:id/invoice-preview`, `POST /api/billing/encounters/:id/invoice` — compiles all role charges, applies discount/insurance, processes payment, discharges the patient.
9. Patient can review everything read-only under `/api/patient-portal/*` and self-book appointments.
10. `GET /api/admin/logs` — System Administrator monitors login/logout/idle-logout/action audit trail; `/api/admin/users` manages accounts and RBAC role assignment.

## Notes

- This backend intentionally has no bundled frontend; it is designed to be
  consumed by any client (web, mobile, or the Swagger/Postman collection of
  your choice) or extended with one.
- The class/sequence/activity/use-case diagrams referenced by each feature
  live in the original lab manual document; this codebase is the corresponding
  working implementation of those designs.

## PHP / MySQL frontend

A separate, standalone frontend that talks directly to MySQL (independent of this Node/Express API) lives in `php-app/`. It implements the same 9 features with plain PHP + PDO + sessions. See `php-app/README.md` for setup instructions.
