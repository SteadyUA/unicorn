# TODO

## Documentation & Testing
- Create documentation for using custom commands.
- Develop integration tests for different Composer versions to guarantee compatibility with new versions.
- Develop a sample monorepo project to demonstrate functionality and serve as a fixture for automated testing.

## Releases & Changelog
- Implement a command (e.g., `composer uni:release <version>`) to manage the monorepo release cycle.
- Automatically gather commits (e.g., parsing Conventional Commits) since the last tag.
- Generate or update `CHANGELOG.md`.
- Bump versions in `composer.json` files using the existing `uni:version` logic.
- Create Git tags for the release.

## Git Split
- Implement a feature to split monorepo packages into their own read-only remote repositories.
- Could be implemented as a standalone command (e.g., `composer uni:split`) or via documentation/integration with existing splitters like `splitsh/lite` or GitHub Actions.

## Orphan Packages Check
- Implement a command (e.g., `composer uni:check-orphans`) to find packages inside `./packages/*` that are not required by any root application (e.g., `web` or `worker`) and are not marked as public libraries. This will help identify and clean up dead code.

## Cross-Platform Compatibility
- Implement and test full support for Windows operating systems (currently symlinks and path resolution might be Unix-specific).
