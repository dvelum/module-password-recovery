<?php
$page =  $this->get('page');
$resource = $this->get('resource');
$lang = $this->get('lang');
$resource->addJS('/js/lib/jquery-2.0.0.min.js');
$resource->addJS('/resources/dvelum_passwordrecovery/js/password.js');
$resource->addCss('/resources/dvelum_passwordrecovery/css/recovery.css',100);

$wwwRoot = Request::wwwRoot();

?>
    <div class="form-wrapper">
        <h3 class="text-muted"><?php echo $lang->get('password_recovery');?></h3>
        <?php if ($this->form) { ?>
            <div class="well">
                <p><?php echo $lang->get('change_pwd_msg');?></p>

                <form class="form-horizontal" action="<?php echo $this->confirmUrl; ?>" method="post"
                      id="newPassForm" data-collapsible="1">
                    <input type="hidden" name="code" value="<?php echo $this->code; ?>"/>

                    <div class="form-group">
                        <label for="new_password" class="col-sm-4 control-label"><?php echo $lang->get('new_pwd');?></label>

                        <div class="col-sm-8">
                            <input name="new_password" type="password" class="form-control" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirm" class="col-sm-4 control-label"><?php echo $lang->get('repeat_pwd');?></label>

                        <div class="col-sm-8">
                            <input name="new_password_confirm" type="password" class="form-control" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <button type="submit" class="btn btn-primary"><?php echo $lang->get('change');?></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="hidden" id="msgbox"></div>
            <p><a href="<?php echo $wwwRoot; ?>"><?php echo $lang->get('back_to_main');?></a></p>
        <?php } ?>
        <?php if ($this->error) { ?>
            <div class="alert alert-danger">
                <?php echo $this->error; ?>
            </div>
            <a href="<?php echo $this->passUrl; ?>"><?php echo $lang->get('restore_password');?></a>
        <?php } ?>
    </div>
