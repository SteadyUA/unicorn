# unicorn

The `composer` plugin organizes a mono-repository of php packages.\
Ensures the consistency of all dependencies on the same package version.\
Adds tools for working with shared dependencies.

1. [Concept](#concept)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Commands](#commands)
5. [The unicorn.json shema](#the-unicornjson-schema)

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
Use the `--copy` option of command [composer uni:install](#composer-uniinstall)

## Installation

Compatible with `composer` version `2.3` or later.\
Currently does not work on `windows` operating system.\
The plugin must be installed globally.
```
composer global require steady-ua/unicorn
```

## Usage

After creating the `unicorn.json` file, just use `composer` as usual.

The `unicorn.lock` file is recommended to be kept under a version control system (eg GIT).
The `uni_vendor` directory, like the `vendor` directories, is recommended to be excluded.
The generated `composer.lock` files should also be excluded.

If a version conflict occurs when including a dependent package, an error will be displayed.

Use next commands to solve problems.

## Commands

### composer uni:install

    composer uni:install [options] [--] [<packages>...]

When called without parameters. If the `uni_vendor` directory does not exist, install all dependencies.
Otherwise, it will check the consistency of package requirements.

You can specify a list of local packages. In this case, for each package, the command will be executed
`composer install`

#### Options:
- `-a, --all` Run command for all local packages.
- `--copy` Packages will be copied instead of symlinks.\
Only allowed when packages are specified.\
It is [possible to set options](#copy-install-options) for executing the command `composer install`
Useful for deployment.
- `--no-scripts` Whether to prevent execution of all defined scripts.

### composer uni:update

    compose uni:update <packages>...

Update required packages in all dependents.

With this command, you can also change the constraint. e.g. `foo/bar:^1.0 or foo/bar=^1.0 or "foo/bar ^1.0"`.
This will edit the `composer.json` files.

During the execution, the `composer.json` files will be changed.\
In case of an error, the files will be returned to their original state.

Additionally, [you can specify](#post-update-scripts) a list of scripts that will be executed after the changes.

### composer uni:version

    uni:version [ major | minor | patch ]

Bump version of package.

Upgrades a package and updates all dependencies.

During the execution, the `composer.json` files will be changed.\
In case of an error, the files will be returned to their original state.

Additionally, [you can specify](#post-update-scripts) a list of scripts that will be executed after the changes.

### composer uni:run-script

    uni:run-script [options] [--] [<script>...]

Runs the scripts defined in unicorn.json, for all packages dependent on the current.

#### Options:
- `-s, --self` Also run script for the current package.
- `-r, --recursive` Recursively resolves dependencies up to the root.
- `-a, --all` Run for all local packages.


### composer uni:depends
    
Shows which packages cause the given package to be installed.

### composer uni:prohibits

Shows which packages prevent the given package from being installed.

### composer uni:show

Shows information about packages.

### composer uni:suggest

Suggest package by specified namespace.

## The unicorn.json schema

Located at the root of the mono-repository.

### repositories

Custom package repositories to use.

The format is the same as [Composer](https://getcomposer.org/doc/04-schema.md#repositories)

Example:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./web-api-project"
        },
        {
            "type": "path",
            "url": "./packages/*"
        }
    ]
}
```
> You can group packages into subdirectories.\
> Then the url will be like this `"url": "./packages/*/*"`

### extra

#### copy-install-options
Type: string

Additional options to be passed to the command `composer install` in copy mode [uni:install](#composer-uniinstall)

Example:
```json
{
    "extra": {
        "copy-install-options": "--no-dev --optimize-autoloader"
    }
}
```

#### post-update-scripts
Type: array

The name of the scripts that will be executed for all changed packages during the execution of commands: [uni:update](#composer-uniupdate), [uni:version](#composer-universion).

Example:
```json
{
    "extra": {
        "post-update-scripts": ["test", "phpstan"]
    }
}
```
