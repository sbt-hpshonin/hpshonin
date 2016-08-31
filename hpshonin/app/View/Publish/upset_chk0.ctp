 <form name='form' action='<?php echo $this->Html->Url("upset_chk1/$project_id/$package_id"); ?>' method=post><input type=submit>
 <input type='hidden' name='target_date'>
 <input type='hidden' name='target_flg'>
 <input type='hidden' name='mgid'>
 </form>
 <?php echo $this->Html->script('/lib/jquery.js'); ?>
 <script>(form.target_date.value) = parent.form.span12.value;</script>
 <script>(form.target_flg.value) = parent.$('input[name=\"a\"]:checked').val();</script>
 <script>(form.mgid.value) = parent.form.mgid.value;</script>
 <script>document.form.submit();</script>
 