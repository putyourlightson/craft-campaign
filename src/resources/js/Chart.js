/** global: Campaign */
/** global: Garnish */
/**
 * Chart class
 */
Campaign.Chart = Garnish.Base.extend(
    {
        formats: {
            minutes: 'H:m',
            hours: 'H:00',
            days: ''
        },

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

                // new Chart({
                //     parent: '#' + $(this).attr('id'),
                //     type: 'percentage',
                //     height: 100,
                //     data: data,
                //     colors: $(this).attr('data-colors').split(',')
                // });
            });
        },

        getChart: function() {
            $('#chart').html('');
            $('.report-chart .spinner').show();
            var interval = $('#interval').val();

            $.get({
                url: Craft.getActionUrl(this.settings.action),
                dataType: 'json',
                data: {
                    campaignId: this.settings.campaignId,
                    mailingListId: this.settings.mailingListId,
                    interval: interval
                },
                success: $.proxy(function(data) {
                    this.drawChart(data, interval);
                    $('.report-chart .spinner').hide();
                }, this)
            });
        },

        drawChart: function(data, interval) {
            var chart = new ApexCharts(
                document.querySelector("#chart"),
                {
                    chart: {
                        type: data.type,
                        height: 300,
                        zoom: {
                            enabled: true
                        }
                    },
                    stroke: {
                        width: 2,
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    colors: data.colors,
                    series: data.series,
                    markers: {
                        size: 4,
                    },
                    xaxis: {
                        type: 'datetime',
                    },
                    tooltip: {
                        x: {
                            format: this.formats[interval] ? this.formats[interval] : ''
                        },
                    },
                    legend: {
                        show: false,
                    }
                }
            );

            chart.render();
        },
    }
);