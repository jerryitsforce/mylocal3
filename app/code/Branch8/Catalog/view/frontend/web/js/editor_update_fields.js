require([
    'jquery',
    'plugins/DOMPurify'
], function($, DOMPurify) {
    'use strict';
    var editorFields = ['note', 'specification', 'recommendation', 'description'];
    var intervalEditor = setInterval(function(){
        if(typeof tinymce != "undefined"){
            $.each(editorFields, function(k, elm){
                $('#edit-product #'+elm).click(function(){
                    setUpdatedContent(elm);
                });
                tinymce.get(elm).on('keyup', function(e) {
                    setUpdatedContent(elm);
               });
            });
            clearInterval(intervalEditor);
        }
    }, 100);
    

    function setUpdatedContent(elm){
        let currentUpdatedFields = $('#editor_updated_fields').val();
        if(currentUpdatedFields.trim() == ''){
            $('#editor_updated_fields').val(elm);
        }else{
            let currentUpdatedFieldsObj = currentUpdatedFields.split(',');
            if(!currentUpdatedFieldsObj.includes(elm)){
                currentUpdatedFieldsObj[currentUpdatedFieldsObj.length] = elm;
            }
            $('#editor_updated_fields').val(currentUpdatedFieldsObj.join(','));
        }
    }
});