# payright-magento2
PayRight payment method plugin for Magento v2.x, follow the steps below for 
configuration setup and installation.

> 26th February 2021
> 
> This plugin is yet 'optimized' for `composer` standards. Hence, the:
> 1. Missing `composer.json` file.
> 1. The plugin folder structure will be changed. From `Payright/Payright/...` to `payright-magento2/...`.
> 
> Below installation instructions currently provide 'manual module installation steps', via copy/paste of files.

## 1.1 Installation
This section outlines the steps to install the Payright plugin for the first time.

>  [MAGENTO] refers to the installed Magento file directory. Such as `/var/www/magento1.9`

#### Requirements
+ Access Token - A 'sandbox access token', or 'production access token'.

#### How to install

1. Download the plugin (available as a .zip or tar.gz file).
2. Unzip the file.
3. Copy the 'Payright' folder to `[MAGENTO]/app/code/`.
4. Open your command-line interface (CLI).
5. In command-line interface (CLI), run the below command(s) to install & enable the Payright module:  
    ``` 
    php bin/magento module:enable Payright_Payright
    php bin/magento setup:upgrade
    php bin/magento setup:static-content:deploy
     
    php bin/magento cache:clear
    ```
   
### 1.2	Payright Plugin 

#### Primary Configuration
Complete the below steps to configure the merchant’s Payright merchant configuration settings in Magento Admin.

1. Login to Magento Admin and navigate to **Stores** > **Configuration** > **Sales** > **Payment Methods** > **Payright**.
1. Enter your store **Access Token**.
1. Select your store **Region** (either Australia or New Zealand).
1. Enable the Payright plugin by selecting 'Yes' from the 'Enabled' field.
1. Configure the Payright API Mode
   1. **Sandbox Mode** for testing on a staging instance.
   1. **Production Mode** for a live website and legitimate transactions.
1. Save the configuration settings.

#### Secondary Configurations

1. Login to Magento Admin and navigate to **Stores** > **Configuration** > **Sales** > **Payment Methods** > **Payright**.
1. Configure the display of the Payright installments details on Product Pages (individual product display pages).
1. Enter a **Minimum Amount** to display the installments.
