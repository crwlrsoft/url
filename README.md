<p align="center"><a href="https://www.crwlr.software" target="_blank"><img src="https://github.com/crwlrsoft/graphics/blob/eee6cf48ee491b538d11b9acd7ee71fbcdbe3a09/crwlr-logo.png" alt="crwlr.software logo" width="260"></a></p>

# A Swiss Army knife for URLs

This package is for you when PHP's parse_url() is not enough.

__Key Features:__
* __Parse a URL__ and access or modify all its __components__ separately.
* Resolve any __relative reference__ you may find in an HTML document __to an
absolute URL__, based on the document's URL.
* Get not only the full __host__ of a URL, but also the __registrable domain__,
the __domain suffix__ and the __subdomain__ parts of the host separately
(Thanks to the [Mozilla Public Suffix List](https://publicsuffix.org/)).
* An advanced API to access and manipulate the __URL query__ component.
* __Compare URLs__ or components of URLs (e.g. checking if different URLs
point to the same host or domain)
* Thanks to [symfony/polyfill-intl-idn](https://github.com/symfony/polyfill-intl-idn)
it's also no problem to parse __internationalized domain names (IDN)__.
* Includes an adapter class which implements the
[PSR-7 UriInterface](https://github.com/php-fig/http-message/blob/master/src/UriInterface.php).

## Documentation
You can find the documentation at [crwlr.software](https://www.crwlr.software/packages/url/getting-started).

## Contributing

If you consider contributing something to this package, read the [contribution guide (CONTRIBUTING.md)](CONTRIBUTING.md).
