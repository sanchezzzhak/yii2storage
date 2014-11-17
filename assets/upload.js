$(function(){

    var _class = '.yii2upload';
    var _item = '.template-upload';

    _formatFileSize = function (bytes) {
        if (typeof bytes !== 'number') {
            return '';
        }
        if (bytes >= 1000000000) {
            return (bytes / 1000000000).toFixed(2) + ' GB';
        }
        if (bytes >= 1000000) {
            return (bytes / 1000000).toFixed(2) + ' MB';
        }
        return (bytes / 1000).toFixed(2) + ' KB';
    }

    $.each($(_class), function(k,yii2upload){

        $(yii2upload).closest('form').fileupload({
            multiple    : $(yii2upload).data('multiple'),
            autoUpload  : $(yii2upload).data('autoupload'),
            dataType    : 'json',
            singleFileUploads: true,
            url : $(yii2upload).data('url'),
            uploadTemplateId: null,
            downloadTemplateId: null,
            progressall: function (e, data) {
                console.log(data);

                var progress = parseInt(data.loaded / data.total * 100, 10);
                $(this).find('.progress .bar').css('width',progress + '%');


            },
            add: function (e, data) {
                if (e.isDefaultPrevented()) {
                    return false;
                }
                var $this = $(this);
                var conteiner = $this.find('.files');
                var odata = {
                    files: data.files,
                    autoUpload: $(this).find(_class).data('autoupload')
                };

                data.context = $(tmpl("tmpl-add", odata));
                data.context.appendTo(conteiner);

                data.process(function () {
                    return $this.fileupload('process', data);
                }).always(function () {
                    data.context.each(function (index) {
                        $(this).find('.size').text(
                            _formatFileSize(data.files[index].size)
                        );
                    }).removeClass('processing');
                }).done(function () {
                    data.context.find('.start').prop('disabled', false);

                    if(odata.autoUpload) data.submit();

                });
            },

            done: function (e, data) {

                var $this = $(this);
                        console.log(data);
                if (data.context) {
                    $(data.context).each(function (index) {
                        var file = data.files[index] || {error: 'Empty file upload result'};
                        var node = $(this);
                        var odata = {
                            result: data.result,
                            files: data.files,
                            formatFileSize: _formatFileSize,
                            autoUpload: $(this).find(_class).data('autoupload')
                        };
                        data.context = $(tmpl("tmpl-download", odata)).replaceAll(node);

                        data.context.find('a.preview').on('click', function(){
                            $(this).hide();
                            var box = $(this).closest(_item).find('.preview-box');
                            box.removeClass('hide').addClass('show');
                        });

                        data.context.find('.crop').on('click',function() {
                            $(this).closest(_item).find('.preview-box > img').cropper({
                                autoCropArea: 0.6,
                                zoomable:false
                            }).on('resize.cropper', function(e){
                                $(this).closest(_item).find('.cropper-container').css('top',0).css('left',0);
                            });


                        });

                        data.context.find('.crop-cancel').on('click',function() {
                            var box = $(this).closest(_item).find('.preview-box');
                                //box.removeClass('show').addClass('hide');
                            if( box.find('.cropper-container').length > 0  ) {
                                $(this).closest(_item).find('.preview-box > img').cropper("destroy");
                            } else {
                                box.removeClass('show').addClass('hide');
                                $(this).closest(_item).find('a.preview').show();
                            }
                        });

                    });
                }
            }
        })

    });


});