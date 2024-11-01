<?php

function wp_backup_plus_get_settings_page_url() {
	return apply_filters('wp_backup_plus_get_settings_page_url', WP_Backup_Plus::get_settings_page_url());
}

function wp_backup_plus_the_settings_page_url() {
	echo apply_filters('wp_backup_plus_the_settings_page_url', wp_backup_plus_get_settings_page_url());
}

function wp_backup_plus_rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir . "/" . $object) == "dir")
					wp_backup_plus_rrmdir($dir . "/" . $object);
				else
					unlink($dir . "/" . $object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}
