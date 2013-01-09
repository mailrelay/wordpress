<div class="wrap">
    <?php screen_icon('options-general');  ?>
    <h2 id='mailrelay_settings'><?php _e( 'Mairelay Step 2 - Choose groups', $this->slug ); ?></h2> 


    <form name="webservices_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="chk_hidden" value="Y" />
	<input type="hidden" name="step" value="step3" />
	<input type="hidden" name="usrname" value="<?php echo $this->username; ?>">
	<input type="hidden" name="pwd" value="<?php echo $this->password; ?>" />
	<input type="hidden" name="userhost" value="<?php echo $this->userhost; ?>">

	<table class="form-table">
            <tr>
                <th scope="row">
                    <label for="group"><?php _e( 'Please Select Group', 'mailrelay' ); ?></label>
                </th>
		<td>
                    <select multiple="multiple" name="group[]" size="5" style="height:auto;">
			<?php foreach($this->groups as $key => $group ): ?>
                            <option value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
			<?php endforeach; ?>
                    </select>
                    <p>
                        <?php _e('All your Wordpress users will be synced with the groups you are choosing now.', $this->slug); ?><br />
                        <?php _e('To create new groups in Mailrelay, you must login into the control panel and click into the Mail Relay > Subscribers groups', $this->slug); ?><br />
                        <?php _e('Once there you can add a new group for your Wordpress users, or edit an existing one', $this->slug); ?>
                    </p>
		</td>
            </tr>
	</table>

        <p class="submit">
            <input type="button" onclick="return check_form();" name="Select groups" value="<?php _e('Select groups', $this->slug ) ?>" class="button-primary" />
	</p>
</form>

</div>
<script type="text/javascript">
    function check_form()
    {
        if( document.webservices_form["group[]"].value === "" ){
            
            alert("<?php echo _e('Please select at least one Group.', $this->slug ); ?>");
            return false; 
            
        }else{
            
            document.webservices_form.submit();
            document.webservices_form.action="";
            
        }
    }
</script>
