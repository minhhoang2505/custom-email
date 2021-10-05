<?php
// send an automated user 'inactivity' email
//* Add action on login to update the last_login user meta
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
    //* Get the contributors and authors who haven't logged in in 20 days ( X days)
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
