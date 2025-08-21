(function ($) {
  /**
   * Easy Currency Shortcode Menu Module
   */
  const EccwMenuModule = {
    init: function () {
      // Attach event listener
      $("#eccw-add-shortcode-menu-button").on("click", function (e) {
        e.preventDefault();
        if (typeof wpNavMenu !== "undefined") {
          wpNavMenu.registerChange();
        }
        EccwMenuModule.addShortcodeMenuItem();
      });
    },

    /**
     * Add shortcode item into WordPress Nav Menu
     */
    addShortcodeMenuItem: function () {
      const $wrapper = $(".eccw-menu-shortcode-wrapper");
      const description = $("#eccw-menu-shortcode-html-field").val();

      $wrapper.find(".spinner").show();

      const idRegex = /menu-item\[([^\]]*)/;
      const $dbField = $wrapper.find(".menu-item-db-id");
      const match = idRegex.exec($dbField.attr("name"));
      const dbID = match && match[1] ? parseInt(match[1], 10) : 0;

      const menuItems = {};
      menuItems[dbID] = $wrapper.getItemData("add-menu-item", dbID);
      menuItems[dbID]["menu-item-description"] = description;

      if (!menuItems[dbID]["menu-item-title"]) {
        menuItems[dbID]["menu-item-title"] = "(Untitled)";
      }

      const nonce = $("#eccw-menu-item-nonce").val();

      const requestData = {
        action: "eccw_get_description_transient",
        "description-nonce": nonce,
        "menu-item": menuItems[dbID],
      };

      $.post(eccw_nav_menu_ajax.ajaxurl, requestData, function (objectId) {
        
        $("#eccw-menu-shortcode-wrapper .menu-item-object-id").val(objectId);

        wpNavMenu.addItemToMenu(menuItems, wpNavMenu.addMenuItemToBottom, function () {
          $wrapper.find(".spinner").hide();
          $("#eccw-menu-shortcode-title").val("").blur();
          $("#eccw-menu-shortcode-html-field").val("");
        });
      });
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    EccwMenuModule.init();
  });

})(jQuery);
