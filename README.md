# Swiss Army knife for urls

This package is for you when PHP's parse_url() is not enough. 
 
__Key Features:__
* __Parse a url__ and access or modify all its __components__ separately.
* Resolve any __relative url__ you may find in an Html document __to an 
absolute url__, with the document's url.
* Get not only the full __host__ of a url, but also the __registrable domain__, 
the __domain suffix__ and the __subdomain__ parts of the host separately
(Thanks to the [Mozilla Public Suffix List](https://publicsuffix.org/)).
* __Compare components__ of different urls (e.g. checking if different urls 
point to the same host or domain)
* Thanks to [true/punycode](https://github.com/true/php-punycode) it's also no 
problem to parse __internationalized domain names (IDN)__.

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

```php
$url = Url::parse('https://john:123@www.example.com:8080/foo?bar=baz');
 
// Accessing url components as properties
$scheme = $url->scheme;                 // => "https"
$user = $url->user;                     // => "john"
$host = $url->host;                     // => "www.example.com"
$domain = $url->domain;                 // => "example.com"
 
// Or via method calls
$port = $url->port();                   // => 8080
$domainSuffix = $url->domainSuffix();   // => "com"
$path = $url->path();                   // => "/foo"
$fragment = $url->fragment();           // => NULL
```

#### Available url components

Below is a list of all components the Url class takes care of. The 
highlighted part in the example url shows what the component returns.

* __scheme__  
__https__ `://john:123@subdomain.example.com:8080/foo?bar=baz#anchor`
* __user__  
`https://` __john__ `:123@subdomain.example.com:8080/foo?bar=baz#anchor`
* __pass__ or __password__ (alias)  
`https://john:` __123__ `@subdomain.example.com:8080/foo?bar=baz#anchor`
* __host__  
`https://john:123@` __subdomain.example.com__ `:8080/foo?bar=baz#anchor`
* __domain__  
`https://john:123@subdomain.` __example.com__ `:8080/foo?bar=baz#anchor`
* __domainLabel__  
`https://john:123@subdomain.` __example__ `.com:8080/foo?bar=baz#anchor`
* __domainSuffix__  
`https://john:123@subdomain.example.` __com__ `:8080/foo?bar=baz#anchor`
* __subdomain__  
`https://john:123@` __subdomain__ `.example.com:8080/foo?bar=baz#anchor`
* __port__  
`https://john:123@subdomain.example.com:` __8080__ `/foo?bar=baz#anchor`
* __path__  
`https://john:123@subdomain.example.com:8080` __/foo__ `?bar=baz#anchor`
* __query__  
`https://john:123@subdomain.example.com:8080/foo?` __bar=baz__ `#anchor`
* __fragment__  
`https://john:123@subdomain.example.com:8080/foo?bar=baz#` __anchor__

When a component is not present in a url (e.g. it doesn't contain user and 
password) the corresponding properties will return `NULL`.

#### Combinations of components

##### root

There are situations where it can be very helpful to get the `root` as it's 
called here. It returns everything that comes before the path component.

```php
$url = Url::parse('https://www.example.com:8080/foo?bar=baz');
$root = $url->root();   // => "https://www.example.com:8080"
```

##### relative

Complementary to the `root` you can also retrieve all components starting from 
the path (path, query and fragment) combined, via the `relative` property. 
It's called `relative` because it's like a relative url (without scheme and 
host information).

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
replace or set a value. So for example if you have an array of urls and you 
want to be sure that they are all on https, you can achieve that like this:

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

Another example: most websites can be reached with or without the www 
subdomain. If you have an array of urls and want to assure that they all 
point to the version with www:

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
url components](#available-url-components). And for the query string you can 
also just provide an array:

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
a string because of its __toString() method. 

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

### Comparing url components

If you need to, it's really easy to compare components of 2 different urls. 

```php
$url1 = Url::parse('https://www.example.com/foo/bar');
$url2 = Url::parse('https://www.example.org/contact?key=value');
 
if ($url1->compare($url2, 'host')) {
    echo "Urls 1 and 2 ARE on the same host.\n";
} else {
    echo "Urls 1 and 2 ARE NOT on the same host.\n";
}
 
if ($url1->compare($url2, 'subdomain')) {
    echo "Urls 1 and 2 ARE on the same subdomain.\n";
} else {
    echo "Urls 1 and 2 ARE NOT on the same subdomain.\n";
}
 
if ($url1->compare($url2, 'query')) {
    echo "Urls 1 and 2 HAVE the same query.\n";
} else {
    echo "Urls 1 and 2 DO NOT HAVE the same query.\n";
}
```
__Output__
```output
Urls 1 and 2 ARE NOT on the same host.
Urls 1 and 2 ARE on the same subdomain.
Urls 1 and 2 DO NOT HAVE the same query.
```

And again, this can be done with all components listed under the 
[available url components](#available-url-components). Instead of a Url 
object (`$url2` in the example above) you can also just provide a url as a 
string. 

```php
$url1 = Url::parse('https://www.example.com/foo/bar');
$url2 = 'https://www.example.org/foo/bar?key=value';
 
if ($url1->compare($url2, 'path')) {
    echo "Urls 1 and 2 HAVE the same path.\n";
} else {
    echo "Urls 1 and 2 DO NOT HAVE the same path.\n";
}
```
__Output__
```output
Urls 1 and 2 HAVE the same path.
```

### Internationalized domain names (IDN)

```php
echo Url::parse('https://www.пример.онлайн/hello/world')->toString();
```
__Output__
```output
https://www.xn--e1afmkfd.xn--80asehdb/hello/world
```

Behind the curtains [true/punycode](https://github.com/true/php-punycode) is 
used to parse internationalized domain names.

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
