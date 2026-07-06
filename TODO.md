# TODO

## Releases & Changelog
- [ ] Implement a high-level orchestrator command (`composer uni:release`) to manage the monorepo release cycle:
  - Analyze Git history since the last release tag to determine changed packages and semver bumps (using Conventional Commits).
  - Act as a Manager: Call the low-level engine (`uni:version`) to update `composer.json` versions, resolve dependency constraints, and run tests.
  - Generate and update `CHANGELOG.md`.
  - Create the root release commit (`chore(release): publish`) and push Git tags.
  - *Synergy*: This pairs perfectly with the continuous delivery design of `uni:split`, which will automatically mirror these new tags to the split repositories via CI/CD.

## Git Split
- [x] Implement a feature to split monorepo packages into their own read-only remote repositories.
- [x] Could be implemented as a standalone command (e.g., `composer uni:split`) or via documentation/integration with existing splitters like `splitsh/lite` or GitHub Actions. (Resolved via documentation)

## Cross-Platform Compatibility
- Implement and test full support for Windows operating systems (currently symlinks and path resolution might be Unix-specific).
