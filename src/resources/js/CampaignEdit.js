/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * CampaignEdit class
 */
Campaign.CampaignEdit = Garnish.Base.extend({
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

        Craft.sendActionRequest('POST', 'campaign/campaigns/send-test', {data})
            .then((response) => {
                Craft.cp.displayNotice(response.data.message);
            })
            .catch(({response}) => {
                if (response.data.message) {
                    Craft.cp.displayError(response.data.message);
                }
                else {
                    Craft.cp.displayError();
                }
            })
            .finally(() => {
                $('.send-test').removeClass('disabled');
            });
    },
});

new Campaign.CampaignEdit();
