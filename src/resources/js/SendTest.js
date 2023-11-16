/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SendTest class
 */
Campaign.SendTest = Garnish.Base.extend({
    $elementSelect: null,

    init: function() {
        this.$elementSelect = $('.test-email .elementselect');
        this.preventUnsavedChanges();
        this.addListener(this.$elementSelect, 'change', 'preventUnsavedChanges');
        this.addListener($('.send-test'), 'click', 'sendTest');
    },

    preventUnsavedChanges: function() {
        this.$elementSelect.find('input').attr('name', '');
    },

    sendTest: function(event) {
        event.preventDefault();

        if ($('.send-test').hasClass('disabled')) {
            return;
        }

        $('.send-test').addClass('disabled');

        const contactIds = [];
        this.$elementSelect.find('input').each(function() {
            contactIds.push($(this).val());
        });

        const data = {
            contactIds: contactIds,
            campaignId: $('.send-test').data('campaign'),
            sendoutId: $('.send-test').data('sendout'),
        };

        Craft.sendActionRequest('POST', $('.send-test').data('action'), {data})
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

new Campaign.SendTest();
