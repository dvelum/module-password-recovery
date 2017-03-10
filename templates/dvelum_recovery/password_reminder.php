<?php
$page =  $this->get('page');
$resource = $this->get('resource');
$lang = $this->get('lang');
$resource->addJS('/js/lib/jquery-2.0.0.min.js');
$resource->addJS('/resources/dvelum_passwordrecovery/js/password.js');
$resource->addCss('/resources/dvelum_passwordrecovery/css/recovery.css',100);


?>
<div class="form-wrapper">
    <h3 class="text-muted"><?php echo $lang->get('password_recovery');?></h3>
    <div class="well">
        <p><?php echo $lang->get('password_recovery_description');?></p>
        <form class="form-horizontal" action="<?=$this->formUrl; ?>" method="post" id="passForm">
            <div class="form-group">
                <label for="email" class="col-sm-4 control-label"><?php echo $lang->get('your_email');?></label>

                <div class="col-sm-8">
                    <input name="email" type="text" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-4 col-sm-8">
                    <button type="submit" class="btn btn-primary"><?php echo $lang->get('send');?></button>
                </div>
            </div>
        </form>
    </div>
    <div class="hidden" id="msgbox"></div>
</div>
