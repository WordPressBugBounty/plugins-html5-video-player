// identifire 'h5vp-script'
(function ($) {
  $(document).ready(function () {
    // display flex to block for videojs
    $(".video-js").parent().parent().attr("style", "display:block");

    const h5vpVideos = document.querySelectorAll(".h5vp_player");

    Object.keys(h5vpVideos).map((item) => {
      const video = $(h5vpVideos[item]);
      const options = video.data("options");
      const infos = video.data("infos");
      const provider = video.data("provider");
      const id = video.data("id");
      const H5VP_Video_Obj = new H5VP_Video();

      // console.log('player', id)

      setTimeout(() => {
        if (provider == "library" || provider == "amazons3") {
          H5VP_Video_Obj.newVideo(".player" + id + " video", options, infos, id);
        } else {
          H5VP_Video_Obj.newVideo(".player" + id + " div.notlibrary", options, infos, id);
        }
      }, 1000);
    });

    //Owl Carousel for Video Playlist
  });
})(jQuery);

class H5VP_Video {
  constructor() {
    //let myClass = this;
    this.hlsVideo = [];
    this.dashVideo = [];
  }

  quickPlayer() {
    if (typeof ajax.quickPlayer != "undefined") {
      const options = ajax.quickPlayer.options;
      if (typeof i18n != "undefined") {
        options.i18n = i18n;
      }
      options.controls = this.getControls(options.controls);
      if (typeof ajax.globalWorking != "undefined" && ajax.globalWorking == 1) {
        const player = Plyr.setup("video", options);
      } else {
        const player = Plyr.setup("#h5vp_quick_player", options);
      }
    }
  }

  playWithHTML(options, infos = null) {
    options.controls = this.getControls(options.controls);
    const player = Plyr.setup("video", options);
  }

  /**
   *
   * @param {string} selector
   * @param {object} options
   * @param {object} infos
   */
  newVideo(selector, options, infos, id) {
    let windowWidth = window.innerWidth;
    options.seekTime = parseInt(options.seekTime);
    if (typeof i18n != "undefined") {
      options.i18n = i18n;
    }

    //Set Controls Base On Device
    options.controls = this.getControls(options.controls);

    // Create Player Object
    const player = Plyr.setup(selector, options);

    //Count Total Views
    this.countViews(player, infos.id);

    // Do Sticky if Sticky is enabled
    if (infos.sticky == 1 && windowWidth > 768) {
      this.doSticky(player, infos.id);
    }

    //Password Controller
    if (infos.protected) {
      this.passwordController(selector, player, infos, id);
    } else {
      this.setSource(selector, player, infos);
    }
    // return player;
  }

  /**
   * Count total views
   * @param {player object} player
   * @param {int} id
   */
  countViews(player, id) {
    player.map((player) => {
      let i = 0;
      player.on("play", (event) => {
        if (i < 1) {
          jQuery.post(
            ajax.ajax_url,
            {
              action: "video_played",
              data: 1,
              id: id,
            },
            function (data) {}
          );
        }
        i++;
      });
    });
  }

  /**
   * Do sticky if sticky is enabeld
   * @param {player object} player
   * @param {int} id
   */
  doSticky(players, id) {
    players.map((player) => {
      let $ = jQuery;
      let wrapper = $(".h5vp_video_sticky" + id);
      let sticky;
      let videoHeight = 450;
      setTimeout(() => {
        if (typeof wrapper.find("video")[0] !== "undefined") {
          videoHeight = wrapper.height() ? wrapper.height() : 450;
        }
      }, 1000);
      $(window).on("scroll", function () {
        if (player.playing == true) {
          sticky = parseInt(wrapper.find(".plyr--playing").parent().offset().top);
          if (parseInt(window.pageYOffset - 100) > sticky) {
            wrapper.find(".plyr--playing").addClass("video-sticky in");
            wrapper.find(".plyr--playing").parent().find(".close").show();
            setTimeout(() => {
              wrapper.height(videoHeight);
            }, 1100);
            wrapper
              .find(
                "button[data-plyr=restart],button[data-plyr=fast-forward], button[data-plyr=pip],a[data-plyr=download],button[data-plyr=settings], button[data-plyr=rewind]"
              )
              .hide();
          } else {
            wrapper.height("initial");
            wrapper.find(".plyr--playing").removeClass("video-sticky in");
            wrapper.find(".close").hide();
            wrapper
              .find(
                "button[data-plyr=restart],button[data-plyr=fast-forward], button[data-plyr=pip],a[data-plyr=download],button[data-plyr=settings], button[data-plyr=rewind]"
              )
              .show();
          }
        }
        if (parseInt(window.pageYOffset + 100) < sticky) {
          wrapper.height("initial");
          wrapper.find(".plyr--playing").removeClass("video-sticky in");
          wrapper.find(".plyr--paused").removeClass("video-sticky in");
          wrapper.find(".close").hide();
          wrapper
            .find(
              "button[data-plyr=restart],button[data-plyr=fast-forward], button[data-plyr=pip],a[data-plyr=download],button[data-plyr=settings], button[data-plyr=rewind]"
            )
            .show();
        }
      });

      $(".h5vp_video_sticky" + id + " .close").on("click", function () {
        let wrapper = $(".h5vp_video_sticky" + id);
        wrapper.find(".plyr").removeClass("video-sticky in");
        wrapper
          .find(
            "button[data-plyr=restart],button[data-plyr=fast-forward], button[data-plyr=pip],a[data-plyr=download],button[data-plyr=settings], button[data-plyr=rewind]"
          )
          .show();
        player.pause();
        $(this).hide();
      });
    });
  }

  /**
   *
   * @param {object} player
   * @param {object} infos
   */
  passwordController(selector, players, infos, id) {
    let myClass = this;
    let $ = jQuery;
    players.map((player) => {
      $(".h5vp_player .password_form form[video=" + id + "]").on("submit", function (e) {
        e.preventDefault();
        let newThis = this;
        let videoId = $(newThis).attr("video");
        let form = $(newThis).parent();
        let overlay = $(newThis).parent().parent().find(".video_overlay");
        $.post(
          ajax.ajax_url,
          {
            action: "h5vp_password_checker",
            password: $(this).find("#password").val(),
            postid: $(this).find("#postid").val(),
          },
          function (data) {
            if (data == "true") {
              $(newThis).find(".notice").addClass("success").text("Success");
              setTimeout(() => {
                form.hide();
                overlay.hide();
              }, 1000);
              $.post(
                ajax.ajax_url,
                {
                  action: "h5vp_get_protected_video",
                  postid: videoId,
                },
                function (video) {
                  infos.video = JSON.parse(video);
                  myClass.setSource("player" + videoId, [player], infos);
                }
              );
            } else {
              $(newThis).find(".notice").addClass("error").text("Wrong Password");
            }
          }
        );
      });
    });
  }

  /**
   *
   * @param {array with object} VideoQuality
   */
  getQuality(VideoQuality) {
    if (typeof VideoQuality === "object" && VideoQuality !== null) {
      let videoQuality = [];
      let length = VideoQuality.length;
      for (let i = 0; i < length; i++) {
        videoQuality[i] = {
          src: VideoQuality[i].video_file,
          size: VideoQuality[i].size,
        };
      }
      return videoQuality;
    }
  }

  /**
   *
   * @param {string} subtitle
   */
  getSubtitle(subtitle) {
    if (typeof subtitle === "object" && subtitle !== null) {
      let videoSubtitle = [];
      let length = subtitle.length;
      for (let i = 0; i < length; i++) {
        videoSubtitle[i] = {
          kind: "captions",
          label: subtitle[i].label,
          src: subtitle[i].caption_file,
          default: true,
        };
      }
      return videoSubtitle;
    }
  }

  /**
   *
   * @param {object} oldControls
   */
  getControls(oldControls) {
    let windowWidth = window.innerWidth;
    let controls = [];
    for (let [key, value] of Object.entries(oldControls)) {
      if ((value == "show" || value == "mobile") && windowWidth > 576) {
        controls.push(key);
      } else if (value != "mobile" && value != "hide" && windowWidth < 576) {
        controls.push(key);
      }
    }
    return controls;
  }

  /**
   *
   * @param {object} player
   * @param {object} infos
   */
  setSource(selector, players, infos) {
    let myClass = this;
    players.map((player) => {
      if (!infos.streaming) {
        if (infos.provider == "library" || infos.provider == "amazons3") {
          let VideoQuality = this.getQuality(infos.video.VideoQuality) ? this.getQuality(infos.video.VideoQuality) : [];
          let subtitle = this.getSubtitle(infos.video.subtitle) ? this.getSubtitle(infos.video.subtitle) : [];
          player.source = {
            type: "video",
            sources: [{ src: infos.video.source, type: "video/mp4", size: 720 }, ...VideoQuality],
            poster: infos.video.poster,
            tracks: subtitle,
          };
        } else if (infos.provider == "youtube") {
          player.source = {
            type: "video",
            sources: [{ src: infos.video.source, provider: "youtube" }],
            poster: infos.video.poster,
          };
        } else if (infos.provider == "vimeo") {
          player.source = {
            type: "video",
            sources: [{ src: infos.video.source, provider: "vimeo" }],
            poster: infos.video.poster,
          };
        }
      } else {
        if (infos.streamingType == "hls") {
          this.videoHls(selector, player, infos);
        } else if (infos.streamingType == "dash") {
          this.videoDash(selector, player, infos);
        }
      }
    });
  }

  videoHls(selector, player, infos) {
    if (!Hls.isSupported()) {
      console.log("hls not support");
    } else {
      const video = document.querySelector(selector);
      // For more Hls.js options, see https://github.com/dailymotion/hls.js
      const hls = new Hls();
      hls.loadSource(infos.video.source);
      hls.attachMedia(video);
      player.on("languagechange", () => {
        setTimeout(() => (hls.subtitleTrack = player.currentTrack), 50);
      });
    }
  }

  videoDash(selector, player, infos) {
    const videos = document.querySelectorAll(selector);
    let length = videos.length;
    for (let i = 0; i < length; i++) {
      const dash = dashjs.MediaPlayer().create();
      dash.initialize(videos[i], infos.video.source, true);
    }
  }
}
