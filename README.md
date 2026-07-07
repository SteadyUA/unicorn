# unicorn

The `composer` plugin organizes a mono-repository of php packages.\
Ensures the consistency of all dependencies on the same package version.\
Adds tools for working with shared dependencies.

1. [Concept](#concept)
2. [Documentation](#documentation)

## Concept

For example, we have two projects:
- the `web` project serves http requests
- the `worker` project for background processes

Both projects use common packages placed in the `packages` directory

```text
 apps/
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
 composer.json  # monorepo definition root config
```

Raw `composer` - provides the ability to include local packages by specifying your own `path` repository.\
But there are a number of limitations, such as:
- in each `composer.json` file you must describe all local repositories.
- each package has its own local file, and it is possible that packages of different versions are used.
- difficult to analyze package usage.
- difficult to update dependencies used in different packages.

The `steady-ua/unicorn` plugin removes these restrictions and provides tools for analyzing and updating dependencies.

**Why Unicorn instead of other Monorepo tools?**
Unlike tools that merge all `composer.json` files into a single root file, `unicorn` works as a native Composer v2 plugin.
- **Native Dependency Resolution**: All monorepo packages are registered in a local Composer registry. This delegates validation entirely to Composer's native SAT solver. To add a dependency, simply navigate to the needed package and run `composer require my/foo`. Composer will naturally prevent version conflicts (e.g., trying to install different versions of a third-party library across the monorepo) and will automatically detect and prevent circular dependencies during installation.
- **Semantic Versioning at the Core**: The entire architecture revolves around semantic versioning. Each package must define its version in `composer.json` (e.g., `"version": "1.0.0"`). To simplify workflows, the `uni:version` command automates version bumping, updates the constraints in any dependent local packages, and runs configured tests or scripts. For external dependencies, the `uni:update` command simplifies third-party package migration by synchronizing updates and running tests across all dependent local packages.
- **Dependency Analysis & Visualization**: Includes built-in commands like `uni:why` and `uni:why-not` to trace dependency paths across the monorepo, as well as an interactive browser-based dependency graph visualization using the `uni:server` command.
- **Isolated Contexts**: For development, a shared `vendor` folder is created at the project root. However, packages remain self-sufficient. Each package retains its own `composer.json` and gets its own local `vendor` directory where only its explicitly required dependencies are installed via symlinks. This prevents accidental reliance on undeclared packages and allows you to run dependency analysis tools accurately at the individual package level.
- **Smart CI capabilities**: Commands like `uni:run` allow you to execute scripts (e.g., tests or linters) recursively up the dependency tree, ensuring that changes in a base package don't break downstream dependents without having to run tests for the entire monorepo.
- **Distribution and Release**: Solves common monorepo deployment issues.\
 The `uni:build` command extracts an independent, ready-to-run application with only its strictly required dependencies, making it perfect for Docker builds or direct deployment without dragging the entire monorepo along. \
 Additionally, the `uni:split` command automates the publishing of local packages to separate, read-only repositories. This allows external projects outside the monorepo to easily require your shared packages via standard Composer installations.

## Documentation

For a detailed guide on how to work with Unicorn, check out the [docs/](./docs) directory:
- [Getting Started](./docs/01-getting-started.md)
- [Development Workflow](./docs/02-workflow.md)
- [Commands Reference](./docs/03-commands.md)
- [Monorepo Split Guide](./docs/04-monorepo-split.md)
