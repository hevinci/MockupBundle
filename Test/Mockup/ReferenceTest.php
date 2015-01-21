<?php

namespace HeVinci\MockupBundle\Mockup;

class ReferenceTest extends \PHPUnit_Framework_TestCase
{
    public function testFromPath()
    {
        $ref = Reference::fromPath('FooBarBundle', 'baz/test.html.twig');
        $this->assertEquals(
            'FooBarBundle::mockup/baz/test.html.twig',
            $ref->getTemplateReference()
        );
        $this->assertEquals('baz/test.html', $ref->getShortReference());
        $this->assertEquals(1, $ref->getDepthLevel());
    }

    public function testFromTemplate()
    {
        $ref = Reference::fromTemplate('fake::mockup/index.html.twig');
        $this->assertEquals(
            'fake::mockup/index.html.twig',
            $ref->getTemplateReference()
        );
        $this->assertEquals('index.html', $ref->getShortReference());
        $this->assertEquals(0, $ref->getDepthLevel());
    }
} 