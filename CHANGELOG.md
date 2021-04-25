# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [7.5.1] - 2021-04-25
### Fixed
- Some phpstan errors [#42], [#43]

## [7.5.0] - 2021-04-24
### Changed
- `isset($row->fieldname)` returns `true` in the following cases:
  - If `fieldname` is direct related table with a value. For example: `isset($comment->post)` returns `true` if `isset($comment->post_id)`.
  - If `fieldname` is a related table with many values. For example: `isset($post->comment)` always return true even if the post has zero comments.

## [7.4.2] - 2021-04-01
### Fixed
- The array syntax to update a table row (Like `$db->post[2] = ['title' => 'New title']; `) didn't work in all cases. This fix changed the return type of `Table::offsetSet` to `void` (previously it was `Row`). [#41]

## [7.4.1] - 2020-03-12
### Fixed
- Support for PHP 7.2 [#37]

## [7.4.0] - 2020-01-10
### Added
- `SelectAggregate` query allows to set not only fields but anything (math operations, for instance) and save the result as a column [#36]
- `Select` query has the `whereSprintf` and `orWhereSprintf` modifiers.
- New method `Table::get` To return a row from a table

### Deprecated
- Magic method to return rows from a table using a field. Use `$table->get(['slug' => 'value'])` instead `$table->slug('value')`.

## [7.3.6] - 2019-12-25
### Added
- New function `Database::clearCache()` to clear the cache of all tables

## [7.3.5] - 2019-12-12
### Added
- New function `getArray()` to SELECT queries, to return an array with data instead a `Row` or `RowCollection`.

### Fixed
- `NULL` values in `Json` and `Serializable` fields

## [7.3.4] - 2019-12-03
### Fixed
- Revert the bugfix added in v7.3.0 about not including `NULL` values on insert and fixed in a different way.
- Added `void` return types to some methods of `Field` class.

## [7.3.3] - 2019-12-01
### Fixed
- The field `Point` now use the function `ST_AsText` instead `asText` (that was deprecated and removed in MySql 8)

## [7.3.2] - 2019-11-28
### Added
- New method `bindValue()` to SELECT queries

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

[#36]: https://github.com/oscarotero/simple-crud/issues/36
[#37]: https://github.com/oscarotero/simple-crud/issues/37
[#41]: https://github.com/oscarotero/simple-crud/issues/41
[#42]: https://github.com/oscarotero/simple-crud/issues/42
[#43]: https://github.com/oscarotero/simple-crud/issues/43

[7.5.1]: https://github.com/oscarotero/simple-crud/compare/v7.5.0...v7.5.1
[7.5.0]: https://github.com/oscarotero/simple-crud/compare/v7.4.2...v7.5.0
[7.4.2]: https://github.com/oscarotero/simple-crud/compare/v7.4.1...v7.4.2
[7.4.1]: https://github.com/oscarotero/simple-crud/compare/v7.4.0...v7.4.1
[7.4.0]: https://github.com/oscarotero/simple-crud/compare/v7.3.6...v7.4.0
[7.3.6]: https://github.com/oscarotero/simple-crud/compare/v7.3.5...v7.3.6
[7.3.5]: https://github.com/oscarotero/simple-crud/compare/v7.3.4...v7.3.5
[7.3.4]: https://github.com/oscarotero/simple-crud/compare/v7.3.3...v7.3.4
[7.3.3]: https://github.com/oscarotero/simple-crud/compare/v7.3.2...v7.3.3
[7.3.2]: https://github.com/oscarotero/simple-crud/compare/v7.3.1...v7.3.2
[7.3.1]: https://github.com/oscarotero/simple-crud/compare/v7.3.0...v7.3.1
[7.3.0]: https://github.com/oscarotero/simple-crud/compare/v7.2.5...v7.3.0
[7.2.5]: https://github.com/oscarotero/simple-crud/compare/v7.2.4...v7.2.5
[7.2.4]: https://github.com/oscarotero/simple-crud/compare/v7.2.3...v7.2.4
[7.2.3]: https://github.com/oscarotero/simple-crud/compare/v7.2.2...v7.2.3
[7.2.2]: https://github.com/oscarotero/simple-crud/compare/v7.2.1...v7.2.2
[7.2.1]: https://github.com/oscarotero/simple-crud/compare/v7.2.0...v7.2.1
[7.2.0]: https://github.com/oscarotero/simple-crud/compare/v7.1.0...v7.2.0
[7.1.0]: https://github.com/oscarotero/simple-crud/compare/v7.0.0...v7.1.0
