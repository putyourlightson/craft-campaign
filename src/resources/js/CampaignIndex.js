/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * CampaignIndex class
 */
Campaign.CampaignIndex = Craft.BaseElementIndex.extend({
    publishableCampaignTypes: null,
    $newCampaignBtnGroup: null,
    $newCampaignBtn: null,

    init: function(elementType, $container, settings) {
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Get publishable campaign types
        this.publishableCampaignTypes = [];

        for (var i = 0; i < Craft.publishableCampaignTypes.length; i++) {
            var campaignType = Craft.publishableCampaignTypes[i];

            if (this.getSourceByKey('campaignType:' + campaignType.uid)) {
                this.publishableCampaignTypes.push(campaignType);
            }
        }

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific campaign type in the URL?
        if (this.settings.context == 'index' && typeof defaultCampaignTypeHandle !== 'undefined') {
            for (var i = 0; i < this.$sources.length; i++) {
                var $source = $(this.$sources[i]);

                if ($source.data('handle') == defaultCampaignTypeHandle) {
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
        var selectedSourceHandle = this.$source.data('handle');

        // Update the New campaign button
        // ---------------------------------------------------------------------

        if (this.publishableCampaignTypes.length) {
            // Remove the old button, if there is one
            if (this.$newCampaignBtnGroup) {
                this.$newCampaignBtnGroup.remove();
            }

            // Determine if they are viewing a campaign type
            var selectedCampaignType;

            if (selectedSourceHandle) {
                for (var i = 0; i < this.publishableCampaignTypes.length; i++) {
                    if (this.publishableCampaignTypes[i].handle == selectedSourceHandle) {
                        selectedCampaignType = this.publishableCampaignTypes[i];
                        break;
                    }
                }
            }

            this.$newCampaignBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            // If they are, show a primary "New campaign" button, and a dropdown of the other campaign types (if any).
            // Otherwise only show a menu button
            if (selectedCampaignType) {
                var href = this._getCampaignTypeTriggerHref(selectedCampaignType),
                    label = (this.settings.context == 'index' ? Craft.t('campaign', 'New campaign') : Craft.t('campaign', 'New {campaignType} campaign', {campaignType: selectedCampaignType.name}));
                this.$newCampaignBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newCampaignBtnGroup);

                if (this.settings.context != 'index') {
                    this.addListener(this.$newCampaignBtn, 'click', function(ev) {
                        this._openCreateCampaignModal(ev.currentTarget.getAttribute('data-id'));
                    });
                }

                if (this.publishableCampaignTypes.length > 1) {
                    $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newCampaignBtnGroup);
                }
            }
            else {
                this.$newCampaignBtn = $menuBtn = $('<div class="btn submit add icon menubtn">' + Craft.t('campaign', 'New campaign') + '</div>').appendTo(this.$newCampaignBtnGroup);
            }

            if ($menuBtn) {
                var menuHtml = '<div class="menu"><ul>';

                for (var i = 0; i < this.publishableCampaignTypes.length; i++) {
                    var campaignType = this.publishableCampaignTypes[i];

                    if (
                        (this.settings.context === 'index' && this.siteId == campaignType.siteId) ||
                        (this.settings.context !== 'index' && campaignType != selectedCampaignType)
                    ) {
                        href = this._getCampaignTypeTriggerHref(campaignType);
                        label = (this.settings.context == 'index' ? campaignType.name : Craft.t('campaign', 'New {campaignType} campaign', {campaignType: campaignType.name}));
                        menuHtml += '<li><a ' + href + '">' + Craft.escapeHtml(label) + '</a></li>';
                    }
                }

                menuHtml += '</ul></div>';

                $(menuHtml).appendTo(this.$newCampaignBtnGroup);
                var menuBtn = new Garnish.MenuBtn($menuBtn);

                if (this.settings.context != 'index') {
                    menuBtn.on('optionSelect', ev => {
                        this._openCreateCampaignModal(ev.option.getAttribute('data-id'));
                    });
                }
            }

            this.addButton(this.$newCampaignBtnGroup);
        }

        // Update the URL if we're on the Campaigns index
        // ---------------------------------------------------------------------

        if (this.settings.context == 'index' && typeof history !== 'undefined') {
            var uri = 'campaign/campaigns';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            const url = Craft.getUrl(uri, document.location.search + document.location.hash);
            history.replaceState({}, '', url);
        }
    },

    _getCampaignTypeTriggerHref: function(campaignType) {
        if (this.settings.context == 'index') {
            const uri = `campaign/campaigns/${campaignType.handle}/new`;
            const site = this.getSite();
            const params = site ? {site: site.handle} : undefined;
            return `href="${Craft.getUrl(uri, params)}"`;
        }

        return `data-id="${campaignType.id}"`;
    },

    _openCreateCampaignModal: function(campaignTypeId) {
        if (this.$newCampaignBtn.hasClass('loading')) {
            return;
        }

        // Find the campaign type
        var campaignType;

        for (var i = 0; i < this.publishableCampaignTypes.length; i++) {
            if (this.publishableCampaignTypes[i].id == campaignTypeId) {
                campaignType = this.publishableCampaignTypes[i];
                break;
            }
        }

        if (!campaignType) {
            return;
        }

        this.$newCampaignBtn.addClass('inactive');
        var newCampaignBtnText = this.$newCampaignBtn.text();
        this.$newCampaignBtn.text(Craft.t('campaign', 'New {campaignType} campaign', {campaignType: campaignType.name}));

        Craft.createElementEditor(this.elementType, {
            siteId: this.siteId,
            attributes: {
                campaignTypeId: campaignTypeId
            },
            onHideHud: () => {
                this.$newCategoryBtn.removeClass('inactive').text(newCategoryBtnText);
            },
            onSaveElement: response => {
                // Make sure the right campaign type is selected
                var campaignTypeSourceKey = 'campaignType:' + campaignType.uid;

                if (this.sourceKey != campaignTypeSourceKey) {
                    this.selectSourceByKey(campaignTypeSourceKey);
                }

                this.selectElementAfterUpdate(response.id);
                this.updateElements();
            },
        });
    }
});

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\CampaignElement', Campaign.CampaignIndex);
