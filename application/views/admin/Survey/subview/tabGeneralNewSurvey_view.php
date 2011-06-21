<span class='annotation'> <?php echo $clang->gT("*This setting cannot be changed later!"); ?></span></li>
<li><label for='surveyls_title'><span class='annotationasterisk'>*</span><?php echo $clang->gT("Title"); ?> :</label>
<input type='text' size='82' maxlength='200' id='surveyls_title' name='surveyls_title' /> <span class='annotation'><?php echo $clang->gT("*Required"); ?> </span>
</li>
<li><label for='description'><?php echo $clang->gT("Description:"); ?> </label>
<textarea cols='80' rows='10' id='description' name='description'></textarea>
<!-- getEditor("survey-desc", "description", "[ $clang->gT("Description:", "js") ]", '', '', '', $action) -->
</li>
<li><label for='welcome'><?php echo $clang->gT("Welcome message:"); ?> </label>
<textarea cols='80' rows='10' id='welcome' name='welcome'></textarea>
<!--getEditor("survey-welc", "welcome", "[ $clang->gT("Welcome message:", "js") ]", '', '', '', $action) -->
</li>
<li><label for='endtext'><?php echo $clang->gT("End message:") ;?> </label>
<textarea cols='80' id='endtext' rows='10' name='endtext'></textarea>
<!-- getEditor("survey-endtext", "endtext", "[ $clang->gT("End message:", "js") ]", '', '', '', $action) -->
</li>

<li><label for='url'><?php echo $clang->gT("End URL:"); ?></label>
<input type='text' size='50' id='url' name='url' value='http://' /></li>
<li><label for='urldescrip'><?php echo $clang->gT("URL description:") ; ?></label>
<input type='text' maxlength='255' size='50' id='urldescrip' name='urldescrip' value='' /></li>
<li><label for='dateformat'><?php echo $clang->gT("Date format:") ; ?></label>
<select size='1' id='dateformat' name='dateformat'>

<?php foreach (getDateFormatData () as $index => $dateformatdata) { ?>
<option value='<?php echo $index; ?>'> <?php echo $dateformatdata['dateformat'] ; ?>
</option>
<?php } ?>
</select>
</li>

<li><label for='admin'><?php echo $clang->gT("Administrator:") ; ?></label>
<input type='text' size='50' id='admin' name='admin' value='<?php echo $owner['full_name'] ; ?>' /></li>
<li><label for='adminemail'><?php echo $clang->gT("Admin Email:") ; ?></label>
<input type='text' size='50' id='adminemail' name='adminemail' value='<?php echo $owner['email'] ; ?>' /></li>
<li><label for='bounce_email'><?php echo $clang->gT("Bounce Email:") ; ?></label>
<input type='text' size='50' id='bounce_email' name='bounce_email' value='<?php echo $owner['bounce_email'] ; ?>' /></li>
<li><label for='faxto'><?php echo $clang->gT("Fax to:") ; ?></label>
<input type='text' size='50' id='faxto' name='faxto' /></li>
</ul>
</div>