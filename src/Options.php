<?php
/**
 * MbrAC_Options Class.
 *
 * Options for the Members Access Control.
 *
 * @package   MembersAccessControl
 * @author    GrayDigitalGroup
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace Members\AddOns\MembersAccessControl;

/**
 * MbrAC_Options component class.
 *
 * @since  1.0.0
 * @access public
 */
class MbrAC_Options {

	public $login_page_id_str = '';
	public $thank_you_page_id_str = '';
	public $force_login_page_url_str = '';
	public $login_redirect_url_str = '';
	public $logout_redirect_url_str = '';
	public $password_lost_id_str = '';
	public $password_reset_id_str = '';
	public $unauthorized_redirect_url_str = '';
	public $redirect_on_unauthorized_str = '';

	public $login_page_id = null;
	public $password_lost_page_id = null;
	public $password_reset_page_id = null;
	public $thank_you_page_id = null;
	public $force_login_page_url = null;
	public $login_redirect_url = null;
	public $logout_redirect_url = null;
	public $redirect_on_unauthorized = null;
	public $unauthorized_redirect_url = null;

	public function __construct( $options = array() ){
		$this->set_strings();
		$this->set_from_array( $options );
		$this->set_defaults();
	}

	// This is used to allow permalinks to be retrieved
	// Early on in the game
	public function populate_rewrite() {
		if(empty($GLOBALS['wp_rewrite']))
			$GLOBALS['wp_rewrite'] = new WP_Rewrite();
	}

	public function set_strings(){
		$this->login_page_id_str             = 'login_page_id';
		$this->thank_you_page_id_str         = 'thank_you_page_id';
		$this->force_login_page_url_str      = 'force_login_page_url';
		$this->login_redirect_url_str        = 'login_redirect_url';
		$this->logout_redirect_url_str       = 'logout_redirect_url';
		$this->password_lost_id_str          = 'password_lost_page_id';
		$this->password_reset_id_str         = 'password_reset_page_id';
		$this->unauthorized_redirect_url_str = 'unauthorized_redirect_url';
		$this->redirect_on_unauthorized_str  = 'redirect_on_unauthorized';
	}

	public function set_defaults() {

		if( !isset( $this->login_page_id ) )
			$this->login_page_id = 0;

		if( !isset( $this->password_lost_page_id ) )
			$this->password_lost_page_id = 0;

		if( !isset( $this->password_reset_page_id ) )
			$this->password_reset_page_id = 0;

		if( !isset( $this->thank_you_page_id ) )
			$this->thank_you_page_id = 0;

		if( !isset( $this->force_login_page_url ) ) //Forces wp's login_url filter to be overridden with MP login page permalink
			$this->force_login_page_url = false;

		if( !isset( $this->login_redirect_url ) ) {
			$this->populate_rewrite();
			$this->login_redirect_url = $this->login_page_url();
		}

		if( !isset( $this->logout_redirect_url ) ){
			$this->populate_rewrite();
			$this->logout_redirect_url = home_url();
		}

		if( !isset( $this->redirect_on_unauthorized ) )
			$this->redirect_on_unauthorized = false;

		if( !isset( $this->unauthorized_redirect_url ) ) {
			$this->populate_rewrite();
			$this->unauthorized_redirect_url = $this->login_page_url();
		}

	}

	public function set_from_array( $options = array(), $post_array = false ) {
		if( $post_array ) {
			$this->update( $post_array );
		}
		else { // Set values from array
			foreach( $options as $key => $value ) {
				$this->$key = $value;
			}
		}
	}

	public function update( $params ){
		$this->login_page_id             = ( isset( $params[ $this->login_page_id_str ] ) && is_numeric( $params[ $this->login_page_id_str ] ) ) ? (int)$params[ $this->login_page_id_str ] : 0;

		$this->password_lost_page_id     = ( isset( $params[ $this->password_lost_id_str ] ) && is_numeric( $params[ $this->password_lost_id_str ] ) ) ? (int)$params[ $this->password_lost_id_str ] : 0;

		$this->password_reset_page_id    = ( isset( $params[ $this->password_reset_id_str ] ) && is_numeric( $params[ $this->password_reset_id_str ] ) ) ? (int)$params[ $this->password_reset_id_str ] : 0;

		$this->thank_you_page_id         = ( isset( $params[ $this->thank_you_page_id_str ] ) && is_numeric( $params[ $this->thank_you_page_id_str ] ) ) ? (int)$params[ $this->thank_you_page_id_str ] : 0;

		$this->force_login_page_url      = isset( $params[ $this->force_login_page_url_str ] );

		$this->login_redirect_url        = ( isset( $params[ $this->login_redirect_url_str ] ) && !empty( $params[ $this->login_redirect_url_str ] ) ) ? trim( stripslashes( $params[ $this->login_redirect_url_str ] ) ) : $this->login_page_url();

		$this->logout_redirect_url       = ( isset( $params [$this->logout_redirect_url_str ] ) && !empty( $params[ $this->logout_redirect_url_str ] ) ) ? trim( stripslashes( $params[ $this->logout_redirect_url_str ] ) ) : '';

		$this->redirect_on_unauthorized  = isset( $params[ $this->redirect_on_unauthorized_str ] );

		$this->unauthorized_redirect_url = ( isset( $params [$this->unauthorized_redirect_url_str ] ) && !empty( $params[ $this->unauthorized_redirect_url_str ] ) ) ? trim( stripslashes( $params[ $this->unauthorized_redirect_url_str ] ) ) : $this->login_page_url();
	}

	public function login_page_url( $args = '' ) {
		if( isset( $this->login_page_id ) &&
		is_numeric( $this->login_page_id ) &&
		(int)$this->login_page_id > 0 ) {

			$login_page_id = (int)$this->login_page_id;
			$link = get_permalink( $login_page_id );

			if( !empty( $args ) ) {
				return $link . MbrAC_Utils::get_param_delimiter_char( $link ) . $args;
			}
			else {
				return $link;
			}
		}

		return home_url(); // default to the home url
	}

	public function password_reset_page_url( $args = '' ) {
		if( isset( $this->password_reset_page_id ) &&
		is_numeric( $this->password_reset_page_id ) &&
		(int)$this->password_reset_page_id > 0 ) {

			$password_reset_page_id = (int)$this->password_reset_page_id;
			$link = get_permalink( $password_reset_page_id );

			if( !empty( $args ) ) {
				return $link . MbrAC_Utils::get_param_delimiter_char( $link ) . $args;
			}
			else {
				return $link;
			}
		}

		return home_url(); // default to the home url
	}

	public function password_lost_page_url( $args = '' ) {
		if( isset( $this->password_lost_page_id ) &&
		is_numeric( $this->password_lost_page_id ) &&
		(int)$this->password_lost_page_id > 0 ) {

			$password_lost_page_id = (int)$this->password_lost_page_id;
			$link = get_permalink( $password_lost_page_id );

			if( !empty( $args ) ) {
				return $link . MbrAC_Utils::get_param_delimiter_char( $link ) . $args;
			}
			else {
				return $link;
			}
		}

		return home_url(); // default to the home url
	}

	public function thankyou_page_url( $args = '' ) {
		if( isset( $this->thank_you_page_id ) &&
		is_numeric( $this->thank_you_page_id ) &&
		(int)$this->thank_you_page_id > 0 ) {

			$thank_you_page_id = (int)$this->thank_you_page_id;
			$link = get_permalink( $thank_you_page_id );

			if( !empty( $args ) ) {
				return $link . MbrAC_Utils::get_param_delimiter_char( $link ) . $args;
			}
			else {
				return $link;
			}
		}

		return home_url(); // default to the home url
	}

	public static function fetch( $force = false ){
		static $mbrac_options;

		 if( !isset( $mbrac_options ) || $force ) {
			 $mbrac_options_array = get_option( 'members_access_control_settings' );

			 if(!$mbrac_options_array)
				$mbrac_options = new MbrAC_Options(); // Just grab the defaults
			else if( is_object( $mbrac_options_array ) && is_a( $mbrac_options_array, 'MbrAC_Options' ) ) {
				$mbrac_options = $mbrac_options_array;
				$mbrac_options->set_defaults();
				// $mbrac_options->store(false); // store will convert this back into an array
			}
			else if(!is_array($mbrac_options_array))
				$mbrac_options = new MbrAC_Options(); // Just grab the defaults
			else
				$mbrac_options = new MbrAC_Options( $mbrac_options_array ); // Sets defaults for unset options
		 }

		 $mbrac_options->set_strings(); //keep strings fresh (not db cached)
		 return apply_filters('mbrac_fetch_options', $mbrac_options);
	}
}
