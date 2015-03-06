App.AjaxElement = {}
# Abstract ajax element. Should not be instantiated.
# Be adviced that you must create your own callback methods in your own
# implementation if you are about to use callbackMethod functionality.
class App.AjaxElement.Abstract extends App.AjaxAbstractLogic

    constructor: (@selector, @loader, @errorizers) ->
        super(@selector, @loader, @errorizers)

        # if there is no initial errorizers add default form errorizer to first of errorizers
        if !@errorizers
            @errorizers  = [new App.ElementErrorizer.Default()].concat(@errorizers)

        @currentConfig = @getConfiguration()
        @bind(@currentConfig)

    # get configuration and mix some defaults to them
    getConfiguration: =>
        options = {
            type:               "post"
            dataType:           "html"
            async:              true
            cache:              false
            event:              "click"
        }
        # pass the scope of this object to the constructor, to properly bind the functions
        $.extend(super(@), options)

    #Binds rules to your selector
    bind: (options) =>
        self = this

        $('body').on(options.event, @selector, () ->
            $.ajax({
                url         :   $(this).attr('href')
                type        :   options.type
                async       :   options.async
                cache       :   options.cache
                dataType    :   options.dataType
                success     :   (data, textStatus, jqXHR) =>
                    try
                        options.success($.parseJSON(data), textStatus, jqXHR, $(this))
                    catch error                                                         # "Not parseable JSON response."
                        self.handleFailure(data, $(this))

                beforeSend  :   (jqXHR, settings) =>
                    options.beforeSubmit(jqXHR, $(this), settings)
                error       :   options.error
            })

            return false
        )

    # Called after link has been clicked and before the request is sent.
    preSubmit: ($element) ->
        if @loader.isRunning()
            return false

        @loader.start()

# Default ajax element implementation.
class App.AjaxElement.Default extends App.AjaxElement.Abstract

# Form errorizer
App.ElementErrorizer = {}

# simple errorizer that creates hovering error message box next to activated element
# it automatically hides error after 2 seconds
class App.ElementErrorizer.Default extends App.AbstractErrorizer

    # errorizeClass : your class in error div
    # offsetLeft    : how much left offset you want for your message box
    # offsetTop     : how much top offset you want for your message box
    constructor: (@errorizeClass = 'errorized-element', @offsetLeft = 0, @offsetTop = 0) ->

    errorize: ($link, response) ->
        if response.failure
            offset = $link.offset()
            $('body').append('<div class="'+@errorizeClass+'" style="top:' + (offset.top+@offsetTop)+ 'px; left:'+(offset.left+@offsetLeft)+'px;">'+response.failure+'</div>')
            $("."+@errorizeClass).delay(2000).hide('slow', () =>
                $("."+@errorizeClass).remove()
            )
            true
        else
            false

    clear: =>
        $("."+@errorizeClass).hide('slow', () =>
            $("."+@errorizeClass).remove()
        )
