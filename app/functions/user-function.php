<?php
if (! defined('BASE_PATH'))
    exit('No direct script access allowed');
/**
 * tinyCampaign User Functions
 *
 * @license GPLv3
 *         
 * @since 2.0.0
 * @package tinyCampaign
 * @author Joshua Parker <joshmac3@icloud.com>
 */

$app = \Liten\Liten::getInstance();

function rolePerm($id)
{
    $app = \Liten\Liten::getInstance();
    $role = $app->db->query("SELECT permission from role WHERE ID = ?", [
        $id
    ]);
    $q1 = $role->find(function ($data) {
        $array = [];
        foreach ($data as $d) {
            $array[] = $d;
        }
        return $array;
    });
    $a = [];
    foreach ($q1 as $v) {
        $a[] = $v;
    }
    $sql = $app->db->permission();
    $q2 = $sql->find(function ($data) {
        $array = [];
        foreach ($data as $d) {
            $array[] = $d;
        }
        return $array;
    });
    foreach ($q2 as $r) {
        $perm = maybe_unserialize($v['permission']);
        echo '
				<tr>
					<td>' . $r['permName'] . '</td>
					<td class="text-center">';
        if (in_array($r['permKey'], $perm)) {
            echo '<input type="checkbox" name="permission[]" value="' . $r['permKey'] . '" checked="checked" />';
        } else {
            echo '<input type="checkbox" name="permission[]" value="' . $r['permKey'] . '" />';
        }
        echo '</td>
            </tr>';
    }
}

function userPerm($id)
{
    $app = \Liten\Liten::getInstance();
    $array = [];
    $pp = $app->db->query("SELECT permission FROM user_perms WHERE id = ?", [
        $id
    ]);
    $q = $pp->find(function ($data) {
        $array = [];
        foreach ($data as $d) {
            $array[] = $d;
        }
        return $array;
    });
    foreach ($q as $r) {
        $array[] = $r;
    }
    $userPerm = maybe_unserialize($r['permission']);
    /**
     * Select the role(s) of the user who's
     * id = $id
     */
    $array1 = [];
    $pr = $app->db->query("SELECT roleID from user_roles WHERE id = ?", [
        $id
    ]);
    $q1 = $pr->find(function ($data) {
        $array = [];
        foreach ($data as $d) {
            $array[] = $d;
        }
        return $array;
    });
    foreach ($q1 as $r1) {
        $array1[] = $r1;
    }
    /**
     * Select all the permissions from the role(s)
     * that are connected to the selected user.
     */
    $array2 = [];
    $role = $app->db->query("SELECT permission from role WHERE ID = ?", [
        _h($r1['roleID'])
    ]);
    $q2 = $role->find(function ($data) {
        $array = [];
        foreach ($data as $d) {
            $array[] = $d;
        }
        return $array;
    });
    foreach ($q2 as $r2) {
        $array2[] = $r2;
    }
    $perm = maybe_unserialize($r2['permission']);
    $permission = $app->db->permission();
    $sql = $permission->find(function ($data) {
        $array = [];
        foreach ($data as $d) {
            $array[] = $d;
        }
        return $array;
    });
    foreach ($sql as $row) {
        echo '
            <tr>
                <td>' . $row['permName'] . '</td>
                <td class="text-center">';
        if (in_array($row['permKey'], $perm)) {
            echo '<input type="checkbox" name="permission[]" value="' . $row['permKey'] . '" checked="checked" disabled="disabled" />';
        } elseif ($userPerm != '' && in_array($row['permKey'], $userPerm)) {
            echo '<input type="checkbox" name="permission[]" value="' . $row['permKey'] . '" checked="checked" />';
        } else {
            echo '<input type="checkbox" name="permission[]" value="' . $row['permKey'] . '" />';
        }
        echo '</td>
            </tr>';
    }
}

/**
 * Returns the name of a particular user.
 *
 * @since 2.0.0
 * @param int $id
 *            User id.
 * @return string
 */
function get_name($id)
{
    if ('' == _trim($id)) {
        $message = _t('Invalid user id: empty id given.');
        _incorrectly_called(__FUNCTION__, $message, '2.0.0');
        return;
    }
    
    if (! is_numeric($id)) {
        $message = _t('Invalid user id: user id must be numeric.');
        _incorrectly_called(__FUNCTION__, $message, '2.0.0');
        return;
    }
    
    $name = get_user_by('id', $id);
    
    return _h($name->fname) . ' ' . _h($name->lname);
}

/**
 * Shows selected user's initials instead of
 * his/her's full name.
 *
 * @since 2.0.0
 * @param int $id
 *            User id
 * @param int $initials
 *            Number of initials to show.
 * @return string
 */
function get_initials($id, $initials = 2)
{
    if ('' == _trim($id)) {
        $message = _t('Invalid user ID: empty ID given.');
        _incorrectly_called(__FUNCTION__, $message, '2.0.0');
        return;
    }
    
    if (! is_numeric($id)) {
        $message = _t('Invalid user ID: user id must be numeric.');
        _incorrectly_called(__FUNCTION__, $message, '2.0.0');
        return;
    }
    
    $name = get_user_by('id', $id);
    
    if ($initials == 2) {
        return mb_substr(_h($name->fname), 0, 1, 'UTF-8') . '. ' . mb_substr(_h($name->lname), 0, 1, 'UTF-8') . '.';
    } else {
        return _h($name->lname) . ', ' . mb_substr(_h($name->fname), 0, 1, 'UTF-8') . '.';
    }
}

/**
 * Retrieve requested field from user table
 * based on user's id.
 *
 * @since 2.0.0
 * @param int $id
 *            User ID.
 * @param mixed $field
 *            Data requested of particular user.
 * @return mixed
 */
function get_user_value($id, $field)
{
    $value = get_user_by('id', $id);
    
    return $value->$field;
}

/**
 * Retrieves a list of roles from the roles table.
 *
 * @since 6.0.04
 * @return mixed
 */
function get_perm_roles()
{
    $app = \Liten\Liten::getInstance();
    $query = $app->db->query('SELECT
    		trim(leading "0" from ID) AS roleID, roleName
		FROM role');
    $result = $query->find(function ($data) {
        $array = [];
        foreach ($data as $d) {
            $array[] = $d;
        }
        return $array;
    });
    
    foreach ($result as $r) {
        echo '<option value="' . _h($r['roleID']) . '">' . _h($r['roleName']) . '</option>' . "\n";
    }
}

/**
 * Retrieves user data given a user ID or user array.
 *
 * @since 2.0.0
 * @param int|tc_User|null $user
 *            User ID or user array.
 * @param bool $object
 *            If set to true, data will return as an object, else as an array.
 */
function get_user($user, $object = true)
{
    if ($user instanceof \app\src\tc_User) {
        $_user = $user;
    } elseif (is_array($user)) {
        if (empty($user['id'])) {
            $_user = new \app\src\tc_User($user);
        } else {
            $_user = \app\src\tc_User::get_instance($user['id']);
        }
    } else {
        $_user = \app\src\tc_User::get_instance($user);
    }
    
    if (! $_user) {
        return null;
    }
    
    if ($object == true) {
        $_user = array_to_object($_user);
    }
    
    return $_user;
}

/**
 * Checks whether the given username exists.
 *
 * @since 2.0.0
 * @param string $username
 *            Username to check.
 * @return int|false The user's ID on success, and false on failure.
 */
function username_exists($username)
{
    if ($user = get_user_by('uname', $username)) {
        return $user->id;
    }
    return false;
}

/**
 * Checks whether the given email exists.
 *
 * @since 2.0.0
 * @param string $email
 *            Email to check.
 * @return int|false The user's ID on success, and false on failure.
 */
function email_exists($email)
{
    if ($user = get_user_by('email', $email)) {
        return $user->id;
    }
    return false;
}