$(function(){



    var yii2upload = function() {

        var _class = '.yii2upload';
        var _item  = '.template-upload';
        var _itemDownload  = '.template-download';
        var _formatFileSize = function (bytes) {
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
        };

        function handledUploaderInit(){

            $(document).on('click',_class,function(e){
                var yii2upload = $(this);

                var tmpl_add      = yii2upload.data('tmpl-add') || 'tmpl-add';
                var tmpl_download = yii2upload.data('tmpl-download') || 'tmpl-download';

                console.log(tmpl_download , tmpl_add);

                yii2upload.closest('form').fileupload({
                    multiple    : yii2upload.data('multiple') == '1' || false,
                    autoUpload  : yii2upload.data('autoupload') == '1' || false,
                    dataType    : 'json',
                    singleFileUploads: true,
                    url : yii2upload.data('url'),
                    uploadTemplateId: null,
                    downloadTemplateId: null,
                    progressall: function (e, data) {
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

                        data.context = $(tmpl(tmpl_add, odata));

                        if( $this.find(_class).data('singleupload') ){
                            conteiner.html(data.context);
                        }else{
                            data.context.appendTo(conteiner);
                        }

                        data.process(function () {
                            return $this.fileupload('process', data);

                        }).always(function () {

                            data.context.each(function (index) {

                                var size = (data.files[index])?  data.files[index].size : 0;
                                if(size == 0) return false;

                                $(this).find('.size').text(
                                    _formatFileSize(size)
                                );

                            }).removeClass('processing');

                        }).done(function () {

                            data.context.find('.start').prop('disabled', false);
                            if(odata.autoUpload)
                                data.submit();

                        });
                    },

                    done: function (e, data) {
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

                                data.context = $(tmpl(tmpl_download, odata)).replaceAll(node);
                                data.context.find('.meta').val( JSON.stringify(odata.result) );

                            });
                        }

                    }
                });
                return true;
            });

        }

        function handledCropperInit(){

            $(document).on('click',_class + ' .crop' ,function(e){

                $(this).closest(_itemDownload).find('.preview-box > img').off().cropper({
                    autoCropArea: 0.6,
                    zoomable:false
                }).on('resize.cropper, built.cropper', function(){
                    $(this).closest(_itemDownload).find('.cropper-container').css('top',0).css('left',0);
                });
                return false;
            });

            $(document).on('click',_class + ' .crop-cancel',function() {

                var box = $(this).closest(_itemDownload).find('.preview-box');
                if($(this).closest(_class).data('crop') && box.find('.cropper-container').length > 0  ) {
                    $(this).closest(_itemDownload).find('.preview-box > img').cropper("destroy");
                    return false;
                }

                box.removeClass('show').addClass('hide');
                $(this).closest(_itemDownload).find('a.preview').show();

                return false;
            });
        }

        function handledUploaderPreviewInit(){
            $(document).on('click',_class + ' a.preview' ,function(e){
                $(this).hide();
                $(this).closest(_itemDownload).find('.preview-box').removeClass('hide').addClass('show');
                return false;
            })
        }




        function init(){
            handledUploaderInit();
                handledCropperInit();
                    handledUploaderPreviewInit();
        }
        init();
    };
    yii2upload();


});