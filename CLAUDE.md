


# Project: oauth2-geocaching

PHP library (`surfoo/oauth2-geocaching`, PHP >=8.2): Geocaching OAuth 2.0 provider for
`league/oauth2-client`, plus a token-refresh plugin for `php-http/client-common`.
Autoloaded as `League\OAuth2\Client\` → `src/`, tests as `League\OAuth2\Client\Test\` → `test/src/`.

## Layout
- `src/Provider/Geocaching.php` — the OAuth2 provider (extends `AbstractProvider`).
- `src/Provider/GeocachingConfig.php` — per-environment URL config (dev/staging/production + aliases).
- `src/Provider/GeocachingResourceOwner.php` — resource owner (user) accessor.
- `src/Plugin/TokenRefreshPlugin.php` — intercepts 401s, refreshes via the provider, retries
  with `$next` (not `$first`) to avoid infinite refresh loops; has lock-based concurrency
  protection via `TokenStorageInterface`.
- `src/Token/TokenSet.php` — immutable access+refresh token value object.
- `src/Token/TokenStorageInterface.php` — consumer-implemented storage contract (get/save/lock).
- `src/Exception/` — `TokenRefreshException`, `RefreshTokenExpiredException` (extends it),
  `TokenStorageException`.
- `demo/index.php` — runnable example wiring the pieces together.
- `README.md` / `UPGRADE-3.0.md` — documented public API surface; check before removing anything
  that looks unused (e.g. `GeocachingConfig::create()`, `TokenStorageInterface::isUserLocked()`
  are documented and must stay even though library code doesn't call them internally).

## Commands
- `composer phpstan` — static analysis (`phpstan.neon.dist`).
- `composer phpcs` / `composer phpcbf` — PSR2 style check / autofix.
- `composer rector` — automated refactors (`rector.php`).
- `composer phpunit` — run tests with coverage (`XDEBUG_MODE=coverage vendor/bin/phpunit`).
  Plain `vendor/bin/phpunit` also works without coverage.

## Conventions / gotchas
- `TokenRefreshPlugin` logs with a `[GEOCACHING]` prefix; keep that when adding log calls.
- Library, not an app — before deleting an apparently-unused public method/constant, grep
  `README.md` and `UPGRADE-3.0.md` first; it may be documented consumer-facing API even with
  no internal caller.
- Git branch: `develop` is the main/PR-target branch (not `main`).

<!-- rtk-instructions v2 -->
# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. If RTK has a dedicated filter, it uses it. If not, it passes through unchanged. This means RTK is always safe to use.

**Important**: Even in command chains with `&&`, use `rtk`:
```bash
# ❌ Wrong
git add . && git commit -m "msg" && git push

# ✅ Correct
rtk git add . && rtk git commit -m "msg" && rtk git push
```

## RTK Commands by Workflow

### Build & Compile (80-90% savings)
```bash
rtk cargo build         # Cargo build output
rtk cargo check         # Cargo check output
rtk cargo clippy        # Clippy warnings grouped by file (80%)
rtk tsc                 # TypeScript errors grouped by file/code (83%)
rtk lint                # ESLint/Biome violations grouped (84%)
rtk prettier --check    # Files needing format only (70%)
rtk next build          # Next.js build with route metrics (87%)
```

### Test (60-99% savings)
```bash
rtk cargo test          # Cargo test failures only (90%)
rtk go test             # Go test failures only (90%)
rtk jest                # Jest failures only (99.5%)
rtk vitest              # Vitest failures only (99.5%)
rtk playwright test     # Playwright failures only (94%)
rtk pytest              # Python test failures only (90%)
rtk rake test           # Ruby test failures only (90%)
rtk rspec               # RSpec test failures only (60%)
rtk test <cmd>          # Generic test wrapper - failures only
```

### Git (59-80% savings)
```bash
rtk git status          # Compact status
rtk git log             # Compact log (works with all git flags)
rtk git diff            # Compact diff (80%)
rtk git show            # Compact show (80%)
rtk git add             # Ultra-compact confirmations (59%)
rtk git commit          # Ultra-compact confirmations (59%)
rtk git push            # Ultra-compact confirmations
rtk git pull            # Ultra-compact confirmations
rtk git branch          # Compact branch list
rtk git fetch           # Compact fetch
rtk git stash           # Compact stash
rtk git worktree        # Compact worktree
```

Note: Git passthrough works for ALL subcommands, even those not explicitly listed.

### GitHub (26-87% savings)
```bash
rtk gh pr view <num>    # Compact PR view (87%)
rtk gh pr checks        # Compact PR checks (79%)
rtk gh run list         # Compact workflow runs (82%)
rtk gh issue list       # Compact issue list (80%)
rtk gh api              # Compact API responses (26%)
```

### JavaScript/TypeScript Tooling (70-90% savings)
```bash
rtk pnpm list           # Compact dependency tree (70%)
rtk pnpm outdated       # Compact outdated packages (80%)
rtk pnpm install        # Compact install output (90%)
rtk npm run <script>    # Compact npm script output
rtk npx <cmd>           # Compact npx command output
rtk prisma              # Prisma without ASCII art (88%)
```

### Files & Search (60-75% savings)
```bash
rtk ls <path>           # Tree format, compact (65%)
rtk read <file>         # Code reading with filtering (60%)
rtk grep <pattern>      # Search grouped by file (75%). Format flags (-c, -l, -L, -o, -Z) run raw.
rtk find <pattern>      # Find grouped by directory (70%)
```

### Analysis & Debug (70-90% savings)
```bash
rtk err <cmd>           # Filter errors only from any command
rtk log <file>          # Deduplicated logs with counts
rtk json <file>         # JSON structure without values
rtk deps                # Dependency overview
rtk env                 # Environment variables compact
rtk summary <cmd>       # Smart summary of command output
rtk diff                # Ultra-compact diffs
```

### Infrastructure (85% savings)
```bash
rtk docker ps           # Compact container list
rtk docker images       # Compact image list
rtk docker logs <c>     # Deduplicated logs
rtk kubectl get         # Compact resource list
rtk kubectl logs        # Deduplicated pod logs
```

### Network (65-70% savings)
```bash
rtk curl <url>          # Compact HTTP responses (70%)
rtk wget <url>          # Compact download output (65%)
```

### Meta Commands
```bash
rtk gain                # View token savings statistics
rtk gain --history      # View command history with savings
rtk discover            # Analyze Claude Code sessions for missed RTK usage
rtk proxy <cmd>         # Run command without filtering (for debugging)
rtk init                # Add RTK instructions to CLAUDE.md
rtk init --global       # Add RTK to ~/.claude/CLAUDE.md
```

## Token Savings Overview

| Category | Commands | Typical Savings |
|----------|----------|-----------------|
| Tests | vitest, playwright, cargo test | 90-99% |
| Build | next, tsc, lint, prettier | 70-87% |
| Git | status, log, diff, add, commit | 59-80% |
| GitHub | gh pr, gh run, gh issue | 26-87% |
| Package Managers | pnpm, npm, npx | 70-90% |
| Files | ls, read, grep, find | 60-75% |
| Infrastructure | docker, kubectl | 85% |
| Network | curl, wget | 65-70% |

Overall average: **60-90% token reduction** on common development operations.
<!-- /rtk-instructions -->
