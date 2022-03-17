/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * ContactEdit class
 */
Campaign.ContactEdit = Garnish.Base.extend(
    {
        init: function() {
            this.initElementThumbs();
            this.addListener($.find('.update-subscription'), 'click', 'updateSubscription');
        },

        initElementThumbs: function() {
            var elements = $('.meta .element');

            if (elements.length) {
                var thumbLoader = new Craft.ElementThumbLoader();
                thumbLoader.load(elements);
            }
        },

        updateSubscription: function(event) {
            var $this = $(event.target);
            var $row = $this.closest('tr');

            if (confirm($this.attr('data-confirm'))) {
                var data = {
                    contactId: $row.attr('data-contact-id'),
                    mailingListId: $row.attr('data-mailing-list-id'),
                };

                Craft.sendActionRequest('POST', $this.attr('data-action'), {data})
                    .then((response) => {
                        $row.find('.subscriptionStatus').attr('class', 'subscriptionStatus ' + response.data.subscriptionStatus).text(response.data.subscriptionStatusLabel);

                        $row.find('input, .remove').addClass('hidden');

                        if (response.data.subscriptionStatus == 'subscribed') {
                            $row.find('input.unsubscribe').removeClass('hidden');
                        }
                        else {
                            $row.find('input.subscribe').removeClass('hidden');
                        }

                        if (response.data.subscriptionStatus) {
                            $row.find('.remove').removeClass('hidden');
                        }

                        Craft.cp.displayNotice(response.data.message);
                    })
                    .catch(({response}) => {
                        if (response.data.message) {
                            Craft.cp.displayError(response.data.message);
                        }
                        else {
                            Craft.cp.displayError();
                        }
                    });
            }
        },
    }
);

new Campaign.ContactEdit();
