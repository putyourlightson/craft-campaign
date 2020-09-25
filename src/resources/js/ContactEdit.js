/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * ContactEdit class
 */
Campaign.ContactEdit = Garnish.Base.extend(
    {
        init: function() {
            this.addListener($.find('.update-subscription'), 'click', 'updateSubscription');
        },

        updateSubscription: function(event) {
            var $this = $(event.target);
            var $row = $this.closest('tr');

            if (confirm($this.attr('data-confirm'))) {
                var data = {
                    contactId: $row.attr('data-contact-id'),
                    mailingListId: $row.attr('data-mailing-list-id'),
                };

                Craft.postActionRequest($this.attr('data-action'), data, $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        $row.find('.subscriptionStatus').attr('class', 'subscriptionStatus ' + response.subscriptionStatus).text(response.subscriptionStatusLabel);

                        $row.find('input, .remove').addClass('hidden');

                        if (response.subscriptionStatus == 'subscribed') {
                            $row.find('input.unsubscribe').removeClass('hidden');
                        }
                        else {
                            $row.find('input.subscribe').removeClass('hidden');
                        }

                        if (response.subscriptionStatus) {
                            $row.find('.remove').removeClass('hidden');
                        }

                        Craft.cp.displayNotice(Craft.t('campaign', 'Subscription successfully updated.'));
                    }
                    else {
                        Craft.cp.displayError(Craft.t('campaign', 'Couldn’t update subscription.'));
                    }
                }, this));
            }
        },
    }
);

new Campaign.ContactEdit();
