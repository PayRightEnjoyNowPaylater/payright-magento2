# PayRight-Magento2
PayRight Extension for Magento 2

# 1.1 New Payright Installation
This section outlines the steps to install the Payright plugin for the first time.

Note: [MAGENTO] refers to the root folder where Magento is installed.

1. Download the Magento-Payright plugin - Available as a .zip or tar.gz file from the Payright GitHub directory.
2. Unzip the file
3. Copy the 'Payright' folder to: 
[MAGENTO]/app/code/
4. Open Command Line Interface
5. In CLI, run the below command to enable Payright module:  
 php bin/magento module:enable Payright_Payright
6. In CLI, run the Magento setup upgrade:  
 php bin/magento setup:upgrade
7. In CLI, run the Magento Dependencies Injection Compile:  
 php bin/magento setup:di:compile
8. In CLI, run the Magento Static Content deployment:  
 php bin/magento setup:static-content:deploy
9. Login to Magento Admin and navigate to System/Cache Management
10. Flush the cache storage by selecting Flush Cache Storage

# 1.2	Website Configuration
Payright operates under an assumptions based on Magento configurations. To align with these assumptions, the Magento configurations must reflect the below.

1. Website Currency must be set to AUD
Navigate to Magento Admin/System/Configuration/Currency Setup Set the base, display and allowed currency to AUD.

# 1.3	Payright Merchant Setup
Complete the below steps to configure the merchant’s Payright Merchant Credentials in Magento Admin.

Note: Prerequisite for this section is to obtain a Payright Merchant Username, Merchant Password, Client Username, Client Password and an Api Key from Payright.

1. Navigate to Magento Admin/Stores/Configuration/Sales/Payment Methods/Payright
2. Enter the Username, Password and API key.
3. Enter the Merchant Name and Merchant Password.
4. Enable Payright plugin using the Enabled checkbox.
5. Configure the Payright API Mode (Sandbox Mode for testing on a staging instance and Production Mode for a live website and legitimate transactions).
6. Save the configuration.

# 1.4	Payright Display Configuration

1. Navigate to System/Configuration/Sales/Payright
2. Configure the display of the Payright installments details on Product Pages (individual product display pages) and Category  Pages (the listing of products, which would also include Search Pages).
3. Enter a Minimum amount to display the installments.
4. Login to Magento Admin and navigate to System/Cache Management.
5. Flush the cache storage by selecting Flush Cache Storage
