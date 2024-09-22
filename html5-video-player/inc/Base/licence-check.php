<?php
// Add settings link on plugin page
function your_plugin_settings_link($links) {
    $licence = get_option('h5vp_licence_activated', false);
    $licence ? '<a href="#">Active Licence</a>' : '<a href="#">Deactivate Licence</a>';
    array_unshift($links, $settings_link); 
    return $links; 
  }
   
  $plugin = plugin_basename(__FILE__); 
  add_filter("plugin_action_links_$plugin", 'your_plugin_settings_link' );