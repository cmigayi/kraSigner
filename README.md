# kraSigner
## Agreed Project scope: 

- Integrate Unleashed API to ESD;
- Send the pdf invoice for ESD signing;
- Send the Electronically signed PDF invoices via email to a recipient.

## What has been delivered in alignment with the scope/project objective:

- Fetch sales invoices from Unleashed API.
- Check if invoice has ever been signed (data from Mysql DB)
- Send each invoice for signing to ESD API.
- Generate a QRCode for the KRA signed invoice.
- Generate an HTML template for the signed invoice (Out of scope).
- Convert HTML invoice to PDF file.
- Track invoice status via a Mysql DB (Out of scope).
- Send email and a signed invoice PDF attachment to recipients.

## Access server or AWS environment or your preference
1. SSH
```
ssh -i yourkey.pem serverusername@server-ip
```
2. If you have .ppk, convert by using putty-tools
```
apt-get install putty-tools
puttygen <the_key.ppk> -O private-openssh -o <new_openssh_key>.key
```
## Pre-requisites:
1. Install mysql
```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get purge mysql-server mysql-client
sudo apt-get install mysql-server mysql-client
```
2. Install PHP >= 7
3. Install Composer 

## Installation:
0. Clone from this repo into your prefered server location:
```
git clone https://github.com/cmigayi/kraSigner.git
```
1. Update php composer. Install composer if not available. 
```
composer update
```
2. Load composer to generate relevant files and libraries.
```
composer dump-autoload -o
```
3. Create the following directories in project root:
    - invoices
    - logs
    - tmp
4. Add "Write" permission to each directory
```
sudo chmod o+w invoices logs tmp
``` 	
5. You will need a Config.php with these details:
```
<?php
return [
	"host" => "localhost",
	"username" => "",
	"password" => "",
	"database" => "",

	"unleashed_api" => "",
	"unleashed_api_id" => "",
	"unleashed_api_key" => "",

	"esd_api" => "",

	"smtp_server" => "",
	"email_username" => "",
	"email_password" => "",
	"port" => 587,
	"from" => "",
];
```
6. Create DB in Mysql, then import the .sql file
```
mysql -u user -p databasename < databasename.sql
```  
7. In case of any issues, you can check the server logs. Here is a location for Apache2 logs:
```
sudo ls /var/log/apache2/
```