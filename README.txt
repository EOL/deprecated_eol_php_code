
=== Installation

There are a few things you must do before using this code:

1. Give write permission for the following directories to your web server user:
    /temp
    /applications/content_server/content
    /applications/content_server/content_partners
    /applications/content_server/resources
    /applications/content_server/tmp

2. Copy the following files and edit the configuration parameters:
    cp config/database.sample.yml config/database.yml
    cp config/start.sample.php config/start.php
    cp config/constants.sample.php config/constants.php

3. Install the PHP PEAR package Horde/YAML
    first install PEAR if not aleardy installed
        http://pear.php.net/manual/en/installation.introduction.php
    with pear installed run these two commands (see http://railsforphp.com/2008/01/08/php-meet-yaml for more details):
        pear channel-discover pear.horde.org
        pear install horde/yaml



=== Getting Started
 
You need to include /config/start.php for any application that you want to be connected
to the databases configured in database.yml and in the current environment

The default environment is 'ddevelopment' unless you change the default in start.php 
or override this value by including this line BEFORE including start.php:
    define("ENVIRONMENT", 'new_environment');

---

Things to know:

You can set the following constants BEFORE including /config/start.php for debugging:
    define('MYSQL_DEBUG', true);
    define('DEBUG', true);
    define('DEBUG_TO_FILE', true);

If DEBUG_TO_FILE is defined then debug messages will be written to /temp/application.log
in stead of standard output

Call these functions to send messages to the configured output when the above constants are true:
    Functions::mysql_debug(string);
    Functions::debug(string);

---

Test can be initiated by calling running /tests/all_tests.php from your browser
All tests run in the test environment

*** There is a serious lack of coverage with the included tests. We will be working to improve this.
in the mean time the test are really not to be trusted as an indicator of anything. ***


Fixture *.yml files can be added to /fixtures. Any fields that don't match the fields in your test database will be ignored.

Fixture data is turned into mock objects which can be accessed within tests as such:
    $this->fixtures->fixture_name->row_identifier->field
    e.g. $this->fixtures->agents->me->id



