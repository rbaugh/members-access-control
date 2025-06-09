<div id="password-reset-form" class="widecolumn">
	<?php if ( $attributes['show_title'] ) : ?>
	<h3><?php _e( 'Pick a New Password', 'members' ); ?></h3>
	<?php endif; ?>

	<form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
		<?php
		foreach ( $attributes  as $key => $value ) {
			if ( in_array( $key, array( 'errors', 'show_title' ) ) ) {
				continue;
			}

			if ( 'login' === $key ) {
				echo '<input type="hidden" id="user_login" name="rp_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" autocomplete="off" />';
			} else {
				echo '<input type="hidden" name="rp_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
			}
		}
		?>

		<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
			<?php foreach ( $attributes['errors'] as $error ) : ?>
		<p>
				<?php echo $error; ?>
		</p>
		<?php endforeach; ?>
		<?php endif; ?>

		<p>
			<label for="pass1"><?php _e( 'New password', 'members' ); ?></label>
			<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
		</p>
		<p>
			<label for="pass2"><?php _e( 'Repeat new password', 'members' ); ?></label>
			<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
		</p>

		<p class="description"><?php echo wp_get_password_hint(); ?></p>

		<p class="resetpass-submit">
			<input type="submit" name="submit" id="resetpass-button" class="button" value="<?php _e( 'Reset Password', 'members' ); ?>" />
		</p>
	</form>
</div>
