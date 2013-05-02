<?php

namespace Xi\Bundle\AjaxBundle\Tests\Controller;

use PHPUnit_Framework_TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\ResolvedFormType;
use Xi\Bundle\AjaxBundle\Controller\JsonResponseController;
use Xi\Bundle\AjaxBundle\Tests\Model\TestUser;
use Xi\Bundle\AjaxBundle\Tests\Form\Type\TestUserInfoFormType;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;

/**
 * @author Mikko Hirvonen <mikko.petteri.hirvonen@gmail.com>
 * @author Henri Vesala <henri.vesala@gmail.com>
 * @group  xi
 * @group  xi-json-response-controller
 */
class JsonResponseControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var Container
     */
    private $container;

    public function setUp()
    {
        parent::setUp();

        $this->controller = new JsonResponseController();

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
                   ->method('trans')
                   ->will($this->returnCallback(function($trans) {
                       return 'translated ' . $trans;
                   }));

        $this->container = new Container();
        $this->container->set('translator', $translator);

        $this->controller->setContainer($this->container);
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
        $form->get('name')->addError(new FormError('xoo'));

        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'name' => array('translated xoo')
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
        $form->get('name')->addError(new FormError('xoo'));
        $form->get('userInfo')->get('address')->addError(new FormError('bar'));

        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'name'     => array('translated xoo'),
                        'userInfo' => array(
                            'childErrors' => array(
                                'address' => array('translated bar'),
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
        $form->get('userInfo')->get('address')->addError(new FormError('bar'));
        
        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'userInfo' => array(
                            'childErrors' => array(
                                'address' => array('translated bar'),
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
        $form->get('name')->addError(new FormError('xoo'));
        
        $this->assertEquals(
            array(
                'my_form' => array(
                    'childErrors' => array(
                        'name' => array('translated xoo'),
                    ),
                ),
            ),
            $this->controller->getFormErrorsForJson($form)
        );
    }

    /**
     * @test
     */
    public function formGeneralErrors()
    {
        $form = $this->createUserForm();
        $form->addError(new FormError('foo'))
             ->addError(new FormError('bar'));

        $this->assertEquals(
            array(
                'my_form' => array(
                    'errors' => array(
                        'translated foo',
                        'translated bar',
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
        return $this->createNamedFormBuilder()->add('name')->getForm();
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
        $formType = new FormType();
        $resolvedTypeFactory = new ResolvedFormTypeFactory($formType, array(new CoreExtension()));

        $registry = new FormRegistry(array(new CoreExtension()), $resolvedTypeFactory);
        $factory = new FormFactory($registry, $resolvedTypeFactory);

        return $factory->createNamedBuilder('my_form', $formType, new TestUser());
    }
}
