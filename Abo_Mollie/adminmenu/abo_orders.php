<?php
// Query to get all records from the KAbbo table
$query = 'SELECT * FROM KAbbo';
$result = $db->getObjects($query);

// Start the HTML table
echo '<table border="1" class="abo_table_bk">
        <thead>
            <tr>
                <th>ID</th>
                <th>Bestell-ID</th>
                <th>Kunden-ID</th>
                <th>Startdatum</th>
                <th>Nächstes Datum</th>
                <th>Intervall</th>
                <th>Preis</th>
                <th>Kunden-ID Mollie</th>
                <th>Bestell-ID Mollie</th>
                <th>Status</th>
               
            </tr>
        </thead>
        <tbody>';

// Loop through each record and create table rows
if ($result) {
    foreach ($result as $row) {
        echo '<tr>
                <td>' . $row->ID . '</td>
                <td>' . $row->Order_ID . '</td>
                <td>' . $row->Customer_ID . '</td>
                <td>' . $row->Start_date . '</td>
                <td>' . $row->NextStart_date . '</td>
                <td>' . $row->Frequency . '</td>
                <td>' . $row->Discounted_price . '</td>
                <td>' . $row->CustomerID_Mollie . '</td>
                <td>' . $row->OrderID_Mollie . '</td>
                <td>' . $row->Status . '</td>
                
              </tr>';
    }
} else {
    echo '<tr><td colspan="11">No orders found</td></tr>';
}

// Close the table
echo '</tbody></table>';




// Add the CSS styling
echo '<style>
    .abo_table_bk {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 18px;
        text-align: left;
    }
    
    .abo_table_bk th, .abo_table_bk td {
        padding: 12px 15px;
        border: 1px solid #ddd;
    }

    .abo_table_bk th {
       background-color: #3c99d4;
    color: #fff;
        text-align: center;
    }

    /* Alternate row colors */
    .abo_table_bk tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .abo_table_bk tr:nth-child(odd) {
        background-color: #ffffff;
    }

    /* Action links styling */
    .abo_table_bk a {
        color: #007bff;
        text-decoration: none;
    }
    
    .abo_table_bk a:hover {
        text-decoration: underline;
    }
</style>';
?>
