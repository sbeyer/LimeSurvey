/// Build with: 
///     ./build.sh profile=ls20 action=release internStrings=true optimize=shrinksafe
/// 
/// Questions? mmacasek@mitre.org
///

dependencies ={
    layers:  [
        {
          name: "dojo.js",
          dependencies: [
            "dojo.data.ItemFileReadStore",
            "dojo.fx",
            "dojo.parser",
            "dojo.Tooltip",
            "dojo.dnd",
            "dojo.dnd.Mover",
            "dojo.dnd.Movable",
            "dojo.nls",

            "dojox.fx.easing",
            "dojox.grid",
            "dojox.grid.Grid",
            "dojox.grid.VirtualGrid",
            "dojox.dtl",
            "dojox.dtl.Context",
            "dojox.dtl._HtmlTemplated",
            "dojox.string",
            "dojox.jsonPath",
            "dojox.data.jsonPathStore",
            
            "dijit.InlineEditBox",
            "dijit.form.Button",
            "dijit.form.CheckBox",
            "dijit.form.ComboBox",
            "dijit.form.CurrencyTextBox",
            "dijit.form.DateTextBox",
            "dijit.form.FilteringSelect",
            "dijit.form.Form",
            "dijit.form.NumberSpinner",
            "dijit.form.NumberTextBox",
      	    "dijit.form.Textarea",
            "dijit.form.TextBox",
            "dijit.form.TimeTextBox",
            "dijit.form.ValidationTextBox",
            "dijit.layout.BorderContainer",
            "dijit.layout.ContentPane",
            "dijit.Tooltip",
            "dijit.Editor",
            "dijit.Form",
            "dijit.form.MultiSelect",
            "dijit.Slider",
            
            "ls.widget.LoopInput",
            "ls.widget.QuestionChooser",
            "ls.widget.RadioSetEditor",
            "ls.widget.DateEditor",
            "ls.widget.InputEditor",
            /*"ls.widget.QuestionEditor",*/
            "ls.widget.TextBoxEditor"
          ]
        }
    ],
    prefixes: [
        [ "dijit", "../dijit" ],
        [ "dojox", "../dojox" ],
        [ "ls", "../ls" ]
    ]
};





/*

/dojo/dijit/Tooltip.js (15ms)dojo.js.uncompres
/dojo/dijit/_Widget.js (15ms)dojo.js.uncompres
/dojo/dijit/_base.js (14ms)dojo.js.uncompres
/dojo/dijit/_base/focus.js (18ms)dojo.js.uncompres
/dojo/dijit/_base/manager.js (19ms)dojo.js.uncompres
/dojo/dijit/_base/place.js (19ms)dojo.js.uncompres
/dojo/dijit/_base/popup.js (16ms)dojo.js.uncompres
/dojo/dijit/_base/window.js (15ms)dojo.js.uncompres
/dojo/dijit/_base/scroll.js (17ms)dojo.js.uncompres
/dojo/dijit/_base/sniff.js (16ms)dojo.js.uncompres
/dojo/dijit/_base/bidi.js (18ms)dojo.js.uncompres
/dojo/dijit/_base/typematic.js (18ms)dojo.js.uncompres
/dojo/dijit/_base/wai.js (20ms)dojo.js.uncompres
/dojo/dijit/_Templated.js (18ms)dojo.js.uncompres
/dojo/dijit/form/Form.js (21ms)dojo.js.uncompres
/dojo/dijit/form/TextBox.js (18ms)dojo.js.uncompres
/dojo/dijit/form/_FormWidget.js (18ms)dojo.js.uncompres
/dojo/dijit/form/Textarea.js (21ms)dojo.js.uncompres
/dojo/dijit/nls/Textarea.js (16ms)dojo.js.uncompres
/dojo/dijit/form/DateTextBox.js (16ms)dojo.js.uncompres
/dojo/dijit/_Calendar.js (16ms)dojo.js.uncompres
/dojo/dijit/form/_DateTimeTextBox.js (16ms)dojo.js.uncompres
/dojo/dijit/form/ValidationTextBox.js (18ms)dojo.js.uncompres
/dojo/dijit/form/nls/validate.js (16ms)dojo.js.uncompres
/dojo/dijit/form/Button.js (16ms)dojo.js.uncompres
/dojo/dijit/_Container.js (18ms)dojo.js.uncompres
/dojo/dijit/form/FilteringSelect.js (19ms)dojo.js.uncompres
/dojo/dijit/form/ComboBox.js (18ms)dojo.js.uncompres
/dojo/dijit/form/nls/ComboBox.js (17ms)dojo.js.uncompres
/dojo/dijit/form/NumberTextBox.js (18ms)dojo.js.uncompres
/dojo/dijit/form/CurrencyTextBox.js (17ms)dojo.js.uncompres
/dojo/dijit/form/NumberSpinner.js (17ms)dojo.js.uncompres
/dojo/dijit/form/_Spinner.js (18ms)dojo.js.uncompres
/dojo/dijit/form/CheckBox.js (20ms)dojo.js.uncompres
/dojo/dijit/form/TimeTextBox.js (21ms)dojo.js.uncompres
/dojo/dijit/_TimePicker.js (19ms)dojo.js.uncompres
/dojo/dijit/layout/BorderContainer.js (16ms)dojo.js.uncompres
/dojo/dijit/layout/_LayoutWidget.js (14ms)dojo.js.uncompres
/dojo/dijit/layout/ContentPane.js (17ms)dojo.js.uncompres
/dojo/dijit/nls/loading.js (16ms)dojo.js.uncompres

/ls/widget/LoopInput.js (16ms)dojo.js.uncompres

/dojo/dojox/dtl/_HtmlTemplated.js (19ms)dojo.js.uncompres
/dojo/dojox/dtl/html.js (19ms)dojo.js.uncompres
/dojo/dojox/dtl/_base.js (19ms)dojo.js.uncompres
/dojo/dojox/string/Builder.js (15ms)dojo.js.uncompres
/dojo/dojox/string/tokenize.js (17ms)dojo.js.uncompres
/dojo/dojox/dtl/Context.js (16ms)dojo.js.uncompres
/dojo/dojox/dtl/render/html.js (16ms)dojo.js.uncompres
/dojo/dojox/dtl/contrib/dijit.js (17ms)
*/







