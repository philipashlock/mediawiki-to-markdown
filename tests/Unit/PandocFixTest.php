<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\PandocFix;

class PandocFixTest extends TestCase
{
    public function test_fix_empty_param_arguments()
    {
        $fixLink = new PandocFix();
        $link = ['[http://domain.com/?a=one&b=&c=3 link text]'];
        $linkPost = '[http://domain.com/?a=one&b=%20&c=3 link text]';
        $linkClean = $fixLink->urlFix($link);
        $this->assertEquals($linkPost, $linkClean);
    }

    public function test_fix_trailing_empty_param_arguments()
    {
        $fixLink = new PandocFix();
        $link = ['[http://domain.com/?a=one&b=&c= link text]'];
        $linkPost = '[http://domain.com/?a=one&b=%20&c=%20 link text]';
        $linkClean = $fixLink->urlFix($link);
        $this->assertEquals($linkPost, $linkClean);
    }

    public function test_fix_trailing_period_param_arguments()
    {
        $fixLink = new PandocFix();
        $link = ['[http://domain.com/?a=one.&b=two&c=three link text]'];
        $linkPost = '[http://domain.com/?a=one.%20&b=two&c=three link text]';
        $linkClean = $fixLink->urlFix($link);
        $this->assertEquals($linkPost, $linkClean);
    }
}
