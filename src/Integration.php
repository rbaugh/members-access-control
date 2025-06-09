<?php
/**
 * Integration Class.
 *
 * Integrates the plugin with the Members plugin.
 *
 * @package   MembersAccessControl
 * @author    GrayDigitalGroup
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace Members\AddOns\MembersAccessControl;

use function members_register_cap;

/**
 * Integration component class.
 *
 * @since  1.0.0
 * @access public
 */
class Integration {

	/**
	 * For use with WP cache
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	protected $status = '';

	/**
	 * Bootstraps the component.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function boot() {
		$this->status = access_control_addon()->namespace . '_status';

		require_once access_control_addon()->dir . 'src/Options.php';
		require_once access_control_addon()->dir . 'src/Utilities.php';

		// Actions
		add_action( 'template_redirect', array( $this, 'check_access' ) );
		add_action( 'members_register_settings_views', array( $this, 'register_views' ) );
		// add_action( 'add_meta_boxes', array( $this, 'remove_member_meta_boxes' ), 99 );
		add_action( 'wp_logout', array( $this, 'logout_redirect_override' ), 99999, 1 );

		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );

		// Handlers for form posting actions
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
		add_action( 'wp_loaded', array( $this, 'do_password_lost' ), 20 );
		// add_action( 'wp_login_failed', array( $this, 'wp_login_failed' ), 10, 2 );

		// Profile form fields
		add_action( 'show_user_profile', array( $this, 'use_profile_field' ), 9, 1 );
		add_action( 'edit_user_profile', array( $this, 'use_profile_field' ), 9, 1 );
		add_action( 'personal_options_update', array( $this, 'user_profile_field_save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'user_profile_field_save' ) );

		// Filters
		add_filter( 'init', array( $this, 'override_wp_login_url_init' ) );
		add_filter( 'login_form_bottom', array( $this, 'login_form_bottom' ), 10, 2 );
		add_filter( 'login_form_top', array( $this, 'login_form_top' ), 10, 2 );
		add_filter( 'login_form_defaults', array( $this, 'login_form_defaults' ) );
		add_filter( 'members_check_parent_post_permission', array( $this, 'members_check_parent_post_permission' ), 10, 3 );
		add_filter( 'admin_email_check_interval', '__return_zero' );
		add_filter( 'lostpassword_url', array( $this, 'override_lostpassword_url' ), 10, 1 );
		add_filter( 'members_is_private_page', array( $this, 'members_is_private_page' ), 10, 1 );

		// Other customizations
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 );

		add_shortcode( 'mbr-login-form', array( $this, 'member_login_form' ) );
		add_shortcode( 'mbr-password-lost-form', array( $this, 'render_password_lost_form' ) );
		add_shortcode( 'mbr-password-reset-form', array( $this, 'render_password_reset_form' ) );

		add_filter( 'login_url', array( $this, 'override_wp_login_url' ), 999999, 2 );
	}

	public function members_check_parent_post_permission( $check_parent, $post_id, $user_id ) {
		return ( 9410 === (int) $post_id ) ? false : $check_parent;
	}

	/**
	 * Add check on page to see if user can access or not
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function check_access() {
		if ( ! is_admin() ) {
			global $post;

			// Prevents us double matching a URI and causing a redirect loop.
			if ( isset( $_GET['action'] ) && 'mbrac_unauthorized' === $_GET['action'] ) {
				return;
			}

			$uri         = urldecode( $_SERVER['REQUEST_URI'] );
			$mbr_options = MbrAC_Options::fetch();
			$delim       = MbrAC_Utils::get_param_delimiter_char( $mbr_options->unauthorized_redirect_url );

			// If this is not a singular post item/page exit with current template.
			if ( ! is_singular() || ! is_object( $post ) ) {
				return;
			}

			$user          = wp_get_current_user();
			$can_user_view = members_can_user_view_post( $user->ID, $post->ID );

			if ( $can_user_view ) {
				return;
			}

			if ( ! is_user_logged_in() || empty( $user ) || ( $user->ID === 0 ) ) {
				// Page requires permissions so we need to force a login.
				$redirect_url = $mbr_options->login_page_url( 'action=mbrac_unauthorized&redirect_to=' . urlencode( $uri ) );
			} elseif ( function_exists( 'members_get_post_access_message' ) && ! empty( members_get_post_access_message( $post->ID ) ) ) {
				// Post has a custom error message.
				return;
			} else {
				// User does not have the permitted roles tto view this post/page.
				$redirect_url = "{$mbr_options->unauthorized_redirect_url}{$delim}action=mbrac_unauthorized&redirect_to=" . urlencode( $uri );
			}
			MbrAC_Utils::wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Function for determining whether a page should be public even though we're in private
	 * site mode.  Plugin devs can filter this to make specific pages public.
	 *
	 * @param bool $is_private The parent bool value.
	 * @return bool
	 */
	public function members_is_private_page( $is_private ) {
		global $post;
		$mbr_options = MbrAC_Options::fetch();

		// Check if current page is the login, password rest, or forgot password pages.
		if ( $post->ID === $mbr_options->login_page_id || $post->ID === $mbr_options->password_lost_page_id || $post->ID === $mbr_options->password_reset_page_id ) {
			$is_private = false;
		}
		return $is_private;
	}

	/**
	 * Registers custom settings views with the Members plugin.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object $manager
	 * @return void
	 */
	function register_views( $manager ) {

		// Bail if not on the settings screen.
		if ( 'members-settings' !== $manager->name ) {
			return;
		}

		require_once access_control_addon()->dir . 'admin/view-settings.php';

		// Register a view for the plugin settings.
		$manager->register_view(
			new View_Settings(
				'members_access_control',
				array(
					'label'    => esc_html__( 'Access Control', 'members' ),
					'priority' => 15,
				)
			)
		);
	}

	/**
	 * Removes the members custom permissions advanced metabox when using Gutenberg
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function remove_member_meta_boxes( $post_type ) {
		global $wp_meta_boxes;
		$type = get_post_type_object( $post_type );

		if ( $type->show_in_rest && post_type_supports( $post_type, 'editor' ) ) {
			remove_meta_box( 'members-cp', $post_type, 'advanced' );
		}
	}

	// This needs to be done in init as before then it seems to cause conflicts with Shield Security plugin
	public function override_wp_login_url_init() {
		add_filter( 'login_url', array( $this, 'override_wp_login_url' ), 999999, 2 );
		add_filter( 'authenticate', array( $this, 'login_authenticate' ), 30, 3 );

		add_action( 'login_form_login', array( $this, 'redirect_from_wp_login' ), 999999 );
	}

	// Override the default WordPress login url
	public function override_wp_login_url( $url, $redirect_to ) {
		$mbrac_options = MbrAC_Options::fetch();
		if ( empty( $redirect_to ) ) {
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
		}

		if ( false !== strpos( $redirect_to, '==' ) ) {
			$redirect_to = urldecode( str_replace( '==', '%3D%3D', $redirect_to ) );
		}

		$redirect_to = urldecode( $redirect_to ); // might not be urlencoded, but let's do this just in case before we call urlencode below

		if ( ! $mbrac_options->force_login_page_url ) {
			return $url;
		}

		if ( ! empty( $redirect_to ) ) {
			$new_login_url = $mbrac_options->login_page_url( 'redirect_to=' . urlencode( $redirect_to ) );
		} else {
			$new_login_url = $mbrac_options->login_page_url();
		}

		return $new_login_url;
	}

	/**
	 * An action that fires before a specified login form (login) action.
	 *
	 * @return void
	 */
	public function redirect_from_wp_login() {
		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;
			if ( false !== strpos( $redirect_to, '==' ) ) {
				$redirect_to = urldecode( str_replace( '==', '%3D%3D', $redirect_to ) );
			}

			if ( is_user_logged_in() ) {
				wp_redirect( urlencode( $redirect_to ) );
				exit;
			}

			$login_url = wp_login_url();

			if ( ! empty( $redirect_to ) ) {
				$login_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $login_url );
			}

			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Checks for login error and adds parameters to pass along to the form if needed.
	 *
	 * @param object $user     The user object that contains the errors if present.
	 * @param string $username The username used for the login.
	 * @param string $password The password used for the login.
	 *
	 * @return object|redirect
	 */
	public function login_authenticate( $user, $username, $password ) {
		if ( is_wp_error( $user ) ) {
			$match_errors = array( 'authentication_failed', 'invalid_email', 'invalid_username', 'invalid_password', 'invalidcombo', 'email' );
			$bad_login    = false;
			foreach ( $user->errors as $key => $value ) {
				if ( in_array( $key, $match_errors ) ) {
					unset( $user->errors[ $key ] );
					$bad_login = true;
				}
			}

			if ( true === $bad_login ) {
				$error_msg                             = sprintf( '<strong>Error</strong>: The username or password you entered was incorrect. <a href="%s">Lost your password?</a>', $this->override_lostpassword_url() );
				$user->errors['authentication_failed'] = array(
					$error_msg,
				);
			}
			return $user;

			$redirect_url = wp_get_referer();
			if ( empty( $redirect_url ) ) {
				foreach ( $user->errors as $key => $error ) {
					if ( in_array( $key, array( 'empty_password', 'empty_username' ) ) ) {
						unset( $user->errors[ $key ] );
						$user->errors[ 'custom_' . $key ] = $error;
					}
				}
			}
		}

		if ( $user && property_exists( $user, 'ID' ) ) {
			$disabled = get_user_meta( $user->ID, 'members_disable_user', true );
			if ( '1' === $disabled ) {
				$bypass_disabled = false;
				/**
				 * Filters whether to bypass the login.
				 *
				 * A boolean is returned specifying whether to bypass the disabled user error.
				 *
				 * @param bool    $bypass_disabled Bool indicating whether or not to bypass the disabled block.
				 * @param WP_User $user            The WP User instance.
				 */
				$bypass_disabled = apply_filters( 'members_disabled_user', $bypass_disabled, $user );
				if ( ! $bypass_disabled ) {
					$error_msg = '<strong>Error</strong>: The account you are trying to access has been disabled or no longer exists. Please contact membership staff for assistance.';
					$user      = new \WP_Error( 'disabled_account', __( $error_msg ) );
				}
			}
		}

		return $user;
	}

	/**
	 * Alters the default logout redirect
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function logout_redirect_override( $user_id ) {
		$mbrac_options              = MbrAC_Options::fetch();
		$members_overwrite_redirect = apply_filters( 'members_override_redirect', false, $user_id );

		if ( ! $members_overwrite_redirect && isset( $mbrac_options->logout_redirect_url ) && ! empty( $mbrac_options->logout_redirect_url ) ) {
			MbrAC_Utils::wp_redirect( $mbrac_options->logout_redirect_url );
			exit;
		}
	}

	/**
	 * Redirects the user to the custom "Forgot your password?" page instead of
	 * wp-login.php?action=lostpassword.
	 */
	public function redirect_to_custom_lostpassword() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// if ( is_user_logged_in() ) {
			// $this->redirect_logged_in_user();
			// exit;
			// }
			$mbrac_options = MbrAC_Options::fetch();
			wp_redirect( $mbrac_options->password_lost_page_url() );
			exit;
		}
	}

	/**
	 * Gets the custom lost password url and returns to WP filter hook.
	 *
	 * @return string The custom lost password url if set.
	 */
	public function override_lostpassword_url() {
		$mbrac_options = MbrAC_Options::fetch();
		return $mbrac_options->password_lost_page_url();
	}

	/**
	 * Redirects to the custom password reset page, or the login page
	 * if there are errors.
	 */
	public function redirect_to_custom_password_reset() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			$mbrac_options = MbrAC_Options::fetch();

			// Verify key / login combo
			$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( $mbrac_options->login_page_url( 'login=expiredkey' ) );
				} else {
					wp_redirect( $mbrac_options->login_page_url( 'login=invalidkey' ) );
				}
				exit;
			}

			$mbrac_queries = array();
			parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ), $mbrac_queries );

			$redirect_url = $mbrac_options->password_reset_page_url();
			foreach ( $mbrac_queries as $key => $value ) {
				$redirect_url = add_query_arg( esc_attr( $key ), esc_attr( $value ), $redirect_url );
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Outputs a login form with specific redirect_to paramater.
	 *
	 * @param array  $atts    The attributes for the shortcode.
	 * @param string $content The extra content from the shortcode.
	 *
	 * @return string
	 */
	public function member_login_form( $atts, $content ) {
		$mbrac_options = MbrAC_Options::fetch();
		$login_page_id = ( ! empty( $mbrac_options->login_page_id ) && $mbrac_options->login_page_id > 0 ) ? $mbrac_options->login_page_id : 0;

		$defaults = array(
			'show_forgot_password' => false,
		);

		$atts = shortcode_atts( $defaults, $atts, 'members' );

		// Initially set redirect_to to the default
		$redirect_to = $mbrac_options->login_redirect_url;

		// if redirect_to isset then set it to the query param
		if ( isset( $_REQUEST['redirect_to'] ) && ! empty( $_REQUEST['redirect_to'] ) ) {
			$redirect_to = urldecode( $_REQUEST['redirect_to'] );
			// Security fix. Restrict redirect_to param to safe URLs PT#154812459
			$redirect_to = wp_validate_redirect( $redirect_to, apply_filters( 'wp_safe_redirect_fallback', home_url(), 302 ) );
		}

		// if we're on a page other than the login page and we're in a shortcode
		if ( ( ! isset( $_REQUEST['redirect_to'] ) || empty( $_REQUEST['redirect_to'] ) ) && ! is_page( $login_page_id ) ) {
			$redirect_to = urldecode( $_SERVER['REQUEST_URI'] );
		}
		do_action( 'login_enqueue_scripts' );
		return wp_login_form(
			array(
				'echo'                 => false,
				'redirect'             => $redirect_to,
				'show_forgot_password' => $atts['show_forgot_password'],
			)
		);
	}

	/**
	 * Sets custom login form text.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of login form arguments
	 *
	 * @return string
	 */
	public function login_form_top( $content, $args ) {
		if ( isset( $_REQUEST['fail_reason'] ) && ! empty( $_REQUEST['fail_reason'] ) ) {
			$content .= '<span class="login-error"><strong>' . __( 'ERROR', 'members' ) . '</strong>: ';
			switch ( $_REQUEST['fail_reason'] ) {
				case 'missing_login_params':
					$content .= 'Login Credentials Missing';
					break;
				default:
					$content .= 'Failed Login';
					break;
			}
			$content .= '</span>';
		}

		if ( isset( $_REQUEST['checkemail'] ) && ( 'confirm' === $_REQUEST['checkemail'] ) ) {
			$content     .= '<p class="login-info callout warning">';
				$content .= __( 'You should receive an email if an account is found matching the email address supplied. Please check your spam folder as well for the email.', 'members' );
			$content     .= '</p>';
		}

		if ( isset( $_REQUEST['password'] ) && ( 'changed' === $_REQUEST['password'] ) ) {
			$content     .= '<p class="login-info callout primary">';
				$content .= __( 'Your password has been changed. You can sign in now.', 'members' );
			$content     .= '</p>';
		}

		if ( isset( $_REQUEST['username'] ) && ! empty( $_REQUEST['username'] ) ) {
			$args['value_username'] = $_REQUEST['username'];
		}

		return $content;
	}

	/**
	 * Sets the content to display at the bottom of the login form.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of login form arguments.
	 */
	public function login_form_bottom( $content, $args ) {
		if ( array_key_exists( 'show_forgot_password', $args ) && null !== filter_var( $args['show_forgot_password'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) && true === boolval( $args['show_forgot_password'] ) ) {
			$mbrac_options = MbrAC_Options::fetch();

			$content .= sprintf( '<p class="login-forgot-password"><a href="%s">Forgot Password</a></p>', $mbrac_options->password_lost_page_url() );
		}
		return $content;
	}

	/**
	 * Alters login form arguments.
	 *
	 * @param array $args The dfault arguments for the form.
	 *
	 * @return array
	 */
	public function login_form_defaults( $args ) {
		$defaults = array(
			'show_forgot_password' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( isset( $_REQUEST['fail_reason'] ) && ! empty( $_REQUEST['fail_reason'] ) && isset( $_REQUEST['username'] ) && ! empty( $_REQUEST['username'] ) ) {
			$args['value_username'] = $_REQUEST['username'];
		}

		return $args;
	}

	/**
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param  array  $attributes  Shortcode attributes.
	 * @param  string $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes         = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'members' );
		} else {
			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] [] = $this->get_error_message( $error_code );
				}
			}

			return $this->get_template_html( 'password_lost_form', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param  array  $attributes  Shortcode attributes.
	 * @param  string $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes         = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'members' );
		} elseif ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
			$mbrac_queries = array();
			parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ), $mbrac_queries );

			foreach ( $mbrac_queries as $key => $value ) {
				$attributes[ esc_attr( $key ) ] = esc_attr( $value );
			}

			// Error messages
			$errors = array();
			if ( isset( $_REQUEST['error'] ) ) {
				$error_codes = explode( ',', $_REQUEST['error'] );

				foreach ( $error_codes as $code ) {
					$errors [] = $this->get_error_message( $code );
				}
			}
			$attributes['errors'] = $errors;

			return $this->get_template_html( 'password_reset_form', $attributes );
		} else {
			return __( 'Invalid password reset link.', 'members' );
		}
	}

	/**
	 * Renders the contents of the given template to a string and returns it.
	 *
	 * @param string $template_name The name of the template to render (without .php)
	 * @param array  $attributes    The PHP variables for the template
	 *
	 * @return string               The contents of the template.
	 */
	private function get_template_html( $template_name, $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}

		ob_start();

		do_action( 'personalize_login_before_' . $template_name );

		require access_control_addon()->dir . 'templates/' . $template_name . '.php';

		do_action( 'personalize_login_after_' . $template_name );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Initiates password reset.
	 */
	public function do_password_lost() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['mbr_lost_password'], $_REQUEST['mbr-lost-password-nonce'] ) ) {
			$nonce_value = $this->wc_get_var( $_REQUEST['mbr-lost-password-nonce'], $this->wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'lost_password' ) ) {
				return;
			}

			if ( ! isset( $_POST['user_login'] ) || empty( $_POST['user_login'] ) ) {
				return;
			}

			try {
				retrieve_password( $_POST['user_login'] );
			} catch (\Exception $ex) {}
			$mbrac_options = MbrAC_Options::fetch();

			$redirect_url = $mbrac_options->login_page_url();
			$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
			if ( ! empty( $_REQUEST['redirect_to'] ) ) {
				$redirect_url = $_REQUEST['redirect_to'];
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
	 *
	 * @since  3.2.0
	 * @param  mixed  $var     Variable.
	 * @param  string $default Default value.
	 * @return mixed
	 */
	public function wc_get_var( &$var, $default = null ) {
		return isset( $var ) ? $var : $default;
	}

	/**
	 * Resets the user's password if the password reset form was submitted.
	 */
	public function do_password_reset() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$rp_key   = $_REQUEST['rp_key'];
			$rp_login = $_REQUEST['rp_login'];

			$user          = check_password_reset_key( $rp_key, $rp_login );
			$mbrac_options = MbrAC_Options::fetch();

			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( $mbrac_options->login_page_url( 'login=expiredkey' ) );
				} else {
					wp_redirect( $mbrac_options->login_page_url( 'login=invalidkey' ) );
				}
				exit;
			}

			if ( isset( $_POST['pass1'] ) ) {
				if ( $_POST['pass1'] != $_POST['pass2'] ) {
					// Passwords don't match
					$redirect_url = $mbrac_options->password_reset_page_url();

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}

				if ( empty( $_POST['pass1'] ) ) {
					// Password is empty
					$redirect_url = $mbrac_options->password_reset_page_url();

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );

					wp_redirect( $redirect_url );
					exit;

				}

				// Parameter checks OK, reset password
				reset_password( $user, $_POST['pass1'] );
				wp_redirect( $mbrac_options->login_page_url( 'password=changed' ) );
			} else {
				echo 'Invalid request.';
			}

			exit;
		}
	}

	public function wp_login_failed( $username, $error ) {
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;
		if ( false !== strpos( $redirect_to, '==' ) ) {
			$redirect_to = urldecode( str_replace( '==', '%3D%3D', $redirect_to ) );
		}

		$login_url = wp_login_url();

		if ( ! empty( $redirect_to ) ) {
			$login_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $login_url );
		}

		$login_url = add_query_arg( 'error', $error->get_error_codes()[0], $login_url );

		wp_redirect( $login_url );
		exit;
	}


	//
	// OTHER CUSTOMIZATIONS
	//

	/**
	 * Returns the message body for the password reset mail.
	 * Called through the retrieve_password_message filter.
	 *
	 * @param string  $message    Default mail message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 *
	 * @return string   The mail message to send.
	 */
	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		// Create new message
		$msg  = __( 'Hello!', 'members' ) . "\r\n\r\n";
		$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'members' ), $user_login ) . "\r\n\r\n";
		$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'members' ) . "\r\n\r\n";
		$msg .= __( 'To reset your password, visit the following address:', 'members' ) . "\r\n\r\n";
		$msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
		$msg .= __( 'Thanks!', 'members' ) . "\r\n";

		return $msg;
	}

	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code    The error code to look up.
	 *
	 * @return string               An error message.
	 */
	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			// Login errors

			case 'empty_username':
				return __( 'You do have an email address, right?', 'members' );

			case 'empty_password':
				return __( 'You need to enter a password to login.', 'members' );

			case 'invalid_username':
				return __(
					"We don't have any users with that email address. Maybe you used a different one when signing up?",
					'members'
				);

			case 'incorrect_password':
				$err = __(
					"The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?",
					'members'
				);
				return sprintf( $err, wp_lostpassword_url() );

			// Registration errors

			case 'email':
				return __( 'The email address you entered is not valid.', 'members' );

			case 'email_exists':
				return __( 'An account exists with this email address.', 'members' );

			case 'closed':
				return __( 'Registering new users is currently not allowed.', 'members' );

			case 'captcha':
				return __( 'The Google reCAPTCHA check failed. Are you a robot?', 'members' );

			// Lost password

			case 'empty_username':
				return __( 'You need to enter your email address to continue.', 'members' );

			case 'invalid_email':
			case 'invalidcombo':
				return __( 'There are no users registered with this email address.', 'members' );

			// Reset password

			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'members' );

			case 'password_reset_mismatch':
				return __( "The two passwords you entered don't match.", 'members' );

			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'members' );

			case 'disabled_account':
				return __( 'The account you are trying to access has been disabled or no longer exists. Please contact membership staff for assistance.' );
			default:
				break;
		}

		return __( 'An unknown error occurred. Please try again later.', 'members' );
	}

	/**
	 * Add the field to user profiles
	 *
	 * @since 1.0.0
	 * @param object $user
	 */
	public function use_profile_field( $user ) {

		// Only show this option to users who can delete other users
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		?>
		<h2>Account Status</h2>
		<table class="form-table">
			<tbody>
				<tr>
					<th>
						<label for="members_disable_user"><?php _e( ' Disable User Account', 'members' ); ?></label>
					</th>
					<td>
						<input type="checkbox" name="members_disable_user" id="members_disable_user" value="1" <?php checked( 1, get_the_author_meta( 'members_disable_user', $user->ID ) ); ?> />
						<span class="description"><?php _e( 'If checked, the user cannot login with this account.', 'members' ); ?></span>
					</td>
				</tr>
			<tbody>
		</table>
		<?php
	}

	/**
	 * Saves the custom field to user meta
	 *
	 * @since 1.0.0
	 * @param int $user_id
	 */
	public function user_profile_field_save( $user_id ) {

		// Only worry about saving this field if the user has access
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		if ( ! isset( $_POST['members_disable_user'] ) ) {
			$disabled = 0;
		} else {
			$disabled = $_POST['members_disable_user'];
		}

		update_user_meta( $user_id, 'members_disable_user', $disabled );
	}
}
