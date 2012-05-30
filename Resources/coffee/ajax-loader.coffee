# Ajax loader
App.AjaxLoader = {}

# Abstract ajax loader. Should not be instantiated.
class App.AjaxLoader.Abstract
    running = false

    start: ->
        if !running
            running = true
            @onStart()

    stop: ->
        if running
            @onStop()
            running = false

    isRunning: ->
        running

    # Implement me in a child class.
    # onStart: ->

    # Implement me in a child class.
    # onStop: ->


# Default ajax loader
class App.AjaxLoader.Default extends App.AjaxLoader.Abstract
    constructor: (@containerElement = '#container', @loaderElement = '<div id="ajax-loader">loading...</div>') ->

    onStart: ->
        $(@containerElement).append @loaderElement

    onStop: ->
        $('#' + $(@loaderElement).attr 'id').remove()
