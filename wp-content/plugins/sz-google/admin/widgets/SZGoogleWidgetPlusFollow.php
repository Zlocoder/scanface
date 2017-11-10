<?php

/**
 * HTML code of this widget in the administration section
 * This code is on a separate file to exclude it from the frontend
 *
 * @package SZGoogle
 * @subpackage SZGoogleAdmin
 * @author Massimo Della Rovere
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

if (!defined('SZ_PLUGIN_GOOGLE') or !SZ_PLUGIN_GOOGLE) die();

?>
<!-- WIDGETS (Table for the FORM widget) -->
<table id="SZGoogleWidgetPlusFollow" class="sz-google-table-widget">

<!-- WIDGETS (Field with inclusion of the title widget) -->
<tr class="only-widgets">
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_title ?>"><?php echo ucfirst(__('title','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals"><input class="widefat" id="<?php echo $ID_title ?>" name="<?php echo $NAME_title ?>" type="text" value="<?php echo $VALUE_title ?>" placeholder="<?php echo __('insert title for widget','szgoogleadmin') ?>"/></td>
</tr>

<!-- WIDGETS (Field for entering URL type) -->
<tr>
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_urltype ?>"><?php echo ucfirst(__('type','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals">
		<select class="sz-google-switch-hidden widefat" id="<?php echo $ID_urltype ?>" name="<?php echo $NAME_urltype ?>" onchange="szgoogle_switch_hidden_onchange(this);" data-open="1" data-switch="sz-google-switch-url">
			<option value="1" <?php echo selected("1",$VALUE_urltype) ?>><?php echo __('URL for page or profile','szgoogleadmin') ?></option>
			<option value="2" <?php echo selected("2",$VALUE_urltype) ?>><?php echo __('configuration page','szgoogleadmin') ?></option>
			<option value="3" <?php echo selected("3",$VALUE_urltype) ?>><?php echo __('configuration profile','szgoogleadmin') ?></option>
		</select>
	</td>
</tr>

<!-- WIDGETS (Field for entering specific URL) -->
<tr class="sz-google-switch-url sz-google-hidden">
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_url ?>"><?php echo ucfirst(__('URL','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals"><input class="sz-upload-image-url widefat" id="<?php echo $ID_url ?>" name="<?php echo $NAME_url ?>" type="text" value="<?php echo $VALUE_url ?>" placeholder="<?php echo __('insert destination URL','szgoogleadmin') ?>"/></td>
</tr>

<!-- WIDGETS (Field for insertion type of badge) -->
<tr>
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_badge ?>"><?php echo ucfirst(__('type','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals">
		<select class="sz-google-switch-hidden widefat" id="<?php echo $ID_badge ?>" name="<?php echo $NAME_badge ?>" onchange="szgoogle_switch_hidden_onchange(this);" data-switch="sz-google-switch-display" data-close="0">
			<option value="0" <?php echo selected("0",$VALUE_badge) ?>><?php echo __('button without badge','szgoogleadmin') ?></option>
			<option value="1" <?php echo selected("1",$VALUE_badge) ?>><?php echo __('button with badge','szgoogleadmin') ?></option>
		</select>
	</td>
</tr>

<!-- WIDGETS (Field to insert text to use as a badge) -->
<tr class="sz-google-switch-display sz-google-hidden">
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_text ?>"><?php echo ucfirst(__('text','szgoogleadmin')) ?>:</label></td>
	<td colspan="2"><textarea class="widefat" rows="3" cols="20" id="<?php echo $ID_text ?>" name="<?php echo $NAME_text ?>" placeholder="<?php echo __('insert text for badge','szgoogleadmin') ?>"><?php echo $VALUE_text ?></textarea></td>
</tr>

<!-- WIDGETS (Field to insert image to use as a badge) -->
<tr class="sz-google-switch-display sz-google-hidden">
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_img ?>"><?php echo ucfirst(__('image','szgoogleadmin')) ?>:</label></td>
	<td colspan="1" class="sz-cell-vals"><input class="sz-upload-image-url-2 widefat" id="<?php echo $ID_img ?>" name="<?php echo $NAME_img ?>" type="text" value="<?php echo $VALUE_img ?>" placeholder="<?php echo __('choose image for badge','szgoogleadmin') ?>"/></td>
	<td colspan="1" class="sz-cell-vals"><input class="sz-upload-image-button button" type="button" value="<?php echo ucfirst(__('file','szgoogleadmin')) ?>" data-field-url="sz-upload-image-url-2" data-title="<?php echo ucfirst(__('select or upload a file','szgoogleadmin')) ?>" data-button-text="<?php echo ucfirst(__('confirm selection','szgoogleadmin')) ?>"/></td>
</tr>

<!-- WIDGETS (Field for entering the position) -->
<tr class="sz-google-switch-display sz-google-hidden">
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_position ?>"><?php echo ucfirst(__('position','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals">
		<select class="widefat" id="<?php echo $ID_position ?>" name="<?php echo $NAME_position ?>">
			<option value="outside" <?php echo selected("outside",$VALUE_position) ?>><?php echo __('position outside','szgoogleadmin') ?></option>
			<option value="top"     <?php echo selected("top"    ,$VALUE_position) ?>><?php echo __('position top'    ,'szgoogleadmin') ?></option>
			<option value="center"  <?php echo selected("center" ,$VALUE_position) ?>><?php echo __('position center' ,'szgoogleadmin') ?></option>
			<option value="bottom"  <?php echo selected("bottom" ,$VALUE_position) ?>><?php echo __('position bottom' ,'szgoogleadmin') ?></option>
		</select>
	</td>
</tr>

<!-- WIDGETS (Field for entering alignment) -->
<tr>
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_align ?>"><?php echo ucfirst(__('alignment','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals">
		<select class="widefat" id="<?php echo $ID_align ?>" name="<?php echo $NAME_align ?>">
			<option value="none"   <?php echo selected("none"  ,$VALUE_align) ?>><?php echo __('alignment none'  ,'szgoogleadmin') ?></option>
			<option value="left"   <?php echo selected("left"  ,$VALUE_align) ?>><?php echo __('alignment left'  ,'szgoogleadmin') ?></option>
			<option value="center" <?php echo selected("center",$VALUE_align) ?>><?php echo __('alignment center','szgoogleadmin') ?></option>
			<option value="right"  <?php echo selected("right" ,$VALUE_align) ?>><?php echo __('alignment right' ,'szgoogleadmin') ?></option>
		</select>
	</td>
</tr>

<!-- WIDGETS (Field button to insert size) -->
<tr>
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_size ?>"><?php echo ucfirst(__('size','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals">
		<select class="widefat" id="<?php echo $ID_size ?>" name="<?php echo $NAME_size ?>">
			<option value="medium" <?php echo selected("medium",$VALUE_size) ?>><?php echo __('size medium','szgoogleadmin') ?></option>
			<option value="small"  <?php echo selected("small" ,$VALUE_size) ?>><?php echo __('size small' ,'szgoogleadmin') ?></option>
			<option value="large"  <?php echo selected("large" ,$VALUE_size) ?>><?php echo __('size large' ,'szgoogleadmin') ?></option>
		</select>
	</td>
</tr>

<!-- WIDGETS (Field button to insert annotations) -->
<tr>
	<td colspan="1" class="sz-cell-keys"><label for="<?php echo $ID_annotation ?>"><?php echo ucfirst(__('annotation','szgoogleadmin')) ?>:</label></td>
	<td colspan="2" class="sz-cell-vals">
		<select class="widefat" id="<?php echo $ID_annotation ?>" name="<?php echo $NAME_annotation ?>">
			<option value="none"   <?php echo selected("none"  ,$VALUE_annotation) ?>><?php echo __('annotation none'  ,'szgoogleadmin') ?></option>
			<option value="inline" <?php echo selected("inline",$VALUE_annotation) ?>><?php echo __('annotation inline','szgoogleadmin') ?></option>
			<option value="bubble" <?php echo selected("bubble",$VALUE_annotation) ?>><?php echo __('annotation bubble','szgoogleadmin') ?></option>
		</select>
	</td>
</tr>

<!-- WIDGETS (Closing the main table form widget) -->
</table>

<!-- WIDGETS (Javascript code for UI functions) -->
<script type="text/javascript">
	jQuery(document).ready(function() {
		if (typeof(szgoogle_switch_hidden_onload) == 'function') { szgoogle_switch_hidden_onload('SZGoogleWidgetPlusFollow'); }
		if (typeof(szgoogle_upload_select_media)  == 'function') { szgoogle_upload_select_media(); }
	});
</script>