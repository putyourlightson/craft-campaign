/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SendoutPreflight class
 */
Campaign.SendoutPreflight = Garnish.Base.extend({
    modal: null,

    init: function() {
        this.getPendingRecipientCount();
        this.addListener($('.prepare'), 'click', 'preflight');
        this.addListener($('.preflight .cancel'), 'click', 'cancel');
        this.addListener($('.preflight .launch'), 'click', 'launch');
    },

    getPendingRecipientCount: function() {
        if ($('.pendingRecipientCount').length) {
            if ($('.preflight').length) {
                $('.preflight .message').html(
                    $('.preflight .message').html().replace('{recipients}', '<span class="pendingRecipientCount"></span>')
                );
            }

            const url = Craft.getActionUrl('campaign/sendouts/get-pending-recipient-count');
            const sendoutId = $('input[name=sendoutId]').val();

            $.get(url, {sendoutId: sendoutId}, (data) => {
                $('.pendingRecipientCount').replaceWith(data);
            });
        }
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

        if ($('.preflight .launch').hasClass('disabled')) {
            return;
        }

        $('.preflight .launch').disable();
        $('.preflight .cancel').disable();
        $('.preflight .spinner').removeClass('hidden');

        const data = {
            sendoutId: $('input[name=sendoutId]').val()
        };

        Craft.sendActionRequest('POST', 'campaign/sendouts/send', {data})
            .then((response) => {
                if (Craft.runQueueAutomatically) {
                    Craft.sendActionRequest('POST', 'queue/run');
                }

                $('.preflight .confirm').fadeOut(function() {
                    $('.preflight .launched').fadeIn();
                });
            })
            .catch(({response}) => {
                if (response.data.message) {
                    $('.preflight .error').text(response.data.message).removeClass('hidden');
                }
                else {
                    Craft.cp.displayError();
                }
            })
            .finally(() => {
                $('.preflight .spinner').addClass('hidden');
            });
    },
});

new Campaign.SendoutPreflight();
