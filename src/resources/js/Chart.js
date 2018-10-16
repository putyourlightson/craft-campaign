/** global: Campaign */
/** global: Garnish */
/**
 * Chart class
 */
Campaign.Chart = Garnish.Base.extend(
    {
        init: function(settings) {
            this.setSettings(settings);

            // Draw percentage charts
            this.drawPercentageCharts();

            // Add listener to report tab
            $('.tab-report').click($.proxy(function() {
                this.getChart();
                this.drawPercentageCharts();
            }, this));

            // Add listener to interval select
            $('#interval').change($.proxy(function() {
                this.getChart();
            }, this));

            // Add listener to refresh button
            $('#refresh').click($.proxy(function(event) {
                event.preventDefault();
                this.getChart();
            }, this));
        },

        drawPercentageCharts: function() {
            var options = {
                chart: {
                    type: 'radialBar',
                    height: 200,
                    animations: {
                        enabled: false
                    },
                },
                plotOptions: {
                    radialBar: {
                        hollow: {
                            size: '75%'
                        },
                        dataLabels: {
                            name: {
                                offsetY: -10,
                                fontSize: '14px',
                            },
                            value: {
                                offsetY: 3,
                                fontSize: '20px',
                            },
                        },
                    },
                },
            };

            $('.percentage-chart').each(function() {
                options.series = [$(this).attr('data-value')];
                options.labels = [$(this).attr('data-label')];
                options.colors = [$(this).attr('data-color')];

                var chart = new ApexCharts($(this)[0], options);

                chart.render();
            });
        },

        getChart: function(){

            $('#chart').html('').css('min-height', '');
            $('.report-chart .spinner').show();

            $.get({
                url: Craft.getActionUrl(this.settings.action),
                dataType: 'json',
                data: {
                    campaignId: this.settings.campaignId,
                    mailingListId: this.settings.mailingListId,
                    interval: $('#interval').val(),
                },
                success: $.proxy(function(data) {
                    this.drawChart(data);
                    $('.report-chart .spinner').hide();
                }, this)
            });
        },

        drawChart: function(data) {
            var intervalFormats = {
                minutes: {hour: 'numeric', minute: 'numeric', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'},
                hours: {hour: 'numeric', minute: 'numeric', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'},
                days: {weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'},
                months: {month: 'short', year: 'numeric'},
                years: {year: 'numeric'},
            };

            var dateTimeFormat = new Intl.DateTimeFormat(data.locale, intervalFormats[data.interval] ? intervalFormats[data.interval] : {});

            var chart = new ApexCharts(document.querySelector("#chart"), {
                chart: {
                    type: 'line',
                    height: 300,
                    zoom: {
                        enabled: true
                    }
                },
                stroke: {
                    width: 2,
                    lineCap: 'round',
                },
                dataLabels: {
                    enabled: false,
                },
                colors: data.colors,
                series: data.series,
                markers: {
                    size: 3,
                    hover: {
                        size: 4,
                    }
                },
                xaxis: {
                    type: 'datetime',
                    tooltip: {
                        enabled: false,
                    },
                },
                yaxis: {
                    tickAmount: data.maxValue < 5 ? data.maxValue + 1 : 5,
                    max: data.maxValue * 1.2,
                },
                grid: {
                    show: true,
                    strokeDashArray: 5,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                tooltip: {
                    x: {
                        formatter: function(val) {
                            return dateTimeFormat.format(val);
                        }
                    },
                },
                legend: {
                    show: false,
                }
            });

            chart.render();
        },
    }
);
