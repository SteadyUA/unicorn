# Getting Started

The `steady-ua/unicorn` plugin is designed to manage PHP monorepos seamlessly as a native Composer v2 plugin. It completely eliminates the need to manually declare local `path` repositories in every package's `composer.json` and ensures dependency versions remain consistent across your entire project.

## Installation

Because Unicorn intercepts and extends global Composer behavior for local path resolution, it must be installed globally. It requires Composer `2.3` or later and PHP `>=8.0`.

```bash
composer global require steady-ua/unicorn
```

*(Note: Windows operating systems are currently not supported).*

## The Configuration File (`composer.json`)

To turn any directory into a Unicorn-powered monorepo, you simply place a `composer.json` file in the root directory. This file dictates where Unicorn should look for local packages. It must have `"type": "monorepo"`.

### Example `composer.json`

```json
{
    "type": "monorepo",
    "repositories": [
        {
            "type": "path",
            "url": "./apps/*"
        },
        {
            "type": "path",
            "url": "./packages/*"
        }
    ]
}
```

The `"repositories"` block uses standard Composer syntax. By using wildcards (`*`), you can tell Unicorn to recursively scan folders for `composer.json` files. As this is simply a monorepo root description, fields like `name`, `description`, and `require` are not needed.

### Additional Configuration Options

Unicorn supports specific configuration options defined in the `extra` block of the root `composer.json`:

```json
{
    "type": "monorepo",
    "repositories": [
        { "type": "path", "url": "./packages/*" }
    ],
    "extra": {
        "build-install-options": "--no-dev --optimize-autoloader",
        "post-update-scripts": [
            "test",
            "lint"
        ],
        "uni-split": {
            "remote-pattern": "https://github.com/my-org/{name}.git"
        }
    }
}
```

- **`build-install-options`**: A string containing additional flags passed to Composer during the `uni:build` command (e.g., `--no-dev --optimize-autoloader`).
- **`post-update-scripts`**: An array of script names that will be automatically executed after a successful `uni:update` or `uni:version` for the affected packages. Note that a script will only run in an affected package if that package has the script defined in its own `composer.json`. It is recommended to use consistent script names (e.g., `"test"`, `"lint"`) across all your packages.
- **`uni-split`**: Configuration for automating the split of your local packages into read-only remote repositories. See the [Monorepo Split Guide](04-monorepo-split.md) for full details.

### Typical Directory Structure

A recommended monorepo structure looks like this:

> **Important**: Every local package in the monorepo must use semantic versioning. This means each package's `composer.json` must contain a `"version"` field (e.g., `"version": "1.0.0"`).

```text
my-monorepo/
├── composer.json         # Monorepo configuration
├── apps/                 # Root applications (e.g., Web, API, Workers)
│   ├── web/
│   │   └── composer.json
│   └── worker/
│       └── composer.json
└── packages/             # Shared local dependencies
    ├── database/
    │   └── composer.json
    └── logger/
        └── composer.json
```

> **Tip**: You can explore a fully configured example in the [Unicorn Demo Repository](https://github.com/SteadyUA/unicorn-demo).

## First Installation

Once your root `composer.json` is set up and your packages are in place, simply run:

```bash
composer uni:install
```

Unicorn will:
1. Scan the configured paths for local packages.
2. Resolve the global dependency tree using Composer's native SAT solver (preventing version conflicts).
3. Download third-party libraries into a shared `vendor/` directory at the root (to save disk space and time).
4. Symlink the required libraries back into the individual `vendor/` folders of each package, ensuring **strict autoloader isolation**.

> **Troubleshooting**: If initialization fails (for example, with a "There are no commands defined in the 'uni' namespace" error), it usually means one of your local packages has an invalid `composer.json` file. Run `composer uni:doctor` to diagnose the exact state of your monorepo and pinpoint the issue.

Next, read about the [Daily Workflow](./02-workflow.md) to learn how to manage dependencies during development.
