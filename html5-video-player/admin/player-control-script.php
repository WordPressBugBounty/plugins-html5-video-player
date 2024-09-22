<?php
//should be delete later raju
add_action('wp_footer', function () {
    global $post;
    if(!$post){
        return false;
    }
    $result = array();
//get shortcode regex pattern wordpress function
    $pattern = get_shortcode_regex(array('html5_video_player'));
    if (preg_match_all('/' . $pattern . '/s', $post->post_content, $matches)) {
        $keys = array();
        $result = array();
        foreach ($matches[0] as $key => $value) {
            // $matches[3] return the shortcode attribute as string
            // replace space with '&' for parse_str() function
            $get = str_replace(" ", "&", $matches[3][$key]);
            parse_str($get, $output);

            //get all shortcode attribute keys
            $keys = array_unique(array_merge($keys, array_keys($output)));
            $result[] = $output;
        }
        //var_dump($result);
        if ($keys && $result) {
            // Loop the result array and add the missing shortcode attribute key
            foreach ($result as $key => $value) {
                // Loop the shortcode attribute key
                foreach ($keys as $attr_key) {
                    $result[$key][$attr_key] = isset($result[$key][$attr_key]) ? $result[$key][$attr_key] : null;
                }
                //sort the array key
                ksort($result[$key]);
            }
        }
        //display the result
        $allId = array();
        foreach ($result as $hook) {
            $allId[$hook['id']] = $hook['id'];
        }

        foreach($allId as $hookId){
            $id = str_replace('"', "", $hookId);
            $id = str_replace("'", "", $id);
            
            apply_filters('h5vp_player_script_' . $id, null, $id);
        }
    }
    do_action('h5vp_quick_player');
    //do_action('h5vp_player_script', $result);

    global $post;
    $result = array();
//get shortcode regex pattern wordpress function
    $pattern = get_shortcode_regex(array('video_playlist'));
    if (preg_match_all('/' . $pattern . '/s', $post->post_content, $matches)) {
        $keys = array();
        $result = array();
        foreach ($matches[0] as $key => $value) {
            // $matches[3] return the shortcode attribute as string
            // replace space with '&' for parse_str() function
            $get = str_replace(" ", "&", $matches[3][$key]);
            parse_str($get, $output);

            //get all shortcode attribute keys
            $keys = array_unique(array_merge($keys, array_keys($output)));
            $result[] = $output;

        }
        //var_dump($result);
        if ($keys && $result) {
            // Loop the result array and add the missing shortcode attribute key
            foreach ($result as $key => $value) {
                // Loop the shortcode attribute key
                foreach ($keys as $attr_key) {
                    $result[$key][$attr_key] = isset($result[$key][$attr_key]) ? $result[$key][$attr_key] : null;
                }
                //sort the array key
                ksort($result[$key]);
            }
        }
        //display the result
        $allId = array();
        foreach ($result as $hook) {
            $allId[$hook['id']] = $hook['id'];
        }

        foreach($allId as $hookId){
            $id = str_replace('"', "", $hookId);
            $id = str_replace("'", "", $id);
            do_action('video_playlist' . $id, $id);
        }
    }
}, 30);

// add_filter('h5vp_player_script_45', function($content, $id){
//     echo $content;
// },20,2);

// add_filter('h5vp_player_script_42', function($content, $id){
//     echo $content;
// },20,2);