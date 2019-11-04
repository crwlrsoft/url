# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
