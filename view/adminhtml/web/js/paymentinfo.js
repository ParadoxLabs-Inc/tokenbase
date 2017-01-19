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
            editSelector: '.links a, .action-edit, .action-back',
            editSelectorInd: '.address-list-item',
            editSelectorIndTarget: '.action-edit:first',
            formSelector: 'form',
            saveSelector: '.action-save'
        },

        _create: function() {
            var wrapper = this;
            var spinner = $(wrapper.options.spinnerSelectorOuter).find(wrapper.options.spinnerSelectorInner);

            // Remove silly class on the wrapper
            wrapper.element.parent().removeClass('admin__scope-old');

            // Catch deletes
            wrapper.element.find(wrapper.options.deleteSelector).off().on('click', function() {
                if (confirm($.mage.__("Are you sure you want to delete this?"))) {
                    var card = this;

                    spinner.show();
                    $.post(this.href, function(data) {
                        spinner.hide();

                        if(data.success) {
                            $(card).closest('li').slideUp();
                        }
                        else if(typeof data.message != 'undefined') {
                            alert(data.message);
                        }
                    }, 'json');
                }

                return false;
            });

            // Catch method changes and card edits
            wrapper.element.find(wrapper.options.editSelector).off().on('click', function() {
                spinner.show();
                wrapper.element.parent().load(this.href, function () {
                    wrapper._create();
                    spinner.hide();
                });

                return false;
            });

            // Catch indirect card edits
            wrapper.element.find(wrapper.options.editSelectorInd).off().on('click', function(e) {
                e.preventDefault();

                wrapper.element.find(wrapper.options.editSelectorIndTarget).trigger('click');
            });

            // Catch saves
            wrapper.element.find(wrapper.options.formSelector).off().on('submit', function(e) {
                e.preventDefault();

                $(wrapper.element).find(wrapper.options.saveSelector).prop('disabled', true);

                spinner.show();
                $.post(this.action, $(this).serialize(), function(data) {
                    spinner.hide();

                    if(typeof data === 'object') {
                        if(typeof data.message != 'undefined') {
                            alert(data.message);

                            wrapper.element.find(wrapper.options.saveSelector).removeProp('disabled');
                        }
                    }
                    else {
                        wrapper.element.parent().html(data);
                        wrapper._create();
                    }
                });

                return false;
            });

            wrapper.element.find(wrapper.options.saveSelector).off().on('click', function(e) {
                e.preventDefault();

                $(this).prop('disabled', true);

                spinner.show();
                $.post(this.form.action, $(this.form).serialize(), function(data) {
                    spinner.hide();

                    if(typeof data === 'object') {
                        if(typeof data.message != 'undefined') {
                            alert(data.message);

                            $(wrapper.element).find(wrapper.options.saveSelector).prop('disabled', false);
                        }
                    }
                    else {
                        wrapper.element.parent().html(data);
                        wrapper._create();
                    }
                });

                return false;
            });
        }
    });

    return $.mage.tokenbasePaymentinfo;
});
