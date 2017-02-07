<?php
if (!defined('BASE_PATH'))
    exit('No direct script access allowed');
use app\src\Exception\NotFoundException;
use app\src\Exception\Exception;
use Cascade\Cascade;
use app\src\NodeQ\tc_NodeQ as Node;
use app\src\NodeQ\NodeQException;
use app\src\elFinder\elFinderConnector;
use app\src\elFinder\elFinder;
use PDOException as ORMException;

$app->group('/campaign', function() use ($app) {

    /**
     * Before route check.
     */
    $app->before('GET', '/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
        }
    });

    $app->get('/', function () use($app) {

        try {
            $msgs = $app->db->campaign()
                ->where('owner = ?', get_userdata('id'))
                ->orderBy('sendstart', 'ASC')
                ->find();
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage());
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage());
        }

        tc_register_style('datatables');
        tc_register_script('datatables');

        $app->view->display('campaign/index', [
            'title' => _t('My Campaigns'),
            'msgs' => $msgs
            ]
        );
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/create/', function() {
        if (!hasPermission('create_campaign')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
        }
    });

    $app->match('GET|POST', '/create/', function () use($app) {

        if ($app->req->isPost()) {
            try {
                $msg = $app->db->campaign();
                $msg->owner = get_userdata('id');
                $msg->node = $app->req->post['node'];
                $msg->subject = $app->req->post['subject'];
                $msg->from_name = $app->req->post['from_name'];
                $msg->from_email = $app->req->post['from_email'];
                $msg->html = $app->req->post['html'];
                $msg->text = $app->req->post['text'];
                $msg->footer = $app->req->post['footer'];
                $msg->status = 'ready';
                $msg->sendstart = $app->req->post['sendstart'];
                $msg->archive = $app->req->post['archive'];
                $msg->addDate = \Jenssegers\Date\Date::now();
                $msg->save();

                $ID = $msg->lastInsertId();

                foreach ($app->req->post['lid'] as $list) {
                    $cpgn_list = $app->db->campaign_list();
                    $cpgn_list->insert([
                        'cid' => $ID,
                        'lid' => $list
                    ]);
                }

                try {
                    Node::create($app->req->post['node'], [
                        'lid' => 'integer',
                        'mid' => 'integer',
                        'sid' => 'integer',
                        'to_email' => 'string',
                        'to_name' => 'string',
                        'message_html' => 'string',
                        'message_plain_text' => 'string',
                        'timestamp_created' => 'string',
                        'timestamp_to_send' => 'string',
                        'timestamp_sent' => 'string',
                        'is_sent' => 'string',
                        'serialized_headers' => 'string'
                    ]);
                } catch (NodeQException $e) {
                    _tc_flash()->error($e->getMessage());
                }
                tc_logger_activity_log_write('New Record', 'Campaign', _filter_input_string(INPUT_POST, 'subject'), get_userdata('uname'));
                _tc_flash()->success(_tc_flash()->notice(200), get_base_url() . 'campaign' . '/' . $ID . '/');
            } catch (NotFoundException $e) {
                _tc_flash()->error($e->getMessage());
            } catch (Exception $e) {
                _tc_flash()->error($e->getMessage());
            } catch (ORMException $e) {
                _tc_flash()->error($e->getMessage());
            }
        }

        tc_register_style('select2');
        tc_register_style('iCheck');
        tc_register_style('datetime');
        tc_register_script('select2');
        tc_register_script('moment.js');
        tc_register_script('datetime');
        tc_register_script('iCheck');

        $app->view->display('campaign/create', [
            'title' => _t('Create Campaign')
            ]
        );
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
        }
    });

    $app->match('GET|POST', '/(\d+)/', function ($id) use($app) {

        if ($app->req->isPost()) {
            try {
                $msg = $app->db->campaign();
                $msg->subject = $app->req->post['subject'];
                $msg->from_name = $app->req->post['from_name'];
                $msg->from_email = $app->req->post['from_email'];
                $msg->html = $app->req->post['html'];
                $msg->text = $app->req->post['text'];
                $msg->footer = $app->req->post['footer'];
                $msg->status = $app->req->post['status'];
                $msg->sendstart = $app->req->post['sendstart'];
                $msg->archive = $app->req->post['archive'];
                $msg->where('id = ?', $id)->_and_()
                    ->where('owner = ?', get_userdata('id'));
                $msg->update();

                tc_cache_delete($id, 'cpgn');
                tc_cache_delete($id, 'clist');
                tc_logger_activity_log_write('Update Record', 'Campaign', _filter_input_string(INPUT_POST, 'subject'), get_userdata('uname'));
                _tc_flash()->success(_tc_flash()->notice(200), $app->req->server['HTTP_REFERER']);
            } catch (NotFoundException $e) {
                _tc_flash()->error($e->getMessage());
            } catch (Exception $e) {
                _tc_flash()->error($e->getMessage());
            } catch (ORMException $e) {
                _tc_flash()->error($e->getMessage());
            }
        }

        $msg = $app->db->campaign()
            ->where('owner = ?', get_userdata('id'))->_and_()
            ->where('id = ?', $id)
            ->findOne();

        /**
         * If the database table doesn't exist, then it
         * is false and a 404 should be sent.
         */
        if ($msg == false) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If the query is legit, but there
         * is no data in the table, then 404
         * will be shown.
         */ elseif (empty($msg) == true) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If data is zero, 404 not found.
         */ elseif (count(_h($msg->id)) <= 0) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If we get to this point, the all is well
         * and it is ok to process the query and print
         * the results in a html format.
         */ else {

            tc_register_style('select2');
            tc_register_style('iCheck');
            tc_register_style('datetime');
            tc_register_script('select2');
            tc_register_script('moment.js');
            tc_register_script('datetime');
            tc_register_script('iCheck');

            $app->view->display('campaign/view', [
                'title' => _t('View/Edit Campaign'),
                'cpgn' => $msg
                ]
            );
        }
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/queue/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/(\d+)/queue/', function ($id) use($app) {

        $cpgn = get_campaign_by_id($id);
        if (_h($cpgn->status) == 'processing') {
            _tc_flash()->error(_t('Message is already queued.'), $app->req->server['HTTP_REFERER']);
            exit();
        }
        
        if($cpgn->id <= 0) {
            _tc_flash()->success(_t('Campaign does not exist.'), $app->req->server['HTTP_REFERER']);
            exit();
        }
        
        $app->hook->{'do_action'}('queue_campaign', $cpgn);
        
        redirect($app->req->server['HTTP_REFERER']);
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/pause/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/(\d+)/pause/', function ($id) use($app) {

        $cpgn = get_campaign_by_id($id);
        if (_h($cpgn->status) == 'paused') {
            _tc_flash()->error(_t('Message is already paused.'), $app->req->server['HTTP_REFERER']);
            exit();
        }

        try {
            $upd = $app->db->campaign();
            $upd->set([
                    'status' => 'paused'
                ])
                ->where('id = ?', _h($cpgn->id))
                ->update();
            tc_logger_activity_log_write('Update Record', 'Campaign Paused', _h($cpgn->subject), get_userdata('uname'));
            _tc_flash()->success(_t('Campaign was successfully paused.'), $app->req->server['HTTP_REFERER']);
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        }
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/resume/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/(\d+)/resume/', function ($id) use($app) {

        $cpgn = get_campaign_by_id($id);
        if (_h($cpgn->status) == 'processing') {
            _tc_flash()->error(_t('Message is already processing.'), $app->req->server['HTTP_REFERER']);
            exit();
        }

        try {
            $upd = $app->db->campaign();
            $upd->set([
                    'status' => 'processing'
                ])
                ->where('id = ?', _h($cpgn->id))
                ->update();
            tc_logger_activity_log_write('Update Record', 'Campaign Resumed', _h($cpgn->subject), get_userdata('uname'));
            _tc_flash()->success(_t('Campaign was successfully resumed.'), $app->req->server['HTTP_REFERER']);
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        }
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/test/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->post('/(\d+)/test/', function ($id) use($app) {
        $cpgn = get_campaign_by_id($id);
        $sub = get_user_by('id', get_userdata('id'));
        $server = get_server_info($app->req->post['server']);

        $footer = _escape($cpgn->footer);
        $footer = str_replace('{email}', _h($sub->email), $footer);
        $footer = str_replace('{from_email}', _h($cpgn->from_email), $footer);

        $msg = _escape($cpgn->html);
        $msg = str_replace('{todays_date}', \Jenssegers\Date\Date::now()->format('M d, Y'), $msg);
        $msg = str_replace('{view_online}', '<a href="' . get_base_url() . 'archive/' . $id . '/">' . _t('View this email in your browser') . '</a>', $msg);
        $msg = str_replace('{first_name}', _h($sub->fname), $msg);
        $msg = str_replace('{last_name}', _h($sub->lname), $msg);
        $msg = str_replace('{email}', _h($sub->email), $msg);
        $msg = str_replace('{address1}', _h($sub->address1), $msg);
        $msg = str_replace('{address2}', _h($sub->address2), $msg);
        $msg = str_replace('{city}', _h($sub->city), $msg);
        $msg = str_replace('{state}', _h($sub->state), $msg);
        $msg = str_replace('{postal_code}', _h($sub->postal_code), $msg);
        $msg = str_replace('{country}', _h($sub->country), $msg);
        $msg .= $footer;
        tinyc_email($server, _h($sub->email), _h($cpgn->subject), $msg);
        redirect($app->req->server['HTTP_REFERER']);
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/report/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/(\d+)/report/', function ($id) use($app) {

        $cpgn = get_campaign_by_id($id);
        $opened = $app->db->tracking()
            ->where('tracking.cid = ?', $id)->_and_()
            ->whereNotNull('tracking.first_open')
            ->sum('tracking.viewed');
        $unique_opens = $app->db->tracking()
            ->where('tracking.cid = ?', $id)->_and_()
            ->whereNotNull('tracking.first_open')
            ->groupBy('tracking.cid')
            ->count('tracking.id');
        $clicks = $app->db->tracking_link()
            ->where('tracking_link.cid = ?', $id)
            ->groupBy('tracking_link.cid')
            ->sum('tracking_link.clicked');
        $unique_clicks = $app->db->tracking_link()
            ->where('tracking_link.cid = ?', $id)
            ->groupBy('tracking_link.sid')
            ->count('tracking_link.id');

        /**
         * If the database table doesn't exist, then it
         * is false and a 404 should be sent.
         */
        if ($cpgn == false) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If the query is legit, but there
         * is no data in the table, then 404
         * will be shown.
         */ elseif (empty($cpgn) == true) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If data is zero, 404 not found.
         */ elseif (count(_h($cpgn->id)) <= 0) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If we get to this point, the all is well
         * and it is ok to process the query and print
         * the results in a html format.
         */ else {

            tc_register_style('datatables');
            tc_register_script('datatables');

            $app->view->display('campaign/report', [
                'title' => _h($cpgn->subject),
                'cpgn' => $cpgn,
                'opened' => $opened,
                'unique_opens' => $unique_opens,
                'clicks' => $clicks,
                'unique_clicks' => $unique_clicks
                ]
            );
        }
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/report/opened/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/(\d+)/report/opened/', function ($id) use($app) {

        $cpgn = get_campaign_by_id($id);
        $opens = $app->db->subscriber()
            ->select('subscriber.email,tracking.*')
            ->_join('tracking', 'tracking.sid = subscriber.id')
            ->_join('campaign', 'tracking.cid = campaign.id')
            ->where('campaign.id = ?', _h($cpgn->id))
            ->find();

        /**
         * If the database table doesn't exist, then it
         * is false and a 404 should be sent.
         */
        if ($cpgn == false) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If the query is legit, but there
         * is no data in the table, then 404
         * will be shown.
         */ elseif (empty($cpgn) == true) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If data is zero, 404 not found.
         */ elseif (count(_h($cpgn->id)) <= 0) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If we get to this point, the all is well
         * and it is ok to process the query and print
         * the results in a html format.
         */ else {

            tc_register_style('datatables');
            tc_register_script('datatables');

            $app->view->display('campaign/opened', [
                'title' => _h($cpgn->subject),
                'cpgn' => $cpgn,
                'opens' => $opens
                ]
            );
        }
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/(\d+)/report/opened/', function() {
        if (!hasPermission('manage_campaigns')) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/(\d+)/report/clicked/', function ($id) use($app) {

        $cpgn = get_campaign_by_id($id);
        $clicks = $app->db->subscriber()
            ->select('subscriber.email,tracking_link.*')
            ->_join('tracking_link', 'tracking_link.sid = subscriber.id')
            ->_join('campaign', 'tracking_link.cid = campaign.id')
            ->where('campaign.id = ?', _h($cpgn->id))
            ->find();

        /**
         * If the database table doesn't exist, then it
         * is false and a 404 should be sent.
         */
        if ($cpgn == false) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If the query is legit, but there
         * is no data in the table, then 404
         * will be shown.
         */ elseif (empty($cpgn) == true) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If data is zero, 404 not found.
         */ elseif (count(_h($cpgn->id)) <= 0) {

            $app->view->display('error/404', ['title' => '404 Error']);
        }
        /**
         * If we get to this point, the all is well
         * and it is ok to process the query and print
         * the results in a html format.
         */ else {

            tc_register_style('datatables');
            tc_register_script('datatables');

            $app->view->display('campaign/clicked', [
                'title' => _h($cpgn->subject),
                'cpgn' => $cpgn,
                'clicks' => $clicks
                ]
            );
        }
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST|PATCH|PUT|OPTIONS|DELETE', '/connector/', function() {
        if (!is_user_logged_in()) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->match('GET|POST|PATCH|PUT|OPTIONS|DELETE', '/connector/', function () use($app) {
        error_reporting(0);
        try {
            _mkdir($app->config('file.savepath') . get_userdata('uname') . '/');
        } catch (\app\src\Exception\IOException $e) {
            Cascade::getLogger('error')->error(sprintf('IOSTATE[%s]: Unable to create directory: %s', $e->getCode(), $e->getMessage()));
        }
        $opts = [
            // 'debug' => true,
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'path' => $app->config('file.savepath') . get_userdata('uname') . '/',
                    'alias' => 'Files',
                    'mimeDetect' => 'auto',
                    'accessControl' => 'access',
                    'attributes' => [
                        [
                            'read' => true,
                            'write' => true,
                            'locked' => false
                        ],
                        [
                            'pattern' => '/\.tmb/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => false
                        ],
                        [
                            'pattern' => '/\.quarantine/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => false
                        ],
                        [
                            'pattern' => '/\.DS_Store/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => false
                        ],
                        [
                            'pattern' => '/\.json$/',
                            'read' => true,
                            'write' => true,
                            'hidden' => false,
                            'locked' => false
                        ]
                    ],
                    'uploadMaxSize' => '500M',
                    'uploadAllow' => [
                        'image/png', 'image/gif', 'image/jpeg',
                        'application/pdf', 'application/msword',
                        'application/zip', 'audio/mpeg', 'audio/x-m4a',
                        'audio/x-wav', 'text/css', 'text/plain',
                        'text/x-comma-separated-values', 'video/mpeg',
                        'video/mp4', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-powerpoint', 'application/vnd.ms-excel'
                    ],
                    'uploadOrder' => ['allow', 'deny']
                ]
            ]
        ];
        // run elFinder
        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/elfinder/', function() {
        if (!is_user_logged_in()) {
            _tc_flash()->error(_t('You lack the proper permission to access the requested screen.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->match('GET|POST', '/elfinder/', function () use($app) {

        tc_register_script('elfinder');

        $app->view->display('campaign/elfinder', [
            'title' => 'elfinder 2.0'
            ]
        );
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/getTemplate/(\d+)/', function() {
        if (!hasPermission('create_campaign')) {
            _tc_flash()->{'error'}(_t('You lack the proper permission to create a campaign.'));
        }
    });

    $app->get('/getTemplate/(\d+)/', function($id) use($app) {
        try {
            $template = $app->db->template()
                ->where('owner = ?', get_userdata('id'))->_and_()
                ->where('id = ?', $id)
                ->find();

            foreach ($template as $tpl) {
                echo _escape($tpl->content);
            }
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage());
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage());
        }
    });

    /**
     * Before route check.
     */
    $app->before('GET', '/(\d+)/d/', function() {
        if (!hasPermission('delete_campaign')) {
            _tc_flash()->error(_t('You lack the proper permission to delete a campaign.'), get_base_url() . 'campaign' . '/');
            exit();
        }
    });

    $app->get('/(\d+)/d/', function ($id) use($app) {
        $cpgn = get_campaign_by_id($id);

        try {
            $msg = $app->db->campaign()
                ->where('owner = ?', get_userdata('id'))->_and_()
                ->where('id = ?', $id);

            try {
                Node::remove(_h($cpgn->node));
            } catch (NodeQException $e) {
                _tc_flash()->error($e->getMessage());
            } catch (Exception $e) {
                _tc_flash()->error($e->getMessage());
            }

            $msg->reset()->findOne($id)->delete();
            tc_cache_delete($id, 'campaign');
            tc_cache_delete($id, 'cpgn');
            tc_cache_delete($id, 'clist');
            _tc_flash()->success(_tc_flash()->notice(200), $app->req->server['HTTP_REFERER']);
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage(), $app->req->server['HTTP_REFERER']);
        }
    });
});

$app->setError(function() use($app) {

    $app->view->display('error/404', ['title' => '404 Error']);
});
