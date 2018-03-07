/** global: Campaign */
/** global: Garnish */
/**
 * SendoutIndex class
 */
Campaign.SendoutIndex = Craft.BaseElementIndex.extend(
    {
        publishableSendoutTypes: null,
        $newSendoutBtnGroup: null,
        $newSendoutBtn: null,

        afterInit: function() {
            // Get publishable sendout types
            this.publishableSendoutTypes = [];

            for (var i = 0; i < Craft.publishableSendoutTypes.length; i++) {
                var sendoutType = Craft.publishableSendoutTypes[i];

                if (this.getSourceByKey('sendoutTypeId:' + sendoutType.id)) {
                    this.publishableSendoutTypes.push(sendoutType);
                }
            }

            this.base();
        },

        getDefaultSourceKey: function() {
            // Did they request a specific sendout type in the URL?
            if (this.settings.context == 'index' && typeof defaultSendoutTypeHandle !== 'undefined') {
                for (var i = 0; i < this.$sources.length; i++) {
                    var $source = $(this.$sources[i]);

                    if ($source.data('handle') == defaultSendoutTypeHandle) {
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

            // Update the New sendout button
            // ---------------------------------------------------------------------

            if (this.publishableSendoutTypes.length) {
                // Remove the old button, if there is one
                if (this.$newSendoutBtnGroup) {
                    this.$newSendoutBtnGroup.remove();
                }

                // Determine if they are viewing a sendout type
                var selectedSendoutType;

                if (handle) {
                    for (var i = 0; i < this.publishableSendoutTypes.length; i++) {
                        if (this.publishableSendoutTypes[i].handle == handle) {
                            selectedSendoutType = this.publishableSendoutTypes[i];
                            break;
                        }
                    }
                }

                this.$newSendoutBtnGroup = $('<div class="btngroup submit"/>');
                var $menuBtn;

                // If they are, show a primary "New sendout" button, and a dropdown of the other sendout types (if any).
                // Otherwise only show a menu button
                if (selectedSendoutType) {
                    var href = this._getSendoutTypeTriggerHref(selectedSendoutType),
                        label = (this.settings.context == 'index' ? Craft.t('campaign', 'New sendout') : Craft.t('campaign', 'New {sendoutType} sendout', {sendoutType: selectedSendoutType.name}));
                    this.$newSendoutBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newSendoutBtnGroup);

                    if (this.settings.context != 'index') {
                        this.addListener(this.$newSendoutBtn, 'click', function(ev) {
                            this._openCreateSendoutModal(ev.currentTarget.getAttribute('data-id'));
                        });
                    }

                    if (this.publishableSendoutTypes.length > 1) {
                        $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newSendoutBtnGroup);
                    }
                }
                else {
                    this.$newSendoutBtn = $menuBtn = $('<div class="btn submit add icon menubtn">' + Craft.t('campaign', 'New sendout') + '</div>').appendTo(this.$newSendoutBtnGroup);
                }

                if ($menuBtn) {
                    var menuHtml = '<div class="menu"><ul>';

                    for (var i = 0; i < this.publishableSendoutTypes.length; i++) {
                        var sendoutType = this.publishableSendoutTypes[i];

                        if (this.settings.context == 'index' || sendoutType != selectedSendoutType) {
                            var href = this._getSendoutTypeTriggerHref(sendoutType),
                                label = (this.settings.context == 'index' ? sendoutType.name : Craft.t('campaign', 'New {sendoutType} sendout', {sendoutType: sendoutType.name}));
                            menuHtml += '<li><a ' + href + '">' + Craft.escapeHtml(label) + '</a></li>';
                        }
                    }

                    menuHtml += '</ul></div>';

                    var $menu = $(menuHtml).appendTo(this.$newSendoutBtnGroup),
                        menuBtn = new Garnish.MenuBtn($menuBtn);

                    if (this.settings.context != 'index') {
                        menuBtn.on('optionSelect', $.proxy(function(ev) {
                            this._openCreateSendoutModal(ev.option.getAttribute('data-id'));
                        }, this));
                    }
                }

                this.addButton(this.$newSendoutBtnGroup);
            }

            // Update the URL if we're on the Sendouts index
            // ---------------------------------------------------------------------

            if (this.settings.context == 'index' && typeof history !== 'undefined') {
                var uri = 'campaign/sendouts';

                if (handle) {
                    uri += '/' + handle;
                }

                history.replaceState({}, '', Craft.getUrl(uri));
            }

            this.base();
        },

        _getSendoutTypeTriggerHref: function(sendoutType) {
            if (this.settings.context == 'index') {
                return 'href="' + Craft.getUrl('campaign/sendouts/' + sendoutType.handle + '/new') + '"';
            }
            else {
                return 'data-id="' + sendoutType.id + '"';
            }
        },

        _openCreateSendoutModal: function(sendoutTypeId) {
            if (this.$newSendoutBtn.hasClass('loading')) {
                return;
            }

            // Find the sendout type
            var sendoutType;

            for (var i = 0; i < this.publishableSendoutTypes.length; i++) {
                if (this.publishableSendoutTypes[i].id == sendoutTypeId) {
                    sendoutType = this.publishableSendoutTypes[i];
                    break;
                }
            }

            if (!sendoutType) {
                return;
            }

            this.$newSendoutBtn.addClass('inactive');
            var newSendoutBtnText = this.$newSendoutBtn.text();
            this.$newSendoutBtn.text(Craft.t('campaign', 'New {sendoutType} sendout', {sendoutType: sendoutType.name}));

            Craft.createElementEditor(this.elementType, {
                hudTrigger: this.$newSendoutBtnGroup,
                elementType: 'campaign\\elements\\SendoutElement',
                attributes: {
                    sendoutTypeId: sendoutTypeId
                },
                onBeginLoading: $.proxy(function() {
                    this.$newSendoutBtn.addClass('loading');
                }, this),
                onEndLoading: $.proxy(function() {
                    this.$newSendoutBtn.removeClass('loading');
                }, this),
                onHideHud: $.proxy(function() {
                    this.$newSendoutBtn.removeClass('inactive').text(newSendoutBtnText);
                }, this),
                onSaveElement: $.proxy(function(response) {
                    // Make sure the right sendout type is selected
                    var sendoutTypeSourceKey = 'sendoutType:' + sendoutTypeId;

                    if (this.sourceKey != sendoutTypeSourceKey) {
                        this.selectSourceByKey(sendoutTypeSourceKey);
                    }

                    this.selectElementAfterUpdate(response.id);
                    this.updateElements();
                }, this)
            });
        }
    });

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\SendoutElement', Campaign.SendoutIndex);
