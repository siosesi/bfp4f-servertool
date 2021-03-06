<?php
/**
 * BattlefieldTools.com BFP4F ServerTool
 * Version 0.6.0
 *
 * Copyright (C) 2013 <Danny Li> a.k.a. SharpBunny
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>. 
 */
 
require_once('../../core/init.php');

$user->checkLogin(true);

// Check his rights
if($userInfo['rights_limiters'] == 'no') {
	header('Location: ' . HOME_URL . 'panel/accessDenied.php');
	die();
}

$pageTitle = $lang['tool_wl'];
include(CORE_DIR . '/cp_header.php');

// Itemlist
$it = new Itemlist($db, $config);
$it2 = $it->fetchItems();
$items = $it2['items'];

$status = '';

// If form is posted
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status']) && isset($_POST['ignvip']) && isset($_POST['kickmsg']) && isset($_POST['forbidden'])) {
	
	sleep(2);
	
	$errors = array();
	
	// Clean the post variables
	foreach ($_POST as $key => $value) {
		$_POST[$key] = trim($value);	
	}
	
	// Some checks
	
	// Check status
	if($_POST['status'] != 'true' && $_POST['status'] != 'false') {
		$errors[] = $lang['tool_wl_err1'];
	}
	// Check ignvip
	if($_POST['ignvip'] != 'true' && $_POST['ignvip'] != 'false') {
		$errors[] = $lang['tool_wl_err2'];
	}
	
	// Check forbidden
	if(!empty($_POST['forbidden'])) {
		$forbidden = explode(',', $_POST['forbidden']);
		foreach($forbidden as $item) {
			if(!isset($items[$item])) {
				$errors[] = replace($lang['tool_wl_err3'], array('%id%' => $item));
			}
		}
	} else {
		$forbidden = array( );
	}
	
	if(isset($_POST['inverse'])) {
		$_POST['inverse'] = 'true';
	} else {
		$_POST['inverse'] = 'false';
	}
	
	// Check errors and stuff
	if(count($errors) == 0) {
				
		if(updateSetting('tool_wl', $_POST['status']) && updateSetting('tool_wl_ignorevip', $_POST['ignvip']) && updateSetting('tool_wl_msg', $_POST['kickmsg']) && updateSetting('tool_wl_items', json_encode($forbidden)) && updateSetting('tool_wl_inverse', $_POST['inverse'])) {
			$status = '<div class="alert alert-success alert-block"><h4><i class="fa fa-check"></i> ' . $lang['word_ok'] . '</h4><p>' . $lang['msg_settings_saved'] . '</p></div>';
			$log->insertActionLog($userInfo['user_id'], 'Weapon limiter settings edited');
			
			// Reload settings
			fetchSettings();
		} else {
			$status = '<div class="alert alert-danger alert-block"><h4><i class="fa fa-times"></i> ' . $lang['word_error'] . '</h4><p>' . $result['message'] . '</p></div>';
		}
		
	} else {
		$status = '<div class="alert alert-danger alert-block"><h4><i class="fa fa-times"></i> ' . $lang['word_error'] . '</h4><p>' . $lang['msg_error'] . '</p><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
	}
	
}
?>
			
			<div class="row">
				<div class="col-md-8 col-md-offset-2">
					
					<h2><i class="fa fa-ban"></i> <?=$lang['tool_wl']?> <small><?=$lang['tool_wl_desc']?></small></h2>
					<hr />

					<form action="<?=HOME_URL?>panel/tool/wl.php" method="post" class="form-horizontal">
						
						<?=$status?>
						
						<br />
						
						<div class="form-group">
							<label class="control-label col-sm-3"><i class="fa fa-cog"></i> <?=$lang['word.tool']?></label>
							<div class="col-sm-9">
								<select name="status" class="selectpicker show-tick" data-width="100%" required>
									<option value="false" data-icon="fa fa-times"><?=$lang['word_disabled']?></option>
									<option value="true" data-icon="fa fa-check"<?=(($settings['tool_wl'] == 'true') ? ' selected' : '')?>><?=$lang['word_enabled']?></option>
								</select>

							</div>
						</div>
						
						<div class="form-group">
							<label class="control-label col-sm-3"><i class="fa fa-star"></i> <?=$lang['tool_gen_ignorevip']?></label>
							<div class="col-sm-9">
								<select name="ignvip" class="selectpicker show-tick" data-width="100%" required>
									<option value="false" data-icon="fa fa-times"><?=$lang['word_disabled']?></option>
									<option value="true" data-icon="fa fa-check"<?=(($settings['tool_wl_ignorevip'] == 'true') ? ' selected' : '')?>><?=$lang['word_enabled']?></option>
								</select>

							</div>
						</div>
						
						<hr />
						
						<div class="form-group">
							<label class="control-label col-sm-3"><i class="fa fa-comment"></i> <?=$lang['tool_gen_kick_msg']?></label>
							<div class="col-sm-9">
								<input type="text" name="kickmsg" class="form-control" value="<?=$settings['tool_wl_msg']?>" required />
								
								<span class="help-block">
									<small><?=$lang['tool_gen_help2']?></small>
								</span>
							</div>
						</div>
						
						<hr />
						
						<div class="form-group">
							<label class="control-label col-sm-3"><i class="fa fa-ban"></i> <?=$lang['tool_wl_disallowed']?></label>
							<div class="col-sm-9">
								<input id="wl" name="forbidden" />
								
								<script>
								$(function() {
									$('input#wl').tagsinput({
										itemValue: 'value',
										itemText: 'text',
										typeahead: {
									 		source: function(query) {
												return $.getJSON('<?=HOME_URL?>panel/ajax/fetchItems.php');
											}
										}
									});
									
									// Insert the selected items
<?php
$fItems = json_decode($settings['tool_wl_items'], true);
foreach($fItems as $item) {
?>
									$('input#wl').tagsinput('add',{"value":<?=$item?> ,"text":"<?=$items[$item]['item_name']?>"});
<?php
}
?>
								});
								</script>
							</div>
						</div>
						
						<div class="form-group">
							<div class="col-sm-9 col-sm-offset-3">
								<div class="checkbox">
									<label><input type="checkbox" name="inverse"<?=(($settings['tool_wl_inverse'] == 'true') ? ' checked' : '')?> /> <span><?=$lang['tool_wl_inverse']?></span></label>
								</div>
							</div>
 						</div>
						
						<br />
						
						<button class="btn btn-primary pull-right" type="submit"><i class="fa fa-save"></i> <?=$lang['btn_save']?></button> 
						<a href="<?=HOME_URL?>panel" class="btn btn-link pull-right"><i class="fa fa-arrow-left"></i> <?=$lang['btn_back']?></a>
						
					</form>
					
				</div>
			</div>
			
<?php include(CORE_DIR . '/cp_footer.php'); ?>
