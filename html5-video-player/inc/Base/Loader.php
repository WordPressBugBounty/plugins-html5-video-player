<?php
namespace H5VP\Base;

class Loader {

    public function register(){
        // add_action('wp_head', [$this, 'loadAssets']);
    }

    public function loadAssets(){
        ?>
            <script> function loadHVPAssets(){ const element = document.getElementById('h5vp-player-js'); if(!element){ const script = document.createElement('script'); script.src = `<?php echo esc_url(H5VP_PRO_PLUGIN_DIR) ?>dist/player.js?ver=<?php echo esc_html(H5VP_PRO_VER) ?>`; document.getElementsByTagName("head")[0].appendChild(script); } if(typeof hpublic === 'undefined'){ const script = document.createElement('script'); script.innerHTML = `var hpublic = {dir: '<?php echo esc_url(H5VP_PRO_PLUGIN_DIR); ?>', siteUrl: '<?php echo esc_url(site_url()) ?>',userId: <?php echo esc_html(get_current_user_id()) ?>}`; document.getElementsByTagName("head")[0].appendChild(script); }} document.addEventListener('DOMContentLoaded', function(){ const video = document.getElementsByTagName('video');if(video.length){loadHVPAssets();}})</script>
        <?php
    }
}