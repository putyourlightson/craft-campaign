/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * CP class
 */
Campaign.CP = Garnish.Base.extend({
    init: function() {
        this.loadElementThumbs();
        this.addListener($.find('.filter'), 'change', 'applyFilter');
    },

    loadElementThumbs: function() {
        var elements = $('.elementThumb');

        if (elements.length) {
            (new Craft.ElementThumbLoader()).load(elements);
        }
    },

    applyFilter: function(event) {
        event.preventDefault();

        var $this = $(event.target);
        var baseUrl = window.location.href.split('?')[0];
        var filterType = $this.attr('data-type');
        window.location.href = $this.val() ? baseUrl + '?' + filterType + '=' + $this.val() : baseUrl;
    },
});

new Campaign.CP();
