<?php

namespace Xi\Bundle\AjaxBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Checks if response is meant to be json and encodes it if it does.
 * Also Fakes json response if multipart form is used as some browsers (IE) cant handle json response in this case.
 *
 * @author Tohtori & Johtajamme suuri
 */
class JsonResponseListener
{
    
    public function onKernelRequest(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        if(is_array($controllerResult) && isset($controllerResult['jsonresponse'])) {
            $request = $event->getRequest();

            if($request->getMethod() == 'POST' && preg_match('#^multipart\/form-data#', $request->headers->get('content-type'))) {
                $response = new Response('<textarea>'.json_encode($controllerResult['jsonresponse']).'</textarea>');
                $response->headers->set('Content-Type', 'text/html');  
            } else {
                $response = new Response(json_encode($controllerResult['jsonresponse']));
                $response->headers->set('Content-Type', 'application/json');                
            }

            $event->setResponse($response); 
        }
    }
    
}
