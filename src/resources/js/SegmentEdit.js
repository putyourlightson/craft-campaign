/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SegmentEdit class
 */
Campaign.SegmentEdit = Garnish.Base.extend({
    init: function() {
        this.addListeners($('.segment-conditions'));
        this.refreshConditions();
    },

    addListeners: function(element) {
        const $element = $(element);
        this.addListener($element.find('.addAndCondition'), 'click', 'addAndCondition');
        this.addListener($element.find('.addOrCondition'), 'click', 'addOrCondition');
        this.addListener($element.find('.deleteAndCondition'), 'click', 'deleteAndCondition');
        this.addListener($element.find('.deleteOrCondition'), 'click', 'deleteOrCondition');
        this.addListener($element.find('.conditionField'), 'change', 'refreshConditions');
    },

    addAndCondition: function(event) {
        const $this = $(event.target);
        const $newAndCondition = $('.andCondition[data-new="1"]').clone();
        $newAndCondition.attr('data-new', '').removeClass('hidden');
        $this.closest('.andCondition').after($newAndCondition);

        this.addListeners($newAndCondition);
        this.refreshConditions();
    },

    addOrCondition: function(event) {
        const $this = $(event.target);
        const $newOrCondition = $('.andCondition[data-new="1"] .orCondition').clone();
        $newOrCondition.attr('data-new', '');
        $this.closest('.orCondition').after($newOrCondition);

        this.addListeners($newOrCondition);
        this.refreshConditions();
    },

    deleteOrCondition: function(event) {
        const $this = $(event.target);

        if ($this.hasClass('disabled')) {
            return false;
        }

        if ($this.closest('.andCondition').find('.orCondition').length === 1) {
            $this.closest('.andCondition').remove();
        }
        else {
            $this.closest('.orCondition').remove();
        }

        this.refreshConditions();
    },

    refreshConditions: function() {
        $('.andCondition:not([data-new="1"])').each(function(andIndex) {
            $(this).find('.orCondition').each(function(orIndex) {
                $(this).find('select, input').each(function() {
                    const name = $(this).attr('name').replace(
                        /conditions(\]?)\[(.*?)]\[(.*?)]/,
                        'conditions$1[' + andIndex + '][' + orIndex + ']'
                    );
                    $(this).attr('name', name);
                    $(this).removeClass('hidden').prop('disabled', false);
                });

                const selectedOption = $(this).find('.conditionField option:selected');
                const field = $(selectedOption).attr('data-field');
                const unique = $(selectedOption).attr('data-unique');
                $(this).closest('.orCondition').find('.conditionOperator').addClass('hidden').prop('disabled', true);
                $(this).closest('.orCondition').find('.conditionOperator.' + field).removeClass('hidden').prop('disabled', false);
                $(this).closest('.orCondition').find('.conditionValue').addClass('hidden').find('input, select').prop('disabled', true);
                $(this).closest('.orCondition').find('.conditionValue.' + field).removeClass('hidden').find('input, select').prop('disabled', false);
                $(this).closest('.orCondition').find('.conditionValue.' + field + '-' + unique).removeClass('hidden').find('input, select').prop('disabled', false);

                if ($(this).closest('.orCondition').find('.conditionValue:visible').length === 0) {
                    $(this).closest('.orCondition').find('.conditionValue.default').removeClass('hidden').find('input, select').prop('disabled', false);
                }
            });
        });

        $('.conditions .conditionValue.field-craft-fields-Date input:not(.hasDatepicker)').datepicker($.extend({
            defaultDate: new Date()
        }, Craft.datepickerOptions));

        $('.deleteOrCondition').removeClass('disabled');

        if ($('.andCondition:not(.hidden) .orCondition').length === 1) {
            $('.deleteOrCondition').addClass('disabled');
        }
    },
});

new Campaign.SegmentEdit();
