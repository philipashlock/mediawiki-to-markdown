<?php

namespace App;

use Pandoc\Pandoc;
use App\CleanLink;
use App\PandocFix;

class Convert
{

    /**
     * Converter Version
     * @var string
     */
    private $version = '0.9.0';
    /**
     * Path and name of  file to convert
     * @var String
     */
    private $filename;

    /**
     * Path to directory to save converted files
     * @var String
     */
    private $output;

    /**
     * Set to true will save converted files in one directory level
     * @var boolean
     */
    private $flatten = false;

    /**
     * Set to true will add a permalink in 'gfm' format to each converted file
     * @var boolean
     */
    private $addmeta = false;

    /**
     * Set to true will force the file matching the name of each directory to index.md
     * @var boolean
     */
    private $indexes = false;

    /**
     * Which format to convert files to.
     * @var string
     */
    private $format = 'gfm';

    /**
     * Holds the count of how many files converted
     * @var integer
     */
    private $counter = 0;

    /**
     * Holds list of files converted when 'indexes' is set to true
     * @var [type]
     */
    private $directory_list;

    /**
     * Holds XML Data for each 'page' found in the XML file
     * @var [type]
     */
    private $dataToConvert;

    /**
     * Holds instance of Pandoc
     * @var Object
     */
    private $pandoc;

    /**
     * Options for Pandoc object
     * @var Array
     */
    private $pandocOptions;

    /**
     * Set whether the version of pandoc in use contains a known link bug
     * @see // Link to bug on Github
     * @var [type]
     */
    private $pandocBroken;

    /**
     * Construct
     */
    public function __construct($options)
    {
        $this->setArguments($options);
    }

    public function run()
    {
        $this->createDirectory($this->output);
        $this->pandocSetup();
        $this->loadData($this->loadFile());
        $this->convertData();
        $this->renameFiles($this->directory_list);
        $this->message("$this->counter files converted");
    }

    /**
     * Get instance and setup pandoc
     * @return
     */
    public function pandocSetup()
    {
        $this->pandoc = new Pandoc();
        $this->pandocBroken = (version_compare($this->pandoc->getVersion(), '2.0.2', '<='));
        $this->pandocOptions = [
            "from"  => "mediawiki",
            "to"    => $this->format
        ];
    }

    /**
     * Method to oversee the cleaning, preparation and converting of one page
     */
    public function convertData()
    {
        foreach ($this->dataToConvert as $node) {
            $fileMeta = $this->retrieveFileInfo($node->xpath('title'));

            $text = $node->xpath('revision/text');
            $text = $this->cleanText($text[0], $fileMeta);
            $text = $this->runPandoc($text);

            $text .= $this->getMetaData($fileMeta);
            $this->saveFile($fileMeta, $text);
            $this->counter++;
        }
    }

    /**
     * Handles the various tasks to clean and get text ready to convert
     * @param  string $text Text to convert
     * @param  array $fileMeta File information
     * @return string Cleaned text
     */
    public function cleanText($text, $fileMeta)
    {
        $callback = new cleanLink($this->flatten, $fileMeta);
        $callbackFix = new pandocFix();

        // decode inline html
        $text = html_entity_decode($text);

        // Hack to fix URLs for older version of pandoc
        if ($this->pandocBroken) {
            $text = preg_replace_callback('/\[(http.+?)\]/', [$callbackFix, 'urlFix'], $text);
        }

        // clean up links
        return preg_replace_callback('/\[\[(.+?)\]\]/', [$callback, "cleanLink"], $text);
    }

    /**
     * Run pandoc and do the actual conversion
     * @param  string $text Text to convert
     * @return string Converted Text
     */
    public function runPandoc($text)
    {
        $text = $this->pandoc->runWith($text, $this->pandocOptions);
        $text = str_replace('\_', '_', $text);

        return $text;
    }

   /**
     * Save new mark down file
     * @param  string $fileMeta Name of file to save
     * @param  strong $text     Body of file to save
     */
    public function saveFile($fileMeta, $text)
    {
        $this->createDirectory($fileMeta['directory']);

        $file = fopen($fileMeta['directory'] . $fileMeta['filename'] . '.md', 'w');
        fwrite($file, $text);
        fclose($file);

        $this->message("Converted: " . $fileMeta['directory'] . $fileMeta['filename']);
    }

    /**
     * Build array of file information
     * @param  array $title Title of current page to convert
     * @return array File information: Directory, filename, title and url
     */
    public function retrieveFileInfo($title)
    {
        $title = (string)$title[0];
        $url = str_replace(' ', '_', $title);
        $filename = $url;
        $directory = '';

        if ($slash = strpos($url, '/')) {
            $title = str_replace('/', ' ', $title);
            $url_parts = pathinfo($url);
            $directory = $url_parts['dirname'];
            $filename = $url_parts['basename']; // Avoids breaking names with periods on them
            $this->directory_list[] = $directory;
            if ($this->flatten && $directory != '') {
                $filename = str_replace('/', '_', $directory) . '_' . $filename;
                $directory  = '';
            } else {
                $directory = rtrim($directory, '/') . '/';
            }
        }
        $directory = $this->output . $directory;

        return [
            'directory' => $directory,
            'filename' => $filename,
            'title' => $title,
            'url' => $url
        ];
    }

    /**
     * Simple method to handle outputing messages to the CLI
     * @param  string $message Message to output
     */
    public function message($message)
    {
        echo $message . PHP_EOL;
    }

    /**
     * Rename files that have the same name as a folder to index.md
     * @return boolean
     */
    public function renameFiles()
    {
        if ($this->flatten || !count((array)$this->directory_list) || !$this->indexes) {
            return false;
        }

        foreach ($this->directory_list as $directory_name) {
            if (file_exists($this->output . $directory_name . '.md')) {
                rename($this->output . $directory_name . '.md', $this->output . $directory_name . '/index.md');
            }
        }
        return true;
    }

    /**
     * Build and return Permalink metadata
     * @param array $fileMeta File Title and URL
     * @return  string Page body with meta data added
     */
    public function getMetaData($fileMeta)
    {
        return ($this->addmeta)
            ? sprintf("---\ntitle: %s\npermalink: /%s/\n---\n\n", $fileMeta['title'], $fileMeta['url'])
            : '';
    }

    public function loadFile()
    {
        if (!file_exists($this->filename)) {
            throw new \Exception('Input file does not exist: ' . $this->filename);
        }

        $file = file_get_contents($this->filename);

        return str_replace('xmlns=', 'ns=', $file); //$string is a string that contains xml...
    }

    /**
     * Load XML contents into variable
     */
    public function loadData($xml)
    {
        if (($xml = new \SimpleXMLElement($xml)) === false) {
            throw new \Exception('Invalid XML File.');
        }
        $this->dataToConvert = $xml->xpath('page');

        if ($this->dataToConvert == '') {
            throw new \Exception('XML Data is empty');
        }
    }

    /**
     * Get command line arguments into variables
     * @param  array $argv Array hold command line interface arguments
     */
    public function setArguments($options)
    {
        $this->setOption('filename', $options, null);
        $this->setOption('output', $options, 'output');
        $this->setOption('format', $options, 'gfm');
        $this->setOption('flatten', $options);
        $this->setOption('indexes', $options);
        $this->setOption('addmeta', $options);
        $this->output = rtrim($this->output, '/') . '/';
    }

    /**
     * Set an Option
     * @param string $name  Option name
     * @param string $value Option value
     */
    public function setOption($name, $options, $default = false)
    {
        $this->{$name} = (isset($options[$name]) ? (empty($options[$name]) ? true : $options[$name]) : $default);
    }

    /**
     * Helper method to cleanly create a directory if none already exists
     * @param string $output Returns path
     */
    public function createDirectory($directory = null)
    {
        if (!empty($directory) && !file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception('Unable to create directory: ' . $directory);
            }
        }
        return $directory;
    }

    /**
     * Get Option
     * @param string $name  Option name
     * @param string $value Option value
     */
    public function getOption($name)
    {
        return $this->{$name};
    }

    /**
     * Get Version
     */
    public function getVersion()
    {
        echo "Version: {$this->version}";
    }
    /**
     * Basic help instructions
     */
    public function help()
    {
        echo <<<HELPMESSAGE
Version: {$this->version}
MIT License: https://opensource.org/licenses/MIT

Mediawiki to GFM converter is a script that will convert a set of media wiki
files to Github Flavoured Markdown (GFM). This converter has been tested to work
with Mediawiki 1.27.x and 1.29.x.

Requirements:
    pandoc: Installation instructions are here https://pandoc.org/installing.html
            Tested on version 2.0.1.1 and 2.0.2 
    mediawiki: https://www.mediawiki.org/wiki/MediaWiki
               Tested on version 1.27.x and 1.29.x

Run the script on your exported MediaWiki XML file:
    ./convert.php --filename=/path/to/filename.xml 

Options:
    ./convert.php --filename=/path/to/filename.xml --output=/path/to/converted/files --format=gfm --addmeta --flatten --indexes

    --filename : Location of the mediawiki exported XML file to convert to GFM format (Required).
    --output   : Location where you would like to save the converted files (Default: ./output).
    --format   : What format would you like to convert to. Default is GFM (for use 
        in Gitlab and Github) See pandoc documentation for more formats (Default: 'gfm').
    --addmeta  : This flag will add a Permalink to each file (Default: false).
    --flatten  : This flag will force all pages to be saved in a single level 
                 directory. File names will be converted in the following way:
                 Mediawiki_folder/My_File_Name -> Mediawiki_folder_My_File_Name
                 and saved in a file called 'Mediawiki_folder_My_File_Name.md'
    --help     : This help message.


Export Mediawiki Files to XML
In order to convert from MediaWiki format to GFM and use in Gitlab (or Github), you will 
first need to export all the pages you wish to convert from Mediawiki into an XML file. 
Here are a few simple steps to help
you accomplish this quickly:

    1. MediaWiki -> Special Pages -> 'All Pages'
    2. With help from the filter tool at the top of 'All Pages', copy the page names
       to convert into a text file (one file name per line).
    3. MediaWiki -> Special Pages -> 'Export'
    4. Paste the list of pages into the Export field. 
       Note: This convert script will only do latest version, not revisions. 
    5. Check: 'Include only the current revision, not the full history' 
    6. Uncheck: Include Templates
    7. Check: Save as file
    8. Click on the 'Export' button.

In theory you can convert to any of these formats… but this haven't been tested:
    https://pandoc.org/MANUAL.html#description

HELPMESSAGE;
    }
}
