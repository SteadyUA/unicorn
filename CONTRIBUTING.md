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

*(Instructions for running PHPUnit will go here once the test suite is fully set up)*

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
