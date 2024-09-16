<?php declare(strict_types=1);

namespace Plugin\Abo_Mollie;

use JTL\Alert\Alert;
use JTL\Cache\JTLCacheInterface;
use JTL\Catalog\Category\Kategorie;
use JTL\Catalog\Category\KategorieListe;
use JTL\Session\Frontend;
use JTL\Catalog\Product\Artikel;
use JTL\Catalog\Product\ArtikelListe;
use JTL\Cart\CartHelper; 
use JTL\Filter\ProductFilter;
use JTL\Filter\Config;
use JTL\Consent\Item;
use JTL\Events\Dispatcher;
use JTL\Events\Event;
use JTL\Helpers\Form; 
use JTL\Helpers\Request;
use JTL\Link\LinkInterface;
use JTL\Plugin\Bootstrapper;
use JTL\DB\DbInterface;
use JTL\Shop;
use JTL\Helpers\Text;
use JTL\Mail\Mail\Mail;
use JTL\Mail\Mailer;
use JTL\Language\LanguageHelper;
use JTL\Smarty\JTLSmarty;
use JTL\Checkout\Kupon;
use JTL\Smarty\ContextType;
use JTL\Checkout\Bestellung;
use Smarty_Internal_Template;
use stdClass;
use Smarty;


/**
 * Class Bootstrap 
 * @package Abbo_Mollie
 */
class Bootstrap extends Bootstrapper
{
    /**
     * @var TestHelper
     */
    private $helper;
    /**
     * @var JTLSmarty
     */
    private $jsmarty;

    /**
     * @inheritdoc
     */
    public function boot(Dispatcher $dispatcher)
    {
        parent::boot($dispatcher);

        if (Shop::isFrontend() === false) {
            return;
        }
        $plugin       = $this->getPlugin();
        // $this->helper = new TestHelper(
        //     $plugin,
        //     $this->getDB(),
        //     $this->getCache()
        // );
        /*--------------------------- HOOK_SMARTY_OUTPUTFILTER --------------------------------------------*/
        $dispatcher->listen(
            'shop.hook.' . \HOOK_SMARTY_OUTPUTFILTER ,  function (array &$args) use ($plugin) {
                $this->addtimeinterval($args);
                $this->addabodetails($args);
                $this->abo_accounttable($args);
                $this->abo_allorders($args);
                $this->abo_pushnote($args);
            
          
          
        });

        

        /*--------------------------- AJAX IO REQUESTS --------------------------------------------*/
        $dispatcher->listen(
            'shop.hook.' . \HOOK_IO_HANDLE_REQUEST , function (array &$args){
            $args['io']->register('lssaveabbo', [$this, 'lssaveabbo']);
            $args['io']->register('lssubscription_mollie', [$this, 'lssubscription_mollie']);
            $args['io']->register('lslogged_user', [$this, 'lslogged_user']);
            $args['io']->register('removeabo', [$this, 'removeabo']);
            $args['io']->register('abocompleteorder', [$this, 'abocompleteorder']);
          
        });

    }


    public function  abo_pushnote(){
        $intervaltext = Shop::Lang()->get('interval', 'Abo_Mollie');
        $discounttext = Shop::Lang()->get('abo_discount_text', 'Abo_Mollie');


        $discountelement = '<div class="abo_discount_div">'.$discounttext.'</div>';
        $discounttxt = pq('body .productbox-inner .pushed-success-details-wrapper');
        $target = $discounttxt->find('.pushed-success-buttons');
        // Check if .pushed-success-buttons exists in the selected context
        $target->before($discountelement);


        $internaltxt = pq('body .productbox-inner .pushed-success-details-wrapper .form-row');
        $intervaltelement = '<dd class="col-12 frequency_number" ><b>'.$intervaltext.':</b> <span class="interval_num">1 Week</span></dd>';
        
        $internaltxt->append($intervaltelement);
     
      
       
    }

    public function  abo_allorders($args){
     $suburl = $_GET['abo'];
     $plugin = $this->getPlugin();
     $smarty = Shop::Smarty();
    
     if($suburl=='1'){
        $abotablecheck = $this->abotablecheck();
    
        // Assign abodetails to Smarty
        $smarty->assign('abotablecheck', $abotablecheck);
        $smarty->assign('suburl', $suburl);
        // Render the 'ls_abo_details.tpl' template with the abodetails
         $aboorders = $smarty->fetch($plugin->getPaths()->getFrontendPath() . 'template/ls/ls_orderdetails.tpl');

        // Append to account
        $colDiv = pq('body #account > .col');
        $colDiv->html($aboorders);
     }   
    }



    public function abocompleteorder($params) {
        $db = Shop::Container()->getDB();
        $settings = $db->getObjects("SELECT ID, value FROM xplugin_ws5_mollie_plugin_settings WHERE ID IN ('apiKey', 'test_apiKey', 'testAsAdmin')");
 
         $orderid= $params['orderid'];
         
         // Extract the numeric part from the Order_ID
        $orderNumber = preg_replace('/[^0-9]/', '',$orderid); // Removes non-numeric characters
        // Initialize variables to hold the API keys and testAsAdmin value
        $apiKey = '';
        $testApiKey = '';
        $testAsAdmin = false;
    
        // Loop through the settings to assign values to respective variables
        foreach ($settings as $setting) {
        switch ($setting->ID) {
        case 'apiKey':
            $apiKey = $setting->value;
            break;
        case 'test_apiKey':
            $testApiKey = $setting->value;
            break;
        case 'testAsAdmin':
            $testAsAdmin = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            break;
        }
        }
      
        // Determine the API key to use based on testAsAdmin value
        $selectedApiKey = $testAsAdmin ? $testApiKey : $apiKey;
        $apiKey = $selectedApiKey;


        $query = 'SELECT * FROM KAbbo WHERE  Order_ID ='.$orderNumber;
        $result = $db->getObjects($query);
        if (!empty($result)) {
            $kabodata = $result[0];
            $customerIdmollie = $kabodata->CustomerID_Mollie;
            $priceD = $kabodata->Discounted_price;
            $interval = $kabodata->Frequency;
            $startDate = $kabodata->Start_date;
            $description = $kabodata->Frequency.' Abo '.$orderNumber;
            $customerID = $kabodata->Customer_ID;
            $OrderIDD = $orderNumber;
            $amount = array('currency' => 'EUR', 'value' => $priceD);

        }
     $this->createSubscription($apiKey, $customerIdmollie, $amount, $interval, $startDate, $description, $db,$customerID, $OrderIDD);


    }

    
    public function getPluginConfig($params = null){
        return $plugin->getConfig();
    }


    
    //get abo order details on account page
    public function lslogged_user($params){
      
        $userid = $params['logged_userID'];
        $url = $params['urlbestellung'];
         $db = Shop::Container()->getDB();
   
         // Fetch existing frequencies and coupons from the database
         $query = 'SELECT * FROM KAbbo where Customer_ID = '. $userid. ' AND Status = "true"';
         $result = $db->getObjects($query);
         $orderdata_abo = [];
         $smarty = Shop::Smarty();
 
         foreach ($result as $entry) {
           $orderid = $db->getObjects("SELECT kBestellung FROM tbestellung WHERE cBestellNr  LIKE CONCAT('%', ".$entry->Order_ID.")");
        
          
             
             $orderstatus = $entry->Status;
              if($orderstatus=='true'){
               $orderstatus = 'Active';
              }else{
               $orderstatus = 'Deactivated';
              }
             $orderdata_abo[] = [
                
                 'Order_ID'  => $entry->Order_ID,
                 'Start_date'     => $entry->Start_date,
                 'NextStart_date'     => $entry->NextStart_date,
                 'Status'     => $orderstatus,
                 'Ordernumber' => $orderid[0]->kBestellung,
                 'url' => $url
 
             ];
              }
      
         // Check if the Smarty instance is valid
         if ($smarty !== null) {
             // Assign data to Smarty
       //  $smarty->assign('loginuser', $orderdata_abo);
           return  $orderdata_abo;
          
             // Optionally, log or handle $output if you need to process it further
             // For example, you can return it or print it if needed:
             // echo $output;
         } else {
             error_log('Smarty instance is null.');
         }
         }


    public function abo_accounttable()
    {
        $plugin = $this->getPlugin();
       
        // Assuming $this->getSmarty() correctly retrieves the Smarty instance
        $smarty = Shop::Smarty();
        // $oBestellung = $this->getBestellung();
        // Render the 'abodetail.tpl' template with the order details

        
        $orderNumber = $_GET['bestellung'] ?? null;
        $db = Shop::Container()->getDB();
        $orderid = $db->getObjects("SELECT cBestellNr  FROM tbestellung WHERE kBestellung  LIKE '".$orderNumber."'");
        if($orderid){
            $cleanedOrderNumber = preg_replace('/\D/', '', $orderid[0]->cBestellNr);
        }
        $suburl = $_GET['abo'];
   
        if ($cleanedOrderNumber) {
            // Fetch abodetails using the modified function
            $aboDetails = $this->abodetails($cleanedOrderNumber);
    
            // Assign abodetails to Smarty
            $smarty->assign('aboDetails', $aboDetails);
    
            // Render the 'ls_abo_details.tpl' template with the abodetails
            $abotable = $smarty->fetch($plugin->getPaths()->getFrontendPath() . 'template/ls/ls_abo_details.tpl');
    
            // Append to .order-details-data
            $colDiv = pq('body .order-details-data');
            $colDiv->append($abotable);
        } else {
                    if($suburl==''){
                        $abotable = $smarty->fetch($plugin->getPaths()->getFrontendPath() . 'template/ls/abotable_account.tpl');
           
                        $colDiv = pq('body #account > .col');
                        $rowDivs = $colDiv->find('.row');
                        
                        // Append to the third row or create a new one
                        if ($rowDivs->length >= 3) {
                            $rowDivs->eq(4)->append($abotable);
                        } else {
                            $newRowDiv = pq('<div class="row"></div>');
                            $newRowDiv->append($abotable);
                            $colDiv->append($newRowDiv);
                        }
                    }
           
           
           
        }

    }



    public function addabodetails()
    {
        $plugin = $this->getPlugin();
       
        // Assuming $this->getSmarty() correctly retrieves the Smarty instance
        $smarty = Shop::Smarty();
        // $oBestellung = $this->getBestellung();
        // Render the 'abodetail.tpl' template with the order details
        $abodetail = $smarty->fetch($plugin->getPaths()->getFrontendPath() . 'template/ls/abodetail.tpl');
    
        // Manipulate the DOM to insert the abodetail HTML into the .basket element
        pq('body .order-compltersec')->append($abodetail);
    }


    /**
     * 
     *  SAVE ABO FREQUENCY IN DADTABASE
     * 
     * 
     */
    public function lssaveabbo($params): array {
        // Initialize the response array
        $response = [
            'success' => false,
            'message' => 'An error occurred'
        ];
    
        // Check if params are provided
        if (isset($params['formdata'])) {
            // Retrieve form data from params
            $coupons = $params['formdata']['coupon'] ?? [];
            $frequencies = $params['formdata']['frequency'] ?? [];
            $ids = $params['formdata']['kFrequency'] ?? [];
    
            try {
                // Initialize database instance
                $db = Shop::Container()->getDB();
    
                // Begin transaction
                $db->beginTransaction();
    
                // Debugging
                error_log("Frequency IDs: " . print_r($ids, true));
                error_log("Frequencies: " . print_r($frequencies, true));
                error_log("Coupons: " . print_r($coupons, true));
    
                // Retrieve existing frequencies from the database
                $existingFrequencies = $db->getObjects('SELECT kFrequency, cFrequency FROM tfrequency');
                $existingFrequenciesMap = [];
                foreach ($existingFrequencies as $row) {
                    $existingFrequenciesMap[$row->kFrequency] = $row->cFrequency;
                }
    
                // Determine the maximum allowed rows
                $maxRows = 3;
                $currentRows = count($existingFrequencies);
                $rowsToAdd = max(0, $maxRows - $currentRows);
    
                // Process each row from the form data
                for ($i = 0; $i < count($frequencies); $i++) {
                    if (!empty($frequencies[$i]) && !empty($coupons[$i])) {
                        $frequency = $frequencies[$i];
                        $coupon = $coupons[$i];
                        $id = $ids[$i] ?? null;
    
                        if ($id && isset($existingFrequenciesMap[$id])) {
                            // Update existing frequency
                            $db->update('tfrequency', 'kFrequency', (int)$id, (object)[
                                'cFrequency' => $frequency,
                                'cFreq_coupon' => $coupon
                            ]);
                            error_log("Updated ID: " . $id . " to Frequency: " . $frequency . ", Coupon: " . $coupon); // Debugging
                        } elseif ($rowsToAdd > 0) {
                            // Insert new frequency if there's space
                            $db->insert('tfrequency', (object)[
                                'cFrequency' => $frequency,
                                'cFreq_coupon' => $coupon
                            ]);
                            error_log("Inserted Frequency: " . $frequency . ", Coupon: " . $coupon); // Debugging
                            $rowsToAdd--; // Decrement the number of rows left to add
                            // Update the map with newly inserted frequency
                            // $newId = $db->getLastInsertId();
                            // $existingFrequenciesMap[$newId] = $frequency;
                        }
                    }
                }
    
                // Commit transaction
                $db->commit();
    
                // Set success response
                $response['success'] = true;
                $response['message'] = 'Data processed successfully';
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollBack();
                error_log("Error: " . $e->getMessage()); // Debugging
                $response['message'] = 'Error: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'No form data received';
        }
    
        return $response;
    }
   

    public function addtimeinterval()
    {
       $db = Shop::Container()->getDB();
       $plugin  = $this->getPlugin();
        // Fetch existing frequencies and coupons from the database
        $query = 'SELECT kFrequency, cFrequency, cFreq_coupon FROM tfrequency';
       $existingFrequencies = $db->getObjects($query);

       error_log(print_r($existingFrequencies, true));

        $frequencies = [];
        if (!empty($existingFrequencies)) {
           foreach ($existingFrequencies as $entry) {
            $frequencies[] = [
                'kFrequency' => $entry->kFrequency,
                'frequency'  => $entry->cFrequency,
                'coupon'     => $entry->cFreq_coupon
            ];
        }
       }
    
        // Debug: Log processed data to check if it's being processed correctly
       error_log(print_r($frequencies, true));


        // Get the Smarty instance
        $smarty = Shop::Smarty();
    
        // Check if the Smarty instance is valid
        if ($smarty !== null) {
            // Assign data to Smarty
          $smarty->assign('frequencies', $frequencies);
          $freq_abo  = $smarty->fetch($plugin->getPaths()->getFrontendPath()  . 'template/ls/frequency_abo.tpl');
            $productinfo = pq('body .product-detail .product-info');
            $target = $productinfo->find('#add-to-cart');
            // Check if .pushed-success-buttons exists in the selected context
            $target->before($freq_abo);
            // pq('body .sub_abbo_weeks')->html($freq_abo); 
         
            // Optionally, log or handle $output if you need to process it further
            // For example, you can return it or print it if needed:
            // echo $output;
        } else {
            error_log('Smarty instance is null.');
        }
    }

    // getting url parameter
   public function suburl($params,$smarty)
   {
       
       $smarty->assign('suburl', $_GET['abo'] ?? '');
      

    
   }

  //unsubscription
  public function removeabo($params)
  {
      $db = Shop::Container()->getDB();
      $settings = $db->getObjects("SELECT ID, value FROM xplugin_ws5_mollie_plugin_settings WHERE ID IN ('apiKey', 'test_apiKey', 'testAsAdmin')");

       $orderidtodelete= $params['orderid'];
       // Extract the numeric part from the Order_ID
      $orderNumber = preg_replace('/[^0-9]/', '', $orderidtodelete); // Removes non-numeric characters
      // Initialize variables to hold the API keys and testAsAdmin value
      $apiKey = '';
      $testApiKey = '';
      $testAsAdmin = false;
  
      // Loop through the settings to assign values to respective variables
      foreach ($settings as $setting) {
      switch ($setting->ID) {
      case 'apiKey':
          $apiKey = $setting->value;
          break;
      case 'test_apiKey':
          $testApiKey = $setting->value;
          break;
      case 'testAsAdmin':
          $testAsAdmin = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
          break;
      }
      }
  
      // Determine the API key to use based on testAsAdmin value
      $selectedApiKey = $testAsAdmin ? $testApiKey : $apiKey;
      $apiKey = $selectedApiKey;
      //remove abo from mollie also
      $query = 'SELECT * FROM KAbbo WHERE  Order_ID ='.$orderNumber;
      $result = $db->getObjects($query);

  
        // Check if results were found
        if (!empty($result)) {
      $customerID =   $result[0]->CustomerID_Mollie;
       $subID =   $result[0]->OrderID_Mollie;
      $curl = curl_init();

      curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.mollie.com/v2/customers/'.$customerID.'/subscriptions/'.$subID,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'DELETE',
      CURLOPT_HTTPHEADER => array(
      'Authorization: Bearer '.$apiKey
      ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
     
    $responseData = json_decode($response, true);
     $responseData = json_decode($response, true);
     if (isset($responseData['status']) && $responseData['status'] === 'canceled') {
 
     // Prepare and execute the delete query
     $db->delete('KAbbo', 'Order_ID',$orderNumber);
    
     return array(['status' => 'success','message' => $responseData['status']]);
    
   
     }

  }

  }



//get orders from kabbo table
   public function abotablecheck()
{
   $db = Shop::Container()->getDB();
   
        // Execute the query and fetch the result
        $resultprefix = $db->getObjects('SELECT cWert FROM teinstellungen WHERE cName = "bestellabschluss_bestellnummer_praefix"');
       // Initialize orderprefix
       $orderprefix = '';
        // Check if a result was found and return the prefix
        if (!empty($resultprefix) && isset($resultprefix[0]->cWert)) {
           $orderprefix = $resultprefix[0]->cWert;
           }

   // Prepare the SQL query to fetch Start_date and NextStart_date from Kabbo based on Order_ID
   $query = 'SELECT * FROM KAbbo ';
   $result = $db->getObjects($query);

     // Check if results were found
     if (!empty($result)) {
        

       // Extract all Order_IDs from the result set
      return  array_map(function($result) use ($orderprefix) {
           return $orderprefix . $result->Order_ID;
       }, $result);
       
       // Assign the Order_IDs to the Smarty variable
     
   }else{
        return null;
   }
}



public function abodetails($orderNumber)
{
    $db = Shop::Container()->getDB();

    // Sanitize the Order Number
    $numericOrderID = preg_replace('/[^0-9]/', '', $orderNumber);

    // Prepare the SQL query to fetch Start_date and NextStart_date from Kabbo based on Order_ID
    $query = 'SELECT Start_date, NextStart_date, Frequency FROM KAbbo WHERE Order_ID =' . intval($numericOrderID);
    $result = $db->getObjects($query);

    // Return the fetched data or null values if not found
    if (!empty($result)) {
        return [
            'startDate' => $result[0]->Start_date,
            'nextStartDate' => $result[0]->NextStart_date,
            'interval' => $result[0]->Frequency
        ];
    } else {
        return [
            'startDate' => null,
            'nextStartDate' => null,
            'interval' => null
        ];
    }
}


    

   // TO create mollie subscription by list and sell
   public function lssubscription_mollie($params){
    $currentdate = date("Y-m-d");
 
   $db = Shop::Container()->getDB();
   $settings = $db->getObjects("SELECT ID, value FROM xplugin_ws5_mollie_plugin_settings WHERE ID IN ('apiKey', 'test_apiKey', 'testAsAdmin')");


   // Initialize variables to hold the API keys and testAsAdmin value
   $apiKey = '';
   $testApiKey = '';
   $testAsAdmin = false;

   // Loop through the settings to assign values to respective variables
   foreach ($settings as $setting) {
   switch ($setting->ID) {
   case 'apiKey':
       $apiKey = $setting->value;
       break;
   case 'test_apiKey':
       $testApiKey = $setting->value;
       break;
   case 'testAsAdmin':
       $testAsAdmin = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
       break;
   }
   }

   // Determine the API key to use based on testAsAdmin value
   $selectedApiKey = $testAsAdmin ? $testApiKey : $apiKey;
   $apiKey = $selectedApiKey;
   $customerIdmollie ;
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $subscriptions = json_decode($params['subscriptions'], true);
      
       if (!empty($subscriptions)) {
           

           if ($db === null) {
               return json_encode(['status' => 'error', 'message' => 'Database connection failed']);
               exit;
           }
           

           
      
       // Extract values from $subscription array
        
          $customerID = (int)$subscriptions[0]['Customer_ID'];
          $orderID = (int)$subscriptions[0]['Order_ID'];

           $cmail = $db->getObjects('SELECT cMail FROM tkunde WHERE  kKunde = '.$customerID);
          $emailId = $cmail[0]->cMail;
           
           $customerIdmollie = $this->getCustomerIdByEmail($emailId, $apiKey);
          
    
          
           $existingEntries = $db->getObjects('SELECT * FROM KAbbo WHERE Order_ID = '.$orderID.' AND Customer_ID = '.$customerID);

       
           $startdate = date('Y-m-d', strtotime($currentdate.'+'.$subscriptions[0]['Frequency']));
               
            $pricestring = $subscriptions[0]['Discounted_price'];
              $pricestring = str_replace('€', '', $pricestring);
              $pricestring = str_replace('EUR', '', $pricestring);
              $pricestring = str_replace(' ', '', $pricestring);
              $priceD = str_replace(',', '.', $pricestring);
             
           if (count($existingEntries) == 0) {
                   // Entry does not exist, proceed with insertion
                       $subscriptionObj = new stdClass();
                       $subscriptionObj->ID = '';
                       
                       $subscriptionObj->Order_ID = $subscriptions[0]['Order_ID'];
                       $subscriptionObj->Customer_ID = $customerID;
                       $subscriptionObj->Start_date = $startdate;
                       $subscriptionObj->Frequency = $subscriptions[0]['Frequency'];
                       $subscriptionObj->Discounted_price = $priceD;
                       
                       $OrderIDD =  $subscriptions[0]['Order_ID'];



   
                   if ($customerIdmollie) {
                                   $mandateIds = $this->findMandate($customerIdmollie, $apiKey);
                           
                               // Check if the 'mandates' array exists and is not empty
                               if (isset($mandateIds['_embedded']['mandates']) && count($mandateIds['_embedded']['mandates']) > 0) {
                                   $latestMandate = $mandateIds['_embedded']['mandates'][0];
                                   $mandateId = $latestMandate['id'];
                                   
                                   if ($mandateId) {
                                   
                                       $amount = array('currency' => 'EUR', 'value' => $priceD);
                                       $interval = $subscriptions[0]['Frequency'];
                                       $startDate = $startdate;
                                       $description = $interval .' Abo '.$subscriptions[0]['Order_ID'];
       
                                   
                                           // Insert the subscription object into the database
                                       $insertId = $db->insert('KAbbo', $subscriptionObj);
                                       if($insertId){
                                   
                                       return $this->createSubscription($apiKey, $customerIdmollie, $amount, $interval, $startDate, $description, $db,$customerID, $OrderIDD);
                                   
                                       }else{
                                           return json_encode(['status' => 'Error','message' => 'Subscription Not Created' ]);
                                           exit;
                                       }
                                   } else {
                                   
                                  $paymentID =  $this->getPaymentID($emailId,$apiKey);
                               
                                  
                         
                                         
                                             //return  $paymentID;
                                             if (isset($paymentID['details']['consumerName']) ) {

                                              $consumerName = $paymentID['details']['consumerName'];                                     
                                               $methodname = $paymentID['method'];
                                               if($methodname=='paypal'){
                                                 
                                                   $consumerAccount =$paymentID['details']['consumerEmail'];
                                                   $consumerBic = $paymentID['details']['paypalBillingAgreementId'];
                                                   $signaturedate = (string)date("Y-m-d");
                                               }else{
                                                   if($methodname=='banktransfer'){
                                                       $methodname =  'directdebit';
                                                   }else{
                                                       $methodname =  'creditcard';
                                                       
                                                   }
                                                  
                                                   $consumerAccount = $paymentID['details']['consumerAccount'];
                                                   $consumerBic = $paymentID['details']['consumerBic'];
                                                   $signaturedate = (string)date("Y-m-d");
                                               }
                                              
                                       
                                               $result = $this->createMandates($apiKey, $methodname, $consumerName, $consumerAccount, $consumerBic, $customerIdmollie, $signaturedate);
                                               
                                               
                                               if ($result['error']) {
                                                   return json_encode(['status' => 'Error','message' => "Failed to create mandate. Error: " . $result['error_message'] . (isset($result['error_code']) ? " (Code: " . $result['error_code'] . ")" : "") ]);
                                               
                                               } else {  
                                                   $amount = array('currency' => 'EUR', 'value' => $priceD);
                                                   $interval = $subscriptions[0]['Frequency'];
                                                   $startDate = $startdate;
                                                   $description = $interval .' Abo '.$subscriptions[0]['Order_ID'];
                       
                                               
                                                       // Insert the subscription object into the database
                                                   $insertId = $db->insert('KAbbo', $subscriptionObj);
                                                   if($insertId){
                                                   
                                                   // Assuming $db is your database connection object 
                                                   return $this->createSubscription($apiKey, $customerIdmollie, $amount, $interval, $startDate, $description, $db,$customerID , $OrderIDD);
                                               
                                                   }else{
                                                       return json_encode(['status' => 'Error','message' => 'Subscription Not Created' ]);
                                                       exit;
                                                   }
                                               
                                               }
                                           
                                           } else {
                                            $subscriptionObj->CustomerID_Mollie = $customerIdmollie;
                                            $subscriptionObj->Status = false;
                                            $insertId = $db->insert('KAbbo', $subscriptionObj);

                                          $mailsentt =  $this->createprocessmail($emailId,$OrderIDD,$apiKey);

                                          $myText = Shop::Lang()->get('aboorderCompletedPost', 'Abo_Mollie');
                                          $aboheading = Shop::Lang()->get('aboprocessordernote', 'Abo_Mollie');

                                         
                                          if($mailsentt){
                                            return json_encode(['status' => 'success', 'message' => 'Subscription updated in the database.','ordernote' => $myText, 'aboheading' => $aboheading]);
                                        }else{
                                            return   json_encode(['status' => 'error' , 'message'=> 'Error in sending mail!' ]);
                                        }
                                       
                                               
                                           }
                       
                                    
                           
                                   }
                               } else {

                                   $paymentID =  $this->getPaymentID($emailId,$apiKey);
                             
                                         //return  $paymentID;
                                           if (isset($paymentID['details']['consumerName'])) {
                                          
                                               $consumerName =$paymentID['details']['consumerName'];
                                               $methodname = $paymentID['method'];
                                               if($methodname=='paypal'){
                                                   $consumerName =$paymentID['details']['consumerName'];
                                                   $consumerAccount = $paymentID['details']['consumerEmail'];
                                                   $consumerBic = $paymentID['details']['paypalBillingAgreementId'];
                                                   $signaturedate = (string)date("Y-m-d");
                                               }else{
                                                   if($methodname=='banktransfer'){
                                                       $methodname =  'directdebit';
                                                   }else{
                                                       $methodname =  'creditcard';
                                                       
                                                   }
                                                   $consumerName = $paymentID['details']['consumerName'];
                                                   $consumerAccount = $paymentID['details']['consumerAccount'];
                                                   $consumerBic = $paymentID['details']['consumerBic'];
                                                   $signaturedate = (string)date("Y-m-d");
                                               }
                                              

                                       
                                               $result = $this->createMandates($apiKey, $methodname, $consumerName, $consumerAccount, $consumerBic, $customerIdmollie, $signaturedate);
                                               
                                               
                                               if ($result['error']) {
                                                   return json_encode(['status' => 'Error','message' => "Failed to create mandate. Error: " . $result['error_message'] . (isset($result['error_code']) ? " (Code: " . $result['error_code'] . ")" : "") ]);
                                               
                                               } else {  
                                                   $amount = array('currency' => 'EUR', 'value' => $priceD);
                                                   $interval = $subscriptions[0]['Frequency'];
                                                   $startDate = $startdate;
                                                   $description = $interval .' Abo '.$subscriptions[0]['Order_ID'];
                       
                                               
                                                       // Insert the subscription object into the database
                                                   $insertId = $db->insert('KAbbo', $subscriptionObj);
                                                   if($insertId){
                                                   
                                                   // Assuming $db is your database connection object 
                                                   return $this->createSubscription($apiKey, $customerIdmollie, $amount, $interval, $startDate, $description, $db,$customerID , $OrderIDD);
                                               
                                                   }else{
                                                       return json_encode(['status' => 'Error','message' => 'Subscription Not Created' ]);
                                                       exit;
                                                   }
                                               
                                               }
                                           
                                           } else {
                                            $subscriptionObj->CustomerID_Mollie = $customerIdmollie;
                                            $subscriptionObj->Status = false;
                                            $insertId = $db->insert('KAbbo', $subscriptionObj);
                                            $mailsentt =  $this->createprocessmail($emailId,$OrderIDD,$apiKey);
                                            $myText = Shop::Lang()->get('aboprocessordernote', 'Abo_Mollie');
                                            $aboheading = Shop::Lang()->get('aboorderCompletedPost', 'Abo_Mollie');
  
                                           
                                            if($mailsentt){
                                              return json_encode(['status' => 'success', 'message' => 'Subscription updated in the database.','ordernote' => $myText, 'aboheading' => $aboheading]);
                                          }else{
                                              return   json_encode(['status' => 'error' , 'message'=> 'Error in sending mail!' ]);
                                          }
                                       
                                           
                                           }
                       
                                       
                                   
                                   }


                           } else {
                                   return json_encode(['status' => 'error', 'message' => 'Customer not found']);
                               
                                   exit;
                               }

               
               //  echo json_encode(['status' => 'success']);



               } else {
                   // Entry already exists, handle accordingly (skip or update)
                   return json_encode(['status' => 'success', 'message' => "Entry Customer_ID $customerID already exists."]);
                   // You can choose to skip or update the existing entry here
               }
           
           
       
       } else {
           return json_encode(['status' => 'error', 'message' => 'No subscriptions found']);
       }
   } else {
       return json_encode(['status' => 'error', 'message' => 'Invalid request method']);
   }

}

// get customer Id from mollie

function getCustomerIdByEmail($email,$apiKey) {
   
   $curl = curl_init();

   curl_setopt_array($curl, array(
       CURLOPT_URL => 'https://api.mollie.com/v2/customers',
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_HTTPHEADER => array(
           'Content-Type: application/json',
           'Authorization: Bearer ' . $apiKey
       ),
   ));

   $response = curl_exec($curl);

   curl_close($curl);

   if (!$response) {
       die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
   }

   $customers = json_decode($response, true);
   $customerId = null; // Initialize customerId to null

   if (isset($customers['error'])) {
       die('API Error: ' . $customers['error']['message']);
   }

   foreach ($customers['_embedded']['customers'] as $customer) {

       
       if($customer['email'] === $email) {
           $customerId = $customer['id'];
   break; // Exit the loop early if a match is found
       }
   }

   if ($customerId === null) {
       // Execute your additional PHP code here if no matching customer was found


       $curl = curl_init();
       
       curl_setopt_array($curl, array(
         CURLOPT_URL => 'https://api.mollie.com/v2/customers',
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'POST',
         CURLOPT_POSTFIELDS => array('name' => 'dummy dev','email' => $email),
         CURLOPT_HTTPHEADER => array(
           'Authorization: Bearer ' . $apiKey
         ),
       ));
       
       $response = curl_exec($curl);
       
       curl_close($curl);
       $customerdata = json_decode($response, true);
       $customerId = $customerdata['id'];
      return  $customerId;
       // Add more code here as needed
   } else {
       

       return  $customerId;
   }
}

//get mandate ID
// Function to find mandates for a customer
function findMandate($customerIdmollie,$apiKey) {
   $curl = curl_init();

   curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.mollie.com/v2/customers/'.$customerIdmollie.'/mandates',
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_ENCODING => '',
   CURLOPT_MAXREDIRS => 10,
   CURLOPT_TIMEOUT => 0,
   CURLOPT_FOLLOWLOCATION => true,
   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
   CURLOPT_CUSTOMREQUEST => 'GET',
   CURLOPT_HTTPHEADER => array(
       'Authorization: Bearer ' . $apiKey
   ),
   ));
   
   $response = curl_exec($curl);

   if ($response === false) {
       die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
   }
   
   curl_close($curl);
   
   // Decode the JSON response
   $responseData = json_decode($response, true);
       return $responseData;


   

}


// Function to create a subscription
function createSubscription($apiKey, $customerIdmollie, $amount, $interval, $startDate, $description, $db, $customerID, $OrderIDD) {
   $curl = curl_init();
  

   $data = array(
       'amount[currency]' => $amount['currency'],
       'amount[value]' => $amount['value'],
       'interval' => $interval,
       'startDate' => $startDate,
       'description' => $description
   );


   

   curl_setopt_array($curl, array(
       CURLOPT_URL => 'https://api.mollie.com/v2/customers/' . $customerIdmollie . '/subscriptions',
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'POST',
       CURLOPT_POSTFIELDS => http_build_query($data),
       CURLOPT_HTTPHEADER => array(
           'Authorization: Bearer ' . $apiKey,
           'Content-Type: application/x-www-form-urlencoded'
       ),
   ));

   $response = curl_exec($curl);
   
   if ($response === false) {
       $error_message = curl_error($curl);
       $error_code = curl_errno($curl);
       curl_close($curl);
      return json_encode(['status' => 'error', 'message' => 'cURL error: ' . $error_message . ' (Code: ' . $error_code . ')']);
      
   }
   
   curl_close($curl);

   // Decode the JSON response
   $responseArray = json_decode($response, true);

   // Check if the subscription was created successfully
   if (isset($responseArray['id'])) {
       // Subscription created successfully, run SQL query
       $subscriptionObjs = new stdClass();
       $subscriptionObjs->CustomerID_Mollie = $responseArray['customerId'];
       $subscriptionObjs->OrderID_Mollie = $responseArray['id'];
       $subscriptionObjs->NextStart_date = $startDate;
       $subscriptionObjs->Status = 'true';

       $primaryKeyColumn = 'Order_ID';
       $primaryKeyValue = $OrderIDD;


       // Check if entry already exists
       $existingEntries = $db->getObjects('SELECT * FROM KAbbo WHERE Order_ID = '.$OrderIDD.' AND Customer_ID = '.$customerID);

       if (count($existingEntries) == 0) {
           // Insert the subscription object into the database
           $insertResult = $db->insert('KAbbo', (array) $subscriptionObjs);
           if ($insertResult) {
               return json_encode(['status' => 'success', 'message' => 'Subscription inserted into the database with ID: ' . $insertResult]);
           } else {
               return json_encode(['status' => 'error', 'message' => 'Failed to insert subscription into the database.']);
           }
       } else {
  
           $tableName = 'KAbbo';
       // Perform the update using the correct method signature
           $updateResult = $db->update($tableName, $primaryKeyColumn, $primaryKeyValue,$subscriptionObjs);
           if ($updateResult) {
            $myText = Shop::Lang()->get('aboorderConfirmationPost', 'Abo_Mollie');
            $aboheading = Shop::Lang()->get('aboorderCompletedPost', 'Abo_Mollie');

               return json_encode(['status' => 'success', 'message' => 'Subscription updated in the database.','ordernote' => $myText, 'aboheading' => $aboheading]);
           } else {
               return json_encode(['status' => 'error', 'message' => 'Failed to update subscription in the database.']);
           }
       }
   } else {
       // Output the error for debugging
       return json_encode(['status' => 'error', 'message' => 'Failed to create subscription: ' ,'mollieresponse' => $response]);
   }
}



function getPaymentID($emailId, $apiKey){
   $curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL =>'https://api.mollie.com/v2/payments/',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
CURLOPT_HTTPHEADER => array(
   'Authorization: Bearer '.$apiKey
),
));

$response = curl_exec($curl);

curl_close($curl);
$searchEmail = $emailId;
// Array to store the details of the latest payment
$latestPaymentDetails = [];
// Decode the JSON response into an associative array
$responseArray = json_decode($response, true);

// Check if the _embedded key exists and contains payments
if (isset($responseArray['_embedded']['payments'])) {
// Extract payments array
$payments = $responseArray['_embedded']['payments'];

// Find the latest payment with the matching billing email
foreach ($payments as $payment) {
   if (isset($payment['details']) && $payment['billingAddress']['email'] === $searchEmail) {
      // Store the details in an array
      $latestPaymentDetails = ['details' => $payment['details'], 'method' => $payment['method'],'emailid' => $payment['billingAddress']['email']];

       // Stop after the first match (latest payment)
       break;
   }
}
} 
// Return the details if found
return !empty($latestPaymentDetails) ? $latestPaymentDetails : null;
}
    
function createMandates($apiKey, $methodname, $consumerName, $consumerAccount, $consumerBic, $customerIdmollie, $signaturedate) {
    // Initialize cURL
    $curl = curl_init();
    if($methodname=='paypal'){
        $data = array(
            'method' => $methodname,
            'consumerName' => $consumerName,
            'consumerEmail' => $consumerAccount,
            'paypalBillingAgreementId' => $consumerBic,
            'signatureDate' => $signaturedate
        );
    }else{
        // Data to be sent in the POST request
    $data = array(
        'method' => $methodname,
        'consumerName' => $consumerName,
        'consumerAccount' => $consumerAccount,
        'consumerBic' => $consumerBic,
        'signatureDate' => $signaturedate
    );
    }
  
 
    
    
    // Set cURL options
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mollie.com/v2/customers/' . $customerIdmollie . '/mandates',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded'
        ),
    ));
    
    // Execute the cURL request and fetch the response
    $response = curl_exec($curl);
    
    // Check for cURL errors
    if ($response === false) {
        $error_message = curl_error($curl);
        $error_code = curl_errno($curl);
        curl_close($curl);
        return array('error' => true, 'error_message' => $error_message, 'error_code' => $error_code);
    }
    
    // Close the cURL session
    curl_close($curl);
    
    // Decode the JSON response into an associative array
    $responsemandate = json_decode($response, true);
    return     $responsemandate;
    // Check if the response contains an 'id'
    if (isset($responsemandate['id'])) {
        return array('error' => false, 'mandate_id' => $responsemandate['id']);
    } else {
        // Return an error if no 'id' found
        return array('error' => true, 'error_message' => 'No mandate ID found in response.');
    }
    }

    function createProcessMail($customerID, $orderID,$apiKey) {
     // Get the Mailer instance
     $mailer = Shop::Container()->get(Mailer::class);
     $db = Shop::Container()->getDB();
    
    // Retrieve customer details using a raw SQL query
 $customers = $db->getObjects('SELECT * FROM tkunde WHERE cMail = ' . $db->quote($customerID));

 $customer = $customers[0]; // Access the first element
 

 // Retrieve order details using a raw SQL query
 $orders = $db->getObjects('SELECT * FROM tbestellung WHERE cBestellNr Like ' . $db->quote($orderID));
 
 $order = $orders[0]; // Access the first element

 $responseData = null;

 $query = 'SELECT * FROM KAbbo WHERE  Order_ID ='.$orderID;
 $result = $db->getObjects($query);
 if (!empty($result)) {
     $kabodata = $result[0];

     $data = [
         'amount' => [
             'currency' => 'EUR',
             'value' => '1.00' // Note: Amount should be a string with two decimals
         ],
         'customerId' => $kabodata->CustomerID_Mollie,
         'sequenceType' => 'first',
         'description' => 'First payment ' . $orderID,
         'redirectUrl' => 'https://deine-futterwelt.de/abo-process?aboorder='.$orderID
     
     ];

     $curl = curl_init('https://api.mollie.com/v2/payments');
     curl_setopt_array($curl, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_POST => true,
         CURLOPT_HTTPHEADER => [
             'Content-Type: application/json',
             'Authorization: Bearer ' . $apiKey
         ],
         CURLOPT_POSTFIELDS => json_encode($data),
     ]);

     $response = curl_exec($curl);
     curl_close($curl);
    
     $responseData = json_decode($response, true);
   
         // Successful response
      $paymentid = $responseData['id'];
      if($paymentid){

         $idWithoutPrefix = str_replace("tr_", "", $paymentid);
     
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mollie.com/v2/payments/'.$paymentid.'/refunds',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('amount[currency]' => 'EUR','amount[value]' => '1.00'),
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer ' . $apiKey
        ),
      ));
      
      curl_exec($curl);
      
      curl_close($curl);
      
     }
 } 
 
     // Prepare email subject and body (HTML)
     $subject = 'Your Order is in Process';
     
     $orderdata = [];

     $orderdata[] = [
         'cBestellNr' => $order->cBestellNr,
         'cVorname'  => $customer->cVorname,
         'checkoutlink' => 'https://www.mollie.com/checkout/credit-card/embedded/'. $idWithoutPrefix
     ];


 // Debug: Log processed data to check if it's being processed correctly
error_log(print_r($orderdata, true));

 // Get the Smarty instance
 $smarty = Shop::Smarty();

// Prepare custom HTML content for the email body
$htmlContent =  $smarty->assign('orderdata', $orderdata)
                ->fetch($plugin->getPaths()->getFrontendPath() . 'template/ls/lsaboprocessmail.tpl');
 // Create a new Mail object
 $mail = new Mail();
 $mail->setToName($customer->cVorname);
 $mail->setToMail($customer->cMail);
 $mail->setSubject($subject);
 $mail->setBodyHTML($htmlContent);
 // $mail->setBodyText($plainTextContent);
 $mail->setLanguage(LanguageHelper::getDefaultLanguage());
 // Send the email
 $result = $mailer->send($mail);
 return $result;
}

}

