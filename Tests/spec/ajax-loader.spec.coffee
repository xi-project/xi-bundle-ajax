require "./init.coffee"
require "../../Resources/coffee/ajax-loader.coffee"

describe "ajax-loader", ->
    container = $('<div id="container"></div>')
    loader = null

    beforeEach ->
        loader = new App.AjaxLoader.Default(container)
        $('body').append container

    afterEach ->
        container.remove()

    it "starts the loader", ->
        expect(loader).toBeDefined()
        loader.stop()
        loader.start()
        expect(container.find('#ajax-loader').length).toBeGreaterThan(0)
        loader.stop()

    it "stops the loader", ->
        loader.stop()
        loader.start()
        expect(container.find('#ajax-loader').length).toBeGreaterThan(0)
        loader.stop()
        expect(container.find('#ajax-loader').length).toEqual(0)

    it "appends only one loader element", ->
        loader.start()
        loader.start()
        loader.start()
        expect(container.find('#ajax-loader').length).toEqual(1)
        loader.stop()

    it "is not running when it's not started", ->
        expect(loader.isRunning()).toBe(false)

    it "is running when it's started", ->
        loader.start()
        expect(loader.isRunning()).toBe(true)
        loader.stop()

    it "stops running when it's stopped", ->
        loader.start()
        loader.stop()
        expect(loader.isRunning()).toBe(false)
