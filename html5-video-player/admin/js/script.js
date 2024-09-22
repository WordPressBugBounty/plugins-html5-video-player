(function ($) {
  $(document).ready(function () {
    $(".h5vp_player_type .csf-fieldset .csf--button-group .csf--button").on("click", function () {
      setTimeout(() => {
        $(".h5vp_player_type_controls").addClass("h5vp_player_controls_click_trigger newclassbro");
        $(".h5vp_player_controls_click_trigger").removeClass("h5vp_player_type_controls");
        const playerType = $(this).parent(".csf--button-group").find(".csf--active input").val();
        $('.h5vp_player_controls_click_trigger .csf-fieldset .csf--button-group .csf--button input[value="' + playerType + '"]')
          .parent()
          .trigger("click");

        $(".h5vp_player_controls_click_trigger").removeClass("h5vp_player_controls_click_trigger");
      }, 100);
      setTimeout(() => {
        $(".newclassbro").addClass("h5vp_player_type_controls");
      }, 1000);
    });

    $(".h5vp_player_type_controls .csf-fieldset .csf--button-group .csf--button").on("click", function () {
      setTimeout(() => {
        $(".h5vp_player_type").addClass("h5vp_player_click_trigger");
        $(".h5vp_player_controls_click_trigger").removeClass("h5vp_player_type");
        const playerType = $(this).parent(".csf--button-group").find(".csf--active input").val();
        $('.h5vp_player_click_trigger .csf-fieldset .csf--button-group .csf--button input[value="' + playerType + '"]')
          .parent()
          .trigger("click");
        $(".h5vp_player_click_trigger").removeClass("h5vp_player_click_trigger");
      }, 100);
      setTimeout(() => {
        $(".h5vp_player_click_trigger").addClass("h5vp_player_type");
      }, 1000);
    });

    $(".h5vp_duplicate_player").on("click", function (event) {
      event.preventDefault();
      $.post(
        ajax.ajax_url,
        {
          action: "h5vp_dulicate_player",
          postid: $(this).data("postid"),
          security: $(this).attr("security"),
        },
        function (data) {
          if (data) {
            let location = window.location.href;
            if (location.split("?").length > 1) {
              location = location + "&duplicate=success";
            } else {
              location = location + "?duplicate=success";
            }
            window.location.href = location;
          }
        }
      );
    });

    let url = window.location.href;
    let newUrl = url.search("duplicate=success");
    if (newUrl != "-1") {
      window.history.pushState({ urlPath: "edit.php?post_type=videoplayer" }, "", "edit.php?post_type=videoplayer");
    }

    $(".h5vp_front_shortcode input").on("click", function (e) {
      e.preventDefault();

      let shortcode = $(this).parent().find("input")[0];
      shortcode.select();
      shortcode.setSelectionRange(0, 30);
      document.execCommand("copy");
      $(this).parent().find(".htooltip").text("Copied Successfully!");
    });

    $(".h5vp_front_shortcode input").on("mouseout", function () {
      $(this).parent().find(".htooltip").text("Copy To Clipboard");
    });

    $(".h5vp_show_password").on("click", function () {
      let type = $(this).parent().find("input").attr("type");
      if (type == "password") {
        $(this).parent().find("input").attr("type", "text");
      } else {
        $(this).parent().find("input").attr("type", "password");
      }
    });
  });
})(jQuery);
