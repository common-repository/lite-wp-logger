jQuery( function($) {

    function loggerDateFilterInit() {
        let dateFrom = $( 'input[name="datefrom"]' ), dateTo = $( 'input[name="dateto"]' );

        $( 'input[name="datefrom"], input[name="dateto"]' ).datepicker( { dateFormat : "yy-mm-dd" } );

        dateFrom.on( 'change', function() {
            dateTo.datepicker( 'option', 'minDate', dateFrom.val() );
        } );

        dateTo.on( 'change', function() {
            dateFrom.datepicker( 'option', 'maxDate', dateTo.val() );
        } );
    }

    loggerDateFilterInit();

    $( '.post-type-wplogs' ).on( 'click', '.log-details-btn', function ( e ) {
        e.preventDefault();

        let logID = '';
        if ( $( this ).hasClass( 'row-title' ) ) {
            logID = $(this).attr('href').split("=")[1].split("&")[0];
        } else {
            logID = $(this).attr('data-logid');
        }

        const theBox = $('.log-details-viewer');

        theBox.addClass('show').addClass('loading');
        theBox.find('.content').html('')
        $('.log-details-viewer-back').addClass('show');

        let reqData = {
            action: logs_vars.plugin_name+'_getLogDetails',
            nonce: logs_vars.nonce,
            log_id: logID
        }
        $.post(logs_vars.url, reqData,
            (response) => {
                theBox.removeClass('loading');
                if(response.data && response.data === 'wrong id'){
                    theBox.removeClass('loading').removeClass('show');
                    $('.log-details-viewer-back').removeClass('show');
                }else if(response.data){
                    let uaparser = UAParser(response.data.agent)
                    let theHtml = ''
                    theHtml += '<p><b>'+logs_vars.translations.title+'</b>'+response.data.title+'</p>'
                    theHtml += '<p><b>'+logs_vars.translations.importance+'</b>'+response.data.importance+'</p>'
                    theHtml += '<p><b>'+logs_vars.translations.type+'</b>'+response.data.type+'</p>'
                    theHtml += '<p><b>'+logs_vars.translations.user_agent+'</b> '+
                        logs_vars.translations.os+uaparser.os.name+'('+uaparser.os.version+'), '+
                        logs_vars.translations.browser+uaparser.browser.name+'('+uaparser.browser.version+')</p>'
                    theHtml += '<p><b>'+logs_vars.translations.user_ip+'</b>'+response.data.ip+'</p>'
                    if(response.data.user){
                        theHtml += '<p><b>'+logs_vars.translations.user_name+'</b>'+response.data.user.display_name+'('+response.data.user.user_login+')</p>'
                        theHtml += '<p><b>'+logs_vars.translations.user_email+'</b>'+response.data.user.user_email+'</p>'
                    }
                    theHtml += '<p><b>'+logs_vars.translations.log_details+'</b></p>'+
                        '<div class="log-details"><div class="log-details-inner">' +response.data.content+'</div></div>'
                    theBox.find('.content').html(theHtml)
                }
            }
        ).fail(() => {
            theBox.removeClass('loading').removeClass('show');
            $('.log-details-viewer-back').removeClass('show');
        });

    } );

    $( '.log-details-viewer-back, .log-details-viewer-close' ).on( 'click', function () {
        $( '.log-details-viewer-back' ).removeClass( 'show' );
        $( '.log-details-viewer' ).removeClass( 'show' );
    } );

    let fromNowTimeout;
    if ( $( '.from-now' ).length )
        fromNowUpdate();

    function fromNowUpdate() {
        clearTimeout( fromNowTimeout )
        $( '.from-now' ).each( function () {
            let unixTime = $( this ).attr( 'data-unix' );
            $( this ).html( moment.unix( unixTime ).fromNow() )
        } );
        fromNowTimeout = setTimeout( () => {
            fromNowUpdate();
        }, 30000 ); // update from noe every 30 secs
    }

    let logs_refresh_interval, logsReLoaderTimeout;

    if ( logs_vars.settings.logs_auto_refresh ) {
        logs_refresh_interval = Number( logs_vars.settings.logs_refresh_interval ) * 1000;
        logsReLoaderTimeout = setTimeout( () => {
            runReLoader();
        }, logs_refresh_interval );
    }

    function runReLoader() {
        $( '.log-auto-loading' ).addClass( 'show' );
        clearTimeout(logsReLoaderTimeout)
        $( '.post-type-wplogs #posts-filter' ).load( window.location + ' #posts-filter', () => {
            $( '.log-auto-loading' ).removeClass( 'show' );
            loggerDateFilterInit();
            if ( $( '.from-now' ).length ) fromNowUpdate();
            logsReLoaderTimeout = setTimeout( () => {
                runReLoader();
            }, logs_refresh_interval );
        });
    }

} );