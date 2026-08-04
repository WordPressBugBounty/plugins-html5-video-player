# Contributing — invariants you must not break

`bp-extension-manager` is bundled inside multiple host plugins. Several copies (possibly at
different versions) can load in the same WordPress request. The rules below are what keep that
safe. Breaking one can fatal every host that ships the library.

## Coexistence invariants

1. **`bootstrap.php` records version + path only — it NEVER requires class files.**
   It is the one file that runs from *every* copy. If it loaded classes, two copies would
   redeclare them → fatal.

2. **`bpem_register_copy()` and `$GLOBALS['bpem_copies']` are frozen forever.**
   Their bytes must be byte-identical across every released version. The `function_exists()`
   guard means only the first-loaded copy defines them; all copies must agree on the contract.

3. **Never `define()` a library version constant.** Use the local `$bpem_this_version` in
   `bootstrap.php`. A version constant would be defined by every copy → "constant already
   defined" notice and ambiguous version. `BPEM_LOADED` / `BPEM_URL` / `BPEM_VERSION` are
   defined exactly once, by the winning copy in `includes/load.php` — that is safe.

4. **Everything mutable is keyed by the host `slug`.** Options (`bpem_{slug}_enabled`),
   transients (`bpem_{slug}_catalog`), REST namespace (`bpem/{slug}/v1`), AJAX action
   (`bpem_{slug}_license`), nonce (`bpem_{slug}_admin`), asset handle (`bpem-admin-{slug}`),
   mount node (`bpem-{slug}-extensions`). No global state. Host A toggling must never affect
   Host B.

5. **Max Plan / Freemius is read from the host's config, never a global.** No hardcoded
   `bp3d_fs()` or `'max'` anywhere in the library.

6. **An extension targets exactly one `slug`; new extensions are disabled by default.**
   `register()` rejects a mismatched `get_host_slug()`. An id absent from
   `bpem_{slug}_enabled` is disabled.

7. **Bump `$bpem_this_version` in `bootstrap.php` on every release** (and `BPEM_VERSION` in
   `includes/load.php` to match). Newest-wins only works if versions are accurate.

## Security invariants

- REST routes: `permission_callback` (capability) + core nonce via `X-WP-Nonce`.
- AJAX: `check_ajax_referer()` + `current_user_can()`.
- `ext_id` is always validated against registered/catalog ids — never a free-form lookup.
- Install download URL is resolved **server-side** and host-allowlisted — never client-supplied.
- Escape all admin output; sanitize license keys with `sanitize_text_field()`.

## Release checklist

- [ ] Bump `$bpem_this_version` (bootstrap.php) + `BPEM_VERSION` (includes/load.php).
- [ ] `npm run build`; commit `build/`.
- [ ] Run the test suite (unit + coexistence + JS).
- [ ] Confirm `bootstrap.php` still requires no class files and the frozen block is unchanged.
```
