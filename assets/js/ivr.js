$(document).ready(function(){
	//add "add row" button
	var new_entrie = '<tr>' + $('#ivr_entries > tbody:last').find('tr:last').html() + '</tr>';
	$('#add_entrie').click(function(){
		id = new Date().getTime();
		$('#ivr_entries > tbody:last').find('tr:last').after(new_entrie.replace(/DESTID/g, id));
		bind_dests_double_selects();
	});
	
 	$('[name=frm_ivr]').submit(function(){
		//set timeout/invalid destination
		invalid = $('[name=' + $('[name=gotoinvalid]').val() + 'invalid]').val();
		timeout = $('[name=' + $('[name=gototimeout]').val() + 'timeout]').val();
		$(this).append('<input type="hidden" name="invalid_destination" value="'+invalid+'">');
		$(this).append('<input type="hidden" name="timeout_destination" value="' + timeout + '">');
		
		//set goto fileds for destinations
		$('[name^=goto]').each(function(){
			num = $(this).attr('name').replace('goto', '');
			dest = $('[name=' + $(this).val() + num + ']').val();
			$(this).parent().find('input[name="entries[goto][]"]').val(dest)
			//console.log(num, dest, $(this).parent().find('input[name="entries[goto][]"]').val())
		})
		
		//set ret_ivr checkboxes to SOMETHING so that they get sent back
		$('[name="entries[ivr_ret][]"]').not(':checked').each(function(){
			$(this).attr('checked','checked').val('uncheked')
		})
		
		//disable dests so that they dont get posted
		$('.destdropdown, .destdropdown2').attr("disabled", "disabled");
	})
	
	//reenable dests in case there was an error on the page and it didnt get postedj
	$('[name=frm_ivr]').submit(function(){
		setTimeout(restore_form_elemens, 100);
	})
	
	//delete rows on click
	$('.delete_entrie').live('click', function(){
		$(this).closest('tr').fadeOut('normal', function(){$(this).closest('tr').remove();})
	})
	
});

function restore_form_elemens() {
	$('.destdropdown, .destdropdown2').removeAttr('disabled')
	$('[name="entries[ivr_ret][]"][value=uncheked]').each(function(){
		$(this).removeAttr('checked')
	})
}