/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * DataTable class
 */
Campaign.DataTable = Garnish.Base.extend({
    init: function(settings) {
        this.setSettings(settings);

        this.createDataTable();
    },

    createDataTable: function() {
        const id = this.settings.id;
        const placeholder = this.settings.placeholder ?? '';
        const options = this.settings.options ?? {};

        const dataTable = $('#' + id).DataTable(options);
        dataTable.on('draw', () => Craft.initUiElements());

        $('#' + id + '_wrapper select').wrap('<div class="select"></div>');

        $('#' + id + '_filter input').addClass('text fullwidth')
            .attr('autocomplete', 'off')
            .attr('placeholder', placeholder)
            .wrap('<div class="flex-grow texticon search icon clearable"></div>');

        $('#' + id + '_wrapper').prepend('<div class="toolbar"></div>');

        $('#' + id + '_length').appendTo('#' + id + '_wrapper .toolbar');
        $('#' + id + '_filter').appendTo('#' + id + '_wrapper .toolbar');

        $('#' + id).removeClass('hidden');
    },
});
