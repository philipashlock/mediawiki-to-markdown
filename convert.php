
<?php

$arguments = arguments($argv);

require 'vendor/autoload.php';



// Load arguments passed from CLI 

if(empty($arguments['filename'])) {
    echo "No input file specified. Use --filename=mediawiki.xml" . PHP_EOL . PHP_EOL; 
    exit;
}

if(!empty($arguments['output'])) {
    $output_path = $arguments['output'];
        
    if(!file_exists($output_path)) {
        echo "Creating output directory $output_path" . PHP_EOL . PHP_EOL;
        mkdir($output_path);
    }

} else {
    $output_path = '';
}

if(!empty($arguments['format'])) {
    $format = $arguments['format'];
} else {
    $format = 'markdown_github';
}


if(!empty($arguments['fm']) OR (empty($arguments['fm']) && $format == 'markdown_github')) {
    $add_meta = true;
} else {
    $add_meta = false;
}




// Load XML file
$file = file_get_contents($arguments['filename']);

$xml = str_replace('xmlns=', 'ns=', $file); //$string is a string that contains xml... 

$xml = new SimpleXMLElement($xml);


$result = $xml->xpath('page');
$count = 0;
$directory_list = array();

// Iterate through XML
while(list( , $node) = each($result)) {
    
    $title = $node->xpath('title');
    $title = $title[0];
    $url = str_replace(' ', '_', $title);

    if($slash = strpos($url, '/')){
        $title = str_replace('/', ' ', $title);
        $directory = substr($url, 0, $slash);
        $filename = substr($url, $slash+1);
        $directory_list[$directory] = true;
    } else {
        $directory = '';
        $filename = $url;
    }

    $text = $node->xpath('revision/text');
    $text = $text[0];
    $text = html_entity_decode($text); // decode inline html
    $text = preg_replace_callback('/\[\[(.+?)\]\]/', "new_link", $text); // adds leading slash to links, "absolute-path reference"
    
    // prepare to append page title frontmatter to text
    if ($add_meta) {    
        $frontmatter = "---\n";
        $frontmatter .= "title: $title\n";
        $frontmatter .= "permalink: /$url/\n";
        $frontmatter .= "---\n\n";
    }

    $pandoc = new Pandoc\Pandoc();
    $options = array(
        "from"  => "mediawiki",
        "to"    => $format
    );
    $text = $pandoc->runWith($text, $options);

    $text = str_replace('\_', '_', $text);

    if ($add_meta) {
        $text = $frontmatter . $text;
    }

    if (substr($output_path, -1) != '/') $output_path = $output_path . '/';

    $directory = $output_path . $directory;

    // create directory if necessary
    if(!empty($directory)) {
        if(!file_exists($directory)) {
            mkdir($directory);
        }

        $directory = $directory . '/';
    }

    // create file
    $file = fopen(normalizePath($directory . $filename . '.md'), 'w');
    fwrite($file, $text);
    fclose($file);

    $count++;

}


// Rename and move files with the same name as directories
if (!empty($directory_list) && !empty($arguments['indexes'])) {

    $directory_list = array_keys($directory_list);

    foreach ($directory_list as $directory_name) {

        if(file_exists($output_path . $directory_name . '.md')) {
            rename($output_path . $directory_name . '.md', $output_path . $directory_name . '/index.md');
        }
    }

}

if ($count > 0) {
    echo "$count files converted" . PHP_EOL . PHP_EOL;
}


function arguments($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
      if (preg_match('/--([^=]+)=(.*)/',$arg,$reg)) {
        $_ARG[$reg[1]] = $reg[2];
      } elseif(preg_match('/-([a-zA-Z0-9])/',$arg,$reg)) {
            $_ARG[$reg[1]] = 'true';
        }
  
    }
  return $_ARG;
}


function new_link($matches){
    if(strpos($matches[1], '|') != true) {
        $new_link = str_replace(' ', '_', $matches[1]);
        return "[[/$new_link|${matches[1]}]]";
    } else {

        $link = trim(substr($matches[1], 0, strpos($matches[1], '|')));
        $link = '/' . str_replace(' ', '_', $link);

        $link_text = trim(substr($matches[1], strpos($matches[1], '|')+1));

        return "[[$link|$link_text]]";
    }
}


// Borrowed from http://php.net/manual/en/function.realpath.php
function normalizePath($path)
{
    $parts = array();                         // Array to build a new path from the good parts
    $path = str_replace('\\', '/', $path);    // Replace backslashes with forwardslashes
    $path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
    $segments = explode('/', $path);          // Collect path segments
    $test = '';                               // Initialize testing variable
    foreach($segments as $segment)
    {
        if($segment != '.')
        {
            $test = array_pop($parts);
            if(is_null($test))
                $parts[] = $segment;
            else if($segment == '..')
            {
                if($test == '..')
                    $parts[] = $test;
                if($test == '..' || $test == '')
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


?>
