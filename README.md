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

## Installation:
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
4. You will need a Config.php with this details:
```
<?php
return [
	"host" => "localhost",
	"username" => "cilo",
	"password" => "cilo2019",
	"database" => "kra_signer",

	"unleashed_api" => "https://api.unleashedsoftware.com/",
	"unleashed_api_id" => "d423e2fe-a575-4f9e-abe0-3154376ce090",
	"unleashed_api_key" => "YAeCbF4cCajaiDyGzeQhbSZzbT5uQRIl3ni3y0HJiw6JW3KMCNOFrMP5opFPqkz0Ssxshx33vcOs3NYFQ==",

	"esd_api" => "http://172.16.24.44:5000/EsdApi/deononline/",

	"smtp_server" => "smtp.gmail.com",
	"email_username" => "migayicecil@gmail.com",
	"email_password" => "pfplxbsufsaayjio",
	"port" => 587,
	"from" => "migayicecil@gmail.com",
];
```
