export default {
    template: `
<div class="main-panel">
    <div class="content-wrapper">
        <div class="mb-3"><h2>{{parent.translate('reports_quick_view')}}</h2></div>
        <div class="row mb-2">
            <div class="col-lg-3 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 stretch-card">
                        <div class="col-md-6 col-lg-12 stretch-card">
                            <div class="card bg-success card-rounded report-info-card">
                                <div class="card-body">
                                    <h4 class="card-title card-title-dash text-white mb-1">{{parent.translate('logged_in_today')}}</h4>
                                    <b v-if="reportCards && (reportCards.users || reportCards.users === 0)">{{reportCards.users}}</b>
                                    <b v-else>...</b>
                                    <small class="text-white-50 p-1">{{parent.translate('users')}}</small>
                                    <i class="mdi mdi-account-group"></i>
                                </div>
                            </div>
                        </div>     
                    </div>                
                </div>
            </div>
            <div class="col-lg-3 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 stretch-card">
                        <div class="col-md-6 col-lg-12 stretch-card">
                            <div class="card bg-warning card-rounded report-info-card">
                                <div class="card-body pb-0">
                                    <h4 class="card-title card-title-dash text-white mb-1">{{parent.translate('posts_this_week')}}</h4>
                                    <b v-if="reportCards && (reportCards.posts || reportCards.posts === 0)">{{reportCards.posts}}</b>
                                    <b v-else>...</b>
                                    <small class="text-white-50 p-1">{{parent.translate('posts')}}</small>
                                    <i class="mdi mdi-book-multiple"></i>
                                </div>
                            </div>
                        </div>     
                    </div>                
                </div>
            </div>
            <div class="col-lg-3 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 stretch-card">
                        <div class="col-md-6 col-lg-12 stretch-card">
                            <div class="card bg-danger card-rounded report-info-card">
                                <div class="card-body pb-0">
                                    <h4 class="card-title card-title-dash text-white mb-1">{{parent.translate('comments_this_week')}}</h4>
                                    <b v-if="reportCards && (reportCards.comments || reportCards.comments === 0)">{{reportCards.comments}}</b>
                                    <b v-else>...</b>
                                    <small class="text-white-50 p-1">{{parent.translate('comments')}}</small>
                                    <i class="mdi mdi-comment"></i>
                                </div>
                            </div>
                        </div>     
                    </div>                
                </div>
            </div>
            <div class="col-lg-3 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 stretch-card">
                        <div class="col-md-6 col-lg-12 stretch-card">
                            <div class="card bg-primary-2 card-rounded report-info-card">
                                <div class="card-body pb-0">
                                    <h4 class="card-title card-title-dash text-white mb-1">{{parent.translate('updates_this_week')}}</h4>
                                    <b v-if="reportCards && (reportCards.plugins || reportCards.plugins === 0)">{{reportCards.plugins}}</b>
                                    <b v-else>...</b>
                                    <small class="text-white-50 p-1">{{parent.translate('plugins_updated')}}</small>
                                    <i class="mdi mdi-power-plug"></i>
                                </div>
                            </div>
                        </div>     
                    </div>                
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="row flex-grow">
                    <div class="col-12 stretch-card">
                        <div class="card card-rounded" style="height: 100%; margin-top: 0">
                            <div class="card-body">
                                <div v-if="types === 'loading'" class="grad-loading"
                                 style="width: 456px; margin: 0 auto; height: 456px; border-radius: 50%;"></div>
                                <canvas class="my-auto" id="chart" height="0" style="width: 456px; margin: 0 auto; display: block;"></canvas>
                            </div>
                        </div>
                    </div>                
                </div>
            </div>
            <div class="col-lg-4 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-lg-12">
                        <div class="card card-rounded" style="height: 100%; margin-top: 0">
                            <div class="card-body">
                                <div id="chart-legend" class="mt-3 text-center">
                                    <ul> 
                                        <li v-for="index in 18" :key="'loading-types-'+index">
                                            <div class="grad-loading" style="height:18px; width:90%; margin-bottom:6px;"></div>
                                        </li>   
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
    </div>
</div>
    `,
    data() {
        return {
            parent: this.$parent.$parent,
            types: 'loading',
            reportCards: null,
            chart: null,
        }
    },
    mounted() {
        this.parent.setPage();
        this.getReportCards();
        this.getTopTypes();
    },
    beforeUnmount() {
        if(this.chart)
            this.chart.destroy()
    },
    methods: {
        getReportCards() {
            this.parent.sendAjax('getReportCards')
                .then((response) => {
                    this.reportCards = response
                })
        },
        getTopTypes() {
            this.parent.sendAjax('getLogTopTypes')
                .then((response) => {
                    this.types = response;
                    this.setupChart();
                })
        },
        setupChart() {
            let ChartCanvas = jQuery("#chart").get(0).getContext("2d");

            let counts = [];
            let names = [];
            this.types.forEach((item)=>{
                counts.push(item.count);
                names.push(item.title);
            });

            let theColors = [
                '#F44336',
                '#EC407A',
                '#9C27B0',
                '#B388FF',
                '#536DFE',
                '#0091EA',
                '#00B8D4',
                '#00695C',
                '#64DD17',
                '#FFC400',
                '#FF6D00',
                '#FF3D00',
                '#DD2C00',
                '#5D4037',
                '#4E342E',
                '#424242',
                '#37474F',
            ];

            theColors = theColors.concat(
                theColors.concat(
                    theColors.concat(theColors)
                )
            );

            let ChartData = {
                datasets: [{
                    data: counts,
                    backgroundColor: theColors,
                    borderColor: theColors,
                }],
                labels: names
            };
            let ChartOptions = {
                // cutoutPercentage: 50,
                // animationEasing: "easeOutBounce",
                // animateRotate: true,
                // animateScale: false,
                // responsive: true,
                // maintainAspectRatio: true,
                // showScale: true,
                plugins: {
                    legend: false,
                },
            };
            this.chart = new Chart(ChartCanvas, {
                type: 'doughnut',
                data: ChartData,
                options: ChartOptions,
                plugins: [{
                    beforeInit: function(chart, args, options) {
                        let ul = '<div class="chartjs-legend"><ul>';
                        chart.data.labels.forEach((label, i) => {
                            ul += `
                            <li>
                                <span style="background-color: ${ chart.data.datasets[0].backgroundColor[i] }"></span>
                                ${ label }
                            </li>`;
                        });
                        ul += '</ul></div>'
                        return jQuery('#chart-legend').html(ul);
                    }
                }]
            });
        },
    },
}