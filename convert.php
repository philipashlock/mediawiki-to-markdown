#!/usr/bin/env php
<?php

/**
 * Convert converts Mediawiki format to Github Flavoured Markdown format
 *
 * Current Author
 * @author James Riordon <jriordon@outofcontrol.ca>
 * @link  https://github.com/outofcontrol/mediawiki-to-gfm
 * Original Author
 * @author Philip Ashlock <philip.ashlock@gsa.gov>
 * @link  https://github.com/philipashlock/mediawiki-to-markdown
 * @license MIT License https://opensource.org/licenses/MIT
 */

if (is_file(__DIR__.'/vendor/autoload.php') === true) {
    require_once 'vendor/autoload.php';
} else {
    exit("Please run 'composer update --no-dev' first." . PHP_EOL);
}

$args = getopt(
    '',
    [
        'filename:',
        'output::',
        'format::',
        'addmeta::',
        'flatten::',
        'indexes::',
        'version::',
        'help::'
    ]
);

$convert = new App\Convert($args);

if (isset($args['help'])) {
    $convert->help();
    exit;
}

if (isset($args['version'])) {
    $convert->getVersion();
    exit;
}

try {
    $convert->run();
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
