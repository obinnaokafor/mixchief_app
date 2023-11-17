$('table').on('click', '.delete-row', function(e){
	e.preventDefault();

	const $target = $(e.currentTarget)
	const $row = $target.closest('tr');
	const link = $target.attr('href');
	const name = $target.data('name');

	console.log({name, link})

	fetch(link, {
		credentials: 'same-origin',
		method: 'DELETE'
	})
	.then(response => response.json())
	.then(item => {
		swal(`${name} deleted successfully`)
		.then(() => {
			$row.fadeOut(300, function(){
				$row.remove();
			})
		})
	})
})

$('.json-form').on('submit', function(e){
	e.preventDefault();
	const $target = $(e.currentTarget)
	$target.find('.save-button').attr('disabled', true).html('Saving...');

	var details = $target.serializeArray()
	
	var item = {};
	for(let i=0; i<details.length; i++){
	    item[details[i].name] = details[i].value;
	}

	var link = $target.attr('action');

	console.log(item)


	fetch(link, {
		credentials: 'same-origin',
		method: 'POST',
		body: JSON.stringify(item)
	}).then(response => response.json())
	.then(resource => {
		console.log(resource)
		if (resource.message) {
			swal(resource.message)
			return;
		}
		var tableBody = $target.closest('.content').find('table tbody');
		tableBody.find('.odd').hide();
		tableBody.append(itemRow(resource))
		$target.find('.save-button').attr('disabled', false).html('Save');
		swal(`${resource.name} added successfully`).then(res => {
			$target[0].reset()
			// $('#rowSelection').DataTable();
			
			
		})
	})
});