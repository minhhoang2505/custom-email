<?php
/**
 *
 */
//* Add action on login to update the last_login user meta
add_action( 'wp_login', 'lp_user_last_login', 10, 2 );
function lp_user_last_login( $user_login, $user ) {
    update_user_meta( $user->ID, 'lp_last_login', time() );
}

//* Schedule a daily cron event
if( ! wp_next_scheduled( 'lp_check_send_email' ) ) {
    wp_schedule_event( time(), 'daily', 'lp_check_send_email' );
}

//* Add action to daily cron event
add_action( 'lp_check_send_email', 'lp_check_send_email_func' );
function lp_check_send_email_func($user, $data) {

    $new_users = get_user_meta($user->ID,'lp_check_send_email');
    if (empty($new_users)) {
        // nothing to process
        die();
    }

    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT status
        FROM {$wpdb->prefix}learnpress_user_items WHERE user_id = %d AND status = %s",
        $data['user_id'],
        $data['course_id'], 'enrolled');
    $user_enrolled = $wpdb->get_results($query);

    //* Get the contributors and authors who haven't logged in in 20 days
    $users = new \WP_User_Query( [
        'role'         => [ 'contributor', 'author', ],
        'meta_key'     => 'lp_last_login',
        'meta_value'   => strtotime( '20 days' ),
        'meta_compare' => '>=',
    ] );
    $email_send_last = $users->get_results();

    if($user_enrolled && $email_send_last) {
        $email_to_send = 'first reminde';
    }

    // Send email weekly
    $day = 60 * 60 * 24;
    $current_time = time();
    foreach ($new_users as $user => &$info)
    {
        if (empty($info['ID']) or empty($info['email'])) {
            unset($new_users[$user]);
            continue;
        }

        $new_lp_user_login = get_user_meta($info['ID'],'lp_user_last_login');
        if (!empty($new_lp_user_login)) {
            unset($new_users[$user]);
            continue;
        }

        $days_since_user_login = ($current_time - $info['lp_last_login']) / $day;
        if ($days_since_user_login < 7) {
            continue;
        }
        if ( !empty($user_enrolled) ) {
            unset($new_users[$user]);
            continue;
        } elseif ($days_since_user_login >= 7) {
            $email_to_send = 'reminder';
        }

        if (!empty($info['last_email']) and ($info['last_email'] == $email_to_send)) {
            continue;
        }
        $info['last_email'] = $email_to_send;
        $send_new_user_email = send_new_user_email($email_to_send);
        if (empty($new_user_email['subject']) or empty($send_new_user_email['message'])) {
            continue;
        }
        $headers = array("Content-Type: text/html; charset=UTF-8", "From: Website <info@website.com>");

        wp_mail($info['email'], $send_new_user_email['subject'], $send_new_user_email['message'], $headers);
    }
    update_user_meta($info['ID'],'lp_user_last_login',$new_users);
}

function send_new_user_email($stage)
{
    $subject = '';
    $message = '';
    switch($stage) {
        case 'first reminder':
            $subject = "Please Complete Your Profile";
            $message = "Thank you for your interest etc";
            break;
        case 'reminder':
            $subject = "Reminder: Please Complete Your Profile";
            $message = "Thank you for your interest etc";
            break;
    }
    return array(
        'subject' => $subject,
        'message' => $message
    );
}
