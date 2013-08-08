<?php

namespace Xi\Bundle\AjaxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extends Symfony controller with JSON success and failure functionality.
 *
 * @author Mikko Hirvonen <mikko.petteri.hirvonen@gmail.com>
 * @author Henri Vesala <henri.vesala@gmail.com>
 */
class JsonResponseController extends BaseController
{
    /**
     * @param mixed $response
     * @return array
     */
    public function createJsonSuccessResponse($response = null)
    {
        return $this->createResponseFor('success', $response);
    }

    /**
     * @param string $route
     * @param array $options  (key value pairs for route parameters)
     * @return array
     */
    public function createJsonSuccessRedirectResponse(
        $route,
        array $options = array()
    ) {
        return $this->createJsonSuccessResponse(array(
            'redirect' => $this->generateUrl($route, $options),
        ));
    }

    /**
     * @return array
     */
    public function createJsonSuccessReloadResponse()
    {
        return $this->createJsonSuccessResponse(array('reload' => true));
    }
    
    /**
     * Returns a response with success -status, content and callback that should
     * be called in your javascript file.
     * 
     * @param string $content
     * @param string $callback
     * @return array
     */
    public function createJsonSuccessWithContent($content, $callback)
    {
        return $this->createResponseFor('success', array(
            'content' => $content, 'callback' => $callback
        ));
    }
    
    /**
     * @param mixed $response
     * @return array
     */
    public function createJsonFailureResponse($response = null)
    {
        return $this->createResponseFor('failure', $response);
    }

    /**
     * @param Form $form
     * @return array
     */
    public function createJsonFormFailureResponse(Form $form)
    {
        return $this->createJsonFailureResponse(array(
            'formErrors' => $this->getFormErrorsForJson($form),
        ));
    }

    /**
     * Gets form errors for interoperability with javascript AjaxForm.
     *
     * @param Form $form
     * @return array
     */
    public function getFormErrorsForJson(Form $form)
    {
        $errors = array();
        $translator = $this->get('translator');
        
        if (count($form->getErrors())) {
            foreach ($form->getErrors() as $error) {
                $errors[$form->getName()]['errors'][] = $translator->trans(
                    $error->getMessageTemplate(), 
                    $error->getMessageParameters(),
                    $this->getValidationTranslationDomain()
                );
            }
        }

        if ($form->count()) {
            foreach ($form->all() as $child) {
                if ($child->count()) {
                    if ($childErrors = $this->getFormErrorsForJson($child)) {
                        $errors[$form->getName()]['childErrors'] = array_merge_recursive(
                            isset($errors[$form->getName()]['childErrors'])
                                ? $errors[$form->getName()]['childErrors']
                                : array(),
                            $childErrors
                        );
                    }
                } else if (count($child->getErrors())) {
                    $self = $this;
                    $errors[$form->getName()]['childErrors'][$child->getName()] = array_map(
                        function($error) use ($translator, $self) {
                            return $translator->trans(
                                $error->getMessageTemplate(),
                                $error->getMessageParameters(),
                                $self->getValidationTranslationDomain()
                            );
                        }, $child->getErrors()
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Processes a form and executes and returns the result of either success or
     * failure callback.
     *
     * @param  Form     $form
     * @param  callback $successCallback
     * @param  callback $failureCallback
     * @return mixed
     */
    protected function processForm(
        Form $form,
        $successCallback,
        $failureCallback = null
    ) {
        if ($form->bind($this->getRequest())->isValid()) {
            return $successCallback($form);
        }

        $self = $this;
        $failureCallback = $failureCallback ?: function($form) use ($self) {
            return $self->createJsonFormFailureResponse($form);
        };

        return $failureCallback($form);
    }

    /**
     * @param string $what
     * @param mixed $response
     * @return array
     */
    private function createResponseFor($what, $response = null)
    {
        return array('jsonresponse' => array(
            $what => $response === null ? true : $response,
        ));
    }

    /**
     * @return string
     */
    private function getValidationTranslationDomain()
    {
        if ($this->container->hasParameter($key = 'framework.validation.translation_domain')) {
            return $this->container->getParameter($key);
        }

        return 'validators';
    }
}
