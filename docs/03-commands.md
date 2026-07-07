# Commands Reference

All Unicorn commands are prefixed with `uni:` and can be executed via the standard `composer` CLI.

> **Tip**: You can use the standard Composer `-d` or `--working-dir` option with any command to specify the working directory without needing to `cd` into it (e.g., `composer uni:run test -d packages/logger`). This is especially useful for automation scripts.

---

### `composer uni:install [<packages>...]`
*(Alias: `composer uni:i`)*

Initializes or restores the monorepo dependencies.
- If called without parameters and the global `vendor` directory does not exist, it installs all dependencies for the entire monorepo.
- If the global `vendor` directory exists, it verifies the consistency of package requirements against `composer.lock`.
- If a list of local packages is provided, it behaves like running `composer install` inside each of those packages.

> **Important**: You should run this command every time you pull code changes during development (e.g., after `git pull`) to ensure that all local dependencies are properly symlinked and the dependencies tree is updated.

**Example:**
```bash
composer uni:install
```

---

### `composer uni:update <packages>...`
Updates the specified packages across all dependents in the monorepo.
- Supports changing constraints on the fly: e.g., `composer uni:update "foo/bar:^1.0"` or `composer uni:update foo/bar=^1.0`.
- Edits `composer.json` files safely (reverts them if the update fails).
- Executes scripts defined in `post-update-scripts` (in the root `composer.json`) after a successful update. See [Additional Configuration Options](01-getting-started.md#additional-configuration-options) for an example.

**Example:**
```bash
composer uni:update "guzzlehttp/guzzle ^7.0"
```

---

### `composer uni:run [options] <script>...`
Executes the given script for packages that depend on the current target package. Essential for Smart CI pipelines to test downstream impacts.

**Example:**
```bash
composer uni:run test
```

By default, scripts are run **only** for immediate dependents.
For example, given the dependency chain: `application` -> `package A` -> `package B` -> `our target package`:
- Running `uni:run test` inside `our target package` will execute the script **only for `package B`**.

**Options:**
- `-s, --self`: Also run the script for the current target package itself (e.g., runs for `package B` AND `our target package`). Standard practice assumes you run `composer run ...` for the target package directly, hence it's excluded by default.
- `-r, --recursive`: Recursively resolve and run the script for all dependents up to the root (e.g., runs for `application`, `package A`, and `package B`).
- `-a, --all`: Run the script for *all* local packages in the monorepo, regardless of the dependency tree.
- `-l, --list`: List all available scripts.

---

### `composer uni:build [options] <package> <directory>`
Prepares a package for production deployment.
- **`<package>`**: The name of the local package you want to build (e.g., `my-org/web`).
- **`<directory>`**: The target directory where the built application will be placed (e.g., `./dist/web`).
- Copies all required packages (both third-party and local) into the specified `<directory>` instead of symlinking them, and automatically performs a `composer install`.
- **Options:**
  - `-f, --force`: Removes the target directory if it already exists before building.
  - `--env-file`: Path to a file containing environment variables to load during the build process.
  - `--env`: Pass environment variables directly as key=value pairs (e.g., `--env=APP_ENV=prod`). Can be specified multiple times.
- You can pass additional install flags (like `--no-dev --optimize-autoloader`) via the `build-install-options` config in the root `composer.json` (see [Additional Configuration Options](./01-getting-started.md#additional-configuration-options) for an example).

**Example:**
```bash
composer uni:build my-org/web ./dist/web
```

---

### `composer uni:split`
Splits local monorepo packages into their own independent remote Git repositories.
- Essential for mirroring packages to read-only repositories (like `github.com/my-org/logger`).
- Automatically handles Git history extraction and tag pushing.

For a full setup guide, see [Monorepo Split](04-monorepo-split.md).

---

### `composer uni:doctor`
Diagnoses the state of the monorepo and detects configuration issues.
- Validates the root `composer.json` configuration.
- Checks all local package directories for valid `composer.json` files and proper placement.
- Verifies if the monorepo is initialized properly.
- Scans for orphaned dependencies (local packages that are not required by any other package).

---

### `composer uni:server`
Spins up a local HTTP web server (default port `8067`) to visually explore the monorepo's dependency graph. Uses Mermaid JS to render interactive architectural diagrams. You can customize the port by setting the `UNI_SERVER_PORT` environment variable.

The server allows analyzing package dependencies and provides two main views depending on where the command is executed:

#### 1. Package List View (running outside a package)
When the server is launched from the root or outside a specific package directory, it displays a comprehensive package list:

![Package List](img/server-package-list.jpg)

- Includes **sorting and search** capabilities.
- By default, **third-party and dev-dependencies are hidden** to keep the list clean.
- A **filter in the top right corner** allows you to customize which packages are displayed.

#### 2. Package Details View (running inside a local package)
When the server is launched from within a local package directory, it focuses on that specific package:

![Package Details](img/server-package.jpg)

- Displays **author information**.
- Lists **dependents** (packages that depend on the current one).
- Lists **requirements** (packages the current package depends on).
- Features an **interactive diagram** at the bottom for navigating between packages.

The interactive diagram shows only *direct* dependencies by default. For deeper analysis, you can click the following links:
- **Dependents from above**: Renders a full dependency tree from the top down to the target package.
- **Requirements to the very bottom**: Renders a diagram of all dependencies all the way down from the target package.

---

### `composer uni:version [ major | minor | patch ]`
Bumps the version of the current package and automatically updates the requirement constraint in all packages that depend on it.

This command will:
1. Bump the version inside the target package's `composer.json`.
2. Find all packages in the monorepo whose constraints for the target package no longer match the new version.
3. Automatically update their `require` statements to match the new version (e.g., to `"^2.0"`).
4. Perform a test installation to ensure the monorepo remains stable.
5. Execute any scripts defined in `post-update-scripts` for the affected packages.
6. Automatically roll back all version changes if any installation or script execution errors occur.

---

> **Note**: The `uni:why`, `uni:why-not`, and `uni:show` commands are based on the built-in Composer commands (`why`, `why-not`, and `show`). Therefore, they fully support all standard options and flags of their parent commands.

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
