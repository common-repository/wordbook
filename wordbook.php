<?php
/*
Plugin Name: Wordbook
Plugin URI: http://www.tsaiberspace.net/projects/wordpress/wordbook/
Description: Cross-post your blog updates to your Facebook account. Navigate to <a href="admin.php?page=wordbook">Settings &rarr; Wordbook</a> for configuration.
Author: Robert Tsai
Author URI: http://www.tsaiberspace.net/
Version: 0.16.3
*/

/*
 * Copyright 2009 Robert Tsai (email : wordpress@tsaiberspace.net)
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once('wordbook_admin.php');
require_once('wordbook_credits.php');
require_once('wordbook_db.php');

function wordbook_environment() {
    global $table_prefix, $wp_version;

    define('WORDBOOK_DEBUG', false);
    define('WORDBOOK_TESTING', false);

    $facebook_config['debug'] = WORDBOOK_TESTING && !$_POST['action'];

    define('WORDBOOK_FB_APIKEY', '21e0776b27318e5867ec665a5b18a850');
    define('WORDBOOK_FB_SECRET', 'f342d13c5094bef736842e4832420e8f');
    define('WORDBOOK_FB_APIVERSION', '1.0');
    define('WORDBOOK_FB_DOCPREFIX',
        'http://wiki.developers.facebook.com/index.php/');
    define('WORDBOOK_FB_MAXACTIONLEN', 60);
    define('WORDBOOK_FB_PUBLISH_STREAM', 'publish_stream');

    define('WORDBOOK_OPTIONS', 'wordbook_options');
    define('WORDBOOK_OPTION_SCHEMAVERS', 'schemavers');

    define('WORDBOOK_ERRORLOGS', $table_prefix . 'wordbook_errorlogs');
    define('WORDBOOK_POSTLOGS', $table_prefix . 'wordbook_postlogs');
    define('WORDBOOK_USERDATA', $table_prefix . 'wordbook_userdata');

    define('WORDBOOK_EXCERPT_SHORTSTORY', 256);
    define('WORDBOOK_EXCERPT_WIDEBOX', 96);
    define('WORDBOOK_EXCERPT_NARROWBOX', 40);

    define('WORDBOOK_MINIMUM_ADMIN_LEVEL', 2);      /* Author role or above. */
    define('WORDBOOK_OPTIONS_PAGENAME', 'wordbook');
    define('WORDBOOK_OPTIONS_URL',
        'admin.php?page=' . WORDBOOK_OPTIONS_PAGENAME);

    define('WORDBOOK_SCHEMA_VERSION', 5);

    $wordbook_wp_version_tuple = explode('.', $wp_version);
    define('WORDBOOK_WP_VERSION', $wordbook_wp_version_tuple[0] * 10 +
        $wordbook_wp_version_tuple[1]);

    $wordbook_php_version_tuple = explode('.', phpversion());
    define('WORDBOOK_PHP_VERSION', $wordbook_php_version_tuple[0] * 10 +
        $wordbook_php_version_tuple[1]);

    if (function_exists('json_encode')) {
        define('WORDBOOK_JSON_ENCODE', 'PHP');
    } else {
        define('WORDBOOK_JSON_ENCODE', 'Wordbook');
    }

    if (function_exists('simplexml_load_string')) {
        define('WORDBOOK_SIMPLEXML', 'PHP');
    } else {
        define('WORDBOOK_SIMPLEXML', 'Facebook');
    }

    if (WORDBOOK_PHP_VERSION >= 50) {
        define('FACEBOOK_PHP_API', 'PHP5');
    } else {
        define('FACEBOOK_PHP_API', 'PHP4');
    }
}

function wordbook_debug($message) {
    if (WORDBOOK_DEBUG) {
        $fp = fopen('/tmp/wb.log', 'a');
        $date = date('D M j, g:i:s a');
        fwrite($fp, "$date: $message");
        fclose($fp);
    }
}

function wordbook_load_apis() {
    if (defined('WORDBOOK_APIS_LOADED')) {
        return;
    }
    if (WORDBOOK_JSON_ENCODE == 'Wordbook') {
        function json_encode($var) {
            if (is_array($var)) {
                $encoded = '{';
                $first = true;
                foreach ($var as $key => $value) {
                    if (!$first) {
                        $encoded .= ',';
                    } else {
                        $first = false;
                    }
                    $encoded .= "\"$key\":" . json_encode($value);
                }
                $encoded .= '}';
                return $encoded;
            }
            if (is_string($var)) {
                return "\"$var\"";
            }
            return $var;
        }
    }
    if (FACEBOOK_PHP_API == 'PHP5') {
        if (!class_exists('Facebook')) {
            /* Defend against other plugins. */
            require_once('facebook-platform/php/facebook.php');
        }
        require_once('wordbook_php5.php');
    }
    define('WORDBOOK_APIS_LOADED', true);
}

/******************************************************************************
 * Facebook API wrappers.
 */

function wordbook_fbclient($wbuser) {
    wordbook_load_apis();
    $secret = null;
    $session_key = null;
    if ($wbuser) {
        $secret = $wbuser->secret;
        $session_key = $wbuser->session_key;
    }
    if (!$secret) {
        $secret = WORDBOOK_FB_SECRET;
    }
    if (!$session_key) {
        $session_key = '';
    }
    return wordbook_rest_client($secret, $session_key);
}

function wordbook_fbclient_facebook_finish($wbuser, $result, $method,
        $error_code, $error_msg, $postid) {
    if ($error_code) {
        wordbook_set_userdata_facebook_error($wbuser, $method, $error_code,
            $error_msg, $postid);
    } else {
        wordbook_clear_userdata_facebook_error($wbuser);
    }
    return $result;
}

function wordbook_fbclient_setfbml($wbuser, $fbclient, $postid,
        $exclude_postid) {
    list($result, $error_code, $error_msg) = wordbook_fbclient_setfbml_impl(
        $fbclient, wordbook_fbmltext($exclude_postid));
    return wordbook_fbclient_facebook_finish($wbuser, $result,
        'profile.setFBML', $error_code, $error_msg, $postid);
}

function wordbook_fbclient_publishaction($wbuser, $fbuid, $fbname, $fbclient,
        $postid) {
    $post = get_post($postid);
    $post_link = get_permalink($postid);
    $post_title = get_the_title($postid);
    $post_content = $post->post_content;
    $post_excerpt = wordbook_post_excerpt($post_content,
        WORDBOOK_EXCERPT_SHORTSTORY);

    /* Pull out images from <img/> tags. */
    preg_match_all('/<img \s+ ([^>]*\s+)? src \s* = \s* [\'"](.*?)[\'"]/ix',
        $post_content, $matches);
    $images = array();
    foreach ($matches[2] as $ii => $imgsrc) {
        if ($imgsrc) {
            if (stristr(substr($imgsrc, 0, 8), '://') === false) {
                /* Fully-qualify src URL if necessary. */
                $scheme = $_SERVER['HTTPS'] ? 'https' : 'http';
                $new_imgsrc = "$scheme://" . $_SERVER['SERVER_NAME'];
                if ($imgsrc[0] == '/') {
                    $new_imgsrc .= $imgsrc;
                }
                $imgsrc = $new_imgsrc;
            }
            $images[] = array(
                'src' => $imgsrc,
                'href' => $post_link,
                );
        }
    }

    /* Pull out <wpg2> image tags. */
    $wpg2_g2path = get_option('wpg2_g2paths');
    if ($wpg2_g2path) {
        $g2embeduri = $wpg2_g2path['g2_embeduri'];
        if ($g2embeduri) {
            preg_match_all('/<wpg2>(.*?)</ix', $post_content, $wpg_matches);
            foreach ($wpg_matches[1] as $wpgtag) {
                if ($wpgtag) {
                    $images[] = array(
                        'src' => "$g2embeduri?g2_view=core.DownloadItem"
                            . "&g2_itemId=$wpgtag",
                        'href' => $post_link,
                        );
                }
            }
        }
    }

    list($result, $error_code, $error_msg, $method) =
        wordbook_fbclient_publishaction_impl($fbclient, $post_title, $post_link,
            $post_excerpt, $images);
    return wordbook_fbclient_facebook_finish($wbuser, $result, $method,
        $error_code, $error_msg, $postid);
}

/******************************************************************************
 * WordPress hooks: update Facebook when a blog entry gets published.
 */

function wordbook_post_excerpt($content, $maxlength) {
    $excerpt = apply_filters('the_excerpt', $content);
    if (function_exists('strip_shortcodes')) {
        $excerpt = strip_shortcodes($excerpt);
    }
    $excerpt = strip_tags($excerpt);
    if (strlen($excerpt) > $maxlength) {
        $excerpt = substr($excerpt, 0, $maxlength - 3) . '...';
    }
    return $excerpt;
}

function wordbook_fbmltext($exclude_postid) {
    /* Set the Wordbook box to contain a summary of the blog front page
     * (just those posts written by this user). Don't show
     * password-protected posts. */
    global $user_ID, $user_identity, $user_login, $wpdb;

    $blog_link = get_bloginfo('url');
    $blog_name = get_bloginfo('name');
    $blog_atitle = '';
    if (($blog_description = get_bloginfo('description'))) {
        $blog_atitle = $blog_description;
    } else {
        $blog_atitle = $blog_name;
    }
    $author_link = "$blog_link/author/$user_login/";
    $text = <<<EOM
<style>
  td { vertical-align: top; }
  td.time { text-align: right; padding-right: 1ex; }
</style>
<fb:subtitle>
  Blog posts from <a href="$author_link" title="$user_identity's posts at $blog_name" target="$blog_name">$user_identity</a> at <a href="$blog_link" title="$blog_atitle" target="$blog_name">$blog_name</a>
</fb:subtitle>
EOM;

    $posts_per_page = get_option('posts_per_page');
    if ($posts_per_page <= 0) {
        $posts_per_page = 10;
    }
    $exclude_postid_selector = $exclude_postid == null ? "" :
        "AND ID != $exclude_postid";
    $postidrows = $wpdb->get_results("
        SELECT ID
        FROM $wpdb->posts
        WHERE post_type = 'post'
            AND post_status = 'publish'
            AND post_author = $user_ID
            AND post_password = ''
            $exclude_postid_selector
        ORDER BY post_date DESC
        LIMIT $posts_per_page
        ");

    $postid = 0;
    if ($postidrows) {
        $postid = $postidrows[0]->ID;
        $text .= <<<EOM
<div class="minifeed clearfix">
  <table>
EOM;
        foreach ($postidrows as $postidrow) {
            $post = get_post($postidrow->ID);
            $post_link = get_permalink($postidrow->ID);
            $post_title = get_the_title($postidrow->ID);
            $post_date_gmt = strtotime($post->post_date);
            $post_excerpt_wide = wordbook_post_excerpt($post->post_content,
                WORDBOOK_EXCERPT_WIDEBOX);
            $post_excerpt_narrow = wordbook_post_excerpt($post->post_content,
                WORDBOOK_EXCERPT_NARROWBOX);
            $text .= <<<EOM
    <tr>
      <td class="time">
    <span class="date">
      <fb:time t="$post_date_gmt" />
    </span>
      </td>
      <td>
    <a href="$post_link" target="$blog_name">$post_title</a>:
    <fb:wide>$post_excerpt_wide</fb:wide>
    <fb:narrow>$post_excerpt_narrow</fb:narrow>
      </td>
    </tr>
EOM;
        }
        $text .= <<<EOM
  </table>
</div>
EOM;
    } else {
        $text .= "I haven't posted anything (yet).";
    }

    return $text;
}

function wordbook_publish_action($post) {
    wordbook_deletefrom_errorlogs($post->ID);
    if ($post->post_password != '') {
        /* Don't publish password-protected posts to news feed. */
        return null;
    }
    if (!($wbuser = wordbook_get_userdata($post->post_author)) ||
            !$wbuser->session_key) {
        return null;
    }

    /* If publishing a new blog post, update text in "Wordbook" box. */

    $fbclient = wordbook_fbclient($wbuser);
    if ($post->post_type == 'post' && !wordbook_fbclient_setfbml($wbuser,
            $fbclient, $post->ID, null)) {
        return null;
    }

    /*
     * Publish posts to Wall.
     *
     * Don't spam Facebook by re-publishing already-recently-published
     * posts.
     */

    if (!wordbook_postlogged($post->ID)) {
        list($fbuid, $users, $error_code, $error_msg) =
            wordbook_fbclient_getinfo($fbclient, array('name'));
        if ($fbuid && is_array($users) && ($user = $users[0])) {
            $fbname = $user['name'];
        } else {
            $fbname = 'A friend';
        }
        wordbook_fbclient_publishaction($wbuser, $fbuid, $fbname, $fbclient,
            $post->ID);
        wordbook_insertinto_postlogs($post->ID);
    }

    return null;
}

function wordbook_transition_post_status($newstatus, $oldstatus, $post) {
    if ($newstatus == 'publish') {
        return wordbook_publish_action($post);
    }

    $postid = $post->ID;
    if (($wbuser = wordbook_get_userdata($post->post_author)) &&
            $wbuser->session_key) {
        $fbclient = wordbook_fbclient($wbuser);
        list($result, $error_code, $error_msg) = wordbook_fbclient_setfbml(
            $wbuser, $fbclient, $postid, $postid);
    }
}

function wordbook_delete_post($postid) {
    $post = get_post($postid);
    if (($wbuser = wordbook_get_userdata($post->post_author)) &&
            $wbuser->session_key) {
        $fbclient = wordbook_fbclient($wbuser);
        list($result, $error_code, $error_msg) = wordbook_fbclient_setfbml(
            $wbuser, $fbclient, $postid, $postid);
    }
    wordbook_deletefrom_errorlogs($postid);
    wordbook_deletefrom_postlogs($postid);
}

/******************************************************************************
 * Register hooks with WordPress.
 */

wordbook_environment();

/* Plugin maintenance. */
register_deactivation_hook(__FILE__, 'wordbook_deactivate');
add_action('delete_user', 'wordbook_delete_user');
add_action('admin_menu', 'wordbook_admin_menu');

/* Post/page maintenance and publishing hooks. */
add_action('delete_post', 'wordbook_delete_post');

if (WORDBOOK_WP_VERSION >= 23) {
    define('WORDBOOK_HOOK_PRIORITY', 10);    /* Default; see add_action(). */
    add_action('transition_post_status', 'wordbook_transition_post_status',
        WORDBOOK_HOOK_PRIORITY, 3);
} else {
    /* WordPress-2.2. */
    function wordbook_publish($postid) {
        $post = get_post($postid);
        return wordbook_transition_post_status('publish', null, $post);
    }
    add_action('publish_post', 'wordbook_publish');
    add_action('publish_page', 'wordbook_publish');
}

// vim:et sw=4 ts=8
?>
