# Contributing to Unicorn

Thank you for your interest in contributing to the `steady-ua/unicorn` Composer plugin!

## Development Setup

This repository uses a Git Submodule for its testing fixture (`tests/Fixtures/demo`). This demo project is a completely separate repository (`steady-ua/unicorn-demo`) that we use to run integration tests against a realistic monorepo structure.

### Cloning the Repository

To ensure you have all the necessary files, including the test fixtures, you must clone the repository recursively:

```bash
git clone --recursive https://github.com/steady-ua/unicorn.git
```

If you have already cloned the repository without the `--recursive` flag, the `tests/Fixtures/demo` directory will be empty. You can initialize and fetch the submodule by running:

```bash
git submodule update --init --recursive
```

### Running Tests

To run the automated integration tests:

```bash
vendor/bin/phpunit
```

### Manual Testing

For isolation, it is highly recommended to perform manual testing inside a **devcontainer**. Since Composer plugins must be installed globally, a devcontainer ensures your local machine's global Composer setup remains unaffected.

To manually test changes during development, configure your global Composer to use the local source code via a path repository. This creates a symlink, so any changes you make to the code are immediately available.

```bash
# 1. Add the local directory as a repository in the global config
composer global config repositories.unicorn path /workspaces/unicorn

# 2. Install the plugin globally from the local source (via symlink)
composer global require steady-ua/unicorn:@dev
```

Once installed, you can navigate to the demo fixture and run commands to see your changes in action:

```bash
cd /workspaces/unicorn/tests/Fixtures/demo
composer uni:doctor
```

### Updating the Demo Submodule

If you need to make changes to the demo project or pull the latest changes from its remote repository, navigate to the submodule directory, perform your Git operations, and then commit the updated submodule reference in the parent repository:

```bash
cd tests/Fixtures/demo
git checkout main
git pull origin main
cd ../../../
git add tests/Fixtures/demo
git commit -m "Update demo submodule"
```
