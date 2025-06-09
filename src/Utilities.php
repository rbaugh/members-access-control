<?php
/**
* MbrAC_Utils Class.
*
* Utilities/helpers for the Members Access Control.
*
* @package MembersAccessControl
* @author GrayDigitalGroup
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

namespace Members\AddOns\MembersAccessControl;

/**
* MbrAC_Utils component class.
*
* @since 1.0.0
* @access public
*/
class MbrAC_Utils{

	public static function wp_pages_dropdown($field_name, $page_id = 0) {
		$pages = get_pages();
		$selected_page_id = (isset($_POST[$field_name])?$_POST[$field_name]:$page_id);

		?>
		<select name="<?php echo $field_name; ?>" id="<?php echo $field_name; ?>">
			<option>&nbsp;</option>
			<?php
			foreach($pages as $page) {
				$selected = (((isset($_POST[$field_name]) && $_POST[$field_name] == $page->ID) || (!isset($_POST[$field_name]) && $page_id == $page->ID))?' selected="selected"':'');
				?>
				<option value="<?php echo $page->ID; ?>" <?php echo $selected; ?>><?php echo $page->post_title; ?>&nbsp;</option>
				<?php
			}
			?>
		</select>
		<?php

		if( isset( $selected_page_id ) && $selected_page_id > 0 ) {
			$permalink = get_permalink($selected_page_id);
		?>
			&nbsp;<a href="<?php echo admin_url("post.php?post={$selected_page_id}&action=edit"); ?>" target="_blank" class="button"><?php _e('Edit', 'memberpress'); ?></a>
			<a href="<?php echo $permalink; ?>" target="_blank" class="button"><?php _e('View', 'memberpress'); ?></a>
		<?php
		}
	}

	public static function wp_redirect($location, $status = 302) {
		self::include_pluggables('wp_redirect');

		//Don't cache redirects YO!
		header( "Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate" );
		header( "Pragma: no-cache" );
		header( "Expires: Fri, 01 Jan 2016 00:00:01 GMT", true ); //Some date in the past
		wp_redirect( $location, $status );

		exit;
	}

	public static function include_pluggables($function_name) {
		if( !function_exists( $function_name ) ) {
			require_once( ABSPATH . WPINC . '/pluggable.php' );
		}
	}

	public static function get_param_delimiter_char($link)
	{
		return ((preg_match("#\?#",$link))?'&':'?');
	}
}
