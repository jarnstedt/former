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

        $bag = m::mock('bag');
        $bag->shouldReceive('first')
            ->andReturn('');

        $this->session->shouldReceive('get')
            ->once()
            ->andReturn($bag);
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
     * Test file input field
     */
    public function testFile()
    {
        // mock old input
        $this->session->shouldReceive('getOldInput')
            ->andReturn(null);

        $form = $this->former->make();
        $file1 = $form->file('test');
        $this->assertContains('name="test"', $file1);
        $this->assertContains('type="file"', $file1);
        $file2 = $form->file('foobar', array('class' => 'something'));
        $this->assertContains('name="foobar"', $file2);
        $this->assertContains('class="something', $file2);
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

    /**
     * Test getting default values from old input
     */
    public function testOldInput()
    {
        $this->session->shouldReceive('getOldInput')
            ->andReturn('foobar');
        $form = $this->former->make();
        $text = $form->text('test');
        $this->assertContains('foobar', $text);
    }

    /**
     * Test getting default values from model
     */
    public function testFormModel()
    {
        $this->session->shouldReceive('getOldInput')
            ->andReturn(null);

        $obj = m::mock('Obj');
        $obj->test = 'foobar';
        $obj->id = 3;

        // Text field
        $form = $this->former->make($obj);
        $text = $form->text('test');
        $this->assertContains('foobar', $text);

        // Select field
        $select = $form->select('id', null, array(1 => 'foo', 2 => 'bar', 3 => 'test', 4 => 'foobar'));
        $this->assertContains('value="3" selected', $select);
    }

}
