/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * Report class
 */
Campaign.Report = Garnish.Base.extend({
    init: function() {
        this.loadElementThumbs();
        this.addListener($.find('.filter'), 'change', 'applyFilter');
    },

    loadElementThumbs: function() {
        const elements = $('.elementThumb');

        if (elements.length) {
            (new Craft.ElementThumbLoader()).load(elements);
        }
    },

    applyFilter: function(event) {
        event.preventDefault();

        const $this = $(event.target);
        const baseUrl = window.location.href.split('?')[0];
        const filterType = $this.attr('data-type');
        window.location.href = $this.val() ? baseUrl + '?' + filterType + '=' + $this.val() : baseUrl;
    },
});

new Campaign.Report();
