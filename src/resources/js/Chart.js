/** global: Campaign */
/** global: Craft */
/** global: Garnish */
/**
 * Chart class
 */
Campaign.Chart = Garnish.Base.extend({
    chart: null,

    init: function(settings) {
        this.setSettings(settings);

        this.getChart();

        // Add listener to report tab
        $('.pane-tabs [data-id=tab-report]').click(() => {
            this.getChart();
        });

        // Add listener to interval select
        $('[data-id=interval]').change(() => {
            this.getChart();
        });

        // Add listener to refresh button
        $('[data-id=refresh]').click((event) => {
            event.preventDefault();
            this.getChart();
        });
    },

    getChart: function() {
        $('[data-id=chart]').hide();
        $('.report-chart .spinner').show();

        $.get({
            url: Craft.getActionUrl(this.settings.action),
            dataType: 'json',
            data: {
                campaignId: this.settings.campaignId,
                mailingListId: this.settings.mailingListId,
                interval: $('[data-id=interval]').val(),
            },
            success: (data) => {
                $('[data-id=chart]').show();
                $('.report-chart .spinner').hide();

                // Draw the percentage charts here, so we can be absolutely sure that ApexCharts has loaded!
                this.drawPercentageCharts();

                this.drawChart(data);
            }
        });
    },

    drawPercentageCharts: function() {
        // Only draw the percentage charts once!
        if (this.chart !== null) {
            console.log(this.chart);
            return;
        }

        const options = {
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

            const chart = new ApexCharts($(this)[0], options);
            chart.render();
        });
    },

    drawChart: function(data) {
        const intervalFormats = {
            minutes: {hour: 'numeric', minute: 'numeric', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'},
            hours: {hour: 'numeric', minute: 'numeric', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'},
            days: {weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'},
            months: {month: 'short', year: 'numeric'},
            years: {year: 'numeric'},
        };

        const dateTimeFormat = new Intl.DateTimeFormat(data.locale, intervalFormats[data.interval] ? intervalFormats[data.interval] : {});

        const options = {
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
                labels: {
                    datetimeUTC: false,
                },
                tooltip: {
                    enabled: false,
                },
            },
            yaxis: {
                tickAmount: data.maxValue < 5 ? data.maxValue : 5,
                forceNiceScale: true,
                labels: {
                    formatter: function(val) {
                        return val.toFixed(0)
                    }
                },
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
        };

        if (this.chart === null) {
            this.chart = new ApexCharts(document.querySelector('[data-id=chart]'), options);
            this.chart.render();
        }
        else {
            this.chart.updateOptions(options);
        }
    },
});
