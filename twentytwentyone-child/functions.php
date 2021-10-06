<?php
//
///**
// * Check last login
// *
// */
//
///**
// * Display last login time
// *
// */
//
//function last_login() {
//    $last_login = get_the_author_meta('last_login');
//    $format = apply_filters( 'thim_custom_date_format', get_option( 'date_format' ) );
//    $the_login_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $last_login ), $format);
//    return $the_login_date;
//}
//
//////////
//add_action( 'wp_login', 'user_last_login', 10, 2 );
//
//function user_last_login( $user_login, $user )
//{
//    $users_last_login = get_user_meta($user->ID, 'new_user_followup');
//    if (empty($users_last_login)) {
//        $user_last_login_notification_email = array();
//    }
//
//    $user_last_login_notification_email[$user->user_login] = array(
//        'ID' => $user->ID,
//        'email' => $user->user_email,
//        'last_login_time' => time(),
//        'last_email' => last_login()
//    );
//    update_user_meta( $user->ID, 'new_user_followup', 'last_login_time' );
//
//    return $user_login;
//}
////////////////////////////////////////////////
//
///* Set cron for not login email */
///* Create the Schedule */
//wp_schedule_event(time(), 'weekly', 'new_user_followup');
//
///* Create the Followup Sequence */
//add_action('new_user_followup', 'user_followup');
//
//function user_followup($user) {
//    $new_users = get_user_meta($user->ID,'new_user_followup');
//    if (empty($new_users)) {
//        // nothing to process
//        die();
//    }
//
//    $day = 60 * 60 * 24;
//    $current_time = time();
//
//    /* Looping Through the New Users */
//    foreach ($new_users as $user => &$info)
//    {
//
//        if (empty($info['ID']) or empty($info['email'])) {
//            unset($new_users[$user]);
//            continue;
//        }
//
//        /* Stop Sending Reminders */
//        if (is_user_logged_in()) {
//            unset($new_users[$user]);
//            continue;
//        }
//
//        /* Calculate How Long Since Last Login */
//        $days_since_last_login = ($current_time - $info['last_login']) / $day;
//        if ($days_since_last_login < 7) {
//            continue;
//        }
//
//        if ($days_since_last_login > 7) {
//            $email_to_send = ' reminder';
//        }
//
//        /* Send an Email */
//        if (!empty($info['last_email']) and ($info['last_email'] == $email_to_send)) {
//            continue;
//        }
//
//        $info['last_email'] = $email_to_send;
//
//        /* Figure Out Which Email to Send and Send it */
//        $new_user_email = new_user_email($email_to_send);
//        if (empty($new_user_email['subject']) or empty($new_user_email['message'])) {
//            continue;
//        }
//
//        $headers = array("Content-Type: text/html; charset=UTF-8", "From: Website <minhhoang@website.com>");
//
//        wp_mail($info['email'], $new_user_email['subject'], $new_user_email['message'], $headers);
//    }
//
//     update_user_meta( $user->ID, 'new_user_followup', 'last_login_time' );
//}
//
//function new_user_email($stage)
//{
//    $subject = '';
//    $message = '';
//    switch($stage) {
//        case 'reminder':
//            $subject = "Please login your account";
//            $message = "Thank you for your interest etc";
//            break;
//    }
//    return array(
//        'subject' => $subject,
//        'message' => $message
//    );
//}


/**
 * send email if user not login
 */

// B1: Fix last email sau 20 ngày 5/10/2021
//  Đến ngày 12 nếu $ngay_chua_dang_nhap = 7
// Nếu đăng nhập: - Hoàn thành course -> break không gửi mail
// - Chưa hoàn thành course -> lấy course chưa hoàn thành -> query for get_course_by_user -> lấy được list course user
// For: nếu chưa finish -> tạo cron_job với ngày hiện tại - ngày chưa đăng nhập. Nếu >= 7 để gửi email.

//Trường hợp 2:

function last_login() {
    $last_login = get_the_author_meta('last_login');
    $format = apply_filters( 'thim_custom_date_format', get_option( 'date_format' ) );
    $the_login_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $last_login ), $format);
    return $the_login_date;
}

////////
add_action( 'wp_login', 'user_last_login', 10, 2 );

function user_last_login( $user_login, $user )
{
    $users_last_login = get_user_meta($user->ID, 'new_user_followup');
    if (empty($users_last_login)) {
        $user_last_login_notification_email = array();
    }

    $user_last_login_notification_email[$user->user_login] = array(
        'ID' => $user->ID,
        'email' => $user->user_email,
        'last_login' => time(),
        'last_email' => last_login()
    );
    update_user_meta( $user->ID, 'new_user_followup', '' );

    return $user_login;
}

wp_schedule_event(time(), 'weekly', 'new_user_followup');

function lp_list_course( $data, $user ) {
    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT status 
        FROM {$wpdb->prefix}learnpress_user_items WHERE user_id = %d AND status = %s",
        $data['user_id'],
        $data['course_id'], 'finished');

    $result = $wpdb->get_results( $query );


    $new_users = get_user_meta($user->ID,'new_user_followup');
    $day = 60 * 60 * 24;
    $current_time = time();

    if (empty($new_users)) {
        // nothing to process
        die();
    }

    foreach ($new_users as $user => &$info){


        if(is_user_logged_in() && $result == true){
            /* Stop Sending Reminders Email */
            unset($new_users[$user]);
        } else{
            $days_since_last_login = ($current_time - $info['last_login']) / $day;
            if ($days_since_last_login >= 7) {
                $email_to_send = ' reminder';
            }

            /* Send an Email */
            if (!empty($info['last_email']) and ($info['last_email'] == $email_to_send)) {
                continue;
            }
            $info['last_email'] = $email_to_send;

        }
    }


}
///////////////////////////////////////////////////////////////
//* Add action on login to update the last_login user meta
/*
add_action( 'wp_login', '_user_last_login', 10, 2 );
function _user_last_login( $user_login, $user ) {
    update_user_meta( $user->ID, 'last_login', time() );
}

//* Schedule a daily cron event
if( ! wp_next_scheduled( '_inactivity_reminder' ) ) {
    wp_schedule_event( time(), 'daily', '_inactivity_reminder' );
}

//* Add action to daily cron event
add_action( '_inactivity_reminder', '_inactivity_reminder' );
function _inactivity_reminder() {
    //* Get the contributors and authors who haven't logged in in 20 days
    $users = new \WP_User_Query( [
        'role'         => [ 'contributor', 'author', ],
        'meta_key'     => 'last_login',
        'meta_value'   => strtotime( '-20 days' ),
        'meta_compare' => '<',
    ] );
    foreach( $users->get_results() as $user ) {
        wp_mail(
            $user->user_email,
            __( 'Inactivity Notice', 'learn-press' ),
            __( 'We notice you have not logged in for 20 days.' ,'learn-press' )
        );
    }
}
