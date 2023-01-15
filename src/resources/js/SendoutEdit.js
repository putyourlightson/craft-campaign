/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SendoutEdit class
 */
Campaign.SendoutEdit = Garnish.Base.extend({
    containerId: null,

    init: function(containerId) {
        this.containerId = containerId;
        this.initElementSelectListener();
    },

    initElementSelectListener: function() {
        const $container = $('#' + this.containerId);
        const elementSelect = $container.find('.elementselect').first().data('elementSelect');
        elementSelect.on('selectElements', function(event) {
            const campaignTitle = event.target.$elements.first().text();
            const $inputFields = $container.find('.title-field, .subject-field');
            $inputFields.each(function() {
                if ($(this).val() === '') {
                    $(this).val(campaignTitle);
                }
            });
        });
    }
});
