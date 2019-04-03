# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## 7.0.0 - 2019-04-03
This library was rewritten and a lot of breaking changes were included.

### Added
- This changelog

### Changed
- Minimum requirement is `php >= 7.2`
- Added `Atlas.Pdo` as dependency
- Use [PSR-14 Event Dispatcher](https://www.php-fig.org/psr/psr-14/) to handle events
- Added `Atlas.Query` as a dependency to create the queries and adopt its API
- The pagination info is returned with `$selectQuery->getPageInfo()` function.
- Many other changes. See the docs.
