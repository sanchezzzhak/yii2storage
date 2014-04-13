<?php
namespace yii2\storage\views;
use yii\helpers\Html;
?>


<div class="yii2upload">
	<div class="row">
		<div class="col-md-3">
			<span class="btn btn-success fileinput-button btn-block">
				<i class="glyphicon glyphicon-plus"></i>
				<span><?=$label_btn?></span>
				<?=Html::activeFileInput($model, $name, $options) . "\n"; ?>
			</span>
		</div>
		<div class="col-md-9">
			<div class="drop-zone">
				<span>DROP FILE</span>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12 filelist">
			<div class="image">
				<div class="actions">
					<a href="javascript:;" title="remove"><i class="glyphicon glyphicon-remove"></i></a>
				</div>
			</div>
			<div class="image">
				<div class="actions">
					<a href="javascript:;" title="remove"><i class="glyphicon glyphicon-remove"></i></a>
				</div>
			</div>
			<div class="image">
				<div class="actions">
					<a href="javascript:;" title="remove"><i class="glyphicon glyphicon-remove"></i></a>
				</div>
			</div>

		</div>
	</div>

</div>
