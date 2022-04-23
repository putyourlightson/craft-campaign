/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SendoutIndex class
 */
Campaign.SendoutIndex = Craft.BaseElementIndex.extend({
    editableSendoutTypes: null,
    $newSendoutBtnGroup: null,
    $newSendoutBtn: null,

    init: function(elementType, $container, settings) {
        this.editableSendoutTypes = [];
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Find which of the visible sendout types the user has permission to create new sendouts in
        this.editableSendoutTypes = Craft.editableSendoutTypes.filter(
            sendoutType => !!this.getSourceByKey(`sendoutType:${sendoutType.handle}`)
        );

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific sendout type in the URL?
        if (this.settings.context === 'index' && typeof defaultSendoutTypeHandle !== 'undefined') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                if ($source.data('handle') === defaultSendoutTypeHandle) {
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

        // Update the New sendout button
        // ---------------------------------------------------------------------

        // Remove the old button, if there is one
        if (this.$newSendoutBtnGroup) {
            this.$newSendoutBtnGroup.remove();
        }

        if (this.editableSendoutTypes.length) {
            // Determine if they are viewing a sendout type that they have permission to create sendouts in
            const selectedSendoutType = this.editableSendoutTypes.find(
                sendoutType => sendoutType.handle === selectedSourceHandle
            );

            this.$newSendoutBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
            let $menuBtn;
            const menuId = 'new-campaign-menu-' + Craft.randomString(10);

            // If they are, show a primary "New sendout" button, and a dropdown of the other sendout types (if any).
            // Otherwise only show a menu button
            if (selectedSendoutType) {
                this.$newSendoutBtn = Craft.ui.createButton({
                        label: this.settings.context === 'index'
                            ? Craft.t('campaign', 'New sendout')
                            : Craft.t('campaign', 'New {sendoutType} sendout', {
                                sendoutType: selectedSendoutType.name,
                            }),
                        spinner: true,
                    })
                    .addClass('submit add icon')
                    .appendTo(this.$newSendoutBtnGroup);

                this.addListener(this.$newSendoutBtn, 'click', () => {
                    this._createSendout(selectedSendoutType.handle);
                });

                if (this.editableSendoutTypes.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn btngroup-btn-last',
                        'aria-controls': menuId,
                        'data-disclosure-trigger': '',
                    }).appendTo(this.$newSendoutBtnGroup);
                }
            }
            else {
                this.$newSendoutBtn = $menuBtn = Craft.ui.createButton({
                        label: Craft.t('campaign', 'New sendout'),
                        spinner: true,
                    })
                    .addClass('submit add icon menubtn btngroup-btn-last')
                    .attr('aria-controls', menuId)
                    .attr('data-disclosure-trigger', '')
                    .appendTo(this.$newSendoutBtnGroup);
            }

            this.addButton(this.$newSendoutBtnGroup);

            if ($menuBtn) {
                const $menuContainer = $('<div/>', {
                    id: menuId,
                    class: 'menu menu--disclosure',
                }).appendTo(this.$newSendoutBtnGroup);
                const $ul = $('<ul/>').appendTo($menuContainer);

                for (const sendoutType of this.editableSendoutTypes) {
                    if (this.settings.context === 'index' || sendoutType !== selectedSendoutType) {
                        const $li = $('<li/>').appendTo($ul);
                        const $a = $('<a/>', {
                            role: 'button',
                            tabindex: '0',
                            text: Craft.t('campaign', 'New {sendoutType} sendout', {
                                sendoutType: sendoutType.name,
                            }),
                        }).appendTo($li);
                        this.addListener($a, 'click', () => {
                            $menuBtn.data('trigger').hide();
                            this._createSendout(sendoutType.handle);
                        });
                    }
                }

                new Garnish.DisclosureMenu($menuBtn);
            }
        }

        // Update the URL if we're on the Sendouts index
        // ---------------------------------------------------------------------

        if (this.settings.context == 'index' && typeof history !== 'undefined') {
            let uri = 'campaign/sendouts';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            const url = Craft.getUrl(uri, document.location.search + document.location.hash);
            history.replaceState({}, '', url);
        }
    },

    _createSendout: function(sendoutTypeHandle) {
        if (this.$newSendoutBtn.hasClass('loading')) {
            console.warn('New sendout creation already in progress.');
            return;
        }

        // Find the sendout type
        const sendoutType = this.editableSendoutTypes.find(
            sendoutType => sendoutType.handle === sendoutTypeHandle
        );

        if (!sendoutType) {
            throw `Invalid sendout type: ${sendoutTypeHandle}`;
        }

        this.$newSendoutBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: this.elementType,
                siteId: this.siteId,
                sendoutType: sendoutTypeHandle,
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
                    // Make sure the right sendoutType is selected
                    const sendoutTypeSourceKey = `sendoutType:${sendoutType.handle}`;

                    if (this.sourceKey !== sendoutTypeSourceKey) {
                        this.selectSourceByKey(sendoutTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(ev.data.element.id);
                    this.updateElements();
                });
            }
        }).finally(() => {
            this.$newSendoutBtn.removeClass('loading');
        });
    },
});

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\SendoutElement', Campaign.SendoutIndex);
