<?php
if (!defined('BASE_PATH'))
    exit('No direct script access allowed');
/**
 * Subscribers View
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
define('SCREEN_PARENT', 'list');
define('SCREEN', 'lists');

?>        

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><?= _h($list->name); ?>: <?= _t('Import Subscribers'); ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= get_base_url(); ?>dashboard/"><i class="fa fa-dashboard"></i> <?= _t('Dashboard'); ?></a></li>
            <li class="active"><?= _t('Import Subscribers'); ?></li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">

        <?= _tc_flash()->showMessage(); ?> 

        <!-- SELECT2 EXAMPLE -->
        <div class="box box-default">
            <div class="box-body">
                    <!-- form start -->
                    <form role="form" method="post" action="<?= get_base_url(); ?>list/<?=_h($list->id);?>/import/" enctype="multipart/form-data">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="exampleInputFile"><?= _t('File input'); ?></label>
                                <input type="file" name="csv_import" required/>
                            </div>
                            <!-- /.box-body -->
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary"><?= _t('Submit'); ?></button>
                                <button type="button" class="btn btn-primary" onclick="window.location='<?=get_base_url();?>list/'"><?= _t('Cancel'); ?></button>
                            </div>
                        </div>
                        <!-- /.box -->
                    </form>
                    <!-- form end -->
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->

    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php $app->view->stop(); ?>