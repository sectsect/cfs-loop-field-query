<?php
/*
Plugin Name: CFS Loop-Field Query
Plugin URI: https://github.com/sectsect/cfs-loop-field-query
Description: Modify the Query to multiple dates in a post For Custom Field Suite "Loop Field".
Author: SECT INTERACTIVE AGENCY
Version: 2.0.6
Author URI: https://www.ilovesect.com/
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cfs-loop-field-query-activator.php
 */
function activate_cfs_loop_field_query() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cfs-loop-field-query-activator.php';
	Cfs_Loop_Field_Query_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cfs-loop-field-query-deactivator.php
 */
function deactivate_cfs_loop_field_query() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cfs-loop-field-query-deactivator.php';
	Cfs_Loop_Field_Query_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cfs_loop_field_query' );
register_deactivation_hook( __FILE__, 'deactivate_cfs_loop_field_query' );

global $wpdb;
define('TABLE_NAME', $wpdb->prefix.'cfs_loop_field_query');
define('CFS_LFQ_POST_TYPE', get_option('cfs_lfq_posttype'));
define('CFS_LFQ_TAXONOMY', get_option('cfs_lfq_taxonomy'));
define('CFS_LFQ_CFS_LOOP', get_option('cfs_lfq_dategroup'));
define('CFS_LFQ_CFS_LOOP_DATE', get_option('cfs_lfq_datefield'));
define('CFS_LFQ_CFS_LOOP_STARTTIME', get_option('cfs_lfq_starttimefield'));
define('CFS_LFQ_CFS_LOOP_FINISHTIME', get_option('cfs_lfq_finishtimefield'));

require_once plugin_dir_path( __FILE__ ) . 'functions/composer/vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/custom-queries.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/admin.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/calendar.php';

if ( ! class_exists( 'CFS_LFQ' ) ) {
    class CFS_LFQ
    {
        function __construct() {
			add_action('cfs_after_save_input', array( $this, 'save_event' ));
			add_action('before_delete_post', array( $this, 'delete_event' ));
			add_action('pre_get_posts', array( $this, 'cfs_lfq_pre_get_posts' ));
			add_filter('query_vars', array( $this, 'cfs_lfq_register_query_vars' ));
        }

		/*==================================================
		    Save to "wp_cfs_loop_field_query" table.
		================================================== */
		// Sorting by the value of the second dimension in the array of two-dimensional array.
		function sortArrayByKey(&$array, $sortKey, $sortType = SORT_ASC)
		{
		    $tmpArray = array();
		    foreach ($array as $key => $row) {
		        $tmpArray[$key] = $row[$sortKey];
		    }
		    array_multisort($tmpArray, $sortType, $array);
		    unset($tmpArray);
		}

		function save_event($params)
		{
		    if (get_post_type($params['post_data']['ID']) == CFS_LFQ_POST_TYPE && CFS()->get(CFS_LFQ_CFS_LOOP, $params['post_data']['ID'])) {
		        global $wpdb;
		        $postID = $params['post_data']['ID'];
		        $fields = CFS()->get(CFS_LFQ_CFS_LOOP, $postID);
		        $this->sortArrayByKey($fields, CFS_LFQ_CFS_LOOP_DATE);  // sorting by "date"

		        $sql = 'DELETE FROM '.TABLE_NAME." WHERE post_id = $postID;";
		        $sql = $wpdb->prepare($sql);
		        $result = $wpdb->query($sql);

		        foreach ($fields as $field) {
		            $date = str_replace('-', '', $field[CFS_LFQ_CFS_LOOP_DATE]);

		            if ($field[CFS_LFQ_CFS_LOOP_STARTTIME]) {
		                $stime = str_replace(':', '', $field[CFS_LFQ_CFS_LOOP_STARTTIME].':00');
		            } else {
		                $stime = 'null';
		            }
		            if ($field[CFS_LFQ_CFS_LOOP_FINISHTIME]) {
		                $ftime = str_replace(':', '', $field[CFS_LFQ_CFS_LOOP_FINISHTIME].':00');
		            } else {
		                $ftime = 'null';
		            }
		            $sql = 'INSERT INTO '.TABLE_NAME." (post_id, date, starttime, finishtime) VALUES ($postID, $date, $stime, $ftime);";
		            $sql = $wpdb->prepare($sql);
		            $result = $wpdb->query($sql);
		        }
		    }
		}

		/*==================================================
		    Delete the data in "wp_cfs_loop_field_query" table.
		================================================== */
		function delete_event($postID)
		{
		    if (get_post_type($postID) == CFS_LFQ_POST_TYPE && CFS()->get(CFS_LFQ_CFS_LOOP, $postID)) {
		        global $wpdb;
		        $sql = 'DELETE FROM '.TABLE_NAME." WHERE post_id = $postID;";
		        $sql = $wpdb->prepare($sql);
		        $result = $wpdb->query($sql);
		    }
		}

		/*==================================================
		    Modify the Main Query
		================================================== */
		function cfs_lfq_pre_get_posts($query)
		{
		    if (is_admin() || !$query->is_main_query()) {
		        return;
		    }

			if (CFS_LFQ_POST_TYPE) {
			    if ($query->is_post_type_archive(CFS_LFQ_POST_TYPE)) {
			        add_filter('posts_fields', 'event_fields', 10, 2);
			        add_filter('posts_join', 'event_join', 10, 2);
			        add_filter('posts_where', 'event_where', 10, 2);
			        add_filter('posts_orderby', 'event_orderby', 10, 2);
			        // if (!is_date()) {
			        //     add_filter('posts_groupby', 'event_groupby', 10, 2);        // ========== Disabled the outputs to duplicate post on Page "post_type_archive". (It is sorted based on the last date to hold) ==========
			        // }
			    }
			}

		    if (CFS_LFQ_TAXONOMY) {
		        if ($query->is_tax(CFS_LFQ_TAXONOMY)) {
		            add_filter('posts_fields', 'event_fields', 10, 2);
		            add_filter('posts_join', 'event_join', 10, 2);
		            add_filter('posts_where', 'event_where', 10, 2);
		            add_filter('posts_orderby', 'event_orderby', 10, 2);
		        //    add_filter('posts_groupby', 'event_groupby', 10, 2);        // ========== Disabled the outputs to duplicate post on Page "taxonomy". (It is sorted based on the last date to hold) ==========
		        }
		    }
		}

		/*==================================================
		    Add query_vars for calendar
		================================================== */
		function cfs_lfq_register_query_vars($qvars)
		{
		    $qvars[] = 'calendar';

		    return $qvars;
		}
	}
	new CFS_LFQ();
}

/*==================================================
    Add Time Picker to CFS Field     @ https://github.com/ersoma/cfs-time
================================================== */
if ( ! class_exists( 'cfs_time_picker_addon' ) ) {
	class cfs_time_picker_addon
	{
	    public function __construct()
	    {
	        add_filter('cfs_field_types', array($this, 'cfs_field_types'));
	    }

	    public function cfs_field_types($field_types)
	    {
	        $field_types['time_picker'] = dirname(__FILE__) . '/functions/cfs-time/time.php';

	        return $field_types;
	    }
	}
	$cfs_time_picker_addon = new cfs_time_picker_addon();
}

/*==================================================
    Native code Example
================================================== */
// $ary          = array();
// $posttype     = get_post_type();
// $today        = date_i18n("Y-m-d");
// $page         = (get_query_var('paged')) ? get_query_var('paged') : 1;
// $perpage      = 3;
// $offset       = ($page - 1) * $perpage;
//
// global $wpdb;
// $tablename = $wpdb->prefix . 'cfs_lfq_fairs';
// $results   = $wpdb->get_results("
//     SELECT *
//     FROM $wpdb->posts
//     LEFT JOIN $tablename ON $wpdb->posts.ID = $tablename.post_id
//     WHERE post_type = '$posttype'
//     AND post_status = 'publish'
//     AND date >= $today
//     ORDER BY date,post_id ASC
//     LIMIT $perpage
//     OFFSET $offset
// ");
// foreach ($results as $value){
//     $post_id  = $value->ID;
//     $perm	  = get_permalink($value->ID);
//     $date	  = date_i18n('Ymd', strtotime($value->date));
//     $title	  = $value->post_title;
//     array_push($ary, array('date' => $date, 'id' => $post_id, 'permlink' => $perm, 'title' => $title));
// }
