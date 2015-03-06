App.AjaxForm = {}
# Abstract ajax form. Should not be instantiated.
# Be adviced that you must create your own callback methods in your own
# implementation if you are about to use callbackMethod functionality.
class App.AjaxForm.Abstract extends App.AjaxAbstractLogic

    constructor: (@selector, @loader, @errorizers) ->
        super(@selector, @loader, @errorizers)
        # if there is no initial errorizers add default form errorizer to first of errorizers
        if @errorizers
            @errorizers  = [new App.FormErrorizer.Default()].concat(@errorizers)

        $(@selector).ajaxForm(@getConfiguration())

        @bindSubmitClickHandler()

    getConfiguration: =>
        config = super(@)
        config.delegation = true    # support for dynamicaly loaded forms
        return config

    # Called after submit has been clicked and before the request is sent.
    # This is called after the submitButtonClick method
    preSubmit: ($form) ->
        @disableSubmits $form
        @loader.start()

    # Called when submit button is clicked
    # This is called before the preSubmit method and changing of the ajaxFrom settings is still possible
    # Clicked submit button is passed as second parameter
    submitButtonClick: ($form, $button) ->

    # Called after response has been determined to be either success or failure.
    preHandleResponse: ($form) ->
        @loader.stop()
        @clearErrors($form)
        @enableSubmits $form

    enableSubmits: ($form) ->
        @findFormSubmits($form).removeAttr 'disabled'

    disableSubmits: ($form) ->
        @findFormSubmits($form).attr 'disabled', 'disabled'

    findFormSubmits: ($form) ->
        $form.find('input[type="image"],input[type="submit"],button')

    bindSubmitClickHandler: () ->
        submitButtons = $(@selector)
            .find('input[type="image"],input[type="submit"],button')

        self = @

        $(submitButtons).click (event) ->
            $form = $(this).closest('form');
            self.submitButtonClick($form, $(this))


# Default ajax form implementation.
class App.AjaxForm.Default extends App.AjaxForm.Abstract

    # Called when submit button is clicked
    # This is called before the preSubmit method and changing of the ajaxFrom settings is still possible
    # Clicked submit button is passed as second parameter
    submitButtonClick: ($form, $button) ->
        super $form, $button
        @updateFormAction $form, $button

    # Change the action of the form if submit button has a data-action parameter
    updateFormAction: ($form, $button) ->
        if $button and $button.data('action')
            @storeFormAction $form
            $form.attr('action', $button.data('action'))
        else
            @restoreFormAction $form

    storeFormAction: ($form) ->
        if ! $form.data('main-action')
            $form.data('main-action', $form.attr('action'))

    restoreFormAction: ($form) ->
        if $form.data('main-action')
            $form.attr('action', $form.data('main-action'))

# Form errorizer
App.FormErrorizer = {}

# Basic div form errorizer.
class App.FormErrorizer.Default extends App.AbstractErrorizer
    constructor: (@errorizeClass = 'errorized', @messageClass = 'error', @errorGroupClass = 'error-group') ->
        @formErrorPosition = 'top'
        @formErrorFadeOutTime = null

    # Errorizes a form
    errorize: ($form, response) ->
        if response.failure && response.failure.formErrors
            formName = getFormName response.failure.formErrors

            # General errors
            if response.failure.formErrors[formName].errors
                @displayFormErrors $form, response.failure.formErrors[formName].errors

            # Child (= input) specific errors
            if response.failure.formErrors[formName].childErrors
                @_errorizeChildren $form, response.failure.formErrors[formName].childErrors, formName
            true
        else
            false

    # Clears form of errors
    clear: ($form) ->
        $form.find(".#{@errorizeClass}")
             .removeClass(".#{@errorizeClass}")
             .filter(".#{@messageClass}")
             .remove();

    # Display general form errors.
    displayFormErrors: ($form, messages) ->
        $(messages).each (i, message) =>
            element = @getErrorElement()

            if @formErrorPosition is 'bottom'
                $form.append element.text(message)
            else
                $form.prepend element.text(message)

            if (@formErrorFadeOutTime > 0)
                element.delay(@formErrorFadeOutTime).fadeOut();

    # Display an error next to a field.
    displayFieldError: (fieldId, errors) ->

        $field = $('[name^="' + fieldId + '"]').first()
        # grouped errors
        if $field.closest(".#{@errorGroupClass}").length
            $field = $field.closest(".#{@errorGroupClass}")
            $field.addClass @errorizeClass
            $.each errors, (i, message) =>
                $field.after @getWrappedError(message)

        else
            $field.addClass @errorizeClass
            $.each errors, (i, message) =>
                $field.after @getErrorElement().text message

    # Form may need some extra elements as wrapper...
    getWrappedError: (message) ->
        @getErrorElement().text message


    # Get error element for display.
    getErrorElement: ->
        $('<div/>', {'class': "#{@errorizeClass} #{@messageClass}"})

    # set form error position
    # 'top' OR 'bottom'
    setFormErrorPosition: (@formErrorPosition) ->

    # set form error fade out time
    # 'null OR amount in milliseconds
    setFormErrorFadeOutTime: (@formErrorFadeOutTime) ->

    # Errorizes children recursively.
    _errorizeChildren: ($form, childErrors, path) ->

        $(childErrors).each (i, child) =>
            $.each child, (inputId, errors) =>
                if  errors.errors
                    if typeof inputId =='string'
                        @displayFieldError(resolvePath(path, inputId), errors.errors)
                    else
                         @displayFormErrors $form, errors.errors

                if errors.childErrors
                    @_errorizeChildren $form, errors.childErrors, resolvePath(path, inputId)

                if $.isArray errors
                    @displayFieldError(resolvePath(path, inputId), errors)

    # Gets form name by extracting the key of given object.
    # For example { "my_form": { "errors": [...] } } returns "my_form".
    getFormName = (form) ->
        for k of form
            key = k

        key

    resolvePath = (path, inputId) ->
        path + '[' + inputId + ']'
