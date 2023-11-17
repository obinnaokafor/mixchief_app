$('.delete-item').on('click', function(e){
	e.preventDefault();
	const $target = $(e.currentTarget);
	const id = $target.data('id');
	const link = $target.attr('href');

	fetch(link, {credentials:'same-origin'})
	.then(response => response.json())
	.then(json => {
		swal(json['message'])
		.then(res => {
			$target.closest('tr').fadeOut()
		})
	})
})

// When a new inventory item is selected in the add/edit supply section
// append an input 
$('#stockitems').on('change', '.stock-item', newStockItem)

function newStockItem(e){
	console.log('here')
	var $target = $(e.currentTarget);
	var surround = $target.closest('.row').find('.stockitem');
	if ($target.val() === "-1") {
		surround.show().find('input').focus().attr('required', true);
		surround.find('input').focus();
	}else {
		surround.find('input').attr('required', false);
		surround.hide()
	}
}

function confirmDelete(warning = 'Are you sure you want to delete this?'){
	var response = confirm(warning);
	if (!response) {
		e.preventDefault();
	}
}