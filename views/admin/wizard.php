<?php /** dashboard wizard page template */
/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use WPLogger\Plugin\Admin;

$admin = new Admin;
$admin->retrieveSettings();
$admin->retrieveEventsSettings();
$admin->setSettingsFields();
$admin->setEventsSettingsFields();
$plugin_settings = $admin->settings;
$events_settings = $admin->events_settings; ?>
<main>
    <div class="container">
        <div class="plugin-logo">
            <img src="<?php echo WP_LOGGER_DIR_URL; ?>/assets/admin/img/logo.svg" alt="<?php echo WP_LOGGER_NAME; ?>">
            <h2><span style="color: rgb(52, 177, 170);">WP</span> Logger</h2>
        </div>
        <div id="wizard">
            <h3>
                <div class="media">
                    <div class="bd-wizard-step-icon"><i class="mdi mdi-account-outline"></i></div>
                    <div class="media-body">
                        <div class="bd-wizard-step-title"><?php _e( 'Welcome!' , 'lite-wp-logger' ); ?></div>
                        <div class="bd-wizard-step-subtitle"><?php _e( 'Basic Info' , 'lite-wp-logger' ); ?></div>
                    </div>
                </div>
            </h3>
            <section>
                <div class="content-wrapper">
                    <h4 class="section-heading"><?php _e( 'Welcome!' , 'lite-wp-logger' ); ?></h4>
                    <div class="row">
                        <div class="col-12">
                            <p>
	                            <?php _e( 'With WP Logger Plugin you can identify WordPress security issues before they become a problem.<br>
                                Keep track of everything happening on your WordPress including WordPress users activity.<br>
                                Similar to Windows Event Log and Linux Syslog,
                                WP Logger generates a security alert for everything that happens on your WordPress blogs and websites.
                                Use the Activity log viewer included in the plugin to see all the security alerts.' , 'lite-wp-logger' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>
            <h3>
                <div class="media">
                    <div class="bd-wizard-step-icon"><i class="mdi mdi-bank"></i></div>
                    <div class="media-body">
                        <div class="bd-wizard-step-title"><?php _e( 'Initial Settings' , 'lite-wp-logger' ); ?></div>
                        <div class="bd-wizard-step-subtitle"><?php _e( 'Settings' , 'lite-wp-logger' ); ?></div>
                    </div>
                </div>
            </h3>
            <section>
                <div class="content-wrapper">
                    <h4 class="section-heading"><?php _e( 'Initial Settings' , 'lite-wp-logger' ); ?></h4>
                    <div class="row">
                        <div class="form-group col-12">
                            <label for="logs_expire"><?php _e( 'How long store logs' , 'lite-wp-logger' ); ?>?
                                <small>(<?php _e( 'Days' , 'lite-wp-logger' ); ?>)</small></label>
                            <input type="text" name="logs_expire" pattern="\d*" class="form-control"
                                placeholder="<?php _e( 'How long store logs' , 'lite-wp-logger' ); ?>?"
                                value="<?php echo esc_attr( $admin->getSetting( 'logs_expire' ) ); ?>">
                        </div>
                    </div>
                </div>
            </section>
            <h3>
                <div class="media">
                    <div class="bd-wizard-step-icon"><i class="mdi mdi-account-check-outline"></i></div>
                    <div class="media-body">
                        <div class="bd-wizard-step-title"><?php _e( 'Events Control' , 'lite-wp-logger' ); ?></div>
                        <div class="bd-wizard-step-subtitle"><?php _e( 'Events Settings' , 'lite-wp-logger' ); ?></div>
                    </div>
                </div>
            </h3>
            <section>
                <div class="content-wrapper">
                    <h4 class="section-heading mb-5"><?php _e( 'Events Settings' , 'lite-wp-logger' ); ?></h4>
                    <div class="row">
                        <div class="form-group col-12">
                            <input type="checkbox" name="event_login" value="1"
                                <?php echo ( $admin->getEventSetting( 'login' ) )? 'checked' : ''; ?>>
                            <span><?php _e( 'Logging user login?' , 'lite-wp-logger' ); ?></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-12">
                            <input type="checkbox" name="event_session" value="1"
                                <?php echo ( $admin->getEventSetting( 'session' ) )? 'checked' : ''; ?>>
                            <span><?php _e( 'Logging user active login sessions?' , 'lite-wp-logger' ); ?></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-12">
                            <input type="checkbox" name="event_new_post" value="1"
                                <?php echo ( $admin->getEventSetting( 'new_post' ) )? 'checked' : ''; ?>>
                            <span><?php _e( 'Logging every new post?' , 'lite-wp-logger' ); ?></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-12">
                            <input type="checkbox" name="event_delete_post" value="1"
                                <?php echo ( $admin->getEventSetting( 'delete_post' ) )? 'checked' : ''; ?>>
                            <span><?php _e( 'Logging every delete post?' , 'lite-wp-logger' ); ?></span>
                        </div>
                    </div>
                </div>
            </section>
            <h3>
                <div class="media">
                    <div class="bd-wizard-step-icon"><i class="mdi mdi-emoticon-outline"></i></div>
                    <div class="media-body">
                        <div class="bd-wizard-step-title"><?php _e( 'Review' , 'lite-wp-logger' ); ?></div>
                        <div class="bd-wizard-step-subtitle"><?php _e( 'Review Info' , 'lite-wp-logger' ); ?></div>
                    </div>
                </div>
            </h3>
            <section>
                <div class="content-wrapper">
                    <h4 class="section-heading mb-5"><?php _e( 'Review' , 'lite-wp-logger' ); ?></h4>
                    <h5 class="font-weight-bold"><?php _e( 'Settings' , 'lite-wp-logger' ); ?></h5>
                    <p class="mb-4">
                        <b><?php _e( 'How long store logs' , 'lite-wp-logger' ); ?>:</b> <span id="logs_expire">
                            <?php echo $admin->getSetting( 'logs_expire' ); ?>
                        </span> <?php _e( 'Days' , 'lite-wp-logger' ); ?>
                    </p>
                    <h5 class="font-weight-bold"><?php _e( 'Events Control' , 'lite-wp-logger' ); ?></h5>
                    <p>
                        <b><?php _e( 'Logging user login' , 'lite-wp-logger' ); ?>:</b> <span id="event_login">
                            <?php echo ( $admin->getEventSetting( 'login' ) )? 'Yes' : 'No'; ?>
                        </span><br>
                        <b><?php _e( 'Logging user active login sessions' , 'lite-wp-logger' ); ?>:</b> <span id="event_session">
                            <?php echo ( $admin->getEventSetting( 'session' ) )? 'Yes' : 'No'; ?>
                        </span><br>
                        <b><?php _e( 'Logging every new post' , 'lite-wp-logger' ); ?>:</b> <span id="event_new_post">
                            <?php echo ( $admin->getEventSetting( 'new_post' ) )? 'Yes' : 'No'; ?>
                        </span><br>
                        <b><?php _e( 'Logging every delete post' , 'lite-wp-logger' ); ?>:</b> <span id="event_delete_post">
                            <?php echo ( $admin->getEventSetting( 'delete_post' ) )? 'Yes' : 'No'; ?>
                        </span>
                    </p>
                </div>
            </section>
        </div>
    </div>
</main>