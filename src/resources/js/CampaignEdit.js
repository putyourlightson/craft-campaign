/** global: Campaign */
/** global: Garnish */
/**
 * CampaignEdit class
 */
Campaign.CampaignEdit = Garnish.Base.extend(
    {
        init: function() {
            this.addListener($('.send-test'), 'click', 'sendTest');
        },

        sendTest: function(event) {
            var data = {
                contactId: $('#testContact input').val(),
                campaignId: $('input[name=campaignId]').val()
            };

            Craft.postActionRequest('campaign/campaigns/send-test', data, function(response, textStatus) {
                if (textStatus === 'success') {
                    if (response.success) {
                        Craft.cp.displayNotice(Craft.t('campaign', 'Test email sent.'));
                    } else {
                        Craft.cp.displayError(response.error);
                    }
                }
            });
        },
    }
);

new Campaign.CampaignEdit();