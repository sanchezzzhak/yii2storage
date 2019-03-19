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
	  var btnMore = $('<button>').text('+').off('click').on('click', $.proxy(this.onShowView, this));
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
	  endPointUrl: 'upload?act=crop',
	  
	  // crop settings
	  autoCropArea: 0.6,
	  zoomable: true
	};
 
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(CropImagePlugin, Plugin);
 
	this.id = this.options.id || 'CropImage';
	this.type = TYPES.FILE;
	this.block = 'wgt-crop-image-plugin';
  }
  
  CropImagePlugin.prototype = {
  
	init: function () {
	  var wrap = this.getPluginContainer();
	
	  wrap.off('click', '.crop-cancel')
	  .on('click', '.crop-cancel', $.proxy(this.cropCancel, this));
	  
	  wrap.off('click', '.crop-me')
	  .on('click', '.crop-me', $.proxy(this.cropSave, this));
	  
	},
	
	install: function () {
	  var target = this.options.target;
	  this.mount(target, this);
	  this.init();
	},
	
	cropSave: function(e){
	
	},
	
  	cropCancel: function(e){
      this.hidePlugin();
	},
	
	showCrop: function(id, result){
	  this.app.hidePluginsByType(TYPES.FILE);
	  this.app.hidePluginsByType(TYPES.VIEW_FILES);
	  this.app.hidePluginsByType(TYPES.ADAPTER);
	  
	  var container = this.getPluginContainer();
	  if (!container) {
		return;
	  }
	  this.showPlugin();
	  var image = $('<img>', {src: result.url });
	  
	  var wrap = container
	  .find('.cropper-container')
	  .css('top', 0)
	  .css('left', 0)
	  .empty();
	  
	  wrap.append(image);
	  
	  wrap.find('img').off().cropper({
		autoCropArea: this.options.autoCropArea,
		zoomable: this.options.zoomable,
	  });
	  
	  console.log('show crop', id, result);
	  
	  /*
	  	/*
		  $(this).closest(self.selectors.item_download).find('.preview-box > img').off().cropper({
			  autoCropArea: 0.6,
			  zoomable: false
		  }).on('resize.cropper, built.cropper', function(){
		  $(this).closest(self.selectors.item_download).find('.cropper-container').css('top',0).css('left',0);
	
	 	*/
	  
	},
	
	render(stage) {
	  if (this.options.template === null) {
		this.options.template = `
			<div class="cropper-container">
		
			</div>
			<div class="cropper-footer">
				<button class="wgt-btn crop-me">crop save</button>
				<button class="wgt-btn crop-cancel">cancel</button>
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
  // EDIT FILE PLUGIN
  // ==========================================
  
  function EditFilePlugin(app, options){}
  
  
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
  
	  var selector = this.el.find('.' + this.block).find('.files');
	  
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
	  
	  console.log('raw', raw);
	  console.log('el', el);
	  
	  
	  
	  var plugin = this.app.getPlugin('CropImage');
	  plugin.showCrop(tid, JSON.parse(raw));
	},
	
	addFile: function (result) {
      
      var tid = (new Date).getTime();
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
		compileTmpl.find('.preview').css("background-image", 'url(' + result.images.thumbnail.url + ')');
		
		// is enable plugin crop
		var cropPlugin = this.app.getPlugin('CropImage');
		if(cropPlugin){
		  var btn = $('<button>', {class: 'wgt-btn crop'}).text(this.options.labelCrop);
		  compileTmpl.find('.wgt-template-actions').append(btn)
		}
		
	  }
	  
	  this.el.find('.' + this.block).find('.files').append(compileTmpl);
	},
	
	editFile: function(e){
	  var el = $(e.currentTarget);
	},
	
	removeFile: function(e){
		var el = $(e.currentTarget).closest('.template-download').remove();
	},
	
	render(stage) {
	  
	  if (this.options.template === null) {
		this.options.template = '<div class="files"></div>';
	  }
	  
	  /*
	  	 {% if( o.result.errors){ %}
				  <div class="error-upload cancel "> <p> File :{%= o.file.name_display %} <?=$context->labelUploadError?> </p>
				  {% for (var key in o.result.errors) { %}
				  {%=o.result.errors[key][0]%}</div>
				  {% } %}
				  </div>
			  {% }
			  
		  {% if(o.file.images && o.file.images.thumbnail) { %}
			  
				  <span>
					  <a class="preview" href="javascript:;"><img src="{%=o.file.images.thumbnail.url%}"></a>
					  <div class="preview-box hide">
						  <div class="act">
							  <?php if($context->crop): ?>
								  <a href="javascript:;" class="crop btn inline"> <?=$context->labelCrop;?></a>
							  <?php endif;?>
								  <a href="javascript:;" class="crop-cancel btn inline"> <?=$context->labelCancel?></a>
						  </div>
						  <img src="{%=o.result.images.preview.url%}">
					  </div>
				  </span>
		  
			  {% } %}
			  
	  * */
	  
	  if (this.options.downloadItemTemplate === null) {
	  
		this.options.downloadItemTemplate = `
		  <div class="template-download" data-tid="{%= o.tid %}">
			<input type="hidden" name="{%= o.inputName %}" value="">
		
		    <span class="preview"></span>
		    
		 	<div class="wgt-template-info">
		 	 <p class="name">{%= o.file.name_display %}</p>
			 <p class="size">{%= o.sizeFormat %}</p>
			 
			  <div class="wgt-template-actions">
			  	  <button class="wgt-btn delete">{%= o.labelDelete %}</button>
			  	  <button class="wgt-btn edit">{%= o.labelEdit %}</button>
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
	  inputName: 'link'
	};
	
	options = $.extend(defaultOpts, options);
	Plugin.call(this, app, options);
	inherits(LinkUploadPlugin, Plugin);
	
	this.id = this.options.id || 'LinkUpload';
	this.type = TYPES.ADAPTER;
	this.block = 'wgt-link-upload-plugin';
  }
  
  LinkUploadPlugin.prototype = {
	install: function () {
	  var template = this.options.template;
	  if (template === null) {
		this.options.template = `
			<div>Enter URL to import a file</div>
			<div><input type="text" name="{%= o.inputName %}" class="input-form"></div>
			<div><button class="btn">Import</div>
			`;
	  }
	  
	  var target = this.options.target;
	  if (target !== undefined) {
		this.mount(target, this)
	  }
	},
	
	render(stage) {
	  var plugin = $('<div>', {class: this.block});
	  if (this.options.isHidden) {
		plugin.hide();
	  }
	  var compileTmpl = tmpl(this.options.template, {
		inputName: this.options.fileName
	  });
	  
	  plugin.append(compileTmpl);
	  this.el.find('.wgt-wrap-content').append(plugin);
	}
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
	  
	  var uploadButton = $('<button/>')
	  .addClass('wgt-btn')
	  .prop('disabled', false)
	  .text('Start')
	  .on('click', function () {
		var $this = $(this),
		  data = $this.data();
		$this
		.off('click')
		.text('Abort')
		.on('click', function () {
		  $this.remove();
		  data.abort();
		});
		data.submit().always(function () {
		  $this.remove();
		});
	  });
	  
	  var fileUploader = $el.find('input[type="file"]').fileupload({
		dataType: 'json',
		dropZone: this.options.dropZone ? $el.find('.wgt-drop-zone') : null,
		autoUpload: this.options.autoUpload,
		url: this.options.endPointUrl,
		uploadTemplateId: null,
		downloadTemplateId: null
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
		var progress = parseInt(data.loaded / data.total * 100, 10);
		$el.find('.progress .bar').css('width', progress + '%');
	  });
	  
	  fileUploader.on('fileuploaddone', function (e, data) {
		/** @var {ViewFiles}*/
		var plugin = self.app.getPlugin('ViewFiles');
		if (!plugin) {
		  throw new Error('Plugin ViewFiles not found');
		}
	 
		plugin.addFile(data.result);
		data.context.remove();
	
		$.each(data.result.files, function (index, file) {
		  if (file.url) {
			var link = $('<a>')
			.attr('target', '_blank')
			.prop('href', file.url);
			$(data.context.children()[index])
			.wrap(link);
		  } else if (file.error) {
			var error = $('<span class="text-danger"/>').text(file.error);
			$(data.context.children()[index])
			.append('<br>')
			.append(error);
		  }
		});
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
		
		data.use(LinkUploadPlugin, {
		  endPointUrl: '/group/default/upload'
		});
		data.use(CropImagePlugin, {
		  endPointUrl: '/group/default/upload?act=crop'
		});
		data.use(DeviceUploadPlugin, {
		  endPointUrl: '/group/default/upload'
		});
		data.use(AdaptersPlugin, {});
		data.use(ViewFilesPlugin, {});
		
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
