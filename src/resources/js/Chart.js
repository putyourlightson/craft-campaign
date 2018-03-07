/** global: Campaign */
/** global: Garnish */
/**
 * Chart class
 */
Campaign.Chart = Garnish.Base.extend(
    {
        chart: null,

        init: function(settings) {
            this.setSettings(settings);

            // Draw percentage charts
            this.drawPercentageCharts();

            // Add listener to report tab
            $('.tab-report').click($.proxy(function() {
                this.chart.refresh();
            }, this));

            // Add listener to interval
            $('#interval').change($.proxy(function() {
                this.getChart();
            }, this));
        },

        drawPercentageCharts: function() {
            $('.percentage-chart').each(function() {
                var data = {
                    labels: $(this).attr('data-labels').split(','),
                    datasets: [
                        {
                            values: $.map($(this).attr('data-values').split(','), Number)
                        }
                    ]
                };

                new Chart({
                    parent: '#' + $(this).attr('id'),
                    type: 'percentage',
                    height: 100,
                    data: data,
                    colors: $(this).attr('data-colors').split(',')
                });
            });
        },

        getChart: function() {
            $('#chart').html('');
            $('.report-chart .spinner').show();

            $.get({
                url: Craft.getActionUrl(this.settings.action),
                dataType: 'json',
                data: {
                    campaignId: this.settings.campaignId,
                    mailingListId: this.settings.mailingListId,
                    interval: $('#interval').val()
                },
                success: $.proxy(function(data) {
                    this.drawChart(data);
                    $('.report-chart .spinner').hide();
                }, this)
            });
        },

        drawChart: function(data) {
            this.chart = new Chart({
                parent: "#chart",
                title: data.title ? data.title : '',
                type: data.type ? data.type : 'bar',
                height: data.type == 'percentage' ? 100 : 250,
                data: data.data,
                colors: data.colors,
                format_tooltip_x: d => this.getTooltip(data, d)
            });
        },

        getTooltip: function(data, label) {
            return data.data.indexes ? data.data.indexes[label] : label;
        },
    }
);