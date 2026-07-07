# Development Workflow

Working in a Unicorn monorepo is designed to feel as close to standard Composer development as possible, but with superpowers to manage cross-package dependencies.

## 0. Initialization and Syncing

Whether you are setting up the project for the first time or pulling new code changes from your repository (e.g., after `git pull`), you must rebuild the local dependency symlinks and autoloader. To do this, simply run:

```bash
composer uni:install
```
*(Alias: `composer uni:i`)*

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

> **Tip**: If it is not possible to update to the required version (e.g., due to dependency conflicts), you can analyze the blocking dependencies using the `uni:why-not` command:
> ```bash
> composer uni:why-not "guzzlehttp/guzzle ^7.0"
> ```

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

> **Tip**: If a version bump fails (e.g., due to conflicts in dependent packages), you can use the `uni:why` command to see all packages that depend on the current package and might be blocking the update:
> ```bash
> composer uni:why -t
> ```

## 4. Visualizing the Architecture

As monorepos grow, it becomes difficult to understand which package depends on which. Unicorn ships with a built-in interactive visualizer.

From the root of your project, run:
```bash
composer uni:server
```
After running the command, a link will appear in your console. Open this link in your browser to explore your dependency graph interactively. For full details and configuration options, see the [`uni:server` documentation in the Commands Reference](03-commands.md#composer-uniserver).

## 5. Smart Continuous Integration (CI)

When you make a change in a low-level package (like `logger`), you need to ensure you haven't broken any packages that depend on it (like `database` or `web`). Running the entire test suite for the monorepo can take hours.

While you use the standard Composer command to run tests for the **current** package:
```bash
cd packages/logger
composer run test
```

After modifying your code, you can easily run tests for the packages that depend on the current package using:
```bash
composer uni:run test
```

This approach guarantees that downstream dependents are tested against your changes without the overhead of running unrelated test suites in the monorepo.

For full details on dependency tree resolution and additional options (such as recursive execution), see the [`uni:run` documentation in the Commands Reference](03-commands.md#composer-unirun-options-script).

## 6. Building for Production

When it's time to deploy an application (e.g., `apps/web`) to a production server or package it into a Docker image, you don't want to carry over the symlinks pointing to the global `vendor` directory.

Use the build command:
```bash
composer uni:build my-org/web ./dist/web
```
The first parameter (`my-org/web`) is the name of the package you want to build, and the second (`./dist/web`) is the target directory where the build will be placed.

This command will copy the application into the target directory and automatically perform a `composer install` there. This ensures all required dependencies (both local and external) are installed as actual files rather than symlinks, generating a fresh, production-ready `vendor/autoload.php`.

You can customize the parameters passed to this installation process (such as `--no-dev`) using the `build-install-options` setting in your root `composer.json` (see the [Getting Started guide](./01-getting-started.md#additional-configuration-options)).
