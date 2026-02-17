<?php
 if ( ! defined( 'ABSPATH' ) ) exit;

echo wp_kses_post( $content );

?>
<script>
    (function(){
        // find in every second until found the popup
        const findPopup = () => {
            const popup = document.getElementById('<?php echo esc_js( $attributes['targetId'] ); ?>');
            if(popup) {
                const trigger = document.querySelector('.<?php echo esc_js( $attributes['targetId'] ); ?> a');
                
                trigger.addEventListener('click', (e) => {
                    const video = popup.querySelector('video');                
                    e.preventDefault();
                    if(video) {
                        popup.classList.add('h5vp_popup_open');
                        video.play();
                    }
                });
                
                // clsoe popup, find in every second until found the close button
                const findCloseBtn = () => {    
                    const closeBtn = popup.querySelector('.popup_close_button');
                    if(closeBtn) {
                        closeBtn?.addEventListener('click', (e) => {
                            const video = popup.querySelector('video');                
                            e.preventDefault();
                            if(video) {
                                video.pause();
                                popup.classList.remove('h5vp_popup_open');
                            }
                        });
                    } else {
                        setTimeout(() => {
                            findCloseBtn();
                        }, 500);
                    }
                }
                findCloseBtn();
            } else {
                setTimeout(() => {
                    findPopup();
                }, 500);
            }
        }
        findPopup();
    })()
</script>