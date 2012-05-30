<?php

namespace Xi\Bundle\AjaxBundle\Tests\EventListener;

use Xi\Bundle\AjaxBundle\EventListener\JsonResponseListener,
    PHPUnit_Framework_TestCase,
    Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent AS Event,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @author Mikko Hirvonen <mikko.petteri.hirvonen@gmail.com>
 * @author Henri Vesala <henri.vesala@gmail.com>
 * @group  xi
 * @group  xi-json-response-listener
 */
class JsonResponseListenerTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * @test
     */
    public function handleJsonResponse()
    {
        $event = $this->createEvent(array('jsonresponse' => array('success' => true)));
        $listener = new JsonResponseListener();
        $listener->onKernelRequest($event);
 
        $this->assertEquals('application/json', $event->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('{"success":true}', $event->getResponse()->getContent());
    }
    
    /**
     * @test
     */
    public function handleNotJsonResponse()
    {
        $event = $this->createEvent('luss luss'); 
        $listener = new JsonResponseListener();
        $listener->onKernelRequest($event);
 
        $this->assertEquals(NULL, $event->getResponse());      
    }   
    
    /**
     * @test
     */
    public function handleMultipartJsonResponse()
    {  
        $event = $this->createEvent(array('jsonresponse' => array('success' => true)), 'POST', array('content-type' => 'multipart/form-data foobar lus lus xoo xoo')); 
        $listener = new JsonResponseListener();
        $listener->onKernelRequest($event);
        
        $this->assertEquals('text/html', $event->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('<textarea>{"success":true}</textarea>', $event->getResponse()->getContent());
    }    

    /**
     * @test
     */
    public function handleInvalidGETMultipartJsonResponse()
    {  
        $event = $this->createEvent(array('jsonresponse' => array('success' => true)), 'GET', array('content-type' => 'multipart/form-data foobar lus lus xoo xoo')); 
        $listener = new JsonResponseListener();
        $listener->onKernelRequest($event);
        
        $this->assertEquals('application/json', $event->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('{"success":true}', $event->getResponse()->getContent());
    }    
    
     /**
     * @test
     */
    public function handlePOSTJsonResponse()
    {  
        $event = $this->createEvent(array('jsonresponse' => array('success' => true)), 'POST'); 
        $listener = new JsonResponseListener();
        $listener->onKernelRequest($event);
        
        $this->assertEquals('application/json', $event->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('{"success":true}', $event->getResponse()->getContent());
    }   
    
    
    private function createEvent($content, $method = 'GET', $headers = array())
    {
        $server = array('REQUEST_METHOD' => $method);
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request', null, array(array(), array(), array(), array(), array(), $server));
        $event = new Event($this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'), $request, HttpKernelInterface::MASTER_REQUEST, $content);
        $event->getRequest()->headers = new HeaderBag($headers);        
        return $event;
    }

}
