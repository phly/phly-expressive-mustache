# Changelog

All notable changes to this project will be documented in this file, in reverse
chronological order by release.

## 1.0.1 - 2015-12-08

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/phly/phly-expressive-mustache/pull/3) updates the
  zendframework/zend-expressive-helpers dependency to 1.2 to ensure no circular
  dependency issues occur.

## 1.0.0 - 2015-12-07

### Added

- [#2](https://github.com/phly/phly-expressive-mustache/pull/2) adds the new
  `UriHelper` class, which implements a higher order section for rendering
  route-based URIs inside of templates.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#2](https://github.com/phly/phly-expressive-mustache/pull/2) replaces the
  zend-expressive dependency with:
  - zendframework/zend-expressive-template (which contains the
    `TemplateRendererInterface` and traits used by `MustacheTemplate`)
  - zendframework/zend-expressive-helpers (which provides the `UrlHelper` on
    which the new `UriHelper` depends)

## 0.3.0 - 2015-10-27

### Added

- `MustacheTemplate::addParamsListener()` allows you to provide a callable for
  merging default template parameters with those provided at `render()`; this is
  particularly useful when considering view model objects, which may need custom
  logic in order to merge such variables.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to zend-expressive 1.0.

## 0.2.0 - 2015-09-04

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to zend-expressive 0.2.

## 0.1.1 - 2015-09-02

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixed an "undefined variable" error when injecting paths via the MustacheTemplateFactory.

## 0.1.0 - 2015-09-02

### Added

- Everything:
  - `Phly\Expressive\Mustache\MustacheTemplate` provides an Expressive-compatible Mustache adapter.
  - `Phly\Expressive\Mustache\MustacheTemplateFactory` provides a
    configuration-driven container-interop factory for creating a
    `MustacheTemplate` instance.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
