# Monorepo Split

When working with a monorepo, you often want to split your individual packages into separate, read-only remote repositories. This allows users to require only the specific packages they need without downloading the entire monorepo.

Unicorn provides a native `uni:split` command to automate this process completely, allowing for zero-configuration auto-discovery of your local packages.

## How it works

The `composer uni:split` command uses the blazing-fast `splitsh-lite` utility under the hood. When executed, it will:
1. Automatically download the correct `splitsh-lite` binary to Composer's global cache directory.
2. Discover all local packages in your monorepo.
3. Extract the commit history for each package.
4. Push the history directly to the target remote repositories via `git push`.

## Configuration

There are two ways to configure the split behavior depending on your needs: **Global (Opt-out)** or **Per-Package (Opt-in)**.

### 1. Global Pattern (Opt-out)

If you want all packages to be split by default, add the `uni-split` configuration to your root `composer.json`:

```json
{
    "extra": {
        "uni-split": {
            "remote-pattern": "https://oauth2:${GITLAB_TOKEN}@gitlab.com/acme/{name}.git",
            "remote-branch": "main"
        }
    }
}
```

- **`remote-pattern`**: A URL template. The `{name}` placeholder will be automatically replaced with the short name of the package (e.g., if the package is `acme/logger`, it uses `logger`).
- **`remote-branch`**: The target branch (defaults to `main`).
- **`remote-tag-prefix`**: The prefix for git tags (defaults to `v`). Set to `""` (empty string) to push tags without the `v` prefix.

With this setup, any new package you add to `packages/` will be automatically split without any extra configuration!

#### Opting out
If you have a private package that you do NOT want to split, simply disable it in that specific package's `composer.json`:
```json
{
    "name": "acme/private-app",
    "extra": {
        "uni-split": false
    }
}
```

### 2. Per-Package Config (Opt-in)

If you prefer to explicitly choose which packages get split (or if a package needs a completely different URL/branch), do NOT add the global pattern to the root `composer.json`. Instead, configure `uni-split` directly in the specific package's `composer.json`:

```json
{
    "name": "acme/logger",
    "extra": {
        "uni-split": {
            "remote-pattern": "https://oauth2:${GITLAB_TOKEN}@gitlab.com/acme/custom-logger.git",
            "remote-branch": "master"
        }
    }
}
```

> [!TIP]
> **Environment Variable Expansion**
> You can use environment variables (e.g., `${GITLAB_TOKEN}` or `${GITHUB_TOKEN}`) in any URL. This allows you to keep credentials safe and secure in your CI/CD secrets without committing them to the repository.

### Tag Pushing

Because `unicorn` requires all local packages to have a `version` field in their `composer.json`, `uni:split` will automatically push this version as a Git tag to the target repository (e.g., `v1.0.0`) during the split process!

### 3. CI/CD Integration (e.g. GitHub Actions)

The best place to run this command is from a CI/CD pipeline after a successful release or merge to the main branch.

```yaml
name: "Packages Split"

on:
  push:
    branches:
      - main
    tags:
      - '*'

jobs:
  split:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          # Fetch all history so that the split process works correctly
          fetch-depth: 0

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer

      - name: Install Unicorn
        run: |
          composer global config allow-plugins.steady-ua/unicorn true
          composer global require steady-ua/unicorn

      - name: Split Packages
        env:
          GITLAB_TOKEN: ${{ secrets.GITLAB_TOKEN }}
        run: composer uni:split -v
```

## Supported Providers

Because Unicorn simply executes `git push` to the URL provided in your `remote-pattern` config, it supports **all Git providers** (GitHub, GitLab, Bitbucket, Gitea, etc.). All you need is the correct authentication format supported by your provider (HTTPS with tokens, or SSH URLs if your CI environment has the SSH keys configured).
