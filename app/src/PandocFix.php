<?php 

namespace App;

class pandocFix 
{
    /**
     * Hack to get around PanDoc <= 2.0.2 
     *     https://github.com/jgm/pandoc/issues/4068
     * @param  string $url URL to fix
     * @return string      Broken URL
     */
    public function urlFix($url)
    {
        return str_replace(['=&', '= ', '.&'], ['=%20&', '=%20 ', '.%20&'], $url[0]);
    }

}
