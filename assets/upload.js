$(function(){

    $.each($('.yii2upload'), function(k,yii2upload){

        $(yii2upload).find('input:file').fileupload({
            autoUpload  : $(yii2upload).data('autoupload'),
            dataType    : 'json',
            url : $(yii2upload).data('url'),
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $(yii2upload).find('.progress .bar').css('width',progress + '%');
            },
            started: function(){

            },
            done: function (e, data) {
                var result = data.result;
                console.log(result);
            }
        });
    });
});