eol_php_code Repository
----------




Things you must do before using this code:
==========================================
Give write permission for the following directories to your web server user:
    /temp
    /applications/content_server/content
    /applications/content_server/content_partners
    /applications/content_server/resources
    /applications/content_server/tmp

=-=

This code uses Horde/Yaml for importing fixtures. You'll need PEAR to install this package.
Read about the install at: http://railsforphp.com/2008/01/08/php-meet-yaml/.

The basics are (with PEAR already installed):
    pear channel-discover pear.horde.org
    pear install horde/yaml

=-=

Copy the following files and edit the configuration parameters:
    /config/database.sample.yml
    /config/start.sample.php
    /config/constants_sample.php

=-=

You need to include /config/start.php for most applications.






----------
Things to know:
==========================================
You can set the following constants BEFORE including /config/start.php for debugging:
    define('MYSQL_DEBUG', true);
    define('DEBUG', true);

...and you can call these functions to display messages when these constants are true respectively:
    Functions::mysql_debug(string);
    Functions::debug(string);

=-=

This is configured to default to the development environment unless you change the default in start.php, 
or override the default with:
    define("ENVIRONMENT", "test");

=-=

Test can be initiated by calling running /tests/all_tests.php from your browser.

All test run in the test environment

Fixture yml can be added to /fixtures. Any fields that don't match the fields in your test database will be ignored.

Fixture data is turned into mock objects which can be accessed within tests as such:
    $this->fixtures->fixture_name->row_identifier->field
    e.g. $this->fixtures->agents->me->id

















