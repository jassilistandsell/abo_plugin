<?php
// Include your database connection
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the user wants to edit or remove
    if (isset($_POST['edit'])) {
        // Handle the edit action
        $id = intval($_POST['edit']); // ID of the row to edit

        // Collect the updated data for this row
        $order_id = $_POST['Order_ID'][$id];
        $customer_id = $_POST['Customer_ID'][$id];
        $start_date = $_POST['Start_date'][$id];
        $next_start_date = $_POST['NextStart_date'][$id];
        $frequency = $_POST['Frequency'][$id];
        $discounted_price = $_POST['Discounted_price'][$id];

        // Update the row in the database
        $query = "UPDATE KAbbo 
                  SET Order_ID = ?, Customer_ID = ?, Start_date = ?, NextStart_date = ?, Frequency = ?, Discounted_price = ? 
                  WHERE ID = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id, $customer_id, $start_date, $next_start_date, $frequency, $discounted_price, $id]);

        // Redirect back to the table page
        header('Location: your_table_page.php');
        exit();
    } elseif (isset($_POST['remove'])) {
        // Handle the remove action
        $id = intval($_POST['remove']); // ID of the row to remove

        // Delete the row from the database
        $query = "DELETE FROM KAbbo WHERE ID = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        // Redirect back to the table page
        header('Location: your_table_page.php');
        exit();
    }
}
?>
