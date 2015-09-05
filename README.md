# ![Alt text](images/logo.jpg "SECT") CFS Loop Field Query <span style="font-weight: normal; font-size: 16px;">- For Events -<span>

### Modify the Query to multiple dates in a post For Custom Field Suite "Loop Field".  

> **NOTE**  
You must be active Custom Field Suite Plugin <https://wordpress.org/plugins/custom-field-suite/>  
Create a Loop Field and Date Field in the Loop Field using CFS Plugin.

#### Installation
- - -
 1. Download the cfs-loop-field-query.zip file to your computer.  
 2. Unzip the file.  
 3. Upload the cfs-loop-field-query directory to your /wp-content/plugins/ directory.  
 4. Activate the plugin through the 'Plugins' menu in WordPress.  
 You can access the some setting by going to Settings -> CFS Loop Field Query.
 5. Setting "Post Type Name", "Loop Field Name", "Date Field Name in Loop Feld".  
 ( Optional Field: "Taxonomy Name", "StartTime Field", "FinishTime Field" )  
 That's it. The main query of your select post types will be rewritten.

#### TIP
- - -
* If you want to apply to some existing article, resave the article.  
* This Plugin includes adding Time-Picker Field in CFS.
* Supports Page `is_date()` includes `is_year()` `is_month()` `is_day()`.

### Usage Example
- - -
You can get a sub query using the `new CFS_LFQ_Query()`

#### Example: Sub Query
    <?php
        $ary	 = array();
        $page    = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $perpage = 10;
        $offset  = ($page - 1) * $perpage;
        $args    = array(
            'posts_per_page' => $perpage
        );
        $query = new CFS_LFQ_Query($args);
    ?>
    <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); ?>
        // something
    <?php endwhile; ?>
    <?php endif;?>
    <?php wp_reset_postdata(); ?>

#### Example: Sub Query For Calendar
    <?php
        $ary	 = array();
        $page    = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $perpage = -1;
    	$offset  = ($page - 1) * $perpage;
        $args    = array(
            'posts_per_page'    => $perpage,
            'calendar'          => true		// For get the data from not today but first day in this month.
        );
        $query = new CFS_LFQ_Query($args);
    ?>
    <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); ?>
    <?php
        $date       = date_i18n('Ymd', strtotime($post->date));
        $post_id    = $post->ID;
        $perm       = get_the_permalink();
        $title      = get_the_title();
        array_push($ary, array('date' => $date, 'id' => $post_id, 'permlink' => $perm, 'title' => $title));
    ?>
    <?php endwhile; ?>
    <?php endif;?>
    <?php wp_reset_postdata(); ?>

    <?php
        // Passing array to your Calendar Class.
        require_once 'Calendar/Month/Weeks.php';
        calendar($ary, 0);
    ?>
#### Example: Get the "Date", "StartTime" and "FinishTime"
    <div id="date">
        <?php echo date('Y-m-d', strtotime($post->date)); ?>
    </div>
    <time>
        <?php echo date("H:i", strtotime($post->starttime)); ?> ~ <?php echo date("H:i", strtotime($post->finishtime)); ?>
    </time>
### Change log  
- - -
**version 1.1**
 * Support "StartTime" and "FinishTime" for each Date.
