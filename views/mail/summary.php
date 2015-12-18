<?php
use achertovsky\syslog\models\Syslog;
?>

<table border>
    <tr>
        <th>Time</th>
        <th>Log Source</th>
        <th>User #</th>
        <th>Issues</th>
        <th>Messages</th>
    </tr>
<?php foreach ($summary as $log) : ?>
    <tr>
        <td><?=Yii::$app->formatter->asDatetime($log['created_at'])?></td>
        <td><?=$log['log_source']?></td>
        <td><?=$log['user_id']?></td>
        <td><?php 
        if (empty($log['issues'])) :
            echo 'No issues occurred';
         else :
            foreach ($log['issues'] as $issue) :
            ?>
            <p><?=$issue?></p>
            <?php 
            endforeach;
        endif;?>
        </td>
        <td><?php 
        if (empty($log['messages'])) :
            echo 'No messages for this log';
         else :
            foreach ($log['messages'] as $message) :
            ?>
            <p><?=$message?></p>
            <?php 
            endforeach;
        endif;?></td>
    </tr>
<?php endforeach;?>
</table>