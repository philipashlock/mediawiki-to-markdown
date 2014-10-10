mediawiki-to-markdown
=====================

Convert MediaWiki pages to GitHub flavored Markdown

## Requirements

* PHP
* Pandoc


## Export MediaWiki Pages

You'll export all your pages as a single XML file following these steps: http://en.wikipedia.org/wiki/Help:Export


## Installation

### Install Pandoc

http://johnmacfarlane.net/pandoc/installing.html


### Get Composer

`curl -sS https://getcomposer.org/installer | php`


### Install Composer Packages

`php composer.phar install`


## Run

The only parameter you need to specify is the name of the xml file you exported from MediaWiki, eg: 

`php convert.php --filename=mediawiki.xml`