import '../../global/jquery.pagination.js';

export default {
    template: `
<div class="main-panel">
    <div class="content-wrapper">
        <div class="mb-3"><h2>{{parent.translate('custom_report')}}</h2></div>
        <div class="row">
            <div class="col-lg-12 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 grid-margin stretch-card">
                        <div class="card card-rounded custom-report">
                            <div class="card-body">
                                <div class="d-sm-flex justify-content-between align-items-start mb-3">
                                    <div class="col">
                                        <h4 class="card-title card-title-dash">{{ parent.translate( 'custom_report' ) }}</h4>
                                        <p class="card-subtitle card-subtitle-dash">{{ parent.translate('custom_report_logs' ) }}</p>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" :class="{ 'left btn btn-primary btn-sm text-white me-0 ms-2': true, disabled: ( logs === 'loading' || !parent.is_premium ) }" 
                                        @click.prevent="exportCsv">{{parent.translate('export_csv')}}</a>
                                        <a href="#" :class="{ 'left btn btn-primary btn-sm text-white me-0 ms-2': true, disabled: ( logs === 'loading' || !parent.is_premium ) }" 
                                        @click.prevent="exportPdf">{{parent.translate('export_pdf')}}</a>
                                    </div>
                                </div>
                                <div class="d-sm-flex justify-content-between align-items-start mb-2">
                                    <div class="col">
                                        <a href="#" :class="{ 'left btn btn-primary btn-sm text-white me-0': true, disabled: ( logs === 'loading' || !parent.is_premium ) }" 
                                        @click.prevent="showFilters">{{ (showFiltersRow === 'showFilters')? parent.translate('hide_filters') : parent.translate('show_filters') }}</a>
                                        <a href="#" :class="{ 'left btn btn-warning text-white btn-sm me-0 ms-2': true, disabled: ( logs === 'loading' || !parent.is_premium ) }" 
                                        @click.prevent="runReLoader">{{ parent.translate( 'reload' ) }}</a>
                                    </div>
                                </div>
                                <div :class="{ 'row filters-row': true, show: showFiltersRow === 'showFilters', loading: logs === 'loading' }">
                                    <div class="col-md-4 mb-3">
                                        <label for="log-keyword" class="text-small mb-1">{{ parent.translate( 'filter_by_keyword' ) }}</label>
                                        <input type="text" class="form-control" id="log-keyword" name="keyword" :placeholder="parent.translate('keyword')" v-model="selectedWord">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="filterdate" class="text-small mb-1">{{ parent.translate( 'filter_by_date' ) }}</label>
                                        <div class="input-group input-daterange">
                                            <input type="text" name="date-from" class="form-control" data-date-format="yyyy-mm-dd" :placeholder="parent.translate('date_from')">
                                            <div class="input-group-addon">{{ parent.translate( 'to' ) }}</div>
                                            <input type="text" name="date-to" class="form-control" data-date-format="yyyy-mm-dd" :placeholder="parent.translate('date_to')">
                                        </div>
                                    </div> 
                                    <div class="col-md-4 mb-3">
                                        <label for="log-type" class="text-small mb-1">{{ parent.translate( 'filter_by_log_type' ) }}</label>
                                        <select class="type-select select2 w-100" id="log-type" name="type">
                                            <option v-if="types" value="0">{{ parent.translate( 'select_type' ) }}</option>
                                            <option v-if="types" v-for="type in types" :key="type.term_id" :value="type.term_id">{{ type.title }}</option>
                                            <option v-else value="0">{{ parent.translate( 'loading' ) }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="log-importance" class="text-small mb-1">{{ parent.translate( 'filter_by_log_importance' ) }}</label>
                                        <select class="importance-select select2 w-100" id="log-importance" name="importance">
                                            <option v-if="importance" value="0">{{ parent.translate( 'select_importance' ) }}</option>
                                            <option v-if="importance" v-for="impo in importance" :key="impo.term_id" :value="impo.term_id">{{ impo.title }}</option>
                                            <option v-else value="0">{{ parent.translate( 'loading' ) }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="log-user" class="text-small mb-1">{{ parent.translate( 'filter_by_user' ) }}</label>
                                        <select class="user-select w-100" id="log-user" name="user">
                                            <option value="0">{{ parent.translate( 'select_user' ) }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="log-user-role" class="text-small mb-1">{{ parent.translate( 'filter_by_user_role' ) }}</label>
                                        <select class="role-select select2 w-100" id="log-user-role" name="role">
                                            <option v-if="roles" value="0">{{ parent.translate( 'select_role' ) }}</option>
                                            <option v-if="roles" v-for="( role, key ) in roles" :key="key" :value="key">{{ role }}</option>
                                            <option v-else value="0">{{ parent.translate( 'loading' ) }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="log-user-ip" class="text-small mb-1">{{ parent.translate( 'filter_by_user_ip' ) }}</label>
                                        <input type="text" class="form-control" id="log-user-ip" name="ip" :placeholder="parent.translate('ip_address')" v-model="selectedIP">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="per-page" class="text-small mb-1">{{ parent.translate( 'logs_per_page' ) }}</label><br>
                                        <div class="form-number">
                                            <i class="mdi mdi-plus form-number-up" @click="moveNumber('perPage', 'up', $event)"></i>
                                            <i class="mdi mdi-minus form-number-down" @click="moveNumber('perPage', 'down', $event)"></i>
                                            <input id="per-page" type="number" min="1" max="100" name="per-page" v-model="perPage">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="log-filter" class="text-small mb-1">{{ parent.translate( 'apply_filter' ) }}</label>
                                        <a href="#" type="submit" @click.prevent="applyFilters()" id="log-filter" style="display: block; padding: 14px 0;" 
                                        :class="{ 'btn btn-primary me-0 btn-wrapper': true, disabled: logs === 'loading' }">
                                            {{ parent.translate( 'filter' ) }}
                                        </a>
                                    </div>
                                    
                                </div>
                                <div class="table-responsive mt-1">
                                    <table class="table select-table">
                                        <thead>
                                        <tr>
                                            <th style="width: 50%;">{{parent.translate('log_details')}}</th>
                                            <th style="width: 14%;">{{parent.translate('user')}}</th>
                                            <th style="width: 12%;">{{parent.translate('type')}}</th>
                                            <th style="width: 14%;">{{parent.translate('date')}}</th>
                                            <th style="width: 10%;">{{parent.translate('importance')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody v-if="logs === 'loading'">
                                            <tr v-for="key in perPage" :key="'log-'+key">
                                                <td>
                                                    <div class="grad-loading" style="height: 18px; width: 350px; margin-bottom:5px;"></div>
                                                    <div class="grad-loading" style="height: 18px; width: 350px; margin-bottom:5px;"></div>
                                                    <div class="grad-loading" style="height: 18px; width: 350px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 20px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 20px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 20px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 32px; width: 32px; border-radius: 50%;"></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tbody v-if="logs && logs.data && logs.data !== 'No result' && Array.isArray(logs.data)">
                                            <tr v-for="(log, key) in logs.data" :key="'log-'+key">
                                                <td>
                                                    <h6 v-html="log.title"></h6>
                                                    <div v-html="log.desc"></div>
                                                </td>
                                                <td>
                                                    <h6>{{ log.user.user_login }}</h6>
                                                    <span v-if="log.user && log.user.ID">
                                                        <i class="mdi mdi-account"></i>: {{ log.user.display_name }}
                                                    </span>
                                                    <span v-else>
                                                        <i class="mdi mdi-account"></i>: {{parent.translate('anonymous')}}
                                                    </span>
                                                </td>
                                                <td>
                                                    <h6 v-if="log.type">{{ log.type.title }}</h6>
                                                    <span v-if="log.mainType">{{ log.mainType.title }}</span>
                                                </td>
                                                <td>
                                                    <h6 v-if="log.dateNow">{{ log.dateNow }}</h6>
                                                    <h6 v-else>---</h6>
                                                    <span v-if="log.dateFull">{{ log.dateFull }}</span>
                                                    <span v-else>---</span>
                                                </td>
                                                <td>
                                                    <i v-if="log.importance" 
                                                    :class="{ 'mdi mdi-alert-circle-outline': true, [ 'severity-'+log.importance.name ]: true }" 
                                                    :title="log.importance.title"></i>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tbody v-else-if="logs && logs.data">
                                            <tr>
                                                <td>
                                                    <h6>{{parent.translate('no_logs')}}</h6>
                                                </td>
                                                <td>
                                                    <h6>...</h6>
                                                </td>
                                                <td>
                                                    <span>...</span>
                                                </td>
                                                <td>
                                                    <span>...</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="text-center mt-4" v-if="logs && maxPageNum > 1">
                                        <ul id="pagination" class="pagination"></ul>
                                    </div>
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
            types: null,
            importance: null,
            roles: null,
            logs: 'loading',
            selectedType: 0,
            selectedImportance: 0,
            selectedUser: 0,
            selectedRole: 0,
            selectedDateFrom: '',
            selectedDateTo: '',
            selectedIP: '',
            selectedWord: '',
            showFiltersRow: 'hideFilters',
            perPage: 10,
            currentPageNum: 1,
            maxPageNum: 1,
            reports_refresh_interval: 0,
            reLoaderTimeout: null,
            dateTimeout: null,
        }
    },
    mounted() {
        this.parent.setPage('logs');

        if ( reports_vars.settings.reports_auto_refresh ) {
            this.reports_refresh_interval =
                Number( reports_vars.settings.reports_refresh_interval ) * 1000;
            this.runReLoader();
        } else {
            this.getLastLogs();
        }

        this.init();
        this.getTypes();
        this.getImportance();
        this.getUserRoles();
        this.loadUserSelect();
    },
    beforeUnmount() {
        if ( this.reLoaderTimeout )
            clearTimeout( this.reLoaderTimeout );
        if ( this.dateTimeout )
            clearTimeout( this.dateTimeout );
    },
    methods: {
        init() {
            jQuery( '.select2' ).select2();
            jQuery( '.input-daterange input' ).each( function() {
                jQuery( this ).datepicker( 'clearDates' );
            } );
        },
        getTypes() {
            this.parent.sendAjax( 'getLogTypes' )
                .then( ( response ) => {
                    this.types = response
                } );
        },
        getImportance() {
            this.parent.sendAjax( 'getLogImportance' )
                .then( ( response ) => {
                    this.importance = response
                } );
        },
        getUserRoles() {
            this.parent.sendAjax( 'getUserRoles' )
                .then( ( response ) => {
                    this.roles = response
                } );
        },
        loadUserSelect() {
            let userSelect = jQuery( '.user-select' );
            if ( userSelect.length )
                userSelect.select2( {
                    ajax: {
                        type: 'POST',
                        dataType : 'json',
                        contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                        url: reports_vars.url,
                        processResults: ( data, params ) => {
                            return {
                                results: jQuery.map( data, ( item ) => {
                                    return {
                                        id: item.ID,
                                        text: item.user_login +
                                            ( ( Number( item.ID ) !== 0 )? ' (' + item.display_name + ')' : '' ),
                                    };
                                }),
                                pagination: {
                                    more: data.length
                                },
                            };
                        },
                        data: ( params )=>{
                            return {
                                search: params.term,
                                nonce: reports_vars.nonce,
                                action: reports_vars.plugin_name + '_getUsers',
                                page: params.page,
                                no_option: true,
                            };
                        }
                    }
                } );
        },
        getLastLogs() {
            let reqData = { filters: {} }

            if ( ! isNaN( Number( this.perPage ) ) )
                reqData.per_page = this.perPage;

            if ( this.selectedDateFrom !== '' )
                reqData.filters.date_from = this.selectedDateFrom;

            if ( this.selectedDateTo !== '' )
                reqData.filters.date_to = this.selectedDateTo;

            if ( Number( this.selectedType ) !== 0 )
                reqData.filters.type = this.selectedType;

            if ( Number( this.selectedImportance ) !== 0 )
                reqData.filters.importance = this.selectedImportance;

            if ( Number( this.selectedUser ) !== 0 )
                reqData.filters.user = this.selectedUser;

            if ( Number( this.selectedRole ) !== 0 )
                reqData.filters.role = this.selectedRole;

            if ( Number( this.currentPageNum ) !== 1 )
                reqData.page = this.currentPageNum;

            if ( this.selectedIP !== '' )
                reqData.filters.ip = this.selectedIP;

            if ( this.selectedWord !== '' )
                reqData.filters.word = this.selectedWord;

            this.logs = 'loading';

            this.parent.sendAjax( 'getLastLogs' , reqData )
                .then( ( response ) => {
                    if (response.data && response.data !== "No result"){
                        for ( let logI in response.data ) {
                            if( response.data[ logI ].date ) {
                                response.data[ logI ].dateFull =
                                    moment.unix( response.data[ logI ].date ).format( "MMM D YYYY, HH:mm:ss" );
                                response.data[ logI ].dateNow = '...';
                            }
                        }
                        this.logs = response;
                        this.dateUpdate();
                        this.currentPageNum = response.current_page;
                        this.maxPageNum = response.max_page;
                        if ( this.maxPageNum > 1 )
                            setTimeout( () => {
                                this.initPaginate();
                            },100 )
                    } else {
                        this.logs = response;
                        this.currentPageNum = 1;
                        this.maxPageNum = 1;
                    }
                } );
        },
        applyFilters( pageNum = 1 ) {
            this.logs = null;
            this.selectedDateFrom = jQuery( 'input[name=date-from]' ).val()
            this.selectedDateTo = jQuery( 'input[name=date-to]' ).val()
            this.selectedType = jQuery( 'select[name=type]' ).val()
            this.selectedImportance = jQuery( 'select[name=importance]' ).val()
            this.selectedUser = jQuery( 'select[name=user]' ).val()
            this.selectedRole = jQuery( 'select[name=role]' ).val()
            this.currentPageNum = pageNum;
            this.maxPageNum = 1;
            this.getLastLogs();
        },
        initPaginate() {
            jQuery( '#pagination' ).pagination( {
                items: this.maxPageNum,
                itemOnPage: 4,
                currentPage: this.currentPageNum,
                cssStyle: '',
                prevText: '<span aria-hidden="true">&laquo;</span>',
                nextText: '<span aria-hidden="true">&raquo;</span>',
                onPageClick: ( page, e ) => {
                    e.preventDefault();
                    this.applyFilters( page );
                },
            } );
        },
        dateUpdate () {
            clearTimeout( this.dateTimeout );
            for ( let logI in this.logs.data )
                if ( this.logs.data[ logI ].date )
                    this.logs.data[ logI ].dateNow =
                        moment.unix( this.logs.data[ logI ].date ).fromNow();
            this.dateTimeout = setTimeout( () => {
                this.dateUpdate();
            }, 30000 );
        },
        exportPdf() {
            const jsPDF = window.jspdf.jsPDF;
            let headers = [
                {
                    "id": "details",
                    "name": "details",
                    "prompt": "Details",
                    "width": 92,
                    "align": "left",
                    "padding": 0,
                },
                {
                    "id": "user",
                    "name": "user",
                    "prompt": "User",
                    "width": 40,
                    "align": "left",
                    "padding": 0,
                },
                {
                    "id": "type",
                    "name": "type",
                    "prompt": "Type",
                    "width": 36,
                    "align": "left",
                    "padding": 0,
                },
                {
                    "id": "importance",
                    "name": "importance",
                    "prompt": "Importance",
                    "width": 28,
                    "align": "left",
                    "padding": 0,
                },
                {
                    "id": "date",
                    "name": "date",
                    "prompt": "Date",
                    "width": 40,
                    "align": "left",
                    "padding": 0,
                }
            ];

            let theLogs = [];
            if ( this.logs && this.logs.data[0] ) {
                for ( let logIndex in this.logs.data ) {
                    theLogs.push( {
                        details: this.convertHTML(
                            `${this.convertHTMLEntity(this.logs.data[ logIndex ].title)}\n${this.removeATags(this.logs.data[ logIndex ].desc)}`
                        ),
                        type: this.logs.data[ logIndex ].type.title,
                        importance: this.logs.data[ logIndex ].importance.title,
                        user: ( this.logs.data[ logIndex ].user.ID )?
                            this.logs.data[ logIndex ].user.user_login +
                            '(' + this.logs.data[ logIndex ].user.display_name + ')' : 'none',
                        date: this.logs.data[ logIndex ].dateFull,
                    })
                }
                let doc = new jsPDF( { putOnlyUsedFonts: true, } );
                doc.setFontSize( 14 );
                doc.text( 'WP Logger Custom Export', 40, 27 );
                doc.addImage( reports_vars.plugin_url + 'assets/admin/img/logo.jpg', 'JPEG', 16, 16, 20, 20 );
                doc.table( 16, 40, theLogs, headers, {
                    printHeaders: true,
                    fontSize: 7.2,
                    padding: 1.5,
                } );
                doc.save( 'wp-logger-export.pdf' );
            }
        },
        exportCsv() {
            let theLogs = [];
            theLogs.push( [ 'Details', 'Type', 'Importance', 'User', 'Date' ] )

            if ( this.logs && this.logs.data[0] ){
                for ( let logIndex in this.logs.data ) {
                    theLogs.push( [
                        this.convertHTML(
                        `${this.convertHTMLEntity(this.logs.data[ logIndex ].title)}\n${this.removeATags(this.logs.data[ logIndex ].desc)}`
                        ),
                        this.logs.data[ logIndex ].type.title,
                        this.logs.data[ logIndex ].importance.title,
                        ( this.logs.data[ logIndex ].user.ID )?
                            this.logs.data[ logIndex ].user.user_login +
                            '(' + this.logs.data[ logIndex ].user.display_name + ')' : 'none',
                        this.logs.data[ logIndex ].dateFull,
                    ] )
                }
                let arrayToCsv = theLogs.map( row =>
                    row
                        .map( String )  // convert every value to String
                        .map( v => v.replaceAll( '"', '""' ) )  // escape double colons
                        .map( v => `"${v}"` )  // quote it
                        .join( ',' )  // comma-separated
                ).join( '\r\n' );  // rows starting on new lines

                let blob = new Blob( [ arrayToCsv ], { type: 'text/csv;charset=utf-8;' } );
                let pom = document.createElement( 'a' );
                pom.href = URL.createObjectURL( blob );
                pom.setAttribute( 'download', 'wp-logger-export.csv' );
                pom.click();
            }
        },
        showFilters() {
            if ( this.showFiltersRow === 'hideFilters')
                this.showFiltersRow = 'showFilters'
            else
                this.showFiltersRow = 'hideFilters'
        },
        convertHTMLEntity( text ){
            const span = document.createElement( 'span' );
            return text
                .replace( /&[#A-Za-z0-9]+;/gi, ( entity,position,text ) => {
                    span.innerHTML = entity;
                    return span.innerText;
                });
        },
        convertHTML( text ){
            return text.replace(/<br\s*\/?>/gi,'\n').replace(/\n$/, '');;
        },
        moveNumber( item, action, event ) {
            let max = Number( event.target.parentElement.children[2].attributes.max.value );
            let min = Number( event.target.parentElement.children[2].attributes.min.value );

            if ( action === 'up' ){
                if ( max !== 0 ) {
                    if ( Number( this[ item ] ) < max ) {
                        this[ item ] = Number( this[ item ] ) + 1;
                    }
                } else {
                    this[ item ] = Number( this[ item ] ) + 1;
                }
            } else {
                if ( Number( this[ item ] ) > min ) {
                    this[ item ] = Number( this[ item ] ) - 1;
                }
            }
        },
        runReLoader() {
            this.getLastLogs();
            if ( reports_vars.settings.reports_auto_refresh ) {
                clearTimeout( this.reLoaderTimeout );
                this.reLoaderTimeout = setTimeout( () => {
                    this.runReLoader();
                }, this.reports_refresh_interval );
            }
        },
        removeATags( content ) {
            let div = document.createElement( 'div' );
            div.innerHTML = content;
            let elements = div.getElementsByTagName( 'a' );
            while ( elements[0] )
                elements[0].parentNode.removeChild( elements[0] )
            return div.innerHTML
        },
    },
}