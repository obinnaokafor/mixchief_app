document.getElementById('add-item').addEventListener('click', addItem);

var itms;

var loadItems = async function(){
	var s = await fetch(inventory_url, {credentials: 'same-origin'})
			.then(response => response.json())
			.then(stock => {
				itms = stock;
				return stock;
			});
	return s;
}

loadItems();

var optionTemplate = (part) => `
	<option value=${part['id']}|${part['unit']}>${part['name'].toUpperCase()}</option>
`;

var selectTemplate = (items) => `
	<select class="form-control select-list ajaxSelect" name="stock[]" required>
		<option value="">Select Inventory Item</option>
		${items.map(option => optionTemplate(option)).join("")}
	</select>
`

function addItem(){
	// console.log('hello')
	$('.cancel-button').hide();
	if (!itms) {
		loadItems().then(response => {
			$('#stockitems').append(itemRow(response))
		})
	}else {
		$('#stockitems').append(itemRow(itms))
	}
	$('.ajaxSelect').select2();
}

$('#stockitems').on('click', '.cancel-button', function(e){
	e.preventDefault();
	const $target = $(e.currentTarget)
	const parent = $target.closest('.row').remove()
	$('#stockitems .row').last().find('.cancel-button').show()
});