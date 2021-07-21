# payright-magento2
PayRight "payment method" plugin for Magento v2.x.x platform. 

Please follow the steps below for installation and configuration setup.

## Installation
This section outlines the steps to install the Payright plugin for the first time.

>  [MAGENTO] refers to the installed Magento file directory. For example, `/var/www/magento2`.

#### Requirements
+ Access Token - A 'sandbox access token', or 'production access token'.

> Create a Payright Developer account at Payright Developer Portal (https://developers.payright.com.au).
> Enter e-mail address to sign up. Use the received sign-in e-mail, with unique login link to authenticate.

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
   
> Tip: If `php bin/magento cache:clear` is causing issues, instead "disable cache" and "re-enable cache" 
> at the end of CLI installation/update of modules.
> 
> For example:
> ``` 
> php bin/magento cache:disable
> php bin/magento setup:upgrade
> php bin/magento setup:static-content:deploy
> php bin/magento cache:enable
> ```

### Payright Plugin 

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

#### Optional Configurations

1. Login to Magento Admin and navigate to **Stores** > **Configuration** > **Sales** > **Payment Methods** > **Payright**.
1. Configure the display of the Payright installments details on Product Pages (individual product display pages).
1. Enter a **Minimum Amount** to display the installments.
