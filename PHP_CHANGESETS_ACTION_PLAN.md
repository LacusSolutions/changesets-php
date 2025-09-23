# PHP Changesets - Detailed Action Plan

## Executive Summary

This document outlines a comprehensive plan to create a PHP version of the JavaScript `changesets` tool, adapted for PHP environments using Composer as the package manager and Git as the versioning tool. The goal is to provide the same functionality and workflow benefits that changesets offers to JavaScript/Node.js projects, but tailored for PHP development practices.

## Understanding the Original JavaScript Changesets

### Core Concept
Changesets is a tool for managing versioning and changelogs with a focus on multi-package repositories. It allows contributors to declare an "intent to release" packages at particular semver bump types with a summary of changes made.

### Key Features of JS Changesets

1. **Changeset Creation**: Interactive CLI to create changeset files with package selection, semver bump types, and change descriptions
2. **Version Management**: Combines multiple changesets into single releases per package, handling internal dependencies
3. **Changelog Generation**: Automatically creates/updates CHANGELOG.md files
4. **Publishing**: Handles npm publishing with proper tagging
5. **Monorepo Support**: Manages complex interdependencies in multi-package repositories
6. **Pre-release Support**: Handles pre-release versions and snapshots
7. **Configuration**: Flexible configuration for different workflows
8. **Git Integration**: Full Git integration for tagging, commit detection, and file tracking

### Architecture Overview

The JS version consists of multiple packages:
- `@changesets/cli` - Main CLI interface
- `@changesets/types` - Shared type definitions
- `@changesets/config` - Configuration management
- `@changesets/git` - Git operations
- `@changesets/assemble-release-plan` - Release planning logic
- `@changesets/apply-release-plan` - Applying version changes
- `@changesets/write` - File writing operations
- `@changesets/read` - File reading operations
- `@changesets/parse` - Changeset parsing
- `@changesets/get-dependents-graph` - Dependency graph management
- `@changesets/changelog-*` - Changelog generators
- `@changesets/errors` - Error handling
- `@changesets/logger` - Logging utilities

## PHP Environment Analysis

### PHP Ecosystem Characteristics

1. **Package Management**: Composer is the standard package manager
2. **Versioning**: Uses semantic versioning (semver) similar to npm
3. **Monorepo Support**: Limited compared to JavaScript, but growing with tools like Composer workspaces
4. **CLI Tools**: PHP has robust CLI capabilities with Symfony Console, Laravel Artisan, etc.
5. **Configuration**: YAML, JSON, and PHP array configurations are common
6. **Git Integration**: PHP has good Git integration through libraries like GitWrapper

### Key Differences from JavaScript

1. **Package Discovery**: Composer uses `composer.json` files instead of `package.json`
2. **Dependency Resolution**: Composer's dependency resolution is different from npm
3. **Publishing**: Packagist is the primary registry, not npm
4. **Workspace Support**: Composer workspaces are less mature than npm workspaces
5. **CLI Distribution**: PHP tools are typically distributed via Composer, not npm

## Proposed PHP Architecture

### Core Components

#### 1. Main CLI Package (`lacus/changesets-cli`)
- Symfony Console-based CLI interface
- Command structure mirroring JS version:
  - `init` - Initialize changesets in a project
  - `add` - Create new changeset
  - `version` - Apply changesets and update versions
  - `publish` - Publish packages (placeholder for future Packagist integration)
  - `status` - Show changeset status
  - `pre` - Pre-release management

#### 2. Core Packages

**`lacus/changesets-types`**
- PHP classes/interfaces defining core data structures
- Changeset, Release, Package, Config types
- Semver version handling

**`lacus/changesets-config`**
- Configuration management for `.changeset/config.php`
- Support for YAML, JSON, and PHP array configs
- Validation and defaults

**`lacus/changesets-git`**
- Git operations using GitWrapper or similar
- File tracking, commit detection, tagging
- Branch and ref management

**`lacus/changesets-assemble-release-plan`**
- Release planning logic
- Dependency graph analysis
- Version calculation and conflict resolution

**`lacus/changesets-apply-release-plan`**
- Apply version changes to composer.json files
- Update changelog files
- Handle internal dependencies

**`lacus/changesets-write`**
- Changeset file creation
- Changelog writing
- File formatting and prettification

**`lacus/changesets-read`**
- Changeset file parsing
- Composer.json reading
- Configuration loading

**`lacus/changesets-parse`**
- Changeset YAML front matter parsing
- Markdown processing
- Validation

**`lacus/changesets-get-dependents-graph`**
- Composer dependency analysis
- Internal package dependency tracking
- Graph traversal for version updates

**`lacus/changesets-changelog-*`**
- Changelog generation plugins
- GitHub integration
- Git-based changelog generation

**`lacus/changesets-errors`**
- Custom exception classes
- Error handling utilities

**`lacus/changesets-logger`**
- Logging interface
- Console output formatting

### File Structure

```
changesets/
├── packages/
│   ├── cli/                    # Main CLI package (lacus/changesets-cli)
│   ├── types/                  # Core type definitions (lacus/changesets-types)
│   ├── config/                 # Configuration management (lacus/changesets-config)
│   ├── git/                    # Git operations (lacus/changesets-git)
│   ├── assemble-release-plan/  # Release planning (lacus/changesets-assemble-release-plan)
│   ├── apply-release-plan/     # Version application (lacus/changesets-apply-release-plan)
│   ├── write/                  # File writing (lacus/changesets-write)
│   ├── read/                   # File reading (lacus/changesets-read)
│   ├── parse/                  # Parsing utilities (lacus/changesets-parse)
│   ├── get-dependents-graph/   # Dependency analysis (lacus/changesets-get-dependents-graph)
│   ├── changelog-git/          # Git changelog generator (lacus/changesets-changelog-git)
│   ├── changelog-github/       # GitHub changelog generator (lacus/changesets-changelog-github)
│   ├── errors/                 # Error handling (lacus/changesets-errors)
│   └── logger/                 # Logging (lacus/changesets-logger)
├── docs/                       # Documentation
├── tests/                      # Test suite
├── composer.json               # Root composer.json
└── README.md
```

## Detailed Implementation Plan

### Phase 1: Foundation (Weeks 1-2)

#### 1.1 Project Setup
- [ ] Create monorepo structure with Composer workspaces
- [ ] Set up PHPUnit testing framework
- [ ] Configure PHPStan for static analysis
- [ ] Set up CI/CD pipeline (GitHub Actions)
- [ ] Create basic documentation structure
- [ ] Configure package naming: `lacus/changesets-*` for all packages

#### 1.2 Core Types Package
- [ ] Define core interfaces and classes:
  - `Changeset` class
  - `Release` class
  - `Package` class
  - `Config` class
  - `VersionType` enum
  - `AccessType` enum
- [ ] Implement semver version handling
- [ ] Create comprehensive test suite

#### 1.3 Configuration Package
- [ ] Implement configuration loading from multiple formats
- [ ] Create configuration validation
- [ ] Define default configuration
- [ ] Support for `.changeset/config.php`, `.changeset/config.yaml`, `.changeset/config.json`

### Phase 2: Core Functionality (Weeks 3-4)

#### 2.1 Git Integration Package (`lacus/changesets-git`)
- [ ] Implement Git operations using GitWrapper
- [ ] File tracking and change detection
- [ ] Commit and tag management
- [ ] Branch and ref operations
- [ ] Shallow clone handling

#### 2.2 Parsing Package (`lacus/changesets-parse`)
- [ ] YAML front matter parsing for changesets
- [ ] Markdown processing
- [ ] Changeset validation
- [ ] Composer.json parsing

#### 2.3 Read/Write Packages (`lacus/changesets-read`, `lacus/changesets-write`)
- [ ] Changeset file reading and writing
- [ ] Composer.json manipulation
- [ ] Changelog file handling
- [ ] File formatting and prettification

### Phase 3: Release Management (Weeks 5-6)

#### 3.1 Dependency Graph Package (`lacus/changesets-get-dependents-graph`)
- [ ] Composer dependency analysis
- [ ] Internal package discovery
- [ ] Dependency graph construction
- [ ] Graph traversal algorithms

#### 3.2 Assemble Release Plan Package (`lacus/changesets-assemble-release-plan`)
- [ ] Changeset processing logic
- [ ] Version calculation
- [ ] Dependency resolution
- [ ] Conflict detection and resolution
- [ ] Pre-release handling

#### 3.3 Apply Release Plan Package (`lacus/changesets-apply-release-plan`)
- [ ] Version application to composer.json files
- [ ] Changelog generation and updating
- [ ] Internal dependency updates
- [ ] File cleanup

### Phase 4: CLI Interface (Weeks 7-8)

#### 4.1 CLI Package (`lacus/changesets-cli`)
- [ ] Symfony Console application setup
- [ ] Command implementations:
  - `init` command
  - `add` command with interactive prompts
  - `version` command
  - `status` command
  - `pre` command
- [ ] Configuration integration
- [ ] Error handling and user feedback

#### 4.2 Changelog Generators (`lacus/changesets-changelog-git`, `lacus/changesets-changelog-github`)
- [ ] Git-based changelog generator
- [ ] GitHub integration changelog generator
- [ ] Plugin system for custom generators

### Phase 5: Advanced Features (Weeks 9-10)

#### 5.1 Monorepo Support
- [ ] Composer workspace detection
- [ ] Multi-package repository handling
- [ ] Cross-package dependency management
- [ ] Workspace-aware operations

#### 5.2 Publishing Integration
- [ ] Packagist publishing preparation
- [ ] Git tag creation
- [ ] Release artifact generation
- [ ] Publishing workflow automation

#### 5.3 Advanced Configuration
- [ ] Custom changelog formats
- [ ] Commit message templates
- [ ] Pre-release templates
- [ ] Snapshot releases

### Phase 6: Testing and Documentation (Weeks 11-12)

#### 6.1 Comprehensive Testing
- [ ] Unit tests for all packages
- [ ] Integration tests
- [ ] End-to-end tests
- [ ] Performance testing
- [ ] Cross-platform testing

#### 6.2 Documentation
- [ ] Complete API documentation
- [ ] User guides and tutorials
- [ ] Migration guides from other tools
- [ ] Best practices documentation
- [ ] Examples and use cases

## Technical Considerations

### PHP Version Support
- Minimum PHP 8.1 for modern features
- Support for PHP 8.2 and 8.3
- Use of typed properties and attributes

### Dependencies
- **Symfony Console**: CLI framework
- **GitWrapper**: Git operations
- **Symfony Yaml**: YAML parsing
- **Composer**: Package management integration
- **Semver**: Version handling
- **Monolog**: Logging
- **PHPUnit**: Testing

### Configuration Format

```php
// .changeset/config.php
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

### Changeset File Format

```markdown
---
"vendor/package-name": major
"vendor/another-package": minor
---

Description of the changes made.
```

## Pros and Cons Analysis

### Pros

1. **Familiar Workflow**: PHP developers get the same benefits as JS developers
2. **Composer Integration**: Native integration with PHP's package ecosystem
3. **Monorepo Support**: Better support for PHP monorepos than existing tools
4. **Extensibility**: Plugin system for custom changelog generators
5. **Git Integration**: Full Git workflow integration
6. **Type Safety**: PHP 8+ type system for better reliability
7. **Performance**: PHP can be faster than Node.js for file operations

### Cons

1. **Ecosystem Maturity**: PHP tooling ecosystem is less mature than JavaScript
2. **Monorepo Adoption**: PHP monorepos are less common than JS monorepos
3. **Publishing Integration**: Packagist integration is more complex than npm
4. **CLI Distribution**: Composer-based distribution is less convenient than npm
5. **Learning Curve**: New tool for PHP developers to learn
6. **Maintenance**: Additional tool to maintain and update

## Migration Strategy

### From Existing Tools

1. **From Conventional Commits**: Provide migration tool to convert commit messages to changesets
2. **From Manual Versioning**: Gradual adoption with existing workflows
3. **From Other Monorepo Tools**: Migration scripts for common tools

### Adoption Strategy

1. **Start with Single Packages**: Begin with simple single-package repositories
2. **Gradual Monorepo Adoption**: Expand to monorepos as tool matures
3. **Community Engagement**: Open source development with community feedback
4. **Documentation and Examples**: Comprehensive guides and real-world examples

## Success Metrics

1. **Adoption Rate**: Number of projects using the tool
2. **Community Engagement**: GitHub stars, issues, contributions
3. **Feature Completeness**: Parity with JS changesets
4. **Performance**: Speed of operations compared to JS version
5. **Reliability**: Bug reports and stability metrics
6. **Documentation Quality**: User feedback on documentation

## Risk Mitigation

1. **Technical Risks**:
   - Composer integration complexity → Extensive testing and fallback options
   - Git operation reliability → Use proven Git libraries
   - Performance issues → Benchmarking and optimization

2. **Adoption Risks**:
   - Low initial adoption → Strong documentation and examples
   - Competition from existing tools → Focus on unique value proposition
   - Maintenance burden → Community involvement and clear contribution guidelines

3. **Ecosystem Risks**:
   - Composer changes → Version compatibility testing
   - PHP version support → Clear version requirements
   - Breaking changes → Semantic versioning and migration guides

## Package Naming Convention

All packages will follow the `lacus/changesets-*` naming pattern:

- **Main CLI**: `lacus/changesets-cli`
- **Core Types**: `lacus/changesets-types`
- **Configuration**: `lacus/changesets-config`
- **Git Operations**: `lacus/changesets-git`
- **Release Planning**: `lacus/changesets-assemble-release-plan`
- **Version Application**: `lacus/changesets-apply-release-plan`
- **File Operations**: `lacus/changesets-read`, `lacus/changesets-write`
- **Parsing**: `lacus/changesets-parse`
- **Dependencies**: `lacus/changesets-get-dependents-graph`
- **Changelog Generators**: `lacus/changesets-changelog-git`, `lacus/changesets-changelog-github`
- **Utilities**: `lacus/changesets-errors`, `lacus/changesets-logger`

## Conclusion

The PHP changesets project represents a significant opportunity to bring the proven benefits of the JavaScript changesets tool to the PHP ecosystem. While there are challenges related to the different nature of PHP development and package management, the core concepts translate well and can provide substantial value to PHP developers working on both single packages and monorepos.

The phased approach allows for iterative development and community feedback, while the comprehensive feature set ensures parity with the JavaScript version. Success will depend on careful attention to PHP-specific requirements, strong community engagement, and robust documentation and examples.

The project should be developed as an open-source initiative with clear contribution guidelines and a focus on long-term maintainability and community growth.
