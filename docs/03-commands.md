# Commands Reference

All Unicorn commands are prefixed with `uni:` and can be executed via the standard `composer` CLI.

---

### `composer uni:install [options] [--] [<packages>...]`
Initializes or restores the monorepo dependencies.
- If called without parameters and `uni_vendor` does not exist, it installs all dependencies for the entire monorepo.
- If `uni_vendor` exists, it verifies the consistency of package requirements against `unicorn.lock`.
- If a list of local packages is provided, it behaves like running `composer install` inside each of those packages.

> **Note**: If any of the local packages in your monorepo contains an invalid `composer.json` file (e.g., syntax errors, missing name, or invalid version constraints), the plugin might fail to initialize entirely, and Composer will output:
> `There are no commands defined in the "uni" namespace.`
> If you encounter this error, run `composer uni:doctor` to diagnose the state of your monorepo and pinpoint the invalid configurations.

---

### `composer uni:update <packages>...`
Updates the specified packages across all dependents in the monorepo.
- Supports changing constraints on the fly: e.g., `composer uni:update "foo/bar:^1.0"` or `composer uni:update foo/bar=^1.0`.
- Edits `composer.json` files safely (reverts them if the update fails).
- Executes scripts defined in `post-update-scripts` (in `unicorn.json`) after a successful update.

---

### `composer uni:run [options] [--] [<script>...]`
Recursively resolves the dependency tree and executes the given script for all packages that depend on the current package. Essential for Smart CI pipelines.
- **Options:**
  - `-s, --self`: Also run the script for the current package.
  - `-r, --recursive`: Recursively resolve dependencies all the way up to the root applications.
  - `-a, --all`: Run the script for *all* local packages in the monorepo.
  - `-l, --list`: List all available scripts.

---

### `composer uni:build <package> <directory>`
Prepares a package for production deployment.
- Copies all required packages (both third-party and local) into the specified `<directory>` instead of symlinking them.
- You can pass additional install flags (like `--no-dev --optimize-autoloader`) via the `build-install-options` config in `unicorn.json`.

---

### `composer uni:server`
Spins up a local HTTP web server (default port `8067`) to visually explore the monorepo's dependency graph. Uses Mermaid JS to render interactive architectural diagrams.

---

### `composer uni:version [ major | minor | patch ]`
Bumps the version of the current package and automatically updates the requirement constraint in all packages that depend on it.

---

### `composer uni:why <package>`
Shows which packages in the monorepo cause the given package to be installed (upstream dependents).

---

### `composer uni:why-not <package>`
Shows which packages prevent the given package from being installed or updated to a specific version due to conflicts.

---

### `composer uni:show`
Displays detailed information about the installed packages in the monorepo.

---

### `composer uni:namespace`
Suggests package namespaces based on a given pattern.
