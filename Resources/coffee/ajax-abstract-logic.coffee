window.App = window.App || {}

# Abstract element errorizer. Should not be instantiated.
class App.AbstractErrorizer
    # Implement me in a child class.
    errorize: ($element, formErrors) ->

    # Implement me in a child class.
    clear: ($element) ->


App.FatalErrorizer = {}

# simple errorizer that creates hovering error message box next to activated element
# it automatically hides error after 2 seconds
class App.FatalErrorizer.Default extends App.AbstractErrorizer

    constructor: (@errorizeClass = 'errorized-element', @offsetLeft = 0, @offsetTop = 0) ->

    errorize: ($element, response) ->
        if response && response.error   # statuscode other than 200
            @handleErrorResponse($element, response)
        else if typeof response == 'string' and response.search(/Fatal error:/i) != -1    # fatal errors: empty responses and strings
            @handleFatalErrorResponse($element, response)
        else
            @handleUnknownErrorResponse($element, response) # something else

    handleErrorResponse: ($element, response) ->
        message = response.error.xhr.status+" "+response.error.xhr.statusText+" "+@getBasicMessage()
        @renderError($element, message)

    handleFatalErrorResponse: ($element, response) ->
        message = "An unrecoverable error occurred. "+@getBasicMessage()
        @renderError($element, message)

    handleUnknownErrorResponse: ($element, response) ->
        @handleFatalErrorResponse($element, response)

    getBasicMessage: () ->
        "<br/> Please refresh your browser and try again. <br />
         If the problem persists please inform us about it by contacting our technical support.
        "
    renderError: ($element, message) ->
        offset = $element.offset()
        $('body').append('<div class="'+@errorizeClass+' fatal-error" style="top:' + (offset.top+@offsetTop)+ 'px; left:50%; ">'+message+'</div>')
        $('.fatal-error').css('margin-left', '-'+$('.fatal-error').width()/2+'px')
        $('body').append('<div class="ui-widget-overlay fatal-error-modal" style="width:'+$(document).width()+'px; height:'+$(document).height()+'px; "></div>');

    clear: =>
        $("."+@errorizeClass).hide('slow', () =>
            $("."+@errorizeClass).remove()
            $('fatal-error-modal').remove()
        )


# Abstract for ajax functionality. Should not be instantiated.
# Be adviced that you must create your own callback methods in your own
# implementation if you are about to use callbackMethod functionality.
class App.AjaxAbstractLogic

    currentElement: null   # we need store current element, so it can be used if response is failure

    constructor: (@selector, @loader, @errorizers) ->
         # add default fatal errorizer to end
        if !@errorizers  or !@errorizers.length
            @errorizers = [new App.FatalErrorizer.Default]
        else
            @errorizers.push(new App.FatalErrorizer.Default)

        if @loader
            @loader = new App.AjaxLoader.Default()

    # Get ajax configuration object.
    # @param that The object used as scope to use for the functions
    getConfiguration: (that) =>

        unless that
            that = @

        return {
            # Success callback
            success: (response, statusText, xhr, @element) =>
                that.element = @element # restore element property, avoid BC break
                that.preHandleResponse @element
                response = that.validateAndParseJsonResponse(response)

                if response && response.success
                    that.handleSuccess response.success
                    return true
                else if response
                    that.handleFailure response, @element
                false

            error: (xhr, ajaxOptions, thrownError) =>
                that.preHandleResponse that.currentElement  #we use stored currentElement because failure does not return it
                that.handleFailure { 'error': {'xhr': xhr, 'ajaxOptions': ajaxOptions, 'thrownError': thrownError} }, that.currentElement

            # Before submit callback
            beforeSubmit: (data, $element, options) =>
                that.currentElement = $element   # this line is mandatory! We need store current element, so it can be used if response is failure
                that.preSubmit $element
        }

    # validates json response and try to parse it if able
    validateAndParseJsonResponse: (response) =>
        if !response
            @handleFailure(response, @element)
        else if !response.success && !response.failure

            try  # we try to parse response to json because jquery.form does not understand json response after file is uploaded.
                parsedResponse = $.parseJSON($(response).text())
            catch error                                                         # "Not parseable JSON response."
                @handleFailure(response, @element)

            if !parsedResponse                                                      # "Not json at all"
                @handleFailure(response, @element)
            else if !parsedResponse.success && !parsedResponse.failure              # "Unknown json response received."
                @handleFailure(parsedResponse, @element)

            return parsedResponse

        return response

    # Called after success response has been received.
    handleSuccess: (success) ->
        if success.redirect
            window.location.href = success.redirect
        else if success.reload
            window.location.reload()

        # if successWithContent is defined script will try to find your callback method.
        # if callback method is faulty an exception is thrown
        if success.content
            if !success.callback
                throw "you don't have callback defined in your response"
            try
               @[success.callback](success.content)
            catch e
                if e instanceof TypeError
                    throw "you don't have callback method defined!\n" + e
                else
                    throw e

    # Called after failure response has been received.
    handleFailure: (failure, $element) ->
        @displayErrors $element, failure
        false;

    # Calls errorizers to show errors
    displayErrors: ($element, response) =>
        for errorizer in @errorizers
            if errorizer.errorize $element, response
                return true

    # Clear errors
    clearErrors: ($element) =>
        for errorizer in @errorizers
            errorizer.clear $element

    # Called after submit has been clicked and before the request is sent.
    preSubmit: ($element) ->
        @loader.start()

    # Called after response has been determined to be either success or failure.
    preHandleResponse: ($element) ->
        @loader.stop()


