<?php
/**
 * WPLogger: Email class
 *
 * Used for email notifications
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Plugin;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use UAParser\Exception\FileNotFoundException;
use UAParser\Parser;
use WPLogger\Logger\Logger;

/**
 * Class Email
 *
 * @package WPLogger
 */
class Email
{
    /**
     * Email constructor.
     *
     * @return void
     */
    public function __construct()
    {
		// stuff
    }

    /**
     * Initialize this class for direct usage
     *
     * @return Email
     */
    public static function initialize(): Email
    {
        return new self;
    }

	/**
	 * For sending log data with email
     *
	 * @param  array $logData
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function sendMail( array $logData )
	{
        if ( $logData['emails'] && $logData['emails'][0] )
		    $to = $logData['emails'];
        else
	        $to = get_bloginfo( 'admin_email' );

		$subject = '['. get_bloginfo( 'name' ) . '] ' . WP_LOGGER_NAME_OUTPUT . ': New log notification';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		/** User agent */
		$uaParser              = Parser::create();
		$uapResult             = $uaParser->parse( $logData['user_agent'] );
		$logData['user_agent'] = $uapResult->toString();
		/** Generate template */
		$body = $this->genTemplate( $logData );

		wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * For creating email HTMl template
     *
	 * @param  array $logData
	 * @return bool
	 */
	private function genTemplate( array $logData )
	{
		ob_start();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<title><?php echo WP_LOGGER_NAME_OUTPUT; ?></title>
			<style>
                @media only screen and (max-width: 620px) {
                    table.body p,
                    table.body ul,
                    table.body ol,
                    table.body td,
                    table.body span,
                    table.body a {
                        font-size: 16px !important;
                    }

                    table.body .wrapper{
                        padding: 10px !important;
                    }

                    table.body .content {
                        padding: 0 !important;
                    }

                    table.body .container {
                        padding: 0 !important;
                        width: 100% !important;
                    }

                    table.body .main {
                        border-left-width: 0 !important;
                        border-radius: 0 !important;
                        border-right-width: 0 !important;
                    }

                    table.body .btn table {
                        width: 100% !important;
                    }

                    table.body .btn a {
                        width: 100% !important;
                    }
                }
                @media all {
	                
                    .ExternalClass p,
                    .ExternalClass span,
                    .ExternalClass font,
                    .ExternalClass td,
                    .ExternalClass div {
                        line-height: 100%;
                    }

                    .apple-link a {
                        color: inherit !important;
                        font-family: inherit !important;
                        font-size: inherit !important;
                        font-weight: inherit !important;
                        line-height: inherit !important;
                        text-decoration: none !important;
                    }

                    #MessageViewBody a {
                        color: inherit;
                        text-decoration: none;
                        font-size: inherit;
                        font-family: inherit;
                        font-weight: inherit;
                        line-height: inherit;
                    }

                    .btn-primary a:hover {
                        background-color: #2a8c87 !important;
                    }
                }
			</style>
		</head>
		<body style="background-color: #f2f3f5; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
		<table role="presentation" class="body" style="border-collapse: separate; mso-table-lspace: 0; mso-table-rspace: 0; width: 100%;">
			<tr>
				<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
				<td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 100%; padding: 20px 10px; width: 680px; margin: 0 auto;">
					<div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px; background: #ffffff; border-radius: 5px;">
						<!-- START CENTERED WHITE CONTAINER -->
                        <div style="overflow: hidden; line-height: 50px; padding: 10px;">
                            <img style="float: left; height: 48px; margin-right: 15px;"
                                 src="<?php echo WP_LOGGER_DIR_URL . 'assets/admin/img/logo-email.png'; ?>" alt="logo">
                            <span style="font-weight: bold; font-size: 22px; text-transform: uppercase;"><span style="color: rgb(52, 177, 170);">WP</span> Logger</span>
                        </div>
                        <div>
                            <h3 style="margin: 10px 0; text-align: center;">New log notification</h3>
                        </div>
                        <table role="presentation" class="main" style="border-collapse: separate; mso-table-lspace: 0; mso-table-rspace: 0; width: 100%; padding: 10px 20px;">
                            <!-- START MAIN CONTENT AREA -->
                            <tr style="border-bottom:1px solid #eee;">
                                <th class="wrapper" style="background-color:#f3f3f3; font-family: sans-serif; font-size: 13px; vertical-align: top; text-align: left; box-sizing: border-box; padding: 10px; line-height: 22px; width: 20%; border-radius: 3px;">
                                    Title
                                </th>
                                <td style="font-family: sans-serif; font-size: 13px; text-align: left; vertical-align: top; padding: 10px;line-height: 22px; width: 80%;">
	                                <?php echo esc_attr( $logData['title'] ); ?>
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid #eee;">
                                <th class="wrapper" style="background-color:#f3f3f3; font-family: sans-serif; font-size: 13px; vertical-align: top; text-align: left; box-sizing: border-box; padding: 10px; line-height: 22px; width: 20%; border-radius: 3px;">
                                    Type
                                </th>
                                <td style="font-family: sans-serif; font-size: 13px; text-align: left; vertical-align: top; padding: 10px;line-height: 22px; width: 80%;">
			                        <?php echo esc_attr( ucwords( str_replace( '_', ' ', $logData['types'] ) ) ); ?>
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid #eee;">
                                <th class="wrapper" style="background-color:#f3f3f3; font-family: sans-serif; font-size: 13px; vertical-align: top; text-align: left; box-sizing: border-box; padding: 10px; line-height: 22px; width: 20%; border-radius: 3px;">
                                    Importance
                                </th>
                                <td style="font-family: sans-serif; font-size: 13px; text-align: left; vertical-align: top; padding: 10px;line-height: 22px; width: 80%;">
			                        <?php echo esc_attr( ucwords( $logData['importance'] ) ); ?>
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid #eee;">
                                <th class="wrapper" style="font-family: sans-serif; font-size: 13px; vertical-align: top; box-sizing: border-box; padding: 10px; text-align: left; background-color:#f1f1f1;line-height: 22px; width: 20%; border-radius: 3px;">
                                    Description
                                </th>
                                <td style="font-family: sans-serif; font-size: 13px; vertical-align: top; padding: 10px;line-height: 22px; width: 80%;">
			                        <?php echo wp_kses( $logData['desc'], Logger::$allowed_html ); ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="wrapper" style="background-color:#f3f3f3; font-family: sans-serif; font-size: 13px; vertical-align: top; text-align: left; box-sizing: border-box; padding: 10px; line-height: 22px; width: 20%; border-radius: 3px;">
                                    User
                                </th>
                                <td style="font-family: sans-serif; font-size: 13px; text-align: left; vertical-align: top; padding: 10px;line-height: 22px; width: 80%;">
                                    <b>User Login:</b> <?php echo esc_attr( $logData['user_data']['user_login'] ); ?><br>
                                    <b>Name:</b> <?php echo esc_attr( $logData['user_data']['display_name'] ); ?><br>
                                    <b>Email:</b> <?php echo esc_attr( $logData['user_data']['user_email'] ); ?><br>
                                    <b>IP:</b> <?php echo esc_attr( $logData['user_ip'] ); ?><br>
                                    <b>Agent:</b> <?php echo esc_attr( $logData['user_agent'] ); ?>
                                </td>
                            </tr>
                            <!-- END MAIN CONTENT AREA -->
                        </table>
						<table role="presentation" class="main" style="border-collapse: separate; mso-table-lspace: 0; mso-table-rspace: 0; width: 100%;">
							<!-- START MAIN CONTENT AREA -->
							<tr>
								<td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 10px; text-align: center;">
                                    <a href="<?php echo admin_url( 'admin.php?page=' . WP_LOGGER_NAME_LINLINE . '-reports' ); ?>" target="_blank"
                                       style="border-radius: 5px; box-sizing: border-box; cursor: pointer; display: inline-block; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-decoration: none; text-transform: capitalize; background-color: #34B1AA; color: #ffffff;">
	                                    <?php echo esc_attr( $logData['user_agent'] ); ?>
                                    </a>
                                </td>
							</tr>
							<!-- END MAIN CONTENT AREA -->
						</table>
						<!-- END CENTERED WHITE CONTAINER -->
						<!-- START FOOTER -->
						<div style="clear: both; margin-top: 10px; text-align: center; width: 100%;">
							<table role="presentation" style="border-collapse: separate; mso-table-lspace: 0; mso-table-rspace: 0; width: 100%;">
								<tr>
									<td style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; color: #999999; font-size: 12px; text-align: center;">
										Powered by <a href="http://wp-logger.com/" style="color: #999999; font-size: 12px; text-align: center; text-decoration: none;">WP Logger</a>.
									</td>
								</tr>
							</table>
						</div>
						<!-- END FOOTER -->
					</div>
				</td>
				<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
			</tr>
		</table>
		</body>
		</html>
		<?php
		$template = ob_get_contents();
		ob_end_clean();
		return $template;
	}

}