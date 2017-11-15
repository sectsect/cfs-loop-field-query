<?php
/*==================================================
    Add Menu Page
================================================== */
add_action('admin_menu', 'cfs_lfq_menu');
function cfs_lfq_menu()
{
    $page_hook_suffix = add_options_page('CFS Loop Field Query', 'CFS Loop Field Query', 'manage_options', 'cfs_lfq_menu', 'cfs_lfq_options_page');
    add_action('admin_print_styles-' . $page_hook_suffix, 'cfs_lfq_admin_styles');
    add_action('admin_print_scripts-' . $page_hook_suffix, 'cfs_lfq_admin_scripts');    // @ https://wpdocs.osdn.jp/%E9%96%A2%E6%95%B0%E3%83%AA%E3%83%95%E3%82%A1%E3%83%AC%E3%83%B3%E3%82%B9/wp_enqueue_script#.E3.83.97.E3.83.A9.E3.82.B0.E3.82.A4.E3.83.B3.E7.AE.A1.E7.90.86.E7.94.BB.E9.9D.A2.E3.81.AE.E3.81.BF.E3.81.A7.E3.82.B9.E3.82.AF.E3.83.AA.E3.83.97.E3.83.88.E3.82.92.E3.83.AA.E3.83.B3.E3.82.AF.E3.81.99.E3.82.8B
    add_action('admin_init', 'register_cfs_lfq_settings');
}
function cfs_lfq_admin_styles()
{
    wp_enqueue_style('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', array());
	wp_enqueue_style('admin-options', plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin-options.css', array());
}
function cfs_lfq_admin_scripts()
{
    wp_enqueue_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery'));
    wp_enqueue_script('script', plugin_dir_url(dirname(__FILE__)) . 'admin/js/script.js', array('select2'));
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
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/index.php';
}

/*==================================================
    Add date column to only list-page for a Specific Post Type
================================================== */
function cfs_lfq_manage_posts_columns($columns)
{
	$columns['eventdate'] = "Event Day<span style ='font-size: 11px; color: #999; margin-left: 12px;'>Today: " . date_i18n('Y-m-d') . "</span>";
	if (CFS_LFQ_CFS_LOOP_STARTTIME || CFS_LFQ_CFS_LOOP_FINISHTIME) {
		$columns['eventtime'] = "Time";
	}
	return $columns;
}
function cfs_lfq_add_column($column_name, $postID)
{
	if ($column_name == "eventdate") {
		$fields = CFS()->get(CFS_LFQ_CFS_LOOP, $postID);
		sort($fields);
		echo '<ul style="padding: 0; margin: 0;">';
		foreach ($fields as $field) {
			if (!$field[CFS_LFQ_CFS_LOOP_FINISHTIME]) {
				$today = date_i18n("Ymd");
				$thedate = date('Ymd', strtotime($field['date']));
				if ($thedate < $today) {
					$finish = ' class="finish"';
				} elseif ($thedate == $today) {
					$finish = ' class="theday"';
				} else {
					$finish = '';
				}
			} else {
				$today = date_i18n("Ymd");
				$thedate = date('Ymd', strtotime($field['date']));
				$rn = date_i18n("YmdHi");
				$fintime = date('YmdHi', strtotime($field['date'] . $field[CFS_LFQ_CFS_LOOP_FINISHTIME]));
				if ($fintime < $rn) {
					$finish = ' class="finish"';
				} elseif ($thedate == $today && $fintime >= $rn) {
					$finish = ' class="theday"';
				} else {
					$finish = '';
				}
			}
			$wd = date('D', strtotime($field['date']));
			if ($wd === "Sat") {
				$wd = '<span style="color: #2ea2cc;">' . $wd . '</span>';
			} elseif ($wd === "Sun") {
				$wd = '<span style="color: #a00;">' . $wd . '</span>';
			} else {
				$wd = "<span>" . $wd . "</span>";
			}
			echo "<li".$finish.">" . date('Y-m-d', strtotime($field['date'])) . "（" . $wd . "）" . "</li>";
		}
		echo '</ul>';
	} elseif ($column_name == "eventtime") {
		$fields = CFS()->get(CFS_LFQ_CFS_LOOP, $postID);
		sort($fields);
		echo '<ul style="padding: 0; margin: 0;">';
		foreach ($fields as $field) {
			$today = date_i18n("Ymd");
			$thedate = date('Ymd', strtotime($field['date']));
			if ($thedate < $today) {
				$finish = ' class="finish"';
			} elseif ($thedate == $today) {
				$finish = ' class="theday"';
			} else {
				$finish = '';
			}
			echo "<li".$finish.">" . date('H:i', strtotime($field['starttime'])) . " - " . date('H:i', strtotime($field['finishtime'])) . "</li>";
		}
		echo '</ul>';
	}
}
if(is_admin()){
	global $pagenow;
	if (isset($_GET['post_type']) && $_GET['post_type'] == CFS_LFQ_POST_TYPE && is_admin() && $pagenow == 'edit.php')  {
		add_filter('manage_posts_columns', 'cfs_lfq_manage_posts_columns');
		add_action('manage_posts_custom_column', 'cfs_lfq_add_column', 10, 2);
	}
}

/*==================================================
    Add CSS to edit.php
================================================== */
if (is_admin()) {
    global $pagenow;
    if (isset($_GET['post_type']) && $_GET['post_type'] == CFS_LFQ_POST_TYPE && is_admin() && $pagenow == 'edit.php') {
        wp_enqueue_style('admin-edit', plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin-edit.css', array());
    }
}
