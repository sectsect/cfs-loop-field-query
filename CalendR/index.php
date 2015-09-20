<?php
require 'vendor/autoload.php';
// Use the factory to get your period
$factory = new CalendR\Calendar;
date_default_timezone_set('Asia/Tokyo');
$month = $factory->getMonth(2015, 10);

$weekdayBase = 1;  // 0:日曜～6:土曜
$weekdayDefines = array(array('日', 'sunday'),array('月', 'monday'),array('火', 'tuesday'),array('水', 'wednesday'),array('木', 'thursday'),array('金', 'friday'),array('土', 'saturday'));
?>
<header>
	<h4><?php echo $month->format('n');?>月</h4>
</header>
<table>
    <thead>
		<tr>
			<?php
				for ($i = 0; $i < 7; $i++) {
					$weekday = ($weekdayBase + $i) % 7;
					$weekdayText  = $weekdayDefines[$weekday][0];
					echo '<th class="' . 'dayweek' . '">', $weekdayText, '</th>';
				}
			?>
		</tr>
	</thead>
	<tbody>
        <?php foreach ($month as $week): ?>
            <tr>
                <?php foreach ($week as $day): ?>
                    <?php //Check days that are out of your month ?>
                    <td class="<?php echo mb_strtolower($day->format('D')); ?><?php if (!$month->includes($day)): ?> out-of-month<?php endif; ?>">
                        <?php //echo $day->format('Y-m-d'); ?>
                        <?php echo $day->format('j'); ?>
                    </td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
