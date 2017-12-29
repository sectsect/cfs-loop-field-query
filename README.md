# <img src="https://github-sect.s3-ap-northeast-1.amazonaws.com/logo.svg" width="28" height="auto"> CFS Loop Field Query - For Events -

### Modify the Query to multiple dates in a post For [Custom Field Suite](https://wordpress.org/plugins/custom-field-suite/) "Loop Field".

## Features

For each `Date and Time` set in the `Loop Field`, only the scheduled events are output to Archive Page.

- The `Date and Time` set in the `Loop Field` is outputted as `one event`.
- Displayed in order of the most recent event (`ASC`).
- Closed events is not outputted.
- Supply a `function` for calendar :date:

## Requirements

* PHP 5.3+
* Activation [Custom Field Suite](https://wordpress.org/plugins/custom-field-suite/) Plugin.
* Create a Loop Field and Date Field in the Loop Field using [CFS](https://wordpress.org/plugins/custom-field-suite/) Plugin.
* A 6-pack of beer🍺 (optional, I guess.)

## Installation


 1. `cd /path-to-your/wp-content/plugins/`
 2. `git clone git@github.com:sectsect/cfs-loop-field-query.git`
 3. Activate the plugin through the 'Plugins' menu in WordPress.  
 You can access the some setting by going to `Settings` -> `CFS Loop Field Query`.
 4. Setting `Post Type Name`, `Loop Field Name`, `Date Field Name` in Loop Feld".  
 ( Optional Field: `Taxonomy Name`, `StartTime Field`, `FinishTime Field` )  
 That's it:ok_hand: The main query of your select post types will be modified.

## Fields Structure Example

 <img src="https://github-sect.s3-ap-northeast-1.amazonaws.com/cfs-loop-field-query/screenshot.png" width="789" height="245">

## TIP

* If you want to apply to some existing article, resave the article.  
* This Plugin includes adding Time-Picker Field in CFS. (Using [CFS Time picker add-on](https://github.com/ersoma/cfs-time))
* Support Pages for `is_date()` includes `is_year()` `is_month()` `is_day()`.
* If you have set the 'FinishTime', it does not appear that post when it passes your set time. (Default: The Day Full)

## Usage Example

You can get a sub query with `new CFS_LFQ_Query()`

#### Example: Sub Query
``` php
<?php
    $ary     = array();
    $page    = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $perpage = 10;
    $offset  = ( $page - 1 ) * $perpage;
    $args    = array(
        'posts_per_page' => $perpage
    );
    $query = new CFS_LFQ_Query( $args );
?>
<?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); ?>
    // something
<?php endwhile; ?>
<?php endif;?>
<?php wp_reset_postdata(); ?>
```
#### Example: Sub Query For Calendar w/ `cfs_lfq_calendar()`  
``` php
$dates   = array();
$args    = array(
    'posts_per_page' => -1,
    'calendar'       => true,       // Get the data for Not from the day but from the first day of the month.
);
$query = new CFS_LFQ_Query( $args );
if ( $query->have_posts() ) { while ( $query->have_posts() ) { $query->the_post();
    $date = date( 'Ymd', strtotime( $post->date ) );
    array_push( $dates, $date );
}}
wp_reset_postdata();

// Passing array to cfs_lfq Calendar Class.
$dates  = array_unique( $dates );		// Remove some Duplicate Values(Day)
$months = get_months_from_now( 3 );
$args = array(
    'dates'        => $dates,		// (array) (required) Array of event Date ('Ymd' format)
    'months'       => $months,		// (array) (required) Array of month to generate calendar ('Ym' format)
    'weekdayLabel' => 'default',	// (string) (optional) Available value: 'default' or 'en' Note: 'default' is based on your wordpress locale setting.
    'weekdayBase'  => 0,		// (integer) (optional) The start weekday. 0:sunday ～ 6:saturday Default: 0
    'element'      => 'div',		// (string) (optional) The element for wraping. Default: 'div'
    'class'        => ''		// (string) (optional) The 'class' attribute value for wrap element. Default: ''
);
cfs_lfq_calendar( $args );
```
#### Example: Sub Query For Calendar w/ `Your Calendar Class`
``` php
$ary     = array();
$args    = array(
    'posts_per_page' => -1,
    'calendar'       => true		// Get the data for Not from the day but from the first day of the month.
);
$query = new CFS_LFQ_Query( $args );
if ( $query->have_posts() ) { while ( $query->have_posts() ) { $query->the_post();
    $date    = date( 'Ymd', strtotime( $post->date ) );
    $post_id = $post->ID;
    $perm    = get_the_permalink();
    $title   = get_the_title();
    array_push( $ary, array('date' => $date, 'id' => $post_id, 'permlink' => $perm, 'title' => $title) );
}}
wp_reset_postdata();

// Passing array to your Calendar Class.
require_once 'Calendar/Month/Weeks.php';
calendar( $ary, 0 );
```
#### Example: Get the "Date", "StartTime" and "FinishTime"
``` php
<div id="date">
    <?php echo date( 'Y-m-d', strtotime( $post->date ) ); ?>
</div>
<time>
    <?php echo date( "H:i", strtotime( $post->starttime ) ); ?> ~ <?php echo date( "H:i", strtotime( $post->finishtime ) ); ?>
</time>
```

## function

#### get_months_from_now($num)  
##### Parameters

* **num**
(integer) (required) Number of months to get.  
Default: `1`

##### Return Values

(array)  
`Ym` formatted.

```php
$months = get_months_from_now( 3 );
```


#### cfs_lfq_calendar($args)  
##### Parameters

* **dates**
(array) (required) Array of event Date (`Ymd` format).

* **months**
(array) (required) Array of month to generate calendar (`Ym` format)

* **weekdayLabel**
(string) (optional) Available value: `'default'` or `'en'`.  
Default: `'default'`  
:memo: `'default'` is based on your wordpress locale setting.

* **weekdayBase**
(integer) (optional) The start weekday. `0:sunday ～ 6:saturday`  
Default: `0`

* **element**
(string) (optional) The element for wraping.  
Default: `'div'`

* **class**
(string) (optional) The 'class' attribute value for wrap element.  
Default: `''`

##### Example

```php
$args = array(
	'dates'        => $dates,
	'months'       => $months,
	'weekdayLabel' => 'default',
	'weekdayBase'  => 0,
	'element'      => 'div',
	'class'        => 'myclass'
);
cfs_lfq_calendar( $args );
```

## NOTES for Developer

* This Plugin does not hosting on the [wordpress.org](https://wordpress.org/) repo in order to prevent a flood of support requests from wide audience.

## Change log  

See [CHANGELOG](https://github.com/sectsect/cfs-loop-field-query/blob/master/CHANGELOG.md) file.

## Contributing

1. Create an issue and describe your idea
2. [Fork it](https://github.com/sectsect/cfs-loop-field-query/fork)
3. Create your feature branch (`git checkout -b my-new-feature`)
4. Commit your changes (`git commit -am 'Add some feature'`)
5. Publish the branch (`git push origin my-new-feature`)
6. Create a new Pull Request
7. Profit! :white_check_mark:

## License
See [LICENSE](https://github.com/sectsect/cfs-loop-field-query/blob/master/LICENSE) file.

## Related Plugin
I also have plugin with the same functionality for [Advanced Custom Field](https://wordpress.org/plugins/advanced-custom-fields/) Plugin.  
#### <img src="https://github-sect.s3-ap-northeast-1.amazonaws.com/github.svg" width="22" height="auto"> [ACF Repeater Field Query](https://github.com/sectsect/acf-repeater-field-query)
