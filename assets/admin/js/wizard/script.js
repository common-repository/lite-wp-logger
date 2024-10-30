//Wizard Init
jQuery(function($) {
    $("#wizard").steps( {
        headerTag: "h3",
        bodyTag: "section",
        labels: {
            cancel: wizard_vars.translations['skip_wizard'],
            finish: wizard_vars.translations['finish'],
            next: wizard_vars.translations['next'],
            previous: wizard_vars.translations['previous'],
            loading: wizard_vars.translations['loading'],
        },
        transitionEffect: 1,
        titleTemplate: '#title#',
        enableCancelButton: true,
        onCanceled: function () {
            window.location = wizard_vars.admin_url
        },
        onFinished: function () {
            let finishBott = $('a[href="#finish"]').parent()
            if ( ! finishBott.hasClass( 'disabled' ) ) {
                jQuery.post( wizard_vars.url, {
                        action: wizard_vars.plugin_name+'_saveWizard',
                        nonce: wizard_vars.nonce,
                        logs_expire: $('input[name=logs_expire]').val(),
                        event_login: ($('input[name=event_login]')[0].checked)? 1:0,
                        event_session: ($('input[name=event_session]')[0].checked)? 1:0,
                        event_new_post: ($('input[name=event_new_post]')[0].checked)? 1:0,
                        event_delete_post: ($('input[name=event_delete_post]')[0].checked)? 1:0,
                    },
                    ( response ) => {
                        if ( response !== 'Nonce died :)' )
                            window.location = wizard_vars.admin_url
                        else{
                            alert( wizard_vars.translations['something_went_wrong_please_try_again'] )
                            finishBott.removeClass( 'disabled' );
                        }
                    }
                ).fail( () => {
                    alert( wizard_vars.translations['something_went_wrong_please_try_again'] )
                    finishBott.removeClass( 'disabled' );
                } );
            }
            finishBott.addClass( 'disabled' );
        },
    } )

    // $("#wizard").steps("skip", 0, {
    //     title: "Step Title",
    //     content: "<p>Step Body</p>"
    // });

    $( 'input' ).on( 'change', function () {
        if ( $( this ).attr( 'type' ) === 'checkbox' ) {
            $( '#' + $( this ).attr( 'name' ) ).html( ( $( this )[0].checked )? 'Yes':'No' )
        } else {
            $( '#' + $( this ).attr( 'name' ) ).html( $( this ).val() )
        }
    } );



});