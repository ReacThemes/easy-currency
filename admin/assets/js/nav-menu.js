jQuery("document").ready(function () {
  jQuery("#eccw-add-shortcode-menu-button").on("click", function (e) {
    // call registerChange like any add
    wpNavMenu.registerChange();

    // call our custom function
    gsSimAddWidgettoMenu();
  });

  /**
   * Add our custom Shortcode object to Menu
   *
   * @returns {Boolean}
   */
  // function gsSimAddWidgettoMenu( ) {

  // 	// get the description
  // 	description = jQuery( '#gs-sim-html' ).val();

  // 	// initialise object
  // 	menuItems = { };

  // 	// the usual method for ading menu Item
  // 	processMethod = wpNavMenu.addMenuItemToBottom;

  // 	var t = jQuery( '.eccw-menu-shortcode-wrapper' );

  // 	// Show the ajax spinner
  // 	t.find( '.spinner' ).show();

  // 	// regex to get the index
  // 	re = /menu-item\[([^\]]*)/;

  // 	m = t.find( '.menu-item-db-id' );
  // 	// match and get the index
  // 	listItemDBIDMatch = re.exec( m.attr( 'name' ) ),
  // 		listItemDBID = 'undefined' == typeof listItemDBIDMatch[1] ? 0 : parseInt( listItemDBIDMatch[1], 10 );

  // 	// assign data
  // 	menuItems[listItemDBID] = t.getItemData( 'add-menu-item', listItemDBID );
  // 	menuItems[listItemDBID]['menu-item-description'] = description;

  // 	if ( menuItems[listItemDBID]['menu-item-title'] === '' ) {
  // 		menuItems[listItemDBID]['menu-item-title'] = '(Untitled)';
  // 	}

  // 	// get our custom nonce
  // 	nonce = jQuery( '#eccw-menu-item-nonce' ).val();

  // 	// set up params for our ajax hack
  // 	params = {
  // 		'action': 'gs_sim_description_hack',
  // 		'description-nonce': nonce,
  // 		'menu-item': menuItems[listItemDBID]
  // 	};

  // 	// call it
  // 	jQuery.post( eccw_nav_menu_ajax.ajaxurl, params, function ( objectId ) {

  // 		// returns the incremented object id, add to ui
  // 		jQuery( '#eccw-menu-shortcode-wrapper .menu-item-object-id' ).val( objectId );

  // 		// now call the ususl addItemToMenu
  // 		wpNavMenu.addItemToMenu( menuItems, processMethod, function () {
  // 			// Deselect the items and hide the ajax spinner
  // 			t.find( '.spinner' ).hide();
  // 			// Set form back to defaults
  // 			jQuery( '#eccw-menu-shortcode-title' ).val( '' ).blur();
  // 			jQuery( '#gs-sim-html' ).val( '' );

  // 		} );
  // 	} );
  // }

  function gsSimAddWidgettoMenu() {
    var description = jQuery("#gs-sim-html").val();
    var shortcodeId = jQuery("#eccw-menu-shortcode-id").val(); // select value

    var menuItems = {};
    var processMethod = wpNavMenu.addMenuItemToBottom;
    var t = jQuery(".eccw-menu-shortcode-wrapper");
    t.find(".spinner").show();

    var re = /menu-item\[([^\]]*)/;
    var m = t.find(".menu-item-db-id");
    var listItemDBIDMatch = re.exec(m.attr("name"));
    var listItemDBID =
      typeof listItemDBIDMatch[1] === "undefined"
        ? 0
        : parseInt(listItemDBIDMatch[1], 10);

    menuItems[listItemDBID] = t.getItemData("add-menu-item", listItemDBID);
    menuItems[listItemDBID]["menu-item-description"] = description;
    menuItems[listItemDBID]["menu-item-select-shortcode"] = shortcodeId; // add select value

    if (menuItems[listItemDBID]["menu-item-title"] === "") {
      menuItems[listItemDBID]["menu-item-title"] = "(Untitled)";
    }

    var nonce = jQuery("#eccw-menu-item-nonce").val();
    var params = {
      action: "gs_sim_description_hack",
      "description-nonce": nonce,
      "menu-item": menuItems[listItemDBID],
    };

    jQuery.post(eccw_nav_menu_ajax.ajaxurl, params, function (objectId) {
      jQuery("#eccw-menu-shortcode-wrapper .menu-item-object-id").val(objectId);
      wpNavMenu.addItemToMenu(menuItems, processMethod, function () {
        t.find(".spinner").hide();
        jQuery("#eccw-menu-shortcode-title").val("").blur();
        jQuery("#gs-sim-html").val("");
        jQuery("#eccw-menu-shortcode-id").val("").trigger("change"); // clear select
      });
    });
  }
});
