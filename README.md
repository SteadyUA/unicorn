# unicorn

The `composer` plugin organizes a mono-repository of php packages.\
Ensures the consistency of all dependencies on the same package version.\
Adds tools for working with shared dependencies.

1. [Concept](#concept)
2. [Installation](#installation)
3. [Documentation](#documentation)
4. [Usage](#usage)
5. [Commands](#commands)
6. [The unicorn.json schema](#the-unicornjson-schema)

## Concept

For example, we have two projects:
- the `web` project serves http requests
- the `worker` project for background processes

Both projects use common packages placed in the `packages` directory

```
 web/
    index.php
    composer.json
 worker/
    console.php
    composer.json
 packages/
    foo/
        composer.json
    bar/
        composer.json
 unicorn.json
```

`composer` - provides the ability to include local packages by specifying your own `path` repository.\
But there are a number of limitations, such as:
- in each `composer.json` file you must describe all local repositories.
- each package has its own local file, and it is possible that packages of different versions are used.
- difficult to analyze package usage.
- difficult to update dependencies used in different packages.

The `steady-ua/unicorn` plugin removes these restrictions and provides tools for analyzing and updating dependencies.

**Why Unicorn instead of other Monorepo tools?**
Unlike tools that merge all `composer.json` files into a single root file, `unicorn` works as a native Composer v2 plugin.
- **Native Dependency Resolution**: All monorepo packages are registered in a local Composer registry. This delegates validation entirely to Composer's native SAT solver. Composer will naturally prevent version conflicts (e.g., trying to install different versions of a third-party library across the monorepo) and will automatically detect and prevent circular dependencies during installation.
- **Isolated Contexts**: Each package retains its own independent `composer.json` and context, making packages truly portable and avoiding the "one huge vendor dir" issue for development.
- **Smart CI capabilities**: Commands like `uni:run` allow you to execute scripts (e.g., tests or linters) recursively up the dependency tree, ensuring that changes in a base package don't break downstream dependents without having to run tests for the entire monorepo.

In the root folder, you need to place the `unicorn.json` file.
It describes all the common repositories.
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./web"
        },
        {
            "type": "path",
            "url": "./worker"
        },
        {
            "type": "path",
            "url": "./packages/*"
        }
    ]
}
```
> Optionally, other types of private repositories can be specified.

Now any local package can include packages from these repositories.\
No more need to describe the `path` repositories in each package.

A shared folder `uni_vendor` is created where all required packages are installed.\
All dependent packages create symbolic links to them.

This is to ensure that all dependencies use the same version.\
And it speeds up installation.

The used versions are fixed in the `unicorn.lock` file and will be used during installation.

> When deploying an application, instead of symbolic links, you can copy the necessary packages.\
Command [composer uni:build](#composer-unibuild)

## Installation

Compatible with `composer` version `2.3` or later.
Currently does not work on `windows` operating system.
The plugin must be installed globally.
```bash
# Optional: Pre-approve the plugin to avoid interactive prompts in CI/CD environments
composer global config allow-plugins.steady-ua/unicorn true

composer global require steady-ua/unicorn
```

> **Note for Composer 2.8+**: If you frequently run commands inside monorepo subdirectories that lack their own `composer.json`, Composer may prompt you with `"No composer.json in current directory, do you want to use the one at /path/to/parent?"`. You can disable this prompt and default to the global installation by running:
> ```bash
> composer config -g use-parent-dir false
> ```

## Usage

After creating the `unicorn.json` file, just use `composer` as usual.

## Documentation

For a detailed guide on how to work with Unicorn, check out the [docs/](./docs) directory:
- [Getting Started](./docs/01-getting-started.md)
- [Development Workflow](./docs/02-workflow.md)
- [Commands Reference](./docs/03-commands.md)

The `unicorn.lock` file is recommended to be kept under a version control system (eg GIT).
The `uni_vendor` directory, like the `vendor` directories, is recommended to be excluded.
The generated `composer.lock` files should also be excluded.

If a version conflict occurs when including a dependent package, an error will be displayed.

Use next commands to solve problems.

## Commands & Configuration

The `unicorn` plugin provides many commands for interacting with your monorepo, such as:
- `uni:install` / `uni:update` - Manage dependencies across all packages.
- `uni:run` - Execute scripts recursively.
- `uni:doctor` - Diagnose the state of the monorepo and detect issues (like orphaned dependencies or invalid `composer.json` files).
- `uni:server` - Visually explore the dependency graph.

For the full list of commands and their options, see the **[Commands Reference](./docs/03-commands.md)**.

For details on the `unicorn.json` schema (including `build-install-options` and `post-update-scripts`), see the **[Getting Started Guide](./docs/01-getting-started.md)**.
