<?php

function wordbook_fbclient_methods() {
    return array(
        'profile_setFBML',
        'stream_publish',
        'users_getLoggedInUser',
        'users_getInfo',
        'users_hasAppPermission',
        'auth_getSession',
        );
}

function wordbook_fbclient_missing_method($methodname) {
    return !method_exists('FacebookRestClient', $methodname);
}

/******************************************************************************
 * Wordbook setup and administration.
 */

function wordbook_admin_load() {
    if (!$_POST['action']) {
        return;
    }

    switch ($_POST['action']) {

    case 'one_time_code':
        $token = $_POST['one_time_code'];
        $fbclient = wordbook_fbclient(null);
        list($result, $error_code, $error_msg) = wordbook_fbclient_getsession(
            $fbclient, $token);
        if ($result) {
            wordbook_clear_errorlogs();
            $onetime_data = null;
            $secret = $result['secret'];
            $session_key = $result['session_key'];
        } else {
            $onetime_data = array(
                'onetimecode' => $token,
                'error_code' => $error_code,
                'error_msg' => $error_msg,
                );
            $secret = null;
            $session_key = null;
        }
        $use_facebook = true;
        $facebook_error = null;
        wordbook_set_userdata($use_facebook, $onetime_data, $facebook_error,
            $secret, $session_key);
        wp_redirect(WORDBOOK_OPTIONS_URL);
        break;

    case 'delete_userdata':
        wordbook_delete_userdata();
        wp_redirect(WORDBOOK_OPTIONS_URL);
        break;

    case 'clear_errorlogs':
        wordbook_clear_errorlogs();
        wp_redirect(WORDBOOK_OPTIONS_URL);
        break;
    }

    exit;
}

function wordbook_admin_head() {
?>
    <style type="text/css">
    .wordbook_setup { margin: 0 3em; }
    .wordbook_notices { margin: 0 3em; }
    .wordbook_status { margin: 0 3em; }
    .wordbook_errors { margin: 0 3em; }
    .wordbook_thanks { margin: 0 3em; }
    .wordbook_thanks ul { margin: 1em 0 1em 2em; list-style-type: disc; }
    .wordbook_support { margin: 0 3em; }
    .wordbook_support ul { margin: 1em 0 1em 2em; list-style-type: disc; }
    .facebook_picture {
        float: right;
        border: 1px solid black;
        padding: 2px;
        margin: 0 0 1ex 2ex;
    }
    .wordbook_errorcolor { color: #c00; }
    table.wordbook_errorlogs { text-align: center; }
    table.wordbook_errorlogs th, table.wordbook_errorlogs td {
        padding: 0.5ex 1.5em;
    }
    table.wordbook_errorlogs th { background-color: #999; }
    table.wordbook_errorlogs td { background-color: #f66; }
    </style>
<?php
}

function wordbook_option_notices() {
    global $user_ID, $wp_version;
    wordbook_upgrade();
    wordbook_trim_postlogs();
    wordbook_trim_errorlogs();
    $errormsg = null;
    if (WORDBOOK_WP_VERSION < 22) {
        $errormsg = sprintf(__('Wordbook requires'
            . ' <a href="%s">WordPress</a>-2.2'
            . ' or newer (you appear to be running version %s).'),
            'http://wordpress.org/download/', $wp_version);
    } else if (WORDBOOK_PHP_VERSION < 50) {
        $errormsg = sprintf(__('Wordbook requires'
            . ' PHP-5.0 or newer (you appear to be running version %s).'),
            phpversion());
    } else if (!($options = wordbook_options()) ||
            !isset($options[WORDBOOK_OPTION_SCHEMAVERS]) ||
            $options[WORDBOOK_OPTION_SCHEMAVERS] <
            WORDBOOK_SCHEMA_VERSION ||
            !($wbuser = wordbook_get_userdata($user_ID)) ||
            ($wbuser->use_facebook && !$wbuser->session_key) ||
            !($fbclient = wordbook_fbclient($wbuser))) {
        $errormsg = sprintf(__('<a href="%s">Wordbook</a>'
            . ' needs to be set up.'),
            WORDBOOK_OPTIONS_URL);
    } else if ($wbuser->facebook_error) {
        $method = $wbuser->facebook_error['method'];
        $error_code = $wbuser->facebook_error['error_code'];
        $error_msg = $wbuser->facebook_error['error_msg'];
        $postid = $wbuser->facebook_error['postid'];
        $suffix = '';
        if ($postid != null && ($post = get_post($postid))) {
            wordbook_deletefrom_postlogs($postid);
            $suffix = ' for <a href="'
                . get_permalink($postid) . '">'
                . get_the_title($postid) . '</a>';
        }
        $errormsg = sprintf(__('<a href="%s">Wordbook</a>'
            . ' failed to communicate with Facebook' . $suffix . ':'
            . ' method = %s, error_code = %d (%s).'
            . " Your blog is OK, but Facebook didn't get"
            . ' the update.'),
            WORDBOOK_OPTIONS_URL,
            wordbook_hyperlinked_method($method),
            $error_code,
            $error_msg);
        wordbook_clear_userdata_facebook_error($wbuser);
    } else {
        list($has_permission, $error_code, $error_msg) =
            wordbook_fbclient_has_app_permission($fbclient,
            WORDBOOK_FB_PUBLISH_STREAM);
        if (!$has_permission) {
            $errormsg = sprintf(__('<a href="%s">Wordbook</a>'
                . ' needs to be set up.'),
                WORDBOOK_OPTIONS_URL);
        }
    }

    if ($errormsg) {
?>

    <h3><?php _e('Notices'); ?></h3>

    <div class="wordbook_notices" style="background-color: #f66;">
    <p><?php echo $errormsg; ?></p>
    </div>

<?php
    }

    return $errormsg;
}

function wordbook_option_setup($wbuser) {
?>

    <h3><?php _e('Setup'); ?></h3>
    <div class="wordbook_setup">

    <p>Wordbook needs to be linked to your Facebook account. This link will be used to publish your WordPress blog updates to your Wall and your friends' News Feeds, and will not be used for any other purpose.</p>

    <p>First, click the following button to log in to your Facebook account and generate a login code. Record the login code and return to this page:</p>

    <div style="text-align: center;">
    <a href="http://www.facebook.com/code_gen.php?v=<?php echo WORDBOOK_FB_APIVERSION; ?>&api_key=<?php echo WORDBOOK_FB_APIKEY; ?>" target="facebook">
    <img
        src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" />
    </a>
    </div>

    <form action="<?php echo WORDBOOK_OPTIONS_URL; ?>" method="post">
        <p>Next, enter the login code obtained in the previous step:</p>
        <div style="text-align: center;">
            <input type="text" name="one_time_code" id="one_time_code"
                value="<?php echo $wbuser->onetime_data['onetimecode']; ?>"
                size="9" />
        </div>
        <input type="hidden" name="action" value="one_time_code" />

<?php
        if ($wbuser) {
            wordbook_render_onetimeerror($wbuser);
            $wbuser->onetime_data = null;
            wordbook_update_userdata($wbuser);
        }
?>

        <p style="text-align: center;">
            <input type="submit" value="<?php _e('Submit &raquo;'); ?>" />
        </p>
    </form>

    </div>

<?php
}

function wordbook_option_status($wbuser) {
?>

    <h3><?php _e('Status'); ?></h3>
    <div class="wordbook_status">

<?php
    $show_credits = false;
    $fbclient = wordbook_fbclient($wbuser);
    list($fbuid, $users, $error_code, $error_msg) =
        wordbook_fbclient_getinfo($fbclient, array(
            'is_app_user',
            'first_name',
            'name',
            'status',
            'pic',
            ));
    $profile_url = "http://www.facebook.com/profile.php?id=$fbuid";

    if ($fbuid) {
        if (is_array($users)) {
            $user = $users[0];

            if ($user['pic']) {
?>

        <div class="facebook_picture">
        <a href="<?php echo $profile_url; ?>" target="facebook">
        <img src="<?php echo $user['pic']; ?>" />
        </a>
        </div>

<?php
            }

            if (!($name = $user['first_name'])) {
                $name = $user['name'];
            }

            if ($user['status']['message']) {
?>

        <p>
        <a href="<?php echo $profile_url; ?>"><?php echo $name; ?></a>
        <i><?php echo $user['status']['message']; ?></i>
        (<?php echo date('D M j, g:i a T', $user['status']['time']); ?>).
        </p>

<?php
            } else {
?>

        <p>
        Hi, <a href="<?php echo $profile_url; ?>"><?php echo $name; ?></a>!
        </p>

<?php
            }


            if ($user['is_app_user']) {
                wordbook_fbclient_setfbml($wbuser, $fbclient, null, null);

                list($has_permission, $error_code, $error_msg) =
                    wordbook_fbclient_has_app_permission(
                    $fbclient, WORDBOOK_FB_PUBLISH_STREAM);
                if ($has_permission) {
                    $show_credits = true;
?>
        <p>Wordbook appears to be configured and working just fine.</p>
<?php
                } else {

                    $url = "http://www.facebook.com/connect/prompt_permissions.php"
                        . "?v=" . WORDBOOK_FB_APIVERSION
                        . "&api_key=" . WORDBOOK_FB_APIKEY
                        . "&ext_perm=" . WORDBOOK_FB_PUBLISH_STREAM
                        . "&fbconnect=true"
                        . "&display=popup"
                        . "&extern=1"
                        . "&enable_profile_selector=1"
                        ;
?>

        <p>
        Wordbook now requires authorization to publish stories to your Facebook
        profile Wall.
        </p>
        
        <p>
        Click the "facebook" button below (the Facebook authorization page will
        open in a new window). Click "Allow Publishing" and return to this
        window:
        </p>

        <div style="text-align: center;">
        <a href="<?php echo $url; ?>" target="facebook">
        <img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" />
        </a>
        </div>

        <p>
        Finally, <a href="<?php echo WORDBOOK_OPTIONS_URL; ?>">reload</a> this
        page.
        </p>

<?php
                }
?>
        <p>If you like, you can start over from the beginning:</p>

<?php
            } else {
?>

        <p>Wordbook is able to connect to Facebook.</p>

        <p>
        Next, add the
        <a href="http://www.facebook.com/apps/application.php?id=3353257731"
            target="facebook">Wordbook</a>
        application to your Facebook profile:
        </p>

        <div style="text-align: center;">
        <a href="http://www.facebook.com/add.php?api_key=<?php
            echo WORDBOOK_FB_APIKEY;
        ?>" target="facebook">
            <img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" />
        </a>
        </div>

        <p>Or, you can start over from the beginning:</p>

<?php
            }
        } else {
?>

        <p>
        Wordbook is configured and working, but <?php
            echo wordbook_hyperinked_method('users.getInfo');
        ?> failed (no Facebook user for uid <?php echo $fbuid; ?>).
        </p>

        <p>Try resetting the configuration:</p>

<?php
        }
    } else {
?>

        <p>
        Failed to communicate with Facebook: <?php
            echo wordbook_hyperlinked_method('users.getLoggedInUser',
                "error_code = $error_code ($error_msg)");
        ?>.
        </p>
        
        <p>Try resetting the configuration:</p>

<?php
    }
?>

        <form action="<?php echo WORDBOOK_OPTIONS_URL; ?>" method="post">
            <input type="hidden" name="action" value="delete_userdata" />
            <p style="text-align: center;">
                <input type="submit"
                    value="<?php _e('Reset Configuration'); ?>" />
            </p>
        </form>

    </div>

<?php
    return array($show_credits);
}

function wordbook_version_ok($currentvers, $minimumvers) {
    // Canonicalize strings like:
    // 'mysqlnd 5.0.5-dev - 081106 - $Revision: 289630 $'
    if (preg_match('/\d+(\S)*/', $currentvers, $m) === 1) {
        $currentvers = $m[0];
    }
    $current = preg_split('/\D+/', $currentvers);
    $minimum = preg_split('/\D+/', $minimumvers);
    for ($ii = 0; $ii < min(count($current), count($minimum)); $ii++) {
        if ($current[$ii] < $minimum[$ii]) {
            return false;
        }
    }
    if (count($current) < count($minimum)) {
        return false;
    }
    return true;
}

function wordbook_option_support() {
    global $wp_version;
?>

    <h3><?php _e('Support'); ?></h3>
    <div class="wordbook_support">

    For feature requests, bug reports, and general support:
    
    <ul>
    
        <li>Consider upgrading to the <a
        href="http://wordpress.org/download/">latest stable release</a>
        of WordPress.</li>

	<li>Check that you have the <a
	href="http://wordpress.org/extend/plugins/wordbook/">latest release</a>
	of Wordbook.</li>

        <li>Check the <a
        href="http://wordpress.org/extend/plugins/wordbook/other_notes/"
        target="wordpress">WordPress.org Notes</a>.</li>
        
        <li>Try the <a
        href="http://www.facebook.com/board.php?uid=3353257731"
        target="facebook">Wordbook Discussion Board</a>.</li>

    </ul>
    
    Please provide the following information about your installation:

    <ul>
<?php

    $wb_version = 'Unknown';
    if (($wordbook_php = file(dirname(__FILE__) . '/wordbook.php')) &&
            (($versionlines = array_values(preg_grep('/^Version:/',
            $wordbook_php)))) &&
            (($versionstrs = explode(':', $versionlines[0]))) &&
            count($versionstrs) >= 2) {
        $wb_version = trim($versionstrs[1]);
    }

    $phpvers = phpversion();
    $mysqlvers = function_exists('mysqli_get_client_info') ?
         mysqli_get_client_info() :
         'Unknown';

    $info = array(
        'Wordbook' => $wb_version,
        'Facebook PHP API' => FACEBOOK_PHP_API,
        'JSON library' => WORDBOOK_JSON_ENCODE,
        'SimpleXML library' => WORDBOOK_SIMPLEXML,
        'WordPress' => $wp_version,
        'PHP' => $phpvers,
        'MySQL' => $mysqlvers,
        );

    $version_errors = array();
    $phpminvers = '5.0';
    $mysqlminvers = '4.0';
    if (!wordbook_version_ok($phpvers, $phpminvers)) {
        /* PHP-5.0 or greater. */
        $version_errors['PHP'] = $phpminvers;
    }
    if ($mysqlvers != 'Unknown' &&
            !wordbook_version_ok($mysqlvers, $mysqlminvers)) {
        /* MySQL-4.0 or greater. */
        $version_errors['MySQL'] = $mysqlminvers;
    }

    foreach ($info as $key => $value) {
        $suffix = '';
        if (($minvers = $version_errors[$key])) {
            $suffix = " <span class=\"wordbook_errorcolor\">"
                . " (need $key version $minvers or greater)"
                . " </span>";
        }
        echo "<li>$key: <b>$value</b>$suffix</li>";
    }
    if (!function_exists('simplexml_load_string')) {
        echo "<li>XML: your PHP is missing <code>simplexml_load_string()</code></li>";
    }
?>
    </ul>

<?php
    $missing_methods = array_filter(wordbook_fbclient_methods(),
        'wordbook_fbclient_missing_method');
    if (count($missing_methods) > 0) {
?>

    <div class="wordbook_errorcolor">
    Some other Facebook-related plugin has out-of-date facebook-platform
    libraries that are interfering with Wordbook (missing methods:
    <?php echo implode(', ', $missing_methods); ?>). Deactivate each plugin one
    by one until this conflict goes away, then please contact that plugin's
    author for an update. Active Plugins:

    <ul>
<?php
    $plugins = get_plugins();
    foreach (get_option('active_plugins') as $active_plugin) {
        $plugin = $plugins[$active_plugin];
        $title = $plugin['Title'];
        $version = $plugin['Version'];
        echo "\t<li>$title ($version)</li>\n";
    }
?>
    </ul>
    </div>

<?php
    }

    if ($version_errors) {
?>

    <div class="wordbook_errorcolor">
    Your system does not meet the <a
    href="http://wordpress.org/about/requirements/">WordPress minimum
    reqirements</a>. Things are unlikely to work.
    </div>

<?php
    } else if ($mysqlvers == 'Unknown') {
?>

    <div>
    Please ensure that your system meets the <a
    href="http://wordpress.org/about/requirements/">WordPress minimum
    reqirements</a>.
    </div>

<?php
    }
?>
    </div>

<?php
}

function wordbook_option_manager() {
    global $user_ID;
?>

<div class="wrap">
    <h2><?php _e('Wordbook'); ?></h2>

<?php
    if (!wordbook_option_notices() &&
            ($wbuser = wordbook_get_userdata($user_ID)) &&
            $wbuser->session_key) {
        list($show_credits) = wordbook_option_status($wbuser);
        wordbook_render_errorlogs();
        if ($show_credits) {
            wordbook_option_credits();
        }
    } else {
        wordbook_option_setup($wbuser);
    }
    wordbook_option_support();
?>

</div>

<?php
}

function wordbook_admin_menu() {
    if (current_user_can(WORDBOOK_MINIMUM_ADMIN_LEVEL)) {
        $hook = add_options_page('Wordbook Option Manager', 'Wordbook',
            WORDBOOK_MINIMUM_ADMIN_LEVEL, WORDBOOK_OPTIONS_PAGENAME,
            'wordbook_option_manager');
        add_action("load-$hook", 'wordbook_admin_load');
        add_action("admin_head-$hook", 'wordbook_admin_head');
    }
}

/******************************************************************************
 * One-time code (Facebook)
 */

function wordbook_render_onetimeerror($wbuser) {
    if (($result = $wbuser->onetime_data)) {
?>

    <p>
    There was a problem with the one-time code "<?php
        echo $result['onetimecode'];
    ?>": <?php
        echo wordbook_hyperlinked_method('auth.getSession',
            'error_code = '
            . $result['error_code']
            . ' ('
            . $result['error_msg']
            . ')');
    ?>. Try re-submitting it, or try generating a new one-time code.
    </p>

<?php
    }
}

?>
