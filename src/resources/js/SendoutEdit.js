/** global: Campaign */
/** global: Garnish */
/**
 * SendoutEdit class
 */
Campaign.SendoutEdit = Garnish.Base.extend(
    {
        init: function() {
            this.addListener($('.confirm-send').closest('form'), 'submit', 'confirmSend');
            this.addListener($('.send-test'), 'click', 'sendTest');
            this.addListener($('#testEmail'), 'keypress', 'testEmailKeypress');
        },

        confirmSend: function(event) {
            if ($('.confirm-send').closest('form').find('input[name=action]').length == 1) {
                var confirmMessage = $('.confirm-send').attr('data-confirm');
                if (confirmMessage && !confirm(confirmMessage)) {
                    event.preventDefault();
                };
            }
        },

        sendTest: function(event) {
            var data = {
                testEmail: $('#testEmail').val(),
                sendoutId: $('input[name=sendoutId]').val()
            };

            Craft.postActionRequest('campaign/sendouts/send-test', data, function(response) {
                if (response.success) {
                    Craft.cp.displayNotice(Craft.t('campaign', 'Test email sent.'));
                }
                else {
                    Craft.cp.displayError(response.error);
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