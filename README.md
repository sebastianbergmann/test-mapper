# Test Mapper

`test-mapper` is a tool that visualizes the information of PHPUnit's `@covers` and `@uses` annotations.

## Installation

### PHP Archive (PHAR)

The easiest way to obtain test-mapper is to download a [PHP Archive (PHAR)](http://php.net/phar) that has all required dependencies of test-mapper bundled in a single file:

    wget https://phar.phpunit.de/test-mapper.phar
    chmod +x test-mapper.phar
    mv test-mapper.phar /usr/local/bin/test-mapper

You can also immediately use the PHAR after you have downloaded it, of course:

    wget https://phar.phpunit.de/test-mapper.phar
    php test-mapper.phar

### Composer

Simply add a dependency on `sebastian/test-mapper` to your project's `composer.json` file if you use [Composer](http://getcomposer.org/) to manage the dependencies of your project. Here is a minimal example of a `composer.json` file that just defines a development-time dependency on test-mapper:

    {
        "require-dev": {
            "sebastian/test-mapper": "*"
        }
    }

For a system-wide installation via Composer, you can run:

    composer global require 'sebastian/test-mapper=*'

Make sure you have `~/.composer/vendor/bin/` in your path.

