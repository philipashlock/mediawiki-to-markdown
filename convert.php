
<?php

$arguments = arguments($argv);

require 'vendor/autoload.php';

if(empty($arguments['filename'])) {
    echo "No input file specified. Use --filename=mediawiki.xml" . PHP_EOL . PHP_EOL; 
    exit;
}


$file = file_get_contents($arguments['filename']);

$xml = str_replace('xmlns=', 'ns=', $file); //$string is a string that contains xml... 

$xml = new SimpleXMLElement($xml);

/* Search for <a><b><c> */
$result = $xml->xpath('page');
$count = 0;

while(list( , $node) = each($result)) {
    
    $title = $node->xpath('title');
    $url = $title[0];
    $url = str_replace(' ', '_', $url);

    if($slash = strpos($url, '/')){
        $directory = substr($url, 0, $slash);
        $filename = substr($url, $slash+1);
    } else {
        $directory = '';
        $filename = $url;
    }

    $text = $node->xpath('revision/text');
    $text = $text[0];
    $text = html_entity_decode($text);

    // append page title frontmatter to text
    $frontmatter = "---\n";
    $frontmatter .= "title: $filename\n";
    $frontmatter .= "permalink: $url\n";
    $frontmatter .= "---\n\n";


    $pandoc = new Pandoc\Pandoc();
    $options = array(
        "from"  => "mediawiki",
        "to"    => "markdown_github"
    );
    $text = $pandoc->runWith($text, $options);

    $text = str_replace('\_', '_', $text);

    $text = $frontmatter . $text;

    // create directory if necessary
    if(!empty($directory)) {
        if(!file_exists($directory)) {
            mkdir($directory);
        }

        $directory = $directory . '/';
    }

    // create file

    $file = fopen($directory . $filename . '.md', 'w');
    fwrite($file, $text);
    fclose($file);

    $count++;

}

if ($count > 0) {
    echo "$count files converted" . PHP_EOL . PHP_EOL;
}


function arguments($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
      if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
        $_ARG[$reg[1]] = $reg[2];
      } elseif(ereg('-([a-zA-Z0-9])',$arg,$reg)) {
            $_ARG[$reg[1]] = 'true';
        }
  
    }
  return $_ARG;
}



?>