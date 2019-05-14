/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * SegmentEdit class
 */
Campaign.SegmentEdit = Garnish.Base.extend(
    {
        init: function() {
            this.addListeners($('#fields'));

            this.refreshConditions();
        },

        addListeners: function(elem) {
            var $elem = $(elem);
            this.addListener($elem.find('.addAndCondition'), 'click', 'addAndCondition');
            this.addListener($elem.find('.addOrCondition'), 'click', 'addOrCondition');
            this.addListener($elem.find('.deleteAndCondition'), 'click', 'deleteAndCondition');
            this.addListener($elem.find('.deleteOrCondition'), 'click', 'deleteOrCondition');
            this.addListener($elem.find('.conditionField'), 'change', 'refreshConditions');
        },

        addAndCondition: function(event) {
            var $this = $(event.target);
            var $newAndCondition = $('#newCondition').clone();
            $newAndCondition.attr('id', '').removeClass('hidden');
            $this.closest('.andCondition').after($newAndCondition);

            this.addListeners($newAndCondition);
            this.refreshConditions();
        },

        addOrCondition: function(event) {
            var $this = $(event.target);
            var $newOrCondition = $('#newCondition .orCondition').clone();
            $newOrCondition.attr('id', '');
            $this.closest('.orCondition').after($newOrCondition);

            this.addListeners($newOrCondition);
            this.refreshConditions();
        },

        deleteOrCondition: function(event) {
            var $this = $(event.target);

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
            $('.andCondition:not(#newCondition)').each(function(andIndex) {
                $(this).find('.orCondition').each(function(orIndex) {
                    $(this).find('select, input').each(function() {
                        var name = $(this).attr('name').replace(/conditions\[(.*?)]\[(.*?)]/, 'conditions[' + andIndex + '][' + orIndex + ']');
                        $(this).attr('name', name);
                        $(this).removeClass('hidden').prop('disabled', false);
                    });

                    var selectedOption = $(this).find('.conditionField option:selected');
                    var field = $(selectedOption).attr('data-field');
                    var unique = $(selectedOption).attr('data-unique');
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
    }
);

new Campaign.SegmentEdit();
