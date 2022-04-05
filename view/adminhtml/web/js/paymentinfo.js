/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

/*jshint jquery:true*/
define([
    "jquery",
    "jquery/ui",
    "mage/translate"
], function($) {
    "use strict";

    $.widget('mage.tokenbasePaymentinfo', {
        options: {
            spinnerSelectorOuter: '#tab_block_tokenbase_cards',
            spinnerSelectorInner: '.admin__page-nav-item-message-loader',
            deleteSelector: '.action-delete',
            editSelector: '.ui-tabs-nav a, .action-edit, .action-back',
            editSelectorInd: '.address-list-item',
            editSelectorIndTarget: '.action-edit:first',
            formSelector: 'form',
            saveSelector: '.action-save'
        },

        _create: function() {
            this._initWrapper();
            this._bind();

            this.element.trigger('contentUpdated');
        },

        _initWrapper: function() {
            // Remove silly class on the wrapper
            this.element.parent().removeClass('admin__scope-old');
        },

        _bind: function() {
            // Bind event listeners
            this.element.off('click', this.options.deleteSelector, this.deleteCard.bind(this))
                        .on('click', this.options.deleteSelector, this.deleteCard.bind(this));
            this.element.off('click', this.options.editSelector, this.editCard.bind(this))
                        .on('click', this.options.editSelector, this.editCard.bind(this));
            this.element.off('click', this.options.editSelectorInd, this.editCardIndirect.bind(this))
                        .on('click', this.options.editSelectorInd, this.editCardIndirect.bind(this));

            // Doing this direct A: because we can, B: because we have to remove a jQ validate listener anyway.
            // It triggers a direct element.submit() that bypasses our preventDefault().
            this.element.find(this.options.formSelector)
                .off('submit')
                .on('submit', this.saveCard.bind(this))
                .validation();
        },

        deleteCard: function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            if (confirm($.mage.__("Are you sure you want to delete this?"))) {
                this.showSpinner();

                $.post(
                    $(event.target).closest('a').attr('href'),
                    this.deleteCardHandleResponse.bind(this, event),
                    'json'
                );
            }
        },

        deleteCardHandleResponse: function(event, data) {
            this.hideSpinner();

            if(data.success) {
                $(event.target).closest('li').slideUp();
            }
            else if(typeof data.message != 'undefined') {
                alert(data.message);
            }
        },

        editCard: function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            this.showSpinner();
            $.get({
                url: $(event.target).closest('a').attr('href'),
                success: function(data) {
                    this.element.html(
                        $(
                            $.parseHTML(data.trim(), document, true)
                        ).html()
                    );

                    this._create();
                    this.hideSpinner();
                }.bind(this)
            })
        },

        editCardIndirect: function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            this.element.find(this.options.editSelectorIndTarget).trigger('click');
        },

        saveCard: function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            var form = this.element.find(this.options.formSelector);
            form.data("validator").settings.submitHandler = null;
            if (form.validation('isValid') === false) {
                return;
            }

            this.showSpinner();
            this.element.find(this.options.saveSelector).prop('disabled', true);

            // Kick off an event for tokenizing or other pre-save behavior. Any listeners can set preventSave to
            // prevent submission.
            form.data('preventSave', false);
            form.trigger('tokenbaseSave');

            if (form.data('preventSave') === false) {
                $.post(
                    form.attr('action'),
                    form.serialize(),
                    this.saveCardHandleResponse.bind(this)
                );
            } else {
                this.hideSpinner();
            }
        },

        saveCardHandleResponse: function(data) {
            this.hideSpinner();

            if(typeof data === 'object') {
                if(typeof data.message != 'undefined') {
                    alert(data.message);

                    this.element.find(this.options.saveSelector).removeProp('disabled');
                    this.element.find(this.options.formSelector).trigger('tokenbaseFailure');
                }
            }
            else {
                this.element.html(
                    $(
                        $.parseHTML(data.trim(), document, true)
                    ).html()
                );
                this._create();

                $('html, body').animate({
                    scrollTop: 0
                }, 500);
            }
        },

        showSpinner: function () {
            $(this.options.spinnerSelectorOuter).find(this.options.spinnerSelectorInner).show();
        },

        hideSpinner: function () {
            $(this.options.spinnerSelectorOuter).find(this.options.spinnerSelectorInner).hide();
        }
    });

    return $.mage.tokenbasePaymentinfo;
});
