/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * CampaignIndex class
 */
Campaign.CampaignIndex = Craft.BaseElementIndex.extend({
    editableCampaignTypes: null,
    $newCampaignBtnGroup: null,
    $newCampaignBtn: null,

    init: function(elementType, $container, settings) {
        this.editableCampaignTypes = [];
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Find which of the visible campaign types the user has permission to create new campaigns in
        this.editableCampaignTypes = Craft.editableCampaignTypes.filter(
            campaignType => !!this.getSourceByKey(`campaignType:${campaignType.uid}`)
        );

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific campaign type in the URL?
        if (this.settings.context === 'index' && typeof defaultCampaignTypeHandle !== 'undefined') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                if ($source.data('handle') === defaultCampaignTypeHandle) {
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

        // Update the New campaign button
        // ---------------------------------------------------------------------

        // Remove the old button, if there is one
        if (this.$newCampaignBtnGroup) {
            this.$newCampaignBtnGroup.remove();
        }

        // Get the editable campaign types for the current site
        const editableCampaignTypesForSite = this.editableCampaignTypes.filter(
            campaignType => campaignType.siteId === this.siteId
        );

        if (editableCampaignTypesForSite.length) {
            // Determine if they are viewing a campaign type that they have permission to create campaigns in
            const selectedCampaignType = editableCampaignTypesForSite.find(
                campaignType => campaignType.handle === selectedSourceHandle
            );

            this.$newCampaignBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
            let $menuBtn;
            const menuId = 'new-campaign-menu-' + Craft.randomString(10);

            // If they are, show a primary "New campaign" button, and a dropdown of the other campaign types (if any).
            // Otherwise, only show a menu button.
            if (selectedCampaignType) {
                this.$newCampaignBtn = Craft.ui.createButton({
                        label: this.settings.context === 'index'
                            ? Craft.t('campaign', 'New campaign')
                            : Craft.t('campaign', 'New {campaignType} campaign', {
                                campaignType: selectedCampaignType.name,
                            }),
                        spinner: true,
                    })
                    .addClass('submit add icon')
                    .appendTo(this.$newCampaignBtnGroup);

                this.addListener(this.$newCampaignBtn, 'click', () => {
                    this._createCampaign(selectedCampaignType.id);
                });

                if (editableCampaignTypesForSite.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn btngroup-btn-last',
                        'aria-controls': menuId,
                        'data-disclosure-trigger': '',
                    }).appendTo(this.$newCampaignBtnGroup);
                }
            }
            else {
                this.$newCampaignBtn = $menuBtn = Craft.ui.createButton({
                        label: Craft.t('campaign', 'New campaign'),
                        spinner: true,
                    })
                    .addClass('submit add icon menubtn btngroup-btn-last')
                    .attr('aria-controls', menuId)
                    .attr('data-disclosure-trigger', '')
                    .appendTo(this.$newCampaignBtnGroup);
            }

            this.addButton(this.$newCampaignBtnGroup);

            if ($menuBtn) {
                const $menuContainer = $('<div/>', {
                    id: menuId,
                    class: 'menu menu--disclosure',
                }).appendTo(this.$newCampaignBtnGroup);
                const $ul = $('<ul/>').appendTo($menuContainer);

                for (const campaignType of editableCampaignTypesForSite) {
                    if (this.settings.context === 'index' || campaignType !== selectedCampaignType) {
                        const $li = $('<li/>').appendTo($ul);
                        const $a = $('<a/>', {
                            role: 'button',
                            tabindex: '0',
                            text: Craft.t('campaign', 'New {campaignType} campaign', {
                                campaignType: campaignType.name,
                            }),
                        }).appendTo($li);
                        this.addListener($a, 'click', () => {
                            $menuBtn.data('trigger').hide();
                            this._createCampaign(campaignType.id);
                        });
                    }
                }

                new Garnish.DisclosureMenu($menuBtn);
            }
        }

        // Update the URL if we're on the Campaigns index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index' && typeof history !== 'undefined') {
            let uri = 'campaign/campaigns';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            const url = Craft.getUrl(uri, document.location.search + document.location.hash);
            history.replaceState({}, '', url);
        }
    },

    _createCampaign: function(campaignTypeId) {
        if (this.$newCampaignBtn.hasClass('loading')) {
            console.warn('New campaign creation already in progress.');
            return;
        }

        // Find the campaign type
        const campaignType = this.editableCampaignTypes.find(
            campaignType => campaignType.id === campaignTypeId
        );

        if (!campaignType) {
            throw `Invalid campaign type ID: ${campaignTypeId}`;
        }

        this.$newCampaignBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: this.elementType,
                siteId: this.siteId,
                campaignTypeId: campaignTypeId,
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
                    // Make sure the right campaign type is selected
                    const campaignTypeSourceKey = `campaignType:${campaignType.uid}`;

                    if (this.sourceKey !== campaignTypeSourceKey) {
                        this.selectSourceByKey(campaignTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(ev.data.element.id);
                    this.updateElements();
                });
            }
        }).finally(() => {
            this.$newCampaignBtn.removeClass('loading');
        });
    },
});

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\CampaignElement', Campaign.CampaignIndex);
