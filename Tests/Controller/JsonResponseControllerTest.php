<?php

namespace Xi\Bundle\AjaxBundle\Tests\Controller;

use Xi\Bundle\AjaxBundle\Controller\JsonResponseController as Controller,
    SBA\Component\Test\ContainerTestCase,
    Xi\Bundle\AjaxBundle\Tests\Model\TestUser,
    Xi\Bundle\AjaxBundle\Tests\Form\Type\TestUserInfoFormType,
    Symfony\Component\Form\Form,
    Symfony\Component\Form\FormBuilder,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\Translation\Translator,
    Symfony\Component\Translation\MessageSelector,
    Symfony\Component\Translation\Loader\ArrayLoader;
    
/**
 * @author Mikko Hirvonen <mikko.petteri.hirvonen@gmail.com>
 * @author Henri Vesala <henri.vesala@gmail.com>
 * @group sba
 * @group sba-controller
 * @group sba-json-response
 */
class JsonResponseControllerTest extends ContainerTestCase
{
    /**
     * @var Controller
     */
    private $controller;

    public function setUp()
    {
        parent::setUp();

        $this->controller = new Controller();
        
        $container = $this->getContainer();
        $this->controller->setContainer($container);
    }

    /**
     * @test
     */
    public function successResponseDefaultFormat()
    {
        $this->assertEquals(
            array('jsonresponse' => array('success' => true)),
            $this->controller->createJsonSuccessResponse()
        );
    }

    /**
     * @test
     */
    public function failureResponseDefaultFormat()
    {
        $this->assertEquals(
            array('jsonresponse' => array('failure' => true)),
            $this->controller->createJsonFailureResponse()
        );
    }

    /**
     * @test
     * @dataProvider successProvider
     * @param string $expected
     * @param string $response
     */
    public function additionalSuccessParameter($expected, $response)
    {
        $this->assertEquals(
            $expected,
            $this->controller->createJsonSuccessResponse($response)
        );
    }

    /**
     * @return array
     */
    public function successProvider()
    {
        return array(           
            array(
                array('jsonresponse' => 
                    array('success' => 'lus')
                ), 
                'lus',
            ),
            
            array(
                array('jsonresponse' => 
                    array('success' => 
                        array('bar')
                    ),
                ), 
                array('bar')
            ), 
        );
    }
    
    /**
     * @test
     * @dataProvider failureProvider
     * @param array $expected
     * @param array $response
     */
    public function additionalFailureParameter($expected, $response)
    {
        $this->assertEquals(
            $expected,
            $this->controller->createJsonFailureResponse($response)
        );
    }

    /**
     * @return array
     */
    public function failureProvider()
    {
        return array(           
            array(
                array('jsonresponse' => 
                    array('failure' => 'foo'),
                ), 
                'foo'
            ),
            
            array(
                array('jsonresponse' => 
                    array('failure' => 
                        array('xoo'),
                    ),
                ), 
                array('xoo'),
            ), 
        );
    }

    /**
     * @test
     */
    public function addsRedirectToSuccessJsonResponse()
    {
        $controller = $this->getMock('Xi\Bundle\AjaxBundle\Controller\JsonResponseController', array(
            'generateUrl'
        ));

        $controller->expects($this->once())
                   ->method('generateUrl')
                   ->will($this->returnValue('xoo.html'));

        $this->assertEquals(
            array('jsonresponse' => array('success' => array("redirect" => 'xoo.html'))),
            $controller->createJsonSuccessRedirectResponse('route_name')
        );
    }

    /**
     * @test
     */
    public function jsonSuccessReloadResponse()
    {
        $this->assertEquals(
            array('jsonresponse' => array('success' => array('reload' => true))),
            $this->controller->createJsonSuccessReloadResponse()
        );
    }
 
    /**
     * @test
     */
    public function jsonSuccessWithContentResponse()
    {
         $this->assertEquals(
            array('jsonresponse' => array('success' => array("content" => "custom_content","callback" => "custom_callback"))),
            $this->controller->createJsonSuccessWithContent('custom_content', 'custom_callback')
        );          
    }
    
    /**
     * @test
     */
    public function jsonFormFailureResponse()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
                     ->disableOriginalConstructor()
                     ->getMock();

        $this->assertEquals(
            array('jsonresponse' => array("failure" => array("formErrors" => array()))),
            $this->controller->createJsonFormFailureResponse($form)
        );
    }

    /**
     * @test
     */
    public function formErrorsWithoutErrorsReturnsAnArray()
    {
        $this->assertEquals(
            array(),
            $this->controller->getFormErrorsForJson($this->createUserForm())
        );
    }

    /**
     * @test
     */
    public function formErrorsWithFailedChildren()
    {
        $form = $this->createUserForm();

        $form->bind(array());
        
        $this->loadValidationMessageTranslator();
        
        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'name' => array('translated.This value should not be blank')
                    ),
                ),
            ),
            $this->controller->getFormErrorsForJson($form)
        );
    }

    /**
     * @test
     */
    public function formErrorsWithFailedChildrenAndGrandchildren()
    {
        $form = $this->createUserWithChildForm();

        $form->bind(array());

        $this->loadValidationMessageTranslator();
        
        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'name'     => array('translated.This value should not be blank'),
                        'userInfo' => array(
                            'childErrors' => array(
                                'address' => array('translated.This value should not be blank'),
                            ),
                        ),
                    ),
                ),
            ),
            $this->controller->getFormErrorsForJson($form)
        );
    }

    /**
     * @test
     */
    public function formErrorsWithFailedChildrenAndGrandchildren2()
    {
        $form = $this->createUserWithChildForm();

        $form->bind(array('name' => 'xoo'));

        $this->loadValidationMessageTranslator();
        
        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'userInfo' => array(
                            'childErrors' => array(
                                'address' => array('translated.This value should not be blank'),
                            ),
                        ),
                    ),
                ),
            ),
            $this->controller->getFormErrorsForJson($form)
        );
    }

    /**
     * @test
     */
    public function formErrorsDoesNotReturnErrorsForValidChildren()
    {
        $form = $this->createUserWithChildForm();

        $form->bind(array('userInfo' => array('address' => 'foo')));

        $this->loadValidationMessageTranslator();
        
        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'name' => array('translated.This value should not be blank'),
                    ),
                ),
            ),
            $this->controller->getFormErrorsForJson($form)
        );
    }

    /**
     * @return Form
     */
    private function createUserForm()
    {
        return $this->createNamedFormBuilder()
                   ->add('name')
                   ->getForm();
    }

    /**
     * @return Form
     */
    private function createUserWithChildForm()
    {
        return $this->createNamedFormBuilder()
                    ->add('name')
                    ->add('userInfo', new TestUserInfoFormType())
                    ->getForm();
    }

    /**
     * @return FormBuilder
     */
    private function createNamedFormBuilder()
    {
        $factory = $this->getContainer()->get('form.factory');

        return $factory->createNamedBuilder(
            'form',
            'my_form',
            new TestUser(),
            array('csrf_protection' => false)
        );
    }
    
    /**
     * 
     * replace current translator with translator that can translate validation messages
     */
    private function loadValidationMessageTranslator()
    {
        $container = $this->getContainer();
        
        $translator = new Translator('en', new MessageSelector());
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', array('This value should not be blank' => 'translated.This value should not be blank'), 'en');
        $container->set('translator', $translator);
    }
}
