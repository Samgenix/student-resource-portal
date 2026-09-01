# Student Resource Portal -Docker Starter

## Prereqs
- Docker Desktop installed and running

## Run
```bash
docker compose up -d
```
- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081 (Server: `db`, user: `root`, pass: `rootpass`)

## Initialize Database
1) Open phpMyAdmin → Server: `db`
2) Create DB `student_portal` if not present
3) Open the SQL tab and paste contents of `schema.sql` (or import the file)
4) Create your first user via `register.php`

## Stop
```bash
docker compose down
```

## Notes
- PHP connects to MySQL using host `db` (the Compose service name).
- Default non-root DB user: `sp_user` / `sp_pass`.
