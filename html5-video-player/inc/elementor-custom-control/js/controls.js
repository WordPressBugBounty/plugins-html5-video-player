/**
 * View for the `h5vp-select-file` control.
 */
(function ($) {
  "use strict";
  if (typeof elementor === "undefined" || !elementor.modules) {
    return;
  }

  var IMAGE_EXT = /\.(jpe?g|png|gif|webp|avif|svg|bmp)(\?|$)/i;

  var fileselectItemView = elementor.modules.controls.BaseData.extend({
    ui: function () {
      var ui = elementor.modules.controls.BaseData.prototype.ui.apply(this, arguments);

      ui.file = ".h5vp-file";
      ui.choose = ".h5vp-file__choose";
      ui.chooseText = ".h5vp-file__choose-text";
      ui.clear = ".h5vp-file__clear";
      ui.thumb = ".h5vp-file__thumb";
      ui.name = ".h5vp-file__name";
      ui.host = ".h5vp-file__host";
      ui.url = ".h5vp-file__url";

      return ui;
    },

    events: function () {
      var events = elementor.modules.controls.BaseData.prototype.events.apply(this, arguments);

      events["click @ui.choose"] = "onChooseClick";
      events["click @ui.clear"] = "onClearClick";

      return events;
    },

    /**
     * Paint the preview from a URL.
     *
     * Done in place rather than by re-rendering the control: a re-render on
     * input would pull focus out of the URL field mid-typing.
     */
    updatePreview: function (url) {
      // Coerced rather than trusted: a stored value from any older build must
      // not be able to throw in here and take the whole panel down.
      url = typeof url === "string" ? url : "";

      var name = url.split("/").pop().split("?")[0];
      var host = url.replace(/^https?:\/\//, "").split("/")[0];

      this.ui.file.toggleClass("is-empty", !url);
      this.ui.name.text(name);
      this.ui.host.text(host);

      var isImage = !!url && IMAGE_EXT.test(url);
      this.ui.thumb.toggleClass("has-image", isImage);
      this.ui.thumb.css("background-image", isImage ? 'url("' + url.replace(/"/g, "%22") + '")' : "");

      var label = this.ui.chooseText;
      label.text(url ? label.data("replace") : label.data("choose"));
    },

    onChooseClick: function (event) {
      event.preventDefault();

      // Rebuilt per click so the frame always reflects the current selection.
      var self = this;
      var frame = wp.media({
        title: elementor.translate ? elementor.translate("Upload File") : "Upload File",
        button: { text: "Get Link" },
        multiple: false,
      });

      frame.on("select", function () {
        var attachment = frame.state().get("selection").first().toJSON();

        if (attachment && attachment.url) {
          self.setValue(attachment.url);
          self.ui.url.val(attachment.url);
          self.updatePreview(attachment.url);
        }
      });

      frame.open();
    },

    onClearClick: function (event) {
      event.preventDefault();

      this.setValue("");
      this.ui.url.val("");
      this.updatePreview("");
    },

    onReady: function () {
      var self = this;
      var timer = null;

      this.updatePreview(this.getControlValue());

      // Keep the preview in step with a URL typed or pasted straight into the
      // field. The value itself is saved by Elementor's own `data-setting`
      // binding, so this only refreshes what is on screen.
      this.ui.url.on("input.h5vpFile", function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          self.updatePreview(self.ui.url.val());
        }, 300);
      });
    },

    /**
     * Values changed elsewhere (undo/redo, global styles, revisions) come
     * through here rather than through the input event.
     */
    onAfterExternalChange: function () {
      elementor.modules.controls.BaseData.prototype.onAfterExternalChange.apply(this, arguments);

      this.updatePreview(this.getControlValue());
    },

    // No super call: neither control base view defines onBeforeDestroy.
    onBeforeDestroy: function () {
      if (this.ui.url) {
        this.ui.url.off(".h5vpFile");
      }
    },
  });

  elementor.addControlView("h5vp-select-file", fileselectItemView);
})(jQuery);
