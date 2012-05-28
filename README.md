# Ajax Functionality for symfony2

This bundle provides basic ajax functionality for your Symfony2 project.
You can use it in your forms and any other elements that you wish to bind to send data asyncronously.
It also gives use a good indication what's going on by customizable loading indicator and if
you have problems in your backend it manages to fail gracefully with notification to user.



## Installing

### deps -file
```
[XiAjaxBundle]
    git=http://github.com/xi-project/xi-bundle-ajax.git
    target=/bundles/Xi/Bundle/AjaxBundle
```

### autoload.php file
```php
<?php
'Xi\\Bundle'       => __DIR__.'/../vendor/bundles',
?>
```
### appKernel.php -file
```php
<?php
            new Xi\Bundle\AjaxBundle\XiAjaxBundle(),
 ?>
```   

##  AjaxAbstractLogic
This is the base class for ajax logics. You won't use this directly.
You can ofcourse extend this if you want to do your own custom ajax logic

## AjaxForm

The template includes AjaxForm functionality that helps you to make ajax functionality to your forms.

1.  Make sure that you initialize AjaxForm in your CoffeeScript file: `new App.AjaxForm.Default '.ajax-form'`
    - The first argument is required. It specifies the forms you would like to ajaxify.
2.  Make a form with class named after your identifier: `<form class="ajax-form" ... >`
3.  You can make your own instances of AjaxForm. Just extend the abstract base class.
    `class YourNamespace.AjaxForm.YourName extends App.AjaxForm.Abstract`
4.  Implement backend logic for form submitting.

```php
<?php

namespace My\ProjectBundle\Controller;

use SBA\GenericBundle\Controller\JsonResponseController;

class MyController extends JsonResponseController
{
    public function saveAction()
    {
        $form = ...

        if ($form->bindRequest($this->getRequest())->isValid()) {
            // Form is valid. Do something.

            // Redirects automatically to given route.
            return $this->createJsonSuccessRedirectResponse('my_route_name');
        }

        // Form is invalid. Return form failure JSON response, which
        // AjaxForm automatically errorizes for you.
        return $this->createJsonFormFailureResponse($form);
    }
}
```

### App.FormErrorizer.Default
This is the default form errorizer that creates messagebox above or below (configurable) to failed form element.
Messages stays in place until you submit that form again unless you manualy define fadeout time in constructor.


### AjaxElement

The template also includes AjaxElement functionality that helps you to bind ajax functionality to any element.
Of course you would mostlikely use it in anchors. 

1.  Make sure that you initialize AjaxElement in your CoffeeScript file: `new App.AjaxElement.Default '.ajax-link'`
    - The first argument is required. It specifies the elements you would like to ajaxify.
2.  Make a form with class named after your identifier: `<a class="ajax-form" href="your action"... >`
3.  You can make your own instances of AjaxElement. Just extend the abstract base class.
    `class YourNamespace.AjaxElement.YourName extends App.AjaxElement.Abstract`
4.  Implement backend logic for your action.

```php
<?php

namespace My\ProjectBundle\Controller;

use SBA\GenericBundle\Controller\JsonResponseController;

class MyController extends JsonResponseController
{
    // you most likely want to pass some parameter to action
    public function someAction($some_parameter)
    {
        // if parameter is what you want
        if ($some_parameter) {
            // let do something: example reload the page
            return $this->createJsonSuccessReloadResponse();
        } else {
            // lets send a message that parameter is invalid
            return $this->createJsonFailureResponse('some parameter was false...');
        }   
    }
}
```

### App.ElementErrorizer.Default
This is the default Element errorizer. It just creates a box with message next to your activated ajax element.
Message stays for 2 seconds and then dissapears.

Be adviced that you need some styles to for box to show up:

```css
.errorized-element {
    position: absolute;
    z-index: 10000;
    padding: 5px 10px;
    background: red;
    border-radius: 4px;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    font-size: 14px;
}

.fatal-error-modal {
    z-index:            9999;
    background-color:   #000;
    opacity:            .70;
    top:                0px;
    position:           absolute;
    display:            block;
}

```

## Backend logics for ajax functions

### Json Success Response
Just tells that everyting went well and our script may remove the loading animation
No real feedback is given user

```php
<?php
    createJsonSuccessResponse('your response text')
?>
```

### Json Success Redirect Response
Simple redirect request for javascript. Very usable in your backend save actions. 

```php
<?php
    createJsonSuccessRedirectResponse('your_route_name', array('some_id' => 1))
?>
```

### Json Success Reload Response
Sometimes you just want to reload the page. For example if you are using common functionality 
in different context you may not want some fixed route to be redirected. Instead simple page reload will do fine. 

```php
<?php
    createJsonSuccessReloadResponse()
?>
```

### Json Failure Response
Something went wrong. But we are trying to recover from it. Message is passed to errorhandlers and its up to them how
error is presented to user.

```php
<?php
    createJsonFailureResponse('something went wrong...')
?>
```

### Json Form Failure Response
When form is invalid you wish to inform user about it. This function sends form errors to errorhandlers in nice little packet

```php
<?php
    createJsonFormFailureResponse(Form $form)
?>
```

### Json success with content

To use JSON success with content functionality you must define your 
own callback in your javascript file. 

The following example contains `returnCallbackAction` that returns content of some
template file. It also returns javascript callback to be called `yourOwnCallback`

Example:

```php
<?php
public function returnCallbackAction() 
{
    return $this->createJsonSuccessWithContent(
        $this->renderView('YourBundle:SomeName:fileToBeRendered.html.twig'),
        'yourOwnCallback'
    );
}
?>
```

In javascript side you must extend your own presentation of ajaxform and add
`yourOwnCallback` there. In this case `yourOwnCallback` content is the template
file you rendered in your controller. 
The following example is written in Coffeescript.

```coffeescript

class App.AjaxForm.Customized extends App.AjaxForm.Default
    yourOwnCallback: (content) ->
        $('#your-container').html(content)

```
