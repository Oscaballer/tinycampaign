<?php
if (!defined('BASE_PATH'))
    exit('No direct script access allowed');
use app\src\NodeQ\tc_NodeQ as Node;
use app\src\NodeQ\NodeQException;
use app\src\Exception\Exception;
use app\src\NodeQ\Helpers\Validate as Validate;

try {
    if (!Validate::table('php_encryption')->exists()) {
        Node::dispense('php_encryption');
    }
    if (!Validate::table('new_subscriber_notification')->exists()) {
        Node::dispense('new_subscriber_notification');
    }
    /**
     * Add fields to cronjob_handler, if it does not exist.
     * 
     * @since 2.0.3
     */
    $fields = Node::table('cronjob_handler')->fields();
    if(!in_array('status', $fields)) {
        $add_field = Node::table('cronjob_handler');
        $add_field->addFields(['status' => 'integer']);
    }
} catch (NodeQException $e) {
    Cascade\Cascade::getLogger('error')->error(sprintf('NODEQSTATE[%s]: Unable to create Node: %s', $e->getCode(), $e->getMessage()));
} catch (Exception $e) {
    Cascade\Cascade::getLogger('error')->error(sprintf('NODEQSTATE[%s]: Unable to create Node: %s', $e->getCode(), $e->getMessage()));
}
