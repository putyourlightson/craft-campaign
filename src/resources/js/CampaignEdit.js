/** global: Campaign */
/** global: Craft */
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
            if ($('.send-test').hasClass('disabled')) {
                return;
            }

            $('.send-test').addClass('disabled');

            var contactIds = [];
            $('#testContacts input').each(function() {
                contactIds.push($(this).val());
            });

            var data = {
                contactIds: contactIds,
                campaignId: $('input[name=campaignId]').val()
            };

            Craft.postActionRequest('campaign/campaigns/send-test', data, function(response, textStatus) {
                if (textStatus === 'success') {
                    if (response.success) {
                        Craft.cp.displayNotice(Craft.t('campaign', 'Test email sent.'));
                    }
                    else {
                        Craft.cp.displayError(response.error);
                    }
                }
                else if (typeof response.error !== 'undefined') {
                    Craft.cp.displayError(response.error);
                }

                $('.send-test').removeClass('disabled');
            });
        },
    }
);

new Campaign.CampaignEdit();
