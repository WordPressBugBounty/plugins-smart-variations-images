/*!
 * Smart Variations Images - admin bundle.
 * GENERATED FILE - do not edit. Source of truth: src/admin/.
 * Change the sources and run: npm run build:admin
 */
(function() {
  "use strict";
  const $ = window.jQuery;
  const SELECTOR = {
    // Product-data tab rendered by images_settings().
    tabData: "#svi-images_tab_data",
    // Attribute picker built by build_sviAttributesSelector().
    selectContainer: "#sviselect_container",
    bulkSelect: "#sviprobulk",
    addButton: "#addsviprovariation",
    // Gallery list + hidden clone template from build_sviClone().
    gallery: "#svigallery",
    clone: "#svipro_clone",
    unassigned: "#svipro_x",
    defaultGallery: "#svipro_svidefault",
    globalGallery: "#svipro_sviproglobal",
    // Video storage + dialog from build_sviVideo*__premium_only().
    videoStore: "#svipro_video",
    videoDialog: "#svipro_dialogvideo",
    videoInput: "#svipro_video_input",
    videoManager: ".svi-videomanager",
    // WooCommerce main gallery.
    wcGallery: "#product_images_container",
    wcGalleryInput: "input#product_image_gallery"
  };
  function galleryId(key) {
    return `svipro_${key}`;
  }
  function gallery(key) {
    return $(`#${galleryId(key)}`);
  }
  function allGalleries() {
    return $("div[id^=svipro_].postbox");
  }
  const DEFAULTS = {
    legacySettings: false,
    legacyNotices: false
  };
  const overrides = window.SVI_ADMIN_FLAGS && typeof window.SVI_ADMIN_FLAGS === "object" ? window.SVI_ADMIN_FLAGS : {};
  const FLAGS = Object.assign({}, DEFAULTS);
  Object.keys(DEFAULTS).forEach((name) => {
    if (typeof overrides[name] === "boolean") {
      FLAGS[name] = overrides[name];
    }
  });
  const state = {
    /** True while a `woosvi_reloadselect` request is in flight (was `$ajax_run`). */
    ajaxInFlight: false,
    /** True while the script itself triggers the SVI tab click (was `$selectedReload`). */
    programmaticTabClick: false,
    /** Data of the last clicked video icon, used by the dialog (was `$video_data`). */
    videoData: false
  };
  const warned = {};
  function warnMissing(name) {
    if (warned[name] || !window.console || !window.console.warn) {
      return;
    }
    warned[name] = true;
    window.console.warn(`[SVI admin] ${name} is unavailable; that feature is disabled on this screen.`);
  }
  function controlSubfields() {
    $(".svisubfield").each(function markRow() {
      const $element = $(this);
      $element.closest("tr").addClass("wpsfsvi-subfield");
    });
  }
  function loadInits() {
    setTimeout(() => {
      $("input#columns").prop("type", "number").attr("min", 1).attr("max", 10);
      $("input#lens_size").prop("type", "number").attr("min", 100).attr("max", 300);
      $("input#slider_spaceBetweenNavigation,input#slider_spaceBetween,#lightbox_thumbnails_thumbWidth,input#lens_zIndex").prop("type", "number");
      $("input#lightbox_width").prop("type", "number").attr("min", 10).attr("max", 90);
      $("input#lightbox_height").prop("type", "number").attr("min", 100).attr("max", 1e3);
      $("input#hide_thumbs").on("change.svi", function onHideThumbs() {
        if ($(this).val() === 1) {
          $("#woosvi_options-variation_swap label.cb-disable").trigger("click");
          $("#woosvi_options-keep_thumbnails label.cb-disable").trigger("click");
        }
      });
      $("input#keep_thumbnails").on("change.svi", function onKeepThumbnails() {
        if ($(this).val() === 1) {
          $("#woosvi_options-swselect label.cb-disable").trigger("click");
          $("#woosvi_options-hide_thumbs label.cb-disable").trigger("click");
        }
      });
      const $navTabWrapper = $(".fs-section .nav-tab-wrapper");
      const $currentTabs = $(".fs-section .nav-tab-wrapper a");
      let activeTab = "";
      if (!$currentTabs.hasClass("nav-tab-active")) {
        activeTab = " nav-tab-active";
      }
      $navTabWrapper.prepend(`<a href="/wp-admin/admin.php?page=woocommerce_svi" class="nav-tab fs-tab svg-flags-lite home${activeTab}">Settings</a>`);
    }, 500);
    if ($("#wpwrap").find("#wpfooter").length > 0) {
      $("#wpfooter").detach().appendTo("#wpwrap");
    }
  }
  function post(data, dataType = "json") {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: window.ajaxurl,
        type: "POST",
        dataType,
        data,
        success: resolve,
        error: reject
      });
    });
  }
  function woosviReloadSelect(productId) {
    return post({ action: "woosvi_reloadselect", data: productId }, "html");
  }
  function woosviDismissNotice() {
    return post({ action: "woosvi_dismiss_notice" });
  }
  const SAFE_SLUG = /^[A-Za-z0-9_-]+$/;
  function escapeSlug(data) {
    const parts = Array.isArray(data) ? data : [data];
    const safe = parts.every((part) => SAFE_SLUG.test(String(part === null || part === void 0 ? "" : part)));
    if (safe) {
      return Promise.resolve(parts.map((part) => String(part)).join("_svipro_"));
    }
    return post({ action: "woosvi_esc_html", data }, "json");
  }
  function notices() {
    $(document).on("click.sviNotices", ".woosvi-notice-dismissed .notice-dismiss", () => {
      woosviDismissNotice().catch(() => {
      });
    });
  }
  function block() {
    if (!$.fn.block) {
      warnMissing("jQuery blockUI");
      return;
    }
    $(SELECTOR.tabData).block({
      message: null,
      overlayCSS: {
        background: "#fff",
        opacity: 0.6
      }
    });
  }
  function unblock() {
    if (!$.fn.unblock) {
      return;
    }
    $(SELECTOR.tabData).unblock();
  }
  function tipTip($elements) {
    if (!$elements || $elements.length === 0) {
      return;
    }
    if (!$.fn.tipTip) {
      warnMissing("jQuery tipTip");
      return;
    }
    $elements.tipTip({
      attribute: "data-tip",
      fadeIn: 50,
      fadeOut: 50,
      delay: 200
    });
  }
  function initSelect2() {
    if (!$.fn.select2) {
      return;
    }
    const $select = $(SELECTOR.bulkSelect);
    if ($select.length === 0) {
      return;
    }
    if ($select.hasClass("select2-hidden-accessible")) {
      $select.select2("destroy");
    }
    $select.select2({
      placeholder: "Select a Attribute"
    });
  }
  function initMedia() {
    $(".svi_variations_options>a:not(.active)").on("click.svi", () => {
      if (!state.programmaticTabClick) {
        reloadSelect();
      }
    });
  }
  function reloadSelect() {
    if (state.ajaxInFlight) {
      return Promise.reject(new Error("woosvi_reloadselect already running"));
    }
    state.ajaxInFlight = true;
    block();
    return woosviReloadSelect($("#post_ID").val()).then((response) => {
      state.ajaxInFlight = false;
      $(SELECTOR.selectContainer).empty().append(response);
      initSelect2();
      tag();
      unblock();
      return true;
    }).catch((error) => {
      state.ajaxInFlight = false;
      unblock();
      throw error;
    });
  }
  function reloadSelectData() {
    if (state.ajaxInFlight) {
      return Promise.reject(new Error("woosvi_reloadselect already running"));
    }
    state.ajaxInFlight = true;
    return woosviReloadSelect($("#post_ID").val()).catch((error) => {
      state.ajaxInFlight = false;
      throw error;
    });
  }
  function tag() {
    const $tag = $(SELECTOR.bulkSelect);
    $(document).off("click.sviAttributeGroup", ".select2-results__group").on("click.sviAttributeGroup", ".select2-results__group", function onGroupClick() {
      const groupName = $(this).html();
      const options = $("#sviprobulk option");
      $.each(options, (key, value) => {
        const parent = $(value)[0].parentElement;
        if (parent && "label" in parent && parent.label.indexOf(groupName) >= 0) {
          $(value).prop("selected", "selected");
        }
      });
      $("#sviprobulk").trigger("change");
      $("#sviprobulk").select2("close");
    });
    if ($tag.length === 0) {
      return;
    }
    $tag.off("select2:selecting.svi").on("select2:selecting.svi", (e) => {
      const element = e.params && e.params.args && e.params.args.data ? e.params.args.data.element : null;
      if (!element || !element.parentElement || !element.parentElement.parentElement) {
        return;
      }
      const siblings = element.parentElement.children;
      const $siblings = element.parentElement.parentElement.children;
      for (let i = 0; i < siblings.length; i++) {
        if ($siblings[0] && $siblings[0].value === "svidefault") {
          $siblings[0].selected = false;
        }
        if ($siblings[1] && $siblings[1].value === "sviproglobal") {
          $siblings[1].selected = false;
        }
      }
    });
  }
  function tiptipInit() {
    tipTip($(".svi-addvideo,.svi-editvideo"));
  }
  function findVideoVal(id, sviGal = "") {
    const $el = $(SELECTOR.videoStore).find(`input[name="sviproduct_video_gallery${sviGal}[${id}]"]`);
    return $el.length > 0 ? $el.val() : false;
  }
  function videoAppend(videoAction, vidTxt, attachmentId, type, sviGal) {
    return $("<span></span>").addClass(`dashicons dashicons-video-alt3 svi-${videoAction} svi-videomanager`).attr("data-tip", vidTxt).attr("data-attachment_id", attachmentId).attr("data-svi_type", type).attr("data-svi_gal", sviGal);
  }
  function videoStoreInput(sviGal, attachmentId, url) {
    return $('<input class="svipro-product_video_gallery" type="hidden">').attr("name", `sviproduct_video_gallery[${sviGal}][${attachmentId}]`).val(url);
  }
  function prepVideoAppend($target, type) {
    $($target).find("ul li.image").each(function eachImage() {
      const attachmentId = $(this).attr("data-attachment_id");
      const $postbox = $(this).closest(".svi-woocommerce-product-images");
      const sviGal = $postbox.length > 0 ? $postbox.data("svigal") : type === "variable" ? type : attachmentId;
      const videoVal = findVideoVal(attachmentId, `[${sviGal}]`);
      const $icon = videoVal ? videoAppend("editvideo", "Edit/Remove Video", attachmentId, type, sviGal) : videoAppend("addvideo", "Add Video", attachmentId, type, sviGal);
      $(this).append($icon);
    });
    tiptipInit();
    detectVideoAddClick();
  }
  function prepMediaView() {
    $(SELECTOR.videoManager).remove();
    const mainId = $("input#_thumbnail_id").val();
    const videoVal = findVideoVal(mainId, "[wc_svimainvideo]");
    const $inputVideo = mainId && videoVal ? videoAppend("editvideo", "Edit/Remove Video", mainId, "main", "wc_svimainvideo") : videoAppend("addvideo", "Add Video", mainId, "main", "wc_svimainvideo");
    $("#set-post-thumbnail").parent().append($inputVideo);
    if ($("#product-type").val() !== "variable") {
      prepVideoAppend(SELECTOR.wcGallery, "");
    } else {
      prepVideoAppend(".svipro-product_images_container", "variable");
    }
  }
  function detectVideoAddClick() {
    if (!$.fn.dialog) {
      warnMissing("jQuery UI dialog");
      return;
    }
    const $dialog = $(SELECTOR.videoDialog).dialog({
      title: "Video",
      dialogClass: "wp-dialog svipro-dialog",
      autoOpen: false,
      width: 460,
      modal: true,
      resizable: false,
      closeOnEscape: true,
      position: {
        my: "center",
        at: "center",
        of: window
      },
      buttons: [
        {
          text: "Close",
          class: "svi-secondary",
          click: function closeDialog() {
            $(this).dialog("close");
          }
        },
        {
          text: "Remove",
          class: "svi-danger",
          click: function removeVideo() {
            handleVideoData("delete");
            $(this).dialog("close");
          }
        },
        {
          text: "Save Video",
          class: "svi-primary",
          click: function saveVideo() {
            handleVideoData("addedit");
            $(this).dialog("close");
          }
        }
      ],
      close: function onClose() {
        $(SELECTOR.videoInput).val("");
        $("body").removeClass("svi-dialog-open");
      },
      open: function onOpen() {
        $("body").addClass("svi-dialog-open");
        $(".ui-widget-overlay").bind("click", () => {
          $(SELECTOR.videoDialog).dialog("close");
        });
      }
    });
    $dialog.dialog("widget").find(".ui-dialog-titlebar-close").empty().append($("<span></span>").addClass("screen-reader-text").text("Close"));
    $(SELECTOR.videoManager).off("click.sviVideo").on("click.sviVideo", function onVideoIcon(e) {
      e.preventDefault();
      state.videoData = $(this).data();
      const videoVal = findVideoVal(state.videoData.attachment_id, `[${state.videoData.svi_gal}]`);
      if (videoVal) {
        $(SELECTOR.videoInput).val(videoVal);
      }
      $dialog.dialog("open");
    });
  }
  function handleVideoData(action) {
    const $vidInput = $(SELECTOR.videoInput);
    const data = state.videoData || {};
    if (!data.attachment_id && data.attachment_id !== 0) {
      return;
    }
    const $el = $(SELECTOR.videoStore).find(`input[name="sviproduct_video_gallery[${data.svi_gal}][${data.attachment_id}]"]`);
    switch (action) {
      case "addedit":
        if ($vidInput.val() !== "") {
          if ($el.length > 0) {
            $el.val($vidInput.val());
          } else {
            $(SELECTOR.videoStore).append(videoStoreInput(data.svi_gal, data.attachment_id, $vidInput.val()));
          }
        }
        break;
      case "delete":
        if ($el.length > 0) {
          $el.remove();
        }
        break;
    }
    prepMediaView();
  }
  const frames = /* @__PURE__ */ new Map();
  function onlyUnique(ids) {
    const uniqueIds = [];
    $.each(ids, (i, el) => {
      if ($.inArray(el, uniqueIds) === -1) {
        uniqueIds.push(el);
      }
    });
    return uniqueIds;
  }
  function updateGal($imageGalleryIds, $productImages) {
    const attachmentIds = [];
    $productImages.find("li.image").each(function collectId() {
      attachmentIds.push($(this).attr("data-attachment_id"));
    });
    const unique = onlyUnique(attachmentIds);
    $imageGalleryIds.val(unique.join(","));
  }
  function updateUnasignGal(attachmentId) {
    const $thumb = $(SELECTOR.unassigned).find(`ul li.image[data-attachment_id="${attachmentId}"]`);
    const $sviBlock = $thumb.closest("div.svipro-product_images_container");
    const $input = $sviBlock.find("input.svipro-product_image_gallery");
    $thumb.remove();
    updateGal($input, $sviBlock);
  }
  function imageItemMarkup(imageUrl, attachmentId, deleteTitle, deleteText) {
    return `<li class="image" data-attachment_id="${attachmentId}"><img src="${imageUrl}" /><ul class="actions"><li><a href="#/" class="delete" title="${deleteTitle}">${deleteText}</a></li></ul></li>`;
  }
  function startSortable($productImages, $input) {
    $productImages.sortable({
      items: "li.image:not(.ui-state-disabled)",
      connectWith: ".product_galsort",
      cursor: "move",
      scrollSensitivity: 40,
      forcePlaceholderSize: true,
      forceHelperSize: false,
      helper: "clone",
      opacity: 0.65,
      placeholder: "wc-metabox-sortable-placeholder",
      start: (event, ui) => {
        ui.item.css("background-color", "#f6f6f6");
      },
      stop: (event, ui) => {
        ui.item.removeAttr("style");
      },
      receive: (ev, ui) => {
        const attachmentId = ui.item.data("attachment_id");
        if ($(ev.target).find(`li.image[data-attachment_id="${attachmentId}"]`).length > 1) {
          ui.sender.sortable("cancel");
          return;
        }
        const $existing = $("div#svigallery").find(`ul li.image[data-attachment_id="${attachmentId}"]`).length - 1;
        if ($(ui.sender).parent(SELECTOR.wcGallery).length > 0) {
          ui.item.clone().appendTo(ev.target);
          updateUnasignGal(attachmentId);
        }
        const targetSlug = $(ev.target).closest(".postbox").data("svigal");
        if (targetSlug === "unsigned_svi" && $existing > 0) {
          alert(`Notice: This image is still assigned to ${$existing} other Galleries. It will only be unasigned from: ${$(ui.sender).closest(".postbox").find("h2>span").html()}`);
          $(ui.item).fadeOut(2e3).remove();
        }
      },
      update: () => {
        updateGal($input, $productImages);
        const $postbox = $productImages.closest(".postbox");
        removeElementMediaGallery($postbox.data("svigal"), $postbox.data("svikey"));
      }
    }).disableSelection();
  }
  function openMediaFrame(svikey, $input, $productImages) {
    if (!window.wp || !window.wp.media) {
      warnMissing("wp.media");
      return;
    }
    const key = String(svikey);
    if (frames.has(key)) {
      frames.get(key).open();
      return;
    }
    const $productImagesWoo = $(SELECTOR.wcGallery).find("ul.product_images");
    const $gallery = gallery(svikey);
    const $link = $gallery.find(".add_product_images_svipro a").first();
    const frame = wp.media.frames.product_gallery = wp.media({
      title: $link.data("choose"),
      button: {
        text: $link.data("update")
      },
      states: [
        new wp.media.controller.Library({
          title: $link.data("choose"),
          filterable: "all",
          multiple: true
        })
      ]
    });
    frames.set(key, frame);
    frame.on("select", () => {
      const selection = frame.state().get("selection");
      selection.map((attachment) => {
        attachment = attachment.toJSON();
        const attachmentId = attachment.id;
        if (!attachmentId || $productImages.find(`li.image[data-attachment_id="${attachmentId}"]`).length > 0) {
          return;
        }
        if (key !== "x") {
          $(SELECTOR.unassigned).find(`ul li.image[data-attachment_id="${attachmentId}"]`).remove();
        }
        const imageUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
        const markup = imageItemMarkup(imageUrl, attachmentId, $link.data("delete"), $link.data("text"));
        $(markup).insertBefore($productImages.find("li").last());
        if ($(SELECTOR.wcGallery).find(`ul li.image[data-attachment_id="${attachmentId}"]`).length < 1) {
          $productImagesWoo.append(markup);
        }
      });
      updateGal($input, $productImages);
      updateGal($(SELECTOR.unassigned).find("input.svipro-product_image_gallery"), $(SELECTOR.unassigned));
      updateGal($(SELECTOR.wcGalleryInput), $(SELECTOR.wcGallery));
      removeElementMediaGallery($gallery.data("svigal"), $gallery.data("svikey"));
      prepMediaView();
    });
    frame.open();
  }
  function buildMediaGal(slug, svikey) {
    const $gallery = gallery(svikey);
    if ($gallery.length === 0) {
      return;
    }
    const $input = $gallery.find("input.svipro-product_image_gallery");
    const $productImages = $gallery.find("ul.product_images");
    startSortable($productImages, $input);
    $gallery.off("click.sviAdd", ".add_product_images_svipro a").on("click.sviAdd", ".add_product_images_svipro a", function onAddImages(event) {
      event.preventDefault();
      openMediaFrame(svikey, $input, $productImages);
    });
  }
  function removeMediaGallery(slug, svikey) {
    const $gallery = gallery(svikey);
    if ($gallery.length === 0) {
      return;
    }
    $gallery.off("click.sviRemoveGal", "a.sviprobulk_remove").on("click.sviRemoveGal", "a.sviprobulk_remove", function onRemoveGallery(event) {
      const $postbox = $(this).closest(".postbox");
      const $badge = $postbox.find("h2>span>span");
      const txtDel = $badge.length > 0 ? $badge.html() : $postbox.find("h2>span").html();
      if (!confirm(`Delete ${txtDel}?`)) {
        return false;
      }
      event.preventDefault();
      const attachmentIds = $(this).closest("div.svi-woocommerce-product-images").find("input.svipro-product_image_gallery").val().split(",");
      $(this).closest("div.svi-woocommerce-product-images").remove();
      $.each(attachmentIds, (i, id) => {
        if ($("#svigallery").find(`ul li.image[data-attachment_id="${id}"]`).length < 1) {
          $(SELECTOR.wcGallery).find(`ul li.image[data-attachment_id="${id}"]`).remove();
        }
      });
      updateGal($(SELECTOR.wcGalleryInput), $(SELECTOR.wcGallery));
      return false;
    });
  }
  function removeElementMediaGallery(slug, svikey) {
    const $gallery = gallery(svikey);
    if ($gallery.length > 0) {
      $gallery.off("click.sviDeleteImg", "li.image a.delete").on("click.sviDeleteImg", "li.image a.delete", function onDeleteImage() {
        const attachmentId = $(this).closest("li.image").attr("data-attachment_id");
        const $productImages = $(this).closest("ul.product_images");
        const $input = $(this).closest("div.svipro-product_images_container").find("input.svipro-product_image_gallery");
        $(this).closest("li.image").remove();
        if ($("#svigallery").find(`ul li.image[data-attachment_id="${attachmentId}"]`).length < 1) {
          $(SELECTOR.wcGallery).find(`ul li.image[data-attachment_id="${attachmentId}"]`).remove();
        }
        updateGal($(SELECTOR.wcGalleryInput), $(SELECTOR.wcGallery));
        updateGal($input, $productImages);
        $("#tiptip_holder").removeAttr("style");
        $("#tiptip_arrow").removeAttr("style");
        prepMediaView();
        return false;
      });
    }
    if ($(SELECTOR.wcGallery).data("sviDeleteBound")) {
      return;
    }
    $(SELECTOR.wcGallery).data("sviDeleteBound", true);
    $(SELECTOR.wcGallery).on("click.sviDelete", "a.delete", function onDeleteFromWcGallery() {
      const attachmentId = $(this).closest("li.image").attr("data-attachment_id");
      const $thumbs = $("div#svigallery").find(`ul li.image[data-attachment_id="${attachmentId}"]`);
      $.each($thumbs, function removeThumb() {
        const $sviBlock = $(this).closest("div.svipro-product_images_container");
        const $input = $sviBlock.find("input.svipro-product_image_gallery");
        $(this).remove();
        updateGal($input, $sviBlock);
      });
      return false;
    });
  }
  function sortableGallery() {
    $(SELECTOR.gallery).sortable({
      items: ".postbox:not(#svipro_x,#svipro_svidefault,#svipro_sviproglobal)",
      cursor: "move",
      scrollSensitivity: 40,
      forcePlaceholderSize: true,
      forceHelperSize: false,
      helper: "clone",
      opacity: 0.65,
      placeholder: "wc-metabox-sortable-placeholder",
      start: (event, ui) => {
        ui.item.css("background-color", "#f6f6f6");
      },
      stop: (event, ui) => {
        ui.item.removeAttr("style");
      },
      receive: () => {
      },
      update: () => {
        let i = 0;
        $("#svigallery .postbox").filter(function renumber() {
          const id = $(this).attr("id");
          if (id !== "svipro_x" && id !== "svipro_svidefault") {
            $(this).attr("id", `svipro_${i}`);
          }
          i++;
        });
      }
    }).disableSelection();
  }
  function initWcGallerySortable() {
    const $gallery = $(`${SELECTOR.wcGallery} .product_images`);
    if ($gallery.length === 0) {
      return;
    }
    $gallery.sortable({
      connectWith: ".product_galsort",
      connectToSortable: "#destination",
      helper: function helper(e, li) {
        $(this).data("copied", false);
        return li.clone();
      },
      remove: function onRemove(ev, li) {
        setTimeout(() => {
          const attachmentId = li.item.data("attachment_id");
          if ($(ev.target).find(`li.image[data-attachment_id="${attachmentId}"]`).length < 1) {
            $(this).sortable("cancel");
            const attachmentIds = [];
            $(SELECTOR.wcGallery).find("li.image").css("cursor", "default").each(function collectId() {
              attachmentIds.push($(this).attr("data-attachment_id"));
            });
            const unique = onlyUnique(attachmentIds);
            $(SELECTOR.wcGallery).find(SELECTOR.wcGalleryInput).val(unique.join(","));
          }
        }, 200);
      }
    }).disableSelection();
  }
  function initMediaGal() {
    allGalleries().each(function eachGallery() {
      const $gallery = $(this);
      const slug = $gallery.data("svigal");
      const svikey = $gallery.data("svikey");
      buildMediaGal(slug, svikey);
      removeMediaGallery(slug, svikey);
      removeElementMediaGallery(slug, svikey);
      sortableGallery();
    });
    initWcGallerySortable();
  }
  function nextSviKey() {
    let key = $("div[id^=svipro_]").length - 1;
    while ($(`#${galleryId(key)}`).length > 0) {
      key++;
    }
    return key;
  }
  function allCombinations(obj, dataTxt) {
    let combos = [{}];
    Object.entries(obj).forEach(([key, values]) => {
      const all = [];
      if (key !== "sviglobals") {
        values.forEach((value) => {
          combos.forEach((combo) => {
            all.push({ ...combo, [key]: value });
          });
        });
        combos = all;
      }
    });
    const out = {
      combo: [],
      txt: []
    };
    if ("sviglobals" in obj) {
      obj.sviglobals.forEach((value) => {
        out.combo.push([value]);
        out.txt.push([value === "svidefault" ? "Default Gallery" : "SVI Global"]);
      });
    }
    combos.forEach((combo) => {
      const values = Object.values(combo);
      out.combo.push(values);
      const txt = $.map(values, (variation) => $.map(dataTxt, (txtObj) => {
        if (txtObj.value === variation) {
          return txtObj.txt.trim();
        }
        return null;
      }));
      out.txt.push(txt);
    });
    return out;
  }
  function runGalCreate(data, dataTxt, iterate = false) {
    return new Promise((resolve, reject) => {
      const $clone = $(SELECTOR.clone).clone();
      const txtJoined = dataTxt.join(" + ");
      escapeSlug(data).then((rawSlug) => {
        const slug = rawSlug.replace(/^\s+/g, "");
        if (data.indexOf("sviproglobal") >= 0 && data.length > 1) {
          unblock();
          resolve(true);
          return;
        }
        if (slug !== "" && $(`div[data-svigal="${slug}"]`).length < 1) {
          if (iterate) {
            block();
          }
          const svikey = slug !== "svidefault" && slug !== "sviproglobal" ? nextSviKey() : slug;
          const textshow = txtJoined === "Default Gallery" || txtJoined === "SVI Global" ? `<span class='svititle svibadge svibadge-light'>${txtJoined}</span>` : `${txtJoined} Gallery</span>`;
          $clone.attr("id", galleryId(svikey)).removeClass("hidden").attr("data-svigal", slug).attr("data-svikey", svikey).find("h2 span.svititle").html(textshow);
          if (slug !== "svidefault" && slug !== "sviproglobal") {
            $clone.find("input.svipro-product_image_gallery_hidden").attr("name", `sviproduct_image_gallery_hidden[${slug}]`);
          } else {
            $clone.find("span.sviHiddenLoop").remove();
          }
          $clone.find("input.svipro-product_image_gallery").attr("name", `sviproduct_image_gallery[${slug}]`);
          $clone.hide();
          let $where;
          switch (svikey) {
            case "svidefault":
              if ($(SELECTOR.unassigned).length > 0) {
                $where = SELECTOR.unassigned;
              }
              break;
            case "sviproglobal":
              if ($(SELECTOR.defaultGallery).length > 0) {
                $where = SELECTOR.defaultGallery;
              } else if ($(SELECTOR.unassigned).length > 0) {
                $where = SELECTOR.unassigned;
              }
              break;
            default:
              if ($(SELECTOR.globalGallery).length > 0) {
                $where = SELECTOR.globalGallery;
              } else if ($(SELECTOR.defaultGallery).length > 0) {
                $where = SELECTOR.defaultGallery;
              } else if ($(SELECTOR.unassigned).length > 0) {
                $where = SELECTOR.unassigned;
              }
              break;
          }
          if ($where) {
            $clone.insertAfter($where);
          } else {
            $(SELECTOR.gallery).prepend($clone);
          }
          $clone.fadeIn(1500);
          buildMediaGal(slug, svikey);
          removeMediaGallery(slug, svikey);
          removeElementMediaGallery(slug, svikey);
          sortableGallery();
        }
        unblock();
        resolve(true);
      }).catch((error) => {
        unblock();
        reject(error);
      });
    });
  }
  function iterateGalCreation(combos, idx) {
    if (idx >= combos.combo.length) {
      return Promise.resolve();
    }
    return runGalCreate(combos.combo[idx], combos.txt[idx], true).then(() => {
      iterateGalCreation(combos, idx + 1);
    });
  }
  function buidlSVIgal(data = false, dataTxt = false) {
    const $slug = $(SELECTOR.bulkSelect);
    if (!data) {
      data = $slug.val();
    }
    if (!dataTxt) {
      dataTxt = $slug.find("option:selected").toArray();
    }
    if (data.length > 0) {
      const grouped = {};
      const pairs = [];
      $.each($slug.find("option:selected"), (i, option) => {
        let label = $(option).parents("optgroup").data("svilabel");
        if (!label) {
          label = "sviglobals";
        }
        const value = $(option).attr("value");
        if (!(label in grouped)) {
          grouped[label] = [];
        }
        grouped[label].push(value);
        pairs.push({ value, txt: $(option).text() });
      });
      iterateGalCreation(allCombinations(grouped, pairs), 0);
      return;
    }
    runGalCreate(data, dataTxt);
  }
  function loadGuide() {
    $("#variable_product_options").off("click.sviGuide", "a.svi-add-additional-images").on("click.sviGuide", "a.svi-add-additional-images", function onGalleryGuide(event) {
      event.preventDefault();
      $(SELECTOR.bulkSelect).val(null).trigger("change");
      state.programmaticTabClick = true;
      $(".svi_variations_options>a:not(.active)").trigger("click");
      block();
      const $variation = $(this).closest(".woocommerce_variation.wc-metabox");
      const $slug = $variation.find("h3 select");
      const $slugName = $variation.find("h3 select").find("option:selected");
      let data = $.map($slug, (item) => {
        if (item.value !== "") {
          return item.value;
        }
        return null;
      });
      let dataTxt = $slugName.toArray().map((item) => {
        if (item.value !== "") {
          return item.text;
        }
        return null;
      }).filter(Boolean).join(" + ");
      const $wrapper = $("div#sviselect_container");
      reloadSelectData().then((response) => {
        $wrapper.empty().append(response);
        initSelect2();
        if (data.length > 0) {
          $(SELECTOR.bulkSelect).val(data);
        } else {
          $(SELECTOR.bulkSelect).val(["sviproglobal"]);
          data = ["sviproglobal"];
          dataTxt = "SVI GLobal";
        }
        $(SELECTOR.bulkSelect).trigger("change");
        buidlSVIgal(data, dataTxt);
        state.programmaticTabClick = false;
        state.ajaxInFlight = false;
      }).catch(() => {
        state.programmaticTabClick = false;
        state.ajaxInFlight = false;
        unblock();
      });
    });
  }
  function goMediaGallery() {
    $(SELECTOR.addButton).off("click.sviAddGal").on("click.sviAddGal", (event) => {
      event.preventDefault();
      block();
      buidlSVIgal();
    });
  }
  function loadProductInits() {
    $("#woocommerce-product-data").on("woocommerce_variations_loaded", () => {
      loadGuide();
    });
    $("#variable_product_options").on("woocommerce_variations_added", () => {
      loadGuide();
    });
    if (window.wp && window.wp.media && window.wp.media.view && window.wp.media.view.Modal) {
      window.wp.media.view.Modal.prototype.on("all", (event) => {
        if (event === "close") {
          setTimeout(() => {
            prepMediaView();
          }, 200);
        }
      });
    } else {
      warnMissing("wp.media");
    }
  }
  function init() {
    if ($("body").hasClass("woocommerce_page_woosvi-options-settings")) {
      if (FLAGS.legacySettings) {
        loadInits();
      }
      controlSubfields();
      return;
    }
    if ($("body").hasClass("post-type-product")) {
      loadProductInits();
      if (FLAGS.legacyNotices) {
        notices();
      }
      initMedia();
      goMediaGallery();
      initMediaGal();
      prepMediaView();
    }
  }
  if (!window.WOOSVIADM) {
    window.WOOSVIADM = {};
  } else if (typeof window.WOOSVIADM !== "object") {
    throw new Error("WOOSVIADM is not an Object type");
  }
  window.WOOSVIADM.isLoaded = false;
  window.WOOSVIADM.STARTS = {
    NAME: "Application initialize module",
    VERSION: 1,
    init,
    loadInits,
    control_subfields: controlSubfields,
    notices,
    loadProductInits,
    loadGuide,
    block,
    unblock,
    initMedia,
    reloadSelect,
    reloadSelectPromise: reloadSelectData,
    tag,
    goMediaGallery,
    buidlSVIgal,
    iterateGalCreation,
    runGalCreate,
    allCombinations,
    initMediaGal,
    sortableGallery,
    startSortable,
    buildMediaGal,
    removeMediaGallery,
    removeElementMediaGallery,
    updateUnasignGal,
    updateGal,
    esc_html: escapeSlug,
    onlyUnique,
    prepMediaView,
    prepVideoAppend,
    detectVideoAddClick,
    tiptipInit,
    handleVideoData,
    findVideoVal,
    videoAppend
  };
  $(document).ready(() => {
    window.WOOSVIADM.STARTS.init();
  });
})();
