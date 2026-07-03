# Getting Started

The `steady-ua/unicorn` plugin is designed to manage PHP monorepos seamlessly as a native Composer v2 plugin. It completely eliminates the need to manually declare local `path` repositories in every package's `composer.json` and ensures dependency versions remain consistent across your entire project.

## Installation

Because Unicorn intercepts and extends global Composer behavior for local path resolution, it must be installed globally. It requires Composer `2.3` or later and PHP `>=8.0`.

```bash
composer global require steady-ua/unicorn
```

*(Note: Windows operating systems are currently not supported).*

## The Configuration File (`unicorn.json`)

To turn any directory into a Unicorn-powered monorepo, you simply place a `unicorn.json` file in the root directory. This file dictates where Unicorn should look for local packages.

### Example `unicorn.json`

```json
{
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

The `"repositories"` block uses standard Composer syntax. By using wildcards (`*`), you can tell Unicorn to recursively scan folders for `composer.json` files.

### Typical Directory Structure

A recommended monorepo structure looks like this:

```text
my-monorepo/
├── unicorn.json          # Global configuration
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

## First Installation

Once your `unicorn.json` is set up and your packages are in place, simply run:

```bash
composer uni:install
```

Unicorn will:
1. Scan the configured paths for local packages.
2. Resolve the global dependency tree using Composer's native SAT solver (preventing version conflicts).
3. Download third-party libraries into a shared `uni_vendor/` directory at the root (to save disk space and time).
4. Symlink the required libraries back into the individual `vendor/` folders of each package, ensuring **strict autoloader isolation**.

> **Troubleshooting**: If initialization fails (for example, with a "There are no commands defined in the 'uni' namespace" error), it usually means one of your local packages has an invalid `composer.json` file. Run `composer uni:doctor` to diagnose the exact state of your monorepo and pinpoint the issue.

Next, read about the [Daily Workflow](./02-workflow.md) to learn how to manage dependencies during development.
