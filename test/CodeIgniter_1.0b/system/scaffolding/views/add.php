<?php  $this->load->view('header');  ?>

<p><?php echo anchor(array($base_uri, 'view'), '&lt; View All'); ?></p>


<?php echo form_open($action); ?>

<table border="0" cellpadding="3" cellspacing="1">
<?php foreach($fields as $field): ?>

<?php if ($field->primary_key == 1) continue; ?>

<tr>
	<td><?php echo $field->name; echo ' '.$field->def; ?></td>
	
	<?php if ($field->type == 'blob'): ?>
	<td><textarea class="textarea" name="<?php echo $field->name; ?>" cols="60" rows="10" ><?php echo $field->def; ?></textarea></td>
	<?php else : ?>
	<td><input class="input" name="<?php echo $field->name; ?>" value="<?php echo $field->def; ?>" size="60" /></td>
	<?php endif; ?>
	
</tr>
<?php endforeach; ?>
</table>

<input type="submit" class="submit" value="Insert" />

</form>

<?php $this->load->view('footer'); ?>
