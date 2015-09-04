<?php
/*
Plugin Name: CFS Loop-Field Query for Events
Plugin URI: http://www.ilovesect.com/
Description: Modify the Query to multiple dates in a post For Custom Field Suite "Loop Field".
Author: SECT INTERACTIVE AGENCY
Version: 1.0
Author URI: http://www.ilovesect.com/
*/

global $wpdb;
define('TABLE_NAME', $wpdb->prefix . 'cfs_loop_field_query');

define('CFS_LFQ_POST_TYPE', get_option('cfs_lfq_posttype'));
define('CFS_LFQ_TAXONOMY', get_option('cfs_lfq_taxonomy'));
define('CFS_LFQ_CFS_LOOP', get_option('cfs_lfq_dategroup'));
define('CFS_LFQ_CFS_LOOP_DATE', get_option('cfs_lfq_datefield'));
//define('CFS_LFQ_CFS_LOOP_STARTTIME', 'starttime');

if(is_admin()){
    register_activation_hook( __FILE__, 'cfs_lfq_activate' );
}
function cfs_lfq_activate() {
    global $wpdb;
    $cfs_lfq_db_version = '1.0';
    $installed_ver      = get_option( 'cfs_lfq_version' );
    if( $installed_ver != $cfs_lfq_db_version ) {
        $sql = "CREATE TABLE " . TABLE_NAME . " (
              event_id bigint(20) NOT NULL AUTO_INCREMENT,
              post_id bigint(20) NOT NULL,
              date date NOT NULL,
              PRIMARY KEY  (event_id, post_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('cfs_lfq_version', $cfs_lfq_db_version);
    }
}

/*==================================================
	記事投稿時にCF"StartDate"を"wp_events"テーブルに保存
================================================== */
function save_event($params) {
	if(get_post_type($params['post_data']['ID']) == CFS_LFQ_POST_TYPE && CFS()->get(CFS_LFQ_CFS_LOOP, $params['post_data']['ID'])){
		global $wpdb;
		$postID    = $params['post_data']['ID'];
		$fields    = CFS()->get(CFS_LFQ_CFS_LOOP, $postID);

		$sql       = "DELETE FROM " . TABLE_NAME . " WHERE post_id = $postID;";
		$sql       = $wpdb->prepare($sql);
		$result    = $wpdb->query($sql);

		foreach($fields as $field){
			$date = str_replace("-", "", $field[CFS_LFQ_CFS_LOOP_DATE]);
			$sql = "INSERT INTO " . TABLE_NAME . " (post_id, date) VALUES ($postID, $date);";
			$sql = $wpdb->prepare($sql);
			$result = $wpdb->query($sql);

			// $sql = "INSERT INTO $tablename (post_id, date) VALUES ($postID, $startdate) ON DUPLICATE KEY UPDATE StartDate = $startdate;";
			// var_dump($sql); // debug
			// $sql = $wpdb->prepare($sql);
			// var_dump($sql); // debug
			// $result = $wpdb->query($sql);
			// var_dump($result);
		}
		// $sql1 = "SET @i := 0;";
		// $sql2 = "UPDATE $tablename SET event_id = (@i := @i +1) ORDER BY date ASC;";
		// $sql1 = $wpdb->prepare($sql1);
		// $sql2 = $wpdb->prepare($sql2);
		// $result1 = $wpdb->query($sql1);
		// $result2 = $wpdb->query($sql2);
		// var_dump($result1 . " / " . $result2);
	}
}
add_action('cfs_after_save_input', 'save_event');

/*==================================================
	記事完全削除時に"wp_events"テーブル内、該当テーブルも削除
================================================== */
add_action( 'before_delete_post', 'delete_event' );
function delete_event($postID) {
	if(get_post_type($postID) == CFS_LFQ_POST_TYPE && CFS()->get(CFS_LFQ_CFS_LOOP, $postID)){
		global $wpdb;
		$sql = "DELETE FROM " . TABLE_NAME . " WHERE post_id = $postID;";
		$sql = $wpdb->prepare($sql);
		$result = $wpdb->query($sql);
	}
}



/*==================================================
	Modify the Main Query
================================================== */
function cfs_lfq_pre_get_posts( $query ) {
    // if( !empty($query->query_vars['calendar']) ){
	// 	$venue = $query->get('calendar');
	// 	$query->set('calendar',$venue);
	// }

	if(is_admin() || !$query->is_main_query())
		return;

	// if(!is_user_logged_in()){	//	For "Preview" on status: future/draft/private
	// 	$query->set('post_status', 'publish');
	// }

	if($query->is_post_type_archive(CFS_LFQ_POST_TYPE)){
		$query->set('posts_per_page', 3);

        add_filter('posts_fields', 'event_fields', 10, 2);
		add_filter('posts_join', 'event_join', 10, 2);
		add_filter('posts_where', 'event_where', 10, 2);
		add_filter('posts_orderby', 'event_orderby', 10, 2);
    //    add_filter('posts_groupby', 'event_groupby', 10, 2);        // ========== 投稿の重複出力を切る ==========
	}

    if(CFS_LFQ_TAXONOMY){
        if($query->is_tax(CFS_LFQ_TAXONOMY)){
            add_filter('posts_fields', 'event_fields', 10, 2);
            add_filter('posts_join', 'event_join', 10, 2);
            add_filter('posts_where', 'event_where', 10, 2);
            add_filter('posts_orderby', 'event_orderby', 10, 2);
        //    add_filter('posts_groupby', 'event_groupby', 10, 2);        // ========== 投稿の重複出力を切る ==========
        }
    }
}
add_action( 'pre_get_posts', 'cfs_lfq_pre_get_posts');

/*==================================================
	Add query_vars for calendar
================================================== */
function cfs_lfq_register_query_vars( $qvars ){
	$qvars[] = 'calendar';
	return $qvars;
}
add_filter('query_vars', 'cfs_lfq_register_query_vars' );
/*==================================================
    Modify & Setting the Sub Query @ http://bradt.ca/blog/extending-wp_query/
================================================== */
class CFS_LFQ_Query extends WP_Query {
	function __construct( $args = array() ) {
		$args = array_merge( $args, array(
			'post_type' => CFS_LFQ_POST_TYPE
		) );
        /*==================================================
            Remove the add_filter('pre_get_posts').
        ================================================== */
        remove_filter('posts_fields', 'event_fields', 10, 2);
        remove_filter('posts_join', 'event_join', 10, 2);
        remove_filter('posts_where', 'event_where', 10, 2);
        remove_filter('posts_where', 'calendar_where', 10, 2);
        remove_filter('posts_orderby', 'event_orderby', 10, 2);
        remove_filter('post_limits', 'event_limits', 10, 2);
        remove_filter('posts_groupby', 'event_groupby', 10, 2);

        add_filter('posts_fields', 'event_fields', 10, 2);
    	add_filter('posts_join', 'event_join', 10, 2);
        if($args['calendar']){
            add_filter('posts_where', 'calendar_where', 10, 2);
        }else{
            add_filter('posts_where', 'event_where', 10, 2);
        }
    	add_filter('posts_orderby', 'event_orderby', 10, 2);
        add_filter('post_limits', 'event_limits', 10, 2);
    //    add_filter('posts_groupby', 'event_groupby',10, 2);

		parent::__construct( $args );

		// Make sure these filters don't affect any other queries
        remove_filter('posts_fields', 'event_fields', 10, 2);
        remove_filter('posts_join', 'event_join', 10, 2);
        remove_filter('posts_where', 'event_where', 10, 2);
        remove_filter('posts_where', 'calendar_where', 10, 2);
        remove_filter('posts_orderby', 'event_orderby', 10, 2);
        remove_filter('post_limits', 'event_limits', 10, 2);
    //    remove_filter('posts_groupby', 'event_groupby', 10, 2);
	}
}


/*==================================================
    functions
================================================== */
function event_fields( $select ){
	global $wpdb;
	$select = "* ";

	return $select;
}

function event_join( $join ){
    global $wpdb;
    $join = "LEFT JOIN " . TABLE_NAME . " ON {$wpdb->posts}.ID = " . TABLE_NAME . ".post_id";

    return $join;
}

function event_where( $where ){
    $today = date_i18n("Ymd");
//    $where = " AND post_type = '". CFS_LFQ_POST_TYPE . "' AND post_status = 'publish' AND date >= $today ";

    if(!is_date()){
        $where = " AND post_type = '". CFS_LFQ_POST_TYPE . "' AND post_status = 'publish' AND date >= $today ";
    }else{
        if(is_year()){
            $theyaer   = get_query_var('year');
            $startday  = $theyaer . "-01-01";
            $finishday = $theyaer . "-12-31";
            $where = " AND post_type = '". CFS_LFQ_POST_TYPE . "' AND post_status = 'publish' AND date BETWEEN '$startday' AND '$finishday' ";
        }
        if(is_month()){
            $themonth   = get_query_var('year')."-".get_query_var('monthnum');
            $startday  = $themonth . "-01";
            $finishday = $themonth . "-31";
            $where = " AND post_type = '". CFS_LFQ_POST_TYPE . "' AND post_status = 'publish' AND date BETWEEN '$startday' AND '$finishday' ";
        }
        if(is_day()){
            $thedate = get_query_var('year').'-'.get_query_var('monthnum').'-'.get_query_var('day');
            $theday = date_i18n('Ymd', strtotime($thedate));
            $where = " AND post_type = '". CFS_LFQ_POST_TYPE . "' AND post_status = 'publish' AND date = $theday ";
        }
    }

    return $where;
}

function event_orderby( $orderby ){
    return "date, post_id ASC";
}

function event_limits( $limit ){
    return $limit;
}

function event_groupby( $groupby ){
    global $wpdb;
    if(is_post_type_archive(CFS_LFQ_POST_TYPE)){        //  is_post_type_archive の場合、重複投稿を出力させないため、groupbyでくくる
        $groupby = "{$wpdb->posts}.ID";
    }
    return $groupby;
}

// ========== For calendar ========== (Date starts from first day in the month.)
function calendar_where( $where ){
    $today = date_i18n("Ym"."01");
    $where = " AND post_type = '". CFS_LFQ_POST_TYPE . "' AND post_status = 'publish' AND date >= $today ";

    return $where;
}

/*==================================================
    Add Time Picker to CFS Field     @ https://github.com/ersoma/cfs-time
================================================== */
$cfs_time_picker_addon = new cfs_time_picker_addon();

class cfs_time_picker_addon{
    function __construct() {
        add_filter('cfs_field_types', array($this, 'cfs_field_types'));
    }

    function cfs_field_types( $field_types ) {
        $field_types['time_picker'] = dirname( __FILE__ ) . '/cfs-time/time.php';
        return $field_types;
    }
}

/*==================================================
	Add Menu Page
================================================== */
add_action('admin_menu', 'cfs_lfq_menu');
function cfs_lfq_menu() {
//	add_menu_page('CFS Loop Field Query', 'CFS Loop Field Query', 8, 'cfs_lfq_menu', 'cfs_lfq_options_page');		// 第三引数： 2 （管理者〜寄稿者）	5 （管理者〜編集者）	8 （管理者のみ）
    add_options_page('CFS Loop Field Query', 'CFS Loop Field Query', 8, 'cfs_lfq_menu', 'cfs_lfq_options_page');		// 第三引数： 2 （管理者〜寄稿者）	5 （管理者〜編集者）	8 （管理者のみ）
	add_action('admin_init', 'register_cfs_lfq_settings');
}
function register_cfs_lfq_settings() {
	register_setting('cfs_lfq-settings-group', 'cfs_lfq_posttype');
    register_setting('cfs_lfq-settings-group', 'cfs_lfq_taxonomy');
	register_setting('cfs_lfq-settings-group', 'cfs_lfq_dategroup');
	register_setting('cfs_lfq-settings-group', 'cfs_lfq_datefield');
}
function cfs_lfq_options_page() {
	require_once(plugin_dir_path( __FILE__ ) . "admin/index.php");
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


?>
