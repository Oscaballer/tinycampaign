<?php

if (!defined('BASE_PATH'))
    exit('No direct script access allowed');
use Respect\Validation\Validator as v;
use TinyC\Exception\NotFoundException;
use TinyC\Exception\Exception;
use PDOException as ORMException;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use TinyC\NodeQ\tc_NodeQ as Node;
use TinyC\NodeQ\NodeQException;
use Cascade\Cascade;

/**
 * Index Router
 *
 * @license GPLv3
 *         
 * @since 2.0.0
 * @package tinyCampaign
 * @author Joshua Parker <joshmac3@icloud.com>
 */
$hasher = new \TinyC\PasswordHash(8, FALSE);

/**
 * Before route check.
 */
$app->before('GET|POST', '/', function() {
    if (is_user_logged_in()) {
        redirect(get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/', function () use($app) {

    if ($app->req->isPost()) {
        /**
         * This function is documented in app/functions/auth-function.php.
         * 
         * @since 2.0.0
         */
        tc_authenticate_user($app->req->_post('uname'), $app->req->_post('password'), $app->req->_post('rememberme'));
    }

    $app->view->display('index/index', [
        'title' => _t('Login')
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/permission.*', function() {
    if (!hasPermission('access_permission_screen')) {
        _tc_flash()->{'error'}(_t("You don't have permission to access the Permission screen."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/permission/', function () use($app) {


    $app->view->display('permission/index', [
        'title' => _t('Manage Permissions')
            ]
    );
});

$app->match('GET|POST', '/permission/(\d+)/', function ($id) use($app) {
    if ($app->req->isPost()) {
        try {
            $perm = $app->db->permission();
            foreach (_filter_input_array(INPUT_POST) as $k => $v) {
                $perm->$k = $v;
            }
            $perm->where('id = ?', $id);
            if ($perm->update()) {
                tc_logger_activity_log_write('Update Record', 'Permission', _filter_input_string(INPUT_POST, 'permName'), get_userdata('uname'));
                _tc_flash()->success(_tc_flash()->notice(200));
            } else {
                _tc_flash()->{'error'}(_tc_flash()->notice(409));
            }
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    try {
        $perm = $app->db->permission()->where('id = ?', $id)->findOne();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    /**
     * If the database table doesn't exist, then it
     * is false and a 404 should be sent.
     */
    if ($perm == false) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If the query is legit, but there
     * is no data in the table, then 404
     * will be shown.
     */ elseif (empty($perm) == true) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If data is zero, 404 not found.
     */ elseif (count(_escape($perm->id)) <= 0) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If we get to this point, the all is well
     * and it is ok to process the query and print
     * the results in a html format.
     */ else {

        $app->view->display('permission/view', [
            'title' => _t('Edit Permission'),
            'perm' => $perm
                ]
        );
    }
});

$app->match('GET|POST', '/permission/add/', function () use($app) {

    if ($app->req->isPost()) {
        try {
            $perm = $app->db->permission();
            foreach (_filter_input_array(INPUT_POST) as $k => $v) {
                $perm->$k = $v;
            }
            if ($perm->save()) {
                tc_logger_activity_log_write('New Record', 'Permission', _filter_input_string(INPUT_POST, 'permName'), get_userdata('uname'));
                _tc_flash()->success(_tc_flash()->notice(200), get_base_url() . 'permission' . '/');
            } else {
                _tc_flash()->{'error'}(_tc_flash()->notice(409));
            }
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    $app->view->display('permission/add', [
        'title' => _t('Add New Permission')
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/role.*', function() {
    if (!hasPermission('access_role_screen')) {
        _tc_flash()->{'error'}(_t("You don't have permission to access the Role screen."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/role/', function () use($app) {

    $app->view->display('role/index', [
        'title' => _t('Manage Roles')
            ]
    );
});

$app->match('GET|POST', '/role/(\d+)/', function ($id) use($app) {
    try {
        $role = $app->db->role()->where('id = ?', $id)->findOne();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    /**
     * If the database table doesn't exist, then it
     * is false and a 404 should be sent.
     */
    if ($role == false) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If the query is legit, but there
     * is no data in the table, then 404
     * will be shown.
     */ elseif (empty($role) == true) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If data is zero, 404 not found.
     */ elseif (count(_escape($role->id)) <= 0) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If we get to this point, the all is well
     * and it is ok to process the query and print
     * the results in a html format.
     */ else {

        tc_register_style('select2');
        tc_register_style('iCheck');
        tc_register_script('select2');
        tc_register_script('iCheck');

        $app->view->display('role/view', [
            'title' => _t('Edit Role'),
            'role' => $role
                ]
        );
    }
});

$app->match('GET|POST', '/role/add/', function () use($app) {

    if ($app->req->isPost()) {
        try {
            $role = $app->db->role();
            $role->insert([
                'roleName' => $app->req->post['roleName'],
                'permission' => maybe_serialize($app->req->post['permission'])
            ]);

            $ID = $role->lastInsertId();
            _tc_flash()->success(_tc_flash()->notice(200), get_base_url() . 'role' . '/' . $ID . '/');
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    tc_register_style('select2');
    tc_register_style('iCheck');
    tc_register_script('select2');
    tc_register_script('iCheck');

    $app->view->display('role/add', [
        'title' => _t('Add Role')
            ]
    );
});

$app->post('/role/editRole/', function () use($app) {
    try {
        $role = $app->db->role();
        $role->set([
                    'roleName' => $app->req->post['roleName'],
                    'permission' => maybe_serialize($app->req->post['permission'])
                ])
                ->where('id = ?', $app->req->post['roleID'])
                ->update();

        _tc_flash()->success(_tc_flash()->notice(200));
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    redirect($app->req->server['HTTP_REFERER']);
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/template.*', function() {
    if (!hasPermission('manage_campaigns')) {
        _tc_flash()->{'error'}(_t("You don't have permission to access the Templates screen."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/template/', function () use($app) {

    try {
        $tpl = $app->db->template()
                ->where('owner = ?', get_userdata('id'))
                ->find();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    tc_register_style('datatables');
    tc_register_style('select2');
    tc_register_script('select2');
    tc_register_script('datatables');

    $app->view->display('template/index', [
        'title' => _t('Templates'),
        'templates' => $tpl
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/template/(\d+)/', function() {
    if (!hasPermission('edit_campaign')) {
        _tc_flash()->{'error'}(_t("You don't have permission to edit templates."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/template/(\d+)/', function ($id) use($app) {

    if ($app->req->isPost()) {
        try {
            $tpl = $app->db->template();
            $tpl->set([
                        'name' => $app->req->post['name'],
                        'description' => $app->req->post['description'],
                        'content' => $app->req->post['content']
                    ])
                    ->where('id = ?', $id)->_and_()
                    ->where('owner = ?', get_userdata('id'))
                    ->update();
            tc_logger_activity_log_write('Update Record', 'Template', $app->req->post['name'], get_userdata('uname'));
            _tc_flash()->success(_tc_flash()->notice(200), $app->req->server['HTTP_REFERER']);
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    try {
        $tpl = $app->db->template()
                ->where('id = ?', $id)->_and_()
                ->where('owner = ?', get_userdata('id'))
                ->findOne();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    /**
     * If the database table doesn't exist, then it
     * is false and a 404 should be sent.
     */
    if ($tpl == false) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If the query is legit, but there
     * is no data in the table, then 404
     * will be shown.
     */ elseif (empty($tpl) == true) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If data is zero, 404 not found.
     */ elseif (count(_escape($tpl->id)) <= 0) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If we get to this point, the all is well
     * and it is ok to process the query and print
     * the results in a html format.
     */ else {

        tc_register_style('select2');
        tc_register_style('iCheck');
        tc_register_script('select2');
        tc_register_script('iCheck');

        $app->view->display('template/view', [
            'title' => _t('Edit Template'),
            'tpl' => $tpl
                ]
        );
    }
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/template/create/', function() {
    if (!hasPermission('create_campaign')) {
        _tc_flash()->{'error'}(_t("You don't have permission to create templates."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/template/create/', function () use($app) {

    if ($app->req->isPost()) {
        try {
            $tpl = $app->db->template();
            $tpl->insert([
                'name' => $app->req->post['name'],
                'description' => $app->req->post['description'],
                'content' => $app->req->post['content'],
                'owner' => get_userdata('id'),
                'addDate' => \Jenssegers\Date\Date::now()
            ]);
            $ID = $tpl->lastInsertId();

            tc_logger_activity_log_write('New Record', 'Subscriber', $app->req->post['fname'] . ' ' . $app->req->post['lname'], get_userdata('uname'));
            _tc_flash()->success(_tc_flash()->notice(200), get_base_url() . 'template' . '/' . $ID . '/');
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    tc_register_style('select2');
    tc_register_style('iCheck');
    tc_register_script('select2');
    tc_register_script('iCheck');

    $app->view->display('template/create', [
        'title' => _t('Create Template')
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET', '/template/(\d+)/d/', function() use($app) {
    if (!hasPermission('delete_campaign')) {
        _tc_flash()->{'error'}(_t("You don't have permission to delete templates."), $app->req->server['HTTP_REFERER']);
        exit();
    }
});

$app->get('/template/(\d+)/d/', function ($id) use($app) {
    try {
        $app->db->template()
                ->where('owner = ?', get_userdata('id'))->_and_()
                ->where('id = ?', $id)
                ->reset()
                ->findOne($id)
                ->delete();

        _tc_flash()->success(_tc_flash()->notice(200), $app->req->server['HTTP_REFERER']);
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage(), $app->req->server['HTTP_REFERER']);
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage(), $app->req->server['HTTP_REFERER']);
    }
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/server.*', function() {
    if (!hasPermission('manage_campaigns')) {
        _tc_flash()->{'error'}(_t("You don't have permission to access the Servers screen."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/server/', function () use($app) {

    try {
        $servers = $app->db->server()
                ->where('owner = ?', get_userdata('id'))
                ->find();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    tc_register_style('datatables');
    tc_register_style('select2');
    tc_register_script('select2');
    tc_register_script('datatables');

    $app->view->display('server/index', [
        'title' => _t('Servers'),
        'servers' => $servers
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/server/(\d+)/', function() {
    if (!hasPermission('edit_campaign')) {
        _tc_flash()->{'error'}(_t("You don't have permission to edit servers."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/server/(\d+)/', function ($id) use($app) {

    try {
        $node = Node::table('php_encryption')->find(1);
    } catch (TinyC\NodeQ\NodeQException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (NodeQException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    if ($app->req->isPost()) {
        try {
            $server = $app->db->server();
            $server->set([
                        'name' => $app->req->post['name'],
                        'hname' => $app->req->post['hname'],
                        'uname' => $app->req->post['uname'],
                        'password' => Crypto::encrypt($app->req->post['password'], Key::loadFromAsciiSafeString($node->key)),
                        'port' => $app->req->post['port'],
                        'protocol' => $app->req->post['protocol'],
                        'throttle' => $app->req->post['throttle'],
                        'femail' => $app->req->post['femail'],
                        'fname' => $app->req->post['fname'],
                        'remail' => $app->req->post['remail'],
                        'rname' => $app->req->post['rname']
                    ])
                    ->where('id = ?', $id)->_and_()
                    ->where('owner = ?', get_userdata('id'))
                    ->update();
            tc_logger_activity_log_write('Update Record', 'Server', $app->req->post['name'], get_userdata('uname'));
            _tc_flash()->success(_tc_flash()->notice(200), $app->req->server['HTTP_REFERER']);
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    try {
        $server = $app->db->server()
                ->where('id = ?', $id)->_and_()
                ->where('owner = ?', get_userdata('id'))
                ->findOne();

        try {
            $password = Crypto::decrypt(_escape($server->password), Key::loadFromAsciiSafeString($node->key));
        } catch (Defuse\Crypto\Exception\BadFormatException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    /**
     * If the database table doesn't exist, then it
     * is false and a 404 should be sent.
     */
    if ($server == false) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If the query is legit, but there
     * is no data in the table, then 404
     * will be shown.
     */ elseif (empty($server) == true) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If data is zero, 404 not found.
     */ elseif (count(_escape($server->id)) <= 0) {

        $app->view->display('error/404', ['title' => '404 Error']);
    }
    /**
     * If we get to this point, the all is well
     * and it is ok to process the query and print
     * the results in a html format.
     */ else {

        tc_register_style('select2');
        tc_register_style('iCheck');
        tc_register_script('select2');
        tc_register_script('iCheck');

        $app->view->display('server/view', [
            'title' => _t('Edit Server'),
            'server' => $server,
            'password' => $password
                ]
        );
    }
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/server/create/', function() {
    if (!hasPermission('create_campaign')) {
        _tc_flash()->{'error'}(_t("You don't have permission to create servers."), get_base_url() . 'dashboard' . '/');
    }
});

$app->match('GET|POST', '/server/create/', function () use($app) {

    try {
        $node = Node::table('php_encryption')->find(1);
    } catch (TinyC\NodeQ\NodeQException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (NodeQException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    if ($app->req->isPost()) {
        try {
            $tpl = $app->db->server();
            $tpl->insert([
                'name' => $app->req->post['name'],
                'hname' => $app->req->post['hname'],
                'uname' => $app->req->post['uname'],
                'password' => Crypto::encrypt($app->req->post['password'], Key::loadFromAsciiSafeString($node->key)),
                'port' => $app->req->post['port'],
                'protocol' => $app->req->post['protocol'],
                'throttle' => $app->req->post['throttle'],
                'femail' => $app->req->post['femail'],
                'fname' => $app->req->post['fname'],
                'remail' => $app->req->post['remail'],
                'rname' => $app->req->post['rname'],
                'owner' => get_userdata('id'),
                'addDate' => \Jenssegers\Date\Date::now()
            ]);
            $ID = $tpl->lastInsertId();

            tc_logger_activity_log_write('New Record', 'Server', $app->req->post['name'], get_userdata('uname'));
            _tc_flash()->success(_tc_flash()->notice(200), get_base_url() . 'server' . '/' . $ID . '/');
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    tc_register_style('select2');
    tc_register_style('iCheck');
    tc_register_script('select2');
    tc_register_script('iCheck');

    $app->view->display('server/create', [
        'title' => _t('Create a Server')
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/server/(\d+)/test/', function() use($app) {
    if (!hasPermission('create_campaign')) {
        _tc_flash()->{'error'}(_t("You don't have permission to send from an SMTP server."), $app->req->server['HTTP_REFERER']);
    }
});

$app->match('GET|POST', '/server/(\d+)/test/', function ($id) use($app) {
    $server = get_server_info($id);
    $app->hook->{'do_action_array'}('tinyc_test_email_init', [$server, $app->req->post['to_email'], $app->req->post['subject'], $app->req->post['message'], '']);
    redirect($app->req->server['HTTP_REFERER']);
});

/**
 * Before route check.
 */
$app->before('GET', '/server/(\d+)/d/', function() use($app) {
    if (!hasPermission('delete_campaign')) {
        _tc_flash()->{'error'}(_t("You don't have permission to delete a server."), $app->req->server['HTTP_REFERER']);
        exit();
    }
});

$app->get('/server/(\d+)/d/', function ($id) use($app) {
    try {
        $app->db->server()
                ->where('owner = ?', get_userdata('id'))->_and_()
                ->where('id = ?', $id)
                ->reset()
                ->findOne($id)
                ->delete();

        _tc_flash()->success(_tc_flash()->notice(200), $app->req->server['HTTP_REFERER']);
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage(), $app->req->server['HTTP_REFERER']);
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage(), $app->req->server['HTTP_REFERER']);
    }
});

$app->get('/archive/', function () use($app) {
    try {
        $archives = $app->db->campaign()
                ->where('campaign.archive = "1"')
                ->find();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    tc_register_style('datatables');
    tc_register_script('datatables');

    $app->view->display('index/archives', [
        'title' => _t('Archived Campaigns'),
        'archives' => $archives
            ]
    );
});

$app->get('/archive/(\d+)/', function ($id) use($app) {
    try {
        $cpgn = $app->db->campaign()
                ->where('campaign.id = ?', $id)->_and_()
                ->where('campaign.archive = "1"')
                ->findOne();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    /**
     * If the database table doesn't exist, then it
     * is false and a 404 should be sent.
     */
    if ($cpgn == false) {
        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If the query is legit, but there
     * is no data in the table, then 404
     * will be shown.
     */ elseif (empty($cpgn) == true) {
        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If data is zero, 404 not found.
     */ elseif (count(_escape($cpgn->id)) <= 0) {
        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If we get to this point, the all is well
     * and it is ok to process the query and print
     * the results in a html format.
     */ else {

        $app->view->display('index/view-archive', [
            'title' => _escape($cpgn->subject),
            'cpgn' => $cpgn
                ]
        );
    }
});

/**
 * Before route check.
 */
$app->before('GET', '/confirm/', function() use($app) {
    header('Content-Type: application/json');
    $app->res->_format('json', 404);
    exit();
});

$app->get('/confirm/(\w+)/lid/(\d+)/sid/(\d+)/', function ($code, $lid, $sid) use($app) {

    $list = get_list_by('id', $lid);

    try {
        $subscriber = $app->db->subscriber_list()
                ->select('subscriber_list.lid,subscriber_list.sid')
                ->select('subscriber_list.code,subscriber_list.confirmed,subscriber.email')
                ->_join('subscriber', 'subscriber_list.sid = subscriber.id')
                ->where('subscriber_list.lid = ?', $lid)->_and_()
                ->where('subscriber_list.sid = ?', $sid)->_and_()
                ->where('subscriber_list.code = ?', $code)->_and_()
                ->where('subscriber_list.confirmed = "0"')
                ->findOne();
        /**
         * Check if subscriber has already confirmed subscription.
         */
        if (_escape($subscriber->confirmed) == 1) {
            _tc_flash()->{'error'}(sprint(_t("Your subscription to <strong>%s</strong> has already been confirmed."), _escape($list->name)));
        }
        /**
         * If the database table doesn't exist, then it
         * is false and a 404 should be sent.
         */ elseif ($subscriber == false) {

            _tc_flash()->{'error'}(_tc_flash()->notice(404));
        }
        /**
         * If the query is legit, but there
         * is no data in the table, then 404
         * will be shown.
         */ elseif (empty($subscriber) == true) {

            _tc_flash()->{'error'}(_tc_flash()->notice(404));
        }
        /**
         * If data is zero, 404 not found.
         */ elseif (count(_escape($subscriber->sid)) <= 0) {

            _tc_flash()->{'error'}(_tc_flash()->notice(404));
        }
        /**
         * If we get to this point, the all is well
         * and it is ok to process the query.
         */ else {
            $sub = $app->db->subscriber_list();
            $sub->set([
                        'confirmed' => (int) 1
                    ])
                    ->where('lid = ?', $lid)->_and_()
                    ->where('sid = ?', $sid)->_and_()
                    ->where('code = ?', $code)
                    ->update();

            subscribe_email_node(_escape($list->code), $subscriber);

            $app->hook->{'do_action'}('check_subscriber_email', _escape($subscriber->email));

            if (_escape($list->notify_email) == 1) {
                try {
                    Node::dispense('new_subscriber_notification');
                    $notify = Node::table('new_subscriber_notification');
                    $notify->lid = (int) $lid;
                    $notify->sid = (int) $sid;
                    $notify->sent = (int) 0;
                    $notify->save();
                } catch (NodeQException $e) {
                    Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
                } catch (Exception $e) {
                    Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
                }
            }

            _tc_flash()->success(sprintf(_t("Your subscription to <strong>%s</strong> has been confirmed. Thank you."), _escape($list->name)));
        }
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    $app->view->display('index/status', [
        'title' => _t('Email Confirmed')
            ]
    );
});

/**
 * Before route check.
 */
$app->before('POST', '/subscribe/', function() use($app) {
    if (!$app->req->server['HTTP_REFERER']) {
        header('Content-Type: application/json');
        $app->res->_format('json', 204);
        exit();
    }

    if ($app->req->isPost()) {
        $app->hook->{'do_action'}('validation_check', $app->req->post);
    }
});

$app->post('/subscribe/', function () use($app) {

    /**
     * Check list code is valid.
     */
    $list = get_list_by('code', $app->req->post['code']);
    /**
     * Check if subscriber exists.
     */
    $get_sub = get_subscriber_by('email', $app->req->post['email']);
    if (_escape($get_sub->id) > 0) {
        _tc_flash()->{'error'}(sprintf(_t('Your email is already in the system. <a href="%s"><strong>Click here</strong></a> to update your preferences.'), get_base_url() . 'preferences' . '/' . _escape($get_sub->code) . '/subscriber/' . _escape($get_sub->id) . '/'), get_base_url() . 'status' . '/');
        exit();
    }
    /**
     * Checks if email is valid.
     */
    if (!v::email()->validate($app->req->post['email'])) {
        _tc_flash()->{'error'}(_t('Invalid email address.'), get_base_url() . 'status' . '/');
        exit();
    }
    /**
     * Set spam tolerance.
     */
    \TinyC\tc_StopForumSpam::$spamTolerance = _escape(get_option('spam_tolerance'));
    /**
     * Check if subscriber is actually a spammer.
     */
    if (\TinyC\tc_StopForumSpam::isSpamBotByEmail($app->req->post['email'])) {
        _tc_flash()->{'error'}(_t('Your email address has been flagged as spam and will not be subscribed to the list.'), get_base_url() . 'status' . '/');
        exit();
    }

    try {
        $subscriber = $app->db->subscriber();
        $subscriber->insert([
            'fname' => $app->req->post['fname'],
            'lname' => $app->req->post['lname'],
            'email' => $app->req->post['email'],
            'state' => if_null($app->req->post['state']),
            'country' => if_null($app->req->post['country']),
            'code' => _random_lib()->generateString(50, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
            'ip' => $app->req->server['REMOTE_ADDR'],
            'addedBy' => (int) 1,
            'addDate' => Jenssegers\Date\Date::now()
        ]);
        $sid = $subscriber->lastInsertId();

        $sub_list = $app->db->subscriber_list();
        $sub_list->insert([
            'lid' => _escape($list->id),
            'sid' => $sid,
            'method' => 'subscribe',
            'addDate' => Jenssegers\Date\Date::now(),
            'code' => _random_lib()->generateString(200, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
            'confirmed' => (_escape($list->optin) == 1 ? 0 : 1)
        ]);

        $sub = $app->db->subscriber_list()
                ->where('lid = ?', _escape($list->id))->_and_()
                ->where('sid = ?', $sid)->_and_()
                ->findOne();

        if (_escape($list->notify_email) == 1 && _escape($list->optin) == 0) {
            try {
                Node::dispense('new_subscriber_notification');
                $notify = Node::table('new_subscriber_notification');
                $notify->lid = _escape((int) $list->id);
                $notify->sid = (int) $sid;
                $notify->sent = (int) 0;
                $notify->save();
            } catch (NodeQException $e) {
                Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
            } catch (Exception $e) {
                Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
            }
        }

        tc_cache_flush_namespace('my_subscribers');
        tc_cache_flush_namespace('list_subscribers');
        tc_logger_activity_log_write('New Record', 'Subscriber', $app->req->post['fname'] . ' ' . $app->req->post['lname'], get_user_value('1', 'uname'));
        check_custom_success_url($app->req->post['code'], $sub);
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }
});

/**
 * Before route check.
 */
$app->before('POST', '/asubscribe/', function() use($app) {
    $valid = true;
    if (!$app->req->server['HTTP_REFERER']) {
        $status = _t("error");
        $message = '<font style="color:#ff0000">' . _t("No referrer.") . '</font>';
        $valid = false;
    } elseif ($app->req->post['m6qIHt4Z5evV'] != '' || !empty($app->req->post['m6qIHt4Z5evV'])) {
        $status = _t("error");
        $message = '<font style="color:#ff0000">' . _t("Spam is not allowed.") . '</font>';
        $valid = false;
    } elseif ($app->req->post['YgexGyklrgi1'] != '' || !empty($app->req->post['YgexGyklrgi1'])) {
        $status = _t("error");
        $message = '<font style="color:#ff0000">' . _t("Spam is not allowed.") . '</font>';
        $valid = false;
    }
    if (!$valid) {
        $data = array(
            'status' => $status,
            'message' => $message
        );

        echo json_encode($data);
    }
});

$app->post('/asubscribe/', function () use($app) {
    $valid = true;
    /**
     * Put email into a variable for use.
     */
    $email = $app->req->post['email'];
    /**
     * Retrive list info.
     */
    $list = get_list_by('code', $app->req->post['code']);
    /**
     * Retrieve subscriber info.
     */
    $get_sub = get_subscriber_by('email', $email);
    /**
     * Set spam tolerance.
     */
    \TinyC\tc_StopForumSpam::$spamTolerance = _escape(get_option('spam_tolerance'));
    /**
     * Check if email is empty.
     */
    if (empty($email)) {
        $status = _t("error");
        $message = '<font style="color:#ff0000">' . _t("Email address cannot be blank.") . '</font>';
        $valid = false;
    }
    /**
     * Check if subscriber exists.
     */ elseif (_escape($get_sub->id) > 0) {
        $status = _t("error");
        $message = '<font style="color:#ff0000">' . _t("Your email is already in the system.") . '</font>';
        $valid = false;
    }
    /**
     * Checks if email is valid.
     */ elseif (!v::email()->validate($email)) {
        $status = _t("error");
        $message = '<font style="color:#ff0000">' . _t("You must enter a valid email.") . '</font>';
        $valid = false;
    }
    /**
     * Check if subscriber is actually a spammer.
     */ elseif (\TinyC\tc_StopForumSpam::isSpamBotByEmail($email)) {
        $status = _t("error");
        $message = '<font style="color:#ff0000">' . _t("Your email address was flagged as spam.") . '</font>';
        $valid = false;
    }

    if ($valid) {
        try {
            $subscriber = $app->db->subscriber();
            $subscriber->insert([
                'email' => $email,
                'code' => _random_lib()->generateString(50, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                'ip' => $app->req->server['REMOTE_ADDR'],
                'spammer' => (int) 0,
                'addedBy' => (int) 1,
                'addDate' => Jenssegers\Date\Date::now()
            ]);
            $sid = $subscriber->lastInsertId();

            $sub_list = $app->db->subscriber_list();
            $sub_list->insert([
                'lid' => _escape($list->id),
                'sid' => $sid,
                'method' => 'subscribe',
                'addDate' => Jenssegers\Date\Date::now(),
                'code' => _random_lib()->generateString(200, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                'confirmed' => (_escape($list->optin) == 1 ? 0 : 1)
            ]);

            if (_escape($list->notify_email) == 1 && _escape($list->optin) == 0) {
                try {
                    Node::dispense('new_subscriber_notification');
                    $notify = Node::table('new_subscriber_notification');
                    $notify->lid = _escape((int) $list->id);
                    $notify->sid = (int) $sid;
                    $notify->sent = (int) 0;
                    $notify->save();
                } catch (NodeQException $e) {
                    Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
                } catch (Exception $e) {
                    Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
                }
            }

            $new_sub = $app->db->subscriber_list()
                    ->where('sid = ?', $sid)
                    ->findOne();

            if ($list->optin == 1) {
                // send confirm email and redirect.
                confirm_email_node($list->code, $new_sub);
            } elseif ($list->optin == 0) {
                // send success email and redirect to default success.
                subscribe_email_node($list->code, $new_sub);
            }

            tc_cache_flush_namespace('my_subscribers');
            tc_cache_flush_namespace('list_subscribers');
            $status = _t("success");
            $message = '<font style="color:#008000">' . _t("You have been successfully subscribed. Check your email.") . '</font>';
        } catch (ORMException $e) {
            $status = _t("error");
            $message = '<font style="color:#ff0000">' . _t("Server error.") . '</font>';
            Cascade::getLogger('error')->{'error'}(sprintf('APISTATE[%s]: %s', $e->getCode(), $e->getMessage()));
        } catch (Exception $e) {
            $status = _t("error");
            $message = '<font style="color:#ff0000">' . _t("Server error.") . '</font>';
            Cascade::getLogger('error')->{'error'}(sprintf('APISTATE[%s]: %s', $e->getCode(), $e->getMessage()));
        }
    }
    $data = array(
        'status' => $status,
        'message' => $message
    );

    echo json_encode($data);
});

/**
 * Before route check.
 */
$app->before('GET', '/unsubscribe/', function() use($app) {
    header('Content-Type: application/json');
    $app->res->_format('json', 204);
    exit();
});

$app->get('/unsubscribe/(\w+)/lid/(\d+)/sid/(\d+)/rid/(\d+)/', function ($code, $lid, $sid, $rid) use($app) {

    $list = get_list_by('id', $lid);

    try {
        $subscriber = $app->db->subscriber_list()
                ->select('subscriber_list.lid,subscriber_list.sid')
                ->select('subscriber_list.code,subscriber_list.confirmed,subscriber.email')
                ->_join('subscriber', 'subscriber_list.sid = subscriber.id')
                ->where('subscriber_list.lid = ?', $lid)->_and_()
                ->where('subscriber_list.sid = ?', $sid)->_and_()
                ->where('subscriber_list.code = ?', $code)->_and_()
                ->where('subscriber_list.unsubscribed = "0"')
                ->findOne();
        /**
         * If the database table doesn't exist, then it
         * is false and a 404 should be sent.
         */
        if ($subscriber == false) {

            _tc_flash()->{'error'}(_tc_flash()->notice(404));
        }
        /**
         * If the query is legit, but there
         * is no data in the table, then 404
         * will be shown.
         */ elseif (empty($subscriber) == true) {

            _tc_flash()->{'error'}(_tc_flash()->notice(404));
        }
        /**
         * If data is zero, 404 not found.
         */ elseif (count(_escape($subscriber->sid)) <= 0) {

            _tc_flash()->{'error'}(_tc_flash()->notice(404));
        }
        /**
         * If we get to this point, then all is well
         * and it is ok to process the query.
         */ else {
            $sub = $app->db->subscriber_list();
            $sub->set([
                        'unsubscribed' => (int) 1
                    ])
                    ->where('lid = ?', $lid)->_and_()
                    ->where('sid = ?', $sid)->_and_()
                    ->where('code = ?', $code)
                    ->update();
            try {
                $upd = $app->db->campaign_queue();
                $upd->set([
                    'is_unsubscribed' => (int) 1,
                    'timestamp_unsubscribed' => (string) Jenssegers\Date\Date::now()->format('Y-m-d H:i:s')
                ])->where('id', $rid)->update();
            } catch (ORMException $e) {
                Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
            } catch (Exception $e) {
                Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
            }
            tc_cache_flush_namespace('my_subscribers');
            tc_cache_flush_namespace('list_subscribers');
            unsubscribe_email_node(_escape($list->code), $subscriber);
            $app->hook->{'do_action'}('check_subscriber_email', _escape($subscriber->email));
            _tc_flash()->success(sprintf(_t("Unsubscribing to mailing list <strong>%s</strong> was successful."), _escape($list->name)));
        }
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    $app->view->display('index/status', [
        'title' => _t('Unsubscribe Confirmed')
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET', '/xunsubscribe/', function() use($app) {
    header('Content-Type: application/json');
    $app->res->_format('json', 204);
    exit();
});

$app->get('/xunsubscribe/(\w+)/lid/(\d+)/sid/(\d+)/rid/(\d+)/', function ($code, $lid, $sid, $rid) use($app) {

    $list = get_list_by('id', $lid);

    try {
        $subscriber = $app->db->subscriber_list()
                ->select('subscriber_list.lid,subscriber_list.sid')
                ->select('subscriber_list.code,subscriber_list.confirmed,subscriber.email')
                ->_join('subscriber', 'subscriber_list.sid = subscriber.id')
                ->where('subscriber_list.lid = ?', $lid)->_and_()
                ->where('subscriber_list.sid = ?', $sid)->_and_()
                ->where('subscriber_list.code = ?', $code)->_and_()
                ->where('subscriber_list.unsubscribed = "0"')
                ->findOne();
        /**
         * If the database table doesn't exist, then it
         * is false and a 404 should be sent.
         */
        if ($subscriber == false) {

            header('Content-Type: application/json');
            $app->res->_format('json', 404);
            exit();
        }
        /**
         * If the query is legit, but there
         * is no data in the table, then 404
         * will be shown.
         */ elseif (empty($subscriber) == true) {

            header('Content-Type: application/json');
            $app->res->_format('json', 404);
            exit();
        }
        /**
         * If data is zero, 404 not found.
         */ elseif (count(_escape($subscriber->sid)) <= 0) {

            header('Content-Type: application/json');
            $app->res->_format('json', 404);
            exit();
        }
        /**
         * If we get to this point, then all is well
         * and it is ok to process the query.
         */ else {
            $sub = $app->db->subscriber_list();
            $sub->set([
                        'unsubscribed' => (int) 1
                    ])
                    ->where('lid = ?', $lid)->_and_()
                    ->where('sid = ?', $sid)->_and_()
                    ->where('code = ?', $code)
                    ->update();
            try {
                $upd = $app->db->campaign_queue();
                $upd->set([
                            'is_unsubscribed' => (int) 1,
                            'timestamp_unsubscribed' => (string) Jenssegers\Date\Date::now()->format('Y-m-d H:i:s')
                        ])
                        ->where('id', $rid)
                        ->update();
            } catch (ORMException $e) {
                Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
            } catch (Exception $e) {
                Cascade::getLogger('error')->{'error'}(sprintf('NODEQSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
            }
            tc_cache_flush_namespace('my_subscribers');
            tc_cache_flush_namespace('list_subscribers');
            unsubscribe_email_node(_escape($list->code), $subscriber);
            $app->hook->{'do_action'}('check_subscriber_email', _escape($subscriber->email));
        }
    } catch (ORMException $e) {
        Cascade::getLogger('error')->{'error'}(sprintf('SQLSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
    } catch (Exception $e) {
        Cascade::getLogger('error')->{'error'}(sprintf('SQLSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
    }
});

/**
 * Before route check.
 */
$app->before('GET', '/tracking/', function() use($app) {
    header('Content-Type: application/json');
    $app->res->_format('json', 204);
    exit();
});

/**
 * Before route check.
 */
$app->before('GET', '/tracking/cid/', function() use($app) {
    header('Content-Type: application/json');
    $app->res->_format('json', 204);
    exit();
});

$app->get('/tracking/cid/(\d+)/sid/(\d+)/', function ($cid, $sid) use($app) {

    //Begin the header output
    header('Content-Type: image/png');

    try {
        $tracking = $app->db->tracking()
                ->where('cid = ?', $cid)->_and_()
                ->where('sid = ?', $sid)
                ->count('id');

        if ($tracking <= 0) {
            $track = $app->db->tracking();
            $track->insert([
                'cid' => $cid,
                'sid' => $sid,
                'first_open' => \Jenssegers\Date\Date::now(),
                'viewed' => +1
            ]);

            $app->db->query(
                    "UPDATE `campaign` "
                    . "SET `viewed` = `viewed` + 1 "
                    . "WHERE `id` = ?", [$cid]
            );
        } else {
            $track = $app->db->tracking()
                    ->where('cid = ?', $cid)->_and_()
                    ->where('sid = ?', $sid)
                    ->findOne();
            $track->set([
                        'viewed' => _escape((int) $track->viewed) + 1
                    ])
                    ->update();

            /* $campaign = $app->db->campaign()
              ->where('id = ?', $cid)
              ->findOne(); */
            $app->db->query(
                    "UPDATE `campaign` "
                    . "SET `viewed` = `viewed` + 1 "
                    . "WHERE `id` = ?", [$cid]
            );
            /* $cpgn2 = $app->db->campaign();
              $cpgn2->viewed = _escape((int)$campaign->viewed) +1;
              $cpgn2->where('id = ?', $cid)
              ->update(); */
        }
        tc_cache_flush_namespace('domain_report');
        tc_cache_flush_namespace('oday_report');
        tc_cache_flush_namespace('ohour_report');
        tc_cache_flush_namespace('cday_report');
        tc_cache_flush_namespace('chour_report');
    } catch (ORMException $e) {
        Cascade::getLogger('error')->{'error'}(sprintf('SQLSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
    } catch (Exception $e) {
        Cascade::getLogger('error')->{'error'}(sprintf('SQLSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
    }
    //Get the http URI to the image
    $img = get_base_url() . 'static/assets/img/blank.png';

    //Get the filesize of the image for headers
    $filesize = filesize(BASE_PATH . 'static/assets/img/blank.png');

    //Now actually output the image requested, while disregarding if the database was affected
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Disposition: attachment; filename="blank.png"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $filesize);
    readfile($img);

    //All done, get out!
    exit();
});

$app->get('/lt/', function () use($app) {

    $parsed = parse_url($app->req->get['url'], PHP_URL_QUERY);
    parse_str($parsed, $output);
    parse_str($app->req->server['QUERY_STRING'], $string);

    /**
     * Remove all parameters to url.
     */
    $url = strtok($app->req->get['url'], '?');

    try {
        if (strpos($app->req->get['utm_campaign'], '_') !== false) {
            $cid = $app->req->get['cid'];
        } else {
            $cid = $string['utm_campaign'];
        }

        if (is_numeric($app->req->get['utm_term']) !== false) {
            $sid = $app->req->get['utm_term'];
        } else {
            $sid = $app->req->get['sid'];
        }

        $tracking = $app->db->tracking_link()
                ->where('cid = ?', $cid)->_and_()
                ->where('sid = ?', $sid)->_and_()
                ->where('url = ?', $url)
                ->count('id');

        if ($tracking <= 0) {
            $track1 = $app->db->tracking_link();
            $track1->insert([
                'cid' => $cid,
                'sid' => $sid,
                'source' => $output['utm_source'],
                'medium' => $string['utm_medium'],
                'url' => $url,
                'clicked' => +1,
                'addDate' => \Jenssegers\Date\Date::now()
            ]);
        } else {
            $track2 = $app->db->tracking_link()
                    ->where('cid = ?', $cid)->_and_()
                    ->where('sid = ?', $sid)->_and_()
                    ->where('url = ?', $url)
                    ->findOne();
            $track2->set([
                        'clicked' => _escape((int) $track2->clicked) + 1
                    ])
                    ->update();
        }
        tc_cache_flush_namespace('domain_report');
        tc_cache_flush_namespace('oday_report');
        tc_cache_flush_namespace('ohour_report');
        tc_cache_flush_namespace('cday_report');
        tc_cache_flush_namespace('chour_report');
    } catch (ORMException $e) {
        Cascade::getLogger('error')->{'error'}(sprintf('SQLSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
    } catch (Exception $e) {
        Cascade::getLogger('error')->{'error'}(sprintf('SQLSTATE[%s]: %s', $e->getCode(), $e->getMessage()));
    }
    redirect($app->req->get['url'] . $app->req->server['QUERY_STRING']);
});

/**
 * Before route check.
 */
$app->before('GET', '/status/', function() use($app) {
    if (!$app->req->server['HTTP_REFERER']) {
        header('Content-Type: application/json');
        $app->res->_format('json', 204);
        exit();
    }
});

$app->get('/status/', function () use($app) {

    $app->view->display('index/status', [
        'title' => _t('Status')
            ]
    );
});

$app->before('GET', '/spam/', function() use($app) {
    if (!$app->req->server['HTTP_REFERER']) {
        header('Content-Type: application/json');
        $app->res->_format('json', 204);
        exit();
    }
});

$app->get('/spam/', function () use($app) {

    $app->view->display('index/status', [
        'title' => _t('No Spamming!')
            ]
    );
});

/**
 * Before route check.
 */
$app->before('GET|POST', '/logout/', function() {
    if (!is_user_logged_in()) {
        _tc_flash()->{'error'}(_t('You must first be logged in before you can logout.'), get_base_url());
        exit();
    }
});

$app->get('/logout/', function () {

    tc_logger_activity_log_write('Authentication', 'Logout', get_name(get_userdata('id')), get_userdata('uname'));
    /**
     * This function is documented in app/functions/auth-function.php.
     * 
     * @since 6.2.0
     */
    tc_clear_auth_cookie();

    redirect(get_base_url());
});

$app->match('GET|POST', '/preferences/', function () use($app) {
    header('Content-Type: application/json');
    $app->res->_format('json', 204);
    exit();
});

$app->match('GET|POST', '/preferences/(\w+)/subscriber/(\d+)/', function ($code, $id) use($app) {

    if ($app->req->isPost()) {
        try {
            $subscriber = $app->db->subscriber();
            $subscriber->set([
                'fname' => $app->req->post['fname'],
                'lname' => $app->req->post['lname'],
                'email' => $app->req->post['email'],
                'address1' => $app->req->post['address1'],
                'address2' => $app->req->post['address2'],
                'city' => $app->req->post['city'],
                'state' => if_null($app->req->post['state']),
                'postal_code' => $app->req->post['postal_code'],
                'country' => if_null($app->req->post['country'])
            ]);
            $subscriber->where('id = ?', $id)
                    ->update();

            $data = [];
            $data['lid'] = $app->req->post['lid'];

            foreach ($app->req->post['id'] as $list) {
                $sub = $app->db->subscriber_list()
                        ->where('sid = ?', $id)->_and_()
                        ->where('lid = ?', $list)
                        ->findOne();

                if ($sub == false && $list == $data['lid'][$list]) {
                    $sub_list = $app->db->subscriber_list();
                    $sub_list->insert([
                        'lid' => $list,
                        'sid' => $id,
                        'addDate' => Jenssegers\Date\Date::now(),
                        'code' => _random_lib()->generateString(100, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                        'confirmed' => 1
                    ]);
                } else {
                    $sub_list = $app->db->subscriber_list();
                    $sub_list->set([
                                'lid' => $list,
                                'sid' => $id,
                                'unsubscribed' => ($list > $data['lid'][$list] ? (int) 1 : (int) 0)
                            ])
                            ->where('sid = ?', $id)->_and_()
                            ->where('lid = ?', $list)
                            ->update();
                }
            }

            tc_cache_delete($id, 'subscriber');
            _tc_flash()->success(_tc_flash()->notice(200), $app->req->server['HTTP_REFERER']);
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage());
        }
    }

    try {
        $get_sub = $app->db->subscriber()
                ->where('code = ?', $code)->_and_()
                ->where('id = ?', $id)
                ->findOne();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage());
    }

    /**
     * If the database table doesn't exist, then it
     * is false and a 404 should be sent.
     */
    if ($get_sub == false) {

        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If the query is legit, but there
     * is no data in the table, then 404
     * will be shown.
     */ elseif (empty($get_sub) == true) {

        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If data is zero, 404 not found.
     */ elseif (_escape($get_sub->id) <= 0) {

        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If we get to this point, the all is well
     * and it is ok to process the query and print
     * the results in a html format.
     */ else {

        tc_register_style('select2');
        tc_register_style('iCheck');
        tc_register_script('select2');
        tc_register_script('iCheck');

        $app->view->display('index/preferences', [
            'title' => _t('My Preferences'),
            'subscriber' => $get_sub
                ]
        );
    }
});

$app->post('/reset-password/', function () use($app) {

    $user = get_user_by('email', $app->req->post['email']);

    if (_escape($user->email) == '') {
        _tc_flash()->{'error'}(_t('A user with that email does not exist.'), get_base_url());
    }

    try {
        $code = _random_lib()->generateString(100, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $pass = $app->db->user();
        $pass->set([
                    'code' => $code,
                ])
                ->where('id = ?', _escape($user->id))
                ->update();

        $domain = get_domain_name();
        $site = _escape(get_option('system_name'));
        $link = get_base_url() . 'password' . '/' . $code . '/';
        $message = _file_get_contents(APP_PATH . 'views/setting/tpl/reset_password.tpl');
        $message = str_replace('{password_reset}', sprintf('<a href="%s" style="display: block;text-decoration: none;font-family: Helvetica, Arial, sans-serif;color: #ffffff;font-weight: bold;text-align: center;"><span style="text-decoration: none;color: #ffffff;text-align: center;display: block;">' . _t('Reset Password') . '</span></a>', $link), $message);
        $message = str_replace('{system_name}', $site, $message);
        $message = str_replace('{email}', _escape($user->email), $message);
        $message = str_replace('{system_url}', get_base_url(), $message);
        $headers = "From: $site <auto-reply@$domain>\r\n";
        if (_escape(get_option('tc_smtp_status')) == 0) {
            $headers .= "X-Mailer: tinyCampaign " . CURRENT_RELEASE . "\r\n";
            $headers .= "MIME-Version: 1.0" . "\r\n";
        }

        try {
            _tc_email()->tc_mail(_escape($user->email), _escape(get_option('system_name')) . ': ' . _t('Password Reset'), $message, $headers);
        } catch (phpmailerException $e) {
            _tc_flash()->{'error'}($e->getMessage(), get_base_url());
        }

        _tc_flash()->success(_t('Please check your email for instructions on changing your password.'), get_base_url());
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage(), get_base_url());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage(), get_base_url());
    }
});

$app->match('GET|POST', '/password/(\w+)/', function ($code) use($app) {

    try {
        $user = $app->db->user()
                ->where('code = ?', $code)
                ->findOne();
    } catch (ORMException $e) {
        _tc_flash()->{'error'}($e->getMessage(), get_base_url());
    } catch (Exception $e) {
        _tc_flash()->{'error'}($e->getMessage(), get_base_url());
    }

    if ($app->req->isPost()) {
        if ($app->req->post['password'] != $app->req->post['confirm']) {
            _tc_flash()->{'error'}(_t('Passwords did not match.'), $app->req->server['HTTP_REFERER']);
            exit();
        }

        $password = $app->req->post['password'];

        try {
            $pass = $app->db->user();
            $pass->set([
                        'code' => NULL,
                        'password' => tc_hash_password($password)
                    ])
                    ->where('id = ?', _escape($user->id))
                    ->update();

            $domain = get_domain_name();
            $site = _escape(get_option('system_name'));

            $message = _file_get_contents(APP_PATH . 'views/setting/tpl/new_password.tpl');
            $message = str_replace('{password}', $password, $message);
            $message = str_replace('{system_name}', $site, $message);
            $headers = "From: $site <auto-reply@$domain>\r\n";
            if (_escape(get_option('tc_smtp_status')) == 0) {
                $headers .= "X-Mailer: tinyCampaign " . CURRENT_RELEASE . "\r\n";
                $headers .= "MIME-Version: 1.0" . "\r\n";
            }

            try {
                _tc_email()->tc_mail(_escape($user->email), _escape(get_option('system_name')) . ': ' . _t('New Password'), $message, $headers);
            } catch (phpmailerException $e) {
                _tc_flash()->{'error'}($e->getMessage(), get_base_url() . 'status' . '/');
            }

            _tc_flash()->success(_t('Your password was updated successfully.'), get_base_url() . 'status' . '/');
        } catch (ORMException $e) {
            _tc_flash()->{'error'}($e->getMessage(), $app->req->server['HTTP_REFERER']);
        } catch (Exception $e) {
            _tc_flash()->{'error'}($e->getMessage(), $app->req->server['HTTP_REFERER']);
        }
    }

    /**
     * If the database table doesn't exist, then it
     * is false and a 404 should be sent.
     */
    if ($user == false) {

        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If the query is legit, but there
     * is no data in the table, then 404
     * will be shown.
     */ elseif (empty($user) == true) {

        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If data is zero, 404 not found.
     */ elseif (_escape($user->id) <= 0) {

        header('Content-Type: application/json');
        $app->res->_format('json', 404);
        exit();
    }
    /**
     * If we get to this point, the all is well
     * and it is ok to process the query and print
     * the results in a html format.
     */ else {

        tc_register_style('select2');
        tc_register_script('select2');

        $app->view->display('index/password', [
            'title' => _t('New Password'),
            'user' => $user
                ]
        );
    }
});

$app->setError(function () use($app) {

    header('Content-Type: application/json');
    $app->res->_format('json', 204);
    exit();
});
