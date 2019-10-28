/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SegmentIndex class
 */
Campaign.SegmentIndex = Craft.BaseElementIndex.extend(
    {
        publishableSegmentTypes: null,
        $newSegmentBtnGroup: null,
        $newSegmentBtn: null,

        init: function(elementType, $container, settings) {
            this.on('selectSource', $.proxy(this, 'updateButton'));
            this.on('selectSite', $.proxy(this, 'updateButton'));
            this.base(elementType, $container, settings);
        },

        afterInit: function() {
            // Get publishable segment types
            this.publishableSegmentTypes = [];

            for (var i = 0; i < Craft.publishableSegmentTypes.length; i++) {
                var segmentType = Craft.publishableSegmentTypes[i];

                if (this.getSourceByKey('segmentTypeId:' + segmentType.id)) {
                    this.publishableSegmentTypes.push(segmentType);
                }
            }

            this.base();
        },

        getDefaultSourceKey: function() {
            // Did they request a specific segment type in the URL?
            if (this.settings.context == 'index' && typeof defaultSegmentTypeHandle !== 'undefined') {
                for (var i = 0; i < this.$sources.length; i++) {
                    var $source = $(this.$sources[i]);

                    if ($source.data('handle') == defaultSegmentTypeHandle) {
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

            // Update the New segment button
            // ---------------------------------------------------------------------

            if (this.publishableSegmentTypes.length) {
                // Remove the old button, if there is one
                if (this.$newSegmentBtnGroup) {
                    this.$newSegmentBtnGroup.remove();
                }

                // Determine if they are viewing a segment type
                var selectedSegmentType;

                if (handle) {
                    for (var i = 0; i < this.publishableSegmentTypes.length; i++) {
                        if (this.publishableSegmentTypes[i].handle == handle) {
                            selectedSegmentType = this.publishableSegmentTypes[i];
                            break;
                        }
                    }
                }

                this.$newSegmentBtnGroup = $('<div class="btngroup submit"/>');
                var $menuBtn;

                // If they are, show a primary "New segment" button, and a dropdown of the other segment types (if any).
                // Otherwise only show a menu button
                if (selectedSegmentType) {
                    var href = this._getSegmentTypeTriggerHref(selectedSegmentType),
                        label = (this.settings.context == 'index' ? Craft.t('campaign', 'New segment') : Craft.t('campaign', 'New {segmentType} segment', {segmentType: selectedSegmentType.name}));
                    this.$newSegmentBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newSegmentBtnGroup);

                    if (this.settings.context != 'index') {
                        this.addListener(this.$newSegmentBtn, 'click', function(ev) {
                            this._openCreateSegmentModal(ev.currentTarget.getAttribute('data-id'));
                        });
                    }

                    if (this.publishableSegmentTypes.length > 1) {
                        $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newSegmentBtnGroup);
                    }
                }
                else {
                    this.$newSegmentBtn = $menuBtn = $('<div class="btn submit add icon menubtn">' + Craft.t('campaign', 'New segment') + '</div>').appendTo(this.$newSegmentBtnGroup);
                }

                if ($menuBtn) {
                    var menuHtml = '<div class="menu"><ul>';

                    for (var i = 0; i < this.publishableSegmentTypes.length; i++) {
                        var segmentType = this.publishableSegmentTypes[i];

                        if (this.settings.context == 'index' || segmentType != selectedSegmentType) {
                            var href = this._getSegmentTypeTriggerHref(segmentType),
                                label = (this.settings.context == 'index' ? segmentType.name : Craft.t('campaign', 'New {segmentType} segment', {segmentType: segmentType.name}));
                            menuHtml += '<li><a ' + href + '">' + Craft.escapeHtml(label) + '</a></li>';
                        }
                    }

                    menuHtml += '</ul></div>';

                    var $menu = $(menuHtml).appendTo(this.$newSegmentBtnGroup),
                        menuBtn = new Garnish.MenuBtn($menuBtn);

                    if (this.settings.context != 'index') {
                        menuBtn.on('optionSelect', $.proxy(function(ev) {
                            this._openCreateSegmentModal(ev.option.getAttribute('data-id'));
                        }, this));
                    }
                }

                this.addButton(this.$newSegmentBtnGroup);
            }

            // Update the URL if we're on the Segments index
            // ---------------------------------------------------------------------

            if (this.settings.context == 'index' && typeof history !== 'undefined') {
                var uri = 'campaign/segments';

                if (handle) {
                    uri += '/' + handle;
                }

                history.replaceState({}, '', Craft.getUrl(uri));
            }
        },

        _getSegmentTypeTriggerHref: function(segmentType) {
            if (this.settings.context == 'index') {
                var uri = 'campaign/segments/' + segmentType.handle + '/new';
                if (this.siteId && this.siteId != Craft.siteId) {
                    for (var i = 0; i < Craft.sites.length; i++) {
                        if (Craft.sites[i].id == this.siteId) {
                            uri += '/' + Craft.sites[i].handle;
                        }
                    }
                }
                return 'href="' + Craft.getUrl(uri) + '"';
            }
            else {
                return 'data-id="' + segmentType.id + '"';
            }
        },

        _openCreateSegmentModal: function(segmentTypeId) {
            if (this.$newSegmentBtn.hasClass('loading')) {
                return;
            }

            // Find the segment type
            var segmentType;

            for (var i = 0; i < this.publishableSegmentTypes.length; i++) {
                if (this.publishableSegmentTypes[i].id == segmentTypeId) {
                    segmentType = this.publishableSegmentTypes[i];
                    break;
                }
            }

            if (!segmentType) {
                return;
            }

            this.$newSegmentBtn.addClass('inactive');
            var newSegmentBtnText = this.$newSegmentBtn.text();
            this.$newSegmentBtn.text(Craft.t('campaign', 'New {segmentType} segment', {segmentType: segmentType.name}));

            Craft.createElementEditor(this.elementType, {
                hudTrigger: this.$newSegmentBtnGroup,
                elementType: 'putyourlightson\\campaign\\elements\\SegmentElement',
                attributes: {
                    segmentType: segmentType.handle
                },
                onBeginLoading: $.proxy(function() {
                    this.$newSegmentBtn.addClass('loading');
                }, this),
                onEndLoading: $.proxy(function() {
                    this.$newSegmentBtn.removeClass('loading');
                }, this),
                onHideHud: $.proxy(function() {
                    this.$newSegmentBtn.removeClass('inactive').text(newSegmentBtnText);
                }, this),
                onSaveElement: $.proxy(function(response) {
                    // Make sure the right segment type is selected
                    var segmentTypeSourceKey = 'segmentType:' + segmentType;

                    if (this.sourceKey != segmentTypeSourceKey) {
                        this.selectSourceByKey(segmentTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(response.id);
                    this.updateElements();
                }, this)
            });
        }
    });

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\SegmentElement', Campaign.SegmentIndex);
