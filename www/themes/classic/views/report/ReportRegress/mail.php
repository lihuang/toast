<?php
echo $this->renderPartial('_mail', array('report' => $report, 'onlyfail' => $onlyfail, 'reports' => $reports));
?>