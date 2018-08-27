<?php

namespace App;

/**
 * Callback class to generate proper links
 */
class CleanLink
{
    private $flatten;

    private $meta;

    /**
     * _construct
     * @param boolean $flatten Passed from, argv, do we want flat files or not
     */
    public function __construct($flatten, $meta)
    {
        $this->flatten = $flatten;
        $this->meta = $meta;
    }

    /**
     * Generate a new clean link to be converted
     * @param  array $matches Holds the complete information for a link
     * @return string          Return newly created link
     */
    public function cleanLink($matches)
    {

        $linkToClean = $matches[1];

        // If links starts with http we have a malformed Wiki link. Return Broken link.
        if (preg_match("/^https?:\/\//", $linkToClean)) return '[' . $linkToClean . ']';

        // convert relative paths to absolute paths /^\.?\.?\//
        if (preg_match('/^\.*?\//', $linkToClean)) $linkToClean = $this->meta['url'] . '/' . $linkToClean;

        if (strpos($linkToClean, '|') === false) {
            $link = $linkToClean;
            $link_text = $linkToClean;
        } else {
            list($link, $link_text) = explode('|', $linkToClean);
        }

        // Normalize Path - remove extra ../ 
        $link = $this->normalizePath(trim($link));

        // Flat file structure  - replace / with _
        if ($this->flatten) {
            $link = str_replace('/', '_', $link);
        }

        // Cleanup remaining artifacts
        $link = str_replace(' ', '_', $link);

        $link_text = trim($link_text);

        return "[[$link|$link_text]]";
    }

    /**
     * Normalize a path by removing ./ and ../
     * @see  http://php.net/manual/en/function.realpath.php
     * @param  string $path Path to be normalized
     * @return string       Normalized path
     */
    public function normalizePath($path)
    {
        $parts = [];                         // Array to build a new path from the good parts
        $path = str_replace('\\', '/', $path);    // Replace backslashes with forwardslashes
        $path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
        $segments = explode('/', $path);          // Collect path segments
        $test = '';                               // Initialize testing variable
        foreach ($segments as $segment)
        {
            if ($segment != '.')
            {
                $test = array_pop($parts);
                if (is_null($test))
                    $parts[] = $segment;
                else if ($segment == '..')
                {
                    if ($test == '..')
                        $parts[] = $test;
                    if ($test == '..' || $test == '')
                        $parts[] = $segment;
                }
                else
                {
                    $parts[] = $test;
                    $parts[] = $segment;
                }
            }
        }
        return implode('/', $parts);
    }
}
