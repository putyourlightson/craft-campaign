/** global: Campaign */
/** global: Garnish */
/**
 * CampaignEdit class
 */
Campaign.CampaignEdit = Garnish.Base.extend(
    {
        init: function() {
            this.addListener($('.send-test'), 'click', 'sendTest');
            this.addListener($('#testEmail'), 'keypress', 'testEmailKeypress');
        },

        sendTest: function(event) {
            var data = {
                testEmail: $('#testEmail').val(),
                campaignId: $('input[name=campaignId]').val()
            };

            Craft.postActionRequest('campaign/campaigns/send-test', data, function(response) {
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

new Campaign.CampaignEdit();