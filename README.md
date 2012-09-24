# Ajax Functionality for symfony2

This bundle provides basic ajax functionality for your Symfony2 project.
You can use it in your forms and any other elements that you wish to bind to send data asyncronously.
It also gives use a good indication what's going on by customizable loading indicator and if
you have problems in your backend it manages to fail gracefully with notification to user.

## Requirements:
1. [jQuery](http://jquery.com) 
2. [jQuery.form](http://www.malsup.com/jquery/form) 

## Installing

### composer.json
```javascript
"require": {
    ...
    "xi/ajax-bundle": "2.1.x-dev"
}
```     

### AppKernel.php
```php
<?php

$bundles = array(
    ...
    new Xi\Bundle\AjaxBundle\XiAjaxBundle(),
);
```   

### base.html.twig -example
Here is an example how you could load your JavaScript files using assets.
Because ajax functionalities are in different files you can deside yourself which components you wish to load.

```html
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

{% javascripts filter="?yui_js" output="js/main.js"
    '@YourOwnBundle/Resources/js/jquery.form.js'
    '@XiAjaxBundle/Resources/coffee/ajax-abstract-logic.coffee'
    '@XiAjaxBundle/Resources/coffee/ajax-loader.coffee'
    '@XiAjaxBundle/Resources/coffee/ajax-form.coffee'
    '@XiAjaxBundle/Resources/coffee/ajax-element.coffee'
    '@YourOwnBundle/Resources/coffee/main.coffee'
%}
    <script src="{{ asset_url }}"></script>
{% endjavascripts %}
```


##  AjaxAbstractLogic
This is the base class for ajax logics. You won't use this directly.
You can of course extend this if you want to do your own custom ajax logic.

## AjaxForm

The template includes AjaxForm functionality that helps you to make ajax functionality to your forms.

1.  Install [jQuery.form](http://www.malsup.com/jquery/form) as AjaxForm uses this.
2.  Make sure that you initialize AjaxForm in your CoffeeScript file: `new App.AjaxForm.Default '.ajax-form'`
    - The first argument is required. It specifies the forms you would like to ajaxify.
3.  Make a form with class named after your identifier: `<form class="ajax-form" ... >`
4.  You can make your own instances of AjaxForm. Just extend the abstract base class.
    `class YourNamespace.AjaxForm.YourName extends App.AjaxForm.Abstract`
5.  Implement backend logic for form submitting.
6.  You can change the action of the form if submit button has a data-action parameter so you can have
    several ways to submit the form that have different actions

```php
<?php

namespace My\ProjectBundle\Controller;

use Xi\Bundle\AjaxBundle\Controller\JsonResponseController;

class MyController extends JsonResponseController
{
    public function saveAction()
    {
        $form = ...

        if ($form->bind($this->getRequest())->isValid()) {
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

use Xi\Bundle\AjaxBundle\Controller\JsonResponseController;

class MyController extends JsonResponseController
{
    public function someAction($someParameter)
    {
        if ($someParameter) {
            // Do something. For example reload the page.
            return $this->createJsonSuccessReloadResponse();
        } else {
            // Parameter was invalid.
            return $this->createJsonFailureResponse('Some parameter was false...');
        }
    }
}
```

### App.ElementErrorizer.Default
This is the default Element errorizer. It just creates a box with message next to your activated ajax element.
Message stays for 2 seconds and then disappears.

Be adviced that you need some styles to for the box to show up:

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

## Backend logic for ajax functions

### Json Success Response
Just tells that everyting went well and our script may remove the loading animation.
No real feedback is given user.

```php
<?php

$this->createJsonSuccessResponse('your response text')
```

### Json Success Redirect Response
Simple redirect request for javascript. Very usable in your backend save actions. 

```php
<?php

$this->createJsonSuccessRedirectResponse('your_route_name', array('some_id' => 1))
```

### Json Success Reload Response
Sometimes you just want to reload the page. For example if you are using common functionality 
in different context you may not want some fixed route to be redirected. Instead simple page reload will do fine. 

```php
<?php

$this->createJsonSuccessReloadResponse()
```

### Json Failure Response
Something went wrong. But we are trying to recover from it. Message is passed to errorhandlers and its up to them how
the error is presented to user.

```php
<?php

$this->createJsonFailureResponse('something went wrong...')
```

### Json Form Failure Response
When form is invalid you wish to inform user about it. This function sends form errors to errorhandlers in nice little packet

```php
<?php

createJsonFormFailureResponse(Form $form)
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
