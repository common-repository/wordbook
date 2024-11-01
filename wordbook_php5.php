<?php

function wordbook_rest_client($secret, $session_key) {
    return new FacebookRestClient(WORDBOOK_FB_APIKEY, $secret, $session_key);
}

function wordbook_fbclient_setfbml_impl($fbclient, $text) {
    try {
        $result = $fbclient->profile_setFBML(null, null, $text, null, null,
            $text);
        $error_code = null;
        $error_msg = null;
    } catch (Exception $e) {
        $result = null;
        $error_code = $e->getCode();
        $error_msg = $e->getMessage();
    }
    return array($result, $error_code, $error_msg);
}

function wordbook_html_entity_decode($text) {
    return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
}

function wordbook_fbclient_image_as_media($image) {
    return array(
        'type' => 'image',
        'src' => $image['src'],
        'href' => $image['href'],
        );
}

function wordbook_fbclient_publishaction_impl($fbclient, $post_title,
        $post_link, $post_excerpt, $images) {
    try {
        $method = 'stream.publish';
        $media = array_map('wordbook_fbclient_image_as_media', $images);
        $attachment = array(
            'name' => wordbook_html_entity_decode($post_title),
            'href' => $post_link,
            'description' => wordbook_html_entity_decode($post_excerpt),
            'media' => $media,
            );
        $message = wordbook_html_entity_decode($post_title);
        $action_links = array(array(
            'text' => 'Read entire article',
            'href' => $post_link,
            ));
        $result = $fbclient->stream_publish($message,
            json_encode($attachment), json_encode($action_links));
    } catch (Exception $e) {
        $error_code = $e->getCode();
        $error_msg = $e->getMessage();
    }
    return array($result, $error_code, $error_msg, $method);
}

function wordbook_fbclient_getinfo($fbclient, $fields) {
    try {
        $uid = $fbclient->users_getLoggedInUser();
        $users = $fbclient->users_getInfo(array($uid), $fields);
        $error_code = null;
        $error_msg = null;
    } catch (Exception $e) {
        $uid = null;
        $users = null;
        $error_code = $e->getCode();
        $error_msg = $e->getMessage();
    }
    return array($uid, $users, $error_code, $error_msg);
}

function wordbook_fbclient_has_app_permission($fbclient, $ext_perm) {
    try {
        $has_permission = $fbclient->users_hasAppPermission($ext_perm);
        $error_code = null;
        $error_msg = null;
    } catch (Exception $e) {
        $has_permission = null;
        $error_code = $e->getCode();
        $error_msg = $e->getMessage();
    }
    return array($has_permission, $error_code, $error_msg);
}

function wordbook_fbclient_getsession($fbclient, $token) {
    try {
        $result = $fbclient->auth_getSession($token);
        $error_code = null;
        $error_msg = null;
    } catch (Exception $e) {
        $result = null;
        $error_code = $e->getCode();
        $error_msg = $e->getMessage();
    }
    return array($result, $error_code, $error_msg);
}

// vim:et sw=4 ts=8
?>
