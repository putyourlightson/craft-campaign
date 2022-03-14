/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SendoutEdit class
 */
Campaign.SendoutEdit = Garnish.Base.extend(
    {
        modal: null,

        init: function() {
            this.getPendingRecipientCount();
            this.addListener($('.prepare'), 'click', 'preflight');
            this.addListener($('.preflight .cancel'), 'click', 'cancel');
            this.addListener($('.preflight .launch'), 'click', 'launch');
            this.addListener($('.send-test'), 'click', 'sendTest');
        },

        getPendingRecipientCount: function() {
            if ($('.pendingRecipientCount').length) {
                if ($('.preflight').length) {
                    $('.preflight .message').html(
                        $('.preflight .message').html().replace('{recipients}', '<span class="pendingRecipientCount"></span>')
                    );
                }

                var url = Craft.getActionUrl('campaign/sendouts/get-pending-recipient-count');
                var sendoutId = $('input[name=sendoutId]').val();

                $.get(url, {sendoutId: sendoutId}, function(data) {
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

            var data = {
                sendoutId: $('input[name=sendoutId]').val()
            };

            Craft.postActionRequest('campaign/sendouts/send', data, function(response, textStatus) {
                $('.preflight .spinner').addClass('hidden');

                if (textStatus === 'success') {
                    if (response.success) {
                        if (Craft.runQueueAutomatically) {
                            Craft.postActionRequest('queue/run');
                        }

                        $('.preflight .confirm').fadeOut(function() {
                            $('.preflight .launched').fadeIn();
                        });
                    }
                    else if (response.errors) {
                        $('.preflight .error').text(response.error).removeClass('hidden');
                    }
                    else {
                        Craft.cp.displayError();
                    }
                }
            });
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

                $('.send-test').removeClass('disabled');
            });
        },
    }
);

new Campaign.SendoutEdit();
