# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [7.3.1] - 2019-11-28
### Added
- New method `orIgnore()` to INSERT queries, to ignore the insert on duplicate keys

### Fixed
- Ignore duplications on insert many-to-many relations, instead throw an exception

## [7.3.0] - 2019-11-28
### Added
- Queries have the method `get` as an alias of `run`.
- New method `Table::getOrCreate()`.
- Magic methods to select a row by other unique keys than id. For example `$db->post->slug('post-slug')`.
- New method `whereEquals` to SELECT queries.

### Fixed
- Do not include `NULL` values on insert, in order to generate defaults values in the database (for example for TIMESTAMP)

## [7.2.5] - 2019-11-11
### Fixed
- Exception thrown on load relations including NULL values

## [7.2.4] - 2019-11-07
### Added
- Row::id() to return the id of the row (and generate one if it's empty)

### Fixed
- Execute Row::relate() when the row is not saved (and does not have an id)

## [7.2.3] - 2019-11-07
### Fixed
- Allow to assign names to the field `Field` factory

## [7.2.2] - 2019-11-07
### Fixed
- Allow to override the default constants `Table::ROW_CLASS` and `Table::ROWCOLLECTION_CLASS`

## [7.2.1] - 2019-08-31
### Fixed
- FieldFactories are not initialized in the database constructor.

## [7.2.0] - 2019-08-25
### Added
- New method `Row::reload()` refresh the data from the database and, optionally, discard changes.
- New argument to `RowCollection::toArray()` to convert only the collection but not the rows.
- Provided a basic event dispatcher
- Added the `Table::init()` method to run custom code after instantation.

### Fixed
- Field `Serialize` returns `NULL` if the value is not a string.
- `NULL` values on insert.
- Update a row with no changes.

## [7.1.0] - 2019-08-23
### Changed
- BREAKING: The way to define custom fields has changed in order to make it more easy and less verbose.

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

[7.3.1]: https://github.com/oscarotero/simple-crud/compare/v7.3.0...v7.3.1
[7.3.0]: https://github.com/oscarotero/simple-crud/compare/v7.2.5...v7.3.0
[7.2.5]: https://github.com/oscarotero/simple-crud/compare/v7.2.4...v7.2.5
[7.2.4]: https://github.com/oscarotero/simple-crud/compare/v7.2.3...v7.2.4
[7.2.3]: https://github.com/oscarotero/simple-crud/compare/v7.2.2...v7.2.3
[7.2.2]: https://github.com/oscarotero/simple-crud/compare/v7.2.1...v7.2.2
[7.2.1]: https://github.com/oscarotero/simple-crud/compare/v7.2.0...v7.2.1
[7.2.0]: https://github.com/oscarotero/simple-crud/compare/v7.1.0...v7.2.0
[7.1.0]: https://github.com/oscarotero/simple-crud/compare/v7.0.0...v7.1.0
