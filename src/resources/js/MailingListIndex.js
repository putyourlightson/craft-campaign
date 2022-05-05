/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * MailingListIndex class
 */
Campaign.MailingListIndex = Craft.BaseElementIndex.extend({
    editableMailingListTypes: null,
    $newMailingListBtnGroup: null,
    $newMailingListBtn: null,

    init: function(elementType, $container, settings) {
        this.editableMailingListTypes = [];
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Find which of the visible mailing list types the user has permission to create new mailing lists in
        this.editableMailingListTypes = Craft.editableMailingListTypes.filter(
            mailingListType => !!this.getSourceByKey(`mailingListType:${mailingListType.uid}`)
        );

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific mailing list type in the URL?
        if (this.settings.context === 'index' && typeof defaultMailingListTypeHandle !== 'undefined') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                if ($source.data('handle') === defaultMailingListTypeHandle) {
                    return $source.data('key');
                }
            }
        }

        return this.base();
    },

    updateButton: function() {
        if (!this.$source) {
            return;
        }

        // Get the handle of the selected source
        const selectedSourceHandle = this.$source.data('handle');

        // Update the New mailing list button
        // ---------------------------------------------------------------------

        // Remove the old button, if there is one
        if (this.$newMailingListBtnGroup) {
            this.$newMailingListBtnGroup.remove();
        }

        // Get the editable mailing list types for the current site
        const editableMailingListTypesForSite = this.editableMailingListTypes.filter(
            mailingListType => mailingListType.siteId === this.siteId
        );

        if (editableMailingListTypesForSite.length) {
            // Determine if they are viewing a mailing list type that they have permission to create mailing lists in
            const selectedMailingListType = editableMailingListTypesForSite.find(
                mailingListType => mailingListType.handle === selectedSourceHandle
            );

            this.$newMailingListBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
            let $menuBtn;
            const menuId = 'new-campaign-menu-' + Craft.randomString(10);

            // If they are, show a primary "New mailing list" button, and a dropdown of the other mailing list types (if any).
            // Otherwise only show a menu button
            if (selectedMailingListType) {
                this.$newMailingListBtn = Craft.ui.createButton({
                        label: this.settings.context === 'index'
                            ? Craft.t('campaign', 'New mailing list')
                            : Craft.t('campaign', 'New {mailingListType} mailing list', {
                                mailingListType: selectedMailingListType.name,
                            }),
                        spinner: true,
                    })
                    .addClass('submit add icon')
                    .appendTo(this.$newMailingListBtnGroup);

                this.addListener(this.$newMailingListBtn, 'click', () => {
                    this._createMailingList(selectedMailingListType.id);
                });

                if (editableMailingListTypesForSite.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn btngroup-btn-last',
                        'aria-controls': menuId,
                        'data-disclosure-trigger': '',
                    }).appendTo(this.$newMailingListBtnGroup);
                }
            }
            else {
                this.$newMailingListBtn = $menuBtn = Craft.ui.createButton({
                        label: Craft.t('campaign', 'New mailing list'),
                        spinner: true,
                    })
                    .addClass('submit add icon menubtn btngroup-btn-last')
                    .attr('aria-controls', menuId)
                    .attr('data-disclosure-trigger', '')
                    .appendTo(this.$newMailingListBtnGroup);
            }

            this.addButton(this.$newMailingListBtnGroup);

            if ($menuBtn) {
                const $menuContainer = $('<div/>', {
                    id: menuId,
                    class: 'menu menu--disclosure',
                }).appendTo(this.$newMailingListBtnGroup);
                const $ul = $('<ul/>').appendTo($menuContainer);

                for (const mailingListType of editableMailingListTypesForSite) {
                    if (this.settings.context === 'index' || mailingListType !== selectedMailingListType) {
                        const $li = $('<li/>').appendTo($ul);
                        const $a = $('<a/>', {
                            role: 'button',
                            tabindex: '0',
                            text: Craft.t('campaign', 'New {mailingListType} mailing list', {
                                mailingListType: mailingListType.name,
                            }),
                        }).appendTo($li);
                        this.addListener($a, 'click', () => {
                            $menuBtn.data('trigger').hide();
                            this._createMailingList(mailingListType.id);
                        });
                    }
                }

                new Garnish.DisclosureMenu($menuBtn);
            }
        }

        // Update the URL if we're on the MailingLists index
        // ---------------------------------------------------------------------

        if (this.settings.context == 'index' && typeof history !== 'undefined') {
            let uri = 'campaign/mailinglists';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            const url = Craft.getUrl(uri, document.location.search + document.location.hash);
            history.replaceState({}, '', url);
        }
    },

    _createMailingList: function(mailingListTypeId) {
        if (this.$newMailingListBtn.hasClass('loading')) {
            console.warn('New mailing list creation already in progress.');
            return;
        }

        // Find the mailingListType
        const mailingListType = this.editableMailingListTypes.find(
            mailingListType => mailingListType.id === mailingListTypeId
        );

        if (!mailingListType) {
            throw `Invalid mailing list type ID: ${mailingListTypeId}`;
        }

        this.$newMailingListBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: this.elementType,
                siteId: this.siteId,
                mailingListTypeId: mailingListTypeId,
            },
        }).then(ev => {
            if (this.settings.context === 'index') {
                document.location.href = Craft.getUrl(ev.data.cpEditUrl, {fresh: 1});
            }
            else {
                const slideout = Craft.createElementEditor(this.elementType, {
                    siteId: this.siteId,
                    elementId: ev.data.element.id,
                    draftId: ev.data.element.draftId,
                    params: {
                        fresh: 1,
                    },
                });
                slideout.on('submit', () => {
                    // Make sure the right mailingListType is selected
                    const mailingListTypeSourceKey = `mailingListType:${mailingListType.uid}`;

                    if (this.sourceKey !== mailingListTypeSourceKey) {
                        this.selectSourceByKey(mailingListTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(ev.data.element.id);
                    this.updateElements();
                });
            }
        }).finally(() => {
            this.$newMailingListBtn.removeClass('loading');
        });
    },
});

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\MailingListElement', Campaign.MailingListIndex);
