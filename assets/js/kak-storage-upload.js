(function($){
    "use strict";

    var kakStorageUpload = function (element, options) {
        var self = this;
        self.element = $(element);
        self.init(options);
        self.destroy();
        self.create();
    };
    /**
     * @type {{constructor: Function, init: Function, destroy: Function, create: Function}}
     */
    kakStorageUpload.prototype = {
        constructor: kakStorageUpload,
        /**
         * @param options
         */
        selectors : {
            base_class    : '.kak-storage-upload',
            item_upload   : '.template-upload',
            item_download : '.template-download'
        },
        init: function (options) {
            var self = this, $el = self.element;

            self.$tmpl_upload = $el.data('tmpl-add') || 'tmpl-add';
            self.$tmpl_download = $el.data('tmpl-download') || 'tmpl-download';

            self.$click  = $el.find(this.selectors.base_class);

            $.each(options, function (key, value) {
                self[key] = value;
            });
            $el.triggerHandler('init',this);
        },
        destroy : function(){
            var self = this, $el = self.element;
            $el.triggerHandler('destroy',this);

            $(document).off('click',self.selectors.base_class + ' a.preview');
            $(document).off('click',self.selectors.base_class + ' .crop');
            $(document).off('click',self.selectors.base_class + ' .crop-cancel');

        },
        create: function(){
            var self = this, $el = self.element;
            $el.triggerHandler('create',this);

            this.initFileUpload();
            this.initCropHandle();
            this.initPreviewHandle();

        },

        initCropHandle: function(){
            var self = this, $el = self.element;

            $(document).on('click',self.selectors.base_class + ' .crop' ,function(e){

                $(this).closest(self.selectors.item_download).find('.preview-box > img').off().cropper({
                    autoCropArea: 0.6,
                    zoomable: false
                }).on('resize.cropper, built.cropper', function(){
                    $(this).closest(self.selectors.item_download).find('.cropper-container').css('top',0).css('left',0);
                });

                return false;
            });
            $(document).on('click',self.selectors.base_class  + ' .crop-cancel',function() {

                var box = $(this).closest(self.selectors.item_download).find('.preview-box');
                if($el.data('crop') && box.find('.cropper-container').length > 0  ) {
                    $(this).closest(self.selectors.item_download).find('.preview-box > img').cropper("destroy");
                    return false;
                }
                box.removeClass('show').addClass('hide');
                $(this).closest(self.selectors.item_download).find('a.preview').show();

                return false;
            });
        },
        initPreviewHandle: function(){
            var self = this, $el = self.element;
            console.log(self.selectors.base_class);
            $(document).on('click',self.selectors.base_class + ' a.preview' ,function(e){

                console.log('click preview');

                $(this).hide();
                $(this).closest(self.selectors.item_download).find('.preview-box').removeClass('hide').addClass('show');
                return false;
            })

        },
        initFileUpload: function(e){
            var self = this, $el = self.element;

            console.log('initFileUpload');

            $el.closest('form').fileupload({
                multiple    : $el.data('multiple') == '1' || false,
                autoUpload  : $el.data('autoupload') == '1' || false,
                dataType    : 'json',
                singleFileUploads: true,
                url : $el.data('url'),
                uploadTemplateId: null,
                downloadTemplateId: null,
                progressall: function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    $(this).find('.progress .bar').css('width',progress + '%');
                },
                add:  function(e, data){

                    if (e.isDefaultPrevented()) {
                        return false;
                    }
                    var $this = $(this);
                    var conteiner = $this.find('.files');

                    var odata = {
                        files: data.files,
                        autoUpload: $el.data('autoupload')
                    };
                    data.context = $(tmpl(self.$tmpl_upload, odata));

                    if( $el.data('singleupload') ){
                        conteiner.html(data.context);
                    }else{
                        data.context.appendTo(conteiner);
                    }

                    data.process(function () {
                        return $this.fileupload('process', data);
                    }).always(function () {
                        data.context.each(function (index) {
                            var size = (data.files[index])?  data.files[index].size : 0;
                            if(size == 0)
                                return false;

                            $(this).find('.size').text(self._formatFileSize(size));

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
                                formatFileSize: self._formatFileSize,
                                autoUpload: $el.data('autoupload')
                            };
                            data.context = $(tmpl(self.$tmpl_download, odata)).replaceAll(node);
                            data.context.find('.meta').val( JSON.stringify(odata.result) );

                        });
                    }
                }
            });
            return true;

        },
        _formatFileSize : function (bytes) {
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
    };

    $.fn.kakStorageUpload = function (option) {
        var args = Array.apply(null, arguments);
        args.shift();
        return this.each(function () {
            var $this = $(this), data = $this.data('kakStorageUpload'), options = typeof option === 'object' && option;
            if (!data) {
                data = new kakStorageUpload(this, $.extend({}, $.fn.kakStorageUpload.defaults, options, $(this).data()));
                $this.data('kakStorageUpload', data);
            }
            if (typeof option === 'string') {
                data[option].apply(data, args);
            }
        });
    };
    $.fn.kakStorageUpload.defaults = {};
    $.fn.kakStorageUpload.Constructor = kakStorageUpload;

})(window.jQuery);
