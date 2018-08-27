<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\CleanLink;

class CleanLinkTest extends TestCase
{
    public function SetUp()
    {
        parent::Setup();

        $this->meta = ['url' => 'Path/To/Files'];
        $this->cleanLink = new CleanLink(false, $this->meta);
        $this->cleanLinkFlat = new CleanLink(true, $this->meta);
    }

    public function test_normalized_link_path()
    {
        $path = '/test/../one/four/six/../.././two/./three';
        $pathPost = '/one/two/three';

        $this->assertEquals($pathPost, $this->cleanLink->normalizePath($path));
    }

    public function test_link_is_clean_when_not_flattened_starting_wth_dotdot()
    {
        $link = [
            '[[../minutes|Link to Mintes]]',
            '../minutes|Link to Mintes'
        ];

        $linkPost = '[[Path/To/minutes|Link to Mintes]]';

        $this->assertEquals($linkPost, $this->cleanLink->cleanLink($link));
    }

    public function test_link_is_clean_when_not_flattened_starting_wth_path()
    {
        $link = [
            '[[Directory/structure/2017/minutes|Link to Mintes]]',
            'Directory/structure/2017/minutes|Link to Mintes'
        ];

        $linkPost = '[[Directory/structure/2017/minutes|Link to Mintes]]';

        $this->assertEquals($linkPost, $this->cleanLink->cleanLink($link));
    }

    public function test_link_is_clean_when_flattened_starting_wth_dotdot()
    {
        $link = [
            '[[../minutes|Link to Mintes]]',
            '../minutes|Link to Mintes'
        ];

        $linkPost = '[[Path_To_minutes|Link to Mintes]]';

        $this->assertEquals($linkPost, $this->cleanLinkFlat->cleanLink($link));
    }

    public function test_link_is_clean_when_flat_starting_wth_path()
    {
        $link = [
            '[[Directory/structure/2017/minutes|Link to Mintes]]',
            'Directory/structure/2017/minutes|Link to Mintes'
        ];

        $linkPost = '[[Directory_structure_2017_minutes|Link to Mintes]]';

        $this->assertEquals($linkPost, $this->cleanLinkFlat->cleanLink($link));
    }

    public function test_clean_external_good_link()
    {
        $link = [
            '[http://domain.com/?a=1&b=&c=3 a text link]',
            'http://domain.com/?a=1&b=&c=3 a text link'
        ];

        $linkPost = '[http://domain.com/?a=1&b=&c=3 a text link]';

        $this->assertEquals($linkPost, $this->cleanLinkFlat->cleanLink($link));
    }

    public function test_clean_external_broken_link()
    {
        $link = [
            '[[http://domain.com/?a=1&b=&c=3 a text link]]',
            'http://domain.com/?a=1&b=&c=3 a text link'
        ];

        $linkPost = '[http://domain.com/?a=1&b=&c=3 a text link]';

        $this->assertEquals($linkPost, $this->cleanLinkFlat->cleanLink($link));
    }
}
