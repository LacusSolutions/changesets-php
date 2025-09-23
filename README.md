# PHP Changesets

A tool for managing versioning and changelogs in PHP projects, inspired by the JavaScript [changesets](https://github.com/changesets/changesets) tool.

## Overview

PHP Changesets provides a workflow for managing versioning and changelogs in PHP projects, with special support for monorepos using Composer workspaces. It allows contributors to declare an "intent to release" packages at particular semver bump types with a summary of changes made.

## Features

- **Changeset Creation**: Interactive CLI to create changeset files with package selection, semver bump types, and change descriptions
- **Version Management**: Combines multiple changesets into single releases per package, handling internal dependencies
- **Changelog Generation**: Automatically creates/updates CHANGELOG.md files
- **Monorepo Support**: Manages complex interdependencies in multi-package repositories
- **Pre-release Support**: Handles pre-release versions and snapshots
- **Configuration**: Flexible configuration for different workflows
- **Git Integration**: Full Git integration for tagging, commit detection, and file tracking

## Installation

```bash
composer require lacus/changesets-cli
```

## Quick Start

1. Initialize changesets in your project:
```bash
vendor/bin/changesets init
```

2. Create a changeset:
```bash
vendor/bin/changesets add
```

3. Apply changesets and update versions:
```bash
vendor/bin/changesets version
```

## Configuration

Create a `.changeset/config.php` file in your project root:

```php
<?php

return [
    'changelog' => ['lacus/changesets-changelog-git', ['repo' => 'user/repo']],
    'commit' => false,
    'access' => 'public',
    'baseBranch' => 'main',
    'updateInternalDependencies' => 'patch',
    'ignore' => [],
    'prettier' => true,
    'privatePackages' => [
        'version' => true,
        'tag' => false,
    ],
];
```

## Changeset Format

Changesets are markdown files with YAML front matter:

```markdown
---
"vendor/package-name": major
"vendor/another-package": minor
---

Description of the changes made.
```

## Commands

- `init` - Initialize changesets in a project
- `add` - Create new changeset
- `version` - Apply changesets and update versions
- `status` - Show changeset status
- `pre` - Pre-release management

## Development

This is a monorepo containing multiple packages:

- `lacus/changesets-cli` - Main CLI interface
- `lacus/changesets-types` - Core type definitions
- `lacus/changesets-config` - Configuration management
- `lacus/changesets-git` - Git operations
- `lacus/changesets-parse` - Parsing utilities
- `lacus/changesets-read` - File reading operations
- `lacus/changesets-write` - File writing operations
- And more...

## Testing

```bash
composer test
```

## License

MIT
