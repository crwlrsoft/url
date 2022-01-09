# Swiss Army knife for urls

This package is for you when PHP's parse_url() is not enough.

__Key Features:__
* __Parse a url__ and access or modify all its __components__ separately.
* Resolve any __relative reference__ you may find in an HTML document __to an
absolute url__, based on the document's url.
* Get not only the full __host__ of a url, but also the __registrable domain__,
the __domain suffix__ and the __subdomain__ parts of the host separately
(Thanks to the [Mozilla Public Suffix List](https://publicsuffix.org/)).
* __Compare urls__ or components of urls (e.g. checking if different urls
point to the same host or domain)
* Thanks to [symfony/polyfill-intl-idn](https://github.com/symfony/polyfill-intl-idn)
it's also no problem to parse __internationalized domain names (IDN)__.
* Includes an adapter class which implements the
[PSR-7 UriInterface](https://github.com/php-fig/http-message/blob/master/src/UriInterface.php).

## Requirements

Requires PHP version 7.2 or above.

## Installation

Install the latest version with:

```sh
composer require crwlr/url
```

## Usage

### Including the package

```php
<?php

include('vendor/autoload.php');

use Crwlr\Url\Url;
```

To start using the library include composer's autoload file and import the
Url class so you don't have to write the full namespace path again and again.
Further code examples skip the above.

### Parsing urls

Parsing a url is easy as pie:

```php
$url = Url::parse('https://john:123@www.example.com:8080/foo?bar=baz');
```

The static `parse` method of the `Url` class provides a convenient way to
create a new instance and then access all of it's components separately.

```php
// Accessing url components via method calls
$port = $url->port();                   // => 8080
$domainSuffix = $url->domainSuffix();   // => "com"
$path = $url->path();                   // => "/foo"
$fragment = $url->fragment();           // => NULL

// Or as properties
$scheme = $url->scheme;                 // => "https"
$user = $url->user;                     // => "john"
$host = $url->host;                     // => "www.example.com"
$domain = $url->domain;                 // => "example.com"
```

Of course you can also get a new instance using the `new` keyword.

```php
$url = new Url('https://www.steve.jobs/');
```

__Relative urls__  

New in v1.0 of this package is, that you can obtain an instance of `Url` from
a relative url as well. Previous versions throw an `InvalidUrlException` when
the url string doesn't contain a valid scheme component.

```php
$url = Url::parse('/some/path?query=string');
var_dump($url->__toString());   // => '/some/path?query=string'
var_dump($url->scheme());       // => null
var_dump($url->path());         // => '/some/path'
```

#### Available url components

Below, you can see a visualization of all the components that are available to
you via a `Url` object.

```plaintext
https://john:123@subdomain.example.com:8080/foo?bar=baz#anchor

                     domainLabel  domainSuffix
                              ↓     ↓
 _____  ____ ___ _____________________ ____ ____ _______ ______
|https||john|123|subdomain.example.com|8080|/foo|bar=baz|anchor|
 ‾‾‾‾‾  ‾‾‾‾ ‾‾‾ ‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾ ‾‾‾‾ ‾‾‾‾ ‾‾‾‾‾‾‾ ‾‾‾‾‾‾
   ↑      ↑   ↑     ↑           ↑       ↑    ↑      ↑       ↑
 scheme user  ↑  subdomain   domain    port path  query  fragment
              ↑        ⤷ host ⤶
       |   pass(word)                      |
       |___________________________________|
       |john:123@subdomain.example.com:8080|
       |‾‾‾‾‾‾‾‾|‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾
       |________|         ↑
       |john:123|     authority
        ‾‾‾‾‾‾‾‾
            ↑
        userInfo
```

When a component is not present in a url (e.g. it doesn't contain user and
password) the corresponding properties will return `NULL`.

#### Further available component combinations

The following combinations of components aren't really common, but may as well
be useful sometimes.

```plaintext
 _______________________   ___________________
|https://www.example.com| |/foo?bar=baz#anchor|
 ‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾   ‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾
            ↑                       ↑
          root                  relative
```

##### root

The `root` as it's called here, consists of the scheme and the authority
components.

```php
$url = Url::parse('https://www.example.com:8080/foo?bar=baz');
$root = $url->root();   // => "https://www.example.com:8080"
```

##### relative

Complementary to `root` you can retrieve path, query and fragment via the
`relative` method.

```php
$url = Url::parse('https://www.example.com/foo?bar=baz#anchor');
$relative = $url->relative();   // => "/foo?bar=baz#anchor"
```

#### Parsing a query string

If you're after the query of a url you may want to get it as an array. Don't
worry, nothing easier than that:

```php
$url = Url::parse('https://www.example.com/foo?bar=baz&key=value');
var_dump($url->queryArray());
```
__Output__
```output
array(2) {
  ["bar"]=>
  string(3) "baz"
  ["key"]=>
  string(5) "value"
}
```

### Modifying urls

All methods that are used to get a component's value can also be used to
replace or set its value. So for example if you have an array of urls and you
want to be sure that they are all on https, you can achieve this simply by
setting the scheme to `https` for all of them in a loop.

```php
$urls = [
    'https://www.example.com',
    'http://notsecure.example.org/foo',
    'https://secure.example.org/bar',
    'http://www.example.com/baz'
];

foreach ($urls as $key => $url) {
    $urls[$key] = Url::parse($url)->scheme('https')->toString();
}

var_dump($urls);

```
__Output__
```output
array(4) {
  [0]=>
  string(24) "https://www.example.com/"
  [1]=>
  string(33) "https://notsecure.example.org/foo"
  [2]=>
  string(30) "https://secure.example.org/bar"
  [3]=>
  string(27) "https://www.example.com/baz"
}
```

Another example: your website can be reached with or without the www subdomain.
Sloppy input data can easily be fixed by just assigning the same host to all of
them.

```php
$urls = [
    'https://www.example.com/stuff',
    'https://example.com/yolo',
    'https://example.com/products',
    'https://www.example.com/contact',
];

$urls = array_map(function($url) {
    return Url::parse($url)->host('www.example.com')->toString();
}, $urls);

var_dump($urls);
```
__Output__
```output
array(4) {
  [0]=>
  string(29) "https://www.example.com/stuff"
  [1]=>
  string(28) "https://www.example.com/yolo"
  [2]=>
  string(32) "https://www.example.com/products"
  [3]=>
  string(31) "https://www.example.com/contact"
}
```

And that's the same for all components that are listed under the [available
url components](#available-url-components).

And the query can even be set as an array:

```php
$url = Url::parse('https://www.example.com/foo');
$url->queryArray(['param' => 'value', 'marco' => 'polo']);
echo $url;
```
__Output__
```output
https://www.example.com/foo?param=value&marco=polo
```

Btw.: As you can see in the example above, you can use a Url object like
a string because of its `__toString()` method.

### Resolving relative urls

When you scrape urls from a website you will come across relative urls like
`/path/to/page`, `../path/to/page`, `?param=value`, `#anchor` and alike. This
package makes it a breeze to resolve these urls to absolute ones with the url
of the page where they have been found on.

```php
$documentUrl = Url::parse('https://www.example.com/foo/bar/baz');

$relativeLinks = [
    '/path/to/page',
    '../path/to/page',
    '?param=value',
    '#anchor'
];

$absoluteLinks = array_map(function($relativeLink) use ($documentUrl) {
    return $documentUrl->resolve($relativeLink)->toString();
}, $relativeLinks);

var_dump($absoluteLinks);
```
__Output__
```output
array(4) {
  [0]=>
  string(36) "https://www.example.com/path/to/page"
  [1]=>
  string(40) "https://www.example.com/foo/path/to/page"
  [2]=>
  string(47) "https://www.example.com/foo/bar/baz?param=value"
  [3]=>
  string(42) "https://www.example.com/foo/bar/baz#anchor"
}
```

If you pass an absolute url to `resolve()` it will just return that absolute
url.

### Comparing urls or url components

You may think to compare two urls you don't need this library, but whenever
you have urls from an unpredictable input source, I'd recommend to use it.
Imagine a case like this:

```php
$url1 = 'https://www.example.com/foo/bár/báz';
$url2 = 'https://www.example.com/foo/b%C3%A1r/b%C3%A1z';

var_dump($url1 === $url2);
// Returns false. Of course the two strings aren't equal.

var_dump(Url::parse($url1)->isEqualTo($url2));
// Returns true, because the path /foo/bár/báz is percent-encoded
// in the Url class to /foo/b%C3%A1r/b%C3%A1z
```

It's also really easy to compare the same component of two different
urls.

```php
$url1 = Url::parse('https://u:p@www.example.com/foo?q=s#frag');
$url2 = Url::parse('http://s:a@jobs.eggsample.org/bar?u=t#ment');
$url3 = clone $url1;

$url1->isSchemeEqualIn($url2); // false
$url1->isSchemeEqualIn($url3); // true

$url1->isHostEqualIn($url2); // false
$url1->isHostEqualIn($url3); // true

$url1->isPasswordEqualIn($url2); // false
$url1->isPasswordEqualIn($url3); // true
```

__These are all available comparison methods:__
* isEqualTo($url)
* isComponentEqualIn($url, $componentName)
* isSchemeEqualIn($url)
* isAuthorityEqualIn($url)
* isUserEqualIn($url)
* isPasswordEqualIn($url)
* isUserInfoEqualIn($url)
* isHostEqualIn($url)
* isDomainEqualIn($url)
* isDomainLabelEqualIn($url)
* isDomainSuffixEqualIn($url)
* isSubdomainEqualIn($url)
* isPortEqualIn($url)
* isPathEqualIn($url)
* isQueryEqualIn($url)
* isFragmentEqualIn($url)

### Internationalized domain names (IDN)

```php
echo Url::parse('https://www.пример.онлайн/hello/world')->toString();
```
__Output__
```output
https://www.xn--e1afmkfd.xn--80asehdb/hello/world
```

Behind the curtains [symfony/polyfill-intl-idn](https://github.com/symfony/polyfill-intl-idn)
is used, so you don't need to have the
[internationalization PHP extension](https://www.php.net/manual/de/intl.installation.php)
installed to parse internationalized domain names.

To check if a url contains an internationalized domain name you can use the
`hasIdn` method:

```php
Url::parse('https://www.example.com')->hasIdn();           // => false
Url::parse('https://www.müller.de')->hasIdn();             // => true
Url::parse('https://www.xn--m1adged4c3a.com')->hasIdn();   // => true
```

### PSR-7 UriInterface adapter class

The `Url` class does not support immutability as it is required by the
[PSR-7 UriInterface](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface).
But the package provides an adapter class `Crwlr\Url\Psr\Uri` which has an
instance of the `Url` class in a private property and thus assures immutability.

#### Usage Example

```php
$url = 'https://user:password@www.example.com:1234/foo/bar?some=query#fragment';
$uri = Url::parsePsr7($url); // Or instead: new Crwlr\Url\Psr\Uri($url);

var_dump($uri->getScheme());        // => 'https'
var_dump($uri->getAuthority());     // => 'user:password@www.example.com:1234'
var_dump($uri->getUserInfo());      // => 'user:password'
var_dump($uri->getHost());          // => 'www.example.com'
var_dump($uri->getPort());          // => 1234
var_dump($uri->getPath());          // => '/foo/bar'
var_dump($uri->getQuery());         // => 'some=query'
var_dump($uri->getFragment());      // => 'fragment'

// Keep in mind an instance of Uri is immutable and all the methods that change
// state (method names starting with "with") return a new instance:
$newUri = $uri->withScheme('http');
var_dump($uri->getScheme());        // => 'https'
var_dump($newUri->getScheme());     // => 'http'

$uri = $newUri->withUserInfo('u', 'p');
var_dump($uri->getUserInfo());      // => 'u:p'
$uri = $uri->withHost('foo.bar.com');
var_dump($uri->getHost());          // => 'foo.bar.com'
$uri = $uri->withPort(666);
var_dump($uri->getPort());          // => 666
$uri = $uri->withPath('/path');
var_dump($uri->getPath());          // => '/path'
$uri = $uri->withQuery('foo=bar');
var_dump($uri->getQuery());         // => 'foo=bar
$uri = $uri->withFragment('baz');
var_dump($uri->getFragment());      // => 'baz'
var_dump($uri->__toString());
// => 'http://u:p@foo.bar.com:666/path?foo=bar#baz'
```

### Exceptions

There are two Exceptions that can be thrown by the `Url` class:
* `InvalidUrlException` when you try to create an instance from a
  string that isn't a valid Uri.
* `InvalidUrlComponentException` when you try to set an invalid
  new value for a component (scheme, host,...).

When you're dealing with unpredictable input source, you should
catch and handle them somehow.

### Updating Mozilla's Public Suffix List

Mozilla's [Public Suffix List](https://publicsuffix.org/list/) is parsed and
stored in a file in this package to be able to extract the domain suffix from
a url's host component. It should be updated with every new release
of this package. If you need to get the latest version of the list
immediately, because a particular new suffix isn't included in the list in
this repository, you can update it using the following composer command:

```sh
composer update-suffixes
```

__Note:__ Please don't overuse this, as Mozilla states on their page:

> If you wish to make your app download an updated list periodically, please
use this URL and have your app download the list no more than once per day.
(The list usually changes a few times per week; more frequent downloading is
pointless and hammers our servers.)

[https://publicsuffix.org/list/](https://publicsuffix.org/list/)
