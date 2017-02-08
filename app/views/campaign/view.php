<?php
if (!defined('BASE_PATH'))
    exit('No direct script access allowed');
/**
 * Edit Campaign View
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
define('SCREEN_PARENT', 'cpgns');
define('SCREEN', 'cpgn');
?>

<script src="//cdn.tinymce.com/4/tinymce.min.js"></script>
<script type="text/javascript">
    /////////////
    // TINYMCE //
    /////////////

    // Initialize TinyMCE
    tinyMCE.init({
        theme: 'modern',
        mode: 'specific_textareas',
        editor_selector: 'template',
        elements: 'message',
        extended_valid_elements: 'doctype|html|head|meta|title|link|style|body',
        height: '350',
        autosave_ask_before_unload: false,
        relative_urls: false,
        remove_script_host: false,
        plugins: [
            "advlist autolink lists link image charmap print preview anchor",
            "searchreplace visualblocks code",
            "insertdatetime media table contextmenu paste",
            "template"
        ],
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | template | placeholder",
        templates: [
            <?php foreach (get_user_template()as $t) : ?>
                {"title": "<?= _h($t->name); ?>", "description": "<?= _h($t->description); ?>", "url": "<?= get_base_url() . 'campaign' . '/getTemplate/' . _h($t->id) . '/'; ?>"},
            <?php endforeach; ?>
        ],
        file_picker_callback: elFinderBrowser,
        setup: function (editor) {
            editor.addButton('placeholder', {
                type: 'menubutton',
                text: 'Email Placeholders',
                icon: false,
                menu: [
                    {text: 'Date', onclick: function () {
                            editor.insertContent('{todays_date}');
                        }},
                    {text: 'View Online', onclick: function () {
                            editor.insertContent('{view_online}');
                        }},
                    {text: 'First Name', onclick: function () {
                            editor.insertContent('{first_name}');
                        }},
                    {text: 'Last Name', onclick: function () {
                            editor.insertContent('{last_name}');
                        }},
                    {text: 'Subscriber Email', onclick: function () {
                            editor.insertContent('{email}');
                        }},
                    {text: 'Address1', onclick: function () {
                            editor.insertContent('{address1}');
                        }},
                    {text: 'Address2', onclick: function () {
                            editor.insertContent('{address2}');
                        }},
                    {text: 'City', onclick: function () {
                            editor.insertContent('{city}');
                        }},
                    {text: 'State', onclick: function () {
                            editor.insertContent('{state}');
                        }},
                    {text: 'Postal Code', onclick: function () {
                            editor.insertContent('{postal_code}');
                        }},
                    {text: 'Country', onclick: function () {
                            editor.insertContent('{country}');
                        }},
                    {text: 'Unsubscribe URL', onclick: function () {
                            editor.insertContent('{unsubscribe_url}');
                        }},
                    {text: 'Subscriber Preferences', onclick: function () {
                            editor.insertContent('{personal_preferences}');
                        }}
                ]
            });
        }
    });
    function elFinderBrowser(callback, value, meta) {
        tinymce.activeEditor.windowManager.open({
            file: '<?= get_base_url(); ?>campaign/elfinder/', // use an absolute path!
            title: 'elFinder 2.0',
            width: 900,
            height: 600,
            resizable: 'yes'
        }, {
            oninsert: function (file) {
                // Provide file and text for the link dialog
                if (meta.filetype == 'file') {
//            callback('mypage.html', {text: 'My text'});
                    callback(file.url);
                }

                // Provide image and alt text for the image dialog
                if (meta.filetype == 'image') {
//            callback('myimage.jpg', {alt: 'My alt text'});
                    callback(file.url);
                }

                // Provide alternative source and posted for the media dialog
                if (meta.filetype == 'media') {
//            callback('movie.mp4', {source2: 'alt.ogg', poster: 'image.jpg'});
                    callback(file.url);
                }
            }
        });
        return false;
    };
</script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><?= _t('View/Edit Campaign'); ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= get_base_url(); ?>dashboard/"><i class="fa fa-dashboard"></i> <?= _t('Dashboard'); ?></a></li>
            <li><a href="<?= get_base_url(); ?>campaign/"><i class="fa fa-envelope"></i> <?= _t('Campaigns'); ?></a></li>
            <li class="active"><?= _t('View/Edit Campaign'); ?></li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        
        <?= _tc_flash()->showMessage(); ?>
        
        <div class="box box-default">
            <!-- form start -->
            <form method="post" action="<?= get_base_url(); ?>campaign/<?=(int)_h($cpgn->id);?>/" data-toggle="validator" autocomplete="off" id="form">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= _t('Node'); ?></label>
                                <input type="text" class="form-control" value="<?=_h($cpgn->node);?>" readonly required>
                            </div>

                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Email Subject'); ?></label>
                                <input type="text" class="form-control" name="subject" value="<?=_h($cpgn->subject);?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('From Name'); ?></label>
                                <input type="text" class="form-control" name="from_name" value="<?=_h($cpgn->from_name);?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('From Email'); ?></label>
                                <input type="text" class="form-control" name="from_email" value="<?=_h($cpgn->from_email);?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Send Start'); ?></label>
                                <div class='input-group date' id='datetimepicker1'>
                                    <input type='text' class="form-control" name="sendstart" value="<?=_h($cpgn->sendstart);?>" required/>
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                                <p class="help-block"><?=_t('Start data and time of the campaign. Editing it later will not update the queue once it starts.');?></p>
                            </div>
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Archive?'); ?></label>
                                <select class="form-control select2" name="archive" style="width: 100%;" required>
                                    <option>&nbsp;</option>
                                    <option value="1"<?=selected('1', _h($cpgn->archive), false);?>><?=_t('Yes');?></option>
                                    <option value="0"<?=selected('0', _h($cpgn->archive), false);?>><?=_t('No');?></option>
                                </select>
                                <p class="help-block"><?=_t('Should this campaign be available in the online archives?');?></p>
                            </div>
                            
                        </div>
                        <!-- /.col -->
                        <div class="col-md-6">
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Status'); ?></label>
                                <select class="form-control select2" name="status" style="width: 100%;" required>
                                    <option>&nbsp;</option>
                                    <option value="ready"<?=selected('ready', _h($cpgn->status), false);?>><?=_t('Ready');?></option>
                                    <option value="processing"<?=selected('processing', _h($cpgn->status), false);?>><?=_t('Processing');?></option>
                                    <option value="paused"<?=selected('paused', _h($cpgn->status), false);?>><?=_t('Paused');?></option>
                                    <option value="processing"<?=selected('processing', _h($cpgn->status), false);?>><?=_t('Resume');?></option>
                                    <option value="sent"<?=selected('sent', _h($cpgn->status), false);?>><?=_t('Sent');?></option>
                                </select>
                                <p class="help-block"><?=_t('Manually set this field is campaign status is not setting properly.');?></p>
                            </div>
                            
                            <div class="form-group">
                                <label><?= _t('Lists'); ?></label><br />
                                <ul><?php get_campaign_lists((int)_h($cpgn->id)); ?></ul>
                            </div>
                            
                            <div class="form-group">
                                <label><?= _t('Owner'); ?></label>
                                <input type="text" class="form-control" value="<?=get_name((int)_h($cpgn->owner));?>"readonly>
                            </div>
                            
                            <div class="form-group">
                                <label><?= _t('Modified'); ?></label>
                                <input type="text" class="form-control" value="<?= Jenssegers\Date\Date::parse(_h($cpgn->LastUpdate))->format('M. d, Y @ h:i A');?>" readonly>
                            </div>
                            
                        </div>
                        <!-- /.col -->
                        
                        <div class="col-md-12">
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Compose Message'); ?></label>
                                <!-- Custom Tabs -->           
                                <div class="nav-tabs-custom">
                                    <ul class="nav nav-tabs">
                                        <li class="active"><a href="#html-message" data-toggle="tab"><?= _t('HTML'); ?></a></li>
                                        <li><a href="#text-message" data-toggle="tab"><?= _t('Text'); ?></a></li>
                                    </ul>
                                    <!-- // Tabs Heading END -->
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="html-message">
                                            <textarea class="form-control template" rows="3" name="html" required><?=_escape($cpgn->html);?></textarea>
                                        </div>
                                        <div class="tab-pane" id="text-message">
                                            <textarea class="form-control" rows="22" name="text"><?=_h($cpgn->text);?></textarea>
                                        </div>
                                    </div>
                                    <!-- // Custom Tabs END -->
                                </div>
                            </div>
                            
                        </div>
                        <!-- /.col -->
                        
                        <div class="col-md-12">
                            
                            <div class="form-group">
                                <label><font color="red">*</font> <?= _t('Message Footer'); ?></label>
                                <textarea class="form-control" rows="8" name="footer" required><?=_h($cpgn->footer);?></textarea>
                            </div>
                            
                        </div>
                        <!-- /.col -->
                        
                    </div>
                    <!-- /.row -->
                </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <button<?=ie('campaign_inquiry_only');?> type="submit" class="btn btn-primary"><?=_t('Submit');?></button>
                <button type="button" class="btn btn-primary" onclick="window.location='<?=get_base_url();?>campaign/'"><?=_t( 'Cancel' );?></button>
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
