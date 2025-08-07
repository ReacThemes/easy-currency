
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
            let selectedCurrency = $(e.target).data('value');
            $('.easy_currency_switcher_form input[name="easy_currency"]').val(selectedCurrency);
            $('.easy_currency_switcher_form input[name="easy_currency"]').attr('val', selectedCurrency);
            $('.easy_currency_switcher_form').submit();
        }
    }

    ECCWPublic.init();






    


})(jQuery);