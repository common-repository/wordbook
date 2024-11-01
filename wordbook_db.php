<?php

function wordbook_deactivate() {
    global $wpdb;

    wp_cache_flush();
    $errors = array();
    foreach (array(
            WORDBOOK_ERRORLOGS,
            WORDBOOK_POSTLOGS,
            WORDBOOK_USERDATA,
            ) as $tablename) {
        $result = $wpdb->query("
            DROP TABLE IF EXISTS $tablename
            ");
        if ($result === false) {
            $errors[] = "Failed to drop $tablename";
        }
    }
    delete_option(WORDBOOK_OPTIONS);
    wp_cache_flush();

    if ($errors) {
        echo '<div id="message" class="updated fade">' . "\n";
        foreach ($errors as $errormsg) {
            _e("$errormsg<br />\n");
        }
        echo "</div>\n";
    }
}

/******************************************************************************
 * DB schema.
 */

function wordbook_upgrade() {
    global $wpdb, $table_prefix;

    $options = wordbook_options();

    if ($options && isset($options[WORDBOOK_OPTION_SCHEMAVERS]) &&
            $options[WORDBOOK_OPTION_SCHEMAVERS] == WORDBOOK_SCHEMA_VERSION) {
        return;
    }

    wp_cache_flush();
    if (!$options || !isset($options[WORDBOOK_OPTION_SCHEMAVERS]) ||
            $options[WORDBOOK_OPTION_SCHEMAVERS] < 5) {
        $errors = array();

        foreach (array(
                WORDBOOK_ERRORLOGS,
                WORDBOOK_POSTLOGS,
                WORDBOOK_USERDATA,
                $table_prefix . 'wordbook_onetimecode',
                ) as $tablename) {
            $result = $wpdb->query("
                DROP TABLE IF EXISTS $tablename
                ");
            if ($result === false) {
                $errors[] = "Failed to drop $tablename";
            }
        }

        $result = $wpdb->query('
            CREATE TABLE ' . WORDBOOK_POSTLOGS . ' (
                `postid` BIGINT(20) NOT NULL
                , `timestamp` TIMESTAMP
            )
            ');
        if ($result === false) {
            $errors[] = 'Failed to create ' . WORDBOOK_POSTLOGS;
        }

        $result = $wpdb->query('
            CREATE TABLE ' . WORDBOOK_ERRORLOGS . ' (
                `timestamp` TIMESTAMP
                , `user_ID` BIGINT(20) UNSIGNED NOT NULL
                , `method` VARCHAR(255) NOT NULL
                , `error_code` INT NOT NULL
                , `error_msg` VARCHAR(80) NOT NULL
                , `postid` BIGINT(20) NOT NULL
            )
            ');
        if ($result === false) {
            $errors[] = 'Failed to create ' . WORDBOOK_ERRORLOGS;
        }

        $result = $wpdb->query('
            CREATE TABLE ' . WORDBOOK_USERDATA . ' (
                `user_ID` BIGINT(20) UNSIGNED NOT NULL
                , `use_facebook` TINYINT(1) NOT NULL DEFAULT 1
                , `onetime_data` LONGTEXT NOT NULL
                , `facebook_error` LONGTEXT NOT NULL
                , `secret` VARCHAR(80) NOT NULL
                , `session_key` VARCHAR(80) NOT NULL
            )
            ');
        if ($result === false) {
            $errors[] = 'Failed to create ' . WORDBOOK_USERDATA;
        }

        if ($errors) {
            echo '<div id="message" class="updated fade">' . "\n";
            foreach ($errors as $errormsg) {
                _e("$errormsg<br />\n");
            }
            echo "</div>\n";
            return;
        }

        $options = array(
            WORDBOOK_OPTION_SCHEMAVERS => 5,
            );
    }

    wordbook_set_options($options);
    wp_cache_flush();
}

function wordbook_delete_user($user_id) {
    global $wpdb;
    $errors = array();
    foreach (array(
            WORDBOOK_USERDATA,
            WORDBOOK_ERRORLOGS,
            ) as $tablename) {
        $result = $wpdb->query('
            DELETE FROM ' . $tablename . '
            WHERE user_ID = ' . $user_id . '
            ');
        if ($result === false) {
            $errors[] = "Failed to remove user $user_id from $tablename";
        }
    }
    if ($errors) {
        echo '<div id="message" class="updated fade">' . "\n";
        foreach ($errors as $errormsg) {
            _e("$errormsg<br />\n");
        }
        echo "</div>\n";
    }
}

/******************************************************************************
 * Wordbook user data.
 */

function wordbook_get_userdata($user_id) {
    global $wpdb;

    $rows = $wpdb->get_results('
        SELECT *
        FROM ' . WORDBOOK_USERDATA . '
        WHERE user_ID = ' . $user_id . '
        ');
    if ($rows) {
        $rows[0]->onetime_data = unserialize($rows[0]->onetime_data);
        $rows[0]->facebook_error =
            unserialize($rows[0]->facebook_error);
        $rows[0]->secret = unserialize($rows[0]->secret);
        $rows[0]->session_key = unserialize($rows[0]->session_key);
        return $rows[0];
    }
    return null;
}

function wordbook_set_userdata($use_facebook, $onetime_data, $facebook_error,
        $secret, $session_key) {
    global $user_ID, $wpdb;
    wordbook_delete_userdata();
    if (WORDBOOK_WP_VERSION >= 25) {
        $result = $wpdb->insert(WORDBOOK_USERDATA,
            array('user_ID' => $user_ID,
                'use_facebook' => ($use_facebook ? 1 : 0),
                'onetime_data' => serialize($onetime_data),
                'facebook_error' => serialize($facebook_error),
                'secret' => serialize($secret),
                'session_key' => serialize($session_key),
                ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
            );
    } else {
        /* N.B.: SQL injection vulnerability from Facebook responses. */
        $result = $wpdb->query("
            INSERT INTO " . WORDBOOK_USERDATA . " (
                user_ID
                , use_facebook
                , onetime_data
                , facebook_error
                , secret
                , session_key
            ) VALUES (
                " . $user_ID . "
                , " . ($use_facebook ? 1 : 0) . "
                , '" . serialize($onetime_data) . "'
                , '" . serialize($facebook_error) . "'
                , '" . serialize($secret) . "'
                , '" . serialize($session_key) . "'
            )
            ");
    }
}

function wordbook_update_userdata($wbuser) {
    return wordbook_set_userdata($wbuser->use_facebook, $wbuser->onetime_data,
        $wbuser->facebook_error, $wbuser->secret, $wbuser->session_key);
}

function wordbook_set_userdata_facebook_error($wbuser, $method, $error_code,
        $error_msg, $postid) {
    $wbuser->facebook_error = array(
        'method' => $method,
        'error_code' => $error_code,
        'error_msg' => $error_msg,
        'postid' => $postid,
        );
    wordbook_update_userdata($wbuser);
    wordbook_appendto_errorlogs($method, $error_code, $error_msg, $postid);
}

function wordbook_clear_userdata_facebook_error($wbuser) {
    $wbuser->facebook_error = null;
    return wordbook_update_userdata($wbuser);
}

function wordbook_delete_userdata() {
    global $user_ID;
    wordbook_delete_user($user_ID);
}

/******************************************************************************
 * Post logs - record time of last post to Facebook
 */

function wordbook_trim_postlogs() {
    /* Forget that something has been posted to Facebook if it's been
     * longer than some delta of time. */
    global $wpdb;
    $result = $wpdb->query('
        DELETE FROM ' . WORDBOOK_POSTLOGS . '
        WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ');
}

function wordbook_postlogged($postid) {
    global $wpdb;
    $rows = $wpdb->get_results('
        SELECT *
        FROM ' . WORDBOOK_POSTLOGS . '
        WHERE postid = ' . $postid . '
            AND timestamp > DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ');
    return $rows ? true : false;
}

function wordbook_insertinto_postlogs($postid) {
    global $wpdb;
    wordbook_deletefrom_postlogs($postid);
    if (!WORDBOOK_TESTING) {
        if (WORDBOOK_WP_VERSION >= 25) {
            $result = $wpdb->insert(WORDBOOK_POSTLOGS,
                array('postid' => $postid),
                array('%d')
                );
        } else {
            $result = $wpdb->query('
                INSERT INTO ' . WORDBOOK_POSTLOGS . ' (
                    postid
                ) VALUES (
                    ' . $postid . '
                )
                ');
        }
    }
}

function wordbook_deletefrom_postlogs($postid) {
    global $wpdb;
    $result = $wpdb->query('
        DELETE FROM ' . WORDBOOK_POSTLOGS . '
        WHERE postid = ' . $postid . '
        ');
}

/******************************************************************************
 * Error logs - record errors
 */

function wordbook_hyperlinked_method($method, $text = null) {
    if (!$text) {
        $text = $method;
    }
    return '<a href="'
        . WORDBOOK_FB_DOCPREFIX . $method . '"'
        . ' title="Facebook API documentation" target="facebook"'
        . '>'
        . $text
        . '</a>';
}

function wordbook_trim_errorlogs() {
    global $wpdb;
    $result = $wpdb->query('
        DELETE FROM ' . WORDBOOK_ERRORLOGS . '
        WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ');
}

function wordbook_clear_errorlogs() {
    global $user_ID, $wpdb;
    $result = $wpdb->query('
        DELETE FROM ' . WORDBOOK_ERRORLOGS . '
        WHERE user_ID = ' . $user_ID . '
        ');
    if ($result === false) {
        echo '<div id="message" class="updated fade">';
        _e('Failed to clear error logs.');
        echo "</div>\n";
    }
}

function wordbook_appendto_errorlogs($method, $error_code, $error_msg,
        $postid) {
    global $user_ID, $wpdb;
    if ($postid == null) {
        $postid = 0;
        $user_id = $user_ID;
    } else {
        $post = get_post($postid);
        $user_id = $post->post_author;
    }
    if (WORDBOOK_WP_VERSION >= 25) {
        $result = $wpdb->insert(WORDBOOK_ERRORLOGS,
            array('user_ID' => $user_id,
                'method' => $method,
                'error_code' => $error_code,
                'error_msg' => $error_msg,
                'postid' => $postid,
                ),
            array('%d', '%s', '%d', '%s', '%d')
            );
    } else {
        /* N.B.: SQL injection vulnerability from Facebook responses. */
        $result = $wpdb->query('
            INSERT INTO ' . WORDBOOK_ERRORLOGS . ' (
                user_ID
                , method
                , error_code
                , error_msg
                , postid
            ) VALUES (
                ' . $user_id . '
                , "' . str_replace('"', '\"', $method) . '"
                , ' . $error_code . '
                , "' . str_replace('"', '\"', $error_msg) . '"
                , ' . $postid . '
            )
            ');
    }
}

function wordbook_deletefrom_errorlogs($postid) {
    global $wpdb;
    $result = $wpdb->query('
        DELETE FROM ' . WORDBOOK_ERRORLOGS . '
        WHERE postid = ' . $postid . '
        ');
}

function wordbook_render_errorlogs() {
    global $user_ID, $wpdb;

    $rows = $wpdb->get_results('
        SELECT *
        FROM ' . WORDBOOK_ERRORLOGS . '
        WHERE user_ID = ' . $user_ID . '
        ORDER BY timestamp
        ');
    if ($rows) {
?>

    <h3><?php _e('Errors'); ?></h3>
    <div class="wordbook_errors">

    <p>
    Your blog is OK, but Wordbook was unable to update your Wall:
    </p>

    <table class="wordbook_errorlogs">
        <tr>
            <th>Timestamp</th>
            <th>Post</th>
            <th>Method</th>
            <th>Error Code</th>
            <th>Error Message</th>
        </tr>

<?php
        foreach ($rows as $row) {
            $hyperlinked_post = '';
            if (($post = get_post($row->postid))) {
                $hyperlinked_post = '<a href="'
                    . get_permalink($row->postid) . '">'
                    . get_the_title($row->postid) . '</a>';
            }
            $hyperlinked_method = wordbook_hyperlinked_method($row->method);
?>

        <tr>
            <td><?php echo $row->timestamp; ?></td>
            <td><?php echo $hyperlinked_post; ?></td>
            <td><?php echo $hyperlinked_method; ?></td>
            <td><?php echo $row->error_code; ?></td>
            <td><?php echo $row->error_msg; ?></td>
        </tr>

<?php
        }
?>

    </table>

    <form action="<?php echo WORDBOOK_OPTIONS_URL; ?>" method="post">
        <input type="hidden" name="action" value="clear_errorlogs" />
        <p class="submit" style="text-align: center;">
            <input type="submit" value="<?php _e('Clear Errors'); ?>" />
        </p>
    </form>

    </div>

<?php
    }
}

function wordbook_options() {
    return get_option(WORDBOOK_OPTIONS);
}

function wordbook_set_options($options) {
    update_option(WORDBOOK_OPTIONS, $options);
}

function wordbook_get_option($key) {
    $options = wordbook_options();
    return isset($options[$key]) ? $options[$key] : null;
}

function wordbook_set_option($key, $value) {
    $options = wordbook_options();
    $options[$key] = $value;
    wordbook_set_options($options);
}

function wordbook_delete_option($key) {
    $options = wordbook_options();
    unset($options[$key]);
    update_option(WORDBOOK_OPTIONS, $options);
}

?>
