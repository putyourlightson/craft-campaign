/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SegmentIndex class
 */
Campaign.SegmentIndex = Craft.BaseElementIndex.extend({
    editableSegmentTypes: null,
    $newSegmentBtnGroup: null,
    $newSegmentBtn: null,

    init: function(elementType, $container, settings) {
        this.editableSegmentTypes = [];
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Find which of the visible segment types the user has permission to create new segments in
        this.editableSegmentTypes = Craft.editableSegmentTypes.filter(g => !!this.getSourceByKey(`segmentType:${g.handle}`));

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific segment type in the URL?
        if (this.settings.context === 'index' && typeof defaultSegmentTypeHandle !== 'undefined') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                if ($source.data('handle') === defaultSegmentTypeHandle) {
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

        // Update the New segment button
        // ---------------------------------------------------------------------

        if (this.editableSegmentTypes.length) {
            // Remove the old button, if there is one
            if (this.$newSegmentBtnGroup) {
                this.$newSegmentBtnGroup.remove();
            }

            // Determine if they are viewing a segment type that they have permission to create segments in
            const selectedSegmentType = this.editableSegmentTypes.find(g => g.handle === selectedSourceHandle);

            this.$newSegmentBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
            let $menuBtn;
            const menuId = 'new-campaign-menu-' + Craft.randomString(10);

            // If they are, show a primary "New segment" button, and a dropdown of the other segment types (if any).
            // Otherwise only show a menu button
            if (selectedSegmentType) {
                this.$newSegmentBtn = Craft.ui.createButton({
                        label: this.settings.context === 'index'
                            ? Craft.t('campaign', 'New segment')
                            : Craft.t('campaign', 'New {segmentType} segment', {
                                segmentType: selectedSegmentType.name,
                            }),
                        spinner: true,
                    })
                    .addClass('submit add icon')
                    .appendTo(this.$newSegmentBtnGroup);

                this.addListener(this.$newSegmentBtn, 'click', () => {
                    this._createSegment(selectedSegmentType.handle);
                });

                if (this.editableSegmentTypes.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn btngroup-btn-last',
                        'aria-controls': menuId,
                        'data-disclosure-trigger': '',
                    }).appendTo(this.$newSegmentBtnGroup);
                }
            }
            else {
                this.$newSegmentBtn = $menuBtn = Craft.ui.createButton({
                        label: Craft.t('campaign', 'New segment'),
                        spinner: true,
                    })
                    .addClass('submit add icon menubtn btngroup-btn-last')
                    .attr('aria-controls', menuId)
                    .attr('data-disclosure-trigger', '')
                    .appendTo(this.$newSegmentBtnGroup);
            }

            this.addButton(this.$newSegmentBtnGroup);

            if ($menuBtn) {
                const $menuContainer = $('<div/>', {
                    id: menuId,
                    class: 'menu menu--disclosure',
                }).appendTo(this.$newSegmentBtnGroup);
                const $ul = $('<ul/>').appendTo($menuContainer);

                for (const segmentType of this.editableSegmentTypes) {
                    if (
                        (this.settings.context === 'index' || segmentType !== selectedSegmentType)
                    ) {
                        const $li = $('<li/>').appendTo($ul);
                        const $a = $('<a/>', {
                            role: 'button',
                            tabindex: '0',
                            text: Craft.t('campaign', 'New {segmentType} segment', {
                                segmentType: segmentType.name,
                            }),
                        }).appendTo($li);
                        this.addListener($a, 'click', () => {
                            $menuBtn.data('trigger').hide();
                            this._createSegment(segmentType.id);
                        });
                    }
                }

                new Garnish.DisclosureMenu($menuBtn);
            }
        }

        // Update the URL if we're on the Segments index
        // ---------------------------------------------------------------------

        if (this.settings.context == 'index' && typeof history !== 'undefined') {
            let uri = 'campaign/segments';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            const url = Craft.getUrl(uri, document.location.search + document.location.hash);
            history.replaceState({}, '', url);
        }
    },

    _createSegment: function(segmentTypeHandle) {
        if (this.$newSegmentBtn.hasClass('loading')) {
            console.warn('New segment creation already in progress.');
            return;
        }

        // Find the segmentType
        const segmentType = this.editableSegmentTypes.find(s => s.handle === segmentTypeHandle);

        if (!segmentType) {
            throw `Invalid segment type: ${segmentTypeHandle}`;
        }

        this.$newSegmentBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: this.elementType,
                siteId: this.siteId,
                segmentType: segmentTypeHandle,
            },
        }).then(ev => {
            if (this.settings.context === 'index') {
                document.location.href = Craft.getUrl(ev.data.cpEditUrl, {fresh: 1});
            } else {
                const slideout = Craft.createElementEditor(this.elementType, {
                    siteId: this.siteId,
                    elementId: ev.data.element.id,
                    draftId: ev.data.element.draftId,
                    params: {
                        fresh: 1,
                    },
                });
                slideout.on('submit', () => {
                    // Make sure the right segmentType is selected
                    const segmentTypeSourceKey = `segmentType:${segmentType.uid}`;

                    if (this.sourceKey !== segmentTypeSourceKey) {
                        this.selectSourceByKey(segmentTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(ev.data.element.id);
                    this.updateElements();
                });
            }
        }).finally(() => {
            this.$newSegmentBtn.removeClass('loading');
        });
    },
});

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\SegmentElement', Campaign.SegmentIndex);
