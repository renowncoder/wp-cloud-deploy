<?php
/**
 * WordPress app settings.
 *
 * @package wpcd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WORDPRESS_APP_SETTINGS
 */
class WORDPRESS_APP_SETTINGS extends WPCD_APP_SETTINGS {

	/**
	 * Holds a reference to this class
	 *
	 * @var $instance instance.
	 */
	private static $instance;

	/**
	 * Static function that can initialize the class
	 * and return an instance of itself.
	 *
	 * @TODO: This just seems to duplicate the constructor
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wordpress_SERVER_APP_SETTINGS constructor.
	 */
	public function __construct() {

		// setup WordPress and settings hooks.
		$this->hooks();

	}

	/**
	 * Hook into WordPress and other plugins as needed.
	 */
	private function hooks() {

		add_filter( 'wpcd_settings_tabs', array( &$this, 'settings_tabs' ) );  // add a new tab to the settings page.

		add_filter( 'wpcd_settings_metaboxes', array( &$this, 'settings_metaboxes' ) );  // add new metaboxes to our new tab on the settings pages.

		// Enable filter on the private key to encrypt it when its being stored.
		add_filter( 'rwmb_wordpress_app_aws_secret_key_value', array( &$this, 'encrypt_aws_secret_key' ), 10, 3 );

		// Enable filter on the private key field to decrypt it when its being retrieved.
		add_filter( 'rwmb_wordpress_app_aws_secret_key_field_meta', array( &$this, 'decrypt_aws_secret_key' ), 10, 3 );

	}

	/**
	 * Encrypt the aws secret key before it is saved in the database.
	 *
	 * @param string $new new.
	 * @param string $field field.
	 * @param string $old old.
	 */
	public function encrypt_aws_secret_key( $new, $field, $old ) {
		if ( ! empty( $new ) ) {
			return WPCD()->encrypt( $new );
		}
		return $new;
	}

	/**
	 * Decrypt the aws secret key before it is shown on the screen.
	 *
	 * @param string $meta meta.
	 * @param string $field field.
	 * @param string $saved saved.
	 */
	public function decrypt_aws_secret_key( $meta, $field, $saved ) {
		if ( ! empty( $meta ) ) {
			return WPCD()->decrypt( $meta );
		}
		return $meta;
	}

	/**
	 * Add a new tab to the settings page
	 *
	 * Filter hook: wpcd_settings_tabs
	 *
	 * @param array $tabs Array of tabs on the settings page.
	 *
	 * @return array $tabs New array of tabs on the settings page
	 */
	public function settings_tabs( $tabs ) {
		$new_tab = array( 'app-wordpress-app' => __( 'APP: WordPress - Settings', 'wpcd' ) );
		$tabs    = $tabs + $new_tab;
		return $tabs;
	}


	/**
	 * Add a new metaboxes to the settings page
	 *
	 * See the Metabox.IO website for documentation on
	 * the structure of the metabox settings array.
	 * https://docs.metabox.io/extensions/mb-settings-page/
	 *
	 * Filter hook: wpcd_settings_metaboxes
	 *
	 * @param array $metaboxes Array of metaboxes on the settings page.
	 *
	 * @return array $metaboxes New array of metaboxes on the settings page
	 */
	public function settings_metaboxes( $metaboxes ) {

		$metaboxes[] = array(
			'id'             => 'wordpress-app',
			'title'          => __( 'General WordPress App Settings', 'wpcd' ),
			'settings_pages' => 'wpcd_settings',
			'tab'            => 'app-wordpress-app',  // this is the top level tab on the setttings screen, not to be confused with the tabs inside a metabox as we're defining below!
		// List of tabs in the metabox, in one of the following formats:
		// 1) key => label.
		// 2) key => array( 'label' => Tab label, 'icon' => Tab icon ).
			'tabs'           => $this->metabox_tabs(),
			'tab_style'      => 'left',
			'tab_wrapper'    => true,
			'fields'         => apply_filters( 'wpcd_wordpress-app_settings_fields', $this->all_fields() ),

		);

		return $metaboxes;
	}

	/**
	 * Return a list of tabs that will go inside the metabox.
	 */
	public function metabox_tabs() {
		$tabs = array(
			'wordpress-app-general-wpadmin'      => array(
				'label' => 'General',
				'icon'  => 'dashicons-text',
			),
			'wordpress-app-servers'              => array(
				'label' => 'Servers',
				'icon'  => 'dashicons-align-full-width',
			),
			'wordpress-app-backup'               => array(
				'label' => 'Backup and Restore',
				'icon'  => 'dashicons-images-alt2',
			),
			'wordpress-app-fields-and-links'     => array(
				'label' => 'Fields & Links',
				'icon'  => 'dashicons-editor-unlink',
			),
			'wordpress-app-plugin-theme-updates' => array(
				'label' => 'Theme & Plugin Updates',
				'icon'  => 'dashicons-admin-plugins',
			),
			'wordpress-app-dns-cloudflare'       => array(
				'label' => 'DNS Integration: Cloudflare',
				'icon'  => 'dashicons-cloud',
			),
			'wordpress-app-email-notify'         => array(
				'label' => 'Email Notifications',
				'icon'  => 'dashicons-email',
			),
			'wordpress-app-slack-notify'         => array(
				'label' => 'Slack Notifications',
				'icon'  => 'dashicons-admin-comments',
			),
			'wordpress-app-zapier-notify'        => array(
				'label' => 'Zapier Notifications',
				'icon'  => 'dashicons-embed-generic',
			),
			'wordpress-app-color-settings'       => array(
				'label' => 'Styles',
				'icon'  => 'dashicons-color-picker',
			),
			'wordpress-app-email-gateway'        => array(
				'label' => 'Email Gateway',
				'icon'  => 'dashicons-email-alt2',
			),
			'wordpress-app-rest-api'             => array(
				'label' => 'Rest API',
				'icon'  => 'dashicons-rest-api',
			),

		/*
		'wordpress-app-scripts' => array(
			'label' => 'Scripts',
			'icon'  => 'dashicons-format-aside',
		),
		*/
		);

		return apply_filters( 'wpcd_wordpress-app_settings_tabs', $tabs );
	}

	/**
	 * Return an array that combines all fields that will go on all tabs.
	 */
	public function all_fields() {
		$general_fields = $this->general_fields();
		// Removing script fields for now since they're not being used.
		/* $script_fields	= $this->scripts_fields(); */
		$server_fields                = $this->server_fields();
		$backup_fields                = $this->backup_fields();
		$fields_and_links             = $this->fields_and_links();
		$theme_and_plugin_updates     = $this->theme_and_plugin_updates();
		$email_notification_fields    = $this->email_notification_fields();
		$slack_notification_fields    = $this->slack_notification_fields();
		$zapier_notification_fields   = $this->zapier_notification_fields();
		$button_color_settings_fields = $this->button_color_settings_fields();
		$email_gateway_load_defaults  = $this->email_gateway_load_defaults();
		$cf_dns_fields                = $this->cf_dns_fields();
		$rest_api_fields              = $this->rest_api_fields();
		$all_fields                   = array_merge( $general_fields, $server_fields, $backup_fields, $fields_and_links, $theme_and_plugin_updates, $email_notification_fields, $slack_notification_fields, $zapier_notification_fields, $button_color_settings_fields, $email_gateway_load_defaults, $cf_dns_fields, $rest_api_fields );
		return $all_fields;
	}

	/**
	 * Return array portion of field settings for use in the script fields tab.
	 */
	public function scripts_fields() {

		$fields = array(
			array(
				'id'   => 'wordpress_app_script_version',
				'type' => 'text',
				'name' => __( 'Version of scripts', 'wpcd' ),
				'desc' => __( '<b>For future use - not yet active</b> <em>You can set the version when you deploy a new site</em> The default is V1.  Updates to plugins that contain new scripts will NOT usually change this value so if you want to use new scripts on plugin updates, you should change this version number.', 'wpcd' ),
				'tab'  => 'wordpress-app-scripts',
			),
			array(
				'id'   => 'wordpress_app_commands_after_server_install',
				'type' => 'textbox',
				'name' => __( 'After provisioning commands', 'wpcd' ),
				'desc' => __( '<b>For future use - not yet active</b> Run these commands after the server has been provisioned.', 'wpcd' ),
				'tab'  => 'wordpress-app-scripts',
			),
		);

		return $fields;

	}

	/**
	 * Return array portion of field settings for use in the general fields tab.
	 */
	public function general_fields() {

		$fields = array(
			array(
				'type' => 'heading',
				'name' => __( 'Server Options', 'wpcd' ),
				'desc' => __( 'Server options specific to the WordPress app.', 'wpcd' ),
				'tab'  => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_default_os',
				'type'    => 'select',
				'name'    => __( 'Default OS', 'wpcd' ),
				'tooltip' => __( 'Select the default OS to be used when deploying a new WordPress server!', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
				'std'     => 'ubuntu2004lts',
				'options' => WPCD()->get_os_list(),
			),
			array(
				'id'      => 'wordpress_app_use_extended_server_name',
				'type'    => 'checkbox',
				'name'    => __( 'Override the server name?', 'wpcd' ),
				'tooltip' => __( 'Set the server name to a system generated name that includes the name entered by the admin. If unchecked, always use just the admin defined server name - admin MUST ensure that the server name is unique for certain cloud providers or server creation will fail!', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_server_tab_style',
				'type'    => 'select',
				'options' => array(
					'left'    => __( 'Vertical', 'wpcd' ),
					'default' => __( 'Horizontal', 'wpcd' ),
					'box'     => __( 'Boxed', 'wpcd' ),
				),
				'name'    => __( 'Tab Style For Server Detail Screen', 'wpcd' ),
				'tooltip' => __( 'The tabs on the server detail screen are vertical but you can switch them to a horizontal style.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_hide_notes_on_server_services_tab',
				'type'    => 'checkbox',
				'name'    => __( 'Hide Notes Column On Services Tab?', 'wpcd' ),
				'tooltip' => __( 'On the services tab we show a notes column.  For experienced admins this is not necessary - check this box to remove the unnecessary text from the screen.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_enable_bulk_delete_on_server_when_delete_protected',
				'type'    => 'checkbox',
				'name'    => __( 'Enable Bulk Trash Action for Deleted-protected Servers [Danger]', 'wpcd' ),
				'tooltip' => __( 'Enable the bulk trash option on the server list screen for those items that are delete protected. Usually the checkbox for the server is disabled for delete protected items - which means that other bulk options will not be available for DELETE PROTECTED servers either. Enable to overide this logic and allow bulk actions on DELETE PROTECTED items as well. However, doing so can allow your servers to be inadvertently deleted via the BULK TRASH operation.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_disable_bulk_delete_on_full_server_list',
				'type'    => 'checkbox',
				'name'    => __( 'Disable Bulk Trash Action', 'wpcd' ),
				'tooltip' => __( 'Disable the bulk delete option for all servers.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'type' => 'heading',
				'name' => __( 'App Options', 'wpcd' ),
				'desc' => __( 'Options specific to the WordPress app.', 'wpcd' ),
				'tab'  => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_tab_style',
				'type'    => 'select',
				'options' => array(
					'left'    => __( 'Vertical', 'wpcd' ),
					'default' => __( 'Horizontal', 'wpcd' ),
					'box'     => __( 'Boxed', 'wpcd' ),
				),
				'name'    => __( 'Tab Style For Site Detail Screen', 'wpcd' ),
				'tooltip' => __( 'The tabs on the site detail screen are vertical but you can switch them to a horizontal style.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_show_vnstat_in_app',
				'type'    => 'checkbox',
				'name'    => __( 'Show VNSTAT in the app screen?', 'wpcd' ),
				'tooltip' => __( 'VNSTAT provides network statistics data for the entire server. You can choose to show this data in the statistics tab on each site - if you do not have a need to secure sites between users on the same server.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_enable_bulk_delete_on_app_when_delete_protected',
				'type'    => 'checkbox',
				'name'    => __( 'Enable Bulk Trash Action for Delete-protected Sites [Danger]', 'wpcd' ),
				'tooltip' => __( 'Enable the bulk trash option on the app list screen for those items that are delete protected. Usually the checkbox for the app is disabled for delete protected items - which means that other bulk options will not be available for DELETE PROTECTED apps either. Enable to overide this logic and allow bulk actions on DELETE PROTECTED items as well. However, doing so can allow your apps to be inadvertently deleted via the BULK TRASH operation.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_disable_bulk_delete_on_full_app_list',
				'type'    => 'checkbox',
				'name'    => __( 'Disable Bulk Trash Action', 'wpcd' ),
				'tooltip' => __( 'Disable the bulk delete option for all apps.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
			array(
				'type' => 'heading',
				'name' => __( 'Labels', 'wpcd' ),
				'desc' => __( 'Label options specific to the WordPress app.', 'wpcd' ),
				'tab'  => 'wordpress-app-general-wpadmin',
			),
			array(
				'id'      => 'wordpress_app_show_label_in_lists',
				'type'    => 'checkbox',
				'name'    => __( 'Show the \'WordPress\' label in server and site lists?', 'wpcd' ),
				'tooltip' => __( 'If you are running multiple apps you will likely want to know which servers and apps are WordPress related.  If so, turn this on.', 'wpcd' ),
				'tab'     => 'wordpress-app-general-wpadmin',
			),
		);
		return $fields;

	}

	/**
	 * Array of fields used in the servers.
	 */
	public function server_fields() {

		$fields = array(
			array(
				'type' => 'heading',
				'name' => __( 'Server Setup Options', 'wpcd' ),
				'desc' => __( 'Services and actions to perform immediately after a server has been deployed.', 'wpcd' ),
				'tab'  => 'wordpress-app-servers',
			),
			array(
				'id'      => 'wordpress_app_servers_add_delete_protection',
				'type'    => 'checkbox',
				'name'    => __( 'Delete Protect New Servers?', 'wpcd' ),
				'tooltip' => __( 'Should deletion protection automatically be enabled on new servers?', 'wpcd' ),
				'tab'     => 'wordpress-app-servers',
			),
			array(
				'id'      => 'wordpress_app_servers_activate_callbacks',
				'type'    => 'checkbox',
				'name'    => __( 'Install Callbacks?', 'wpcd' ),
				'tooltip' => __( 'Turn this on to automatically install callbacks on all new servers - this is recommended.', 'wpcd' ),
				'tab'     => 'wordpress-app-servers',
			),
			array(
				'id'      => 'wordpress_app_servers_activate_backups',
				'type'    => 'checkbox',
				'name'    => __( 'Setup Backups?', 'wpcd' ),
				'tooltip' => __( 'Turn this on to automatically setup backups for all sites on new servers - this is recommended if you have configured AWS S3 defaults.', 'wpcd' ),
				'tab'     => 'wordpress-app-servers',
			),
			array(
				'id'      => 'wordpress_app_servers_activate_config_backups',
				'type'    => 'checkbox',
				'name'    => __( 'Setup Local Configuration Backups?', 'wpcd' ),
				'tooltip' => __( 'Turn this on to automatically setup 90 days of local backups for all critical configuration files on new servers - this is recommended.', 'wpcd' ),
				'tab'     => 'wordpress-app-servers',
			),
			array(
				'id'      => 'wordpress_app_servers_refresh_servers',
				'type'    => 'checkbox',
				'name'    => __( 'Refresh Services Status?', 'wpcd' ),
				'tooltip' => __( 'Refresh the status of services shown on the SERVICES tab of your new server.', 'wpcd' ),
				'tab'     => 'wordpress-app-servers',
			),
			array(
				'id'      => 'wordpress_app_servers_run_all_linux_updates',
				'type'    => 'checkbox',
				'name'    => __( 'Run All Linux Updates?', 'wpcd' ),
				'tooltip' => __( 'Most new servers have a lot of updates that need to be run overnight. You can turn this on to force the updates to run asap.  Note that this will chew up CPU cycles and cause your server to be slow for a bit. If you need to use your servers immediately do not enable this.', 'wpcd' ),
				'tab'     => 'wordpress-app-servers',
			),

		);

		return $fields;
	}

	/**
	 * Array of fields used in the fields and links tab.
	 */
	public function fields_and_links() {

		$fields = array(
			array(
				'id'   => 'wordpress_fields_and_links_heading_01',
				'type' => 'heading',
				'name' => __( 'Fields & Links', 'wpcd' ),
				'desc' => __( 'Show or hide certain fields specific to the WordPress app.', 'wpcd' ),
				'tab'  => 'wordpress-app-fields-and-links',
			),
			array(
				'id'      => 'wordpress_app_show_install_wp_link_in_server_list',
				'type'    => 'checkbox',
				'name'    => __( 'Show Install WP Link', 'wpcd' ),
				'tooltip' => __( 'Show an INSTALL WORDPRESS link under the Title column in the Server List', 'wpcd' ),
				'tab'     => 'wordpress-app-fields-and-links',
			),
			array(
				'id'      => 'wordpress_app_show_logs_dropdown_in_server_list',
				'type'    => 'checkbox',
				'name'    => __( 'Show Logs Drop-down', 'wpcd' ),
				'tooltip' => __( 'Show a dropdown of WordPress installation log attempts under the Title column in the Server List', 'wpcd' ),
				'tab'     => 'wordpress-app-fields-and-links',
			),

		);

		return $fields;
	}

	/**
	 * Array of fields used to store the default s3 backup settings.
	 */
	public function backup_fields() {

		$fields = array(
			array(
				'id'   => 'wordpress_app_backup_heading_01',
				'type' => 'heading',
				'name' => __( 'AWS S3 Credentials', 'wpcd' ),
				'desc' => __( 'Sites can be backed up to AWS S3.  These are the AWS credentials that will be used for all sites when backing up and restoring data.  If you need to, you can set different credentials for each server - you can do that in the CLOUD SERVERS screens.', 'wpcd' ),
				'tab'  => 'wordpress-app-backup',
			),
			array(
				'id'      => 'wordpress_app_aws_access_key',
				'type'    => 'text',
				'name'    => __( 'AWS Access Key ID', 'wpcd' ),
				'tooltip' => __( 'AWS Access Key ID', 'wpcd' ),
				'tab'     => 'wordpress-app-backup',
				'std'     => wpcd_get_option( 'wordpress_app_aws_access_key' ),
				'size'    => 60,
			),
			array(
				'id'      => 'wordpress_app_aws_secret_key',
				'type'    => 'text',
				'name'    => __( 'AWS Secret Key', 'wpcd' ),
				'tooltip' => __( 'AWS Secret Key', 'wpcd' ),
				'tab'     => 'wordpress-app-backup',
				'size'    => 60,
			),
			array(
				'id'      => 'wordpress_app_aws_bucket',
				'type'    => 'text',
				'name'    => __( 'AWS Bucket Name', 'wpcd' ),
				'tooltip' => __( 'AWS Bucket Name', 'wpcd' ),
				'tab'     => 'wordpress-app-backup',
				'std'     => wpcd_get_option( 'wordpress_app_aws_bucket' ),
				'size'    => 60,
			),
			array(
				'id'   => 'wordpress_app_backup_warning',
				'type' => 'custom_html',
				'std'  => __( 'Warning! If you are using our SELL SERVERS WITH WOOCOMMERCE premium option, do NOT set these defaults. Otherwise all servers, including your customer servers, will be able to get these. Since your customers might be able to log into their own servers, they will be able to view these credentials. Instead, set them on each server as needed.  See our WOOCOMMERCE documentation for more information or contact our support team with your questions.', 'wpcd' ),
				'tab'  => 'wordpress-app-backup',
			),
		);

		return $fields;

	}

	/**
	 * Array of fields used to get the theme & plugin update settings.
	 */
	public function theme_and_plugin_updates() {

		$fields = array(
			array(
				'id'   => 'wordpress_app_t_and_p_updates_heading_01',
				'type' => 'heading',
				'name' => __( 'Image Compare Service API', 'wpcd' ),
				'desc' => __( 'We use the https://htmlcsstoimage.com/ service to create and compare images taken of the site before and after the update. This helps us to determine if there were any significant changes to the site after the update is complete.  And, if there were, automatically roll back the changes. Enter your API credentials for the https://htmlcsstoimage.com/ service in this section.', 'wpcd' ),
				'tab'  => 'wordpress-app-plugin-theme-updates',
			),
			array(
				'id'      => 'wordpress_app_hcti_api_user_id',
				'type'    => 'text',
				'name'    => __( 'API User Id', 'wpcd' ),
				'tooltip' => __( 'htmlcsstoimage.com API User Id - this is not your htmlcsstoimage.com login or email address.', 'wpcd' ),
				'tab'     => 'wordpress-app-plugin-theme-updates',
				'std'     => wpcd_get_option( 'wordpress_app_hcti_api_user_id' ),
				'size'    => 60,
			),
			array(
				'id'      => 'wordpress_app_hcti_api_key',
				'type'    => 'text',
				'name'    => __( 'API Key', 'wpcd' ),
				'tooltip' => __( 'htmlcsstoimage.com API Key', 'wpcd' ),
				'tab'     => 'wordpress-app-plugin-theme-updates',
				'std'     => wpcd_get_option( 'wordpress_app_hcti_api_key' ),
				'size'    => 60,
			),
			array(
				'id'      => 'wordpress_app_tandc_updates_pixel_threshold',
				'type'    => 'number',
				'name'    => __( 'Max Pixel Variance', 'wpcd' ),
				'tooltip' => __( 'What is maximum number of pixels that are allowed to be different between before and after images? If the pixel variance exceeds this value the updates will be rolled back. ', 'wpcd' ),
				'tab'     => 'wordpress-app-plugin-theme-updates',
				'std'     => Max( 1, (int) wpcd_get_option( 'wordpress_app_tandc_updates_pixel_threshold' ) ),
				'size'    => 10,
				'min'     => -1,
				'max'     => 1000000,
			),

			array(
				'id'   => 'wordpress_app_t_and_p_updates_heading_02',
				'type' => 'heading',
				'name' => __( 'Excluded Plugins', 'wpcd' ),
				'desc' => __( 'Never update these plugins', 'wpcd' ),
				'tab'  => 'wordpress-app-plugin-theme-updates',
			),
			array(
				'id'      => 'wordpress_app_plugin_updates_excluded_list',
				'type'    => 'text',
				'name'    => __( 'Exclude These Plugins From Updates', 'wpcd' ),
				'desc'    => __( 'Enter the list of plugins separated by commas - eg: akismet,awesome-suppport, woocommerce', 'wpcd' ),
				'tooltip' => __( 'The name is usually the FOLDER in which the plugin is installed - you will not find this at the top of the main plugin file. ', 'wpcd' ),
				'tab'     => 'wordpress-app-plugin-theme-updates',
			),

			array(
				'id'   => 'wordpress_app_t_and_p_updates_heading_03',
				'type' => 'heading',
				'name' => __( 'Excluded Themes', 'wpcd' ),
				'desc' => __( 'Never update these Themes', 'wpcd' ),
				'tab'  => 'wordpress-app-plugin-theme-updates',
			),
			array(
				'id'      => 'wordpress_app_theme_updates_excluded_list',
				'type'    => 'text',
				'name'    => __( 'Exclude These Themes From Updates', 'wpcd' ),
				'desc'    => __( 'Enter the list of themes separated by commas - eg: ocean,beaver-builder,divi', 'wpcd' ),
				'tooltip' => __( 'The name is usually the FOLDER in which the theme is installed - you will not find this at the top of the main theme css file. ', 'wpcd' ),
				'tab'     => 'wordpress-app-plugin-theme-updates',
			),

		);

		return $fields;

	}

	/**
	 * Array of fields used to store the email notification text.
	 */
	public function email_notification_fields() {

		$fields = array(
			array(
				'id'   => 'wordpress_app_email_notify_heading',
				'type' => 'heading',
				'name' => __( 'Email Notification for user', 'wpcd' ),
				'desc' => __( 'This message is sent to a user when the user has configured a notification profile and a notification event matches the profile.', 'wpcd' ),
				'tab'  => 'wordpress-app-email-notify',
			),
			array(
				'id'   => 'wordpress_app_email_notify_subject',
				'type' => 'text',
				'name' => __( 'Subject', 'wpcd' ),
				'tab'  => 'wordpress-app-email-notify',
				'size' => 60,
			),
			array(
				'id'      => 'wordpress_app_email_notify_body',
				'type'    => 'wysiwyg',
				'name'    => __( 'Body', 'wpcd' ),
				'desc'    => __( 'Valid substitutions are: ##USERNAME##, ##FIRST_NAME##, ##LAST_NAME##, ##TYPE##, ##MESSAGE##, ##REFERENCE##, ##SERVERNAME## ##DOMAIN##, ##DATE##, ##TIME##, ##SERVERID##, ##SITEID##, ##IPV4##, ##PROVIDER##.', 'wpcd' ),
				'options' => array(
					'textarea_rows' => 12,
				),
				'tab'     => 'wordpress-app-email-notify',
				'size'    => 60,
			),
		);

		return $fields;

	}

	/**
	 * Array of fields used to store the slack notification text.
	 */
	public function slack_notification_fields() {

		$fields = array(
			array(
				'id'   => 'wordpress_app_slack_notify_heading',
				'type' => 'heading',
				'name' => __( 'Slack Notification for user', 'wpcd' ),
				'desc' => __( 'This message is pushed to a slack channel when the user has configured a notification profile and a notification event matches the profile.', 'wpcd' ),
				'tab'  => 'wordpress-app-slack-notify',
			),
			array(
				'id'      => 'wordpress_app_slack_notify_message',
				'type'    => 'wysiwyg',
				'name'    => __( 'Message', 'wpcd' ),
				'desc'    => __( 'Valid substitutions are: ##USERNAME##, ##FIRST_NAME##, ##LAST_NAME##, ##TYPE##, ##MESSAGE##, ##REFERENCE##, ##SERVERNAME## ##DOMAIN##, ##DATE##, ##TIME##, ##SERVERID##, ##SITEID##, ##IPV4##, ##PROVIDER##.', 'wpcd' ),
				'options' => array(
					'textarea_rows' => 12,
				),
				'tab'     => 'wordpress-app-slack-notify',
				'size'    => 60,
			),
		);

		return $fields;

	}

	/**
	 * Array of fields used to store the zapier notification text.
	 */
	public function zapier_notification_fields() {

		$fields = array(
			array(
				'id'   => 'wordpress_app_zapier_notify_heading',
				'type' => 'heading',
				'name' => __( 'Zapier Notification for user', 'wpcd' ),
				'desc' => __( 'This message is sent to Zapier when the user has configured a notification profile and a notification event matches the profile.', 'wpcd' ),
				'tab'  => 'wordpress-app-zapier-notify',
			),
			array(
				'id'      => 'wordpress_app_zapier_notify_message',
				'type'    => 'wysiwyg',
				'name'    => __( 'Message', 'wpcd' ),
				'desc'    => __( 'Valid substitutions are: ##USERNAME##, ##USERID##, ##USEREMAIL##, ##FIRST_NAME##, ##LAST_NAME##, ##TYPE##, ##MESSAGE##, ##REFERENCE##, ##SERVERNAME##, ##DOMAIN##, ##SERVERID##, ##SITEID#, ##IPV4##, ##PROVIDER##, ##DATE##, ##TIME##.', 'wpcd' ),
				'options' => array(
					'textarea_rows' => 12,
				),
				'tab'     => 'wordpress-app-zapier-notify',
				'size'    => 60,
			),
		);

		return $fields;

	}

	/**
	 * Array of fields used to store the color settings text.
	 */
	public function button_color_settings_fields() {

		$fields = array(
			array(
				'id'   => 'wordpress_app_button_color_heading',
				'type' => 'heading',
				'name' => __( 'Colors for the user notification shortcode', 'wpcd' ),
				'desc' => __( 'These settings are used to manage the color of the buttons shown when the user notification shortcode is used.', 'wpcd' ),
				'tab'  => 'wordpress-app-color-settings',
			),
			array(
				'name'          => 'Add New Button',
				'id'            => 'wordpress_app_add_new_button_color',
				'type'          => 'color',
				'alpha_channel' => true,
				'tab'           => 'wordpress-app-color-settings',
			),
			array(
				'name'          => 'Submit Button',
				'id'            => 'wordpress_app_submit_button_color',
				'type'          => 'color',
				'alpha_channel' => true,
				'tab'           => 'wordpress-app-color-settings',
			),
			array(
				'name'          => 'Update Button',
				'id'            => 'wordpress_app_update_button_color',
				'type'          => 'color',
				'alpha_channel' => true,
				'tab'           => 'wordpress-app-color-settings',
			),
			array(
				'name'          => 'Test Button',
				'id'            => 'wordpress_app_test_button_color',
				'type'          => 'color',
				'alpha_channel' => true,
				'tab'           => 'wordpress-app-color-settings',
			),

		);

		return $fields;

	}

	/**
	 * Array of fields used to store the email gateway load defaults settings.
	 */
	public function email_gateway_load_defaults() {

		/* Email Gateway */
		$eg_desc = __( 'Set default values you can use when setting up server level email gateways.', 'wpcd' );

		$fields = array(
			array(
				'type' => 'heading',
				'name' => __( 'EMAIL GATEWAY', 'wpcd' ),
				'desc' => $eg_desc,
				'tab'  => 'wordpress-app-email-gateway',
			),
			array(
				'id'      => 'wpcd_email_gateway_smtp_server',
				'type'    => 'text',
				'name'    => __( 'SMTP Server & Port', 'wpcd' ),
				'tooltip' => __( 'Enter the url/address for your outgoing email server - usually in the form of a subdomain.domain.com:port - eg: <i>smtp.ionos.com:587</i>.', 'wpcd' ),
				'tab'     => 'wordpress-app-email-gateway',
			),
			array(
				'id'      => 'wpcd_email_gateway_smtp_user',
				'type'    => 'text',
				'name'    => __( 'User Name', 'wpcd' ),
				'tooltip' => __( 'Your user id for connecting to the smtp server', 'wpcd' ),
				'tab'     => 'wordpress-app-email-gateway',
			),
			array(
				'id'      => 'wpcd_email_gateway_smtp_password',
				'type'    => 'text',
				'name'    => __( 'Password', 'wpcd' ),
				'tooltip' => __( 'Your password for connecting to the smtp server', 'wpcd' ),
				'tab'     => 'wordpress-app-email-gateway',
			),
			array(
				'id'      => 'wpcd_email_gateway_smtp_domain',
				'type'    => 'text',
				'name'    => __( 'From Domain', 'wpcd' ),
				'tooltip' => __( 'The default domain for sending messages', 'wpcd' ),
				'tab'     => 'wordpress-app-email-gateway',
			),
			array(
				'id'      => 'wpcd_email_gateway_smtp_hostname',
				'type'    => 'text',
				'name'    => __( 'FQDN Hostname', 'wpcd' ),
				'tooltip' => __( 'FQDN for the server. Some SMTP servers will require this to be a working domain name (example: server1.myblog.com)', 'wpcd' ),
				'tab'     => 'wordpress-app-email-gateway',
			),
			array(
				'id'      => 'wpcd_email_gateway_smtp_note',
				'type'    => 'textarea',
				'name'    => __( 'Brief Note', 'wpcd' ),
				'tooltip' => __( 'Just a note in case you need a reminder about the details of this email gateway setup.', 'wpcd' ),
				'tab'     => 'wordpress-app-email-gateway',
			),
		);

		return $fields;

	}

	/**
	 * Return array portion of field settings for use in the Cloudflare DNS section of the wc sites tab.
	 */
	public function cf_dns_fields() {

		$fields = array(
			array(
				'type' => 'heading',
				'name' => __( 'Automatic DNS via CloudFlare', 'wpcd' ),
				'desc' => __( 'When a site is provisioned it can be automatically assigned a subdomain based on the domain specified below. If this domain is setup in cloudflare, we can automatically point the IP address to the newly created subdomain.', 'wpcd' ),
				'tab'  => 'wordpress-app-dns-cloudflare',
			),
			array(
				'id'         => 'wordpress_app_dns_cf_temp_domain',
				'type'       => 'text',
				'name'       => __( 'Temporary Domain', 'wpcd' ),
				'tooltip'    => __( 'The domain under which a new site\'s temporary sub-domain will be created.', 'wpcd' ),
				'desc'       => __( 'This needs to be a short domain - max 19 chars.' ),
				'size'       => '20',
				'attributes' => array(
					'maxlength' => '19',
				),
				'tab'        => 'wordpress-app-dns-cloudflare',
			),
			array(
				'id'      => 'wordpress_app_dns_cf_enable',
				'type'    => 'checkbox',
				'name'    => __( 'Enable Cloudflare Auto DNS', 'wpcd' ),
				'tooltip' => __( 'Turn this on so that when a new site is being created, the newly created subdomain can be automatically added to your CloudFlare configuration.', 'wpcd' ),
				'tab'     => 'wordpress-app-dns-cloudflare',
			),
			array(
				'id'   => 'wordpress_app_dns_cf_zone_id',
				'type' => 'text',
				'name' => __( 'Zone ID', 'wpcd' ),
				'desc' => __( 'Your zone id can be found in the lower right of the CloudFlare overview page for your domain', 'wpcd' ),
				'size' => 35,
				'tab'  => 'wordpress-app-dns-cloudflare',
			),
			array(
				'id'   => 'wordpress_app_dns_cf_token',
				'type' => 'text',
				'name' => __( 'API Security Token', 'wpcd' ),
				'desc' => __( 'Generate a new token for your zone by using the GET YOUR API TOKEN link located in the lower right of the CloudFlare overview page for your domain.  This should use the EDIT ZONE DNS api token template.', 'wpcd' ),
				'size' => 35,
				'tab'  => 'wordpress-app-dns-cloudflare',
			),
			array(
				'id'      => 'wordpress_app_dns_cf_disable_proxy',
				'type'    => 'checkbox',
				'name'    => __( 'Disable Cloudflare Proxy', 'wpcd' ),
				'tooltip' => __( 'All new subdomains added to CloudFlare will automatically be proxied (orange flag turned on.) Check this box to turn off this behavior.', 'wpcd' ),
				'tab'     => 'wordpress-app-dns-cloudflare',
			),
			array(
				'id'      => 'wordpress_app_dns_cf_auto_delete',
				'type'    => 'checkbox',
				'name'    => __( 'Auto Delete DNS Entry', 'wpcd' ),
				'tooltip' => __( 'Should we attempt to delete the DNS entry for the domain at cloudflare when a site is deleted?', 'wpcd' ),
				'tab'     => 'wordpress-app-dns-cloudflare',
			),          // This one probably should be moved to it's own tab once we get more than on DNS provider.
			array(
				'id'      => 'wordpress_app_auto_issue_ssl',
				'type'    => 'checkbox',
				'name'    => __( 'Automatically Issue SSL', 'wpcd' ),
				'tooltip' => __( 'If DNS was automatically updated after a new site is provisioned, attempt to get an SSL certificate from LETSENCRYPT?', 'wpcd' ),
				'tab'     => 'wordpress-app-dns-cloudflare',
			),
		);
		return $fields;
	}

	/**
	 * Return array portion of field settings for use in rest API tab.
	 */
	public function rest_api_fields() {

		$fields = array(
			array(
				'type' => 'heading',
				'name' => __( 'REST API [Beta]', 'wpcd' ),
				'desc' => __( 'Activate the REST API', 'wpcd' ),
				'tab'  => 'wordpress-app-rest-api',
			),
			array(
				'id'   => 'wordpress_app_rest_api_enable',
				'type' => 'checkbox',
				'name' => __( 'Enable the REST API', 'wpcd' ),
				'tab'  => 'wordpress-app-rest-api',
			),
		);
		return $fields;
	}

}
