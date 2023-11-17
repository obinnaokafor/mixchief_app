var Order = {
	initialize: function($table, $right){
		this.table = $table;
		this.right = $right;

		this.orderitems = {};
		this.items = {};
		this.groups = {};
		this.categories = {};
		// $('.payButton').featherlight()
		this.right.find('.mainmenu').on('click', 'button', this.handleGroupClick.bind(this));
		this.right.find('.mains').on('click', 'button', this.categoryClick.bind(this));
		this.right.find('.itemlist').on('click', 'button', this.itemClick.bind(this));
		this.table.find('#saleList').on('click', '.stepper-arrow', this.stepperIncrease.bind(this));
		this.table.find('#saleList').on('keyup', '.stepper-input', this.stepperIncrease.bind(this));
		this.table.find('.ui-view-cart').on('click', 'td > a.cross', this.removeItem.bind(this));
		this.table.find('.place-order').on('click', '.order-place', this.submitOrder.bind(this));
		this.table.find('.place-order').on('click', '.reset', resetOrder);
		$('.load-orders-button').on('click', this.loadOrders.bind(this));
		this.loadItems();
		this.id = 0;
		this.discount = 0;
		this.payment = 'Cash';
		this.status = "";
		this.comment = "";
		this.total = 0;
	},
	loadItems: function(){
		var $self = this;
		doFetch(items_url)
		.then(response => {
			let itemOrder = []
			var grouped = groupBy(response, ['group_name', 'item_name']);
			$self.groups = grouped;
			$self.right.find('.mainmenu').html($self.createElement($self.groups))
			if (Object.keys(grouped).length === 1) {
				$('.mainmenu button')[0].click();
			}
		}).catch(e => console.log(e))
	},
	/*
	*	Populate order list with items in this.orderitems object
	*/
	loadOrder: function(){
		let output = "";
		for(var key in this.orderitems){
			output += rowTemplate(this.orderitems[key])
		}
		this.table.find('#saleList tbody').html(output)
		this.calculateTotal()
		$("input.quantity").stepper();
		$('input.quantity').click( function(e) {
		    $(this).select();
		});


	},
	calculateTotal: function() {
		const total = Object.keys(this.orderitems).map(item => this.orderitems[item]).reduce((acc, cur) => {
			return acc + (cur.price*cur.quantity)
		}, 0)
		this.table.find('#total').html(total);
		if (total) this.table.find('.order-button').show()
		else this.table.find('.order-button').hide()
		this.total = total;
		return total;
	},
	itemAdded: function(item) {
		return `<li>${item.name} (${item.quantity}) - ${item.price}</li>`;
	},
	itemAddedList: function(item){
		const items = Object.assign({}, this.orderitems);
		// console.log(items)
		var list = `
			<h4 class="text-white">${item.name} added</h4>
			<h3 class="text-white">Total: ${this.total}</h3>
		`;

		$('.flash-message').html(list).fadeIn(300, function(){
			setTimeout(() => {
				$(this).fadeOut();
			},3000)
		});
	},
	addItem: function(item) {
		if (this.orderitems[item.id]) {
			this.orderitems[item.id]['quantity']++;
		}else {
			item['quantity'] = 1;
			this.orderitems[item.id] = item;
		}

		
	
		// if (this.status === 'Pending') {
		// 	fetch(`/admin/order/additem`, {
		// 		credentials: 'same-origin',
		// 		method: 'POST',
		// 		body: JSON.stringify(item)
		// 	})
		// }
		// console.log(item)
		this.loadOrder();
		this.itemAddedList(this.orderitems[item.id]);
	},
	addSavedItem: function(item){
		// console.log(item)
		this.orderitems[item.id] = item;
		this.loadOrder();
	},
	removeItem: function(e){
		e.preventDefault();
		const row = $(e.currentTarget).closest('tr');
		const itemid = row.data('row');
		const item = this.orderitems[itemid]
		if (item.status === 'Pending') {
			doFetch(`${delete_saved_order_items}/${this.id}/${itemid}`)
			.then(response => console.log(response))
			// .then(jsonResponse => console.log({jsonResponse}))
			.catch(e => console.log({e}))
		}
		

		delete this.orderitems[itemid];
		if (Object.keys(this.orderitems).length <= 0) this.id = 0;
		this.calculateTotal()

		// this.itemAddedList();

		row.fadeOut(300, () => {
			row.closest("tr").remove()
		});
	},
	/** 
		receives an onchange event to increase quantity of an item
		gets the new quantity and assigns it to item quantity in order object
		Calls setTotal to update the total of the row
	*/
	stepperIncrease: function(event){
		console.log('hello')
		let row = $(event.currentTarget).closest('tr');
		let input = row.find('input.quantity');
		let value = input.val() > 0 ? input.val() : 1;
		input.val(value)
		this.orderitems[row.data('row')]['quantity'] = value;
		this.setTotal(row)
	},
	/*
		Receives an item id
		gets the quantity and price from order object
		returns total
	*/
	itemTotal: function(id){
		return this.orderitems[id]['price']*this.orderitems[id]['quantity'];
	},
	/* 
		receives an order item row, 
		calls itemTotal and passes the item id, updates the row total
		calls calculateTotal to update the order total
	 */
	setTotal: function(row){
		row.find('.green').html(this.itemTotal(row.data('row')));
		this.calculateTotal();
	},
	incrementItem: function(item){
		let saleItems = this.table.find('.sale-row');
		const $self = this;
		$.each(saleItems, function(i, val){
			let row = $(val)
			if (row.data('row') === item['id']) {
				var initial = row.find('.stepper-input').val();
				row.find('.stepper-input').val(parseFloat(initial)+1);
				$self.setTotal(row)
			}
		})
	},
	submitOrder: function(e){
		const order = this.createTable(this.orderitems)
		$.featherlight(order, {
			afterClose: () => {
				this.eventSet = null;
			}
		})

		$('#email-receipt').on('change', function(e) {
			var $box = $(e.currentTarget);
			$('#emailForReceipt').toggle(this.checked).focus()
			if ($('#emailForReceipt').is(":hidden")) {
				$('#emailForReceipt').val("");
				$('.saveOrder').attr('disabled', false);
			}
			else $('.saveOrder').attr('disabled', true);
		})

		$('.pays').on('click', (e) => {
			e.preventDefault()
			const $target = $(e.currentTarget);
			$('.pays').removeClass('selectedOption');
			$target.addClass('selectedOption');
			var payItem = $('.payItems');
			$('.numpad').remove();
			calculateChange();

			switch($target.data('pay')){
				case 'save':
					$('.featherlight').css({
						'z-index': '9999'
					})
					swal({
						text: `Save ${this.total}?`,
						showCancelButton: true
					}).then(() => {
						if (this.saveOrder()) {
							swal('Order Saved Successfully');
							resetOrder();
						}
						else swal('An error occured, please try again');
					}, () => {
						$('.featherlight').css({
							'z-index': '999999',
							'background-color': 'rgba(0,0,0,.8)' 
						})
					})
					break;
				case 'card':
					$('.featherlight').css({
						'z-index': '9999'
					})
					swal({
						text: `Complete card payment (${this.total})?`,
						showCancelButton: true
					}).then(() => {
						this.placeOrder('Card');
					}, () => {
						console.log('cancelled')
					})
					
					break;
				case 'transfer':
					$('.featherlight').css({
						'z-index': '9999'
					})
					swal({
						text: `Complete transfer payment (${this.total})?`,
						showCancelButton: true
					}).then(() => {
						this.placeOrder('Transfer');
					}, () => {
						console.log('cancelled')
					})
					
					break;
					// If cash, open numpad and register event listeners for numpad to work
				case 'cash':

					$('.payItems').append(numpad(this.total));
					var objDiv = $('.featherlight-content')[0];
					objDiv.scrollTop = objDiv.scrollHeight;
					if (!this.eventSet) {
						$('.confirm-order').on('click', '.numbutton button', numpadClick);
						$('.confirm-order').on('click', '.clearbutton button', function(e){
							document.getElementById('amountPaid').value = 0;
							calculateChange();
						});
						$('#amountPaid').on('focus', function(e){
							const $target = $(e.currentTarget);
							if ($target.val() == 0) {
								$(this).select();
							}
						})
						$('#amountPaid').on('keyup', (e) => {
							const $target = $(e.currentTarget)
							$('#amountPaid').val(typeof(parseInt($target.val())) === 'number' ? $target.val() : 0);
							calculateChange();
						})
						$('.confirm-order').on('click', '.submit-cash', this.placeOrder.bind(this, 'Cash'));
						this.eventSet = true;
					}
					break;
				default:
					break;
			}
		})
		
	},
	pay: function(payment){
		const orderItems = this.paySave();
	},
	saveOrder: function(){
		this.status = 'Pending';
		doFetch(order_url, {method: 'POST', body: JSON.stringify(this.paySave())})
		.then(jsonResponse => console.log(jsonResponse));

		resetOrder();
		return true;
	},
	showSavedOrders: function(orders){
		let rows = orders.map(order => this.savedOrdersRow(order));
		const orderss = `<div class="load-orders">
					<h3>Select order to load</h3>
					<table class='table'>
					<thead>
						<tr>
							<th>Time</th>
							<th>Total</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						${rows.join("")}
					</tbody>
				</table>
			</div>
		`
		$.featherlight(orderss)
		$('.load-orders').on('click', '.load-order', this.loadSavedOrder.bind(this))
		$('.load-orders').on('click', '.delete-order', this.deleteSavedOrder.bind(this))
	},
	loadOrders: function(){
		doFetch(saved_orders)
		.then(orders => {
			if (orders.length) {
				this.showSavedOrders(orders)
			}else {
				swal('No saved orders');
				return;
			}
		});
	},
	savedOrdersRow: (item) => {
		return `
			<tr>
				<td>${moment(item.date).format('HH:mm:ss')}</td>
				<td>${item.amount}</td>
				<td><button class="btn btn-primary load-order" data-id="${item.id}">Select</button></td>
				<td><button class="btn btn-danger delete-order" data-id="${item.id}" style="width: 50px;">X</button></td>
			</tr>
		`;
	},
	// Populate the order list with saved order items
	loadSavedOrder: function(e){
		const id = $(e.currentTarget).data('id');

		var that = this;
		doFetch(`${saved_order}/${id}`)
		.then(items => {
			that.orderitems = {};
			// Object.assign(that.orderitems, items)
			// console.log(that)
			items.forEach(item => that.addSavedItem(item))
			that.id = items[0].orderid;
			that.status = items[0].status
		});
		this.loadOrder();
		closeModal();
	},
	// Delete saved order
	deleteSavedOrder: function(e){
		const $target = $(e.currentTarget);
		const id = $target.data('id');

		// var that = this;
		fetch(`${delete_saved_order}/${id}`, {method: 'DELETE'})
		.then(items => {
			if (items) {
				swal('Saved order deleted successfully').then(function() {
					$target.closest('tr').hide();
				})
			}
		});
	},
	paySave: function(){
		let order = {};
		order['items'] = Object.assign({}, this.orderitems);
		order['id'] = this.id;
		order['discount'] = this.discount;
		order['payment'] = this.payment;
		order['status'] = this.status;
		order['customer'] = this.customer;
		order['total'] = this.calculateTotal();
		order['total'] = this.calculateTotal();
		order['comment'] = this.comment ? this.comment : "Hello World";
		
		return order;
	},
	placeOrder: function(payment){
		this.payment = payment ? payment : 'Card';
		this.status = 'Completed';
		this.customer = $('#emailForReceipt').val() ? $('#emailForReceipt').val() : null;

		doFetch(order_url, {body: JSON.stringify(this.paySave()), method: 'POST'})
		.then(response => {
			swal(response['message']);
			resetOrder();
			this.eventSet = null;
		})
		.catch(e => console.log(e.responseText))
	},
	createTable: function(items){
		let rows = Object.keys(items).map(item => confirmTableRow(items[item]));
		return `<div class="confirm-order" id="invoice-POS">
					<div id="preloader">      
					    <div id="loading-animation">&nbsp;</div>
					</div>
					<div class="customScroll order-list" id="table">
						<table class='customScroll'>
							<thead>
								<tr class="tabletitle">
									<th class="item">Name</th>
									<th>Price</th>
									<th>Quantity</th>
									<th>Total</th>
								</tr>
							</thead>
							<tbody>
								${rows.join("")}
								<tr class="tabletitle">
									<td></td><td></td><td colspan="1">Total: </td><td>${this.total}</td>
								</tr>
								<tr class="tabletitle">
									<td></td><td></td><td colspan="1">Cash: </td><td class="cash-amout"></td>
								</tr>
								<tr class="tabletitle">
									<td></td><td></td><td colspan="1">Change: </td><td class="change-amout"></td>
								</tr>
							</tbody>
						</table>
					</div>
				<div class="checkbox">
					<label for="email-receipt" class="receipt itemtext">
						<input type="checkbox" id="email-receipt" style="margin-top: -10px;" /> Email Receipt?
					</label>
					<input type="email" class="form-control" id="emailForReceipt" placeholder="Enter Customer Email" style="height: 35px !important;"/>
				</div>
				${payOptions(this.total)}
			</div>
		`
	},
	handleGroupClick: function(e){
		const $target = $(e.currentTarget);
		$target.closest('.row').find('.main').removeClass('selected');
		$target.addClass('selected');
		// $('html, body').animate({
	 //        scrollTop: $('.items-row').offset().top
	 //    }, 300, function(){
	 //    });
		const $targetname = $target.data('name');
		const group = this.groups[$targetname];
		this.categories = group;
		this.right.find('.itemlist').html(this.createItemElement(group));
		$('p.items-buttons').show();
	}, 
	categoryClick: function(e){
		const $targetname = $(e.currentTarget).data('name');
		const category = this.categories[$targetname];
		this.right.find('.itemlist').html(this.createItemElement(category));
	},
	itemClick: function(e){
		const $item = $(e.currentTarget).data()
		// console.log($item)
		this.addItem($item);
	},
	createElement: function(element){
		var main = "<div class='row'>";
		for(var key in element){
			
			main += `<div class='col-sm-3 col-xs-4 text-center pos-button'>
				<button class='btn btn-default main mobile-button' data-name='${key}'>${key.toUpperCase()}</button>
				</div>
			`;
		}
		main += "</div>";
		return main;
	},
	createItemElement: function(element){
		var output = "<div class='row items-row'>";
		for(var key in element){
			let el = element[key][0]
			output += `<div class='col-sm-3 col-xs-4 text-center item-button pos-button'>
				<button class='btn sub mobile-button' data-price='${el.item_price}' data-category='${el.group_name}' data-id='${el.item_id}' data-name='${el.item_name}'>${el.item_name}</button>
				</div>
			`;
		}
		output += "</div>";
		return output;
	},
	closeDay: function(){
		
	}
}

function doFetch(link, props){
	var load = $('#preloader');
	load.show();
	var init = {
		credentials: 'same-origin',

	}

	return fetch(link, Object.assign(init, props))
			.then(response => {
				load.fadeOut(300, function(){
					$(this).remove();
				});
				return response.json();
			});
}

const numpad = (total, change) => `
	<div class="col-sm-6 numpad">
		<table class="table mobile-total">
			<tr>
				<td>Total: </td>
				<td>${total}</td>
			</tr>
			<tr>
				<td>Change: </td>
				<td class="change-mobile">0</td>
			</tr>
		</table>
		<input type="text" class="form-control amountPaid" id="amountPaid" value="0">
		<div class="flexing">
			<div class="numbutton">
				<button class="btn btn-primary num">7</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">8</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">9</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">4</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">5</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">6</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">1</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">2</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">3</button>
			</div>
			<div class="clearbutton">
				<button class="btn btn-primary num">Clear</button>
			</div>
			<div class="numbutton">
				<button class="btn btn-primary num">0</button>
			</div>
			<div class="submitbutton">
				<button class="btn btn-primary submit-cash">Submit</button>
			</div>
		</div>
	</div>
`;

const payOptions = () => `
	<div class="row payItems">
		<div class="col-sm-6">
			<div class="row">
				<div class="col-sm-6 col-xs-6 min-padding">
					<button class="btn btn-default saveOrder pays itemtext" data-pay="save">Save</button>
				</div>
				<div class="col-sm-6 col-xs-6 min-padding">
					<button class="btn btn-default pays itemtext" data-pay="card">Card</button>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-6 col-xs-6 min-padding">
					<button class="btn btn-default transfer pays itemtext" data-pay="transfer">Transfer</button>
				</div>
				<div class="col-sm-6 col-xs-6 min-padding">
					<button class="btn btn-default cash pays itemtext" data-pay="cash">Cash</button>
				</div>
			</div>
		</div>
		
	</div>
`;

const confirmTableRow = (item) => {
	return `
		<tr class="service">
			<td class="tableitem"><p class="itemtext">${item.name}</p></td>
			<td class="tableitem"><p class="itemtext">${item.price}</p></td>
			<td class="tableitem"><p class="itemtext">${item.quantity}</p></td>
			<td class="tableitem"><p class="itemtext">${item.quantity*item.price}</p></td>
		</tr>
	`
}

// const values = [500, 1000, 2000];

// const numpad = () => `
// 	<div class="row">
// 		<div class="col-sm-6">
// 			<label for="amountPaid">Enter Amount: </label>
// 			<input type="text" class="form-control amountPaid" id="amountPaid" value="0">
// 			${values.map(value => valueButton(value)).join("")}
// 		</div>
// 		<button class="btn btn-primary submit-cash">Submit</button>
// 	</div>
// `;

// let valueButton = (value) => `
// 	<button class="btn btn-defaults" onclick="insertValue(${value});">${value}</button>
// `;

function numpadClick(e){
	var $button = $(e.currentTarget);
	var paid = document.getElementById('amountPaid');
	var entered = typeof(parseInt($button.text())) === 'number' ? $button.text() : 0;
	var total = parseFloat(paid.value) ? paid.value : "";
	paid.value = total + entered;

	calculateChange();
}

function calculateChange(){
	let paids = document.getElementById('amountPaid') ? parseFloat(document.getElementById('amountPaid').value) : 0;
	if (paids) {
		$('.cash-amout').text(paids);
		var change = paids > Order.total ? paids - Order.total : 0;
		$('.change-amout').text(change);

		$('.change-mobile').html(change)
	}else {
		$('.change-amout').text(0);
		$('.cash-amout').text(0);
	}
}

// function insertValue(value, inp = false){
// 	let current = document.getElementById('amountPaid').value ? parseInt(document.getElementById('amountPaid').value) : 0;
// 	let paid = inp ? parseInt(value) : current + parseInt(value);
	
// }

function closeModal(){
	var current = $.featherlight.current();
	if (current) {
		current.close();
	}
}

function resetOrder(){
	Order.orderitems = {};
	Order.id = 0;
	Order.status = "";
	Order.loadOrder()
	closeModal();
}

Order.initialize($('.cover'), $('.buttons'));

const rowTemplate = (item) => `
	<tr data-row="${item['id']}" class="sale-row">
		<td colspan="2">
			<h6><a href="#">${item['name']}</a></h6>
		</td>
		<td class="lblue col-sm-hidden">${item['price']}</td>
		<td class="quantity"><input type="number" min="1" max="40" value="${item['quantity'] ? item['quantity'] : 1}" class="quantity"/></td>
		<td class="green">${item['price']*item['quantity']} </td>
		<td class="text-center">
			<a href="#" class="cross" data-toggle="tooltip" data-placement="top" title="Remove"><i class="fa fa-times"></i></a>
		</td>
	</tr>
`;


function printReport(data, cancelled, payments){
	// select all elements in order pane and create a list of items
	// to open in modal and confirm order
	console.log(payments);
	let output = "<div id='reports'>";
	output += "<p class='table-heading'>Good Chops Restaurant</p>";
	output += "<p class='table-heading'>House 23, 52 road, Festac Town, Lagos</p>";
	output += "<table class='table' id='dailyReport'>";
	let total = 0;
	for(var groups in data){
		output += "<tr><th class='report-group'>" + groups + "</th></tr>";
		let groupTotal = 0;
		for(var cats in data[groups]){
			output += "<tr><th>" + cats + "</th>";
			output += "<th>Quantity</th>";
			output += "<th>Price</th></tr>";
			let catTotal = 0;
			for(let i=0; i < data[groups][cats].length; i++){
				let price = (data[groups][cats][i].quantity*data[groups][cats][i].item_price) - data[groups][cats][i].discount;
				output += "<tr><td>" + data[groups][cats][i].item_name + "</td>";
				output += "<td>" + data[groups][cats][i].quantity + "</td>";
				output += "<td>" + data[groups][cats][i].item_price + "</td></tr>";
				catTotal += +data[groups][cats][i].item_price;
			}
			output += "<tr class='totals'><td></td><td>" + cats + " total:</td><td>" + catTotal + "</td></tr>";
			groupTotal += +catTotal;
			catTotal = 0;
		}
		output += "<tr class='totals'><td></td><td>" + groups + " total:</td><td>" + groupTotal + "</td></tr>";
		total += +groupTotal;
		groupTotal = 0;
	}
	output += "<tr><td></td><td></td><td></td></tr>";
	output += "<tr><th colspan='2'>CANCELLED</th><th>Quantity</th></tr>";
	for (var i = 0; i < cancelled.length; i++) {
		output += "<tr><td colspan='2'>" + cancelled[i].item_name + "</td>";
		output += "<td>" + cancelled[i].quantity + "</td></tr>";
	}
	output += "<tr><td></td><td></td><td></td></tr>";
	for (var i = 0; i < payments.length; i++) {
		output += "<tr class='totals'><td></td><td>" + payments[i]['payment'] + "</td><td>" + payments[i]['amount'] + "</td></tr>";
	}
	output += "<tr><td></td><td></td><td></td></tr>";
	output += "<tr class='totals'><td></td><td>Total:</td><td>" + total + "</td></tr>";
	output += "</table>";
	
	output += "</table></div>";

	$.featherlight($output);

}

function shiftClose(){
	var closeshift = confirm("Are you sure you want to close the register: ");
	if (closeshift) {
		$.get(reportUrl, function(data){
			let json = JSON.parse(data);
			let report = category(JSON.parse(json.report));
			let cancelled = category(JSON.parse(json.cancelled));
			let payments = JSON.parse(json.payments);
			console.log(report);
			console.log(cancelled['CANCELLED']['VOID']);
			console.log(payments);
			printReport(report, cancelled['CANCELLED']['VOID'], payments);
		});
	}
}