;(function ($) {
	//Unauthorized stuff
	if ($('input[name="members_access_control_settings[redirect_on_unauthorized]"]').is(':checked')) {
		$('.unauthorized_redirect_url').slideDown()
	} else {
		$('.unauthorized_redirect_url').slideUp()
	}

	$('input[name="members_access_control_settings[redirect_on_unauthorized]"]').click(function () {
		if ($('input[name="members_access_control_settings[redirect_on_unauthorized]"]').is(':checked')) {
			$('.unauthorized_redirect_url').slideDown()
		} else {
			$('.unauthorized_redirect_url').slideUp()
		}
	})
})(jQuery)