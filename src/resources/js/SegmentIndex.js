/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SegmentIndex class
 */
Campaign.SegmentIndex = Craft.BaseElementIndex.extend({
    $newSegmentBtn: null,

    afterInit: function() {
        if (Craft.canEditSegments) {
            this.createButton();
        }

        this.base();
    },

    createButton: function() {
        this.$newSegmentBtn = Craft.ui.createButton({
                label: Craft.t('campaign', 'New segment'),
                spinner: true,
            })
            .addClass('submit add icon');

        this.addListener(this.$newSegmentBtn, 'click', () => {
            this._createSegment();
        });

        this.addButton(this.$newSegmentBtn);
    },

    _createSegment: function() {
        if (this.$newSegmentBtn.hasClass('loading')) {
            console.warn('New segment creation already in progress.');
            return;
        }

        this.$newSegmentBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: this.elementType,
                siteId: this.siteId,
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
