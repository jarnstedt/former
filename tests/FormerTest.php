<?php
use Jarnstedt\Former\Former;
use \Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollection;
use Illuminate\Html\FormBuilder;

class FormerTest extends \PHPUnit_Framework_TestCase {

    public function setUp()
    {
        parent::setUp();
        $this->urlGenerator = new UrlGenerator(new RouteCollection, Request::create('/foo', 'GET'));
        $this->htmlBuilder = new HtmlBuilder($this->urlGenerator);
        $this->config = m::mock('\Illuminate\Config\Repository');
        $this->session = m::mock('\Illuminate\Session\Store');
        $this->config->shouldReceive('get')
            ->andReturn('');

        $this->session->shouldReceive('get')
            ->once()
            ->andReturn(array());
        $this->session->shouldReceive('getToken')
            ->once()
            ->andReturn('');
        
        $this->former = new Former(
            $this->htmlBuilder,
            $this->urlGenerator,
            $this->session,
            $this->config
        );
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * Test creating Former object
     */
    public function testCreate()
    {
        $this->assertInstanceOf('Jarnstedt\Former\Former', $this->former);
    }

    public function testMakeWithNoParameters()
    {
        $result = $this->former->make();
        $this->assertInstanceOf('Jarnstedt\Former\Former', $result);
    }

    public function testOpen()
    {
        $result = $this->former->make(array('foo' => 'bar'));
        $this->assertInstanceOf('Jarnstedt\Former\Former', $result);
        $this->session->shouldReceive('getOldInput')->andReturn('test');
        $html = $result->open();

        $this->assertStringEndsWith('value="test">', $html);
    }

    /**
     * Test text input field
     */
    public function testText()
    {
        // mock old input
        $this->session->shouldReceive('getOldInput')
            ->andReturn(null);

        $form = $this->former->make();
        $text = $form->text('name', 'Text.req', 'default value', array('class' => 'custom-class'));

        $this->assertContains('for="name"', $text);
        $this->assertContains('Text', $text);
        $this->assertContains('default value', $text);
        $this->assertContains('custom-class', $text);

        $text2 = $form->text('foobar', null, null, array('attribute' => 'something'));
        $this->assertContains('name="foobar"', $text2);
        $this->assertContains('attribute="something"', $text2);
    }

    /**
     * Test select input field
     */
    public function testSelect()
    {
        // mock old input
        $this->session->shouldReceive('getOldInput')
            ->andReturn(null);

        $form = $this->former->make();
        $options = array(
            'Example',
            'First',
            'Second',
        );
        $html = $form->select('foobar', 'Label', $options);
        $this->assertContains('name="foobar"', $html);
        $this->assertContains('Label', $html);
    }

    /**
     * Test submit button
     */
    public function testSubmit()
    {
        // mock old input
        $this->session->shouldReceive('getOldInput')
            ->andReturn(null);

        $form = $this->former->make();
        $btn1 = $form->submit("Submit");
        $this->assertEquals('<button type="submit" class="btn">Submit</button>', $btn1);
        $btn2 = $form->submit("Submit", array('class' => 'foobar'));
        $this->assertContains('foobar', $btn2);
    }

}