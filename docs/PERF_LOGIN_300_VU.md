# Login Concurrency Performance — 300 VU

## Context

The concurrent-login investigation identified PHP-FPM saturation as the leading hypothesis, with application processing latency and burst concurrency as additional factors. The handover also explicitly requires correlating K6 latency/results with PHP-FPM workers, CPU, TCP states, and container health before declaring a root cause.

Current `main` already contains the earlier student authentication hot-path optimization. `Auth::auth()` now measures validation, user lookup, `password_verify()`, profile lookup, unit lookup, session write, and total authentication time.

## Change in this commit

`app/Config/Filters.php` no longer runs `PageCache` and `PerformanceMetrics` as global required filters. Authentication is stateful and should not be page-cached. `PerformanceMetrics` remains available for targeted `benchmark/*` profiling instead of adding work to every request.

This change is intended to reduce measurement noise and remove unnecessary request-path work from the login benchmark. It does **not** prove PHP-FPM is the root cause.

## Benchmark procedure

1. Pull the latest `main`.
2. Restart PHP-FPM/application containers so configuration is loaded.
3. Clear only stale test session files before a controlled run.
4. Run serial baseline: 20 GET `/login` requests.
5. Run staged concurrency: 20, 50, 100, 150, 200, 250, 300 VU.
6. Compare 300 VU instant burst against 300 VU ramp-up over 30–60 seconds.
7. During each run record:
   - K6 success rate and `login_duration` p95/p99
   - CSRF success/failure
   - POST `/auth` count and auth success
   - PHP-FPM worker count and CPU
   - MySQL CPU
   - TCP states (`LISTEN`, `ESTABLISHED`, `TIME_WAIT`)
   - Nginx/PHP container restart count
8. Inspect `AUTH_TIMING` application logs for the first material latency increase and the dominant sub-stage.

## Interpretation

Treat PHP-FPM saturation as confirmed only when worker usage reaches the configured ceiling at the same time as PHP CPU pressure and request latency increase.

Do not infer a bottleneck from the session-file count alone. Do not increase `pm.max_children` or switch session/cache storage to Redis until the controlled benchmark establishes that the current resource is actually limiting throughput.

## Acceptance target for the next 300 VU run

- 100% auth success
- 0 CSRF failure
- 0 connection refused
- 0 unexpected HTTP failure
- No container restart
- No memory exhaustion
- No sustained worker starvation
- Acceptable p95/p99 latency

Once 300 VU is stable, repeat the same methodology toward the approximately 1,000 concurrent-user target.
