/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * ContactIndex class
 */
Campaign.ContactIndex = Craft.BaseElementIndex.extend({
    $newContactBtnGroup: null,

    init: function(elementType, $container, settings) {
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },

    updateButton: function() {
        // Remove the old button, if there is one
        if (this.$newContactBtn) {
            this.$newContactBtn.remove();
        }

        this.$newContactBtn = $menuBtn = Craft.ui.createButton({
                label: Craft.t('campaign', 'New contact'),
                spinner: true,
            })
            .addClass('submit add icon btngroup-btn-last');

        this.addListener(this.$newContactBtn, 'click', () => {
            this._createContact();
        });

        this.addButton(this.$newContactBtn);
    },

    _createContact: function() {
        if (this.$newContactBtn.hasClass('loading')) {
            console.warn('New contact creation already in progress.');
            return;
        }

        this.$newContactBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: this.elementType,
                siteId: this.siteId,
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
                    this.selectElementAfterUpdate(ev.data.element.id);
                    this.updateElements();
                });
            }
        }).finally(() => {
            this.$newContactBtn.removeClass('loading');
        });
    },
});

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\ContactElement', Campaign.ContactIndex);
