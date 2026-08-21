# CI4 e-Ujian — 1K Concurrent Application Audit

## Target

Application target: approximately 1,000 concurrent users during exam sessions.
This document records application-level changes for the `perf/1k-concurrent` branch.

## Current findings from `main`

### 1. Cache is file-based
`app/Config/Cache.php` uses `file` as the primary handler and `dummy` as backup.

Impact: cache traffic creates filesystem I/O and does not provide shared cache semantics for multiple application nodes.

Planned change: Redis primary cache, with file fallback only where operationally necessary.

### 2. Session is file-based
`app/Config/Session.php` uses `FileHandler` and `WRITEPATH . 'session'`.

Impact: concurrent users create/read session files and this prevents clean horizontal scaling.

Planned change: Redis session handler. Keep session payload minimal.

### 3. CodeIgniter optimization caches are disabled
`app/Config/Optimize.php` has both `configCacheEnabled` and `locatorCacheEnabled` disabled.

Planned change: enable both for production deployment, unless the deployment process intentionally manages cache generation elsewhere.

### 4. Database debug is enabled in configuration
`app/Config/Database.php` has `DBDebug => true`.

Planned change: make production configuration use `DBDebug => false` through environment-aware configuration, while preserving debugging in development/testing.

### 5. Exam page renders the complete exam in one server-rendered response
`app/Views/siswa/obe/kerjakan_ujian.php` renders every question and answer textarea in a single HTML form.

Impact: larger HTML responses and higher PHP memory/CPU per exam start. More importantly, it makes the client submit the entire answer set when finishing.

Planned change: split exam delivery from answer persistence; load only the necessary question payload and save answers incrementally in a controlled API.

### 6. Exam submission posts the whole form
The current exam view builds a `FormData` from `formUjian` and sends it to `siswa/ujian/selesai/{id}` when finishing.

Impact: a 1,000-user synchronized submit can create a large request burst and a large transactional database workload.

Planned change: idempotent single-answer persistence with a final lightweight submit operation.

### 7. Timer is client-side and triggers a full final submission
The view uses a one-second JavaScript interval and calls the final submission path when time expires.

Planned change: keep the UI countdown client-side, but make the server authoritative for the exam deadline and submission state. Avoid repeated server polling for timer updates.

### 8. Login performs multiple role-specific queries
`app/Controllers/Auth.php` performs the user lookup and then additional queries for `pegawai`, `pleton`, `kompi`, `batalyon`, or `siswa`, followed by another full-name query.

Impact: a synchronized login event can cause unnecessary query multiplication.

Planned change: consolidate login profile lookup where practical and avoid duplicate queries, without changing authentication behavior.

### 9. Global PageCache/PerformanceMetrics/DebugToolbar configuration needs production review
`app/Config/Filters.php` currently registers `pagecache`, `performance`, and `toolbar` as required filters.

Planned change: explicitly disable Debug Toolbar and development-only performance instrumentation in production. PageCache must not cache authenticated or exam-stateful responses.

### 10. OBE controller contains potentially expensive unbounded queries
`app/Controllers/ObeController.php` includes endpoints using `findAll()`/full-table queries and correlated subqueries. Examples include student data retrieval and class-exam listing with COUNT/GROUP_CONCAT/FIND_IN_SET.

Impact: administrative endpoints can consume substantial DB resources if large datasets are returned without pagination/filtering. These are not necessarily on the student exam hot path, but should be bounded.

Planned change: pagination, explicit column selection, proper joins/indexes, and cache for read-mostly reference data.

### 11. `kelas_ujian.deskripsi` stores pleton IDs as text
The controller serializes multiple pleton IDs into a string such as `Pleton ID: 1,2,3` and later reconstructs the relationship with `FIND_IN_SET()`.

Impact: this prevents efficient relational indexing and can make queries increasingly expensive.

Planned change: use the existing `kelas_ujian_peserta` relation for participant membership and avoid using `FIND_IN_SET()` for read-path filtering. A future normalized pivot for class-to-pleton assignment can be considered separately.

### 12. Static exam assets should be kept outside the PHP request path
The application should serve CSS/JS/images directly from OpenLiteSpeed/CDN and not route static assets through CodeIgniter.

## Priority

### P0 — required before claiming 1K readiness
- Redis session
- Redis cache
- Production optimization/cache settings
- Production database debug behavior
- Audit and redesign exam answer persistence
- Server-authoritative exam deadline/submission state
- Remove development-only request instrumentation from production

### P1 — strongly recommended
- Paginate all large administrative API responses
- Remove unbounded `findAll()` from frequently accessed endpoints
- Consolidate login/profile queries
- Add/verify database indexes from actual query plans
- Replace `FIND_IN_SET()` relationship usage on hot paths
- Explicit API field selection

### P2 — load-test driven
- Fine-tune cache TTLs
- Optimize response payload sizes
- Tune answer-save batching/debouncing on the frontend
- Add rate limiting
- Add application performance metrics

## Important constraint

Do not change `main` directly. All application changes belong to `perf/1k-concurrent` and should be reviewed and load-tested before merging.
