(function ($) {

  var ECCWAdmin = {
    init: function () {
      this.currencyFieldsRepeater();
      this.eccwShortcodeCreation();
      this.eccwShortcodeDelete();
      this.pluginOptions();
      this.copyEccwShortcode();
      this.copyEccwShortcode();
      this.EccwShortcodeModal();
      this.SwitcherModalStyle();
      this.initializeEccwModalFormInputs();
      this.initializeRangeSlider();
      this.eccwTabSwitch();
      this.initSwitcherToggle();

      $(document)
        .on(
          "click.ECCWAdmin",
          ".eccw-notice .notice-dismiss",
          this.ignorePluginNotice
        )
        .on(
          "click.ECCWAdmin",
          ".eccw-settings-tabs .update-currency-rates",
          this.updateCurrecnyRates
        )
        .on(
          "change.ECCWAdmin",
          'select[name="options[currency_aggregator]"]',
          this.selectAggregator
        )
        .on(
          "click.ECCWAdmin",
          ".eccw-settings-tabs .add-currency",
          this.addCurrencyRow
        )
        .on(
          "click.ECCWAdmin",
          ".eccw-settings-tabs .remove-row",
          this.removeCurrencyRow
        );
    },
    initializeEccwModalFormInputs: function (context = document) {
      $(context)
        .find(".eccw-rang-input")
        .each(function (index, element) {
          if ($(element).hasClass("initialized")) return;

          let copy = $(element).clone();
          let postfix = $(element).attr("unit");

          $(copy).addClass("copied").attr("type", "hidden");
          $(element).after(copy).removeAttr("name").removeAttr("id");

          $(element)
            .addClass("initialized")
            .ionRangeSlider({
              skin: "modern",
              min: 0,
              max: 600,
              from: this.value,
              to: 600,
              postfix: postfix,
              onStart: function (data) {
                $(copy).prop("value", data.from + "px");
              },
              onChange: function (data) {
                $(copy).prop("value", data.from + "px");
              },
            });
        });

      // Color Picker
      $(context)
        .find(".eccw-color-input,.eccw-border-picker")
        .wpColorPicker({
          change: function (event, ui) {
            $(
              ".eccw-style-modal-switcher-save-btn, .eccw-style-modal-switcher-save-closebtn"
            ).prop("disabled", false);
          },
        });

      // Dimension Field Handling
      $(context)
        .find(".eccw-dimension-input")
        .each(function () {
          let fields = $(this).attr("fields");
          let unit = $(this).attr("unit");
          if (fields) {
            fields = JSON.parse(fields);

            let dimensionWrapper = $(
              '<div class="eccw-dimension-field-wrapper"></div>'
            );
            $(this).after(dimensionWrapper);

            let inputs = "";
            $.each(fields, function (indexInArray, valueOfElement) {
              inputs +=
                '<input type="' +
                valueOfElement.type +
                '" name="' +
                valueOfElement.name +
                '" value="' +
                valueOfElement.value +
                '" placeholder="' +
                valueOfElement.placeholder +
                '" class="eccw-input eccw-dimension-field"/>';
            });

            dimensionWrapper.html(inputs + " " + unit);
          }
        });

      $(context)
        .find(".eccw-dimension-field")
        .keyup(function (e) {
          e.preventDefault();
          let value = $(this).val();
          $(this).parent().prev().val(value);
        });
    },

    ajaxLoader: function (action) {
      if (action == "show") {
        $(".tabs.eccw-settings-tabs .ajax-loader").css("display", "flex");
      } else {
        $(".tabs.eccw-settings-tabs .ajax-loader").css("display", "none");
      }
    },
    showError: function (msg) {
      $(".eccw-err-msg").html(msg);
      $(".alert-error").fadeIn();
      setTimeout(() => {
        $(".alert-error").fadeOut();
      }, 2000);
    },
    selectAggregator: function (that) {
      let aggregator = $(this).val();
      let premiumAggregators = ["apilayer", "openexchangerates"];

      if (premiumAggregators.includes(aggregator)) {
        $(".eccw-currency-aggregator-api-key-input")
          .parent()
          .parent()
          .css("display", "contents");
      } else {
        $(".eccw-currency-aggregator-api-key-input")
          .parent()
          .parent()
          .css("display", "none");
      }
    },
    ignorePluginNotice: function (that) {
      let notice_id = $(this).parent().data("notice_id");

      $.ajax({
        type: "POST",
        url: eccw_vars.ajaxurl,
        data: {
          action: "eccw_ignore_plugin_notice",
          notice_id: notice_id,
          nonce: eccw_vars.nonce,
        },
        cache: false,
      });
    },
    pluginOptions: function (that) {
      // Show the first tab and hide the rest
      $("#tabs-nav li:first-child").addClass("active");
      $(".tab-content").hide();
      $(".tab-content:first").show();

      // Click function
      $("#tabs-nav li").click(function () {
        $("#tabs-nav li").removeClass("active");
        $(this).addClass("active");
        $(".tab-content").hide();

        var activeTab = $(this).find("a").attr("href");
        $(activeTab).fadeIn();
        return false;
      });

      let aggregator = $(".eccw-currency-aggregator-input").val();
      let premiumAggregators = ["apilayer", "openexchangerates"];

      if (premiumAggregators.includes(aggregator)) {
        $(".eccw-currency-aggregator-api-key-input")
          .parent()
          .parent()
          .css("display", "contents");
      } else {
        $(".eccw-currency-aggregator-api-key-input")
          .parent()
          .parent()
          .css("display", "none");
      }

      //enable range slider
    },
    currencyFieldsRepeater: function () {
      $('.eccw-settings-tabs input[type="checkbox"]').on("change", function () {
        if ($(this).is(":checked")) {
          $(this).val(1); // Set value to "yes" when checked
        } else {
          $(this).val(0); // Clear value when unchecked
        }
      });
    },
    updateCurrecnyRates: function (that) {
      let rateFields = $(".eccw-settings-tabs input.currency-rate");
      ECCWAdmin.ajaxLoader("show");

      let requestedCurrencies = [];

      $('select[name^="eccw_currency_table"][name$="[code]"]').each(function (
        index,
        element
      ) {
        // Wrap the DOM element in jQuery to use .val()
        requestedCurrencies.push($(element).val());
      });

      let aggregator = $(".eccw-currency-aggregator-input").val();
      let premiumAggregators = ["apilayer", "openexchangerates"];
      let apiKey = $(".eccw-currency-aggregator-api-key-input").val();
      let error = false;

      if (premiumAggregators.includes(aggregator)) {
        if (apiKey == "") {
          ECCWAdmin.showError(
            "Invalid API Credentials. Update Valid API Credentials and try again"
          );
          error = true;
          ECCWAdmin.ajaxLoader("hide");
        } else {
          $(".eccw-err-msg").html();
          $(".alert-error").fadeOut();
          error = false;
        }
      }
      //error = true;
      if (error != true) {
        $.ajax({
          type: "POST",
          url: eccw_vars.ajaxurl,
          data: {
            action: "eccw_update_currency_rates",
            requestedCurrencies: requestedCurrencies,
            nonce: eccw_vars.nonce,
          },
          cache: false,
          success: function (response) {
            console.log(response);

            if (response.success) {
              let ratesObject = response.data;

              let ratesArray = Object.values(ratesObject);

              $(rateFields).each(function (index, element) {
                element == this;

                if (ratesArray[index]["rate"]) {
                  $(element).val(ratesArray[index]["rate"]);
                }

                $(element).trigger("change");
              });
            } else {
              ECCWAdmin.showError(response.data);
            }
            ECCWAdmin.ajaxLoader("hide");
          },
        });
      }
    },
    loadCurrencyOptions: function () {
      // Function to fetch and populate currency options from JSON
      return $.getJSON(
        eccw_vars.pluginURL + "/admin/assets/json/currency-countries.json"
      )
        .then(function (data) {
          // Extract currency codes (keys of JSON object)
          var options = "";
          $.each(data, function (code) {
            options += '<option value="' + code + '">' + code + "</option>";
          });
          return options;
        })
        .catch(function () {
          console.error("Could not load currency-countries.json");
          return '<option value="">Error loading currencies</option>';
        });
    },
    reindexRows: function () {
      // Function to re-index all rows
      $("#eccw-repeatable-fields-table tbody tr").each(function (index) {
        // Update the name attributes of the input fields
        $(this)
          .find("input, select")
          .each(function () {
            var name = $(this).attr("name");
            // Update the index in the name attribute
            var updatedName = name.replace(/\[\d+\]/, "[" + index + "]");
            $(this).attr("name", updatedName);
          });
      });
    },
    addCurrencyRow: function () {
      // Add a new row with dynamically populated currency options
      // Get the current row count to determine the next index
      var rowCount = $("#eccw-repeatable-fields-table tbody tr").length;

      if (
        window.eccwPROAdmin != null &&
        window.eccwPROAdmin.eccwProActivated == true
      ) {
        rowCount = -1;
      }

      if (rowCount < 2) {
        ECCWAdmin.loadCurrencyOptions().then(function (currencyOptions) {
          // Build a new row with the correct index and currency options
          var row =
            "<tr>" +
            '<td><input type="radio" name="eccw_currency_table[default]" value="" /></td>' +
            '<td><select name="eccw_currency_table[' +
            rowCount +
            '][code]">' +
            currencyOptions +
            "</select></td>" +
            '<td><input type="text" name="eccw_currency_table[' +
            rowCount +
            '][rate]" value="" class="currency-rate" /></td>' +
            '<td><select name="eccw_currency_table[' +
            rowCount +
            '][symbol_position]">' +
            '<option value="left">Left</option>' +
            '<option value="right">Right</option>' +
            "</select></td>" +
            '<td><select name="eccw_currency_table[' +
            rowCount +
            '][decimal]">' +
            '<option value="1">1</option>' +
            '<option value="2">2</option>' +
            '<option value="3">3</option>' +
            '<option value="4">4</option>' +
            '<option value="5">5</option>' +
            '<option value="6">6</option>' +
            '<option value="7">7</option>' +
            '<option value="8">8</option>' +
            "</select></td>" +
            '<td><input type="text" name="eccw_currency_table[' +
            rowCount +
            '][decimal_separator]" value="" /></td>' +
            '<td><input type="text" name="eccw_currency_table[' +
            rowCount +
            '][thousand_separator]" value="" /></td>' +
            '<td><input type="text" name="eccw_currency_table[' +
            rowCount +
            '][description]" value="" class="widefat"/></td>' +
            '<td><button type="button" class="button remove-row">Remove</button></td>' +
            "</tr>";

          // Append the new row
          $("#eccw-repeatable-fields-table tbody").append(row);

          // Re-index all rows after adding the new one
          ECCWAdmin.reindexRows();
        });
      } else {
        ECCWAdmin.showError(
          "Can't add more currencies. Please upgrade to our Premium Version to add more!"
        );
      }
    },
    removeCurrencyRow: function (that) {
      $(this).closest("tr").remove();
      // Re-index all rows after removal
      ECCWAdmin.reindexRows();
    },

    // shortcode generator js
    eccwShortcodeCreation: function () {
      $(".eccw-shortcode-popup-form").on("click", function (e) {
        e.preventDefault();
        $(".eccw-shortcode-modal, .eccw-modal-overlay").fadeIn();
      });

      $("#eccw-close-modal, #eccw-modal-overlay").on("click", function () {
        $("#eccw-shortcode-modal, #eccw-modal-overlay").fadeOut();
      });

      // Handle form submit with AJAX
      $(".create-shortcode-submit-button").on("click", function (e) {
        e.preventDefault();

        let formData = $("#eccw-shortcode-modal :input").serialize();
        console.log(formData);

        $.post(
          eccw_vars.ajaxurl,
          {
            action: "eccw_create_shortcode",
            nonce: eccw_vars.nonce,
            form_data: formData,
          },
          function (response) {
            if (response.success) {
              const id = response.data.id;
              const code = response.data.shortcode;
              const card = `
                          <div class="eccw-designer-card">
                              <div class="eccw-designer-info">
                                  <div class="eccw-shortcode-box">
                                      <input type="text" readonly class="eccw-shortcode-input" value="${code}">
                                      <button type="button" class="eccw-copy-btn" title="Copy shortcode">📋</button>
                                  </div>
                              </div>
                              <div class="eccw-designer-actions">
                                  <button class="eccw-btn-edit" data-id="${id}">Edit</button>
                                  <button class="eccw-btn-delete" data-id="${id}">Delete</button>
                              </div>
                          </div>`;

              $(".eccw-designer-list").prepend(card);

              $("#eccw-shortcode-modal, #eccw-modal-overlay").fadeOut();
              $("#eccw-shortcode-modal :input").val("");
            } else {
              alert(response.data || "Error creating shortcode");
            }
          }
        );
      });
    },

    // shortcode delete js

    eccwShortcodeDelete: function () {
      $(document).on("click", ".eccw-btn-delete", function (e) {
        if (!confirm("Are you sure you want to delete this shortcode?")) return;

        const id = $(this).data("id");

        $.post(
          eccw_vars.ajaxurl,
          {
            action: "eccw_delete_shortcode",
            nonce: eccw_vars.nonce,
            id: id,
          },
          function (response) {
            if (response.success) {
              card.remove();
            } else {
              alert(response.data || "Failed to delete shortcode.");
            }
          }
        );
      });
    },

    // shortcode copy js

    copyEccwShortcode: function () {
      $(document).on("click", ".eccw-copy-btn", function () {
        const input = $(this).siblings(".eccw-shortcode-input")[0];
        input.select();
        input.setSelectionRange(0, 99999);

        try {
          document.execCommand("copy");

          $(".eccw-copy-btn").each(function () {
            $(this).html(
              '<img draggable="false" role="img" class="emoji" alt="📋" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f4cb.svg">'
            );
          });

          const button = $(this);
          button.html(
            '<img draggable="false" role="img" class="emoji" alt="✅" src="https://s.w.org/images/core/emoji/16.0.1/svg/2705.svg">'
          );

          setTimeout(() => {
            button.html(
              '<img draggable="false" role="img" class="emoji" alt="📋" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f4cb.svg">'
            );
          }, 5000);
        } catch (err) {
          alert("Failed to copy");
        }
      });
    },

    // shortcode modal js

    EccwShortcodeModal: function () {
      $(document).ready(function ($) {
        let currentShortcodeId = null;
        let switcherType = null;

        $(document).on("click", ".eccw-btn-edit", function (e) {
          e.preventDefault();

          switcherType = $(this).data("switchertype");
          currentShortcodeId = $(this).data("id");

          $("#eccw-style-modal-switcher-id").val(currentShortcodeId);
          $("#eccw-style-modal-switcher-type").val(switcherType);

          $(".eccw-style-modal-switcher-form").empty();
          $(".eccw-tab-btn").removeClass("active");
          $(".eccw-tab-btn[data-tab='eccw_general_tab']").addClass("active");

          $(".eccw-style-modal-switcher-form").attr(
            "data-eccwtab",
            "eccw_general_tab"
          );

          eccwLoadModalTabContent(
            currentShortcodeId,
            "eccw_general_tab",
            false,
            function () {
              $("#eccw-style-modal-switcher").fadeIn();
            }
          );
        });

        // Tab click event
        $(document).on("click", ".eccw-tab-btn", function (e) {
          e.preventDefault();
          let tabKey = $(this).data("tab");

          console.log(tabKey);

          if (!currentShortcodeId) return;

          $(".eccw-tab-btn").removeClass("active");
          $(this).addClass("active");

          $(".eccw-style-modal-switcher-form").attr("data-eccwtab", tabKey);

          eccwLoadModalTabContent(
            currentShortcodeId,
            tabKey,
            true,
            function () {
            }
          );
        });

        function eccwLoadModalTabContent(
          shortcodeId,
          tabKey,
          animate,
          callback
        ) {
          let $formWrapper = $(".eccw-style-modal-switcher-form");

          let fixedHeight = $formWrapper.outerHeight();
          $formWrapper.css("min-height", fixedHeight + "px");

          if (animate) {
            $formWrapper.addClass("eccw-loading");
          }

          $.post(
            eccw_vars.ajaxurl,
            {
              action: "eccw_load_modal_content",
              shortcode_id: shortcodeId,
              tab_key: tabKey,
              nonce: eccw_vars.nonce,
            },
            function (response) {
              if (response.success) {
                if (animate) {
                  $formWrapper.fadeOut(150, function () {
                    $formWrapper
                      .html(response.data.html)
                      .fadeIn(150, function () {
                        $formWrapper.css("min-height", "");
                        $formWrapper.removeClass("eccw-loading");
                        ECCWAdmin.initializeEccwModalFormInputs($formWrapper);

                        bindSaveButtonEnable();
                        ECCWAdmin.initializeRangeSlider();
                        if (callback) callback();
                      });
                  });
                } else {
                  $formWrapper.html(response.data.html);
                  ECCWAdmin.initializeEccwModalFormInputs($formWrapper);
                  $formWrapper.css("min-height", "");
                  ECCWAdmin.initializeRangeSlider();
                  if (callback) callback();
                }
              } else {
                alert("Failed to load modal content.");
                $formWrapper.css("min-height", "");
                if (callback) callback();
              }
            }
          );
        }

        // Close modal
        $(".eccw-style-modal-switcher-close")
          .off("click")
          .on("click", function () {
            closeEccwModal();
          });

        $("#eccw-style-modal-switcher, .eccw-shortcode-modal")
          .off("click")
          .on("click", function (event) {
            if (
              event.target.id === "eccw-style-modal-switcher" ||
              event.target.id === "eccw-shortcode-modal"
            ) {
              closeEccwModal();
            }
          });

        function closeEccwModal() {
          $("#eccw-style-modal-switcher, .eccw-shortcode-modal,.eccw-modal-overlay").fadeOut();
        }
      });
    },

    SwitcherModalStyle: function () {
      function saveStyleAndMaybeClose(shouldClose) {
        let form = $(".eccw-style-modal-switcher-form");
        let shortcodeId = $("#eccw-style-modal-switcher-id").val();
        let serializedData = form.find(":input").serializeArray();

        serializedData.push(
          { name: "action", value: "eccw_save_shortcode_style" },
          { name: "sd_id", value: shortcodeId },
          { name: "nonce", value: eccw_vars.nonce }
        );

        $.post(eccw_vars.ajaxurl, serializedData, function (response) {
          if (response.success) {
            if (shouldClose) {
              $("#eccw-style-modal-switcher").fadeOut();
            }
            $(
              ".eccw-style-modal-switcher-save-btn, .eccw-style-modal-switcher-save-closebtn"
            ).prop("disabled", true);
          }
        });
      }

      $(".eccw-style-modal-switcher-save-btn").on("click", function (e) {
        e.preventDefault();
        saveStyleAndMaybeClose(false);
      });

      $(".eccw-style-modal-switcher-save-closebtn").on("click", function (e) {
        e.preventDefault();
        saveStyleAndMaybeClose(true);
      });
    },


    initializeRangeSlider: function () {
      function initializeSliders() {
        $(".eccw-slider-range")
          .off("input change")
          .on("input change", function () {
            let sliderId = $(this).attr("id");
            let escapedId = sliderId.replace(/([\[\]])/g, "\\$1");
            $("#" + escapedId + "_value").val($(this).val());
          });

        $(".eccw-slider-range-value")
          .off("input change")
          .on("input change", function () {
            let numberInputId = $(this).attr("id");

            let sliderId = numberInputId.replace(/_value$/, "");
            let escapedSliderId = sliderId.replace(/([\[\]])/g, "\\$1");

            let newVal = $(this).val();

            let $slider = $("#" + escapedSliderId);
            let min = parseInt($slider.attr("min"), 10);
            let max = parseInt($slider.attr("max"), 10);

            if (newVal === "") newVal = min;
            else newVal = Math.min(Math.max(parseInt(newVal, 10), min), max);

            $(this).val(newVal);
            $slider.val(newVal);
          });
      }

      initializeSliders();
    },

    eccwTabSwitch: function () {
      $(document).on("click", ".eccw-tab-toggle .eccw-tab-option", function () {
        let $this = $(this);
        let value = $this.data("value");
        let $wrapper = $this.closest(".eccw-tab-toggle");

        $wrapper.find(".eccw-tab-option").removeClass("active");
        $this.addClass("active");

        let inputName = $wrapper.data("input");
        $('input[name="' + inputName + '"]')
          .val(value)
          .trigger("change");
      });
    },

    initSwitcherToggle:  function(panelSelector, checkboxName) {
        var $panel = $(panelSelector);

        if (!$panel.length) return; 

        function toggleSwitcherFields() {
            var isChecked = $panel.find('input[name="' + checkboxName + '"]').is(':checked');

            if (isChecked) {
                $panel.find('.eccw-switcher-ui-control').slideDown();
                $panel.find('.eccw-position-settings').slideDown();
            } else {
                $panel.find('.eccw-switcher-ui-control').slideUp();
                $panel.find('.eccw-position-settings').slideUp();
            }
        }

        toggleSwitcherFields();

        $panel.find('input[name="' + checkboxName + '"]').on('change', function() {
            toggleSwitcherFields();
        });
    },


  };

  ECCWAdmin.init();

  function bindSaveButtonEnable() {
    $(".eccw-style-modal-switcher-form").on(
      "input change",
      ":input",
      function () {
        $(
          ".eccw-style-modal-switcher-save-btn, .eccw-style-modal-switcher-save-closebtn"
        )
          .prop("disabled", false)
          .removeAttr("disabled");
      }
    );
  }

  bindSaveButtonEnable();

   $('#eccw-modal-overlay').on('click', function() {
    $('#eccw-modal-overlay, #eccw-shortcode-modal').fadeOut();
  });

  $('.eccw-sticky-select2').select2({
      placeholder: 'Select pages', 
      allowClear: true 
  });

   ECCWAdmin.initSwitcherToggle(
      '#tab_currency_switcher_sticky', 
      'design[eccw_show_hide_side_currency]'
  );
  

})(jQuery);
