# Upgrade from 1.x to 2.0.0

- Required minimum PHP version is now 8.0.
- Access to url components via class properties (instead of methods) using magic getter and setter was removed. So if you've been using components like `$url->host`, `$url->path` and so on (see https://www.crwlr.software/packages/url/components), you need to change those to method calls, so just: `$url->host()`, `$url->path()`. The property access was just an alias.

# Upgrade from 0.x to 1.0.0

- Required minimum PHP version is now 7.2.
- Instances of the `Url` class can now be created from relative
  references (without scheme). In v0.1 creating a new instance
  from a relative reference threw an Exception. If your 
  application expects this behavior, you can use the 
  `isRelativeReference` method of the `Url` object to find out
  if the URL in question is a relative reference.
- If you're using any other class than the `Url` class directly in
  your code, please take a look at the `CHANGELOG.md` entry for
  v1.0.0.
