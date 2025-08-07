
(function($) {

    ECCWPublic = {
        init: function (){
            this.closeDropdownOutSideClick();
            $( document )
                .on( 'click.ECCWPublic', '.easy-currency-switcher .easy-currency-switcher-toggle', this.openDropdown )
                .on( 'click.ECCWPublic', '.easy-currency-switcher-select li', this.updateSwitcher )
        },
        openDropdown: function (e) { 
            e.stopPropagation(); // Prevent propagation to the document
            $('.easy-currency-switcher-select').toggleClass('open'); // Toggle the dropdown
        },
        closeDropdownOutSideClick: function () {
            // Close dropdown when clicking outside
            $(document).on('click', function (e) {
                const dropdown = $('.easy-currency-switcher-select');
                if (!dropdown.is(e.target) && dropdown.has(e.target).length === 0) {
                    dropdown.removeClass('open'); // Close dropdown
                }
            });
        },
        updateSwitcher: function (e) {
            // let selectedCurrency = $(e.target).data('value');
            // let $form = $('.easy_currency_switcher_form');

            // $form.find('input[name="easy_currency"]').val(selectedCurrency);

            // let formData = new FormData($form[0]);

            // let baseUrl = window.location.href.split('?')[0];
            // let redirectUrl = baseUrl + '?easy_currency=' + selectedCurrency;

            // fetch(redirectUrl, {
            //     method: 'POST',
            //     body: formData,
            //     credentials: 'same-origin'
            // })
            // .then(response => {
            //     if (response.redirected) {
            //         window.location.href = response.url; 
            //     } else {
            //         window.location.href = redirectUrl;
            //     }
            // });
        }
    }

    ECCWPublic.init();


})(jQuery);