<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.ilovesect.com/
 * @since      1.0.0
 *
 * @package    Cfs_Loop_Field_Query
 * @subpackage Cfs_Loop_Field_Query/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Cfs_Loop_Field_Query
 * @subpackage Cfs_Loop_Field_Query/includes
 * @author     SECT INTERACTIVE AGENCY <info@sectwebstudio.com>
 */
class Cfs_Loop_Field_Query_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
	    $cfs_lfq_db_version = '1.0';
	    $installed_ver = get_option('cfs_lfq_version');
	    $charset_collate = $wpdb->get_charset_collate();
	    if ($installed_ver != $cfs_lfq_db_version) {
	        $sql = 'CREATE TABLE '.CFS_LFQ_TABLE_NAME." (
	              event_id bigint(20) NOT NULL AUTO_INCREMENT,
	              post_id bigint(20) NOT NULL,
	              date date NOT NULL,
	              starttime time,
	              finishtime time,
	              PRIMARY KEY  (event_id, post_id)
	            ) $charset_collate;";
	        require_once ABSPATH.'wp-admin/includes/upgrade.php';
	        dbDelta($sql);
	        update_option('cfs_lfq_version', $cfs_lfq_db_version);
	    }
	}

}
