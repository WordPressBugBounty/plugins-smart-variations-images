var frame,wcsvfs=wcsvfs||{};jQuery(document).ready(function(e){"use strict";function t(){i.find(".wcsvfs-term-name input, .wcsvfs-term-slug input").val(""),n.removeClass("is-active"),r.removeClass("error success").hide(),i.hide()}var s=window.wp,a=e("body");e("#term-color").wpColorPicker(),a.on("click",".wcsvfs-upload-image-button",function(t){t.preventDefault();var a=e(this);frame?frame.open():(frame=s.media.frames.downloadable_file=s.media({title:wcsvfs.i18n.mediaTitle,button:{text:wcsvfs.i18n.mediaButton},multiple:!1}),frame.on("select",function(){var e=frame.state().get("selection").first().toJSON();a.siblings("input.wcsvfs-term-image").val(e.id),a.siblings(".wcsvfs-remove-image-button").show(),a.parent().prev(".wcsvfs-term-image-thumbnail").find("img").attr("src",e.sizes.thumbnail.url)}),frame.open())}).on("click",".wcsvfs-remove-image-button",function(){var t=e(this);return t.siblings("input.wcsvfs-term-image").val(""),t.siblings(".wcsvfs-remove-image-button").show(),t.parent().prev(".wcsvfs-term-image-thumbnail").find("img").attr("src",wcsvfs.placeholder),!1});var i=e("#wcsvfs-modal-container"),n=i.find(".spinner"),r=i.find(".message"),c=null;a.on("click",".wcsvfs_add_new_attribute",function(t){t.preventDefault();var a=e(this),n=s.template("wcsvfs-input-tax"),r={type:a.data("type"),tax:a.closest(".woocommerce_attribute").data("taxonomy")};i.find(".wcsvfs-term-swatch").html(e("#tmpl-wcsvfs-input-"+r.type).html()),i.find(".wcsvfs-term-tax").html(n(r)),"color"==r.type&&i.find("input.wcsvfs-input-color").wpColorPicker(),c=a.closest(".woocommerce_attribute.wc-metabox"),i.show()}).on("click",".wcsvfs-modal-close, .wcsvfs-modal-backdrop",function(e){e.preventDefault(),t()}),a.on("click",".wcsvfs-new-attribute-submit",function(a){a.preventDefault();var o=e(this),l=(o.data("type"),!1),m={};i.find(".wcsvfs-input").each(function(){var t=e(this);"slug"==t.attr("name")||t.val()?t.removeClass("error"):(t.addClass("error"),l=!0),m[t.attr("name")]=t.val()}),l||(n.addClass("is-active"),r.hide(),s.ajax.send("wcsvfs_add_new_attribute",{data:m,error:function(e){n.removeClass("is-active"),r.addClass("error").text(e).show()},success:function(e){n.removeClass("is-active"),r.addClass("success").text(e.msg).show(),c.find("select.attribute_values").append('<option value="'+e.id+'" selected="selected">'+e.name+"</option>"),c.find("select.attribute_values").change(),t()}}))})});