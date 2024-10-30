import SideNav from './parts/side-nav.js';

const routes = [
    { path: '/', name: 'reports', component: ()=> import( './reports.js' ) },
    { path: '/logs', name: 'logs', component: ()=> import( './logs.js' ) },
    { path: '/online-users', name: 'online-users', component: ()=> import( './online-users.js' ) },
    { path: '/settings', name: 'settings', component: ()=> import( './settings.js' ) },
    { path: '/events', name: 'events', component: ()=> import( './events.js' ) },
];

const router = VueRouter.createRouter( {
    history: VueRouter.createWebHashHistory(),
    routes,
} );

const app = Vue.createApp( {
    components: {
        'side-nav': SideNav,
    },
    template: `
<div class="container-fluid page-body-wrapper">
    <side-nav />
    <router-view />
</div>
    `,
    data() {
        return {
            translations: reports_vars.translations,
            is_premium: reports_vars.is_premium,
            // is_premium: false,
        }
    },
    mounted() {
        if ( ! wplogger_vars.settings.admin_notify )
            wpLoggerRunNotify();
        // checking every window size change
        this.handleResize();
        window.addEventListener( 'resize', this.handleResize );
    },
    methods: {
        sendAjax( action, extraData = null ) {
            return new Promise( ( resolve, reject ) => {
                let reqData = {
                    action: reports_vars.plugin_name + '_' + action,
                    nonce: reports_vars.nonce,
                };
                if( extraData )
                    reqData = Object.assign( extraData, reqData );
                jQuery.post( reports_vars.url, reqData,
                    ( response ) => {
                        resolve( response );
                    }
                ).fail( ( error ) => {
                    reject( error );
                } );
            } );
        },
        translate( transKey ) {
            if ( this.translations[ transKey ] )
                return this.translations[ transKey ];
            else
                return transKey.split( "_" )
                    .map( substr => substr.charAt( 0 )
                        .toUpperCase() + substr.slice( 1 ) )
                            .join( " " );
        },
        handleResize() {
            let panel = jQuery( '.page-body-wrapper' );
            let sideH = jQuery( '#adminmenuwrap' ).height();
            if ( sideH >= panel.height() )
                panel.css( 'min-height', sideH + 'px' );
        },
        setPage( pname = '' ) {
            if ( pname === '' ) pname = 'wplogger-reports';
            else pname = '#/'+pname;
            jQuery( '.toplevel_page_wplogger .wp-submenu li.current' ).removeClass( 'current' );
            jQuery( '.toplevel_page_wplogger .wp-submenu li a[href$="' + pname + '"]' ).parent().addClass( 'current' );
        }
    }
} );
app.use( router );
app.mount( "#app" );