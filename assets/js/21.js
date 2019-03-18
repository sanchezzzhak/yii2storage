// disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator && navigator.userAgent),
// imageMaxWidth: 800,
// imageMaxHeight: 800,
// imageCrop: true,

progressall: function (e, data) {
  var progress = parseInt(data.loaded / data.total * 100, 10);
  $el.find('.progress .bar').css('width', progress + '%');
},
add: function (e, data) {
  var $this = $(this);
  
  
  
  
  // var conteiner = $el.find('.files');
  // var odata = {
  // files: data.files,
  // autoUpload: self.options.autoUpload,
  // labelCancel: self.options.labelCancel,
  // labelProcessingUpload: self.options.labelProcessingUpload,
  // labelStart: self.options.labelStart,
  // labelDelete: self.options.labelDelete,
  // };
  // data.context = $(tmpl(self.options.uploadItemTemplate, odata));
  //
  //
  // if (self.options.singleFileUploads) {
  // conteiner.html(data.context);
  // } else {
  // data.context.appendTo(conteiner);
  // }
  //
  // data.context.find('.start').on('click', function(){
  // data.submit();
  // })
  // data.context.find('.cancell').on('click', function(){
  // data.abort();
  // })
  
  
  data.context = $el.find('.files');
  
  $.each(data.files, function (index, file) {
	

	node.append(uploadButton.clone(true).data(data));
	
	
	node.appendTo(data.context);
  });
  
  
  
  data.process(function () {
	return $this.fileupload('process', data);
  })
  .always(function () {
	data.context.each(function (index) {
	  var size = (data.files[index]) ? data.files[index].size : 0;
	  var name = (data.files[index]) ? data.files[index].name : '';
	  
	  var canvas = data.files[index].preview;
	  if(canvas){
		var dataURL = canvas.toDataURL();
		$(this).find('.preview').css("background-image", 'url(' + dataURL + ')');
	  }
	  $(this).find('.name').text(name);
	  $(this).find('.size').text(formatFileSize(size));
	  
	}).removeClass('processing');
  })
  .done(function () {
	if (odata.autoUpload) {
	  data.submit();
	  return;
	}
  });
  
},
done: function (e, data) {
  
  /** @var {ViewFiles}*/
  var plugin = self.app.getPlugin('ViewFiles');
  if (!plugin) {
	throw new Error('Plugin ViewFiles not found');
  }
  
  plugin.addFile(data.result);
  
  data.context.remove();
  
  // $(data.context).each(function (index) {
  //    console.log('data.files', data);
  // });
  // $(data.context).each(function (index) {
  //   var file = data.files[index] || {error: 'Empty file upload result'};
  //   var node = $(this);
  //   var odata = {
  // 	result: data.result,
  // 	files: data.files,
  // 	formatFileSize: self._formatFileSize,
  // 	autoUpload: $el.data('autoupload')
  //   };
  //    data.context = $(tmpl(self.$tmpl_download, odata)).replaceAll(node);
  //   data.context.find('.meta').val( JSON.stringify(odata.result) );
  //
  // });
}