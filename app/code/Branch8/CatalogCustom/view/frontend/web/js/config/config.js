require(
    [
        'jquery',
        'mage/translate',
    ],
    function ($) {
    	if($('#dotsquares_imageeditor_general_theme').val() == 1){
    			 $('#dotsquares_imageeditor_general_menu option[value = 3]').show();
    			 $('#dotsquares_imageeditor_general_menu option[value = 4]').show();
    			 $('#dotsquares_imageeditor_general_menu option[value = 1]').hide();
    			 $('#dotsquares_imageeditor_general_menu option[value = 2]').hide();
    		}else{
    			$('#dotsquares_imageeditor_general_menu option[value = 3]').hide();
    			 $('#dotsquares_imageeditor_general_menu option[value = 4]').hide();
    			 $('#dotsquares_imageeditor_general_menu option[value = 1]').show();
    			 $('#dotsquares_imageeditor_general_menu option[value = 2]').show();
    		}

    	$('#dotsquares_imageeditor_general_theme').change(function(){    		
    		if($(this).val() == 1){
    			 $('#dotsquares_imageeditor_general_menu option[value = 3]').show();
    			 $('#dotsquares_imageeditor_general_menu option[value = 4]').show();
    			 $('#dotsquares_imageeditor_general_menu option[value = 1]').hide();
    			 $('#dotsquares_imageeditor_general_menu option[value = 2]').hide();
    			 $('#dotsquares_imageeditor_general_menu').val(4).trigger('change');
    		}else{
    			$('#dotsquares_imageeditor_general_menu option[value = 3]').hide();
    			 $('#dotsquares_imageeditor_general_menu option[value = 4]').hide();
    			 $('#dotsquares_imageeditor_general_menu option[value = 1]').show();
    			 $('#dotsquares_imageeditor_general_menu option[value = 2]').show();
    			 $('#dotsquares_imageeditor_general_menu').val(2).trigger('change');
    		}
    	});
    }
);