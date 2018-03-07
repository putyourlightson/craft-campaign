/** global: Campaign */
/** global: Garnish */
/**
 * MailingListIndex class
 */
Campaign.MailingListIndex = Craft.BaseElementIndex.extend(
    {
        publishableMailingListTypes: null,
        $newMailingListBtnGroup: null,
        $newMailingListBtn: null,

        afterInit: function() {
            // Get publishable mailing list types
            this.publishableMailingListTypes = [];

            for (var i = 0; i < Craft.publishableMailingListTypes.length; i++) {
                var mailingListType = Craft.publishableMailingListTypes[i];

                if (this.getSourceByKey('mailingListType:' + mailingListType.id)) {
                    this.publishableMailingListTypes.push(mailingListType);
                }
            }

            this.base();
        },

        getDefaultSourceKey: function() {
            // Did they request a specific mailing list type in the URL?
            if (this.settings.context == 'index' && typeof defaultMailingListTypeHandle !== 'undefined') {
                for (var i = 0; i < this.$sources.length; i++) {
                    var $source = $(this.$sources[i]);

                    if ($source.data('handle') == defaultMailingListTypeHandle) {
                        return $source.data('key');
                    }
                }
            }

            return this.base();
        },

        onSelectSource: function() {
            var handle;

            // Get the handle of the selected source
            handle = this.$source.data('handle');

            // Update the New mailing list button
            // ---------------------------------------------------------------------

            if (this.publishableMailingListTypes.length) {
                // Remove the old button, if there is one
                if (this.$newMailingListBtnGroup) {
                    this.$newMailingListBtnGroup.remove();
                }

                // Determine if they are viewing a mailing list type
                var selectedMailingListType;

                if (handle) {
                    for (var i = 0; i < this.publishableMailingListTypes.length; i++) {
                        if (this.publishableMailingListTypes[i].handle == handle) {
                            selectedMailingListType = this.publishableMailingListTypes[i];
                            break;
                        }
                    }
                }

                this.$newMailingListBtnGroup = $('<div class="btngroup submit"/>');
                var $menuBtn;

                // If they are, show a primary "New mailing list" button, and a dropdown of the other mailing list types (if any).
                // Otherwise only show a menu button
                if (selectedMailingListType) {
                    var href = this._getMailingListTypeTriggerHref(selectedMailingListType),
                        label = (this.settings.context == 'index' ? Craft.t('campaign', 'New mailing list') : Craft.t('campaign', 'New {mailingListType} mailing list', {mailingListType: selectedMailingListType.name}));
                    this.$newMailingListBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newMailingListBtnGroup);

                    if (this.settings.context != 'index') {
                        this.addListener(this.$newMailingListBtn, 'click', function(ev) {
                            this._openCreateMailingListModal(ev.currentTarget.getAttribute('data-id'));
                        });
                    }

                    if (this.publishableMailingListTypes.length > 1) {
                        $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newMailingListBtnGroup);
                    }
                }
                else {
                    this.$newMailingListBtn = $menuBtn = $('<div class="btn submit add icon menubtn">' + Craft.t('campaign', 'New mailing list') + '</div>').appendTo(this.$newMailingListBtnGroup);
                }

                if ($menuBtn) {
                    var menuHtml = '<div class="menu"><ul>';

                    for (var i = 0; i < this.publishableMailingListTypes.length; i++) {
                        var mailingListType = this.publishableMailingListTypes[i];

                        if (this.settings.context == 'index' || mailingListType != selectedMailingListType) {
                            var href = this._getMailingListTypeTriggerHref(mailingListType),
                                label = (this.settings.context == 'index' ? mailingListType.name : Craft.t('campaign', 'New {mailingListType} mailing list', {mailingListType: mailingListType.name}));
                            menuHtml += '<li><a ' + href + '">' + Craft.escapeHtml(label) + '</a></li>';
                        }
                    }

                    menuHtml += '</ul></div>';

                    var $menu = $(menuHtml).appendTo(this.$newMailingListBtnGroup),
                        menuBtn = new Garnish.MenuBtn($menuBtn);

                    if (this.settings.context != 'index') {
                        menuBtn.on('optionSelect', $.proxy(function(ev) {
                            this._openCreateMailingListModal(ev.option.getAttribute('data-id'));
                        }, this));
                    }
                }

                this.addButton(this.$newMailingListBtnGroup);
            }

            // Update the URL if we're on the MailingLists index
            // ---------------------------------------------------------------------

            if (this.settings.context == 'index' && typeof history !== 'undefined') {
                var uri = 'campaign/mailinglists';

                if (handle) {
                    uri += '/' + handle;
                }

                history.replaceState({}, '', Craft.getUrl(uri));
            }

            this.base();
        },

        _getMailingListTypeTriggerHref: function(mailingListType) {
            if (this.settings.context == 'index') {
                return 'href="' + Craft.getUrl('campaign/mailinglists/' + mailingListType.handle + '/new') + '"';
            }
            else {
                return 'data-id="' + mailingListType.id + '"';
            }
        },

        _openCreateMailingListModal: function(mailingListTypeId) {
            if (this.$newMailingListBtn.hasClass('loading')) {
                return;
            }

            // Find the mailing list type
            var mailingListType;

            for (var i = 0; i < this.publishableMailingListTypes.length; i++) {
                if (this.publishableMailingListTypes[i].id == mailingListTypeId) {
                    mailingListType = this.publishableMailingListTypes[i];
                    break;
                }
            }

            if (!mailingListType) {
                return;
            }

            this.$newMailingListBtn.addClass('inactive');
            var newMailingListBtnText = this.$newMailingListBtn.text();
            this.$newMailingListBtn.text(Craft.t('campaign', 'New {mailingListType} mailing list', {mailingListType: mailingListType.name}));

            Craft.createElementEditor(this.elementType, {
                hudTrigger: this.$newMailingListBtnGroup,
                elementType: 'putyourlightson\\campaign\\elements\\MailingListElement',
                attributes: {
                    mailingListTypeId: mailingListTypeId
                },
                onBeginLoading: $.proxy(function() {
                    this.$newMailingListBtn.addClass('loading');
                }, this),
                onEndLoading: $.proxy(function() {
                    this.$newMailingListBtn.removeClass('loading');
                }, this),
                onHideHud: $.proxy(function() {
                    this.$newMailingListBtn.removeClass('inactive').text(newMailingListBtnText);
                }, this),
                onSaveElement: $.proxy(function(response) {
                    // Make sure the right mailing list type is selected
                    var mailingListTypeSourceKey = 'mailingListType:' + mailingListTypeId;

                    if (this.sourceKey != mailingListTypeSourceKey) {
                        this.selectSourceByKey(mailingListTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(response.id);
                    this.updateElements();
                }, this)
            });
        }
    });

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\MailingListElement', Campaign.MailingListIndex);
