function wpLoggerCurrentDateTime() {
    return new Date().toJSON().slice(0, 19).replace( 'T', ' ' );
}

function wpLoggerSendNotify( newLogs ) {
    for ( let logIndex in newLogs ) {
        Toastify( {
            text: newLogs[ logIndex ].title + ' | ' + newLogs[ logIndex ].username,
            duration: 5000,
            destination: wplogger_vars.logs_url,
            newWindow: true,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: {
                'margin-top': '25px'
            },
        } ).showToast();
    }
}

function wpLoggerRunNotify() {
    wp.heartbeat.interval( 'fast' );
    let updatedDate = wpLoggerCurrentDateTime();
    jQuery( document ).on( 'heartbeat-send', ( event, data ) => {
        data[ wplogger_vars.plugin_name + '_todate' ] = updatedDate;
        updatedDate = wpLoggerCurrentDateTime();
    } );
    jQuery( document ).on( 'heartbeat-tick', ( event, data ) => {
        let newLogs = data[ wplogger_vars.plugin_name + '_newLogs' ];
        if ( newLogs && newLogs[0] ) wpLoggerSendNotify( newLogs )
    } );
}

if ( wplogger_vars.settings.admin_notify ) {
    wpLoggerRunNotify()
}

let wplogger_activate_menu = jQuery( '#toplevel_page_wplogger .wp-submenu li a[href$=-activate]' )
if ( wplogger_activate_menu.length ) {
    wplogger_activate_menu
        .addClass( 'activate-license-trigger' )
        .addClass( 'wp-logger' );
}
