<?php

/*
  Plugin Name: WPWA Pluggable Plugin
  Plugin URI:
  Description: Explain the use of pluggable plugins by sending mails on post saving
  Version: 1.0
  Author: Rakhitha Nimesh
  Author URI: http://www.innovativephp.com/
  License: GPLv2 or later
 */

add_action('save_post', 'wpwa_new_topic_notification',10,3);
function wpwa_new_topic_notification($post_id, $post, $update) {
  $post = get_post($post_id );

  if ( !wp_verify_nonce($_POST['topic_meta_nonce'], 'wpwaf-topic-meta' ) ) {
       return $post->ID;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
        return;
  }

  if ( !wp_is_post_revision( $post_id ) && $post->post_type == 'wpwaf_topic' ) {
    $post_title = get_the_title($post_id);
    $post_url = get_permalink($post_id);
    $post_author = get_userdata( $post->post_author );
    wpwa_send_new_topic_notification($post_author->user_email , $post_title, $post_url, $post_id);
  }
}

if (!function_exists('wpwa_send_new_topic_notification')) {
  function wpwa_send_new_topic_notification($email,$heading, $content) {
    $message = "<p><b>$heading</b><br/></p>";
    $message .= "<p>$content<br/></p>";
    
    wp_mail($email, "Pluggable Plugins", $message);
  }
}


add_filter( 'wp_mail_content_type', 'wpwa_mail_content_type' );
function wpwa_mail_content_type() {
    return 'text/html';
}


function wpwa_send_new_topic_notification($email, $heading, $content, $topic_id = '' ) {
  // Send notification to user
  $message = "";
  $message = "<p><b>$heading</b><br/></p>";
  $message .= "<p>$content<br/></p>"; 

  wp_mail($email, "Pluggable Plugins", $message);

  if($topic_id != ''){
    // Send notifications to admins
    $author = get_user_by( 'email', $email );
    $message_admin = $message;
    $message_admin .= "<p>".$author->first_name. " ". $author->last_name . "<br/></p>";  
    $users_query = new WP_User_Query( array( 
                  'role' => 'administrator', 
                  'orderby' => 'display_name'
                  ) );
    $results_admin = $users_query->get_results();

    foreach($results_admin as $user){
      wp_mail($user->user_email,"Pluggable Plugins", $message_admin);
    }

    // Send notification to moderators
    $message_moderator = $message;
    $message_moderator.= "<p>".$author->first_name. " ". $author->last_name . "<br/></p>";  
    $message_moderator .= get_edit_post_link( $topic_id);
    $users_query = new WP_User_Query( array( 
                  'role' => 'wpwaf_moderator', 
                  'orderby' => 'display_name'
                  ) );
    $results_moderator = $users_query->get_results();

    foreach($results_moderator as $user){
      wp_mail($user->user_email,"Pluggable Plugins", $message_moderator);
    }
  }
}

