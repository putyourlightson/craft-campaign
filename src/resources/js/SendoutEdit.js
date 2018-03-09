/** global: Campaign */
/** global: Garnish */
/**
 * SendoutEdit class
 */
Campaign.SendoutEdit = Garnish.Base.extend(
    {
        modal: null,

        init: function() {
            this.addListener($('.prepare'), 'click', 'preflight');
            this.addListener($('.preflight .cancel'), 'click', 'cancel');
            this.addListener($('.preflight .launch'), 'click', 'launch');
            this.addListener($('.send-test'), 'click', 'sendTest');
            this.addListener($('#testEmail'), 'keypress', 'testEmailKeypress');
        },

        preflight: function(event) {
            if (this.modal === null) {
                this.modal = new Garnish.Modal($('.preflight'), {
                    hideOnEsc: false,
                    hideOnShadeClick: false
                });
            }
            else {
                this.modal.show();
            }
        },

        cancel: function(event) {
            if (!$('.preflight .cancel').hasClass('disabled')) {
                this.modal.hide();
            }
        },

        launch: function(event) {
            event.preventDefault();

            if (!$('.preflight .launch').hasClass('disabled')) {
                $('.preflight .launch').disable();
                $('.preflight .cancel').disable();
                $('.preflight .spinner').removeClass('hidden');

                var data = {
                    sendoutId: $('input[name=sendoutId]').val()
                };

                Craft.postActionRequest('campaign/sendouts/send-sendout', data, function(response, textStatus) {
                    if (textStatus !== 'success' || !response.success) {
                        $('.preflight .spinner').addClass('hidden');
                        $('.preflight .error').text(response.error).removeClass('hidden');
                        return;
                    }

                    Craft.postActionRequest('queue/run');

                    $('.preflight .confirm').fadeOut(function() {
                        $('.preflight .launched').fadeIn();
                    });
                });
            }
        },

        sendTest: function(event) {
            var data = {
                testEmail: $('#testEmail').val(),
                sendoutId: $('input[name=sendoutId]').val()
            };

            Craft.postActionRequest('campaign/sendouts/send-test', data, function(response, textStatus) {
                if (textStatus === 'success') {
                    if (response.success) {
                        Craft.cp.displayNotice(Craft.t('campaign', 'Test email sent.'));
                    }
                    else {
                        Craft.cp.displayError(response.error);
                    }
                }
            });
        },

        testEmailKeypress: function(event) {
            if (event.which == 13) {
                event.preventDefault();
                this.sendTest();
            }
        },
    }
);

new Campaign.SendoutEdit();