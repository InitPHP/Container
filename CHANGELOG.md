# Changelog

All notable changes to `initphp/container` are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **BREAKING:** Raised the minimum PHP version to 8.2 (`"php": "^8.2"`). The
  CI matrix now covers PHP 8.2, 8.3, 8.4 and 8.5.
- **BREAKING:** The code style moved to PER-CS, enforced by the shared
  PHP-CS-Fixer configuration (`.php-cs-fixer.dist.php`). The source was
  reformatted accordingly; there are no behaviour changes.
