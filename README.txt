
=== Installation

There are a few things you must do before using this code:

1. Give write permission for the following directories to your web server user:
    /temp
    /applications/content_server/content
    /applications/content_server/content_partners
    /applications/content_server/resources
    /applications/content_server/tmp

2. Update in /config/environment.php the constants for:
    WEB_ROOT        - eg: 'http://localhost/eol_php_code/'
    PHP_BIN_PATH    - eg: '/usr/local/bin/php ' NOTE: THE SPACE IS IMPORTANT
    MAGICK_HOME     - eg: '/usr/local/ImageMagick/'

3. In same file uncomment the Memcached connection if you prefer:
    $GLOBALS['ENV_MEMCACHED_SERVER'] = 'localhost';

4. Create other files in /config/environments/ENV_NAME.php:
    these environment files will be loaded when boot.php is included,
    which is towards the TOP of environment.php

5. Install the PHP PEAR package Horde/YAML
    first install PEAR if not aleardy installed
        http://pear.php.net/manual/en/installation.introduction.php
    with pear installed run these two commands (see http://railsforphp.com/2008/01/08/php-meet-yaml for more details):
        pear channel-discover pear.horde.org
        pear install horde/yaml



=== Getting Started

You need to include /config/environment.php for any application that you want to be connected
to the databases configured in database.yml and in the current environment

The default environment is 'development' unless you change the default in environment.php

The default environment can be overridden by:
    
    including this line BEFORE including environment.php:
        $GLOBALS['ENV_NAME'] = $ENVIRONMENT;
    
    calling a script and including the GET parameter:
        http://localhost/eol_php_code/.../script.php?ENV_NAME=$ENVIRONMENT
    
    calling a command line script and including the argument:
        > php script.php ENV_NAME=$ENVIRONMENT



=== Tests

Tests are best initiated from the command line by running:
    > php tests/run_tests.php

Or running a group with:
    > php tests/run_tests.php web
    > php tests/run_tests.php unit

Or running an individual test with:
    > php tests/run_tests.php unit/test_name.php

Fixture *.yml files can be added to /tests/fixtures. Any fields that don't match the fields in your test database will be ignored.
Test will only use fixtures if they have a public class attribute defined:
    public $load_fixtures = true;

Fixture data is turned into mock objects which can be accessed within tests as such:
    $this->fixtures->fixture_name->row_identifier->field
    e.g. $this->fixtures->agents->me->id


