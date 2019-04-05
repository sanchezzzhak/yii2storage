(function ($) {
  "use strict";
  
  var TYPES = {
	ADAPTER: 'adapter',
	VIEW_FILES: 'view-files',
	FILE: 'file'
  };
  
  var SELECTORS = {
	WRAP_HEADER: '.wgt-wrap-header',
	HEADER_MORE: '.wgt-header-more',
	WRAP_CONTENT: '.wgt-wrap-content'
  };
  
  
  // ==========================================
  // HELPERS
  // ==========================================
  
  function inherits(ctor, superCtor) {
	Object.defineProperty(ctor, 'super_', {
	  value: superCtor,
	  writable: true,
	  configurable: true
	});
	Object.setPrototypeOf(ctor.prototype, superCtor.prototype);
  }
  
  function formatFileSize(bytes) {
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
  
  // ==========================================
  // CORE PLUGIN
  // ==========================================
  
  function Plugin(app, options) {
	this.app = app;
	this.options = options || {};
  }
  
  Plugin.prototype = {
	constructor: Plugin,
	install: function () {
	},
	mount: function (target, plugin) {
	  var pluginName = plugin.id;
	  if (!target) {
		target = this.app.element;
	  }
	  
	  this.el = $(target);
	  this.render({});
	},
	
	hidePlugin: function(){
	  if(this.el !== undefined && this.block !== undefined){
		return this.el.find('.' +  this.block).hide();
	  }
	},
 
	showPlugin: function(){
	  if(this.el !== undefined && this.block !== undefined){
		return this.el.find('.' +  this.block).show();
	  }
	},
	
	getPluginContainer: function(){
	  if(this.el === undefined || this.block === undefined){
	    return null;
	  }
	  return this.el.find('.' +  this.block);
	},
	
	render: function (state) {
	  throw (new Error('Extend the render method to add your plugin to a DOM element'));
	}
  };
  
  // ==========================================
  // ADAPTERS PLUGIN
  // ==========================================
  
  function AdaptersPlugin(app, options) {
	var defaultOpts = {
	  template: null
	};
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(AdaptersPlugin, Plugin);
	
	this.id = this.options.id || 'Adapters';
	this.type = 'adapters';
	this.block = 'wgt-adapters-plugin';
  }
  
  AdaptersPlugin.prototype = {
	install: function () {
	  var template = this.options.template;
	  if (template === null) {
		this.options.template = `
			<ul></ul>
		`;
	  }
	  this.mount(this.options.target, this);
	},
	render: function (stage) {
	  var plugin = $('<div>', {class: this.block});
	  var compileTmpl = tmpl(this.options.template, {});
	  plugin.append(compileTmpl);
	  this.el.find(SELECTORS.WRAP_CONTENT).append(plugin);
	  
	  // header add menu
	  var btnMore = $('<button>', {
	    type: 'button'
	  }).text('+').off('click').on('click', $.proxy(this.onShowView, this));
	  this.el.find(SELECTORS.WRAP_HEADER).find(SELECTORS.HEADER_MORE).append(btnMore);
	  
	  this.initViews();
	},
	onShowView: function (e) {
	  var plugin = this.el.find('.' + this.block);
	  if (!plugin.hasClass(".wgt-stage-open")) {
		plugin.addClass('wgt-stage-open');
	  } else {
		plugin.removeClass('wgt-stage-open');
	  }
	},
	showAdapterById: function (pluginId) {
	  for (var i = 0, l = this.app.plugins[TYPES.ADAPTER].length; i < l; i++) {
		var plugin = this.app.plugins[TYPES.ADAPTER][i];
		if (plugin.id === pluginId) {
		  this.el.find('.' + plugin.block).show();
		}
	  }
	},
	hideAllAdapters: function () {
	  this.app.hidePluginsByType(TYPES.ADAPTER);
	},
	onShowPlugin: function (e) {
	  var el = $(e.currentTarget);
	  var pluginId = el.data('plugin');
	  this.hideAllAdapters();
	  this.showAdapterById(pluginId);
	  this.el.find('.' + this.block).removeClass('wgt-stage-open');
	},
	initViews: function () {
	  var container = this.el.find('.' + this.block).find('ul');
	  for (var i = 0, l = this.app.plugins[TYPES.ADAPTER].length; i < l; i++) {
		var plugin = this.app.plugins[TYPES.ADAPTER][i];
		var li = $('<li>');
		var item = $('<a>', {
		  href: '#', data: {
			'plugin': plugin.id
		  }
		});
		item.text(plugin.id);
		item.off('click').on('click', $.proxy(this.onShowPlugin, this));
		container.append(li.append(item));
	  }
	}
	
  };
  
  // ==========================================
  // CROP IMAGE PLUGIN
  // ==========================================
  
  
  function CropImagePlugin(app, options){
	var defaultOpts = {
	  template: null,
	  isHidden: true,
	  endPointUrl: '',
	  
	  // cropperjs settings
	  cropperOptions: {
		autoCropArea: 0.6,
		zoomable: true,
		rotatable:true
	  },
	  
	  labelSave: '<i class="fas fa-crop-alt"></i> Save',
	  labelCancel: '<i class="fas fa-ban"></i> Cancel',
	  labelRotateUp: '<i class="fa fa-undo-alt"></i>',
	  labelRotateDown: '<i class="fa fa-redo-alt"></i>',
	  labelFlipHorizontal: '<i class="fa fa-arrows-alt-h"></i>',
	  labelFlipVertical: '<i class="fa fa-arrows-alt-v"></i>',
	  
	  enableRotate: true,
	  enableFlip: true,
	  rotateDegrees: 90
	  
	};
	
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(CropImagePlugin, Plugin);
 
	this.id = this.options.id || 'CropImage';
	this.type = TYPES.FILE;
	this.block = 'wgt-crop-image-plugin';
	
	this.fileId = null;
  }
  
  CropImagePlugin.prototype = {
  
	init: function () {
	  
	  var wrap = this.getPluginContainer();
	
	  wrap.off('click', '.crop-cancel').on('click', '.crop-cancel', $.proxy(this.cropCancel, this));
	  wrap.off('click', '.crop-me').on('click', '.crop-me', $.proxy(this.cropSave, this));
	  
	  wrap.off('click', '.crop-rotate-up').on('click', '.crop-rotate-up', $.proxy(this.cropRotateUp, this));
	  wrap.off('click', '.crop-rotate-down').on('click', '.crop-rotate-down', $.proxy(this.cropRotateDown, this));
	  
	  wrap.off('click', '.crop-flip-horizontal').on('click', '.crop-flip-horizontal', $.proxy(this.cropFlipHorizontal, this));
	  wrap.off('click', '.crop-flip-vertical').on('click', '.crop-flip-vertical', $.proxy(this.cropFlipVertical, this));
	  

	  /*

.cropper-crop-box, .cropper-view-box {
  border-radius: 50%;
}

.cropper-view-box {
  box-shadow: 0 0 0 1px #39f;
  outline: 0;
}

function getRoundedCanvas(sourceCanvas) {
  var canvas = document.createElement('canvas');
  var context = canvas.getContext('2d');
  offsetTop = Math.round(cropper.getCropBoxData().top);
  offsetLeft = Math.round(cropper.getCropBoxData().left);
  var width = sourceCanvas.width;
  var height = sourceCanvas.height;
  canvas.width = width;
  canvas.height = height;
  context.imageSmoothingEnabled = true;
  context.drawImage(sourceCanvas, 0, 0, width, height);
  context.globalCompositeOperation = 'destination-in';
  context.beginPath();
  context.ellipse(width/2, height/2, width/2, height/2, 0 * Math.PI, 0, 45 * Math.PI);
  context.fill();
  return canvas;
}

*/
	},
	
	install: function () {
	  var target = this.options.target;
	  this.mount(target, this);
	  this.init();
	},
	
	cropSave: function(e){
	  var self = this;
	  var el = $(e.currentTarget);
	  
	  var viewFilesPlugin = this.app.getPlugin('ViewFiles');
	  
	  var img = this.getImage();
	  var canvas = img.cropper('getCroppedCanvas');
	  var imgUrl = canvas.toDataURL('image/jpeg');
	  
	  var replaceJson = viewFilesPlugin.getItemResult(self.fileId);
	  
	  canvas.toBlob(function(blob){
	    var formData = new FormData();
		formData.append('cropped', blob);
		formData.append('replace', replaceJson);
		
		var endPointUrl = self.options.endPointUrl + $.param({ act: 'crop'});
		
		$.ajax(endPointUrl, {
		  method: "post",
		  data: formData,
		  processData: false,
		  contentType: false,
		  success: function(result) {
			if (typeof result === 'string') {
			  result = JSON.parse(result);
			}
			viewFilesPlugin.updateFile(self.fileId, result);
			self.cropCancel();
		  },
		  error: function(err) {
			console.log('Upload error');
		  }
		});
	  });
	  
	},
 
	getImage: function(){
	  var container = this.getPluginContainer();
	  return container.find('.cropper-container .wrap-image-source');
	},
 
	cropFlipHorizontal: function(e){
	  var data = this.getImage().cropper('getData');
	  this.getImage().cropper('scale', -data.scaleX, data.scaleY);
	},
 
	cropFlipVertical: function(e){
	  var data = this.getImage().cropper('getData');
	  this.getImage().cropper('scale', data.scaleX, -data.scaleY);
	},
	
	cropRotateUp: function(e){
	  this.getImage().cropper('rotate', this.options.rotateDegrees);
 	},
  
	cropRotateDown: function(e){
	  this.getImage().cropper('rotate', this.options.rotateDegrees * -1);
	},
	
  	cropCancel: function(e){
      this.hidePlugin();
      this.app.getPlugin('DeviceUpload').showPlugin();
      this.app.getPlugin('ViewFiles').showPlugin();
	},
	
	showCrop: function(id, result){
	  this.fileId = id;
	  
	  this.app.hidePluginsByType(TYPES.FILE);
	  this.app.hidePluginsByType(TYPES.VIEW_FILES);
	  this.app.hidePluginsByType(TYPES.ADAPTER);
	  
	  var container = this.getPluginContainer();
	  if (!container) {
		return;
	  }
	  this.showPlugin();
	  
	  var image = $('<img>', {src: result.base_url + result.path, class: 'wrap-image-source'});
	  var wrap = container.find('.cropper-container').empty();
	  
	  wrap.append(image);
	  wrap.find('img').off().cropper('destroy').cropper(this.options.cropperOptions);
	},
	
	render(stage) {
	  if (this.options.template === null) {
		this.options.template = `
			<div class="cropper-container"></div>
			<div class="cropper-footer">
			 	<button type="button" class="wgt-btn crop-me">{%# o.labelSave %}</button>
				<button type="button" class="wgt-btn crop-cancel">{%# o.labelCancel %}</button>
				
				{% if( o.enableRotate) { %}
					<button type="button" class="wgt-btn crop-rotate-up">{%# o.labelRotateUp %}</button>
					<button type="button" class="wgt-btn crop-rotate-down">{%# o.labelRotateDown %}</button>
				{% } %}
				
				{% if( o.enableFlip) { %}
					<button type="button" class="wgt-btn crop-flip-horizontal">{%# o.labelFlipHorizontal %}</button>
					<button type="button" class="wgt-btn crop-flip-vertical">{%# o.labelFlipVertical %}</button>
				{% } %}
			</div>
		`;
	  }
	  
	  var plugin = $('<div>', {class: this.block});
	  if (this.options.isHidden) {
		plugin.hide();
	  }
	  var compileTmpl = tmpl(this.options.template, {
		enableRotate: this.options.enableRotate,
		enableFlip: this.options.enableFlip,
		labelSave: this.options.labelSave,
		labelCancel: this.options.labelCancel,
		labelRotateUp: this.options.labelRotateUp,
		labelRotateDown:  this.options.labelRotateDown,
		labelFlipHorizontal: this.options.labelFlipHorizontal,
		labelFlipVertical:  this.options.labelFlipVertical
	  });
	  plugin.append(compileTmpl);
	  this.el.find('.wgt-wrap-content').append(plugin);
	  
	}
  };
  
  // ==========================================
  // EDIT FILE PLUGIN
  // ==========================================
  
  function EditFilePlugin(app, options){}
  
  // ==========================================
  // INSTAGRAM PLUGIN
  // ==========================================
  function InstagramPlugin(app, options) {http://nebesa.local/groups/create#
	var defaultOpts = {
	  isHidden: true,
	  template: null,
	  authUrl: '',
	};
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(InstagramPlugin, Plugin);
	
	this.id = this.options.id || 'Instagram';
	this.type = TYPES.ADAPTER;
	this.block = 'wgt-instagram-plugin';
	
	this.authWindow = null;
	this.authStage = false;
  }
  
  InstagramPlugin.prototype = {
    init: function(){
      var wrap = this.getPluginContainer();
      
      wrap.off('click', '.btn-inst-connect')
	  .on('click', '.btn-inst-connect', $.proxy(this.connectionAuthInstagram, this));
      
	},
	
	install: function () {
	  
	  if (this.options.template === null) {
		this.options.template = `<div class="auth-container">
			<button type="button" class="btn btn-inst-connect">Connect to Instagram</button>
		</div>`;
	  }
   
	  var target = this.options.target;
	  this.mount(target, this);
	  this.init();
	},
	
    render: function(stage){
	  var plugin = $('<div>', {class: this.block});
	  if (this.options.isHidden) {
		plugin.hide();
	  }
	  var compileTmpl = tmpl(this.options.template, {});
  
	  plugin.append(compileTmpl);
	  this.el.find('.wgt-wrap-content').append(plugin);
	},
 
	connectionAuthInstagram: function(e){
	  this.authWindow = window.open(this.options.authUrl, '_blank')
	},
	
  };
  
  
  // ==========================================
  // VIEW FILES PLUGIN
  // ==========================================
  
  function ViewFilesPlugin(app, options) {
	var defaultOpts = {
	  template: null,
	  downloadItemTemplate: null,
	  labelDelete: 'Delete',
	  labelEdit: 'Edit',
	  labelCrop: 'Crop',
	  inputName: 'meta[]'
	};
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(ViewFilesPlugin, Plugin);
	
	this.id = this.options.id || 'ViewFiles';
	this.type = 'view-files';
	this.block = 'wgt-views-files-plugin';
  }
  
  
  ViewFilesPlugin.prototype = {
    init: function(){
  
	  var selector = this.getFilesContainer();
	  
	  selector.off('click', '.delete')
	  .on('click', '.delete', $.proxy(this.removeFile, this));
	
	  selector.off('click', '.edit')
	  .on('click', '.edit', $.proxy(this.editFile, this));
	  
	  selector.off('click', '.crop')
	  .on('click', '.crop', $.proxy(this.cropImageFile, this));
	  
	  
	},
	install: function () {
	  var target = this.options.target;
	  this.mount(target, this);
	  this.init();
	},
 
	cropImageFile: function(e){
	  var el = $(e.currentTarget).closest('.template-download');
	  var tid = el.attr('data-tid');
	  var raw = el.find('input[name="' + this.options.inputName +'"]').val();
	  var plugin = this.app.getPlugin('CropImage');
	  plugin.showCrop(tid, JSON.parse(raw));
	},
	
	getFilesContainer: function(){
      return this.getPluginContainer().find('.files');
	},
	
	getItemContainer: function(tid){
	  return this.getFilesContainer().find('.template-download[data-tid="'+ tid +'"]');
	},
	
	getItemResult: function(tid){
      var node = this.getItemContainer(tid);
      var result = node.find('input[name="' + this.options.inputName + '"]').val();
      return result ? JSON.stringify(result): {};
	},
	
	updateFile: function(tid, result){
	  var compileTmpl = $(tmpl(this.options.downloadItemTemplate, {
		tid: tid,
		file: result,
		sizeFormat: formatFileSize(result.size),
		labelDelete: this.options.labelDelete,
		labelEdit: this.options.labelEdit,
		inputName: this.options.inputName
	  }));
	  
	  compileTmpl.find('input[name="' + this.options.inputName + '"]')
	  .val(JSON.stringify(result));
	  
	  if(result.images !==undefined && result.images.thumbnail !==undefined){
		compileTmpl.find('.preview').css("background-image", 'url(' + result.images.thumbnail.base_url +  result.images.thumbnail.path + ')');
		
		// is enable plugin crop
		var cropPlugin = this.app.getPlugin('CropImage');
		if(cropPlugin){
		  var btn = $('<button>', {class: 'wgt-btn crop', type: 'button'}).text(this.options.labelCrop);
		  compileTmpl.find('.wgt-template-actions').append(btn)
		}
	  }
	  
	  var node = this.getItemContainer(tid);
	  if(!node.length){
		this.getFilesContainer().append(compileTmpl);
	  }else {
		compileTmpl.replaceAll(node);
	  }
	},
	
	addFile: function (result) {
      var tid = (new Date).getTime();
	  this.updateFile(tid, result);
	},
	
	// todo добавить редактирование
	editFile: function(e){
	  var el = $(e.currentTarget);
	},
	
	removeFile: function(e){
		var el = $(e.currentTarget).closest('.template-download');
		var data = el.find('input[name="' + this.options.inputName + '"]').val()
		el.remove();
	},
	
	render(stage) {
	  
	  if (this.options.template === null) {
		this.options.template = '<div class="files"></div>';
	  }
	  
	  if (this.options.downloadItemTemplate === null) {
	  
		this.options.downloadItemTemplate = `
		  <div class="template-download" data-tid="{%= o.tid %}">
			<input type="hidden" name="{%= o.inputName %}" value="">
		
		    <span class="preview"></span>
		    
		 	<div class="wgt-template-info">
		 	 <p class="name">{%= o.file.name_display %}</p>
			 <p class="size">{%= o.sizeFormat %}</p>
			 
			  <div class="wgt-template-actions">
			  	  <button type="button" class="wgt-btn delete">{%= o.labelDelete %}</button>
			  	  <button type="button" class="wgt-btn edit">{%= o.labelEdit %}</button>
			  </div>
			</div>
		
		  </div>
`;
	  }
	  
	  
	  var plugin = $('<div>', {class: this.block});
	  if (this.options.isHidden) {
		plugin.hide();
	  }
	  var compileTmpl = tmpl(this.options.template, {});
	  plugin.append(compileTmpl);
	  this.el.find('.wgt-wrap-content').append(plugin);
	}
  };
  
  // ==========================================
  // LINK UPLOAD PLUGIN
  // ==========================================
  
  function LinkUploadPlugin(app, options) {
	var defaultOpts = {
	  target: null,
	  template: null,
	  isHidden: true,
	  labelInputTitle: 'Enter URL to import a file',
	  labelImport: 'Import',
	};
	
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(LinkUploadPlugin, Plugin);
	
	this.id = this.options.id || 'LinkUpload';
	this.type = TYPES.ADAPTER;
	this.block = 'wgt-link-upload-plugin';
  }
  
  LinkUploadPlugin.prototype = {
    init: function(){
	  var wrap = this.getPluginContainer();
	  wrap.off('click', '.btn-link-upload').on('click', '.btn-link-upload', $.proxy(this.uploadFile, this));
	},
 
	install: function () {
	  var template = this.options.template;
	  if (template === null) {
		this.options.template = `
			<div>{%= o.labelInputTitle %}</div>
			<div><input type="text" class="input-form url-link-upload"></div>
			<div><button type="button" lass="btn btn-link-upload">{%= o.labelImport %}</div>
			`;
	  }
	  
	  var target = this.options.target;
	  if (target !== undefined) {
		this.mount(target, this)
	  }
	  this.init();
	},
	
	render(stage) {
	  var plugin = $('<div>', {class: this.block});
	  if (this.options.isHidden) {
		plugin.hide();
	  }
	  var compileTmpl = tmpl(this.options.template, {
		labelInputTitle: this.options.labelInputTitle,
		labelImport: this.options.labelImport
	  });
	  
	  plugin.append(compileTmpl);
	  this.el.find('.wgt-wrap-content').append(plugin);
	},
 
	uploadFile: function(e){
      var self = this;
	  
	  var viewFilesPlugin = this.app.getPlugin('ViewFiles');
   
	  var endPointUrl = this.options.endPointUrl + $.param({ act: 'remote-upload'});
	  var remoteUrl = this.getPluginContainer().find('.url-link-upload').val();
      $.ajax(endPointUrl,{
        method: 'post',
		dataType: 'json',
		data: { remote: remoteUrl }
	  }).done(function(result){
	    if(!result || !result.path){
			return;
		}
	    if(result.path){
		  viewFilesPlugin.addFile(result);
		}
	  })
	  
	},
  };
  
  // ==========================================
  // DEVICE UPLOAD PLUGIN
  // ==========================================
  
  function DeviceUploadPlugin(app, options) {
	
	var defaultOpts = {
	  target: null,
	  template: null,
	  isHidden: false,
	  uploadItemTemplate: null,
	  multiple: true,
	  endPointUrl: '',
	  maxChunkSize: 0,
	  autoUpload: false,
	  dropZone: true,
	  dropZoneEffect: true,
	  singleFileUploads: false,
	  
	  labelUpload: 'Select files ...',
	  labelDropZone: 'Drop files here',
	  labelStart: 'Start',
	  labelCancel: 'Cancel',
	  labelDelete: 'Delete',
	  labelProcessingUpload: 'Processing',
	  fileName: 'file[]'
	};
	
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(DeviceUploadPlugin, Plugin);
	
	this.id = this.options.id || 'DeviceUpload';
	this.type = TYPES.ADAPTER;
	this.block = 'wgt-device-upload-plugin';
  }
  
  DeviceUploadPlugin.prototype = {
	initFileUpload: function () {
	  var self = this;
	  var $el = this.el.find('.wgt-device-upload-plugin');
	  
	  if (this.options.uploadItemTemplate === null) {
		this.options.uploadItemTemplate = `
{% for (var i=0, file; file = o.files[i]; i++) { %}
	  <div class="template-upload" data-tid="{%= file.tid %}">
		  <span class="preview"></span>
		  <p class="name">{%= file.name %}</p>
		  <p class="size">{%= o.labelProcessingUpload %}</p>
		  <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
			  <div class="progress-bar bar" style="width:0%;"></div>
		  </div>
	  
		  <div class="wgt-template-actions">
		  
		  </div>
	  </div>
{% } %}
`;
	  }
	  
	  if(this.options.multiple){
		$el.find('input[type="file"]').prop('multiple', true);
	  }
	  
	  var uploadButton = $('<button/>', {type: 'button'})
	  .addClass('wgt-btn')
	  .prop('disabled', false)
	  .text(self.options.labelStart)
	  .on('click', function () {
		var $this = $(this),
		  data = $this.data();
		$this
		.off('click')
		.text(self.options.labelCancel)
		.on('click', function () {
		  $this.remove();
		  data.abort();
		});
		data.submit().always(function () {
		  $this.remove();
		});
	  });
	  
	  
	  var fileUploader = $el.find('input[type="file"]').fileupload({
		maxChunkSize: this.options.maxChunkSize,
		dataType: 'json',
		dropZone: this.options.dropZone ? $el.find('.wgt-drop-zone') : null,
		autoUpload: this.options.autoUpload,
		url: this.options.endPointUrl,
		uploadTemplateId: null,
		downloadTemplateId: null,
		drop: function (e, data) {
		  $.each(data.files, function (index, file) {
			console.log('Dropped file: ' + file.name);
		  });
		},
		beforeSend: function(xhr, data) {
		  var file = data.files[0];
		  xhr.setRequestHeader('X-File-Id', file.tid);
		  xhr.setRequestHeader('X-File-Name', file.name);
		  xhr.setRequestHeader('X-File-Chunk-Size', self.options.maxChunkSize);
		},
	  });
	  

	  
	  fileUploader.on('fileuploadadd', function (e, data) {
		var $this = $(this);
		
		data.context = $('<div/>').appendTo($el.find('.files'));
		
		$.each(data.files, function (index, file) {
		  file.tid = (new Date).getTime();
		  var odata = {
			files: [file],
			autoUpload: self.options.autoUpload,
			labelCancel: self.options.labelCancel,
			labelProcessingUpload: self.options.labelProcessingUpload,
			labelStart: self.options.labelStart,
			labelDelete: self.options.labelDelete,
		  };

		  var node = $(tmpl(self.options.uploadItemTemplate, odata));

		  if (!index) {
			node.find('.wgt-template-actions')
			.append(
			  uploadButton.clone(true).data(data)
			);
		  }
		  node.appendTo(data.context);
		});
		
	  });
	  
	  
	  fileUploader.on('fileuploadprocessalways', function (e, data) {
		var index = data.index,
		  file = data.files[index],
		  node = $(data.context.children()[index]);
		
		var canvas = file.preview;
		if (canvas) {
		  var dataURL = canvas.toDataURL();
		  node.find('.preview').css("background-image", 'url(' + dataURL + ')');
		}
		var size = file.size;
		var name = file.name;
		node.find('.name').text(name);
		node.find('.size').text(formatFileSize(size))
	  });
	  
	  fileUploader.on('fileuploadprogressall', function (e, data) {
		var progressbar = self.app.element.find('.wgt-all-progress .bar');
		if(progressbar.length){
		  var progress = parseInt(data.loaded / data.total * 100, 10);
		  progressbar.css('width', progress + '%');
		}
	  });
	  
	  fileUploader.on('fileuploaddone', function (e, data) {
		/** @var {ViewFiles}*/
		var plugin = self.app.getPlugin('ViewFiles');
		if (!plugin) {
		  throw new Error('Plugin ViewFiles not found');
		}
	 
		if(data.result && data.result.path){
		  plugin.addFile(data.result);
		  data.context.remove();
		}
		

		//
		// $.each(data.result.files, function (index, file) {
		//   if (file.path) {
		// 	var link = $('<a>')
		// 	.attr('target', '_blank')
		// 	.prop('href', file.base_url + file.path);
		// 	$(data.context.children()[index])
		// 	.wrap(link);
		//   } else if (file.error) {
		// 	var error = $('<span class="text-danger"/>').text(file.error);
		// 	$(data.context.children()[index])
		// 	.append('<br>')
		// 	.append(error);
		//   }
		// });
	  });
	  
	},
	
	install: function () {
	  var template = this.options.template;
	  if (template === null) {
		this.options.template = `
		  {% if (o.dropZone) { %}
			<div class="wgt-drop-zone">{%=o.labelDropZone %}</div>
		  {% } %}
			<span class="btn fileinput-button">
			  <i class="glyphicon glyphicon-plus"></i>
			  <span>{%=o.labelUpload %}</span>
			  <input type="file" name="{%=o.fileName %}">
            </span>
            <div class="files"></div>
			`;
	  }
	  
	  var target = this.options.target;
	  if (target !== undefined) {
		this.mount(target, this)
	  }
	  this.initFileUpload();
	},
	
	render(stage) {
	  var plugin = $('<div>', {class: this.block});
	  if (this.options.isHidden) {
		plugin.hide();
	  }
	  var compileTmpl = tmpl(this.options.template, {
		dropZone: this.options.dropZone,
		labelDropZone: this.options.labelDropZone,
		labelUpload: this.options.labelUpload,
		fileName: this.options.fileName
	  });
	  
	  plugin.append(compileTmpl);
	  this.el.find('.wgt-wrap-content').append(plugin);
	}
  };
  
  // ==========================================
  // CORE
  // ==========================================
  
  var kakStorageAdvancedUpload = function (element, options) {
	this.element = $(element);
	this.options = options;
	this.plugins = {}
	this.install();
  };
  
  kakStorageAdvancedUpload.prototype = {
	constructor: kakStorageAdvancedUpload,
	init: function () {
	
	},
	install: function () {
	  this.element.find('');
	},
	
	use: function (PluginObj, opts) {
	  if (typeof PluginObj !== 'function') {
		var msg = 'Expected a plugin class, but got ' + (PluginObj === null ? 'null' : typeof PluginObj) + '.';
		throw new TypeError(msg);
	  }
	  
	  // Instantiate
	  var plugin = new PluginObj(this, opts);
	  
	  var pluginId = plugin.id;
	  this.plugins[plugin.type] = this.plugins[plugin.type] || [];
	  
	  if (!pluginId) {
		throw new Error('Your plugin must have an id');
	  }
	  
	  if (!plugin.type) {
		throw new Error('Your plugin must have a type');
	  }
	  
	  if (!plugin.install) {
		throw new Error('Your plugin not call method install');
	  }
	  
	  var existsPluginAlready = this.getPlugin(pluginId);
	  if (existsPluginAlready) {
		var msg = 'Already found a plugin named ' + existsPluginAlready.id + ' Tried to use: ' + pluginId + '.\n';
		throw new Error(msg)
	  }
	  this.plugins[plugin.type].push(plugin);
	  
	  plugin.install();
	  
	  return this;
	},
	
	hidePluginsByType: function(id){
	  for (var i = 0, l = this.plugins[id].length; i < l; i++) {
		var plugin = this.plugins[id][i];
		this.element.find('.' + plugin.block).hide();
	  }
	},
	
	getPlugin: function (pluginId) {
	  for (var group in this.plugins) {
		for (var i = 0, l = this.plugins[group].length; i < l; i++) {
		  var plugin = this.plugins[group][i];
		  if (plugin.id === pluginId) {
			return plugin;
		  }
		}
	  }
	  return false;
	}
  };
  
  // wrap jquery
  $.fn.kakStorageAdvancedUpload = function (option) {
	var args = Array.apply(null, arguments);
	
	args.shift();
	return this.each(function () {
	  var $this = $(this), data = $this.data('kakStorageAdvancedUpload'),
		options = typeof option === 'object' && option;
	  if (!data) {
		data = new kakStorageAdvancedUpload(this, $.extend({}, $.fn.kakStorageAdvancedUpload.defaults, options, $(this).data()));
		
		var endPountUrl = data.options.url;
		if (endPountUrl.indexOf('?') === -1) {
		  endPountUrl+= '?';
		}
		var endPointOptions = {endPointUrl: endPountUrl };
		
		data.use(LinkUploadPlugin, $.extend(endPointOptions, data.options.linkUpload || {} ));
		data.use(CropImagePlugin, $.extend(endPointOptions, data.options.cropImage || {} ));
		data.use(DeviceUploadPlugin, $.extend(endPointOptions, data.options.deviceUpload || {} ));
	 
		if (data.options.instagram) {
		  data.use(InstagramPlugin, data.options.instagram);
		}
		
		data.use(AdaptersPlugin, data.options.adapters || {});
		data.use(ViewFilesPlugin, data.options.view || {});
		
		$this.data('kakStorageAdvancedUpload', data);
	  }
	  if (typeof option === 'string') {
		data[option].apply(data, args);
	  }
	});
  };
  $.fn.kakStorageAdvancedUpload.defaults = {};
  $.fn.kakStorageAdvancedUpload.Constructor = kakStorageAdvancedUpload;
  
})(window.jQuery);
