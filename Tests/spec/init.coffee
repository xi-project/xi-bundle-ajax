# create a fake window etc.
jsdom = require("jsdom").jsdom
document = jsdom("<html><head></head><body>hello world</body></html>")
window   = document.createWindow()
global.document   = window.document

global.jQuery = require("jquery")

global.$ = global.jQuery
global.window = window

# fake the app on global scope
global.App = {}

# # return self when translation is not initialized
# String::t = () ->
#     @toString()
