# Contributing (backend)

Umbrella docs (preferred):

- Branching: `design/BRANCHING.md` — `main` / `develop` / `feature/*` / `hotfix/*` / `chore/*`
- Standards: `design/ENGINEERING-STANDARDS.md`
- Process: `design/CONTRIBUTING.md`

## Branch cheat sheet

```bash
git fetch origin
git checkout develop && git pull
git checkout -b feature/t-01-loan-rules
# … work …
git push -u origin HEAD
# Open PR → develop
```

Hotfix:

```bash
git checkout main && git pull
git checkout -b hotfix/critical-auth
# PR → main, then merge/PR back into develop
```

## Quick checks

```bash
composer install
vendor/bin/pint --test
php artisan test
```

## Architecture

Domain modules under `domains/*`. Request lifecycle:

`Route → Controller → FormRequest → Service → Repository → Resource`

Policies must use `$user->hasActiveRole(...)` for mutations. Do not add `return true` mutate abilities.
