<?php

if (!defined('BASE_PATH'))
    exit('No direct script access allowed');
use TinyC\Exception\NotFoundException;
use TinyC\Exception\Exception;
use PDOException as ORMException;
use Cascade\Cascade;

/**
 * Dashboard Router
 *
 * @license GPLv3
 *         
 * @since 2.0.0
 * @package tinyCampaign
 * @author Joshua Parker <joshmac3@icloud.com>
 */
/**
 * Before router check.
 */
$app->before('GET|POST', '/dashboard(.*)', function () {
    if (!hasPermission('access_dashboard')) {
        redirect(get_base_url());
    }
});

$app->group('/dashboard', function () use($app) {

    $app->get('/', function () use($app) {

        tc_register_script('highcharts');
        tc_register_script('dashboard');

        $app->view->display('dashboard/index', [
            'title' => _t('Dashboard')
        ]);
    });

    $app->match('GET|POST', '/support/', function () use($app) {
        if ($app->req->isPost()) {
            $name = $app->req->post['name'];
            $email = $app->req->post['email'];
            $topic = $app->req->post['topic'];
            $summary = $app->req->post['summary'];
            $details = $app->req->post['details'];
            $attachment = $name = $app->req->post['attachment'];
        }

        tc_register_style('select2');
        tc_register_script('select2');

        $app->view->display('dashboard/support', [
            'title' => 'Support Ticket'
        ]);
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/system-snapshot/', function () {
        if (!hasPermission('access_settings_screen')) {
            _tc_flash()->error(_t("You don't have permission to view the System Snapshot Report screen."), get_base_url() . 'dashboard' . '/');
        }
    });

    $app->get('/system-snapshot/', function () use($app) {
        try {
            $db = $app->db->query("SELECT version() AS version")->findOne();
            $user = $app->db->user()->where("status = '1'")->count('id');
            $error = $app->db->error()->count('ID');
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage());
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage());
        }
        $app->view->display('dashboard/system-snapshot', [
            'title' => _t('System Snapshot Report'),
            'db' => $db,
            'user' => $user,
            'error' => $error
        ]);
    });

    /**
     * Before route check.
     */
    $app->before('GET|POST', '/flushCache/', function () {
        if (!hasPermission('access_settings_screen')) {
            _tc_flash()->error(_t("You are not allowed to flush the cache."), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/flushCache/', function () use($app) {
        tc_cache_flush();
        _tc_flash()->success(_t('Cache was flushed successfully.'), $app->req->server['HTTP_REFERER']);
    });

    $app->get('/getSubList/', function () use($app) {

        try {
            $q = $app->db->list()
                    ->select('list.name')
                    ->select('COUNT(subscriber_list.sid) AS count')
                    ->_join('subscriber_list', 'list.id = subscriber_list.lid')
                    ->where('subscriber_list.confirmed = "1"')->_and_()
                    ->where('subscriber_list.unsubscribed <> "1"')->_and_()
                    ->where('list.owner = ?', get_userdata('id'))
                    ->groupBy('subscriber_list.lid')
                    ->orderBy('subscriber_list.addDate', 'DESC');
            $results = tc_cache_get('dash_slist', 'dash_slist');
            if (empty($results)) {
                // Use closure as callback
                $results = $q->find(function($data) {
                    $array = [];
                    foreach ($data as $d) {
                        $array[] = $d;
                    }
                    return $array;
                });
                tc_cache_add('dash_slist', $results, 'dash_slist');
            }
            $rows = [];
            foreach ($results as $list) {
                $row[0] = _escape($list['name']);
                $row[1] = _escape($list['count']);
                array_push($rows, $row);
            }
            print json_encode($rows, JSON_NUMERIC_CHECK);
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage());
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage());
        }
    });

    $app->get('/getSentEmail/', function () use($app) {

        try {
            $q = $app->db->campaign()
                    ->select('list.name')
                    ->select('campaign.recipients AS count')
                    ->_join('campaign_list', 'campaign.id = campaign_list.cid')
                    ->_join('list', 'campaign_list.lid = list.id')
                    ->where('campaign.owner = ?', get_userdata('id'))
                    ->groupBy('list.id');
            $results = tc_cache_get('dash_sent', 'dash_sent');
            if (empty($results)) {
                // Use closure as callback
                $results = $q->find(function($data) {
                    $array = [];
                    foreach ($data as $d) {
                        $array[] = $d;
                    }
                    return $array;
                });
                tc_cache_add('dash_sent', $results, 'dash_sent');
            }

            $rows = [];
            foreach ($results as $email) {
                $row[0] = _escape($email['name']);
                $row[1] = _escape($email['count']);
                array_push($rows, $row);
            }
            print json_encode($rows, JSON_NUMERIC_CHECK);
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage());
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage());
        }
    });

    $app->get('/getCpgnList/', function () use($app) {
        try {
            $q = $app->db->campaign()
                    ->select('list.name AS List')
                    ->select('COUNT(campaign_list.id) AS count')
                    ->_join('campaign_list', 'campaign.id = campaign_list.cid')
                    ->_join('list', 'campaign_list.lid = list.id')
                    ->where('campaign.owner = ?', get_userdata('id'))
                    ->groupBy('list.name')
                    ->orderBy('campaign.id', 'DESC')
                    ->limit(10);
            $results = tc_cache_get('dash_cpgns', 'dash_cpgns');
            if (empty($results)) {
                // Use closure as callback
                $results = $q->find(function($data) {
                    $array = [];
                    foreach ($data as $d) {
                        $array[] = $d;
                    }
                    return $array;
                });
                tc_cache_add('dash_cpgns', $results, 'dash_cpgns');
            }

            $rows = [];
            foreach ($results as $c) {
                $row[0] = _escape($c['List']);
                $row[1] = _escape($c['count']);
                array_push($rows, $row);
            }
            print json_encode($rows, JSON_NUMERIC_CHECK);
        } catch (NotFoundException $e) {
            _tc_flash()->error($e->getMessage());
        } catch (Exception $e) {
            _tc_flash()->error($e->getMessage());
        } catch (ORMException $e) {
            _tc_flash()->error($e->getMessage());
        }
    });

    $app->get('/getBouncedEmail/', function () use($app) {

        try {
            $q = $app->db->campaign()
                    ->select('campaign.bounces as count,list.name')
                    ->_join('campaign_list', 'campaign.id = campaign_list.cid')
                    ->_join('list', 'campaign_list.lid = list.id')
                    ->where('campaign.bounces > 0')->_and_()
                    ->where('campaign.owner = ?', get_userdata('id'))
                    ->groupBy('campaign.id');
            $results = tc_cache_get('dash_bounce', 'dash_bounce');
            if (empty($results)) {
                // Use closure as callback
                $results = $q->find(function($data) {
                    $array = [];
                    foreach ($data as $d) {
                        $array[] = $d;
                    }
                    return $array;
                });
                tc_cache_add('dash_bounce', $results, 'dash_bounce');
            }

            $rows = [];
            foreach ($results as $cpgn) {
                $row[0] = _escape($cpgn['name']);
                $row[1] = _escape($cpgn['count']);
                array_push($rows, $row);
            }
            print json_encode($rows, JSON_NUMERIC_CHECK);
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
    $app->before('GET|POST|PATCH|PUT|OPTIONS|DELETE', '/media-connector/', function() {
        if (!is_user_logged_in()) {
            _tc_flash()->{'error'}(_t("You do not have permission to access requested screen"), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->match('GET|POST|PATCH|PUT|OPTIONS|DELETE', '/media-connector/', function () use($app) {
        error_reporting(0);
        try {
            _mkdir(BASE_PATH . 'static' . DS . 'media' . DS . get_userdata('id') . DS);
        } catch (\TinyC\Exception\IOException $e) {
            Cascade::getLogger('error')->error(sprintf('IOSTATE[%s]: Unable to create directory: %s', $e->getCode(), $e->getMessage()));
        }
        $opts = [
            // 'debug' => true,
            'locale' => 'en_US.UTF-8',
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'startPath' => BASE_PATH . 'static' . DS . 'media' . DS . get_userdata('id') . DS,
                    'path' => BASE_PATH . 'static' . DS . 'media' . DS . get_userdata('id') . DS,
                    'alias' => 'Media Library',
                    'mimeDetect' => 'auto',
                    'accessControl' => 'access',
                    'tmbURL' => get_base_url() . 'static/media/' . get_userdata('id') . '/' . '.tmb',
                    'tmpPath' => BASE_PATH . 'static' . DS . 'media' . DS . '.tmb',
                    'URL' => get_base_url() . 'static/media/' . get_userdata('id') . '/',
                    'attributes' => [
                        [
                            'read' => true,
                            'write' => true,
                            'locked' => false
                        ],
                        [
                            'pattern' => '/\__optimized__/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => true
                        ],
                        [
                            'pattern' => '/\.gitkeep/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => true
                        ],
                        [
                            'pattern' => '/\.gitignore/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => true
                        ],
                        [
                            'pattern' => '/\.htaccess/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => true
                        ],
                        [
                            'pattern' => '/\index.html/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
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
                        'text/plain', 'image/png', 'image/jpeg', 'image/gif', 'application/zip',
                        'text/csv', 'application/pdf', 'application/msword', 'application/vnd.ms-excel',
                        'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.ms-excel',
                        'application/vnd.ms-powerpoint', 'video/mp4'
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
    $app->before('GET|POST|PATCH|PUT|OPTIONS|DELETE', '/ftp-connector/', function() {
        if (!is_user_logged_in()) {
            _tc_flash()->{'error'}(_t("You do not have permission to access requested screen"), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->match('GET|POST|PATCH|PUT|OPTIONS|DELETE', '/ftp-connector/', function () use($app) {
        error_reporting(0);
        $opts = [
            // 'debug' => true,
            'locale' => 'en_US.UTF-8',
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'path' => $app->config('cookies.savepath'),
                    'tmpPath' => $app->config('cookies.savepath') . '.tmb',
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
                            'pattern' => '/\.gitkeep/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => true
                        ],
                        [
                            'pattern' => '/\.gitignore/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => true
                        ],
                        [
                            'pattern' => '/\.htaccess/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
                            'locked' => true
                        ],
                        [
                            'pattern' => '/\index.html/',
                            'read' => false,
                            'write' => false,
                            'hidden' => true,
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
                        'text/plain', 'text/html', 'application/json', 'application/xml',
                        'application/javascript'
                    ],
                    'uploadOrder' => ['allow', 'deny']
                ]
            ]
        ];
        // run elFinder
        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
    });

    $app->before('GET', '/ftp/', function() {
        if (!hasPermission('install_plugins')) {
            _tc_flash()->{'error'}(_t('You do not have permission to access FTP.'), get_base_url() . 'dashboard' . '/');
            exit();
        }
    });

    $app->get('/ftp/', function () use($app) {

        $app->view->display('dashboard/ftp', [
            'title' => _t('FTP')
                ]
        );
    });
});

$app->before('GET', '/media/', function() {
    if (!is_user_logged_in()) {
        _tc_flash()->{'error'}(_t('You do not have permission to manage the media library.'), get_base_url() . 'dashboard' . '/');
        exit();
    }
});

$app->get('/media/', function () use($app) {

    $app->view->display('dashboard/media', [
        'title' => _t('Media Library')
            ]
    );
});

$app->setError(function () use($app) {

    $app->view->display('error/404', [
        'title' => '404 Error'
    ]);
});
