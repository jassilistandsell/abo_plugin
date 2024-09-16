$(document).ready(function() {
    var shopIoUrl = window.location.origin + '/io.php';

    // Check if the sub_abbo_weeks div exists
    if ($('.sub_abbo_weeks').length && $('.product_page_body #add-to-cart button').length) {
        // Add a class (e.g., 'btn-primary') to the #add-to-cart button
        $('.product_page_body #add-to-cart button').each(function() {
            if ($(this).attr('name') === 'inWarenkorb') {
                $(this).addClass('abo-1');
            }
        });
    }else{
        $('.product_page_body #add-to-cart button').each(function() {
            if ($(this).attr('name') === 'inWarenkorb') {
                $(this).addClass('abo-0');
            }
        });
    }




    function updateDropdowns() {
        // Get selected values
        var selectedValues = [];
        $('.frequency-input').each(function() {
            var selectedValue = $(this).val();
            if (selectedValue) {
                selectedValues.push(selectedValue);
            }
        });

        // Update options in all dropdowns
        $('.frequency-input').each(function() {
            var currentDropdown = $(this);
            var currentValue = currentDropdown.val();

            currentDropdown.find('option').each(function() {
                var option = $(this);
                // If the option value is selected in another dropdown and not in the current dropdown, hide it
                if (selectedValues.includes(option.val()) && option.val() !== currentValue) {
                    option.hide();
                } else {
                    option.show();
                }
            });
        });
    }

    // Trigger update when any dropdown value changes
    $(document).on('change', '.frequency-input', function() {
        updateDropdowns();
    });

    // Initialize the dropdowns on page load
    updateDropdowns();

    $('#frequencyForm').submit(function(event) {
        event.preventDefault();

        // Serialize form data into a JSON object
        var formDataArray = $(this).serializeArray();
        var formDataObject = {};

        formDataArray.forEach(function(item) {
            if (item.name.endsWith('[]')) {
                // Remove '[]' and treat as an array
                var key = item.name.slice(0, -2);
                if (!formDataObject[key]) {
                    formDataObject[key] = [];
                }
                formDataObject[key].push(item.value);
            } else {
                formDataObject[item.name] = item.value;
            }
        });

        $.ajax({
            type: 'POST',
            url: shopIoUrl,
            data: {
                'io': JSON.stringify({
                    'name': 'lssaveabbo',
                    'params': [{'formdata': formDataObject}]
                }),
            }
        }).done(function(data) {
            if (data.success) {
                alert('Frequency values saved successfully!');
                location.reload();
            } else {
                alert('Failed to save frequency values: ' + (data.message || 'Unknown error'));
            }
        });
    });


    //complete abo process on credit card
    var urlParams = new URLSearchParams(window.location.search);
    var aboValue = urlParams.get('aboorder');


    // Check if the 'abo' parameter exists and has the value '10124'
    if (aboValue) {
        // Define the URL for the AJAX request
        var shopIoUrl = window.location.origin + '/io.php';

        // Run the AJAX request
        $.ajax({
            type: 'POST',
            url: shopIoUrl,
            data: {
                'io': JSON.stringify(
                    {
                        'name': 'abocompleteorder', 
                        'params': [{'orderid': aboValue}]  
                    }
                ),
            },
            dataType: 'json'
        }).done(function(data){
      

           localStorage.removeItem('subscription_interval');
           localStorage.removeItem('subscription_coupon');
          
           if (data) {
            console.log('Abo Created successfully');
            $('#content-wrapper #content').html('<div class="order-compltersec container ">Abo erfolgreich erstellt</div>'); 
        } else {
            $('#content-wrapper #content').html('<div class="container ">Ich habe auch einen Fehler</div>'); 
        }
       
         
         
       });
            }
    



    var localStorageValue = localStorage.getItem('subscription_interval');
    
        // Check if the element exists on the page
        if ($('.basket .cart-summary  .basket-heading').length) {
            // Retrieve the value from localStorage
           
            var newDiv = $('<div class="abo-order-heading h2">Abo Bestellübersicht</div>');
            // If the localStorageValue exists, append a new div before each '.basket-heading'
            if (localStorageValue) {
                // Loop through each element with the class 'basket-heading'
                $('.basket .cart-summary  .basket-heading').before(newDiv);
                $('.basket .cart-summary  .basket-heading').hide();
               
            }
        }

    
        $('.product_page_body #add-to-cart button').click(function(e){
            e.preventDefault();
        if($(this).hasClass('abo-1') && $(this).attr('name') === 'inWarenkorb'){
            var modalHtml = `
            <div class="modal-overlay" id="custom-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;z-index:9999;">
                <div class="modal-content" style="background: white; padding: 20px; border-radius: 8px; width: 300px; position: relative;">
                    <span class="modal-close" style="position: absolute; top: 10px; right: 10px; cursor: pointer;">&times;</span>
                    <h2 class="modal-header">Produkt ist bereits ohne Abo im Warenkorb</h2>
                    <p class="modal-body">Falls du dieses Produkt in den Warenkorb hinzufügen möchtest, müssen wir erstmal deinen Warenkorb leeren.</p>
                    <div style="text-align: center; margin-top: 20px;">
                        <button id="modal-yes" style="margin-right: 10px;">Ja</button>
                        <button id="modal-no">Nein</button>
                    </div>
                </div>
            </div>
        `;
        
        if (!localStorageValue) {
            aboaddtocart(modalHtml);
        }else{
            $('#buy_form').submit();
        }
        }else if($(this).hasClass('abo-0') && $(this).attr('name') === 'inWarenkorb'){
            var modalHtml = `
            <div class="modal-overlay" id="custom-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;z-index:9999;">
                <div class="modal-content" style="background: white; padding: 20px; border-radius: 8px; width: 300px; position: relative;">
                    <span class="modal-close" style="position: absolute; top: 10px; right: 10px; cursor: pointer;">&times;</span>
                    <h2 class="modal-header">Produkt ist bereits als Abo im Warenkorb</h2>
                    <p class="modal-body">Falls du dieses Produkt in den Warenkorb hinzufügen möchtest, müssen wir erstmal deinen Warenkorb leeren.</p>
                    <div style="text-align: center; margin-top: 20px;">
                        <button id="modal-yes" style="margin-right: 10px;">Ja</button>
                        <button id="modal-no">Nein</button>
                    </div>
                </div>
            </div>
        `;
        
        if (localStorageValue !== '' && localStorageValue !== null) {
            localStorage.removeItem('subscription_interval');
            localStorage.removeItem('subscription_coupon');
            aboaddtocart(modalHtml);
        }else{
            $('#buy_form').submit();
        }
        } 
         
        });
        function aboaddtocart(modalHtml){
           
                // Create the modal HTML
             if($(".head_cart .dropdown-cart-items tr").length > 0){
                $('body').append(modalHtml);
                $('#custom-modal').fadeIn();
        
                $('.modal-close, #modal-no').click(function() {
                    $('#custom-modal').fadeOut(function() {
                        $(this).remove();
                    });
                });
        
        $('#modal-yes').click(function() {
            // Iterate over each cart item and click the remove button
            $(".head_cart .dropdown-cart-items tr").each(function() {
                $(this).find('.cart-items-delete-button').trigger('click');
            });
        
            // Wait a moment to ensure the cart is emptied, then submit the add to cart form
            setTimeout(function() {
                $('#buy_form').submit(); // Submit the form with ID 'buy_form'
            }, 1000); // Adjust timeout if necessary based on your environment
        
            $('#custom-modal').fadeOut(function() {
                $(this).remove();
            });
           
        });
             }else{
                $('#buy_form').submit();
             }
                  
            
        }
        


// Check if the subscription interval has a value
if (localStorageValue) {
    // Show only banktransfer, Mollie, Paypal
    $('#kPlugin_7_banktransfer').show();
    $('#kPlugin_7_mollie').show();
    
    // Check if PayPal div exists and show it
    var paypalElement = $('#kPlugin_7_paypal');
    if (paypalElement.length) {
        paypalElement.show();
    }

    // Hide other payment methods (for example, Klarna)
    $('#kPlugin_7_klarna').hide();
}

 
    // Add a click event listener to the rows with the class 'clickable-row'
    $(document).on('click','.abo_clickrow', function() {
       
        // Get the value of the 'data-href' attribute
        var url = $(this).data('href');
        
        // Redirect the user to the URL
        window.location.href = url;
    });



    

// Check if the purchase type element exists
if ($('.select_purchasetype').length > 0) {

    // Get the subscription interval from localStorage
    var subscriptionInterval = localStorage.getItem('subscription_interval');

    // Check if subscriptionInterval is empty, null, or undefined
    if (!subscriptionInterval) {
        // If subscriptionInterval is removed or empty, select "One-time Purchase"
        $('#one-timeradio').prop('checked', true);
    } 
    // If subscriptionInterval is available, select "Subscribe" and match the dropdown
    else 
    {
        if ($('.frequency_number').length > 0) {

        $('.frequency_number .interval_num').text(subscriptionInterval);

        }
        $('#subscriberadio').prop('checked', true);
        $('select[name="subscription_frequency"]').prop("disabled", true);  
        // Loop through the dropdown options to select the matching interval
        $('select[name="subscription_frequency"] option').each(function() {
            if ($(this).val() === subscriptionInterval) {
                $(this).prop('selected', true);
            }
        });
    }
}



var currentUrl = window.location.href;
if (currentUrl.includes("Bestellvorgang")) {
if ($('#kupon').length > 0) {

    // Check if the coupon has already been applied
    if (localStorage.getItem('coupon_applied') == 'false') {
        // Get the coupon code from localStorage
        var couponCode = localStorage.getItem('subscription_coupon');

        // Check if the coupon code exists
        if (couponCode) {
            // Set the coupon code into the input field
            $('#kupon').val(couponCode);

            // Submit the form automatically
            $('.coupon-form').submit();

            // Mark the coupon as applied in localStorage
            localStorage.setItem('coupon_applied', 'true');

            return false; // Prevent any further action
        }
    }
}
}


// Function to toggle the interval_dropdown visibility
function toggleIntervalDropdown() {
if ($('#subscriberadio').is(':checked')) {
    $('.interval_dropdown').show();
} else {
    $('.interval_dropdown').hide();
}
}

// Initial call to set the correct state on page load
toggleIntervalDropdown();

// Attach event listener for changes on the radio buttons
$('input[name="typeofpurchase"]').change(function() {
toggleIntervalDropdown();
});



// Store selected interval and coupon in local storage on button click
$('.product_page_body #add-to-cart button').click(function() {
   
    localStorage.setItem('coupon_applied', 'false');
    
    var selectedOption = $('select[name="subscription_frequency"] option:selected');
    var intervalValue = selectedOption.val();
    var couponValue = selectedOption.data('coupon');
    
    // Check if a variation is selected
    var variationSelected = $('.form-row.swatches input[type="radio"]:checked').length > 0;

    // If subscription is selected
    if ($('#subscriberadio').is(':checked')) {

        // Ensure a variation is selected if there are variations
        if ($('.form-row.swatches input[type="radio"]').length > 0 && !variationSelected) {
           alert('Please select a product variation.');
          
        }else {
        if (intervalValue !== '-' && couponValue) {
            // Adjust interval based on the provided rules
            var adjustedDays = '0';
            if(intervalValue == '7 Tage'){
                adjustedDays = '5 days';
            } else if(intervalValue == '14 Tage'){
                adjustedDays = '12 days';
            } else if(intervalValue == '30 Tage'){
                adjustedDays = '28 days';
            }

            localStorage.setItem('subscription_interval', adjustedDays);
            localStorage.setItem('subscription_coupon', couponValue);
            
            console.log('Interval and coupon saved to local storage.');
       
        } else {
            console.log('Please select a valid interval and coupon.');
            return false;
        }
    }
    }
});



        // Function to create and append loader to body
        function showLoader() {
            // Create overlay div
            var overlay = $('<div id="abooverlay"></div>');
            // Create loader div
            var loader = $('<div id="aboloader"></div>');
            // Append overlay and loader to body
            $("body").append(overlay).append(loader);
            // Show overlay and loader
            $("#abooverlay").show();
            $("#aboloader").show();
        }
    
      // Function to remove loader and overlay from body
      function hideLoader() {
        $("#abooverlay").remove();
        $("#aboloader").remove();
    }
    
    if ($('#abo_details').length) {
    showLoader();
    }
        setTimeout(function() {
            var frequency = localStorage.getItem('subscription_interval');
     
           
    if(localStorage.getItem('subscription_interval')!=''||subscriptionInterval){
        var orderID = $('#abo_details').attr('data-orderid');
        var customerID = $('#abo_details').attr('data-customerid');
        var productsprise = $('#abo_details').attr('data-productsprise');
        var productsid = $('#abo_details').attr('data-productsid');

        if (orderID !== undefined && orderID !== null) {
            var orderID = orderID.replace('TEST', '');
        } 
    
        if ($('#abo_details').length) {
           
        // Prepare data to send
        var dataToSend = [];
    
            dataToSend.push({
                Product_ID: productsid,
                Order_ID: orderID,
                Customer_ID: customerID,
                Start_date: new Date().toISOString().slice(0, 19).replace('T', ' '),
                Frequency: frequency,
                Discounted_price: productsprise, // Adjust based on your pricing logic
                CustomerID_Mollie: 'MollieCustomerID', // Retrieve Mollie Customer ID as needed
                OrderID_Mollie: 'MollieOrderID' // Retrieve Mollie Order ID as needed
            });
        
            var shopIoUrl = window.location.origin+'/io.php';
      
        // Send data to the custom PHP script
        if (dataToSend) {
            $.ajax({
            type: 'POST',
            url: shopIoUrl,
            data: {
                'io': JSON.stringify(
                    {
                        'name': 'lssubscription_mollie', 
                        'params': [{'subscriptions': JSON.stringify(dataToSend)}]  
                    }
                ),
            }
        }).done(function(data){
             var jsonResponse = JSON.parse(data);
            hideLoader();
            localStorage.removeItem('subscription_interval');
            localStorage.removeItem('subscription_coupon');
            localStorage.removeItem('coupon_applied');
            $('select[name="subscription_frequency"]').prop("disabled", false);  
             console.log(data);
            if(jsonResponse.status==='success'){
                // Correctly target the first container within .order-compltersec
              $('body .order-compltersec .container').eq(0).find('h2').html(jsonResponse.aboheading);
                $('body #order-confirmation .order-confirmation-note').html(jsonResponse.ordernote); 
            }else{
               $('.order-compltersec').html('<div class="container ">'+jsonResponse.message+'</div>'); 
            }
          
          
        });
    
        }
    
    
    }
    }
    }, 7000); // 3000 milliseconds = 3 seconds
    
    
    //unsubscription
    
    $('.abbestellen').click(function(e){
        e.preventDefault();
        var userConfirmed = confirm('Are you sure you want to remove Abo?');
        var  orderidtodelete = $(this).attr('data-orderid');
        if (userConfirmed) {
        var shopIoUrl = window.location.origin+'/io.php';
       
    // Send data to the custom PHP script
    
        $.ajax({
        type: 'POST',
        url: shopIoUrl,
        data: {
            'io': JSON.stringify(
                {
                    'name': 'removeabo', 
                    'params': [{'orderid': orderidtodelete}]  
                }
            ),
        }
    }).done(function(data){
    
        // console.log(data); // Check what is being returned
        if (data) {
            console.log('Abo removed successfully');
            window.location.href = '/Mein-Konto'; // Redirect if successful
        } else {
            alert('There is an error while removing!');
        }
   
    });
        }
    });
    
    
    if ($('#logged_userid').length) {
           
      var  userid = $('#logged_userid').val();
      var  urlbestellung = $('#logged_userid').attr('data-url');
        
            var shopIoUrl = window.location.origin+'/io.php';
      
        // Send data to the custom PHP script
       
            $.ajax({
            type: 'POST',
            url: shopIoUrl,
            data: {
                'io': JSON.stringify(
                    {
                        'name': 'lslogged_user', 
                        'params': [{'logged_userID': userid, 'urlbestellung':urlbestellung }]  
                    }
                ),
            }
        }).done(function(data){
            
           
       
    // Iterate over the data array and create table rows
    $.each(data, function(index, row) {
        // Construct the row HTML
        var tr = '<tr title="" class="abo_clickrow cursor-pointer" data-toggle="tooltip" data-placement="top" data-boundary="window" data-href="'+row.url+''+row.Ordernumber+'" data-original-title="Bestellung anzeigen: Bestellnummer TEST'+row.Order_ID+'">';
        tr += '<td>' + row.Start_date + '</td>';
        tr += '<td>' + row.Order_ID + '</td>';
        tr += '<td>' + row.NextStart_date + '</td>';
        tr += '<td>' + row.Status + '</td>';
        tr += '<td class="text-right-util d-none d-md-table-cell"><i class="fa fa-eye"></i></td>';
        tr += '</tr>';
    
        // Append the row to the table body
        $('.abo_orders tbody').append(tr);
    });
       
          
        });
    
        
    
    
    }

});
