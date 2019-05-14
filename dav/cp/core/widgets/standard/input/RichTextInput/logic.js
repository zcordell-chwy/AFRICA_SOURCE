 /* Originating Release: February 2019 */
RightNow.Widgets.RichTextInput = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.data.isTouchDevice = this._isTouchDevice;
            this.data.readOnly = this.data.attrs.read_only;
            this.data.isSubscribed = false;
            this.data.label = this.data.attrs.label_input;
            this.data.contentWasPruned = false;

            var data = this.data,
                instanceID = this.instanceID,
                Y = this.Y,
                self = RightNow.Widgets.RichTextInput,
                converter = new self.converter(data, instanceID, Y),
                viewController = new self.viewController(data, instanceID, Y);

            viewController.converter = converter;
            viewController.setEditor(new self.editor(data, instanceID, Y));
            viewController.setMenu(new self.menu(data, instanceID + '_Editor', Y));
            viewController.setInsertLinkDialog(new self.insertLinkDialog(data, instanceID, Y));
            viewController.setTooltip(new self.tooltip(data, instanceID, Y));

            this.viewController = viewController;

            // attempt to recompute the input size, if it is visible
            this.Y.one(window).on('windowresize', this.Y.throttle(RightNow.Event.createDelegate(this, function(){
                this.viewController.editor.queueRegrow();
            }), 200));

            this._subscribeToFormValidation();
            this._hintOverlay = null;
            this._initialInputHeight = null;
        },

        /**
         * Returns the field's value.
         * @param  {String=} type html or markdown; defaults to markdown
         * @return {Object}      Field value
         * @see RightNow.Widgets.RichTextInput.viewController#getValue
         */
        getValue: function(type) {
            return this.viewController.getValue(type);
        }
    },

    /**
     * Sets the label text.
     * @param {String} newLabel label text to set
     */
    setLabel: function(newLabel) {
        if (newLabel) {
            this.data.label = newLabel;
            this.Y.one(this.baseSelector + ' label .rn_LabelInput').set('text', newLabel);
        }
    },

    /**
     * Returns the label text.
     * @return {String} Label's text
     */
    getLabel: function() {
        return this.data.label;
    },

    /**
     * Reloads the editor portion of the widget
     * with the given contents.
     * @param  {String}  content  New HTML content
     * @param  {boolean} readOnly True if editor should be placed in read only mode
     */
    reload: function(content, readOnly) {
        this.data.js.initialValue = content || '';
        this.data.readOnly = typeof readOnly === 'undefined' ? this.data.attrs.read_only : readOnly;

        this._subscribeToFormValidation();
        this.viewController.reloadEditor();
        this.viewController.updateMenuForReadOnlyState();
        this._toggleErrorIndicator(false);
    },

    /**
     * Subscribes to the parent form's 'submit' event
     * in order to do validation. Subscribes only if
     * `this.data.readOnly` is false and the event hasn't
     * already been subscribed to.
     */
    _subscribeToFormValidation: function() {
        if (this.data.readOnly || this._subscribedToFormValidation) return;

        this.parentForm().on('submit', this.onValidate, this);
        this._subscribedToFormValidation = true;
    },

    /**
     * Displays an error message for the field.
     * @param  {String} message       Message to display
     * @param  {String} errorLocation ID in which to inject the message
     */
    _displayError: function(message, errorLocation) {
        var commonErrorDiv = this.Y.one('#' + errorLocation),
            label = this.getLabel();

        if (this.data.attrs.label_error_fieldname) {
            label = this.data.attrs.label_error_fieldname;
        }
        else if (this.data.attrs.name === 'SocialQuestion.Body') {
            label = RightNow.Interface.getMessage('QUESTION_LBL');
        }
        else if (this.data.attrs.name === 'SocialQuestionComment.Body') {
            label = RightNow.Interface.getMessage('COMMENT_LBL');
        }

        if (commonErrorDiv) {
            if (message.indexOf("%s") > -1) {
                message = RightNow.Text.sprintf(message, label);
            }
            else if (!RightNow.Text.beginsWith(message, label)) {
                message = (label + ' ' + message);
            }

            commonErrorDiv.append("<div><b><a href='javascript:void(0);' onclick='document.getElementById(\"" +
                this.baseDomID + '_Iframe' + "\").contentWindow.document.body.focus(); return false;'>" + message + "</a></b></div>");
        }
        this._toggleErrorIndicator(true);
    },

    /**
     * Turns the error indicators on and off.
     * @param  {Boolean} show T to show; F to hide
     */
    _toggleErrorIndicator: function(show) {
        var method = (show) ? 'addClass' : 'removeClass';
        this.Y.one(this.baseSelector + '_Input')[method]('rn_ErrorField');
        this.Y.one(this.baseSelector + '_Label')[method]('rn_ErrorLabel');
    },

    /**
     * Event handler executed when form is being submitted.
     * @param {String} type Event name
     * @param {Array} args Event arguments
     * @return {object|boolean} Event object if validation passes,
     *                                False otherwise
     */
    onValidate: function(type, args) {
        var eventObject = this.createEventObject(),
            globalEvent = 'evt_formFieldValidate',
            result = 'Pass',
            errors = [],
            error_location = args[0].data.error_location,
            label = this.getLabel(),
            value = this.getValue(),
            errorMessage;

        eventObject.data.value = value.text;
        this._toggleErrorIndicator(false);

        if (value.error) {
            this._displayError(value.error, error_location);
            result = 'Failure';
        }
        else if (!this.validate(errors, this.Y.Lang.trim(value.text))) {
            this.Y.Array.each(errors, function(error) {
                this._displayError(error, error_location);
            }, this);
            result = 'Failure';

            if (!(eventObject.data.value = this.Y.Lang.trim(value.text) ? value.text : '') && this.data.contentWasPruned) {
                this._displayError(this.data.attrs.label_content_stripped, error_location);
            }
        }

        RightNow.Event.fire(globalEvent + result, eventObject);

        return result === 'Pass' ? eventObject : false;
    },

    /**
     * Whether the current device is a touchscreeny device.
     * This is kinda sketchy, but originally adapted from
     * <https://github.com/Modernizr/Modernizr/blob/master/feature-detects/touch.js>.
     * Doesn't detect mobile FF multitouch.
     * @type {Boolean}
     */
    _isTouchDevice: 'ontouchstart' in window ||
        (window.DocumentTouch && document instanceof DocumentTouch)
});

/**
 * Rich text editor.
 */
RightNow.Widgets.RichTextInput.viewController = RightNow.Widgets.extend({
    /**
     * RightNow.Widgets.RichTextInput.editor instance
     * @type {Object}
     */
    editor: null,

    /**
     * RightNow.Widgets.RichTextInput.menu instance
     * @type {Object}
     */
    menu: null,

    /**
     * RightNow.Widgets.RichTextInput.tooltip instance
     * @type {Object}
     */
    tooltip: null,

    /**
     * RightNow.Widgets.RichTextInput.insertLinkDialog instance
     * @type {Object}
     */
    insertLinkDialog: null,

    /**
     * Whether the editor is in advanced mode
     * @type {Boolean}
     */
    advancedMode: false,

    /**
     * Constructor.
     * Due to the way this widget is built, a constructor
     * is required even if it does nothing.
     */
    constructor: function() {},

    /**
     * Returns the entered content.
     * @param {String=} type html or markdown, defaults to markdown
     * @return {Object} with the following keys:
     *                       -text: Markdown of the entered text (if type is markdown)
     *                       -html: HTML of the entered content (if type is html)
     */
    getValue: function(type) {
        var result = this.editor.getContent(),
            response = {};

        // Put in try/catch to account for how different browsers handle the conversion routines below,
        // especially when a bunch of invalid content is pasted into the editor.
        try {
            if (this.advancedMode) {
                result = this.converter.mdToHTML(this.Y.Lang.trim(this.converter.browserHTMLToMarkdown(result)));
            }

            result = this._removeDisallowedContent(result);

            if (type === 'html') {
                response.html = result.html;
            }
            else if (this.advancedMode) {
                response.text = this.converter.htmlToMd(this.Y.Lang.trim(result.html));
            }
            else {
                // in non-advanced mode, double-up the line breaks to make sure they get preserved in markdown
                response.text = this.converter.htmlToMd(result.html).replace(/\n/g, "\n\n");
            }
        }
        catch (e) {
            response.error = this.data.attrs.label_parsing_error;
        }

        return response;
    },

    /**
     * Sets the editor member and subscribes to its events.
     * @param {Object} editor RightNow.Widgets.RichTextInput.editor instance
     */
    setEditor: function(editor) {
        editor.on({
            'mouseup': this._updateTextState,
            'keydown': this._updateTextState,
            'keyboardFormattingChange': this._keyboardFormattingChange
        }, null, this);

        this.editor = editor;
    },

    /**
     * Reloads the editor component.
     */
    reloadEditor: function() {
        this.editor.reload();
    },

    /**
     * Sets the menu member and subscribes to its events.
     * @param {Object} menu RightNow.Widgets.RichTextInput.menu instance
     */
    setMenu: function(menu) {
        menu.after({
            bold:       this._bolden,
            italic:     this._italicize,
            link:       this._showLinkDialog,
            unlink:     this._unlink,
            bullet:     this._insertBullet,
            advanced:   this._toggleAdvancedMode
        }, null, this);

        this.menu = menu;
        this.updateMenuForReadOnlyState();
    },

    /**
     * Tells the menu to update itself in accordance
     * with the current state of `this.data.readOnly`.
     */
    updateMenuForReadOnlyState: function() {
        this.menu.updateForReadOnlyState();
    },

    /**
     * Sets the tooltip member.
     * @param {Object} tooltip RightNow.Widgets.RichTextInput.tooltip instance
     */
    setTooltip: function(tooltip) {
        this.tooltip = tooltip;
    },

    /**
     * Sets the insertLinkDialog member and subscribes to its events.
     * @param {Object} dialog RightNow.Widgets.RichTextInput.insertLinkDialog instance
     */
    setInsertLinkDialog: function(dialog) {
        dialog.on('insertLink', this._insertLink, this);

        this.insertLinkDialog = dialog;
    },

    /**
     * Fired from editor on mouse up or key down.
     * If text has been selected, the popup menu is shown, otherwise it's hidden.
     * The state of the text at the cursor position is updated in both the popup
     * and slide-out menus.
     * @param  {Object} e containing two properties:
     *                    -event: Mouse up DOM event
     *                    -selection: Selection object
     */
    _updateTextState: function(e) {
        var selection = e.selection,
            selectionMade = selection && !selection.collapsed;

        if(selectionMade && (e.event.type !== 'mouseup' || e.event.button === 1)) {
            this.menu.show(e.event, selectionMade);
        }
        else {
            this.menu.hide(selectionMade);
            this.menu.toggleLimitedFunctionality(false, '.rn_SlideMenu');
        }

        if (e.event.type === 'keydown') {
            RightNow.Event.fire("evt_formInputDataChanged", null);
        }
        this._showStateOfSelectedText(selection);
    },

    /**
     * Called when a keyboard shortcut that formats some text is triggered.
     * Windows automatically does the bolden / italicize.
     * Refresh the menu button indicators for the style that
     * will be applied when this handler finishes.
     * @param  {Object} e Contains three items:
     *                    - automatic: boolean whether the OS
     *                    automatically applied the formatting
     *                    - bold: boolean whether the operation was bold
     *                    - italic: boolean whether the operation was italic
     */
    _keyboardFormattingChange: function(e) {
        if (e.automatic) {
            this.menu.refreshSelectedStateOfButtons(e);
        }
    },

    /**
     * Given the selected set of nodes, shows whether it's bold, italic, etc.
     * Also detects if it's a link and displays the link tooltip if so.
     * @param  {Object=} selection Y.NodeList selected nodes or null if
     *                             nothing's selected
     * @return {Boolean}          Whether the popup menu should show
     */
    _showStateOfSelectedText: function(selection) {
        if (!selection || !selection.elements.size()) {
            this.menu.deselectAllButtons();
            this.tooltip.hide();
            return false;
        }

        var selectionData = this._getSelectionData(selection.elements),
            selectedNode = selectionData.node,
            aTag = selectionData.tag === 'A',
            ancestors = selection.ancestors;

        this.menu.refreshSelectedStateOfButtons({
            link:   aTag,
            bold:   selectedNode && this._nodeIsBold(selectedNode, ancestors),
            italic: selectedNode && this._nodeIsItalic(selectedNode, ancestors),
            bullet: this._nodeIsListItem(ancestors)
        });

        if (aTag && !selectionData.otherTextSelected && !selection.collapsed) {
            this._showLinkTooltip(selectedNode);
            return false;
        }

        return true;
    },

    /**
     * Given a NodeList, returns an object specifying the node, tagName, and whether other text is selected.
     * @param  {Object} list YUI NodeList
     * @return {Object} Selection data:
     *     - node {Object|null} First non text node or null,
     *     - tag {string|null} The node's tagName
     *     - otherTextSelected {bool} True if there is other text selected besides the selected node.
     */
    _getSelectionData: function(list) {
        var otherTextSelected = false,
            selectedNode = null,
            tag = null,
            _tag, text;

        list.some(function(node) {
            if (selectedNode && otherTextSelected) {
                return true;
            }

            if (this.Y.Lang.trim(node.get('text'))) {
                if (!selectedNode && (_tag = node.get('tagName'))) {
                    // Text nodes don't have a tagName attribute.
                    selectedNode = node;
                    tag = _tag;
                }
                else  {
                    otherTextSelected = true;
                }
            }
        }, this);

        return {
            node: selectedNode,
            tag: tag,
            otherTextSelected: otherTextSelected
        }
    },

    /**
     * Determines if the given node is bold:
     * - b tag
     * - strong tag
     * - bold font-weight style
     * - ancestor is a b or strong tag
     * - directly wraps one of these
     * @param  {Object} node      YUI Node
     * @param  {Array=} ancestors Ancestors of node
     * @return {Boolean}           Whether the node is bold
     */
    _nodeIsBold: function(node, ancestors) {
        var match = /^(STRONG|B)$/,
            tagName = node.get('tagName');

        return !!tagName &&
               (match.test(tagName) ||
               node.getStyle('fontWeight') === 'bold' ||
               (ancestors && !!this.Y.Array.grep(ancestors, match).length) ||
               this._wraps(node, 'bold'));
    },

    /**
     * Determines if the given node is italic:
     * - i tag
     * - em tag
     * - italic font style
     * - ancestor is a i or em tag
     * - directly wraps one of these
     * @param  {Object} node      YUI Node
     * @param  {Array=} ancestors Ancestors of node
     * @return {Boolean}           Whether the node is italic
     */
    _nodeIsItalic: function(node, ancestors) {
        var match = /^(I|EM)$/,
            tagName = node.get('tagName');

        return !!tagName &&
               (match.test(tagName) ||
               node.getStyle('fontStyle') === 'italic' ||
               (ancestors && !!this.Y.Array.grep(ancestors, match).length) ||
               this._wraps(node, 'italic'));
    },

    /**
     * Determines if the given list of nodes has an unordered list
     * @param  {Array} ancestors Ancestors of the node
     * @return {Boolean}           Whether the array has a UL
     */
    _nodeIsListItem: function(ancestors) {
        return !!this.Y.Array.grep(ancestors, /^UL$/).length;
    },

    /**
     * Determines if the given node simply wraps another node
     * (meaning their text content is identical)
     * whose style is specified by type.
     * @param  {Object} node YUI Node
     * @param  {String} type bold or italic
     * @return {Boolean}      T if the node wraps another specified
     *                          specified by type, otherwise false
     */
    _wraps: function(node, type) {
        if (node.all('*').size() === 1) {
            var child = node.get('firstChild'),
                text = node.get('textContent') || node.get('innerText'),
                childText = node.get('textContent') || node.get('innerText');

            if (text === childText) {
                if (type === 'bold') {
                    return this._nodeIsBold(child);
                }
                else if (type === 'italic') {
                    return this._nodeIsItalic(child);
                }
            }
        }

        return false;
    },

    /**
     * Displays the insert link dialog, passing in
     * placeholder text and url if the selection
     * contains those.
     */
    _showLinkDialog: function() {
        var selection = this.editor.getSelection('text'),
            placeholderText = '',
            url = '',
            selected;

        // Copy this object here to be used in the _createDialog fn when a user
        // will click on the cancel button. This is needed otherwise reference to the object is not available in _createDialog
        this.data.globalObject = this;

        if (selection) {
            placeholderText = selection;

            selection = this.editor.getSelection();
            selected = selection.elements.item(0);

            if (selected.get('tagName') === 'A') {
                url = selected.getAttribute('href');
            }
        }

        this.editor.stashIESelection();

        this.insertLinkDialog.show(placeholderText, url);
    },

    /**
     * Displays the tooltip for the given node.
     * Adds a one-time click listener on the main document and
     * editor document to hide the tooltip on the next document click.
     * @param  {Object} href Node representing <a> element
     */
    _showLinkTooltip: function(node) {
        var href = node.getAttribute('href');

        this.tooltip
            .setContent('<a href="' + href + '" target="_blank">' + href + '</a>')
            .reposition(node, this.Y.one(this.baseSelector + '_Input'))
            .show();

        var hideTooltipOnClick = function() { this.tooltip.hide(); };
        this.Y.one(document.body).once('click', hideTooltipOnClick, this);
        this.editor.getBody().once('click', hideTooltipOnClick, this);
    },

    /**
     * Inserts a link.
     * @param  {Object} link Link details;
     * should contain a url member and optionally a text member
     */
    _insertLink: function(link) {
        this.editor.insertLink(link);
        this.menu.toggleLimitedFunctionality(false, '.rn_SlideMenu');
        this.menu.hide();
    },

    /**
     * Removes the selected link.
     */
    _unlink: function() {
        this.editor.unlink();
        this.menu.toggleLimitedFunctionality(false, '.rn_SlideMenu');
        this.menu.hide();
    },

    /**
     * Applies bolding.
     */
    _bolden: function() {
        this.editor.boldTheSelection();
        this._showStateOfSelectedText(this.editor.getSelection());
        this._focusOnEventWidgetIframe();
    },

    /**
     * Applies italics.
     */
    _italicize: function() {
        this.editor.italicizeTheSelection();
        this._showStateOfSelectedText(this.editor.getSelection());
        this._focusOnEventWidgetIframe();
    },

    /**
     * Applies a bullet.
     */
    _insertBullet: function() {
        this.editor.insertBullet();
        this._showStateOfSelectedText(this.editor.getSelection());
        this._focusOnEventWidgetIframe();
    },

    /**
     * Focus on the event's widget iframe node.
     */
    _focusOnEventWidgetIframe: function() {
        var iframe = this.Y.one(this.baseSelector + '_Iframe');
        if(iframe) {
            iframe.focus();
        }
    },

    /**
     * Goes in and out of advanced mode. Converts the input to the
     * proper format.
     */
    _toggleAdvancedMode: function() {
        var output = this.editor.getContent();

        if (this.advancedMode) {
            // Going from advanced back to basic.
            this.menu.enableRichMode();
            this.menu.toggleLimitedFunctionality();

            output = this.converter.extractNormalizedMDFromHTML(output);
            output = this.converter.mdToHTML(output);
        }
        else {
            // Going from basic to advanced.
            this.menu.deselectAllButtons();
            this.menu.disableRichMode();

            output = this.converter.htmlToMd(output);
            // Need to render newlines as <br>s in the editor.
            output = output.replace(/\n/g, "<br>");
        }

        this.editor.setContent(output);

        this.advancedMode = !this.advancedMode;
        this.editor.setAdvancedMode(this.advancedMode);
    },

    /**
     * Removes unsupported content types (images, tables, etc.)
     * that may have been inserted via copy-paste.
     * @param  {String} html     HTML editor content
     * @return {Object} with the following keys:
     *                       -html: string of html with any disallowed content removed
     */
    _removeDisallowedContent: function(html) {
        var content = this.Y.Node.create('<div>' + html + '</div>');
        content.all(this.converter.disallowedContent).remove();
        pruned = content.getHTML();
        this.data.contentWasPruned = html.length > pruned.length;

        return { html: pruned };
    }
});

RightNow.Widgets.RichTextInput.editor = RightNow.Widgets.extend({
    /**
     * Whether the editor is in advanced mode.
     * @type {Boolean}
     */
    _advancedMode: false,

    /**
     * Initializes the editor and all its events.
     */
    constructor: function() {
        this.widget = this.Y.one(this.baseSelector + '_Input');
        this.editor = this._initializeEditor();

        if (!this.Y.UA.ie || this.Y.UA.ie > 8) {
            this.Y.later(200, this, this._autoGrowInputHeight, null, true);
        }

        this.Y.augment(this, this.Y.EventTarget);
    },

    /**
     * Returns the contents of the editor
     * @return {String} Contents
     */
    getContent: function() {
        return this.editor.getContent();
    },

    /**
     * Sets the contents of the editor
     * @param {html} html Contents
     */
    setContent: function(html) {
        this.editor.frame.getInstance().one('body').setHTML(html);
    },

    /**
     * Returns the body element of the editor.
     * @return {Object} YUI Node
     */
    getBody: function() {
        return this.editor.frame.getInstance().one('body');
    },

    /**
     * Sets the advancedMode property.
     * @param {Boolean} mode on or off
     */
    setAdvancedMode: function(mode) {
        this._advancedMode = mode;
    },

    /**
     * Queue up an editor regrow
     */
    queueRegrow: function() {
        this._recomputeHeightWhenVisible = true;
    },

    /**
     * The Frame component doesn't work properly if you move its
     * parent somewhere else in the DOM. So destroy and re-initialize.
     * Focus on it too.
     */
    reload: function() {
        this.editor.frame.destroy();
        this.editor = this._initializeEditor(true);
        if (this.shadowInput) {
            this.shadowInput.destroy();
            this.shadowInput = null;
        }
    },

    /**
     * Sets up the Y.Frame instance.
     * @param {Boolean=} focusWhenReady Whether to focus on the editor as soon
     *                                  as it's ready. If not specified, uses
     *                                  the `initial_focus` attribute value.
     */
    _initializeEditor: function(focusWhenReady) {
        this.widget.addClass('rn_Loading');

        var frame = new this.Y.Frame({
                content:    this.data.js.initialValue || '',
                extracss:   this._getDefaultStyles(),
                title:      this.data.label
            });

        // If initial content is given, there's a period of time between
        // when the frame renders, but its content doesn't.
        // Delay removal of the loading indicator until the content is
        // closer to rendering (typically 900-1000 ms).
        frame.onceAfter('render', this.Y.later(this.data.js.initialValue ? 800 : 300,
            this.widget, this.widget.removeClass, 'rn_Loading'));
        frame.after('dom:mouseup', function(e) {
            // Even though this is the 'after' event listener, the text selection often
            // hasn't been updated yet when this fires.
            // Delaying by one tick gives the browser time to do
            // its selection stuff properly.
            this.Y.Lang.later(100, this, this._onMouseUp, e);
        }, this);

        if (this.Y.UA.os === 'macintosh') {
            // The Mac CMD key isn't seen as a modifier, so it requires
            // that we keep track of this specific keypress.
            frame.on(['dom:keydown', 'dom:keyup'], function(e) {
                var cmdKeys = [
                    91,  // Left, Webkit
                    93,  // Right, Webkit
                    224, // Both, Firefox
                ];
                this.cmdKeyDown = e.type === 'keydown' && this.Y.Array.indexOf(cmdKeys, e.keyCode) > -1;
            }, this);
        }

        frame.on('dom:keydown', this._onKeyDown, this);

        frame.render(this.widget);
        frame.get('node')
            .setAttribute('title', frame.get('title'))
            .setAttribute('id', this.baseDomID + '_Iframe');

        var doc = this.Y.Node.getDOMNode(frame.get('node')).contentDocument;

        if (!this.data.readOnly) {
            // Setting the designMode = "on" attribute will make
            // the iframe's contents editable. But when this attribute
            // is set in webkit, the document then interprets the TAB
            // keypress as an editor would (inserting a TAB) instead of
            // focusing on the next-focusable-element, as is expected
            // of a browser input field.
            // Reference: <http://codepen.io/Stumblor/pen/ymuLB>
            doc.body.setAttribute("contenteditable", "true");
        }

        this._recomputeHeightWhenVisible = true;

        try {
            doc.execCommand('styleWithCSS', false);
            doc.execCommand('enableObjectResizing', true);
        }
        catch (IEDoesNotSupportTheseCommandsಠ_ಠ) {}

        if (this.data.attrs.hint && !this.data.attrs.always_show_hint) {
            this._initializeHint(doc.body);
        }

        focusWhenReady || (focusWhenReady = this.data.attrs.initial_focus);
        if (focusWhenReady) {
            doc.body.focus();
        }

        return {
            win:        this.Y.Node.getDOMNode(frame.getInstance().one('window')),
            doc:        doc,
            frame:      frame,
            getContent: function() { return frame.get('content'); }
        };
    },

    /**
     * Initializes the hint overlay
     * @param {Object} The input field
     */
    _initializeHint: function(input) {
        var overlay = this._hintOverlay = new this.Y.Overlay({
            bodyContent: this.Y.Node.create('<span class="rn_RichTextInputHint rn_HintBox">' + this.data.attrs.hint + '</span>'),
            visible: false,
            zIndex: 3,
            align: {
                node: this.baseSelector + '_Iframe',
                points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.BL]
            }
        });

        input.addEventListener('focus', function(){overlay.show();});
        input.addEventListener('blur', function(){overlay.hide();});
        overlay.render();
    },

    /**
     * The goal is to create an editor area that autogrows in height
     * as the user types more lines of text so that he/she doesn't have
     * to deal with a small textbox that has scrollbars--the
     * traditionally poor textbox experience.
     * In order to implement the autogrow behavior we
     * need a 'shadow' frame with none of the scroll restrictions that we have
     * on the 'real' editor frame. By updating the shadow frame's content to match
     * the real editor's content and then getting the shadow frame's natural
     * scrollHeight we know what height to set the real editor's height to.
     * @param  {number} bottomPadding The number of bottom padding pixels to use
     *                                for the shadow frame's body
     * @return {object}               Y.Frame instance
     */
    _createShadowInput: function (bottomPadding) {
        this.originalMinHeight = parseInt(this.widget.getComputedStyle('minHeight'), 10) || 100;
        this.maxGrowHeight = parseInt(this.widget.getComputedStyle('maxHeight'), 10) || 500;

        var shadowContainer = this.Y.Node.create('<div aria-hidden="true"></div>')
            .setStyles({
                visibility:         'hidden',
                height:             0
            });

        this.Y.one(this.baseSelector).append(shadowContainer);
        var shadowInput = new this.Y.Frame({
            extracss: this._getDefaultStyles() + 'body{padding-bottom:' + bottomPadding + 'px;}',
            // Since this is hidden from screen readers, it doesn't need a localized title.
            title:    this.data.label + ' shadow'
        });

        shadowInput.render(shadowContainer);

        shadowInput.get('node').setAttribute('title', shadowInput.get('title'));

        return shadowInput;
    },

    /**
     * Monitors the height of the input area and expands / contracts as necessary
     * (up to a certain threshold, that, with the reference impl. CSS is 100px - 500px).
     * By updating the shadow editor's content to what the user just typed and then comparing its scrollHeight, we
     * can manually set the editor's minHeight when a new line of text is entered.
     * The editor's initial min height is determined by any min-height styling that was applied thru CSS.
     * And the editor's max grow height is determined by the max-height styling that was applied thru CSS.
     */
    _autoGrowInputHeight: function() {
        var yInstance = (this.editor.frame) ? this.editor.frame.getInstance() : null,
            body = (yInstance) ? yInstance.one('body') : null;

        if (!yInstance | !body) return;

        var bottomPadding = 60,
            inputHeight = parseInt(this.widget.getComputedStyle('height'), 10),
            newInput = this.editor.getContent(),
            isVisible = this._elementIsVisible(this.editor.frame.get('node')),
            shadowInstance, shadowBody, height;

        (this.shadowInput || (this.shadowInput = this._createShadowInput(bottomPadding)));

        if (newInput !== this.previousInput ||
            (this._recomputeHeightWhenVisible && isVisible)) {
            // User has typed new content and the shadow editor is now out-of-sync with
            // the visible editor. Update its contents and then see if the visible editor's
            // height needs to be adjusted.

            if (this._advancedMode) {
                newInput.replace(/\n/, '<br>');
            }

            shadowInstance = this.shadowInput.getInstance();

            if (shadowInstance) {
                // Update the shadow frame with what the user just entered and then get
                // its new scrollHeight.
                shadowBody = shadowInstance.one('body').setHTML(newInput);
                shadowHeight = shadowBody.get('scrollHeight');

                if (shadowHeight !== inputHeight) {
                    // Update the editor's height to that the of the shadow frame.
                    if (shadowHeight === this.originalMinHeight + bottomPadding) {
                        shadowHeight = this.originalMinHeight;
                    }
                    shadowHeight += bottomPadding;

                    height = (shadowHeight < this.maxGrowHeight) ? shadowHeight : this.maxGrowHeight;

                    // Hide the hint overlay as soon as the input height increases
                    // so the user is not typing text underneath it.
                    (this._initialInputHeight || (this._initialInputHeight = height));
                    if (this._hintOverlay && height > this._initialInputHeight) {
                        this._hintOverlay.hide();
                    }

                    // Update the iframe container's (div.rn_InputArea) height.
                    this.widget.setStyle('minHeight', height);
                    // YUI sets the iframe's height attribute to '99%' by default.
                    // But also manually set it so that if the editor's height
                    // expands beyond the max grow height (below) but then content
                    // is deleted so that it's now less than the max grow height,
                    // the iframe height can be set back to its default.
                    this.editor.frame.get('node')
                        .setAttribute('height', '99%')
                        // ...And also update the iframe's height to avoid having
                        // the scrollbar appear when the user enters a new line and then
                        // disappear when this method is called within 200 ms.
                        // Set the iframe's minHeight less than its container so text does not overflow.
                        .setStyle('minHeight', height - 20);

                    if (isVisible) {
                        this._recomputeHeightWhenVisible = false;
                    }
                }
                else if (typeof this.previousInput === 'undefined' && !this._elementIsVisible(this.editor.frame.get('node'))) {
                    // When the editor is initially hidden (e.g. any edit form that
                    // is hidden on the page by default), the shadow frame's
                    // scrollHeight value that we're given is wildly incorrect
                    // (e.g. 3000 instead of 150).
                    // So set a flag so that the proper height is retrieved when
                    // the editor becomes visible.
                    this._recomputeHeightWhenVisible = true;
                }
            }
            this.previousInput = newInput;
        }
    },

    /**
     * Determines if an element is visible, accounting
     * for parent element visibility.
     * @param  {Object} el Y.Node
     * @return {Boolean}    True if the element, including its
     *                           parent hierarchy is visible,
     *                           False if the element is hidden
     *                           or el is falsey
     */
    _elementIsVisible: function (el) {
        // We've traversed up the DOM hierarchy and have not found
        // any display:none elements.
        if (!el || !el.get('tagName')) return true;
        // Only checks for the display styling, since that's what will
        // match `rn_Hidden` classes. Does not take into account
        // visibility, opacity, off-screen positioning, etc.
        if (el.getComputedStyle('display') === 'none') return false;

        return this._elementIsVisible(el.get('parentNode'));
    },

    /**
     * Called on mouse up.
     * If text has been selected, the popup menu is shown, otherwise it's hidden.
     * The state of the text at the cursor position is updated in both the popup
     * and slide-out menus.
     * @param  {Object} e Mouse up event
     */
    _onMouseUp: function(e) {
        if (this._advancedMode) return;

        this.fire('mouseup', {
            event: e,
            selection: this.getSelection()
        });
    },

    /**
     * Methods that should be called when the specified key codes are pressed (on mac).
     * @type {Object}
     */
    _keyCombos: {
        'boldTheSelection':      66,      // b
        'italicizeTheSelection': 73       // i
    },

    /**
     * Called on key down. Handles keyboard shortcuts.
     * @param  {Object} e Key down event
     */
    _onKeyDown: function(e) {
        if (!e.shiftKey && (
            (this.cmdKeyDown && !e.ctrlKey) ||
            (typeof this.cmdKeyDown === 'undefined' && e.ctrlKey))) {
            this.Y.Object.some(this._keyCombos, function(keyCode, method) {
                if (e.keyCode === keyCode) {
                    if (this.cmdKeyDown) {
                        // Manually bolden / italicize the text on mac.
                        this[method]();
                    }

                    // TK - Sadly, this doesn't take into account un-bolding and un-italicizing
                    this.fire('keyboardFormattingChange', {
                        bold: method.indexOf('bold') > -1,
                        italic: method.indexOf('italicize') > -1,
                        automatic: !this.cmdKeyDown
                    });

                    return true;
                }
            }, this);
        }

        if (!this._advancedMode) {
            // Advanced mode doesn't provide any context tooltips.
            this.fire('keydown', {
                event: e,
                selection: this.getSelection('cursor')
            });
        }
    },

    /**
     * Returns a NodeList of the selected nodes.
     * @param {String=} type The type of selection to return:
     *                       'text' - only care to get the selected text back
     *                       'cursor' - want to retrieve the container element
     *                                  of the cursor's position
     *                       If this parameter is omitted, the container element
     *                       of the current selection is returned (if nothing is
     *                       selected then null is returned)
     * @return {object|string|null} String selected text if type is 'text'
     *                              Null if type is omitted or 'cursor' and there's
     *                              no selection or cursor position
     *                              Object containing the following properties:
     *                                  -elements: NodeList containing the Nodes
     *                                             comprising the selection
     *                                  -ancestors: Array containing the tag names
     *                                              of the element hiearachy of the
     *                                              selection (up to and excluding
     *                                              the body element)
     */
    getSelection: function(type) {
        var subWindow = this.editor.win,
            selection = subWindow.getSelection ? subWindow.getSelection() : null,
            range;

        if (!selection) {
            // IE8
            range = this.editor.doc.selection.createRange();

            if (type === 'text') return range.text;
        }
        else if (selection && type === 'text') {
            return selection.toString();
        }
        else if (selection.rangeCount) {
            range = selection.getRangeAt(0);
        }

        if (!range) return;

        return this._captureSelectionWithParent(range);
    },

    /**
     * Given a Range object, returns a DOM node
     * representing the range's selection inside of its parent node.
     * @param  {Object} range Range or TextRange object
     * @return {object|null}       Null if nothing's selected.
     *                             Object containing the following:
     *                             -elements: Y.NodeList of selection
     *                             -collapsed: Boolean whether the Range represented
     *                                         a selection (false) or just a cursor (true)
     *                             -ancestors: Array containing tag names of ancestors
     */
    _captureSelectionWithParent: function(range) {
        var parent,
            collapsed,
            ancestors = [],
            selectedFragment;

        if (range.cloneContents) {
            // Range object
            selectedFragment = range.cloneContents();
            collapsed = range.collapsed;

            // IE: firstChild; everything else: parentNode (where, conveniently, firstChild is null).
            var startParent = range.startContainer.firstChild || range.startContainer.parentNode,
                endParent = range.endContainer.firstChild || range.endContainer.parentNode;

            if (startParent.tagName && startParent.tagName !== 'BODY') {
                if (startParent === endParent || startParent.contains(endParent)) {
                    parent = startParent;
                }
                // TK - this code was added to fix a bug. But then it was causing other bugs.
                // Since I couldn't check this in, I have no record of what the circumstances are....
                // else if (endParent.contains && endParent.contains(startParent)) {
                    // parent = endParent;
                // }
            }
        }
        else {
            // TextRange object (IE8 and lower)
            collapsed = !range.text;
            parent = range.parentElement();
        }

        if (parent) {
            // Need to capture some info for later on: the coordinates of the selection and the
            // ancestor chain of the selection.
            // This info doesn't exist on the cloned node.
            // Why clone the node at all? Why not just use the real node?
            // - Not cloning the node causes the selection to get nuked in some circumstances.
            // - Passing around references to a live DOM node on every mouse and key event is not the greatest.

            for (var nextParent = parent, tagName;
                nextParent && nextParent.tagName !== 'BODY'; nextParent = nextParent.parentNode) {
                tagName = nextParent.tagName;
                if (tagName === 'SPAN') {
                    // FF insists on using spans rather than semantic elements
                    if (nextParent.style.fontStyle === 'italic') tagName = 'EM';
                    else if (nextParent.style.fontWeight === 'bold') tagName = 'STRONG';
                }
                ancestors.push(tagName);
            }

            if (parent.setAttribute) {
                parent.setAttribute('data-offsetTop', parent.offsetTop);
                parent.setAttribute('data-offsetLeft', parent.offsetLeft);
                parent.setAttribute('data-offsetHeight', parent.offsetHeight);
                parent.setAttribute('data-offsetWidth', parent.offsetWidth);
            }

            parent = parent.cloneNode(true);
        }

        // Create a Y.NodeList out of the selection.
        var holder = this.editor.frame.getInstance().Node.create('<div>');
        holder.appendChild(parent || selectedFragment);

        return {
            elements:  holder.get('childNodes'),
            collapsed: collapsed,
            ancestors: ancestors
        };
    },

    /**
     * If the browser's IE and there's a selection in the sub-doc,
     * this method stashes it in the `currentSelection` member so that
     * it can be retrieved and modified later.
     */
    stashIESelection: function() {
        if (!this.Y.UA.ie) return;

        var selection,
            doc = this.editor.doc;

        if (doc.getSelection) {
            // IE9+
            if (doc.getSelection().toString()) {
                if (this.Y.UA.ie >= '11') {
                    selection = doc.getSelection();
                    // live range to be used when we insert the new element
                    this.liveRange = selection.getRangeAt(0);
                    // clone the range to ensure it doesn't change when the dialog pops
                    this.currentRange = this.liveRange.cloneRange();
                }
                else {
                    this.currentSelection = doc.selection.createRange();
                }
            }
            else {
                this.currentRange = doc.getSelection();
            }
        }
        else {
            // IE8
            var textRange = doc.selection.createRange();
            this.currentSelection = (textRange.text) ? textRange : null;
        }
    },

    /**
     * Deals with IE's idiosyncracies for inserting HTML into the document.
     * This hack workaround is because IE doesn't support the insertHTML command.
     * (ﾉಥ益ಥ）ﾉ﻿ ┻━┻
     * @param  {String} html HTML to insert
     * @return {Boolean}     False if nothing was inserted (the browser's not IE),
     *                             true otherwise
     */
    _insertHTMLForIE: function(html) {
        if (!this.Y.UA.ie) return false;

        var iframe = this.Y.Node.getDOMNode(this.Y.one(this.baseSelector + '_Input iframe')),
            doc = iframe.contentDocument;

        if (doc.getSelection && doc.getSelection().toString()) {
            // Text is currently selected. Use the pasteHTML API, which will replace the selected text
            // with our html.
            doc.selection.createRange().pasteHTML(html);
        }
        else if (this.currentSelection) {
            // Unlike all decent browsers, when text is selected in the sub-document in IE and an input'
            // field in a modal dialog is focused, the selection in that sub-document is wiped out.
            // Assuming #_stashIESelection was called prior to the selection getting cleared, this
            // will restore that stashed selection and allow us to paste our html in.
            var selection = this.currentSelection;
            selection.pasteHTML(html);
            selection.collapse(false);
            selection.select();
            this.currentSelection = null;
        }
        else if (this.currentRange) {
            // The pasteHTML API is horribly buggy, in that, if you call it when there's no selected text,
            // it'll default to inserting the html at the top of the _current_ document, not the child
            // document we want.
            // This workaround hack 1) focuses the editor, and since that isn't synchronous in IE, 2) comes back
            // after a delay and 3) gets the current selection (which is nothing, just a cursor position) and
            // 4) jams in a new document fragment with our html.
            this.editor.frame.focus(RightNow.Event.createDelegate(this, function() {
                var frag, node;

                if (!doc.getSelection().rangeCount) return;

                frag = doc.createDocumentFragment();
                node = this.editor.frame.getInstance().Node.getDOMNode(this.editor.frame.getInstance().Node.create(html));
                frag.appendChild(node);
                this.liveRange.insertNode(frag);
                this.liveRange.collapse(false);
            }));

            // remove the original selection from the document
            this.currentRange.deleteContents();
        }

        return true;
    },

    /**
     * Ideally we'd just be able to call
     * `doc.execCommand('insertHTML', html)`, but that doesn't work properly
     * in non-IE browsers either...
     * @param  {[type]} html [description]
     * @return {[type]}      [description]
     */
    _insertHTMLForNonIE: function(html) {
        var editor = this.editor;
        editor.frame.focus(function() {
            var selection = editor.win.getSelection();
            if (selection.getRangeAt && selection.rangeCount) {
                var range = selection.getRangeAt(0);
                range.deleteContents();

                var el = document.createElement('div');
                el.innerHTML = html;
                var frag = document.createDocumentFragment(), node, last;
                while (node = el.firstChild) {
                    last = frag.appendChild(node);
                }
                range.insertNode(frag);

                if (last) {
                    range = range.cloneRange();
                    range.setStartAfter(last);
                    range.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            }
        });
    },

    /**
     * Inserts the given html into the editor doc. If text is selected then that's what's replaced by the
     * new html.
     * @param  {String} html HTML to insert
     */
    _insertHTML: function(html) {
        return this._insertHTMLForIE(html) || this._insertHTMLForNonIE(html);
    },

    /**
     * Inserts a link.
     * @param  {Object} link Link details;
     * should contain a url member and optionally a text member
     */
    insertLink: function(link) {
        this._insertHTML("<a href='" + link.url + "'>" + (link.text || link.url) + "</a>");
    },

    /**
     * Removes the selected link.
     */
    unlink: function() {
        this.editor.doc.execCommand('unlink');
    },

    /**
     * Applies bolding.
     */
    boldTheSelection: function() {
        this._applyFormatting('bold');
    },

    /**
     * Applies italics.
     */
    italicizeTheSelection: function() {
        this._applyFormatting('italic');
    },

    /**
     * Applies a bullet.
     */
    insertBullet: function() {
        this._applyFormatting('insertUnorderedList');
    },

    /**
     * Executes the given command with execCommand.
     * @param  {String} command A command to apply with execCommand
     * @see <https://developer.mozilla.org/en-US/docs/Rich-Text_Editing_in_Mozilla#Executing_Commands>
     */
    _applyFormatting: function(command) {
        this.editor.doc.execCommand(command);
    },

    /**
     * Builds a string of CSS to use for the Y.Frame's extracss attribute.
     * @return {String} CSS
     */
    _getDefaultStyles: function() {
        if (!this._defaultStyle) {
            var styles = this.getStatic().editor.editorCSS,
                styleString = '';

            this.Y.Object.each(styles, function(rules, selector, dynamicRules) {
                dynamicRules = (selector === 'body')
                    ? 'font-family:' + this.Y.one(document.body).getComputedStyle('fontFamily') + ';'
                    : '';

                styleString += selector + '{' + rules.join(';') + ';' + dynamicRules + '}';
            }, this);

            this._defaultStyle = styleString;
        }

        return this._defaultStyle;
    }
}, {
    editorCSS: {
        'html':                     ['height: 100%'],
        // not sure if it's a YUI bug (which might not crop up in later versions of YUI),
        // but inline CSS is getting set on the body element in IE that is suppressing
        // the element from getting the full height, so add `!important`
        'body':                     ['background:none', 'height: 100% !important', 'font-size: 1em'],
        'body > p:first-child':     ['margin:0'],
        'iframe,img':               ['display:none']
    }
});

/**
 * Controls the behavior of the slide out and popup
 * rich text menus.
 */
RightNow.Widgets.RichTextInput.menu = RightNow.Widgets.extend({
    /**
     * DOM events to subscribe to thru delegation.
     * Keys and values are structured as:
     * "eventType selector": "event name to fire"
     * @type {Object}
     */
    _events: {
        'click .rn_Bolden':                 'bold',
        'click .rn_Italicize':              'italic',
        'click .rn_InsertLink':             'link',
        'click .rn_RemoveLink':             'unlink',
        'click .rn_InsertBullet':           'bullet',
        'click .rn_AdvancedMode button':    'advanced'
    },

    /**
     * Mapping of state of selected text to the menu's
     * button reflecting that state.
     * @type {Object}
     */
    _buttonClasses: {
        bold:   '.rn_Bolden',
        italic: '.rn_Italicize',
        bullet: '.rn_InsertBullet',
        link:   '.rn_InsertLink'
    },

    /**
     * Labels for keyboard shortcuts that should
     * appear in the specified button's titles.
     * @type {Object}
     */
    _keyShortcuts: {
        'rn_Bolden': {
            win: 'CTRL + B',
            mac: '⌘B'
        },
        'rn_Italicize': {
            win: 'CTRL + I',
            mac: '⌘I'
        }
    },

    /**
     * The YUI Node that's the popup menu.
     * @type {Object}
     */
    popupMenu: null,

    /**
     * The YUI Node that's the slide out menu.
     * @type {Object}
     */
    slideMenu: null,

    /**
     * Whether the current environment limits the functionality
     * that the menu provides. (Basically IE)
     * @type {Object|null}
     */
    limitedFunctionality: null,

    /**
     * Only used when limitedFunctionality is set. Used as a
     * caching mechanism.
     * @type {Boolean}
     */
    richButtonsEnabled: true,

    /**
     * Augments the instance with EventTarget
     * and sets up events.
     */
    constructor: function() {
        this.Y.augment(this, this.Y.EventTarget);
        this.widget = this.Y.one(this.baseSelector);
        this._initializeEvents();

        // IE requires that text be selected before any of its
        // execCommands work.
        if (this.Y.UA.ie) {
            // IE8's TextRange#pasteHTML API is horribly broken.
            this.limitedFunctionality = { severe: this.Y.UA.ie < 9 };
        }
    },

    /**
     * Shows or hides the button depending on whether
     * the `this.data.readOnly` property is set.
     */
    updateForReadOnlyState: function() {
        var button = this.widget.one('button.rn_MenuButton');

        if (this.data.readOnly) {
            RightNow.UI.hide(button);
            this.widget.one('.rn_InputArea').setStyle('backgroundColor', '#EFEFEF');
        }
        else {
            RightNow.UI.show(button);
        }
    },

    /**
     * Updates the selected class of the buttons based
     * on the given state.
     * @param  {Object} states Should container
     * bold, italic, bullet, link members whose values are boolean
     */
    refreshSelectedStateOfButtons: function(states) {
        var selectedClass = 'rn_Selected';

        this.Y.Object.each(states, function(toggleOnOrOff, stateName) {
            this.widget.all(this._buttonClasses[stateName]).toggleClass(selectedClass, toggleOnOrOff);
        }, this);
    },

    /**
     * Removes the selected class from all menu buttons.
     */
    deselectAllButtons: function() {
        this.widget.all('.rn_Menu .rn_RichMode button').removeClass('rn_Selected');
    },

    /**
     * Hides the rich mode menu buttons.
     */
    disableRichMode: function() {
        this.widget.all(this._getSelectorForSelection('rich') + ' button').addClass('rn_Hidden');
        this.widget.all(this._getSelectorForSelection('advanced') + ' a').removeClass('rn_Hidden');
    },

    /**
     * Shows the rich mode menu buttons.
     */
    enableRichMode: function(section) {
        this.widget.all(this._getSelectorForSelection('rich') + ' button').removeClass('rn_Hidden');
        this.widget.all(this._getSelectorForSelection('advanced') + ' a').addClass('rn_Hidden');
    },

    /**
     * Disables / enables rich mode buttons
     * if limitedFunctionality is set.
     * @param  {Boolean} onOrOff T to enable; F to disable
     * @param {String=} classOfButtons The class of container div of button state to toggle
     * (popup or slideout menu). If not specified, all rich mode menu button state is toggled.
     */
    toggleLimitedFunctionality: function(onOrOff, classOfButtons) {
        if (!this.limitedFunctionality || (this.richButtonsEnabled === onOrOff && !classOfButtons)) return;

        // IE8 is so bad...
        // *waits*
        // How bad is it?
        // Glad you asked.
        // It's SO bad that it doesn't properly update the UI of absolutely-positioned elements if you
        // enable & disable them (or even use a class to mimic disabled state) real quickly.
        // The only workaround seems to be killing and re-inserting the DOM elements...
        if (this.limitedFunctionality.severe) {
            this.widget.all('.rn_Menu' + (classOfButtons || '') + ' .rn_RichMode').each(function(div, parent, clone) {
                parent = div.get('parentNode');
                clone = div.cloneNode(true);
                div.remove();
                clone.all('button')[(onOrOff) ? 'removeAttribute' : 'setAttribute']('disabled', true);
                parent.insert(clone, 0);
            });
        }
        else {
            this.widget.all('.rn_Menu' + (classOfButtons || '') + ' .rn_RichMode button.rn_FormatAction')
                [(onOrOff) ? 'removeAttribute' : 'setAttribute']('disabled', true);
        }
        this.richButtonsEnabled = onOrOff;
    },

    /**
     * Enable the selection menu buttons.
     * @param  {Object} clickEvent Click event
     * @return {Boolean} True if the buttons are shown, false if not (doesn't show on touch devices or in read only mode)
     */
    show: function(clickEvent, somethingIsSelected) {
        if (this.data.isTouchDevice || this.data.readOnly) return false;

        if(somethingIsSelected) {
            this.slideMenu || (this.slideMenu = this._createSlideMenu());
            this.slideMenu.one('.rn_RichMode button.rn_InsertLink').removeAttribute('disabled');
            this.slideMenu.one('.rn_RichMode button.rn_InsertLink').addClass('rn_SelectedText');

            if(this.Y.UA.ie) {
                // In IE, formatting buttons remain disabled. Have to enable them manually.
                this.slideMenu.one('.rn_RichMode button.rn_Bolden').removeAttribute('disabled');
                this.slideMenu.one('.rn_RichMode button.rn_Italicize').removeAttribute('disabled');
                this.slideMenu.one('.rn_RichMode button.rn_InsertBullet').removeAttribute('disabled');
            }
            return true;
        }
    },

    /**
     * Disable the selection menu buttons.
     */
    hide: function(somethingIsSelected) {
        if(!somethingIsSelected) {
            this.slideMenu || (this.slideMenu = this._createSlideMenu());
            this.slideMenu.one('.rn_RichMode button.rn_InsertLink').setAttribute('disabled', true);
            this.slideMenu.one('.rn_RichMode button.rn_InsertLink').removeClass('rn_SelectedText');
            this.slideMenu.one('.rn_RichMode button.rn_Bolden').removeClass('rn_SelectedText');
            this.slideMenu.one('.rn_RichMode button.rn_Italicize').removeClass('rn_SelectedText');
            this.slideMenu.one('.rn_RichMode button.rn_InsertBullet').removeClass('rn_SelectedText');
        }
    },

    /**
     * Removes the menus from the DOM and
     * resets the menu members.
     */
    destroy: function() {
        if (this.slideMenu) {
            this.slideMenu.remove();
            this.slideMenu = null;
        }
    },

    /**
     * Returns the class name of the specified section.
     * @param  {String=} section The section name
     *                           all, advanced, rich (default to all)
     * @return {String}         class name
     */
    _getSelectorForSelection: function(section) {
        if (!section || section === 'all') return '.rn_Menu';

        return (section === 'advanced') ? '.rn_AdvancedMode' : '.rn_RichMode';
    },

    /**
     * Sets up the delegate listeners on the _events member items.
     */
    _initializeEvents: function() {
        this.widget.one('button.rn_MenuButton').on('click', this._toggleSlideMenu, this);

        var container = this.widget.get('parentNode');
        this.Y.Object.each(this._events, function(event, action) {
            action = action.split(' ');
            this.publish(event, { emitFacade: true });
            container.delegate(action[0], this._onEventDelegate, action.slice(1).join(' '), this, event);
        }, this);
    },

    /**
     * Event handler for delegate DOM events.
     * @param  {Object} e           DOM event
     * @param  {String} eventToFire Name of local event to fire
     */
    _onEventDelegate: function(e, eventToFire) {
        if (e.currentTarget.getAttribute('disabled')) return;

        if (eventToFire === 'advanced') {
            e.currentTarget.toggleClass('rn_Selected');
        }

        this.fire(eventToFire, { type: eventToFire });
    },

    /**
     * Toggles the display of the menu along
     * with a sweet, sweet animation.
     * @param  {Object} e Click event
     */
    _toggleSlideMenu: function(e) {
        e.currentTarget.toggleClass('rn_Selected');

        this.slideMenu || (this.slideMenu = this._createSlideMenu());

        var to, eventKey;

        if (this.slideMenu.hasClass('rn_Hidden')) {
            eventKey = 'start';

            var parentWidth = parseInt(this.Y.one(this.baseSelector).getComputedStyle('width'), 10);

            if(this.Y.UA.ie) {
                to = parentWidth - (parentWidth - parseInt(e.currentTarget.getComputedStyle('width'), 10)) + 25;
            }
            else {
                to = parentWidth - (parentWidth - parseInt(e.currentTarget.getComputedStyle('width'), 10)) - 1;
            }
        }
        else {
            eventKey = 'end';
            to = -200;
        }

        var anim = new this.Y.Anim({
            node: this.slideMenu,
            to: {
                right: to
            },
            duration: 0.4
        });
        anim.on(eventKey, function() {
            anim.get('node').toggleClass('rn_Hidden');
        });
        anim.run();

        var linkButton = this.slideMenu.one('.rn_RichMode button.rn_InsertLink');
        if (!linkButton.hasClass('rn_SelectedText')) {
            // Disable InsertLink button if no text is selected.
            linkButton.setAttribute('disabled', true);
        }
    },

    /**
     * Creates and inserts the toggle menu.
     * @return {Object} YUI Node instance
     */
    _createSlideMenu: function() {
        var menu = this._renderMenu('rn_SlideMenu').setStyle('right', -200);

        this._supplyButtonTitles(menu);
        this._matchExistingSelection(menu);

        // TK - probably an attribute would control whether advanced mode is available
        menu.one('.rn_AdvancedMode').removeClass('rn_Hidden');

        this.widget.one('button.rn_MenuButton').insert(menu, 'before');

        this.toggleLimitedFunctionality(false, '.rn_SlideMenu');

        return menu;
    },

    /**
     * Renders the menu.
     * @param  {String} className class name to add to the menu
     * @return {Object}           Node menu
     */
    _renderMenu: function(className) {
        return this.Y.Node.create(new EJS({ text: this.getStatic().templates.menu }).render({
            messages: {
                label_advanced_mode: this.data.attrs.label_advanced_mode,
                label_insert_link_dialog: this.data.attrs.label_insert_link_dialog,
                label_help_link: this.data.attrs.label_help_link
            },
            attrs: {
                editor_help_url: this.data.attrs.editor_help_url
            },
            formatContent: !this.data.isTouchDevice
        }))
            .addClass('rn_Hidden')
            .addClass(className);
    },

    /**
     * Pulls the screen reader text out and sets it as each button's title.
     * For buttons that have keyboard shortcuts (included in _keyShortcuts member),
     * also adds those to the title.
     * @param  {Object} menu Node
     */
    _supplyButtonTitles: function(menu) {
        var platform = this.Y.UA.os === 'macintosh' ? 'mac' : 'win',
            firefox = this.Y.UA.gecko > 0;

        menu.all('.rn_ScreenReaderOnly').each(function(span, parent, keyLabel) {
            parent = span.ancestor('button');
            if (parent) {
                keyLabel = '';
                this.Y.Object.some(this._keyShortcuts, function(labels, className) {
                    // Firefox's shortcuts for CTRL+I and CTRL+B override any web
                    // app's shortcuts with those same key combos: We never get notified
                    // of the keydown event. So don't display the keyboard shortcut hints
                    // for Firefox.
                    if (!firefox && parent.hasClass(className)) {
                        return keyLabel = ' (' + labels[platform] + ') ';
                    }
                });
                parent.setAttribute('title', span.getHTML() + keyLabel);
            }
        }, this);
    },

    /**
     * Takes a newly-created menu node and matches its buttons'
     * selected state to an existing menu's (if any) buttons' selected state.
     * @param  {Object} newMenu Node
     */
    _matchExistingSelection: function(newMenu) {
        var selections = this.widget.all('.rn_Menu button').hasClass('rn_Selected');

        if (selections.toString().indexOf('true') > -1) {
            var buttons = newMenu.all('button');
            this.Y.Array.each(selections, function(selected, index) {
                if (selected) {
                    buttons.item(index).addClass('rn_Selected');
                }
            });
        }
    }
});

/**
 * Provides a base class that implementing dialogs can extend from.
 * Shouldn't be used directly.
 */
RightNow.Widgets.RichTextInput.dialog = RightNow.Widgets.extend({
    /**
     * The YUI Panel instance.
     * Created via #_createDialog.
     * @type {Object}
     */
    dialog: null,

    /**
     * Title of the dialog
     * Abstract: Sub-classes should implement
     * @type {String}
     */
    title: null,

    /**
     * Name of the EJS template to use
     * Abstract: Sub-classes should implement
     * @type {String}
     */
    templateName: null,

    /**
     * Augments the instance with YUI's EventTarget.
     */
    constructor: function() {
        this.Y.augment(this, this.Y.EventTarget);
        this.Y.augment(this, RightNow.RequiredLabel);
    },

    /**
     * Shows the dialog; creates it if it doesn't exist.
     */
    show: function() {
        this.dialog || (this.dialog = this._createDialog());

        this.dialog.show();

        var focusFirst = this.dialog.get('contentBox').one('input,a');
        focusFirst && focusFirst.focus();
    },

    /**
     * Hides the dialog and clears all loading indicators
     * and inputs.
     */
    hide: function() {
        if (this.dialog) {
            this.dialog.hide();
            this._resetUIControls();
        }
    },

    /**
     * Destroys the dialog and clears out the dialog member.
     */
    destroy: function() {
        if (this.dialog) {
            this.dialog.destroy();
            this.dialog = null;
        }
    },

    /**
     * Creates the dialog.
     * @param  {Object=} templateData Data to pass to the template
     * @param {Object=} options Dialog creation options; keys include
     *     -buttons: {Array} Buttons to place before the default Cancel button
     * @param {Object=} globalObject RichTextInput object
     * @return {Object}              YUI Panel instance
     */
    _createDialog: function(templateData, options, globalObject) {
        var initialContent = this.templateName ? this.getStatic().templates[this.templateName] : '<form>Provide a template name</form>';
        if (templateData) {
            initialContent = new EJS({ text: initialContent }).render(templateData);
        }

        options = options || {};
        var buttons = [{
            text: RightNow.Interface.getMessage("CANCEL_LBL"),
            handler: options.exitCallback || function() {
                this.hide();
            }
        }];
        if (options && options.buttons) {
            // Add any caller's buttons before the default cancel button
            buttons = options.buttons.concat(buttons);
        }

        var dialog = new RightNow.UI.Dialog.actionDialog(this.data.attrs[this.title], this.Y.Node.create(initialContent), {
                cssClass: 'rn_RichTextInput',
                buttons: buttons,
                exitCallback: options.exitCallback || null
            }),
            enterPressHandler = buttons[0].handler,
            context = null;

        if ('fn' in enterPressHandler && 'scope' in enterPressHandler) {
            context = enterPressHandler.scope;
            enterPressHandler = enterPressHandler.fn;
        }

        RightNow.UI.Dialog.addDialogEnterKeyListener(dialog, function(name, e, target) {
            target = e[1].target;
            if (target.get('tagName') === 'INPUT' && target.getAttribute('type') !== 'file') {
                enterPressHandler.call(this, e);
            }
        }, context);
        dialog.get('contentBox').all('form').on('submit', function(e) { e.halt(); });

        return dialog;
    },

    /**
     * Hides loading indicators, clears inputs, and
     * removes error messages
     * @return {Object} Dialog content, so any inherited method doesn't have to retrieve this content again
     */
    _resetUIControls: function() {
        var dialogContent = this.dialog.get('contentBox');
        dialogContent.all('.rn_Loading').addClass('rn_Hidden');
        dialogContent.all('input').set('value', '');
        dialogContent.all('.rn_ErrorMessage').remove();

        return dialogContent;
    },

    /**
     * Jams an error div into the dialog's form as its first child.
     * @param  {String} message Error message
     * @param  {String} fieldID ID of the error field
     */
    _insertErrorMessage: function(message, fieldID){
        var form = this.dialog.get('contentBox').one('form'),
            error = form.one('.rn_MessageBox');

        if (!error) {
            error = this.Y.Node.create(new EJS({
                text: this.getStatic().templates.errorMessage
            }).render({
                error: {
                    id: fieldID,
                    msg: message
                }
            }));
            form.insert(error, 0);
        }
        form.all('input[type="file"]').set('value', '');
        error.focus();
    }
});

/**
 * Dialog that provides the ability to insert a hyperlink.
 */
RightNow.Widgets.RichTextInput.insertLinkDialog = RightNow.Widgets.RichTextInput.dialog.extend({
    overrides: {
        /**
         * Attribute value to use for the dialog title
         * @type {String}
         */
        title: 'label_insert_link_dialog',

        /**
         * Name of the EJS template
         * @type {String}
         */
        templateName: 'insertLinkForm',

        /**
         * Creates the dialog. Calls into the parent, adding an
         * additional OK button and its handler.
         * @param  {Object=} templateData Data to pass to the template
         * @return {Object}              YUI Panel instance
         */
        _createDialog: function(templateData) {
            templateData || (templateData = {});
            templateData.messages = {
                link_text: this.data.attrs.label_link_text,
                link_to:   this.data.attrs.label_link_to,
                domID:     this.baseDomID
            };

            var globalObject = this.data.globalObject;
            var dialog = this.parent(templateData || true, {
                    buttons: [{
                        text: RightNow.Interface.getMessage("OK_LBL"),
                        handler: {
                            fn: this._onFormSubmit,
                            scope: this
                        }
                    }],
                    exitCallback: function() {
                        this.hide();
                        if(globalObject.editor.Y.UA.ie) {
                            globalObject.menu.hide();
                        }
                        globalObject.menu.toggleLimitedFunctionality(false, '.rn_SlideMenu');
                    }
                },
                this.data.globalObject
            );

            return dialog;
        },

        /**
         * Shows the dialog (creating it if it doesn't exist).
         * Prefills inputs based on what's given in the parameters.
         * @param  {String=} placeholderText Text to use for the hyperlink
         * @param  {String=} url             Link's href
         */
        show: function(placeholderText, url) {
            this.dialog || (this.dialog = this._createDialog());

            var contentBox = this.dialog.get('contentBox');
            contentBox.all('.rn_LinkUrlInput input').set('value', url || '');
            if(!url) {
                contentBox.all('.rn_LinkTextInput input').set('value', '');
            }
            // Remove the error message if present for better user experience
            contentBox.all('.rn_ErrorMessage').remove();

            if (placeholderText) {
                contentBox.one('input').set('value', placeholderText);
            }

            this.dialog.show();
            contentBox.one('input,a').focus();
        }
    },

    /**
     * Event handler when the OK button is clicked.
     * Validates the input.
     */
    _onFormSubmit: function() {
        var inputs = this.dialog.get('contentBox').all('input'),
            text = this.Y.Escape.html(inputs.item(0).get('value')),
            url = inputs.item(1).get('value');

        if (url && RightNow.Text.isValidUrl(url) && url.indexOf('ftp:') !== 0) {
            // Let rendering/object methods catch up
            this.Y.later(200, this, function() {
                // the dialog could be destroyed while waiting (mostly in tests)
                if (this.dialog)
                    this.dialog.hide();
            });

            if (url.indexOf('http') !== 0) {
                // 'something.com' is seen as a relative url.
                url = 'http://' + url;
            }
            this.fire('insertLink', {text: text, url: url});
        }
        else {
            this._insertErrorMessage(RightNow.Interface.getMessage("VALID_URL_REQ_MSG"), inputs.item(1).get("id"));
        }
    }
});

/**
 * Markdown to HTML and HTML to Markdown conversion.
 */
RightNow.Widgets.RichTextInput.converter = RightNow.Widgets.extend({
    disallowedContent: 'iframe,img,table',

    /**
     * Instantiates the third-party converters.
     * TK - Even their APIs are Singleton-ish, instances are actually
     * saving the input given, so we have to re-instantiate for
     * ea. conversion run.........
     */
    constructor: function() {
        /*
         this._htmlToMdConverter = new reMarked({
             link_list: true     // reference-style links
         });

         this._mdToHtmlConverter = new Markdown.Converter();
         */
    },

    /**
     * Instance of reMarked
     * @type {Object}
     */
    // _htmlToMdConverter: null,

    /**
     * Instance of Markdown.Converter
     * @type {Object}
     */
    // _mdToHtmlConverter: null,

    /**
     * Converts the given HTML to markdown.
     * @param  {String} input html to convert
     * @return {String}       converted markdown
     */
    htmlToMd: function(input) {
        if (!input) return '';

        var output = this._convertEditorMarkupIntoSemanticMarkup(input);

        // reMarked may take the line after a list and merge it with the last list item,
        // so add an explicit br tag after every end-of-list element
        output = output.replace(/<\/ul>/g, '</ul><br>');

        output = new reMarked({
            link_list: true,     // Enable reference-style links
            gfm_del:   false,    // Disable strikthroughs
            gfm_tbls:  false,    // Disable tables
            h1_setext: false,    // Disable underlining h1s
            h2_setext: false     // Disable underlining h2s
        }).render(output);

        // undo reMarked's escaping of the forward-slash to preserve links
        return output.replace(/&#x2F;/g, '/');
    },

    /**
     * A line of text ending with two spaces denotes a line break in markdown.
     * The contentEditable document represents that as ' &nbsp;' followed
     * by a div or br (depending on the browser). So this replaces these breaks with
     * the proper newlines and removes any other HTML content.
     * @param  {string} content HTML editor content
     * @return {string}         The content with HTML stripped and newlines inserted
     */
    extractNormalizedMDFromHTML: function(content) {
        var marker = + new Date(),
            converted = content.replace(/<br>/gi, marker); // Firefox implements line breaks with <br>s.

        if (converted === content) {
            // Webkit implements line breaks with <div>s.
            converted = content.replace(/ &nbsp;(<div)/gi, " " + marker + "$1");
        }

        // Remove all generated HTML and HTML entities...
        converted = this.Y.one(document.createDocumentFragment()).setHTML(converted).get('textContent');
        // ...But this will have removed the newlines we worked so hard to detect.
        // Double up newlines to indicate a Markdown line break
        converted = converted.replace(new RegExp(marker, 'g'), " \n\n");

        return converted;
    },

    /**
     * Converts the given markdown to HTML.
     * @param  {String} input markdown to convert
     * @return {String}       converted html
     */
    mdToHTML: function(input) {
        if (!input) return '';

        input = this._prepareStyleTagsForConversionToHTML(input);
        input = this._escape(input);

        var html = (new Markdown.Converter()).makeHtml(input);
        html = this._removeSpacesAddedForConversion(html);

        return this.Y.Node.create('<div>' + html + '</div>').getHTML();
    },

    /**
     * Converts browser's advanced editor HTML to markdown.
     * @param  {String} input html to convert
     * @return {String}       converted markdown
     */
    browserHTMLToMarkdown: function(input) {
        if (!input) return '';

        // Browsers will actually wrap this content in HTML elements. Go ahead and rip through those
        // elements and parse out the text.
        var node = this.Y.Node.create('<div>' + input + '</div>'),
            advancedResultContent = '',
            processSubNodes = function(subnode) {
                if (subnode.get('nodeName') === 'BR') {
                    // preserve br element separation
                    advancedResultContent += "\n\n";
                }
                else if (subnode.get('childNodes').size() === 0) {
                    // if a subnode has no childNodes, it's probably just simple text
                    advancedResultContent += subnode.get('text');
                }
                else {
                    // if the browser wrapped something in a div or a p element, then add two blank lines
                    // so that div/p separation is preserved
                    if (subnode.get('nodeName') === 'DIV' || subnode.get('nodeName') === 'P')
                        advancedResultContent += "\n\n";

                    subnode.get('childNodes').each(processSubNodes);
                }
            };
        node.get('childNodes').each(processSubNodes);
        return advancedResultContent;
    },

    /**
     * Converts a number of elements that the editor document uses for html markup,
     * (but that isn't semantically valid for the markdown converter to recognize)
     * so that we'll get proper markdown after conversion.
     * @param  {String} input html to convert
     * @return {String}       transformed html
     */
    _convertEditorMarkupIntoSemanticMarkup: function(input) {
        var output = this.Y.Node.create('<div>' + input + '</div>'),
            replacement;

        this._fixWhitespace(output);

        this._swapStyleTags(output);

        this._removeExtraSpanTags(output);

        // Some browsers wrap each new line of the input in 'div' and 'p', so we need
        // to process each line element to make sure it plays nice with reMarked
        output.all('div,p').each(function(node) {
            replacement = this._fixBlockElements(node);
            if (replacement) {
                node.replace(replacement);
            }
        }, this);

        // Since we are converting 'div' and 'p' to '<br /><span>', this introduces the
        // possibility of adding a '<br /> tag to the beginning of the input, so explicity
        // check for this, and if found, remove it.
        var nodeToCheck = output.get('childNodes');
        if((nodeToCheck = nodeToCheck.item(0)) && nodeToCheck.get('nodeName').toLowerCase() === 'br') {
            nodeToCheck.remove();
        }

        output.all(this.disallowedContent).remove();

        // close strong and em elements before line breaks (br elements)
        this._fixMultilineFormatting(output);
        // this fix may reintroduce whitespace elements within the strong and em
        // elements again, so run _fixWhitespace one more time
        this._fixWhitespace(output);

        var outputHTML = output.getHTML();
        if(outputHTML.search(/>(&nbsp;)+</) !== -1){
            outputHTML = '<p>' + outputHTML + '</p>';
        }
        return outputHTML;
    },

    /**
     * Some browsers use span and font elements with inline styles instead of semantic elements.
     * They also apply inline styles to semantic elements (e.g. <em style="font-weight:bold">bold and italic</em>)
     * when they feel like it.
     * This function will recursively swap out tags with inline styles for their semantic equivalents.
     * @param {Object} node YUI Node to process style tags
     */
    _swapStyleTags: function(node) {
        if (node.hasChildNodes()) {
            node.get('children').each(function(child) {
                this._swapStyleTags(child);
            }, this);
        }

        if (node.get('nodeName').search(/^(font|span|i|b|strong|em|a)$/i) !== -1) {
            var replacement = this._createSemanticReplacementNode(node);
            if (replacement) {
                node.replace(replacement);
            }
        }
    },

    /**
     * In some browsers extra span tags are added surrounding non-styled text elements.
     * This function will recursively remove those spans, leaving the bare text.
     * @param {Object} node YUI Node to remove spans from
     */
    _removeExtraSpanTags: function(node) {
        if (node.hasChildNodes()) {
            node.get('children').each(function(child) {
                this._removeExtraSpanTags(child);
            }, this);
        }

        if (node.get('nodeName') === 'SPAN') {
            node.replace(this.Y.Node.create(node.getHTML()));
        }
    },

    /**
     * Recursively trims whitespace in inline tags.
     * Due to the way the browser applies text formatting to selections,
     * users can type "is bananas insanity", select " bananas " (the selection may contain
     * the leading or trailing whitespace depending on how accurate the user is),
     * and apply Bold formatting to the word. So it's something like `is<b> bananas </b>insanity`.
     * When going into markdown mode, that's converted to `is** bananas **insanity`, since
     * that's the html that was supplied. But when converting back to html, it comes out as,
     * `is\*\* bananas \*\*insanity`. Both markdown conversion directions are correct.
     * The problem is that the dumb whitespace is on the wrong side of the boundary.
     * This method flips that whitespace by adding whitespace to the previous / next
     * text node sibling and trimming the whitespace from inside the tag.
     * @param  {Object} node YUI Node to consider
     */
    _fixWhitespace: function(node) {
        if (node.hasChildNodes()) {
            node.get('children').each(function(child) {
                this._fixWhitespace(child);
            }, this);
        }

        if (node.get('nodeName').search(/^(font|span|i|b|strong|em|a)$/i) !== -1) {
            var html = node.getHTML(),
                matches = html.match(/^((?:\s|&nbsp;|<br ?\/?>)*)(.*?)((?:\s|&nbsp;|<br ?\/?>)*)$/),
                whiteSpace = matches ? matches.slice(1) : [];

            if (!whiteSpace[0] && !whiteSpace[2]) {
                return;
            }

            node.insert(whiteSpace[0].replace(/\s/g, '&nbsp;'), 'before');

            node.setHTML(whiteSpace[1]);

            node.insert(whiteSpace[2].replace(/\s/g, '&nbsp;'), 'after');
        }
    },

    /**
     * This function swaps out block elements 'div' and 'p' for a 'br' followed by a
     * 'span' that contains the original element's contents.
     * @param {Object} node YUI Node to replace
     */
    _fixBlockElements: function(node) {
        if (node.get('nodeName').toLowerCase() === 'div' || node.get('nodeName').toLowerCase() === 'p') {
            return this.Y.Node.create('<br/><span>' + node.getHTML() + '</span>');
        }
    },

    /**
     * Formatting may span multiple lines, which markdown does not appreciate.
     * Force the formatting tags to close before the line break.
     * @param  {Object} node YUI Node to consider
     */
    _fixMultilineFormatting: function(node) {
        if (node.hasChildNodes()) {
            node.get('children').each(function(child) {
                this._fixMultilineFormatting(child);
            }, this);
        }

        if (node.get('nodeName').search(/^(strong|em)$/i) !== -1) {
            var html = node.getHTML(),
                nodeName = node.get('nodeName').toLowerCase(),
                replacement = html.replace(/<br>/g, '</' + nodeName + '><br><' + nodeName + '>');

            node.replace(this.Y.Node.create('<' + nodeName + '>' + replacement + '</' + nodeName + '>'));
        }
    },

    /**
     * Surrounds **, *, __, and _ with a space, because our
     * Markdown converter doesn't parse styling correctly
     * if the styling is in the middle of a word or at
     * the beginning of a line.
     * @param  {String} input Markdown to prepare
     * @return {String} Prepared Markdown
     */
    _prepareStyleTagsForConversionToHTML: function(input) {
        // Surround ** with a space, for bold
        input = input.replace(/(\*{2}[^\*]+\*{2})+/g, function(match) {
            return " " + match + " ";
        });

        // Surround __ with a space, for bold
        input = input.replace(/(_{2}[^_]+_{2})+/g, function(match) {
            return " " + match + " ";
        });

        // Surround * with a space, for italics. Watch out for newlines,
        // which signify bulleted lists.  Watch out for **, since
        // that case is handled separately
        input = input.replace(/(^|[^\*])(\*[^\*]+[^\n]\*)+/g, function(match) {
            return match.slice(0, match.indexOf('*')) + " " + match.slice(match.indexOf('*'), match.length) + " ";
        });

        // Surround _ with a space, for italics. Watch out for __, since
        // that case is handled separately
        input = input.replace(/(^|[^_])(_[^_]+[^\n]_)+/g, function(match) {
            return match.slice(0, match.indexOf('_')) + " " + match.slice(match.indexOf('_'), match.length) + " ";
        });

        return input;
    },

    /**
     * Remove the extra spaces we introduced on styling
     * elements when preparing the Markdown
     * for conversion to HTML
     * @param  {String} html HTML to remove
     * @return {String} Fixed HTML
     */
    _removeSpacesAddedForConversion: function(html) {
        html = html.replace(/( <strong>)+/g, '<strong>');
        html = html.replace(/(<\/strong> )+/g, '</strong>');
        html = html.replace(/( <em>)+/g, '<em>');
        html = html.replace(/(<\/em> )+/g, '</em>');

        return html;
    },

    /**
     * If node is an element that should be replaced by a semantic element, then a replacement
     * with node's contents is returned.
     * @param  {Object} node The Node to consider for replacement
     * @return {Object|String|null}      String or Node replacement or null if nothing should be replaced
     */
    _createSemanticReplacementNode: function(node) {
        if (!node.get('textContent') && !this.Y.Lang.trim(node.get('innerText'))) return '<br><br>';

        var replacement,
            domNode = this.Y.Node.getDOMNode(node);

        // I would use node#getStyle, but in IE it works like node#getComputedStyle.
        // But I want the actual css style property value, not the computed style,
        // which is what the cascaded current style is.
        if (domNode.style.fontWeight === 'bold' && !this._isSemanticTag(domNode.tagName, 'bold')) {
            replacement = this.Y.Node.create(this._wrapHtmlWithTag(this._getReplacementContent(node), 'strong'));
        }
        if (domNode.style.fontStyle === 'italic' && !this._isSemanticTag(domNode.tagName, 'italic')) {
            replacement = (replacement)
                // node#wrap should work, but doesn't...
                ? replacement.setHTML(this._wrapHtmlWithTag(replacement.getHTML(), 'em'))
                : this.Y.Node.create(this._wrapHtmlWithTag(this._getReplacementContent(node), 'em'));
        }

        return replacement;
    },

    /**
     * Returns the content to use as the content for the replacement node.
     * @param  {Object} node Node being replaced
     * @return {String}      HTML content to use for the replacement
     */
    _getReplacementContent: function(node) {
        return (this._isSemanticTag(node.get('tagName')))
            ? this._getOuterHTML(node)
            : node.getHTML();
    },

    /**
     * Normalizes the retrieval of the outerHTML property.
     * @param  {Object} node Node
     * @return {String}      node's outerHTML
     */
    _getOuterHTML: function(node) {
        // old FF and IE don't have an outerHTML property.
        return node.get('outerHTML') || this.Y.Node.create('<div>').append(node).getHTML();
    },

    /**
     * Wraps the given html with the given tag.
     * @param  {String} html Tag's contents
     * @param  {String} tag  Tag name
     * @return {String}      wrapped html
     */
    _wrapHtmlWithTag: function(html, tag) {
        return '<' + tag + '>' + html + '</' + tag + '>';
    },

    /**
     * Determines if the given tag name is one that we care about.
     * @param  {String}  tag   node's tagName property
     * @param  {String=}  which if not specified, this checks if
     * tag is an i, b, em, or strong;
     * if 'italic', checks if tag is i / em
     * if 'bold', checks if tag is b / strong
     * @return {Boolean}       T if the tag is, F if it isn't
     */
    _isSemanticTag: function(tag, which) {
        if ((!which || which === 'italic') && (tag === 'I' || tag === 'EM')) return true;
        if ((!which || which === 'bold') && (tag === 'B' || tag === 'STRONG')) return true;

        return false;
    },

    /**
     * Characters to convert to character entities.
     * @type {Object}
     */
    _escapeMapping: {
        // Escaping backtick means that inline `code blocks` aren't supported.
        // I'm told that we don't care.
        '`': '&#x60;',
        // Escaping gt & lt means that auto links and quote blocks aren't supported.
        // I'm told that we don't care.
        '<': '&lt;',
        '>': '&gt;',
        '&': '&amp;',
        '"': '&quot;',
        "'": '&#x27;'
        // Don't escape backslash, since it escapes benign sequences like 'http://'
        // '/': '&#x2F;',
    },

    /**
     * YUI's Escape module escapes the '/' character,
     * which breaks things like URLs. Unfortunately,
     * what that module escapes isn't configurable.
     * @param  {String} string to escape
     * @return {String}        string with &, <, >, ', ", ` escaped
     */
    _escape: function(string) {
        var mapping = this._escapeMapping;

        return (string + '').replace(/[&<>"'`]/g, function(match) {
            return mapping[match];
        });
    }
});

/**
 * Provides tooltip functionality intended for displaying info about
 * a highlighted hyperlink.
 */
RightNow.Widgets.RichTextInput.tooltip = RightNow.Widgets.extend({
    /**
     * The Node element
     * @type {Object}
     */
    element: null,
    /**
     * When repositioning the tooltip, the number of pixels to detract
     * from the given x coordinate
     * @type {Number}
     */
    xOffset: 15,
    /**
     * When repositioning the tooltip, the number of pixels to add
     * to the given y coordinate
     * @type {Number}
     */
    yOffset: 10,

    /**
     * Buffer (in px) where the tooltip can't go near the bottom
     * of the container input widget
     * @type {Number}
     */
    bottomBuffer: 50,

    constructor: function() {},

    /**
     * Creates the Node with the tooltip.ejs template
     * and inserts it as the final child of the widget.
     */
    _create: function() {
        this.element = this.Y.Node.create(new EJS({ text: this.getStatic().templates.tooltip }).render({
            messages: {
                label_change_link: this.data.attrs.label_change_link,
                label_remove_link: this.data.attrs.label_remove_link
            }
        }));
        this.Y.one(this.baseSelector).append(this.element);
    },

    /**
     * Sets the content of the tooltip; creates it if it doesn't already exist.
     * @param {String=} content Content to use
     * @return {Object} chainable
     */
    setContent: function(content) {
        this.element || this._create();
        this.element.one('.rn_ToolTipContent').setHTML(content || '');

        return this;
    },

    /**
     * Hides the tooltip by adding the rn_Hidden class.
     * @return {Object} chainable
     */
    hide: function() {
        this.element && this.element.addClass('rn_Hidden');

        return this;
    },

    /**
     * Shows the tooltip; creates it if it doesn't exist.
     * @return {Object} chainable
     */
    show: function() {
        this.element || this._create();
        this.element.removeClass('rn_Hidden');

        return this;
    },

    /**
     * Removes the tooltip element from the DOM
     * and clears out the element member.
     * @return {Object} chainable
     */
    remove: function() {
        if (this.element) {
            this.element.remove();
            this.element = null;
        }

        return this;
    },

    /**
     * Repositions to the given x and y coordinates
     * @param  {Object} reference The Node to display
     *                            the tooltip for
     * @param  {Object} container The container of reference
     *                            to keep the tooltip inside
     * @return {Object}   chainable
     */
    reposition: function(reference, container) {
        var coordinates = {};
        this.Y.Object.each({x: ['offsetLeft'], y: ['offsetTop', 'offsetHeight']}, function(prop, coordinate, value, propName, offset) {
            propName = prop[0];
            value = (reference.get(propName) || parseInt(reference.getAttribute('data-' + propName), 10)) + container.get(propName);

            if (propName = prop[1]) {
                value += (reference.get(propName) || parseInt(reference.getAttribute('data-' + propName), 10));
            }

            offset = this[coordinate + 'Offset'];
            if (value < offset) value = offset + 10;

            coordinates[coordinate] = value;
        }, this);

        this.element || this._create();

        // Don't let tooltip get cutoff near the bottom of the input.
        if (coordinates.y + this.yOffset >= container.get('offsetHeight') - this.bottomBuffer) {
            this.element.setStyles({
                top: 'auto',
                bottom:  (container.get('offsetHeight') - coordinates.y + this.bottomBuffer) + 'px',
                left: (coordinates.x - this.xOffset) + 'px'
            });
        }
        else {
            this.element.setStyles({
                top:  (coordinates.y + this.yOffset) + 'px',
                left: (coordinates.x - this.xOffset) + 'px',
                bottom: 'auto'
            });
        }

        return this;
    }
});
// Mind cleanse. Think about puppies and kittens and kitten mittens.
