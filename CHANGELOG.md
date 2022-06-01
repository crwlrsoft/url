# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2022-06-01
### Added
- The new `queryString()` method can be used to access and
  manipulate the query via a `Query` object when the
  `crwlr/query-string` package is installed.

### Changed
- Update schemes and suffixes lists.

## [1.1.1] - 2022-01-12
### Fixed
- Resolving relative paths without leading slash against a
  base url with an empty path.

### Changed
- Update schemes and suffixes lists.

## [1.1.0] - 2021-01-10
### Added
- Static method to create PSR-7 Uri object
  (`Url::parsePsr7('https://...')`).

### Fixed
- Error when resolving something to a url with an empty path.

## [1.0.2] - 2022-01-05
### Changed
- Run tests also on PHP 8.1 in CI.
- Update schemes and suffixes lists.

## [1.0.1] - 2021-01-04
### Fixed
- Support for PHP 8.0
    - Minor change in Validator because output of PHP's
      parse_url is different when an url includes a
      delimiter for query or fragment but has no actual query
      or fragment (followed by empty string).
    - Change PHP version requirement in composer.json.
    - Only relevant for development: Temporarily add
      PHP_CS_FIXER_IGNORE_ENV=1 to `composer cs` command
      until PHP Coding Standards Fixer fully supports 
      PHP 8.0.
- Getting standard ports for schemes on systems where
  /etc/services file is missing. PHP's getservbyname()
  function uses that file and when it's missing the function
  returns false for all schemes. Fixed that by having a list
  within the package.

## [1.0.0] - 2020-05-11

### Added
- Adapter class Uri that implements the PSR-7 `UriInterface`.
- New methods in `Url` class:
    - `authority`: Get or set the full authority part of 
      the url.
    - `userInfo`: Get or set the full userInfo part of the 
      url.
    - `isRelativeReference`: Returns true when the current
      url is a relative reference.
    - `hasIdn`: Returns true when the current url contains
      an internationalized domain name in the host
      component.
    - `isEqualTo`: Compare the current url to another one.
    - `isComponentEqualIn`: Compare some component of the
      current url to the same component in another url.
      Also with separate methods for all available
      components:
        - `isSchemeEqualIn`
        - `isAuthorityEqualIn`
        - `isUserEqualIn`
        - `isPasswordEqualIn`
        - `isUserInfoEqualIn`
        - `isHostEqualIn`
        - `isDomainEqualIn`
        - `isDomainLabelEqualIn`
        - `isDomainSuffixEqualIn`
        - `isSubdomainEqualIn`
        - `isPortEqualIn`
        - `isPathEqualIn`
        - `isQueryEqualIn`
        - `isFragmentEqualIn`
- New static validation methods in `Validator`:
    - `authority`
    - `authorityComponents`
    - `userInfo`
    - `userInfoComponents`
    - `user`
    - `password` (alias method `pass`)
    - `domainLabel`
    - `callValidationByComponentName`
- Extracted parsing the host part (and registrable domain if
  contained) to separate classes `Host` and `Domain`.
- New class `Helpers` with some static helper methods that
  are used in multiple several classes. Also static access
  to instances of classes `Suffixes` and `Schemes`.
- New `InvalidUrlComponentException` that is thrown when
  you're trying to set an invalid new value for some 
  component.

### Changed
- Required PHP version is now 7.2 because PHP 7.0 and 7.1 are
  no longer actively supported.
- Instances of the Url class can now be created from relative
  references (without scheme). In v0.1 creating a new instance
  from a relative reference threw an Exception. If your 
  application expects this behavior, you can use the 
  `isRelativeReference` method of the `Url` object to find out
  if the url in question is a relative reference.
- All methods in `Validator` are now static and all the
  component validation methods (scheme, host,...) now return
  `null` instead of `false` for invalid values.
  Further Validating a full url was split into 4 different
  methods:
    - `url`: Returns the validated url as string if input is
      valid (`null` if invalid).
    - `urlAndComponents`: Returns an array with validated url
      as string and all single validated url components (
      `null` if invalid).
    - `absoluteUrl`: Same as `url` but only absolute urls are
      considered valid.
    - `absoluteUrlAndComponents`: Same as `urlAndComponents`
      but only absolute urls are valid.
- Switch to `idn_to_ascii` and `idn_to_utf8` (respectively
  [symfony/polyfill-intl-idn](https://packagist.org/packages/symfony/polyfill-intl-idn)
  ) to handle parse internationalized domain names. 
- `InvalidUrlException` now extends `UnexpectedValueException`
  instead of `Exception`.
- Class `Store` is now abstract.

### Removed
- Method `compare` in `Url`. Use `isEqualTo` or the other new
  comparison methods listed under "Added" above.
- Class `Parser`. Most still needed code is moved to `Helpers`
  class.
- Move static method `getStandardPortByScheme` from class 
  `Url` to class `Helpers`.
- Method `userOrPassword` in `Validator`. Use methods `user`
  or `password` (`pass`) instead.

### Fixed
- Version 0.1 had an issue that path, query or fragment could 
  have been double encoded in some cases. This shouldn't
  happen anymore (see method `encodePercentCharacter` in
  `Validator`).
