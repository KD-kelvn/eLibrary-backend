# Contributing (backend)

The canonical guide is **[`design/CONTRIBUTING.md`](../design/CONTRIBUTING.md)** (branching in [`design/BRANCHING.md`](../design/BRANCHING.md), standards in [`design/ENGINEERING-STANDARDS.md`](../design/ENGINEERING-STANDARDS.md)). This file only adds backend-specific notes.

## Backend checks before a PR

```bash
vendor/bin/pint --test
php artisan test
```

## Backend-specific rules

- New code goes in a domain module under `domains/*`, not `app/`.
- Policies use `$user->hasActiveRole(...)` for create/update/delete; `return true` is allowed only for `viewAny`/`view` on public catalogue data.
- Validation rules referencing PostgreSQL schemas go through `SchemaTable::forValidation('schema.table')`.
- New migrations, seeders or env keys must be called out in the PR and added to `README.md`.
