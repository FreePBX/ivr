$(document).ready(function(){
 	$('[name=frm_ivr]').submit(function(){
		//set timeout/invalid destination
		invalid = $('[name=' + $('[name=gotoinvalid]').val() + 'invalid]').val();
		timeout = $('[name=' + $('[name=gototimeout]').val() + 'timeout]').val();
		$(this).append('<input type="hidden" name="invalid_destination" value="'+invalid+'">');
		$(this).append('<input type="hidden" name="timeout_destination" value="' + timeout + '">');
		
		//set goto's for entires
		$('[name^=goto]').each(function(){
			num = $(this).attr('name').replace('goto', '') - 1;
			dest = $('[name=' + $(this).val() + num + ']').val();
			$('input[name="entires[goto][]"]').eq(num).val(dest)
		})
		
		//disable dests so that they dont get posted
		$('.destdropdown, .destdropdown2').attr("disabled", "disabled")
	})
	
	//reenable dests in case there was an error on the page and it didnt get postedj
	$('[name=frm_ivr]').submit(function(){
		setTimeout("$('.destdropdown, .destdropdown2').removeAttr('disabled')", 100);
	})
});