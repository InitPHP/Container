# Changelog

All notable changes to `initphp/container` are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The CI matrix now also covers PHP 8.6. The `php` constraint is unchanged
  (`"php": "^8.2"`).

## [2.0.1] - 2026-11-25

### Documentation

- Documented what every route into an interface or a non-instantiable class
  produces (README, Exceptions, Autowiring, Binding & Factories, Limitations and
  the interface-binding recipe): `get()` on an unbound interface throws
  `NotFoundException`; as a required dependency it throws
  `DependencyHasNoDefaultValueException`; as an optional one `null` or the default
  is injected. `set(Interface::class)` with no concrete class, or an alias to
  another interface, is not a binding: the name is stored as a plain string.
- `DependencyIsNotInstantiableException` is documented for abstract classes, enums
  and non-public constructors on every route where the class has to be built
  (`get()`, a required dependency, a binding's concrete class; an optional
  dependency falls back to `null` or its default instead), and no longer for an
  unbound interface. The docblock of the class was corrected the same way; there is no
  behaviour change.

## [2.0.0] - 2026-11-22

### Changed

- **BREAKING:** Raised the minimum PHP version to 8.2 (`"php": "^8.2"`). The
  CI matrix now covers PHP 8.2, 8.3, 8.4 and 8.5.
- **BREAKING:** The code style moved to PER-CS, enforced by the shared
  PHP-CS-Fixer configuration (`.php-cs-fixer.dist.php`). The source was
  reformatted accordingly; there are no behaviour changes.
