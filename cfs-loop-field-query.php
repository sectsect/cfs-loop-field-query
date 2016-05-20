<?php
/*
Plugin Name: CFS Loop-Field Query for Events
Plugin URI: https://github.com/sectsect/cfs-loop-field-query
Description: Modify the Query to multiple dates in a post For Custom Field Suite "Loop Field".
Author: SECT INTERACTIVE AGENCY
Version: 2.0.0
Author URI: https://www.ilovesect.com/
*/

global $wpdb;
define('TABLE_NAME', $wpdb->prefix.'cfs_loop_field_query');

define('CFS_LFQ_POST_TYPE', get_option('cfs_lfq_posttype'));
define('CFS_LFQ_TAXONOMY', get_option('cfs_lfq_taxonomy'));
define('CFS_LFQ_CFS_LOOP', get_option('cfs_lfq_dategroup'));
define('CFS_LFQ_CFS_LOOP_DATE', get_option('cfs_lfq_datefield'));
define('CFS_LFQ_CFS_LOOP_STARTTIME', get_option('cfs_lfq_starttimefield'));
define('CFS_LFQ_CFS_LOOP_FINISHTIME', get_option('cfs_lfq_finishtimefield'));

if (is_admin()) {
    register_activation_hook(__FILE__, 'cfs_lfq_activate');
}
function cfs_lfq_activate()
{
    global $wpdb;
    $cfs_lfq_db_version = '1.0';
    $installed_ver = get_option('cfs_lfq_version');
    $charset_collate = $wpdb->get_charset_collate();
    if ($installed_ver != $cfs_lfq_db_version) {
        $sql = 'CREATE TABLE '.TABLE_NAME." (
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

/*==================================================
    Save to "wp_cfs_loop_field_query" table.
================================================== */
// Sorting by the value of the second dimension in the array of two-dimensional array.
function sortArrayByKey( &$array, $sortKey, $sortType = SORT_ASC ) {
    $tmpArray = array();
    foreach ( $array as $key => $row ) {
        $tmpArray[$key] = $row[$sortKey];
    }
    array_multisort( $tmpArray, $sortType, $array );
    unset( $tmpArray );
}

function save_event($params)
{
    if (get_post_type($params['post_data']['ID']) == CFS_LFQ_POST_TYPE && CFS()->get(CFS_LFQ_CFS_LOOP, $params['post_data']['ID'])) {
        global $wpdb;
        $postID = $params['post_data']['ID'];
        $fields = CFS()->get(CFS_LFQ_CFS_LOOP, $postID);
        sortArrayByKey( $fields, CFS_LFQ_CFS_LOOP_DATE );  // sorting by "date"

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
add_action('cfs_after_save_input', 'save_event');

/*==================================================
    Delete the data in "wp_cfs_loop_field_query" table.
================================================== */
add_action('before_delete_post', 'delete_event');
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
    // if( !empty($query->query_vars['calendar']) ){
    // 	$venue = $query->get('calendar');
    // 	$query->set('calendar',$venue);
    // }

    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // if(!is_user_logged_in()){	//	For "Preview" on status: future/draft/private
    // 	$query->set('post_status', 'publish');
    // }

    if ($query->is_post_type_archive(CFS_LFQ_POST_TYPE)) {
        $query->set('posts_per_page', -1);

        add_filter('posts_fields', 'event_fields', 10, 2);
        add_filter('posts_join', 'event_join', 10, 2);
        add_filter('posts_where', 'event_where', 10, 2);
        add_filter('posts_orderby', 'event_orderby', 10, 2);
        // if (!is_date()) {
        //     add_filter('posts_groupby', 'event_groupby', 10, 2);        // ========== Disabled the outputs to duplicate post on Page "post_type_archive". (It is sorted based on the last date to hold) ==========
        // }
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
add_action('pre_get_posts', 'cfs_lfq_pre_get_posts');

/*==================================================
    Add query_vars for calendar
================================================== */
function cfs_lfq_register_query_vars($qvars)
{
    $qvars[] = 'calendar';

    return $qvars;
}
add_filter('query_vars', 'cfs_lfq_register_query_vars');
/*==================================================
    Modify & Setting the Sub Query @ http://bradt.ca/blog/extending-wp_query/
================================================== */
class CFS_LFQ_Query extends WP_Query
{
    public function __construct($args = array())
    {
        $args = array_merge($args, array(
            'post_type' => CFS_LFQ_POST_TYPE,
        ));
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
        if ($args['calendar']) {
            add_filter('posts_where', 'calendar_where', 10, 2);
        } else {
            add_filter('posts_where', 'event_where', 10, 2);
        }
        add_filter('posts_orderby', 'event_orderby', 10, 2);
        add_filter('post_limits', 'event_limits', 10, 2);
    //    add_filter('posts_groupby', 'event_groupby',10, 2);

        parent::__construct($args);

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
function event_fields($select)
{
    global $wpdb;
    $select = '* ';

    return $select;
}

function event_join($join)
{
    global $wpdb;
    $join = 'LEFT JOIN '.TABLE_NAME." ON {$wpdb->posts}.ID = ".TABLE_NAME.'.post_id';

    return $join;
}

function event_where($where)
{
    if (!is_date()) {
        // If you have set the 'finishtime', it does not appear that post when it passes your set time. (Default: the day full)
        if (!CFS_LFQ_CFS_LOOP_FINISHTIME) {
            $today = date_i18n('Ymd');
            $where = " AND post_type = '".CFS_LFQ_POST_TYPE."' AND post_status = 'publish' AND date >= $today ";
        } else {
            $currenttime = date_i18n('YmdHis');
            $where = " AND post_type = '".CFS_LFQ_POST_TYPE."' AND post_status = 'publish' AND TIMESTAMP(date,finishtime) > $currenttime ";
        }
    } else {
        if (is_year()) {
            $theyaer = get_query_var('year');
            $startday = $theyaer.'-01-01';
            $finishday = $theyaer.'-12-31';
            $where = " AND post_type = '".CFS_LFQ_POST_TYPE."' AND post_status = 'publish' AND date BETWEEN '$startday' AND '$finishday' ";
        }
        if (is_month()) {
            $themonth = get_query_var('year').'-'.get_query_var('monthnum');
            $startday = $themonth.'-01';
            $finishday = $themonth.'-31';
            $where = " AND post_type = '".CFS_LFQ_POST_TYPE."' AND post_status = 'publish' AND date BETWEEN '$startday' AND '$finishday' ";
        }
        if (is_day()) {
            $thedate = get_query_var('year').'-'.get_query_var('monthnum').'-'.get_query_var('day');
            $theday = date_i18n('Ymd', strtotime($thedate));
            $where = " AND post_type = '".CFS_LFQ_POST_TYPE."' AND post_status = 'publish' AND date = $theday ";
        }
    }

    return $where;
}

function event_orderby($orderby)
{
    return 'date, post_id ASC';
}

function event_limits($limit)
{
    return $limit;
}

function event_groupby($groupby)
{
    global $wpdb;
    if (is_post_type_archive(CFS_LFQ_POST_TYPE)) {        //  is_post_type_archive の場合、重複投稿を出力させないため、groupbyでくくる
        $groupby = "{$wpdb->posts}.ID";
    }

    return $groupby;
}

// ========== For calendar ========== (Date starts from first day in the month.)
function calendar_where($where)
{
    $today = date_i18n('Ym'.'01');
    $where = " AND post_type = '".CFS_LFQ_POST_TYPE."' AND post_status = 'publish' AND date >= $today ";

    return $where;
}

/*==================================================
    Add Time Picker to CFS Field     @ https://github.com/ersoma/cfs-time
================================================== */
$cfs_time_picker_addon = new cfs_time_picker_addon();

class cfs_time_picker_addon
{
    public function __construct()
    {
        add_filter('cfs_field_types', array($this, 'cfs_field_types'));
    }

    public function cfs_field_types($field_types)
    {
        $field_types['time_picker'] = dirname(__FILE__).'/cfs-time/time.php';

        return $field_types;
    }
}

/*==================================================
    Add Menu Page
================================================== */
add_action('admin_menu', 'cfs_lfq_menu');
function cfs_lfq_menu()
{
    $page_hook_suffix = add_options_page('CFS Loop Field Query', 'CFS Loop Field Query', 8, 'cfs_lfq_menu', 'cfs_lfq_options_page');
    add_action('admin_print_styles-' . $page_hook_suffix, 'cfs_lfq_admin_styles');
    add_action('admin_print_scripts-' . $page_hook_suffix, 'cfs_lfq_admin_scripts');    // @ https://wpdocs.osdn.jp/%E9%96%A2%E6%95%B0%E3%83%AA%E3%83%95%E3%82%A1%E3%83%AC%E3%83%B3%E3%82%B9/wp_enqueue_script#.E3.83.97.E3.83.A9.E3.82.B0.E3.82.A4.E3.83.B3.E7.AE.A1.E7.90.86.E7.94.BB.E9.9D.A2.E3.81.AE.E3.81.BF.E3.81.A7.E3.82.B9.E3.82.AF.E3.83.AA.E3.83.97.E3.83.88.E3.82.92.E3.83.AA.E3.83.B3.E3.82.AF.E3.81.99.E3.82.8B
    add_action('admin_init', 'register_cfs_lfq_settings');
}
function cfs_lfq_admin_styles() {
    wp_enqueue_style('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/css/select2.min.css', array());
}
function cfs_lfq_admin_scripts() {
    wp_enqueue_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/js/select2.min.js', array('jquery'));
    wp_enqueue_script('script', plugin_dir_url( __FILE__ ) . 'admin/js/script.js', array('select2'));
}
function register_cfs_lfq_settings()
{
    register_setting('cfs_lfq-settings-group', 'cfs_lfq_posttype');
    register_setting('cfs_lfq-settings-group', 'cfs_lfq_taxonomy');
    register_setting('cfs_lfq-settings-group', 'cfs_lfq_dategroup');
    register_setting('cfs_lfq-settings-group', 'cfs_lfq_datefield');
    register_setting('cfs_lfq-settings-group', 'cfs_lfq_starttimefield');
    register_setting('cfs_lfq-settings-group', 'cfs_lfq_finishtimefield');
}
function cfs_lfq_options_page()
{
    require_once plugin_dir_path(__FILE__) . 'admin/index.php';
}

/*==================================================
	Add date column to only list-page for a Specific Post Type
================================================== */
	function cfs_lfq_manage_posts_columns($columns) {
        $columns['eventdate'] = "Event Day<span style ='font-size: 11px; color: #999; margin-left: 12px;'>Today: " . date_i18n('Y-m-d') . "</span>";
        if(CFS_LFQ_CFS_LOOP_STARTTIME || CFS_LFQ_CFS_LOOP_FINISHTIME){
            $columns['eventtime'] = "Time";
        }
		return $columns;
	}
	function cfs_lfq_add_column($column_name, $postID) {
        if($column_name == "eventdate"){
            $fields = CFS()->get(CFS_LFQ_CFS_LOOP, $postID);
            sort($fields);
            echo '<ul style="padding: 0; margin: 0;">';
            foreach($fields as $field){
				$today = date_i18n("Ymd");
				$thedate = date('Ymd', strtotime($field['date']));
				if($thedate < $today){
					$finish = ' class="finish"';
				}elseif($thedate == $today){
					$finish = ' class="theday"';
				}else{
					$finish = '';
				}
                $wd = date('D', strtotime($field['date']));
                if($wd === "Sat"){
                    $wd = '<span style="color: #2ea2cc;">' . $wd . '</span>';
                }elseif($wd === "Sun"){
                    $wd = '<span style="color: #a00;">' . $wd . '</span>';
                }else{
                    $wd = "<span>" . $wd . "</span>";
                }
                echo "<li".$finish.">" . date('Y-m-d', strtotime($field['date'])) . "（" . $wd . "）" . "</li>";
            }
            echo '</ul>';
        }else if($column_name == "eventtime"){
            $fields = CFS()->get(CFS_LFQ_CFS_LOOP, $postID);
            sort($fields);
            echo '<ul style="padding: 0; margin: 0;">';
            foreach($fields as $field){
                $today = date_i18n("Ymd");
				$thedate = date('Ymd', strtotime($field['date']));
				if($thedate < $today){
					$finish = ' class="finish"';
				}elseif($thedate == $today){
					$finish = ' class="theday"';
				}else{
					$finish = '';
				}
				echo "<li".$finish.">" . date('H:i', strtotime($field['starttime'])) . " - " . date('H:i', strtotime($field['finishtime'])) . "</li>";
            }
            echo '</ul>';
        }
	}
    if(is_admin()){
        global $pagenow;
        if ($_GET['post_type'] == CFS_LFQ_POST_TYPE && is_admin() && $pagenow == 'edit.php')  {
            add_filter( 'manage_posts_columns', 'cfs_lfq_manage_posts_columns' );
	        add_action( 'manage_posts_custom_column', 'cfs_lfq_add_column', 10, 2 );
        }
    }

/*==================================================
    Add CSS to edit.php
================================================== */
global $pagenow;
if ($_GET['post_type'] == CFS_LFQ_POST_TYPE && is_admin() && $pagenow=='edit.php')  {
    wp_enqueue_style('admin-edit', plugin_dir_url( __FILE__ ) . 'admin/css/admin-edit.css', array());
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


/*==================================================
    Get Custom post type day_link
================================================== */
function get_post_type_date_link($post_type, $year, $month = 0, $day = 0)
{
    global $wp_rewrite;
    $post_type_obj = get_post_type_object($post_type);
    $post_type_slug = $post_type_obj->rewrite['slug'] ? $post_type_obj->rewrite['slug'] : $post_type_obj->name;
    if ($day) { // day archive link
        // set to today's values if not provided
        if (!$year) {
            $year = gmdate('Y', current_time('timestamp'));
        }
        if (!$month) {
            $month = gmdate('m', current_time('timestamp'));
        }
        $link = $wp_rewrite->get_day_permastruct();
    } elseif ($month) { // month archive link
        if (!$year) {
            $year = gmdate('Y', current_time('timestamp'));
        }
        $link = $wp_rewrite->get_month_permastruct();
    } else { // year archive link
        $link = $wp_rewrite->get_year_permastruct();
    }
    if (!empty($link)) {
        $link = str_replace('%year%', $year, $link);
        $link = str_replace('%monthnum%', zeroise(intval($month), 2), $link);
        $link = str_replace('%day%', zeroise(intval($day), 2), $link);

        return home_url("$post_type_slug$link");
    }

    return home_url("$post_type_slug");
}


/*==================================================
    Load CalendR Class
================================================== */
require_once plugin_dir_path(__FILE__).'CalendR/vendor/autoload.php';
/*==================================================
    Event Calendar (archive)
================================================== */
function cfs_lfq_calendar($eventdata, $months)
{
    if (CFS_LFQ_POST_TYPE):
    $weekdayBase = 1; // 0:sunday ～ 6:saturday
    $locale      = new WP_Locale();
    $wd          = array_values($locale->weekday_abbrev);
    $wd_en       = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
    $today = date_i18n('Ymd');
    $factory     = new CalendR\Calendar();
    foreach ($months as $month):
        $month = $factory->getMonth(date('Y', strtotime($month)), date('m', strtotime($month)));
    ?>
    <div>
        <header>
        	<h4><?php echo $month->format('M'); ?></h4>
        </header>
        <table cellspacing="0" cellpadding="0" border="0">
            <thead>
        		<tr>
        			<?php
                        for ($i = 0; $i < 7; ++$i) {
                            $weekday     = ($weekdayBase + $i) % 7;
                            $weekdayText = $wd[$weekday];
                            $weekdayEn   = $wd_en[$weekday];
                            echo '<th class="dayweek ' . $weekdayEn . '">'. $weekdayText. '</th>';
                        }
                    ?>
        		</tr>
        	</thead>
        	<tbody>
                <?php foreach ($month as $week): ?>
                    <tr>
                        <?php foreach ($week as $day): ?>
                            <td class="<?php echo mb_strtolower($day->format('D')); ?><?php if($day->format('Ymd') === $today): ?> today<?php endif ?><?php if (!$month->includes($day)): ?> out-of-month<?php endif; ?>">
                                <?php
                                    if ($month->includes($day) && in_array($day->format('Ymd'), $eventdata)) {
                                        $href = get_post_type_date_link(CFS_LFQ_POST_TYPE, $day->format('Y'), $day->format('m'), $day->format('d'));
                                        $dayText = '<a href="' . $href . '"><span>' . $day->format('j') . '</span></a>';
                                    } else {
                                        $dayText = $day->format('j');
                                    }
                                    echo $dayText;
                                ?>
                            </td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php
    endforeach;
    endif;
}

?>
