# Development Workflow

Working in a Unicorn monorepo is designed to feel as close to standard Composer development as possible, but with superpowers to manage cross-package dependencies.

## 1. Requiring Dependencies

### External Libraries
If your `apps/web` package needs a third-party library, navigate to that package's directory and use Composer as usual:

```bash
cd apps/web
composer require guzzlehttp/guzzle
```

Unicorn will automatically intercept the process. It will update the global `composer.lock` file to guarantee that if another package in the monorepo also requires Guzzle, they will both be forced to use compatible versions.

### Internal (Local) Packages
If `apps/web` needs to use a shared internal package (e.g., `packages/logger`), you don't need to configure path repositories. Simply run:

```bash
cd apps/web
composer require my-org/logger
```
Unicorn automatically resolves `my-org/logger` to the local path defined in your root `composer.json`.

## 2. Updating Dependencies Globally

In a monorepo, updating a dependency should often happen across all packages simultaneously to prevent drift. Instead of navigating into each folder, use:

```bash
composer uni:update vendor/package
```
This command updates the required package across *all* dependent packages in the monorepo at once. You can also change version constraints on the fly:
```bash
composer uni:update "guzzlehttp/guzzle ^7.0"
```

## 3. Bumping Local Package Versions

Local packages must have a semantic version defined in their `composer.json` (e.g., `"version": "1.0.0"`). Dependent packages can then require them using version constraints, such as `"require": { "my-org/logger": "^1.0" }`.

You should perform **any** version bump using the `uni:version` command from within the target package's directory. This ensures that dependent packages are properly synchronized. For example, when bumping a major version (e.g., `1.0.0` to `2.0.0`), strict constraints in dependent packages will no longer satisfy the new version.

```bash
cd packages/logger
composer uni:version major
```

This command will:
1. Bump the version inside `packages/logger/composer.json`.
2. Find all packages in the monorepo whose constraints for `logger` no longer match the new version.
3. Automatically update their `require` statements to match the new version (e.g., to `"^2.0"`).
4. Perform a test installation to ensure the monorepo remains stable.
5. Execute any scripts defined in `post-update-scripts` for the affected packages.
6. Automatically roll back all version changes if any installation or script execution errors occur.

## 4. Visualizing the Architecture

As monorepos grow, it becomes difficult to understand which package depends on which. Unicorn ships with a built-in interactive visualizer.

From the root of your project, run:
```bash
composer uni:server
```
This spins up a local HTTP server (default port `8067`). Open the provided link in your browser to explore your dependency graph interactively via a Mermaid diagram. You can click on packages to navigate up and down the dependency tree.

## 5. Smart Continuous Integration (CI)

When you make a change in a low-level package (like `logger`), you need to ensure you haven't broken any packages that depend on it (like `database` or `web`). Running the entire test suite for the monorepo can take hours.

Unicorn provides a "Smart CI" command to run scripts recursively up the dependency tree:

```bash
cd packages/logger
composer uni:run test
```

This will run the `test` script defined in the `composer.json` for:
- The `logger` package itself.
- Any packages that require `logger` (e.g., `database`).
- Any packages that require `database` (e.g., `web`).

This approach guarantees that downstream dependents are tested against your changes without the overhead of running unrelated test suites.

## 6. Building for Production

When it's time to deploy an application (e.g., `apps/web`) to a production server or package it into a Docker image, you don't want to carry over the symlinks pointing to the global `vendor` directory.

Use the build command:
```bash
composer uni:build ./apps/web ./dist/web
```
This command will copy the `web` application into the `./dist/web` directory and install all of its required dependencies (both local and external) as hard files, generating a fresh, production-ready `vendor/autoload.php` optimized for deployment.
