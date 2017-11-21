# Mediawiki to Github Flavoured Markdown

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://travis-ci.org/outofcontrol/mediawiki-to-gfm.svg?branch=master)](https://travis-ci.org/outofcontrol/mediawiki-to-gfm)

Mediawiki to GFM is a script to convert a set of [Mediawiki](https://www.mediawiki.org)
pages to [Github Flavoured Markdown](https://github.github.com/gfm/) (GFM). This script was written from a necessity to convert a MediaWiki installation to a Gitlab wiki. This code is based on [MediaWiki to Markdown](https://github.com/philipashlock/mediawiki-to-markdown) by [Philip Ashlock](https://github.com/philipashlock/). Philip graciously gave us permission to post our version as a new project.

Major differences include the additon of PHPUnit tests, code is broken into classes, deprecated code removed, work around for a bug in Pandoc added, fix for a common MediaWiki user error added, other small changes other small changes. 

## Requirements

* PHP: Tested in PHP 7.0 and 7.1
* Pandoc: Installation instructions are here https://pandoc.org/installing.html
    - Tested on version 2.0.1.1 and 2.0.2 
* MediaWiki: https://www.mediawiki.org/wiki/MediaWiki
    - Tested on version 1.27.x and 1.29.x
* Composer: Installation instructions are here https://getcomposer.org/download/

## Installation 

    git clone https://github.com/outofcontrol/mediawiki-to-gfm.git
    cd mediawiki-to-gfm
    composer update --no-dev

Run the script on your exported MediaWiki XML file:

    ./convert.php --filename=/path/to/filename.xml --output=/path/to/converted/files 

## Options

    ./convert.php --filename=/path/to/filename.xml --output=/path/to/converted/files --format=gfm --addmeta --flatten --indexes

    --filename : Location of the mediawiki exported XML file to convert 
                 to GFM format (Required).
    --output   : Location where you would like to save the converted files
                 (Default: ./output).
    --format   : What format would you like to convert to. Default is GFM 
                 (for use in Gitlab and Github) See pandoc documentation
                 for more formats (Default: 'gfm').
    --addmeta  : This flag will add a Permalink to each file (Default: false).
    --flatten  : This flag will force all pages to be saved in a single level 
                 directory. File names will be converted in the following way:
                 Mediawiki_folder/My_File_Name -> Mediawiki_folder_My_File_Name
                 and saved in a file called 'Mediawiki_folder_My_File_Name.md' 
                 This is required if you are importing into Gitlab (Default: false).
    --help     : This help message.

## Export Mediawiki Files to XML 

In order to convert from MediaWiki format to GFM and use in Gitlab (or Github), you will first need to export all the pages you wish to convert from Mediawiki into an XML file. Here are a few simple steps to help
you accomplish this quickly:

1. MediaWiki -> Special Pages -> 'All Pages'
1. With help from the filter tool at the top of 'All Pages', copy the page names to convert into a text file (one file name per line).
1. MediaWiki -> Special Pages -> 'Export'
1. Paste the list of pages into the Export field. 
   Note: This convert script will only do latest version, not revisions. 
1. Check: 'Include only the current revision, not the full history' 
1. Uncheck: Include Templates
1. Check: Save as file
1. Click on the 'Export' button.

In theory you can convert to any of these formats… (not tested):
    https://pandoc.org/MANUAL.html#description

Updates and improvements are welcome! Please only submit a PR if you have also written tests and tested your code! To run phpunit tests, update composer without the --no-dev parameter:

    composer update

## Disclaimer

This script has not been tested on Windows. 