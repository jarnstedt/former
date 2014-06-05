<?php
use Jarnstedt\Former\Former;
use \Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollection;
use Illuminate\Html\FormBuilder;

class FormerTest extends \PHPUnit_Framework_TestCase {

    protected function setUp()
    {
        $this->urlGenerator = new UrlGenerator(new RouteCollection, Request::create('/foo', 'GET'));
        $this->htmlBuilder = new HtmlBuilder($this->urlGenerator);
    }

    protected function tearDown()
    {
        m::close();
    }

    public function testCreate()
    {

        // $html = m::mock('HtmlBuilder');
        // $url = m::mock('UrlGenerator');
        // $csrfToken = 'test';

        $form = new Former($this->htmlBuilder, $this->urlGenerator, '');

        // $this->assertNotNull($form);
    }

}