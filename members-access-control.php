<?php
/**
 * Plugin Name: Members Access Control
 * Description: Adds ability to lock pages/posts by Members plugin permissions.
 * Version: 1.0.0
 */

namespace Members\AddOns\MembersAccessControl;

/**
 * Primary plugin class.
 *
 * Launches the plugin components and acts as a simple container.
 *
 * @package   MembersAccessControl
 * @author    GrayDigitalGroup
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
class Members_Access_Control {

	/**
	 * Stores the plugin directory path.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $dir;

	/**
	* Stores the plugin directory url.
	*
	* @since 1.0.0
	* @access public
	* @var string
	*/
	public $uri;

	/**
	 * Namespace used for filter hooks and such.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $namespace = 'members/addons/member_access_control';

	/**
	 * Holds an array of the plugin component objects.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array
	 */
	protected $components = [];

	/**
	 * Sets up the object properties.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $path  Plugin directory path.
	 * @param  string  $uri   Plugin directory URI.
	 * @return void
	 */
	public function __construct( ) {

		$this->dir = trailingslashit( realpath( trailingslashit( __DIR__ ) ) );
		$this->uri = trailingslashit( plugin_dir_url( __FILE__  ) );

		$this->registerDefaultComponents();
	}
	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->boot();
		}

		return $instance;
	}

	/**
	 * Bootstraps the components.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function boot() {

		// Bootstrap components.
		foreach ( $this->components as $component ) {
			$component->boot();
		}
	}

	/**
	 * Returns the plugin path.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $file
	 * @return string
	 */
	public function path( $file = '' ) {

		$file = ltrim( $file, '/' );

		return $file ? $this->path . "/{$file}" : $this->path;
	}

	/**
	 * Registers the default plugin components.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	protected function registerDefaultComponents() {

		$components = [
			Integration::class
		];

		foreach ( $components as $component ) {
			$this->registerComponent( $component );
		}
	}

	/**
	 * Returns a plugin component.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $abstract
	 * @return object
	 */
	public function getComponent( $abstract ) {
		return $this->components[ $abstract ];
	}

	/**
	 * Registers a plugin component.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $abstract
	 * @return void
	 */
	protected function registerComponent( $abstract ) {
		$this->components[ $abstract ] = new $abstract();
	}
}

if ( !function_exists( 'access_control_addon' ) ){
	function access_control_addon(){
		require_once 'src/Integration.php';
		return Members_Access_Control::get_instance();
	}
}

add_action( 'members_register_roles', __NAMESPACE__ . '\access_control_addon' );