/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * DataTable class
 */
Campaign.DataTable = Garnish.Base.extend(
    {
        init: function(settings) {
            this.setSettings(settings);

            this.createDataTable();
        },

        createDataTable: function() {
            const id = this.settings.id;
            const itemName = this.settings.itemName ?? 'items';
            const options = this.settings.options ?? {};

            options.language = {
                lengthMenu: '_MENU_ ' + itemName + ' displayed',
                search: '',
                info: '_START_-_END_ of _TOTAL_ ' + itemName,
                infoEmpty: 'Showing 0 to 0 of 0 ' + itemName,
                infoFiltered: '(filtered from _MAX_ total ' + itemName + ')',
                zeroRecords: 'No matching ' + itemName + ' found',
            };

            const dataTable = $('#' + id).DataTable(options);
            dataTable.on('draw', () => Craft.initUiElements());

            $('#' + id + '_wrapper select').wrap('<div class="select"></div>');

            $('#' + id + '_filter input').addClass('text fullwidth')
                .attr('autocomplete', 'off')
                .attr('placeholder', 'Search')
                .wrap('<div class="flex-grow texticon search icon clearable"></div>');

            $('#' + id + '_wrapper').prepend('<div class="toolbar"></div>');

            $('#' + id + '_length').appendTo('#' + id + '_wrapper .toolbar');
            $('#' + id + '_filter').appendTo('#' + id + '_wrapper .toolbar');

            $('#' + id).removeClass('hidden');
        },
    }
);
