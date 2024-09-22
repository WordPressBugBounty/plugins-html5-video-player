<?php
/*-------------------------------------------------------------------------------*/
// Developer page
/*-------------------------------------------------------------------------------*/

add_action('admin_menu', 'h5vp_pro_support_page');

function h5vp_pro_support_page()
{
    // add_submenu_page('edit.php?post_type=videoplayer', 'Help', 'Help', 'manage_options', 'h5vp-support', 'h5vp_pro_support_page_callback');
}

function h5vp_pro_support_page_callback()
{
    ?>
    <div class="bplugins-container">
        <div class="row">
            <div class="bplugins-features">
                <div class="col col-12">
                    <div class="bplugins-feature center">
                        <h1>Help & Usages</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="bplugins-container">
    <div class="row">
        <div class="bplugins-features">
            <div class="col col-4">
                <div class="bplugins-feature center">
                    <i class="fa fa-life-ring"></i>
                    <h3><?php _e('Need any Assistance?', 'h5vp') ?></h3>
                    <p><?php _e('Our Expert Support Team is always ready to help you out promptly.', 'h5vp') ?></p>
                    <a href="https://bplugins.com/support/" target="_blank" class="button
                    button-primary"><?php _e('Contact Support', 'h5vp') ?></a>
                </div>
            </div>
            <div class="col col-4">
                <div class="bplugins-feature center">
                    <i class="fa fa-file-text"></i>
                    <h3><?php _e('Looking for Documentation?', 'h5vp') ?></h3>
                    <p><?php _e('We have detailed documentation on every aspects of HTML5 Video Player.', 'h5vp') ?></p>
                    <a href="https://bplugins.com/docs/html5-video-player/" target="_blank" class="button button-primary"><?php _e('Documentation', 'h5vp') ?></a>
                </div>
            </div>
            <div class="col col-4">
                <div class="bplugins-feature center">
                    <i class="fa fa-thumbs-up"></i>
                    <h3><?php _e('Like This Plugin?', 'h5vp') ?></h3>
                    <p><?php _e('If you like HTML5 Video Player, please leave us a 5 &#11088; rating.', 'h5vp') ?></p>
                    <a href="https://wordpress.org/support/plugin/html5-video-player/reviews/#new-post" target="_blank" class="button
                    button-primary"><?php _e('Rate the Plugin', 'h5vp') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="bplugins-container">
    <div class="row">
        <div class="bplugins-features">
            <div class="col col-12">
                <div class="bplugins-feature center" style="padding:5px;">
                    <h2 style="font-size:22px;"><?php _e("Looking For Demo?", "h5vp") ?> <a href="https://wpvideoplayer.com/#demos" target="_blank"><?php _e("Click Here" ,"h5vp") ?></a></h2>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="bplugins-container">
    <div class="row">
        <div class="bplugins-features">
            <div class="col col-12">
                <div class="bplugins-feature center">
                    <h1>Video Tutorials</h1><br/>
                    <div class="embed-container"><iframe width="100%" height="700px" src="https://www.youtube.com/embed/dLU67e708fg" frameborder="0"
                    allowfullscreen></iframe></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
}