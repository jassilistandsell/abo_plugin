
{assign var="data" value=$orderdata[0]}
               <!-------------------- Greetings Starts here ---------------------------------->
               <table width="600" border="0" cellspacing="0" cellpadding="0" align="center" class="devicewidth">
                  <tr>
                     <td>
                        <table width="100%" bgcolor="#ffffff" border="0" cellspacing="0" cellpadding="0" align="center" class="full" style="text-align:center;">
                        
                           <tr>
                             <!-- customer name -->
                              <td style="font:700 30px Lato, sans-serif; color:#000;">
                                 Hallo {$data.cVorname} <br>
                              </td>
                           </tr>
                         
                           <tr>
                             <!-- customer name -->
                              <td style="font:bold 18px Lato, sans-serif; color:#000;">
                                 Thank you for your order. We are pleased to inform you that your order {$data.cBestellNr} is currently in process.
                              </td>
                           </tr>
                         
                           <tr>
                             <!-- order no. name -->
                             <td style="font:16px Lato, sans-serif; color:#3a3a3a;background: #faf2e7; padding: 20px 8px;">
                               As you are paying abo via credit card first time. So you need to pay 1 euro just to start abo.<br>
                               <br>
							  <h3>Click on button to pay 1 euro.</h3><br>
                               <br>
							   <a href="{$data.checkoutlink}" style="cursor: pointer; 
                                                                                        font:20px Lato, sans-serif;
                                                                                        color: #fff;
                                                                                        background-color: #98846a;
                                                                                        border-color: #927e64;
                                                                                        box-shadow: 0 0 0 0 rgba(155, 141, 122, 0.5);
                                                                                            display: inline-block;
                                                                                            border: 1px solid transparent;
                                                                                              padding: 0.625rem 0.9375rem;
                                                                                              line-height: 1.5;
                                                                                              border-radius: 0.125rem;
                                                                                            text-decoration: none;
   																							 transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;">
							   Complete Abo Order</a>
                            </td>
                           </tr>
                          
                        </table>
                     </td>
                  </tr>
               </table>
               <!-------------------- Greetings ends here---------------------------------->

            
               <table width="600" border="0" cellspacing="0" cellpadding="0" align="center" class="devicewidth">
				<tr>
				
				</tr>
			   </table>

               <!------------------ ORDER FOOTER TABLE STARTS---------->
               <table width="600" border="0" cellspacing="0" cellpadding="0" align="center" class="devicewidth" style="background-color: #ffffff;">
                  <tr>
                     <td>
                        <table width="100%" bgcolor="#ffffff" border="0" cellspacing="0" cellpadding="0" vertical-align="top" align="center" class="full" style="font-family:Lato, sans-serif; vertical-align: top; padding:13px; text-align:left;">
                           <tr>
                              <td style="font-size:14px; color:#404040;">
                                 Über den weiteren Verlauf Ihrer Bestellung werden wir Sie jeweils gesondert informieren.
                              </td>
                           </tr>
                           <tr><td height="20px"></td></tr>
                           <tr>
                              <td style="font-weight:700; font-size:14px; color:#3a3a3a;"> 
                                 Mit freundlichem Gruß
                              </td>
                           </tr>
                           <tr>
                              <td style="font-size:14px; color:#3a3a3a;">
                                Ihr Team von Deine Futterwelt 
                              </td>
                           </tr>
                        </table>
                     </td>
                     <td style="background-color: #ffffff; border-bottom:1px solid #e5e5e5; padding:8px">
                        <center>     
                      
                        </center>  
                     </td>
                  </tr>
               </table>
               <!------------------ ORDER FOOTER TABLE ENDS---------->


<!-------------------------------------------- ORDER CONFIRMATION ends here---------------->

