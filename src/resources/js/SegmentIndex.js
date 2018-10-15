/** global: Campaign */
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

        updateButton: function() {
            // Remove the old button, if there is one
            if (this.$newSegmentBtnGroup) {
                this.$newSegmentBtnGroup.remove();
            }

            this.$newSegmentBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            var href = this._getTriggerHref(),
            label = Craft.t('campaign', 'New segment');
            this.$newSegmentBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newSegmentBtnGroup);

            if (this.settings.context == 'index') {
                this.addButton(this.$newSegmentBtnGroup);
            }
        },

        _getTriggerHref: function(segmentType) {
            if (this.settings.context == 'index') {
                var uri = 'campaign/segments/new';
                if (this.siteId && this.siteId != Craft.siteId) {
                    for (var i = 0; i < Craft.sites.length; i++) {
                        if (Craft.sites[i].id == this.siteId) {
                            uri += '/' + Craft.sites[i].handle;
                        }
                    }
                }
                return 'href="' + Craft.getUrl(uri) + '"';
            }
        },

    });

// Register it!
Craft.registerElementIndexClass('putyourlightson\\campaign\\elements\\SegmentElement', Campaign.SegmentIndex);
