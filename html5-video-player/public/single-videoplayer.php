<?php wp_head(); ?>
<div class="container" style="max-width: 1140px;margin:1000px auto;">
<style>
    .fluid_video_wrapper {
        height: auto !important;
    }
    .fluid_video_wrapper video {
        height: auto !important;
    }
    .sticky {
        width: 400px;
        height: 300px;
        position: fixed;
        top: 50px;
        right: 50px;
    }
</style>
    <div class="video-wrapper" style="width:300px;height: 250px;">
        <?php 
            while(have_posts()): the_post();
                echo "<h2>".get_the_title()."</h2>";
                //the_content();
            endwhile;
        ?>
    </div>
</div>

<script type="text/javascript">

;(function(){
    $(document).ready(function(){

        let player = $(".video-wrapper");
        let sticky = player.offset().top;
        $(window).on('scroll', function(){
            console.log('scrolling');
            console.log('page offset',window.pageYOffset);
            console.log('stickey',sticky);
            if (window.pageYOffset >= sticky) {
                player.find('h2').addClass("video-sticky")
            } else {
                player.find('h2').removeClass("video-sticky");
            }
        });
    });

})(jQuery);

</script>
<?php wp_footer(); ?>
