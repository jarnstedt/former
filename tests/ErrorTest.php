<?php
use Jarnstedt\Former\Former;
use \Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollection;
use Illuminate\Html\FormBuilder;

class ErrorTest extends \PHPUnit_Framework_TestCase {

    public function setUp()
    {
        parent::setUp();
        $this->urlGenerator = new UrlGenerator(new RouteCollection, Request::create('/foo', 'GET'));
        $this->htmlBuilder = new HtmlBuilder($this->urlGenerator);
        $this->config = m::mock('\Illuminate\Config\Repository');
        $this->session = m::mock('\Illuminate\Session\Store');

        $this->session->shouldReceive('getToken')
            ->once()
            ->andReturn('');
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * Test displaying error messages
     */
    public function testDisplayErrors()
    {
        // mock old input
        $this->session->shouldReceive('getOldInput')->andReturn(null);

        $bag = m::mock('\Illuminate\Support\ViewErrorBag');
        $bag->shouldReceive('first')->andReturn('Error message.');

        $this->session->shouldReceive('get')
            ->once()
            ->andReturn($bag);

        $this->config->shouldReceive('get')
            ->with('former::controlGroupError')
            ->once()
            ->andReturn('has-error');

        $this->config->shouldReceive('get')
            ->withAnyArgs()
            ->times(12)
            ->andReturn('');

        $this->former = new Former(
            $this->htmlBuilder,
            $this->urlGenerator,
            $this->session,
            $this->config
        );

        $form = $this->former->make();
        $form->setOption('bootstrap', true);
        $text = $form->text('test');

        // Should have error class 'has-error'
        $this->assertContains('has-error', $text);
    }

}
