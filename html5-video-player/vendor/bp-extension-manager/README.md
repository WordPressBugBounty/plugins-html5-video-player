# bp-extension-manager (`bpem`)

A vendored WordPress library that discovers, gates, licenses, and administers add-on
extensions for **any** host plugin — and is safe to bundle in **multiple** host plugins
at the same time, even at different versions.

- **Namespace:** `BPEM\`
- **Prefix:** `bpem`
- **Autoload root:** `includes/`
- **Min PHP / WP:** 7.4 / 6.0
- **New extensions:** **disabled by default** (an admin enables each one)

---

## Download

[![Download bp-extension-manager.zip](https://img.shields.io/badge/⬇%20Download-bp--extension--manager.zip-1f5cff?style=for-the-badge&logo=wordpress&logoColor=white)](https://github.com/bPlugins/bp-extension-manager/raw/main/dist/bp-extension-manager.zip)

Grab the latest packaged library: **[bp-extension-manager.zip](https://github.com/bPlugins/bp-extension-manager/raw/main/dist/bp-extension-manager.zip)**
(runtime files + compiled admin bundle only — no build tooling). Unzip it into your host
plugin so the path becomes:

```
your-plugin/lib/bp-extension-manager/bootstrap.php
```

Then follow [Host integration](#host-integration). To regenerate the zip yourself, run
`npm run package` (see [Build pipeline](#building-the-bundle)).

---

## How it works (two layers)

1. **Layer 1 — `bootstrap.php`** is a frozen *recorder*. Every bundled copy records its
   version + path into `$GLOBALS['bpem_copies']`. On `plugins_loaded` (priority `-1000`)
   only the **newest** copy loads its classes and fires `bpem_loaded`. Two plugins shipping
   different versions therefore share one set of classes — no "cannot redeclare class" fatal.

2. **Layer 2 — `BPEM\Manager`** is a per-host instance. Each host boots its own Manager on
   `bpem_loaded`; every piece of state (options, REST namespace, AJAX action, nonce, asset
   handle, transient, menu) is keyed by the host `slug`, so two hosts never collide.

---

## Host integration

In your host plugin's main file:

```php
require_once __DIR__ . '/lib/bp-extension-manager/bootstrap.php';

add_action( 'bpem_loaded', function () {
    \BPEM\Manager::boot( array(
        'slug'        => '3d-viewer-premium',           // unique; namespaces everything
        'name'        => '3D Viewer',
        'version'     => BP3D_VERSION,
        'menu_parent' => 'edit.php?post_type=bp3d-model-viewer',
        'freemius'    => function_exists( 'bp3d_fs' ) ? bp3d_fs() : null,
        'max_plan_id' => 'max',
        'catalog_url' => 'https://catalog.example.com/3d-viewer-premium.json',
    ) );
} );
```

### `Manager::boot()` config

| Key | Type | Req | Default | Purpose |
|---|---|---|---|---|
| `slug` | string | ✅ | — | Unique host id; namespaces all state |
| `name` | string | ✅ | — | Display name |
| `version` | string | ✅ | — | Host version (compat checks) |
| `menu_parent` | string | ✅ | — | Parent admin menu slug to nest under |
| `capability` | string | — | `manage_options` | Admin gate |
| `freemius` | object\|callable\|string\|null | — | `null` | Host Freemius (for Max Plan) |
| `max_plan_id` | string\|null | — | `null` | Plan id that unlocks all add-ons |
| `catalog_url` | string\|null | — | `null` | Remote catalog JSON URL (extensions + optional `modules`) |
| `catalog_file` | string\|null | — | *(auto)* | Absolute path to a local catalog file (`.php` returning an array, or `.json`). Used when `catalog_url` is unset. See note below. |
| `modules_catalog_url` | string\|null | — | `null` | Separate module catalog URL (falls back to `catalog_url`) |
| `bundled_modules_dir` | string\|string[]\|null | — | `null` | Read-only module dir(s) shipped inside the host plugin |
| `enable_extensions` | bool | — | `true` | Turn the whole extensions subsystem on/off (tab + routes + boot) |
| `enable_modules` | bool | — | `true` | Turn the whole modules subsystem on/off (tab + routes + boot) |
| `enable_module_upload` | bool | — | `true` | Show the "Upload Module" (.zip) control + register its REST route. Set `false` to hide local uploads; catalog install + delete stay available |
| `enable_freemius_checkout` | bool | — | `true` | Buy paid extensions in-context via the Freemius Checkout overlay (no trip to your site). Set `false` to keep the classic "Buy Now" link-out. See [Buy Now with Freemius Checkout](#buy-now-with-freemius-checkout) |
| `catalog_ttl` | int | — | `2 * HOUR_IN_SECONDS` | Catalog cache lifetime |
| `page_slug` | string | — | `bpem-{slug}-extensions` | Admin page slug |
| `menu_badge` | bool\|string\|null | — | `null` | Show a "New" pill on the Extensions submenu. `true` → "New", or pass custom text. Auto-hides after the admin first opens the page. |
| `menu_badge_persist` | bool | — | `false` | Keep the badge visible always (disables the auto-hide-after-first-visit behavior). |

> **Local catalog & auto-discovery.** When neither `catalog_url` nor `catalog_file` is set, `Manager::boot()` looks for a catalog file next to the file that called it — `extensions.php`, `catalog.php`, `public/extensions.php`, or `public/catalog.php` — using the first that exists. A `.php` catalog is `include`d and must `return` an array (`[ 'schema' => 1, 'extensions' => [...], 'modules' => [...] ]`); treat it as code, not data.
>
> The calling file is detected via `debug_backtrace()`, so auto-discovery only works when the host calls `Manager::boot()` **directly** (including from inside a `bpem_loaded` closure). If you wrap `boot()` in a helper in another file, in an mu-plugin loader, or in a vendored copy of this library, the wrong directory is used and nothing is found — pass `catalog_file` explicitly in that case.

---

## Registering an add-on

An add-on plugin extends `BPEM\BaseExtension` and registers on its host's scoped hook.
It targets **exactly one** host via `get_host_slug()`.

```php
add_action( 'bpem/register/3d-viewer-premium', function ( \BPEM\Manager $manager ) {
    $manager->register( new My_Measure_Tool() );
} );

class My_Measure_Tool extends \BPEM\BaseExtension {
    public function get_id(): string        { return 'measure-tool'; }
    public function get_name(): string      { return 'Measure Tool'; }
    public function get_version(): string   { return '1.2.0'; }
    public function get_host_slug(): string { return '3d-viewer-premium'; } // the ONE host

    public function get_min_parent_version(): string { return '3.2.0'; }
    public function get_required_plugins(): array     { return array(); }
    public function get_freemius()                    { return function_exists( 'mt_fs' ) ? mt_fs() : null; }

    public function get_meta(): array {
        return array(
            'icon_url'          => 'https://cdn.example.com/measure/icon.png',
            'short_description' => 'Measure distances in the 3D scene.',
            'homepage_url'      => 'https://example.com/addons/measure-tool',
            'is_paid'           => true,
            'reload'            => 'notice', // optional: '' (default) | 'notice' | 'auto'
        );
    }

    public function boot(): void {
        // Wire your add-on. Runs ONLY when every gate passes.
    }
}
```

If `get_host_slug()` does not match the host firing the hook, `register()` drops the add-on
and logs (under `WP_DEBUG`) — an add-on physically cannot attach to the wrong host.

An add-on whose admin surfaces (menus, scripts, meta boxes) only appear or disappear on the
next full page load can declare a **reload behavior** — via the `reload` meta key above or by
overriding `get_reload_behavior()`. After a successful toggle the manager UI then either
prompts the admin with a sticky *"Reload the page for this to take effect"* notice and a
**Reload page** button (`'notice'`), or opens a confirmation dialog and reloads once the
admin approves — declining falls back to the sticky notice (`'auto'`). Undeclared
(default) toggles behave as before — no reload, no prompt.

---

## Gating pipeline

Evaluated in order; the first failing gate sets the status and short-circuits:

```
host version >= min?      no → incompatible
required plugins active?  no → missing_dependency
admin-enabled?            no → disabled        (DEFAULT)
licensed?                 no → unlicensed
boot() throws?            yes → error          (caught + logged; host never white-screens)
                          otherwise → active
```

License: host Max Plan unlocks all → free add-on passes → otherwise the add-on's Freemius
`can_use_premium_code()`.

---

## Admin UI

A submenu ("Extensions") is added under the host's `menu_parent`. A React app (built with
`@wordpress/scripts`) lists installed and remote extensions, toggles them, manages licenses,
and installs available ones — over REST (`bpem/{slug}/v1`) plus an admin-ajax license endpoint.

### Building the bundle

```bash
npm install
npm run build      # → build/index.js + index.asset.php + style-index.css
npm run package    # build, then zip the runtime lib → dist/bp-extension-manager.zip
```

The compiled `build/` directory (at the library root) is committed so the library
needs no Node at runtime — and so `npm run package` can stage it into the dist zip.
Rebuild it with `npm run build` and commit the result whenever `src/` changes.
`npm run package` stages only the runtime files (`bootstrap.php`, `includes/`,
`build/`, `catalog/`, docs) into a `bp-extension-manager/`-rooted archive ready to
drop into a host's `lib/`.

### Local development

When you develop by **symlinking** this repo into a host's `vendor/` dir (instead of
copying), two things bite if left unset:

- **Asset URL.** If the symlink's real path lives *outside* the WordPress tree (e.g. a
  repo under `~/Development` symlinked into Local's `wp-content/plugins/…`), the URL
  auto-resolver in [`includes/load.php`](includes/load.php) can't recover the public URL.
  Define it **before** `wp-settings.php` loads — in `wp-config.php` or an mu-plugin —
  pointing at the copy you actually edit:

  ```php
  define('BPEM_URL', 'https://your-site.test/wp-content/plugins/<host>/vendor/bp-extension-manager/');
  ```

  (Or use the `bpem_asset_url` filter.) If it points at a *different* bundled copy, the
  browser loads that copy's CSS/JS and your edits never appear.

- **Winning the newest-copy race.** Several hosts may bundle this library at the same
  version; only the newest copy defines the classes and serves assets. To make your dev
  copy win deterministically, bump its `$bpem_this_version` in `bootstrap.php` above the
  others.

After editing anything under `src/`, run `npm run build` (or `npm start` to watch) — the
compiled `build/` is what WordPress loads, so source edits do nothing until recompiled.

---

## Remote catalog

A static JSON file per host (see [`catalog/sample-host.json`](catalog/sample-host.json),
`schema: 1`). `CatalogService` fetches, validates, caches (transient
`bpem_{slug}_catalog`), and merges it with installed extensions. The `Installer` resolves
the real download URL **server-side** and validates the host against an allowlist — a
client never supplies a download URL.

A catalog entry's `requires_plugins` (or `required_plugins`) accepts, per item, either a
plain basename string (`"b-slider/b-slider.php"`) or an object with `file` (required),
`name`, and `url`. When a required plugin is inactive the card shows an **Activate** link if
it is already installed, otherwise an **Install** link — the object's `url` (e.g. a premium
store) when present, else the WP.org install search for the slug. Retarget any single
descriptor with the `bpem_missing_plugin` filter.

---

## Buy Now with Freemius Checkout

A paid extension can be purchased **in-context** — the [Freemius Checkout](https://freemius.com/help/documentation/selling-with-freemius/freemius-checkout-buy-button/)
overlay opens right on the Extensions page, so buyers never leave the site. It is
**on by default** (`enable_freemius_checkout`); when the overlay isn't available
(script blocked, or an extension has no checkout params) the button falls back to
the classic external **Buy Now** link, so nothing breaks.

**What happens after purchase:**

- **Store (not-installed) extension** → overlay closes → the add-on is downloaded
  and activated. The buyer's license key is forwarded to your
  `bpem/{slug}/install_url` filter so you can mint a **signed, license-scoped
  download URL** for the add-on zip (Freemius add-on downloads require this).
- **Installed but unlicensed extension** → the returned license key is activated
  automatically (or, if the widget returns no key, the license Freemius just
  attached to the buyer's account is **synced**) — the card flips to *Active*.

**Supplying checkout credentials.** The overlay needs the add-on's Freemius
`plugin_id` and `public_key` (both are safe to expose client-side — the public key
is designed for it). For an **installed** add-on they're read from its own Freemius
SDK automatically. For a **store** (catalog-only) add-on, add a `freemius` object
to the catalog entry:

```json
{
  "id": "measure-tool",
  "name": "Measure Tool",
  "version": "1.2.0",
  "is_paid": true,
  "price_label": "$29/yr",
  "plugin_file": "bp-measure-tool/bp-measure-tool.php",
  "freemius": {
    "plugin_id": "12345",
    "public_key": "pk_0123456789abcdef",
    "plan_id": "67890",
    "pricing_id": "13579"
  }
}
```

`plan_id` and `pricing_id` are optional (they preselect a plan). A malformed
`plugin_id`/`public_key` disables the overlay for that entry (link-out fallback).
You can also inject or override params in code with the `bpem/{slug}/checkout_params`
filter, and resolve the post-purchase download with `bpem/{slug}/install_url`
(now passed the license key as its 4th argument) — see
[`examples/host-plugin.php`](examples/host-plugin.php).

---

## Hooks & filters

| Name | Type | Fires / filters |
|---|---|---|
| `bpem_loaded` | action | once, after the newest copy sets up the autoloader |
| `bpem/register/{slug}` | action | host-scoped; add-ons register here (`$manager`) |
| `bpem/{slug}/booted` | action | after a host's extensions are evaluated |
| `bpem/{slug}/catalog` | filter | merged catalog array |
| `bpem/{slug}/extension_status` | filter | computed payload per extension |
| `bpem/{slug}/can_install` | filter | bool gate before install (`$can`, `$ext_id`, `$manager`, `$license_key`) |
| `bpem/{slug}/install_url` | filter | resolved download URL (paid → Freemius/signed) (`$url`, `$entry`, `$manager`, `$license_key`) |
| `bpem/{slug}/checkout_params` | filter | in-context Freemius Checkout params per extension (`$params`, `$ext_id`, `$manager`) |
| `bpem/{slug}/download_hosts` | filter | install download-host allowlist |
| `bpem/{slug}/modules` | filter | merged module list |
| `bpem/{slug}/module_status` | filter | computed payload per module |
| `bpem/{slug}/modules_dir` | filter | managed (writable) modules directory path |
| `bpem/{slug}/bundled_module_dirs` | filter | read-only bundled module directories |
| `bpem/{slug}/module_install_url` | filter | resolved module download URL (paid) |
| `bpem/{slug}/module_download_hosts` | filter | module download-host allowlist |
| `bpem/{slug}/enable_extensions` | filter | enable/disable the whole extensions subsystem (`bool`, `$manager`) |
| `bpem/{slug}/enable_modules` | filter | enable/disable the whole modules subsystem (`bool`, `$manager`) |
| `bpem/{slug}/enable_module_upload` | filter | show/hide the module `.zip` upload control + route (`bool`, `$manager`) |
| `bpem/{slug}/enable_freemius_checkout` | filter | enable/disable in-context Freemius Checkout for paid extensions (`bool`, `$manager`) |
| `bpem_missing_plugin` | filter | (global) one missing-dependency descriptor before it reaches the UI — retarget its install `url`/`name` (`$entry`, `$basename`) |
| `bpem_asset_url` | filter | (global) resolved library-root asset URL (must end with a slash) — override when symlinked outside the WP tree |

See [CONTRIBUTING.md](CONTRIBUTING.md) for the coexistence invariants you must not break.

---

## Modules

**Extensions** are separate add-on *plugins*. **Modules** are feature *packages* that live
inside a host-managed directory and are uploaded, toggled, and deleted from the same admin
page (a **Modules** tab appears next to **Extensions**). A module is **not** a plugin and is
**not** registered in code — it is discovered on disk and gated much like an extension, but
its license is **the host's own** (modules carry no Freemius of their own).

- **Storage:** `wp-content/uploads/bpem-modules/{slug}/{module-id}/` (per host, hardened
  against direct web access).
- **Add a module:** upload a `.zip` on the Modules tab, install one listed in the catalog's
  optional `modules` array, or **bundle it inside the host plugin** (see below).
- **License gate:** unlocked by the host Max Plan, host premium, or a named plan
  (`Requires Plan`). A free `Premium Host Only` module requires a premium host.
- **Default state:** newly added modules default **enabled** (a `Default Enabled: false`
  header opts out).
- **Reload behavior:** a `Reload: notice` header makes the UI prompt for a page reload after
  the module is toggled (or installed/uploaded already enabled); `Reload: auto` opens a
  confirmation dialog and reloads once the admin approves. Remote catalog entries may carry
  the same value as a `reload` key.

Either subsystem can be turned off entirely. Set `'enable_modules' => false` (or
`'enable_extensions' => false`) in `Manager::boot()` and that subsystem registers no REST
routes, shows no tab, and is never scanned or booted. If only modules are enabled, the
submenu is labelled **Modules**; if both are off, no admin page is added.

To decide dynamically — e.g. **offer modules only on a premium host** — filter the toggle
instead of hard-coding it (register the filter before `Manager::boot()` runs):

```php
add_filter( 'bpem/3d-viewer-premium/enable_modules', function ( $on, $manager ) {
    return $on && $manager->is_premium();   // free host → no Modules tab at all
}, 10, 2 );
```

(Individual modules can also be gated one-by-one with the `Premium` / `Premium Host Only`
headers — a free host then sees them but can't enable them. The filter above hides the whole
feature instead.)

### Module package format

A module is a folder whose main `.php` file carries a WordPress-style header, parsed with
`get_file_data()`. The host `require`s this file only when every gate passes. See
[`examples/modules/hello-module/`](examples/modules/hello-module/hello-module.php).

```php
<?php
/**
 * Module Name:       Measure Tool
 * Version:           1.2.0
 * Description:       Measure distances in the 3D scene.
 * Author:            bPlugins
 * Author URI:        https://bplugins.com
 * Icon URL:          https://cdn.example.com/measure/icon.png
 * Requires Host:     3.2.0                          // min host version
 * Requires Plugins:  woocommerce/woocommerce.php    // comma-separated basenames
 * Premium:           true                           // requires a premium host
 * Premium Host Only: false
 * Requires Plan:     max                            // optional host plan id
 * Default Enabled:   true
 * Reload:            notice                         // optional: notice | auto — page reload after toggle
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Wire the feature via WP hooks here — it runs only when the module is active.
```

### Enqueuing assets in a module

When enqueuing scripts (`wp_enqueue_script`) or styles (`wp_enqueue_style`) inside a module:

1. **Use WordPress Action Hooks:** Do NOT call `wp_enqueue_script()` directly in the root of your module PHP file or during file loading. Always wrap your asset calls inside standard WordPress hooks:
   - `wp_enqueue_scripts` (Frontend)
   - `admin_enqueue_scripts` (Admin Dashboard)
   - `enqueue_block_editor_assets` (Gutenberg / Block Editor)

2. **Cross-Platform URL Resolution:** Uploaded modules live in `wp-content/uploads/bpem-modules/{host-slug}/{module-id}/`, **not** in `wp-content/plugins/`. Do not use `plugins_url( 'js/script.js', __FILE__ )` directly as it will fail or return invalid paths inside the `uploads` directory.

Use `wp_upload_dir()` and `wp_normalize_path()` to construct cross-platform URLs safely across Windows, Linux, Apache, and Nginx:

```php
add_action( 'wp_enqueue_scripts', function () {
    $upload_dir     = wp_upload_dir();
    $module_dir     = wp_normalize_path( __DIR__ );
    $upload_basedir = wp_normalize_path( $upload_dir['basedir'] );

    // Resolve URL for uploaded modules (uploads dir) or bundled modules (plugin dir)
    if ( strpos( $module_dir, $upload_basedir ) === 0 ) {
        $module_url = str_replace( $upload_basedir, $upload_dir['baseurl'], $module_dir );
    } else {
        $module_url = plugins_url( '', __FILE__ );
    }

    wp_enqueue_script(
        'my-module-script',
        trailingslashit( $module_url ) . 'assets/js/script.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );
} );
```

### Module gating pipeline

```
host version >= Requires Host?   no → incompatible
required plugins active?         no → missing_dependency
admin-enabled?                   no → disabled        (DEFAULT: enabled)
host license permits?            no → unlicensed
main file require() throws?      yes → error          (caught + logged)
                                 otherwise → active
```

### Bundling modules inside the host plugin

To ship modules **with** the host plugin (available out of the box, no upload), register one
or more read-only directories. Bundled modules appear in the Modules tab and can be toggled
like any other, but are **not deletable** and are **never overwritten** by uploads. Point at a
folder of module subfolders — each subfolder is a module with the header format above.

```php
add_action( 'bpem/register/3d-viewer-premium', function ( \BPEM\Manager $manager ) {
    // your-plugin/modules/<module-id>/<module-id>.php
    $manager->add_module_dir( __DIR__ . '/modules' );
} );
```

Or via config: `'bundled_modules_dir' => __DIR__ . '/modules'` (accepts a string or an array).
The `bpem/{slug}/bundled_module_dirs` filter can add/adjust the list too. If an uploaded
module and a bundled module share an id, the uploaded one wins (so a shipped module can be
updated by uploading a newer copy).

### Security

Uploading a module installs executable PHP, so it is gated like installing a plugin: the
upload/install/delete REST routes require **`install_plugins`** (plus the page capability) and
are blocked when `DISALLOW_FILE_MODS` is set. Uploaded zips are validated (must carry a
`Module Name` header) and confined to the managed directory; deletes cannot escape it.

---

## License

**bp-extension-manager** is free software, released under the
[GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html)
(GPLv2-or-later) — the same license as WordPress itself.

```
This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/gpl-2.0.html>.
```

Create a tutorial video for my WordPress plugin.

Plugin: <path-to-plugin-folder or repo>
Audience: <end users | developers/integrators>
Goal: after watching, the viewer should be able to <install & configure it | build an add-on | …>
Length: ~<8–12> minutes
Language: English

Voice: use "Ava (Premium)" if installed, otherwise best available; 
narration pace conversational, not rushed.

Content:
- Study the plugin code and README first; every claim and code snippet 
  in the video must be copy-paste accurate against the repo.
- Write the script + slide deck first (docs/video-script.md, docs/slides.html), 
  show me the script outline, then render the video without waiting.
- Cover: the problem it solves, install/setup, the 3 most important 
  features with real examples, common pitfalls, where to get help.
- Include live screen footage: spin up a local WordPress (wp-env), 
  install the plugin, and record the key admin/front-end flows with 
  Playwright; splice clips at the matching scene marks.

Branding: bPlugins; end card with <site/repo URL>.
Output: docs/tutorial.mp4, 1920x1080; don't commit binaries to git
