<?php
/**
 * Outputs a custom settings view under "Admin Access" on the Members plugin
 * settings page.
 *
 * @package MembersAccessControl
 * @author GrayDigitalGroup
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace Members\AddOns\MembersAccessControl;

use Members\Admin\View;

/**
 * Sets up and handles the general settings view.
 *
 * @since  1.0.0
 * @access public
 */
class View_Settings extends View {

	/**
	 * Registers the plugin settings.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	function register_settings() {

		// Register the setting.
		register_setting( 'members_access_control_settings', 'members_access_control_settings', array( $this, 'validate_settings' ) );

		/* === Settings Sections === */

		add_settings_section( 'general', esc_html__( 'Access Control', 'members' ), array( $this, 'section_general' ), access_control_addon()->namespace . '/settings' );

		/* === Settings Fields === */

		add_settings_field( 'thank_you', esc_html__( 'Member Pages', 'members' ), array( $this, 'field_thank_you' ), access_control_addon()->namespace . '/settings', 'general' );

		//add_settings_field( 'account', esc_html__( 'Account Page', 'members' ), array( $this, 'field_account' ), access_control_addon()->namespace . '/settings', 'general' );

		add_settings_field( 'login', '', array( $this, 'field_login' ), access_control_addon()->namespace . '/settings', 'general' );

		add_settings_field( 'password_lost', '', array( $this, 'field_pw_lost' ), access_control_addon()->namespace . '/settings', 'general' );

		add_settings_field( 'password_reset', '', array( $this, 'field_pw_reset' ), access_control_addon()->namespace . '/settings', 'general' );

		add_settings_field( 'redirect_on_unauthorized', esc_html__( 'Unauthorized Access', 'members' ), array( $this, 'field_unauthorized' ), access_control_addon()->namespace . '/settings', 'general' );

		add_settings_field( 'login_redirect', esc_html__( 'Login &amp; Logout', 'members' ), array( $this, 'field_login_redirect' ), access_control_addon()->namespace . '/settings', 'general' );

		add_settings_field( 'logout_redirect', '', array( $this, 'field_logout_redirect' ), access_control_addon()->namespace . '/settings', 'general' );

		add_settings_field( 'force_login', '', array( $this, 'field_force_login' ), access_control_addon()->namespace . '/settings', 'general' );

	}

	/**
	 * Validates the plugin settings.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  array  $input
	 * @return array
	 */
	function validate_settings( $settings ) {
		$mbrac_options = new MbrAC_Options( $settings );

		return apply_filters( access_control_addon()->namespace . '/validate_settings', (array)$mbrac_options );
	}

	/**
	 * Role/Caps section callback.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function section_general() { ?>

		<p class="description">
			<?php esc_html_e( 'Set the core membership pages and redirect urls.', 'members' ); ?>
		</p>
	<?php }

	/**
	 * Outputs the redirect URL after login field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_login_redirect() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

		<p>
			<label>
				<?php esc_html_e( 'Url to redirect users to after login:', 'members' ); ?>

				<input type="text" name="members_access_control_settings[<?php echo $mbrac_options->login_redirect_url_str; ?>]" value="<?php echo esc_attr( $mbrac_options->login_redirect_url ); ?>" />
			</label>
		</p>
	<?php }

	/**
	 * Outputs the redirect URL after logout field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_logout_redirect() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

	<p>
		<label>
			<?php esc_html_e( 'Url to redirect users to after logout:', 'members' ); ?>

			<input type="text" name="members_access_control_settings[<?php echo $mbrac_options->logout_redirect_url_str; ?>]" value="<?php echo esc_attr( $mbrac_options->logout_redirect_url ); ?>" />
		</label>
	</p>
	<?php }

	/**
	 * Outputs the redirect URL after logout field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_thank_you() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

	<p>
		<label>
			<?php esc_html_e( 'Member Thank You Page:', 'members' ); ?>

			<?php MbrAC_Utils::wp_pages_dropdown( 'members_access_control_settings[' . $mbrac_options->thank_you_page_id_str . ']', $mbrac_options->thank_you_page_id ); ?>
		</label>
	</p>
	<?php }

	/**
	 * Outputs the redirect URL after logout field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_account() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

	<p>
		<label>
			<?php esc_html_e( 'Member Account Page:', 'members' ); ?>

			<?php MbrAC_Utils::wp_pages_dropdown( 'members_access_control_settings[' . $mbrac_options->thank_you_page_id_str . ']', $mbrac_options->thank_you_page_id ); ?>
		</label>
	</p>
	<?php }

	/**
	 * Outputs the redirect URL after logout field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_login() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

	<p>
		<label>
			<?php esc_html_e( 'Member Login Page:', 'members' ); ?>

			<?php MbrAC_Utils::wp_pages_dropdown( 'members_access_control_settings[' . $mbrac_options->login_page_id_str . ']', $mbrac_options->login_page_id ); ?>
		</label>
	</p>
	<?php }

	/**
	 * Outputs the URL for password lost field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_pw_lost() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

	<p>
		<label>
			<?php esc_html_e( 'Password Lost Page:', 'members' ); ?>

			<?php MbrAC_Utils::wp_pages_dropdown( 'members_access_control_settings[' . $mbrac_options->password_lost_id_str . ']', $mbrac_options->password_lost_page_id ); ?>
		</label>
	</p>
	<?php }

	/**
	 * Outputs the URL for password reset field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_pw_reset() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

	<p>
		<label>
			<?php esc_html_e( 'Password Reset Page:', 'members' ); ?>

			<?php MbrAC_Utils::wp_pages_dropdown( 'members_access_control_settings[' . $mbrac_options->password_reset_id_str . ']', $mbrac_options->password_reset_page_id ); ?>
		</label>
	</p>
	<?php }

	/**
	 * Outputs the redirect URL after logout field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_unauthorized() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

		<p>
			<label>
				<input type="checkbox" name="members_access_control_settings[<?php echo $mbrac_options->redirect_on_unauthorized_str ?>]" <?php echo ($mbrac_options->redirect_on_unauthorized) ? " checked" : "" ?> />
				<?php esc_html_e( 'Redirect unauthorized visitors to a specific URL', 'members' ); ?>
			</label>
		</p>
		<div class="options-block <?php echo $mbrac_options->unauthorized_redirect_url_str; ?>">
			<p>
				<label>
					<?php esc_html_e( 'URL to direct unauthorized visitors to:', 'members' ); ?>

					<input type="text" name="members_access_control_settings[<?php echo $mbrac_options->unauthorized_redirect_url_str; ?>]" value="<?php echo esc_attr( $mbrac_options->unauthorized_redirect_url ); ?>" />
				</label>
			</p>
		</div>
	<?php
	}

	/**
	 * Outputs the redirect URL after logout field.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function field_force_login() {
		$mbrac_options = MbrAC_Options::fetch();
		?>

	<p>
		<label>
			<input type="checkbox" name="members_access_control_settings[<?php echo $mbrac_options->force_login_page_url_str ?>]" <?php echo ($mbrac_options->force_login_page_url) ? " checked" : "" ?> />
			<?php esc_html_e( 'Use Members login page URL', 'members' ); ?>
		</label>
	</p>
	<?php }

	/**
	 * Renders the settings page.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function template() { ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'members_access_control_settings' ); ?>
			<?php do_settings_sections( access_control_addon()->namespace . '/settings' ); ?>
			<?php submit_button( esc_attr__( 'Update Settings', 'members' ), 'primary' ); ?>
		</form>

	<?php }

	public function enqueue() {
		wp_enqueue_script( 'member_access_control_options', access_control_addon()->uri . 'js/admin-settings.js', array( 'jquery-core' ), '1.0', true );
		wp_enqueue_style( 'member_access_control_options', access_control_addon()->uri . 'css/admin-options.css', '1.0', true );
	}
}