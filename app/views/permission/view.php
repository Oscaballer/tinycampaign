<?php if ( ! defined('BASE_PATH') ) exit('No direct script access allowed');
/**
 * View Permission View
 *  
 * @license GPLv3
 * 
 * @since       2.0.0
 * @package     tinyCampaign
 * @author      Joshua Parker <joshmac3@icloud.com>
 */

$app = \Liten\Liten::getInstance();
$app->view->extend('_layouts/dashboard');
$app->view->block('dashboard');
$ePerm = new app\src\ACL();
define('SCREEN_PARENT', 'admin');
define('SCREEN', 'perm');
?>
        
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><?= _t('Edit Permission'); ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= get_base_url(); ?>dashboard/"><i class="fa fa-dashboard"></i> <?= _t('Dashboard'); ?></a></li>
            <li><a href="<?= get_base_url(); ?>permission/"><i class="fa fa-key"></i> <?= _t('Permissions'); ?></a></li>
            <li class="active"><?= _t('Edit Permission'); ?></li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        
        <?=_tc_flash()->showMessage();?>

        <!-- SELECT2 EXAMPLE -->
        <div class="box box-default">
            <!-- form start -->
            <form method="post" action="<?=get_base_url();?>permission/<?=_h($perm->id);?>/" data-toggle="validator" autocomplete="off">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Name'); ?></label>
                                <input type="text" class="form-control" name="permName" value="<?=$ePerm->getPermNameFromID(_h($perm->id));?>" required>
                            </div>
                            
                        </div>
                        <!-- /.col -->
                        
                        <div class="col-md-6">
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Key'); ?></label>
                                <input type="text" class="form-control" name="permKey" value="<?=$ePerm->getPermKeyFromID(_h($perm->id));?>" required>
                            </div>
                            
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <button type="submit" class="btn btn-primary"><?=_t('Update');?></button>
                <button type="button" class="btn btn-primary" onclick="window.location='<?=get_base_url();?>permission/'"><?=_t( 'Cancel' );?></button>
            </div>
        </form>
        <!-- form end -->
        </div>
        <!-- /.box -->

    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php $app->view->stop(); ?>