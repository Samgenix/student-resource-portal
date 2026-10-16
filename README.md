# Student Resource Portal

A PHP/MySQL web app for students to upload, organize, search, and manage shared academic resources, with role-based admin tools. Built with Docker for local development.

## Features
- Registration and login with hashed passwords, session-based auth, and account suspension support
- Resource uploads with file-type allowlisting, MIME-type verification (not just the filename), size limits, and safe generated filenames
- Resource browsing with search across title, description, filename, category, and uploader
- "Manage uploads" page: edit, replace, or delete your own resources, with ownership enforced on every query (not just hidden in the UI)
- Admin dashboard: suspend/activate/delete users, promote/demote admins, CSV export, basic stats
- Shared stylesheet for a consistent layout across pages

## Prereqs
- Docker Desktop installed and running

## Run
```bash
docker compose up -d --build
```
- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081 (Server: `db`, user: `root`, pass: `rootpass`)

`schema.sql` is mounted into MySQL's init directory and runs automatically the first time the `db` data volume is created — no manual import needed. If you ever change the schema and need it to re-run, reset the volume with `docker compose down -v` and start again.

## First use
1. Register an account at `/register.php`
2. Log in and upload a resource
3. To get an admin account: register normally, then set that user's `role` column to `admin` directly in the `users` table via phpMyAdmin — there's no self-serve admin signup

## Stop
```bash
docker compose down
```

## Notes
- PHP connects to MySQL using host `db` (the Compose service name)
- Default non-root DB user: `sp_user` / `sp_pass`
- Uploaded files live outside any PHP-executable path in the app; `www/uploads/.htaccess` also blocks PHP execution there as defense in depth

## Status
Core functionality is in place: auth, uploads, browsing/search, resource management, and admin tools all work end-to-end. UI polish, a full security audit pass, automated testing, and deployment prep are still in progress.
