jQuery(document).ready(function($) {
	$('.wp-backup-plus-provider-server-type').change(function(event) {
		var server_type = $('.wp-backup-plus-provider-server-type:checked').val() == 'local' ? 'local' : 'remote';
		
		$('[data-server-type]').hide().filter('[data-server-type="' + server_type + '"]').show();
	}).change();
});
