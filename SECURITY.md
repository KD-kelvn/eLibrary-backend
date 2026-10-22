# Security

## Secrets

Never commit real credentials (`APP_KEY`, database passwords, API tokens, SMTP passwords) to git. Use `.env` / hosting secrets / GitHub Actions secrets only.

### Laravel `APP_KEY`

- Generate locally with `php artisan key:generate`.
- CI generates a **disposable** key at job time (`openssl rand`) and never stores it in the repo.
- Feature tests can also bind a temporary key in `tests/TestCase.php` when none is configured.

## Incident — GitGuardian #38835658 (22 Oct 2026)

**What happened:** A fixed test-only `APP_KEY` was briefly committed in `phpunit.xml` and `.github/workflows/ci.yml` on commit `b698b3a` while making CI green (PR #5).

**Impact:** That value was **never** a production key. It existed only as a CI/test fixture and was removed from all branch tips before merge. Current `develop` / `main` trees do not contain it.

**Remediation completed**

1. Removed hardcoded keys from the tree; CI uses a runtime-generated key.
2. Force-pushed the feature branch so no branch tip references `b698b3a`.
3. Squash-merged the clean tip into `develop`.

**Still recommended**

1. In GitGuardian, mark incident **#38835658** resolved (revoked / cleaned) after verifying the dashboard.
2. Optionally ask [GitHub Support](https://support.github.com/) to purge dangling commit `b698b3a1e204d8090b2147944019c759f64eeb07` from cache (it is unreachable from `main`/`develop` but may still open by SHA).
3. Rotate `APP_KEY` in every deployed environment (`php artisan key:generate` + redeploy) if there is any chance a shared or production key matched a committed value — standard hygiene after any key exposure alert.
