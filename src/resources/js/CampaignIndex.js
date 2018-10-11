/** global: Campaign */
/** global: Garnish */
/**
 * CampaignIndex class
 */
Campaign.CampaignIndex = Craft.BaseElementIndex.extend(
    {
        publishableCampaignTypes: null,
        $newCampaignBtnGroup: null,
        $newCampaignBtn: null,

        init: function(elementType, $container, settings) {
            this.on('selectSource', $.proxy(this, 'updateButton'));
            this.on('selectSite', $.proxy(this, 'updateButton'));
            this.base(elementType, $container, settings);
        },

        afterInit: function() {
            // Get publishable campaign types
            this.publishableCampaignTypes = [];

            for (var i = 0; i < Craft.publishableCampaignTypes.length; i++) {
                var campaignType = Craft.publishableCampaignTypes[i];

                if (this.getSourceByKey('campaignType:' + campaignType.id)) {
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

            var handle;

            // Get the handle of the selected source
            handle = this.$source.data('handle');

            // Update the New campaign button
            // ---------------------------------------------------------------------

            if (this.publishableCampaignTypes.length) {
                // Remove the old button, if there is one
                if (this.$newCampaignBtnGroup) {
                    this.$newCampaignBtnGroup.remove();
                }

                // Determine if they are viewing a campaign type
                var selectedCampaignType;

                if (handle) {
                    for (var i = 0; i < this.publishableCampaignTypes.length; i++) {
                        if (this.publishableCampaignTypes[i].handle == handle) {
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
                            var href = this._getCampaignTypeTriggerHref(campaignType),
                                label = (this.settings.context == 'index' ? campaignType.name : Craft.t('campaign', 'New {campaignType} campaign', {campaignType: campaignType.name}));
                            menuHtml += '<li><a ' + href + '">' + Craft.escapeHtml(label) + '</a></li>';
                        }
                    }

                    menuHtml += '</ul></div>';

                    var $menu = $(menuHtml).appendTo(this.$newCampaignBtnGroup),
                        menuBtn = new Garnish.MenuBtn($menuBtn);

                    if (this.settings.context != 'index') {
                        menuBtn.on('optionSelect', $.proxy(function(ev) {
                            this._openCreateCampaignModal(ev.option.getAttribute('data-id'));
                        }, this));
                    }
                }

                this.addButton(this.$newCampaignBtnGroup);
            }

            // Update the URL if we're on the Campaigns index
            // ---------------------------------------------------------------------

            if (this.settings.context == 'index' && typeof history !== 'undefined') {
                var uri = 'campaign/campaigns';

                if (handle) {
                    uri += '/' + handle;
                }

                history.replaceState({}, '', Craft.getUrl(uri));
            }
        },

        _getCampaignTypeTriggerHref: function(campaignType) {
            if (this.settings.context == 'index') {
                return 'href="' + Craft.getUrl('campaign/campaigns/' + campaignType.handle + '/new') + '"';
            }
            else {
                return 'data-id="' + campaignType.id + '"';
            }
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
                hudTrigger: this.$newCampaignBtnGroup,
                elementType: 'putyourlightson\\campaign\\elements\\CampaignElement',
                attributes: {
                    campaignTypeId: campaignTypeId
                },
                onBeginLoading: $.proxy(function() {
                    this.$newCampaignBtn.addClass('loading');
                }, this),
                onEndLoading: $.proxy(function() {
                    this.$newCampaignBtn.removeClass('loading');
                }, this),
                onHideHud: $.proxy(function() {
                    this.$newCampaignBtn.removeClass('inactive').text(newCampaignBtnText);
                }, this),
                onSaveElement: $.proxy(function(response) {
                    // Make sure the right campaign type is selected
                    var campaignTypeSourceKey = 'campaignType:' + campaignTypeId;

                    if (this.sourceKey != campaignTypeSourceKey) {
                        this.selectSourceByKey(campaignTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(response.id);
                    this.updateElements();
                }, this)
            });
        }
    });

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\CampaignElement', Campaign.CampaignIndex);
